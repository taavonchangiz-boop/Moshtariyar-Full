@extends('layouts.app')
@section('title','داشبورد فرماندهی')
@section('heading','داشبورد فرماندهی هوشمند')
@section('subtitle','اتاق فرمان روزانه مدیریت فروش، مشتریان، پشتیبانی، باشگاه و مالی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard-command-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/dashboard-charts.css') }}">

@php
    $stats                 = $stats ?? [];
    $top_customers         = $top_customers ?? collect();
    $recent_orders         = $recent_orders ?? collect();
    $quick_insights        = $quick_insights ?? [];
    $command_actions       = $command_actions ?? [];
    $growth_opportunities  = $growth_opportunities ?? [];
    $assistant_scope       = $assistant_scope ?? [];
    $system_health         = $system_health ?? ['score' => 75, 'label' => 'در حال بررسی', 'tone' => 'warn'];
    $money = fn($value) => \Modules\Core\Support\Money::show((float) $value) . ' ' . \Modules\Core\Support\Money::unitLabel();
    $healthTone  = $system_health['tone']  ?? 'warn';
    $healthScore = (int) ($system_health['score'] ?? 75);
    $userName    = auth()->user()->name ?? 'مدیر';

    if (empty($assistant_scope)) {
        $assistant_scope = [
            ['title' => 'فروش و سفارش',       'description' => 'پیگیری سفارش‌های باز، درآمد روزانه و آمار فروش',           'url' => url('/app/orders')],
            ['title' => 'مشتریان و ۳۶۰',     'description' => 'مشتریان ارزشمند، در آستانه ریزش و نیازمند پیگیری',          'url' => url('/app/customers')],
            ['title' => 'پشتیبانی و تیکت',   'description' => 'تیکت‌های فوری، خارج مهلت و پاسخگویی سریع',                   'url' => url('/app/tickets')],
            ['title' => 'باشگاه مشتریان',     'description' => 'اعضا، امتیازات، ماموریت‌ها و کد تخفیف',                      'url' => url('/app/loyalty')],
            ['title' => 'محصولات و انبار',    'description' => 'موجودی، قیمت‌گذاری، طلا و نقره و کالای محاسباتی',           'url' => url('/app/products')],
            ['title' => 'کمپین‌ها و رشد',     'description' => 'کمپین‌های فعال، رفرال و ماموریت‌های رشد',                     'url' => url('/app/campaigns')],
        ];
    }

    if (empty($quick_insights)) {
        $quick_insights = [
            'برای مشتریان بدون خرید در ۹۰ روز اخیر، کمپین بازگشت بساز و ۱۵٪ تخفیف بده.',
            'تیکت‌های خارج مهلت را در اولین فرصت پیگیری کن تا رضایت مشتری حفظ شود.',
            'کالاهای با موجودی کم را چک کن و در صورت نیاز بازخرید انجام بده.',
        ];
    }
@endphp

