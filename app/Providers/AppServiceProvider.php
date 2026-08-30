<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Pack;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PackPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RentalPolicy;
use App\Listeners\LogAuthentication;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // En production : liens absolus en HTTPS (contrats, e-mails, QR codes) et
        // alerte si le mode debug a été laissé actif (fuite de trace et de secrets).
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');

            if (config('app.debug')) {
                \Illuminate\Support\Facades\Log::warning(
                    'APP_DEBUG est actif en production : les traces exposent les variables d\'environnement.'
                );
            }
        }

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Rental::class, RentalPolicy::class);
        Gate::policy(Pack::class, PackPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);

        config([
            'livewire.layout' => 'components.layouts.app',
            'livewire.component_layout' => 'components.layouts.app',
        ]);

        Event::listen(Login::class, [LogAuthentication::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthentication::class, 'handleLogout']);
    }
}
