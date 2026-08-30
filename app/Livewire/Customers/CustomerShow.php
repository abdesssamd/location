<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Services\StoreContext;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CustomerShow extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->authorize('view', $customer);

        $this->customer = $customer->load('rentals');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $hasRentals = Schema::hasTable('rentals');
        $rentals = $hasRentals ? $this->customer->rentals->sortByDesc('created_at') : collect();

        return view('livewire.customers.customer-show', compact('rentals', 'hasRentals'));
    }
}