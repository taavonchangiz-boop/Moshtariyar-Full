@extends('layouts.customer_portal')
@section('title','کارت عضویت')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-card.css') }}">
@php
    $meta = $member->customer->meta ?: [];
    $avatar = $meta['avatar'] ?? null;
    $customerName = $member->customer->full_name ?: 'مشتری عزیز';
    $initial = mb_substr($customerName, 0, 1);
    $tierName = $member->tier?->name ?? 'عضو باشگاه';
    $joinedAt = $member->joined_at ?: $member->created_at;
@endphp
<div class="club-card-page">
    <section class="club-card-hero">
        <div>
            <span class="club-card-eyebrow">کارت دیجیتال عضویت</span>
            <h1>کارت باشگاه، کد معرف و وضعیت وفاداری شما در یک جا 💳</h1>
            <p>این کارت برای نمایش سریع وضعیت عضویت، امتیاز، کیف پول، سطح وفاداری و لینک معرفی شما طراحی شده است. می‌توانید کد معرف را کپی کنید یا کارت را هنگام مراجعه به فروشگاه نمایش دهید.</p>
            <div class="club-card-actions">
                <a class="btn" href="{{ route('club.referrals') }}">دعوت دوستان</a>
                <a class="btn btn-ghost" href="{{ route('club.profile', ['edit' => 1]) }}">ویرایش پروفایل</a>
                <button class="btn btn-ghost" type="button" onclick="window.print()">چاپ کارت</button>
            </div>
        </div>
        <div class="club-card-score">
            <div><span>امتیاز فعلی</span><b>@fa(number_format($member->points))</b><small>قابل تبدیل به کد تخفیف</small></div>
            <div><span>کیف پول</span><b>@money($member->wallet_balance) @unit</b><small>اعتبار فعلی شما</small></div>
            <div><span>سطح عضویت</span><b>{{ $tierName }}</b><small>جایگاه وفاداری شما</small></div>
        </div>
    </section>

    <section class="club-card-grid">
        <article class="club-card-panel is-accent" style="--card-color:#0ea5e9;">
            <header>
                <div>
                    <span>کارت عضویت</span>
                    <h2>نمای کارت دیجیتال</h2>
                </div>
            </header>
            <div class="club-card-visual">
                <div class="club-card-top">
                    <div class="club-card-member">
                        <div class="club-card-avatar">
                            @if($avatar)
                                <img src="{{ asset($avatar) }}" alt="{{ $customerName }}">
                            @else
                                {{ $initial }}
                            @endif
                        </div>
                        <div>
                            <h2>{{ $customerName }}</h2>
                            <p>{{ $tierName }}</p>
                        </div>
                    </div>
                    <span>مشتری‌یار</span>
                </div>

                <div class="club-card-middle">
                    <div><span>کد عضویت</span><b class="ltr">MY-{{ str_pad((string) $member->id, 6, '0', STR_PAD_LEFT) }}</b></div>
                    <div><span>کد معرف</span><b class="ltr">{{ $member->referral_code }}</b></div>
                    <div><span>امتیاز</span><b>@fa(number_format($member->points))</b></div>
                    <div><span>عضویت از</span><b>{{ $joinedAt ? \Modules\Core\Support\Jalali::date($joinedAt) : '—' }}</b></div>
                </div>

                <div class="club-card-bottom">
                    <div class="club-card-qr-wrap">
                        <div class="club-card-qr" aria-label="کد پاسخ سریع کارت عضویت">
                            @for($i = 1; $i <= 81; $i++)<i></i>@endfor
                        </div>
                        <div>
                            <b>کد سریع عضویت</b>
                            <small class="ltr">{{ $member->referral_code }}</small>
                        </div>
                    </div>
                    <small>برای استفاده حضوری، این کارت یا کد معرف را به فروشگاه نشان دهید.</small>
                </div>
            </div>
        </article>

        <article class="club-card-panel is-accent" style="--card-color:#10b981;">
            <header>
                <div>
                    <span>اشتراک‌گذاری</span>
                    <h2>لینک معرفی شما</h2>
                </div>
            </header>
            <p class="muted">این لینک را برای دوستان خود بفرستید. ثبت‌نام با این لینک به‌عنوان زیرمجموعه شما ثبت می‌شود.</p>
            <label>کد معرف</label>
            <div class="club-copy-box"><input class="ltr" value="{{ $member->referral_code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyClubCardText(this)">کپی</button></div>
            <label>لینک معرفی</label>
            <div class="club-copy-box"><input class="ltr" value="{{ $referralLink }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyClubCardText(this)">کپی</button></div>
            <div class="club-card-actions club-card-share-actions">
                <a class="btn btn-ghost" target="_blank" href="https://wa.me/?text={{ urlencode($referralLink) }}">اشتراک در واتساپ</a>
                <a class="btn btn-ghost" target="_blank" href="https://t.me/share/url?url={{ urlencode($referralLink) }}&text={{ urlencode('لینک عضویت من در باشگاه مشتریان') }}">اشتراک در تلگرام</a>
                <button class="btn btn-ghost" type="button" onclick="shareClubCardLink('bale')">اشتراک در بله</button>
                <button class="btn btn-ghost" type="button" onclick="shareClubCardLink('rubika')">اشتراک در روبیکا</button>
                <button class="btn btn-ghost" type="button" onclick="shareClubCardLink('eitaa')">اشتراک در ایتا</button>
            </div>

            <div class="club-card-list" style="margin-top:1rem">
                <article class="club-card-row" style="--row-color:#0ea5e9;"><div class="club-card-row-icon">🛍️</div><div><h3>تعداد خریدها</h3><p>@fa(number_format($stats['orders'])) سفارش ثبت‌شده</p></div><span class="club-card-badge">خرید</span></article>
                <article class="club-card-row" style="--row-color:#10b981;"><div class="club-card-row-icon">🤝</div><div><h3>زیرمجموعه‌ها</h3><p>@fa(number_format($stats['referrals'])) معرفی مستقیم</p></div><span class="club-card-badge">دعوت</span></article>
                <article class="club-card-row" style="--row-color:#8b5cf6;"><div class="club-card-row-icon">٪</div><div><h3>کد تخفیف فعال</h3><p>@fa(number_format($stats['coupons'])) کد قابل استفاده</p></div><span class="club-card-badge">کد</span></article>
            </div>
        </article>
    </section>

    <section class="club-card-grid">
        <article class="club-card-panel is-accent" style="--card-color:#f59e0b;">
            <header><div><span>جایزه‌های اخیر</span><h2>آخرین بردها و پاداش‌ها</h2></div><a href="{{ route('club.rewards') }}">همه جایزه‌ها ←</a></header>
            <div class="club-card-list">
                @forelse($latestRewards as $reward)
                    <article class="club-card-row" style="--row-color:#f59e0b;">
                        <div class="club-card-row-icon">🎁</div>
                        <div><h3>{{ $reward->prize_title ?: 'جایزه گردونه' }}</h3><p>{{ $reward->coupon ? 'کد تخفیف: '.$reward->coupon->code : $reward->deliveryStatusLabel() }}</p><small>@jdatetime($reward->created_at)</small></div>
                        <span class="club-card-badge">جایزه</span>
                    </article>
                @empty
                    <div class="club-card-empty">هنوز جایزه‌ای دریافت نکرده‌اید.</div>
                @endforelse
            </div>
        </article>

        <article class="club-card-panel is-accent" style="--card-color:#8b5cf6;">
            <header><div><span>کدهای تخفیف فعال</span><h2>آماده استفاده در خرید</h2></div><a href="{{ route('club.coupons') }}">همه کدها ←</a></header>
            <div class="club-card-list">
                @forelse($activeCoupons as $coupon)
                    <article class="club-card-row" style="--row-color:#8b5cf6;">
                        <div class="club-card-row-icon">٪</div>
                        <div><h3>{{ $coupon->title }}</h3><p>کد: <span class="ltr">{{ $coupon->code }}</span></p><small>اعتبار: {{ $coupon->expires_at ? \Modules\Core\Support\Jalali::date($coupon->expires_at) : 'بدون تاریخ پایان' }}</small></div>
                        <span class="club-card-badge">فعال</span>
                    </article>
                @empty
                    <div class="club-card-empty">کد تخفیف فعالی ندارید. می‌توانید از امتیازها کد تخفیف بسازید.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
<script>
function copyClubCardText(button) {
    const input = button.closest('.club-copy-box')?.querySelector('input');
    if (!input) return;
    input.select();
    document.execCommand('copy');
    button.textContent = 'کپی شد';
    setTimeout(() => button.textContent = 'کپی', 1200);
}
function shareClubCardLink(channel) {
    const link = @json($referralLink);
    const text = 'لینک عضویت من در باشگاه مشتریان: ' + link;
    navigator.clipboard?.writeText(link).catch(() => {});
    const encodedLink = encodeURIComponent(link);
    const encodedText = encodeURIComponent(text);
    const urls = {
        bale: 'https://ble.ir/share/url?url=' + encodedLink + '&text=' + encodedText,
        rubika: 'https://rubika.ir/share?url=' + encodedLink + '&text=' + encodedText,
        eitaa: 'https://eitaa.com/share/url?url=' + encodedLink + '&text=' + encodedText
    };
    if (urls[channel]) window.open(urls[channel], '_blank');
}
</script>
@endsection