<div class="command-page">

    {{-- ═══════════ هدر ═══════════ --}}
    <section class="command-hero">
        <div class="command-hero-main">
            <span class="command-eyebrow">اتاق فرمان روزانه</span>
            <h2>سلام {{ $userName }}، امروز کسب‌وکار را از همین‌جا مدیریت کن</h2>
            <p>این صفحه، وضعیت واقعی فروش، سفارش‌ها، تیکت‌ها، باشگاه مشتریان، پیش‌فاکتورها و فرصت‌های رشد را کنار هم می‌آورد تا مدیر بدون جابه‌جایی بین چند صفحه، تصمیم سریع و دقیق بگیرد.</p>
            <div class="command-hero-actions">
                <a class="btn" href="{{ url('/app/assistant') }}">پرسش از دستیار</a>
                <button class="btn btn-ghost" type="button" onclick="بازکردن_تنظیمات_ویجت()">⚙️ شخصی‌سازی</button>
                <a class="btn btn-ghost" href="{{ url('/app/tickets?overdue=1') }}">تیکت‌های فوری</a>
                <a class="btn btn-ghost" href="{{ url('/app/customers') }}">مشتریان</a>
                <a class="btn btn-ghost" href="{{ url('/app/orders') }}">سفارش‌ها</a>
            </div>
        </div>

        <aside class="command-health-card is-{{ $healthTone }}">
            <div class="command-health-ring" style="--score: {{ $healthScore }};">
                <strong>@fa($healthScore)</strong>
                <span>از ۱۰۰</span>
            </div>
            <div>
                <span>سلامت عملیات</span>
                <b>{{ $system_health['label'] ?? 'در حال بررسی' }}</b>
                <small>به‌روزرسانی: {{ \Modules\Core\Support\Jalali::date(now()) }}</small>
            </div>
        </aside>
    </section>

    {{-- ═══════════ پنل تنظیمات ویجت‌ها ═══════════ --}}
    <section class="پنل-شخصی‌سازی" id="پنل_شخصی_سازی" style="display:none;">
        <div class="کارت-شخصی‌سازی">
            <div class="سربرگ-شخصی‌سازی">
                <b>⚙️ شخصی‌سازی داشبورد</b>
                <span>ویجت‌هایی که می‌خواهی ببینی را انتخاب کن. چیدمان به‌طور خودکار ذخیره می‌شود.</span>
            </div>
            <div class="فهرست-کلیدها">
                <label class="کلید-ویجت" data-widget="network-metrics">
                    <input type="checkbox" checked onchange="تغییر_ویجت('network-metrics', this.checked)"> شاخص‌های اصلی
                </label>
                <label class="کلید-ویجت" data-widget="action-board">
                    <input type="checkbox" checked onchange="تغییر_ویجت('action-board', this.checked)"> بورد اقدام امروز
                </label>
                <label class="کلید-ویجت" data-widget="growth-insights">
                    <input type="checkbox" checked onchange="تغییر_ویجت('growth-insights', this.checked)"> فرصت‌های رشد و بینش‌ها
                </label>
                <label class="کلید-ویجت" data-widget="assistant-sidebar">
                    <input type="checkbox" checked onchange="تغییر_ویجت('assistant-sidebar', this.checked)"> ستون دستیار
                </label>
                <label class="کلید-ویجت" data-widget="customers-orders">
                    <input type="checkbox" checked onchange="تغییر_ویجت('customers-orders', this.checked)"> مشتریان و سفارش‌های اخیر
                </label>
            </div>
            <div class="عملیات-شخصی‌سازی">
                <button class="btn btn-ghost" onclick="بازنشانی_ویجت‌ها()">بازنشانی به پیش‌فرض</button>
                <button class="btn" onclick="بستن_تنظیمات_ویجت()">تأیید و بستن</button>
            </div>
        </div>
    </section>

    {{-- ═══════════ شاخص‌های اصلی ═══════════ --}}
    <section class="command-metrics-grid ویجت-داشبورد" id="ویجت_network-metrics" aria-label="شاخص‌های اصلی مدیریت">
        <a class="command-metric-card" href="{{ url('/app/orders') }}" style="--metric-color:#2563eb;">
            <span>فروش کل</span>
            <strong>{{ $money($stats['total_revenue'] ?? 0) }}</strong>
            <small>میانگین سفارش: {{ $money($stats['avg_order_value'] ?? 0) }}</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/orders') }}" style="--metric-color:#f59e0b;">
            <span>سفارش‌های باز</span>
            <strong>@fa(number_format($stats['pending_orders'] ?? 0))</strong>
            <small>امروز: @fa(number_format($stats['today_orders'] ?? 0)) سفارش</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/tickets?overdue=1') }}" style="--metric-color:#ef4444;">
            <span>پشتیبانی فوری</span>
            <strong>@fa(number_format($stats['overdue_tickets'] ?? 0))</strong>
            <small>تیکت باز: @fa(number_format($stats['open_tickets'] ?? 0))</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/customers') }}" style="--metric-color:#10b981;">
            <span>مشتریان فعال</span>
            <strong>@fa(number_format($stats['active_customers'] ?? 0))</strong>
            <small>جدید این ماه: @fa(number_format($stats['new_this_month'] ?? 0))</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/loyalty') }}" style="--metric-color:#8b5cf6;">
            <span>باشگاه مشتریان</span>
            <strong>@fa(number_format($stats['loyalty_members'] ?? 0))</strong>
            <small>ماموریت فعال: @fa(number_format($stats['active_missions'] ?? 0))</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/tax-invoices') }}" style="--metric-color:#0ea5e9;">
            <span>پیش‌فاکتورهای باز</span>
            <strong>@fa(number_format($stats['open_proformas'] ?? 0))</strong>
            <small>پیش‌نویس: @fa(number_format($stats['draft_invoices'] ?? 0))</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/reminders') }}" style="--metric-color:#8b5cf6;">
            <span>وظایف باز</span>
            <strong>@fa(number_format($stats['open_tasks'] ?? 0))</strong>
            <small>عقب‌افتاده: @fa(number_format($stats['overdue_tasks'] ?? 0))</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/workflows') }}" style="--metric-color:#14b8a6;">
            <span>اتوماسیون فعال</span>
            <strong>@fa(number_format($stats['active_workflows'] ?? 0))</strong>
            <small>سفر در حال اجرا: @fa(number_format($stats['active_journeys'] ?? 0))</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/reports?tab=clv') }}" style="--metric-color:#ec4899;">
            <span>میانگین ارزش یک سال آینده هر مشتری</span>
            <strong>{{ $money($stats['avg_clv_12m'] ?? 0) }}</strong>
            <small>پیش‌بینی هر مشتری</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/reports?tab=clv') }}" style="--metric-color:#f59e0b;">
            <span>ریسک ریزش میانگین</span>
            <strong>@fa(number_format($stats['avg_churn_probability'] ?? 0))٪</strong>
            <small>در معرض خطر: @fa(number_format($stats['total_at_risk'] ?? 0)) نفر</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/reports?tab=clv') }}" style="--metric-color:#10b981;">
            <span>درآمد پیش‌بینی ۱۲ ماه</span>
            <strong>{{ $money($stats['total_predicted_revenue_12m'] ?? 0) }}</strong>
            <small>قابل بازگشت: {{ $money($stats['recoverable_revenue'] ?? 0) }}</small>
        </a>

        <a class="command-metric-card" href="{{ url('/app/segments') }}" style="--metric-color:#ef4444;">
            <span>مشتریان از دست رفته</span>
            <strong>@fa(number_format($stats['total_churned'] ?? 0))</strong>
            <small>ریسک بالای ۶۶٪ - نیاز به کمپین فوری</small>
        </a>
    </section>

    {{-- ==================== ویجت مشتریان ارزشمند در معرض ریزش - جدید با بالاترین دقت ==================== --}}
    @if(!empty($stats['top_churn_risk']))
    <section class="command-card is-accent" style="--card-color:#ef4444; margin-bottom:1rem;">
        <header>
            <div>
                <span>هشدار هوشمند ریزش</span>
                <h2>💎 مشتریان ارزشمند در معرض ریزش - اقدام فوری با یک کلیک</h2>
                <p>این مشتریان ارزش پیش‌بینی بالایی دارند اما ۴۰ تا ۸۵٪ احتمال می‌رود که دیگر برنگردند. با یک کلیک کمپین بازگشت خودکار از طریق تمام کانال‌های فعال (پیامک، تلگرام، بله، روبیکا، ایتا، واتساپ) برایشان ارسال کن و وظیفه پیگیری برای تیم فروش بساز.</p>
            </div>
            <a class="command-link" href="{{ url('/app/reports?tab=clv') }}">مشاهده همه در گزارش پیشرفته</a>
        </header>
        <div class="command-compact-list">
            @foreach(array_slice($stats['top_churn_risk'], 0, 5) as $c)
                <div style="display:flex; align-items:center; justify-content:space-between; gap:.6rem; padding:.7rem .8rem; border:1px solid var(--line); border-radius:.85rem; background:var(--panel2);">
                    <div style="min-width:0; flex:1;">
                        <b style="display:block; color:var(--txt); font-size:.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $c['full_name'] ?? 'بدون نام' }}</b>
                        <small style="display:block; color:var(--mut); font-size:.75rem;">
                            {{ $c['phone'] ?? '---' }} • {{ $c['recency_days'] ?? 0 }} روز پیش • ریسک {{ $c['churn_probability'] ?? 0 }}٪ • ارزش پیش‌بینی {{ \Modules\Core\Support\Money::show($c['predicted_clv_12m'] ?? 0) }}
                        </small>
                    </div>
                    <form method="post" action="{{ url('/app/customers/' . ($c['customer_id'] ?? 0) . '/return-campaign') }}" style="flex:0 0 auto;">
                        @csrf
                        <button class="btn" style="padding:.4rem .8rem; font-size:.8rem; min-height:2.2rem; white-space:nowrap;">📩 ارسال کمپین بازگشت</button>
                    </form>
                </div>
            @endforeach
        </div>
        <div style="margin-top:.8rem; display:flex; gap:.5rem; flex-wrap:wrap;">
            <a class="btn btn-ghost" href="{{ url('/app/segments/auto') }}">🔄 اجرای مجدد بخش‌بندی و ساخت کمپین بازگشت خودکار</a>
            <small style="color:var(--mut); line-height:2;">این لیست هر روز با تحلیل پیشرفته به‌روز می‌شود</small>
        </div>
    </section>
    @endif

    {{-- ==================== ویجت درآمد بازگشتی - جدید - همیشه نمایش داده می‌شود حتی با صفر ==================== --}}
    <section class="command-card is-accent" style="--card-color:#10b981; margin-bottom:1rem; background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(14,165,233,.06)); border-color: rgba(16,185,129,.22);">
        <header>
            <div>
                <span>گزارش لحظه‌ای بازگشت - ROI مرکز بازگشت و حفظ</span>
                <h2>🎉 درآمد بازگشتی - مشتریانی که برگشته‌اند</h2>
                <p>این‌ها مشتریانی هستند که بعد از ۴۵ روز غیبت دوباره خرید کرده‌اند و به صورت خودکار امتیاز دو برابر گرفته‌اند. نرخ بازگشت فعلی <b>{{ $stats['recovery_rate'] ?? 0 }}٪</b> است. میانگین ارزش بازگشتی {{ $money($stats['recovered_revenue_30d'] ?? 0) }} در ۳۰ روز اخیر.</p>
            </div>
            <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                <a class="command-link" href="{{ url('/app/reports?tab=recovery') }}">📊 گزارش کامل بازگشت و حفظ</a>
                <a class="command-link" href="{{ url('/app/retention?filter=recovered') }}">🛟 مرکز بازگشت و حفظ</a>
            </div>
        </header>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(9rem,1fr)); gap:.6rem; margin-bottom: .9rem;">
            <div style="padding:.7rem .8rem; border-radius:.8rem; background: var(--panel2); border:1px solid var(--line); text-align:center;">
                <small style="display:block; color:var(--mut); font-size:.75rem;">بازگشته ۳۰ روزه</small>
                <b style="font-size:1.2rem; color:#059669;">@fa(number_format($stats['recovered_30d'] ?? 0)) نفر</b>
            </div>
            <div style="padding:.7rem .8rem; border-radius:.8rem; background: var(--panel2); border:1px solid var(--line); text-align:center;">
                <small style="display:block; color:var(--mut); font-size:.75rem;">درآمد بازگشتی ۳۰ روزه</small>
                <b style="font-size:1.05rem; color:#059669;">{{ $money($stats['recovered_revenue_30d'] ?? 0) }}</b>
            </div>
            <div style="padding:.7rem .8rem; border-radius:.8rem; background: var(--panel2); border:1px solid var(--line); text-align:center;">
                <small style="display:block; color:var(--mut); font-size:.75rem;">بازگشته ۷ روزه</small>
                <b style="font-size:1.2rem; color:#0ea5e9;">@fa(number_format($stats['recovered_7d'] ?? 0)) نفر</b>
            </div>
            <div style="padding:.7rem .8rem; border-radius:.8rem; background: var(--panel2); border:1px solid var(--line); text-align:center;">
                <small style="display:block; color:var(--mut); font-size:.75rem;">نرخ بازگشت</small>
                <b style="font-size:1.2rem; color:#8b5cf6;">@fa($stats['recovery_rate'] ?? 0)٪</b>
            </div>
        </div>
        @if(!empty($stats['recovered_list']))
        <div class="command-compact-list">
            @foreach(array_slice($stats['recovered_list'], 0, 5) as $c)
                <div style="display:flex; align-items:center; justify-content:space-between; gap:.6rem; padding:.7rem .8rem; border:1px solid rgba(16,185,129,.22); border-radius:.85rem; background: linear-gradient(90deg, rgba(16,185,129,.08), transparent);">
                    <div style="min-width:0; flex:1;">
                        <b style="display:block; color:var(--txt); font-size:.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $c['full_name'] ?? 'بدون نام' }} 🎉</b>
                        <small style="display:block; color:var(--mut); font-size:.75rem;">
                            {{ $c['phone'] ?? '---' }} • بازگشته پس از {{ $c['gap_days'] ?? 0 }} روز • {{ $c['returned_at_fa'] ?? '' }} • {{ \Modules\Core\Support\Money::show($c['order_total'] ?? 0) }}
                        </small>
                    </div>
                    <span style="flex:0 0 auto; padding:.25rem .6rem; border-radius:999px; background:#10b98122; color:#059669; border:1px solid #10b98144; font-size:.75rem; font-weight:800;">✨ دو برابر شد</span>
                </div>
            @endforeach
        </div>
        @else
        <div style="padding:1rem; border-radius:.8rem; background: var(--panel2); border:1px dashed var(--line); text-align:center;">
            <div style="font-size:2rem; margin-bottom:.4rem;">🛟</div>
            <b style="display:block; margin-bottom:.25rem;">هنوز بازگشتی با فاصله ۴۵ روز در ۳۰ روز اخیر ثبت نشده</b>
            <small style="color:var(--mut); line-height:1.6; display:block;">وقتی مشتری بعد از ۴۵ روز غیبت برگردد، اینجا نمایش داده می‌شود و امتیاز دو برابر می‌گیرد.<br>سیستم فعال است و هر خرید جدید را لحظه‌ای بررسی می‌کند. برای تست، یک سفارش آزمایشی برای مشتری قدیمی ثبت کنید.</small>
        </div>
        @endif
        <div style="margin-top:.8rem; display:flex; gap:.5rem; flex-wrap:wrap;">
            <a class="btn" href="{{ url('/app/reports?tab=recovery') }}">📈 نمودار ماهانه شمسی درآمد بازگشتی</a>
            <a class="btn btn-ghost" href="{{ url('/app/retention') }}">🛟 مرکز بازگشت و حفظ</a>
            <small style="color:var(--mut); line-height:2;">هر بازگشت = امتیاز دو برابر خودکار + یادداشت فروش</small>
        </div>
    </section>

    {{-- ═══════════ نمودارهای زنده ═══════════ --}}
    @php $نمودارها = $chart_data ?? []; @endphp
    <section class="charts-grid ویجت-داشبورد" id="ویجت_dashboard-charts" aria-label="نمودارهای تحلیلی">
        <article class="chart-card">
            <div class="chart-card-header">
                <h3>📊 فروش ۱۲ ماه اخیر</h3>
                <small>به تومان</small>
            </div>
            <div class="chart-body">
                <canvas id="chartMonthlySales"></canvas>
            </div>
        </article>
        <article class="chart-card">
            <div class="chart-card-header">
                <h3>🍩 وضعیت سفارش‌ها</h3>
                <small>تعداد</small>
            </div>
            <div class="chart-body">
                <canvas id="chartOrdersStatus"></canvas>
            </div>
        </article>
    </section>
    <section class="chart-card chart-full-width ویجت-داشبورد" id="ویجت_chart-customers" aria-label="رشد مشتریان">
        <div class="chart-card-header">
            <h3>📈 رشد مشتریان (تجمعی ۱۲ ماه)</h3>
            <small>تعداد کل</small>
        </div>
        <div class="chart-body">
            <canvas id="chartCustomerGrowth"></canvas>
        </div>
    </section>

    {{-- ═══════════ چیدمان اصلی (دو ستونه) ═══════════ --}}
    <section class="command-layout">

        <div class="command-main-column">

            {{-- بورد اقدام امروز --}}
            <article class="command-card is-accent ویجت-داشبورد" id="ویجت_action-board" style="--card-color:#2563eb;">
                <header>
                    <div>
                        <span>بورد اقدام امروز</span>
                        <h2>کارهایی که بیشترین اثر را روی فروش، رضایت و نگهداشت دارند</h2>
                        <p>روی هر ردیف کلیک کن تا مستقیم به بخش مربوطه بروی.</p>
                    </div>
                    <a class="command-link" href="{{ url('/app/assistant') }}">تحلیل با دستیار</a>
                </header>

                <div class="command-action-board">
                    @forelse($command_actions as $action)
                        <a class="command-action-row is-{{ $action['tone'] ?? 'blue' }}" href="{{ $action['url'] ?? '#' }}">
                            <div class="command-action-status">
                                <b>@fa(number_format($action['count'] ?? 0))</b>
                            </div>
                            <div class="command-action-body">
                                <small>{{ $action['group'] ?? 'اقدام' }} · مسئول: {{ $action['owner'] ?? 'مدیریت' }}</small>
                                <h3>{{ $action['title'] ?? 'اقدام پیشنهادی' }}</h3>
                                <p>{{ $action['description'] ?? 'جزئیات اقدام ثبت نشده است.' }}</p>
                            </div>
                            <span>مشاهده</span>
                        </a>
                    @empty
                        <a class="command-action-row is-red" href="{{ url('/app/tickets?overdue=1') }}">
                            <div class="command-action-status"><b>@fa(number_format($stats['overdue_tickets'] ?? 0))</b></div>
                            <div class="command-action-body">
                                <small>پشتیبانی · مسئول: تیم پشتیبانی</small>
                                <h3>تیکت‌های خارج از مهلت پاسخ</h3>
                                <p>این تیکت‌ها از مهلت پاسخ گذشته‌اند و نیاز به پیگیری فوری دارند.</p>
                            </div>
                            <span>مشاهده</span>
                        </a>
                        <a class="command-action-row is-warn" href="{{ url('/app/orders?status=pending') }}">
                            <div class="command-action-status"><b>@fa(number_format($stats['pending_orders'] ?? 0))</b></div>
                            <div class="command-action-body">
                                <small>فروش · مسئول: تیم فروش</small>
                                <h3>سفارش‌های در انتظار پیگیری</h3>
                                <p>سفارش‌های باز نیازمند بررسی و آماده‌سازی برای ارسال.</p>
                            </div>
                            <span>مشاهده</span>
                        </a>
                        <a class="command-action-row is-green" href="{{ url('/app/customers?view=vip') }}">
                            <div class="command-action-status"><b>@fa(number_format($stats['active_customers'] ?? 0))</b></div>
                            <div class="command-action-body">
                                <small>مشتریان · مسئول: تیم CRM</small>
                                <h3>مشتریان ارزشمند برای پیگیری</h3>
                                <p>مشتریان VIP که ارزش پیگیری اختصاصی دارند.</p>
                            </div>
                            <span>مشاهده</span>
                        </a>
                        <a class="command-action-row is-purple" href="{{ url('/app/loyalty') }}">
                            <div class="command-action-status"><b>@fa(number_format($stats['loyalty_members'] ?? 0))</b></div>
                            <div class="command-action-body">
                                <small>باشگاه · مسئول: تیم بازاریابی</small>
                                <h3>مدیریت اعضای باشگاه مشتریان</h3>
                                <p>اعضای فعال و مستحق دریافت پاداش یا کد تخفیف.</p>
                            </div>
                            <span>مشاهده</span>
                        </a>
                    @endforelse
                </div>
            </article>

            {{-- فرصت‌های رشد و بینش‌ها --}}
            <section class="command-two-columns ویجت-داشبورد" id="ویجت_growth-insights">

                <article class="command-card is-accent" style="--card-color:#10b981;">
                    <header>
                        <div>
                            <span>فرصت‌های رشد</span>
                            <h2>اقدام‌های الهام‌گرفته از باشگاه مشتریان</h2>
                        </div>
                    </header>
                    <div class="command-growth-list">
                        @forelse($growth_opportunities as $item)
                            <a class="command-growth-item is-{{ $item['tone'] ?? 'green' }}" href="{{ $item['url'] ?? '#' }}">
                                <b>@fa(number_format($item['value'] ?? 0))</b>
                                <div>
                                    <h3>{{ $item['title'] ?? 'فرصت رشد' }}</h3>
                                    <p>{{ $item['description'] ?? 'توضیح ثبت نشده است.' }}</p>
                                </div>
                            </a>
                        @empty
                            <a class="command-growth-item is-purple" href="{{ url('/app/campaigns') }}"><b>+</b><div><h3>ساخت کمپین رفرال</h3><p>با پاداش دعوت دوستان، مشتریان جدید جذب کن.</p></div></a>
                            <a class="command-growth-item is-warn" href="{{ url('/app/loyalty') }}"><b>+</b><div><h3>ارتقای اعضای باشگاه</h3><p>اعضای نزدیک به سطح بالاتر را پیدا کن و پیشنهاد بده.</p></div></a>
                            <a class="command-growth-item is-cyan" href="{{ url('/app/products') }}"><b>+</b><div><h3>محصولات پرفروش</h3><p>روی کالاهای پرتقاضا موجودی بیشتری تأمین کن.</p></div></a>
                        @endforelse
                    </div>
                </article>

                <article class="command-card is-accent" style="--card-color:#f59e0b;">
                    <header>
                        <div>
                            <span>بینش‌های سریع</span>
                            <h2>خلاصه قابل اقدام برای مدیر</h2>
                        </div>
                    </header>
                    <div class="command-insight-list">
                        @foreach($quick_insights as $insight)
                            <div class="command-insight-item">
                                <span></span>
                                <p>{{ $insight }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>

            </section>
        </div>

        {{-- ستون کناری: دستیار --}}
        <aside class="command-side-column ویجت-داشبورد" id="ویجت_assistant-sidebar">

            <article class="command-card command-assistant-card is-accent" style="--card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>دستیار داخلی سامانه</span>
                        <h2>پرسش مستقیم از داده‌های سایت</h2>
                    </div>
                </header>
                <p>دستیار درباره فروش، مشتریان، تیکت‌ها، باشگاه، محصولات، ووکامرس، فاکتور و پایگاه دانش پاسخ می‌دهد.</p>
                <div class="command-assistant-prompts">
                    <button type="button" data-question="گزارش جامع وضعیت امروز را بده">وضعیت امروز</button>
                    <button type="button" data-question="تیکت‌های فوری چندتاست؟">تیکت‌های فوری</button>
                    <button type="button" data-question="گزارش باشگاه مشتریان را بده">باشگاه مشتریان</button>
                    <button type="button" data-question="سفارش‌های باز و فروش را بررسی کن">فروش و سفارش</button>
                </div>
                <div class="command-assistant-chat" id="commandAssistantChat">
                    <div class="command-bot-message">سلام، من آماده‌ام سؤال مدیریتی شما را از داده‌های همین سامانه بررسی کنم.</div>
                </div>
                <form class="command-assistant-form" id="commandAssistantForm">
                    @csrf
                    <input id="commandAssistantInput" placeholder="مثلاً: گزارش وضعیت امروز را بده" autocomplete="off" required>
                    <button class="btn">ارسال</button>
                </form>
            </article>

            <article class="command-card is-accent" style="--card-color:#0ea5e9;">
                <header>
                    <div>
                        <span>پوشش دستیار</span>
                        <h2>بخش‌های آماده پاسخ‌گویی</h2>
                    </div>
                </header>
                <div class="command-scope-list">
                    @foreach($assistant_scope as $scope)
                        <a href="{{ $scope['url'] ?? '#' }}">
                            <h3>{{ $scope['title'] ?? 'بخش سامانه' }}</h3>
                            <p>{{ $scope['description'] ?? 'توضیحی ثبت نشده است.' }}</p>
                        </a>
                    @endforeach
                </div>
            </article>

        </aside>
    </section>

    {{-- ═══════════ بخش پایین: مشتریان و سفارش‌ها ═══════════ --}}
    <section class="command-two-columns command-bottom-section ویجت-داشبورد" id="ویجت_customers-orders">

        <article class="command-card is-accent" style="--card-color:#10b981;">
            <header>
                <div>
                    <span>مشتریان قابل توجه</span>
                    <h2>آخرین مشتریان و ارزش خرید</h2>
                </div>
                <a class="command-link" href="{{ url('/app/customers') }}">همه مشتریان</a>
            </header>
            <div class="command-compact-list">
                @forelse($top_customers as $customer)
                    <a href="{{ url('/app/customers/' . $customer->id) }}">
                        <div>
                            <b>{{ $customer->full_name ?? 'مشتری بدون نام' }}</b>
                            <small>سفارش: @fa(number_format($customer->orders_count ?? 0))</small>
                        </div>
                        <strong>{{ $money($customer->total_spent ?? $customer->lifetime_value ?? 0) }}</strong>
                    </a>
                @empty
                    <div class="command-empty">هنوز مشتری قابل نمایش وجود ندارد.</div>
                @endforelse
            </div>
        </article>

        <article class="command-card is-accent" style="--card-color:#2563eb;">
            <header>
                <div>
                    <span>آخرین سفارش‌ها</span>
                    <h2>سفارش‌های تازه برای پیگیری سریع</h2>
                </div>
                <a class="command-link" href="{{ url('/app/orders') }}">همه سفارش‌ها</a>
            </header>
            <div class="command-compact-list">
                @forelse($recent_orders as $order)
                    <a href="{{ url('/app/orders/' . $order->id) }}">
                        <div>
                            <b>{{ $order->number ? ('سفارش ' . $order->number) : ('سفارش شماره ' . $order->id) }}</b>
                            <small>{{ $order->customer->full_name ?? 'مشتری نامشخص' }}</small>
                        </div>
                        <strong>{{ $money($order->total ?? 0) }}</strong>
                    </a>
                @empty
                    <div class="command-empty">هنوز سفارشی ثبت نشده است.</div>
                @endforelse
            </div>
        </article>

    </section>
</div>

<script>
// ═══════════════════════════════════════════════
// ۱. شخصی‌سازی داشبورد — نمایش/پنهان کردن ویجت‌ها
// ═══════════════════════════════════════════════

var کلید_ذخیره = 'dashboardWidgets';

function دریافت_تنظیمات_ویجت() {
    try {
        return JSON.parse(localStorage.getItem(کلید_ذخیره)) || {};
    } catch (e) {
        return {};
    }
}

function ذخیره_تنظیمات_ویجت(تنظیمات) {
    localStorage.setItem(کلید_ذخیره, JSON.stringify(تنظیمات));
}

function اعمال_تنظیمات_ویجت() {
    var تنظیمات = دریافت_تنظیمات_ویجت();

    document.querySelectorAll('.ویجت-داشبورد').forEach(function(ویجت) {
        var شناسه = ویجت.getAttribute('id').replace('ویجت_', '');
        var فعال = تنظیمات[شناسه] !== false;
        ویجت.style.display = فعال ? '' : 'none';
    });

    document.querySelectorAll('.کلید-ویجت input').forEach(function(چک) {
        var شناسه = چک.parentElement.getAttribute('data-widget');
        var فعال = تنظیمات[شناسه] !== false;
        چک.checked = فعال;
    });
}

function تغییر_ویجت(شناسه, فعال) {
    var تنظیمات = دریافت_تنظیمات_ویجت();
    تنظیمات[شناسه] = فعال;
    ذخیره_تنظیمات_ویجت(تنظیمات);
    اعمال_تنظیمات_ویجت();
}

function بازنشانی_ویجت‌ها() {
    localStorage.removeItem(کلید_ذخیره);
    اعمال_تنظیمات_ویجت();
    document.querySelectorAll('.کلید-ویجت input').forEach(function(چک) {
        چک.checked = true;
    });
}

function بازکردن_تنظیمات_ویجت() {
    document.getElementById('پنل_شخصی_سازی').style.display = 'block';
    document.getElementById('پنل_شخصی_سازی').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function بستن_تنظیمات_ویجت() {
    document.getElementById('پنل_شخصی_سازی').style.display = 'none';
}

اعمال_تنظیمات_ویجت();

// ═══════════════════════════════════════════════
// ۳. نمودارهای Chart.js - فونت یکسان با سایت اصلی استعداد/وزیرمتن
// ═══════════════════════════════════════════════

(function() {
    // فونت یکسان برای تمام چارت‌ها
    if(typeof Chart !== 'undefined'){
        try{
            Chart.defaults.font.family = "'Estedad', 'Vazirmatn', 'Vazirmatn FD', Tahoma, sans-serif";
            Chart.defaults.font.weight = '600';
            Chart.defaults.font.size = 12;
        }catch(e){}
    }
    var chartFontFamily = "'Estedad', 'Vazirmatn', 'Vazirmatn FD', Tahoma, sans-serif";
    var chartData = @json($نمودارها ?? []);

    function buildMonthlySales() {
        var data = chartData.monthly_sales || {};
        var ctx = document.getElementById('chartMonthlySales');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'فروش (تومان)',
                    data: data.values || [],
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderRadius: 6,
                    barThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false, labels: { font: { family: chartFontFamily, weight: '600' } } },
                    tooltip: { titleFont: { family: chartFontFamily }, bodyFont: { family: chartFontFamily } }
                },
                scales: {
                    y: {
                        ticks: {
                            font: { family: chartFontFamily, weight: '600' },
                            callback: function(v) { return v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v; }
                        }
                    },
                    x: {
                        ticks: { font: { family: chartFontFamily, weight: '600' } }
                    }
                }
            }
        });
    }

    function buildOrdersStatus() {
        var data = chartData.orders_by_status || {};
        var ctx = document.getElementById('chartOrdersStatus');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels || [],
                datasets: [{
                    data: data.values || [],
                    backgroundColor: data.colors || [],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11, family: chartFontFamily, weight: '600' },
                            padding: 12,
                            usePointStyle: true
                        }
                    },
                    tooltip: { titleFont: { family: chartFontFamily }, bodyFont: { family: chartFontFamily } }
                }
            }
        });
    }

    function buildCustomerGrowth() {
        var data = chartData.customer_growth || {};
        var ctx = document.getElementById('chartCustomerGrowth');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'مشتریان',
                    data: data.values || [],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false, labels: { font: { family: chartFontFamily } } },
                    tooltip: { titleFont: { family: chartFontFamily }, bodyFont: { family: chartFontFamily } }
                },
                scales: {
                    y: {
                        ticks: {
                            font: { family: chartFontFamily, weight: '600' },
                            callback: function(v) { return v >= 1000 ? (v / 1000).toFixed(1) + 'K' : v; }
                        }
                    },
                    x: {
                        ticks: { font: { family: chartFontFamily, weight: '600' } }
                    }
                }
            }
        });
    }

    if (typeof Chart !== 'undefined') {
        buildMonthlySales();
        buildOrdersStatus();
        buildCustomerGrowth();
    }
})();

