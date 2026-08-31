<?php

namespace App\Console\Commands;

use App\Models\StockMovement;
use Illuminate\Console\Command;

/**
 * Supprime les mouvements de stock fantômes écrits à tort à la réservation
 * (motif « Réservation » / « Édition réservation »), avant le correctif qui a
 * retiré cette écriture de RentalForm::storeItems(). Ils n'ont jamais modifié
 * products.quantity — seul le journal affiché en est encombré.
 *
 * Les mouvements réels (« Sortie location… », « Retour location… », etc.),
 * écrits par RentalShow au checkout et au retour, ne sont jamais touchés.
 */
class StockMovementCleanup extends Command
{
    protected $signature = 'stock:cleanup-phantom-movements {--apply : Supprime les mouvements (sinon simple rapport)}';

    protected $description = 'Supprime les mouvements de stock fantômes écrits à tort lors d\'une réservation';

    public function handle(): int
    {
        $query = StockMovement::withoutGlobalScopes()->whereIn('reason', ['Réservation', 'Édition réservation']);

        $count = $query->count();

        if ($count === 0) {
            $this->components->info('Aucun mouvement fantôme trouvé.');

            return self::SUCCESS;
        }

        $this->table(
            ['Magasin', 'Article', 'Type', 'Qté', 'Motif', 'Date'],
            $query->clone()->with('product')->latest()->limit(20)->get()
                ->map(fn (StockMovement $m) => [$m->store_id, $m->product?->name ?? 'Article supprimé', $m->type, $m->quantity, $m->reason, $m->date])
                ->all()
        );

        if ($count > 20) {
            $this->line('… et '.($count - 20).' de plus.');
        }

        $this->components->warn($count.' mouvement(s) fantôme(s) trouvé(s).');

        if (! $this->option('apply')) {
            $this->components->info('Relancez avec --apply pour les supprimer.');

            return self::SUCCESS;
        }

        $query->delete();

        $this->components->info($count.' mouvement(s) supprimé(s). Le stock réel (products.quantity) n\'a jamais été modifié par ces lignes.');

        return self::SUCCESS;
    }
}
