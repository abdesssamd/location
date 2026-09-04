<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        // Les montants consolidés ne sont ni calculés ni envoyés à la vue sans
        // la permission : un employé n'a pas à connaître le chiffre d'affaires
        // du magasin, même via le HTML de la page.
        $canSeeFinance = (bool) auth()->user()?->can('finance.view');

        $revenueToday = $canSeeFinance
            ? (int) Payment::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('type', '!=', 'refund')->whereDate('date', now()->toDateString())->sum('amount')
            : 0;
        $activeRentals = Rental::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', 'active')->count();
        $reserved = Rental::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', 'reserved')->count();
        $productCount = Product::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->count();
        $customerCount = Customer::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->count();
        $lowStock = Product::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('quantity', '>', 0)->where('quantity', '<=', 2)->count();

        $upcomingReturns = Rental::with('customer')
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
            ->orderBy('end_date')
            ->limit(6)
            ->get();

        $recentPayments = $canSeeFinance
            ? Payment::with(['rental.customer'])
                ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                ->latest()
                ->limit(6)
                ->get()
            : collect();

        $lowStockProducts = Product::with('images')
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->where('quantity', '>', 0)
            ->where('quantity', '<=', 2)
            ->orderBy('quantity')
            ->limit(6)
            ->get();

        $packBaseQuery = RentalItem::query()
            ->where('is_pack_component', true)
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid)));

        $packRevenue = $canSeeFinance ? (int) $packBaseQuery->sum('line_total') : 0;
        $packSavings = $canSeeFinance
            ? (int) Rental::query()
                ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                ->sum('pack_savings')
            : 0;

        $packsActive = (int) RentalItem::query()
            ->where('is_pack_component', true)
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', 'active'))
            ->distinct('rental_id')
            ->count('rental_id');

        $packsReserved = (int) RentalItem::query()
            ->where('is_pack_component', true)
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', 'reserved'))
            ->distinct('rental_id')
            ->count('rental_id');

        // Le classement des packs reste utile sans les montants : on ne calcule
        // la colonne revenue que pour les profils autorisés.
        $topPacks = RentalItem::query()
            ->where('is_pack_component', true)
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', '!=', 'cancelled'))
            ->selectRaw('COALESCE(NULLIF(pack_name, \'\'), pack_id) as pack_label, COUNT(DISTINCT rental_id) as rentals_count, '.($canSeeFinance ? 'SUM(line_total)' : '0').' as revenue')
            ->groupBy('pack_label')
            ->orderByDesc('rentals_count')
            ->limit(5)
            ->get();

        $topPackProducts = RentalItem::query()
            ->where('is_pack_component', true)
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', '!=', 'cancelled'))
            ->selectRaw('product_id, SUM(quantity) as used_qty')
            ->groupBy('product_id')
            ->orderByDesc('used_qty')
            ->with('product')
            ->limit(6)
            ->get();

        $lateRentals = Rental::query()
            ->with('customer')
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->where('status', 'active')
            ->whereDate('end_date', '<', now()->toDateString())
            ->orderBy('end_date')
            ->get();

        $lateCount = $lateRentals->count();

        $monthly = collect(range(11, 0))->map(function ($i) use ($storeId, $canSeeFinance) {
            $date = now()->startOfMonth()->subMonths($i);

            return [
                'label' => $date->translatedFormat('M'),
                'amount' => $canSeeFinance
                    ? (int) Payment::when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                        ->where('type', '!=', 'refund')
                        ->whereDate('date', '>=', $date->copy()->startOfMonth()->toDateString())
                        ->whereDate('date', '<=', $date->copy()->endOfMonth()->toDateString())
                        ->sum('amount')
                    : 0,
            ];
        });

        $topProductsChart = RentalItem::query()
            ->whereHas('rental', fn ($q) => $q->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->where('status', '!=', 'cancelled'))
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(6)
            ->with('product')
            ->get();

        $chartLabels = $monthly->pluck('label')->all();
        $chartRevenue = $monthly->pluck('amount')->all();
        $topProductLabels = $topProductsChart->map(fn ($r) => $r->product?->name ?? '—')->all();
        $topProductQty = $topProductsChart->pluck('qty')->all();

        return view('livewire.dashboard', compact(
            'revenueToday', 'activeRentals', 'reserved', 'productCount', 'customerCount', 'lowStock',
            'upcomingReturns', 'recentPayments', 'lowStockProducts',
            'packRevenue', 'packSavings', 'packsActive', 'packsReserved', 'topPacks', 'topPackProducts',
            'lateRentals', 'lateCount', 'chartLabels', 'chartRevenue', 'topProductLabels', 'topProductQty',
            'canSeeFinance'
        ));
    }
}