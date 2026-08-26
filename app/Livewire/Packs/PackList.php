<?php

namespace App\Livewire\Packs;

use App\Models\Pack;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class PackList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function archive(int $packId): void
    {
        $pack = Pack::findOrFail($packId);
        $old = $pack->getAttributes();
        $pack->update([
            'status' => Pack::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        AuditLogger::updated($pack, $old, 'pack.archived');
        session()->flash('status', 'Pack archivé.');
    }

    public function duplicate(int $packId): void
    {
        $original = Pack::with(['items', 'images'])->findOrFail($packId);

        $copy = $original->replicate();
        $copy->name = $original->name.' (copie)';
        $copy->reference = $this->suggestReference();
        $copy->status = Pack::STATUS_DRAFT;
        $copy->duplicated_from_id = $original->id;
        $copy->archived_at = null;
        $copy->save();

        foreach ($original->items as $item) {
            $copy->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'selection_mode' => $item->selection_mode,
                'variant_hint' => $item->variant_hint,
                'sort_order' => $item->sort_order,
            ]);
        }

        foreach ($original->images as $image) {
            $copy->images()->create([
                'path' => $image->path,
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ]);
        }

        AuditLogger::created($copy, 'pack.duplicated');

        session()->flash('status', 'Pack dupliqué.');
        $this->redirect(route('packs.edit', $copy), navigate: true);
    }

    protected function suggestReference(): string
    {
        $next = (int) Pack::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->count() + 1;

        return sprintf('PACK-%06d', $next);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $packs = Pack::query()
            ->with(['items.product', 'category', 'images'])
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('reference', 'like', '%'.$this->search.'%');
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(12);

        $statuses = Pack::statusLabels();

        return view('livewire.packs.pack-list', compact('packs', 'statuses'));
    }
}
