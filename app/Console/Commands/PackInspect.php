<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Pack;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Diagnostic détaillé d'un pack : pour chaque ligne « au choix », montre la
 * catégorie visée et pourquoi elle ne trouve (ou trouve) aucun article.
 */
class PackInspect extends Command
{
    protected $signature = 'pack:inspect {reference : Référence du pack, ex. PACK-000001}';

    protected $description = 'Diagnostique une ligne de pack "au choix" qui ne trouve aucun article';

    public function handle(): int
    {
        $pack = Pack::withoutGlobalScopes()->where('reference', $this->argument('reference'))->first();

        if (! $pack) {
            $this->components->error('Pack introuvable : '.$this->argument('reference'));

            return self::FAILURE;
        }

        $this->components->info('Pack '.$pack->reference.' — magasin #'.$pack->store_id);

        foreach ($pack->items as $item) {
            $this->line('');
            $this->line("Ligne #{$item->id}");

            if ($item->product_id) {
                $product = Product::withoutGlobalScopes()->find($item->product_id);
                $this->line('  Article fixe : '.($product ? $product->name.' (magasin #'.$product->store_id.', statut '.$product->status.', qté '.$product->quantity.')' : 'INTROUVABLE (id '.$item->product_id.')'));

                continue;
            }

            if (! $item->category_id) {
                $this->line('  Ligne sans catégorie ni article — configuration incomplète.');

                continue;
            }

            $category = Category::withoutGlobalScopes()->find($item->category_id);

            if (! $category) {
                $this->components->warn('  Catégorie #'.$item->category_id.' INTROUVABLE (supprimée) — relancez pack:doctor --fix.');

                continue;
            }

            $this->line('  Catégorie visée : "'.$category->name.'" (id '.$category->id.', magasin #'.$category->store_id.')');

            if ($category->store_id !== $pack->store_id) {
                $this->components->warn('  Cette catégorie appartient à un AUTRE magasin que le pack — relancez pack:doctor --fix.');

                continue;
            }

            // Tous les articles de cette catégorie précise, quel que soit le magasin,
            // pour repérer une éventuelle catégorie en double du même nom.
            $sameName = Category::withoutGlobalScopes()->where('name', $category->name)->get();

            if ($sameName->count() > 1) {
                $this->components->warn('  Attention : '.$sameName->count().' catégories nommées "'.$category->name.'" existent (ids : '.$sameName->pluck('id')->join(', ').'). Chaque magasin a la sienne — vérifiez que les articles sont bien rattachés à celle du magasin #'.$pack->store_id.'.');
            }

            $productsInCategory = Product::withoutGlobalScopes()->where('category_id', $category->id)->get(['id', 'name', 'store_id', 'status', 'quantity']);

            if ($productsInCategory->isEmpty()) {
                $this->components->error('  Aucun article, dans AUCUN magasin, n\'a cette catégorie (id '.$category->id.'). Les articles existent probablement dans une catégorie différente (voir ci-dessus si doublon de nom).');
            } else {
                $this->table(
                    ['Article', 'Magasin', 'Statut', 'Qté'],
                    $productsInCategory->map(fn ($p) => [$p->name, $p->store_id, $p->status, $p->quantity])->all()
                );

                $wrongStore = $productsInCategory->where('store_id', '!=', $pack->store_id);
                if ($wrongStore->isNotEmpty()) {
                    $this->components->warn('  '.$wrongStore->count().' de ces article(s) sont dans un AUTRE magasin que le pack (#'.$pack->store_id.') : ils ne comptent pas pour ce pack.');
                }
            }
        }

        return self::SUCCESS;
    }
}
