{{-- partial: 360/loyalty --}}
@php
    $member = $loyalty['member'] ?? null;
    $points = (int) ($loyalty['points'] ?? 0);
    $wallet = (float) ($loyalty['wallet_balance'] ?? ($member->wallet_balance ?? 0));
    $tierName = $loyalty['tier'] ?? ($member?->tier?->name ?? null);
    $nextTier = $loyalty['next_tier'] ?? null;
    $nextTierGap = $loyalty['next_tier_gap'] ?? null;
    $tierProgress = (int) ($loyalty['tier_progress'] ?? 0);
    $transactions = $loyalty['transactions'] ?? ($member?->transactions ?? collect());
    $badges = $loyalty['badges'] ?? ($member?->badges ?? collect());
    $referralCode = $member?->referral_code;
    $referralLink = null;

    if ($member) {
        try {
            $referralLink = $member->referral_link;
            $referralCode = $member->referral_code;
        } catch (\Throwable $e) {
            $referralLink = null;
        }
    }

    $loyaltyAdvice = 'این مشتری هنوز عضو باشگاه نیست. پیشنهاد می‌شود با اولین خرید یا ثبت‌نام در باشگاه، مسیر وفادارسازی او فعال شود.';
    $loyaltyColor = '#64748b';
    $loyaltyMode = 'آماده فعال‌سازی';

    if ($member && $nextTier && $nextTierGap !== null && $nextTierGap > 0) {
        $loyaltyAdvice = 'این مشتری فقط ' . \Modules\Core\Support\Num::fa(number_format($nextTierGap)) . ' امتیاز تا سطح «' . $nextTier . '» فاصله دارد. پیشنهاد: مأموریت یا پاداش خرید بعدی فعال شود.';
        $loyaltyColor = '#f59e0b';
        $loyaltyMode = 'نزدیک به ارتقا';
    } elseif ($member && $tierProgress >= 100) {
        $loyaltyAdvice = 'این مشتری در بالاترین سطح ثبت‌شده قرار دارد. پیشنهاد: پیام تشکر، هدیه اختصاصی یا پیشنهاد ویژه برای حفظ مشتری ارسال شود.';
        $loyaltyColor = '#10b981';
        $loyaltyMode = 'وفادار ویژه';
    } elseif ($member && $points > 0) {
        $loyaltyAdvice = 'این مشتری امتیاز فعال دارد. پیشنهاد: روش استفاده از امتیاز یا تبدیل آن به مزیت خرید به او یادآوری شود.';
        $loyaltyColor = '#3b82f6';
        $loyaltyMode = 'دارای امتیاز فعال';
    } elseif ($member) {
        $loyaltyColor = '#8b5cf6';
        $loyaltyMode = 'عضو باشگاه';
    }
@endphp

