<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Num;
use App\Mail\RecoveryWeeklyMail;
use App\Models\RecoveryReportHistory;

class RecoveryHistoryController extends Controller
{

    public function index(Request $request)
    {
        $businessId = $this->currentBusinessId();

        // اگر آرشیو خالی است و داده قدیمی در strategic_briefings وجود دارد، یک‌بار مهاجرت خودکار انجام بده
        try {
            if (Schema::hasTable('recovery_report_histories') && Schema::hasTable('strategic_briefings')) {
                $cnt = RecoveryReportHistory::query()->forBusiness($businessId)->count();
                if ($cnt == 0) {
                    $oldRows = DB::table('strategic_briefings')->where('title','like','%بازگشت و حفظ%')->orderBy('report_date')->limit(50)->get();
                    foreach ($oldRows as $old) {
                        try {
                            $ins = json_decode($old->insights ?? '{}', true) ?? [];
                            DB::table('recovery_report_histories')->insert([
                                'report_date' => $old->report_date ?? now()->toDateString(),
                                'j_today' => $this->extractJalaliFromTitle($old->title ?? ''),
                                'j_from' => $ins['j_from'] ?? null,
                                'j_to' => $ins['j_to'] ?? null,
                                'business_id' => $businessId,
                                'recovered_7d' => $ins['recovered_7d'] ?? 0,
                                'recovered_revenue_7d' => $ins['recovered_revenue_7d'] ?? 0,
                                'recovered_30d' => $ins['recovered_30d'] ?? 0,
                                'recovered_revenue_30d' => $ins['recovered_revenue_30d'] ?? 0,
                                'recovery_rate' => $ins['recovery_rate'] ?? 0,
                                'total_at_risk' => $ins['total_at_risk'] ?? 0,
                                'total_recovered_12m' => $ins['total_recovered_12m'] ?? 0,
                                'total_count_12m' => $ins['total_count_12m'] ?? 0,
                                'monthly_labels' => json_encode($ins['monthly_labels'] ?? [], JSON_UNESCAPED_UNICODE),
                                'monthly_recovered' => json_encode($ins['monthly_recovered'] ?? [], JSON_UNESCAPED_UNICODE),
                                'monthly_count' => json_encode($ins['monthly_count'] ?? [], JSON_UNESCAPED_UNICODE),
                                'recovered_list' => json_encode($ins['recovered_list'] ?? [], JSON_UNESCAPED_UNICODE),
                                'emails_sent' => json_encode([], JSON_UNESCAPED_UNICODE),
                                'summary' => $old->summary ?? $old->title,
                                'insights_json' => $old->insights ?? json_encode($ins, JSON_UNESCAPED_UNICODE),
                                'created_at' => $old->created_at ?? now(),
                                'updated_at' => $old->updated_at ?? now(),
                            ]);
                        } catch (\Throwable $e) { continue; }
                    }
                }
            }
        } catch (\Throwable $e) {}

        $q = RecoveryReportHistory::query()->forBusiness($businessId)->orderByDesc('report_date')->orderByDesc('id');

        // جستجو
        if ($request->filled('search')) {
            $s = trim($request->get('search'));
            $q->where(function($qq) use ($s) {
                $qq->where('j_today', 'like', "%{$s}%")
                   ->orWhere('summary', 'like', "%{$s}%");
            });
        }

        // فیلتر تاریخ شمسی - از و تا
        if ($request->filled('from') && $request->filled('to')) {
            try {
                $from = Jalali::parse($request->get('from'));
                $to = Jalali::parse($request->get('to'))?->endOfDay();
                if ($from && $to) {
                    $q->whereBetween('report_date', [$from->toDateString(), $to->toDateString()]);
                }
            } catch (\Throwable $e) {}
        }

        // صفحه‌بندی
        $perPage = (int)($request->get('per_page', 20));
        $perPage = max(10, min(100, $perPage));
        $histories = $q->paginate($perPage)->withQueryString();

        // آمار کلی آرشیو
        $stats = $this->buildStats($businessId);

        // آخرین ۱۲ گزارش برای نمودار کلی آرشیو
        $chartData = RecoveryReportHistory::query()->forBusiness($businessId)->orderByDesc('report_date')->limit(12)->get()->reverse();

        return view('app.recovery_history', [
            'histories' => $histories,
            'stats' => $stats,
            'chartData' => $chartData,
            'search' => $request->get('search', ''),
            'from' => $request->get('from', ''),
            'to' => $request->get('to', ''),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $businessId = $this->currentBusinessId();
        $history = RecoveryReportHistory::query()->forBusiness($businessId)->where('id', $id)->firstOrFail();

        $reportData = $history->insights_json ?? [];
        if (empty($reportData) && $history->summary) {
            $reportData = [
                'recovered_7d' => $history->recovered_7d,
                'recovered_revenue_7d' => $history->recovered_revenue_7d,
                'recovered_30d' => $history->recovered_30d,
                'recovered_revenue_30d' => $history->recovered_revenue_30d,
                'recovery_rate' => $history->recovery_rate,
                'total_at_risk' => $history->total_at_risk,
                'monthly_labels' => $history->monthly_labels,
                'monthly_recovered' => $history->monthly_recovered,
                'monthly_count' => $history->monthly_count,
                'recovered_list' => $history->recovered_list,
                'total_recovered_12m' => $history->total_recovered_12m,
                'total_count_12m' => $history->total_count_12m,
            ];
        }

        return view('app.recovery_history_detail', [
            'history' => $history,
            'data' => $reportData,
        ]);
    }

    public function resend(Request $request, int $id)
    {
        $businessId = $this->currentBusinessId();
        $history = RecoveryReportHistory::query()->forBusiness($businessId)->where('id', $id)->firstOrFail();

        $reportData = $history->insights_json ?? [
            'recovered_7d' => $history->recovered_7d,
            'recovered_revenue_7d' => $history->recovered_revenue_7d,
            'recovered_30d' => $history->recovered_30d,
            'recovered_revenue_30d' => $history->recovered_revenue_30d,
            'recovery_rate' => $history->recovery_rate,
            'total_at_risk' => $history->total_at_risk,
            'monthly_labels' => $history->monthly_labels,
            'monthly_recovered' => $history->monthly_recovered,
            'monthly_count' => $history->monthly_count,
            'recovered_list' => $history->recovered_list,
            'total_recovered_12m' => $history->total_recovered_12m,
            'total_count_12m' => $history->total_count_12m,
        ];

        $targetEmail = $request->get('email');
        if (!$targetEmail) {
            $targetEmail = DB::table('users')->where(function($q){
                $q->whereIn('role',['admin','owner'])->orWhere('is_superadmin',1);
            })->whereNotNull('email')->value('email');
        }

        if (!$targetEmail) {
            try {
                $targetEmail = \Modules\Core\Entities\Setting::get('admin_email', '');
            } catch (\Throwable $e) {}
        }

        if (!$targetEmail) {
            return redirect()->back()->with('status', 'ایمیلی برای ارسال مجدد یافت نشد - تنظیمات ایمیل مدیر را بررسی کنید');
        }

        try {
            Mail::to($targetEmail)->send(new RecoveryWeeklyMail($reportData, $history->j_today ?? Jalali::date(Carbon::now()), $history->j_from ?? '', $history->j_to ?? ''));
            // بروزرسانی آرشیو
            $emails = $history->emails_sent ?? [];
            $emails[] = $targetEmail . ' (ارسال مجدد ' . now()->format('Y-m-d H:i') . ')';
            $history->update(['emails_sent' => array_unique($emails)]);
            return redirect()->back()->with('status', 'گزارش با موفقیت به ' . $targetEmail . ' ارسال مجدد شد');
        } catch (\Throwable $e) {
            return redirect()->back()->with('status', 'ارسال مجدد ناموفق بود: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $businessId = $this->currentBusinessId();
        $q = RecoveryReportHistory::query()->forBusiness($businessId)->orderByDesc('report_date');

        if ($request->filled('search')) {
            $s = trim($request->get('search'));
            $q->where(function($qq) use ($s) {
                $qq->where('j_today', 'like', "%{$s}%")->orWhere('summary','like',"%{$s}%");
            });
        }

        $rows = $q->limit(500)->get();

        $filename = 'آرشیو-گزارش-بازگشت-' . Jalali::date(Carbon::now()) . '.csv';

        return response()->stream(function() use ($rows) {
            $out = fopen('php://output','w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['تاریخ شمسی','تاریخ میلادی','بازگشته هفته','درآمد هفته','بازگشته ۳۰ روزه','درآمد ۳۰ روزه','نرخ بازگشت٪','در خطر','کل ۱۲ ماه','تعداد کل ۱۲ ماه','ایمیل‌های ارسالی']);
            foreach ($rows as $h) {
                fputcsv($out, [
                    $h->j_today,
                    $h->report_date->format('Y-m-d'),
                    $h->recovered_7d,
                    $h->recovered_revenue_7d,
                    $h->recovered_30d,
                    $h->recovered_revenue_30d,
                    $h->recovery_rate,
                    $h->total_at_risk,
                    $h->total_recovered_12m,
                    $h->total_count_12m,
                    implode(' | ', $h->emails_sent ?? []),
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function generateNow(Request $request)
    {
        // تولید دستی گزارش همین الان
        try {
            Artisan::call('recovery:weekly-report');
            $output = Artisan::output();
            return redirect()->back()->with('status', 'گزارش جدید تولید شد - خروجی: ' . mb_substr($output, 0, 500));
        } catch (\Throwable $e) {
            return redirect()->back()->with('status','خطا در تولید دستی: '.$e->getMessage());
        }
    }

    protected function buildStats(?int $businessId): array
    {
        try {
            $totalReports = RecoveryReportHistory::query()->forBusiness($businessId)->count();
            $totalRevenue = RecoveryReportHistory::query()->forBusiness($businessId)->sum('recovered_revenue_7d') + RecoveryReportHistory::query()->forBusiness($businessId)->sum('recovered_revenue_30d');
            $totalRecovered = RecoveryReportHistory::query()->forBusiness($businessId)->sum('recovered_7d');
            $lastReport = RecoveryReportHistory::query()->forBusiness($businessId)->orderByDesc('report_date')->first();
            $avgRate = RecoveryReportHistory::query()->forBusiness($businessId)->avg('recovery_rate') ?? 0;
            $maxRevenue = RecoveryReportHistory::query()->forBusiness($businessId)->max('recovered_revenue_7d') ?? 0;
            $last30d = RecoveryReportHistory::query()->forBusiness($businessId)->where('report_date','>=', now()->subDays(30)->toDateString())->count();

            return [
                'total_reports' => $totalReports,
                'total_revenue' => (int)$totalRevenue,
                'total_recovered' => (int)$totalRecovered,
                'last_report_date' => $lastReport?->j_today ?? '—',
                'last_report_id' => $lastReport?->id,
                'avg_rate' => round($avgRate,1),
                'max_revenue' => (int)$maxRevenue,
                'last30d' => $last30d,
            ];
        } catch (\Throwable $e) {
            return [
                'total_reports' => 0,'total_revenue'=>0,'total_recovered'=>0,'last_report_date'=>'—','last_report_id'=>null,'avg_rate'=>0,'max_revenue'=>0,'last30d'=>0,
            ];
        }
    }

    protected function extractJalaliFromTitle(string $title): ?string
    {
        try {
            if (preg_match('/\d{4}\/\d{2}\/\d{2}/', $title, $m)) return $m[0];
            $parts = explode(' - ', $title);
            return trim(end($parts)) ?: null;
        } catch (\Throwable $e) { return null; }
    }

    protected function currentBusinessId(): ?int
    {
        try {
            $user = auth()->user();
            if (!$user) return null;
            if (method_exists($user,'isSuperAdmin') && $user->isSuperAdmin()) return null;
            return $user->business_id ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}