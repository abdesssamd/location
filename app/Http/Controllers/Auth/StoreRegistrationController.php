<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\StoreRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StoreRegistrationController extends Controller
{
    public function create(): View
    {
        abort_unless(PlatformSetting::signupEnabled(), 404);

        return view('auth.register-store', [
            'trialDays' => PlatformSetting::trialDays(),
            'autoApproves' => PlatformSetting::autoApproves(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PlatformSetting::signupEnabled(), 404);

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'wilaya' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'conditions' => ['accepted'],
        ], [], [
            'store_name' => 'nom du magasin',
            'conditions' => 'conditions d\'utilisation',
        ]);

        $result = StoreRegistrar::register($data);

        // Acceptation automatique : le magasin entre directement en démonstration.
        if ($result['approved']) {
            Auth::login($result['user']);
            $request->session()->regenerate();

            return redirect()
                ->route('dashboard')
                ->with('status', 'Bienvenue ! Votre magasin est actif pour '.PlatformSetting::trialDays().' jours de démonstration.')
                ->with('new_token', $result['token']?->plainText);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Demande enregistrée. Votre magasin sera activé dès validation par notre équipe, vous recevrez un e-mail à '.$result['user']->email.'.');
    }
}
