<?php

namespace Modules\Core\Services;

use Modules\Automation\Entities\Workflow;
use Modules\Loyalty\Entities\LoyaltyCampaign;
use Modules\Loyalty\Entities\LoyaltyCampaignRewardRule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * اتوماسیون نگهداری و بازگشت خودکار مشتریان بر اساس بخش‌های طلایی
 * با بالاترین دقت و قدرت - بدون نیاز به تنظیم دستی
 */
class RetentionAutomationService
{
    public function ensureDefaultWorkflows(): int
    {
        if (!Schema::hasTable('workflows')) {
            return 0;
        }

        $defaults = [
            [
                'event' => 'no_purchase_30d',
                'name' => 'یادآوری هوشمند ۳۰ روز بدون خرید',
                'description' => 'برای مشتریانی که ۳۰ روز خرید نکرده‌اند - یادآوری محترمانه با پیشنهاد بازگشت',
                'action' => 'messenger',
                'channel' => 'all',
                'delay_min' => 0,
                'template' => "سلام {نام} عزیز 💙\n\nمدتیه از شما خبری نداریم! دلتنگتون شدیم.\n\nبرای بازگشت شما یک هدیه کوچک در نظر گرفتیم:\n🎁 {امتیاز} امتیاز وفاداری\n\nبا خرید بعدی، امتیازها به کیف پول شما اضافه می‌شود.\n\nمنتظر دیدار دوباره شما هستیم\n{برند}",
            ],
            [
                'event' => 'no_purchase_45d',
                'name' => 'کمپین بازگشت ۴۵ روزه - ارزشمند در معرض ریزش',
                'description' => 'مشتریان ارزشمند که ۴۵ روز خرید نکرده‌اند - پیشنهاد ویژه با تخفیف',
                'action' => 'messenger',
                'channel' => 'all',
                'delay_min' => 0,
                'template' => "سلام {نام} جان، شما یکی از ارزشمندترین مشتریان ما هستید 🌟\n\nمتوجه شدیم ۴۵ روزه خرید نکردید و نگران شدیم.\n\nبه خاطر قدردانی از همراهی شما:\n💎 ۲۰٪ تخفیف مخصوص بازگشت\n🎁 ۳۰۰ امتیاز هدیه\n\nکد تخفیف: BAZGASHT{نام}\n\nاین کد تا ۷ روز اعتبار دارد.\n\nبی‌صبرانه منتظرتون هستیم\n{برند} - {لینک_دعوت}",
            ],
            [
                'event' => 'no_purchase_60d',
                'name' => 'آخرین فرصت بازگشت - ۶۰ روز بدون خرید - فعال‌سازی مجدد مشتریان کم‌فعال',
                'description' => 'آخرین تلاش اصولی برای بازگردانی مشتریان کم‌فعال با پیشنهاد ویژه و مهلت محدود - جلوگیری از ریزش کامل',
                'action' => 'messenger',
                'channel' => 'all',
                'delay_min' => 0,
                'template' => "{نام} عزیز، این آخرین پیام ماست 😔\n\n۶۰ روزه که از شما دوریم و نمی‌خواهیم شما را از دست بدهیم.\n\nبه همین خاطر یک پیشنهاد ویژه و تکرار نشدنی:\n🔥 ۳۰٪ تخفیف ویژه بازگشت\n💰 ۵۰۰ امتیاز + ۵۰ هزار تومان کیف پول هدیه\n\nکد: AKHARIN{نام}\nمهلت: فقط ۷۲ ساعت\n\nاگر دوست ندارید دیگر پیام ندهیم، کافیه همین پیام را نادیده بگیرید.\n\nاما اگر برگردید، قول می‌دهیم بهترین تجربه را برایتان بسازیم\n{برند}",
            ],
            [
                'event' => 'cart.abandoned',
                'name' => 'یادآوری سبد رها شده - ۲۴ ساعته',
                'description' => 'مشتری سبد خرید را رها کرده - یادآوری هوشمند با حفظ محصولات سبد',
                'action' => 'messenger',
                'channel' => 'all',
                'delay_min' => 60,
                'template' => "سلام {نام} 👋\n\nدیدیم چند محصول توی سبد خریدت جا مونده! 🛒\n\nنمی‌خوای قبل از اینکه موجودیش تموم بشه تکمیلش کنی؟\n\nبرای اینکه راحت‌تر برگردی:\n🎁 ۱۰٪ تخفیف سبد رها شده\nکد: CART10\n\nسبدت تا ۴۸ ساعت برات نگه داشته می‌شود.\n\nبرای تکمیل: {لینک_دعوت}\n\n{برند}",
            ],
            [
                'event' => 'birthday_soon',
                'name' => 'تبریک تولد خودکار با هدیه وفاداری',
                'description' => 'ارسال تبریک تولد با پاداش ویژه در روز تولد مشتری',
                'action' => 'messenger',
                'channel' => 'all',
                'delay_min' => 0,
                'template' => "تولدت مبارک {نام} عزیز 🎂🎉\n\nامروز روز توئه و ما می‌خواهیم این روز را با هم جشن بگیریم!\n\nهدیه تولدت:\n🎁 ۱۰۰۰ امتیاز وفاداری\n💝 ۱۰۰ هزار تومان کیف پول هدیه\n🎂 ۲۵٪ تخفیف ویژه تولد\n\nکد هدیه: TAVALOD{نام}\nاعتبار: تا ۷ روز بعد از تولد\n\nاز اینکه مشتری وفادار ما هستی، بی‌نهایت سپاسگزاریم\n\nبا آرزوی بهترین‌ها\nتیم {برند}\n{لینک_دعوت}",
            ],
            [
                'event' => 'customer_returned',
                'name' => 'پاداش خوش‌آمد بازگشت - امتیاز دو برابر',
                'description' => 'وقتی مشتری بعد از ۴۵ روز غیبت برمی‌گردد و خرید می‌کند - امتیاز دو برابر خودکار',
                'action' => 'loyalty_reward',
                'channel' => 'all',
                'delay_min' => 0,
                'template' => "سلام {نام} عزیز، به خانه خوش برگشتی! 🎉\n\nخیلی خوشحالیم که دوباره شما را می‌بینیم.\n\nبه خاطر بازگشت ارزشمندت:\n✨ امتیاز این خرید دو برابر شد!\n🎁 {امتیاز} امتیاز هدیه بازگشت به شما اضافه شد\n\nمرسی که دوباره به ما اعتماد کردی\n{برند}",
            ],
        ];

        $created = 0;
        foreach ($defaults as $def) {
            $exists = Workflow::where('event', $def['event'])
                ->where('name', $def['name'])
                ->exists();

            if (!$exists) {
                Workflow::create([
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'event' => $def['event'],
                    'action' => $def['action'],
                    'channel' => $def['channel'],
                    'delay_min' => $def['delay_min'],
                    'template' => $def['template'],
                    'is_active' => true,
                    'type' => 'simple',
                ]);
                $created++;
            }
        }

        return $created;
    }

