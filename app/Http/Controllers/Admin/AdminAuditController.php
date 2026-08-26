<?php

namespace App\Http\Controllers\Admin;

use App\Models\Audit;
use App\Models\Store;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAuditController extends Controller
{
    public function index(): View
    {
        $audits = Audit::with(['user', 'store'])
            ->when(request('store_id'), fn ($q) => $q->where('store_id', request('store_id')))
            ->when(request('action'), fn ($q) => $q->where('action', request('action')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stores = Store::orderBy('name')->get();
        $actions = Audit::query()->select('action')->distinct()->pluck('action')->sort()->values();

        return view('admin.audits.index', compact('audits', 'stores', 'actions'));
    }

    public function exportStore(Store $store): StreamedResponse
    {
        $payload = [
            'store' => $store->only(['name', 'slug', 'address', 'wilaya', 'commune', 'phone', 'email', 'currency', 'tax_rate']),
            'products' => $store->products->map(fn ($p) => $p->only(['reference', 'name', 'category_id', 'brand', 'size', 'color', 'rental_price', 'caution_price', 'quantity', 'status'])),
            'customers' => $store->customers->map(fn ($c) => $c->only(['first_name', 'last_name', 'phone', 'email', 'cin', 'address'])),
            'rentals' => $store->rentals->map(fn ($r) => $r->only(['reference', 'customer_id', 'status', 'start_date', 'end_date', 'total', 'paid_amount'])),
            'payments' => $store->payments->map(fn ($p) => $p->only(['reference', 'rental_id', 'amount', 'method', 'type', 'date'])),
            'exported_at' => now()->toDateTimeString(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, 'export-'.$store->slug.'-'.now()->format('Ymd-His').'.json', ['Content-Type' => 'application/json']);
    }
}