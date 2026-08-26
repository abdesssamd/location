<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\Store;
use App\Models\StoreToken;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\AuditLogger;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AdminSubscriptionController extends Controller
{
    public function index(): View
    {
        $subscriptions = Subscription::with(['store', 'plan'])
            ->latest('updated_at')
            ->paginate(20);

        $pendingPayments = SubscriptionPayment::with(['store', 'plan'])
            ->where('status', SubscriptionPayment::STATUS_PENDING)
            ->latest()
            ->get();

        $stats = [
            'active_stores' => Store::where('status', 'active')->count(),
            'expired' => Subscription::where('status', Subscription::STATUS_EXPIRED)->count(),
            'pro' => Subscription::where('status', Subscription::STATUS_ACTIVE)->whereHas('plan', fn ($q) => $q->where('slug', 'pro'))->count(),
            'premium' => Subscription::where('status', Subscription::STATUS_ACTIVE)->whereHas('plan', fn ($q) => $q->where('slug', 'premium'))->count(),
            'revenue' => (int) SubscriptionPayment::where('status', SubscriptionPayment::STATUS_APPROVED)->sum('amount'),
            'expiring_soon' => Subscription::where('status', Subscription::STATUS_ACTIVE)
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->count(),
        ];

        return view('admin.subscriptions.index', compact('subscriptions', 'pendingPayments', 'stats'));
    }

    public function approve(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        abort_if($payment->status !== SubscriptionPayment::STATUS_PENDING, 422, 'Ce paiement a déjà été traité.');

        $plan = $payment->plan;
        $store = $payment->store;

        SubscriptionService::renew($store, $plan, auth()->id(), 'Paiement approuvé ('.$payment->reference.')');

        $payment->update([
            'status' => SubscriptionPayment::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLogger::log('subscription.payment_approved', $store, null, ['payment' => $payment->reference, 'plan' => $plan->name]);

        return back()->with('status', "Paiement approuvé. Abonnement {$plan->name} activé pour {$store->name}.");
    }

    public function reject(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        abort_if($payment->status !== SubscriptionPayment::STATUS_PENDING, 422, 'Ce paiement a déjà été traité.');

        $payment->update([
            'status' => SubscriptionPayment::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Paiement refusé.');
    }

    public function renew(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'months' => ['nullable', 'integer', 'min:1', 'max:36'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $subscription = SubscriptionService::renew($store, $plan, auth()->id());

        if (! empty($data['months']) && $data['months'] > $plan->months()) {
            $extra = $data['months'] - $plan->months();
            $subscription->update(['ends_at' => $subscription->ends_at->copy()->addMonths($extra)]);
        }

        return back()->with('status', "Abonnement {$plan->name} renouvelé jusqu'au ".$subscription->ends_at?->format('d/m/Y').'.');
    }

    public function changePlan(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        [$ok, $message] = SubscriptionService::changePlan($store, $plan, auth()->id());

        return back()->with($ok ? 'status' : 'error', $message);
    }

    public function regenerateToken(Request $request, Store $store): RedirectResponse
    {
        $token = SubscriptionService::generateToken($store, auth()->id());

        AuditLogger::log('store.token_regenerated', $store, null, ['token' => $token->token]);

        return back()->with('status', 'Nouveau token généré : '.$token->token.' L\'ancien est immédiatement invalide.');
    }

    public function revokeToken(Request $request, Store $store): RedirectResponse
    {
        StoreToken::where('store_id', $store->id)
            ->where('status', StoreToken::STATUS_ACTIVE)
            ->update(['status' => StoreToken::STATUS_REVOKED, 'revoked_at' => now()]);

        AuditLogger::log('store.token_revoked', $store);

        return back()->with('status', 'Token désactivé. Le magasin ne peut plus fonctionner tant qu\'un nouveau token n\'est pas généré.');
    }
}