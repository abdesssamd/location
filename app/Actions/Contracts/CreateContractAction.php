<?php

namespace App\Actions\Contracts;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractOption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateContractAction
{
    public function execute(array $validated): Contract
    {
        return DB::transaction(function () use ($validated): Contract {
            $client = Client::query()
                ->lockForUpdate()
                ->findOrFail($validated['client_id']);

            if ($client->is_blacklisted) {
                throw ValidationException::withMessages([
                    'client_id' => 'Ce client est blacklisté et ne peut pas effectuer de nouvelle location.',
                ]);
            }

            $asset = Asset::query()
                ->with('specifiable')
                ->lockForUpdate()
                ->findOrFail($validated['asset_id']);

            $startAt = Carbon::parse($validated['start_at']);
            $expectedEndAt = Carbon::parse($validated['expected_end_at']);

            if ($expectedEndAt->lessThanOrEqualTo($startAt)) {
                throw ValidationException::withMessages([
                    'expected_end_at' => 'La date de fin doit être postérieure à la date de début.',
                ]);
            }

            $isAvailable = Asset::query()
                ->whereKey($asset->id)
                ->available($startAt->toDateTimeString(), $expectedEndAt->toDateTimeString())
                ->exists();

            if (! $isAvailable) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Le bien sélectionné n’est pas disponible sur cette période.',
                ]);
            }

            $options = collect($validated['options'] ?? [])
                ->map(fn (array $option) => [
                    'name' => $option['name'],
                    'description' => $option['description'] ?? null,
                    'quantity' => (int) $option['quantity'],
                    'unit_price' => round((float) $option['unit_price'], 2),
                    'total_price' => round((int) $option['quantity'] * (float) $option['unit_price'], 2),
                ]);

            $rentalDays = max($startAt->copy()->startOfDay()->diffInDays($expectedEndAt->copy()->startOfDay()), 1);
            $subtotal = round($rentalDays * (float) $asset->daily_rate, 2);
            $optionsTotal = round((float) $options->sum('total_price'), 2);
            $totalAmount = round($subtotal + $optionsTotal, 2);

            $contract = Contract::create([
                'contract_number' => $this->generateContractNumber(),
                'client_id' => $client->id,
                'asset_id' => $asset->id,
                'status' => $startAt->greaterThan(now()) ? Contract::STATUS_RESERVED : Contract::STATUS_ACTIVE,
                'start_at' => $startAt,
                'expected_end_at' => $expectedEndAt,
                'rental_days' => $rentalDays,
                'daily_rate' => $asset->daily_rate,
                'subtotal' => $subtotal,
                'options_total' => $optionsTotal,
                'late_penalty_total' => 0,
                'damage_fee_total' => 0,
                'total_amount' => $totalAmount,
                'deposit_amount' => round((float) $validated['deposit_amount'], 2),
                'deposit_retained_amount' => 0,
                'deposit_method' => $validated['deposit_method'],
                'deposit_status' => $validated['deposit_status'],
                'initial_condition' => $validated['initial_condition'],
                'checkout_notes' => $validated['checkout_notes'] ?? null,
            ]);

            $options->each(fn (array $option) => $contract->options()->create($option));

            $asset->update(['status' => Asset::STATUS_RENTED]);

            return $contract->load(['client', 'asset.specifiable', 'options']);
        });
    }

    protected function generateContractNumber(): string
    {
        $latestId = Contract::query()->max('id') + 1;

        return 'CTR-'.now()->format('Ymd').'-'.str_pad((string) $latestId, 5, '0', STR_PAD_LEFT);
    }
}
