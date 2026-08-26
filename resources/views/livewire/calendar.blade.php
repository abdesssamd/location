<div>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
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

        <div class="card card-pad">
            <div id="calendar" wire:ignore></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            if (! calendarEl) return;
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: '{{ app()->getLocale() === 'ar' ? 'ar' : (app()->getLocale() === 'en' ? 'en' : 'fr') }}',
                direction: '{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: @json($events),
                eventClick: function (info) {
                    if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault();
                    }
                }
            });
            calendar.render();
        });
    </script>
</div>
