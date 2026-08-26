<?php

namespace App\Livewire\Rentals;

use App\Models\Rental;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class RentalList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $packFilter = '';
    public string $from = '';
    public string $to = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPackFilter(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $rentals = Rental::query()
            ->with(['customer', 'items'])
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', '%'.$this->search.'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', '%'.$this->search.'%')->orWhere('last_name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%'));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->packFilter === 'with', fn ($q) => $q->whereHas('items', fn ($i) => $i->where('is_pack_component', true)))
            ->when($this->packFilter === 'without', fn ($q) => $q->whereDoesntHave('items', fn ($i) => $i->where('is_pack_component', true)))
            ->when($this->from, fn ($q) => $q->whereDate('start_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('start_date', '<=', $this->to))
            ->latest()
            ->paginate(15);

        $statusLabels = Rental::statusLabels();

        return view('livewire.rentals.rental-list', compact('rentals', 'statusLabels'));
    }
}