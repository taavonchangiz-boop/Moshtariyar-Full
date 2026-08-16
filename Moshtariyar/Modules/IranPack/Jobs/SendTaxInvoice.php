<?php

namespace Modules\IranPack\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\IranPack\Entities\TaxInvoice;
use Modules\IranPack\Tax\MoadianClient;

class SendTaxInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(public int $invoiceId)
    {
    }

    public function handle(MoadianClient $moadian): void
    {
        $invoice = TaxInvoice::find($this->invoiceId);
        if (! $invoice || in_array($invoice->status, ['sent', 'confirmed'], true)) {
            return;
        }

        try {
            $invoice->update(['status' => 'queued']);
            $response = $moadian->sendInvoice($invoice);

            $ref = $response['result'][0]['referenceNumber']
                ?? ($response['referenceNumber'] ?? null);

            $invoice->update([
                'status'           => $ref ? 'sent' : 'failed',
                'reference_number' => $ref,
                'response'         => $response,
                'error'            => $ref ? null : 'پاسخ سامانه فاقد شمارهٔ مرجع بود',
            ]);
        } catch (\Throwable $e) {
            $invoice->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
