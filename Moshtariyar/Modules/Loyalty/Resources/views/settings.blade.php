@extends('layouts.app')
@section('title','سطح‌ها، قوانین و مأموریت‌های باشگاه')
@section('heading','مرکز طراحی وفاداری و بازی‌وارسازی')
@section('subtitle','مدیریت سطح‌ها، قوانین پاداش، مأموریت‌ها، نشان‌ها، کدهای تخفیف و تبدیل امتیاز')

@section('content')
<link rel="stylesheet" href="{{ asset('css/loyalty-settings-board.css') }}">

@php
    $tierCount = $tiers->count();
    $activeRules = $rules->where('is_active', true)->count();
    $activeMissions = $missions->where('is_active', true)->count();
    $activeBadges = $badges->where('is_active', true)->count();
    $activeRedemptionRules = $redemptionRules->where('is_active', true)->count();
    $validCoupons = $coupons->filter(function ($coupon) {
        return empty($coupon->used_at) && (empty($coupon->expires_at) || $coupon->expires_at >= now());
    })->count();
    $conditionLabels = [
        'points' => 'امتیاز کل',
        'referrals' => 'تعداد معرفی',
        'orders' => 'تعداد سفارش',
        'manual' => 'دستی',
    ];
@endphp

<div class="loyalty-settings-page">
    <section class="loyalty-settings-hero">
        <div class="loyalty-settings-hero-main">
            <span>طراحی سیستم وفاداری</span>
            <h2>سطح‌ها، مأموریت‌ها، پاداش‌ها و تبدیل امتیاز را از یک مرکز حرفه‌ای مدیریت کنید</h2>
            <p>این صفحه قلب باشگاه مشتریان است. اینجا مشخص می‌کنید هر رفتار مشتری چه پاداشی دارد، مشتری‌ها چطور سطح می‌گیرند، چه مأموریت‌هایی فعال است و امتیاز چگونه به کد تخفیف یا اعتبار تبدیل می‌شود.</p>
        </div>
        <div class="loyalty-settings-hero-metrics">
            <div><span>سطح‌ها</span><b>@fa($tierCount)</b><small>مسیر رشد مشتریان</small></div>
            <div><span>قوانین فعال</span><b>@fa($activeRules)</b><small>پاداش خودکار</small></div>
            <div><span>مأموریت فعال</span><b>@fa($activeMissions)</b><small>بازی‌وارسازی</small></div>
            <div><span>قانون تبدیل فعال</span><b>@fa($activeRedemptionRules)</b><small>امتیاز به کد تخفیف</small></div>
        </div>
    </section>

    <section class="loyalty-settings-insights">
        <article><span></span><b>@fa($activeBadges)</b><small>نشان فعال</small></article>
        <article><span></span><b>@fa($validCoupons)</b><small>کد تخفیف معتبر اخیر</small></article>
        <article><span></span><b>@fa($rules->count())</b><small>کل قوانین پاداش</small></article>
        <article><span></span><b>@fa($missions->count())</b><small>کل مأموریت‌ها</small></article>
        <article><span></span><b>@fa($redemptionRules->count())</b><small>قوانین تبدیل</small></article>
    </section>

    <section class="loyalty-settings-board">
        <article class="loyalty-settings-column" style="--settings-color:#f59e0b;">
            <header>
                <div><span></span><h3>سطح‌های عضویت</h3></div>
                <b>@fa($tierCount)</b>
            </header>
            <p>سطح‌ها مسیر رشد مشتری را مشخص می‌کنند و می‌توانند مبنای پاداش، کش‌بک و تجربه اختصاصی باشند.</p>

            <details class="loyalty-settings-create-panel">
                <summary>افزودن سطح جدید</summary>
                <form method="post" action="{{ url('/app/loyalty/tiers') }}" class="loyalty-settings-form">
                    @csrf
                    <div><label>نام سطح</label><input name="name" required placeholder="طلایی"></div>
                    <div><label>حداقل امتیاز</label><input name="min_points" class="ltr" value="0"></div>
                    <div><label>کش‌بک ٪</label><input name="cashback_rate" class="ltr" value="0"></div>
                    <div><label>رنگ</label><input name="color" value="#f59e0b" class="ltr"></div>
                    <button class="btn">افزودن سطح</button>
                </form>
            </details>

            <div class="loyalty-tier-roadmap">
                @forelse($tiers as $tier)
                    @php
                        $membersInTier = \Modules\Loyalty\Entities\LoyaltyMember::where('tier_id', $tier->id)->count();
                    @endphp
                    <article class="loyalty-tier-card" style="--tier-color: {{ $tier->color ?? '#f59e0b' }};">
                        <div class="loyalty-tier-dot"></div>
                        <div>
                            <h4>{{ $tier->name }}</h4>
                            <span>از @fa(number_format($tier->min_points)) امتیاز</span>
                        </div>
                        <div>
                            <b>@fa($membersInTier)</b>
                            <small>عضو</small>
                        </div>
                        <div>
                            <b>@fa($tier->cashback_rate)٪</b>
                            <small>کش‌بک</small>
                        </div>
                    </article>
                @empty
                    <div class="loyalty-settings-empty">سطحی تعریف نشده است.</div>
                @endforelse
            </div>
        </article>

        <article class="loyalty-settings-column" style="--settings-color:#10b981;">
            <header>
                <div><span></span><h3>قوانین پاداش</h3></div>
                <b>@fa($rules->count())</b>
            </header>
            <p>قوانین پاداش تعیین می‌کنند کدام رفتار مشتری، چه امتیاز یا کیف پولی ایجاد کند.</p>

            <details class="loyalty-settings-create-panel">
                <summary>افزودن قانون پاداش</summary>
                <form method="post" action="{{ url('/app/loyalty/rules') }}" class="loyalty-settings-form">
                    @csrf
                    <div class="loyalty-settings-wide"><label>نام قانون</label><input name="name" required placeholder="امتیاز ثبت‌نام"></div>
                    <div><label>رویداد</label><select name="event">@foreach($events as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label>نوع پاداش</label><select name="reward_type">@foreach($rewardTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label>مقدار</label><input name="reward_value" class="ltr" required></div>
                    <div><label>سقف پاداش</label><input name="max_reward" class="ltr" placeholder="اختیاری"></div>
                    <button class="btn">افزودن قانون</button>
                </form>
            </details>

            <div class="loyalty-rule-list">
                @forelse($rules as $rule)
                    <details class="loyalty-rule-card {{ $rule->is_active ? 'is-active' : 'is-inactive' }}">
                        <summary>
                            <span class="loyalty-settings-arrow">⌄</span>
                            <div><h4>{{ $rule->name }}</h4><small>{{ $events[$rule->event] ?? $rule->event }}</small></div>
                            <b>{{ $rule->is_active ? 'فعال' : 'غیرفعال' }}</b>
                        </summary>
                        <div class="loyalty-rule-body">
                            <div><span>نوع پاداش</span><b>{{ $rewardTypes[$rule->reward_type] ?? $rule->reward_type }}</b></div>
                            <div><span>مقدار</span><b>@fa($rule->reward_value)</b></div>
                            <div><span>سقف</span><b>{{ $rule->max_reward ? \Modules\Core\Support\Num::fa($rule->max_reward) : 'بدون سقف' }}</b></div>
                            <form method="post" action="{{ url('/app/loyalty/rules/'.$rule->id.'/toggle') }}">
                                @csrf
                                <button class="btn btn-ghost">{{ $rule->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}</button>
                            </form>
                        </div>
                    </details>
                @empty
                    <div class="loyalty-settings-empty">قانونی تعریف نشده است.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="loyalty-settings-board">
        <article class="loyalty-settings-column" style="--settings-color:#3b82f6;">
            <header>
                <div><span></span><h3>مأموریت‌ها</h3></div>
                <b>@fa($missions->count())</b>
            </header>
            <p>مأموریت‌ها رفتارهای ارزشمند مشتری را به هدف، پاداش و انگیزه تبدیل می‌کنند.</p>

            <details class="loyalty-settings-create-panel">
                <summary>افزودن مأموریت</summary>
                <form method="post" action="{{ url('/app/loyalty/missions') }}" class="loyalty-settings-form">
                    @csrf
                    <div><label>عنوان</label><input name="title" required></div>
                    <div><label>رویداد</label><select name="event">@foreach($missionEvents as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label>هدف</label><input name="target" value="1" class="ltr"></div>
                    <div><label>نوع پاداش</label><select name="reward_type"><option value="points_fixed">امتیاز ثابت</option><option value="cashback_fixed">کیف پول ثابت</option></select></div>
                    <div><label>مقدار پاداش</label><input name="reward_value" class="ltr" value="0"></div>
                    <div class="loyalty-settings-wide"><label>توضیح</label><input name="description"></div>
                    <button class="btn">افزودن مأموریت</button>
                </form>
            </details>

            <div class="loyalty-mission-list">
                @forelse($missions as $mission)
                    <article class="loyalty-mission-card {{ $mission->is_active ? 'is-active' : 'is-inactive' }}">
                        <header><h4>{{ $mission->title }}</h4><span>{{ $mission->is_active ? 'فعال' : 'غیرفعال' }}</span></header>
                        <p>{{ $mission->description ?: 'توضیحی ثبت نشده است.' }}</p>
                        <div class="loyalty-settings-mini-grid">
                            <div><span>رویداد</span><b>{{ $missionEvents[$mission->event] ?? $mission->event }}</b></div>
                            <div><span>هدف</span><b>@fa($mission->target)</b></div>
                            <div><span>پاداش</span><b>@fa($mission->reward_value)</b></div>
                        </div>
                    </article>
                @empty
                    <div class="loyalty-settings-empty">مأموریتی تعریف نشده است.</div>
                @endforelse
            </div>
        </article>

        <article class="loyalty-settings-column" style="--settings-color:#8b5cf6;">
            <header>
                <div><span></span><h3>نشان‌های افتخار</h3></div>
                <b>@fa($badges->count())</b>
            </header>
            <p>نشان‌ها به مشتری حس پیشرفت، موفقیت و جایگاه ویژه می‌دهند.</p>

            <details class="loyalty-settings-create-panel">
                <summary>افزودن نشان</summary>
                <form method="post" action="{{ url('/app/loyalty/badges') }}" class="loyalty-settings-form">
                    @csrf
                    <div><label>عنوان</label><input name="title" required></div>
                    <div><label>آیکن</label><input name="icon" value="🏅"></div>
                    <div><label>رنگ</label><input name="color" value="#38bdf8" class="ltr"></div>
                    <div><label>شرط</label><select name="condition_type"><option value="points">امتیاز کل</option><option value="referrals">تعداد معرفی</option><option value="orders">تعداد سفارش</option><option value="manual">دستی</option></select></div>
                    <div><label>مقدار شرط</label><input name="condition_value" value="0" class="ltr"></div>
                    <div class="loyalty-settings-wide"><label>توضیح</label><input name="description"></div>
                    <button class="btn">افزودن نشان</button>
                </form>
            </details>

            <div class="loyalty-badge-list">
                @forelse($badges as $badge)
                    <article class="loyalty-badge-card" style="--badge-color: {{ $badge->color ?? '#38bdf8' }};">
                        <div>{{ $badge->icon ?: '🏅' }}</div>
                        <section><h4>{{ $badge->title }}</h4><span>{{ $badge->description ?: 'بدون توضیح' }}</span></section>
                        <aside><b>{{ $conditionLabels[$badge->condition_type] ?? $badge->condition_type }}</b><small>@fa($badge->condition_value)</small></aside>
                    </article>
                @empty
                    <div class="loyalty-settings-empty">نشانی تعریف نشده است.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="loyalty-settings-board">
        <article class="loyalty-settings-column" style="--settings-color:#ef4444;">
            <header>
                <div><span></span><h3>تبدیل امتیاز به کد تخفیف</h3></div>
                <b>@fa($redemptionRules->count())</b>
            </header>
            <p>قوانین تبدیل، امتیازهای مشتریان را به کد تخفیف قابل استفاده تبدیل می‌کنند.</p>

            <details class="loyalty-settings-create-panel">
                <summary>ساخت قانون تبدیل</summary>
                <form method="post" action="{{ url('/app/loyalty/redemption-rules') }}" class="loyalty-settings-form">
                    @csrf
                    <div class="loyalty-settings-wide"><label>عنوان</label><input name="title" placeholder="کد تخفیف ۱۰٪ با ۲۰۰۰ امتیاز" required></div>
                    <div><label>امتیاز لازم</label><input name="points_required" class="ltr" required></div>
                    <div><label>نوع تخفیف</label><select name="discount_type"><option value="fixed">مبلغی</option><option value="percent">درصدی</option></select></div>
                    <div><label>مقدار تخفیف</label><input name="discount_value" class="ltr" required></div>
                    <div><label>حداقل خرید</label><input name="min_order_total" class="ltr" value="0"></div>
                    <div><label>اعتبار روز</label><input name="expires_days" class="ltr" value="30"></div>
                    <div><label>دفعات مصرف</label><input name="usage_limit" class="ltr" value="1"></div>
                    <div class="loyalty-settings-wide"><label>توضیح</label><input name="description"></div>
                    <button class="btn">ساخت قانون تبدیل</button>
                </form>
            </details>

            <div class="loyalty-redemption-list">
                @forelse($redemptionRules as $rule)
                    <details class="loyalty-redemption-card {{ $rule->is_active ? 'is-active' : 'is-inactive' }}">
                        <summary>
                            <span class="loyalty-settings-arrow">⌄</span>
                            <div><h4>{{ $rule->title }}</h4><small>@fa(number_format($rule->points_required)) امتیاز لازم</small></div>
                            <b>{{ $rule->is_active ? 'فعال' : 'غیرفعال' }}</b>
                        </summary>
                        <div class="loyalty-rule-body">
                            <div><span>تخفیف</span><b>{{ $rule->discount_type === 'percent' ? \Modules\Core\Support\Num::fa($rule->discount_value) . '٪' : \Modules\Core\Support\Money::show($rule->discount_value) . ' تومان' }}</b></div>
                            <div><span>حداقل خرید</span><b>@money($rule->min_order_total) تومان</b></div>
                            <div><span>اعتبار</span><b>@fa($rule->expires_days) روز</b></div>
                            <form method="post" action="{{ url('/app/loyalty/redemption-rules/'.$rule->id.'/toggle') }}">@csrf<button class="btn btn-ghost">{{ $rule->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}</button></form>
                        </div>
                    </details>
                @empty
                    <div class="loyalty-settings-empty">قانونی تعریف نشده است.</div>
                @endforelse
            </div>
        </article>

        <article class="loyalty-settings-column" style="--settings-color:#06b6d4;">
            <header>
                <div><span></span><h3>کدهای تخفیف عمومی</h3></div>
                <b>@fa($coupons->count())</b>
            </header>
            <p>کدهای تخفیف عمومی برای استفاده در کمپین‌ها یا ارسال دستی به مشتریان ساخته می‌شوند.</p>
            <a class="btn btn-ghost loyalty-settings-export" href="{{ url('/app/loyalty/coupons/export') }}">دریافت فایل کدهای تخفیف برای فروشگاه</a>

            <details class="loyalty-settings-create-panel">
                <summary>ساخت کد تخفیف عمومی</summary>
                <form method="post" action="{{ url('/app/loyalty/coupons') }}" class="loyalty-settings-form">
                    @csrf
                    <div><label>عنوان</label><input name="title" required></div>
                    <div><label>نوع</label><select name="discount_type"><option value="fixed">مبلغ ثابت</option><option value="percent">درصدی</option></select></div>
                    <div><label>مقدار</label><input name="discount_value" class="ltr" required></div>
                    <div><label>حداقل خرید</label><input name="min_order_total" class="ltr" value="0"></div>
                    <div><label>انقضا</label><input name="expires_at" class="jdate" placeholder="۱۴۰۵/۰۳/۲۶"></div>
                    <button class="btn">ساخت کد تخفیف</button>
                </form>
            </details>

            <div class="loyalty-coupon-list">
                @forelse($coupons as $coupon)
                    <article class="loyalty-coupon-card">
                        <h4 class="ltr">{{ $coupon->code }}</h4>
                        <span>{{ $coupon->title }}</span>
                        <b>@fa(number_format($coupon->discount_value))</b>
                    </article>
                @empty
                    <div class="loyalty-settings-empty">کد تخفیفی ساخته نشده است.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection