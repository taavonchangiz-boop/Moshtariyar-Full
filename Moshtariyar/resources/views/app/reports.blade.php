@extends('layouts.app')
@section('title','گزارش‌ها - نسخه سازگار قدیمی')
@section('heading','گزارش‌های قدیمی - سازگار')
@section('subtitle','این صفحه برای سازگاری با نسخه‌های قدیم نگه داشته شده - نسخه حرفه‌ای جدید در مرکز مدرن است')

@section('content')
<link rel="stylesheet" href="{{ asset('css/reports-board.css') }}">
<link rel="stylesheet" href="{{ asset('css/reports-analytics.css') }}">
<link rel="stylesheet" href="{{ asset('css/reports-center.css') }}">

@php
    $descriptions = [
        'sales_by_status' => 'سهم هر وضعیت سفارش از تعداد و مبلغ فروش را نشان می‌دهد.',
        'sales_by_source' => 'مشخص می‌کند فروش‌ها از چه منبعی وارد سامانه شده‌اند.',
        'top_customers' => 'مشتریانی را نشان می‌دهد که بیشترین ارزش خرید را داشته‌اند.',
        'customers_by_src' => 'نمایش می‌دهد مشتریان از چه راهی وارد سامانه شده‌اند.',
        'monthly_sales' => 'روند فروش ماهانه و رشد یا افت درآمد را بررسی می‌کند.',
        'payments_by_gateway' => 'عملکرد درگاه‌های پرداخت را از نظر تعداد و مبلغ نمایش می‌دهد.',
        'tax_invoices_status' => 'وضعیت فاکتورهای رسمی، مبلغ، مالیات و قابل پرداخت را نشان می‌دهد.',
        'loyalty_from_sales' => 'اثر فروش روی امتیاز، کیف پول و برگشت پاداش‌ها را بررسی می‌کند.',
        'referral_sales' => 'نشان می‌دهد معرفی دوستان چه مقدار فروش ایجاد کرده است.',
        'orders_loyalty_effect' => 'سفارش‌هایی را نشان می‌دهد که بیشترین اثر را روی باشگاه مشتریان داشته‌اند.',
    ];
    $reportGroups = collect([
        'sales' => ['label' => 'فروش و درآمد','color' => '#10b981','items' => ['sales_by_status','sales_by_source','monthly_sales','top_customers']],
        'customers' => ['label' => 'مشتریان و جذب','color' => '#3b82f6','items' => ['customers_by_src','referral_sales']],
        'finance' => ['label' => 'مالی و فاکتور','color' => '#f59e0b','items' => ['payments_by_gateway','tax_invoices_status']],
        'club' => ['label' => 'باشگاه مشتریان','color' => '#8b5cf6','items' => ['loyalty_from_sales','orders_loyalty_effect']],
    ]);
    $moneyKeys = ['مبلغ','ارزش','مبلغ کل','میانگین سفارش','جمع مبلغ','جمع مالیات','جمع قابل پرداخت','فروش ناشی از معرفی','کیف پول داده‌شده','کیف پول برگشتی','مقدار'];
    $rowCount = count($rows ?? []);
    $moneyTotal = 0;
    foreach(($rows ?? []) as $row){ foreach($row as $k=>$v){ if(is_numeric($v) && in_array($k,$moneyKeys,true)) $moneyTotal += (float)$v; } }
@endphp

