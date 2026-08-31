<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\PackItem;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Détecte les lignes de pack « au choix » dont la catégorie ou l'article
 * référencé n'appartient pas au magasin du pack.
 *
 * Ce cas se produisait quand un super admin éditait un pack destiné à un
 * magasin pendant que sa barre latérale pointait sur un autre : le
 * formulaire proposait alors les catégories du mauvais magasin, et la ligne
 * devenait invisible pour toujours (0 article trouvé) dans le magasin réel
 * du pack.
 */
class PackDoctor extends Command
{
    protected $signature = 'pack:doctor {--fix : Détache les références étrangères (repasse la ligne en manuel, sans catégorie/article)}';

    protected $description = 'Détecte (et corrige avec --fix) les lignes de pack pointant vers un autre magasin';

    public function handle(): int
    {
        $items = PackItem::withoutGlobalScopes()->with('pack')->get();

        $rows = [];
        $broken = [];

        foreach ($items as $item) {
            if (! $item->pack) {
                continue;
            }

            $problem = null;

            if ($item->category_id) {
                $category = Category::withoutGlobalScopes()->find($item->category_id);
                if (! $category) {
                    $problem = 'catégorie supprimée';
                } elseif ($category->store_id !== $item->pack->store_id) {
                    $problem = 'catégorie du magasin '.$category->store_id.' (pack au magasin '.$item->pack->store_id.')';
                }
            }

            if ($item->product_id) {
                $product = Product::withoutGlobalScopes()->find($item->product_id);
                if (! $product) {
                    $problem = 'article supprimé';
                } elseif ($product->store_id !== $item->pack->store_id) {
                    $problem = 'article du magasin '.$product->store_id.' (pack au magasin '.$item->pack->store_id.')';
                }
            }

            if ($problem) {
                $rows[] = [$item->pack->store_id, $item->pack->reference, $item->id, $problem];
                $broken[] = $item->id;
            }
        }

        if (! $rows) {
            $this->components->info('Aucune ligne de pack ne référence un autre magasin.');

            return self::SUCCESS;
        }

        $this->table(['Magasin du pack', 'Pack', 'Ligne #', 'Problème'], $rows);

        if (! $this->option('fix')) {
            $this->components->warn(count($broken).' ligne(s) concernée(s). Relancez avec --fix pour les détacher (elles redeviennent des lignes vides à réassigner manuellement).');

            return self::SUCCESS;
        }

        PackItem::withoutGlobalScopes()->whereIn('id', $broken)->update([
            'category_id' => null,
            'product_id' => null,
        ]);

        $this->components->info(count($broken).' ligne(s) détachée(s) — à réassigner depuis l\'édition du pack concerné.');

        return self::SUCCESS;
    }
}
