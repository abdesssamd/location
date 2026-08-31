<?php

use App\Models\Category;
use App\Models\Product;
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
    StoreContext::set($this->store->id);
});

it('affiche la liste des articles', function () {
    Product::create(['store_id' => $this->store->id, 'name' => 'Costume Premium', 'reference' => 'ART-000001', 'rental_price' => 5000, 'caution_price' => 10000, 'quantity' => 1, 'status' => 'available']);

    $this->actingAs($this->user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('Costume Premium');
});

it('cree un article avec categorie et QR code', function () {
    $cat = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('name', 'Smoking Noir')
        ->set('reference', 'SMK-001')
        ->set('category_id', $cat->id)
        ->set('rental_price', '8000')
        ->set('caution_price', '15000')
        ->set('quantity', 2)
        ->call('save');

    $product = Product::where('reference', 'SMK-001')->firstOrFail();
    expect($product->name)->toBe('Smoking Noir');
    expect($product->category_id)->toBe($cat->id);
    expect($product->qr_code)->not->toBeNull();
    expect($product->store_id)->toBe($this->store->id);
});

it('un magasin ne voit pas les articles d un autre magasin dans la liste', function () {
    $other = Store::create(['name' => 'Autre', 'slug' => 'autre', 'token' => 'tok-other', 'status' => 'active']);
    StoreContext::set($other->id);
    Product::create(['store_id' => $other->id, 'name' => 'Robe Secret', 'reference' => 'R-1', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 1, 'status' => 'available']);

    StoreContext::set($this->store->id);

    $this->actingAs($this->user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertDontSee('Robe Secret');
});

it('le super admin cree un article en choisissant le magasin', function () {
    $superAdmin = User::where('is_super_admin', true)->firstOrFail();
    // Pas de contexte magasin (super admin navigue sans sous-domaine)
    StoreContext::set(null);

    Livewire::actingAs($superAdmin)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('store_id', $this->store->id)
        ->set('name', 'Costume Super Admin')
        ->set('reference', 'SA-001')
        ->set('rental_price', '5000')
        ->set('caution_price', '10000')
        ->set('quantity', 2)
        ->call('save');

    $product = Product::where('reference', 'SA-001')->firstOrFail();
    expect($product->store_id)->toBe($this->store->id);
});

it('refuse la creation d article sans magasin pour le super admin', function () {
    $superAdmin = User::where('is_super_admin', true)->firstOrFail();
    StoreContext::set(null);

    Livewire::actingAs($superAdmin)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('name', 'Orphelin')
        ->set('reference', 'ORP-001')
        ->set('rental_price', '1000')
        ->set('caution_price', '1000')
        ->call('save')
        ->assertHasErrors('store_id');

    expect(Product::where('reference', 'ORP-001')->exists())->toBeFalse();
});

it('ajoute rapidement une categorie depuis le formulaire article', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('showQuickCategory', true)
        ->set('newCategoryName', 'Caftans')
        ->set('newCategoryColor', '#22c55e')
        ->call('quickAddCategory');

    $category = \App\Models\Category::where('name', 'Caftans')->firstOrFail();
    expect($category->color)->toBe('#22c55e');
    expect($category->store_id)->toBe($this->store->id);
});

it('cree une variante d article par taille cochee', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('name', 'Costume Multi')
        ->set('reference', 'MULTI-1')
        ->set('sizes', ['M', 'L', 'XL'])
        ->set('rental_price', '5000')
        ->set('caution_price', '10000')
        ->call('save');

    expect(Product::where('store_id', $this->store->id)->where('name', 'Costume Multi')->count())->toBe(3);

    foreach (['MULTI-1-M' => 'M', 'MULTI-1-L' => 'L', 'MULTI-1-XL' => 'XL'] as $ref => $size) {
        $variant = Product::where('reference', $ref)->firstOrFail();
        expect($variant->size)->toBe($size);
        expect($variant->store_id)->toBe($this->store->id);
    }
});

it('applique une taille unique coquee sans creer de variantes', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('name', 'Robe Solo')
        ->set('reference', 'SOLO-1')
        ->set('sizes', ['S'])
        ->set('rental_price', '3000')
        ->set('caution_price', '6000')
        ->call('save');

    $product = Product::where('reference', 'SOLO-1')->firstOrFail();
    expect($product->size)->toBe('S');
});

it('affiche la fiche article avec son historique de stock', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume Fiche', 'reference' => 'FICHE-1', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 3, 'status' => 'available']);

    \App\Models\StockMovement::create([
        'store_id' => $this->store->id,
        'product_id' => $product->id,
        'user_id' => $this->user->id,
        'type' => 'in',
        'quantity' => 3,
        'date' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Costume Fiche')
        ->assertSee('Réception');
});

it('supprime et duplique un article', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-000002', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 1, 'status' => 'available']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductList::class)
        ->call('duplicateProduct', $product->id);

    expect(Product::where('name', 'like', '%copie%')->exists())->toBeTrue();

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductList::class)
        ->call('deleteProduct', $product->id);

    expect(Product::find($product->id))->toBeNull();
});
it('applique les photos a toutes les variantes de taille creees', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('name', 'Costume Photo')
        ->set('reference', 'PHOTO-1')
        ->set('sizes', ['40', '42', '50', '58'])
        ->set('rental_price', '5000')
        ->set('caution_price', '10000')
        ->set('photos', [\Illuminate\Http\UploadedFile::fake()->image('costume.jpg', 800, 800)])
        ->call('save');

    $variants = Product::where('store_id', $this->store->id)->where('name', 'Costume Photo')->get();
    expect($variants)->toHaveCount(4);

    foreach ($variants as $variant) {
        $images = \App\Models\ProductImage::where('product_id', $variant->id)->get();
        expect($images)->toHaveCount(1);
        expect($images->first()->is_primary)->toBeTrue();
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($images->first()->path);
    }
});

it('propose les tailles de la categorie selectionnee dans le formulaire', function () {
    $costumes = \App\Models\Category::create(['store_id' => $this->store->id, 'name' => 'Costumes Test', 'sizes' => ['40', '42', '50', '58']]);
    $chaussures = \App\Models\Category::create(['store_id' => $this->store->id, 'name' => 'Chaussures Test', 'sizes' => ['36', '37', '38', '39']]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('category_id', $costumes->id);

    expect($component->viewData('sizePresets'))->toBe(['40', '42', '50', '58']);
    expect($component->viewData('usingCategorySizes'))->toBeTrue();

    $component->set('category_id', $chaussures->id);
    expect($component->viewData('sizePresets'))->toBe(['36', '37', '38', '39']);
});

it('revient a la liste generique si la categorie n a pas de tailles definies', function () {
    $decoration = \App\Models\Category::create(['store_id' => $this->store->id, 'name' => 'Décoration Test']);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('category_id', $decoration->id);

    expect($component->viewData('usingCategorySizes'))->toBeFalse();
    expect($component->viewData('sizePresets'))->toBe(\App\Livewire\Products\ProductForm::sizePresets());
});

it('reinitialise les tailles cochees quand on change de categorie a la creation', function () {
    $costumes = \App\Models\Category::create(['store_id' => $this->store->id, 'name' => 'Costumes Reset', 'sizes' => ['40', '42']]);
    $chaussures = \App\Models\Category::create(['store_id' => $this->store->id, 'name' => 'Chaussures Reset', 'sizes' => ['36', '37']]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('category_id', $costumes->id)
        ->set('sizes', ['40', '42'])
        ->set('category_id', $chaussures->id)
        ->assertSet('sizes', []);
});
