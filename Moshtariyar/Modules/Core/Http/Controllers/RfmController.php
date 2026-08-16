<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Money;
use Modules\Core\Support\Num;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RfmController extends Controller
{
    public const SEGMENTS = [
        'champions'    => ['label' => 'قهرمانان',           'color' => '#f59e0b', 'desc' => 'تازه، پرتکرار، پرارزش'],
        'loyal'        => ['label' => 'وفاداران',            'color' => '#10b981', 'desc' => 'خریدهای مکرر و فعال'],
        'potential'    => ['label' => 'در حال رشد',          'color' => '#38bdf8', 'desc' => 'تازه‌واردهای باانگیزه'],
        'new'          => ['label' => 'تازه‌وارد',            'color' => '#818cf8', 'desc' => 'اولین خرید را انجام داده‌اند'],
        'at_risk'      => ['label' => 'در آستانه ریزش',      'color' => '#ef4444', 'desc' => 'قبلاً فعال، حالا کم‌مراجعه'],
        'hibernating'  => ['label' => 'خفته',               'color' => '#64748b', 'desc' => 'مدت زیادی است خرید نکرده‌اند'],
        'lost'         => ['label' => 'از دست رفته',         'color' => '#6b7280', 'desc' => 'ماه‌هاست که بازنگشته‌اند'],
        'no_purchase'  => ['label' => 'بدون خرید',           'color' => '#94a3b8', 'desc' => 'هنوز خریدی ثبت نکرده‌اند'],
    ];

    public function index(Request $request)
    {
        $segment = $request->get('segment');
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $stats       = $this->stats();
        $segments    = $this->segments();
        $treemap     = $this->treemapData($segments);
        $donut       = $this->donutData($segments);
        $migration   = $this->migrationData();
        $customers   = $this->customerRows($segment, $perPage, ($page - 1) * $perPage);
        $hasNext     = count($customers) > $perPage;
        $customers   = array_slice($customers, 0, $perPage);

        // تولید تمام HTML در کنترلر (روش امن، مثل نسخه اصلی)
        $html = $this->buildHtml($stats, $segments, $treemap, $donut, $migration, $customers, $segment, $page, $hasNext);

        return view('app.rfm', ['content' => $html, 'heading' => 'تحلیل رفتار مشتریان', 'subtitle' => 'شناسایی گروه‌های مشتریان بر اساس تازگی، تعداد، ارزش خرید و طول رابطه']);
    }

    // ─────── HTML Builder ───────
    private function buildHtml(array $stats, array $segments, array $treemap, array $donut, array $migration, array $customers, ?string $segment, int $page, bool $hasNext): string
    {
        $h  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $fa = fn($v) => Num::fa($v);
        $u  = fn($p) => url($p);

        $html = '';

        // ─── hero ───
        $html .= '<div style="background:linear-gradient(135deg,rgba(56,189,248,.10),rgba(16,185,129,.06),rgba(245,158,11,.04));border:1px solid var(--line);border-radius:20px;padding:24px 22px;margin-bottom:20px;">';
        $html .= '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">';
        $html .= '<div style="flex:1;min-width:240px;">';
        $html .= '<h2 style="margin:0 0 4px;font-size:1.25rem;">تحلیل رفتار خرید مشتریان</h2>';
        $html .= '<p class="muted" style="margin:0;font-size:.88rem;line-height:1.8;">مشتریان بر اساس <b style="color:#38bdf8;">تازگی خرید</b>، <b style="color:#10b981;">دفعات خرید</b>، <b style="color:#f59e0b;">ارزش خرید</b> و <b style="color:#a78bfa;">طول رابطه</b> گروه‌بندی شده‌اند.</p>';
        $html .= '</div>';
        $html .= '<div style="display:flex;gap:10px;flex-wrap:wrap;">';
        $html .= '<a class="btn btn-ghost" href="'.$u('/app/reports').'">📋 گزارش‌ها</a>';
        $html .= '<a class="btn" href="'.$u('/app/reports/rfm/export?segment='.($segment??'')).'">📥 خروجی اکسل</a>';
        $html .= '</div></div></div>';

        // ─── کارت‌های آماری ۶ تایی ───
        $statCards = [
            ['title'=>'کل مشتریان','value'=>$fa(number_format($stats['customers'])),'color'=>'#38bdf8','icon'=>'👥'],
            ['title'=>'مشتریان خریدکرده','value'=>$fa(number_format($stats['buyers'])),'color'=>'#10b981','icon'=>'🛒'],
            ['title'=>'فعال در ۳۰ روز','value'=>$fa(number_format($stats['active'])),'color'=>'#f59e0b','icon'=>'⚡'],
            ['title'=>'نیازمند بازگشت','value'=>$fa(number_format($stats['at_risk'])),'color'=>'#ef4444','icon'=>'⚠️'],
            ['title'=>'میانگین ارزش خرید','value'=>Money::show($stats['avg_order_value']),'color'=>'#a78bfa','icon'=>'💰'],
            ['title'=>'نرخ ریزش','value'=>$fa($stats['churn_rate']).'٪','color'=>$stats['churn_rate']>30?'#ef4444':'#10b981','icon'=>'📉'],
        ];
        $html .= '<div class="rfm-stats-grid" style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;">';
        foreach ($statCards as $sc) {
            $html .= '<div class="rfm-stat" style="background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px 14px;text-align:center;">';
            $html .= '<div style="font-size:1.8rem;margin-bottom:2px;">'.$sc['icon'].'</div>';
            $html .= '<div style="font-size:1.4rem;font-weight:900;color:'.$sc['color'].';margin-bottom:4px;">'.$sc['value'].'</div>';
            $html .= '<div class="muted" style="font-size:.78rem;">'.$sc['title'].'</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        // ─── دو نمودار کنار هم ───
        $html .= '<div class="rfm-charts" style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px;">';

        // نقشه درختی
        $html .= '<div class="card" style="margin-bottom:0;">';
        $html .= '<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;"><span style="font-size:1.5rem;">🗺️</span><h3 style="margin:0;">نقشه درختی مشتریان</h3></div>';
        $html .= '<p class="muted" style="font-size:.8rem;margin:4px 0 14px;">هر خانه یک گروه را نشان می‌دهد. اندازه = تعداد مشتریان. رنگ = وضعیت.</p>';
        if (count($treemap)) {
            $maxCount = $treemap[0]['count'] ?? 1;
            $html .= '<div class="rfm-treemap" style="display:flex;flex-wrap:wrap;gap:8px;min-height:300px;">';
            foreach ($treemap as $tm) {
                $sizePct = max(18, round(($tm['count'] / $maxCount) * 100));
                if ($sizePct > 60) $w = '100%';
                elseif ($sizePct > 35) $w = 'calc(50% - 4px)';
                elseif ($sizePct > 18) $w = 'calc(33.33% - 6px)';
                else $w = 'calc(25% - 6px)';
                $escurl = $u('/app/reports/rfm?segment='.$tm['key']);
                $c = $tm['color'];
                $html .= '<a href="'.$escurl.'" class="rfm-tm-block" style="width:'.$w.';min-width:80px;flex:1 0 auto;background:'.$c.'14;border:2px solid '.$c.'33;border-radius:16px;padding:18px 14px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;text-decoration:none;cursor:pointer;transition:all .25s;min-height:90px;" onmouseover="this.style.transform=\'translateY(-4px)\';this.style.boxShadow=\'0 12px 28px rgba(0,0,0,.25)\';this.style.borderColor=\''.$c.'\';this.style.background=\''.$c.'22\';" onmouseout="this.style.transform=\'\';this.style.boxShadow=\'\';this.style.borderColor=\''.$c.'33\';this.style.background=\''.$c.'14\';">';
                $html .= '<div style="font-size:1.6rem;font-weight:900;color:'.$c.';margin-bottom:4px;">'.$fa($tm['count']).'</div>';
                $html .= '<div style="font-size:.88rem;font-weight:700;color:var(--txt);margin-bottom:3px;">'.$h($tm['label']).'</div>';
                $html .= '<div style="font-size:.72rem;color:var(--mut);">'.$h($tm['desc']).'</div>';
                $html .= '<div style="margin-top:6px;font-size:.75rem;font-weight:700;color:'.$c.';background:'.$c.'18;padding:3px 10px;border-radius:999px;">'.$fa($tm['percent']).'٪</div>';
                $html .= '</a>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="empty">داده‌ای برای نمایش نقشه وجود ندارد.</div>';
        }
        $html .= '</div>';

        // نمودار دایره‌ای
        $html .= '<div class="card" style="margin-bottom:0;">';
        $html .= '<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;"><span style="font-size:1.5rem;">🍩</span><h3 style="margin:0;">پراکندگی گروه‌ها</h3></div>';
        $html .= '<p class="muted" style="font-size:.8rem;margin:4px 0 14px;">سهم هر گروه از کل مشتریانی که حداقل یک خرید داشته‌اند.</p>';
        if (count($donut)) {
            $totalSeg = array_sum(array_column($donut, 'count'));
            $html .= '<div class="rfm-donut-wrap" style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">';
            // SVG donut
            $html .= '<div style="position:relative;width:190px;height:190px;flex-shrink:0;margin:0 auto;">';
            $html .= '<svg viewBox="0 0 190 190" style="width:100%;height:100%;transform:rotate(-90deg);">';
            $cumDeg = 0;
            foreach ($donut as $d) {
                $deg = ($d['percent'] / 100) * 360;
                $startAngle = $cumDeg;
                $endAngle = $cumDeg + $deg;
                $cumDeg = $endAngle;
                $x1 = 95 + 85 * cos(deg2rad($startAngle));
                $y1 = 95 + 85 * sin(deg2rad($startAngle));
                $x2 = 95 + 85 * cos(deg2rad($endAngle));
                $y2 = 95 + 85 * sin(deg2rad($endAngle));
                $large = $deg > 180 ? 1 : 0;
                $html .= '<path d="M 95 95 L '.round($x1,2).' '.round($y1,2).' A 85 85 0 '.$large.' 1 '.round($x2,2).' '.round($y2,2).' Z" fill="'.$d['color'].'" opacity="0.85" stroke="var(--bg)" stroke-width="2"/>';
            }
            $html .= '<circle cx="95" cy="95" r="52" fill="var(--bg)"/>';
            $html .= '</svg>';
            $html .= '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;"><div style="font-size:1.5rem;font-weight:900;color:var(--txt);">'.$fa($totalSeg).'</div><div style="font-size:.7rem;color:var(--mut);">مشتری</div></div>';
            $html .= '</div>';
            // Legend
            $html .= '<div style="flex:1;min-width:170px;" class="rfm-legend">';
            foreach ($donut as $d) {
                $html .= '<div style="display:flex;align-items:center;gap:10px;margin-bottom:7px;font-size:.82rem;padding:5px 0;">';
                $html .= '<span style="width:14px;height:14px;border-radius:4px;background:'.$d['color'].';flex-shrink:0;box-shadow:0 2px 6px '.$d['color'].'44;"></span>';
                $html .= '<span style="flex:1;color:var(--txt);font-weight:600;">'.$h($d['label']).'</span>';
                $html .= '<span style="color:var(--mut);font-size:.75rem;">'.$fa($d['count']).' نفر</span>';
                $html .= '<span style="font-weight:800;color:'.$d['color'].';min-width:40px;text-align:left;">'.$fa($d['percent']).'٪</span>';
                $html .= '</div>';
            }
            $html .= '</div></div>';
        } else {
            $html .= '<div class="empty">داده‌ای برای نمایش نمودار وجود ندارد.</div>';
        }
        $html .= '</div>';
        $html .= '</div>'; // end charts row

        // ─── گزارش مهاجرت ───
        $net = $migration['net_change'];
        $netColor = $net >= 0 ? '#10b981' : '#ef4444';
        $netSign = $net >= 0 ? '+' : '';
        $netLabel = $net >= 0 ? 'روند رو به بهبود' : 'روند رو به افت';
        $html .= '<div class="card" style="margin-bottom:20px;">';
        $html .= '<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;"><span style="font-size:1.5rem;">📊</span><h3 style="margin:0;">رفت‌وآمد مشتریان در یک ماه گذشته</h3></div>';
        $html .= '<p class="muted" style="font-size:.8rem;margin:4px 0 14px;">مشتریانی که از وضعیت فعال دور شده‌اند و آن‌هایی که بازگشته‌اند.</p>';
        $html .= '<div class="rfm-mig-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">';
        $html .= '<div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15);border-radius:16px;padding:18px;text-align:center;"><div style="font-size:.78rem;color:var(--mut);margin-bottom:6px;">در مسیر افت</div><div style="font-size:1.8rem;font-weight:900;color:#ef4444;">'.$fa($migration['to_at_risk']).'</div><div style="font-size:.75rem;color:var(--mut);margin-top:4px;">نفر در حال دور شدن</div></div>';
        $html .= '<div style="background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.15);border-radius:16px;padding:18px;text-align:center;"><div style="font-size:.78rem;color:var(--mut);margin-bottom:6px;">بازگشت دوباره</div><div style="font-size:1.8rem;font-weight:900;color:#10b981;">'.$fa($migration['recovered']).'</div><div style="font-size:.75rem;color:var(--mut);margin-top:4px;">نفر بازگشته</div></div>';
        $html .= '<div style="background:rgba(167,139,250,.06);border:1px solid rgba(167,139,250,.15);border-radius:16px;padding:18px;text-align:center;"><div style="font-size:.78rem;color:var(--mut);margin-bottom:6px;">تغییر خالص</div><div style="font-size:1.8rem;font-weight:900;color:'.$netColor.';">'.$netSign.$fa($net).'</div><div style="font-size:.75rem;color:var(--mut);margin-top:4px;">'.$netLabel.'</div></div>';
        $html .= '</div></div>';

        // ─── فیلتر گروه‌ها ───
        $html .= '<div class="card" style="margin-bottom:20px;">';
        $html .= '<h3 style="margin-top:0;">گروه‌های مشتریان</h3>';
        $html .= '<p class="muted" style="font-size:.82rem;margin-bottom:14px;">برای دیدن جزئیات هر گروه روی آن کلیک کنید.</p>';
        $html .= '<div class="rfm-seg-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">';
        // همه
        $allActive = !$segment ? 'active' : '';
        $allBorder = !$segment ? '#38bdf8' : 'var(--line)';
        $html .= '<a href="'.$u('/app/reports/rfm').'" class="rfm-seg-btn '.$allActive.'" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-radius:14px;background:var(--panel2);border:2px solid '.$allBorder.';color:var(--txt);text-decoration:none;font-weight:600;transition:.2s;font-size:.9rem;"><span>👥 همه مشتریان</span><b style="color:var(--acc);">'.$fa($stats['customers']).'</b></a>';
        foreach ($segments as $seg) {
            if ($seg['count'] <= 0) continue;
            $isActive = $segment === $seg['key'];
            $border = $isActive ? $seg['color'] : 'var(--line)';
            $html .= '<a href="'.$u('/app/reports/rfm?segment='.$seg['key']).'" class="rfm-seg-btn '.($isActive?'active':'').'" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-radius:14px;background:var(--panel2);border:2px solid '.$border.';color:var(--txt);text-decoration:none;font-weight:600;transition:.2s;font-size:.9rem;" onmouseover="this.style.borderColor=\''.$seg['color'].'\'" onmouseout="this.style.borderColor=\''.($isActive?$seg['color']:'var(--line)').'\'"><span style="display:flex;align-items:center;gap:8px;"><span style="width:10px;height:10px;border-radius:50%;background:'.$seg['color'].';flex-shrink:0;"></span>'.$h($seg['label']).'</span><b style="color:'.$seg['color'].';">'.$fa($seg['count']).'</b></a>';
        }
        $html .= '</div></div>';

        // ─── فهرست مشتریان ───
        $html .= '<div class="card" style="margin-bottom:14px;">';
        $html .= '<h3 style="margin:0 0 4px;">جزئیات مشتریان</h3>';
        $html .= '<p class="muted" style="font-size:.8rem;margin-bottom:16px;">امتیاز هر مشتری در چهار بُعد نمایش داده شده است.</p>';
        if (count($customers)) {
            $html .= '<div class="rfm-list" style="display:grid;gap:10px;">';
            foreach ($customers as $c) {
                $html .= '<div class="rfm-row" style="background:var(--panel2);border:1px solid var(--line);border-radius:18px;padding:18px;border-right:4px solid '.$c['color'].';transition:.2s;" onmouseover="this.style.boxShadow=\'0 6px 20px rgba(0,0,0,.12)\'" onmouseout="this.style.boxShadow=\'\'">';

                // نام و گروه
                $html .= '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:12px;">';
                $html .= '<div><a href="'.$h($c['url']).'" style="font-weight:900;font-size:1.05rem;color:var(--acc);text-decoration:none;">'.$h($c['name']).'</a><div class="muted" style="font-size:.78rem;">'.$h($c['phone'] ?: ($c['email'] ?: 'بدون اطلاعات تماس')).'</div></div>';
                $html .= '<span style="display:inline-flex;padding:5px 14px;border-radius:999px;font-size:.78rem;font-weight:800;background:'.$c['color'].'18;color:'.$c['color'].';">'.$h($c['segment_label']).'</span>';
                $html .= '</div>';

                // اطلاعات خرید
                $html .= '<div class="rfm-meta" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:14px;color:var(--mut);font-size:.84rem;">';
                $html .= '<div>🕐 آخرین خرید: <b style="color:var(--txt);">'.$h($c['last_order_j']).'</b></div>';
                $html .= '<div>📅 فاصله: <b style="color:var(--txt);">'.$h($c['days_since']).'</b></div>';
                $html .= '<div>🔢 تعداد خرید: <b style="color:var(--txt);">'.$h($c['orders_count']).'</b></div>';
                $html .= '<div>💵 مجموع خرید: <b style="color:var(--txt);">'.$h($c['total_spent']).'</b></div>';
                $html .= '<div>📆 طول رابطه: <b style="color:var(--txt);">'.$h($c['length_label']).'</b></div>';
                $html .= '</div>';

                // نوارهای امتیاز
                $dims = [
                    ['key'=>'r','label'=>'تازگی خرید','color'=>'#38bdf8'],
                    ['key'=>'f','label'=>'دفعات خرید','color'=>'#10b981'],
                    ['key'=>'m','label'=>'ارزش خرید','color'=>'#f59e0b'],
                    ['key'=>'l','label'=>'طول رابطه','color'=>'#a78bfa']
                ];
                $html .= '<div class="rfm-bars" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;">';
                foreach ($dims as $dim) {
                    $score = $c[$dim['key']];
                    $html .= '<div style="background:rgba(255,255,255,.02);border:1px solid rgba(148,163,184,.10);border-radius:12px;padding:8px 10px;">';
                    $html .= '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;"><span style="font-size:.72rem;color:var(--mut);">'.$dim['label'].'</span><span style="font-weight:800;font-size:.8rem;color:'.$dim['color'].';">'.$fa($score).' از ۵</span></div>';
                    $html .= '<div style="display:flex;gap:3px;">';
                    for ($i=1; $i<=5; $i++) {
                        $html .= '<span style="height:6px;flex:1;border-radius:999px;background:'.($i<=$score?$dim['color']:'#334155').';"></span>';
                    }
                    $html .= '</div></div>';
                }
                $html .= '</div>';

                // پیشنهاد
                $html .= '<div style="padding:10px 14px;border-radius:12px;background:'.$c['color'].'0A;border:1px solid '.$c['color'].'18;color:var(--mut);font-size:.83rem;line-height:1.8;"><b style="color:'.$c['color'].';">💡 پیشنهاد:</b> '.$h($c['suggestion']).'</div>';
                $html .= '</div>';
            }
            $html .= '</div>';

            // صفحه‌بندی
            $html .= '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:18px;padding-top:12px;border-top:1px solid var(--line);">';
            $html .= '<div>'.($page>1?'<a class="btn btn-ghost" href="'.$u('/app/reports/rfm?segment='.($segment??'').'&page='.($page-1)).'">→ قبلی</a>':'').'</div>';
            $html .= '<div class="muted" style="font-size:.85rem;">صفحه '.$fa($page).'</div>';
            $html .= '<div>'.($hasNext?'<a class="btn btn-ghost" href="'.$u('/app/reports/rfm?segment='.($segment??'').'&page='.($page+1)).'">بعدی ←</a>':'').'</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="empty" style="padding:40px;">داده‌ای برای نمایش وجود ندارد.</div>';
        }
        $html .= '</div>';

        // ─── CSS ریسپانسیو ───
        $html .= '<style>
@media(min-width:1400px){.rfm-stats-grid{grid-template-columns:repeat(6,1fr)!important;}}
@media(max-width:1200px){.rfm-stats-grid{grid-template-columns:repeat(3,1fr)!important;}}
@media(max-width:900px){.rfm-charts{grid-template-columns:1fr!important;}.rfm-stats-grid{grid-template-columns:repeat(3,1fr)!important;}.rfm-mig-grid{grid-template-columns:repeat(3,1fr)!important;}}
@media(max-width:680px){.rfm-stats-grid{grid-template-columns:repeat(2,1fr)!important;gap:8px;}.rfm-stat{padding:12px 8px!important;}.rfm-bars{grid-template-columns:repeat(2,1fr)!important;}.rfm-meta{grid-template-columns:repeat(2,1fr)!important;}.rfm-seg-grid{grid-template-columns:repeat(2,1fr)!important;}.rfm-mig-grid{grid-template-columns:repeat(3,1fr)!important;gap:8px;}}
@media(max-width:440px){.rfm-stats-grid{grid-template-columns:1fr 1fr!important;gap:6px;}.rfm-stat{padding:10px 6px!important;border-radius:12px!important;}.rfm-bars{grid-template-columns:1fr!important;}.rfm-meta{grid-template-columns:1fr!important;}.rfm-seg-grid{grid-template-columns:1fr!important;}.rfm-mig-grid{grid-template-columns:1fr!important;}.rfm-tm-block{min-width:70px!important;padding:12px 10px!important;}.rfm-row{padding:12px!important;}.rfm-donut-wrap{flex-direction:column!important;align-items:center!important;}.rfm-legend{width:100%!important;}}
.rfm-seg-btn.active{border-color:var(--acc)!important;box-shadow:0 0 0 3px rgba(56,189,248,.10);}
.rfm-seg-btn:hover:not(.active){transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.12);}
.rfm-tm-block:hover{z-index:2;}
</style>';

        return $html;
    }

    // ─────── Data Methods ───────
    public function export(Request $request): StreamedResponse
    {
        $segment = $request->get('segment');
        return response()->streamDownload(function () use ($segment) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['نام','موبایل','ایمیل','آخرین خرید','روز از آخرین خرید','تعداد خرید','مجموع خرید','طول رابطه (روز)','گروه','پیشنهاد']);
            $offset = 0;
            do {
                $rows = $this->customerRows($segment, 500, $offset);
                foreach ($rows as $r) fputcsv($out, [$r['name'],$r['phone'],$r['email'],$r['last_order_j'],$r['days_since_raw'],$r['orders_count_raw'],$r['total_spent_raw'],$r['length_days'],$r['segment_label'],$r['suggestion']]);
                $offset += 500;
            } while (count($rows) === 500);
            fclose($out);
        }, 'moshtariyar-rfm-'.now()->format('Ymd-His').'.csv', ['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function analyze(): array {
        $segments = $this->segments();
        return ['rows'=>$this->customerRows(null, 10, 0), 'segments'=>$segments, 'stats'=>$this->stats(), 'treemap'=>$this->treemapData($segments)];
    }

    private function stats(): array {
        $c = (int)DB::table('customers')->count();
        $b = (int)DB::table('orders')->whereNotNull('customer_id')->distinct()->count('customer_id');
        $a = (int)DB::table('orders')->whereNotNull('customer_id')->where('placed_at','>=',now()->subDays(30))->distinct()->count('customer_id');
        $tr = (float)DB::table('orders')->sum('total');
        $to = (int)DB::table('orders')->whereNotNull('customer_id')->count();
        $ar = $this->segmentCount('at_risk') + $this->segmentCount('hibernating') + $this->segmentCount('lost');
        $avo = $to > 0 ? round($tr / $to) : 0;
        return ['customers'=>$c,'buyers'=>$b,'active'=>$a,'at_risk'=>$ar,'total_revenue'=>$tr,'avg_order_value'=>$avo,'churn_rate'=>$b>0?round(($ar/$b)*100,1):0];
    }

    private function segments(): array {
        $items = self::SEGMENTS;
        foreach ($items as $k => &$v) { $v['key']=$k; $v['count']=$this->segmentCount($k); }
        return $items;
    }

    private function segmentCount(string $s): int {
        $w = $this->segmentSql($s);
        $sql = 'SELECT COUNT(*) c FROM ('.$this->baseSql().') x';
        if ($w) $sql .= " WHERE {$w}";
        return (int)(DB::selectOne($sql)->c ?? 0);
    }

    private function treemapData(array $segs): array {
        $t = array_sum(array_column($segs,'count'));
        $items = [];
        foreach ($segs as $s) if ($s['count']>0) { $pct = $t>0?round(($s['count']/$t)*100,1):0; $items[] = ['key'=>$s['key'],'label'=>$s['label'],'desc'=>$s['desc'],'color'=>$s['color'],'count'=>$s['count'],'percent'=>$pct]; }
        usort($items, fn($a,$b)=>$b['count']<=>$a['count']);
        return $items;
    }

    private function donutData(array $segs): array {
        $t = array_sum(array_column($segs,'count'));
        $items = []; $cum = 0;
        foreach ($segs as $s) if ($s['count']>0&&$t>0) { $pct = round(($s['count']/$t)*100,1); $items[] = ['key'=>$s['key'],'label'=>$s['label'],'color'=>$s['color'],'count'=>$s['count'],'percent'=>$pct,'start'=>$cum]; $cum += $pct; }
        return $items;
    }

    private function migrationData(): array {
        $m = (int)DB::selectOne("SELECT COUNT(*) c FROM (SELECT customer_id, MAX(placed_at) lo, COUNT(*) cnt FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id) x WHERE lo >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND lo < DATE_SUB(NOW(), INTERVAL 30 DAY) AND cnt >= 3")->c;
        $r = (int)DB::selectOne("SELECT COUNT(*) c FROM (SELECT customer_id, MAX(placed_at) lo, COUNT(*) cnt FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id) x WHERE lo >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND cnt >= 3")->c;
        return ['to_at_risk'=>$m,'recovered'=>$r,'net_change'=>$r-$m];
    }

    private function baseSql(): string {
        return "SELECT c.id, c.full_name, c.phone, c.email, c.created_at, o.last_order_at, o.first_order_at, COALESCE(o.orders_count,0) orders_count, COALESCE(o.total_spent,0) total_spent FROM customers c LEFT JOIN (SELECT customer_id, MAX(placed_at) last_order_at, MIN(placed_at) first_order_at, COUNT(*) orders_count, COALESCE(SUM(total),0) total_spent FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id) o ON o.customer_id = c.id";
    }

    private function segmentSql(?string $s): ?string {
        return match($s){
            'no_purchase'=>'orders_count = 0',
            'champions'=>'orders_count >= 8 AND total_spent >= 50000000 AND last_order_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'loyal'=>'orders_count >= 4 AND last_order_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'potential'=>'orders_count BETWEEN 1 AND 3 AND last_order_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'new'=>'orders_count = 1 AND last_order_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'at_risk'=>'orders_count >= 3 AND last_order_at < DATE_SUB(NOW(), INTERVAL 60 DAY) AND last_order_at >= DATE_SUB(NOW(), INTERVAL 120 DAY)',
            'hibernating'=>'orders_count > 0 AND last_order_at < DATE_SUB(NOW(), INTERVAL 120 DAY) AND last_order_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)',
            'lost'=>'orders_count > 0 AND last_order_at < DATE_SUB(NOW(), INTERVAL 365 DAY)',
            default=>null,
        };
    }

    private function customerRows(?string $s, int $lim, int $off): array {
        $sql = $this->baseSql();
        $w = $this->segmentSql($s);
        if ($w) $sql .= " WHERE {$w}";
        $sql .= " ORDER BY total_spent DESC, orders_count DESC LIMIT ? OFFSET ?";
        return array_map(fn($r)=>$this->mapRow($r), DB::select($sql, [$lim, $off]));
    }

    private function mapRow($row): array {
        $oc = (int)$row->orders_count; $t = (float)$row->total_spent;
        $last = $row->last_order_at ? \Carbon\Carbon::parse($row->last_order_at) : null;
        $first = $row->first_order_at ? \Carbon\Carbon::parse($row->first_order_at) : null;
        $created = \Carbon\Carbon::parse($row->created_at);
        $dr = $last ? (int)$last->diffInDays(now()) : null;
        $ld = $first ? (int)$first->diffInDays(now()) : (int)$created->diffInDays(now());
        $r = $this->recencyScore($dr); $f = $this->frequencyScore($oc); $m = $this->monetaryScore($t); $l = $this->lengthScore($ld);
        [$key,$sl,$sg,$color] = $this->segment($r,$f,$m,$l,$oc);
        return ['id'=>(int)$row->id,'name'=>$row->full_name?:'—','phone'=>$row->phone,'email'=>$row->email,'last_order_j'=>$last?Jalali::date($last):'—','days_since'=>$dr===null?'بدون خرید':Num::fa($dr).' روز','days_since_raw'=>$dr??'','orders_count'=>Num::fa(number_format($oc)),'orders_count_raw'=>$oc,'total_spent'=>Money::show($t),'total_spent_raw'=>$t,'length_days'=>$ld,'length_label'=>$this->lengthLabel($ld),'r'=>$r,'f'=>$f,'m'=>$m,'l'=>$l,'segment_key'=>$key,'segment_label'=>$sl,'suggestion'=>$sg,'color'=>$color,'url'=>url('/app/customers/'.$row->id)];
    }

    private function recencyScore(?int $d): int { if($d===null)return 0; return match(true){$d<=7=>5,$d<=30=>4,$d<=60=>3,$d<=120=>2,default=>1}; }
    private function frequencyScore(int $c): int { return match(true){$c>=10=>5,$c>=5=>4,$c>=3=>3,$c>=2=>2,$c>=1=>1,default=>0}; }
    private function monetaryScore(float $t): int { return match(true){$t>=50000000=>5,$t>=20000000=>4,$t>=5000000=>3,$t>=1000000=>2,$t>0=>1,default=>0}; }
    private function lengthScore(int $d): int { return match(true){$d>=730=>5,$d>=365=>4,$d>=180=>3,$d>=90=>2,$d>=30=>1,default=>0}; }
    private function lengthLabel(int $d): string { return match(true){$d>=730=>'بیش از ۲ سال',$d>=365=>'بیش از ۱ سال',$d>=180=>'۶ ماه تا ۱ سال',$d>=90=>'۳ تا ۶ ماه',$d>=30=>'۱ تا ۳ ماه',default=>'کمتر از ۱ ماه'}; }

    private function segment(int $r, int $f, int $m, int $l, int $oc): array {
        if($oc===0)return['no_purchase',self::SEGMENTS['no_purchase']['label'],'برای اولین خرید، پیام خوش‌آمد و کوپن شروع خرید ارسال کنید.',self::SEGMENTS['no_purchase']['color']];
        if($r>=4&&$f>=4&&$m>=4&&$l>=4)return['champions',self::SEGMENTS['champions']['label'],'پیشنهاد ویژه، دعوت به برنامه سفیران برند، دسترسی زودهنگام به محصولات جدید و هدیه اختصاصی.',self::SEGMENTS['champions']['color']];
        if($r>=4&&$f>=3)return['loyal',self::SEGMENTS['loyal']['label'],'تخفیف ویژه وفاداری، نمایش زودهنگام محصولات جدید، مأموریت خرید بعدی.',self::SEGMENTS['loyal']['color']];
        if($r>=4&&$f<=2)return['potential',self::SEGMENTS['potential']['label'],'پیشنهاد خرید دوم یا سوم، پیام آموزشی، مأموریت ساده برای تشویق.',self::SEGMENTS['potential']['color']];
        if($r>=4&&$f==1&&$l<=1)return['new',self::SEGMENTS['new']['label'],'ارسال ۳ پیامک خوش‌آمدگویی در ۱۴ روز اول، معرفی باشگاه مشتریان.',self::SEGMENTS['new']['color']];
        if($r<=2&&$f>=3)return['at_risk',self::SEGMENTS['at_risk']['label'],'کمپین بازگشت با تخفیف ویژه و زمان محدود، تماس یا پیام شخصی.',self::SEGMENTS['at_risk']['color']];
        if($r<=2&&$f>0)return['hibernating',self::SEGMENTS['hibernating']['label'],'پیشنهاد بازگشت با انگیزه قوی، کد تخفیف ۲۰٪ با اعتبار ۴۸ ساعته.',self::SEGMENTS['hibernating']['color']];
        if($r===1&&$l>=3)return['lost',self::SEGMENTS['lost']['label'],'آخرین تلاش با تخفیف قابل توجه، یا انتقال به لیست غیرفعال.',self::SEGMENTS['lost']['color']];
        return['potential',self::SEGMENTS['potential']['label'],'با پیشنهاد مناسب و یادآوری خرید، تعامل را بیشتر کنید.',self::SEGMENTS['potential']['color']];
    }
}
