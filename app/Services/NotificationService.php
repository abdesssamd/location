<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Rental;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\UpcomingReturnNotification;

class NotificationService
{
    public const LOW_STOCK_THRESHOLD = 2;

    public static function notifyLowStock(Product $product): void
    {
        if ($product->quantity > self::LOW_STOCK_THRESHOLD || $product->quantity <= 0) {
            return;
        }

        $users = User::query()
            ->where('store_id', $product->store_id)
            ->where('is_active', true)
            ->role(['admin', 'manager', 'storekeeper'])
            ->get();

        foreach ($users as $user) {
            $user->notify(new LowStockNotification($product));
        }
    }

    public static function notifyUpcomingReturn(Rental $rental): void
    {
        if ($rental->status !== 'active' || ! $rental->end_date) {
            return;
        }

        $inDays = now()->startOfDay()->diffInDays($rental->end_date->startOfDay(), false);
        if ($inDays > 1 || $inDays < 0) {
            return;
        }

        $users = User::query()
            ->where('store_id', $rental->store_id)
            ->where('is_active', true)
            ->role(['admin', 'manager', 'cashier'])
            ->get();

        foreach ($users as $user) {
            $user->notify(new UpcomingReturnNotification($rental));
        }
    }
}