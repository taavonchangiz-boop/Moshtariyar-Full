@extends('layouts.customer_portal')
@section('title','پیشخوان باشگاه')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-dashboard.css') }}">
@php
    $profileDone = (bool) $member->profile_completed;
    $typeLabels = \Modules\Loyalty\Entities\WheelPrize::TYPES;
    $customerName = trim((string) ($member->customer->full_name ?? 'مشتری عزیز'));
    $initials = mb_substr($customerName, 0, 1);
    $avatar = $member->customer?->meta['avatar'] ?? null;
    $levelName = $member->tier?->name ?? 'عضو تازه‌وارد';
    $levelProgress = min(100, max(12, (int) (($member->points_lifetime % 1000) / 10)));
    $todayWheelText = $canSpinToday ? 'آماده چرخش' : 'جایزه امروز ثبت شده';
@endphp

<div class="club-dashboard-page">
    <section class="club-dashboard-hero">
        <div class="club-dashboard-hero-main">
            <span class="club-dashboard-eyebrow">پیشخوان اختصاصی باشگاه</span>
            <div class="club-dashboard-title-row">
                <div class="club-dashboard-avatar">
                    @if($avatar)
                        <img src="{{ asset($avatar) }}" alt="{{ $customerName }}">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                <div>
                    <h1>{{ $customerName }} عزیز، به باشگاه مشتریان خوش آمدید 👋</h1>
                    <p>اینجا مرکز امتیازها، کیف پول، گردونه شانس، کدهای تخفیف، مأموریت‌ها و ارتباط مستقیم شما با کسب‌وکار است.</p>
                </div>
            </div>
            <div class="club-dashboard-actions">
                <a class="btn" href="{{ route('club.wheel') }}">{{ $canSpinToday ? 'چرخاندن گردونه امروز' : 'مشاهده جایزه امروز' }}</a>
                <a class="btn btn-ghost" href="{{ route('club.rewards') }}">جایزه‌های من</a>
                <a class="btn btn-ghost" href="{{ route('club.profile') }}">{{ $profileDone ? 'ویرایش پروفایل' : 'تکمیل پروفایل' }}</a>
            </div>
        </div>
        <aside class="club-dashboard-hero-side">
            <div class="club-dashboard-level-card">
                <span>سطح فعلی شما</span>
                <strong>{{ $levelName }}</strong>
                <div>
                    <b>@fa(number_format($member->points_lifetime))</b>
                    <small>امتیاز کل ثبت‌شده در باشگاه</small>
                </div>
                <div class="club-dashboard-progress"><span style="--progress: {{ $levelProgress }}%;"></span></div>
                <small>با خرید، مأموریت، دعوت دوستان و گردونه شانس، مسیر رشد شما کامل‌تر می‌شود.</small>
            </div>
        </aside>
    </section>

    <section class="club-dashboard-metrics">
        <article class="club-dashboard-metric" style="--metric-color:var(--acc)">
            <span>امتیاز قابل استفاده</span>
            <b>@fa(number_format($member->points))</b>
            <small>برای تبدیل به کد تخفیف و پاداش</small>
        </article>
        <article class="club-dashboard-metric" style="--metric-color:var(--ok)">
            <span>کیف پول</span>
            <b>@money($member->wallet_balance) @unit</b>
            <small>اعتبار شما در باشگاه</small>
        </article>
        <article class="club-dashboard-metric" style="--metric-color:var(--vio)">
            <span>سطح عضویت</span>
            <b>{{ $member->tier?->name ?? 'بدون سطح' }}</b>
            <small>جایگاه فعلی شما</small>
        </article>
        <article class="club-dashboard-metric" style="--metric-color:var(--warn)">
            <span>دوستان معرفی‌شده</span>
            <b>@fa(number_format($referralsCount))</b>
            <small>زیرمجموعه‌های مستقیم شما</small>
        </article>
    </section>

    <section class="club-dashboard-grid">
        <article class="club-dashboard-card is-accent" style="--card-color:var(--acc)">
            <header>
                <div>
                    <span class="club-dashboard-eyebrow">مسیرهای سریع</span>
                    <h2>از کجا شروع کنیم؟</h2>
                </div>
                <span class="club-dashboard-badge {{ $profileDone ? 'is-ok' : 'is-warn' }}">{{ $profileDone ? 'پروفایل کامل' : 'پروفایل ناقص' }}</span>
            </header>
            <div class="club-dashboard-shortcuts">
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--warn)" href="{{ route('club.wheel') }}">
                    <i>🎡</i><b>گردونه شانس</b><small>{{ $todayWheelText }}</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--ok)" href="{{ route('club.missions') }}">
                    <i>🎯</i><b>مأموریت‌ها</b><small>فعالیت کنید و پاداش بگیرید</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--acc)" href="{{ route('club.coupons') }}">
                    <i>٪</i><b>کدهای تخفیف</b><small>امتیاز را به خرید تبدیل کنید</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--ok)" href="{{ route('club.orders') }}">
                    <i>🛍️</i><b>خریدهای من</b><small>سفارش‌ها و امتیاز خریدها</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--acc)" href="{{ route('club.card') }}">
                    <i>💳</i><b>کارت عضویت</b><small>کد معرف و کارت دیجیتال</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--vio)" href="{{ route('club.journey') }}">
                    <i>🧭</i><b>سفر من</b><small>همه رویدادها و قدم بعدی</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--vio)" href="{{ route('club.badges') }}">
                    <i>🏅</i><b>نشان‌ها</b><small>افتخارات باشگاهی شما</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--ok)" href="{{ route('club.referrals') }}">
                    <i>🤝</i><b>دعوت دوستان</b><small>معرفی کنید و رشد کنید</small>
                </a>
                <a class="club-dashboard-shortcut" style="--shortcut-color:var(--acc)" href="{{ route('club.tickets') }}">
                    <i>🎫</i><b>پشتیبانی</b><small>درخواست‌های خود را پیگیری کنید</small>
                </a>
            </div>
        </article>

        <article class="club-dashboard-card is-accent" style="--card-color:{{ $canSpinToday ? 'var(--warn)' : 'var(--ok)' }}">
            <header>
                <div>
                    <span class="club-dashboard-eyebrow">گردونه امروز</span>
                    <h2>{{ $canSpinToday ? 'شانس امروز منتظر شماست' : 'جایزه امروز ثبت شد' }}</h2>
                </div>
                <span class="club-dashboard-badge {{ $canSpinToday ? 'is-warn' : 'is-ok' }}">{{ $canSpinToday ? 'آماده' : 'انجام شد' }}</span>
            </header>
            <p>گردونه شانس می‌تواند امتیاز، کیف پول، کد تخفیف یا جایزه ویژه برای شما ثبت کند.</p>
            <div class="club-dashboard-list" style="margin-top:.85rem">
                <div class="club-dashboard-row">
                    <div class="club-dashboard-icon" style="--icon-color:var(--warn)">🎁</div>
                    <div>
                        <h3>@fa(number_format($activePrizeCount ?? 0)) جایزه فعال</h3>
                        <p>نتیجه چرخش در پروفایل و جایزه‌های شما ذخیره می‌شود.</p>
                    </div>
                    <a class="btn btn-ghost" href="{{ route('club.wheel') }}">ورود</a>
                </div>
            </div>
        </article>
    </section>

    <section class="club-dashboard-grid-reverse">
        <article class="club-dashboard-card is-accent" style="--card-color:var(--ok)">
            <header>
                <div>
                    <span class="club-dashboard-eyebrow">معرفی دوستان</span>
                    <h2>لینک اختصاصی شما</h2>
                </div>
                <a href="{{ route('club.referrals') }}">جزئیات ←</a>
            </header>
            <p>این لینک را برای دوستان خود بفرستید. ثبت‌نام با این لینک به‌عنوان زیرمجموعه شما ثبت می‌شود.</p>
            <label>کد معرف</label>
            <div class="club-dashboard-copy"><input class="ltr" value="{{ $member->referral_code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyDashboardText(this)">کپی</button></div>
            <label>لینک معرفی</label>
            <div class="club-dashboard-copy"><input class="ltr" value="{{ $referralLink }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyDashboardText(this)">کپی</button></div>
        </article>

        <article class="club-dashboard-card">
            <header>
                <div>
                    <span class="club-dashboard-eyebrow">آخرین بردها</span>
                    <h2>جایزه‌های اخیر گردونه</h2>
                </div>
                <a href="{{ route('club.rewards') }}">همه جایزه‌ها ←</a>
            </header>
            <div class="club-dashboard-list">
                @forelse($latestWheelSpins as $spin)
                    <article class="club-dashboard-row">
                        <div class="club-dashboard-icon" style="--icon-color:var(--warn)">
                            @if($spin->prize_image)
                                <img src="{{ asset($spin->prize_image) }}" alt="{{ $spin->prize_title }}">
                            @else
                                🎁
                            @endif
                        </div>
                        <div>
                            <h3>{{ $spin->prize_title ?: 'جایزه گردونه' }}</h3>
                            <p>{{ $typeLabels[$spin->prize_type] ?? 'جایزه' }} · @jdatetime($spin->created_at)</p>
                        </div>
                        <span class="club-dashboard-badge {{ $spin->delivery_status === 'failed' ? 'is-warn' : 'is-ok' }}">{{ $spin->deliveryStatusLabel() }}</span>
                    </article>
                @empty
                    <div class="club-dashboard-empty">هنوز جایزه‌ای از گردونه نگرفته‌اید. امروز شانس خود را امتحان کنید.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="club-dashboard-grid">
        <article class="club-dashboard-card">
            <header>
                <div>
                    <span class="club-dashboard-eyebrow">ردپای امتیاز</span>
                    <h2>آخرین تراکنش‌ها</h2>
                </div>
                <a href="{{ route('club.transactions') }}">همه تراکنش‌ها ←</a>
            </header>
            <div class="club-dashboard-timeline">
                @forelse($transactions as $transaction)
                    <article class="club-dashboard-transaction">
                        <div>
                            <h3>{{ $transaction->reason }}</h3>
                            <p>{{ \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS[$transaction->kind] ?? $transaction->kind }} · {{ $transaction->direction === 'credit' ? 'افزایش' : 'کاهش' }} · مانده @fa(number_format($transaction->balance_after))</p>
                        </div>
                        <strong>@fa(number_format($transaction->amount))</strong>
                    </article>
                @empty
                    <div class="club-dashboard-empty">تراکنشی ثبت نشده است.</div>
                @endforelse
            </div>
        </article>

        <article class="club-dashboard-card is-accent" style="--card-color:{{ $profileDone ? 'var(--ok)' : 'var(--warn)' }}">
            <header>
                <div>
                    <span class="club-dashboard-eyebrow">سلامت حساب</span>
                    <h2>وضعیت باشگاه شما</h2>
                </div>
                <span class="club-dashboard-badge {{ $profileDone ? 'is-ok' : 'is-warn' }}">{{ $profileDone ? 'آماده' : 'نیازمند تکمیل' }}</span>
            </header>
            <div class="club-dashboard-list">
                <article class="club-dashboard-row">
                    <div class="club-dashboard-icon" style="--icon-color:{{ $profileDone ? 'var(--ok)' : 'var(--warn)' }}">{{ $profileDone ? '✓' : '!' }}</div>
                    <div>
                        <h3>{{ $profileDone ? 'پروفایل شما کامل است' : 'پروفایل را کامل کنید' }}</h3>
                        <p>{{ $profileDone ? 'اطلاعات اصلی شما ثبت شده و آماده دریافت پاداش‌های بعدی هستید.' : 'با تکمیل پروفایل، مأموریت مربوط به پروفایل و پاداش‌های آن فعال می‌شود.' }}</p>
                    </div>
                    <a class="btn btn-ghost" href="{{ route('club.profile') }}">{{ $profileDone ? 'ویرایش' : 'تکمیل' }}</a>
                </article>
                <article class="club-dashboard-row">
                    <div class="club-dashboard-icon" style="--icon-color:var(--acc)">🔔</div>
                    <div>
                        <h3>اعلان‌های خوانده‌نشده</h3>
                        <p>@fa(number_format($unreadCount ?? 0)) پیام جدید در مرکز اعلان‌ها</p>
                    </div>
                    <a class="btn btn-ghost" href="{{ route('club.notifications') }}">مشاهده</a>
                </article>
            </div>
        </article>
    </section>
</div>

{{-- Navigation نوار پایین موبایل --}}
<nav class="club-dashboard-mobile-nav">
    <a href="{{ route('club.dashboard') }}" class="active">
        <i>🏠</i>
        داشبورد
    </a>
    <a href="{{ route('club.wheel') }}">
        <i>🎡</i>
        گردونه
    </a>
    <a href="{{ route('club.missions') }}">
        <i>🎯</i>
        مأموریت
    </a>
    <a href="{{ route('club.coupons') }}">
        <i>٪</i>
        تخفیف
    </a>
    <a href="{{ route('club.profile') }}">
        <i>👤</i>
        پروفایل
    </a>
</nav>

<script>
function copyDashboardText(button) {
    const input = button.closest('.club-dashboard-copy')?.querySelector('input');
    if (!input) return;
    input.select();
    document.execCommand('copy');
    button.textContent = 'کپی شد';
    setTimeout(() => button.textContent = 'کپی', 1200);
}
</script>
@endsection