<section class="customer360-loyalty-board" style="--loyalty-color: {{ $loyaltyColor }};">
    <header class="customer360-loyalty-board-hero">
        <div>
            <span class="customer360-loyalty-chip">باشگاه مشتریان</span>
            <h3>وضعیت وفاداری، پاداش و معرفی مشتری</h3>
            <p>{{ $member ? 'پرونده باشگاه این مشتری، امتیاز، سطح، کیف پول، کد معرفی و آخرین تراکنش‌های وفاداری را یکجا نمایش می‌دهد.' : 'این مشتری هنوز عضو باشگاه نیست و می‌توانید مسیر وفادارسازی او را از همینجا شروع کنید.' }}</p>
        </div>
        <div class="customer360-loyalty-status-card">
            <span>وضعیت فعلی</span>
            <b>{{ $loyaltyMode }}</b>
            <small>{{ $tierName ? 'سطح: ' . $tierName : 'سطح ثبت نشده' }}</small>
        </div>
    </header>

    @if($member)
        <div class="customer360-loyalty-metric-grid">
            <article style="--metric-color:#3b82f6;">
                <span>امتیاز قابل استفاده</span>
                <b>@fa(number_format($points))</b>
                <small>امتیاز فعال در باشگاه</small>
            </article>
            <article style="--metric-color:#10b981;">
                <span>کیف پول</span>
                <b>@money($wallet) @unit</b>
                <small>اعتبار ثبت‌شده برای مشتری</small>
            </article>
            <article style="--metric-color:#8b5cf6;">
                <span>سطح فعلی</span>
                <b>{{ $tierName ?: 'بدون سطح' }}</b>
                <small>{{ $nextTier ? 'سطح بعدی: ' . $nextTier : 'سطح بعدی ثبت نشده' }}</small>
            </article>
        </div>

        <div class="customer360-loyalty-progress-panel">
            <div class="customer360-loyalty-progress-top">
                <div>
                    <span>پیشرفت سطح</span>
                    <b>@fa($tierProgress)٪</b>
                </div>
                @if($nextTier && $nextTierGap !== null)
                    <small>تا سطح «{{ $nextTier }}» حدود @fa(number_format($nextTierGap)) امتیاز مانده است.</small>
                @else
                    <small>این مشتری در مسیر سطح‌بندی فعلی، محدودیت بعدی ثبت‌شده‌ای ندارد.</small>
                @endif
            </div>
            <div class="customer360-loyalty-progress"><span style="width: {{ max(0, min(100, $tierProgress)) }}%;"></span></div>
        </div>

        <div class="customer360-loyalty-advice-panel">
            <span>پیشنهاد وفادارسازی</span>
            <p>{{ $loyaltyAdvice }}</p>
            <div class="customer360-loyalty-action-row">
                <a href="javascript:void(0)" onclick="openPointsModal()">تخصیص امتیاز</a>
                <a href="{{ url('/app/loyalty/' . $member->id) }}">پرونده باشگاه</a>
                <a href="{{ url('/app/loyalty/campaigns') }}">کمپین‌های باشگاه</a>
            </div>
        </div>

        <div class="customer360-loyalty-lower-grid">
            <article class="customer360-referral-pro-card">
                <header>
                    <div>
                        <span>معرفی دوستان</span>
                        <h4>کد و لینک معرفی</h4>
                    </div>
                    <b>🤝</b>
                </header>
                @if($referralCode)
                    <label>کد معرف</label>
                    <div class="customer360-copy-row">
                        <input class="ltr" value="{{ $referralCode }}" readonly onclick="this.select()">
                        <button type="button" onclick="copyCustomer360Value(this)">کپی</button>
                    </div>
                @else
                    <div class="customer360-empty-mini">کد معرف هنوز ساخته نشده است.</div>
                @endif
                @if($referralLink)
                    <label>لینک معرفی</label>
                    <div class="customer360-copy-row">
                        <input class="ltr" value="{{ $referralLink }}" readonly onclick="this.select()">
                        <button type="button" onclick="copyCustomer360Value(this)">کپی</button>
                    </div>
                @endif
            </article>

            <article class="customer360-transactions-pro-card">
                <header>
                    <div>
                        <span>تراکنش‌های وفاداری</span>
                        <h4>آخرین امتیازها و پاداش‌ها</h4>
                    </div>
                    <a href="{{ url('/app/loyalty/' . $member->id) }}">جزئیات</a>
                </header>
                <div class="customer360-transaction-list-pro">
                    @forelse($transactions->take(5) as $transaction)
                        <div class="customer360-transaction-pro-row">
                            <div>
                                <b>{{ $transaction->reason ?: 'تراکنش باشگاه' }}</b>
                                <span>@jdatetime($transaction->created_at)</span>
                            </div>
                            <strong class="{{ ($transaction->direction ?? '') === 'debit' ? 'is-debit' : 'is-credit' }}">
                                {{ ($transaction->direction ?? '') === 'debit' ? '−' : '+' }}@fa(number_format(abs((float) $transaction->amount)))
                            </strong>
                        </div>
                    @empty
                        <div class="customer360-empty-mini">هنوز تراکنش امتیازی ثبت نشده است.</div>
                    @endforelse
                </div>
            </article>
        </div>

        @if($badges instanceof \Illuminate\Support\Collection && $badges->isNotEmpty())
            <div class="customer360-badge-pro-list">
                <span>نشان‌های اخیر</span>
                <div>
                    @foreach($badges->take(8) as $badge)
                        <b>🏅 نشان شماره @fa($badge->badge_id ?? $badge->id)</b>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="customer360-loyalty-empty-state-pro">
            <div>🎁</div>
            <h3>این مشتری هنوز عضو باشگاه نیست</h3>
            <p>برای شروع وفادارسازی، می‌توانید بعد از اولین خرید یا از بخش باشگاه مشتریان، عضویت او را فعال کنید.</p>
            <a href="{{ url('/app/loyalty') }}">رفتن به باشگاه مشتریان</a>
        </div>
    @endif
</section>

<script>
function copyCustomer360Value(button) {
    const input = button.closest('.customer360-copy-row')?.querySelector('input');
    if (!input) return;
    input.select();
    document.execCommand('copy');
    button.textContent = 'کپی شد';
    setTimeout(() => button.textContent = 'کپی', 1200);
}
</script>