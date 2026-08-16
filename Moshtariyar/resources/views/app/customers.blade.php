@extends('layouts.app')
@section('title','مشتریان')
@section('heading','بورد مدیریت مشتریان')
@section('subtitle','پرونده ۳۶۰ درجه مشتری، ارزش خرید، منبع جذب، تاریخچه سفارش و پیگیری سریع')

@section('content')
<link rel="stylesheet" href="{{ asset('css/customers-board.css') }}">

@php
    $customerRows = $customers->getCollection();

    // نگاشت منبع جذب
    $sourceLabels = [
        'woocommerce'  => 'فروشگاه',
        'manual'       => 'ثبت دستی',
        'club_portal'  => 'باشگاه مشتریان',
        'form'         => 'فرم سایت',
        'website-form' => 'فرم سایت',
        'lead'         => 'تبدیل از سرنخ',
    ];
    $sourceTitle = fn ($source) => $sourceLabels[$source] ?? ($source ?: 'نامشخص');
    $sourceClass = function ($source) {
        return match ($source) {
            'woocommerce' => 'is-source-woo',
            'club_portal' => 'is-source-portal',
            'form', 'website-form' => 'is-source-form',
            'lead' => 'is-source-lead',
            default => 'is-source-manual',
        };
    };

    // چهار گروه اصلی
    $groupCards = collect([
        ['key' => 'vip',    'label' => 'ارزشمند (VIP)',     'color' => '#8b5cf6', 'hint' => 'مشتریانی با ارزش خرید بالا و چند سفارش'],
        ['key' => 'active', 'label' => 'فعال',              'color' => '#10b981', 'hint' => 'مشتریانی که خرید معتبر داشته‌اند'],
        ['key' => 'new',    'label' => 'تازه‌وارد',         'color' => '#3b82f6', 'hint' => 'مشتریان تازه ثبت‌شده بدون خرید'],
        ['key' => 'follow', 'label' => 'نیازمند پیگیری',    'color' => '#f59e0b', 'hint' => 'مشتریانی که هنوز خریدی ثبت نکرده‌اند'],
    ]);

    $customerGroups = collect([
        'vip'    => $customerRows->filter(fn($c) => (float)($c->lifetime_value ?? 0) > 0 && (int)($c->orders_count ?? 0) >= 2)->values(),
        'active' => $customerRows->filter(fn($c) => (int)($c->orders_count ?? 0) > 0 && !((float)($c->lifetime_value ?? 0) > 0 && (int)($c->orders_count ?? 0) >= 2))->values(),
        'new'    => $customerRows->filter(fn($c) => (int)($c->orders_count ?? 0) === 0 && $c->created_at && $c->created_at->gte(now()->subDays(30)))->values(),
        'follow' => $customerRows->filter(fn($c) => (int)($c->orders_count ?? 0) === 0 && (!$c->created_at || $c->created_at->lt(now()->subDays(30))))->values(),
    ]);

    $visibleRevenue = $customerRows->sum(fn($c) => (float)($c->lifetime_value ?? 0));
    $visibleOrders  = $customerRows->sum(fn($c) => (int)($c->orders_count ?? 0));

    // گروه‌های پیش‌فرض باز
    $openGroups = ['vip', 'active'];
    if (request('view') === 'follow') { $openGroups = ['follow']; }
    if (request('view') === 'new')    { $openGroups = ['new']; }

    // آواتار خودکار با رنگ بر اساس اسم
    $avatarPalette = [
        ['bg' => 'rgba(139, 92, 246, 0.15)', 'fg' => '#6d28d9'],
        ['bg' => 'rgba(16, 185, 129, 0.15)', 'fg' => '#047857'],
        ['bg' => 'rgba(59, 130, 246, 0.15)', 'fg' => '#1d4ed8'],
        ['bg' => 'rgba(245, 158, 11, 0.15)', 'fg' => '#b45309'],
        ['bg' => 'rgba(239, 68, 68, 0.15)',  'fg' => '#b91c1c'],
        ['bg' => 'rgba(14, 165, 233, 0.15)', 'fg' => '#0284c7'],
        ['bg' => 'rgba(236, 72, 153, 0.15)', 'fg' => '#be185d'],
    ];
    $pickAvatarColor = function ($name) use ($avatarPalette) {
        $idx = crc32((string)$name) % count($avatarPalette);
        return $avatarPalette[abs($idx)];
    };

    // Star rating 1-5 بر اساس ارزش
    $rateCustomer = function ($customer) {
        $orders = (int)($customer->orders_count ?? 0);
        $value  = (float)($customer->lifetime_value ?? 0);
        if ($orders >= 10 || $value >= 50_000_000) return 5;
        if ($orders >= 5  || $value >= 20_000_000) return 4;
        if ($orders >= 3  || $value >= 5_000_000)  return 3;
        if ($orders >= 1)                           return 2;
        return 1;
    };
