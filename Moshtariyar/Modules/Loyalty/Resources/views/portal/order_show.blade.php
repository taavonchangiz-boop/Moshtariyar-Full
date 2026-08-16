@extends('layouts.customer_portal')
@section('title','جزئیات سفارش')
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
    [$statusText, $statusClass, $statusColor] = $statusLabels[$order->status] ?? [$order->status ?: 'نامشخص', 'is-muted', '#64748b'];
    $ticketSubject = 'پیگیری سفارش شماره ' . ($order->number ?: $order->id);
    $ticketMessage = 'سلام، لطفاً درباره سفارش شماره ' . ($order->number ?: $order->id) . ' راهنمایی بفرمایید.';
@endphp
<div class="club-orders-page">
    <section class="club-orders-hero">
        <div>
            <span class="club-orders-eyebrow">جزئیات سفارش</span>
            <h1>سفارش شماره {{ $order->number ?: $order->id }}</h1>
            <p>جزئیات خرید، آیتم‌ها، وضعیت سفارش، امتیازهای وفاداری و مسیر ثبت درخواست درباره همین سفارش در این صفحه قرار دارد.</p>
            <div class="club-orders-actions">
                <a class="btn btn-ghost" href="{{ route('club.orders') }}">بازگشت به خریدها</a>
                <a class="btn" href="{{ route('club.tickets', ['subject' => $ticketSubject, 'message' => $ticketMessage]) }}">ثبت درخواست درباره سفارش</a>
                <a class="btn btn-ghost" href="{{ route('club.coupons') }}">کدهای تخفیف من</a>
            </div>
        </div>
        <div class="club-orders-score-grid">
            <div><span>وضعیت سفارش</span><b>{{ $statusText }}</b><small>آخرین وضعیت ثبت‌شده</small></div>
            <div><span>مبلغ سفارش</span><b>@money($order->total) @unit</b><small>جمع کل خرید</small></div>
            <div><span>امتیاز این سفارش</span><b>@fa(number_format($orderPoints))</b><small>پاداش وفاداری خرید</small></div>
        </div>
    </section>

    <section class="club-order-summary-grid">
        <div><span>شماره سفارش</span><b>{{ $order->number ?: $order->id }}</b><small>شناسه خرید شما</small></div>
        <div><span>تاریخ ثبت</span><b>{{ $order->placed_at ? \Modules\Core\Support\Jalali::date($order->placed_at) : \Modules\Core\Support\Jalali::date($order->created_at) }}</b><small>زمان ثبت سفارش</small></div>
        <div><span>منبع سفارش</span><b>{{ $order->source ?: 'سامانه' }}</b><small>محل ثبت سفارش</small></div>
        <div><span>مالیات</span><b>@money($order->tax_total ?? 0) @unit</b><small>مبلغ مالیات ثبت‌شده</small></div>
    </section>

    <section class="club-orders-grid">
        <article class="club-orders-card is-accent" style="--orders-card-color:{{ $statusColor }};">
            <header>
                <div>
                    <span>آیتم‌های سفارش</span>
                    <h2>محصول‌های خریداری‌شده</h2>
                </div>
                <span class="club-orders-badge {{ $statusClass }}">{{ $statusText }}</span>
            </header>
            <div class="club-order-items-list">
                @forelse($order->items as $item)
                    <article class="club-order-item-row" style="--order-color:#0ea5e9;">
                        <div class="club-order-icon">📦</div>
                        <div>
                            <h3>{{ $item->name ?: 'محصول' }}</h3>
                            <p>کد کالا: <span class="ltr">{{ $item->sku ?: '—' }}</span> · تعداد: @fa(number_format((float) $item->qty))</p>
                            <small>قیمت واحد: @money($item->unit_price ?? 0) @unit</small>
                        </div>
                        <strong class="club-order-amount">@money($item->line_total ?? 0) @unit</strong>
                    </article>
                @empty
                    <div class="club-order-empty">برای این سفارش آیتمی ثبت نشده است.</div>
                @endforelse
            </div>
        </article>

        <aside class="club-orders-card is-accent" style="--orders-card-color:{{ $suggestion['color'] ?? '#0ea5e9' }};">
            <header>
                <div>
                    <span>پیشنهاد هوشمند</span>
                    <h2>{{ $suggestion['title'] }}</h2>
                </div>
            </header>
            <div class="club-order-insight" style="--insight-color:{{ $suggestion['color'] ?? '#0ea5e9' }};">
                <h3>{{ $suggestion['title'] }}</h3>
                <p>{{ $suggestion['body'] }}</p>
                <div class="club-orders-actions"><a class="btn btn-ghost" href="{{ $suggestion['url'] }}">{{ $suggestion['label'] }}</a></div>
            </div>

            <h3 style="margin:1rem 0 .65rem">تراکنش‌های مرتبط</h3>
            <div class="club-order-timeline">
                @forelse($relatedTransactions as $transaction)
                    <article class="club-order-transaction-row" style="--order-color:{{ ($transaction->direction ?? '') === 'debit' ? '#ef4444' : '#10b981' }};">
                        <div class="club-order-icon">{{ ($transaction->direction ?? '') === 'debit' ? '−' : '+' }}</div>
                        <div>
                            <h3>{{ $transaction->reason ?: 'تراکنش سفارش' }}</h3>
                            <p>نوع: {{ \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS[$transaction->kind] ?? $transaction->kind }} · @jdatetime($transaction->created_at)</p>
                        </div>
                        <strong class="club-order-amount">{{ ($transaction->direction ?? '') === 'debit' ? '−' : '+' }}@fa(number_format($transaction->amount))</strong>
                    </article>
                @empty
                    <div class="club-order-empty">تراکنش وفاداری مستقیمی برای این سفارش ثبت نشده است.</div>
                @endforelse
            </div>
        </aside>
    </section>
</div>
@endsection