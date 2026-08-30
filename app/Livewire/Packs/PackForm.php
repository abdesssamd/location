<?php

namespace App\Livewire\Packs;

use App\Models\Category;
use App\Models\Pack;
use App\Models\PackImage;
use App\Models\PackItem;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\ImageService;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class PackForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $packId = null;

    public ?Pack $pack = null;

    public string $name = '';
    public string $reference = '';
    public ?int $category_id = null;
    public ?int $store_id = null;
    public string $description = '';
    public string $pricing_mode = Pack::PRICING_FIXED;
    public int|string $pack_price = 0;
    public int|string $discount_value = 0;
    public string $discount_type = 'percent';
    public int|string $caution = 0;
    public string $status = Pack::STATUS_ACTIVE;
    public string $rental_conditions = '';

    public string $product_search = '';
    public string $category_search = '';

    public string $composeMode = 'product';
    public ?int $selectedCategoryId = null;

    /** @var array<int, array<string,mixed>> */
    public array $items = [];

    /** @var array */
    public $photos = [];

    public array $existingPhotos = [];

    public function mount(mixed $pack = null): void
    {
        if ($pack) {
            $this->pack = $pack instanceof Pack ? $pack->load(['items.product', 'images']) : Pack::with(['items.product', 'images'])->findOrFail((int) $pack);
            $this->packId = $this->pack->id;
            $this->fillFromPack();
        } else {
            if ((bool) optional(auth()->user())->is_super_admin && ! $this->store_id && StoreContext::id()) {
                $this->store_id = StoreContext::id();
            }
            $this->reference = $this->suggestReference();
        }
    }

    protected function fillFromPack(): void
    {
        $this->name = $this->pack->name;
        $this->reference = $this->pack->reference;
        $this->category_id = $this->pack->category_id;
        $this->description = (string) $this->pack->description;
        $this->pricing_mode = $this->pack->pricing_mode;
        $this->pack_price = (int) $this->pack->pack_price;
        $this->discount_value = (int) $this->pack->discount_value;
        $this->discount_type = (string) ($this->pack->discount_type ?: 'percent');
        $this->caution = (int) $this->pack->caution;
        $this->status = $this->pack->status;
        $this->rental_conditions = collect($this->pack->rental_conditions ?? [])->implode("\n");

        $this->items = $this->pack->items->map(fn (PackItem $item) => [
            'product_id' => $item->product_id,
            'category_id' => $item->category_id,
            'quantity' => $item->quantity,
            'selection_mode' => $item->selection_mode,
            'variant_hint' => $item->variant_hint,
        ])->values()->toArray();

        $this->existingPhotos = $this->pack->images->map(fn (PackImage $img) => [
            'id' => $img->id,
            'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($img->path),
            'is_primary' => (bool) $img->is_primary,
        ])->toArray();
    }

    protected function isSuperAdmin(): bool
    {
        return (bool) optional(auth()->user())->is_super_admin;
    }

    protected function resolveStoreId(): ?int
    {
        if ($this->isSuperAdmin() && $this->store_id) {
            return $this->store_id;
        }

        return StoreContext::id() ?? $this->store_id;
    }

    protected function suggestReference(): string
    {
        $storeId = $this->resolveStoreId();
        $next = (int) Pack::query()->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->count() + 1;

        return sprintf('PACK-%06d', $next);
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

        $this->items[] = [
            'product_id' => $product->id,
            'category_id' => null,
            'quantity' => 1,
            'selection_mode' => 'auto',
            'variant_hint' => '',
        ];

        $this->product_search = '';
    }

    /**
     * Ajoute une ligne de pack par catégorie : un article disponible de la
     * catégorie sera choisi automatiquement (ou manuellement) lors de la location.
     */
    public function addCategory(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        foreach ($this->items as &$item) {
            if (! empty($item['category_id']) && (int) $item['category_id'] === $category->id && empty($item['product_id'])) {
                $item['quantity']++;

                return;
            }
        }

        $this->items[] = [
            'product_id' => null,
            'category_id' => $category->id,
            'quantity' => 1,
            'selection_mode' => 'manual',
            'variant_hint' => '',
        ];

        $this->category_search = '';
        $this->selectedCategoryId = null;
    }

    public function addSelectedCategory(): void
    {
        if ($this->selectedCategoryId) {
            $this->addCategory((int) $this->selectedCategoryId);
        }
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function removeTemporaryPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function removeExistingPhoto(int $photoId): void
    {
        abort_if($this->pack === null, 404);
        $this->authorize('update', $this->pack);

        // Borne la photo au pack en cours : un identifiant arbitraire ne doit pas
        // permettre de supprimer l'image d'un autre magasin.
        $image = $this->pack->images()->findOrFail($photoId);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
        $image->delete();

        $this->existingPhotos = collect($this->existingPhotos)->filter(fn ($p) => $p['id'] !== $photoId)->values()->toArray();
        $this->ensurePrimary();
    }

    public function setPrimaryExisting(int $photoId): void
    {
        if (! $this->packId) {
            return;
        }

        $this->authorize('update', $this->pack);

        PackImage::where('pack_id', $this->packId)->update(['is_primary' => false]);
        PackImage::where('pack_id', $this->packId)->where('id', $photoId)->update(['is_primary' => true]);

        $this->existingPhotos = collect($this->existingPhotos)
            ->map(fn ($p) => ['id' => $p['id'], 'url' => $p['url'], 'is_primary' => $p['id'] === $photoId])
            ->values()
            ->toArray();
    }

    protected function ensurePrimary(): void
    {
        if (! $this->packId) {
            return;
        }

        if (! PackImage::where('pack_id', $this->packId)->where('is_primary', true)->exists()) {
            $first = PackImage::where('pack_id', $this->packId)->orderBy('sort_order')->first();
            if ($first) {
                $first->update(['is_primary' => true]);
            }
        }
    }

    public function save(): void
    {
        $this->authorize($this->packId ? 'update' : 'create', $this->pack ?? \App\Models\Pack::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'pricing_mode' => ['required', 'in:fixed,calculated'],
            'pack_price' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['nullable', 'in:percent,amount'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'caution' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,draft,archived'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.category_id' => ['nullable', 'exists:categories,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.selection_mode' => ['required', 'in:auto,manual'],
            'photos.*' => ['image', 'max:10240'],
        ]);

        // Chaque ligne doit viser un article OU une catégorie
        foreach ($this->items as $idx => $item) {
            if (empty($item['product_id']) && empty($item['category_id'])) {
                $this->addError("items.{$idx}.product_id", 'Choisissez un article ou une catégorie.');

                return;
            }
        }

        if ((bool) optional(auth()->user())->is_super_admin && $this->store_id) {
            $storeId = $this->store_id;
        } else {
            $storeId = StoreContext::id() ?? $this->store_id;
        }
        if (! $storeId) {
            $this->addError('store_id', missing_store_message('créer un pack'));
            session()->flash('error', missing_store_message('créer un pack'));

            return;
        }

        $data = [
            'store_id' => $storeId,
            'category_id' => $this->category_id,
            'reference' => strtoupper($this->reference),
            'name' => $this->name,
            'description' => $this->description,
            'pricing_mode' => $this->pricing_mode,
            'pack_price' => (int) $this->pack_price,
            'discount_type' => $this->pricing_mode === Pack::PRICING_CALCULATED ? $this->discount_type : null,
            'discount_value' => $this->pricing_mode === Pack::PRICING_CALCULATED ? (float) $this->discount_value : null,
            'caution' => (int) $this->caution,
            'status' => $this->status,
            'archived_at' => $this->status === Pack::STATUS_ARCHIVED ? now() : null,
            'rental_conditions' => collect(explode("\n", $this->rental_conditions))->map(fn ($v) => trim($v))->filter()->values()->all(),
        ];

        if ($this->packId) {
            $old = $this->pack->getAttributes();
            $this->pack->update($data);
            AuditLogger::updated($this->pack, $old, 'pack.updated');
        } else {
            $this->pack = Pack::create($data);
            $this->packId = $this->pack->id;
            AuditLogger::created($this->pack, 'pack.created');
        }

        $this->syncItems();
        $this->storePhotos();

        session()->flash('status', $this->packId ? 'Pack enregistré.' : 'Pack créé.');
        $this->redirect(route('packs.index'), navigate: true);
    }

    protected function syncItems(): void
    {
        PackItem::where('pack_id', $this->packId)->delete();

        foreach ($this->items as $idx => $item) {
            PackItem::create([
                'pack_id' => $this->packId,
                'product_id' => ! empty($item['product_id']) ? (int) $item['product_id'] : null,
                'category_id' => ! empty($item['category_id']) ? (int) $item['category_id'] : null,
                'quantity' => (int) $item['quantity'],
                'selection_mode' => ($item['selection_mode'] ?? 'auto') ?: 'auto',
                'variant_hint' => ($item['variant_hint'] ?? null) ?: null,
                'sort_order' => $idx,
            ]);
        }
    }

    protected function storePhotos(): void
    {
        if (empty($this->photos) || ! $this->packId) {
            $this->ensurePrimary();

            return;
        }

        $imageService = app(ImageService::class);
        $hasPrimary = PackImage::where('pack_id', $this->packId)->where('is_primary', true)->exists();
        $lastSort = (int) PackImage::where('pack_id', $this->packId)->max('sort_order');

        foreach ($this->photos as $photo) {
            $path = $imageService->store($photo, 'packs/'.$this->packId);
            $isPrimary = ! $hasPrimary && $lastSort === 0 && PackImage::where('pack_id', $this->packId)->count() === 0;

            PackImage::create([
                'pack_id' => $this->packId,
                'path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => ++$lastSort,
            ]);

            $hasPrimary = $hasPrimary || $isPrimary;
        }

        $this->photos = [];
        $this->ensurePrimary();
    }

    public function getNormalPriceProperty(): int
    {
        $productIds = collect($this->items)->pluck('product_id')->filter()->unique()->all();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $categoryIds = collect($this->items)->pluck('category_id')->filter()->unique()->all();
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        return (int) collect($this->items)->sum(function ($item) use ($products, $categories) {
            $qty = (int) ($item['quantity'] ?? 0);

            // Ligne par catégorie : prix du premier article de la catégorie
            if (! empty($item['category_id']) && empty($item['product_id'])) {
                $category = $categories->get((int) $item['category_id']);
                if (! $category) {
                    return 0;
                }

                $price = Product::where('category_id', $category->id)
                    ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
                    ->orderBy('rental_price', 'desc')
                    ->value('rental_price');

                return $qty * (int) ($price ?? 0);
            }

            $product = $products->get((int) ($item['product_id'] ?? 0));

            return $qty * (int) ($product?->rental_price ?? 0);
        });
    }

    public function getFinalPriceProperty(): int
    {
        $normal = $this->normalPrice;

        if ($this->pricing_mode === Pack::PRICING_FIXED) {
            return max(0, (int) $this->pack_price);
        }

        if ($this->discount_type === 'percent') {
            return max(0, (int) round($normal - ($normal * ((float) $this->discount_value / 100))));
        }

        return max(0, $normal - (int) $this->discount_value);
    }

    public function getSavingsProperty(): int
    {
        return max(0, $this->normalPrice - $this->finalPrice);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $categories = Category::with('children')->orderBy('name')->get();
        $needsStore = (bool) optional(auth()->user())->is_super_admin;
        $stores = $needsStore ? \App\Models\Store::where('status', 'active')->orderBy('name')->get() : collect();

        $products = $this->product_search
            ? Product::query()
                ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
                ->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->product_search.'%')
                        ->orWhere('reference', 'like', '%'.$this->product_search.'%');
                })
                ->orderBy('name')
                ->limit(8)
                ->get()
            : collect();

        $picked = Product::whereIn('id', collect($this->items)->pluck('product_id')->filter()->all())->get()->keyBy('id');

        $categoryMap = Category::whereIn('id', collect($this->items)->pluck('category_id')->filter()->all())->get()->keyBy('id');

        return view('livewire.packs.pack-form', compact('categories', 'needsStore', 'stores', 'products', 'picked', 'categoryMap'));
    }
}
