@extends('layouts.customer_portal')
@section('title','دفتر امتیاز و کیف پول')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-transactions.css') }}">
@php
    $kindLabels = \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS;
    $sourceLabels = [
        'manual' => 'ثبت دستی',
        'order_reward' => 'پاداش خرید',
        'rollback' => 'برگشت سفارش',
        'wheel' => 'گردونه شانس',
        'convert' => 'تبدیل امتیاز',
        'loyalty_rule' => 'قانون وفاداری',
        'mission' => 'مأموریت',
        'redemption_rule' => 'ساخت کد تخفیف',
    ];
@endphp
<div class="club-transactions-page">
    <section class="club-transactions-hero">
        <div>
            <span class="club-transactions-eyebrow">دفتر مالی وفاداری</span>
            <h1>همه امتیازها و اعتبار کیف پول شما، شفاف و قابل پیگیری 📊</h1>
            <p>هر امتیاز، هر برداشت، هر جایزه گردونه، هر تبدیل امتیاز و هر اعتبار کیف پول در این دفتر ثبت می‌شود تا همیشه بدانید پاداش‌ها از کجا آمده‌اند و کجا مصرف شده‌اند.</p>
            <div class="club-transactions-actions">
                <a class="btn" href="{{ route('club.coupons') }}">تبدیل امتیاز به کد تخفیف</a>
                <a class="btn btn-ghost" href="{{ route('club.rewards') }}">جایزه‌های من</a>
                <a class="btn btn-ghost" href="{{ route('club.journey') }}">سفر من</a>
            </div>
        </div>
        <div class="club-transactions-score-grid">
            <div><span>مانده امتیاز</span><b>@fa(number_format($member->points))</b><small>قابل استفاده برای پاداش‌ها</small></div>
            <div><span>مانده کیف پول</span><b>@money($member->wallet_balance) @unit</b><small>اعتبار فعلی شما</small></div>
            <div><span>تعداد تراکنش‌ها</span><b>@fa(number_format($stats['count'] ?? 0))</b><small>کل رویدادهای مالی باشگاه</small></div>
        </div>
    </section>

    <section class="club-transactions-metrics">
        <article class="club-transactions-metric" style="--metric-color:var(--ok)"><span>امتیاز دریافت‌شده</span><b>@fa(number_format($stats['point_credit'] ?? 0))</b><small>جمع افزایش امتیاز</small></article>
        <article class="club-transactions-metric" style="--metric-color:var(--bad)"><span>امتیاز مصرف‌شده</span><b>@fa(number_format($stats['point_debit'] ?? 0))</b><small>جمع برداشت امتیاز</small></article>
        <article class="club-transactions-metric" style="--metric-color:var(--acc)"><span>اعتبار کیف پول دریافتی</span><b>@money($stats['wallet_credit'] ?? 0) @unit</b><small>جمع افزایش کیف پول</small></article>
        <article class="club-transactions-metric" style="--metric-color:var(--warn)"><span>برداشت کیف پول</span><b>@money($stats['wallet_debit'] ?? 0) @unit</b><small>جمع مصرف کیف پول</small></article>
    </section>

    <section class="club-transactions-grid">
        <article class="club-transactions-card is-accent" style="--transactions-card-color:#0ea5e9;">
            <header>
                <div>
                    <span>ریز تراکنش‌ها</span>
                    <h2>تاریخچه امتیاز و کیف پول</h2>
                </div>
                <span class="club-transactions-badge">@fa(number_format($transactions->total())) مورد</span>
            </header>

            <form method="get" class="club-transactions-filter">
                <div>
                    <label>جستجو</label>
                    <input name="search" value="{{ request('search') }}" placeholder="جستجو در علت یا منبع تراکنش">
                </div>
                <div>
                    <label>نوع</label>
                    <select name="kind">
                        <option value="">همه</option>
                        <option value="point" @selected(request('kind') === 'point')>امتیاز</option>
                        <option value="wallet" @selected(request('kind') === 'wallet')>کیف پول</option>
                    </select>
                </div>
                <div>
                    <label>جهت</label>
                    <select name="direction">
                        <option value="">همه</option>
                        <option value="credit" @selected(request('direction') === 'credit')>واریز</option>
                        <option value="debit" @selected(request('direction') === 'debit')>برداشت</option>
                    </select>
                </div>
                <button class="btn">اعمال</button>
                @if(request()->hasAny(['search','kind','direction']))
                    <a class="btn btn-ghost" href="{{ route('club.transactions') }}">پاک کردن</a>
                @endif
            </form>

            <div class="club-transactions-list">
                @forelse($transactions as $transaction)
                    @php
                        $isCredit = $transaction->direction === 'credit';
                        $color = $isCredit ? '#10b981' : '#ef4444';
                        $source = $sourceLabels[$transaction->ref_type ?: 'manual'] ?? ($transaction->ref_type ?: 'ثبت دستی');
                    @endphp
                    <article class="club-transaction-row" style="--transaction-color: {{ $color }};">
                        <div class="club-transaction-icon">{{ $isCredit ? '➕' : '➖' }}</div>
                        <div>
                            <span class="club-transactions-chip">{{ $kindLabels[$transaction->kind] ?? $transaction->kind }} · {{ $source }}</span>
                            <h3>{{ $transaction->reason ?: 'تراکنش باشگاه' }}</h3>
                            <p>{{ $isCredit ? 'واریز' : 'برداشت' }} · مانده بعد از تراکنش: @fa(number_format($transaction->balance_after))</p>
                            <small>@jdatetime($transaction->created_at)</small>
                        </div>
                        <div class="club-transaction-amount">
                            <strong class="{{ $isCredit ? 'is-credit' : 'is-debit' }}">{{ $isCredit ? '+' : '−' }}@fa(number_format($transaction->amount))</strong>
                            <span class="club-transactions-badge {{ $isCredit ? 'is-ok' : 'is-bad' }}">{{ $isCredit ? 'افزایش' : 'کاهش' }}</span>
                        </div>
                    </article>
                @empty
                    <div class="club-transactions-empty">تراکنشی با این فیلتر پیدا نشد.</div>
                @endforelse
            </div>
            <div style="margin-top:1rem">{{ $transactions->links() }}</div>
        </article>

        <aside class="club-transactions-card is-accent" style="--transactions-card-color:#10b981;">
            <header>
                <div>
                    <span>منابع پاداش</span>
                    <h2>از کجا امتیاز گرفته‌اید؟</h2>
                </div>
            </header>
            <div class="club-transactions-source-list">
                @forelse($sourceSummary as $source)
                    @php $label = $sourceLabels[$source->source] ?? $source->source; @endphp
                    <article class="club-transaction-source-row" style="--transaction-color:#8b5cf6;">
                        <div class="club-transaction-source-icon">◆</div>
                        <div>
                            <h3>{{ $label }}</h3>
                            <p>تعداد رویداد: @fa(number_format($source->total_count))</p>
                            <small>جمع مقدار: @fa(number_format($source->total_amount))</small>
                        </div>
                        <span class="club-transactions-badge is-muted">منبع</span>
                    </article>
                @empty
                    <div class="club-transactions-empty">هنوز منبعی برای تراکنش‌ها ثبت نشده است.</div>
                @endforelse
            </div>

            <div class="club-transactions-card" style="margin-top:1rem;box-shadow:none">
                <header>
                    <div>
                        <span>راهنمای استفاده</span>
                        <h2>قدم بعدی</h2>
                    </div>
                </header>
                <p>اگر امتیاز فعال دارید، می‌توانید آن را به کد تخفیف تبدیل کنید. اگر کیف پول دارید، در خریدهای بعدی از اعتبار خود استفاده کنید.</p>
                <div class="club-transactions-actions"><a class="btn btn-ghost" href="{{ route('club.coupons') }}">ساخت کد تخفیف</a></div>
            </div>
        </aside>
    </section>
</div>
@endsection