@extends('layouts.customer_portal')
@section('title','گردونه شانس من')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-wheel.css') }}">

@php
    $typeLabels = \Modules\Loyalty\Entities\WheelPrize::TYPES;
    $activePrizes = $prizes->values();
    $totalChance = max(1, (int) $activePrizes->sum('chance'));
    $stops = [];
    $currentDegree = 0;

    foreach ($activePrizes as $prize) {
        $slice = max(1, (int) $prize->chance) / $totalChance * 360;
        $start = $currentDegree;
        $currentDegree += $slice;
        $end = $currentDegree;
        $stops[] = ($prize->color ?: '#38bdf8') . " {$start}deg {$end}deg";
    }

    $wheelGradient = count($stops) ? 'conic-gradient(' . implode(',', $stops) . ')' : 'conic-gradient(#38bdf8 0deg 120deg,#10b981 120deg 240deg,#f59e0b 240deg 360deg)';
@endphp

<div class="club-wheel-page" style="--wheel-gradient: {{ $wheelGradient }};">
    <section class="club-wheel-hero">
        <div>
            <span class="club-wheel-eyebrow">گردونه شانس باشگاه</span>
            <h1>{{ $member->customer->full_name }} عزیز، شانس امروز شما آماده است 🎡</h1>
            <p>گردونه را بچرخانید و جایزه خود را همان لحظه در پروفایل ببینید. جایزه می‌تواند امتیاز، کیف پول، کد تخفیف یا هدیه ویژه باشد.</p>
            <div class="club-wheel-actions">
                @if($canSpinToday && $activePrizes->isNotEmpty())
                    <form method="post" action="{{ route('club.wheel.spin') }}" onsubmit="return startCustomerWheelSpin(this)">
                        @csrf
                        <button class="btn" id="customerWheelButton">چرخاندن گردونه امروز</button>
                    </form>
                @elseif($todaySpin)
                    <a class="btn" href="{{ route('club.rewards') }}">مشاهده جایزه امروز</a>
                @else
                    <span class="btn btn-ghost">فعلاً جایزه فعالی وجود ندارد</span>
                @endif
                <a class="btn btn-ghost" href="{{ route('club.rewards') }}">جایزه‌های من</a>
                <a class="btn btn-ghost" href="{{ route('club.journey') }}">سفر من</a>
            </div>
        </div>
        <div class="club-wheel-score">
            <div><span>جایزه فعال</span><b>@fa(number_format($wheelStats['active_prizes'] ?? $activePrizes->count()))</b><small>قابل نمایش روی گردونه</small></div>
            <div><span>چرخش‌های شما</span><b>@fa(number_format($wheelStats['total_spins'] ?? $spins->count()))</b><small>همه نتیجه‌های ثبت‌شده</small></div>
            <div><span>وضعیت امروز</span><b>{{ $wheelStats['today_status'] ?? ($canSpinToday ? 'آماده چرخش' : 'چرخیده شده') }}</b><small>{{ $canSpinToday ? 'امروز می‌توانید شانس خود را امتحان کنید' : 'فردا دوباره برگردید' }}</small></div>
        </div>
    </section>

    <section class="club-wheel-grid">
        <article class="club-wheel-card is-accent" style="--wheel-card-color:#f59e0b;">
            <header>
                <div>
                    <span>نمای گردونه</span>
                    <h2>جایزه‌های امروز</h2>
                </div>
                <span class="club-wheel-badge {{ $canSpinToday ? 'is-warn' : 'is-ok' }}">{{ $canSpinToday ? 'آماده چرخش' : 'انجام شد' }}</span>
            </header>
            <div class="club-wheel-stage">
                <div class="club-wheel-wrap-pro">
                    <div class="club-wheel-pointer-pro">▼</div>
                    <div class="club-wheel-circle-pro" id="customerWheelCircle"></div>
                    <div class="club-wheel-center-pro"><span>بچرخان</span></div>
                    <div class="club-wheel-marker-layer-pro">
                        @foreach($activePrizes as $index => $prize)
                            <div class="club-wheel-marker-pro" style="--marker-angle: {{ $activePrizes->count() ? round(($index / max(1, $activePrizes->count())) * 360 + (180 / max(1, $activePrizes->count()))) : 0 }}deg; --marker-color: {{ $prize->color ?: '#38bdf8' }};">
                                @if($prize->image)
                                    <img src="{{ asset($prize->image) }}" alt="{{ $prize->title }}">
                                @else
                                    <span>🎁</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="club-prize-chip-list-pro">
                @forelse($activePrizes as $prize)
                    <span style="--chip-color: {{ $prize->color ?: '#38bdf8' }};">{{ $prize->title }}</span>
                @empty
                    <span style="--chip-color:#64748b;">فعلاً جایزه‌ای ثبت نشده است</span>
                @endforelse
            </div>
        </article>

        <aside class="club-wheel-card is-accent" style="--wheel-card-color:#10b981;">
            <header>
                <div>
                    <span>نتیجه امروز</span>
                    <h2>{{ $todaySpin ? 'جایزه امروز شما' : 'هنوز نچرخانده‌اید' }}</h2>
                </div>
            </header>
            <div class="club-wheel-result-card">
                @if($todaySpin)
                    <article class="club-wheel-result-box" style="--row-color:#10b981;">
                        <div class="club-wheel-icon">
                            @if($todaySpin->prize_image)
                                <img src="{{ asset($todaySpin->prize_image) }}" alt="{{ $todaySpin->prize_title }}">
                            @else
                                🎁
                            @endif
                        </div>
                        <div>
                            <span class="club-wheel-chip">{{ $typeLabels[$todaySpin->prize_type] ?? 'جایزه' }}</span>
                            <h3>{{ $todaySpin->prize_title ?: 'جایزه گردونه' }}</h3>
                            <p>مقدار: @fa(number_format($todaySpin->amount)) · @jdatetime($todaySpin->created_at)</p>
                            @if($todaySpin->coupon)
                                <div class="club-wheel-code-box"><input value="{{ $todaySpin->coupon->code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyWheelCode(this)">کپی</button></div>
                            @endif
                        </div>
                        <span class="club-wheel-badge is-ok">{{ $todaySpin->deliveryStatusLabel() }}</span>
                    </article>
                @else
                    <article class="club-wheel-tip-row" style="--row-color:#f59e0b;">
                        <div class="club-wheel-icon">🎡</div>
                        <div><h3>اولین چرخش امروز منتظر شماست</h3><p>روی دکمه چرخاندن بزنید تا نتیجه در پروفایل و جایزه‌های شما ثبت شود.</p></div>
                        <span class="club-wheel-badge is-warn">آماده</span>
                    </article>
                @endif

                <article class="club-wheel-tip-row" style="--row-color:#0ea5e9;">
                    <div class="club-wheel-icon">ℹ️</div>
                    <div><h3>جایزه‌ها کجا ثبت می‌شوند؟</h3><p>نتیجه گردونه در بخش جایزه‌های من، دفتر امتیاز و سفر مشتری شما ثبت می‌شود.</p></div>
                    <a class="btn btn-ghost" href="{{ route('club.rewards') }}">مشاهده</a>
                </article>
            </div>
        </aside>
    </section>

    <section class="club-wheel-grid-reverse">
        <article class="club-wheel-card is-accent" style="--wheel-card-color:#0ea5e9;">
            <header>
                <div>
                    <span>آخرین نتیجه‌ها</span>
                    <h2>چرخش‌های اخیر شما</h2>
                </div>
                <a href="{{ route('club.rewards') }}">همه جایزه‌ها ←</a>
            </header>
            <div class="club-wheel-result-card">
                @forelse($spins as $spin)
                    <article class="club-wheel-spin-row" style="--row-color:#0ea5e9;">
                        <div class="club-wheel-icon">
                            @if($spin->prize_image)
                                <img src="{{ asset($spin->prize_image) }}" alt="{{ $spin->prize_title }}">
                            @else
                                🎁
                            @endif
                        </div>
                        <div>
                            <span class="club-wheel-chip">{{ $typeLabels[$spin->prize_type] ?? 'جایزه' }}</span>
                            <h3>{{ $spin->prize_title }}</h3>
                            <p>@jdatetime($spin->created_at)</p>
                            @if($spin->coupon)
                                <div class="club-wheel-code-box"><input value="{{ $spin->coupon->code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyWheelCode(this)">کپی</button></div>
                            @endif
                        </div>
                        <span class="club-wheel-badge">{{ $spin->deliveryStatusLabel() }}</span>
                    </article>
                @empty
                    <div class="club-wheel-empty">هنوز گردونه را نچرخانده‌اید. اولین جایزه منتظر شماست.</div>
                @endforelse
            </div>
        </article>

        <aside class="club-wheel-card is-accent" style="--wheel-card-color:#8b5cf6;">
            <header>
                <div>
                    <span>راهنمای گردونه</span>
                    <h2>چطور بیشترین استفاده را ببرید؟</h2>
                </div>
            </header>
            <div class="club-wheel-result-card">
                <article class="club-wheel-tip-row" style="--row-color:#8b5cf6;"><div class="club-wheel-icon">۱</div><div><h3>هر روز شانس خود را امتحان کنید</h3><p>برای جلوگیری از سوءاستفاده، هر عضو روزانه یک بار امکان چرخاندن دارد.</p></div></article>
                <article class="club-wheel-tip-row" style="--row-color:#10b981;"><div class="club-wheel-icon">۲</div><div><h3>جایزه‌ها در پروفایل ثبت می‌شوند</h3><p>امتیاز، کیف پول و کد تخفیف به‌صورت خودکار در حساب شما ثبت می‌شود.</p></div></article>
                <article class="club-wheel-tip-row" style="--row-color:#f59e0b;"><div class="club-wheel-icon">۳</div><div><h3>پیام جایزه ممکن است ارسال شود</h3><p>اگر برای جایزه پیامک، ایمیل یا پیام‌رسان فعال باشد، پیام نتیجه نیز ارسال می‌شود.</p></div></article>
            </div>
        </aside>
    </section>
</div>

<script>
function startCustomerWheelSpin(form) {
    const wheel = document.getElementById('customerWheelCircle');
    const button = document.getElementById('customerWheelButton');
    if (wheel) {
        wheel.classList.remove('club-wheel-spin-pro');
        void wheel.offsetWidth;
        wheel.classList.add('club-wheel-spin-pro');
    }
    if (button) {
        button.disabled = true;
        button.textContent = 'در حال چرخش...';
    }
    setTimeout(() => form.submit(), 2200);
    return false;
}

function copyWheelCode(button) {
    const input = button.closest('.club-wheel-code-box')?.querySelector('input');
    if (!input) return;
    input.select();
    document.execCommand('copy');
    button.textContent = 'کپی شد';
    setTimeout(() => button.textContent = 'کپی', 1200);
}
</script>
@endsection