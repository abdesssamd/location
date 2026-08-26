<?php

namespace App\Livewire;

use App\Models\Rental;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Calendar extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        $rentals = Rental::query()
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->with(['customer'])
            ->whereIn('status', ['reserved', 'active', 'completed'])
            ->whereDate('end_date', '>=', now()->subMonths(1))
            ->orderBy('start_date')
            ->get();

        $events = $rentals->map(function (Rental $r) {
            $color = match ($r->status) {
                'active' => '#e11d48',
                'reserved' => '#2563eb',
                'completed' => '#16a34a',
                default => '#71717a',
            };

            return [
                'id' => $r->id,
                'title' => ($r->customer?->full_name ?? 'Client').' — '.$r->reference,
                'start' => $r->start_date->toDateString(),
                'end' => $r->end_date->toDateString(),
                'url' => route('rentals.show', $r),
                'backgroundColor' => $color,
                'borderColor' => $color,
            ];
        })->all();

        return view('livewire.calendar', compact('events'));
    }
}
