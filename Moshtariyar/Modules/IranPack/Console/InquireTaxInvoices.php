<?php

namespace Modules\IranPack\Console;

use Illuminate\Console\Command;
use Modules\IranPack\Entities\TaxInvoice;
use Modules\IranPack\Tax\MoadianClient;

class InquireTaxInvoices extends Command
{
    protected $signature = 'moadian:inquire {--limit=50}';
    protected $description = 'استعلام وضعیت فاکتورهای ارسال‌شده به سامانه مودیان';

    public function handle(MoadianClient $moadian): int
    {
        if (! $moadian->isConfigured()) {
            $this->warn('اتصال سامانه مودیان پیکربندی نشده است.');
            return self::SUCCESS;
        }

        $invoices = TaxInvoice::where('status', 'sent')
            ->whereNotNull('reference_number')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($invoices as $inv) {
            try {
                $res = $moadian->inquiry($inv->reference_number);
                $status = $res['result'][0]['status'] ?? null;
                if ($status === 'SUCCESS') {
                    $inv->update(['status' => 'confirmed', 'response' => $res]);
                } elseif ($status === 'FAILED') {
                    $inv->update(['status' => 'rejected', 'response' => $res]);
                }
            } catch (\Throwable $e) {
                // در دور بعدی دوباره تلاش می‌شود
            }
        }

        $this->info("استعلام {$invoices->count()} فاکتور انجام شد.");
        return self::SUCCESS;
    }
}
