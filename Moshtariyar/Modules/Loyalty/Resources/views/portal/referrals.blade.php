@extends('layouts.customer_portal')
@section('title','معرفی دوستان')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-referrals.css') }}">
@php
    $totalReferrals = $member->referralsMade()->where('level', 1)->count();
    $successfulReferrals = $member->referralsMade()->where('level', 1)->whereIn('status', ['registered', 'qualified', 'rewarded'])->count();
    $rewardedReferrals = $member->referralsMade()->where('level', 1)->where('status', 'rewarded')->count();
    $pointsReward = $member->referralsMade()->sum('reward_points');
    $walletReward = $member->referralsMade()->sum('reward_wallet');
    $statusLabels = ['registered'=>'ثبت‌نام‌شده','qualified'=>'دارای خرید','rewarded'=>'پاداش‌داده‌شده','cancelled'=>'لغوشده'];
    $channelLabels = ['direct'=>'مستقیم','whatsapp'=>'واتساپ','telegram'=>'تلگرام','instagram'=>'اینستاگرام','eitaa'=>'ایتا','bale'=>'بله','rubika'=>'روبیکا','sms'=>'پیامک'];
    $inviteText = 'به باشگاه مشتریان بپیوندید و از امتیازها، جایزه‌ها و کدهای تخفیف استفاده کنید: ' . $referralLink;
