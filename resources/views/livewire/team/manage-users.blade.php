<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="page-title">Équipe</h1>
                <p class="page-subtitle">Gérer les employés du magasin et leurs rôles.</p>
            </div>
            <button wire:click="openCreate" class="inline-flex items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                <flux:icon.plus variant="mini" /> Nouvel employé
            </button>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        @if ($showForm)
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">{{ $editingUserId ? 'Modifier l\'employé' : 'Nouvel employé' }}</h2>

                <form wire:submit="save" class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Nom complet</label>
                        <input wire:model="name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Email</label>
                        <input wire:model="email" type="email" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('email') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Téléphone</label>
                        <input wire:model="phone" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('phone') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Mot de passe {{ $editingUserId ? '(laisser vide pour ne pas changer)' : '' }}</label>
                        <input wire:model="password" type="password" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('password') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Rôle</label>
                        <select wire:model="role" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                            @foreach ($roles as $r)
                                <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="isActive" id="isActive" class="h-4 w-4 rounded border-zinc-300" />
                        <label for="isActive" class="text-sm text-zinc-700">Compte actif</label>
                    </div>
                    <div class="flex gap-3 sm:col-span-2">
                        <button type="submit" class="rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                        <button type="button" wire:click="closeForm" class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Annuler</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="border-b border-zinc-100 p-4">
                <div class="relative">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Rechercher un employé..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
            </div>

            <div class="divide-y divide-zinc-100">
                @forelse ($users as $user)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700">{{ $user->initials() }}</span>
                            <div>
                                <p class="flex items-center gap-2 text-sm font-medium text-zinc-900">
                                    {{ $user->name }}
                                    @if (!$user->is_active)
                                        <span class="badge-zinc">Inactif</span>
                                    @endif
                                </p>
                                <p class="text-xs text-zinc-500">{{ $user->email }} · {{ $user->phone ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="badge-blue">{{ ucfirst($user->getRoleNames()->first() ?? 'aucun') }}</span>
                            <div class="flex gap-1">
                                <button wire:click="openEdit({{ $user->id }})" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900" title="Modifier">
                                    <flux:icon.pencil-square variant="mini" />
                                </button>
                                <button wire:click="toggleActive({{ $user->id }})" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900" title="Activer/Désactiver">
                                    <flux:icon.power variant="mini" />
                                </button>
                                <button wire:click="deleteUser({{ $user->id }})" wire:confirm="Supprimer cet employé ?" class="rounded-lg p-1.5 text-zinc-500 hover:bg-rose-50 hover:text-rose-600" title="Supprimer">
                                    <flux:icon.trash variant="mini" />
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-zinc-500">Aucun employé.</div>
                @endforelse
            </div>
        </div>

        <div>
            {{ $users->links() }}
        </div>
    </div>
</div>