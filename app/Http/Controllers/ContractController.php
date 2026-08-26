<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\StoreContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function show(Rental $rental): View
    {
        $this->authorize('view', $rental);
        $rental->load(['customer', 'items.product', 'items.pack', 'user']);
        $store = StoreContext::store();

        return view('contracts.contract', compact('rental', 'store'));
    }

    public function pdf(Rental $rental)
    {
        $this->authorize('view', $rental);
        $rental->load(['customer', 'items.product', 'items.pack', 'user']);
        $store = StoreContext::store();

        $pdf = Pdf::loadView('contracts.contract', compact('rental', 'store'));

        return $pdf->download('contrat-'.$rental->reference.'.pdf');
    }
}