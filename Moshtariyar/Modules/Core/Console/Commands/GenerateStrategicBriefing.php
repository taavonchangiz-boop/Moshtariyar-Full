<?php

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Services\StrategicBriefingService;
use Illuminate\Support\Facades\DB;

class GenerateStrategicBriefing extends Command
{
    protected $signature = 'strategic:generate-briefing';
    protected $description = 'تولید گزارش استراتژیک روزانه برای مدیر';

    public function handle(StrategicBriefingService $service)
    {
        $this->info('در حال تحلیل داده‌ها و تولید گزارش استراتژیک...');

        $briefingData = $service->generateDailyBriefing();

        DB::table('strategic_briefings')->insert([
            'report_date' => $briefingData['date'],
            'content'     => $briefingData['content'],
            'churn_count' => $briefingData['metrics']['churn_count'],
            'growth_count'=> $briefingData['metrics']['growth_count'],
            'health_score'=> $briefingData['metrics']['health_score'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->info('گزارش با موفقیت تولید و ذخیره شد.');
        return 0;
    }
}