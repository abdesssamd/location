<x-layouts.admin title="Audit">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="page-title">Journal d'audit</h1>
                <p class="page-subtitle">Toutes les actions enregistrées sur la plateforme.</p>
            </div>
        </div>

        <form method="GET" class="card p-4">
            <div class="grid gap-3 md:grid-cols-3">
                <select name="store_id" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les magasins</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
                <select name="action" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Toutes les actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Filtrer</button>
                    <a href="{{ route('admin.audits.index') }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Réinitialiser</a>
                </div>
            </div>
        </form>

        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Magasin</th>
                        <th class="px-4 py-3">Utilisateur</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Cible</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($audits as $audit)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5 text-zinc-500">{{ $audit->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5">{{ $audit->store?->name ?? '<Système>' }}</td>
                            <td class="px-4 py-2.5">{{ $audit->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5"><span class="badge-zinc">{{ $audit->action }}</span></td>
                            <td class="px-4 py-2.5 font-mono text-xs text-zinc-500">{{ class_basename($audit->auditable_type) }} #{{ $audit->auditable_id }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-zinc-400">{{ $audit->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Aucune entrée d'audit.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-zinc-100 p-4">
                {{ $audits->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>