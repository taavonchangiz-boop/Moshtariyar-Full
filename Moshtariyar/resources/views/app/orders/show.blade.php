@extends('layouts.app')
@section('title', 'جزئیات سفارش #' . ($order->number ?? $order->id))
@section('heading', 'جزئیات سفارش')

@section('content')
@php
    $حالت_ونهی = request()->boolean('embed');

    // ترجمهٔ وضعیت‌ها به زبان ساده و محاوره‌ای
    $statusMap = [
        'pending'    => 'در انتظار تایید',
        'processing' => 'در حال آماده‌سازی',
        'on_hold'    => 'معلق شده',
        'completed'  => 'تکمیل شده',
        'cancelled'  => 'لغو شده',
        'refunded'   => 'مرجوع شده',
        'failed'     => 'ناموفق',
    ];
    $currentStatus = $statusMap[$order->status] ?? $order->status;
@endphp

<div class="card">
    <div class="row" style="justify-content:space-between; align-items:center">
        <div>
            <h3 style="margin:0">سفارش شماره #@fa($order->number ?? $order->id)</h3>
            <p class="muted">تاریخ ثبت: @jdate($order->placed_at)</p>
        </div>
        <div style="display:flex; gap:10px">
            {{-- دکمهٔ بازگشت فقط در حالت عادی نمایش داده شود، نه در کشو --}}
            @unless($حالت_ونهی)
                <a href="{{ url('/app/orders') }}" class="btn btn-ghost">بازگشت به لیست</a>
            @endunless
            <a href="{{ url('/app/orders/'.$order->id.'/invoice') }}" class="btn">🖨 چاپ فاکتور</a>
        </div>
    </div>
</div>

{{-- چیدمان دوستونه — در موبایل تک‌ستونه می‌شود --}}
<div class="جزئیات-سفارش">
    {{-- ستون اطلاعات مشتری --}}
    <div class="card">
        <h4>اطلاعات مشتری</h4>
        <hr>
        <div class="فهرست-اطلاعات">
            <div class="ردیف-اطلاعات">
                <span class="برچسب-اطلاعات">نام مشتری:</span>
                <span class="مقدار-اطلاعات">{{ $order->customer->full_name ?? 'نامشخص' }}</span>
            </div>
            <div class="ردیف-اطلاعات">
                <span class="برچسب-اطلاعات">تلفن تماس:</span>
                <span class="مقدار-اطلاعات">{{ $order->customer->phone ?? '—' }}</span>
            </div>
            <div class="ردیف-اطلاعات">
                <span class="برچسب-اطلاعات">وضعیت فعلی:</span>
                <span class="مقدار-اطلاعات">
                    <span class="نشان-وضعیت {{ $order->status == 'completed' ? 'تکمیل' : 'در-جریان' }}">
                        {{ $currentStatus }}
                    </span>
                </span>
            </div>
        </div>
    </div>

    {{-- ستون اقلام سفارش --}}
    <div class="card">
        <h4>لیست کالاهای سفارش</h4>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>نام کالا</th>
                        <th style="text-align:center">تعداد</th>
                        <th style="text-align:center">قیمت هر واحد</th>
                        <th style="text-align:center">جمع کل</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td data-label="نام کالا">{{ $item->name }} <br> <small class="muted">{{ $item->sku }}</small></td>
                            <td data-label="تعداد" style="text-align:center">@fa($item->qty)</td>
                            <td data-label="قیمت واحد" style="text-align:center">@money($item->unit_price)</td>
                            <td data-label="جمع کل" style="text-align:center">@money($item->line_total)</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:left; font-weight:bold; font-size:16px">مبلغ نهایی قابل پرداخت:</td>
                        <td style="text-align:center; font-weight:bold; font-size:18px; color:#2563eb">@money($order->total) @unit</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
/* ═══════════════ شیوه‌نامهٔ جزئیات سفارش ═══════════════ */

/* چیدمان دوستونه — در موبایل تک‌ستونه */
.جزئیات-سفارش {
    display: grid;
    grid-template-columns: 1fr 3fr;
    gap: 20px;
    margin-top: 20px;
}

/* فهرست اطلاعات مشتری */
.فهرست-اطلاعات {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ردیف-اطلاعات {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--line);
}

.برچسب-اطلاعات {
    color: var(--mut);
    font-size: 13px;
}

.مقدار-اطلاعات {
    font-weight: bold;
    font-size: 14px;
}

/* نشان وضعیت */
.نشان-وضعیت {
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    color: #fff;
}

.نشان-وضعیت.تکمیل {
    background: #10b981;
}

.نشان-وضعیت.در-جریان {
    background: #f59e0b;
}

/* ═══════════════ واکنش‌گرایی ═══════════════ */

/* تبلت — ستون‌ها کمی باریک‌تر */
@media (max-width: 900px) {
    .جزئیات-سفارش {
        grid-template-columns: 1fr 2fr;
        gap: 16px;
    }
}

/* موبایل — تک‌ستونه */
@media (max-width: 640px) {
    .جزئیات-سفارش {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-top: 12px;
    }

    .card {
        padding: 0.85rem;
        border-radius: 0.85rem;
        margin-bottom: 0.5rem;
    }

    .card h3 {
        font-size: 0.95rem;
    }

    .card h4 {
        font-size: 0.88rem;
    }

    .card .row {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.5rem;
    }

    .card .row > div:last-child {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .card .btn {
        font-size: 0.78rem;
        padding: 0.5rem 0.75rem;
        flex: 1;
        text-align: center;
        justify-content: center;
    }

    /* جدول در موبایل */
    .table-wrap table {
        min-width: 0 !important;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .table-wrap table thead {
        display: none;
    }

    .table-wrap table tbody,
    .table-wrap table tr,
    .table-wrap table td {
        display: block;
        width: 100%;
    }

    .table-wrap table tr {
        background: var(--panel2);
        border: 1px solid var(--line);
        border-radius: 0.75rem;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .table-wrap table td {
        border: 0 !important;
        white-space: normal !important;
        padding: 0.4rem 0.35rem !important;
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        align-items: center;
        text-align: left;
        direction: rtl;
    }

    .table-wrap table td::before {
        content: attr(data-label);
        font-weight: 900;
        color: var(--mut);
        text-align: right;
        min-width: 5.5rem;
        max-width: 40%;
        font-size: 0.75rem;
    }

    .table-wrap table tfoot tr {
        background: rgba(16, 185, 129, 0.08);
        border-color: rgba(16, 185, 129, 0.25);
    }

    .table-wrap table tfoot td {
        font-size: 0.85rem !important;
    }
}

/* موبایل بسیار کوچک */
@media (max-width: 380px) {
    .card {
        padding: 0.65rem;
        border-radius: 0.7rem;
    }

    .card h3 {
        font-size: 0.85rem;
    }

    .مقدار-اطلاعات {
        font-size: 0.8rem;
    }

    .برچسب-اطلاعات {
        font-size: 0.72rem;
    }
}
</style>
@endsection