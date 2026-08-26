<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\StoreContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PackReturnController extends Controller
{
    public function show(Rental $rental): View
    {
        $rental->load(['customer', 'items.product', 'items.pack', 'user']);
        $store = StoreContext::store();

        return view('contracts.pack-return', compact('rental', 'store'));
    }

    public function pdf(Rental $rental)
    {
        $rental->load(['customer', 'items.product', 'items.pack', 'user']);
        $store = StoreContext::store();

        $pdf = Pdf::loadView('contracts.pack-return', compact('rental', 'store'));

        return $pdf->download('retour-pack-'.$rental->reference.'.pdf');
    }
}
