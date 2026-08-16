@extends('layouts.customer_portal')
@section('title','نشان‌ها')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-badges.css') }}">
@php
    $conditionLabels = [
        'points' => 'امتیاز کل',
        'referrals' => 'تعداد معرفی',
        'orders' => 'تعداد سفارش',
        'manual' => 'اهدای دستی',
    ];
@endphp
<div class="club-badges-page">
    <section class="club-badges-hero">
        <div>
            <span class="club-badges-eyebrow">افتخارات باشگاه</span>
            <h1>نشان‌های افتخار، مسیر پیشرفت و دستاوردهای شما 🏅</h1>
            <p>نشان‌ها جایگاه و فعالیت شما را در باشگاه نشان می‌دهند. با خرید، دعوت دوستان، تکمیل مأموریت‌ها و افزایش امتیاز، نشان‌های جدید دریافت می‌کنید.</p>
            <div class="club-badges-actions">
                <a class="btn" href="{{ route('club.missions') }}">مشاهده مأموریت‌ها</a>
                <a class="btn btn-ghost" href="{{ route('club.leaderboard') }}">جدول برترین‌ها</a>
                <a class="btn btn-ghost" href="{{ route('club.journey') }}">سفر من</a>
            </div>
        </div>
        <div class="club-badges-score">
            <div><span>نشان‌های دریافت‌شده</span><b>@fa(number_format($stats['owned'] ?? 0))</b><small>از @fa(number_format($stats['total'] ?? 0)) نشان فعال</small></div>
            <div><span>امتیاز کل</span><b>@fa(number_format($stats['points'] ?? 0))</b><small>مبنای دریافت نشان‌ها</small></div>
            <div><span>نزدیک‌ترین نشان</span><b>@fa(number_format($stats['next_percent'] ?? 100))٪</b><small>بیشترین پیشرفت فعلی</small></div>
        </div>
    </section>

    @if($nextBadge)
        @php $next = $nextBadge['badge']; @endphp
        <section class="club-badge-card club-badges-next" style="--badge-color: {{ $next->color ?: '#0ea5e9' }};">
            <header>
                <div>
                    <span>نزدیک‌ترین دستاورد</span>
                    <h2>{{ $next->title }}</h2>
                </div>
                <span class="club-badge-status">@fa($nextBadge['percent'])٪ تکمیل</span>
            </header>
            <div class="club-badge-meta-row">
                <i>{{ $next->icon ?: '🏅' }}</i>
                <div>
                    <b>{{ $next->description ?: 'نشان افتخار باشگاه مشتریان' }}</b>
                    <small>تا دریافت این نشان حدود @fa(number_format($nextBadge['remaining'])) مورد دیگر نیاز دارید.</small>
                </div>
            </div>
            <div class="club-badge-progress-top"><span>پیشرفت</span><b>@fa($nextBadge['percent'])٪</b></div>
            <div class="club-badge-progress"><span style="--progress: {{ $nextBadge['percent'] }}%;"></span></div>
        </section>
    @endif

    <section class="club-badges-grid">
        @forelse($badgeCards as $card)
            @php
                $badge = $card['badge'];
                $hasBadge = $card['owned'];
                $ownedRow = $card['owned_row'];
                $color = $badge->color ?: '#0ea5e9';
            @endphp
            <article class="club-badge-card {{ $hasBadge ? 'is-owned' : '' }}" style="--badge-color: {{ $color }};">
                <header>
                    <div>
                        <span>{{ $conditionLabels[$badge->condition_type] ?? 'شرط دریافت' }}</span>
                        <h2>{{ $badge->title }}</h2>
                    </div>
                    <span class="club-badge-status">{{ $hasBadge ? 'دریافت شده' : 'در مسیر دریافت' }}</span>
                </header>

                <div class="club-badge-icon">{{ $badge->icon ?: '🏅' }}</div>
                <p>{{ $badge->description ?: 'نشان افتخار باشگاه مشتریان' }}</p>

                <div class="club-badge-meta">
                    <div class="club-badge-meta-row">
                        <i>✓</i>
                        <div>
                            <b>شرط دریافت</b>
                            <small>{{ $conditionLabels[$badge->condition_type] ?? 'شرط اختصاصی' }} · هدف @fa(number_format($card['target']))</small>
                        </div>
                    </div>
                    <div class="club-badge-meta-row">
                        <i>↗</i>
                        <div>
                            <b>پیشرفت شما</b>
                            <small>@fa(number_format($card['current'])) از @fa(number_format($card['target'])) · @fa($card['percent'])٪</small>
                        </div>
                    </div>
                    @if($hasBadge && $ownedRow)
                        <div class="club-badge-meta-row">
                            <i>🎉</i>
                            <div>
                                <b>زمان دریافت</b>
                                <small>@jdatetime($ownedRow->awarded_at)</small>
                            </div>
                        </div>
                    @else
                        <div class="club-badge-meta-row">
                            <i>⏳</i>
                            <div>
                                <b>باقی‌مانده</b>
                                <small>@fa(number_format($card['remaining'])) مورد تا دریافت نشان</small>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="club-badge-progress-top"><span>میزان تکمیل</span><b>@fa($card['percent'])٪</b></div>
                <div class="club-badge-progress"><span style="--progress: {{ $card['percent'] }}%;"></span></div>
            </article>
        @empty
            <div class="club-badges-empty">فعلاً نشانی تعریف نشده است.</div>
        @endforelse
    </section>
</div>
@endsection