    public function createRetentionCampaigns(array $analysis): int
    {
        if (!Schema::hasTable('loyalty_campaigns')) {
            return 0;
        }

        $campaigns = [
            [
                'key' => 'auto_return_valuable',
                'title' => 'کمپین بازگشت خودکار - مشتریان ارزشمند در معرض ریزش',
                'slug' => 'auto-return-valuable-' . date('Y-m'),
                'description' => 'این کمپین به صورت خودکار برای مشتریان ارزشمند که ریسک ریزش ۴۰ تا ۸۵٪ دارند ساخته شد. پاداش: ۳۰۰ امتیاز + ۲۰٪ تخفیف بازگشت. هر روز با بخش‌بندی خودکار به‌روزرسانی می‌شود.',
                'type' => 'mission',
                'rules' => [
                    ['event' => 'first_purchase','beneficiary' => 'referrer','reward_type' => 'points_fixed','reward_value' => 300,'release_policy' => 'immediate','release_days' => 0,],
                    ['event' => 'first_purchase','beneficiary' => 'referrer','reward_type' => 'coupon_percent','reward_value' => 20,'release_policy' => 'immediate','release_days' => 0,],
                ],
            ],
            [
                'key' => 'auto_wake_sleeping',
                'title' => 'کمپین فعال‌سازی مجدد مشتریان کم‌فعال با پیشنهاد ویژه',
                'slug' => 'auto-wake-sleeping-' . date('Y-m'),
                'description' => 'برای مشتریان کم‌فعال با سابقه خرید که ۶۶ تا ۸۵٪ احتمال ریزش دارند - پاداش ۵۰۰ امتیاز + ۱۰٪ تخفیف بازگشت. این کمپین به صورت اصولی برای بازگردانی مشتریان کم‌فعال طراحی شده و هر روز به‌روزرسانی می‌شود.',
                'type' => 'mission',
                'rules' => [
                    ['event' => 'first_purchase','beneficiary' => 'referrer','reward_type' => 'points_fixed','reward_value' => 500,'release_policy' => 'immediate','release_days' => 0,],
                ],
            ],
            [
                'key' => 'auto_birthday_month',
                'title' => 'جشن تولد ماهانه - تبریک خودکار',
                'slug' => 'auto-birthday-' . date('Y-m'),
                'description' => 'کمپین تبریک تولد خودکار که هر روز مشتریانی که تولدشان است را با ۱۰۰۰ امتیاز و کیف پول تشویق می‌کند.',
                'type' => 'seasonal',
                'rules' => [
                    ['event' => 'profile_complete','beneficiary' => 'referrer','reward_type' => 'points_fixed','reward_value' => 1000,'release_policy' => 'immediate','release_days' => 0,],
                ],
            ],
            [
                'key' => 'auto_return_double',
                'title' => 'پاداش بازگشت - امتیاز دو برابر برای بازگشت‌کنندگان',
                'slug' => 'auto-return-double-' . date('Y-m'),
                'description' => 'هر مشتری که بعد از ۴۵ روز غیبت برگردد، امتیاز این خریدش دو برابر می‌شود. خودکار فعال است.',
                'type' => 'mission',
                'rules' => [
                    ['event' => 'first_purchase','beneficiary' => 'referrer','reward_type' => 'points_fixed','reward_value' => 200,'release_policy' => 'immediate','release_days' => 0,],
                ],
            ],
        ];

        $created = 0;
        foreach ($campaigns as $def) {
            $exists = LoyaltyCampaign::where('slug', $def['slug'])->exists();
            if ($exists) continue;
            $similar = LoyaltyCampaign::where('title', 'like', '%' . mb_substr($def['title'], 0, 15) . '%')->where('created_at', '>=', now()->subDays(30))->exists();
            if ($similar) continue;
            try {
                $campaign = LoyaltyCampaign::create([
                    'title' => $def['title'],
                    'slug' => $def['slug'] . '-' . Str::random(4),
                    'description' => $def['description'],
                    'type' => $def['type'],
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths(2),
                    'meta' => ['auto_created' => true, 'key' => $def['key']],
                ]);
                foreach ($def['rules'] as $rule) {
                    LoyaltyCampaignRewardRule::create(array_merge($rule, ['campaign_id' => $campaign->id,'is_active' => true,]));
                }
                $created++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ایجاد کمپین بازگشت خودکار ناموفق: ' . $e->getMessage());
            }
        }
        return $created;
    }

