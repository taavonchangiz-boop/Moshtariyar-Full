<?php

namespace Modules\WooBridge\Console;

use Illuminate\Console\Command;
use Modules\WooBridge\Entities\SyncLog;
use Modules\WooBridge\Jobs\ProcessWooWebhook;

class RetryFailedSync extends Command
{
    protected $signature = 'woobridge:retry-failed {--limit=50}';
    protected $description = 'ری‌تری رکوردهای ناموفق همگام‌سازی ووکامرس';

    public function handle(): int
    {
        $logs = SyncLog::where('status', 'failed')
            ->latest('id')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($logs as $log) {
            $log->update(['status' => 'pending']);
            ProcessWooWebhook::dispatch($log->id);
        }

        $this->info("تعداد {$logs->count()} رکورد دوباره در صف قرار گرفت.");
        return self::SUCCESS;
    }
}
