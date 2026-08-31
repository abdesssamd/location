<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\RentalItem;
use App\Services\StoreContext;
use Illuminate\Console\Command;

/**
 * Répare le parc après le changement de modèle de stock.
 *
 * Avant : chaque réservation décrémentait products.quantity, et le retour le
 * recréditait — l'unité était donc comptée deux fois (parc ET engagement).
 * Depuis le correctif, products.quantity représente le parc total détenu et
 * seule la disponibilité par dates est calculée.
 *
 * Les réservations créées sous l'ancien modèle ont laissé un parc diminué :
 * cette commande le recrédite pour les locations encore en cours.
 */
class StockReconcile extends Command
{
    protected $signature = 'stock:reconcile {--apply : Applique les corrections (sinon simple rapport)} {--store= : Limiter à un magasin}';

    protected $description = 'Recrédite le parc des articles diminué par l\'ancien modèle de stock';

    public function handle(): int
    {
        // Portée plateforme : la commande travaille sur tous les magasins.
        StoreContext::set($this->option('store') ? (int) $this->option('store') : null);

        $products = Product::query()
            ->orderBy('store_id')
            ->orderBy('name')
            ->get();

        $rows = [];
        $toFix = [];

        foreach ($products as $product) {
            // Unités engagées par des locations encore ouvertes : sous l'ancien
            // modèle, elles ont été retirées du parc et jamais restituées.
            $engaged = (int) RentalItem::query()
                ->where('product_id', $product->id)
                ->whereHas('rental', fn ($q) => $q->whereIn('status', ['reserved', 'active']))
                ->sum('quantity');

            $suspect = $engaged > 0 && $product->quantity < $engaged;

            $rows[] = [
                $product->store_id,
                $product->reference,
                mb_strimwidth($product->name, 0, 28, '…'),
                $product->quantity,
                $engaged,
                $suspect ? '<fg=yellow>'.($product->quantity + $engaged).'</>' : '—',
            ];

            if ($suspect) {
                $toFix[$product->id] = $engaged;
            }
        }

        $this->table(
            ['Magasin', 'Référence', 'Article', 'Parc actuel', 'Engagé', 'Parc corrigé'],
            $rows
        );

        // Parc à zéro sans engagement : souvent un article décrémenté sous l'ancien
        // modèle puis clôturé sous le nouveau. Impossible de deviner le parc réel,
        // donc on signale sans corriger.
        $zeroed = $products->filter(fn (Product $p) => $p->quantity <= 0);

        if ($zeroed->isNotEmpty()) {
            $this->components->warn(
                $zeroed->count().' article(s) ont un parc à 0 et apparaîtront « indisponibles » : '
                .$zeroed->take(10)->pluck('reference')->join(', ')
                .'. Corrigez la quantité sur la fiche article si ces pièces existent réellement.'
            );
        }

        if (! $toFix) {
            $this->components->info('Aucun article à recréditer automatiquement : le parc est cohérent avec les locations en cours.');

            return self::SUCCESS;
        }

        $this->components->warn(count($toFix).' article(s) ont un parc inférieur aux unités engagées — typiquement décrémentées par l\'ancien modèle.');

        if (! $this->option('apply')) {
            $this->components->info('Relancez avec --apply pour recréditer ces articles.');

            return self::SUCCESS;
        }

        foreach ($toFix as $productId => $engaged) {
            $product = Product::find($productId);

            if (! $product) {
                continue;
            }

            $product->quantity += $engaged;

            // Un article redevenu positif n'a plus de raison d'être hors service.
            if ($product->quantity > 0 && $product->status === Product::STATUS_OFFLINE) {
                $product->status = Product::STATUS_AVAILABLE;
            }

            $product->save();
        }

        $this->components->info(count($toFix).' article(s) recrédités.');

        return self::SUCCESS;
    }
}
