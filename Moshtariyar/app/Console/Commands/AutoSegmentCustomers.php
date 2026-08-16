<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Services\ClvAdvancedService;
use Modules\Core\Services\RetentionAutomationService;

class AutoSegmentCustomers extends Command
{
    protected $signature = 'customers:auto-segment {--business= : شناسه کسب‌وکار (اختیاری)}';
    protected $description = 'بخش‌بندی خودکار مشتریان بر اساس CLV پیشرفته و پیش‌بینی ریزش - با بالاترین دقت';

    public function handle(ClvAdvancedService $service, RetentionAutomationService $retentionService): int
    {
        $businessId = $this->option('business') ? (int) $this->option('business') : null;

        $this->info('شروع بخش‌بندی خودکار با تحلیل CLV پیشرفته و اتوماسیون بازگشت...');

        $result = $service->autoSegment($businessId);

        $this->info("تعداد بخش‌های جدید ساخته شده: {$result['created']}");
        $this->info("تعداد مشتریان بخش‌بندی شده: {$result['assigned']}");

        if (isset($result['analysis'])) {
            $a = $result['analysis'];
            $this->table(
                ['شاخص', 'مقدار'],
                [
                    ['کل مشتریان تحلیل شده', $a['total_customers']],
                    ['میانگین CLV ۱۲ ماه آینده', number_format($a['avg_clv_12m']) . ' تومان'],
                    ['میانگین ریسک ریزش', $a['avg_churn_probability'] . '%'],
                    ['در معرض خطر', $a['total_at_risk']],
                    ['از دست رفته', $a['total_churned']],
                    ['درآمد پیش‌بینی ۱۲ ماه', number_format($a['total_predicted_revenue_12m']) . ' تومان'],
                ]
            );

            $this->info('توزیع ریزش:');
            foreach ($a['churn_distribution'] as $k => $v) {
                $this->line("  - {$k}: {$v} مشتری");
            }

            $this->info('توزیع بخش‌ها:');
            foreach ($a['segment_distribution'] as $k => $v) {
                $this->line("  - {$k}: {$v} مشتری");
            }

            // اجرای اتوماسیون بازگشت خودکار
            $this->info('در حال ساخت کمپین‌های بازگشت خودکار و جریان‌های کاری...');
            $retentionResult = $retentionService->runAfterSegmentation($a);
            
            $this->info("جریان‌های کاری جدید ساخته شده: {$retentionResult['workflows_created']}");
            $this->info("کمپین‌های بازگشت جدید ساخته شده: {$retentionResult['campaigns_created']}");
            $this->info("پتانسیل درآمد قابل بازگشت (در معرض خطر): " . number_format($retentionResult['at_risk_revenue_potential']) . " تومان");
            $this->info("پتانسیل درآمد خوابیده: " . number_format($retentionResult['sleeping_revenue_potential']) . " تومان");
            $this->info("جمع پتانسیل قابل بازگشت: " . number_format($retentionResult['total_recoverable']) . " تومان");
        }

        return self::SUCCESS;
    }
}