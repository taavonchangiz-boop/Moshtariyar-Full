@extends('layouts.customer_portal')
@section('title','سفر من')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-journey.css') }}">
<div class="club-journey-page">
    <section class="club-journey-hero">
        <div>
            <span class="club-journey-eyebrow">سفر مشتری من</span>
            <h1>همه مسیر شما با برند، در یک نقشه زنده 🧭</h1>
            <p>خریدها، امتیازها، جایزه‌های گردونه، اعلان‌ها و درخواست‌های پشتیبانی شما در یک مسیر زمانی مرتب شده‌اند تا بدانید تا امروز چه اتفاق‌هایی افتاده و قدم بعدی چیست.</p>
            <div class="club-journey-actions">
                <a class="btn" href="{{ route('club.missions') }}">ادامه با مأموریت‌ها</a>
                <a class="btn btn-ghost" href="{{ route('club.orders') }}">خریدهای من</a>
                <a class="btn btn-ghost" href="{{ route('club.rewards') }}">جایزه‌های من</a>
            </div>
        </div>
        <div class="club-journey-score-grid">
            <div><span>تعداد خریدها</span><b>@fa(number_format($stats['orders'] ?? 0))</b><small>سفارش‌های ثبت‌شده</small></div>
            <div><span>ارزش خرید</span><b>@money($stats['spent'] ?? 0) @unit</b><small>مجموع خریدهای شما</small></div>
            <div><span>جایزه‌های گردونه</span><b>@fa(number_format($stats['rewards'] ?? 0))</b><small>بردها و نتیجه‌های ثبت‌شده</small></div>
        </div>
    </section>

    <section class="club-journey-lanes">
        <article class="club-journey-lane" style="--lane-color:#0ea5e9;">
            <i>👤</i>
            <h3>شروع رابطه</h3>
            <p>ثبت‌نام، تکمیل پروفایل و ورود به باشگاه، شروع مسیر وفاداری شماست.</p>
        </article>
        <article class="club-journey-lane" style="--lane-color:#10b981;">
            <i>🛍️</i>
            <h3>خرید و تعامل</h3>
            <p>هر خرید و هر تعامل مهم، داده ارزشمند و فرصت پاداش تازه می‌سازد.</p>
        </article>
        <article class="club-journey-lane" style="--lane-color:#f59e0b;">
            <i>🎁</i>
            <h3>پاداش و بازی</h3>
            <p>گردونه، کد تخفیف، مأموریت و نشان‌ها، مسیر را جذاب‌تر می‌کنند.</p>
        </article>
        <article class="club-journey-lane" style="--lane-color:#8b5cf6;">
            <i>🎫</i>
            <h3>پشتیبانی و اعتماد</h3>
            <p>درخواست‌ها و پاسخ‌ها کنار خریدها ثبت می‌شوند تا ارتباط شفاف بماند.</p>
        </article>
    </section>

    <section class="club-journey-grid">
        <article class="club-journey-card is-accent" style="--journey-card-color:#0ea5e9;">
            <header>
                <div>
                    <span>خط زمانی</span>
                    <h2>آخرین رویدادهای شما</h2>
                </div>
                <span class="club-journey-badge">@fa(number_format($timeline->count())) رویداد</span>
            </header>
            <div class="club-journey-timeline">
                @forelse($timeline as $item)
                    <article class="club-journey-event" style="--event-color: {{ $item['color'] }};">
                        <div class="club-journey-event-icon">{{ $item['icon'] }}</div>
                        <div>
                            <span class="club-journey-chip">{{ $item['type'] }}</span>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['body'] }}</p>
                            <small>{{ \Modules\Core\Support\Jalali::datetime($item['date']) }}</small>
                        </div>
                        @if(!empty($item['url']))
                            <a class="btn btn-ghost" href="{{ $item['url'] }}">مشاهده</a>
                        @endif
                    </article>
                @empty
                    <div class="club-journey-empty">هنوز رویدادی برای سفر شما ثبت نشده است. با تکمیل پروفایل، خرید یا چرخاندن گردونه، مسیر شما شروع می‌شود.</div>
                @endforelse
            </div>
        </article>

        <aside class="club-journey-card is-accent" style="--journey-card-color:#10b981;">
            <header>
                <div>
                    <span>قدم بعدی</span>
                    <h2>پیشنهادهای هوشمند</h2>
                </div>
            </header>
            <div class="club-journey-next-list">
                @foreach($nextActions as $action)
                    <article class="club-journey-next-card" style="--next-color: {{ $action['color'] }};">
                        <i>{{ $action['icon'] }}</i>
                        <div>
                            <h3>{{ $action['title'] }}</h3>
                            <p>{{ $action['body'] }}</p>
                            <div class="club-journey-actions"><a class="btn btn-ghost" href="{{ $action['url'] }}">انجام بده</a></div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="club-journey-card" style="margin-top:1rem;box-shadow:none">
                <header>
                    <div>
                        <span>وضعیت ارتباط</span>
                        <h2>درخواست‌های باز</h2>
                    </div>
                    <span class="club-journey-badge">@fa(number_format($stats['tickets'] ?? 0)) مورد</span>
                </header>
                <p>اگر درباره خرید، امتیاز یا جایزه‌ای سوال دارید، از مرکز پشتیبانی پیگیری کنید.</p>
                <div class="club-journey-actions"><a class="btn btn-ghost" href="{{ route('club.tickets') }}">رفتن به پشتیبانی</a></div>
            </div>
        </aside>
    </section>
</div>
@endsection