<?php

namespace App\Actions\Payments;

use App\Actions\Contracts\GenerateInvoiceAction;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class RecordPaymentAction
{
    public function __construct(
        protected GenerateInvoiceAction $generateInvoiceAction,
    ) {
    }

    public function execute(Contract $contract, array $validated): Payment
    {
        return DB::transaction(function () use ($contract, $validated): Payment {
            $contract = Contract::query()
                ->with('invoice')
                ->lockForUpdate()
                ->findOrFail($contract->id);

            $payment = $contract->payments()->create([
                'invoice_id' => $contract->invoice?->id,
                'payment_date' => $validated['payment_date'],
                'amount' => round((float) $validated['amount'], 2),
                'method' => $validated['method'],
                'status' => $validated['status'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice = $this->generateInvoiceAction->execute($contract->fresh(['payments', 'invoice']));

            if (! $payment->invoice_id) {
                $payment->update(['invoice_id' => $invoice->id]);
            }

            return $payment->fresh();
        });
    }
}
