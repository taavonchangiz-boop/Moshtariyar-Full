<!DOCTYPE html>
<html lang="fa" dir="rtl" style="overflow-x: hidden;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f4f7fa">
    <meta name="description" content="@yield('description', 'سامانهٔ مدیریت هوشمند مشتریان، فروش، وفاداری و اتوماسیون — مشتری‌یار')">
    <meta name="robots" content="noindex, nofollow">

    {{-- Open Graph — شبکه‌های اجتماعی --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', 'سامانهٔ مدیریت هوشمند مشتریان، فروش، وفاداری و اتوماسیون — مشتری‌یار')">
    @php
        $siteBrandName = \Modules\Core\Entities\Setting::get('site_brand_name', config('brand.name'));
        $siteFaviconUrl = \Modules\Core\Entities\Setting::get('site_favicon_url', '');
        $siteLogoUrl = \Modules\Core\Entities\Setting::get('site_logo_url', config('brand.logo'));
        $siteBrandIcon = \Modules\Core\Entities\Setting::get('site_icon', '✦');
        $siteDefaultTheme = \Modules\Core\Entities\Setting::get('site_default_theme', 'light');
        $siteBrandTagline = \Modules\Core\Entities\Setting::get('site_brand_tagline', config('brand.tagline'));
        $sitePrimaryColor = \Modules\Core\Entities\Setting::get('site_primary_color', '#0ea5e9');
    @endphp
    <style>
        :root{
            --site-primary: {{ $sitePrimaryColor }};
        }
        .topbar-title-icon{
            background: var(--site-primary) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px color-mix(in srgb, var(--site-primary) 28%, transparent) !important;
        }
        /* دکمه‌ها — ۲۰۲۶ مدرن — بدون گرادینت جیغ بچه‌گانه */
        .btn{
            background: var(--site-primary) !important;
            color: #ffffff !important;
            border: 1px solid color-mix(in srgb, var(--site-primary) 84%, black 16%) !important;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.08) inset, 0 1px 2px rgba(15,23,42,0.06) !important;
        }
        .btn:hover{
            background: color-mix(in srgb, var(--site-primary) 88%, black 12%) !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.12) inset, 0 6px 18px rgba(15,23,42,0.10) !important;
            filter: brightness(1.04);
        }
        .btn:active{
            background: color-mix(in srgb, var(--site-primary) 84%, black 16%) !important;
            transform: translateY(0) scale(.985);
            filter: brightness(.97);
        }
        .nav-sub a.active{
            border-right-color: var(--site-primary) !important;
        }
        .nav-sub a:hover .nav-sub-icon,
        .nav-sub a.active .nav-sub-icon{
            background: color-mix(in srgb, var(--site-primary) 14%, transparent) !important;
            border-color: color-mix(in srgb, var(--site-primary) 24%, transparent) !important;
            color: var(--site-primary) !important;
        }
    </style>
    <meta property="og:site_name" content="{{ $siteBrandName }}">

    <title>@yield('title', $siteBrandName)</title>
    @if(!empty($siteFaviconUrl))
        <link rel="icon" type="image/webp" href="{{ asset(ltrim($siteFaviconUrl,'/')) }}">
        <link rel="shortcut icon" href="{{ asset(ltrim($siteFaviconUrl,'/')) }}">
    @endif

    {{-- پیش‌بارگذاری فونت‌های حیاتی --}}
    <link rel="preload" href="{{ asset('fonts/estedad/Estedad-VF.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/vazirmatn/Vazirmatn-VF.woff2') }}" as="font" type="font/woff2" crossorigin>

    {{-- شیوه‌نامه‌های اصلی --}}
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/floating-actions.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hero-metrics-compact.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-readability-fix.css') }}">
    @stack('styles')
    {{-- بازطراحی مدرن ۲۰۲۶ دکمه‌ها — باید بعد از همهٔ استایل‌های بورد لود شود تا گرادینت بچه‌گانه را کامل بازنویسی کند --}}
    <link rel="stylesheet" href="{{ asset('css/buttons-modern-2026.css') }}">

    <style>
        /* آیکن‌های جذاب منو - هماهنگ با باشگاه مشتریان */
        .nav-sub a{
            display:flex !important;
            align-items:center !important;
            gap:.55rem !important;
            min-height:2.4rem;
            border-radius:.75rem;
            transition:.18s ease;
            position:relative;
        }
        .nav-sub-icon{
            width:1.65rem;
            height:1.65rem;
            min-width:1.65rem;
            border-radius:.6rem;
            display:grid;
            place-items:center;
            background:var(--panel2);
            border:1px solid var(--line);
            font-size:.92rem;
            line-height:1;
            flex-shrink:0;
            transition:.18s ease;
        }
        body.light .nav-sub-icon{
            background:#f8fafc;
            border-color:#e2e8f0;
        }
        .nav-sub a:hover .nav-sub-icon,
        .nav-sub a.active .nav-sub-icon{
            background:rgba(14,165,233,.12);
            border-color:rgba(14,165,233,.22);
            color:var(--acc);
            transform:translateX(-.08rem);
        }
        body.light .nav-sub a:hover .nav-sub-icon,
        body.light .nav-sub a.active .nav-sub-icon{
            background:#e0f2fe;
            border-color:#7dd3fc;
        }
        .nav-sub a.active{
            background:rgba(14,165,233,.10) !important;
            color:var(--acc) !important;
            font-weight:800;
            border-right:3px solid var(--acc);
        }
        body.light .nav-sub a.active{
            background:#e0f2fe !important;
            color:#0284c7 !important;
        }
        /* آیکن گروه اصلی کمی بزرگتر و جذاب‌تر */
        .nav-group summary .nav-emoji{
            width:1.75rem;
            height:1.75rem;
            display:inline-grid;
            place-items:center;
            border-radius:.6rem;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(148,163,184,.12);
            font-size:.95rem;
            margin-left:.15rem;
        }
        body.light .nav-group summary .nav-emoji{
            background:#f1f5f9;
            border-color:#e2e8f0;
        }
        .nav-group[open] summary .nav-emoji{
            background:rgba(14,165,233,.14);
            border-color:rgba(14,165,233,.22);
        }
    </style>
