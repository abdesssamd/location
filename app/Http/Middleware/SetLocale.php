<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale') ?? auth()->user()?->locale ?? config('app.locale');

        if (in_array($locale, ['fr', 'ar', 'en'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}