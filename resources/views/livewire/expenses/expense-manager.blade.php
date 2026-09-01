<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Dépenses</h1>
            <p class="page-subtitle">Loyer, salaires, réparations, fournitures… les frais du magasin.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Dépensé aujourd'hui</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($today) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Ce mois</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($month) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Total</p>
                <p class="mt-1 text-2xl font-semibold text-rose-600">{{ money($total) }}</p>
            </div>
        </div>

        @can('expenses.create')
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Nouvelle dépense</h2>
                <form wire:submit="save" class="mt-4 grid gap-4 md:grid-cols-6">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700">Libellé</label>
                        <input wire:model="label" placeholder="Facture d'électricité août" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        @error('label') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Catégorie</label>
                        <div class="flex gap-2">
                            <select wire:model="expense_category_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                <option value="">— Aucune —</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="$toggle('showNewCategory')" class="shrink-0 rounded-xl border border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50" title="Nouvelle catégorie">
                                <flux:icon.plus variant="mini" />
                            </button>
                        </div>
                        @if ($showNewCategory)
                            <div class="mt-2 flex items-center gap-2 rounded-xl bg-zinc-50 p-3">
                                <input wire:model="newCategoryName" placeholder="Nom de la catégorie" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                                <input type="color" wire:model="newCategoryColor" class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-zinc-300 bg-white p-1" title="Couleur" />
                                <button type="button" wire:click="quickAddCategory" class="shrink-0 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Ajouter</button>
                            </div>
                            @error('newCategoryName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @endif
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Montant</label>
                        <input wire:model="amount" type="number" min="1" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        @error('amount') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Mode</label>
                        <select wire:model="payment_method" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                            @foreach (\App\Models\Expense::methodLabels() as $value => $methodLabel)
                                <option value="{{ $value }}">{{ $methodLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Date</label>
                        <input wire:model="date" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        @error('date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-3">
                        <label class="text-sm font-medium text-zinc-700">Notes</label>
                        <input wire:model="notes" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700">Justificatif (photo)</label>
                        <input type="file" wire:model="proof" accept="image/*" class="block w-full text-sm text-zinc-600" />
                        @error('proof') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 self-end pb-2.5 text-sm text-zinc-700">
                        <input type="checkbox" wire:model="is_recurring" class="rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                        Dépense récurrente
                    </label>

                    <div class="flex items-end md:col-span-6">
                        <button type="submit" class="rounded-xl bg-brand-800 px-6 py-2 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        @endcan

        <div class="card overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-zinc-100 p-4 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Libellé ou référence..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <select wire:model.live="filterCategory" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <input wire:model.live="from" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                <input wire:model.live="to" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Référence</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Libellé</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Mode</th>
                            <th class="px-4 py-3 text-right">Montant</th>
                            <th class="px-4 py-3">Par</th>
                            <th class="px-4 py-3">Justificatif</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($expenses as $expense)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="px-4 py-2.5 font-mono text-xs text-zinc-600">{{ $expense->reference }}</td>
                                <td class="px-4 py-2.5 text-zinc-500">{{ $expense->date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 font-medium text-zinc-900">
                                    {{ $expense->label }}
                                    @if ($expense->is_recurring)
                                        <span class="badge-blue ml-1">Récurrente</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if ($expense->category)
                                        <span class="inline-flex items-center gap-1.5 text-zinc-700">
                                            <span class="h-2 w-2 rounded-full" style="background: {{ $expense->category->color ?? '#71717a' }}"></span>
                                            {{ $expense->category->name }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-zinc-500">{{ \App\Models\Expense::methodLabels()[$expense->payment_method] ?? $expense->payment_method }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-rose-600">− {{ money($expense->amount) }}</td>
                                <td class="px-4 py-2.5 text-zinc-500">{{ $expense->user?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($expense->proof_path)
                                        @php($proofUrl = route('files.expense', $expense))
                                        <a href="{{ $proofUrl }}" target="_blank" class="block">
                                            <img src="{{ $proofUrl }}" class="size-10 rounded-lg border border-zinc-200 object-cover" alt="Justificatif" />
                                        </a>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    @can('delete', $expense)
                                        <button type="button" wire:click="delete({{ $expense->id }})" wire:confirm="Supprimer cette dépense ?" class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600">
                                            <flux:icon.trash variant="mini" />
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-10 text-center text-zinc-500">Aucune dépense enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 p-4">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
</div>
