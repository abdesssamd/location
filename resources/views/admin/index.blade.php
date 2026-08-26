<x-layouts.admin title="Admin">
    @php
        $stats = [
            'stores' => \App\Models\Store::count(),
            'activeStores' => \App\Models\Store::where('status', 'active')->count(),
            'suspendedStores' => \App\Models\Store::where('status', 'suspended')->count(),
            'users' => \App\Models\User::where('is_super_admin', false)->count(),
            'products' => \App\Models\Product::count(),
        ];
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="page-title">Tableau de bord plateforme</h1>
            <p class="page-subtitle">Vue globale de tous les magasins.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Magasins</p>
                <p class="mt-1 text-2xl font-semibold">{{ $stats['stores'] }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Actifs</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $stats['activeStores'] }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Suspendus</p>
                <p class="mt-1 text-2xl font-semibold text-rose-600">{{ $stats['suspendedStores'] }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Utilisateurs</p>
                <p class="mt-1 text-2xl font-semibold">{{ $stats['users'] }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Articles</p>
                <p class="mt-1 text-2xl font-semibold">{{ $stats['products'] }}</p>
            </div>
        </div>

        <div class="flex">
            <a href="{{ route('admin.stores.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                <flux:icon.plus variant="mini" /> Créer un magasin
            </a>
        </div>
    </div>
</x-layouts.admin>