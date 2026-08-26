@props(['status' => null, 'type' => null])

@php
    $message = $status ?? session('status') ?? session('success') ?? session('error');
    $kind = $type ?? (session('error') ? 'error' : 'success');
@endphp

@if ($message)
    @if ($kind === 'error')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $message }}
        </div>
    @else
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ $message }}
        </div>
    @endif
@endif

@if ($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <p class="font-semibold">Veuillez corriger les erreurs suivantes :</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif