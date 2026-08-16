@extends('layouts.app')
@section('title','مرکز گزارشات و تحلیل رفتار مشتری')
@section('heading','مرکز گزارشات و تحلیل رفتار مشتری')
@section('subtitle','تحلیل هوشمند فروش، مشتری، ارزش عمر، پرفروش‌ترین‌ها، کانال و پشتیبانی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/reports-center.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

@php
    if (!function_exists('fa_num_hub')) {
        function fa_num_hub($n) {
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return str_replace($en, $fa, (string) $n);
        }
    }
    if (!function_exists('fa_money_hub')) {
        function fa_money_hub($n) { return fa_num_hub(number_format((int) $n)); }
    }
    if (!function_exists('trend_class_hub')) {
        function trend_class_hub($v) { if ($v > 0) return 'up'; if ($v < 0) return 'down'; return 'flat'; }
    }
    $rangeLabels = ['7'=>'هفته گذشته','30'=>'ماه گذشته','90'=>'سه ماه گذشته','365'=>'سال گذشته','all'=>'همه زمان'];
    $rangeLabel = $rangeLabels[$range] ?? 'ماه گذشته';
@endphp

<div class="reports-page" id="reportsPage">

    {{-- هدر --}}
    <section class="reports-hero">
        <div>
            <span class="reports-eyebrow">مرکز تحلیل مدیریتی</span>
            <h2>تصمیم‌گیری سریع بر پایه داده واقعی</h2>
            <p>
                همه اطلاعات کسب‌وکار شما در یک جا به هم وصل است. از ورود مشتری و ثبت سفارش تا پرداخت، پشتیبانی و امتیاز باشگاه، همه در یک پرونده کامل و قابل پیگیری ثبت می‌شود و به شکل کارت‌های بصری و نمودارهای قابل فهم نمایش داده می‌شود. همه تاریخ‌ها شمسی هستند.
            </p>

            <div class="reports-actions">
                <div class="filter-box">
                    <div class="filter-box-title">بازه زمانی گزارش</div>
                    <div class="date-range-filter">
                        @foreach($rangeLabels as $k=>$lbl)
                            <a href="{{ url('/app/reports?tab='.$tab.'&range='.$k) }}" class="date-range-btn {{ $range===$k?'is-active':'' }}">{{ $lbl }}</a>
                        @endforeach
                    </div>
                    <form method="get" action="{{ url('/app/reports') }}" class="date-inputs-row">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <input type="hidden" name="range" value="{{ $range }}">
                        <div class="date-input-small">
                            <label>از تاریخ</label>
                            <input name="from" value="{{ $from }}" placeholder="۱۴۰۳/۰۱/۰۱" class="jdate">
                        </div>
                        <div class="date-input-small">
                            <label>تا تاریخ</label>
                            <input name="to" value="{{ $to }}" placeholder="۱۴۰۳/۱۲/۲۹" class="jdate">
                        </div>
                        <button class="btn btn-small">اعمال بازه</button>
                    </form>
                </div>
                <a class="btn btn-ghost" href="{{ url('/app') }}">← بازگشت به پیشخوان</a>
            </div>

            <div class="filter-summary" style="margin-top:.8rem">
                بازه انتخابی: <b>{{ $from_jalali }} تا {{ $to_jalali }}</b> • تب فعال: <b>{{ $tabs[$tab] ?? $tab }}</b> • میانگین سفارش: <b>{{ fa_money_hub($overview['avg_order'] ?? 0) }} تومان</b>
            </div>
        </div>

        <div class="reports-score">
            <div class="reports-score-item" style="--metric-color:#10b981;">
                <span>مجموع فروش</span>
                <b>{{ fa_money_hub($overview['total_sales'] ?? 0) }}</b>
                <small>تومان - {{ $rangeLabel }}</small>
                @if(($overview['sales_change'] ?? 0)!=0)
                    <span class="trend {{ trend_class_hub($overview['sales_change']) }}">{{ $overview['sales_change']>0?'▲':'▼' }} {{ fa_num_hub(abs($overview['sales_change'])) }}٪ نسبت به قبل</span>
                @endif
            </div>
            <div class="reports-score-item" style="--metric-color:#3b82f6;">
                <span>تعداد سفارش</span>
                <b>{{ fa_num_hub($overview['order_count'] ?? 0) }}</b>
                <small>سفارش ثبت‌شده</small>
                @if(($overview['orders_change'] ?? 0)!=0)
                    <span class="trend {{ trend_class_hub($overview['orders_change']) }}">{{ $overview['orders_change']>0?'▲':'▼' }} {{ fa_num_hub(abs($overview['orders_change'])) }}٪</span>
                @endif
            </div>
            <div class="reports-score-item" style="--metric-color:#8b5cf6;">
                <span>مشتری فعال خریدار</span>
                <b>{{ fa_num_hub($overview['active_customers'] ?? 0) }}</b>
                <small>در این بازه خرید کرده</small>
            </div>
            <div class="reports-score-item" style="--metric-color:#f59e0b;">
                <span>مشتری جدید</span>
                <b>{{ fa_num_hub($overview['new_customers'] ?? 0) }}</b>
                <small>عضو تازه</small>
                @if(($overview['customers_change'] ?? 0)!=0)
                    <span class="trend {{ trend_class_hub($overview['customers_change']) }}">{{ $overview['customers_change']>0?'▲':'▼' }} {{ fa_num_hub(abs($overview['customers_change'])) }}٪</span>
                @endif
            </div>
        </div>
    </section>

    {{-- منو + محتوا --}}
    <div class="reports-layout">
        <nav class="reports-tabs" id="reportsTabs">
            <button type="button" class="reports-tab-btn {{ $tab==='overview'?'is-active':'' }}" data-tab="overview" style="--tab-color:#10b981;"><span class="icon">📈</span><span class="label">نمای کلی</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='sales'?'is-active':'' }}" data-tab="sales" style="--tab-color:#3b82f6;"><span class="icon">💰</span><span class="label">گزارش فروش</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='rfm'?'is-active':'' }}" data-tab="rfm" style="--tab-color:#8b5cf6;"><span class="icon">👥</span><span class="label">گروه‌بندی مشتریان</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='clv'?'is-active':'' }}" data-tab="clv" style="--tab-color:#ec4899;"><span class="icon">💎</span><span class="label">ارزش عمر مشتری</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='recovery'?'is-active':'' }}" data-tab="recovery" style="--tab-color:#10b981;"><span class="icon">🛟</span><span class="label">درآمد بازگشتی</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='top'?'is-active':'' }}" data-tab="top" style="--tab-color:#f59e0b;"><span class="icon">🏆</span><span class="label">پرفروش‌ترین‌ها</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='channels'?'is-active':'' }}" data-tab="channels" style="--tab-color:#0ea5e9;"><span class="icon">🎯</span><span class="label">کانال‌های فروش</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='payments'?'is-active':'' }}" data-tab="payments" style="--tab-color:#22c55e;"><span class="icon">💳</span><span class="label">پرداخت‌ها</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='support'?'is-active':'' }}" data-tab="support" style="--tab-color:#ef4444;"><span class="icon">🎧</span><span class="label">عملکرد پشتیبانی</span></button>
            <button type="button" class="reports-tab-btn {{ $tab==='export'?'is-active':'' }}" data-tab="export" style="--tab-color:#14b8a6;"><span class="icon">📤</span><span class="label">خروجی و ارسال</span></button>
        </nav>

        <div class="reports-content">

            {{-- تب ۱: نمای کلی --}}
            <section class="reports-panel {{ $tab==='overview'?'is-active':'' }}" data-panel="overview">
                <div class="report-card">
                    <header>
                        <div>
                            <span class="card-chip">📈 روند فروش</span>
                            <h3>روند فروش در {{ $rangeLabel }}</h3>
                            <p>هر نقطه یک روز یا هفته یا ماه است. این نمودار به شما می‌گوید چه زمانی اوج فروش بوده و کمپین را کی بزنید.</p>
                        </div>
                        <div class="export-actions"><a class="btn btn-ghost" href="{{ url('/app/reports/export?tab=overview&range='.$range) }}">خروجی اکسل</a></div>
                    </header>
                    <div class="chart-container is-large"><canvas id="salesTrendChart"></canvas></div>
                </div>

                <div class="hub-stat-grid">
                    <div class="hub-stat-card">
                        <div class="hub-stat-icon" style="background:rgba(16,185,129,.12);color:#047857;">💰</div>
                        <div class="hub-stat-title">میانگین هر سفارش</div>
                        <div class="hub-stat-value">{{ fa_money_hub($overview['avg_order'] ?? 0) }} <span>تومان</span></div>
                    </div>
                    <div class="hub-stat-card">
                        <div class="hub-stat-icon" style="background:rgba(59,130,246,.12);color:#1d4ed8;">🛒</div>
                        <div class="hub-stat-title">سفارش‌های بازه</div>
                        <div class="hub-stat-value">{{ fa_num_hub($overview['order_count'] ?? 0) }} <span>سفارش</span></div>
                    </div>
                    <div class="hub-stat-card">
                        <div class="hub-stat-icon" style="background:rgba(139,92,246,.12);color:#7c3aed;">👤</div>
                        <div class="hub-stat-title">مشتری فعال</div>
                        <div class="hub-stat-value">{{ fa_num_hub($overview['active_customers'] ?? 0) }} <span>نفر</span></div>
                    </div>
                    <div class="hub-stat-card">
                        <div class="hub-stat-icon" style="background:rgba(245,158,11,.12);color:#b45309;">✨</div>
                        <div class="hub-stat-title">مشتری جدید</div>
                        <div class="hub-stat-value">{{ fa_num_hub($overview['new_customers'] ?? 0) }} <span>نفر</span></div>
                    </div>
                </div>

                @if(!empty($sales['by_status']))
                <div class="report-card">
                    <header><div><span class="card-chip">🎯 وضعیت سفارش‌ها</span><h3>سفارش‌ها در چه وضعیتی هستند؟</h3><p>ببینید چقدر تکمیل شده، چقدر در انتظار پرداخت است و کجا گیر دارد.</p></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead><tr><th>وضعیت</th><th>تعداد</th><th>مبلغ</th><th>سهم</th></tr></thead>
                            <tbody>
                                @php $sumStatus = array_sum(array_column($sales['by_status'],'sum')) ?: 1; @endphp
                                @foreach($sales['by_status'] as $st)
                                    @php $pct = round($st['sum']/$sumStatus*100); @endphp
                                    <tr><td><b>{{ $st['status'] }}</b></td><td class="num">{{ fa_num_hub($st['count']) }}</td><td class="num">{{ fa_money_hub($st['sum']) }} تومان</td><td><div class="progress-bar"><div class="progress-track"><div class="progress-fill" style="width:{{ $pct }}%"></div></div><b>{{ fa_num_hub($pct) }}٪</b></div></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </section>

            {{-- تب ۲: گزارش فروش --}}
            <section class="reports-panel {{ $tab==='sales'?'is-active':'' }}" data-panel="sales">
                <div class="report-grid-2">
                    <div class="report-card">
                        <header><div><span class="card-chip">🕐 ساعت‌های طلایی</span><h3>بهترین ساعت فروش</h3><p>بفهمید مشتری‌ها چه ساعتی بیشتر خرید می‌کنند تا پیام و پیشنهاد را همان موقع بفرستید.</p></div></header>
                        <div class="chart-container is-small"><canvas id="salesByHourChart"></canvas></div>
                    </div>
                    <div class="report-card">
                        <header><div><span class="card-chip">📅 روزهای طلایی</span><h3>بهترین روز هفته</h3><p>شنبه تا جمعه، کدام روز بیشتر می‌فروشید؟</p></div></header>
                        <div class="chart-container is-small"><canvas id="salesByWeekdayChart"></canvas></div>
                    </div>
                </div>

                @if(!empty($sales['by_source']))
                <div class="report-card">
                    <header><div><span class="card-chip">🎯 منبع فروش</span><h3>فروش از کجا آمده؟</h3><p>فروشگاه، حضوری، تلگرام، تلفنی و ...</p></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead><tr><th>منبع</th><th>تعداد</th><th>مبلغ</th><th>سهم</th></tr></thead>
                            <tbody>
                                @php $sumSrc = array_sum(array_column($sales['by_source'],'sum')) ?: 1; @endphp
                                @foreach($sales['by_source'] as $src)
                                    @php $pct = round($src['sum']/$sumSrc*100); @endphp
                                    <tr><td><b>{{ $src['source'] }}</b></td><td class="num">{{ fa_num_hub($src['count']) }}</td><td class="num">{{ fa_money_hub($src['sum']) }}</td><td><div class="progress-bar"><div class="progress-track"><div class="progress-fill" style="width:{{ $pct }}%"></div></div><b>{{ fa_num_hub($pct) }}٪</b></div></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </section>

            {{-- تب ۳: گروه‌بندی هوشمند --}}
            <section class="reports-panel {{ $tab==='rfm'?'is-active':'' }}" data-panel="rfm">
                <div class="report-card">
                    <header><div><span class="card-chip">🧠 دسته‌بندی هوشمند</span><h3>تحلیل رفتار خرید - تازگی، تعداد، مبلغ</h3><p>مشتری‌ها بر اساس رفتار خریدشان به ۶ گروه تقسیم شدند. هر گروه راهکار خودش را دارد و پرونده کامل خودش را دارد.</p></div></header>
                    <div class="report-grid-3">
                        @foreach($rfm['segments'] ?? [] as $seg)
                            <div class="info-card is-accent" style="--info-color:{{ $seg['color'] }};">
                                <div class="title">{{ $seg['label'] }} <span class="report-badge" style="background:{{ $seg['color'] }}22;color:{{ $seg['color'] }};">{{ fa_num_hub($seg['count']) }} نفر</span></div>
                                <div class="desc">{{ $seg['desc'] }}</div>
                                @if(($rfm['total'] ?? 0)>0)
                                    <div class="progress-bar" style="margin-top:.35rem"><div class="progress-track"><div class="progress-fill" style="width:{{ round($seg['count']/($rfm['total']?:1)*100) }}%;background:{{ $seg['color'] }}"></div></div><b>{{ fa_num_hub(round($seg['count']/($rfm['total']?:1)*100)) }}٪</b></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="report-card">
                    <header><div><span class="card-chip">📖 پیشنهاد اقدام برای هر گروه</span><h3>الان چه کار کنیم؟</h3></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead><tr><th>دسته</th><th>ویژگی</th><th>پیشنهاد اقدام فوری</th></tr></thead>
                            <tbody>
                                <tr><td><span class="report-badge is-loyal">قهرمانان</span></td><td>اخیراً و زیاد خرید کرده‌اند</td><td>محصول جدید اول به آن‌ها بده، هدیه ویژه و دسترسی زودهنگام</td></tr>
                                <tr><td><span class="report-badge is-new">وفاداران</span></td><td>خرید تکراری دارند</td><td>برنامه معرفی دوستان، پاداش وفاداری، عضویت طلایی</td></tr>
                                <tr><td><span class="report-badge is-new">مستعد</span></td><td>اخیراً خرید کرده‌اند</td><td>محصول مکمل پیشنهاد بده، کوپن خرید بعدی</td></tr>
                                <tr><td><span class="report-badge is-new">جدید</span></td><td>اولین خرید</td><td>۳ پیام خوشامد در ۱۴ روز، معرفی باشگاه، آموزش استفاده</td></tr>
                                <tr><td><span class="report-badge is-risk">در معرض خطر</span></td><td>مدتی نیستند</td><td>یادآوری، کد بازگشت، بپرس چرا نیامده</td></tr>
                                <tr><td><span class="report-badge is-lost">از دست رفته</span></td><td>خیلی وقت نیستند</td><td>تخفیف بزرگ محدود، تماس مستقیم برای احیا</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                @if(!empty($rfm['top_customers']))
                <div class="report-card">
                    <header><div><span class="card-chip">⭐ ۵۰ مشتری برتر بر اساس رفتار خرید</span><h3>ارزشمندترین‌ها در این بازه</h3></div><div class="export-actions"><a class="btn" href="{{ url('/app/reports/modern-export?tab=rfm&range='.$range) }}">خروجی اکسل</a></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>گروه</th><th>تعداد خرید</th><th>تازگی</th><th>مبلغ</th></tr></thead>
                            <tbody>
                                @foreach($rfm['top_customers'] as $i=>$c)
                                <tr><td class="rank {{ $i<3?'top-'.($i+1):'' }}">{{ fa_num_hub($i+1) }}</td><td><b>{{ $c['name'] }}</b></td><td class="num">{{ fa_num_hub($c['phone']) }}</td><td><span class="report-badge" style="background:{{ $c['color'] }}22;color:{{ $c['color'] }};">{{ $c['segment'] }}</span></td><td class="num">{{ fa_num_hub($c['frequency']) }}</td><td class="num">{{ fa_num_hub($c['recency']) }} روز پیش</td><td class="num">{{ fa_money_hub($c['monetary']) }} تومان</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </section>

            {{-- تب ۴: ارزش عمر پیشرفته با پیش‌بینی ریزش --}}
            <section class="reports-panel {{ $tab==='clv'?'is-active':'' }}" data-panel="clv">
                <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));">
                    <div class="kpi-tile" style="--kpi-color:#ec4899;"><span>میانگین CLV ۱۲ ماه آینده</span><b>{{ fa_money_hub($clv['avg_clv_12m'] ?? $clv['avg_clv'] ?? 0) }}</b><small>تومان پیش‌بینی</small></div>
                    <div class="kpi-tile" style="--kpi-color:#f59e0b;"><span>میانگین ریسک ریزش</span><b>{{ fa_num_hub($clv['avg_churn_probability'] ?? 0) }}٪</b><small>کل مشتریان</small></div>
                    <div class="kpi-tile" style="--kpi-color:#ef4444;"><span>در معرض خطر</span><b>{{ fa_num_hub($clv['total_at_risk'] ?? 0) }}</b><small>نیاز به بازگشت</small></div>
                    <div class="kpi-tile" style="--kpi-color:#991b1b;"><span>از دست رفته</span><b>{{ fa_num_hub($clv['total_churned'] ?? 0) }}</b><small>بیش از ۶۶٪ ریسک</small></div>
                    <div class="kpi-tile" style="--kpi-color:#10b981;"><span>درآمد پیش‌بینی ۱۲ ماه</span><b>{{ fa_money_hub($clv['total_predicted_revenue_12m'] ?? 0) }}</b><small>تومان</small></div>
                    <div class="kpi-tile" style="--kpi-color:#3b82f6;"><span>کل مشتریان تحلیل شده</span><b>{{ fa_num_hub($clv['total_customers'] ?? count($clv['top_clv'] ?? [])) }}</b><small>با سابقه خرید</small></div>
                </div>

                <div class="report-grid-2">
                    <div class="report-card">
                        <header><div><span class="card-chip">📊 توزیع ریسک ریزش</span><h3>مشتریان چقدر در خطر ریزش هستند؟</h3><p>هرچه ریسک بالاتر، زودتر باید کمپین بازگشت بفرستید</p></div></header>
                        <div class="chart-container is-small"><canvas id="churnDistributionChart"></canvas></div>
                        <div class="report-table-wrap" style="margin-top:1rem">
                            <table class="report-table">
                                <thead><tr><th>سطح ریسک</th><th>تعداد</th><th>توضیح</th></tr></thead>
                                <tbody>
                                    @php $cd = $clv['churn_distribution'] ?? []; @endphp
                                    <tr><td><span class="report-badge" style="background:#10b98122;color:#047857;">سالم</span></td><td class="num">{{ fa_num_hub($cd['healthy'] ?? 0) }}</td><td>ریسک زیر ۲۰٪ - وفادار</td></tr>
                                    <tr><td><span class="report-badge" style="background:#3b82f622;color:#1d4ed8;">پایدار</span></td><td class="num">{{ fa_num_hub($cd['stable'] ?? 0) }}</td><td>ریسک ۲۱ تا ۴۰٪</td></tr>
                                    <tr><td><span class="report-badge" style="background:#f59e0b22;color:#b45309;">در خطر</span></td><td class="num">{{ fa_num_hub($cd['at_risk'] ?? 0) }}</td><td>ریسک ۴۱ تا ۶۵٪ - نیاز به یادآوری</td></tr>
                                    <tr><td><span class="report-badge" style="background:#ef444422;color:#b91c1c;">در حال ریزش</span></td><td class="num">{{ fa_num_hub($cd['churning'] ?? 0) }}</td><td>ریسک ۶۶ تا ۸۵٪ - کمپین فوری</td></tr>
                                    <tr><td><span class="report-badge" style="background:#991b1b22;color:#991b1b;">از دست رفته</span></td><td class="num">{{ fa_num_hub($cd['lost'] ?? 0) }}</td><td>ریسک بالای ۸۵٪ - آخرین شانس</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="report-card">
                        <header><div><span class="card-chip">🎯 بخش‌بندی خودکار بر اساس CLV</span><h3>بخش‌های طلایی سیستم</h3><p>سیستم هر روز مشتریان را خودکار در این بخش‌ها قرار می‌دهد</p></div><div class="export-actions"><a class="btn btn-ghost" href="{{ url('/app/segments') }}">مدیریت بخش‌ها</a></div></header>
                        <div class="report-table-wrap">
                            <table class="report-table">
                                <thead><tr><th>بخش</th><th>تعداد</th><th>سهم</th></tr></thead>
                                <tbody>
                                    @php 
                                        $sd = $clv['segment_distribution'] ?? [];
                                        $totalSeg = array_sum($sd) ?: 1;
                                    @endphp
                                    @forelse($sd as $label => $cnt)
                                        @php $pct = round($cnt/$totalSeg*100); @endphp
                                        <tr><td><b>{{ $label }}</b></td><td class="num">{{ fa_num_hub($cnt) }}</td><td><div class="progress-bar"><div class="progress-track"><div class="progress-fill" style="width:{{ $pct }}%"></div></div><b>{{ fa_num_hub($pct) }}٪</b></div></td></tr>
                                    @empty
                                        <tr><td colspan="3"><div class="reports-empty"><h3>هنوز بخش‌بندی نشده</h3><p>دستور customers:auto-segment را اجرا کنید</p></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:1rem; display:flex; gap:.5rem; flex-wrap:wrap;">
                            <form method="post" action="{{ url('/app/segments/auto') }}" onsubmit="return confirm('بخش‌بندی خودکار اجرا شود؟')" style="display:inline;">
                                @csrf
                                <button class="btn">🔄 اجرای بخش‌بندی خودکار</button>
                            </form>
                            <small style="color:var(--mut); line-height:2;">این کار هر شب خودکار هم اجرا می‌شود و بخش‌ها را به‌روز می‌کند</small>
                        </div>
                    </div>
                </div>

                <div class="report-grid-2">
                    <div class="report-card">
                        <header><div><span class="card-chip">💎 ارزش عمر پیش‌بینی ۱۲ ماه - ۵۰ نفر برتر</span><h3>پردرآمدترین مشتریان آینده</h3><p>این‌ها بیشترین سود ۱۲ ماه آینده را خواهند داشت - بیشترین تمرکز نگهداری</p></div><div class="export-actions"><a class="btn" href="{{ url('/app/reports/modern-export?tab=clv&range='.$range) }}">دانلود اکسل</a></div></header>
                        <div class="report-table-wrap">
                            <table class="report-table">
                                <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>کل خرید</th><th>میانگین سبد</th><th>فاصله خرید</th><th>ریسک ریزش</th><th>CLV ۱۲ ماه</th></tr></thead>
                                <tbody>
                                    @forelse(($clv['top_clv'] ?? []) as $i=>$c)
                                        <tr>
                                            <td class="rank {{ $i<3?'top-'.($i+1):'' }}">{{ fa_num_hub($i+1) }}</td>
                                            <td><b>{{ $c['full_name'] ?? $c['name'] ?? '—' }}</b></td>
                                            <td class="num">{{ fa_num_hub($c['phone'] ?? '---') }}</td>
                                            <td class="num">{{ fa_money_hub($c['total_spent'] ?? $c['value'] ?? 0) }}</td>
                                            <td class="num">{{ fa_money_hub($c['avg_order'] ?? 0) }}</td>
                                            <td class="num">{{ fa_num_hub($c['avg_interval_days'] ?? 0) }} روز</td>
                                            <td><span class="report-badge" style="background:{{ $c['churn_color'] ?? '#10b981' }}22;color:{{ $c['churn_color'] ?? '#10b981' }};">{{ fa_num_hub($c['churn_probability'] ?? 0) }}٪ {{ $c['churn_label'] ?? '' }}</span></td>
                                            <td class="num"><b>{{ fa_money_hub($c['predicted_clv_12m'] ?? $c['value'] ?? 0) }} تومان</b></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8"><div class="reports-empty"><h3>هنوز مشتری با خرید ثبت نشده</h3><p>بعد از ثبت اولین سفارش، اینجا پر می‌شود.</p></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="report-card">
                        <header><div><span class="card-chip">⚠️ ارزشمند در معرض ریزش - ۲۰ نفر اول</span><h3>طلایی‌هایی که دارند می‌روند!</h3><p>CLV بالا اما ریسک ریزش ۴۰ تا ۸۵٪ - کمپین بازگشت فوری بفرستید</p></div></header>
                        <div class="report-table-wrap">
                            <table class="report-table">
                                <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>آخرین خرید</th><th>ریسک</th><th>CLV پیش‌بینی</th><th>اقدام</th></tr></thead>
                                <tbody>
                                    @forelse(($clv['top_churn_risk'] ?? []) as $i=>$c)
                                        <tr>
                                            <td class="rank">{{ fa_num_hub($i+1) }}</td>
                                            <td><b>{{ $c['full_name'] ?? $c['name'] ?? '—' }}</b></td>
                                            <td class="num">{{ fa_num_hub($c['phone'] ?? '---') }}</td>
                                            <td class="num">{{ fa_num_hub($c['recency_days'] ?? 0) }} روز پیش</td>
                                            <td><span class="report-badge" style="background:{{ $c['churn_color'] ?? '#ef4444' }}22;color:{{ $c['churn_color'] ?? '#ef4444' }};">{{ fa_num_hub($c['churn_probability'] ?? 0) }}٪</span></td>
                                            <td class="num">{{ fa_money_hub($c['predicted_clv_12m'] ?? 0) }}</td>
                                            <td><a href="{{ url('/app/customers/' . ($c['customer_id'] ?? $c['id'] ?? 0)) }}" class="btn btn-ghost" style="padding:.2rem .5rem;font-size:.75rem">کمپین بازگشت</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7"><div class="reports-empty"><h3>مشتری در معرض خطر یافت نشد</h3><p>عالی! فعلاً مشتری ارزشمند در خطر ریزش ندارید</p></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            {{-- تب جدید: درآمد بازگشتی - ماهانه شمسی --}}
            <section class="reports-panel {{ $tab==='recovery'?'is-active':'' }}" data-panel="recovery">
                <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));">
                    <div class="kpi-tile" style="--kpi-color:#10b981;"><span>بازگشته‌های ۳۰ روز اخیر</span><b>{{ fa_num_hub($recovery['recovered_30d'] ?? 0) }}</b><small>نفر - نرخ بازگشت {{ fa_num_hub($recovery['recovery_rate'] ?? 0) }}٪</small></div>
                    <div class="kpi-tile" style="--kpi-color:#059669;"><span>درآمد بازگشتی ۳۰ روزه</span><b>{{ fa_money_hub($recovery['recovered_revenue_30d'] ?? 0) }}</b><small>تومان - واقعی</small></div>
                    <div class="kpi-tile" style="--kpi-color:#0ea5e9;"><span>بازگشته‌های ۷ روز اخیر</span><b>{{ fa_num_hub($recovery['recovered_7d'] ?? 0) }}</b><small>اقدام فوری موفق</small></div>
                    <div class="kpi-tile" style="--kpi-color:#f59e0b;"><span>کل بازگشت ۱۲ ماه</span><b>{{ fa_money_hub($recovery['total_recovered_12m'] ?? 0) }}</b><small>جمع سال - شمسی</small></div>
                    <div class="kpi-tile" style="--kpi-color:#8b5cf6;"><span>میانگین ماهانه بازگشت و حفظ</span><b>{{ fa_money_hub($recovery['avg_monthly_recovered'] ?? 0) }}</b><small>تومان در ماه</small></div>
                    <div class="kpi-tile" style="--kpi-color:#ec4899;"><span>در معرض خطر فعلی</span><b>{{ fa_num_hub($recovery['total_at_risk'] ?? 0) }}</b><small>پتانسیل بازگشت</small></div>
                </div>

                <div class="report-grid-2">
                    <div class="report-card">
                        <header><div><span class="card-chip">📈 نمودار ماهانه شمسی - درآمد بازگشتی</span><h3>چه ماهی بیشتر مشتری برگشته؟</h3><p>هر ستون یک ماه شمسی - بازگشت بعد از ۴۵ روز غیبت = بازگشتی</p></div></header>
                        <div class="chart-container"><canvas id="recoveryMonthlyChart"></canvas></div>
                        <div class="report-table-wrap" style="margin-top:1rem">
                            <table class="report-table">
                                <thead><tr><th>ماه شمسی</th><th>تعداد بازگشته</th><th>درآمد بازگشتی</th></tr></thead>
                                <tbody>
                                    @forelse(array_map(null, $recovery['monthly_labels'] ?? [], $recovery['monthly_count'] ?? [], $recovery['monthly_recovered'] ?? []) as $row)
                                        <tr><td><b>{{ $row[0] }}</b></td><td class="num">{{ fa_num_hub($row[1] ?? 0) }} نفر</td><td class="num">{{ fa_money_hub($row[2] ?? 0) }} تومان</td></tr>
                                    @empty
                                        <tr><td colspan="3"><div class="reports-empty"><h3>هنوز بازگشتی ثبت نشده</h3></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="report-card">
                        <header><div><span class="card-chip">🎉 بازگشته‌ها در بازه انتخابی {{ $range==='7'?'۷ روزه':($range==='90'?'۹۰ روزه':($range==='365'?'یک ساله':($range==='all'?'کل تاریخ':'۳۰ روزه'))) }}</span><h3>چه کسانی در این بازه برگشتند؟</h3><p>فهرست مشتریانی که بعد از ۴۵ روز غیبت دوباره خرید کرده‌اند - امتیاز دو برابر گرفته‌اند</p></div><div class="export-actions"><a href="{{ url('/app/retention') }}" class="btn">🛟 مرکز بازگشت و حفظ</a></div></header>
                        <div class="report-table-wrap">
                            <table class="report-table">
                                <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>غیبت قبل بازگشت</th><th>مبلغ بازگشت</th><th>تاریخ شمسی</th><th>پرونده</th></tr></thead>
                                <tbody>
                                    @forelse(($recovery['recovered_in_range'] ?? []) as $i=>$c)
                                        <tr>
                                            <td class="rank">{{ fa_num_hub($i+1) }}</td>
                                            <td><b>{{ $c['full_name'] ?? '—' }}</b></td>
                                            <td class="num">{{ fa_num_hub($c['phone'] ?? '---') }}</td>
                                            <td class="num">{{ fa_num_hub($c['gap_days'] ?? 0) }} روز</td>
                                            <td class="num">{{ fa_money_hub($c['total'] ?? 0) }} تومان</td>
                                            <td class="num">{{ $c['date_fa'] ?? '---' }}</td>
                                            <td><a href="{{ url('/app/customers/'.($c['customer_id'] ?? 0)) }}" class="btn btn-ghost" style="padding:.2rem .5rem;font-size:.75rem">👁️ پرونده</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7"><div class="reports-empty"><h3>در این بازه بازگشتی نبوده</h3><p>بازه را بزرگ‌تر کنید (۹۰ روزه یا کل تاریخ) یا از تب ۳۰ روزه مرکز بازگشت و حفظ را ببینید.</p></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:1rem; padding:.8rem; border-radius:.8rem; background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(14,165,233,.06)); border:1px solid rgba(16,185,129,.18);">
                            <b>✨ اتوماسیون امتیاز دو برابر فعال است</b>
                            <p style="margin:.3rem 0 0; font-size:.84rem; opacity:.8;">هر مشتری که در این لیست می‌بینید، به صورت خودکار امتیاز دو برابر گرفته و یادداشت بازگشت برای تیم فروش ثبت شده است. نرخ بازگشت فعلی {{ fa_num_hub($recovery['recovery_rate'] ?? 0) }}٪ است.</p>
                        </div>
                    </div>
                </div>

                <div class="report-card" style="margin-top:1rem">
                    <header><div><span class="card-chip">💎 بازگشته‌های برتر - هدیه دو برابر گرفته‌اند</span><h3>چه کسانی اخیراً برگشته‌اند و امتیاز دو برابر گرفته‌اند؟</h3></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>غیبت</th><th>مبلغ</th><th>تاریخ بازگشت شمسی</th></tr></thead>
                            <tbody>
                                @forelse(($recovery['recovered_list'] ?? []) as $i=>$c)
                                    <tr>
                                        <td class="rank">{{ fa_num_hub($i+1) }}</td>
                                        <td><b>{{ $c['full_name'] ?? '---' }}</b></td>
                                        <td>{{ fa_num_hub($c['phone'] ?? '---') }}</td>
                                        <td class="num">{{ fa_num_hub($c['gap_days'] ?? 0) }} روز</td>
                                        <td class="num">{{ fa_money_hub($c['order_total'] ?? 0) }}</td>
                                        <td class="num">{{ $c['returned_at_fa'] ?? '---' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="reports-empty"><h3>هنوز بازگشتی در ۳۰ روز اخیر نبوده</h3></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- تب ۵: پرفروش‌ترین‌ها --}}
            <section class="reports-panel {{ $tab==='top'?'is-active':'' }}" data-panel="top">
                <div class="report-grid-2">
                    <div class="report-card">
                        <header><div><span class="card-chip">📦 ۲۰ محصول پرفروش</span><h3>چه محصولی بیشتر پول آورده؟</h3></div><div class="export-actions"><a class="btn btn-ghost" href="{{ url('/app/reports/modern-export?tab=products&range='.$range) }}">اکسل</a></div></header>
                        <div class="report-table-wrap">
                            <table class="report-table"><thead><tr><th>#</th><th>محصول</th><th>تعداد</th><th>فروش</th></tr></thead>
                                <tbody>
                                    @forelse($topProducts as $i=>$p)
                                        <tr><td class="rank {{ $i<3?'top-'.($i+1):'' }}">{{ fa_num_hub($i+1) }}</td><td><b>{{ $p->product_name ?? 'نامشخص' }}</b></td><td class="num">{{ fa_num_hub($p->qty) }}</td><td class="num">{{ fa_money_hub($p->total) }}</td></tr>
                                    @empty
                                        <tr><td colspan="4"><div class="reports-empty"><h3>داده‌ای نیست</h3></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="report-card">
                        <header><div><span class="card-chip">📁 دسته‌های پرفروش</span><h3>کدام دسته بیشتر فروخته؟</h3></div></header>
                        <div class="report-table-wrap">
                            <table class="report-table"><thead><tr><th>#</th><th>دسته</th><th>تعداد</th><th>فروش</th></tr></thead>
                                <tbody>
                                    @forelse($topCategories as $i=>$c)
                                        <tr><td class="rank {{ $i<3?'top-'.($i+1):'' }}">{{ fa_num_hub($i+1) }}</td><td><b>{{ $c->cat_name ?? $c->name ?? 'بدون دسته' }}</b></td><td class="num">{{ fa_num_hub($c->qty) }}</td><td class="num">{{ fa_money_hub($c->total) }}</td></tr>
                                    @empty
                                        <tr><td colspan="4"><div class="reports-empty"><h3>داده‌ای نیست</h3></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <header><div><span class="card-chip">🌟 ۲۰ مشتری برتر بازه</span><h3>پرخرج‌ترین‌ها در {{ $rangeLabel }}</h3></div><div class="export-actions"><a class="btn" href="{{ url('/app/reports/modern-export?tab=top&range='.$range) }}">دانلود اکسل</a></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table"><thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>سفارش</th><th>خرید</th><th>پرونده</th></tr></thead>
                            <tbody>
                                @forelse($topCustomers as $i=>$c)
                                    <tr><td class="rank {{ $i<3?'top-'.($i+1):'' }}">{{ fa_num_hub($i+1) }}</td><td><b>{{ $c['name'] }}</b></td><td class="num">{{ fa_num_hub($c['phone']) }}</td><td class="num">{{ fa_num_hub($c['orders']) }}</td><td class="num">{{ fa_money_hub($c['spent']) }} تومان</td><td><a href="{{ $c['url'] }}" class="btn btn-ghost" style="padding:.2rem .5rem;font-size:.75rem">پرونده</a></td></tr>
                                @empty
                                    <tr><td colspan="6"><div class="reports-empty"><h3>داده‌ای نیست</h3></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- تب ۶: کانال‌ها --}}
            <section class="reports-panel {{ $tab==='channels'?'is-active':'' }}" data-panel="channels">
                <div class="report-card">
                    <header><div><span class="card-chip">🎯 توزیع فروش بر اساس کانال</span><h3>مشتری از کجا آمده؟</h3><p>هر سفارش یک منبع دارد: فروشگاه اینترنتی، ثبت حضوری، تلگرام، تلفنی و ...</p></div></header>
                    @if(!empty($channels))
                        <div class="chart-container is-small"><canvas id="channelsChart"></canvas></div>
                        <div class="report-table-wrap" style="margin-top:1rem">
                            <table class="report-table"><thead><tr><th>کانال</th><th>تعداد</th><th>فروش</th><th>سهم</th></tr></thead>
                                <tbody>
                                    @php $sumCh = array_sum(array_column($channels,'sales')) ?: 1; @endphp
                                    @foreach($channels as $ch)
                                        @php $pct = round($ch['sales']/$sumCh*100); @endphp
                                        <tr><td><b>{{ $ch['label'] }}</b></td><td class="num">{{ fa_num_hub($ch['count']) }}</td><td class="num">{{ fa_money_hub($ch['sales']) }}</td><td><div class="progress-bar"><div class="progress-track"><div class="progress-fill" style="width:{{ $pct }}%"></div></div><b>{{ fa_num_hub($pct) }}٪</b></div></td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="reports-empty"><h3>داده‌ای نیست</h3><p>سفارش‌های این بازه فاقد منبع هستند. در ثبت سفارش، منبع را مشخص کن.</p></div>
                    @endif
                </div>
            </section>

            {{-- تب ۷: پرداخت --}}
            <section class="reports-panel {{ $tab==='payments'?'is-active':'' }}" data-panel="payments">
                <div class="kpi-grid">
                    <div class="kpi-tile" style="--kpi-color:#22c55e;"><span>مجموع پرداخت</span><b>{{ fa_money_hub($payments['total'] ?? 0) }}</b><small>تومان - {{ $rangeLabel }}</small></div>
                    <div class="kpi-tile" style="--kpi-color:#3b82f6;"><span>تعداد درگاه فعال</span><b>{{ fa_num_hub(count($payments['by_gateway'] ?? [])) }}</b><small>در بازه</small></div>
                    <div class="kpi-tile" style="--kpi-color:#8b5cf6;"><span>کل تراکنش</span><b>{{ fa_num_hub(array_sum(array_column($payments['by_gateway'] ?? [],'count'))) }}</b><small>تراکنش</small></div>
                </div>

                @if(!empty($payments['by_gateway']))
                <div class="report-card">
                    <header><div><span class="card-chip">💳 عملکرد درگاه‌ها</span><h3>کدام درگاه بیشتر پول آورده؟</h3></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table"><thead><tr><th>درگاه</th><th>تعداد</th><th>مبلغ</th><th>سهم</th></tr></thead>
                            <tbody>
                                @php $sumPay = array_sum(array_column($payments['by_gateway'],'sum')) ?: 1; @endphp
                                @foreach($payments['by_gateway'] as $g)
                                    @php $pct = round($g['sum']/$sumPay*100); @endphp
                                    <tr><td><b>{{ $g['gateway'] }}</b></td><td class="num">{{ fa_num_hub($g['count']) }}</td><td class="num">{{ fa_money_hub($g['sum']) }}</td><td><div class="progress-bar"><div class="progress-track"><div class="progress-fill" style="width:{{ $pct }}%"></div></div><b>{{ fa_num_hub($pct) }}٪</b></div></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if(!empty($payments['by_status']))
                <div class="report-card">
                    <header><div><span class="card-chip">📊 وضعیت تراکنش‌ها</span><h3>موفق، ناموفق، در انتظار</h3></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table"><thead><tr><th>وضعیت</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                            <tbody>
                                @foreach($payments['by_status'] as $s)
                                <tr><td>{{ $s['status'] }}</td><td class="num">{{ fa_num_hub($s['count']) }}</td><td class="num">{{ fa_money_hub($s['sum']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </section>

            {{-- تب ۸: پشتیبانی --}}
            <section class="reports-panel {{ $tab==='support'?'is-active':'' }}" data-panel="support">
                <div class="kpi-grid">
                    <div class="kpi-tile" style="--kpi-color:#3b82f6;"><span>کل تیکت</span><b>{{ fa_num_hub($support['total'] ?? 0) }}</b><small>{{ $rangeLabel }}</small></div>
                    <div class="kpi-tile" style="--kpi-color:#ef4444;"><span>تیکت باز</span><b>{{ fa_num_hub($support['open'] ?? 0) }}</b><small>در انتظار پاسخ</small></div>
                    <div class="kpi-tile" style="--kpi-color:#10b981;"><span>حل‌شده</span><b>{{ fa_num_hub($support['closed'] ?? 0) }}</b><small>بسته شده</small></div>
                    <div class="kpi-tile" style="--kpi-color:#f59e0b;"><span>میانگین پاسخ</span><b>{{ fa_num_hub($support['avg_response'] ?? 0) }}</b><small>دقیقه</small></div>
                </div>

                @if(!empty($support['by_dept']))
                <div class="report-card">
                    <header><div><span class="card-chip">🏢 تفکیک تیکت بر اساس بخش</span><h3>کدام بخش بیشتر تیکت دارد؟</h3></div></header>
                    <div class="report-table-wrap">
                        <table class="report-table"><thead><tr><th>بخش</th><th>تعداد</th><th>سهم</th></tr></thead>
                            <tbody>
                                @php $totDept = array_sum(array_column($support['by_dept'],'count')) ?: 1; @endphp
                                @foreach($support['by_dept'] as $d)
                                    @php $pct = round($d['count']/$totDept*100); @endphp
                                    <tr><td><b>{{ $d['name'] }}</b></td><td class="num">{{ fa_num_hub($d['count']) }}</td><td><div class="progress-bar"><div class="progress-track"><div class="progress-fill" style="width:{{ $pct }}%"></div></div><b>{{ fa_num_hub($pct) }}٪</b></div></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </section>

            {{-- تب ۹: خروجی --}}
            <section class="reports-panel {{ $tab==='export'?'is-active':'' }}" data-panel="export">
                <div class="report-card">
                    <header><div><span class="card-chip">📤 خروجی و ارسال گزارش</span><h3>دانلود سریع گزارش‌ها به صورت فایل صفحه گسترده</h3><p>فایل‌ها با فرمت سازگار با اکسل و با حروف فارسی هستند، مستقیم در اکسل باز می‌شوند. تاریخ‌ها شمسی هستند.</p></div></header>
                    <div class="report-grid-2">
                        <div class="info-card is-accent" style="--info-color:#10b981;"><div class="title">💰 گزارش مشتریان برتر</div><div class="desc">۵۰۰ مشتری با بالاترین خرید در {{ $rangeLabel }}</div><div class="export-actions" style="margin-top:.5rem"><a class="btn" href="{{ url('/app/reports/modern-export?tab=top&range='.$range) }}">دانلود فایل صفحه گسترده</a></div></div>
                        <div class="info-card is-accent" style="--info-color:#f59e0b;"><div class="title">🏆 گزارش محصولات پرفروش</div><div class="desc">۵۰۰ محصول با بیشترین فروش در {{ $rangeLabel }}</div><div class="export-actions" style="margin-top:.5rem"><a class="btn" href="{{ url('/app/reports/modern-export?tab=products&range='.$range) }}">دانلود فایل صفحه گسترده</a></div></div>
                        <div class="info-card is-accent" style="--info-color:#8b5cf6;"><div class="title">👥 خروجی گروه‌بندی مشتریان بر اساس رفتار خرید</div><div class="desc">۵۰ مشتری برتر بر اساس رفتار خرید در {{ $rangeLabel }} - قابل فهم برای همه</div><div class="export-actions" style="margin-top:.5rem"><a class="btn" href="{{ url('/app/reports/modern-export?tab=rfm&range='.$range) }}">دانلود گروه‌بندی هوشمند</a></div></div>
                        <div class="info-card is-accent" style="--info-color:#ec4899;"><div class="title">💎 خروجی ارزش عمر مشتری</div><div class="desc">۵۰ مشتری با بالاترین ارزش عمر کل سامانه</div><div class="export-actions" style="margin-top:.5rem"><a class="btn" href="{{ url('/app/reports/modern-export?tab=clv&range='.$range) }}">دانلود ارزش عمر</a></div></div>
                    </div>
                    <div class="filter-summary" style="margin-top:1rem">💡 برای ارسال خودکار گزارش روزانه به ایمیل مدیر، در بخش <a href="{{ url('/app/settings?tab=notifications') }}" style="color:var(--acc);font-weight:1000;">تنظیمات ← اعلان‌ها</a> گزینه «گزارش روزانه» را روشن کن.</div>
                </div>
            </section>

        </div>
    </div>
</div>

<script>
var ACTIVE_KEY = 'ayarpro_reports_tab_v2';
function switchTab(name){
    document.querySelectorAll('.reports-tab-btn').forEach(function(b){ b.classList.toggle('is-active', b.dataset.tab===name); });
    document.querySelectorAll('.reports-panel').forEach(function(p){ p.classList.toggle('is-active', p.dataset.panel===name); });
    try{ localStorage.setItem(ACTIVE_KEY,name); }catch(e){}
    try{
        var url=new URL(window.location.href);
        url.searchParams.set('tab',name);
        history.replaceState({},'',url.toString());
    }catch(e){}
    setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 100);
}
function initTabs(){
    document.querySelectorAll('.reports-tab-btn').forEach(function(btn){
        btn.addEventListener('click',function(){ switchTab(btn.dataset.tab); });
    });
    var urlTab=new URLSearchParams(window.location.search).get('tab');
    var savedTab; try{savedTab=localStorage.getItem(ACTIVE_KEY);}catch(e){savedTab=null;}
    var initial=urlTab||savedTab||'{{ $tab }}'||'overview';
    if(!document.querySelector('.reports-tab-btn[data-tab="'+initial+'"]')) initial='overview';
    switchTab(initial);
}
/* تبدیل اعداد انگلیسی به فارسی برای نمودارها */
function toFaDigits(n){
    if(n===null || n===undefined) return '';
    var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return String(n).replace(/\d/g, function(d){ return fa[d]; });
}
// فونت یکسان با سایت اصلی - استعداد و وزیرمتن
var chartFont={family:"'Estedad', 'Vazirmatn', 'Vazirmatn FD', Tahoma, sans-serif",size:12,weight: '600'};
if(window.Chart){
    try{
        Chart.defaults.font.family = "'Estedad', 'Vazirmatn', 'Vazirmatn FD', Tahoma, sans-serif";
        Chart.defaults.font.weight = '600';
    }catch(e){}
}
var chartColors={primary:'#10b981',secondary:'#3b82f6',purple:'#8b5cf6',orange:'#f59e0b',pink:'#ec4899',red:'#ef4444',teal:'#14b8a6'};
function initCharts(){
    var trendEl=document.getElementById('salesTrendChart');
    if(trendEl && window.Chart){
        new Chart(trendEl,{
            type:'line',
            data:{
                labels:{!! json_encode($overview['trend_labels'] ?? []) !!},
                datasets:[{label:'فروش',data:{!! json_encode($overview['trend_data'] ?? []) !!},borderColor:chartColors.primary,backgroundColor:chartColors.primary+'20',borderWidth:2.5,fill:true,tension:.38,pointRadius:3,pointHoverRadius:5}]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{
                    legend:{display:false},
                    tooltip:{callbacks:{label:function(ctx){ return ' فروش: ' + toFaDigits(ctx.parsed.y.toLocaleString()); }}}
                },
                scales:{
                    y:{beginAtZero:true,ticks:{font:chartFont,callback:function(v){ return toFaDigits(v); }}},
                    x:{ticks:{font:chartFont}}
                }
            }
        });
    }
    var hourEl=document.getElementById('salesByHourChart');
    if(hourEl && window.Chart){
        var hourLabels=[]; for(var i=0;i<24;i++) hourLabels.push(toFaDigits(i)+':۰۰');
        new Chart(hourEl,{
            type:'bar',
            data:{labels:hourLabels,datasets:[{label:'تعداد سفارش',data:{!! json_encode($sales['by_hour'] ?? []) !!},backgroundColor:chartColors.secondary,borderRadius:4}]},
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{
                    legend:{display:false},
                    tooltip:{callbacks:{label:function(ctx){ return ' ' + toFaDigits(ctx.parsed.y) + ' سفارش'; }}}
                },
                scales:{
                    y:{beginAtZero:true,ticks:{font:chartFont,callback:function(v){ return toFaDigits(v); }}},
                    x:{ticks:{font:chartFont}}
                }
            }
        });
    }
    var weekEl=document.getElementById('salesByWeekdayChart');
    if(weekEl && window.Chart){
        new Chart(weekEl,{
            type:'bar',
            data:{labels:['شنبه','یک‌شنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه'],datasets:[{label:'تعداد سفارش',data:{!! json_encode(array_values($sales['by_weekday'] ?? [0,0,0,0,0,0,0])) !!},backgroundColor:chartColors.purple,borderRadius:4}]},
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{
                    legend:{display:false},
                    tooltip:{callbacks:{label:function(ctx){ return ' ' + toFaDigits(ctx.parsed.y) + ' سفارش'; }}}
                },
                scales:{
                    y:{beginAtZero:true,ticks:{font:chartFont,callback:function(v){ return toFaDigits(v); }}},
                    x:{ticks:{font:chartFont}}
                }
            }
        });
    }
    var chEl=document.getElementById('channelsChart');
    if(chEl && window.Chart){
        var chLabels={!! json_encode(array_column($channels,'label')) !!};
        var chData={!! json_encode(array_column($channels,'sales')) !!};
        if(chLabels.length>0){
            new Chart(chEl,{
                type:'doughnut',
                data:{labels:chLabels,datasets:[{data:chData,backgroundColor:[chartColors.primary,chartColors.secondary,chartColors.purple,chartColors.orange,chartColors.pink,chartColors.red,chartColors.teal]}]},
                options:{
                    responsive:true,maintainAspectRatio:false,
                    plugins:{
                        legend:{position:'bottom',labels:{font:chartFont}},
                        tooltip:{callbacks:{label:function(ctx){ var l=ctx.label||''; var v=ctx.parsed||0; return ' ' + l + ': ' + toFaDigits(v.toLocaleString()) + ' تومان'; }}}
                    }
                }
            });
        }
    }
    var churnEl=document.getElementById('churnDistributionChart');
    if(churnEl && window.Chart){
        var cd = {!! json_encode($clv['churn_distribution'] ?? []) !!};
        var churnLabels = ['سالم','پایدار','در خطر','در حال ریزش','از دست رفته'];
        var churnData = [cd.healthy||0, cd.stable||0, cd.at_risk||0, cd.churning||0, cd.lost||0];
        var churnColors = ['#10b981','#3b82f6','#f59e0b','#ef4444','#991b1b'];
        new Chart(churnEl,{
            type:'bar',
            data:{labels:churnLabels,datasets:[{label:'تعداد مشتری',data:churnData,backgroundColor:churnColors,borderRadius:6}]},
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{
                    legend:{display:false},
                    tooltip:{callbacks:{label:function(ctx){ return ' ' + toFaDigits(ctx.parsed.y) + ' مشتری'; }}}
                },
                scales:{
                    y:{beginAtZero:true,ticks:{font:chartFont,callback:function(v){ return toFaDigits(v); }}},
                    x:{ticks:{font:chartFont}}
                }
            }
        });
    }
    var recoveryEl=document.getElementById('recoveryMonthlyChart');
    if(recoveryEl && window.Chart){
        var recLabels = {!! json_encode($recovery['monthly_labels'] ?? []) !!};
        var recData = {!! json_encode($recovery['monthly_recovered'] ?? []) !!};
        var recCount = {!! json_encode($recovery['monthly_count'] ?? []) !!};
        new Chart(recoveryEl,{
            type:'bar',
            data:{
                labels: recLabels,
                datasets:[
                    {label:'درآمد بازگشتی (تومان)', data: recData, backgroundColor:'#10b981', borderRadius:6, yAxisID:'y'},
                    {label:'تعداد بازگشته', data: recCount, backgroundColor:'#0ea5e9', borderRadius:6, yAxisID:'y1'}
                ]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                interaction:{mode:'index', intersect:false},
                plugins:{
                    legend:{position:'bottom', labels:{font:chartFont}},
                    tooltip:{
                        callbacks:{
                            label:function(ctx){
                                var lbl = ctx.dataset.label || '';
                                if(lbl.indexOf('درآمد')!==-1){
                                    return ' ' + lbl + ': ' + toFaDigits(ctx.parsed.y.toLocaleString()) + ' تومان';
                                }
                                return ' ' + lbl + ': ' + toFaDigits(ctx.parsed.y) + ' نفر';
                            }
                        }
                    }
                },
                scales:{
                    y:{type:'linear', position:'left', beginAtZero:true, ticks:{font:chartFont, callback:function(v){ return toFaDigits((v/1000).toFixed(0))+'k'; }}, title:{display:true, text:'تومان', font:chartFont}},
                    y1:{type:'linear', position:'right', beginAtZero:true, grid:{drawOnChartArea:false}, ticks:{font:chartFont, callback:function(v){ return toFaDigits(v); }}, title:{display:true, text:'نفر', font:chartFont}},
                    x:{ticks:{font:chartFont, maxRotation:45}}
                }
            }
        });
    }
}
document.addEventListener('DOMContentLoaded',function(){ initTabs(); setTimeout(initCharts, 150); });
</script>
@endsection
