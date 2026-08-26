@php
    $brandColor = \App\Services\StoreContext::store()?->color;
@endphp
<div class="flex aspect-square size-8 items-center justify-center rounded-lg text-white" {{ $brandColor ? 'style="background-color: '.e($brandColor).'"' : 'style="background-color: var(--color-brand-800, #1e3a5f)"' }}>
    <x-app-logo-icon class="size-5" />
</div>
<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate leading-none font-semibold">{{ \App\Services\StoreContext::store()?->name ?? 'LouerPro' }}</span>
</div>