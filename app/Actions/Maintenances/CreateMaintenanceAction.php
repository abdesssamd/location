<?php

namespace App\Actions\Maintenances;

use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Support\Facades\DB;

class CreateMaintenanceAction
{
    public function execute(Asset $asset, array $validated): Maintenance
    {
        return DB::transaction(function () use ($asset, $validated): Maintenance {
            $asset = Asset::query()->lockForUpdate()->findOrFail($asset->id);

            $maintenance = $asset->maintenances()->create([
                'start_date' => $validated['start_date'],
                'expected_end_date' => $validated['expected_end_date'] ?? null,
                'completed_at' => $validated['completed_at'] ?? null,
                'cost' => round((float) ($validated['cost'] ?? 0), 2),
                'status' => $validated['status'] ?? Maintenance::STATUS_IN_PROGRESS,
                'description' => $validated['description'],
            ]);

            $asset->update(['status' => Asset::STATUS_MAINTENANCE]);

            return $maintenance;
        });
    }
}
