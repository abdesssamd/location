<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Diagnostic d'isolation : répond à « pourquoi ce compte voit-il les données
 * d'un autre magasin ? » sans avoir à ouvrir la base à la main.
 */
class TenantDoctor extends Command
{
    protected $signature = 'tenant:doctor {--fix-orphans= : Rattache les données sans magasin à l\'identifiant de magasin donné}';

    protected $description = 'Vérifie le rattachement des comptes et des données à leur magasin';

    public function handle(): int
    {
        $this->components->info('Magasins');
        $this->table(
            ['ID', 'Nom', 'Slug', 'Statut', 'Utilisateurs'],
            Store::withCount('users')->orderBy('id')->get()
                ->map(fn (Store $s) => [$s->id, $s->name, $s->slug, $s->status, $s->users_count])
                ->all()
        );

        $this->components->info('Comptes');
        $this->table(
            ['ID', 'E-mail', 'Super admin', 'store_id', 'Actif'],
            User::orderBy('id')->get()
                ->map(fn (User $u) => [
                    $u->id,
                    $u->email,
                    $u->is_super_admin ? 'OUI' : '—',
                    $u->store_id ?? $this->error_cell('NULL'),
                    $u->is_active ? 'oui' : 'non',
                ])->all()
        );

        $orphans = $this->orphanCounts();

        $this->components->info('Données sans magasin (store_id NULL)');
        $this->table(
            ['Table', 'Lignes orphelines'],
            collect($orphans)->map(fn ($count, $table) => [$table, $count])->values()->all()
        );

        $total = array_sum($orphans);
        $orphanUsers = User::whereNull('store_id')->where('is_super_admin', false)->count();

        if ($orphanUsers > 0) {
            $this->components->warn(
                $orphanUsers.' compte(s) non super admin sans store_id : ces comptes ne peuvent rien voir '
                .'(et, avant le correctif, voyaient toutes les données). Corrigez leur store_id.'
            );
        }

        if ($total > 0 && $this->option('fix-orphans')) {
            $storeId = (int) $this->option('fix-orphans');

            abort_unless(Store::whereKey($storeId)->exists(), 1, 'Magasin introuvable.');

            foreach (array_keys($orphans) as $table) {
                DB::table($table)->whereNull('store_id')->update(['store_id' => $storeId]);
            }

            $this->components->info('Données orphelines rattachées au magasin '.$storeId.'.');
        } elseif ($total > 0) {
            $this->components->warn(
                $total.' ligne(s) sans magasin. Rattachez-les avec : php artisan tenant:doctor --fix-orphans=<id_magasin>'
            );
        }

        if ($total === 0 && $orphanUsers === 0) {
            $this->components->info('Aucun problème de rattachement détecté.');
        }

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    protected function orphanCounts(): array
    {
        $tables = [
            'products', 'categories', 'customers', 'rentals', 'rental_items',
            'payments', 'packs', 'pack_items', 'pack_images', 'product_images',
            'stock_movements',
        ];

        $counts = [];

        foreach ($tables as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $counts[$table] = (int) DB::table($table)->whereNull('store_id')->count();
        }

        return $counts;
    }

    protected function error_cell(string $value): string
    {
        return '<fg=red>'.$value.'</>';
    }
}
