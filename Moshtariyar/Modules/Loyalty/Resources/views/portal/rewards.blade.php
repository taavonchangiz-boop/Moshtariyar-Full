@extends('layouts.customer_portal')
@section('title','جایزه‌های من')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-rewards-center.css') }}">
@php
    $typeLabels = \Modules\Loyalty\Entities\WheelPrize::TYPES;
    $statusLabels = [
        'pending' => ['در انتظار ارسال', 'is-warn', '#f59e0b'],
        'done' => ['ارسال شده', 'is-ok', '#10b981'],
        'partial' => ['ارسال ناقص', 'is-warn', '#f59e0b'],
        'failed' => ['ناموفق', 'is-bad', '#ef4444'],
        'internal' => ['ثبت داخل پنل', 'is-muted', '#64748b'],
    ];
@endphp
<div class="club-rewards-center-page">
    <section class="club-rewards-hero">
        <div>
            <span class="club-rewards-eyebrow">مرکز جایزه‌های باشگاه</span>
            <h1>همه بردها، کدهای تخفیف و پاداش‌های گردونه در یک جا 🎁</h1>
            <p>هر بار که گردونه را می‌چرخانید، نتیجه در این صفحه ثبت می‌شود. امتیاز، اعتبار کیف پول، کد تخفیف و وضعیت ارسال جایزه‌ها همیشه قابل پیگیری هستند.</p>
            <div class="club-rewards-actions">
                <a class="btn" href="{{ route('club.wheel') }}">رفتن به گردونه</a>
                <a class="btn btn-ghost" href="{{ route('club.coupons') }}">کدهای تخفیف</a>
                <a class="btn btn-ghost" href="{{ route('club.transactions') }}">دفتر امتیاز</a>
            </div>
        </div>
        <div class="club-rewards-score">
            <div><span>کل چرخش‌ها</span><b>@fa(number_format($stats['spins'] ?? 0))</b><small>نتیجه‌های ثبت‌شده</small></div>
            <div><span>کد تخفیف فعال</span><b>@fa(number_format($stats['active_coupons'] ?? 0))</b><small>آماده استفاده</small></div>
            <div><span>امتیاز برده‌شده</span><b>@fa(number_format($stats['points'] ?? 0))</b><small>از گردونه شانس</small></div>
        </div>
    </section>

    <section class="club-rewards-grid">
        <article class="club-rewards-card is-accent" style="--reward-color:#0ea5e9;">
            <header>
                <div>
                    <span>سابقه جایزه‌ها</span>
                    <h2>فهرست بردها و نتیجه‌های گردونه</h2>
                </div>
                <span class="club-rewards-badge">@fa(number_format($spins->total())) مورد</span>
            </header>

            <form method="get" class="club-rewards-filter">
                <div><label>جستجو</label><input name="search" value="{{ request('search') }}" placeholder="جستجو در عنوان جایزه یا کد تخفیف"></div>
                <div><label>نوع جایزه</label><select name="type"><option value="">همه</option>@foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach</select></div>
                <div><label>وضعیت ارسال</label><select name="delivery_status"><option value="">همه</option>@foreach($statusLabels as $key => $row)<option value="{{ $key }}" @selected(request('delivery_status') === $key)>{{ $row[0] }}</option>@endforeach</select></div>
                <button class="btn">اعمال</button>
                @if(request()->hasAny(['search','type','delivery_status']))<a class="btn btn-ghost" href="{{ route('club.rewards') }}">پاک کردن</a>@endif
            </form>

            <div class="club-rewards-list">
                @forelse($spins as $spin)
                    @php
                        $statusData = $statusLabels[$spin->delivery_status] ?? [$spin->deliveryStatusLabel(), 'is-muted', '#64748b'];
                        $statusText = $statusData[0];
                        $statusClass = $statusData[1];
                        $statusColor = $statusData[2];
                        $typeText = $typeLabels[$spin->prize_type] ?? 'جایزه';
                    @endphp
                    <article class="club-reward-center-row" style="--row-color:{{ $statusColor }};">
                        <div class="club-reward-center-icon">
                            @if($spin->prize_image)
                                <img src="{{ asset($spin->prize_image) }}" alt="{{ $spin->prize_title }}">
                            @else
                                🎁
                            @endif
                        </div>
                        <div>
                            <span class="club-rewards-chip">{{ $typeText }}</span>
                            <h3>{{ $spin->prize_title ?: 'جایزه گردونه' }}</h3>
                            <p>مقدار: @fa(number_format($spin->amount)) · زمان دریافت: @jdatetime($spin->created_at)</p>
                            @if($spin->coupon)
                                <div class="club-rewards-copy"><input value="{{ $spin->coupon->code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyRewardCode(this)">کپی</button></div>
                            @endif
                        </div>
                        <span class="club-rewards-badge {{ $statusClass }}">{{ $statusText }}</span>
                    </article>
                @empty
                    <div class="club-rewards-empty">جایزه‌ای با این شرایط پیدا نشد.</div>
                @endforelse
            </div>
            <div style="margin-top:1rem">{{ $spins->links() }}</div>
        </article>

        <aside class="club-rewards-card is-accent" style="--reward-color:#10b981;">
            <header>
                <div>
                    <span>خلاصه پاداش‌ها</span>
                    <h2>ارزش بردهای شما</h2>
                </div>
            </header>
            <div class="club-rewards-list">
                <article class="club-reward-summary-row" style="--row-color:#10b981;"><div class="club-reward-center-icon">★</div><div><h3>امتیاز از گردونه</h3><p>@fa(number_format($stats['points'] ?? 0)) امتیاز دریافت‌شده</p></div><span class="club-rewards-badge is-ok">امتیاز</span></article>
                <article class="club-reward-summary-row" style="--row-color:#0ea5e9;"><div class="club-reward-center-icon">💰</div><div><h3>کیف پول از گردونه</h3><p>@money($stats['wallet'] ?? 0) @unit اعتبار مستقیم</p></div><span class="club-rewards-badge">کیف پول</span></article>
                <article class="club-reward-summary-row" style="--row-color:#8b5cf6;"><div class="club-reward-center-icon">٪</div><div><h3>کدهای تخفیف گردونه</h3><p>@fa(number_format($stats['coupon_total'] ?? 0)) کد ساخته‌شده · @fa(number_format($stats['active_coupons'] ?? 0)) فعال</p></div><span class="club-rewards-badge">کد</span></article>
                <article class="club-reward-summary-row" style="--row-color:#f59e0b;"><div class="club-reward-center-icon">✓</div><div><h3>کدهای استفاده‌شده</h3><p>@fa(number_format($stats['used_coupons'] ?? 0)) کد تخفیف استفاده‌شده</p></div><span class="club-rewards-badge is-warn">مصرف</span></article>
            </div>
        </aside>
    </section>

    <section class="club-rewards-card is-accent" style="--reward-color:#8b5cf6;">
        <header>
            <div>
                <span>کدهای تخفیف گردونه</span>
                <h2>کدهای آماده استفاده در خرید</h2>
            </div>
            <a href="{{ route('club.coupons') }}">همه کدها ←</a>
        </header>
        <div class="club-rewards-list">
            @forelse($wheelCoupons as $coupon)
                @php
                    $isExpired = $coupon->expires_at && $coupon->expires_at->isPast();
                    $statusText = $coupon->used_at ? 'استفاده شده' : ($isExpired ? 'منقضی شده' : 'قابل استفاده');
                    $statusClass = $coupon->used_at ? 'is-muted' : ($isExpired ? 'is-warn' : 'is-ok');
                    $color = $coupon->used_at ? '#64748b' : ($isExpired ? '#f59e0b' : '#10b981');
                @endphp
                <article class="club-reward-center-row" style="--row-color:{{ $color }};">
                    <div class="club-reward-center-icon">٪</div>
                    <div>
                        <span class="club-rewards-chip">کد تخفیف</span>
                        <h3>{{ $coupon->title }}</h3>
                        <p>{{ $coupon->discount_type === 'percent' ? 'تخفیف درصدی' : 'تخفیف مبلغی' }} · @fa(number_format((float) $coupon->discount_value))</p>
                        <small>اعتبار: {{ $coupon->expires_at ? \Modules\Core\Support\Jalali::date($coupon->expires_at) : 'بدون تاریخ پایان' }}</small>
                        <div class="club-rewards-copy"><input value="{{ $coupon->code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyRewardCode(this)">کپی</button></div>
                    </div>
                    <span class="club-rewards-badge {{ $statusClass }}">{{ $statusText }}</span>
                </article>
            @empty
                <div class="club-rewards-empty">هنوز کد تخفیفی از گردونه دریافت نکرده‌اید.</div>
            @endforelse
        </div>
    </section>
</div>
<script>
function copyRewardCode(button) {
    const input = button.closest('.club-rewards-copy')?.querySelector('input');
    if (!input) return;
    input.select();
    document.execCommand('copy');
    button.textContent = 'کپی شد';
    setTimeout(() => button.textContent = 'کپی', 1200);
}
</script>
@endsection