    public function getRecoveryMetrics(array $analysis = []): array
    {
        try {
            $recoveredList = [];
            $recoveredRevenue = 0;
            $totalRecovered = 0;
            if (!Schema::hasTable('orders')) {
                return ['recovered_30d'=>0,'recovered_7d'=>0,'recovered_revenue_30d'=>0,'recovery_rate'=>0,'recovered_list'=>[]];
            }
            $recentOrders = DB::table('orders')->whereIn('status', ['completed','processing'])->where('placed_at','>=',now()->subDays(30))->whereNotNull('customer_id')->orderByDesc('placed_at')->limit(200)->get();
            foreach ($recentOrders as $order) {
                try {
                    $prevOrder = DB::table('orders')->where('customer_id',$order->customer_id)->whereIn('status',['completed','processing'])->where('id','!=',$order->id)->where('placed_at','<',$order->placed_at)->orderByDesc('placed_at')->first();
                    if (!$prevOrder) continue;
                    $gap = Carbon::parse($order->placed_at)->diffInDays(Carbon::parse($prevOrder->placed_at));
                    if ($gap >= 45) {
                        $customer = DB::table('customers')->where('id',$order->customer_id)->first();
                        if (!$customer) continue;
                        $alreadyAdded = false;
                        foreach ($recoveredList as $r) { if (($r['customer_id']??0)==$order->customer_id){$alreadyAdded=true;break;}}
                        if ($alreadyAdded) continue;
                        $totalRecovered++; $recoveredRevenue += (float)$order->total;
                        try { $jalali = \Modules\Core\Support\Jalali::fromCarbon(Carbon::parse($order->placed_at)); $faDate = $jalali[0].'/'.str_pad($jalali[1],2,'0',STR_PAD_LEFT).'/'.str_pad($jalali[2],2,'0',STR_PAD_LEFT);} catch (\Throwable $e){ $faDate = Carbon::parse($order->placed_at)->format('Y/m/d');}
                        $recoveredList[] = ['customer_id'=>$order->customer_id,'full_name'=>$customer->full_name?:'بدون نام','phone'=>$customer->phone?:'---','gap_days'=>(int)$gap,'order_total'=>(int)$order->total,'returned_at'=>Carbon::parse($order->placed_at),'returned_at_fa'=>$faDate,'previous_order_date'=>$prevOrder->placed_at,];
                    }
                } catch (\Throwable $e){ continue; }
            }
            $recovered7d = 0;
            foreach ($recoveredList as $r){ if (($r['returned_at']??now())->diffInDays(now())<=7){$recovered7d++;}}
            $totalAtRiskBase = !empty($analysis)?(($analysis['total_at_risk']??0)+($analysis['total_churned']??0)):max(1, DB::table('customers')->count());
            $recoveryRate = $totalAtRiskBase>0?round(($totalRecovered/max(1,$totalAtRiskBase))*100,1):0;
            return ['recovered_30d'=>$totalRecovered,'recovered_7d'=>$recovered7d,'recovered_revenue_30d'=>(int)$recoveredRevenue,'recovery_rate'=>$recoveryRate,'recovered_list'=>array_slice($recoveredList,0,50),];
        } catch (\Throwable $e){
            return ['recovered_30d'=>0,'recovered_7d'=>0,'recovered_revenue_30d'=>0,'recovery_rate'=>0,'recovered_list'=>[],];
        }
    }

    public function runAfterSegmentation(array $analysis): array
    {
        $workflowsCreated = $this->ensureDefaultWorkflows();
        $campaignsCreated = $this->createRetentionCampaigns($analysis);
        $recoveryMetrics = $this->getRecoveryMetrics($analysis);
        $atRiskRevenue = 0; $sleepingRevenue = 0;
        foreach ($analysis['all']??[] as $a){
            if (in_array($a['segment_key']??'',['valuable_at_risk','at_risk'])) $atRiskRevenue += $a['predicted_clv_12m']??0;
            if (in_array($a['segment_key']??'',['churning','lost'])) $sleepingRevenue += $a['predicted_clv_12m']??0;
        }
        return ['workflows_created'=>$workflowsCreated,'campaigns_created'=>$campaignsCreated,'at_risk_revenue_potential'=>$atRiskRevenue,'sleeping_revenue_potential'=>$sleepingRevenue,'total_recoverable'=>$atRiskRevenue+$sleepingRevenue,'recovery_metrics'=>$recoveryMetrics,];
    }
}