@endphp
<div class="club-referral-page">
    <section class="club-referral-hero">
        <div>
            <span class="club-referral-eyebrow">رشد ارگانیک باشگاه</span>
            <h1>دوستانتان را دعوت کنید و پاداش وفاداری بگیرید 🤝</h1>
            <p>لینک معرفی شما همیشه فعال است. هر ثبت‌نام با این لینک به‌عنوان زیرمجموعه شما ثبت می‌شود و در کمپین‌های فعال می‌تواند پاداش امتیازی یا کیف پولی ایجاد کند.</p>
            <div class="club-referral-actions">
                <a class="btn" href="{{ route('club.campaign.league') }}">لیگ دعوت</a>
                <a class="btn btn-ghost" href="{{ route('club.leaderboard') }}">برترین‌ها</a>
                <a class="btn btn-ghost" href="{{ route('club.card') }}">کارت عضویت</a>
            </div>
        </div>
        <div class="club-referral-score">
            <div><span>دعوت مستقیم</span><b>@fa(number_format($totalReferrals))</b><small>افراد ثبت‌شده با لینک شما</small></div>
            <div><span>دعوت موفق</span><b>@fa(number_format($successfulReferrals))</b><small>ثبت‌نام‌شده یا پاداش‌دار</small></div>
            <div><span>پاداش دریافت‌شده</span><b>@fa(number_format($rewardedReferrals))</b><small>دعوت‌های پاداش‌داده‌شده</small></div>
        </div>
    </section>

    <section class="club-referral-grid">
        <article class="club-referral-card is-accent" style="--referral-color:#0ea5e9;">
            <header>
                <div>
                    <span>لینک عمومی معرفی</span>
                    <h2>دعوت‌نامه اختصاصی شما</h2>
                </div>
                <span class="club-referral-badge">فعال</span>
            </header>
            <p>این لینک را برای دوستان خود بفرستید. اگر دوست شما با این لینک عضو شود، در پرونده معرفی‌های شما ثبت خواهد شد.</p>
            <div class="club-referral-copy-box">
                <label>کد معرف</label>
                <div class="club-referral-copy-row"><input class="ltr" value="{{ $member->referral_code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyReferralText(this)">کپی</button></div>
                <label>لینک معرفی</label>
                <div class="club-referral-copy-row"><input class="ltr" value="{{ $referralLink }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyReferralText(this)">کپی</button></div>
            </div>
            <div class="club-referral-share-grid">
                <a class="btn btn-ghost" target="_blank" href="https://wa.me/?text={{ urlencode($inviteText) }}">واتساپ</a>
                <a class="btn btn-ghost" target="_blank" href="https://t.me/share/url?url={{ urlencode($referralLink) }}&text={{ urlencode('دعوت به باشگاه مشتریان') }}">تلگرام</a>
                <button class="btn btn-ghost" type="button" onclick="copyReferralOnly(this)">کپی متن دعوت</button>
                <button class="btn btn-ghost" type="button" onclick="openReferralShare('bale')">بله</button>
                <button class="btn btn-ghost" type="button" onclick="openReferralShare('rubika')">روبیکا</button>
                <button class="btn btn-ghost" type="button" onclick="openReferralShare('eitaa')">ایتا</button>
            </div>
        </article>

        <aside class="club-referral-card is-accent" style="--referral-color:#10b981;">
            <header>
                <div>
                    <span>پاداش معرفی</span>
                    <h2>خلاصه دستاورد شما</h2>
                </div>
            </header>
            <div class="club-referral-list">
                <article class="club-referral-tip-row" style="--row-color:#0ea5e9;"><div class="club-referral-icon">★</div><div><h3>پاداش امتیازی</h3><p>@fa(number_format($pointsReward)) امتیاز از معرفی‌ها</p></div><span class="club-referral-badge">امتیاز</span></article>
                <article class="club-referral-tip-row" style="--row-color:#10b981;"><div class="club-referral-icon">💰</div><div><h3>پاداش کیف پول</h3><p>@money($walletReward) @unit اعتبار دریافتی</p></div><span class="club-referral-badge is-ok">کیف پول</span></article>
                <article class="club-referral-tip-row" style="--row-color:#8b5cf6;"><div class="club-referral-icon">↗</div><div><h3>پیشنهاد رشد</h3><p>لینک عمومی و لینک‌های کمپینی را در کانال‌های مختلف منتشر کنید تا نرخ دعوت بهتر شود.</p></div><span class="club-referral-badge">راهنما</span></article>
            </div>
        </aside>
    </section>

    @if(($campaigns ?? collect())->count())
        <section class="club-referral-card is-accent" style="--referral-color:#8b5cf6; margin-bottom:1rem">
            <header>
                <div>
                    <span>لینک‌های کمپینی</span>
                    <h2>دعوت‌نامه‌های آماده اشتراک‌گذاری</h2>
                </div>
                <a href="{{ route('club.campaign.league') }}">لیگ دعوت ←</a>
            </header>
            <div class="club-referral-list">
                @foreach($campaigns as $campaign)
                    <article class="club-referral-campaign-row" style="--row-color:#8b5cf6; align-items:start">
                        <div class="club-referral-icon">📣</div>
                        <div>
                            <h3>{{ $campaign->title }}</h3>
                            <p>{{ $campaign->description ?: 'کمپین فعال معرفی دوستان' }}</p>
                            <div class="club-referral-campaign-links">
                                @forelse($campaign->channels->where('is_active', true) as $channel)
                                    @php $link = route('club.campaign.referral', ['campaign'=>$campaign->slug, 'channel'=>$channel->channel, 'code'=>$member->referral_code]); @endphp
                                    <label>{{ $channel->label }}</label>
                                    <div class="club-referral-copy-row"><input class="ltr" value="{{ $link }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyReferralText(this)">کپی</button><a class="btn btn-ghost" target="_blank" href="{{ $link }}">بازکردن</a></div>
                                @empty
                                    <div class="club-referral-empty">برای این کمپین کانال فعالی تعریف نشده است.</div>
                                @endforelse
                            </div>
                        </div>
                        <span class="club-referral-badge is-ok">فعال</span>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="club-referral-card is-accent" style="--referral-color:#f59e0b;">
        <header>
            <div>
                <span>افراد معرفی‌شده</span>
                <h2>زیرمجموعه‌ها و وضعیت پاداش</h2>
            </div>
            <span class="club-referral-badge">@fa(number_format($referrals->total())) نفر</span>
        </header>
        <div class="club-referral-list">
            @forelse($referrals as $referral)
                <article class="club-referral-row" style="--row-color:{{ $referral->status === 'cancelled' ? '#64748b' : '#10b981' }};">
                    <div class="club-referral-icon">👤</div>
                    <div>
                        <h3>{{ $referral->referredCustomer?->full_name ?? 'مشتری جدید' }}</h3>
                        <p>موبایل: <span class="ltr">{{ $referral->referredCustomer?->phone ?? '—' }}</span> · کمپین: {{ $referral->campaign?->title ?? 'عمومی' }}</p>
                        <small>کانال: {{ $channelLabels[$referral->campaign_channel] ?? ($referral->campaign_channel ?? '—') }} · پاداش امتیاز: @fa(number_format($referral->reward_points)) · پاداش کیف پول: @money($referral->reward_wallet) @unit · تاریخ: @jdate($referral->created_at)</small>
                    </div>
                    <span class="club-referral-badge {{ $referral->status === 'cancelled' ? 'is-muted' : 'is-ok' }}">{{ $statusLabels[$referral->status] ?? $referral->status }}</span>
                </article>
            @empty
                <div class="club-referral-empty">هنوز زیرمجموعه‌ای ثبت نشده است. لینک معرفی را برای دوستانتان بفرستید.</div>
            @endforelse
        </div>
        <div style="margin-top:1rem">{{ $referrals->links() }}</div>
    </section>
</div>
<script>
const referralInviteText = @json($inviteText);
const referralLink = @json($referralLink);
function copyReferralText(button){const input=button.closest('.club-referral-copy-row')?.querySelector('input'); if(!input)return; input.select(); document.execCommand('copy'); button.textContent='کپی شد'; setTimeout(()=>button.textContent='کپی',1200);}
function copyReferralOnly(button){navigator.clipboard?.writeText(referralInviteText).catch(()=>{}); button.textContent='کپی شد'; setTimeout(()=>button.textContent='کپی متن دعوت',1200);}
function openReferralShare(channel){navigator.clipboard?.writeText(referralLink).catch(()=>{}); const encodedLink=encodeURIComponent(referralLink); const encodedText=encodeURIComponent(referralInviteText); const urls={bale:'https://ble.ir/share/url?url='+encodedLink+'&text='+encodedText,rubika:'https://rubika.ir/share?url='+encodedLink+'&text='+encodedText,eitaa:'https://eitaa.com/share/url?url='+encodedLink+'&text='+encodedText}; if(urls[channel]) window.open(urls[channel], '_blank');}
</script>
@endsection