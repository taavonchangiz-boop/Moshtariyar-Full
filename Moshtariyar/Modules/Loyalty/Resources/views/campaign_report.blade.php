@extends('layouts.app')
@section('title','گزارش کمپین')
@section('heading','گزارش کمپین: '.$campaign->title)
@section('subtitle','تحلیل کلیک، ثبت‌نام، کانال‌های جذب، معرف‌های برتر و پاداش‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('css/loyalty-campaign-report.css') }}">

@php
    $statusLabels = [
        'draft' => ['پیش‌نویس', 'is-muted', '#64748b'],
        'active' => ['فعال', 'is-ok', '#10b981'],
        'paused' => ['متوقف', 'is-warn', '#f59e0b'],
        'finished' => ['پایان‌یافته', 'is-muted', '#64748b'],
        'archived' => ['بایگانی', 'is-muted', '#64748b'],
    ];
    [$campaignStatusText, $campaignStatusClass, $campaignStatusColor] = $statusLabels[$campaign->status] ?? [$campaign->status ?: 'نامشخص', 'is-muted', '#64748b'];
    $rewardStatusLabels = [
        'pending' => ['در انتظار', 'is-warn', '#f59e0b'],
        'released' => ['آزادشده', 'is-ok', '#10b981'],
        'frozen' => ['فریز', 'is-muted', '#64748b'],
        'cancelled' => ['لغو', 'is-bad', '#ef4444'],
    ];
    $safeClicks = max(1, (int) ($stats['clicks'] ?? 0));
    $releasedRatio = (($stats['rewards_pending'] ?? 0) + ($stats['rewards_released'] ?? 0)) > 0
        ? round(($stats['rewards_released'] ?? 0) * 100 / max(1, (($stats['rewards_pending'] ?? 0) + ($stats['rewards_released'] ?? 0))), 1)
        : 0;
@endphp

<div class="campaign-report-page">
    <section class="campaign-report-hero">
        <div class="campaign-report-hero-main">
            <span class="campaign-report-eyebrow">گزارش عملکرد کمپین</span>
            <h2>{{ $campaign->title }}</h2>
            <p>{{ $campaign->description ?: 'در این صفحه عملکرد کانال‌ها، کلیک‌ها، معرفی‌ها، نرخ تبدیل، معرف‌های برتر و وضعیت پاداش‌های کمپین بررسی می‌شود.' }}</p>
            <div class="campaign-report-actions">
                <a class="btn btn-ghost" href="{{ url('/app/loyalty/campaigns') }}">بازگشت به کمپین‌ها</a>
                <a class="btn btn-ghost" href="{{ url('/app/loyalty/settings') }}">تنظیمات پاداش</a>
                @if($campaign->status !== 'archived')
                    <form method="post" action="{{ url('/app/loyalty/campaigns/'.$campaign->id.'/toggle') }}">@csrf<button class="btn">تغییر وضعیت</button></form>
                @endif
            </div>
        </div>
        <div class="campaign-report-hero-metrics">
            <div><span>وضعیت کمپین</span><b>{{ $campaignStatusText }}</b><small>{{ $campaign->type ?: 'نوع ثبت نشده' }}</small></div>
            <div><span>نرخ تبدیل</span><b>@fa($conversion)٪</b><small>ثبت‌نام نسبت به کلیک</small></div>
            <div><span>شروع</span><b>{{ $campaign->starts_at ? \Modules\Core\Support\Jalali::date($campaign->starts_at) : 'بدون زمان' }}</b><small>زمان آغاز کمپین</small></div>
            <div><span>پایان</span><b>{{ $campaign->ends_at ? \Modules\Core\Support\Jalali::date($campaign->ends_at) : 'بدون زمان' }}</b><small>زمان پایان کمپین</small></div>
        </div>
    </section>

    <section class="campaign-report-metrics">
        <article class="campaign-report-metric-card" style="--metric-color:#0ea5e9;"><span>کلیک‌ها</span><b>@fa(number_format($stats['clicks']))</b><small>ورود از لینک‌های کمپین</small></article>
        <article class="campaign-report-metric-card" style="--metric-color:#10b981;"><span>ثبت‌نام یا معرفی</span><b>@fa(number_format($stats['referrals']))</b><small>نتیجه‌های ثبت‌شده</small></article>
        <article class="campaign-report-metric-card" style="--metric-color:#8b5cf6;"><span>پاداش آزادشده</span><b>@fa(number_format($stats['rewards_released']))</b><small>پاداش‌های قطعی‌شده</small></article>
        <article class="campaign-report-metric-card" style="--metric-color:#f59e0b;"><span>در انتظار پاداش</span><b>@fa(number_format($stats['rewards_pending']))</b><small>نیازمند بررسی یا زمان آزادسازی</small></article>
    </section>

    <section class="campaign-report-grid">
        <article class="campaign-report-card is-accent" style="--report-card-color:#0ea5e9;">
            <header>
                <div>
                    <span>کانال‌های کمپین</span>
                    <h2>عملکرد هر مسیر جذب</h2>
                </div>
                <span class="campaign-report-badge">@fa(number_format($channels->count())) کانال</span>
            </header>
            <div class="campaign-report-list">
                @forelse($channels as $channel)
                    @php $progress = min(100, max(0, (float) $channel->conversion)); @endphp
                    <article class="campaign-report-channel-card" style="--row-color:#0ea5e9;">
                        <div class="campaign-report-icon">📣</div>
                        <div>
                            <span class="campaign-report-chip">{{ $channel->channel }}</span>
                            <h3>{{ $channel->label }}</h3>
                            <p>کلیک: @fa(number_format($channel->clicks)) · ثبت‌نام: @fa(number_format($channel->referrals))</p>
                            <div class="campaign-report-progress"><span style="--progress: {{ $progress }}%;"></span></div>
                        </div>
                        <span class="campaign-report-badge {{ $channel->conversion > 0 ? 'is-ok' : 'is-muted' }}">@fa($channel->conversion)٪ تبدیل</span>
                    </article>
                @empty
                    <div class="campaign-report-empty">برای این کمپین هنوز کانالی ثبت نشده است.</div>
                @endforelse
            </div>
        </article>

        <aside class="campaign-report-card is-accent" style="--report-card-color:#10b981;">
            <header>
                <div>
                    <span>سلامت کمپین</span>
                    <h2>تحلیل سریع عملکرد</h2>
                </div>
                <span class="campaign-report-badge {{ $conversion > 0 ? 'is-ok' : 'is-warn' }}">@fa($conversion)٪</span>
            </header>
            <div class="campaign-report-list">
                <article class="campaign-report-row" style="--row-color:#10b981;">
                    <div class="campaign-report-icon">✓</div>
                    <div><h3>پوشش تبدیل</h3><p>از هر @fa(number_format($safeClicks)) کلیک، @fa(number_format($stats['referrals'])) معرفی یا ثبت‌نام ثبت شده است.</p></div>
                    <span class="campaign-report-badge is-ok">تحلیل</span>
                </article>
                <article class="campaign-report-row" style="--row-color:#f59e0b;">
                    <div class="campaign-report-icon">⏱</div>
                    <div><h3>وضعیت پاداش</h3><p>@fa($releasedRatio)٪ از پاداش‌های قابل بررسی آزاد شده‌اند.</p></div>
                    <span class="campaign-report-badge is-warn">پاداش</span>
                </article>
                <article class="campaign-report-row" style="--row-color:#8b5cf6;">
                    <div class="campaign-report-icon">◆</div>
                    <div><h3>پیشنهاد بهینه‌سازی</h3><p>کانال‌هایی با نرخ تبدیل بالاتر را تقویت کنید و برای کانال‌های کم‌اثر، متن دعوت یا پاداش را تغییر دهید.</p></div>
                    <span class="campaign-report-badge">پیشنهاد</span>
                </article>
            </div>
        </aside>
    </section>

    <section class="campaign-report-grid-reverse">
        <article class="campaign-report-card is-accent" style="--report-card-color:#8b5cf6;">
            <header>
                <div>
                    <span>معرف‌های برتر</span>
                    <h2>اعضایی که بیشترین دعوت را ساخته‌اند</h2>
                </div>
                <span class="campaign-report-badge">@fa(number_format($topReferrers->count())) نفر</span>
            </header>
            <div class="campaign-report-list">
                @forelse($topReferrers as $index => $member)
                    <article class="campaign-report-referrer-card" style="--row-color:#8b5cf6;">
                        <div class="campaign-report-icon">@fa($index + 1)</div>
                        <div>
                            <h3>{{ $member->customer?->full_name ?? 'عضو باشگاه' }}</h3>
                            <p>تعداد دعوت موفق: @fa(number_format($member->campaign_referrals_count))</p>
                            <small>کد معرف: <span class="ltr">{{ $member->referral_code ?: '—' }}</span></small>
                        </div>
                        <span class="campaign-report-badge {{ $member->campaign_referrals_count > 0 ? 'is-ok' : 'is-muted' }}">معرف</span>
                    </article>
                @empty
                    <div class="campaign-report-empty">هنوز معرف فعالی برای این کمپین ثبت نشده است.</div>
                @endforelse
            </div>
        </article>

        <aside class="campaign-report-card is-accent" style="--report-card-color:#f59e0b;">
            <header>
                <div>
                    <span>خلاصه پاداش‌ها</span>
                    <h2>ارزش آزادشده کمپین</h2>
                </div>
            </header>
            <div class="campaign-report-list">
                <article class="campaign-report-row" style="--row-color:#0ea5e9;"><div class="campaign-report-icon">★</div><div><h3>امتیاز آزادشده</h3><p>@fa(number_format($stats['reward_points'])) امتیاز به اعضا داده شده است.</p></div><span class="campaign-report-badge">امتیاز</span></article>
                <article class="campaign-report-row" style="--row-color:#10b981;"><div class="campaign-report-icon">💰</div><div><h3>کیف پول آزادشده</h3><p>@money($stats['reward_wallet']) @unit اعتبار کیف پول آزاد شده است.</p></div><span class="campaign-report-badge is-ok">کیف پول</span></article>
                <article class="campaign-report-row" style="--row-color:#f59e0b;"><div class="campaign-report-icon">⏳</div><div><h3>در انتظار آزادسازی</h3><p>@fa(number_format($stats['rewards_pending'])) پاداش هنوز در انتظار است.</p></div><span class="campaign-report-badge is-warn">در انتظار</span></article>
            </div>
        </aside>
    </section>

    <section class="campaign-report-card is-accent" style="--report-card-color:#0ea5e9;">
        <header>
            <div>
                <span>مدیریت پاداش‌ها</span>
                <h2>فهرست پاداش‌های کمپین</h2>
                <p>پاداش‌های در انتظار را می‌توانید آزاد، فریز یا لغو کنید. موارد آزادشده در حساب عضو اعمال شده‌اند.</p>
            </div>
            <span class="campaign-report-badge">@fa(number_format($rewards->total())) پاداش</span>
        </header>

        <div class="campaign-report-list">
            @forelse($rewards as $reward)
                @php
                    [$rewardStatusText, $rewardStatusClass, $rewardStatusColor] = $rewardStatusLabels[$reward->status] ?? [$reward->status, 'is-muted', '#64748b'];
                    $rewardType = \Modules\Loyalty\Entities\LoyaltyCampaignRewardRule::REWARD_TYPES[$reward->reward_type] ?? $reward->reward_type;
                @endphp
                <article class="campaign-report-reward-card" style="--row-color: {{ $rewardStatusColor }};">
                    <div class="campaign-report-icon">🎁</div>
                    <div>
                        <span class="campaign-report-chip">{{ $rewardType }}</span>
                        <h3>{{ $reward->member?->customer?->full_name ?? 'عضو نامشخص' }}</h3>
                        <p>دعوت‌شده: {{ $reward->referral?->referredCustomer?->full_name ?? '—' }} · مقدار: @fa(number_format($reward->amount))</p>
                        <small>زمان آزادسازی: @jdatetime($reward->release_at)</small>
                    </div>
                    <div class="campaign-report-reward-actions">
                        <span class="campaign-report-badge {{ $rewardStatusClass }}">{{ $rewardStatusText }}</span>
                        @if($reward->status === 'pending')
                            <form method="post" action="{{ url('/app/loyalty/campaign-rewards/'.$reward->id.'/release') }}">@csrf<button class="btn btn-ghost">آزاد</button></form>
                            <form method="post" action="{{ url('/app/loyalty/campaign-rewards/'.$reward->id.'/freeze') }}">@csrf<button class="btn btn-ghost">فریز</button></form>
                            <form method="post" action="{{ url('/app/loyalty/campaign-rewards/'.$reward->id.'/cancel') }}">@csrf<button class="btn btn-ghost" style="color:var(--bad)!important">لغو</button></form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="campaign-report-empty">پاداشی برای این کمپین ثبت نشده است.</div>
            @endforelse
        </div>
        <div style="margin-top:1rem">{{ $rewards->links() }}</div>
    </section>
</div>
@endsection