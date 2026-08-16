@extends('layouts.app')
@section('title','گردونه شانس')
@section('heading','مرکز مدیریت گردونه شانس')
@section('subtitle','مدیریت جایزه‌ها، شانس برنده‌شدن، پیش‌نمایش گردونه، ارسال خودکار و آخرین چرخش‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('css/loyalty-wheel-board.css') }}">

@php
    $typeLabels = \Modules\Loyalty\Entities\WheelPrize::TYPES;
    $deliveryLabels = \Modules\Loyalty\Entities\WheelPrize::DELIVERY_CHANNELS;
    $activePrizes = $prizes->where('is_active', true)->values();
    $activeCount = $activePrizes->count();
    $totalChance = max(1, (int) $activePrizes->sum('chance'));
    $pointsTotal = (int) $prizes->where('prize_type', 'point')->where('is_active', true)->sum('amount');
    $walletTotal = (int) $prizes->where('prize_type', 'wallet')->where('is_active', true)->sum('amount');
    $couponCount = (int) $prizes->where('prize_type', 'coupon')->where('is_active', true)->count();
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

<div class="loyalty-wheel-page" style="--wheel-gradient: {{ $wheelGradient }};">
    <section class="loyalty-wheel-hero">
        <div class="loyalty-wheel-hero-main">
            <span>بازی‌وارسازی باشگاه</span>
            <h2>گردونه شانس حالا جایزه را در پروفایل مشتری ثبت می‌کند و پیام جایزه را خودکار می‌فرستد</h2>
            <p>برای هر جایزه می‌توانید نوع پاداش، تصویر، شانس، کد تخفیف اختصاصی و مسیرهای ارسال را مشخص کنید. نتیجه هر چرخش در پرونده مشتری ذخیره می‌شود و مشتری آن را در پنل خودش می‌بیند.</p>
            <div class="loyalty-wheel-hero-actions">
                <a class="btn" href="{{ route('club.wheel') }}" target="_blank">مشاهده گردونه مشتری</a>
                <a class="btn btn-ghost" href="{{ route('club.rewards') }}" target="_blank">مشاهده جایزه‌های من</a>
            </div>
        </div>
        <div class="loyalty-wheel-metrics">
            <div><span>جایزه فعال</span><b>@fa($activeCount)</b><small>قابل نمایش روی گردونه</small></div>
            <div><span>آخرین چرخش‌ها</span><b>@fa($spins->count())</b><small>موارد اخیر ثبت‌شده</small></div>
            <div><span>امتیاز جایزه‌ها</span><b>@fa(number_format($pointsTotal))</b><small>جمع جایزه‌های امتیازی فعال</small></div>
            <div><span>کد تخفیف فعال</span><b>@fa(number_format($couponCount))</b><small>جایزه‌های تخفیفی آماده ارسال</small></div>
        </div>
    </section>

    <section class="loyalty-wheel-main-grid">
        <article class="loyalty-wheel-preview-card">
            <header>
                <span>پیش‌نمایش گردونه</span>
                <h3>نمای زنده جوایز فعال</h3>
            </header>
            <div class="loyalty-wheel-wrap">
                <div class="loyalty-wheel-pointer">▼</div>
                <div class="loyalty-wheel" id="wheelPreview"></div>
                <div class="loyalty-wheel-center"><span>مشتری‌یار</span></div>
                <div class="loyalty-wheel-marker-layer">
                    @foreach($activePrizes as $index => $prize)
                        <div class="loyalty-wheel-marker" style="--marker-angle: {{ $activePrizes->count() ? round(($index / max(1, $activePrizes->count())) * 360 + (180 / max(1, $activePrizes->count()))) : 0 }}deg; --marker-color: {{ $prize->color ?: '#38bdf8' }};">
                            @if($prize->image)
                                <img src="{{ asset($prize->image) }}" alt="{{ $prize->title }}">
                            @else
                                <span>🎁</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <button class="btn loyalty-wheel-demo-button" type="button" onclick="openWheelDemo()">نمایش چرخش نمونه</button>
            <div class="loyalty-wheel-prize-chips">
                @forelse($activePrizes as $prize)
                    <span style="--prize-color: {{ $prize->color ?: '#38bdf8' }};">{{ $prize->title }} · @fa($prize->chance)٪</span>
                @empty
                    <span style="--prize-color:#64748b;">هنوز جایزه فعالی ثبت نشده است</span>
                @endforelse
            </div>
        </article>

        <article class="loyalty-wheel-form-card">
            <header>
                <span>افزودن جایزه</span>
                <h3>جایزه جدید برای گردونه بسازید</h3>
            </header>
            <form method="post" action="{{ url('/app/loyalty/wheel/prizes') }}" enctype="multipart/form-data" class="loyalty-wheel-create-form">
                @csrf
                <div class="loyalty-wheel-wide"><label>عنوان جایزه</label><input name="title" required placeholder="مثلاً ۱۰۰ امتیاز هدیه"></div>
                <div><label>نوع جایزه</label><select name="prize_type">@foreach($typeLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                <div><label>مقدار امتیاز یا کیف پول</label><input name="amount" class="ltr" value="0"></div>
                <div><label>درصد شانس</label><input name="chance" class="ltr" value="10"></div>
                <div><label>رنگ</label><input name="color" class="ltr" value="#38bdf8"></div>
                <div><label>نوع کد تخفیف</label><select name="coupon_discount_type"><option value="fixed">مبلغی</option><option value="percent">درصدی</option></select></div>
                <div><label>مقدار کد تخفیف</label><input name="coupon_discount_value" class="ltr" value="0"></div>
                <div><label>اعتبار کد تخفیف، روز</label><input name="coupon_expires_days" class="ltr" value="7"></div>
                <div class="loyalty-wheel-wide"><label>تصویر جایزه، اختیاری</label><input type="file" name="image" accept="image/*" data-hint="تصویر جایزه گردونه؛ پیشنهاد تصویر مربع و سبک."><small class="loyalty-wheel-size-hint">بهترین اندازه برای نمایش تمیز: تصویر مربع ۵۱۲ در ۵۱۲ پیکسل، با پس‌زمینه ساده و حجم کم. هر تصویری بارگذاری شود بهینه و به webp تبدیل می‌شود.</small></div>
                <div class="loyalty-wheel-wide">
                    <label>مسیرهای ارسال جایزه</label>
                    <div class="loyalty-wheel-channel-grid">
                        @foreach($deliveryLabels as $key => $label)
                            <label><input type="checkbox" name="delivery_channels[]" value="{{ $key }}" @checked($key === 'portal')> {{ $label }}</label>
                        @endforeach
                    </div>
                    <small class="loyalty-wheel-size-hint">اعلان داخل پنل همیشه پیشنهاد می‌شود تا جایزه در پروفایل مشتری باقی بماند. برای پیام‌رسان‌ها باید اتصال همان کانال در سامانه فعال باشد.</small>
                </div>
                <div class="loyalty-wheel-wide"><label>متن پیام جایزه، اختیاری</label><textarea name="delivery_message" rows="3" placeholder="تبریک {name} عزیز! شما برنده {prize} شدید. {reward_text}"></textarea><small class="loyalty-wheel-size-hint">عبارت‌های قابل استفاده: {name}، {prize}، {amount}، {code}، {reward_text}، {link}</small></div>
                <button class="btn loyalty-wheel-wide">افزودن جایزه</button>
            </form>
        </article>
    </section>

    <section class="loyalty-wheel-board-grid">
        <article class="loyalty-wheel-prizes-card">
            <header>
                <span>جوایز گردونه</span>
                <h3>مدیریت کارت‌های جایزه</h3>
            </header>
            <div class="loyalty-wheel-prize-list">
                @forelse($prizes as $prize)
                    @php
                        $selectedDelivery = is_array($prize->delivery_channels) && count($prize->delivery_channels) ? $prize->delivery_channels : ['portal'];
                        $selectedDeliveryText = collect($selectedDelivery)->map(fn($channel) => $deliveryLabels[$channel] ?? $channel)->implode('، ');
                    @endphp
                    <details class="loyalty-wheel-prize-card" style="--prize-color: {{ $prize->color ?: '#38bdf8' }};">
                        <summary>
                            <span class="loyalty-wheel-arrow">⌄</span>
                            <div class="loyalty-wheel-prize-image">
                                @if($prize->image)
                                    <img src="{{ asset($prize->image) }}" alt="{{ $prize->title }}">
                                @else
                                    <b>🎁</b>
                                @endif
                            </div>
                            <div>
                                <h4>{{ $prize->title }}</h4>
                                <small>{{ $typeLabels[$prize->prize_type] ?? $prize->prize_type }} · شانس @fa($prize->chance)٪</small>
                                <small>ارسال: {{ $selectedDeliveryText ?: 'فقط داخل پنل' }}</small>
                            </div>
                            <strong>{{ $prize->is_active ? 'فعال' : 'غیرفعال' }}</strong>
                        </summary>

                        <form method="post" action="{{ url('/app/loyalty/wheel/prizes/' . $prize->id) }}" enctype="multipart/form-data" class="loyalty-wheel-prize-edit-form">
                            @csrf
                            @method('PUT')
                            <div><label>عنوان</label><input name="title" value="{{ $prize->title }}"></div>
                            <div><label>نوع</label><select name="prize_type">@foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected($prize->prize_type === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div><label>مقدار امتیاز یا کیف پول</label><input name="amount" class="ltr" value="{{ $prize->amount }}"></div>
                            <div><label>شانس</label><input name="chance" class="ltr" value="{{ $prize->chance }}"></div>
                            <div><label>رنگ</label><input name="color" class="ltr" value="{{ $prize->color }}"></div>
                            <div><label>نوع کد تخفیف</label><select name="coupon_discount_type"><option value="fixed" @selected(($prize->coupon_discount_type ?? 'fixed') === 'fixed')>مبلغی</option><option value="percent" @selected(($prize->coupon_discount_type ?? '') === 'percent')>درصدی</option></select></div>
                            <div><label>مقدار کد تخفیف</label><input name="coupon_discount_value" class="ltr" value="{{ $prize->coupon_discount_value ?? 0 }}"></div>
                            <div><label>اعتبار کد تخفیف، روز</label><input name="coupon_expires_days" class="ltr" value="{{ $prize->coupon_expires_days ?? 7 }}"></div>
                            <label class="loyalty-wheel-active-check"><input type="checkbox" name="is_active" value="1" @checked($prize->is_active)> فعال باشد</label>
                            <div class="loyalty-wheel-wide"><label>تصویر جدید جایزه</label><input type="file" name="image" accept="image/*" data-hint="تصویر جدید جایزه؛ بهتر است مربع و سبک باشد."><small class="loyalty-wheel-size-hint">بهترین اندازه: ۵۱۲ در ۵۱۲ پیکسل. تصویر پس از بارگذاری به webp تبدیل می‌شود.</small></div>
                            <div class="loyalty-wheel-wide">
                                <label>مسیرهای ارسال جایزه</label>
                                <div class="loyalty-wheel-channel-grid">
                                    @foreach($deliveryLabels as $key => $label)
                                        <label><input type="checkbox" name="delivery_channels[]" value="{{ $key }}" @checked(in_array($key, $selectedDelivery, true))> {{ $label }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="loyalty-wheel-wide"><label>متن پیام جایزه</label><textarea name="delivery_message" rows="3">{{ $prize->delivery_message }}</textarea><small class="loyalty-wheel-size-hint">اگر خالی باشد، متن پیش‌فرض تبریک و لینک جایزه برای مشتری ارسال می‌شود.</small></div>
                            <button class="btn">ذخیره جایزه</button>
                        </form>
                        <form method="post" action="{{ url('/app/loyalty/wheel/prizes/' . $prize->id) }}" onsubmit="return confirm('جایزه حذف شود؟')" class="loyalty-wheel-delete-form">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost">حذف جایزه</button>
                        </form>
                    </details>
                @empty
                    <div class="loyalty-wheel-empty">هنوز جایزه‌ای اضافه نشده است.</div>
                @endforelse
            </div>
        </article>

        <article class="loyalty-wheel-spins-card">
            <header>
                <span>آخرین چرخش‌ها</span>
                <h3>سابقه برنده‌ها و نتیجه‌ها</h3>
            </header>
            <div class="loyalty-wheel-spin-list">
                @forelse($spins as $spin)
                    @php
                        $spinMember = \Modules\Loyalty\Entities\LoyaltyMember::with('customer')->find($spin->member_id);
                        $spinPrize = \Modules\Loyalty\Entities\WheelPrize::find($spin->prize_id);
                    @endphp
                    <article class="loyalty-wheel-spin-card">
                        <div>
                            <h4>{{ $spinMember?->customer?->full_name ?? 'عضو نامشخص' }}</h4>
                            <span>{{ $spin->prize_title ?: ($spinPrize?->title ?? 'جایزه نامشخص') }}</span>
                            @if($spin->coupon)
                                <small>کد تخفیف: <span class="ltr">{{ $spin->coupon->code }}</span></small>
                            @endif
                        </div>
                        <div>
                            <b>@jdatetime($spin->created_at)</b>
                            <small>{{ $typeLabels[$spin->prize_type ?: ($spinPrize?->prize_type ?? '')] ?? ($spin->prize_type ?: 'نامشخص') }} · @fa($spin->amount ?: ($spinPrize?->amount ?? 0))</small>
                            <small>وضعیت ارسال: {{ method_exists($spin, 'deliveryStatusLabel') ? $spin->deliveryStatusLabel() : 'ثبت شده' }}</small>
                        </div>
                    </article>
                @empty
                    <div class="loyalty-wheel-empty">هنوز چرخشی ثبت نشده است.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>

<div class="loyalty-wheel-modal" id="wheelModal" style="--wheel-gradient: {{ $wheelGradient }};">
    <div class="loyalty-wheel-modal-box">
        <button type="button" onclick="closeWheelDemo()">×</button>
        <h2>گردونه شانس</h2>
        <div class="loyalty-wheel-wrap">
            <div class="loyalty-wheel-pointer">▼</div>
            <div class="loyalty-wheel" id="wheelModalWheel"></div>
            <div class="loyalty-wheel-center"><span>بچرخان</span></div>
            <div class="loyalty-wheel-marker-layer">
                @foreach($activePrizes as $index => $prize)
                    <div class="loyalty-wheel-marker" style="--marker-angle: {{ $activePrizes->count() ? round(($index / max(1, $activePrizes->count())) * 360 + (180 / max(1, $activePrizes->count()))) : 0 }}deg; --marker-color: {{ $prize->color ?: '#38bdf8' }};">
                        @if($prize->image)
                            <img src="{{ asset($prize->image) }}" alt="{{ $prize->title }}">
                        @else
                            <span>🎁</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <div id="wheelResult" class="loyalty-wheel-result">برای مشاهده نمونه، روی شروع بزنید.</div>
        <div class="loyalty-wheel-modal-actions">
            <button class="btn" type="button" onclick="spinDemo()">شروع چرخش</button>
            <button class="btn btn-ghost" type="button" onclick="closeWheelDemo()">بستن</button>
        </div>
    </div>
</div>

<script>
function loyaltyWheelBeep(freq, dur) {
    try {
        const audio = new AudioContext();
        const oscillator = audio.createOscillator();
        const gain = audio.createGain();
        oscillator.frequency.value = freq;
        oscillator.connect(gain);
        gain.connect(audio.destination);
        oscillator.start();
        gain.gain.exponentialRampToValueAtTime(0.0001, audio.currentTime + dur / 1000);
        setTimeout(() => audio.close(), dur + 80);
    } catch (error) {}
}

function openWheelDemo() {
    document.getElementById('wheelModal')?.classList.add('show');
}

function closeWheelDemo() {
    document.getElementById('wheelModal')?.classList.remove('show');
}

function spinDemo() {
    const wheel = document.getElementById('wheelModalWheel');
    const result = document.getElementById('wheelResult');
    if (!wheel || !result) return;
    wheel.classList.remove('spin-anim');
    void wheel.offsetWidth;
    loyaltyWheelBeep(520, 180);
    wheel.classList.add('spin-anim');
    result.textContent = 'در حال چرخش...';
    setTimeout(() => {
        loyaltyWheelBeep(880, 300);
        result.textContent = 'نتیجه نمونه: جایزه مشخص شد!';
    }, 4100);
}
</script>
@endsection