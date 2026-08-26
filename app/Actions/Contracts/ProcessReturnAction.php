<?php

namespace App\Actions\Contracts;

use App\Actions\Maintenances\CreateMaintenanceAction;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\Maintenance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessReturnAction
{
    public function __construct(
        protected GenerateInvoiceAction $generateInvoiceAction,
        protected CreateMaintenanceAction $createMaintenanceAction,
    ) {
    }

    public function execute(Contract $contract, array $validated): Contract
    {
        return DB::transaction(function () use ($contract, $validated): Contract {
            $contract = Contract::query()
                ->with(['asset.specifiable', 'invoice', 'payments'])
                ->lockForUpdate()
                ->findOrFail($contract->id);

            if (! in_array($contract->status, [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE, Contract::STATUS_RESERVED], true)) {
                throw ValidationException::withMessages([
                    'contract' => 'Seuls les contrats en cours peuvent être clôturés.',
                ]);
            }

            $asset = Asset::query()
                ->with('specifiable')
                ->lockForUpdate()
                ->findOrFail($contract->asset_id);

            $returnedAt = Carbon::parse($validated['actual_end_at'] ?? now());
            $expectedEndAt = Carbon::parse($contract->expected_end_at);
            $lateDays = $returnedAt->greaterThan($expectedEndAt)
                ? max($expectedEndAt->copy()->startOfDay()->diffInDays($returnedAt->copy()->startOfDay()), 1)
                : 0;

            $latePenaltyTotal = round($lateDays * (float) ($validated['late_penalty_per_day'] ?? 0), 2);
            $damageFeeTotal = round((float) ($validated['damage_fee_total'] ?? 0), 2);
            $depositRetainedAmount = round((float) ($validated['deposit_retained_amount'] ?? 0), 2);

            if ($depositRetainedAmount > (float) $contract->deposit_amount) {
                throw ValidationException::withMessages([
                    'deposit_retained_amount' => 'La retenue sur caution ne peut pas dépasser le montant déposé.',
                ]);
            }

            $finalCondition = $validated['final_condition'];
            $finalCondition['mileage'] = (int) $validated['final_mileage'];

            $contract->update([
                'status' => Contract::STATUS_COMPLETED,
                'actual_end_at' => $returnedAt,
                'late_penalty_total' => $latePenaltyTotal,
                'damage_fee_total' => $damageFeeTotal,
                'deposit_retained_amount' => $depositRetainedAmount,
                'deposit_status' => $this->resolveDepositStatus($contract, $depositRetainedAmount),
                'final_condition' => $finalCondition,
                'return_notes' => $validated['return_notes'] ?? null,
                'deposit_retention_reason' => $validated['deposit_retention_reason'] ?? null,
                'total_amount' => round(
                    (float) $contract->subtotal
                    + (float) $contract->options_total
                    + $latePenaltyTotal
                    + $damageFeeTotal,
                    2
                ),
            ]);

            if ($asset->specifiable && method_exists($asset->specifiable, 'update')) {
                $asset->specifiable->update([
                    'mileage' => (int) $validated['final_mileage'],
                ]);
            }

            if (($validated['asset_next_status'] ?? Asset::STATUS_AVAILABLE) === Asset::STATUS_MAINTENANCE) {
                $asset->update(['status' => Asset::STATUS_MAINTENANCE]);

                $this->createMaintenanceAction->execute($asset, [
                    'start_date' => now()->toDateString(),
                    'expected_end_date' => $validated['maintenance_expected_end_date'] ?? now()->addDays(7)->toDateString(),
                    'cost' => $validated['maintenance_cost'] ?? 0,
                    'description' => $validated['maintenance_description'] ?? 'Maintenance déclenchée après retour du bien.',
                    'status' => Maintenance::STATUS_SCHEDULED,
                ]);
            } else {
                $asset->update(['status' => Asset::STATUS_AVAILABLE]);
            }

            $invoice = $this->generateInvoiceAction->execute($contract->fresh(['payments', 'invoice']));

            $contract->payments()
                ->whereNull('invoice_id')
                ->update(['invoice_id' => $invoice->id]);

            return $contract->fresh(['client', 'asset.specifiable', 'options', 'invoice', 'payments']);
        });
    }

    protected function resolveDepositStatus(Contract $contract, float $depositRetainedAmount): string
    {
        if ($depositRetainedAmount <= 0) {
            return Contract::DEPOSIT_RETURNED;
        }

        if ($depositRetainedAmount >= (float) $contract->deposit_amount) {
            return Contract::DEPOSIT_FULLY_RETAINED;
        }

        return Contract::DEPOSIT_PARTIALLY_RETAINED;
    }
}
