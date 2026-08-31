<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;
use App\Models\Rental;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    $this->customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Karim', 'last_name' => 'Benali', 'phone' => '0555 11 22 33']);
    StoreContext::set($this->store->id);

    // Articles du pack : costume 3000 + chaussures 1500 + cravate 500 = 5000 DA
    $this->costume = Product::create(['store_id' => $this->store->id, 'name' => 'Costume Mariage', 'reference' => 'CST-001', 'rental_price' => 3000, 'caution_price' => 10000, 'quantity' => 5, 'status' => 'available']);
    $this->chaussures = Product::create(['store_id' => $this->store->id, 'name' => 'Chaussures Cirage', 'reference' => 'SH-003', 'rental_price' => 1500, 'caution_price' => 5000, 'quantity' => 10, 'status' => 'available']);
    $this->cravate = Product::create(['store_id' => $this->store->id, 'name' => 'Cravate Soie', 'reference' => 'CR-008', 'rental_price' => 500, 'caution_price' => 1000, 'quantity' => 30, 'status' => 'available']);
});

function createPack(int $storeId, string $name, string $reference, array $products, array $overrides = []): Pack
{
    $pack = Pack::create(array_merge([
        'store_id' => $storeId,
        'name' => $name,
        'reference' => $reference,
        'pricing_mode' => Pack::PRICING_FIXED,
        'pack_price' => 4500,
        'caution' => 5000,
        'status' => Pack::STATUS_ACTIVE,
    ], $overrides));

    foreach ($products as $product) {
        PackItem::create([
            'store_id' => $storeId,
            'pack_id' => $pack->id,
            'product_id' => $product['id'],
            'quantity' => 1,
            'selection_mode' => 'auto',
        ]);
    }

    return $pack;
}

it('cree un pack a prix fixe avec economie de 500 da', function () {
    $pack = createPack($this->store->id, 'Pack Mariage Élégance', 'PACK-001', [
        ['id' => $this->costume->id],
        ['id' => $this->chaussures->id],
        ['id' => $this->cravate->id],
    ]);

    expect($pack->normalPrice())->toBe(5000);
    expect($pack->finalPrice())->toBe(4500);
    expect($pack->savingAmount())->toBe(500);
});

it('calcule le prix du pack avec remise de 10 pourcent', function () {
    $pack = createPack($this->store->id, 'Pack Calculé', 'PACK-002', [
        ['id' => $this->costume->id],
        ['id' => $this->cravate->id],
    ], [
        'pricing_mode' => Pack::PRICING_CALCULATED,
        'pack_price' => 0,
        'discount_type' => 'percent',
        'discount_value' => 10,
    ]);

    // Normal : 3000 + 500 = 3500, -10% = 3150
    expect($pack->normalPrice())->toBe(3500);
    expect($pack->finalPrice())->toBe(3150);
    expect($pack->savingAmount())->toBe(350);
});

it('cree un pack via le formulaire avec ses articles', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Packs\PackForm::class)
        ->set('name', 'Pack Soirée')
        ->set('reference', 'PACK-100')
        ->set('pricing_mode', 'fixed')
        ->set('pack_price', '4500')
        ->set('caution', '5000')
        ->set('items', [
            ['product_id' => $this->costume->id, 'quantity' => 1, 'selection_mode' => 'auto'],
            ['product_id' => $this->cravate->id, 'quantity' => 1, 'selection_mode' => 'auto'],
        ])
        ->call('save');

    $pack = Pack::where('reference', 'PACK-100')->firstOrFail();
    expect($pack->name)->toBe('Pack Soirée');
    expect($pack->store_id)->toBe($this->store->id);
    expect($pack->items()->count())->toBe(2);
});

it('affiche la liste des packs avec prix et economie', function () {
    createPack($this->store->id, 'Pack Élégance', 'PACK-010', [
        ['id' => $this->costume->id],
        ['id' => $this->cravate->id],
    ]);

    $this->actingAs($this->user)
        ->get(route('packs.index'))
        ->assertOk()
        ->assertSee('Pack Élégance')
        ->assertSee('4 500');
});

