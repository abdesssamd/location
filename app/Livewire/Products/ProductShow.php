<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProductShow extends Component
{
    public Product $product;

    public ?int $selectedImageId = null;

    /** Mois affiché par le planning de disponibilité (format Y-m). */
    public string $planningMonth = '';

    /** Nombre d'exemplaires recherchés : un jour est « libre » s'il en reste autant. */
    public int $planningQuantity = 1;

    public function mount(Product $product): void
    {
        $this->authorize('view', $product);

        // Pas de ->limit() dans l'eager load : MariaDB < 10.2 ne supporte pas les fonctions fenêtrées
        $this->product = $product->load(['images', 'category', 'stockMovements']);
        $this->selectedImageId = $this->product->primaryImage()?->id;
        $this->planningMonth = now()->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->planningMonth = \Carbon\Carbon::createFromFormat('Y-m', $this->planningMonth)
            ->startOfMonth()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->planningMonth = \Carbon\Carbon::createFromFormat('Y-m', $this->planningMonth)
            ->startOfMonth()->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->planningMonth = now()->format('Y-m');
    }

    /**
     * Disponibilité jour par jour du mois affiché.
     *
     * Chaque jour est évalué seul (du jour au lendemain) : c'est ce qui permet
     * de repérer un trou d'un jour entre deux locations, invisible si l'on
     * interrogeait le mois d'un bloc.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function planningDays(): array
    {
        $service = app(\App\Services\AvailabilityService::class);
        $month = \Carbon\Carbon::createFromFormat('Y-m', $this->planningMonth)->startOfMonth();
        $today = now()->startOfDay();
        $days = [];

        for ($cursor = $month->copy(); $cursor->month === $month->month; $cursor->addDay()) {
            $date = $cursor->toDateString();
            $free = $service->freeBetween($this->product, $date, $cursor->copy()->addDay()->toDateString());

            $days[] = [
                'date' => $date,
                'day' => $cursor->day,
                'free' => $free,
                'is_free' => $free >= max(1, $this->planningQuantity),
                'is_past' => $cursor->lt($today),
                'is_today' => $cursor->isSameDay($today),
            ];
        }

        return $days;
    }

    public function selectImage(int $imageId): void
    {
        $this->selectedImageId = $imageId;
    }

    public function changeStatus(string $status): void
    {
        $this->authorize('changeStatus', $this->product);

        if (! in_array($status, array_keys(Product::statusLabels()), true)) {
            return;
        }

        $old = $this->product->getAttributes();
        $this->product->update(['status' => $status]);
        AuditLogger::updated($this->product, $old, 'product.status_changed');
        session()->flash('status', 'Statut mis à jour.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $selectedImage = $this->product->images->firstWhere('id', $this->selectedImageId) ?? $this->product->primaryImage();

        $month = \Carbon\Carbon::createFromFormat('Y-m', $this->planningMonth)->startOfMonth();

        // La grille commence lundi : décalage à combler avant le 1er du mois.
        $leadingBlanks = ($month->dayOfWeekIso - 1);

        return view('livewire.products.product-show', [
            'selectedImage' => $selectedImage,
            'statuses' => Product::statusLabels(),
            'planningDays' => $this->planningDays(),
            'planningLabel' => $month->translatedFormat('F Y'),
            'leadingBlanks' => $leadingBlanks,
        ]);
    }
}