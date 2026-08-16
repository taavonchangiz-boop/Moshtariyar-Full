@extends('layouts.app')
@section('title', 'فاکتور سفارش')
@section('heading', 'فاکتور سفارش')
@section('subtitle', 'نمایش و چاپ فاکتور سفارش')

@section('content')
@php
    $paperSize = ($invoiceSettings['paper_size'] ?? 'a4') === 'a5' ? 'a5' : 'a4';
    $primaryColor = $invoiceSettings['primary_color'] ?? '#0ea5e9';
    $logoPath = $invoiceSettings['logo'] ?? '';
    $logoUrl = '';
    if ($logoPath) {
        $logoUrl = str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://') ? $logoPath : asset($logoPath);
    }

    $statusLabels = [
        'pending' => 'در انتظار',
        'processing' => 'در حال آماده‌سازی',
        'on_hold' => 'معلق',
        'completed' => 'تکمیل‌شده',
        'cancelled' => 'لغو شده',
        'refunded' => 'مرجوعی',
        'failed' => 'ناموفق',
    ];

    $baseTotal = 0;
    $taxTotal = 0;
    foreach ($order->items as $item) {
        $meta = is_array($item->meta) ? $item->meta : [];
        $qty = max(1, (int) ($item->qty ?? 1));
        $lineBase = isset($meta['مبلغ_پایه']) ? (float) $meta['مبلغ_پایه'] * $qty : ((float) $item->line_total - ((float) ($meta['مالیات_کل'] ?? 0)));
        $lineTax = isset($meta['مالیات']) ? (float) $meta['مالیات'] * $qty : (float) ($meta['مالیات_کل'] ?? 0);
        if ($lineBase < 0) $lineBase = 0;
        $baseTotal += $lineBase;
        $taxTotal += $lineTax;
    }
    if ($baseTotal <= 0) {
        $baseTotal = max(0, (float) $order->total - (float) $order->tax_total);
        $taxTotal = (float) $order->tax_total;
    }
    $payable = $baseTotal + $taxTotal;
@endphp

