<?php

namespace App\Actions\Contracts;

use App\Models\Contract;
use App\Models\Invoice;

class GenerateInvoiceAction
{
    public function execute(Contract $contract): Invoice
    {
        $amountPaid = (float) $contract->payments()
            ->where('status', 'completed')
            ->sum('amount');

        $totalAmount = round(
            (float) $contract->subtotal
            + (float) $contract->options_total
            + (float) $contract->late_penalty_total
            + (float) $contract->damage_fee_total,
            2
        );

        $status = $amountPaid >= $totalAmount
            ? Invoice::STATUS_PAID
            : ($amountPaid > 0 ? Invoice::STATUS_PARTIALLY_PAID : Invoice::STATUS_UNPAID);

        return Invoice::query()->updateOrCreate(
            ['contract_id' => $contract->id],
            [
                'invoice_number' => $contract->invoice?->invoice_number ?? $this->generateInvoiceNumber(),
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'subtotal' => $contract->subtotal,
                'options_total' => $contract->options_total,
                'late_penalty_total' => $contract->late_penalty_total,
                'damage_fee_total' => $contract->damage_fee_total,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'status' => $status,
            ]
        );
    }

    protected function generateInvoiceNumber(): string
    {
        $latestId = Invoice::query()->max('id') + 1;

        return 'INV-'.now()->format('Ymd').'-'.str_pad((string) $latestId, 5, '0', STR_PAD_LEFT);
    }
}
