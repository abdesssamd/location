<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'LouerPro — Logiciel de location de costumes et matériel événementiel'])
    <meta name="description" content="Gérez vos articles, packs, réservations, contrats et cautions depuis une seule application. Pensé pour les magasins de location de costumes, robes et matériel de fête.">
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased">

{{-- ============ En-tête ============ --}}
<header class="sticky top-0 z-50 border-b border-zinc-200/70 bg-white/85 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-5">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <span class="flex size-9 items-center justify-center rounded-xl bg-brand-900 shadow-sm">
                <x-app-logo-icon class="size-5 fill-current text-white" />
            </span>
            <span class="text-lg font-semibold tracking-tight">LouerPro</span>
        </a>

        <nav class="hidden items-center gap-7 text-sm text-zinc-600 md:flex">
            <a href="#fonctionnalites" class="transition-colors hover:text-zinc-900">Fonctionnalités</a>
            <a href="#workflow" class="transition-colors hover:text-zinc-900">Comment ça marche</a>
            <a href="#tarifs" class="transition-colors hover:text-zinc-900">Tarifs</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="btn btn-ghost">Se connecter</a>
            @if ($signupEnabled)
                <a href="{{ route('store.register') }}" class="btn btn-primary">Créer mon magasin</a>
            @endif
        </div>
    </div>
</header>