it('duplique et archive un pack', function () {
    $pack = createPack($this->store->id, 'Pack Original', 'PACK-020', [
        ['id' => $this->cravate->id],
    ]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Packs\PackList::class)
        ->call('duplicate', $pack->id);

    expect(Pack::where('store_id', $this->store->id)->count())->toBe(2);
    expect(Pack::where('name', 'like', '%Pack Original%')->count())->toBe(2);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Packs\PackList::class)
        ->call('archive', $pack->id);

    expect($pack->refresh()->status)->toBe(Pack::STATUS_ARCHIVED);
    expect($pack->archived_at)->not->toBeNull();
});

it('loue un pack et deduit le stock des articles reels', function () {
    $pack = createPack($this->store->id, 'Pack Location', 'PACK-030', [
        ['id' => $this->costume->id],
        ['id' => $this->chaussures->id],
        ['id' => $this->cravate->id],
    ]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(3)->toDateString())
        ->set('packs', [['pack_id' => $pack->id, 'quantity' => 1, 'selected_products' => []]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();

    // 3 lignes réelles (une par article du pack)
    expect($rental->items()->count())->toBe(3);
    expect($rental->items()->where('is_pack_component', true)->count())->toBe(3);
    expect($rental->items()->first()->pack_id)->toBe($pack->id);

    // Le parc (products.quantity) ne bouge pas : c'est la disponibilité sur la
    // période qui est engagée (sinon le stock serait deduit deux fois).
    expect($this->costume->refresh()->quantity)->toBe(5);
    expect($this->chaussures->refresh()->quantity)->toBe(10);
    expect($this->cravate->refresh()->quantity)->toBe(30);

    $start = now()->addDay()->toDateString();
    $end = now()->addDays(3)->toDateString();
    expect($this->costume->freeBetween($start, $end))->toBe(4);
    expect($this->chaussures->freeBetween($start, $end))->toBe(9);
    expect($this->cravate->freeBetween($start, $end))->toBe(29);

    // Prix du pack : 4500 (et non 5000)
    expect($rental->total)->toBe(4500);
    expect($rental->pack_savings)->toBe(500);
});

it('refuse la location si un article du pack est en rupture', function () {
    $pack = createPack($this->store->id, 'Pack Rupture', 'PACK-040', [
        ['id' => $this->costume->id],
        ['id' => $this->cravate->id],
    ]);

    // La cravate n'a plus de stock
    $this->cravate->update(['quantity' => 0, 'status' => 'offline']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(3)->toDateString())
        ->set('packs', [['pack_id' => $pack->id, 'quantity' => 1, 'selected_products' => []]])
        ->call('save');

    expect(Rental::where('customer_id', $this->customer->id)->count())->toBe(0);
});

it('affiche le pack dans le contrat avec sa composition', function () {
    $pack = createPack($this->store->id, 'Pack Contrat', 'PACK-050', [
        ['id' => $this->costume->id],
        ['id' => $this->cravate->id],
    ]);

    $rental = Rental::create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'user_id' => $this->user->id,
        'reference' => 'LOC-PACK-1',
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'status' => 'reserved',
        'subtotal' => 4500,
        'total' => 4500,
        'pack_savings' => 500,
    ]);

    foreach ([['product' => $this->costume, 'unit' => 3000], ['product' => $this->cravate, 'unit' => 500]] as $row) {
        \App\Models\RentalItem::create([
            'store_id' => $this->store->id,
            'rental_id' => $rental->id,
            'product_id' => $row['product']->id,
            'quantity' => 1,
            'unit_price' => $row['unit'],
            'line_total' => $row['unit'],
            'pack_id' => $pack->id,
            'pack_name' => $pack->name,
            'is_pack_component' => true,
        ]);
    }

    $this->actingAs($this->user)
        ->get(route('contracts.show', $rental))
        ->assertOk()
        ->assertSee('Pack Contrat')
        ->assertSee('4 500');
});

