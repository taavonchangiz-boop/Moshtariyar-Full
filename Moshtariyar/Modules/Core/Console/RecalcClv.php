<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Modules\Core\Entities\Customer;

class RecalcClv extends Command
{
    protected $signature = 'crm:recalc-clv';
    protected $description = 'محاسبهٔ مجدد ارزش طول عمر مشتری (CLV)';

    public function handle(): int
    {
        Customer::query()->chunkById(200, function ($customers) {
            foreach ($customers as $customer) {
                $customer->recalcLifetimeValue();
            }
        });

        $this->info('CLV همهٔ مشتریان به‌روزرسانی شد.');
        return self::SUCCESS;
    }
}
