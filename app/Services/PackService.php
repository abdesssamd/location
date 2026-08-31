<?php

namespace App\Services;

use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;

class PackService
{
    /**
     * @param array<int, int> $selectedProductsByPackItem
     * @return array{is_available: bool, components: array<int, array<string, mixed>>, message: string|null}
     */
    public function availability(Pack $pack, array $selectedProductsByPackItem = [], int $packQuantity = 1, ?string $startDate = null, ?string $endDate = null): array
    {
        $components = [];
        $isAvailable = true;

        $pack->loadMissing('items.product');

        foreach ($pack->items as $packItem) {
            $product = $this->resolveProductForItem(
                $packItem,
                $selectedProductsByPackItem[$packItem->id] ?? null,
                $startDate,
                $endDate
            );
            $requiredQty = max(1, (int) $packItem->quantity) * max(1, $packQuantity);

            $availableQty = $product?->quantity ?? 0;
            if ($startDate && $endDate && $product) {
                $availableQty = app(\App\Services\AvailabilityService::class)->freeBetween($product, $startDate, $endDate);
            }
            $ok = $product && $availableQty >= $requiredQty
                && $product->status !== Product::STATUS_OFFLINE
                && $product->status !== Product::STATUS_LOST;

            // Dire ce qui bloque : « indisponible » sans motif n'aide personne.
            $reason = match (true) {
                $ok => null,
                ! $product && $packItem->category_id !== null => 'aucun article dans cette catégorie pour ce magasin',
                ! $product => 'article introuvable',
                in_array($product->status, [Product::STATUS_OFFLINE, Product::STATUS_LOST], true) => 'article '.($product->status === Product::STATUS_LOST ? 'perdu' : 'hors service'),
                default => $availableQty.' libre(s) sur la période, '.$requiredQty.' requis',
            };

            $components[] = [
                'pack_item_id' => $packItem->id,
                'product_id' => $product?->id,
                'product_name' => $product?->name ?? 'Article introuvable',
                'product_reference' => $product?->reference,
                'required_qty' => $requiredQty,
                'available_qty' => $availableQty,
                'status' => $ok ? 'available' : 'unavailable',
                'reason' => $reason,
            ];

            if (! $ok) {
                $isAvailable = false;
            }
        }

        return [
            'is_available' => $isAvailable,
            'components' => $components,
            'message' => $isAvailable ? null : 'Un ou plusieurs articles du pack ne sont pas disponibles pour cette période.',
        ];
    }

    /**
     * @param array<int, int> $selectedProductsByPackItem
     * @return array<int, array<string, mixed>>
     */
    public function expandToRentalRows(Pack $pack, array $selectedProductsByPackItem = [], int $packQuantity = 1, ?string $startDate = null, ?string $endDate = null): array
    {
        $rows = [];

        $pack->loadMissing('items.product');

        foreach ($pack->items as $packItem) {
            // Mêmes dates que le contrôle de disponibilité : sinon la réservation
            // peut choisir une variante différente de celle annoncée comme libre.
            $product = $this->resolveProductForItem($packItem, $selectedProductsByPackItem[$packItem->id] ?? null, $startDate, $endDate);

            if (! $product) {
                continue;
            }

            $qty = max(1, (int) $packItem->quantity) * max(1, $packQuantity);
            $unit = (int) $product->rental_price;

            $rows[] = [
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $qty * $unit,
                'normal_line_total' => $qty * $unit,
                'is_pack_component' => true,
                'pack_id' => $pack->id,
                'pack_name' => $pack->name,
            ];
        }

        $normalTotal = (int) collect($rows)->sum('line_total');
        $packTargetTotal = $pack->finalPrice() * max(1, $packQuantity);

        if ($normalTotal > 0 && $packTargetTotal >= 0) {
            $allocated = [];
            $used = 0;

            foreach ($rows as $index => $row) {
                $value = (int) floor(((int) $row['line_total'] / $normalTotal) * $packTargetTotal);
                $allocated[$index] = $value;
                $used += $value;
            }

            $remainder = $packTargetTotal - $used;
            $i = 0;
            while ($remainder > 0 && count($rows) > 0) {
                $allocated[$i % count($rows)]++;
                $remainder--;
                $i++;
            }

            foreach ($rows as $index => &$row) {
                $row['line_total'] = max(0, (int) ($allocated[$index] ?? 0));
                $row['unit_price'] = max(0, (int) floor($row['line_total'] / max(1, (int) $row['quantity'])));
            }
        }

        return $rows;
    }

    protected function resolveProductForItem(PackItem $packItem, ?int $selectedProductId, ?string $startDate = null, ?string $endDate = null): ?Product
    {
        if ($selectedProductId) {
            $candidate = Product::find($selectedProductId);
            if ($candidate && $candidate->store_id === $packItem->pack->store_id) {
                return $candidate;
            }
        }

        return $packItem->resolvedProduct($startDate, $endDate);
    }
}