// ═══════════════════════════════════════════════
// ۲. دستیار گفت‌وگو
// ═══════════════════════════════════════════════

(function () {
    const form  = document.getElementById('commandAssistantForm');
    const input = document.getElementById('commandAssistantInput');
    const chat  = document.getElementById('commandAssistantChat');

    if (!form || !input || !chat) { return; }

    function escapeHtml(value) {
        return String(value || '').replace(/[<>&]/g, function (char) {
            return { '<': '&lt;', '>': '&gt;', '&': '&amp;' }[char];
        });
    }

    function addMessage(className, html) {
        const node = document.createElement('div');
        node.className = className;
        node.innerHTML = html;
        chat.appendChild(node);
        chat.scrollTop = chat.scrollHeight;
        return node;
    }

    function ask(question) {
        const text = String(question || '').trim();
        if (!text) { return; }

        input.value = '';
        addMessage('command-user-message', escapeHtml(text));
        const loading = addMessage('command-bot-message', 'در حال بررسی داده‌های سامانه...');

        const data = new FormData();
        data.append('message', text);

        const ctx = window.MoshtariyarAssistantContext || {};
        if (ctx.type) { data.append('context_type', ctx.type); }
        if (ctx.id !== null && ctx.id !== undefined && ctx.id !== '') { data.append('context_id', ctx.id); }
        if (ctx.title) { data.append('context_title', ctx.title); }
        if (ctx.url) { data.append('context_url', ctx.url); }

        fetch('{{ url('/app/assistant/ask') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: data
        })
        .then(function (response) { return response.json(); })
        .then(function (result) {
            let html = escapeHtml(result.answer || 'پاسخی دریافت نشد.').replace(/\n/g, '<br>');
            if (result.url) {
                html += '<br><a class="command-chat-link" href="' + result.url + '">مشاهده بخش مرتبط</a>';
            }
            loading.innerHTML = html;
        })
        .catch(function () {
            loading.innerHTML = 'ارتباط با دستیار برقرار نشد. لطفاً دوباره تلاش کنید.';
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        ask(input.value);
    });

    document.querySelectorAll('[data-question]').forEach(function (button) {
        button.addEventListener('click', function () {
            ask(button.dataset.question);
        });
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection
