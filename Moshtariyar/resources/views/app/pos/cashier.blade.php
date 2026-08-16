@extends('layouts.app')
@section('title','گزارش صندوق و شیفت')
@section('heading','گزارش صندوق و مدیریت شیفت')
@section('subtitle','باز و بسته کردن شیفت، گزارش فروش نقدی و کارتی امروز و تاریخچه شیفت‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('css/warehouses-board.css') }}">
<link rel="stylesheet" href="{{ asset('css/reports-center.css') }}">

@php
    if (!function_exists('fa_num_cash')) {
        function fa_num_cash($n) {
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return str_replace($en, $fa, (string) $n);
        }
    }
    if (!function_exists('fa_money_cash')) {
        function fa_money_cash($n) { return fa_num_cash(number_format((int) $n)); }
    }
@endphp

<div class="warehouses-page">

    <section class="wh-hero">
        <div>
            <span class="wh-eyebrow">صندوق و شیفت</span>
            <h2>شیفت را باز کن، بفروش، و با گزارش دقیق ببند</h2>
            <p>هر صندوقدار شیفت خودش را باز می‌کند، موجودی اول را ثبت می‌کند، در طول شیفت فروش‌ها به صورت خودکار به شیفت اضافه می‌شود و در پایان موجودی آخر را ثبت و شیفت را می‌بندد. همه با تاریخ شمسی.</p>
            <div class="wh-actions">
                @if($openShift)
                    <button class="btn btn-danger" onclick="openModal('closeShiftModal')">بستن شیفت فعلی</button>
                @else
                    <button class="btn" onclick="openModal('openShiftModal')">باز کردن شیفت جدید</button>
                @endif
                <a class="btn btn-ghost" href="{{ url('/app/pos') }}">رفتن به صندوق فروش</a>
                <a class="btn btn-ghost" href="{{ url('/app/orders?source=pos') }}">سفارش‌های امروز صندوق</a>
            </div>
        </div>
        <div class="wh-score">
            <div class="wh-score-item" style="--metric-color:#10b981;"><span>فروش امروز صندوق</span><b>{{ fa_money_cash($stats['total_sales'] ?? 0) }}</b><small>تومان - امروز</small></div>
            <div class="wh-score-item" style="--metric-color:#3b82f6;"><span>تعداد فاکتور امروز</span><b>{{ fa_num_cash($stats['orders_count'] ?? 0) }}</b><small>فاکتور</small></div>
            <div class="wh-score-item" style="--metric-color:#22c55e;"><span>نقدی</span><b>{{ fa_money_cash($stats['cash_sales'] ?? 0) }}</b><small>تومان</small></div>
            <div class="wh-score-item" style="--metric-color:#8b5cf6;"><span>کارت بانکی</span><b>{{ fa_money_cash($stats['card_sales'] ?? 0) }}</b><small>تومان</small></div>
            <div class="wh-score-item" style="--metric-color:#f59e0b;"><span>تخفیف امروز</span><b>{{ fa_money_cash($stats['total_discount'] ?? 0) }}</b><small>تومان</small></div>
        </div>
    </section>

    @if($openShift)
        <section class="report-card" style="border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.06)">
            <header>
                <div>
                    <span class="card-chip">🟢 شیفت باز فعلی</span>
                    <h3>شیفت باز شما از {{ \Modules\Core\Support\Jalali::datetime($openShift->opened_at) }}</h3>
                    <p>مدت زمان: {{ $openShift->duration }} • انبار: {{ $openShift->warehouse->name ?? '—' }} • موجودی اول: {{ fa_money_cash($openShift->opening_cash) }} تومان</p>
                </div>
                <div class="export-actions">
                    <button class="btn btn-danger" onclick="openModal('closeShiftModal')">بستن شیفت</button>
                </div>
            </header>
            <div class="kpi-grid">
                <div class="kpi-tile" style="--kpi-color:#10b981;"><span>فروش این شیفت</span><b>{{ fa_money_cash($openShift->total_sales) }}</b><small>تومان</small></div>
                <div class="kpi-tile" style="--kpi-color:#3b82f6;"><span>تعداد فاکتور این شیفت</span><b>{{ fa_num_cash($openShift->orders_count) }}</b><small>فاکتور</small></div>
                <div class="kpi-tile" style="--kpi-color:#22c55e;"><span>نقدی این شیفت</span><b>{{ fa_money_cash($openShift->cash_sales) }}</b><small>تومان</small></div>
                <div class="kpi-tile" style="--kpi-color:#8b5cf6;"><span>کارتی این شیفت</span><b>{{ fa_money_cash($openShift->card_sales) }}</b><small>تومان</small></div>
            </div>
        </section>
    @else
        <section class="report-card" style="border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.08)">
            <header>
                <div>
                    <span class="card-chip">⚠️ شیفت بسته است</span>
                    <h3>شیفت بازی ندارید - برای فروش باید شیفت باز کنید</h3>
                    <p>برای شروع فروش امروز، موجودی اول صندوق را وارد کنید و شیفت را باز کنید.</p>
                </div>
                <div class="export-actions"><button class="btn" onclick="openModal('openShiftModal')">باز کردن شیفت</button></div>
            </header>
        </section>
    @endif

    <div class="report-grid-2">
        <div class="report-card">
            <header><div><span class="card-chip">📋 فاکتورهای امروز صندوق</span><h3>سفارش‌های ثبت شده امروز از صندوق</h3></div></header>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead><tr><th>شماره</th><th>مشتری</th><th>مبلغ</th><th>پرداخت</th><th>ساعت</th><th>چاپ</th></tr></thead>
                    <tbody>
                        @forelse($todayOrders as $o)
                            <tr>
                                <td><b>{{ $o->number }}</b></td>
                                <td>{{ $o->customer->full_name ?? 'متفرقه' }}</td>
                                <td class="num">{{ fa_money_cash($o->total) }} تومان</td>
                                <td><span class="report-badge is-normal">{{ ['cash'=>'نقدی','card'=>'کارت','transfer'=>'انتقال','combined'=>'ترکیبی'][$o->meta['payment_method'] ?? 'cash'] ?? 'نقدی' }}</span></td>
                                <td class="num">{{ \Modules\Core\Support\Jalali::datetime($o->placed_at) }}</td>
                                <td><a href="{{ url('/app/pos/'.$o->id.'/thermal') }}" target="_blank" class="btn btn-ghost" style="padding:.2rem .5rem;font-size:.7rem">🖨️ حرارتی</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="reports-empty"><h3>امروز هنوز فاکتوری از صندوق ثبت نشده</h3></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-card">
            <header><div><span class="card-chip">🕐 تاریخچه شیفت‌ها</span><h3>۲۰ شیفت اخیر</h3></div></header>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead><tr><th>کاربر</th><th>باز شدن</th><th>بسته شدن</th><th>فروش</th><th>وضعیت</th></tr></thead>
                    <tbody>
                        @forelse($shifts as $sh)
                            <tr>
                                <td><b>{{ $sh->user->name ?? '—' }}</b></td>
                                <td class="num">{{ \Modules\Core\Support\Jalali::datetime($sh->opened_at) }}</td>
                                <td class="num">{{ $sh->closed_at ? \Modules\Core\Support\Jalali::datetime($sh->closed_at) : 'باز' }}</td>
                                <td class="num">{{ fa_money_cash($sh->total_sales) }} تومان</td>
                                <td><span class="report-badge {{ $sh->status==='open'?'is-loyal':'is-normal' }}">{{ $sh->status==='open'?'باز':'بسته' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="reports-empty"><h3>شیفتی ثبت نشده</h3></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div id="openShiftModal" class="wh-modal">
    <div class="wh-modal-box">
        <header><h3>باز کردن شیفت جدید</h3><button onclick="closeModal('openShiftModal')">×</button></header>
        <form method="post" action="{{ url('/app/pos/shifts/open') }}">
            @csrf
            <div class="wh-form-grid">
                <div class="wh-field"><label>انبار شیفت</label><select name="warehouse_id"><option value="">بدون انبار خاص</option>@foreach(\Modules\Core\Entities\Warehouse::where('is_active',true)->get() as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select></div>
                <div class="wh-field"><label>موجودی اول صندوق (تومان) *</label><input name="opening_cash" type="number" min="0" required placeholder="مثلا ۵۰۰۰۰۰"></div>
                <div class="wh-field col-span-2"><label>یادداشت</label><input name="note" placeholder="مثلا شیفت صبح"></div>
            </div>
            <div class="wh-form-footer"><button class="btn">باز کردن شیفت</button></div>
        </form>
    </div>
</div>

@if($openShift)
<div id="closeShiftModal" class="wh-modal">
    <div class="wh-modal-box">
        <header><h3>بستن شیفت فعلی</h3><button onclick="closeModal('closeShiftModal')">×</button></header>
        <form method="post" action="{{ url('/app/pos/shifts/'.$openShift->id.'/close') }}">
            @csrf
            <div class="wh-form-grid">
                <div class="wh-field col-span-2"><label>موجودی آخر صندوق (تومان) *</label><input name="closing_cash" type="number" min="0" required placeholder="مثلا ۱۲۵۰۰۰۰"></div>
                <div class="wh-field col-span-2"><label>یادداشت تحویل</label><input name="note" placeholder="مثلا تحویل به شیفت عصر"></div>
            </div>
            <div style="margin-top:.8rem;padding:.6rem;border:1px dashed var(--line);border-radius:.6rem;background:var(--panel2)">
                <b>خلاصه شیفت فعلی:</b><br>
                فروش کل: {{ fa_money_cash($openShift->total_sales) }} تومان<br>
                نقدی: {{ fa_money_cash($openShift->cash_sales) }} • کارتی: {{ fa_money_cash($openShift->card_sales) }} • تعداد فاکتور: {{ fa_num_cash($openShift->orders_count) }}
            </div>
            <div class="wh-form-footer"><button class="btn btn-danger">بستن شیفت و ثبت گزارش</button></div>
        </form>
    </div>
</div>
@endif

<script>
function openModal(id){ document.getElementById(id)?.classList.add('show'); document.body.style.overflow='hidden'; }
function closeModal(id){ document.getElementById(id)?.classList.remove('show'); document.body.style.overflow=''; }
window.addEventListener('click', function(e){ if(e.target.classList.contains('wh-modal')){ e.target.classList.remove('show'); document.body.style.overflow=''; } });
document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ document.querySelectorAll('.wh-modal.show').forEach(m=>m.classList.remove('show')); document.body.style.overflow=''; } });
</script>

@endsection