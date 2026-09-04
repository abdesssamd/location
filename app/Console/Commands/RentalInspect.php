<?php

namespace App\Console\Commands;

use App\Models\Pack;
use App\Models\Rental;
use Illuminate\Console\Command;

/**
 * Diagnostic : compare les montants enregistrés d'une location avec la somme
 * réelle de ses lignes, pour localiser un écart de total.
 */
class RentalInspect extends Command
{
    protected $signature = 'rental:inspect {reference}';

    protected $description = 'Inspecte les montants d\'une location et de ses lignes';

    public function handle(): int
    {
        $rental = Rental::withoutGlobalScopes()
            ->with('items')
            ->where('reference', $this->argument('reference'))
            ->first();

        if (! $rental) {
            $this->error('Location introuvable.');

            return self::FAILURE;
        }

        $this->info('Location '.$rental->reference.' (magasin '.$rental->store_id.')');
        $this->table(['Champ', 'Valeur'], [
            ['statut', $rental->status],
            ['du', $rental->start_date?->toDateString()],
            ['au', $rental->end_date?->toDateString()],
            ['subtotal', $rental->subtotal],
            ['discount', $rental->discount],
            ['pack_savings', $rental->pack_savings],
            ['caution', $rental->caution],
            ['total', $rental->total],
            ['paid_amount', $rental->paid_amount],
        ]);

        $rows = [];
        foreach ($rental->items as $item) {
            $rows[] = [
                $item->id,
                $item->product_id,
                $item->pack_id ?? '—',
                $item->pack_name ?? '—',
                $item->is_pack_component ? 'oui' : 'non',
                $item->quantity,
                $item->unit_price,
                $item->line_total,
            ];
        }

        $this->table(['id', 'product', 'pack_id', 'pack', 'composant', 'qté', 'PU', 'ligne'], $rows);

        $sumLines = (int) $rental->items->sum('line_total');
        $this->line('Somme des lignes : '.$sumLines);
        $this->line('Subtotal enregistré : '.$rental->subtotal);

        if ($sumLines !== (int) $rental->subtotal) {
            $this->warn('ÉCART : la somme des lignes ne correspond pas au subtotal enregistré.');
        }

        $packIds = $rental->items->pluck('pack_id')->filter()->unique();

        foreach ($packIds as $packId) {
            $pack = Pack::withoutGlobalScopes()->with('items.product')->find($packId);

            if (! $pack) {
                $this->warn('Pack '.$packId.' introuvable.');

                continue;
            }

            $this->info('Pack '.$pack->reference.' — mode '.$pack->pricing_mode);
            $this->line('  pack_price : '.$pack->pack_price);
            $this->line('  normalPrice() : '.$pack->normalPrice());
            $this->line('  finalPrice() : '.$pack->finalPrice());
            $this->line('  savingAmount() : '.$pack->savingAmount());
            $this->line('  lignes du pack : '.$pack->items->count());

            foreach ($pack->items as $packItem) {
                $resolved = $packItem->resolvedProduct();
                $this->line(sprintf(
                    '   - %s | qté %d | résolu: %s (%s DA)',
                    $packItem->displayLabel(),
                    $packItem->quantity,
                    $resolved?->name ?? 'AUCUN',
                    $resolved?->rental_price ?? '—'
                ));
            }
        }

        return self::SUCCESS;
    }
}
