@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" @class(['dark' => auth()->user()?->is_super_admin === false && session('theme') === 'dark'])>
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen text-zinc-900 antialiased">
        <flux:sidebar sticky stashable class="border-r border-white/10">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 py-3" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
                <span class="brand-wordmark text-lg font-semibold tracking-tight">LouerPro</span>
            </a>
            <div class="mx-3 h-px bg-white/10"></div>

            <nav class="nav-scroll flex-1 space-y-1 px-3 py-3">
                <p class="nav-section__title">Menu principal</p>

                <a href="{{ route('dashboard') }}" @class(['nav-item','nav-item--active' => request()->routeIs('dashboard')]) wire:navigate title="Dashboard">
                    <span class="nav-item__icon"><flux:icon.home class="size-5" /></span>
                    <span class="nav-item__label">Dashboard</span>
                </a>

                @if (Route::has('products.index') && auth()->user()->can('products.view'))
                    <a href="{{ route('products.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('products.*')]) wire:navigate title="Articles">
                        <span class="nav-item__icon"><flux:icon.squares-2x2 class="size-5" /></span>
                        <span class="nav-item__label">Articles</span>
                    </a>
                @endif

                @if (Route::has('categories.index') && auth()->user()->can('categories.manage'))
                    <a href="{{ route('categories.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('categories.*')]) wire:navigate title="Catégories">
                        <span class="nav-item__icon"><flux:icon.tag class="size-5" /></span>
                        <span class="nav-item__label">Catégories</span>
                    </a>
                @endif

                @if (Route::has('packs.index') && auth()->user()->can('packs.view'))
                    <a href="{{ route('packs.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('packs.*')]) wire:navigate title="Packs">
                        <span class="nav-item__icon"><flux:icon.archive-box class="size-5" /></span>
                        <span class="nav-item__label">Packs</span>
                    </a>
                @endif

                @if (Route::has('stock.index') && auth()->user()->can('stock.manage'))
                    <a href="{{ route('stock.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('stock.*')]) wire:navigate title="Stock">
                        <span class="nav-item__icon"><flux:icon.cube class="size-5" /></span>
                        <span class="nav-item__label">Stock</span>
                    </a>
                @endif

                @if (Route::has('scan.index') && auth()->user()->can('products.view'))
                    <a href="{{ route('scan.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('scan.*')]) wire:navigate title="Scanner">
                        <span class="nav-item__icon"><flux:icon.qr-code class="size-5" /></span>
                        <span class="nav-item__label">Scanner</span>
                    </a>
                @endif

                @if (Route::has('customers.index') && auth()->user()->can('customers.view'))
                    <a href="{{ route('customers.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('customers.*')]) wire:navigate title="Clients">
                        <span class="nav-item__icon"><flux:icon.users class="size-5" /></span>
                        <span class="nav-item__label">Clients</span>
                    </a>
                @endif

                @if (Route::has('contracts.index') && auth()->user()->can('contracts.view'))
                    <a href="{{ route('contracts.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('contracts.*')]) wire:navigate title="Contrats">
                        <span class="nav-item__icon"><flux:icon.folder-git-2 class="size-5" /></span>
                        <span class="nav-item__label">Contrats</span>
                    </a>
                @endif

                @if (Route::has('rentals.index') && auth()->user()->can('rentals.view'))
                    <a href="{{ route('rentals.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs(['rentals.*','reservations.*'])]) wire:navigate title="Locations">
                        <span class="nav-item__icon"><flux:icon.calendar class="size-5" /></span>
                        <span class="nav-item__label">Locations</span>
                    </a>
                @endif

                @if (Route::has('calendar') && auth()->user()->can('rentals.view'))
                    <a href="{{ route('calendar') }}" @class(['nav-item','nav-item--active' => request()->routeIs('calendar')]) wire:navigate title="Calendrier">
                        <span class="nav-item__icon"><flux:icon.calendar-days class="size-5" /></span>
                        <span class="nav-item__label">Calendrier</span>
                    </a>
                @endif

                @if (Route::has('payments.index') && auth()->user()->can('payments.view'))
                    <a href="{{ route('payments.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('payments.*')]) wire:navigate title="Paiements">
                        <span class="nav-item__icon"><flux:icon.banknotes class="size-5" /></span>
                        <span class="nav-item__label">Paiements</span>
                    </a>
                @endif

                @if (Route::has('reports.index') && auth()->user()->can('reports.view'))
                    <a href="{{ route('reports.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('reports.*')]) wire:navigate title="Rapports">
                        <span class="nav-item__icon"><flux:icon.chart-bar class="size-5" /></span>
                        <span class="nav-item__label">Rapports</span>
                    </a>
                @endif

                @if (Route::has('team.index') && auth()->user()->can('users.manage'))
                    <a href="{{ route('team.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('team.*')]) wire:navigate title="Équipe">
                        <span class="nav-item__icon"><flux:icon.user-group class="size-5" /></span>
                        <span class="nav-item__label">Équipe</span>
                    </a>
                @endif

                @if (Route::has('settings.index') && auth()->user()->can('settings.manage'))
                    <a href="{{ route('settings.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('settings.index')]) wire:navigate title="Paramètres">
                        <span class="nav-item__icon"><flux:icon.cog-6-tooth class="size-5" /></span>
                        <span class="nav-item__label">Paramètres</span>
                    </a>
                @endif

                @if (Route::has('subscription.index'))
                    <a href="{{ route('subscription.index') }}" @class(['nav-item','nav-item--active' => request()->routeIs('subscription.index')]) wire:navigate title="Abonnement">
                        <span class="nav-item__icon"><flux:icon.credit-card class="size-5" /></span>
                        <span class="nav-item__label">Abonnement</span>
                    </a>
                @endif

                <div class="nav-section__sep"></div>
                <p class="nav-section__title">Actions rapides</p>

                @if (Route::has('products.create') && auth()->user()->can('products.create'))
                    <a href="{{ route('products.create') }}" @class(['nav-item','nav-item--active' => request()->routeIs('products.create')]) wire:navigate title="Nouvel article">
                        <span class="nav-item__icon"><flux:icon.plus class="size-5" /></span>
                        <span class="nav-item__label">Nouvel article</span>
                    </a>
                @endif
                @if (Route::has('rentals.create') && auth()->user()->can('rentals.create'))
                    <a href="{{ route('rentals.create') }}" @class(['nav-item','nav-item--active' => request()->routeIs('rentals.create')]) wire:navigate title="Nouvelle location">
                        <span class="nav-item__icon"><flux:icon.calendar-days class="size-5" /></span>
                        <span class="nav-item__label">Nouvelle location</span>
                    </a>
                @endif
                @if (Route::has('packs.create') && auth()->user()->can('packs.create'))
                    <a href="{{ route('packs.create') }}" @class(['nav-item','nav-item--active' => request()->routeIs('packs.create')]) wire:navigate title="Nouveau pack">
                        <span class="nav-item__icon"><flux:icon.archive-box-arrow-down class="size-5" /></span>
                        <span class="nav-item__label">Nouveau pack</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer space-y-2 border-t border-white/10 p-3">
                <livewire:notifications-bell />

                <flux:dropdown position="bottom" align="start">
                    <flux:button variant="ghost" inset="left" class="h-10 w-full rounded-lg px-3 text-left font-medium text-zinc-200 hover:bg-white/5 hover:text-white">
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

                <flux:dropdown position="bottom" align="start" class="w-full">
                    <flux:profile
                        class="!text-zinc-200 hover:!bg-white/5 hover:!text-white"
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevrons-up-down"
                    />

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-white/10 text-white">
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
            </div>
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

        <flux:main class="animate-fade-in">
            {{ $slot }}
        </flux:main>

        @if (Route::has('rentals.create') && auth()->user()->can('rentals.create'))
            <a href="{{ route('rentals.create') }}" class="fab btn btn-primary h-14 w-14 rounded-full p-0 shadow-lg" wire:navigate title="Nouvelle location">
                <flux:icon.plus class="size-6" />
            </a>
        @endif

        @fluxScripts
    </body>
</html>