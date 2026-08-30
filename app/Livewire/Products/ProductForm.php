<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use App\Services\ImageService;
use App\Services\ReferenceGenerator;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class ProductForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $productId = null;

    public ?Product $product = null;

    public string $name = '';
    public string $reference = '';
    public ?int $category_id = null;
    public ?int $store_id = null;
    public string $brand = '';
    public string $size = '';
    /** @var array<int, string> */
    public array $sizes = [];
    public string $color = '';
    public string $material = '';
    public string $rental_price = '0';
    public string $caution_price = '0';
    public ?string $sale_price = null;
    public int $quantity = 1;
    public string $status = 'available';
    public string $barcode = '';
    public string $description = '';
    public string $notes = '';

    /** @var array */
    public $photos = [];

    public array $existingPhotos = [];

    public string $newCategoryName = '';
    public string $newCategoryColor = '#7c3aed';
    public bool $showQuickCategory = false;

    public function mount(mixed $product = null): void
    {
        if ($product) {
            if ($product instanceof Product) {
                $this->product = $product;
                $this->productId = $product->id;
            } else {
                $this->productId = (int) $product;
                $this->product = Product::findOrFail($this->productId);
            }
            $this->fillFromProduct();
        } else {
            if ($this->isSuperAdmin() && ! $this->store_id && StoreContext::id()) {
                $this->store_id = StoreContext::id();
            }
            $this->reference = $this->suggestReference();
        }
    }

    protected function fillFromProduct(): void
    {
        $this->name = $this->product->name;
        $this->reference = $this->product->reference;
        $this->category_id = $this->product->category_id;
        $this->brand = $this->product->brand ?? '';
        $this->size = $this->product->size ?? '';
        $this->color = $this->product->color ?? '';
        $this->material = $this->product->material ?? '';
        $this->rental_price = (string) $this->product->rental_price;
        $this->caution_price = (string) $this->product->caution_price;
        $this->sale_price = $this->product->sale_price !== null ? (string) $this->product->sale_price : null;
        $this->quantity = $this->product->quantity;
        $this->status = $this->product->status;
        $this->barcode = $this->product->barcode ?? '';
        $this->description = $this->product->description ?? '';
        $this->notes = $this->product->notes ?? '';

        $this->existingPhotos = $this->product->images->map(fn (ProductImage $img) => [
            'id' => $img->id,
            'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($img->path),
            'is_primary' => (bool) $img->is_primary,
        ])->toArray();
    }

    protected function suggestReference(): string
    {
        $storeId = $this->resolveStoreId();
        $next = (int) Product::where('store_id', $storeId)->count() + 1;

        return sprintf('ART-%06d', $next);
    }

    protected function isSuperAdmin(): bool
    {
        return (bool) optional(auth()->user())->is_super_admin;
    }

    protected function resolveStoreId(): ?int
    {
        // Le super admin peut choisir le magasin ; sinon le contexte courant prime
        if ($this->isSuperAdmin() && $this->store_id) {
            return $this->store_id;
        }

        return StoreContext::id() ?? $this->store_id;
    }

    public function quickAddCategory(): void
    {
        $storeId = $this->resolveStoreId();

        if (! $storeId) {
            $this->addError('store_id', missing_store_message('créer une catégorie'));
            session()->flash('error', missing_store_message('créer une catégorie'));

            return;
        }

        $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255'],
            'newCategoryColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $category = Category::create([
            'store_id' => $storeId,
            'name' => trim($this->newCategoryName),
            'color' => $this->newCategoryColor ?: null,
        ]);

        $this->category_id = $category->id;
        $this->newCategoryName = '';

        session()->flash('status', "Catégorie « {$category->name} » créée et sélectionnée.");
    }

    public function updatedStoreId(): void
    {
        if (! $this->productId) {
            $this->category_id = null;
            $this->reference = $this->suggestReference();
        }
    }

    public function updatedPhotos(): void
    {
        $this->validate([
            'photos.*' => ['image', 'max:10240'],
        ]);
    }

    public function removeTemporaryPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function removeExistingPhoto(int $photoId): void
    {
        $image = ProductImage::findOrFail($photoId);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
        $image->delete();

        $this->existingPhotos = collect($this->existingPhotos)->filter(fn ($p) => $p['id'] !== $photoId)->values()->toArray();

        if ($this->product) {
            $this->ensurePrimary();
        }
    }

    public function setPrimaryExisting(int $photoId): void
    {
        ProductImage::where('product_id', $this->productId)->update(['is_primary' => false]);
        ProductImage::where('id', $photoId)->update(['is_primary' => true]);

        $this->existingPhotos = collect($this->existingPhotos)
            ->map(fn ($p) => ['id' => $p['id'], 'url' => $p['url'], 'is_primary' => $p['id'] === $photoId])
            ->values()
            ->toArray();
    }

    protected function ensurePrimary(): void
    {
        $this->ensurePrimaryFor($this->product);
    }

    protected function ensurePrimaryFor(?Product $target): void
    {
        if (! $target) {
            return;
        }

        if (! ProductImage::where('product_id', $target->id)->where('is_primary', true)->exists()) {
            $first = ProductImage::where('product_id', $target->id)->orderBy('sort_order')->first();
            if ($first) {
                $first->update(['is_primary' => true]);
            }
        }
    }

    public function save(): void
    {
        $this->authorize($this->productId ? 'update' : 'create', $this->product ?? \App\Models\Product::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'rental_price' => ['required', 'numeric', 'min:0'],
            'caution_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:available,reserved,rented,returning,cleaning,repair,lost,damaged,offline'],
        ]);

        $data = [
            'category_id' => $this->category_id,
            'name' => $this->name,
            'reference' => strtoupper($this->reference),
            'description' => $this->description,
            'brand' => $this->brand,
            'size' => $this->size,
            'color' => $this->color,
            'material' => $this->material,
            'rental_price' => $this->rental_price,
            'caution_price' => $this->caution_price,
            'sale_price' => $this->sale_price ?: null,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'barcode' => $this->barcode ?: $this->reference,
            'notes' => $this->notes,
        ];

        if (! $this->productId) {
            $storeId = $this->resolveStoreId();

            if (! $storeId) {
                // Le champ « magasin » n'est affiché qu'au super admin : sans ce message
                // global, l'échec serait invisible pour un utilisateur de magasin.
                $this->addError('store_id', 'Veuillez sélectionner un magasin.');
                session()->flash('error', $this->isSuperAdmin()
                    ? 'Sélectionnez le magasin auquel rattacher cet article.'
                    : 'Votre compte n\'est rattaché à aucun magasin. Contactez votre administrateur.');

                return;
            }

            $subscription = \App\Services\SubscriptionService::store($storeId);
            if (! $subscription->canCreateProduct()) {
                session()->flash('error', $subscription->limitMessage('product'));
                $this->addError('name', 'Limite du plan atteinte pour les articles.');

                return;
            }

            // Tailles cochées -> une variante d'article par taille
            $sizes = collect($this->sizes)->map(fn ($s) => trim((string) $s))->filter()->unique()->values();

            if ($sizes->count() > 1) {
                $this->createVariants($data, $storeId, $sizes->all());

                return;
            }

            if ($sizes->count() === 1) {
                $data['size'] = $sizes->first();
            }

            $data['store_id'] = $storeId;
            $this->product = Product::create($data);
            $this->productId = $this->product->id;
            $this->product->update(['qr_code' => $this->product->generateQrCodeValue()]);
            AuditLogger::created($this->product, 'product.created');
        } else {
            $old = $this->product->getAttributes();
            $this->product->update($data);
            AuditLogger::updated($this->product, $old, 'product.updated');
        }

        $this->storePhotos();

        session()->flash('status', $this->productId && ! request()->has('new') ? 'Article mis à jour.' : 'Article créé.');

        $this->redirect(route('products.show', $this->product), navigate: true);
    }

    protected function createVariants(array $data, int $storeId, array $sizes)
    {
        $baseRef = strtoupper($this->reference);

        if (Product::where('store_id', $storeId)->where('reference', 'like', $baseRef.'%')->exists()) {
            $this->addError('reference', "La référence {$baseRef} existe déjà dans ce magasin.");

            return;
        }

        $created = collect();
        foreach ($sizes as $sizeValue) {
            $variant = $data;
            $variant['store_id'] = $storeId;
            $variant['size'] = $sizeValue;
            $variant['reference'] = $this->variantReference($baseRef, $sizeValue);
            $variant['barcode'] = $this->barcode ?: $variant['reference'];

            $product = Product::create($variant);
            $product->update(['qr_code' => $product->generateQrCodeValue()]);
            AuditLogger::created($product, 'product.created');

            $created->push($product);
        }

        // Les photos sont attachées à la première variante
        $first = $created->first();
        $this->product = $first;
        $this->productId = $first->id;
        $this->storePhotosFor($first);

        session()->flash('status', count($created).' articles créés (une variante par taille). Les photos sont sur la première taille.');

        $this->redirect(route('products.index'), navigate: true);
    }

    protected function variantReference(string $baseRef, string $sizeValue): string
    {
        $suffix = \Illuminate\Support\Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $sizeValue));

        return $suffix !== '' ? $baseRef.'-'.$suffix : $baseRef;
    }

    protected function storePhotos(): void
    {
        $this->storePhotosFor($this->product);
    }

    protected function storePhotosFor(?Product $target): void
    {
        if (empty($this->photos) || ! $target) {
            $this->ensurePrimary();

            return;
        }

        $imageService = app(ImageService::class);
        $hasPrimary = ProductImage::where('product_id', $target->id)->where('is_primary', true)->exists();
        $lastSort = (int) ProductImage::where('product_id', $target->id)->max('sort_order');

        foreach ($this->photos as $photo) {
            $path = $imageService->store($photo, 'products/'.$target->id);

            $isPrimary = ! $hasPrimary && $lastSort === 0 && ProductImage::where('product_id', $target->id)->count() === 0;

            ProductImage::create([
                'store_id' => $target->store_id ?? StoreContext::id(),
                'product_id' => $target->id,
                'path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => ++$lastSort,
            ]);

            $hasPrimary = $hasPrimary || $isPrimary;
        }

        $this->photos = [];
        $this->ensurePrimaryFor($target);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $storeScope = \App\Models\Scopes\StoreScope::class;

        if ($this->isSuperAdmin()) {
            $resolvedStore = $this->resolveStoreId();
            $categories = Category::withoutGlobalScope($storeScope)
                ->when($resolvedStore, fn ($q) => $q->where('store_id', $resolvedStore))
                ->with(['children' => fn ($q) => $q->withoutGlobalScope($storeScope)])
                ->orderBy('name')
                ->get();
        } else {
            $categories = Category::with('children')->orderBy('name')->get();
        }

        $needsStore = $this->isSuperAdmin();
        $stores = $needsStore ? \App\Models\Store::where('status', 'active')->orderBy('name')->get() : collect();
        $colorPresets = self::colorPresets();
        $sizePresets = self::sizePresets();
        $statuses = [
            'available' => 'Disponible',
            'reserved' => 'Réservé',
            'rented' => 'Loué',
            'returning' => 'En retour',
            'cleaning' => 'En nettoyage',
            'repair' => 'En réparation',
            'lost' => 'Perdu',
            'damaged' => 'Endommagé',
            'offline' => 'Hors service',
        ];

        return view('livewire.products.product-form', compact('categories', 'statuses', 'needsStore', 'stores', 'colorPresets', 'sizePresets'));
    }

    public static function colorPresets(): array
    {
        return [
            'Rouge' => '#ef4444',
            'Bordeaux' => '#7f1d1d',
            'Bleu' => '#3b82f6',
            'Bleu marine' => '#1e3a8a',
            'Vert' => '#22c55e',
            'Noir' => '#111827',
            'Blanc' => '#e5e7eb',
            'Gris' => '#9ca3af',
            'Beige' => '#d6c7a1',
            'Jaune' => '#eab308',
            'Or' => '#d4af37',
            'Violet' => '#8b5cf6',
            'Rose' => '#ec4899',
            'Marron' => '#92400e',
        ];
    }

    public static function sizePresets(): array
    {
        return ['XS', 'S', 'M', 'L', 'XL', 'XXL', '36', '38', '40', '42', '44', '46', '48', '50', '52'];
    }
}