<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Paiements</h1>
            <p class="page-subtitle">Suivi des encaissements et remboursements.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Encaissé aujourd'hui</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($today) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Ce mois</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($month) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Total encaissé</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ money($total) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Remboursé</p>
                <p class="mt-1 text-2xl font-semibold text-rose-600">{{ money($refunded) }}</p>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Nouveau paiement</h2>
            <form wire:submit="recordPayment" class="mt-4 grid gap-4 md:grid-cols-7">
                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-medium text-zinc-700">Location</label>
                    <select wire:model="rental_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                        <option value="">— Choisir —</option>
                        @foreach ($rentals as $rental)
                            <option value="{{ $rental->id }}">{{ $rental->reference }} · {{ $rental->customer?->full_name }} · reste {{ money($rental->remaining) }}</option>
                        @endforeach
                    </select>
                    @error('rental_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Montant</label>
                    <input wire:model="amount" type="number" min="1" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    @error('amount') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Type</label>
                    <select wire:model="type" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Mode</label>
                    <select wire:model="method" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        @foreach ($methodLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Date</label>
                    <input wire:model="date" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    @error('date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2 md:col-span-7">
                    <label class="text-sm font-medium text-zinc-700">Preuve de paiement (photo)</label>
                    <input type="file" wire:model="paymentProof" multiple accept="image/*" class="block w-full text-sm text-zinc-600" />
                    @error('paymentProof.*') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                </div>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-zinc-100 p-4 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Référence ou client..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <select wire:model.live="filterMethod" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les modes</option>
                    @foreach ($methodLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input wire:model.live="from" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                <input wire:model.live="to" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
            </div>

            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Référence</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3">Par</th>
                        <th class="px-4 py-3">Preuve</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($payments as $payment)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5 font-mono text-xs text-zinc-600">{{ $payment->reference }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $payment->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900">{{ $payment->rental?->customer?->full_name }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-zinc-500">{{ $payment->rental?->reference }}</td>
                            <td class="px-4 py-2.5">
                                <span class="{{ $payment->type === 'refund' ? 'badge-red' : ($payment->type === 'deposit' ? 'badge-yellow' : 'badge-green') }}">{{ $typeLabels[$payment->type] ?? $payment->type }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $methodLabels[$payment->method] ?? $payment->method }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $payment->type === 'refund' ? 'text-rose-600' : 'text-emerald-600' }}">{{ $payment->type === 'refund' ? '−' : '+' }}{{ money($payment->amount) }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $payment->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                @if (!empty($payment->proof_image_paths))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($payment->proof_image_paths as $i => $path)
                                            @php($proofUrl = route('files.payment', ['payment' => $payment, 'index' => $i]))
                                            <a href="{{ $proofUrl }}" target="_blank" class="block">
                                                <img src="{{ $proofUrl }}" class="size-10 rounded-lg border border-zinc-200 object-cover" alt="Preuve" />
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-zinc-500">Aucun paiement enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-zinc-100 p-4">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>
