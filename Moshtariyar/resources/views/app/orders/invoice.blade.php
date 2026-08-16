@extends('layouts.app')
@section('title', 'فاکتور سفارش #' . ($order->number ?? $order->id))

@section('content')
<div class="invoice-container" style="direction: rtl; text-align: right; font-family: inherit;">
    <div class="invoice-card">
        <!-- هدر فاکتور -->
        <div class="invoice-header">
            <div class="header-right">
                <h1 style="margin:0; color:#0f172a">فاکتور فروش</h1>
                <p class="muted">شماره سفارش: #@fa($order->number ?? $order->id)</p>
            </div>
            <div class="header-left">
                <h3 style="margin:0">{{ config('brand.name', 'سامانه مدیریت') }}</h3>
                <p>تاریخ صدور: @jdate($order->placed_at)</p>
            </div>
        </div>

        <hr style="border: 0; border-top: 2px solid #eee; margin: 30px 0;">

        <!-- اطلاعات طرفین -->
        <div class="info-section">
            <div class="info-box">
                <strong>اطلاعات مشتری:</strong><br>
                <span class="info-text">{{ $order->customer->full_name ?? 'نامشخص' }}</span><br>
                <span class="info-text">تلفن: {{ $order->customer->phone ?? '—' }}</span>
            </div>
            <div class="info-box text-left">
                <strong>وضعیت سفارش:</strong><br>
                <span class="status-label">
                    @php
                        $statusMap = [
                            'pending' => 'در انتظار', 'processing' => 'در حال آماده‌سازی', 
                            'on_hold' => 'معلق', 'completed' => 'تکمیل شده', 
                            'cancelled' => 'لغو شده', 'refunded' => 'مرجوع شده', 'failed' => 'ناموفق'
                        ];
                    @endphp
                    {{ $statusMap[$order->status] ?? $order->status }}
                </span>
            </div>
        </div>

        <!-- جدول اقلام -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>شرح کالا و خدمات</th>
                    <th style="text-align:center">تعداد</th>
                    <th style="text-align:center">قیمت واحد</th>
                    <th style="text-align:center">جمع کل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->name }}</strong><br>
                            <small class="muted">شناسه: {{ $item->sku }}</small>
                        </td>
                        <td style="text-align:center">@fa($item->qty)</td>
                        <td style="text-align:center">@money($item->unit_price)</td>
                        <td style="text-align:center">@money($item->line_total)</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:left; font-size:16px; font-weight:bold">مبلغ قابل پرداخت:</td>
                    <td style="text-align:center; font-size:20px; font-weight:bold; color:#0ea5e9">@money($order->total) @unit</td>
                </tr>
            </tfoot>
        </table>

        <!-- فوتر فاکتور -->
        <div class="invoice-footer">
            <div class="footer-box">
                <p>مهر و امضای فروشنده</p>
                <div class="signature-line"></div>
            </div>
            <div class="footer-box">
                <p>مهر و امضای خریدار</p>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>

    <div class="actions-area">
        <button onclick="window.print()" class="btn">🖨 چاپ فاکتور</button>
        <a href="{{ url('/app/orders') }}" class="btn btn-ghost">بازگشت به لیست</a>
    </div>
</div>

<style>
.invoice-container { width: 100%; max-width: 900px; margin: 0 auto; }
.invoice-card { 
    background: #fff; 
    padding: 40px; 
    border: 1px solid #e2e8f0; 
    border-radius: 16px; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    color: #1e293b;
}
.invoice-header { display: flex; justify-content: space-between; align-items: flex-start; }
.header-left { text-align: left; }
.info-section { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
.info-box { line-height: 1.8; }
.info-text { font-size: 15px; color: #334155; }
.text-left { text-align: left; }
.status-label { font-weight: bold; color: #0ea5e9; }

.invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
.invoice-table th { 
    background: #f8fafc; 
    padding: 12px; 
    border-bottom: 2px solid #e2e8f0; 
    text-align: right;
    font-size: 14px;
}
.invoice-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
.invoice-table tfoot td { padding: 20px; border-top: 2px solid #e2e8f0; }

.invoice-footer { display: flex; justify-content: space-between; margin-top: 60px; }
.footer-box { text-align: center; width: 200px; }
.signature-line { margin-top: 40px; border-top: 1px solid #94a3b8; }

.actions-area { margin-top: 30px; display: flex; justify-content: center; gap: 15px; }

@media print {
    .btn, .navbar, .sidebar, .footer, .actions-area { display:none !important; }
    .invoice-card { border:none !important; box-shadow:none !important; padding:0 !important; }
    body { background: #fff !important; }
}
</style>
@endsection