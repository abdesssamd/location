<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Créer mon magasin — LouerPro'])
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased">

<div class="grid min-h-svh lg:grid-cols-[1fr_1.1fr]">

    {{-- Colonne argumentaire --}}
    <aside class="relative hidden overflow-hidden bg-brand-950 p-10 text-white lg:flex lg:flex-col lg:justify-between">
        <div aria-hidden="true" class="pointer-events-none absolute -right-32 -top-32 size-96 rounded-full bg-brand-700/40 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 -left-24 size-80 rounded-full bg-wine-800/40 blur-3xl"></div>

        <a href="{{ route('home') }}" class="relative flex items-center gap-2.5">
            <span class="flex size-9 items-center justify-center rounded-xl bg-white/10">
                <x-app-logo-icon class="size-5 fill-current text-white" />
            </span>
            <span class="text-lg font-semibold tracking-tight">LouerPro</span>
        </a>

        <div class="relative max-w-md">
            <h2 class="font-display text-3xl font-semibold leading-tight tracking-tight">
                Tout votre magasin de location dans une seule application.
            </h2>
            <ul class="mt-8 space-y-4 text-sm text-brand-100/85">
                @foreach ([
                    'Vos articles, tailles et photos, avec QR code sur chaque pièce',
                    'Packs à prix remisé, construits sur le stock réel',
                    'Calendrier des réservations sans double location',
                    'Contrats PDF, cautions, avances et pénalités de retard',
                ] as $point)
                    <li class="flex items-start gap-3">
                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-300"></span>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="relative text-xs text-brand-200/60">
            @if ($trialDays > 0)
                {{ $trialDays }} jours d'essai — aucune carte bancaire demandée.
            @else
                Votre espace est prêt dès l'activation de votre abonnement.
            @endif
        </p>
    </aside>

    {{-- Colonne formulaire --}}
    <main class="flex items-center justify-center px-5 py-12 sm:px-10">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2 text-sm text-zinc-500 transition-colors hover:text-zinc-900 lg:hidden">
                ← Retour à l'accueil
            </a>

            <h1 class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">Créer mon magasin</h1>
            <p class="mt-2 text-sm text-zinc-600">
                @if ($autoApproves)
                    Votre espace est créé immédiatement{{ $trialDays > 0 ? ', avec '.$trialDays.' jours d\'essai' : '' }}.
                @else
                    Votre demande est vérifiée par notre équipe, puis votre espace est activé{{ $trialDays > 0 ? ' avec '.$trialDays.' jours d\'essai' : '' }}.
                @endif
            </p>

            <x-flash />

            <form method="POST" action="{{ route('store.register.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="store_name" class="block text-sm font-medium text-zinc-900">Nom du magasin</label>
                    <input id="store_name" name="store_name" value="{{ old('store_name') }}" required autofocus
                           placeholder="Ex. Élégance Location"
                           class="mt-1.5 w-full rounded-xl border border-zinc-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600" />
                    @error('store_name') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-zinc-900">Votre nom</label>
                        <input id="name" name="name" value="{{ old('name') }}" required
                               class="mt-1.5 w-full rounded-xl border border-zinc-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600" />
                        @error('name') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-zinc-900">Téléphone</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel"
                               placeholder="0550 00 00 00"
                               class="mt-1.5 w-full rounded-xl border border-zinc-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600" />
                        @error('phone') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="wilaya" class="block text-sm font-medium text-zinc-900">Wilaya <span class="text-zinc-400">(facultatif)</span></label>
                    <input id="wilaya" name="wilaya" value="{{ old('wilaya') }}"
                           class="mt-1.5 w-full rounded-xl border border-zinc-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600" />
                    @error('wilaya') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-900">E-mail de connexion</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                           class="mt-1.5 w-full rounded-xl border border-zinc-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600" />
                    @error('email') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-zinc-900">Mot de passe</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               class="mt-1.5 w-full rounded-xl border border-zinc-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600" />
                        @error('password') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-zinc-900">Confirmation</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               class="mt-1.5 w-full rounded-xl border border-zinc-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600" />
                    </div>
                </div>

                <label class="flex items-start gap-3 text-sm text-zinc-600">
                    <input type="checkbox" name="conditions" value="1" @checked(old('conditions'))
                           class="mt-0.5 size-4 rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                    <span>J'accepte les conditions d'utilisation et la politique de confidentialité.</span>
                </label>
                @error('conditions') <p class="-mt-3 text-xs text-rose-600">{{ $message }}</p> @enderror

                <button type="submit" class="btn btn-primary w-full">
                    {{ $autoApproves ? 'Créer mon magasin' : 'Envoyer ma demande' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-zinc-600">
                Vous avez déjà un compte ?
                <a href="{{ route('login') }}" class="font-medium text-brand-800 hover:underline">Se connecter</a>
            </p>
        </div>
    </main>
</div>

</body>
</html>