<style>
.invoice-wrap{display:flex;justify-content:center}
.invoice-sheet{background:#fff;color:#111827;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.18);padding:28px;width:100%}
.invoice-sheet.a4{max-width:900px;min-height:1120px}
.invoice-sheet.a5{max-width:620px;min-height:820px}
.invoice-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;border-bottom:2px solid {{ $primaryColor }};padding-bottom:18px;margin-bottom:18px}
.invoice-logo{max-width:110px;max-height:110px;object-fit:contain}
.invoice-title{font-size:1.7rem;font-weight:900;color:{{ $primaryColor }};margin:0}
.invoice-card{border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#f8fafc}
.invoice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:18px}
.invoice-table{width:100%;border-collapse:collapse;margin-top:10px}
.invoice-table th,.invoice-table td{border:1px solid #e5e7eb;padding:10px;text-align:right;vertical-align:top}
.invoice-table th{background:#f1f5f9;font-weight:800}
.invoice-totals{margin-top:18px;display:grid;grid-template-columns:1fr 320px;gap:18px}
.invoice-note{white-space:pre-wrap;line-height:2}
.invoice-actions{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:16px}
@media print{
  body{background:#fff}
  .mobile-bar,.sidebar,.topbar,.invoice-actions,#adminAssistantFloat,#adminAssistantPanel,.card:not(.invoice-holder){display:none!important}
  .layout,.main{display:block!important;padding:0!important;margin:0!important}
  .invoice-holder{border:none!important;box-shadow:none!important;padding:0!important;margin:0!important}
  .invoice-sheet{box-shadow:none!important;border-radius:0!important;max-width:none!important;width:100%!important;padding:14mm!important}
  @page{size: {{ strtoupper($paperSize) }}; margin: 10mm}
}
@media(max-width:760px){
  .invoice-grid,.invoice-totals{grid-template-columns:1fr}
  .invoice-head{flex-direction:column;align-items:flex-start}
}
</style>

<div class="invoice-actions">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn btn-ghost" href="{{ url('/app/orders/'.$order->id) }}">بازگشت به سفارش</a>
        <a class="btn btn-ghost" href="{{ url('/app/settings') }}">تنظیمات فاکتور</a>
    </div>
    <button class="btn" onclick="window.print()">چاپ یا ذخیره فاکتور</button>
</div>

<div class="card invoice-holder">
    <div class="invoice-wrap">
        <div class="invoice-sheet {{ $paperSize }}">
            <div class="invoice-head">
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    @if($invoiceSettings['logo_enabled'] && $logoUrl)
                        <img src="{{ $logoUrl }}" alt="لوگو" class="invoice-logo">
                    @endif
                    <div>
                        <h2 class="invoice-title">فاکتور فروش</h2>
                        <div style="margin-top:6px;font-weight:800;font-size:1.1rem;">{{ $invoiceSettings['store_name'] }}</div>
                        @if($invoiceSettings['seller_info_enabled'])
                            @if(!empty($invoiceSettings['address']))<div style="margin-top:6px;line-height:1.9">{{ $invoiceSettings['address'] }}</div>@endif
                            @if(!empty($invoiceSettings['phone']))<div style="margin-top:4px">شماره تماس: @fa($invoiceSettings['phone'])</div>@endif
                        @endif
                    </div>
                </div>
                <div class="invoice-card" style="min-width:250px;">
                    <div>شماره سفارش: <b>@fa($order->number ?: $order->id)</b></div>
                    <div>تاریخ ثبت: <b>@jdatetime($order->placed_at)</b></div>
                    <div>وضعیت سفارش: <b>{{ $statusLabels[$order->status] ?? $order->status }}</b></div>
                    @if($order->taxInvoice)
                        <div>شناسه مالیاتی: <b class="ltr">@fa($order->taxInvoice->tax_id)</b></div>
                    @endif
                </div>
            </div>

            <div class="invoice-grid">
                <div class="invoice-card">
                    <div style="font-weight:800;margin-bottom:8px;color:{{ $primaryColor }};">مشخصات خریدار</div>
                    <div>نام خریدار: <b>{{ $order->customer->full_name ?? '—' }}</b></div>
                    @if($invoiceSettings['customer_phone_enabled'])
                        <div>شماره تماس: <b class="ltr">@fa($order->customer->phone ?? '—')</b></div>
                    @endif
                    <div>واحد پول: <b>{{ $order->currency === 'IRR' ? 'ریال' : 'تومان' }}</b></div>
                </div>
                <div class="invoice-card">
                    <div style="font-weight:800;margin-bottom:8px;color:{{ $primaryColor }};">خلاصه مبلغ</div>
                    <div>جمع مبلغ کالاها: <b>@money($baseTotal) @unit</b></div>
                    @if($invoiceSettings['tax_details_enabled'])
                        <div>جمع مالیات: <b>@money($taxTotal) @unit</b></div>
                    @endif
                    <div style="margin-top:6px;font-size:1.05rem;">مبلغ نهایی قابل پرداخت: <b style="color:{{ $primaryColor }};">@money($payable) @unit</b></div>
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>ردیف</th>
                        <th>شرح کالا یا خدمت</th>
                        <th>تعداد</th>
                        <th>مبلغ واحد</th>
                        <th>مالیات هر ردیف</th>
                        <th>جمع ردیف</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $index => $item)
                        @php
                            $meta = is_array($item->meta) ? $item->meta : [];
                            $qty = max(1, (int) ($item->qty ?? 1));
                            $lineTax = isset($meta['مالیات']) ? (float) $meta['مالیات'] * $qty : (float) ($meta['مالیات_کل'] ?? 0);
                        @endphp
                        <tr>
                            <td>@fa($index + 1)</td>
                            <td>
                                <b>{{ $item->name }}</b>
                                @if(!empty($meta['نوع_محاسبه']))<div style="margin-top:4px;color:#475569;">روش حساب: {{ $meta['نوع_محاسبه'] }}</div>@endif
                                @if(!empty($meta['فرمول']))<div style="margin-top:4px;color:#475569;">فرمول: {{ $meta['فرمول'] }}</div>@endif
                            </td>
                            <td>@fa($qty)</td>
                            <td>@money($item->unit_price) @unit</td>
                            <td>@money($lineTax) @unit</td>
                            <td>@money($item->line_total) @unit</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="invoice-totals">
                <div class="invoice-card invoice-note">
                    <div style="font-weight:800;margin-bottom:8px;color:{{ $primaryColor }};">یادداشت</div>
                    {{ $invoiceSettings['note'] ?: 'این فاکتور بر اساس اطلاعات ثبت‌شده در سامانه ساخته شده است.' }}
                </div>
                <div class="invoice-card">
                    <div style="display:grid;gap:8px;">
                        <div style="display:flex;justify-content:space-between;gap:8px;"><span>جمع مبلغ کالاها</span><b>@money($baseTotal) @unit</b></div>
                        @if($invoiceSettings['tax_details_enabled'])
                            <div style="display:flex;justify-content:space-between;gap:8px;"><span>مالیات</span><b>@money($taxTotal) @unit</b></div>
                        @endif
                        <div style="display:flex;justify-content:space-between;gap:8px;font-size:1.06rem;border-top:1px solid #e5e7eb;padding-top:8px;"><span>قابل پرداخت</span><b style="color:{{ $primaryColor }};">@money($payable) @unit</b></div>
                    </div>
                </div>
            </div>

            @if(!empty($invoiceSettings['footer']))
                <div style="margin-top:22px;padding-top:14px;border-top:1px dashed #cbd5e1;color:#475569;text-align:center;line-height:1.9;">{{ $invoiceSettings['footer'] }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