@endphp

<div class="customers-page">

    {{-- هدر --}}
    <section class="customers-hero">
        <div>
            <span class="customers-eyebrow">مرکز ارتباط با مشتری - نمای ۳۶۰ درجه</span>
            <h2>هر مشتری یک پرونده زنده برای فروش، وفاداری، پیگیری و ارتباط</h2>
            <p>مشتریان بر اساس ارزش و وضعیت خرید دسته‌بندی شده‌اند. با کلیک روی هر مشتری، پرونده کامل ۳۶۰ درجه شامل آمار، تاریخچه سفارش، تیکت‌ها و پاداش‌ها باز می‌شود.</p>
            <div class="customers-hero-actions">
                <a class="btn" href="#customerQuickPanel">افزودن مشتری جدید</a>
                <button class="btn btn-ghost" type="button" onclick="askCustomersAssistant('لیست مشتریان در ریزش را بده و برای هر کدام یک اقدام پیگیری پیشنهاد بده', this)">تحلیل با دستیار</button>
                <a class="btn btn-ghost" href="{{ url('/app/customers/export') }}">دریافت خروجی</a>
                <a class="btn btn-ghost" href="{{ url('/app/reports/rfm') }}">تحلیل RFM</a>
            </div>
        </div>
        <div class="customers-score">
            <a href="{{ url('/app/customers') }}" style="--metric-color:#8b5cf6;">
                <span>کل مشتریان</span><b>@fa(number_format($customers->total()))</b><small>ثبت‌شده در سامانه</small>
            </a>
            <a href="{{ url('/app/customers?view=vip') }}" style="--metric-color:#f59e0b;">
                <span>ارزش خرید</span><b>@money($visibleRevenue)</b><small>در نمای فعلی</small>
            </a>
            <a href="{{ url('/app/customers') }}" style="--metric-color:#10b981;">
                <span>سفارش‌ها</span><b>@fa(number_format($visibleOrders))</b><small>مجموع در نما</small>
            </a>
            <a href="{{ url('/app/customers?view=follow') }}" style="--metric-color:#ef4444;">
                <span>نیاز پیگیری</span><b>@fa($customerGroups->get('follow', collect())->count())</b><small>در این صفحه</small>
            </a>
        </div>
    </section>

    {{-- نوار فیلترها + سوییچ نمایش --}}
    <form method="get" class="customers-toolbar">
        <div>
            <label>جستجو</label>
            <input name="search" value="{{ request('search') }}" placeholder="نام، شماره، ایمیل یا کد ملی">
        </div>
        <div>
            <label>منبع جذب</label>
            <select name="source">
                <option value="">همه منابع</option>
                @foreach($sourceLabels as $key => $label)
                    <option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>مرتب‌سازی</label>
            <select name="sort">
                <option value="latest" @selected(request('sort') === 'latest' || !request('sort'))>جدیدترین ثبت</option>
                <option value="value"  @selected(request('sort') === 'value')>بیشترین ارزش</option>
                <option value="orders" @selected(request('sort') === 'orders')>بیشترین سفارش</option>
                <option value="name"   @selected(request('sort') === 'name')>الفبایی نام</option>
            </select>
        </div>

        {{-- Monday-style view switcher --}}
        <div class="customers-view-switch" role="tablist">
            <button type="button" class="{{ !request('layout') || request('layout') === 'board' ? 'is-active' : '' }}" onclick="setLayout('board')" title="نمای بورد">
                🗂 بورد
            </button>
            <button type="button" class="{{ request('layout') === 'table' ? 'is-active' : '' }}" onclick="setLayout('table')" title="نمای جدول کامل">
                📋 جدول
            </button>
        </div>

        <div class="customers-toolbar-actions">
            <button class="btn">اعمال فیلتر</button>
            @if(request()->hasAny(['search','source','sort','view','layout']))
                <a class="btn btn-ghost" href="{{ url('/app/customers') }}">پاک کردن</a>
            @endif
        </div>

        <input type="hidden" name="layout" id="layoutInput" value="{{ request('layout', 'board') }}">
    </form>

    {{-- نوار خلاصه --}}
    <section class="customers-insight">
        <div><span class="customers-dot is-vip"></span><b>@fa($customerGroups->get('vip', collect())->count())</b><small>مشتری ارزشمند در این صفحه</small></div>
        <div><span class="customers-dot is-active"></span><b>@fa($customerGroups->get('active', collect())->count())</b><small>مشتری فعال</small></div>
        <div><span class="customers-dot is-new"></span><b>@fa($customerGroups->get('new', collect())->count())</b><small>تازه‌وارد</small></div>
        <div><span class="customers-dot is-follow"></span><b>@fa($customerGroups->get('follow', collect())->count())</b><small>نیازمند پیگیری</small></div>
    </section>

    {{-- بورد + کارت‌های زیر --}}
    <section class="customers-layout">

        <div class="customers-main">
            <article class="customers-card is-accent" style="--card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>بورد مشتریان</span>
                        <h2>مدیریت گروهی مشتریان بر اساس ارزش و وضعیت خرید</h2>
                        <p>روی هر مشتری کلیک کن تا پرونده ۳۶۰ درجه شامل آمار، تاریخچه، سفارش‌ها و پاداش‌ها باز شود.</p>
                    </div>
                    <a class="customers-link" href="#customerQuickPanel">مشتری جدید</a>
                </header>

                <div class="customers-board">
                    @foreach($groupCards as $bucket)
                        @php
                            $groupCustomers = $customerGroups->get($bucket['key'], collect());
                            $groupCount     = $groupCustomers->count();
                            $groupValue     = $groupCustomers->sum(fn($c) => (float)($c->lifetime_value ?? 0));
                            $groupOrders    = $groupCustomers->sum(fn($c) => (int)($c->orders_count ?? 0));
                            $ratio          = $customerRows->count() > 0
                                                 ? round(($groupCount / max(1, $customerRows->count())) * 100)
                                                 : 0;
                            $isOpen         = in_array($bucket['key'], $openGroups, true) && $groupCount > 0;
                            $groupClass     = $isOpen ? 'is-open' : 'is-collapsed';
                        @endphp

                        <div class="customers-group {{ $groupClass }}" style="--group-color:{{ $bucket['color'] }}; --group-ratio:{{ $ratio }}%;">
                            <button type="button" class="customers-group-title" onclick="toggleCustomersGroup(this)">
                                <span class="cg-caret">◀</span>
                                <span class="cg-mark"></span>
                                <span class="cg-title-block">
                                    <b>{{ $bucket['label'] }}</b>
                                    <small>{{ $bucket['hint'] }}</small>
                                </span>
                                <span class="cg-count">@fa($groupCount)</span>
                                <span class="cg-progress"></span>
                                <span class="cg-ratio">@fa($ratio)٪</span>
                            </button>

                            <div class="customers-group-summary">
                                <span>مجموع ارزش خرید: <b>@money($groupValue)</b> @unit</span>
                                <span>مجموع سفارش‌ها: <b>@fa($groupOrders)</b></span>
                                <span>تعداد مشتری: <b>@fa($groupCount)</b></span>
                            </div>

                            <div class="customers-table-wrap">
                                <div class="customers-table">
                                    <div class="customers-thead">
                                        <div>مشتری</div>
                                        <div>ارزش</div>
                                        <div>سفارش</div>
                                        <div>منبع</div>
                                        <div>رتبه</div>
                                        <div>تاریخ ثبت</div>
                                        <div>عملیات</div>
                                    </div>

                                    @forelse($groupCustomers as $customer)
                                        @php
                                            $fullName  = $customer->full_name ?: 'مشتری بدون نام';
                                            $firstChar = mb_substr($fullName, 0, 1);
                                            $palette   = $pickAvatarColor($fullName);
                                            $isVip     = $bucket['key'] === 'vip';
                                            $rating    = $rateCustomer($customer);
                                            $activityPct = min(100, max(0, ((int)($customer->orders_count ?? 0)) * 10));
                                            $phone     = $customer->phone;
                                            $email     = $customer->email;
                                        @endphp

                                        <details class="customers-row">
                                            <summary>
                                                <div class="customers-cell">
                                                    <div class="customers-cell-person">
                                                        <div class="customer-avatar {{ $isVip ? 'has-vip' : '' }}"
                                                             style="--avatar-bg:{{ $palette['bg'] }}; --avatar-color:{{ $palette['fg'] }};">
                                                            {{ $firstChar }}
                                                        </div>
                                                        <div>
                                                            <b>{{ $fullName }}</b>
                                                            <small>{{ $phone ?: ($email ?: 'بدون تماس') }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="customers-cell">
                                                    @if((float)($customer->lifetime_value ?? 0) > 0)
                                                        <span class="customers-pill is-value">@money($customer->lifetime_value)</span>
                                                    @else
                                                        <span class="customers-pill is-empty">—</span>
                                                    @endif
                                                    <span class="customer-activity-bar" style="--activity: {{ $activityPct }}%;"><span></span></span>
                                                </div>
                                                <div class="customers-cell">
                                                    <span class="customers-pill is-orders">@fa($customer->orders_count ?? 0)</span>
                                                </div>
                                                <div class="customers-cell">
                                                    <span class="customers-pill {{ $sourceClass($customer->source) }}">{{ $sourceTitle($customer->source) }}</span>
                                                </div>
                                                <div class="customers-cell">
                                                    <span class="customer-rating">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <span class="{{ $i <= $rating ? 'filled' : '' }}">★</span>
                                                        @endfor
                                                    </span>
                                                </div>
                                                <div class="customers-cell">
                                                    <small style="color:var(--mut); font-size:0.74rem;">@jdate($customer->created_at)</small>
                                                </div>
                                                <div class="customers-cell">
                                                    <div class="customer-quick-icons">
                                                        @if($phone)
                                                            <a href="tel:{{ $phone }}" title="تماس تلفنی">📞</a>
                                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" target="_blank" title="واتس‌اپ">💬</a>
                                                        @endif
                                                        @if($email)
                                                            <a href="mailto:{{ $email }}" title="ارسال ایمیل">✉️</a>
                                                        @endif
                                                        <a href="{{ url('/app/customers/' . $customer->id) }}" title="پرونده ۳۶۰">👤</a>
                                                    </div>
                                                </div>
                                            </summary>

                                            {{-- پنل جزئیات ۳۶۰ درجه --}}
                                            <div class="customers-detail-panel">

                                                {{-- ستون راست: پروفایل و آمار --}}
                                                <div class="customers-detail-block">
                                                    <h4>پرونده مشتری <span>{{ $bucket['label'] }}</span></h4>

                                                    <div class="customer-profile-hero">
                                                        <div class="avatar-large {{ $isVip ? 'has-vip' : '' }}"
                                                             style="--avatar-bg:{{ $palette['bg'] }}; --avatar-color:{{ $palette['fg'] }};">
                                                            {{ $firstChar }}
                                                        </div>
                                                        <div class="profile-info">
                                                            <b>{{ $fullName }}</b>
                                                            @if($phone)
                                                                <small>📞 {{ $phone }}</small>
                                                            @endif
                                                            @if($email)
                                                                <small dir="ltr">✉️ {{ $email }}</small>
                                                            @endif
                                                            <small class="rtl">
                                                                <span class="customer-rating">
                                                                    @for($i = 1; $i <= 5; $i++)
                                                                        <span class="{{ $i <= $rating ? 'filled' : '' }}">★</span>
                                                                    @endfor
                                                                </span>
                                                                رتبه ارزش
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <div class="customer-stats-grid">
                                                        <div class="is-highlight">
                                                            <span>ارزش کل خرید</span>
                                                            <b>@money($customer->lifetime_value ?? 0)</b>
                                                        </div>
                                                        <div class="is-orders">
                                                            <span>تعداد سفارش</span>
                                                            <b>@fa($customer->orders_count ?? 0)</b>
                                                        </div>
                                                        <div class="is-money">
                                                            <span>میانگین سبد</span>
                                                            <b>@money(($customer->orders_count ?? 0) > 0 ? ($customer->lifetime_value ?? 0) / $customer->orders_count : 0)</b>
                                                        </div>
                                                        <div>
                                                            <span>روزهای عضویت</span>
                                                            <b>@fa($customer->created_at ? $customer->created_at->diffInDays(now()) : 0)</b>
                                                        </div>
                                                    </div>

                                                    <div class="customer-quick-actions">
                                                        @if($phone)
                                                            <a href="tel:{{ $phone }}"><span class="qa-icon">📞</span><span>تماس</span></a>
                                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" target="_blank"><span class="qa-icon">💬</span><span>واتس‌اپ</span></a>
                                                        @endif
                                                        <a href="{{ url('/app/reminders?customer_id=' . $customer->id) }}"><span class="qa-icon">⏰</span><span>یادآور</span></a>
                                                        <a href="{{ url('/app/tickets?customer_id=' . $customer->id) }}"><span class="qa-icon">🎫</span><span>تیکت</span></a>
                                                    </div>
                                                </div>

                                                {{-- ستون چپ: Timeline فعالیت‌ها --}}
                                                <div class="customers-detail-block">
                                                    <h4>تاریخچه فعالیت <span>آخرین رخدادها</span></h4>

                                                    <div class="customer-timeline">
                                                        @if(($customer->orders_count ?? 0) > 0)
                                                            <div class="customer-timeline-item">
                                                                <div class="ct-icon is-order">🛒</div>
                                                                <div>
                                                                    <b>@fa($customer->orders_count ?? 0) سفارش ثبت‌شده</b>
                                                                    <small>مجموع ارزش: @money($customer->lifetime_value ?? 0) @unit</small>
                                                                </div>
                                                                <span class="ct-value is-money">فعال</span>
                                                            </div>
                                                        @endif

                                                        @if($customer->created_at)
                                                            <div class="customer-timeline-item">
                                                                <div class="ct-icon">🎯</div>
                                                                <div>
                                                                    <b>عضویت در سامانه</b>
                                                                    <small>از منبع: {{ $sourceTitle($customer->source) }}</small>
                                                                </div>
                                                                <span class="ct-value">@jdate($customer->created_at)</span>
                                                            </div>
                                                        @endif

                                                        @if((float)($customer->lifetime_value ?? 0) === 0)
                                                            <div class="customer-timeline-item">
                                                                <div class="ct-icon is-ticket">⚠️</div>
                                                                <div>
                                                                    <b>هنوز خریدی ثبت نشده</b>
                                                                    <small>پیشنهاد: ارسال پیام تشویقی یا تخفیف خوشامد</small>
                                                                </div>
                                                                <span class="ct-value is-warn">نیاز پیگیری</span>
                                                            </div>
                                                        @endif

                                                        @if($isVip)
                                                            <div class="customer-timeline-item">
                                                                <div class="ct-icon is-reward">🏆</div>
                                                                <div>
                                                                    <b>مشتری VIP</b>
                                                                    <small>ارزش خرید بالا، مستحق تخفیف اختصاصی</small>
                                                                </div>
                                                                <span class="ct-value">اولویت</span>
                                                            </div>
                                                        @endif

                                                        @if(!$phone && !$email)
                                                            <div class="customer-timeline-empty">
                                                                اطلاعات تماس این مشتری ثبت نشده است.
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="customer-detail-actions">
                                                        <a href="{{ url('/app/customers/' . $customer->id) }}" class="is-primary">پرونده کامل ۳۶۰</a>
                                                        <a href="{{ url('/app/customers/' . $customer->id . '/edit') }}">ویرایش</a>
                                                        <a href="{{ url('/app/orders?customer_id=' . $customer->id) }}">سفارش‌ها</a>
                                                        <button type="button" class="is-success" onclick="askCustomersAssistant('برای مشتری {{ addslashes($fullName) }} یک یادآور پیگیری برای فردا بگذار', this)">پیگیری با دستیار</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </details>
                                    @empty
                                        <div class="customers-group-empty">در این گروه مشتری‌ای وجود ندارد.</div>
                                    @endforelse
                                </div>
                            </div>

                            <a class="customers-group-add" href="#customerQuickPanel">+ افزودن مشتری در گروه «{{ $bucket['label'] }}»</a>
                        </div>
                    @endforeach
                </div>

                @if($customers->hasPages())
                    <div style="margin-top:1rem;">{{ $customers->withQueryString()->links() }}</div>
                @endif
            </article>
        </div>

        {{-- بخش زیر بورد --}}
        <div class="customers-below">

            <article class="customers-card is-accent" style="--card-color:#f59e0b;">
                <header>
                    <div>
                        <span>پیشنهاد دستیار</span>
                        <h2>تحلیل و اقدام هوشمند روی مشتریان</h2>
                    </div>
                </header>
                <div class="customers-suggest-list">
                    <button type="button" onclick="askCustomersAssistant('لیست مشتریان در آستانه ریزش را بده و برای هر کدام یک اقدام پیگیری پیشنهاد بده', this)">
                        <b>تحلیل مشتریان در ریزش</b>
                        <small>شناسایی مشتریانی که مدتی خرید نکرده‌اند</small>
                    </button>
                    <button type="button" onclick="askCustomersAssistant('برای مشتریان VIP یک پیشنهاد تخفیف اختصاصی طراحی کن', this)">
                        <b>پیشنهاد VIP اختصاصی</b>
                        <small>طراحی کمپین تخفیف برای مشتریان ارزشمند</small>
                    </button>
                    <button type="button" onclick="askCustomersAssistant('برای مشتریان تازه‌وارد بدون خرید، پیام خوشامد و کد تخفیف اولین خرید بفرست', this)">
                        <b>پیام خوشامد به تازه‌واردها</b>
                        <small>افزایش نرخ تبدیل به خریدار</small>
                    </button>
                    <button type="button" onclick="askCustomersAssistant('گزارش تحلیل ارزش طول عمر مشتری (CLV) را بده', this)">
                        <b>گزارش CLV و ارزش مشتری</b>
                        <small>تحلیل ارزش طول عمر و RFM</small>
                    </button>
                    <button type="button" onclick="askCustomersAssistant('لیست مشتریانی که در ۹۰ روز اخیر خریدی نداشتند را بده و پیشنهاد بازگشت بساز', this)">
                        <b>بازگرداندن مشتریان غیرفعال</b>
                        <small>پیشنهاد کمپین بازگشت ۹۰ روزه</small>
                    </button>
                </div>
            </article>

            <article class="customers-card is-accent" style="--card-color:#10b981;" id="customerQuickPanel">
                <header>
                    <div>
                        <span>افزودن مشتری جدید</span>
                        <h2>ثبت سریع مشتری در سامانه</h2>
                    </div>
                </header>
                <form method="post" action="{{ url('/app/customers') }}" class="customers-quick-form">
                    @csrf

                    <div>
                        <label>نام و نام خانوادگی</label>
                        <input name="full_name" required placeholder="نام کامل مشتری">
                    </div>

                    <div class="customers-form-grid">
                        <div>
                            <label>شماره موبایل</label>
                            <input name="phone" class="ltr" placeholder="۰۹۱۲۳۴۵۶۷۸۹">
                        </div>
                        <div>
                            <label>ایمیل</label>
                            <input name="email" type="email" class="ltr" placeholder="user@example.com">
                        </div>
                    </div>

                    <div class="customers-form-grid">
                        <div>
                            <label>کد ملی</label>
                            <input name="national_id" class="ltr" placeholder="۱۰ رقم">
                        </div>
                        <div>
                            <label>منبع جذب</label>
                            <select name="source">
                                @foreach($sourceLabels as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>نام شرکت (اختیاری)</label>
                        <input name="company_name" placeholder="در صورت مشتری حقوقی">
                    </div>

                    <div class="customers-form-grid">
                        <div>
                            <label>تاریخ تولد (شمسی)</label>
                            <input name="birthday" class="jdate" placeholder="۱۳۷۰/۰۵/۰۱" autocomplete="off">
                        </div>
                        <div>
                            <label>کد اقتصادی</label>
                            <input name="economic_code" class="ltr" placeholder="اختیاری">
                        </div>
                    </div>

                    <div>
                        <label>یادداشت</label>
                        <input name="notes" placeholder="اطلاعات تکمیلی مهم">
                    </div>

                    <button class="btn">ثبت مشتری</button>
                    <small>پس از ثبت، پرونده ۳۶۰ درجه ایجاد می‌شود و می‌توانی سفارش، تیکت و یادآور برای این مشتری ثبت کنی.</small>
                </form>
            </article>

        </div>
    </section>
</div>

<script>
    // باز و بسته کردن گروه‌های بورد
    function toggleCustomersGroup(btn) {
        const group = btn.closest('.customers-group');
        if (!group) return;
        group.classList.toggle('is-collapsed');
        group.classList.toggle('is-open');
    }

    // تغییر نمایش بورد/جدول
    function setLayout(mode) {
        const input = document.getElementById('layoutInput');
        if (input) input.value = mode;
        // فرم را ارسال کن
        input.form.submit();
    }

    // پرسش از دستیار
    function askCustomersAssistant(text, btn) {
        text = text || 'گزارش مشتریان را بده';

        if (btn) {
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            var originalHtml = btn.innerHTML;
            btn.innerHTML = '<b style="color:#6d28d9">در حال باز کردن دستیار...</b><small>لطفاً چند لحظه صبر کنید</small>';
        }

        var assistantUrl = '{{ url("/app/assistant") }}'
            + '?prompt=' + encodeURIComponent(text)
            + '&context_type=customers_center'
            + '&context_title=' + encodeURIComponent('مرکز مدیریت مشتریان')
            + '&context_url='   + encodeURIComponent(window.location.href);

        window.location.href = assistantUrl;
    }
</script>
@endsection
