@extends('layouts.customer_portal')
@section('title','خریدهای من')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-orders.css') }}">
@php
    $statusLabels = [
        'pending' => ['در انتظار پرداخت', 'is-warn', '#f59e0b'],
        'processing' => ['در حال پردازش', 'is-warn', '#f59e0b'],
        'completed' => ['تکمیل شده', 'is-ok', '#10b981'],
        'cancelled' => ['لغو شده', 'is-bad', '#ef4444'],
        'refunded' => ['مرجوع شده', 'is-muted', '#64748b'],
        'failed' => ['ناموفق', 'is-bad', '#ef4444'],
        'on-hold' => ['در انتظار بررسی', 'is-muted', '#64748b'],
    ];
    $lastOrder = $stats['last'] ?? null;
@endphp
<div class="club-orders-page">
    <section class="club-orders-hero">
        <div>
            <span class="club-orders-eyebrow">مرکز خریدهای باشگاه</span>
            <h1>سفارش‌ها، امتیاز خرید و پیگیری‌ها در یک صفحه 🛍️</h1>
            <p>اینجا همه خریدهای شما، وضعیت سفارش‌ها، امتیازهای دریافت‌شده از خرید و مسیر ثبت درخواست درباره هر سفارش نمایش داده می‌شود.</p>
            <div class="club-orders-actions">
                <a class="btn" href="{{ route('club.coupons') }}">استفاده از امتیازها</a>
                <a class="btn btn-ghost" href="{{ route('club.tickets') }}">ثبت درخواست پشتیبانی</a>
                <a class="btn btn-ghost" href="{{ route('club.dashboard') }}">بازگشت به پیشخوان</a>
            </div>
        </div>
        <div class="club-orders-score-grid">
            <div><span>تعداد کل سفارش‌ها</span><b>@fa(number_format($stats['count'] ?? 0))</b><small>خریدهای ثبت‌شده شما</small></div>
            <div><span>ارزش کل خرید</span><b>@money($stats['total'] ?? 0) @unit</b><small>مجموع سفارش‌های ثبت‌شده</small></div>
            <div><span>امتیاز از خریدها</span><b>@fa(number_format($stats['points'] ?? 0))</b><small>امتیاز دریافت‌شده از سفارش‌ها</small></div>
        </div>
    </section>

    <section class="club-orders-grid">
        <article class="club-orders-card is-accent" style="--orders-card-color:#0ea5e9;">
            <header>
                <div>
                    <span>فهرست سفارش‌ها</span>
                    <h2>خریدهای ثبت‌شده شما</h2>
                </div>
                <span class="club-orders-badge">@fa(number_format($orders->total())) سفارش</span>
            </header>
            <div class="club-orders-list">
                @forelse($orders as $order)
                    @php
                        [$statusText, $statusClass, $statusColor] = $statusLabels[$order->status] ?? [$order->status ?: 'نامشخص', 'is-muted', '#64748b'];
                        $orderPoint = (int) $member->transactions()->where('ref_type', 'order_reward')->where('ref_id', $order->id)->where('direction', 'credit')->sum('amount');
                    @endphp
                    <article class="club-order-row" style="--order-color: {{ $statusColor }};">
                        <div class="club-order-icon">🧾</div>
                        <div>
                            <h3>سفارش شماره {{ $order->number ?: $order->id }}</h3>
                            <p>ثبت: {{ $order->placed_at ? \Modules\Core\Support\Jalali::datetime($order->placed_at) : \Modules\Core\Support\Jalali::datetime($order->created_at) }} · منبع: {{ $order->source ?: 'سامانه' }}</p>
                            <small>آیتم‌ها: @fa(number_format($order->items->count())) · امتیاز این سفارش: @fa(number_format($orderPoint))</small>
                        </div>
                        <div class="club-order-side">
                            <span class="club-orders-badge {{ $statusClass }}">{{ $statusText }}</span>
                            <strong class="club-order-amount">@money($order->total) @unit</strong>
                            <a class="btn btn-ghost" href="{{ route('club.orders.show', $order) }}">جزئیات</a>
                        </div>
                    </article>
                @empty
                    <div class="club-order-empty">هنوز سفارشی برای شما ثبت نشده است. با اولین خرید، امتیاز وفاداری و پیشنهادهای اختصاصی فعال می‌شود.</div>
                @endforelse
            </div>
            <div style="margin-top:1rem">{{ $orders->links() }}</div>
        </article>

        <aside class="club-orders-card is-accent" style="--orders-card-color:#10b981;">
            <header>
                <div>
                    <span>تحلیل خرید</span>
                    <h2>رفتار خرید شما</h2>
                </div>
            </header>
            @if($lastOrder)
                <div class="club-order-insight" style="--insight-color:#10b981; margin-bottom:.85rem">
                    <h3>آخرین خرید شما ثبت شده است</h3>
                    <p>آخرین سفارش شما با شماره {{ $lastOrder->number ?: $lastOrder->id }} در تاریخ {{ $lastOrder->placed_at ? \Modules\Core\Support\Jalali::date($lastOrder->placed_at) : \Modules\Core\Support\Jalali::date($lastOrder->created_at) }} ثبت شده است.</p>
                    <div class="club-orders-actions"><a class="btn btn-ghost" href="{{ route('club.orders.show', $lastOrder) }}">مشاهده آخرین سفارش</a></div>
                </div>
            @else
                <div class="club-order-empty">هنوز خریدی ثبت نشده است.</div>
            @endif

            <h3 style="margin:1rem 0 .65rem">محصول‌های پرتکرار</h3>
            <div class="club-orders-list">
                @forelse($favoriteItems as $item)
                    <article class="club-favorite-row" style="--order-color:#8b5cf6;">
                        <div class="club-favorite-icon">⭐</div>
                        <div>
                            <h3>{{ $item->name ?: 'محصول' }}</h3>
                            <p>تعداد خرید: @fa(number_format((int) $item->qty_sum))</p>
                            <small>جمع خرید: @money($item->total_sum ?? 0) @unit</small>
                        </div>
                        <span class="club-orders-badge is-muted">محبوب</span>
                    </article>
                @empty
                    <div class="club-order-empty">بعد از چند خرید، محصولات محبوب شما اینجا نمایش داده می‌شود.</div>
                @endforelse
            </div>
        </aside>
    </section>
</div>
@endsection