@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-[#f6f7f9] text-zinc-900 antialiased">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('admin.index') }}" class="mr-5 flex items-center gap-2 px-1" wire:navigate>
                <span class="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand-800 text-white">
                    <x-app-logo-icon class="size-5" />
                </span>
                <span class="text-lg font-semibold tracking-tight text-zinc-900">LouerPro <span class="text-xs font-normal text-zinc-400">Admin</span></span>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Plateforme" class="grid">
                    <flux:navlist.item icon="home" :href="route('admin.index')" :current="request()->routeIs('admin.index')" wire:navigate>Tableau de bord</flux:navlist.item>
                    <flux:navlist.item icon="building-storefront" :href="route('admin.stores.index')" :current="request()->routeIs('admin.stores.*')" wire:navigate>Magasins</flux:navlist.item>
                    <flux:navlist.item icon="tag" :href="route('admin.plans.index')" :current="request()->routeIs('admin.plans.*')" wire:navigate>Plans</flux:navlist.item>
                    <flux:navlist.item icon="credit-card" :href="route('admin.subscriptions.index')" :current="request()->routeIs('admin.subscriptions.*')" wire:navigate>Abonnements</flux:navlist.item>
                    <flux:navlist.item icon="lifebuoy" :href="route('admin.support.index')" :current="request()->routeIs('admin.support.*')" :badge="\App\Services\SupportService::unreadCountForAdmin() ?: null" wire:navigate>Support</flux:navlist.item>
                    <flux:navlist.item icon="document-text" :href="route('admin.audits.index')" :current="request()->routeIs('admin.audits.*')" wire:navigate>Audit</flux:navlist.item>
                    <flux:navlist.item icon="cog-6-tooth" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" :badge="\App\Models\Store::where('status', 'pending')->count() ?: null" wire:navigate>Paramètres généraux</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />
                <flux:menu class="w-[220px]">
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>