it('restitue le stock de chaque article au retour du pack', function () {
    $pack = createPack($this->store->id, 'Pack Retour', 'PACK-060', [
        ['id' => $this->costume->id],
        ['id' => $this->cravate->id],
    ]);

    $rental = Rental::create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'user_id' => $this->user->id,
        'reference' => 'LOC-PACK-2',
        'start_date' => now()->subDay(),
        'end_date' => now(),
        'status' => 'active',
        'subtotal' => 4500,
        'total' => 4500,
    ]);

    foreach ([['product' => $this->costume, 'unit' => 3000], ['product' => $this->cravate, 'unit' => 500]] as $row) {
        \App\Models\RentalItem::create([
            'store_id' => $this->store->id,
            'rental_id' => $rental->id,
            'product_id' => $row['product']->id,
            'quantity' => 1,
            'unit_price' => $row['unit'],
            'line_total' => $row['unit'],
            'pack_id' => $pack->id,
            'pack_name' => $pack->name,
            'is_pack_component' => true,
        ]);
    }

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalShow::class, ['rental' => $rental])
        ->call('complete');

    expect($rental->refresh()->status)->toBe('completed');
    expect($this->costume->refresh()->quantity)->toBe(5);
    expect($this->cravate->refresh()->quantity)->toBe(30);
});

it('le super admin cree un pack en choisissant le magasin', function () {
    $superAdmin = User::where('is_super_admin', true)->firstOrFail();
    StoreContext::set(null);

    Livewire::actingAs($superAdmin)
        ->test(\App\Livewire\Packs\PackForm::class)
        ->set('store_id', $this->store->id)
        ->set('name', 'Pack Super Admin')
        ->set('reference', 'PACK-SA')
        ->set('pack_price', '2000')
        ->set('caution', '1000')
        ->set('items', [['product_id' => $this->cravate->id, 'quantity' => 1, 'selection_mode' => 'auto']])
        ->call('save');

    $pack = Pack::where('reference', 'PACK-SA')->firstOrFail();
    expect($pack->store_id)->toBe($this->store->id);
});

function createCategoryPack(int $storeId, string $reference, array $categoryIds, array $overrides = []): Pack
{
    $pack = Pack::create(array_merge([
        'store_id' => $storeId,
        'name' => 'Pack Catégorie',
        'reference' => $reference,
        'pricing_mode' => Pack::PRICING_FIXED,
        'pack_price' => 4500,
        'caution' => 5000,
        'status' => Pack::STATUS_ACTIVE,
    ], $overrides));

    foreach ($categoryIds as $categoryId) {
        PackItem::create([
            'pack_id' => $pack->id,
            'category_id' => $categoryId,
            'quantity' => 1,
            'selection_mode' => 'auto',
        ]);
    }

    return $pack;
}

it('cree un pack par categories et calcule le prix via articles resolus', function () {
    $catCostume = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes CAT']);
    $catChemise = Category::create(['store_id' => $this->store->id, 'name' => 'Chemises CAT']);
    $catChauss = Category::create(['store_id' => $this->store->id, 'name' => 'Chaussures CAT']);

    Product::create(['store_id' => $this->store->id, 'name' => 'Costume CAT', 'reference' => 'C-CAT', 'category_id' => $catCostume->id, 'rental_price' => 3000, 'caution_price' => 10000, 'quantity' => 5, 'status' => 'available']);
    Product::create(['store_id' => $this->store->id, 'name' => 'Chemise CAT', 'reference' => 'CH-CAT', 'category_id' => $catChemise->id, 'rental_price' => 1000, 'caution_price' => 2000, 'quantity' => 8, 'status' => 'available']);
    Product::create(['store_id' => $this->store->id, 'name' => 'Chaussure CAT', 'reference' => 'S-CAT', 'category_id' => $catChauss->id, 'rental_price' => 1500, 'caution_price' => 5000, 'quantity' => 10, 'status' => 'available']);

    $pack = createCategoryPack($this->store->id, 'PACK-CAT', [$catCostume->id, $catChemise->id, $catChauss->id]);

    // Prix normal = somme des prix des articles résolus (1 par catégorie)
    expect($pack->normalPrice())->toBe(5500);
    expect($pack->finalPrice())->toBe(4500);
    expect($pack->savingAmount())->toBe(1000);
    expect($pack->items()->count())->toBe(3);
});

