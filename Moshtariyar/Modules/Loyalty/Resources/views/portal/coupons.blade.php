@extends('layouts.customer_portal')
@section('title','کدهای تخفیف')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-coupons.css') }}">
@php
    $allCouponsQuery = \Modules\Loyalty\Entities\LoyaltyCoupon::where('member_id', $member->id);
    $activeCount = (clone $allCouponsQuery)->whereNull('used_at')->where(function($q){ $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()); })->count();
    $usedCount = (clone $allCouponsQuery)->whereNotNull('used_at')->count();
    $expiredCount = (clone $allCouponsQuery)->whereNull('used_at')->whereNotNull('expires_at')->where('expires_at', '<', now())->count();
    $sourceLabels = ['wheel' => 'گردونه شانس', 'points_redemption' => 'تبدیل امتیاز', 'admin' => 'هدیه عمومی', 'manual' => 'ثبت دستی'];
@endphp
<div class="club-coupons-page">
    <section class="club-coupons-hero">
        <div>
            <span class="club-coupons-eyebrow">مرکز کدهای تخفیف</span>
            <h1>امتیازها را به کد تخفیف تبدیل کنید و خرید بعدی را جذاب‌تر بسازید</h1>
            <p>در این صفحه می‌توانید با امتیازهای باشگاه کد تخفیف اختصاصی بسازید، وضعیت کدهای فعال را ببینید و کدهای آماده استفاده را کپی کنید.</p>
            <div class="club-coupons-actions">
                <a class="btn" href="{{ route('club.transactions') }}">دفتر امتیاز</a>
                <a class="btn btn-ghost" href="{{ route('club.rewards') }}">جایزه‌های من</a>
                <a class="btn btn-ghost" href="{{ route('club.orders') }}">خریدهای من</a>
            </div>
        </div>
        <div class="club-coupons-score">
            <div><span>امتیاز فعلی</span><b>@fa(number_format($member->points))</b><small>قابل تبدیل به کد تخفیف</small></div>
            <div><span>کد فعال</span><b>@fa(number_format($activeCount))</b><small>آماده استفاده در خرید</small></div>
            <div><span>قانون فعال</span><b>@fa(number_format($rules->count()))</b><small>روش‌های تبدیل امتیاز</small></div>
        </div>
    </section>

    <section class="club-coupons-grid">
        <article class="club-coupon-card is-accent" style="--coupon-color:#0ea5e9;">
            <header>
                <div>
                    <span>تبدیل امتیاز</span>
                    <h2>ساخت کد تخفیف اختصاصی</h2>
                </div>
                <span class="club-coupon-badge">@fa(number_format($rules->count())) گزینه</span>
            </header>
            <div class="club-coupon-list">
                @forelse($rules as $rule)
                    @php
                        $canRedeem = $member->points >= $rule->points_required;
                        $percent = min(100, (int) floor(((int) $member->points / max(1, (int) $rule->points_required)) * 100));
                        $discountText = $rule->discount_type === 'percent'
                            ? \Modules\Core\Support\Num::fa((float) $rule->discount_value) . '٪'
                            : \Modules\Core\Support\Money::show($rule->discount_value) . ' ' . \Modules\Core\Support\Money::unitLabel();
                    @endphp
                    <article class="club-coupon-rule-row" style="--row-color:{{ $canRedeem ? '#10b981' : '#f59e0b' }};">
                        <div class="club-coupon-icon">٪</div>
                        <div>
                            <span class="club-coupon-chip">{{ $discountText }}</span>
                            <h3>{{ $rule->title }}</h3>
                            <p>{{ $rule->description ?: 'با این قانون می‌توانید امتیاز خود را به کد تخفیف اختصاصی تبدیل کنید.' }}</p>
                            <small>امتیاز لازم: @fa(number_format($rule->points_required)) · اعتبار: @fa($rule->expires_days) روز · استفاده: @fa($rule->usage_limit) بار</small>
                            <div class="club-coupon-progress"><span style="--progress: {{ $percent }}%;"></span></div>
                        </div>
                        <div class="club-coupon-actions">
                            <span class="club-coupon-badge {{ $canRedeem ? 'is-ok' : 'is-warn' }}">{{ $canRedeem ? 'قابل ساخت' : 'امتیاز ناکافی' }}</span>
                            <form method="post" action="{{ route('club.coupons.redeem', $rule) }}" onsubmit="return confirm('امتیاز شما کسر و کد تخفیف ساخته شود؟')">
                                @csrf
                                <button class="btn" @disabled(! $canRedeem)>ساخت کد</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="club-coupon-empty">فعلاً قانون فعالی برای تبدیل امتیاز تعریف نشده است.</div>
                @endforelse
            </div>
        </article>

        <aside class="club-coupon-card is-accent" style="--coupon-color:#10b981;">
            <header>
                <div>
                    <span>وضعیت کدها</span>
                    <h2>خلاصه کدهای تخفیف</h2>
                </div>
            </header>
            <div class="club-coupon-list">
                <article class="club-coupon-guide-row" style="--row-color:#10b981;"><div class="club-coupon-icon">✓</div><div><h3>کدهای فعال</h3><p>@fa(number_format($activeCount)) کد تخفیف آماده استفاده دارید.</p></div><span class="club-coupon-badge is-ok">فعال</span></article>
                <article class="club-coupon-guide-row" style="--row-color:#64748b;"><div class="club-coupon-icon">●</div><div><h3>کدهای استفاده‌شده</h3><p>@fa(number_format($usedCount)) کد قبلاً استفاده شده است.</p></div><span class="club-coupon-badge is-muted">استفاده‌شده</span></article>
                <article class="club-coupon-guide-row" style="--row-color:#f59e0b;"><div class="club-coupon-icon">!</div><div><h3>کدهای منقضی‌شده</h3><p>@fa(number_format($expiredCount)) کد از تاریخ اعتبار گذشته است.</p></div><span class="club-coupon-badge is-warn">منقضی</span></article>
            </div>
        </aside>
    </section>

    <section class="club-coupon-card is-accent" style="--coupon-color:#8b5cf6;">
        <header>
            <div>
                <span>کدهای تخفیف من</span>
                <h2>فهرست کدهای ساخته‌شده</h2>
            </div>
            <span class="club-coupon-badge">@fa(number_format($coupons->total())) کد</span>
        </header>
        <div class="club-coupon-list">
            @forelse($coupons as $coupon)
                @php
                    $isExpired = $coupon->expires_at && $coupon->expires_at->isPast();
                    $status = $coupon->used_at ? 'استفاده شده' : ($isExpired ? 'منقضی شده' : 'فعال');
                    $statusClass = $coupon->used_at ? 'is-muted' : ($isExpired ? 'is-warn' : 'is-ok');
                    $color = $coupon->used_at ? '#64748b' : ($isExpired ? '#f59e0b' : '#10b981');
                    $discount = $coupon->discount_type === 'percent'
                        ? \Modules\Core\Support\Num::fa((float) $coupon->discount_value) . '٪'
                        : \Modules\Core\Support\Money::show($coupon->discount_value) . ' ' . \Modules\Core\Support\Money::unitLabel();
                @endphp
                <article class="club-coupon-row" style="--row-color:{{ $color }};">
                    <div class="club-coupon-icon">٪</div>
                    <div>
                        <span class="club-coupon-chip">{{ $sourceLabels[$coupon->source] ?? ($coupon->source ?: 'باشگاه') }}</span>
                        <h3>{{ $coupon->title }}</h3>
                        <p>تخفیف: {{ $discount }} · امتیاز مصرفی: @fa(number_format($coupon->points_spent ?? 0))</p>
                        <small>پایان اعتبار: {{ $coupon->expires_at ? \Modules\Core\Support\Jalali::date($coupon->expires_at) : 'بدون تاریخ پایان' }}</small>
                        <div class="club-coupon-copy"><input value="{{ $coupon->code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyCouponCode(this)">کپی</button></div>
                    </div>
                    <span class="club-coupon-badge {{ $statusClass }}">{{ $status }}</span>
                </article>
            @empty
                <div class="club-coupon-empty">هنوز کد تخفیفی برای شما ساخته نشده است.</div>
            @endforelse
        </div>
        <div style="margin-top:1rem">{{ $coupons->links() }}</div>
    </section>
</div>
<script>
function copyCouponCode(button){const input=button.closest('.club-coupon-copy')?.querySelector('input'); if(!input)return; input.select(); document.execCommand('copy'); button.textContent='کپی شد'; setTimeout(()=>button.textContent='کپی',1200);}
</script>
@endsection