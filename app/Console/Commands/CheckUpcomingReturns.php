<?php

namespace App\Console\Commands;

use App\Models\Rental;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckUpcomingReturns extends Command
{
    protected $signature = 'rentals:check-returns';

    protected $description = 'Notifie les équipes des locations actives à rendre sous 24h';

    public function handle(): int
    {
        $rentals = Rental::with('customer')
            ->where('status', 'active')
            ->whereDate('end_date', now()->toDateString())
            ->orWhereDate('end_date', now()->addDay()->toDateString())
            ->get();

        foreach ($rentals as $rental) {
            NotificationService::notifyUpcomingReturn($rental);
        }

        $this->info('Notifications envoyées pour '.$rentals->count().' location(s).');

        return self::SUCCESS;
    }
}