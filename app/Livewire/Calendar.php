<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Pack;
use App\Models\Product;
use App\Models\Rental;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Calendar extends Component
{
    public string $view = 'timeline';

    /** @var array<int, string> */
    public array $statuses = ['reserved', 'active', 'completed'];

    public ?int $customerId = null;

    /** Article ou pack sélectionné, préfixé "product-{id}" ou "pack-{id}". */
    public ?string $resourceFilter = null;

    public ?int $previewRentalId = null;

    public function updated(): void
    {
        $this->dispatch('calendar-filters-changed');
    }

    public function resetFilters(): void
    {
        $this->statuses = ['reserved', 'active', 'completed'];
        $this->customerId = null;
        $this->resourceFilter = null;
        $this->dispatch('calendar-filters-changed');
    }

    /**
     * Détail d'une location pour l'aperçu rapide (sans quitter le calendrier).
     */
    public function previewRental(int $rentalId): void
    {
        $this->previewRentalId = $rentalId;
    }

    public function closePreview(): void
    {
        $this->previewRentalId = null;
    }

    /** @return array<string, mixed>|null */
    public function getPreviewProperty(): ?array
    {
        if (! $this->previewRentalId) {
            return null;
        }

        $rental = Rental::query()
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->with(['customer', 'items.product', 'items.pack'])
            ->find($this->previewRentalId);

        if (! $rental) {
            return null;
        }

        return [
            'id' => $rental->id,
            'reference' => $rental->reference,
            'status' => $rental->status,
            'status_label' => Rental::statusLabels()[$rental->status] ?? $rental->status,
            'status_badge' => Rental::statusBadge($rental->status),
            'customer_name' => $rental->customer?->full_name ?? 'Client supprimé',
            'customer_phone' => $rental->customer?->phone,
            'start_date' => $rental->start_date->format('d/m/Y'),
            'end_date' => $rental->end_date->format('d/m/Y'),
            'days' => $rental->days,
            'total' => $rental->total,
            'paid_amount' => $rental->paid_amount,
            'remaining' => $rental->remaining,
            'items' => $rental->items->map(fn ($item) => [
                'label' => $item->pack_name ?? $item->product?->name ?? 'Article supprimé',
                'quantity' => $item->quantity,
                'is_pack_component' => $item->is_pack_component,
            ])->all(),
        ];
    }

    /**
     * Ressources (articles + packs) proposées au filtre et à la vue planning.
     * Limitée pour rester lisible : les plus loués d'abord.
     *
     * @return array<int, array{id: string, title: string}>
     */
    protected function resources(): array
    {
        $storeId = StoreContext::id();

        $products = Product::query()
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->withCount('stockMovements')
            ->orderByDesc('stock_movements_count')
            ->limit(60)
            ->get(['id', 'name', 'reference'])
            ->map(fn (Product $p) => ['id' => 'product-'.$p->id, 'title' => $p->name.' ('.$p->reference.')']);

        $packs = Pack::query()
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->where('status', '!=', Pack::STATUS_ARCHIVED)
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'reference'])
            ->map(fn (Pack $p) => ['id' => 'pack-'.$p->id, 'title' => '📦 '.$p->name.' ('.$p->reference.')']);

        return $products->concat($packs)->values()->all();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        [$resourceType, $resourceId] = $this->resourceFilter
            ? explode('-', $this->resourceFilter, 2)
            : [null, null];

        $rentals = Rental::query()
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->with(['customer', 'items'])
            ->when($this->statuses, fn ($q) => $q->whereIn('status', $this->statuses), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($this->customerId, fn ($q) => $q->where('customer_id', $this->customerId))
            ->when($resourceId, function ($q) use ($resourceType, $resourceId) {
                $q->whereHas('items', function ($q) use ($resourceType, $resourceId) {
                    $resourceType === 'pack'
                        ? $q->where('pack_id', $resourceId)
                        : $q->where('product_id', $resourceId)->whereNull('pack_id');
                });
            })
            ->whereDate('end_date', '>=', now()->subMonths(2))
            ->orderBy('start_date')
            ->get();

        $events = $rentals->map(function (Rental $r) {
            $color = match ($r->status) {
                'active' => '#e11d48',
                'reserved' => '#2563eb',
                'completed' => '#16a34a',
                default => '#71717a',
            };

            $resourceIds = $r->items
                ->map(fn ($item) => $item->pack_id ? 'pack-'.$item->pack_id : ($item->product_id ? 'product-'.$item->product_id : null))
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'id' => $r->id,
                'title' => ($r->customer?->full_name ?? 'Client').' — '.$r->reference,
                'start' => $r->start_date->toDateString(),
                'end' => $r->end_date->addDay()->toDateString(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'resourceIds' => $resourceIds,
            ];
        })->all();

        return view('livewire.calendar', [
            'events' => $events,
            'resources' => $this->resources(),
            'customers' => Customer::query()
                ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
            'statusOptions' => Rental::statusLabels(),
            'preview' => $this->preview,
        ]);
    }
}
