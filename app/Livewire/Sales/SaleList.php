<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SaleList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Sale::class);
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
        $sales = Sale::query()
            ->with(['customer', 'items'])
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', '%'.$this->search.'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', '%'.$this->search.'%')->orWhere('last_name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%'));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->latest()
            ->paginate(15);

        return view('livewire.sales.sale-list', [
            'sales' => $sales,
            'statusLabels' => Sale::statusLabels(),
            'totalRevenue' => (int) Sale::query()
                ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
                ->where('status', Sale::STATUS_COMPLETED)
                ->whereDate('date', now()->toDateString())
                ->sum('total'),
        ]);
    }
}
