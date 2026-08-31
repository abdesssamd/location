<?php

namespace App\Livewire\Rentals;

use App\Models\Customer;
use App\Models\Pack;
use App\Models\Store;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\AvailabilityService;
use App\Services\PackService;
use App\Services\ReferenceGenerator;
use App\Services\StoreContext;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class RentalForm extends Component
{
    public string $mode = 'article';

    public ?Rental $rental = null;

    public ?int $customer_id = null;
    public string $customer_search = '';

    public string $product_search = '';
    public string $pack_search = '';
    public array $items = [];
    public array $packs = [];

    public string $start_date = '';
    public string $end_date = '';
    public int|string $discount = 0;
    public int|string $caution = 0;
    public string $notes = '';

    public bool $showCustomerModal = false;
    public string $new_first_name = '';
    public string $new_last_name = '';
    public string $new_phone = '';
    public string $new_phone_secondary = '';
    public string $new_email = '';
    public string $new_wilaya = '';
    public string $new_commune = '';
    public bool $needsStore = false;
    public ?int $new_store_id = null;

    public function mount(mixed $rental = null): void
    {
        $this->needsStore = (bool) optional(auth()->user())->is_super_admin;

        if ($this->needsStore) {
            $this->new_store_id = (int) (session('admin_store_id', StoreContext::id() ?? (Store::where('status', 'active')->oldest()->value('id') ?? 0))) ?: null;
        }

        $this->start_date = now()->addDay()->toDateString();
        $this->end_date = now()->addDays(2)->toDateString();

        if ($rental) {
            if ($rental instanceof Rental) {
                $this->rental = $rental->load('items');
            } else {
                $this->rental = Rental::findOrFail($rental)->load('items');
            }
            $this->customer_id = $this->rental->customer_id;
            $this->start_date = $this->rental->start_date->toDateString();
            $this->end_date = $this->rental->end_date->toDateString();
            $this->discount = $this->rental->discount;
            $this->caution = $this->rental->caution;
            $this->notes = $this->rental->notes ?? '';

            $this->items = $this->rental->items->map(fn ($it) => [
                'product_id' => $it->product_id,
                'quantity' => $it->quantity,
                'unit_price' => $it->unit_price,
                'pack_id' => $it->pack_id,
                'pack_name' => $it->pack_name,
                'is_pack_component' => (bool) $it->is_pack_component,
            ])->values()->toArray();
        }

        // Depuis le calendrier : clic sur une date vide, dates pré-remplies.
        if (! $rental && request()->has('start')) {
            $start = \Carbon\Carbon::parse(request()->query('start'));
            $this->start_date = $start->toDateString();

            // « end » est déjà la dernière date incluse (le calendrier l'a
            // décrémentée avant de construire l'URL) : ne pas la retrancher
            // une seconde fois ici.
            $end = request()->has('end') ? \Carbon\Carbon::parse(request()->query('end')) : $start->copy()->addDay();
            $this->end_date = $end->max($start)->toDateString();
        }

        if (request()->has('customer')) {
            $this->customer_id = (int) request()->query('customer');
        }

        if (request()->has('product')) {
            $product = Product::find((int) request()->query('product'));
            if ($product) {
                $this->addProduct($product->id);
            }
        }

        if (request()->has('pack')) {
            $pack = Pack::find((int) request()->query('pack'));
            if ($pack) {
                $this->mode = 'pack';
                $this->addPack($pack->id);
            }
        }
    }

    public function selectCustomer(int $id): void
    {
        $this->customer_id = $id;
        $this->customer_search = '';
    }

    public function openCustomerModal(): void
    {
        $this->showCustomerModal = true;
    }

    public function closeCustomerModal(): void
    {
        $this->showCustomerModal = false;
        $this->resetCustomerForm();
    }

    public function createCustomer(): void
    {
        $data = $this->validate([
            'new_first_name' => ['required', 'string', 'max:120'],
            'new_last_name' => ['required', 'string', 'max:120'],
            'new_phone' => ['required', 'string', 'max:30'],
            'new_phone_secondary' => ['nullable', 'string', 'max:30'],
            'new_email' => ['nullable', 'email', 'max:180'],
            'new_wilaya' => ['nullable', 'string', 'max:120'],
            'new_commune' => ['nullable', 'string', 'max:120'],
        ]);

        $storeId = ((bool) optional(auth()->user())->is_super_admin && $this->new_store_id)
            ? $this->new_store_id
            : (StoreContext::id() ?? $this->new_store_id);

        if (! $storeId) {
            session()->flash('error', missing_store_message('créer une réservation'));

            return;
        }

        $customer = Customer::create([
            'store_id' => $storeId,
            'first_name' => $data['new_first_name'],
            'last_name' => $data['new_last_name'],
            'phone' => $data['new_phone'],
            'phone_secondary' => $data['new_phone_secondary'] ?: null,
            'email' => $data['new_email'] ?: null,
            'wilaya' => $data['new_wilaya'] ?: null,
            'commune' => $data['new_commune'] ?: null,
        ]);

        $this->customer_id = $customer->id;
        $this->customer_search = '';
        $this->showCustomerModal = false;
        $this->resetCustomerForm();

        session()->flash('status', 'Client « '.$customer->full_name.' » créé.');
    }

    protected function resetCustomerForm(): void
    {
        $this->new_first_name = '';
        $this->new_last_name = '';
        $this->new_phone = '';
        $this->new_phone_secondary = '';
        $this->new_email = '';
        $this->new_wilaya = '';
        $this->new_commune = '';
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        foreach ($this->items as &$item) {
            if ((int) $item['product_id'] === $product->id) {
                $item['quantity']++;
                return;
            }
        }

        $start = $this->start_date ?: now()->toDateString();
        $end = $this->end_date ?: now()->addDay()->toDateString();
        $free = app(AvailabilityService::class)->freeBetween($product, $start, $end);
        if ($free < 1) {
            session()->flash('error', 'Stock indisponible pour « '.$product->name.' » sur la période choisie.');

            return;
        }

        $this->items[] = [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->rental_price,
            'pack_id' => null,
            'pack_name' => null,
            'is_pack_component' => false,
        ];
        $this->product_search = '';
    }

    public function addPack(int $packId): void
    {
        $pack = Pack::with('items.product')->findOrFail($packId);

        if ($pack->status !== Pack::STATUS_ACTIVE) {
            session()->flash('error', 'Ce pack n\'est pas disponible.');

            return;
        }

        foreach ($this->packs as &$row) {
            if ((int) $row['pack_id'] === $pack->id) {
                $row['quantity']++;

                return;
            }
        }

        $this->packs[] = [
            'pack_id' => $pack->id,
            'quantity' => 1,
            'selected_products' => [],
        ];

        $this->pack_search = '';
    }

    public function removePack(int $index): void
    {
        unset($this->packs[$index]);
        $this->packs = array_values($this->packs);
    }

    public function setPackComponentProduct(int $packIndex, int $packItemId, int $productId): void
    {
        if (! isset($this->packs[$packIndex])) {
            return;
        }

        $selected = $this->packs[$packIndex]['selected_products'] ?? [];
        $selected[$packItemId] = $productId;
        $this->packs[$packIndex]['selected_products'] = $selected;
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'caution' => ['nullable', 'integer', 'min:0'],
            'items' => ['required_without:packs', 'array'],
            'packs' => ['nullable', 'array'],
        ]);

        $rows = $this->expandedItems();
        if (count($rows) < 1) {
            $this->addError('items', 'Ajoutez au moins un article ou un pack.');

            return;
        }

        foreach ($this->packAvailabilityMap() as $availability) {
            if (! $availability['is_available']) {
                session()->flash('error', 'Pack indisponible : '.$availability['message']);

                return;
            }
        }

        if (! $this->validateRowsAvailability($rows, $this->rental)) {
            return;
        }

        // Sous-total = prix normaux ; les lignes pack portent déjà le prix pack réparti
        $subtotal = (int) collect($rows)->sum(fn ($item) => (int) ($item['normal_line_total'] ?? ((int) $item['quantity'] * (int) $item['unit_price'])));
        $packSavings = $this->packSavingsTotal();
        $total = max(0, $subtotal - $packSavings - (int) $this->discount);

        if ($this->rental?->exists) {
            $this->updateRental($rows, $subtotal, $packSavings, $total);
            return;
        }

        $this->createRental($rows, $subtotal, $packSavings, $total);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function createRental(array $rows, int $subtotal, int $packSavings, int $total): void
    {
        $storeId = Customer::find($this->customer_id)?->store_id ?? StoreContext::id();
        $subscription = \App\Services\SubscriptionService::store($storeId);

        if (! $subscription->canCreateRental()) {
            session()->flash('error', $subscription->limitMessage('rental'));

            return;
        }

        $created = DB::transaction(function () use ($rows, $subtotal, $packSavings, $total) {
            // Verrou + revalidation dans la transaction : deux employés qui réservent
            // le dernier article au même instant ne peuvent plus passer tous les deux.
            $this->lockProducts($rows);

            if (! $this->validateRowsAvailability($rows, null)) {
                return null;
            }

            return $this->persistNewRental($rows, $subtotal, $packSavings, $total);
        });

        if (! $created) {
            return;
        }

        session()->flash('status', 'Réservation '.$created->reference.' créée.');
        $this->redirect(route('rentals.show', $created), navigate: true);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function persistNewRental(array $rows, int $subtotal, int $packSavings, int $total): Rental
    {
        $rental = Rental::create([
            'store_id' => Customer::find($this->customer_id)?->store_id ?? StoreContext::id(),
            'customer_id' => $this->customer_id,
            'user_id' => auth()->id(),
            'reference' => ReferenceGenerator::reference('LOC', Rental::class),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => 'reserved',
            'subtotal' => $subtotal,
            'discount' => (int) $this->discount,
            'pack_savings' => $packSavings,
            'caution' => (int) $this->caution,
            'total' => $total,
            'paid_amount' => 0,
            'notes' => $this->notes ?: null,
        ]);

        $this->storeItems($rental, $rows);
        AuditLogger::created($rental, 'rental.created');

        return $rental;
    }

    /**
     * Verrouille les articles concernés le temps de la transaction.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    protected function lockProducts(array $rows): void
    {
        $ids = collect($rows)->pluck('product_id')->filter()->unique()->values()->all();

        if ($ids) {
            Product::whereIn('id', $ids)->lockForUpdate()->get();
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function updateRental(array $rows, int $subtotal, int $packSavings, int $total): void
    {
        $rental = $this->rental;

        if (! in_array($rental->status, ['reserved', 'active'], true)) {
            session()->flash('error', 'Cette location ne peut plus être modifiée.');

            return;
        }

        DB::transaction(function () use ($rental, $rows, $subtotal, $packSavings, $total) {
            $this->lockProducts($rows);

            $wasActive = $rental->status === 'active';
            $oldItems = $wasActive ? $rental->items()->get() : null;

            $rental->items()->delete();

            if ($wasActive) {
                // La location était déjà sortie : les anciens articles rentrent
                // (journal seulement) avant que les nouveaux ne ressortent.
                foreach ($oldItems as $previous) {
                    StockMovement::create([
                        'store_id' => $rental->store_id ?? StoreContext::id(),
                        'product_id' => (int) $previous->product_id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'quantity' => $previous->quantity,
                        'reason' => 'Édition location '.$rental->reference,
                        'date' => now(),
                    ]);
                }
            }

            $old = $rental->getAttributes();
            $rental->update([
                'customer_id' => $this->customer_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'subtotal' => $subtotal,
                'discount' => (int) $this->discount,
                'pack_savings' => $packSavings,
                'caution' => (int) $this->caution,
                'total' => $total,
                'notes' => $this->notes ?: null,
            ]);

            $this->storeItems($rental, $rows);

            if ($wasActive) {
                // Toujours en cours après l'édition : les nouveaux articles sortent.
                foreach ($rows as $item) {
                    StockMovement::create([
                        'store_id' => $rental->store_id ?? StoreContext::id(),
                        'product_id' => (int) $item['product_id'],
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'quantity' => -(int) $item['quantity'],
                        'reason' => 'Édition location '.$rental->reference,
                        'date' => now(),
                    ]);
                }
            }

            AuditLogger::updated($rental, $old, 'rental.updated');
        });

        session()->flash('status', 'Réservation mise à jour.');
        $this->redirect(route('rentals.show', $rental), navigate: true);
    }

    /**
     * Crée les lignes de la réservation. Aucun mouvement de stock n'est écrit
     * ici : une réservation n'engage que la disponibilité par dates
     * (AvailabilityService), elle ne fait rien sortir du magasin. Le seul
     * mouvement réel a lieu au checkout, quand les articles sortent
     * physiquement (voir RentalShow::checkout()).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    protected function storeItems(Rental $rental, array $rows): void
    {
        foreach ($rows as $item) {
            $qty = (int) $item['quantity'];
            $unit = (int) $item['unit_price'];
            $lineTotal = (int) ($item['line_total'] ?? ($qty * $unit));

            RentalItem::create([
                'store_id' => $rental->store_id ?? StoreContext::id(),
                'rental_id' => $rental->id,
                'product_id' => $item['product_id'],
                'pack_id' => $item['pack_id'] ?? null,
                'pack_name' => $item['pack_name'] ?? null,
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
                'is_pack_component' => (bool) ($item['is_pack_component'] ?? false),
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function expandedItems(): array
    {
        $rows = collect($this->items)
            ->map(fn ($item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (int) $item['unit_price'],
                'line_total' => (int) $item['quantity'] * (int) $item['unit_price'],
                'pack_id' => null,
                'pack_name' => null,
                'is_pack_component' => false,
            ])->values()->all();

        $service = app(PackService::class);

        foreach ($this->packs as $selectedPack) {
            $pack = Pack::with('items.product')->find((int) ($selectedPack['pack_id'] ?? 0));
            if (! $pack) {
                continue;
            }

            $expanded = $service->expandToRentalRows(
                $pack,
                $selectedPack['selected_products'] ?? [],
                max(1, (int) ($selectedPack['quantity'] ?? 1)),
                $this->start_date,
                $this->end_date
            );

            foreach ($expanded as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function packSavingsTotal(): int
    {
        $sum = 0;
        foreach ($this->packs as $selectedPack) {
            $pack = Pack::with('items.product')->find((int) ($selectedPack['pack_id'] ?? 0));
            if (! $pack) {
                continue;
            }
            $sum += $pack->savingAmount() * max(1, (int) ($selectedPack['quantity'] ?? 1));
        }

        return $sum;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function packAvailabilityMap(): array
    {
        $service = app(PackService::class);
        $results = [];

        foreach ($this->packs as $index => $selectedPack) {
            $pack = Pack::with('items.product')->find((int) ($selectedPack['pack_id'] ?? 0));
            if (! $pack) {
                continue;
            }

            $check = $service->availability(
                $pack,
                $selectedPack['selected_products'] ?? [],
                max(1, (int) ($selectedPack['quantity'] ?? 1)),
                $this->start_date,
                $this->end_date
            );

            $check['index'] = $index;
            $check['pack'] = $pack;
            $results[] = $check;
        }

        return $results;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function validateRowsAvailability(array $rows, ?Rental $editingRental = null): bool
    {
        $service = app(AvailabilityService::class);
        $start = $this->start_date;
        $end = $this->end_date;
        $ignore = $editingRental?->id;

        $requiredByProduct = collect($rows)
            ->groupBy('product_id')
            ->map(fn ($group) => (int) $group->sum('quantity'));

        $products = Product::whereIn('id', $requiredByProduct->keys()->all())->get()->keyBy('id');
        foreach ($requiredByProduct as $productId => $requiredQty) {
            $product = $products->get((int) $productId);
            if (! $product) {
                session()->flash('error', 'Article introuvable dans la composition.');

                return false;
            }

            $free = $service->freeBetween($product, $start, $end, $ignore);
            if ($free < (int) $requiredQty) {
                $conflicts = $service->conflictsFor($product, $start, $end, $ignore);
                $msg = 'Stock insuffisant pour « '.$product->name.' » du '.$start.' au '.$end.' (libre '.$free.', requis '.$requiredQty.').';
                if ($conflicts) {
                    $msg .= ' Conflits : '.collect($conflicts)->pluck('reference')->join(', ');
                }
                session()->flash('error', $msg);

                return false;
            }
        }

        return true;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $customers = $this->customer_search
            ? Customer::query()
                ->when(! $this->needsStore, fn ($q) => $q->where('store_id', StoreContext::id()))
                ->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('phone', 'like', '%'.$this->customer_search.'%');
                })
                ->limit(8)->get()
            : collect();

        $products = $this->product_search
            ? Product::query()
                ->when(! $this->needsStore, fn ($q) => $q->where('store_id', StoreContext::id()))
                ->where('status', '!=', 'offline')
                ->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->product_search.'%')
                        ->orWhere('reference', 'like', '%'.$this->product_search.'%');
                })
                ->limit(8)->get()
            : collect();

        $packResults = $this->pack_search
            ? Pack::query()
                ->with('items.product')
                ->when(! $this->needsStore, fn ($q) => $q->where('store_id', StoreContext::id()))
                ->where('status', Pack::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->pack_search.'%')
                        ->orWhere('reference', 'like', '%'.$this->pack_search.'%');
                })
                ->limit(8)
                ->get()
            : collect();

        $pickedIds = collect($this->expandedItems())->pluck('product_id')->all();
        $picked = Product::whereIn('id', $pickedIds)->with('images')->get()->keyBy('id');

        $startForAvail = $this->start_date ?: now()->toDateString();
        $endForAvail = $this->end_date ?: now()->addDay()->toDateString();
        $availSvc = app(AvailabilityService::class);
        $productFree = [];
        foreach ($products as $p) {
            $productFree[$p->id] = $availSvc->freeBetween($p, $startForAvail, $endForAvail);
        }

        $itemFree = [];
        foreach ($this->items as $it) {
            $prod = $picked[$it['product_id']] ?? null;
            $itemFree[$it['product_id']] = $prod ? $availSvc->freeBetween($prod, $startForAvail, $endForAvail) : 0;
        }

        $selectedCustomer = $this->customer_id ? Customer::find($this->customer_id) : null;

        $rows = $this->expandedItems();
        $subtotal = (int) collect($rows)->sum(fn ($it) => (int) ($it['line_total'] ?? ((int) $it['quantity'] * (int) $it['unit_price'])));
        $packSavings = $this->packSavingsTotal();
        $total = max(0, $subtotal - $packSavings - (int) $this->discount);
        $packAvailability = $this->packAvailabilityMap();
        $pickedPacks = Pack::with('items.product', 'images')->whereIn('id', collect($this->packs)->pluck('pack_id')->filter()->all())->get()->keyBy('id');

        return view('livewire.rentals.rental-form', compact(
            'customers',
            'products',
            'packResults',
            'picked',
            'selectedCustomer',
            'subtotal',
            'packSavings',
            'total',
            'packAvailability',
            'pickedPacks',
            'rows',
            'productFree',
            'itemFree'
        ));
    }
}