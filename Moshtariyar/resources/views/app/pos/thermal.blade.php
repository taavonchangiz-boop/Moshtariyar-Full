<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>فاکتور حرارتی - {{ $order->number }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
@font-face{font-family:'Vazirmatn';src:url('/fonts/vazirmatn/Vazirmatn-VF.woff2') format('woff2');font-weight:100 900;font-display:swap}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Vazirmatn', Tahoma, sans-serif;background:#fff;color:#000;direction:rtl;font-size:11px;line-height:1.6;width:80mm;max-width:80mm;margin:0 auto;padding:0}
.thermal{width:80mm;max-width:80mm;padding:6mm 4mm;margin:0 auto;background:#fff}
.center{text-align:center}
.bold{font-weight:900}
.small{font-size:9px;color:#444}
.divider{border-top:1px dashed #000;margin:6px 0;padding:0}
.divider-double{border-top:2px double #000;margin:6px 0}
.business-name{font-size:14px;font-weight:1000;margin:2px 0}
.business-meta{font-size:9px;line-height:1.7;color:#222}
.order-meta{display:grid;grid-template-columns:1fr 1fr;gap:2px 6px;font-size:10px;margin:6px 0}
.order-meta b{font-weight:900}
.items{width:100%;border-collapse:collapse;margin:6px 0}
.items th{font-size:9px;font-weight:1000;border-bottom:1px dashed #000;padding:3px 2px;text-align:right}
.items td{font-size:10px;padding:3px 2px;text-align:right;vertical-align:top;border-bottom:1px dotted #ccc}
.items td.num{text-align:left;direction:ltr}
.items td.center{ text-align:center}
.total-box{margin:6px 0;display:grid;gap:2px;font-size:11px}
.total-row{display:flex;justify-content:space-between;gap:6px}
.total-row.is-grand{font-size:13px;font-weight:1000;border-top:1px dashed #000;border-bottom:1px dashed #000;padding:4px 0;margin-top:4px}
.qr{text-align:center;margin:8px 0}
.barcode{text-align:center;margin:6px 0;font-family:monospace;letter-spacing:2px;font-size:12px}
.footer{text-align:center;font-size:8.5px;line-height:1.8;margin-top:8px;color:#222}
@media print{
  @page{size:80mm auto;margin:0}
  body{width:80mm;padding:0}
  .no-print{display:none}
}
</style>
</head>
<body>
<div class="thermal">

    <div class="center">
        @if(!empty($businessName))
            <div class="business-name">{{ $businessName }}</div>
        @endif
        @if(!empty($businessAddress) || !empty($businessPhone))
            <div class="business-meta">
                @if($businessAddress){{ $businessAddress }}<br>@endif
                @if($businessPhone){{ $businessPhone }}@endif
            </div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="order-meta">
        <div><b>شماره فاکتور:</b> {{ $order->number }}</div>
        <div><b>تاریخ:</b> {{ \Modules\Core\Support\Jalali::datetime($order->placed_at) }}</div>
        <div><b>صندوقدار:</b> {{ auth()->user()->name ?? '—' }}</div>
        <div><b>مشتری:</b> {{ $order->customer->full_name ?? 'مشتری متفرقه' }}</div>
        @if($order->customer?->phone)
            <div><b>موبایل:</b> {{ $order->customer->phone }}</div>
        @endif
        <div><b>پرداخت:</b> {{ ['cash'=>'نقدی','card'=>'کارت','transfer'=>'انتقال','combined'=>'ترکیبی'][$order->meta['payment_method'] ?? 'cash'] ?? 'نقدی' }}</div>
    </div>

    <div class="divider"></div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:55%">کالا</th>
                <th class="center" style="width:15%">تعداد</th>
                <th class="num" style="width:15%">فی</th>
                <th class="num" style="width:15%">مبلغ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->name }}<br><span class="small">{{ $item->sku ?: '' }}</span></td>
                    <td class="center">{{ \Modules\Core\Support\Num::fa($item->qty) }}</td>
                    <td class="num">{{ \Modules\Core\Support\Num::fa(number_format($item->unit_price)) }}</td>
                    <td class="num">{{ \Modules\Core\Support\Num::fa(number_format($item->line_total)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="total-box">
        @php
            $subtotal = $order->meta['subtotal'] ?? $order->total;
            $discount = $order->meta['discount'] ?? 0;
            $pointsUsed = $order->meta['points_used'] ?? 0;
            $walletUsed = $order->meta['wallet_used'] ?? 0;
        @endphp
        <div class="total-row"><span>جمع جزء:</span><b>{{ \Modules\Core\Support\Num::fa(number_format($subtotal)) }} {{ \Modules\Core\Support\Money::unitLabel() }}</b></div>
        @if($discount>0)
            <div class="total-row"><span>تخفیف:</span><b>- {{ \Modules\Core\Support\Num::fa(number_format($discount)) }}</b></div>
        @endif
        @if($pointsUsed>0)
            <div class="total-row"><span>کسر امتیاز:</span><b>- {{ \Modules\Core\Support\Num::fa($pointsUsed) }}</b></div>
        @endif
        @if($walletUsed>0)
            <div class="total-row"><span>کسر کیف پول:</span><b>- {{ \Modules\Core\Support\Num::fa(number_format($walletUsed)) }}</b></div>
        @endif
        <div class="total-row is-grand"><span>قابل پرداخت:</span><b>{{ \Modules\Core\Support\Num::fa(number_format($order->total)) }} {{ \Modules\Core\Support\Money::unitLabel() }}</b></div>
    </div>

    <div class="divider"></div>

    <div class="qr">
        <div style="width:28mm;height:28mm;margin:0 auto;border:1px dashed #000;display:grid;place-items:center;font-size:8px">QR: {{ $order->number }}</div>
        <div class="small">برای امتیاز باشگاه، این کد را در اپلیکیشن اسکن کنید</div>
    </div>

    <div class="barcode">*{{ $order->number }}*</div>

    <div class="divider-double"></div>

    <div class="footer">
        <b>{{ $invoiceFooter }}</b><br>
        <span>از خرید شما سپاسگزاریم - به امید دیدار مجدد</span><br>
        <span class="small">این فاکتور بدون مهر و امضا معتبر است - {{ \Modules\Core\Support\Jalali::datetime(now()) }}</span>
    </div>

    <div class="center no-print" style="margin-top:12px;display:grid;gap:6px">
        <button onclick="window.print()" style="width:100%;padding:10px;border:1px solid #000;border-radius:6px;background:#000;color:#fff;font-family:inherit;font-weight:900;cursor:pointer">🖨️ چاپ فاکتور</button>
        <a href="{{ url('/app/pos') }}" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;background:#fff;color:#000;text-decoration:none;font-size:11px;text-align:center">بازگشت به صندوق</a>
    </div>

</div>
</body>
</html>