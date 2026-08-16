@extends('layouts.app')
@section('title','پرونده باشگاه مشتری')
@section('heading',$member->customer->full_name ?? 'عضو باشگاه')
@section('subtitle','جزئیات امتیاز، کیف پول، سطح، معرفی دوستان و اثر سفارش‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('css/loyalty-member-pro.css') }}">

@php
    $customer = $member->customer;
    $currentPoints = (int) ($member->points ?? 0);
    $lifetimePoints = (int) ($member->points_lifetime ?? 0);
    $walletBalance = (float) ($member->wallet_balance ?? 0);
    $currentTier = $member->tier;
    $referralCode = method_exists($member, 'ensureReferralCode') ? $member->ensureReferralCode() : strtoupper(trim((string) ($member->referral_code ?? '')));
    $referralLink = $referralCode !== '' ? route('club.referral', ['code' => $referralCode]) : '';
    $nextTier = $tiers->where('min_points', '>', $currentPoints)->sortBy('min_points')->first();
    $currentTierMin = (int) ($currentTier?->min_points ?? 0);
    $nextTierMin = (int) ($nextTier?->min_points ?? max($currentTierMin + 1, $currentPoints));
    $tierDistance = max(1, $nextTierMin - $currentTierMin);
    $tierProgress = $nextTier ? min(100, max(0, round((($currentPoints - $currentTierMin) / $tierDistance) * 100))) : 100;
    $nextTierGap = $nextTier ? max(0, $nextTierMin - $currentPoints) : 0;
    $referralsCount = $member->referralsMade->count();
    $qualifiedReferrals = $member->referralsMade->whereIn('status', ['qualified', 'rewarded'])->count();
    $orderEffects = $member->transactions->filter(function($transaction){
        return in_array($transaction->ref_type, ['order_reward','order_tier','rollback'], true) || str_contains((string) $transaction->reason, 'سفارش');
    });
    $pointsEarned = (int) $member->transactions->where('kind', 'point')->where('direction', 'credit')->sum('amount');
    $pointsSpent = (int) abs($member->transactions->where('kind', 'point')->where('direction', 'debit')->sum('amount'));
    $walletEarned = (float) $member->transactions->where('kind', 'wallet')->where('direction', 'credit')->sum('amount');
    $walletSpent = (float) abs($member->transactions->where('kind', 'wallet')->where('direction', 'debit')->sum('amount'));
    $memberStatusLabel = $member->profile_completed ? 'پروفایل کامل' : 'پروفایل ناقص';
    $memberStatusColor = $member->profile_completed ? '#10b981' : '#f59e0b';
@endphp

<div class="loyalty-member-page">
    <section class="loyalty-member-hero">
        <div class="loyalty-member-identity">
            <div class="loyalty-member-avatar">{{ mb_substr($customer->full_name ?? 'م', 0, 1) }}</div>
            <div>
                <span>پرونده کامل عضو باشگاه</span>
                <h2>{{ $customer->full_name ?? 'عضو بدون نام' }}</h2>
                <div class="loyalty-member-contact">
                    <b class="ltr">{{ $customer->phone ?? 'بدون شماره' }}</b>
                    <b class="email">{{ $customer->email ?? 'بدون ایمیل' }}</b>
                </div>
                <div class="loyalty-member-tags">
                    <em style="--tag-color: {{ $currentTier?->color ?? '#64748b' }};">{{ $currentTier?->name ?? 'بدون سطح' }}</em>
                    <em style="--tag-color: {{ $memberStatusColor }};">{{ $memberStatusLabel }}</em>
                    <em style="--tag-color: #3b82f6;">کد معرف: {{ $referralCode ?: 'بدون کد' }}</em>
                </div>
            </div>
        </div>
        <div class="loyalty-member-actions">
            @if($customer)
                <a href="{{ url('/app/customers/' . $customer->id) }}" class="btn btn-ghost">پرونده ۳۶۰ مشتری</a>
            @endif
            <a href="{{ url('/app/loyalty') }}" class="btn btn-ghost">بازگشت به اعضا</a>
        </div>
    </section>

    <section class="loyalty-member-metrics">
        <article>
            <span>امتیاز فعلی</span>
            <b>@fa(number_format($currentPoints))</b>
            <small>موجودی امتیاز قابل استفاده</small>
        </article>
        <article>
            <span>امتیاز کل کسب‌شده</span>
            <b>@fa(number_format($lifetimePoints))</b>
            <small>کل امتیاز ثبت‌شده در طول عضویت</small>
        </article>
        <article>
            <span>کیف پول</span>
            <b>@money($walletBalance) @unit</b>
            <small>اعتبار قابل استفاده مشتری</small>
        </article>
        <article>
            <span>معرفی‌ها</span>
            <b>@fa($referralsCount)</b>
            <small>@fa($qualifiedReferrals) معرفی دارای نتیجه</small>
        </article>
    </section>

    <section class="loyalty-member-grid">
        <article class="loyalty-member-card loyalty-tier-card" style="--tier-color: {{ $currentTier?->color ?? '#3b82f6' }};">
            <header>
                <span>سطح عضویت</span>
                <h3>{{ $currentTier?->name ?? 'بدون سطح' }}</h3>
            </header>
            <div class="loyalty-tier-progress-top">
                <b>پیشرفت تا سطح بعد</b>
                <strong>@fa($tierProgress)٪</strong>
            </div>
            <div class="loyalty-tier-progress"><span style="width: {{ $tierProgress }}%;"></span></div>
            @if($nextTier)
                <p>تا سطح «{{ $nextTier->name }}» حدود <b>@fa(number_format($nextTierGap))</b> امتیاز مانده است.</p>
            @else
                <p>این عضو در بالاترین سطح تعریف‌شده یا مسیر سطح بعدی برای او ثبت نشده است.</p>
            @endif
            <div class="loyalty-tier-list">
                @forelse($tiers as $tier)
                    <div class="{{ $currentTier && $currentTier->id === $tier->id ? 'is-current' : '' }}">
                        <span style="--tier-dot: {{ $tier->color ?? '#64748b' }};"></span>
                        <b>{{ $tier->name }}</b>
                        <small>از @fa(number_format($tier->min_points)) امتیاز</small>
                    </div>
                @empty
                    <div class="loyalty-empty-mini">هنوز سطحی تعریف نشده است.</div>
                @endforelse
            </div>
        </article>

        <article class="loyalty-member-card">
            <header>
                <span>عملیات سریع</span>
                <h3>شارژ، کسر و تبدیل پاداش</h3>
            </header>
            <form method="post" action="{{ url('/app/loyalty/'.$member->id.'/adjust') }}" class="loyalty-member-form">
                @csrf
                <div class="loyalty-form-grid">
                    <div>
                        <label>نوع</label>
                        <select name="kind">
                            <option value="point">امتیاز</option>
                            <option value="wallet">اعتبار کیف پول</option>
                        </select>
                    </div>
                    <div>
                        <label>مقدار</label>
                        <input name="amount" class="ltr" required placeholder="برای کسر، مقدار منفی وارد کنید">
                    </div>
                </div>
                <label>علت</label>
                <input name="reason" required placeholder="مثلاً هدیه باشگاه، جبران نارضایتی یا پاداش دستی">
                <button class="btn">ثبت تغییر</button>
            </form>

            <form method="post" action="{{ url('/app/loyalty/'.$member->id.'/convert') }}" class="loyalty-member-form loyalty-convert-form">
                @csrf
                <label>تبدیل امتیاز به کیف پول</label>
                <div class="loyalty-inline-form">
                    <input name="points" class="ltr" required placeholder="تعداد امتیاز">
                    <button class="btn">تبدیل</button>
                </div>
                <small>هر امتیاز طبق قانون فعلی به اعتبار کیف پول تبدیل می‌شود.</small>
            </form>

            <form method="post" action="{{ url('/app/loyalty/wheel/'.$member->id.'/spin') }}" class="loyalty-member-form loyalty-wheel-form">
                @csrf
                <button class="btn btn-ghost">چرخاندن گردونه شانس برای این مشتری</button>
            </form>
        </article>
    </section>

    <section class="loyalty-member-grid">
        <article class="loyalty-member-card">
            <header>
                <span>معرفی دوستان</span>
                <h3>کد و لینک اختصاصی معرفی</h3>
            </header>
            <div class="loyalty-referral-code-box">
                <span>کد معرف</span>
                <b class="ltr">{{ $referralCode ?: 'بدون کد' }}</b>
            </div>
            @if($referralLink)
                <label>لینک معرفی</label>
                <input class="ltr" value="{{ $referralLink }}" readonly onclick="this.select()">
                <a class="btn btn-ghost loyalty-open-link" href="{{ $referralLink }}" target="_blank">باز کردن لینک معرفی</a>
            @endif
        </article>

        <article class="loyalty-member-card">
            <header>
                <span>خلاصه تراکنش‌ها</span>
                <h3>رفتار امتیاز و کیف پول</h3>
            </header>
            <div class="loyalty-transaction-summary">
                <div><span>امتیاز دریافتی</span><b class="is-credit">+@fa(number_format($pointsEarned))</b></div>
                <div><span>امتیاز مصرف‌شده</span><b class="is-debit">-@fa(number_format($pointsSpent))</b></div>
                <div><span>کیف پول دریافتی</span><b class="is-credit">@money($walletEarned)</b></div>
                <div><span>کیف پول مصرف‌شده</span><b class="is-debit">@money($walletSpent)</b></div>
            </div>
        </article>
    </section>

    <section class="loyalty-member-card">
        <header>
            <span>زیرمجموعه‌ها</span>
            <h3>مشتریانی که با کد این عضو معرفی شده‌اند</h3>
        </header>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>مشتری</th><th>وضعیت</th><th>پاداش</th><th>تاریخ</th></tr>
                </thead>
                <tbody>
                @forelse($member->referralsMade as $referral)
                    <tr>
                        <td>{{ $referral->referredCustomer?->full_name ?? '—' }}</td>
                        <td>{{ ['registered'=>'ثبت‌نام‌شده','qualified'=>'دارای خرید','rewarded'=>'پاداش‌داده‌شده','cancelled'=>'لغوشده'][$referral->status] ?? $referral->status }}</td>
                        <td class="muted">@fa(number_format($referral->reward_points)) امتیاز / @money($referral->reward_wallet)</td>
                        <td class="muted ltr">@jdatetime($referral->created_at)</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">هنوز زیرمجموعه‌ای ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="loyalty-member-card">
        <header>
            <span>اثر سفارش‌ها</span>
            <h3>پاداش‌ها و برگشت‌های ناشی از سفارش</h3>
        </header>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>نوع</th><th>جهت</th><th>مقدار</th><th>علت</th><th>تاریخ</th></tr>
                </thead>
                <tbody>
                @forelse($orderEffects as $transaction)
                    <tr>
                        <td>{{ \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS[$transaction->kind] ?? $transaction->kind }}</td>
                        <td>@if($transaction->direction === 'credit')<span class="badge b-ok">اضافه شده</span>@else<span class="badge b-bad">کسر شده</span>@endif</td>
                        <td class="ltr">@fa(number_format($transaction->amount))</td>
                        <td class="muted">{{ $transaction->reason }}</td>
                        <td class="muted ltr">@jdatetime($transaction->created_at)</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">هنوز اثری از سفارش‌ها روی باشگاه این عضو ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="loyalty-member-card">
        <header>
            <span>تاریخچه کامل</span>
            <h3>همه تراکنش‌های امتیاز و کیف پول</h3>
        </header>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>نوع</th><th>جهت</th><th>مقدار</th><th>موجودی پس از</th><th>علت</th><th>تاریخ</th></tr>
                </thead>
                <tbody>
                @forelse($member->transactions as $transaction)
                    <tr>
                        <td>{{ \Modules\Loyalty\Entities\LoyaltyTransaction::KINDS[$transaction->kind] ?? $transaction->kind }}</td>
                        <td>@if($transaction->direction === 'credit')<span class="badge b-ok">دریافت</span>@else<span class="badge b-bad">کسر</span>@endif</td>
                        <td class="ltr">@fa(number_format($transaction->amount))</td>
                        <td class="ltr">@fa(number_format($transaction->balance_after))</td>
                        <td class="muted">{{ $transaction->reason }}</td>
                        <td class="muted ltr">@jdatetime($transaction->created_at)</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">تراکنشی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection