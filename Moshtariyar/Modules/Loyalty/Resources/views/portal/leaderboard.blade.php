@extends('layouts.customer_portal')
@section('title','برترین‌ها')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-leaderboard.css') }}">
@php
    $myAvatar = $member->customer?->meta['avatar'] ?? null;
    $myName = $member->customer?->full_name ?: 'شما';
@endphp
<div class="club-leaderboard-page">
    <section class="club-leaderboard-hero">
        <div>
            <span class="club-leaderboard-eyebrow">رقابت دوستانه باشگاه</span>
            <h1>جدول برترین مشتریان و جایگاه شما 🏆</h1>
            <p>رتبه‌بندی بر اساس امتیاز کل مشتریان محاسبه می‌شود. با خرید، تکمیل مأموریت، دعوت دوستان و دریافت پاداش‌های بیشتر، جایگاه شما در باشگاه بهتر می‌شود.</p>
            <div class="club-leaderboard-actions">
                <a class="btn" href="{{ route('club.missions') }}">افزایش امتیاز با مأموریت‌ها</a>
                <a class="btn btn-ghost" href="{{ route('club.referrals') }}">دعوت دوستان</a>
                <a class="btn btn-ghost" href="{{ route('club.journey') }}">سفر من</a>
            </div>
        </div>
        <div class="club-leaderboard-score">
            <div><span>رتبه شما</span><b>@fa(number_format($stats['my_rank'] ?? $myRank ?? 0))</b><small>جایگاه فعلی در باشگاه</small></div>
            <div><span>امتیاز کل شما</span><b>@fa(number_format($stats['my_score'] ?? $member->points_lifetime))</b><small>مبنای رتبه‌بندی</small></div>
            <div><span>اعضای باشگاه</span><b>@fa(number_format($stats['members'] ?? 0))</b><small>همه اعضای ثبت‌شده</small></div>
        </div>
    </section>

    @if($topThree->isNotEmpty())
        <section class="club-leaderboard-card is-accent" style="--board-color:#f59e0b; margin-bottom:1rem">
            <header>
                <div>
                    <span>سکوی برترین‌ها</span>
                    <h2>سه عضو اول باشگاه</h2>
                </div>
                <span class="club-leaderboard-badge">قهرمانان وفاداری</span>
            </header>
            <div class="club-leaderboard-podium">
                @foreach($topThree as $index => $row)
                    @php
                        $avatar = $row->customer?->meta['avatar'] ?? null;
                        $name = $row->customer?->full_name ?: 'عضو باشگاه';
                        $colors = ['#f59e0b', '#94a3b8', '#b45309'];
                    @endphp
                    <article class="club-leaderboard-podium-card {{ $index === 0 ? 'is-first' : '' }}" style="--podium-color: {{ $colors[$index] ?? '#0ea5e9' }}; order: {{ $index === 0 ? 2 : ($index === 1 ? 1 : 3) }};">
                        <div class="club-leaderboard-podium-rank">@fa($index + 1)</div>
                        <div class="club-leaderboard-avatar">
                            @if($avatar)
                                <img src="{{ asset($avatar) }}" alt="{{ $name }}">
                            @else
                                {{ mb_substr($name, 0, 1) }}
                            @endif
                        </div>
                        <h3>{{ $name }}</h3>
                        <p>{{ $row->tier?->name ?? 'بدون سطح' }}</p>
                        <small>@fa(number_format($row->points_lifetime)) امتیاز کل</small>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="club-leaderboard-grid">
        <article class="club-leaderboard-card is-accent" style="--board-color:#0ea5e9;">
            <header>
                <div>
                    <span>پنجاه نفر اول</span>
                    <h2>جدول اعضای برتر</h2>
                </div>
                <span class="club-leaderboard-badge">@fa(number_format($members->count())) نفر</span>
            </header>
            <div class="club-leaderboard-list">
                @forelse($members as $index => $row)
                    @php
                        $isMe = $row->id === $member->id;
                        $avatar = $row->customer?->meta['avatar'] ?? null;
                        $name = $row->customer?->full_name ?: 'عضو باشگاه';
                    @endphp
                    <article class="club-leaderboard-row {{ $isMe ? 'is-me' : '' }}">
                        <div class="club-leaderboard-rank">@fa($index + 1)</div>
                        <div class="club-leaderboard-avatar">
                            @if($avatar)
                                <img src="{{ asset($avatar) }}" alt="{{ $name }}">
                            @else
                                {{ mb_substr($name, 0, 1) }}
                            @endif
                        </div>
                        <div>
                            <h3>{{ $name }}</h3>
                            <p>سطح: {{ $row->tier?->name ?? 'بدون سطح' }} · امتیاز فعلی: @fa(number_format($row->points))</p>
                            <small>امتیاز کل: @fa(number_format($row->points_lifetime))</small>
                        </div>
                        <div class="club-leaderboard-points">
                            <b>@fa(number_format($row->points_lifetime))</b>
                            <span class="club-leaderboard-badge {{ $isMe ? '' : 'is-muted' }}">{{ $isMe ? 'شما' : 'عضو' }}</span>
                        </div>
                    </article>
                @empty
                    <div class="club-growth-empty">هنوز عضوی برای رتبه‌بندی وجود ندارد.</div>
                @endforelse
            </div>
        </article>

        <aside class="club-leaderboard-card is-accent" style="--board-color:#10b981;">
            <header>
                <div>
                    <span>جایگاه من</span>
                    <h2>مسیر رسیدن به رتبه بهتر</h2>
                </div>
            </header>
            <div class="club-leaderboard-next">
                <article class="club-leaderboard-next-card" style="--next-color:#0ea5e9;">
                    <i>👤</i>
                    <div>
                        <h3>{{ $myName }}</h3>
                        <p>رتبه فعلی شما @fa(number_format($myRank ?? 0)) است و @fa(number_format($member->points_lifetime)) امتیاز کل دارید.</p>
                    </div>
                </article>
                @if($nextTarget)
                    <article class="club-leaderboard-next-card" style="--next-color:#f59e0b;">
                        <i>↗</i>
                        <div>
                            <h3>هدف رتبه بعدی</h3>
                            <p>برای رسیدن به عضو بالاتر، حدود @fa(number_format($stats['gap_to_next'] ?? 0)) امتیاز دیگر نیاز دارید.</p>
                            <div class="club-leaderboard-actions"><a class="btn btn-ghost" href="{{ route('club.missions') }}">رفتن به مأموریت‌ها</a></div>
                        </div>
                    </article>
                @else
                    <article class="club-leaderboard-next-card" style="--next-color:#10b981;">
                        <i>🏆</i>
                        <div>
                            <h3>شما در بهترین جایگاه خود هستید</h3>
                            <p>فعلاً عضو بالاتری برای هدف بعدی پیدا نشد. با ادامه فعالیت، جایگاه خود را حفظ کنید.</p>
                        </div>
                    </article>
                @endif
                <article class="club-leaderboard-next-card" style="--next-color:#8b5cf6;">
                    <i>🤝</i>
                    <div>
                        <h3>دعوت دوستان</h3>
                        <p>دعوت دوستان، خریدهای بعدی و گردونه شانس می‌تواند امتیاز و رتبه شما را بهتر کند.</p>
                        <div class="club-leaderboard-actions"><a class="btn btn-ghost" href="{{ route('club.referrals') }}">دریافت لینک دعوت</a></div>
                    </div>
                </article>
            </div>
        </aside>
    </section>
</div>
@endsection