{{-- ============ Hero ============ --}}
<section class="relative overflow-hidden bg-brand-950 text-white">
    <div aria-hidden="true" class="pointer-events-none absolute -right-40 -top-40 size-[34rem] rounded-full bg-brand-700/40 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-56 -left-40 size-[30rem] rounded-full bg-wine-800/40 blur-3xl"></div>

    <div class="relative mx-auto grid max-w-6xl gap-14 px-5 py-20 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:py-28">
        <div class="animate-fade-up">
            @if ($trialDays > 0 && $signupEnabled)
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium text-brand-50">
                    <span class="size-1.5 rounded-full bg-emerald-400"></span>
                    {{ $trialDays }} jours d'essai offerts — sans carte bancaire
                </span>
            @endif

            <h1 class="mt-5 font-display text-4xl font-semibold leading-[1.1] tracking-tight sm:text-5xl lg:text-[3.4rem]">
                Votre magasin de location,<br class="hidden sm:block" />
                <span class="text-brand-200">enfin sous contrôle.</span>
            </h1>

            <p class="mt-5 max-w-xl text-base leading-relaxed text-brand-100/80">
                Costumes, robes, chaussures, décoration, tables et chaises&nbsp;: suivez chaque article,
                chaque réservation et chaque caution. Le contrat se génère tout seul, le calendrier
                empêche les doubles réservations, et vous savez à tout moment ce qui doit rentrer aujourd'hui.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                @if ($signupEnabled)
                    <a href="{{ route('store.register') }}" class="btn bg-white text-brand-900 shadow-sm hover:bg-brand-50">
                        Créer mon magasin
                    </a>
                @endif
                <a href="{{ route('login') }}" class="btn border border-white/25 text-white hover:bg-white/10">
                    J'ai déjà un compte
                </a>
            </div>

            <dl class="mt-12 grid max-w-md grid-cols-3 gap-6 border-t border-white/10 pt-6 text-sm">
                <div>
                    <dt class="text-brand-200/70">Articles</dt>
                    <dd class="mt-1 font-display text-2xl font-semibold">Illimités</dd>
                </div>
                <div>
                    <dt class="text-brand-200/70">Contrats</dt>
                    <dd class="mt-1 font-display text-2xl font-semibold">PDF auto</dd>
                </div>
                <div>
                    <dt class="text-brand-200/70">Langues</dt>
                    <dd class="mt-1 font-display text-2xl font-semibold">FR / AR</dd>
                </div>
            </dl>
        </div>

        {{-- Aperçu produit : une fiche de location telle qu'elle apparaît dans l'application --}}
        <div class="animate-fade-up rounded-3xl border border-white/10 bg-white/95 p-5 text-zinc-900 shadow-2xl shadow-brand-950/40 sm:p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-xs text-zinc-500">LOC-2026-0184</p>
                    <p class="mt-1 text-base font-semibold">Pack Mariage Élégance</p>
                </div>
                <span class="badge-orange">Loué</span>
            </div>

            <div class="mt-5 space-y-2.5">
                @foreach ([['Costume 3 pièces — Bleu nuit', 'Taille 52', 'badge-green', 'Sorti'], ['Chaussures cuir noir', 'Pointure 43', 'badge-green', 'Sorti'], ['Cravate soie bordeaux', 'Unique', 'badge-yellow', 'Nettoyage']] as [$name, $variant, $badge, $state])
                    <div class="flex items-center gap-3 rounded-xl border border-zinc-200/80 px-3 py-2.5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-800">{{ strtoupper(substr($name, 0, 2)) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ $name }}</span>
                            <span class="block text-xs text-zinc-500">{{ $variant }}</span>
                        </span>
                        <span class="{{ $badge }}">{{ $state }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-3 gap-3 border-t border-zinc-200 pt-4 text-center">
                <div>
                    <p class="text-xs text-zinc-500">Total</p>
                    <p class="mt-0.5 font-semibold tabular-nums">4 500 DA</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500">Avance</p>
                    <p class="mt-0.5 font-semibold tabular-nums text-emerald-600">2 000 DA</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500">Caution</p>
                    <p class="mt-0.5 font-semibold tabular-nums">6 000 DA</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-xl bg-brand-50/70 px-3 py-2.5 text-xs text-brand-900">
                <span>Retour prévu</span>
                <span class="font-semibold">samedi 12 septembre</span>
            </div>
        </div>
    </div>
</section>

{{-- ============ Workflow réel ============ --}}
<section id="workflow" class="border-b border-zinc-200/70 bg-white py-20">
    <div class="mx-auto max-w-6xl px-5">
        <div class="max-w-2xl">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-brand-700">Le parcours d'une location</p>
            <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Du devis au retour de caution, sans papier volant</h2>
            <p class="mt-3 text-zinc-600">Chaque étape est enregistrée&nbsp;: vous retrouvez qui a loué quoi, quand, à quel prix, et dans quel état l'article est revenu.</p>
        </div>

        <ol class="stagger mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                ['1', 'Réservation', 'Client, dates, articles ou pack. La disponibilité est vérifiée sur la période.'],
                ['2', 'Contrat', 'Généré en PDF avec vos conditions, prix, caution et état des articles.'],
                ['3', 'Sortie', 'Les articles passent en « loué », le stock engagé est visible au calendrier.'],
                ['4', 'Retour', 'Contrôle article par article : bon état, sale, endommagé, perdu, avec photos.'],
                ['5', 'Règlement', 'Reste à payer, pénalité de retard, frais de dommage, restitution de caution.'],
            ] as [$step, $title, $text])
                <li class="card card-pad">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-900 font-mono text-sm text-white">{{ $step }}</span>
                    <h3 class="mt-4 text-sm font-semibold">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-zinc-600">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ============ Fonctionnalités ============ --}}
<section id="fonctionnalites" class="bg-zinc-50/70 py-20">
    <div class="mx-auto max-w-6xl px-5">
        <div class="max-w-2xl">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-brand-700">Fonctionnalités</p>
            <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Pensé pour un magasin de location, pas pour une boutique</h2>
        </div>

        <div class="stagger mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Articles &amp; photos', 'Référence, taille, couleur, marque, caution, galerie photo compressée automatiquement. Chaque variante de taille devient un article suivi séparément.'],
                ['Packs', 'Composez « Pack Mariage » à prix remisé. Le pack n\'a pas de stock propre : la disponibilité de chaque article le compose réellement.'],
                ['Calendrier &amp; conflits', 'Vue jour, semaine, mois. Les chevauchements de dates sont bloqués avant la réservation, pas après.'],
                ['Contrats PDF', 'Numéro, magasin, client, articles, dates, caution, conditions et signature. Impression, téléchargement, envoi.'],
                ['Paiements &amp; cautions', 'Avance, paiement partiel, reste dû, remboursement de caution, pénalité de retard et frais de dommage.'],
                ['QR code &amp; scan', 'Chaque article porte son QR code : un scan affiche sa fiche, son état, sa location en cours et son historique.'],
                ['Clients', 'Fiche complète avec historique des locations, retards et dommages. Recherche par nom, téléphone ou CIN.'],
                ['Rôles &amp; permissions', 'Gérant, caissier, magasinier, employé : chacun n\'accède qu\'à ce qui le concerne, action par action.'],
                ['Rapports', 'Chiffre d\'affaires, articles et packs les plus loués, retards, cautions en circulation. Export PDF et CSV.'],
            ] as [$title, $text])
                <article class="card card-pad">
                    <h3 class="text-sm font-semibold">{!! $title !!}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600">{!! $text !!}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ Tarifs ============ --}}