</head>
<body class="{{ request()->cookie('theme', $siteDefaultTheme ?? 'light') === 'dark' ? 'dark' : 'light' }} {{ request()->boolean('embed') ? 'embed-mode' : '' }}">

{{-- ─── لایهٔ محو منوی موبایل ─── --}}
<div class="overlay" onclick="document.body.classList.remove('menu-open')"></div>

{{-- ═══════════════ چیدمان اصلی ═══════════════ --}}
<div class="layout">

    {{-- ═══════════ سایدبار ═══════════ --}}
    <aside class="sidebar">
        <div class="brand">
            @if(!empty($siteLogoUrl))
                <img src="{{ asset(ltrim($siteLogoUrl,'/')) }}" alt="{{ $siteBrandName }}" style="width: 1.9rem; height: 1.9rem; vertical-align: middle; border-radius:.55rem; object-fit:cover; box-shadow:0 .25rem .75rem rgba(0,0,0,.12);" loading="lazy" onerror="this.style.display='none'">
            @endif
            <span style="display:inline-flex; align-items:center; gap:.35rem; min-width:0;">
                @if($siteBrandIcon !== 'logo' || empty($siteLogoUrl))
                    <span style="font-size:1rem;">{{ $siteBrandIcon === 'logo' ? '✦' : $siteBrandIcon }}</span>
                @endif
                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $siteBrandName }}</span>
            </span>
        </div>

        @php $کاربر = auth()->user(); @endphp

        <nav class="nav">
            @php
                $هست_پیشخوان = request()->is('app') || request()->is('app/reports*') || request()->is('app/insights*') || request()->is('app/search*') || request()->is('app/segments*') || request()->is('app/journeys*');
                $هست_فروش = request()->is('app/customers*') || request()->is('app/retention*') || request()->is('app/pipeline*') || request()->is('app/reminders*') || request()->is('app/orders*') || request()->is('app/products*') || request()->is('app/warehouses*') || request()->is('app/pos*') || request()->is('app/messages*');
                $هست_باشگاه = request()->is('app/loyalty*');
                $هست_پشتیبانی = request()->is('app/tickets*') || request()->is('app/kb*') || request()->is('app/support*');
                $هست_فروشگاه = request()->is('app/woocommerce*') || request()->is('app/sync-logs*') || request()->is('app/payments*') || request()->is('app/tax-invoices*');
                $هست_اتوماسیون = request()->is('app/workflows*') || request()->is('app/campaigns*');
                $هست_سیستم = request()->is('app/users*') || request()->is('app/settings*') || request()->is('app/backups*');
                $هست_مدیرکل = request()->is('superadmin*');
            @endphp

            {{-- مدیر کل هلدینگ --}}
            @if($کاربر?->isSuperAdmin())
            <details class="nav-group" {{ $هست_مدیرکل ? 'open' : '' }} style="border-color: var(--warn);">
                <summary><span style="color: var(--warn);"><span class="nav-emoji">🏢</span> مدیریت کل هلدینگ</span></summary>
                <div class="nav-sub">
                    <a href="{{ url('/superadmin/businesses') }}" class="{{ request()->is('superadmin/businesses*') ? 'active' : '' }}"><span class="nav-sub-icon">🏢</span> مدیریت کسب‌وکارها</a>
                    <a href="{{ url('/superadmin/users') }}" class="{{ request()->is('superadmin/users*') ? 'active' : '' }}"><span class="nav-sub-icon">👑</span> مدیریت کل کاربران</a>
                </div>
            </details>
            @endif

            {{-- پیشخوان --}}
            @if($کاربر?->hasPermission('dashboard.view'))
            <details class="nav-group" {{ $هست_پیشخوان ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">🏠</span> پیشخوان</span></summary>
                <div class="nav-sub">
                    <a href="{{ url('/app') }}" class="{{ request()->is('app') ? 'active' : '' }}"><span class="nav-sub-icon">📊</span> داشبورد</a>
                    <a href="{{ url('/app/reports') }}" class="{{ request()->is('app/reports') ? 'active' : '' }}"><span class="nav-sub-icon">📈</span> گزارش‌ها</a>
                    <a href="{{ url('/app/reports/rfm') }}" class="{{ request()->is('app/reports/rfm*') ? 'active' : '' }}"><span class="nav-sub-icon">👥</span> تحلیل رفتار مشتریان</a>
                    <a href="{{ url('/app/insights') }}" class="{{ request()->is('app/insights*') ? 'active' : '' }}"><span class="nav-sub-icon">💡</span> تحلیل هوشمند</a>
                    <a href="{{ url('/app/segments') }}" class="{{ request()->is('app/segments*') ? 'active' : '' }}"><span class="nav-sub-icon">🎯</span> بخش‌بندی مشتریان</a>
                    <a href="{{ url('/app/journeys') }}" class="{{ request()->is('app/journeys*') ? 'active' : '' }}"><span class="nav-sub-icon">🗺️</span> سفر مشتری</a>
                    <a href="{{ url('/app/search/advanced') }}" class="{{ request()->is('app/search*') ? 'active' : '' }}"><span class="nav-sub-icon">🔍</span> جستجوی پیشرفته</a>
                </div>
            </details>
            @endif

            {{-- دستیار هوشمند --}}
            @if($کاربر?->hasPermission('dashboard.view'))
            <details class="nav-group" {{ request()->is('app/assistant*') || request()->is('app/kb/chat-logs*') ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">🤖</span> دستیار هوشمند</span></summary>
                <div class="nav-sub">
                    <a href="{{ url('/app/assistant') }}" class="{{ request()->is('app/assistant') ? 'active' : '' }}"><span class="nav-sub-icon">💬</span> چت با دستیار</a>
                    <a href="{{ url('/app/kb/chat-logs') }}" class="{{ request()->is('app/kb/chat-logs*') ? 'active' : '' }}"><span class="nav-sub-icon">🕘</span> تاریخچه گفتگوها</a>
                    <a href="{{ url('/app/assistant/settings') }}" class="{{ request()->is('app/assistant/settings*') ? 'active' : '' }}"><span class="nav-sub-icon">🧠</span> تنظیمات مغز هوش مصنوعی</a>
                    <a href="{{ url('/app/assistant/widget-settings') }}" class="{{ request()->is('app/assistant/widget-settings*') ? 'active' : '' }}"><span class="nav-sub-icon">🎨</span> تنظیمات ظاهر ابزارک</a>
                </div>
            </details>
            @endif

            {{-- مشتریان و فروش --}}
            @if($کاربر?->hasPermission('customers.view') || $کاربر?->hasPermission('leads.view') || $کاربر?->hasPermission('orders.view'))
            <details class="nav-group" {{ $هست_فروش ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">💼</span> مشتریان و فروش</span></summary>
                <div class="nav-sub">
                    @if($کاربر?->hasPermission('customers.view'))
                    <a href="{{ url('/app/customers') }}" class="{{ request()->is('app/customers*') ? 'active' : '' }}"><span class="nav-sub-icon">👥</span> مشتریان</a>
                    @endif
                    @if($کاربر?->hasPermission('customers.view'))
                    <a href="{{ url('/app/pos') }}" class="{{ request()->is('app/pos*') ? 'active' : '' }}"><span class="nav-sub-icon">💳</span> فروش سریع</a>
                    @endif
                    @if($کاربر?->hasPermission('customers.view'))
                    <a href="{{ url('/app/retention') }}" class="{{ request()->is('app/retention*') ? 'active' : '' }}"><span class="nav-sub-icon">🛟</span> مرکز بازگشت و حفظ مشتری</a>
                    <a href="{{ route('reports.recovery.history') }}" class="{{ request()->is('app/reports/recovery/history*') ? 'active' : '' }}"><span class="nav-sub-icon">🗂️</span> آرشیو گزارش‌های بازگشت</a>
                    <a href="{{ url('/app/messages') }}" class="{{ request()->is('app/messages*') ? 'active' : '' }}"><span class="nav-sub-icon">📨</span> مرکز پیام‌رسانی</a>
                    @endif
                    @if($کاربر?->hasPermission('leads.view'))
                    <a href="{{ url('/app/pipeline') }}" class="{{ request()->is('app/pipeline*') ? 'active' : '' }}"><span class="nav-sub-icon">📉</span> قیف فروش</a>
                    @endif
                    @if($کاربر?->hasPermission('customers.view'))
                    <a href="{{ url('/app/reminders') }}" class="{{ request()->is('app/reminders*') ? 'active' : '' }}"><span class="nav-sub-icon">⏰</span> یادآوری‌ها</a>
                    <a href="{{ url('/app/products') }}" class="{{ request()->is('app/products*') ? 'active' : '' }}"><span class="nav-sub-icon">📦</span> محصولات</a>
                    <a href="{{ url('/app/warehouses') }}" class="{{ request()->is('app/warehouses*') ? 'active' : '' }}"><span class="nav-sub-icon">🏬</span> انبارها</a>
                    <a href="{{ url('/app/pos/cashier') }}" class="{{ request()->is('app/pos/cashier*') || request()->is('app/pos/shifts*') ? 'active' : '' }}"><span class="nav-sub-icon">📊</span> گزارش صندوق</a>
                    @endif
                    @if($کاربر?->hasPermission('orders.view'))
                    <a href="{{ url('/app/orders') }}" class="{{ request()->is('app/orders*') ? 'active' : '' }}"><span class="nav-sub-icon">🛒</span> سفارش‌ها</a>
                    @endif
                </div>
            </details>
            @endif

            {{-- باشگاه مشتریان --}}
            @if($کاربر?->hasPermission('customers.view'))
            <details class="nav-group" {{ $هست_باشگاه ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">🎁</span> باشگاه مشتریان</span></summary>
                <div class="nav-sub">
                    <a href="{{ url('/app/loyalty') }}" class="{{ request()->is('app/loyalty') ? 'active' : '' }}"><span class="nav-sub-icon">📊</span> داشبورد و اعضا</a>
                    <a href="{{ url('/app/loyalty/campaigns') }}" class="{{ request()->is('app/loyalty/campaigns*') ? 'active' : '' }}"><span class="nav-sub-icon">🎯</span> کمپین‌ها و ماموریت‌ها</a>
                    <a href="{{ url('/app/loyalty/reports') }}" class="{{ request()->is('app/loyalty/reports*') ? 'active' : '' }}"><span class="nav-sub-icon">🎁</span> گزارش‌ها و پاداش‌ها</a>
                    @if($کاربر?->hasPermission('settings.manage'))
                    <a href="{{ url('/app/loyalty/settings') }}" class="{{ request()->is('app/loyalty/settings*') ? 'active' : '' }}"><span class="nav-sub-icon">🏆</span> سطوح و قوانین</a>
                    <a href="{{ url('/app/loyalty/wheel') }}" class="{{ request()->is('app/loyalty/wheel*') ? 'active' : '' }}"><span class="nav-sub-icon">🎡</span> گردونه شانس</a>
                    @endif
                    <a href="{{ route('club.login') }}" target="_blank"><span class="nav-sub-icon">🚪</span> ورود به پنل مشتریان</a>
                </div>
            </details>
            @endif

            {{-- پشتیبانی و دانش --}}
            @if($کاربر?->hasPermission('tickets.view'))
            <details class="nav-group" {{ $هست_پشتیبانی ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">💬</span> پشتیبانی و دانش</span></summary>
                <div class="nav-sub">
                    <a href="{{ url('/app/tickets') }}" class="{{ request()->is('app/tickets') || request()->is('app/tickets/*') ? 'active' : '' }}"><span class="nav-sub-icon">🎫</span> درخواست‌ها (تیکت‌ها)</a>
                    <a href="{{ url('/app/tickets/sla') }}" class="{{ request()->is('app/tickets/sla*') ? 'active' : '' }}"><span class="nav-sub-icon">⏱️</span> گزارش پاسخ‌گویی</a>
                    @if($کاربر?->hasPermission('tickets.manage'))
                    <a href="{{ url('/app/tickets/responses') }}" class="{{ request()->is('app/tickets/responses*') ? 'active' : '' }}"><span class="nav-sub-icon">💬</span> پاسخ‌های آماده</a>
                    @endif
                    <a href="{{ url('/app/kb') }}" class="{{ request()->is('app/kb') || request()->is('app/kb/create') || request()->is('app/kb/*/edit') ? 'active' : '' }}"><span class="nav-sub-icon">📚</span> پایگاه دانش</a>
                    <a href="{{ url('/app/kb/chat-logs') }}" class="{{ request()->is('app/kb/chat-logs*') ? 'active' : '' }}"><span class="nav-sub-icon">🤖</span> گفتگوهای دستیار</a>
                    <a href="{{ url('/app/support') }}" class="{{ request()->is('app/support*') ? 'active' : '' }}"><span class="nav-sub-icon">☎️</span> تماس با پشتیبانی</a>
                </div>
            </details>
            @endif

            {{-- فروشگاه و مالی --}}
            @if($کاربر?->hasPermission('woocommerce.view') || $کاربر?->hasPermission('orders.view') || $کاربر?->hasPermission('tax.view'))
            <details class="nav-group" {{ $هست_فروشگاه ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">🛒</span> فروشگاه و مالی</span></summary>
                <div class="nav-sub">
                    @if($کاربر?->hasPermission('woocommerce.view'))
                    <a href="{{ url('/app/woocommerce') }}" class="{{ request()->is('app/woocommerce*') ? 'active' : '' }}"><span class="nav-sub-icon">🔌</span> اتصال ووکامرس</a>
                    <a href="{{ url('/app/sync-logs') }}" class="{{ request()->is('app/sync-logs*') ? 'active' : '' }}"><span class="nav-sub-icon">🔄</span> لاگ همگام‌سازی</a>
                    @endif
                    @if($کاربر?->hasPermission('orders.view'))
                    <a href="{{ url('/app/payments') }}" class="{{ request()->is('app/payments*') ? 'active' : '' }}"><span class="nav-sub-icon">💳</span> تراکنش‌های پرداخت</a>
                    @endif
                    @if($کاربر?->hasPermission('tax.view'))
                    <a href="{{ url('/app/tax-invoices') }}" class="{{ request()->is('app/tax-invoices*') ? 'active' : '' }}"><span class="nav-sub-icon">🧾</span> فاکتورهای رسمی</a>
                    @endif
                </div>
            </details>
            @endif

            {{-- اتوماسیون --}}
            @if($کاربر?->hasPermission('automation.view') || $کاربر?->hasPermission('automation.manage'))
            <details class="nav-group" {{ $هست_اتوماسیون ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">⚙️</span> اتوماسیون</span></summary>
                <div class="nav-sub">
                    <a href="{{ url('/app/workflows') }}" class="{{ request()->is('app/workflows*') ? 'active' : '' }}"><span class="nav-sub-icon">🔄</span> گردش‌کارها</a>
                    <a href="{{ url('/app/campaigns') }}" class="{{ request()->is('app/campaigns*') ? 'active' : '' }}"><span class="nav-sub-icon">📢</span> کمپین‌های پیام‌رسانی</a>
                </div>
            </details>
            @endif

            {{-- مدیریت سیستم --}}
            @if($کاربر?->hasPermission('settings.manage') || $کاربر?->hasPermission('users.manage'))
            <details class="nav-group" {{ $هست_سیستم ? 'open' : '' }}>
                <summary><span><span class="nav-emoji">🧩</span> مدیریت سیستم</span></summary>
                <div class="nav-sub">
                    @if($کاربر?->hasPermission('users.manage'))
                    <a href="{{ url('/app/users') }}" class="{{ request()->is('app/users*') ? 'active' : '' }}"><span class="nav-sub-icon">👥</span> کاربران و دسترسی‌ها</a>
                    @endif
                    @if($کاربر?->hasPermission('settings.manage'))
                    <a href="{{ url('/app/backups') }}" class="{{ request()->is('app/backups*') ? 'active' : '' }}"><span class="nav-sub-icon">💾</span> پشتیبان‌گیری</a>
                    <a href="{{ url('/app/settings') }}" class="{{ request()->is('app/settings*') ? 'active' : '' }}"><span class="nav-sub-icon">⚙️</span> تنظیمات</a>
                    @endif
                </div>
            </details>
            @endif
        </nav>

        {{-- پانوشت سایدبار — کاربر فعلی --}}
        <div style="margin-top:auto;padding-top:0.875rem;border-top:1px solid var(--line);font-size:0.8125rem">
            @if($کاربر)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:0.5rem;margin-bottom:0.5rem">
                <div>
                    <div style="font-weight:600">{{ $کاربر->name }}</div>
                    <div class="muted" style="font-size:0.75rem">{{ $کاربر->roleLabel() }}</div>
                </div>
                <form method="post" action="{{ url('/logout') }}">
                    @csrf
                    <button class="btn btn-ghost" style="padding:0.3125rem 0.625rem;font-size:0.75rem">خروج</button>
                </form>
            </div>
            @endif
            @php
                $siteOwnerName = \Modules\Core\Entities\Setting::get('site_owner_name', config('brand.owner'));
                $siteOwnerUrl = \Modules\Core\Entities\Setting::get('site_owner_url', config('brand.url'));
            @endphp
            <div style="color:var(--mut);font-size:0.75rem;text-align:center">
                محصولی از <a href="{{ $siteOwnerUrl }}" target="_blank" style="color:var(--acc)">{{ $siteOwnerName }}</a>
            </div>
        </div>
    </aside>

    {{-- ═══════════ محتوای اصلی ═══════════ --}}
    <main class="main">

        {{-- آمار سریع اعلان‌های یکپارچه --}}
        @php
            $مرکز_اعلان = app(\Modules\Core\Services\AdminNotificationService::class);
            $همه_اعلان‌ها = $مرکز_اعلان->getUnifiedNotifications(15);
            $شمارش_اعلان‌ها = $مرکز_اعلان->countUnread();
            if (!function_exists('به_ارقام_فارسی')) {
                function به_ارقام_فارسی($عدد) {
                    if ($عدد === null || $عدد === '') return '';
                    return strtr((string)$عدد, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
                }
            }
        @endphp

        {{-- نوار بالای صفحه --}}
        <div class="topbar">
            <div class="topbar-title">
                <button class="menu-btn" onclick="document.body.classList.add('menu-open')" aria-label="منو">☰</button>
                <div class="topbar-title-icon">
                    @if($siteBrandIcon === 'logo' && !empty($siteLogoUrl))
                        <img src="{{ asset(ltrim($siteLogoUrl,'/')) }}" alt="{{ $siteBrandName }}" style="width:100%; height:100%; object-fit:cover; border-radius:.6rem;">
                    @else
                        {{ $siteBrandIcon === 'logo' ? '✦' : $siteBrandIcon }}
                    @endif
                </div>
                <div>
                    <h1>@yield('heading', 'پیشخوان مدیریت')</h1>
                    <div class="muted">@yield('subtitle', 'سامانهٔ مدیریت ارتباط با مشتری')</div>
                </div>
            </div>

            <div class="top-tools">
                <div class="global-search">
                    <input id="globalSearch" placeholder="جستجوی سریع مشتری، سفارش، درخواست...">
                    <div class="global-results" id="globalResults"></div>
                </div>

                {{-- 🔔 مرکز اعلان‌های یکپارچه --}}
                <div class="drop-wrap">
                    <button class="icon-btn dot" data-count="{{ به_ارقام_فارسی($شمارش_اعلان‌ها) }}" onclick="بازکردن_بازشو('unifiedNotificationDrop')" title="اعلان‌ها">🔔</button>
                    <div class="drop-panel اعلان-یکپارچه" id="unifiedNotificationDrop">
                        <div class="سرصفحه-اعلان">
                            <b>🔔 مرکز اعلان‌ها</b>
                            @if($شمارش_اعلان‌ها > 0)
                                <span class="نشان-اعلان">{{ به_ارقام_فارسی($شمارش_اعلان‌ها) }} اعلان جدید</span>
                            @endif
                        </div>
                        <div class="فهرست-اعلان‌ها">
                            @forelse($همه_اعلان‌ها as $اعلان)
                                <a href="{{ $اعلان['url'] ?? '#' }}" class="ردیف-اعلان {{ $اعلان['urgency'] ?? 'normal' }}">
                                    <span class="آیکن-اعلان">{{ $اعلان['icon'] ?? '📌' }}</span>
                                    <div class="متن-اعلان">
                                        <b>{{ $اعلان['title'] ?? 'اعلان' }}</b>
                                        <small>{{ $اعلان['body'] ?? '' }}@if($اعلان['time_fa'] ?? false) · {{ $اعلان['time_fa'] }}@endif</small>
                                    </div>
                                    @if(($اعلان['urgency'] ?? '') === 'urgent')
                                        <span class="برچسب-فوری">فوری</span>
                                    @endif
                                </a>
                            @empty
                                <div class="اعلان-خالی"><span>🎉</span><p>هیچ اعلان جدیدی نداری!</p></div>
                            @endforelse
                        </div>
                        <div class="پانوشت-اعلان">
                            <a href="{{ url('/app/reminders') }}">📝 همهٔ وظایف</a>
                            <a href="{{ url('/app/tickets') }}">🎫 همهٔ تیکت‌ها</a>
                            <a href="{{ url('/app/orders') }}">🛒 همهٔ سفارش‌ها</a>
                        </div>
                    </div>
                </div>

                <button class="icon-btn theme-btn" onclick="تغییر_تم()" title="روشن/تیره">◐</button>
                <div class="live-clock" id="adminClock">—</div>
            </div>
        </div>

        @if(session('status'))
            <div class="card" style="border-color:var(--ok);color:var(--ok)">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</div>

@php
    $بافت_دستیار = ['type' => 'page', 'id' => null, 'title' => trim($__env->yieldContent('heading', 'صفحهٔ فعلی')), 'url' => url()->current()];
@endphp
<script>window.MoshtariyarAssistantContext = @json($بافت_دستیار, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);</script>

<div id="adminAssistantBackdrop" onclick="بستن_دستیار()"></div>
<button id="adminAssistantFloat" onclick="تغییر_وضعیت_دستیار(event)" title="دستیار هوشمند">🤖</button>
<button id="btnScrollToTop" onclick="window.scrollTo({top:0, behavior:'smooth'})" aria-label="پرش به بالا">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>
</button>
<div id="adminAssistantPanel" onclick="event.stopPropagation()">
    <div class="assist-head"><b>دستیار هوشمند</b><button onclick="بستن_دستیار()">×</button></div>
    <iframe id="adminAssistantFrame" src="{{ url('/app/assistant?embed=1') }}" loading="lazy" title="پنل دستیار هوشمند"></iframe>
</div>
<div class="drawer-overlay" id="drawerOverlay" onclick="بستن_کشو()"></div>
<div class="side-drawer" id="sideDrawer">
    <div class="drawer-header"><b id="drawerTitle">جزئیات</b><button class="drawer-close" onclick="بستن_کشو()">×</button></div>
    <div class="drawer-content"><iframe id="drawerIframe" src="" loading="lazy" title="جزئیات"></iframe></div>
</div>
<script src="{{ asset('js/jdatepicker.js') }}"></script>
<script src="{{ asset('js/app-core.js') }}"></script>
@stack('scripts')
</body>
</html>