<div class="reports-page">

    @if(empty($is_old_mode))
        <section class="reports-hero">
            <div>
                <span class="reports-eyebrow">نسخه جدید حرفه‌ای</span>
                <h2>مرکز گزارشات جدید و حرفه‌ای آماده است</h2>
                <p>شما در حالت سازگاری قدیمی هستید. نسخه جدید با ۹ تب حرفه‌ای، نمودارهای تعاملی، گروه‌بندی هوشمند مشتریان بر اساس رفتار خرید، ارزش عمر مشتری و خروجی صفحه گسترده فارسی در مرکز مدرن قرار دارد.</p>
                <div class="reports-actions">
                    <a class="btn" href="{{ url('/app/reports/hub') }}">رفتن به مرکز مدرن حرفه‌ای</a>
                    <a class="btn btn-ghost" href="{{ url('/app/reports?tab=overview') }}">نمای کلی - نسخه جدید</a>
                </div>
            </div>
        </section>
    @endif

    <section class="reports-hero">
        <div class="reports-hero-main">
            <div class="reports-eyebrow">اتاق تحلیل مدیریتی - نسخه سازگار قدیمی</div>
            <h2>گزارش‌های قابل اقدام برای تصمیم‌های سریع و دقیق</h2>
            <p>این نسخه ۱۰ گزارش آماده دارد و همچنان کار می‌کند. نسخه جدید با ۹ تب حرفه‌ای و نمودارهای تعاملی در مرکز جدید قرار دارد.</p>
        </div>
        <div class="reports-hero-metrics">
            <div class="reports-metric-card"><span>گزارش‌های آماده</span><strong>@fa(count($reports))</strong></div>
            <div class="reports-metric-card"><span>ردیف فعلی</span><strong>@fa($rowCount)</strong></div>
            <div class="reports-metric-card"><span>جمع مبلغ‌ها</span><strong>@money($moneyTotal)</strong><small>@unit</small></div>
        </div>
    </section>

    <section class="reports-toolbar">
        <form method="get" class="reports-filter-form" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end">
            <div class="reports-filter-field reports-type-field">
                <label>نوع گزارش</label>
                <select name="type">
                    @foreach($reports as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="reports-filter-field">
                <label>از تاریخ شمسی</label>
                <input name="from" class="jdate" value="{{ $from }}" placeholder="انتخاب تاریخ">
            </div>
            <div class="reports-filter-field">
                <label>تا تاریخ شمسی</label>
                <input name="to" class="jdate" value="{{ $to }}" placeholder="انتخاب تاریخ">
            </div>
            <button class="btn reports-filter-button">اعمال فیلتر</button>
            <a class="btn btn-ghost reports-export-button" href="{{ url('/app/reports/export?type='.$type.'&from='.urlencode($from).'&to='.urlencode($to)) }}">دریافت خروجی</a>
            <a class="btn" href="{{ url('/app/reports/hub') }}">نسخه حرفه‌ای جدید</a>
        </form>
    </section>

    <section class="reports-catalog">
        @foreach($reportGroups as $group)
            <div class="reports-group" style="--report-group-color: {{ $group['color'] }};">
                <header><span></span><h3>{{ $group['label'] }}</h3></header>
                <div class="reports-group-list">
                    @foreach($group['items'] as $reportKey)
                        @if(isset($reports[$reportKey]))
                            <a href="{{ url('/app/reports?type='.$reportKey.'&from='.urlencode($from).'&to='.urlencode($to)) }}" class="report-option {{ $type === $reportKey ? 'active' : '' }}">
                                <b>{{ $reports[$reportKey] }}</b>
                                <small>{{ $descriptions[$reportKey] ?? 'گزارش مدیریتی' }}</small>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>

    <section class="reports-result-card report-card">
        <header class="reports-result-header">
            <div><h3>{{ $reports[$type] ?? 'گزارش' }}</h3><p>{{ $descriptions[$type] ?? '' }}</p></div>
            <div class="reports-result-badge">{{ \Modules\Core\Support\Num::fa($rowCount) }} ردیف</div>
        </header>

        @if(!empty($rows))
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead><tr>@foreach(array_keys($rows[0]) as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                @foreach($row as $k=>$v)
                                    <td>
                                        @if(in_array($k,$moneyKeys,true) && is_numeric($v))
                                            @money($v) @unit
                                        @elseif(is_numeric($v) && !str_contains($k,'تاریخ'))
                                            @fa($v)
                                        @else
                                            {{ \Modules\Core\Support\Num::fa($v) }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="reports-empty"><h3>داده‌ای برای این بازه نیست</h3><p>بازه زمانی را بزرگ‌تر کن یا نوع گزارش را عوض کن.</p></div>
        @endif
    </section>
</div>
@endsection