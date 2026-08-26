<x-layouts.app title="Dashboard">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="page-title">{{ __('Dashboard') }}</h1>
                <p class="page-subtitle">{{ __('Bienvenue,') }} {{ auth()->user()->name }}.</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Articles</p>
                <p class="mt-1 text-2xl font-semibold">{{ $stats['products'] }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Clients</p>
                <p class="mt-1 text-2xl font-semibold">0</p>
            </div>
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Locations en cours</p>
                <p class="mt-1 text-2xl font-semibold">0</p>
            </div>
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Chiffre d'affaires</p>
                <p class="mt-1 text-2xl font-semibold">0 DA</p>
            </div>
        </div>

        <div class="card card-pad">
            <p class="text-sm text-zinc-500">Le module statistiques complet arrive dans les phases suivantes.</p>
        </div>
    </div>
</x-layouts.app>