it('loue un pack par categories en resolvant un article disponible par categorie', function () {
    $catCostume = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes RCAT']);
    $catChauss = Category::create(['store_id' => $this->store->id, 'name' => 'Chaussures RCAT']);

    $costumeCat = Product::create(['store_id' => $this->store->id, 'name' => 'Costume RCAT', 'reference' => 'C-RCAT', 'category_id' => $catCostume->id, 'rental_price' => 3000, 'caution_price' => 10000, 'quantity' => 5, 'status' => 'available']);
    $chaussCat = Product::create(['store_id' => $this->store->id, 'name' => 'Chaussure RCAT', 'reference' => 'S-RCAT', 'category_id' => $catChauss->id, 'rental_price' => 1500, 'caution_price' => 5000, 'quantity' => 10, 'status' => 'available']);

    $pack = createCategoryPack($this->store->id, 'PACK-RCAT', [$catCostume->id, $catChauss->id], [
        'pack_price' => 4000,
    ]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(3)->toDateString())
        ->set('packs', [['pack_id' => $pack->id, 'quantity' => 1, 'selected_products' => []]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();

    // 2 lignes réelles (une par catégorie), disponibilité engagée sur les articles résolus
    expect($rental->items()->count())->toBe(2);
    expect($rental->items()->where('is_pack_component', true)->count())->toBe(2);
    expect($costumeCat->refresh()->quantity)->toBe(5);
    expect($chaussCat->refresh()->quantity)->toBe(10);
    expect($costumeCat->freeBetween(now()->addDay()->toDateString(), now()->addDays(3)->toDateString()))->toBe(4);
    expect($chaussCat->freeBetween(now()->addDay()->toDateString(), now()->addDays(3)->toDateString()))->toBe(9);

    // Prix du pack : 4000
    expect($rental->total)->toBe(4000);
    expect($rental->pack_savings)->toBe(500);
});

it('refuse la location dun pack par categorie si aucun article dispo', function () {
    $catCostume = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes XCAT']);

    Product::create(['store_id' => $this->store->id, 'name' => 'Costume XCAT', 'reference' => 'C-XCAT', 'category_id' => $catCostume->id, 'rental_price' => 3000, 'caution_price' => 10000, 'quantity' => 0, 'status' => 'offline']);

    $pack = createCategoryPack($this->store->id, 'PACK-XCAT', [$catCostume->id]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(3)->toDateString())
        ->set('packs', [['pack_id' => $pack->id, 'quantity' => 1, 'selected_products' => []]])
        ->call('save');

    expect(Rental::where('customer_id', $this->customer->id)->count())->toBe(0);
});

it('choisit une variante libre de la categorie si une autre est deja reservee sur la periode', function () {
    // Reproduit le cas reel : plusieurs variantes de taille dans une meme categorie,
    // une seule est reservee sur les dates demandees. Le pack ne doit pas se bloquer
    // sur cette variante-la, il doit basculer sur une autre variante libre.
    $catCostume = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes VAR']);

    $reserved = Product::create(['store_id' => $this->store->id, 'name' => 'Costume 48', 'reference' => 'C-48', 'category_id' => $catCostume->id, 'rental_price' => 3000, 'caution_price' => 10000, 'quantity' => 1, 'status' => 'available']);
    $free = Product::create(['store_id' => $this->store->id, 'name' => 'Costume 50', 'reference' => 'C-50', 'category_id' => $catCostume->id, 'rental_price' => 3000, 'caution_price' => 10000, 'quantity' => 1, 'status' => 'available']);

    $start = now()->addDay()->toDateString();
    $end = now()->addDays(3)->toDateString();

    // Une location existante engage deja la variante 48 sur ces dates.
    $otherRental = Rental::create([
        'store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id,
        'reference' => 'LOC-VAR-PRIOR', 'start_date' => $start, 'end_date' => $end,
        'status' => 'reserved', 'subtotal' => 3000, 'total' => 3000,
    ]);
    \App\Models\RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $otherRental->id, 'product_id' => $reserved->id, 'quantity' => 1, 'unit_price' => 3000, 'line_total' => 3000]);

    $pack = createCategoryPack($this->store->id, 'PACK-VAR', [$catCostume->id], ['pack_price' => 2500]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('start_date', $start)
        ->set('end_date', $end)
        ->set('packs', [['pack_id' => $pack->id, 'quantity' => 1, 'selected_products' => []]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->where('reference', '!=', 'LOC-VAR-PRIOR')->latest('id')->first();

    expect($rental)->not->toBeNull();
    expect($rental->items()->count())->toBe(1);
    expect($rental->items()->first()->product_id)->toBe($free->id);
});

it('le super admin voit les categories du magasin cible du pack, pas de son contexte ambiant', function () {
    // Reproduit le bug reel : le super admin a un autre magasin selectionne dans
    // sa barre laterale (contexte ambiant) pendant qu'il compose un pack pour un
    // magasin different via le selecteur "Magasin" du formulaire.
    $superAdmin = User::where('is_super_admin', true)->firstOrFail();
    $otherStore = Store::create(['name' => 'Autre Magasin', 'slug' => 'autre-pack-'.time(), 'token' => 'tok-'.time(), 'status' => 'active']);

    $catOther = Category::create(['store_id' => $otherStore->id, 'name' => 'Costumes Autre']);
    $catTarget = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes Cible']);
    Product::create(['store_id' => $this->store->id, 'name' => 'Costume Cible', 'reference' => 'C-CIBLE', 'category_id' => $catTarget->id, 'rental_price' => 3000, 'caution_price' => 10000, 'quantity' => 2, 'status' => 'available']);

    // Contexte ambiant : l'autre magasin (celui choisi dans la barre laterale).
    StoreContext::set($otherStore->id);

    $component = Livewire::actingAs($superAdmin)
        ->test(\App\Livewire\Packs\PackForm::class)
        ->set('store_id', $this->store->id);

    // Le selecteur de categories doit proposer celles du magasin CIBLE du pack,
    // pas celles du contexte ambiant.
    $categories = $component->viewData('categories');
    expect($categories->pluck('id'))->toContain($catTarget->id);
    expect($categories->pluck('id'))->not->toContain($catOther->id);

    // Tenter d'enregistrer une ligne avec la categorie de l'AUTRE magasin doit
    // etre rejete, meme si elle a fuite jusqu'au navigateur.
    $component
        ->set('name', 'Pack Cible')
        ->set('reference', 'PACK-CIBLE')
        ->set('pack_price', '2500')
        ->set('caution', '1000')
        ->set('items', [['product_id' => null, 'category_id' => $catOther->id, 'quantity' => 1, 'selection_mode' => 'auto']])
        ->call('save')
        ->assertHasErrors('items.0.category_id');

    expect(Pack::where('reference', 'PACK-CIBLE')->exists())->toBeFalse();

    // Avec la bonne categorie, l'enregistrement passe et reste resolvable.
    $component
        ->set('items', [['product_id' => null, 'category_id' => $catTarget->id, 'quantity' => 1, 'selection_mode' => 'auto']])
        ->call('save')
        ->assertHasNoErrors();

    // Le pack cree appartient au magasin cible, invisible depuis le contexte
    // ambiant (autre magasin) toujours actif dans ce test : on le lit sans scope.
    $pack = Pack::withoutGlobalScopes()->where('reference', 'PACK-CIBLE')->firstOrFail();
    expect($pack->store_id)->toBe($this->store->id);

    $item = $pack->items()->withoutGlobalScopes()->first();
    expect($item->category_id)->toBe($catTarget->id);
    expect($item->candidateProducts()->pluck('reference'))->toContain('C-CIBLE');
});
