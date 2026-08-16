<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Setting;
use Modules\Automation\Services\WorkflowEngine;
use Carbon\Carbon;
use Modules\Core\Support\Jalali;

class CheckBirthdays extends Command
{
    protected $signature = 'automation:check-birthdays';
    protected $description = 'بررسی تولد مشتریان و ارسال تبریک خودکار با بالاترین دقت';

    public function handle(): int
    {
        if (Setting::get('notif_birthday', '0') === '0') {
            $this->info('تبریک تولد غیرفعال است');
            return self::SUCCESS;
        }

        $engine = app(WorkflowEngine::class);
        $count = 0;

        try {
            $customers = Customer::whereNotNull('meta')
                ->orWhereNotNull('birth_date')
                ->limit(500)
                ->get()
                ->filter(function ($customer) {
                    $meta = $customer->meta ?? [];
                    $birthday = $meta['birthday'] ?? $meta['birth_date'] ?? $customer->birth_date ?? null;
                    if (!$birthday) return false;
                    try {
                        if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $birthday) || preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $birthday)) {
                            $date = Carbon::parse(str_replace('/', '-', $birthday));
                            return $date->format('m-d') === now()->format('m-d');
                        }
                        if (preg_match('/^\d{1,2}\/\d{1,2}$/', $birthday)) {
                            [$m, $d] = explode('/', $birthday);
                            return (int)$m === now()->month && (int)$d === now()->day;
                        }
                        if (preg_match('/^13\d{2}\/\d{1,2}\/\d{1,2}$/', $birthday)) {
                            $todayJalali = Jalali::date(now());
                            $todayParts = explode('/', $todayJalali);
                            $bParts = explode('/', $birthday);
                            if (count($todayParts) === 3 && count($bParts) === 3) {
                                return $todayParts[1] == $bParts[1] && $todayParts[2] == $bParts[2];
                            }
                        }
                        return false;
                    } catch (\Throwable $e) {
                        return false;
                    }
                });

            foreach ($customers as $customer) {
                try {
                    $engine->fire('birthday_soon', $customer);
                    usleep(200000);
                    $count++;
                } catch (\Throwable $e) {
                    Log::warning('خطا در تبریک تولد: ' . $e->getMessage());
                }
            }

            $this->info("تعداد {$count} تبریک تولد ارسال شد");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('خطا: ' . $e->getMessage());
            Log::error('CheckBirthdays error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}