<section id="tarifs" class="bg-white py-20">
    <div class="mx-auto max-w-6xl px-5">
        <div class="max-w-2xl">
            <p class="text-xs font-medium uppercase tracking-[0.16em] text-brand-700">Tarifs</p>
            <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Un abonnement par magasin</h2>
            @if ($trialDays > 0 && $signupEnabled)
                <p class="mt-3 text-zinc-600">Tous les plans commencent par <strong>{{ $trialDays }} jours d'essai</strong>. Vous ne payez qu'ensuite, et vos données restent les vôtres.</p>
            @endif
        </div>

        <div class="mt-12 grid gap-5 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <div class="card card-pad flex flex-col {{ $plan->is_popular ? 'ring-2 ring-brand-800' : '' }}">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-xl font-semibold">{{ $plan->name }}</h3>
                        @if ($plan->is_popular)
                            <span class="badge-blue">Le plus choisi</span>
                        @endif
                    </div>

                    <p class="mt-2 text-sm text-zinc-600">{{ $plan->description }}</p>

                    <p class="mt-6">
                        <span class="font-display text-3xl font-semibold tracking-tight">{{ money($plan->price) }}</span>
                        <span class="text-sm text-zinc-500">/ {{ $plan->billing_period === 'yearly' ? 'an' : 'mois' }}</span>
                    </p>

                    <ul class="mt-6 flex-1 space-y-2 text-sm text-zinc-700">
                        <li class="flex items-start gap-2">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-700"></span>
                            {{ $plan->limitLabel($plan->max_products) }} articles
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-700"></span>
                            {{ $plan->limitLabel($plan->max_customers) }} clients
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-700"></span>
                            {{ $plan->limitLabel($plan->max_users) }} utilisateurs
                        </li>
                        @foreach (array_slice($plan->features ?? [], 0, 5) as $feature)
                            <li class="flex items-start gap-2">
                                <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-700"></span>
                                {{ \App\Models\Plan::featureLabels()[$feature] ?? $feature }}
                            </li>
                        @endforeach
                    </ul>

                    @if ($signupEnabled)
                        <a href="{{ route('store.register') }}" class="btn {{ $plan->is_popular ? 'btn-primary' : 'btn-secondary' }} mt-6 w-full">Commencer</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ Appel final ============ --}}
@if ($signupEnabled)
    <section class="bg-brand-950 py-20 text-white">
        <div class="mx-auto max-w-3xl px-5 text-center">
            <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Ouvrez votre magasin en ligne aujourd'hui</h2>
            <p class="mt-4 text-brand-100/80">
                Créez votre compte, ajoutez vos premiers articles et éditez votre premier contrat dans la foulée.
                @if ($trialDays > 0) {{ $trialDays }} jours pour vous décider. @endif
            </p>
            <a href="{{ route('store.register') }}" class="btn mt-8 bg-white text-brand-900 hover:bg-brand-50">Créer mon magasin</a>
        </div>
    </section>
@endif

<footer class="border-t border-zinc-200 bg-white py-10">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-5 text-sm text-zinc-500 sm:flex-row">
        <p>© {{ now()->year }} LouerPro — Logiciel de location pour magasins.</p>
        <div class="flex items-center gap-5">
            <a href="{{ route('login') }}" class="transition-colors hover:text-zinc-900">Connexion</a>
            @if ($signupEnabled)
                <a href="{{ route('store.register') }}" class="transition-colors hover:text-zinc-900">Créer un magasin</a>
            @endif
        </div>
    </div>
</footer>

</body>
</html>
