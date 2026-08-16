@extends('layouts.app')
@section('title', 'جزئیات سفارش')
@section('heading', 'سفارش شماره ' . ($order->number ?: $order->id))
@section('subtitle', 'جزئیات کامل مبلغ، مشتری و روش محاسبه')

@section('content')
@php
    $statusLabel = $statusLabels[$order->status] ?? $order->status;
    $sourceLabel = $sourceLabels[$order->source] ?? $order->source;
@endphp

<div class="grid grid-4">
    <div class="card stat"><div class="num">@fa($order->number ?: $order->id)</div><div class="lbl">شماره سفارش</div></div>
    <div class="card stat"><div class="num">@money($order->total)</div><div class="lbl">مبلغ نهایی (@unit)</div></div>
    <div class="card stat"><div class="num">@money($order->tax_total)</div><div class="lbl">مالیات ثبت‌شده (@unit)</div></div>
    <div class="card stat"><div class="num">{{ $statusLabel }}</div><div class="lbl">وضعیت سفارش</div></div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3 style="margin-top:0">اطلاعات سفارش</h3>
        <div style="display:grid;gap:10px">
            <div>روش ثبت: <b>{{ $sourceLabel }}</b></div>
            <div>زمان ثبت: <b class="ltr">@jdatetime($order->placed_at)</b></div>
            <div>واحد پول: <b>{{ $order->currency === 'IRR' ? 'ریال' : 'تومان' }}</b></div>
            <div>مشتری: <b>{{ $order->customer->full_name ?? '—' }}</b></div>
            <div>شماره مشتری: <b class="ltr">@fa($order->customer->phone ?? '—')</b></div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
            <a class="btn btn-ghost" href="{{ url('/app/orders') }}">بازگشت به سفارش‌ها</a>
            <a class="btn btn-ghost" href="{{ url('/app/orders/'.$order->id.'/invoice') }}">مشاهده فاکتور</a>
            @if($order->customer_id)
                <a class="btn btn-ghost" href="{{ url('/app/customers/'.$order->customer_id) }}">مشاهده پرونده مشتری</a>
            @endif
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0">کارهای سریع</h3>
        <div style="display:grid;gap:12px;">
            <form method="post" action="{{ url('/app/orders/'.$order->id.'/status') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
                @csrf
                <div style="flex:1;min-width:220px;">
                    <label>وضعیت سفارش</label>
                    <select name="status">
                        <option value="pending" @selected($order->status==='pending')>در انتظار</option>
                        <option value="processing" @selected($order->status==='processing')>در حال آماده‌سازی</option>
                        <option value="on_hold" @selected($order->status==='on_hold')>معلق</option>
                        <option value="completed" @selected($order->status==='completed')>تکمیل‌شده</option>
                        <option value="cancelled" @selected($order->status==='cancelled')>لغو شده</option>
                        <option value="refunded" @selected($order->status==='refunded')>مرجوعی</option>
                        <option value="failed" @selected($order->status==='failed')>ناموفق</option>
                    </select>
                </div>
                <button class="btn btn-primary">ثبت وضعیت</button>
            </form>

            <form method="post" action="{{ url('/app/orders/'.$order->id.'/pay') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
                @csrf
                <div style="flex:1;min-width:220px;">
                    <label>درگاه پرداخت</label>
                    <select name="gateway">
                        <option value="zarinpal">زرین‌پال</option>
                        <option value="zibal">زیبال</option>
                    </select>
                </div>
                <button class="btn btn-primary">رفتن به پرداخت</button>
            </form>

            <form method="post" action="{{ url('/app/orders/'.$order->id.'/tax-invoice') }}">
                @csrf
                <button class="btn btn-ghost">صدور فاکتور رسمی</button>
            </form>
        </div>
    </div>
</div>

@if(($loyaltyTransactions ?? collect())->isNotEmpty())
<div class="card">
    <h3 style="margin-top:0">اثر این سفارش روی باشگاه مشتریان</h3>
    @foreach($loyaltyTransactions as $tx)
        <div style="padding:10px 0;border-bottom:1px solid var(--line)">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <b>{{ \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS[$tx->kind] ?? $tx->kind }}</b>
                <span class="badge {{ $tx->direction==='credit' ? 'b-ok' : 'b-bad' }}">{{ $tx->direction==='credit' ? 'اضافه شده' : 'کسر شده' }}</span>
            </div>
            <div class="muted" style="margin-top:4px;line-height:1.9;">{{ $tx->reason }}</div>
            <div class="muted" style="font-size:.78rem;margin-top:4px;">مقدار: @fa(number_format($tx->amount)) · مانده پس از ثبت: @fa(number_format($tx->balance_after)) · زمان: @jdatetime($tx->created_at)</div>
        </div>
    @endforeach
</div>
@endif

<div class="card">
    <h3 style="margin-top:0">ریز اقلام و روش حساب مبلغ</h3>
    @forelse($order->items as $item)
        @php $meta = is_array($item->meta) ? $item->meta : []; @endphp
        <div style="border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:14px;background:var(--panel2);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                <div>
                    <b style="font-size:1.05rem;">{{ $item->name }}</b>
                    <div class="muted">کد کالا: <span class="ltr">{{ $item->sku ?: '—' }}</span></div>
                    @if(isset($meta['وضعیت_موجودی']))
                        <div style="margin-top:6px;">
                            <span class="badge {{ ($meta['وضعیت_موجودی'] ?? '') === 'کم_شده' ? 'b-ok' : (($meta['وضعیت_موجودی'] ?? '') === 'برگشت_داده_شد' ? 'b-warn' : 'b-mut') }}">
                                {{ ($meta['وضعیت_موجودی'] ?? '') === 'کم_شده' ? 'موجودی کم شده' : (($meta['وضعیت_موجودی'] ?? '') === 'برگشت_داده_شد' ? 'موجودی برگشته' : 'وضعیت موجودی ثبت نشده') }}
                            </span>
                        </div>
                    @endif
                </div>
                <div style="text-align:left;">
                    <div>تعداد: <b>@fa($item->qty)</b></div>
                    <div>مبلغ واحد: <b>@money($item->unit_price) @unit</b></div>
                    <div>مبلغ کل: <b>@money($item->line_total) @unit</b></div>
                </div>
            </div>

            @if(!empty($meta))
                <div style="margin-top:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                    @foreach($meta as $key => $value)
                        <div style="background:var(--panel);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:12px;">
                            @php $cleanKey = str_replace('_', ' ', $key); @endphp
                            <div class="muted" style="font-size:.78rem;margin-bottom:4px;">{{ $cleanKey }}</div>
                            @if(is_array($value))
                                <div style="display:grid;gap:6px;">
                                    @foreach($value as $subKey => $subValue)
                                        <div style="display:flex;justify-content:space-between;gap:8px;">
                                            <span class="muted">{{ $subKey }}</span>
                                            <b>{{ \Modules\Core\Support\DisplayValue::show($subValue, (string) $subKey) }}</b>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <b>{{ \Modules\Core\Support\DisplayValue::show($value, $cleanKey) }}</b>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="muted" style="margin-top:10px;">برای این قلم، جزئیات محاسبه جداگانه‌ای ثبت نشده است.</div>
            @endif
        </div>
    @empty
        <div class="empty">برای این سفارش قلمی ثبت نشده است.</div>
    @endforelse
</div>
@endsection
