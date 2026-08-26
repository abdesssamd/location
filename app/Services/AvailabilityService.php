<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RentalItem;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityService
{
    /**
     * Quantité d'unités d'un produit déjà engagées (réservées ou en cours)
     * sur une plage de dates, hors location en cours d'édition.
     */
    public function committedBetween(int $productId, string $start, string $end, ?int $ignoreRentalId = null): int
    {
        return (int) RentalItem::query()
            ->where('product_id', $productId)
            ->whereHas('rental', function (Builder $q) use ($start, $end, $ignoreRentalId) {
                $q->whereIn('status', ['reserved', 'active'])
                    ->where('end_date', '>=', $start)
                    ->where('start_date', '<=', $end);

                if ($ignoreRentalId) {
                    $q->where('id', '!=', $ignoreRentalId);
                }
            })
            ->sum('quantity');
    }

    /**
     * Stock libre d'un produit sur une plage de dates
     * (capacité totale - engagements chevauchants).
     */
    public function freeBetween(Product $product, string $start, string $end, ?int $ignoreRentalId = null): int
    {
        if (in_array($product->status, [Product::STATUS_OFFLINE, Product::STATUS_LOST], true)) {
            return 0;
        }

        return (int) $product->quantity - $this->committedBetween($product->id, $start, $end, $ignoreRentalId);
    }

    public function isAvailable(Product $product, string $start, string $end, int $requiredQty, ?int $ignoreRentalId = null): bool
    {
        return $this->freeBetween($product, $start, $end, $ignoreRentalId) >= $requiredQty;
    }

    /**
     * Liste des locations en conflit pour un produit sur une plage donnée.
     */
    public function conflictsFor(Product $product, string $start, string $end, ?int $ignoreRentalId = null): array
    {
        return RentalItem::query()
            ->where('product_id', $product->id)
            ->whereHas('rental', function (Builder $q) use ($start, $end, $ignoreRentalId) {
                $q->whereIn('status', ['reserved', 'active'])
                    ->where('end_date', '>=', $start)
                    ->where('start_date', '<=', $end);

                if ($ignoreRentalId) {
                    $q->where('id', '!=', $ignoreRentalId);
                }
            })
            ->with('rental.customer')
            ->get()
            ->map(fn (RentalItem $item) => [
                'rental_id' => $item->rental_id,
                'reference' => $item->rental?->reference,
                'customer' => $item->rental?->customer?->full_name,
                'start' => $item->rental?->start_date?->format('d/m/Y'),
                'end' => $item->rental?->end_date?->format('d/m/Y'),
                'quantity' => $item->quantity,
            ])
            ->all();
    }
}
