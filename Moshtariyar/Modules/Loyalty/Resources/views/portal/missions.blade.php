@extends('layouts.customer_portal')
@section('title','مأموریت‌ها')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-missions.css') }}">
@php
    $doneCount = collect($done)->count();
    $totalMissions = $missions->count();
    $claimableCount = collect($missionCards ?? [])->where('can_claim', true)->count();
@endphp
<div class="club-missions-page">
    <section class="club-missions-hero">
        <div>
            <span class="club-missions-eyebrow">مسیر رشد مشتری</span>
            <h1>مأموریت‌ها را انجام دهید، پیشرفت را ببینید و پاداش بگیرید 🎯</h1>
            <p>هر مأموریت بر اساس رفتار واقعی شما در باشگاه سنجیده می‌شود؛ مثل تکمیل پروفایل، خرید، دعوت دوستان یا تعداد سفارش‌ها. وقتی هدف کامل شد، پاداش را دریافت کنید.</p>
            <div class="club-missions-actions">
                <a class="btn" href="{{ route('club.profile') }}">تکمیل پروفایل</a>
                <a class="btn btn-ghost" href="{{ route('club.referrals') }}">دعوت دوستان</a>
                <a class="btn btn-ghost" href="{{ route('club.orders') }}">خریدهای من</a>
            </div>
        </div>
        <div class="club-missions-score">
            <div><span>مأموریت انجام‌شده</span><b>@fa(number_format($doneCount))</b><small>از @fa(number_format($totalMissions)) مأموریت فعال</small></div>
            <div><span>آماده دریافت پاداش</span><b>@fa(number_format($claimableCount))</b><small>مأموریت کامل‌شده و دریافت‌نشده</small></div>
            <div><span>امتیاز فعلی</span><b>@fa(number_format($member->points))</b><small>مانده امتیاز شما</small></div>
        </div>
    </section>

    <section class="club-missions-grid">
        @forelse($missionCards as $card)
            @php
                $mission = $card['mission'];
                $isDone = $card['is_done'];
                $canClaim = $card['can_claim'];
                $color = $isDone ? '#10b981' : ($canClaim ? '#f59e0b' : '#0ea5e9');
                $rewardText = $mission->reward_type === 'points_fixed'
                    ? \Modules\Core\Support\Num::fa(number_format($mission->reward_value)) . ' امتیاز'
                    : \Modules\Core\Support\Money::show($mission->reward_value) . ' ' . \Modules\Core\Support\Money::unitLabel() . ' کیف پول';
                $eventLabel = \Modules\Loyalty\Entities\LoyaltyMission::EVENTS[$mission->event] ?? 'فعالیت باشگاه';
            @endphp
            <article class="club-mission-card" style="--mission-color: {{ $color }};">
                <header>
                    <div>
                        <span>{{ $eventLabel }}</span>
                        <h2>{{ $mission->title }}</h2>
                    </div>
                    <span class="club-mission-status">{{ $isDone ? 'انجام شده' : ($canClaim ? 'آماده دریافت' : 'در حال انجام') }}</span>
                </header>
                <p>{{ $mission->description ?: 'این مأموریت را انجام دهید تا پاداش آن برای شما ثبت شود.' }}</p>
                <div class="club-mission-body" style="margin-top:.85rem">
                    <div class="club-mission-row">
                        <div class="club-mission-icon">✓</div>
                        <div>
                            <h3>پیشرفت مأموریت</h3>
                            <p>{{ $card['label'] }}</p>
                            <small>هدف: @fa(number_format($card['target'])) · پیشرفت: @fa(number_format($card['current']))</small>
                        </div>
                        <span class="club-mission-status">@fa($card['percent'])٪</span>
                    </div>
                    <div class="club-mission-row">
                        <div class="club-mission-icon">🎁</div>
                        <div><h3>پاداش مأموریت</h3><p>{{ $rewardText }}</p></div>
                        <span class="club-mission-status">پاداش</span>
                    </div>
                    <div class="club-mission-progress-wrap">
                        <div class="club-mission-progress-top"><span>میزان تکمیل</span><b>@fa($card['percent'])٪</b></div>
                        <div class="club-mission-progress"><span style="--progress: {{ $card['percent'] }}%;"></span></div>
                    </div>
                </div>
                <div class="club-mission-footer">
                    @if($isDone)
                        <span class="club-mission-status">دریافت شده @if($card['completed_at']) · @jdatetime($card['completed_at']) @endif</span>
                    @elseif($canClaim)
                        <form method="post" action="{{ route('club.missions.claim', $mission) }}">
                            @csrf
                            <button class="btn">دریافت پاداش</button>
                        </form>
                    @else
                        <a class="btn btn-ghost" href="{{ $card['action_url'] }}">{{ $card['action_label'] }}</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="club-missions-empty">فعلاً مأموریتی تعریف نشده است.</div>
        @endforelse
    </section>
</div>
@endsection