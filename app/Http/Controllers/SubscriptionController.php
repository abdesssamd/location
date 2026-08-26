<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\StoreContext;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $storeId = auth()->user()->store_id ?? StoreContext::id();
        $service = SubscriptionService::store($storeId);

        $payments = SubscriptionPayment::with('plan')
            ->where('store_id', $storeId)
            ->latest()
            ->limit(10)
            ->get();

        return view('subscription.index', [
            'service' => $service,
            'subscription' => $service->subscription(),
            'plan' => $service->plan(),
            'usage' => $service->usage(),
            'payments' => $payments,
            'methods' => SubscriptionPayment::METHODS,
            'token' => \App\Models\StoreToken::where('store_id', $storeId)->where('status', 'active')->first(),
        ]);
    }

    public function plans(): View
    {
        return view('subscription.plans', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'service' => SubscriptionService::store(auth()->user()->store_id ?? StoreContext::id()),
            'featureLabels' => Plan::featureLabels(),
        ]);
    }

    /**
     * Choisir un plan : crée un paiement en attente de validation par le super admin.
     */
    public function subscribe(Request $request, Plan $plan): RedirectResponse
    {
        $storeId = auth()->user()->store_id ?? StoreContext::id();

        $payment = SubscriptionPayment::create([
            'store_id' => $storeId,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'method' => 'cash',
            'status' => SubscriptionPayment::STATUS_PENDING,
            'reference' => 'SUB-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6)),
            'notes' => 'Demande de souscription au plan '.$plan->name,
        ]);

        return redirect()
            ->route('subscription.index')
            ->with('status', "Demande enregistrée pour le plan {$plan->name}. Elle sera activée après validation du paiement (réf. {$payment->reference}).");
    }

    /**
     * Paiement hors ligne : le magasin soumet la preuve, le super admin approuve.
     */
    public function payOffline(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'method' => ['required', 'in:'.implode(',', array_keys(SubscriptionPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'image', 'max:4096'],
        ]);

        $storeId = auth()->user()->store_id ?? StoreContext::id();
        $plan = Plan::findOrFail($data['plan_id']);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs/'.$storeId, 'public');
        }

        SubscriptionPayment::create([
            'store_id' => $storeId,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'method' => $data['method'],
            'status' => SubscriptionPayment::STATUS_PENDING,
            'reference' => $data['reference'] ?: ('SUB-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6))),
            'proof_path' => $proofPath,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('subscription.index')
            ->with('status', 'Preuve de paiement envoyée. Votre abonnement sera activé après vérification par l\'administrateur.');
    }
}