<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class PurchaseList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Purchase::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'items'])
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', '%'.$this->search.'%')
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', '%'.$this->search.'%'));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->latest()
            ->paginate(15);

        return view('livewire.purchases.purchase-list', [
            'purchases' => $purchases,
            'statusLabels' => Purchase::statusLabels(),
            'totalDue' => (int) Purchase::query()
                ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
                ->where('status', Purchase::STATUS_RECEIVED)
                ->get()
                ->sum('remaining'),
        ]);
    }
}
