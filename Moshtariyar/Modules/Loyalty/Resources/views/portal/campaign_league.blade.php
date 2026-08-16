@extends('layouts.customer_portal')
@section('title','لیگ دعوت')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-campaign-league.css') }}">
@php
    $conversion = ($stats['clicks'] ?? 0) > 0 ? round(($stats['referrals'] ?? 0) * 100 / max(1, $stats['clicks']), 1) : 0;
@endphp
<div class="club-league-page">
    <section class="club-league-hero">
        <div>
            <span class="club-league-eyebrow">رقابت معرفی دوستان</span>
            <h1>لیگ دعوت؛ رقابت برای رشد باشگاه و دریافت پاداش 🏆</h1>
            <p>در کمپین‌های فعال، هر دعوت موفق شما در جدول لیگ ثبت می‌شود. لینک کمپین را منتشر کنید، دوستانتان را عضو کنید و جایگاه خود را بالا ببرید.</p>
            <div class="club-league-actions">
                <a class="btn" href="{{ route('club.referrals') }}">دریافت لینک دعوت</a>
                <a class="btn btn-ghost" href="{{ route('club.leaderboard') }}">برترین‌های باشگاه</a>
                <a class="btn btn-ghost" href="{{ route('club.card') }}">کارت عضویت</a>
            </div>
        </div>
        <div class="club-league-score">
            <div><span>رتبه شما</span><b>{{ $myRank ? \Modules\Core\Support\Num::fa(number_format($myRank)) : '—' }}</b><small>در کمپین انتخاب‌شده</small></div>
            <div><span>دعوت موفق شما</span><b>@fa(number_format($myCount))</b><small>برای کمپین فعلی</small></div>
            <div><span>کمپین‌های فعال</span><b>@fa(number_format($stats['campaigns'] ?? 0))</b><small>قابل رقابت</small></div>
        </div>
    </section>

    <section class="club-league-card is-accent" style="--league-color:#0ea5e9; margin-bottom:1rem">
        <header>
            <div>
                <span>انتخاب کمپین</span>
                <h2>کدام رقابت را می‌خواهید ببینید؟</h2>
            </div>
        </header>
        <form method="get" class="club-league-selector">
            <div>
                <label>کمپین فعال</label>
                <select name="campaign" onchange="this.form.submit()">
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->slug }}" @selected($selected?->id === $campaign->id)>{{ $campaign->title }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn">نمایش</button>
        </form>
    </section>

    @if($selected)
        <section class="club-league-grid">
            <article class="club-league-card is-accent" style="--league-color:#f59e0b;">
                <header>
                    <div>
                        <span>{{ $selected->title }}</span>
                        <h2>سکوی برترین معرف‌ها</h2>
                    </div>
                    <span class="club-league-badge">@fa(number_format($stats['top_count'] ?? 0)) دعوت برتر</span>
                </header>
                @if($topThree->isNotEmpty())
                    <div class="club-league-podium">
                        @foreach($topThree as $index => $leader)
                            @php
                                $name = $leader->customer?->full_name ?: 'عضو باشگاه';
                                $avatar = $leader->customer?->meta['avatar'] ?? null;
                                $colors = ['#f59e0b', '#94a3b8', '#b45309'];
                            @endphp
                            <article class="club-league-podium-card {{ $index === 0 ? 'is-first' : '' }}" style="--podium-color: {{ $colors[$index] ?? '#0ea5e9' }}; order: {{ $index === 0 ? 2 : ($index === 1 ? 1 : 3) }};">
                                <div class="club-league-podium-rank">@fa($index + 1)</div>
                                <div class="club-league-avatar">
                                    @if($avatar)<img src="{{ asset($avatar) }}" alt="{{ $name }}">@else{{ mb_substr($name, 0, 1) }}@endif
                                </div>
                                <h3>{{ $name }}</h3>
                                <p>{{ $leader->tier?->name ?? 'بدون سطح' }}</p>
                                <small>@fa(number_format($leader->campaign_referrals_count)) دعوت موفق</small>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="club-league-empty">هنوز کسی در این کمپین دعوت موفق ثبت نکرده است. شما می‌توانید اولین نفر باشید.</div>
                @endif
            </article>

            <aside class="club-league-card is-accent" style="--league-color:#10b981;">
                <header>
                    <div>
                        <span>آمار کمپین</span>
                        <h2>وضعیت رقابت فعلی</h2>
                    </div>
                </header>
                <div class="club-league-list">
                    <article class="club-league-tip-row" style="--row-color:#0ea5e9;"><div class="club-league-icon">👁</div><div><h3>کلیک‌ها</h3><p>@fa(number_format($stats['clicks'] ?? 0)) ورود از لینک‌های کمپین</p></div><span class="club-league-badge">بازدید</span></article>
                    <article class="club-league-tip-row" style="--row-color:#10b981;"><div class="club-league-icon">✓</div><div><h3>دعوت‌های موفق</h3><p>@fa(number_format($stats['referrals'] ?? 0)) ثبت‌نام یا معرفی ثبت‌شده</p></div><span class="club-league-badge is-ok">دعوت</span></article>
                    <article class="club-league-tip-row" style="--row-color:#8b5cf6;"><div class="club-league-icon">٪</div><div><h3>نرخ تبدیل</h3><p>@fa($conversion)٪ از کلیک‌ها به معرفی تبدیل شده‌اند.</p></div><span class="club-league-badge">تبدیل</span></article>
                    <article class="club-league-tip-row" style="--row-color:#f59e0b;"><div class="club-league-icon">⏳</div><div><h3>زمان باقی‌مانده</h3><p>{{ $stats['days_left'] !== null ? \Modules\Core\Support\Num::fa(number_format($stats['days_left'])) . ' روز' : 'بدون زمان پایان' }}</p></div><span class="club-league-badge is-muted">زمان</span></article>
                </div>
            </aside>
        </section>

        <section class="club-league-grid-reverse">
            <article class="club-league-card is-accent" style="--league-color:#8b5cf6;">
                <header>
                    <div>
                        <span>جدول رقابت</span>
                        <h2>همه معرف‌های فعال کمپین</h2>
                    </div>
                    <span class="club-league-badge">@fa(number_format($leaders->count())) نفر</span>
                </header>
                <div class="club-league-list">
                    @forelse($leaders as $index => $leader)
                        @php
                            $isMe = $leader->id === $member->id;
                            $name = $leader->customer?->full_name ?: 'عضو باشگاه';
                            $avatar = $leader->customer?->meta['avatar'] ?? null;
                            $progress = ($stats['top_count'] ?? 0) > 0 ? min(100, round($leader->campaign_referrals_count * 100 / max(1, $stats['top_count']))) : 0;
                        @endphp
                        <article class="club-league-row {{ $isMe ? 'is-me' : '' }}" style="--row-color:{{ $isMe ? '#0ea5e9' : '#8b5cf6' }};">
                            <div class="club-league-rank">@fa($index + 1)</div>
                            <div class="club-league-avatar">@if($avatar)<img src="{{ asset($avatar) }}" alt="{{ $name }}">@else{{ mb_substr($name, 0, 1) }}@endif</div>
                            <div>
                                <h3>{{ $name }}</h3>
                                <p>دعوت موفق: @fa(number_format($leader->campaign_referrals_count)) · سطح: {{ $leader->tier?->name ?? 'بدون سطح' }}</p>
                                <div class="club-league-progress"><span style="--progress: {{ $progress }}%;"></span></div>
                            </div>
                            <span class="club-league-badge {{ $isMe ? 'is-ok' : 'is-muted' }}">{{ $isMe ? 'شما' : 'معرف' }}</span>
                        </article>
                    @empty
                        <div class="club-league-empty">هنوز رتبه‌ای برای این کمپین ثبت نشده است.</div>
                    @endforelse
                </div>
            </article>

            <aside class="club-league-card is-accent" style="--league-color:#0ea5e9;">
                <header>
                    <div>
                        <span>کانال‌های فعال</span>
                        <h2>از کجا دعوت کنید؟</h2>
                    </div>
                </header>
                <div class="club-league-list">
                    @forelse($selectedChannels as $channel)
                        @php $link = route('club.campaign.referral', ['campaign'=>$selected->slug, 'channel'=>$channel->channel, 'code'=>$member->referral_code]); @endphp
                        <article class="club-league-channel-row" style="--row-color:#0ea5e9;">
                            <div class="club-league-icon">📣</div>
                            <div><h3>{{ $channel->label }}</h3><p class="ltr">{{ $link }}</p></div>
                            <button class="club-league-badge" type="button" onclick="copyLeagueLink(this)">کپی</button>
                            <input type="hidden" value="{{ $link }}">
                        </article>
                    @empty
                        <div class="club-league-empty">برای این کمپین کانال فعالی ثبت نشده است.</div>
                    @endforelse
                </div>
            </aside>
        </section>
    @else
        <div class="club-league-card"><div class="club-league-empty">کمپین فعالی وجود ندارد.</div></div>
    @endif
</div>
<script>
function copyLeagueLink(button) {
    const input = button.parentElement.querySelector('input');
    if (!input) return;
    navigator.clipboard?.writeText(input.value).catch(() => {});
    button.textContent = 'کپی شد';
    setTimeout(() => button.textContent = 'کپی', 1200);
}
</script>
@endsection