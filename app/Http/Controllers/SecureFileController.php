<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\RentalItem;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sert les pièces jointes sensibles (preuves de paiement, photos de retour,
 * justificatifs d'abonnement) après contrôle du magasin et de la permission.
 *
 * Ces fichiers vivent sur le disque « local » : ils ne sont donc jamais
 * accessibles directement par une URL /storage/…
 */
class SecureFileController extends Controller
{
    /** Preuve de paiement d'une location. */
    public function payment(Payment $payment, int $index): StreamedResponse
    {
        $this->authorize('view', $payment);

        return $this->stream(($payment->proof_image_paths ?? [])[$index] ?? null);
    }

    /** Photo d'état d'un article au retour. */
    public function rentalReturn(RentalItem $item, int $index): StreamedResponse
    {
        abort_if($item->rental === null, 404);
        $this->authorize('view', $item->rental);

        return $this->stream(($item->return_image_paths ?? [])[$index] ?? null);
    }

    /** Justificatif de paiement d'abonnement : le magasin concerné ou le super admin. */
    public function subscriptionProof(SubscriptionPayment $payment): StreamedResponse
    {
        $user = auth()->user();

        abort_unless(
            $user->is_super_admin || (int) $user->store_id === (int) $payment->store_id,
            403
        );

        return $this->stream($payment->proof_path);
    }

    /**
     * Les fichiers créés avant le durcissement vivent encore sur le disque public :
     * on les sert par la même route contrôlée plutôt que de casser l'historique.
     */
    protected function stream(?string $path): StreamedResponse
    {
        abort_if($path === null || $path === '', 404);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path);
            }
        }

        abort(404);
    }
}
