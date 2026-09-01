<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
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

it('affiche la page des depenses et cree les categories par defaut', function () {
    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertOk();

    expect(ExpenseCategory::where('store_id', $this->store->id)->count())->toBe(count(ExpenseCategory::defaultNames()));
    expect(ExpenseCategory::where('store_id', $this->store->id)->where('name', 'Loyer')->exists())->toBeTrue();
});

it('ne recree pas les categories par defaut si elles existent deja', function () {
    Livewire::actingAs($this->user)->test(\App\Livewire\Expenses\ExpenseManager::class);
    Livewire::actingAs($this->user)->test(\App\Livewire\Expenses\ExpenseManager::class);

    expect(ExpenseCategory::where('store_id', $this->store->id)->count())->toBe(count(ExpenseCategory::defaultNames()));
});

it('enregistre une depense', function () {
    $category = ExpenseCategory::where('store_id', $this->store->id)->first()
        ?? ExpenseCategory::create(['store_id' => $this->store->id, 'name' => 'Loyer']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Expenses\ExpenseManager::class)
        ->set('label', 'Loyer août')
        ->set('amount', '25000')
        ->set('payment_method', 'transfer')
        ->set('date', now()->toDateString())
        ->set('expense_category_id', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $expense = Expense::where('store_id', $this->store->id)->where('label', 'Loyer août')->firstOrFail();
    expect($expense->amount)->toBe(25000);
    expect($expense->reference)->toStartWith('DEP-');
    expect($expense->user_id)->toBe($this->user->id);
});

it('cree rapidement une nouvelle categorie de depense', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Expenses\ExpenseManager::class)
        ->set('newCategoryName', 'Assurance')
        ->set('newCategoryColor', '#22c55e')
        ->call('quickAddCategory');

    $category = ExpenseCategory::where('store_id', $this->store->id)->where('name', 'Assurance')->firstOrFail();
    expect($category->color)->toBe('#22c55e');
});

it('supprime une depense', function () {
    $expense = Expense::create(['store_id' => $this->store->id, 'user_id' => $this->user->id, 'reference' => 'DEP-TEST-1', 'label' => 'Test', 'amount' => 1000, 'payment_method' => 'cash', 'date' => now()->toDateString()]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Expenses\ExpenseManager::class)
        ->call('delete', $expense->id);

    expect(Expense::find($expense->id))->toBeNull();
});

it('filtre les depenses par categorie et par date', function () {
    $catA = ExpenseCategory::create(['store_id' => $this->store->id, 'name' => 'Cat A']);
    $catB = ExpenseCategory::create(['store_id' => $this->store->id, 'name' => 'Cat B']);

    Expense::create(['store_id' => $this->store->id, 'expense_category_id' => $catA->id, 'reference' => 'DEP-A', 'label' => 'Depense A', 'amount' => 1000, 'payment_method' => 'cash', 'date' => now()->toDateString()]);
    Expense::create(['store_id' => $this->store->id, 'expense_category_id' => $catB->id, 'reference' => 'DEP-B', 'label' => 'Depense B', 'amount' => 2000, 'payment_method' => 'cash', 'date' => now()->subMonths(2)->toDateString()]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Expenses\ExpenseManager::class)
        ->set('filterCategory', $catA->id);

    expect($component->viewData('expenses')->pluck('reference'))->toContain('DEP-A');
    expect($component->viewData('expenses')->pluck('reference'))->not->toContain('DEP-B');
});

it('un employe sans permission ne peut pas ouvrir les depenses', function () {
    $employe = User::create(['store_id' => $this->store->id, 'name' => 'Employe', 'email' => 'emp-dep@test.com', 'password' => 'password', 'is_active' => true]);
    $employe->assignRole('employee');

    $this->actingAs($employe)->get(route('expenses.index'))->assertForbidden();
});

it('un caissier sans permission expenses ne peut pas ouvrir les depenses', function () {
    $caissier = User::create(['store_id' => $this->store->id, 'name' => 'Caissier', 'email' => 'caisse-dep@test.com', 'password' => 'password', 'is_active' => true]);
    $caissier->assignRole('cashier');

    $this->actingAs($caissier)->get(route('expenses.index'))->assertForbidden();
});

it('un magasin ne voit pas les depenses d un autre magasin', function () {
    $otherStore = Store::create(['name' => 'Autre', 'slug' => 'autre-dep', 'token' => 'tok-dep', 'status' => 'active']);
    \App\Services\SubscriptionService::createSubscription($otherStore, \App\Models\Plan::where('slug', 'pro')->firstOrFail(), \App\Models\Subscription::STATUS_ACTIVE);
    \App\Models\StoreToken::issue($otherStore->id);

    StoreContext::set($otherStore->id);
    Expense::create(['store_id' => $otherStore->id, 'reference' => 'DEP-AUTRE', 'label' => 'Depense Autre', 'amount' => 5000, 'payment_method' => 'cash', 'date' => now()->toDateString()]);
    StoreContext::set($this->store->id);

    // L'utilisateur du magasin A ne doit pas voir la dépense du magasin B.
    Expense::create(['store_id' => $this->store->id, 'reference' => 'DEP-MOI', 'label' => 'Depense A', 'amount' => 1000, 'payment_method' => 'cash', 'date' => now()->toDateString()]);

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('DEP-MOI')
        ->assertDontSee('DEP-AUTRE');
});

it('une preuve de depense n est pas accessible depuis un autre magasin', function () {
    $otherStore = Store::create(['name' => 'Autre B', 'slug' => 'autre-dep-b', 'token' => 'tok-dep-b', 'status' => 'active']);
    StoreContext::set($otherStore->id);
    $expense = Expense::create(['store_id' => $otherStore->id, 'reference' => 'DEP-PROOF', 'label' => 'Avec preuve', 'amount' => 3000, 'payment_method' => 'cash', 'date' => now()->toDateString(), 'proof_path' => 'expenses/'.$otherStore->id.'/preuve.webp']);
    StoreContext::set($this->store->id);

    $this->actingAs($this->user)
        ->get(route('files.expense', $expense))
        ->assertNotFound();
});

it('le rapport calcule le benefice net location plus vente moins depenses', function () {
    $customer = \App\Models\Customer::create(['store_id' => $this->store->id, 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0550']);
    $rental = \App\Models\Rental::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-PROF-1', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000]);
    \App\Models\Payment::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'user_id' => $this->user->id, 'reference' => 'PAY-PROF-1', 'amount' => 3000, 'method' => 'cash', 'type' => 'payment', 'date' => now()->toDateString()]);

    $product = \App\Models\Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-PROF-1', 'rental_price' => 1000, 'sale_price' => 5000, 'caution_price' => 0, 'quantity' => 3, 'status' => 'available']);
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $product->id)
        ->call('checkout');

    Expense::create(['store_id' => $this->store->id, 'reference' => 'DEP-PROF-1', 'label' => 'Loyer', 'amount' => 2000, 'payment_method' => 'cash', 'date' => now()->toDateString()]);

    $component = Livewire::actingAs($this->user)->test(\App\Livewire\Reports\Reports::class);

    expect($component->viewData('revenue'))->toBe(3000);
    expect($component->viewData('saleRevenue'))->toBe(5000);
    expect($component->viewData('expenseTotal'))->toBe(2000);
    expect($component->viewData('netProfit'))->toBe(3000 + 5000 - 2000);
});
