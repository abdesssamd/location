<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['fr', 'ar', 'en'], true)) {
            abort(404);
        }

        session(['locale' => $locale]);

        if ($user = $request->user()) {
            $user->update(['locale' => $locale]);
        }

        return redirect()->back();
    }
}