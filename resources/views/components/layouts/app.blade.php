@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" @class(['dark' => auth()->user()?->is_super_admin === false && session('theme') === 'dark'])>
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-[#f6f7f9] text-zinc-900 antialiased">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="mr-5 flex items-center gap-2 px-1" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
                <span class="text-lg font-semibold tracking-tight text-zinc-900">LouerPro</span>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="{{ __('Menu') }}" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>

                    @if (Route::has('products.index') && auth()->user()->can('products.view'))
                        <flux:navlist.item icon="squares-2x2" :href="route('products.index')" :current="request()->routeIs('products.*')" wire:navigate>{{ __('Articles') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('categories.index') && auth()->user()->can('categories.manage'))
                        <flux:navlist.item icon="tag" :href="route('categories.index')" :current="request()->routeIs('categories.*')" wire:navigate>Catégories</flux:navlist.item>
                    @endif

                    @if (Route::has('packs.index') && auth()->user()->can('packs.view'))
                        <flux:navlist.item icon="archive-box" :href="route('packs.index')" :current="request()->routeIs('packs.*')" wire:navigate>Packs</flux:navlist.item>
                    @endif

                    @if (Route::has('stock.index') && auth()->user()->can('stock.manage'))
                        <flux:navlist.item icon="cube" :href="route('stock.index')" :current="request()->routeIs('stock.*')" wire:navigate>{{ __('Stock') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('scan.index') && auth()->user()->can('products.view'))
                        <flux:navlist.item icon="qr-code" :href="route('scan.index')" :current="request()->routeIs('scan.*')" wire:navigate>Scanner</flux:navlist.item>
                    @endif

                    @if (Route::has('customers.index') && auth()->user()->can('customers.view'))
                        <flux:navlist.item icon="users" :href="route('customers.index')" :current="request()->routeIs('customers.*')" wire:navigate>{{ __('Clients') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('contracts.index') && auth()->user()->can('contracts.view'))
                        <flux:navlist.item icon="folder-git-2" :href="route('contracts.index')" :current="request()->routeIs('contracts.*')" wire:navigate>{{ __('Contrats') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('rentals.index') && auth()->user()->can('rentals.view'))
                        <flux:navlist.item icon="calendar" :href="route('rentals.index')" :current="request()->routeIs('rentals.*') || request()->routeIs('reservations.*')" wire:navigate>{{ __('Locations') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('calendar') && auth()->user()->can('rentals.view'))
                        <flux:navlist.item icon="calendar-days" :href="route('calendar')" :current="request()->routeIs('calendar')" wire:navigate>Calendrier</flux:navlist.item>
                    @endif

                    @if (Route::has('payments.index') && auth()->user()->can('payments.view'))
                        <flux:navlist.item icon="banknotes" :href="route('payments.index')" :current="request()->routeIs('payments.*')" wire:navigate>{{ __('Paiements') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('reports.index') && auth()->user()->can('reports.view'))
                        <flux:navlist.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.*')" wire:navigate>{{ __('Rapports') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('team.index') && auth()->user()->can('users.manage'))
                        <flux:navlist.item icon="user-group" :href="route('team.index')" :current="request()->routeIs('team.*')" wire:navigate>{{ __('Équipe') }}</flux:navlist.item>
                    @endif

@if (Route::has('settings.index') && auth()->user()->can('settings.manage'))
                        <flux:navlist.item icon="cog-6-tooth" :href="route('settings.index')" :current="request()->routeIs('settings.index')" wire:navigate>{{ __('Paramètres') }}</flux:navlist.item>
                    @endif

                    @if (Route::has('subscription.index'))
                        <flux:navlist.item icon="credit-card" :href="route('subscription.index')" :current="request()->routeIs('subscription.index')" wire:navigate>Abonnement</flux:navlist.item>
                    @endif
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                @if (Route::has('products.create') && auth()->user()->can('products.create'))
                    <flux:navlist.item icon="plus" :href="route('products.create')" :current="request()->routeIs('products.create')" wire:navigate>{{ __('Nouvel article') }}</flux:navlist.item>
                @endif
                @if (Route::has('rentals.create') && auth()->user()->can('rentals.create'))
                    <flux:navlist.item icon="calendar-days" :href="route('rentals.create')" :current="request()->routeIs('rentals.create')" wire:navigate>{{ __('Nouvelle location') }}</flux:navlist.item>
                @endif
                @if (Route::has('packs.create') && auth()->user()->can('packs.create'))
                    <flux:navlist.item icon="archive-box-arrow-down" :href="route('packs.create')" :current="request()->routeIs('packs.create')" wire:navigate>Nouveau pack</flux:navlist.item>
                @endif
            </flux:navlist>

            <!-- Notifications -->
            <livewire:notifications-bell />

            <!-- Langue -->
            <flux:dropdown position="bottom" align="start">
                <flux:button variant="ghost" inset="left" class="h-10 rounded-lg px-3 font-medium">
                    {{ strtoupper(app()->getLocale()) }}
                </flux:button>

                <flux:menu class="w-[140px]">
                    @foreach (['fr' => 'Français', 'ar' => 'العربية', 'en' => 'English'] as $code => $label)
                        <flux:menu.item href="{{ route('locale.switch', $code) }}">
                            {{ $label }}
                            @if (app()->getLocale() === $code) <flux:icon.check variant="mini" /> @endif
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

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

        <!-- Mobile header -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <livewire:notifications-bell />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

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