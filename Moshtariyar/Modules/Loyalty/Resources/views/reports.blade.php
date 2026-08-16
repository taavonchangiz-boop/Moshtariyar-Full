@extends('layouts.app')
@section('title','داشبورد تحلیلی باشگاه')
@section('heading','داشبورد تحلیلی وفاداری')
@section('subtitle','تحلیل اعضا، امتیاز، کیف پول، معرفی دوستان، اثر سفارش‌ها و برگشت پاداش‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('css/loyalty-reports-board.css') }}">

@php
    $pointsCurrent = (float) ($stats['points_current'] ?? 0);
    $pointsLifetime = (float) ($stats['points_lifetime'] ?? 0);
    $pointsUsedOrExpired = max(0, $pointsLifetime - $pointsCurrent);
    $rewardedMembers = (int) ($stats['rewarded_members'] ?? 0);
    $membersCount = max(1, (int) ($stats['members'] ?? 0));
    $rewardCoverage = round(($rewardedMembers / $membersCount) * 100, 1);
    $referrals = (int) ($stats['referrals'] ?? 0);
    $referredBuyers = (int) ($stats['referred_buyers'] ?? 0);
    $referralConversion = $referrals > 0 ? round(($referredBuyers / max(1, $referrals)) * 100, 1) : 0;
    $orderRewardsTotal = (float) ($stats['order_points'] ?? 0) + (float) ($stats['order_wallet'] ?? 0);
    $rollbackTotal = (float) ($stats['rollback_points'] ?? 0) + (float) ($stats['rollback_wallet'] ?? 0);
    $rollbackRatio = $orderRewardsTotal > 0 ? round(($rollbackTotal / max(1, $orderRewardsTotal)) * 100, 1) : 0;

    $boardSections = collect([
        ['key' => 'purchase', 'label' => 'اثر خریدها', 'color' => '#3b82f6', 'count' => $topPurchaseEffects->count(), 'hint' => 'اعضایی که از سفارش‌ها بیشترین پاداش گرفته‌اند'],
        ['key' => 'referral', 'label' => 'معرف‌های برتر', 'color' => '#10b981', 'count' => $topReferrers->count(), 'hint' => 'اعضایی که بیشترین دعوت مستقیم را ساخته‌اند'],
        ['key' => 'points', 'label' => 'برترین امتیازها', 'color' => '#f59e0b', 'count' => $topPoints->count(), 'hint' => 'اعضایی با بیشترین امتیاز کل ثبت‌شده'],
        ['key' => 'upgrade', 'label' => 'نزدیک ارتقا', 'color' => '#8b5cf6', 'count' => $nearTier->count(), 'hint' => 'اعضایی که برای ارتقای سطح مناسب بررسی هستند'],
        ['key' => 'rollback', 'label' => 'برگشت‌ها', 'color' => '#ef4444', 'count' => $recentRollbacks->count(), 'hint' => 'برگشت‌های ناشی از لغو یا مرجوعی سفارش'],
    ]);
@endphp

<div class="loyalty-report-page">
    <section class="loyalty-report-hero">
        <div class="loyalty-report-hero-main">
            <span>داشبورد وفاداری و رشد</span>
            <h2>اثر واقعی باشگاه مشتریان را روی خرید، معرفی و پاداش ببینید</h2>
            <p>این صفحه نشان می‌دهد پاداش‌ها چگونه بین اعضا توزیع شده‌اند، معرفی دوستان چقدر فروش ساخته، چه مقدار امتیاز و کیف پول صادر شده و کجا نیازمند بهینه‌سازی هستیم.</p>
        </div>
        <div class="loyalty-report-hero-metrics">
            <div>
                <span>اعضای باشگاه</span>
                <b>@fa(number_format($stats['members'] ?? 0))</b>
                <small>کل اعضای ثبت‌شده</small>
            </div>
            <div>
                <span>پوشش پاداش خرید</span>
                <b>@fa($rewardCoverage)٪</b>
                <small>اعضای پاداش‌گرفته از خرید</small>
            </div>
            <div>
                <span>تبدیل معرفی به خریدار</span>
                <b>@fa($referralConversion)٪</b>
                <small>خریداران معرفی‌شده</small>
            </div>
            <div>
                <span>نسبت برگشت پاداش</span>
                <b>@fa($rollbackRatio)٪</b>
                <small>نسبت برگشت به پاداش خرید</small>
            </div>
        </div>
    </section>

    <section class="loyalty-report-metrics">
        <article><span>امتیاز فعلی</span><b>@fa(number_format($stats['points_current'] ?? 0))</b><small>امتیاز فعال اعضا</small></article>
        <article><span>امتیاز کل صادرشده</span><b>@fa(number_format($stats['points_lifetime'] ?? 0))</b><small>کل امتیاز طول عمر</small></article>
        <article><span>امتیاز مصرف یا برگشت</span><b>@fa(number_format($pointsUsedOrExpired))</b><small>اختلاف کل با موجودی فعلی</small></article>
        <article><span>کیف پول فعال</span><b>@money($stats['wallet'] ?? 0) تومان</b><small>اعتبار فعلی اعضا</small></article>
        <article><span>کل معرفی‌ها</span><b>@fa(number_format($stats['referrals'] ?? 0))</b><small>همه دعوت‌های ثبت‌شده</small></article>
        <article><span>فروش ناشی از معرفی</span><b>@money($stats['referral_sales'] ?? 0) تومان</b><small>خریدهای زیرمجموعه‌ها</small></article>
        <article><span>پاداش خرید</span><b>@fa(number_format($stats['order_points'] ?? 0))</b><small>امتیاز داده‌شده از سفارش</small></article>
        <article><span>اعتبار خرید</span><b>@money($stats['order_wallet'] ?? 0) تومان</b><small>کیف پول داده‌شده از سفارش</small></article>
    </section>

    <section class="loyalty-report-insights">
        <div class="loyalty-report-insight-card is-good">
            <span>تحلیل معرفی</span>
            <b>@fa($referredBuyers) خریدار از معرفی</b>
            <p>از @fa($referrals) معرفی ثبت‌شده، @fa($referredBuyers) مورد به خرید معتبر رسیده‌اند.</p>
        </div>
        <div class="loyalty-report-insight-card is-warning">
            <span>تحلیل پاداش</span>
            <b>@fa($rewardCoverage)٪ پوشش پاداش خرید</b>
            <p>اگر این عدد پایین است، قانون‌های پاداش خرید یا اطلاع‌رسانی به اعضا باید بررسی شود.</p>
        </div>
        <div class="loyalty-report-insight-card is-danger">
            <span>تحلیل برگشت</span>
            <b>@fa($rollbackRatio)٪ برگشت پاداش</b>
            <p>برگشت زیاد می‌تواند نشانه لغو سفارش، مرجوعی یا نیاز به کنترل قوانین آزادسازی باشد.</p>
        </div>
    </section>

    <section class="loyalty-report-board-shell">
        <div class="loyalty-report-board">
            @foreach($boardSections as $section)
                <section class="loyalty-report-column" style="--report-color: {{ $section['color'] }};">
                    <header>
                        <div>
                            <span></span>
                            <h3>{{ $section['label'] }}</h3>
                        </div>
                        <b>@fa($section['count'])</b>
                    </header>
                    <p>{{ $section['hint'] }}</p>

                    <div class="loyalty-report-card-list">
                        @if($section['key'] === 'purchase')
                            @forelse($topPurchaseEffects as $member)
                                <details class="loyalty-report-mini-card">
                                    <summary class="loyalty-report-mini-summary">
                                        <span class="loyalty-report-arrow">⌄</span>
                                        <div class="loyalty-report-mini-title">
                                            <h4>{{ $member->customer?->full_name ?? 'عضو بدون نام' }}</h4>
                                            <small>{{ $member->tier?->name ?? 'بدون سطح' }}</small>
                                        </div>
                                        <strong>@fa(number_format($member->purchase_points ?? 0))</strong>
                                    </summary>
                                    <div class="loyalty-report-mini-body">
                                        <div class="loyalty-report-mini-grid">
                                            <div><span>امتیاز خرید</span><b>@fa(number_format($member->purchase_points ?? 0))</b></div>
                                            <div><span>کیف پول خرید</span><b>@money($member->purchase_wallet ?? 0) تومان</b></div>
                                        </div>
                                        <footer>
                                            <a href="{{ url('/app/loyalty/' . $member->id) }}">پرونده باشگاه</a>
                                            @if($member->customer)
                                                <a href="{{ url('/app/customers/' . $member->customer->id) }}">پرونده ۳۶۰</a>
                                            @endif
                                        </footer>
                                    </div>
                                </details>
                            @empty
                                <div class="loyalty-report-empty">هنوز اثر خریدی ثبت نشده است.</div>
                            @endforelse
                        @elseif($section['key'] === 'referral')
                            @forelse($topReferrers as $member)
                                <details class="loyalty-report-mini-card">
                                    <summary class="loyalty-report-mini-summary">
                                        <span class="loyalty-report-arrow">⌄</span>
                                        <div class="loyalty-report-mini-title">
                                            <h4>{{ $member->customer?->full_name ?? 'عضو بدون نام' }}</h4>
                                            <small class="ltr">{{ $member->referral_code ?: 'بدون کد' }}</small>
                                        </div>
                                        <strong>@fa($member->direct_referrals_count)</strong>
                                    </summary>
                                    <div class="loyalty-report-mini-body">
                                        <div class="loyalty-report-mini-grid">
                                            <div><span>معرفی مستقیم</span><b>@fa($member->direct_referrals_count)</b></div>
                                            <div><span>کد معرفی</span><b class="ltr">{{ $member->referral_code ?: '—' }}</b></div>
                                        </div>
                                        <footer>
                                            <a href="{{ url('/app/loyalty/' . $member->id) }}">پرونده باشگاه</a>
                                        </footer>
                                    </div>
                                </details>
                            @empty
                                <div class="loyalty-report-empty">معرفی فعالی ثبت نشده است.</div>
                            @endforelse
                        @elseif($section['key'] === 'points')
                            @forelse($topPoints as $member)
                                <details class="loyalty-report-mini-card">
                                    <summary class="loyalty-report-mini-summary">
                                        <span class="loyalty-report-arrow">⌄</span>
                                        <div class="loyalty-report-mini-title">
                                            <h4>{{ $member->customer?->full_name ?? 'عضو بدون نام' }}</h4>
                                            <small>{{ $member->tier?->name ?? 'بدون سطح' }}</small>
                                        </div>
                                        <strong>@fa(number_format($member->points_lifetime))</strong>
                                    </summary>
                                    <div class="loyalty-report-mini-body">
                                        <div class="loyalty-report-mini-grid">
                                            <div><span>امتیاز کل</span><b>@fa(number_format($member->points_lifetime))</b></div>
                                            <div><span>امتیاز فعلی</span><b>@fa(number_format($member->points))</b></div>
                                        </div>
                                        <footer>
                                            <a href="{{ url('/app/loyalty/' . $member->id) }}">پرونده باشگاه</a>
                                        </footer>
                                    </div>
                                </details>
                            @empty
                                <div class="loyalty-report-empty">داده‌ای برای امتیازها وجود ندارد.</div>
                            @endforelse
                        @elseif($section['key'] === 'upgrade')
                            @forelse($nearTier as $member)
                                <details class="loyalty-report-mini-card">
                                    <summary class="loyalty-report-mini-summary">
                                        <span class="loyalty-report-arrow">⌄</span>
                                        <div class="loyalty-report-mini-title">
                                            <h4>{{ $member->customer?->full_name ?? 'عضو بدون نام' }}</h4>
                                            <small>{{ $member->tier?->name ?? 'بدون سطح' }}</small>
                                        </div>
                                        <strong>@fa(number_format($member->points_lifetime))</strong>
                                    </summary>
                                    <div class="loyalty-report-mini-body">
                                        <div class="loyalty-report-mini-grid">
                                            <div><span>سطح فعلی</span><b>{{ $member->tier?->name ?? 'بدون سطح' }}</b></div>
                                            <div><span>امتیاز کل</span><b>@fa(number_format($member->points_lifetime))</b></div>
                                        </div>
                                        <footer>
                                            <a href="{{ url('/app/loyalty/' . $member->id) }}">بررسی ارتقا</a>
                                        </footer>
                                    </div>
                                </details>
                            @empty
                                <div class="loyalty-report-empty">عضوی برای بررسی ارتقا وجود ندارد.</div>
                            @endforelse
                        @elseif($section['key'] === 'rollback')
                            @forelse($recentRollbacks as $transaction)
                                <details class="loyalty-report-mini-card">
                                    <summary class="loyalty-report-mini-summary">
                                        <span class="loyalty-report-arrow">⌄</span>
                                        <div class="loyalty-report-mini-title">
                                            <h4>{{ $transaction->member?->customer?->full_name ?? 'عضو بدون نام' }}</h4>
                                            <small>@jdatetime($transaction->created_at)</small>
                                        </div>
                                        <strong>@fa(number_format($transaction->amount))</strong>
                                    </summary>
                                    <div class="loyalty-report-mini-body">
                                        <div class="loyalty-report-mini-grid">
                                            <div><span>نوع</span><b>{{ \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS[$transaction->kind] ?? $transaction->kind }}</b></div>
                                            <div><span>مقدار</span><b>@fa(number_format($transaction->amount))</b></div>
                                        </div>
                                        <p>{{ $transaction->reason }}</p>
                                    </div>
                                </details>
                            @empty
                                <div class="loyalty-report-empty">برگشتی ثبت نشده است.</div>
                            @endforelse
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </section>

    <section class="modern-card" style="padding:0; overflow:hidden; margin-bottom: 2rem;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--line);">
            <span class="badge b-ok" style="margin-bottom: 0.5rem;">اثر سفارش‌ها</span>
            <h3 style="margin: 0;">پاداش‌ها و برگشت‌های ناشی از سفارش‌های اخیر</h3>
        </div>
        <div class="modern-table-wrap" style="border-radius:0; border:none; overflow-x:auto;">
            <table class="modern-table" style="width:100%; min-width:800px; text-align:right;">
                <thead>
                    <tr><th style="padding:1rem;">سفارش</th><th style="padding:1rem;">مشتری</th><th style="padding:1rem;">وضعیت</th><th style="padding:1rem;">امتیاز داده‌شده</th><th style="padding:1rem;">کیف پول داده‌شده</th><th style="padding:1rem;">امتیاز برگشتی</th><th style="padding:1rem;">کیف پول برگشتی</th><th style="padding:1rem;">تاریخ</th></tr>
                </thead>
                <tbody>
                    @forelse($recentOrderEffects as $row)
                        <tr>
                            <td style="padding:1rem;" data-label="سفارش"><a href="{{ url('/app/orders/' . $row['order']->id) }}" style="font-weight:bold;">سفارش شماره @fa($row['order']->number ?: $row['order']->id)</a></td>
                            <td style="padding:1rem;" data-label="مشتری"><b>{{ $row['customer']?->full_name ?? '—' }}</b></td>
                            <td style="padding:1rem;" data-label="وضعیت"><span class="badge b-mut">{{ ['pending'=>'در انتظار','processing'=>'در حال آماده‌سازی','on_hold'=>'معلق','completed'=>'تکمیل‌شده','cancelled'=>'لغو شده','refunded'=>'مرجوعی','failed'=>'ناموفق'][$row['order']->status] ?? $row['order']->status }}</span></td>
                            <td style="padding:1rem;" data-label="امتیاز داده‌شده" style="color:var(--ok); font-weight:bold;">@fa(number_format($row['points_added']))</td>
                            <td style="padding:1rem;" data-label="کیف پول داده‌شده" style="color:var(--ok); font-weight:bold;">@money($row['wallet_added']) تومان</td>
                            <td style="padding:1rem;" data-label="امتیاز برگشتی" style="color:var(--bad);">@fa(number_format($row['points_back']))</td>
                            <td style="padding:1rem;" data-label="کیف پول برگشتی" style="color:var(--bad);">@money($row['wallet_back']) تومان</td>
                            <td style="padding:1rem;" data-label="تاریخ" style="color:var(--mut);">@fa(\Modules\Core\Support\Jalali::datetime(\Carbon\Carbon::parse($row['order']->placed_at)))</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center; padding:3rem; color:var(--mut);">هنوز اثری از سفارش‌ها در باشگاه ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="modern-card" style="padding:0; overflow:hidden; margin-bottom: 2rem;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--line);">
            <span class="badge b-acc" style="margin-bottom: 0.5rem;">تراکنش‌های اخیر</span>
            <h3 style="margin: 0;">تراکنش‌های ۳۰ روز اخیر باشگاه</h3>
        </div>
        <div class="modern-table-wrap" style="border-radius:0; border:none; overflow-x:auto;">
            <table class="modern-table" style="width:100%; min-width:800px; text-align:right;">
                <thead>
                    <tr><th style="padding:1rem;">تاریخ</th><th style="padding:1rem;">نوع تراکنش</th><th style="padding:1rem;">جهت</th><th style="padding:1rem;">جمع مقدار</th><th style="padding:1rem;">تعداد تراکنش‌ها</th></tr>
                </thead>
                <tbody>
                    @forelse($dailyTransactions as $row)
                        <tr>
                            <td style="padding:1rem;" data-label="تاریخ" style="font-weight:bold;">@fa(\Modules\Core\Support\Jalali::date(\Carbon\Carbon::parse($row->d)))</td>
                            <td style="padding:1rem;" data-label="نوع تراکنش">{{ \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS[$row->kind] ?? $row->kind }}</td>
                            <td style="padding:1rem;" data-label="جهت">
                                @if($row->direction === 'credit')
                                    <span class="badge b-ok">واریز (افزایش)</span>
                                @else
                                    <span class="badge b-bad">برداشت (کاهش)</span>
                                @endif
                            </td>
                            <td style="padding:1rem;" data-label="جمع مقدار" style="font-weight:bold; font-size:1.1rem;">@fa(number_format($row->total))</td>
                            <td style="padding:1rem;" data-label="تعداد تراکنش‌ها" style="color:var(--mut);">@fa($row->cnt) تراکنش</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center; padding:3rem; color:var(--mut);">داده‌ای وجود ندارد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection