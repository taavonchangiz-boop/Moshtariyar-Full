@extends('layouts.app')
@section('title','باشگاه مشتریان')
@section('heading','بورد اعضای باشگاه و جدول پاداش')
@section('subtitle','مدیریت اعضا، امتیاز، کیف پول، سکه، معرفی دوستان و جدول پاداش هر عضو')

@section('content')
<link rel="stylesheet" href="{{ asset('css/loyalty-members-board.css') }}">
<link rel="stylesheet" href="{{ asset('css/loyalty-dashboard.css') }}">

@php
    $visibleMembers = collect($members->items());

    // چهار گروه اصلی طبق dastyar.md بخش 6-2 و 4-2
    $boardBuckets = collect([
        ['key' => 'valuable', 'label' => 'ارزشمند',        'color' => '#f59e0b', 'hint' => 'اعضایی با امتیاز، کیف پول یا سطح فعال'],
        ['key' => 'active',   'label' => 'فعال',           'color' => '#10b981', 'hint' => 'اعضایی که وضعیت پایه آن‌ها کامل‌تر است'],
        ['key' => 'new',      'label' => 'تازه‌وارد',      'color' => '#3b82f6', 'hint' => 'اعضای تازه یا کم‌امتیاز که باید فعال شوند'],
        ['key' => 'needs',    'label' => 'نیازمند تکمیل', 'color' => '#ef4444', 'hint' => 'بدون سطح، بدون کد معرف یا پروفایل ناقص'],
    ]);

    $memberBucket = function ($member) {
        if (empty($member->referral_code) || empty($member->tier_id) || ! $member->profile_completed) {
            return 'needs';
        }
        if ((int) $member->points >= 500 || (float) $member->wallet_balance > 0 || $member->tier) {
            return 'valuable';
        }
        if ($member->created_at && $member->created_at->greaterThanOrEqualTo(now()->subDays(30))) {
            return 'new';
        }
        return 'active';
    };

    $memberGroups = $visibleMembers->groupBy($memberBucket);

    // گروه‌هایی که پیش‌فرض باز باشند
    $openGroups = ['valuable', 'active'];
    if (request('without_tier') || request('without_referral')) { $openGroups = ['needs']; }
    if (request('with_wallet'))                                  { $openGroups = ['valuable']; }
@endphp

<div class="loyalty-page">

    {{-- هدر --}}
    <section class="loyalty-hero">
        <div>
            <span class="loyalty-eyebrow">مرکز وفاداری و پاداش مشتریان</span>
            <h2>اعضای باشگاه را مثل یک بورد مدیریتی کنترل کن و جدول پاداش هر عضو را ببین</h2>
            <p>اعضا بر اساس ارزش، فعالیت و نیاز به تکمیل دسته‌بندی شده‌اند. با کلیک روی هر عضو، امتیاز، کیف پول، سکه، کد تخفیف و تاریخچه پاداش‌های او را می‌بینی و می‌توانی پاداش جدید ثبت کنی.</p>
            <div class="loyalty-hero-actions">
                <a class="btn" href="{{ url('/app/loyalty/settings') }}">قوانین پاداش و سطح‌ها</a>
                <button class="btn btn-ghost" type="button" onclick="askLoyaltyAssistant('یک قانون جدید برای پاداش دادن به اعضای باشگاه بساز', this)">ساخت با دستیار</button>
                <a class="btn btn-ghost" href="{{ url('/app/loyalty/reports') }}">گزارش باشگاه</a>
                <a class="btn btn-ghost" href="{{ url('/app/loyalty/wheel') }}">گردونه شانس</a>
            </div>
        </div>
        <div class="loyalty-score">
            <a href="{{ url('/app/loyalty') }}" style="--metric-color:#f59e0b;">
                <span>کل اعضا</span><b>@fa(number_format($stats['members'] ?? 0))</b><small>ثبت‌شده در باشگاه</small>
            </a>
            <a href="{{ url('/app/loyalty') }}" style="--metric-color:#0ea5e9;">
                <span>امتیاز فعال</span><b>@fa(number_format($stats['points'] ?? 0))</b><small>جمع امتیازات همه اعضا</small>
            </a>
            <a href="{{ url('/app/loyalty?with_wallet=1') }}" style="--metric-color:#10b981;">
                <span>کیف پول</span><b>@money($stats['wallet'] ?? 0)</b><small>موجودی کل کیف پول‌ها</small>
            </a>
            <a href="{{ url('/app/loyalty') }}" style="--metric-color:#8b5cf6;">
                <span>معرفی‌ها</span><b>@fa(number_format($stats['referrals'] ?? 0))</b><small>ثبت رفرال موفق</small>
            </a>
        </div>
    </section>

    <section class="ly-kpi-row">
        <div class="ly-kpi-card" style="--k-color:#f59e0b;"><span>⭐ کل اعضا</span><b>@fa(number_format($stats['members'] ?? 0))</b><small>ثبت‌شده در باشگاه</small></div>
        <div class="ly-kpi-card" style="--k-color:#3b82f6;"><span>✨ مجموع امتیازات</span><b>@fa(number_format($stats['points'] ?? 0))</b><small>امتیاز فعال کل اعضا</small></div>
        <div class="ly-kpi-card" style="--k-color:#10b981;"><span>💰 مجموع کیف پول</span><b>@money($stats['wallet'] ?? 0) @unit</b><small>موجودی کیف پول‌ها</small></div>
        <div class="ly-kpi-card" style="--k-color:#8b5cf6;"><span>🔗 معرفی‌های موفق</span><b>@fa(number_format($stats['referrals'] ?? 0))</b><small>ثبت رفرال موفق</small></div>
        <div class="ly-kpi-card" style="--k-color:#ef4444;"><span>⚠ بدون سطح</span><b>@fa(number_format($stats['without_tier'] ?? 0))</b><small>نیازمند ارتقا</small></div>
        <div class="ly-kpi-card" style="--k-color:#0ea5e9;"><span>✅ پروفایل کامل</span><b>@fa(number_format($stats['active_profiles'] ?? 0))</b><small>تکمیل‌شده</small></div>
    </section>

    <section class="ly-chart-section">
        <div class="ly-chart-header"><h3>📊 توزیع اعضا بر اساس سطح</h3><small>تعداد اعضا در هر سطح باشگاه</small></div>
        <div class="ly-chart-body"><canvas id="chartTierDist"></canvas></div>
    </section>

    {{-- نوار فیلترها --}}
    <form method="get" class="loyalty-toolbar">
        <div>
            <label>جستجو</label>
            <input name="search" value="{{ request('search') }}" placeholder="نام، شماره یا کد معرف">
        </div>
        <div>
            <label>سطح</label>
            <select name="tier_id">
                <option value="">همه سطح‌ها</option>
                @foreach($tiers as $tier)
                    <option value="{{ $tier->id }}" @selected((string) request('tier_id') === (string) $tier->id)>{{ $tier->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>مرتب‌سازی</label>
            <select name="sort">
                <option value="points"    @selected(request('sort', 'points') === 'points')>بیشترین امتیاز</option>
                <option value="wallet"    @selected(request('sort') === 'wallet')>بیشترین کیف پول</option>
                <option value="referrals" @selected(request('sort') === 'referrals')>بیشترین معرفی</option>
                <option value="joined"    @selected(request('sort') === 'joined')>جدیدترین عضو</option>
            </select>
        </div>
        <label class="loyalty-toolbar-check">
            <input type="checkbox" name="without_tier" value="1" @checked(request('without_tier'))>
            <span>بدون سطح</span>
        </label>
        <label class="loyalty-toolbar-check">
            <input type="checkbox" name="with_wallet" value="1" @checked(request('with_wallet'))>
            <span>دارای کیف پول</span>
        </label>
        <label class="loyalty-toolbar-check">
            <input type="checkbox" name="without_referral" value="1" @checked(request('without_referral'))>
            <span>بدون کد معرف</span>
        </label>
        <div class="loyalty-toolbar-actions">
            <button class="btn">اعمال فیلتر</button>
            @if(request()->hasAny(['search','tier_id','sort','without_tier','with_wallet','without_referral']))
                <a class="btn btn-ghost" href="{{ url('/app/loyalty') }}">پاک کردن</a>
            @endif
        </div>
    </form>

    {{-- نوار خلاصه --}}
    <section class="loyalty-insight">
        <div><span class="loyalty-dot is-gold"></span><b>@fa(number_format($stats['without_tier'] ?? 0))</b><small>عضو بدون سطح</small></div>
        <div><span class="loyalty-dot is-ok"></span><b>@fa(number_format($stats['with_wallet'] ?? 0))</b><small>عضو دارای کیف پول</small></div>
        <div><span class="loyalty-dot is-red"></span><b>@fa(number_format($stats['without_referral'] ?? 0))</b><small>عضو بدون کد معرف</small></div>
        <div><span class="loyalty-dot is-blue"></span><b>@fa(number_format($stats['active_profiles'] ?? 0))</b><small>پروفایل تکمیل‌شده</small></div>
    </section>

    {{-- بورد اصلی + کارت های زیر --}}
    <section class="loyalty-layout">

        {{-- بورد اصلی --}}
        <div class="loyalty-main">
            <article class="loyalty-card is-accent" style="--card-color:#f59e0b;">
                <header>
                    <div>
                        <span>بورد اعضای باشگاه</span>
                        <h2>مدیریت گروهی اعضا بر اساس ارزش و وضعیت پاداش</h2>
                        <p>روی هر ردیف کلیک کن تا جدول پاداش کامل عضو (امتیاز، سکه، کیف پول، کد تخفیف، تاریخچه) باز شود.</p>
                    </div>
                    <a class="loyalty-link" href="{{ url('/app/loyalty/settings') }}">قوانین پاداش</a>
                </header>

                <div class="loyalty-board">
                    @foreach($boardBuckets as $bucket)
                        @php
                            $groupMembers = $memberGroups->get($bucket['key'], collect());
                            $groupCount   = $groupMembers->count();
                            $groupPoints  = $groupMembers->sum(fn ($m) => (int)   $m->points);
                            $groupWallet  = $groupMembers->sum(fn ($m) => (float) $m->wallet_balance);
                            $ratio        = $visibleMembers->count() > 0
                                                ? round(($groupCount / max(1, $visibleMembers->count())) * 100)
                                                : 0;
                            $isOpen       = in_array($bucket['key'], $openGroups, true) && $groupCount > 0;
                            $groupClass   = $isOpen ? 'is-open' : 'is-collapsed';
                        @endphp

                        <div class="loyalty-group {{ $groupClass }}" style="--group-color:{{ $bucket['color'] }}; --group-ratio:{{ $ratio }}%;">
                            <button type="button" class="loyalty-group-title" onclick="toggleLoyaltyGroup(this)">
                                <span class="lg-caret">◀</span>
                                <span class="lg-mark"></span>
                                <span class="lg-title-block">
                                    <b>{{ $bucket['label'] }}</b>
                                    <small>{{ $bucket['hint'] }}</small>
                                </span>
                                <span class="lg-count">@fa($groupCount)</span>
                                <span class="lg-progress"></span>
                                <span class="lg-ratio">@fa($ratio)٪</span>
                            </button>

                            <div class="loyalty-group-summary">
                                <span>مجموع امتیاز: <b>@fa(number_format($groupPoints))</b></span>
                                <span>مجموع کیف پول: <b>@money($groupWallet)</b></span>
                                <span>تعداد اعضا: <b>@fa($groupCount)</b></span>
                            </div>

                            <div class="loyalty-table-wrap">
                                <div class="loyalty-table">
                                    <div class="loyalty-thead">
                                        <div>عضو</div>
                                        <div>سطح</div>
                                        <div>امتیاز</div>
                                        <div>کیف پول</div>
                                        <div>سکه/موقت</div>
                                        <div>معرفی</div>
                                        <div>کد رفرال</div>
                                        <div>عملیات</div>
                                    </div>

                                    @forelse($groupMembers as $member)
                                        @php
                                            $customer  = $member->customer;
                                            $tier      = $member->tier;
                                            $tierColor = $tier->color ?? '#94a3b8';
                                            $fullName  = $customer->full_name ?? 'مشتری بدون نام';
                                            $firstChar = mb_substr($fullName, 0, 1);
                                            $refCode   = $member->referral_code;
                                            $coinBalance     = $member->coin_balance ?? 0;
                                            $tempPoints      = $member->temp_points ?? 0;
                                            $discountCoupons = $member->coupons_count ?? 0;
                                            $transactions    = $member->transactions ?? collect();
                                        @endphp

                                        <details class="loyalty-row">
                                            <summary>
                                                <div class="loyalty-cell">
                                                    <div class="loyalty-cell-person">
                                                        <div class="loyalty-avatar" style="--tier-color:{{ $tierColor }};">{{ $firstChar }}</div>
                                                        <div>
                                                            <b>{{ $fullName }}</b>
                                                            <small>{{ $customer->phone ?? 'بدون شماره' }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="loyalty-cell">
                                                    @if($tier)
                                                        <span class="loyalty-pill is-tier" style="--tier-color:{{ $tierColor }};">{{ $tier->name }}</span>
                                                    @else
                                                        <span class="loyalty-pill is-notier">بدون سطح</span>
                                                    @endif
                                                </div>
                                                <div class="loyalty-cell">
                                                    <span class="loyalty-pill is-point">@fa(number_format($member->points))</span>
                                                </div>
                                                <div class="loyalty-cell">
                                                    @if((float)$member->wallet_balance > 0)
                                                        <span class="loyalty-pill is-wallet">@money($member->wallet_balance)</span>
                                                    @else
                                                        <span class="loyalty-pill is-empty">—</span>
                                                    @endif
                                                </div>
                                                <div class="loyalty-cell">
                                                    @if($coinBalance > 0 || $tempPoints > 0)
                                                        <span class="loyalty-pill is-point">@fa(number_format($coinBalance ?: $tempPoints))</span>
                                                    @else
                                                        <span class="loyalty-pill is-empty">—</span>
                                                    @endif
                                                </div>
                                                <div class="loyalty-cell">
                                                    <span class="loyalty-pill {{ ($member->referrals_count ?? 0) > 0 ? 'is-wallet' : 'is-empty' }}">
                                                        @fa($member->referrals_count ?? 0)
                                                    </span>
                                                </div>
                                                <div class="loyalty-cell">
                                                    @if($refCode)
                                                        <span class="loyalty-pill is-refcode">{{ $refCode }}</span>
                                                    @else
                                                        <span class="loyalty-pill is-noref">بدون کد</span>
                                                    @endif
                                                </div>
                                                <div class="loyalty-cell">
                                                    <div class="loyalty-cell-actions">
                                                        <a href="{{ url('/app/loyalty/' . $member->id) }}">پرونده</a>
                                                        @if($customer)
                                                            <a href="{{ url('/app/customers/' . $customer->id) }}">۳۶۰</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </summary>

                                            {{-- پنل جزئیات: جدول پاداش کامل عضو --}}
                                            <div class="loyalty-detail-panel">

                                                {{-- ستون راست: جدول پاداش (طبق dastyar 6-2) --}}
                                                <div class="loyalty-detail-block">
                                                    <h4>جدول پاداش عضو <span>{{ $tier->name ?? 'بدون سطح' }}</span></h4>

                                                    <div class="loyalty-reward-grid">
                                                        <div class="is-point">
                                                            <span>امتیاز اصلی</span>
                                                            <b>@fa(number_format($member->points))</b>
                                                        </div>
                                                        <div class="is-coin">
                                                            <span>امتیاز موقت</span>
                                                            <b>@fa(number_format($tempPoints))</b>
                                                        </div>
                                                        <div class="is-wallet">
                                                            <span>کیف پول</span>
                                                            <b>@money($member->wallet_balance)</b>
                                                        </div>
                                                        <div class="is-coin">
                                                            <span>سکه</span>
                                                            <b>@fa(number_format($coinBalance))</b>
                                                        </div>
                                                        <div class="is-discount">
                                                            <span>کدهای تخفیف</span>
                                                            <b>@fa($discountCoupons)</b>
                                                        </div>
                                                        <div>
                                                            <span>معرفی موفق</span>
                                                            <b>@fa($member->referrals_count ?? 0)</b>
                                                        </div>
                                                        <div>
                                                            <span>کد رفرال</span>
                                                            <b class="ltr">{{ $refCode ?: '—' }}</b>
                                                        </div>
                                                        <div>
                                                            <span>وضعیت پروفایل</span>
                                                            <b>{{ $member->profile_completed ? '✓ کامل' : '⚠ ناقص' }}</b>
                                                        </div>
                                                    </div>

                                                    {{-- عملیات سریع پاداش --}}
                                                    <div class="loyalty-detail-actions">
                                                        <button type="button" class="is-primary" onclick="askLoyaltyAssistant('برای عضو {{ addslashes($fullName) }} ۱۰۰ امتیاز پاداش اضافه کن', this)">+ ۱۰۰ امتیاز</button>
                                                        <button type="button" onclick="askLoyaltyAssistant('برای عضو {{ addslashes($fullName) }} یک کد تخفیف ۱۰ درصدی صادر کن', this)">+ کد تخفیف</button>
                                                        <button type="button" onclick="askLoyaltyAssistant('برای عضو {{ addslashes($fullName) }} ۱۰ سکه اضافه کن', this)">+ ۱۰ سکه</button>
                                                        <a href="{{ url('/app/loyalty/' . $member->id) }}">پرونده کامل</a>
                                                    </div>
                                                </div>

                                                {{-- ستون چپ: تاریخچه تراکنش پاداش --}}
                                                <div class="loyalty-detail-block">
                                                    <h4>تاریخچه پاداش <span>@fa($transactions instanceof \Countable ? $transactions->count() : 0) تراکنش</span></h4>
                                                    <div class="loyalty-history">
                                                        @forelse(($transactions ?? []) as $tx)
                                                            @php
                                                                $isPlus  = ($tx->amount ?? 0) > 0;
                                                                $iconCls = $isPlus ? 'is-plus' : 'is-minus';
                                                                $valCls  = $isPlus ? 'is-plus' : 'is-minus';
                                                                $sign    = $isPlus ? '+' : '−';
                                                                $abs     = abs((int)($tx->amount ?? 0));
                                                            @endphp
                                                            <div class="loyalty-history-row">
                                                                <div class="lh-icon {{ $iconCls }}">{{ $isPlus ? '↑' : '↓' }}</div>
                                                                <div>
                                                                    <b>{{ $tx->reason ?? $tx->description ?? $tx->type ?? 'تراکنش پاداش' }}</b>
                                                                    <small>@jdate($tx->created_at ?? now())</small>
                                                                </div>
                                                                <span class="lh-value {{ $valCls }}">{{ $sign }} @fa(number_format($abs))</span>
                                                            </div>
                                                        @empty
                                                            <div class="loyalty-history-empty">
                                                                هنوز تراکنش پاداشی برای این عضو ثبت نشده است. با دستیار می‌توانی اولین پاداش را ثبت کنی.
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </details>
                                    @empty
                                        <div class="loyalty-group-empty">در این گروه عضوی وجود ندارد.</div>
                                    @endforelse
                                </div>
                            </div>

                            <a class="loyalty-group-add" href="{{ url('/app/loyalty/settings') }}">+ افزودن قانون پاداش برای گروه «{{ $bucket['label'] }}»</a>
                        </div>
                    @endforeach
                </div>

                @if($members->hasPages())
                    <div style="margin-top:1rem;">{{ $members->links() }}</div>
                @endif
            </article>
        </div>

        {{-- بخش زیر بورد: پیشنهاد دستیار (راست) + فرم ثبت پاداش سریع (چپ) --}}
        <div class="loyalty-below">

            <article class="loyalty-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>پیشنهاد دستیار</span>
                        <h2>قوانین و پاداش‌های آماده</h2>
                    </div>
                </header>
                <div class="loyalty-suggest-list">
                    <button type="button" onclick="askLoyaltyAssistant('یک قانون بساز که به عضو جدید بعد از ثبت‌نام ۱۰۰ امتیاز خوشامد بدهد', this)">
                        <b>پاداش خوشامد ۱۰۰ امتیاز</b>
                        <small>هر عضو جدید بعد از ثبت‌نام</small>
                    </button>
                    <button type="button" onclick="askLoyaltyAssistant('یک قانون تبدیل امتیاز به کد تخفیف بساز؛ ۲۰۰۰ امتیاز = ۱۰ درصد تخفیف', this)">
                        <b>تبدیل ۲۰۰۰ امتیاز به تخفیف</b>
                        <small>هر ۲۰۰۰ امتیاز = ۱۰٪ تخفیف</small>
                    </button>
                    <button type="button" onclick="askLoyaltyAssistant('یک قانون پاداش رفرال بساز؛ معرفی موفق ۵۰ امتیاز به دعوت‌کننده و ۱۰۰ امتیاز به عضو جدید', this)">
                        <b>پاداش رفرال (معرفی موفق)</b>
                        <small>دعوت‌کننده ۵۰ امتیاز، عضو جدید ۱۰۰ امتیاز</small>
                    </button>
                    <button type="button" onclick="askLoyaltyAssistant('یک قانون کش‌بک بساز؛ ۵ درصد از هر خرید به‌عنوان سکه به مشتری بازگردد', this)">
                        <b>کش‌بک ۵٪ روی خرید</b>
                        <small>هر خرید = ۵٪ سکه بازگشتی</small>
                    </button>
                    <button type="button" onclick="askLoyaltyAssistant('برای اعضای بدون خرید در ۹۰ روز اخیر یک پیشنهاد ۱۵ درصد تخفیف بساز', this)">
                        <b>بازگرداندن اعضای غیرفعال</b>
                        <small>۹۰ روز بدون خرید = تخفیف ۱۵٪</small>
                    </button>
                </div>
            </article>

            <article class="loyalty-card is-accent" style="--card-color:#8b5cf6;" id="loyaltyQuickRewardPanel">
                <header>
                    <div>
                        <span>ثبت پاداش سریع</span>
                        <h2>افزودن یا کسر پاداش برای یک عضو</h2>
                    </div>
                </header>
                <form method="post" action="{{ url('/app/loyalty/quick-reward') }}" class="loyalty-quick-form">
                    @csrf

                    <div>
                        <label>جستجوی عضو</label>
                        <input name="member_search" placeholder="نام، شماره یا کد معرف عضو" autocomplete="off">
                    </div>

                    <div class="loyalty-form-grid">
                        <div>
                            <label>نوع پاداش</label>
                            <select name="reward_type" id="loyaltyRewardType">
                                <option value="point">امتیاز</option>
                                <option value="coin">سکه</option>
                                <option value="wallet">کیف پول (تومان)</option>
                                <option value="discount">کد تخفیف (٪)</option>
                            </select>
                        </div>
                        <div>
                            <label>عملیات</label>
                            <select name="operation">
                                <option value="add">افزودن</option>
                                <option value="subtract">کسر</option>
                            </select>
                        </div>
                    </div>

                    <div class="loyalty-form-grid">
                        <div>
                            <label>مقدار</label>
                            <input name="amount" type="number" min="0" step="1" placeholder="مثلاً ۱۰۰" required>
                        </div>
                        <div>
                            <label>تاریخ اعتبار (شمسی)</label>
                            <input name="expires_at" class="jdate" placeholder="۱۴۰۵/۱۲/۲۹" autocomplete="off">
                        </div>
                    </div>

                    <div>
                        <label>دلیل پاداش</label>
                        <input name="reason" placeholder="مثلاً: پاداش خرید ویژه یا معرفی دوست">
                    </div>

                    <div>
                        <label>توضیحات (اختیاری)</label>
                        <input name="notes" placeholder="یادداشت داخلی برای مدیر">
                    </div>

                    <button class="btn">ثبت پاداش</button>
                    <small>پس از ثبت، در جدول پاداش عضو و تاریخچه او نمایش داده می‌شود.</small>
                </form>
            </article>

        </div>
    </section>
</div>

<script>
    // باز و بسته کردن گروه‌های بورد اعضا
    function toggleLoyaltyGroup(btn) {
        const group = btn.closest('.loyalty-group');
        if (!group) return;
        group.classList.toggle('is-collapsed');
        group.classList.toggle('is-open');
    }

    // پرسش از دستیار برای ساخت قانون یا ثبت پاداش
    function askLoyaltyAssistant(text, btn) {
        text = text || 'یک قانون پاداش بساز';

        if (btn) {
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            var originalHtml = btn.innerHTML;
            btn.innerHTML = '<b style="color:#b45309">در حال باز کردن دستیار...</b><small>لطفاً چند لحظه صبر کنید</small>';
        }

        var assistantUrl = '{{ url("/app/assistant") }}'
            + '?prompt=' + encodeURIComponent(text)
            + '&context_type=loyalty_center'
            + '&context_title=' + encodeURIComponent('باشگاه مشتریان و جدول پاداش')
            + '&context_url='   + encodeURIComponent(window.location.href);

        window.location.href = assistantUrl;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    if (typeof Chart === 'undefined') return;
    var tiers = @json($tiers ?? []);
    var tierNames = tiers.map(function(t) { return t.name || ('سطح ' + t.id); });
    var tierColors = tiers.map(function(t) { return t.color || '#3b82f6'; });
    var memberCounts = [];
    tiers.forEach(function(tier) {
        var rows = document.querySelectorAll('.loyalty-pill.is-tier');
        var count = 0;
        rows.forEach(function(r) { if (r.textContent.trim() === tier.name) count++; });
        memberCounts.push(count || 0);
    });
    var ctx = document.getElementById('chartTierDist');
    if (!ctx || !tierNames.length) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: tierNames,
            datasets: [{ data: memberCounts, backgroundColor: tierColors, borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11, family: 'inherit' }, padding: 12, usePointStyle: true } } }
        }
    });
})();
</script>
@endsection
