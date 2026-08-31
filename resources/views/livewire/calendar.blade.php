<div>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="page-title">Calendrier des locations</h1>
                <p class="page-subtitle">Réservations, locations en cours et retours par période.</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-rose-600"></span> En cours</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-blue-600"></span> Réservé</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-green-600"></span> Terminé</span>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card card-pad">
            <div class="flex flex-wrap items-end gap-4">
                <div class="min-w-[11rem]">
                    <label class="text-xs font-medium text-zinc-500">Vue</label>
                    <div class="mt-1 inline-flex rounded-xl border border-zinc-300 p-0.5">
                        <button type="button"
                            @click="view = 'timeline'; renderCalendar()"
                            :class="view === 'timeline' ? 'bg-brand-800 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition">Par client</button>
                        <button type="button"
                            @click="view = 'planning'; renderCalendar()"
                            :class="view === 'planning' ? 'bg-brand-800 text-white' : 'text-zinc-600 hover:bg-zinc-50'"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition">Planning par article</button>
                    </div>
                </div>

                <div class="min-w-[10rem]">
                    <label class="text-xs font-medium text-zinc-500">Statuts</label>
                    <div class="mt-1.5 flex flex-wrap gap-3">
                        @foreach ($statusOptions as $value => $label)
                            <label class="inline-flex items-center gap-1.5 text-sm">
                                <input type="checkbox" wire:model.live="statuses" value="{{ $value }}"
                                    class="rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="min-w-[12rem] flex-1">
                    <label class="text-xs font-medium text-zinc-500" for="cal-customer">Client</label>
                    <select id="cal-customer" wire:model.live="customerId"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="">Tous les clients</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->first_name }} {{ $customer->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[14rem] flex-1">
                    <label class="text-xs font-medium text-zinc-500" for="cal-resource">Article ou pack</label>
                    <select id="cal-resource" wire:model.live="resourceFilter"
                        class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="">Tous les articles</option>
                        @foreach ($resources as $resource)
                            <option value="{{ $resource['id'] }}">{{ $resource['title'] }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" wire:click="resetFilters"
                    class="rounded-xl border border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-600 hover:bg-zinc-50">
                    Réinitialiser
                </button>

                <a href="{{ route('rentals.create') }}" wire:navigate
                    class="ml-auto rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    + Nouvelle réservation
                </a>
            </div>
        </div>

        <div class="card card-pad" x-data="louerCalendar(@js($events), @js($resources))" x-init="init()" wire:ignore>
            <div id="calendar"></div>
        </div>
    </div>

    {{-- Aperçu rapide d'une location, sans quitter le calendrier --}}
    @if ($preview)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/40 p-4" wire:click.self="closePreview">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-mono text-xs text-zinc-500">{{ $preview['reference'] }}</p>
                        <h2 class="mt-0.5 text-base font-semibold text-zinc-900">{{ $preview['customer_name'] }}</h2>
                        @if ($preview['customer_phone'])
                            <p class="text-xs text-zinc-500">{{ $preview['customer_phone'] }}</p>
                        @endif
                    </div>
                    <span class="{{ $preview['status_badge'] }}">{{ $preview['status_label'] }}</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-zinc-500">Période</p>
                        <p class="mt-0.5 font-medium">{{ $preview['start_date'] }} → {{ $preview['end_date'] }}</p>
                        <p class="text-xs text-zinc-400">{{ $preview['days'] }} jour(s)</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500">Reste à payer</p>
                        <p class="mt-0.5 font-medium {{ $preview['remaining'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ money($preview['remaining']) }}
                        </p>
                        <p class="text-xs text-zinc-400">sur {{ money($preview['total']) }}</p>
                    </div>
                </div>

                <div class="mt-4 space-y-1.5 border-t border-zinc-100 pt-3">
                    @foreach ($preview['items'] as $item)
                        @if (! $item['is_pack_component'])
                            <p class="flex items-center justify-between text-sm">
                                <span class="text-zinc-700">{{ $item['label'] }}</span>
                                <span class="text-zinc-500">× {{ $item['quantity'] }}</span>
                            </p>
                        @endif
                    @endforeach
                </div>

                <div class="mt-5 flex gap-2">
                    <a href="{{ route('rentals.show', $preview['id']) }}" wire:navigate
                        class="flex-1 rounded-xl bg-brand-800 px-4 py-2 text-center text-sm font-medium text-white hover:bg-brand-700">
                        Voir la fiche complète
                    </a>
                    <button type="button" wire:click="closePreview"
                        class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/resource@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/resource-timegrid@6.1.11/index.global.min.js"></script>
    <script>
        function louerCalendar(events, resources) {
            return {
                view: 'timeline',
                calendar: null,

                init() {
                    this.renderCalendar();

                    // Un changement de filtre côté serveur redessine le calendrier
                    // avec les nouveaux événements, sans recharger la page.
                    Livewire.on('calendar-filters-changed', () => {
                        this.$nextTick(() => this.renderCalendar());
                    });
                },

                buildCalendar() {
                    const isPlanning = this.view === 'planning';
                    const rtl = document.documentElement.dir === 'rtl';

                    return new FullCalendar.Calendar(document.getElementById('calendar'), {
                        schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
                        initialView: isPlanning ? 'resourceTimeGridWeek' : 'dayGridMonth',
                        locale: '{{ app()->getLocale() === "ar" ? "ar" : (app()->getLocale() === "en" ? "en" : "fr") }}',
                        direction: rtl ? 'rtl' : 'ltr',
                        height: 'auto',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: isPlanning ? '' : 'dayGridMonth,timeGridWeek,listWeek',
                        },
                        resources: isPlanning ? resources : [],
                        resourceAreaHeaderContent: 'Articles',
                        events: events,
                        eventClick: (info) => {
                            @this.call('previewRental', parseInt(info.event.id));
                        },
                        dateClick: (info) => {
                            if (isPlanning) return;
                            window.location.href = '{{ route('rentals.create') }}?start=' + info.dateStr;
                        },
                        select: (info) => {
                            const end = new Date(info.end);
                            end.setDate(end.getDate() - 1);
                            window.location.href = '{{ route('rentals.create') }}?start=' + info.startStr + '&end=' + end.toISOString().slice(0, 10);
                        },
                        selectable: true,
                    });
                },

                renderCalendar() {
                    if (this.calendar) {
                        this.calendar.destroy();
                    }
                    this.calendar = this.buildCalendar();
                    this.calendar.render();
                },
            };
        }
    </script>
</div>
