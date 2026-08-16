<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f3f7fb">

    {{-- جلوگیری از نمایه‌سازی صفحات داخلی باشگاه توسط موتورهای جستجو --}}
    <meta name="robots" content="noindex, nofollow">

    {{-- عنوان و توضیحات — پویا از هر صفحهٔ باشگاه --}}
    <title>@yield('title', 'باشگاه مشتریان') — {{ config('brand.name') }}</title>
    <meta name="description" content="@yield('description', 'پنل اختصاصی باشگاه مشتریان — پیگیری امتیازها، کیف پول، کوپن‌ها، مأموریت‌ها، گردونهٔ شانس، تیکت‌ها و گفت‌وگو با دستیار هوشمند.')">

    {{-- Open Graph — برای اشتراک‌گذاری در شبکه‌های اجتماعی --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'باشگاه مشتریان') — {{ config('brand.name') }}">
    <meta property="og:description" content="پنل اختصاصی باشگاه مشتریان — پیگیری امتیازها، کیف پول، گردونهٔ شانس، و گفت‌وگو با دستیار هوشمند.">
    <meta property="og:site_name" content="{{ config('brand.name') }}">
    <meta property="og:locale" content="fa_IR">

    {{-- PWA — نصب روی تلفن همراه --}}
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('brand.name') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/moshtariyar-logo.svg') }}">

    {{-- پیش‌بارگذاری فونت‌های حیاتی برای بهبود سرعت بارگذاری --}}
    <link rel="preload" href="{{ asset('fonts/estedad/Estedad-VF.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/vazirmatn/Vazirmatn-VF.woff2') }}" as="font" type="font/woff2" crossorigin>

    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <style>
        :root{--bg:#f3f7fb;--panel:#ffffff;--panel2:#eef4fb;--glass:rgba(255,255,255,.92);--line:#d8e2ef;--txt:#122033;--mut:#66758a;--acc:#0369a1;--acc2:#0ea5e9;--ok:#10b981;--warn:#f59e0b;--bad:#ef4444;--vio:#7c3aed;--clock1:#0284c7;--clock2:#7c3aed;}
        body.dark{--bg:#08111f;--panel:#101c2e;--panel2:#0d1728;--glass:rgba(16,28,46,.88);--line:#2b3b52;--txt:#e8eef7;--mut:#9fb0c7;--acc:#38bdf8;--acc2:#0ea5e9;--ok:#10b981;--warn:#f59e0b;--bad:#ef4444;--vio:#a78bfa;--clock1:#0ea5e9;--clock2:#a78bfa}
        *{box-sizing:border-box} body{margin:0;min-height:100vh;background:radial-gradient(900px 420px at 85% -10%,rgba(56,189,248,.14),transparent),radial-gradient(700px 360px at 10% 0,rgba(167,139,250,.10),transparent),var(--bg);color:var(--txt);font-family:var(--font-fa,Tahoma,sans-serif);font-size:14px;line-height:1.85;transition:.2s background,.2s color} a{color:var(--acc);text-decoration:none}.wrap{width:100%;max-width:none;margin:0;padding:0}.card{background:var(--glass);border:1px solid rgba(128,128,128,.16);border-radius:22px;padding:22px;margin-bottom:16px;box-shadow:0 18px 55px rgba(15,23,42,.08)}body.dark .card{box-shadow:0 18px 55px rgba(0,0,0,.16)}.hero{background:linear-gradient(135deg,rgba(14,165,233,.12),rgba(167,139,250,.09));border-color:rgba(56,189,248,.22)}.grid{display:grid;gap:16px}.grid-4{grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}.grid-2{grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}input,select,textarea{background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:14px;padding:12px 14px;width:100%;font-family:inherit}label{display:block;color:var(--mut);font-size:.86rem;margin-bottom:6px;margin-top:8px}.btn{background:linear-gradient(135deg,var(--acc2),var(--acc));color:#ffffff;border:0;border-radius:14px;padding:12px 18px;font-family:inherit;font-weight:900;cursor:pointer;box-shadow:0 10px 25px rgba(14,165,233,.18)}.btn-ghost{background:#ffffff;border:1px solid var(--line);color:var(--txt);box-shadow:none}body.dark .btn-ghost{background:transparent}.muted{color:var(--mut)}.stat{text-align:center;position:relative;overflow:hidden}.stat .num{font-size:1.85rem;font-weight:1000;color:var(--acc);direction:rtl}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:560px}th,td{padding:11px;border-bottom:1px solid var(--line);white-space:nowrap;text-align:right}th{color:var(--mut);font-weight:600}.ltr{direction:ltr;unicode-bidi:plaintext}.alert{border-color:var(--ok);color:var(--ok)}.err{color:#dc2626}.badge{display:inline-block;padding:3px 10px;border-radius:999px;font-size:.78rem}.b-ok{background:rgba(16,185,129,.15);color:var(--ok)}.b-warn{background:rgba(245,158,11,.15);color:var(--warn)}.b-bad{background:rgba(239,68,68,.15);color:var(--bad)}.b-mut{background:rgba(148,163,184,.15);color:var(--mut)}.dot{position:relative}.dot[data-count]:not([data-count=""]):after{content:attr(data-count);position:absolute;top:-7px;left:-7px;min-width:19px;height:19px;border-radius:99px;background:var(--bad);color:white;font-size:11px;display:grid;place-items:center}.modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.46);z-index:99;align-items:center;justify-content:center;padding:18px}.modal.show{display:flex}.modal-box{max-width:760px;width:100%;max-height:82vh;overflow:auto;background:var(--panel);border:1px solid var(--line);border-radius:24px;padding:20px}.portal-shell{display:grid;grid-template-columns:18rem minmax(0,1fr);gap:1rem;width:100%;min-height:100vh;padding:1rem}.portal-sidebar{position:sticky;top:1rem;align-self:start;height:calc(100vh - 2rem);overflow:auto;border:1px solid var(--line);border-radius:1.45rem;background:rgba(255,255,255,.88);box-shadow:0 1.2rem 3.2rem rgba(15,23,42,.08);padding:1rem}.dark .portal-sidebar{background:rgba(16,28,46,.92);box-shadow:0 1.2rem 3.2rem rgba(0,0,0,.22)}.portal-brand{display:flex;align-items:center;gap:.75rem;padding:.35rem .2rem 1rem;border-bottom:1px solid var(--line);margin-bottom:1rem}.portal-brand-mark{width:3rem;height:3rem;border-radius:1.15rem;display:grid;place-items:center;background:conic-gradient(from 180deg,var(--acc),var(--ok),var(--vio),var(--acc));color:#fff;font-size:1.35rem;box-shadow:0 .9rem 1.8rem rgba(14,165,233,.22)}.portal-brand b{display:block;color:var(--txt);font-size:1.05rem}.portal-brand span{display:block;color:var(--mut);font-size:.78rem}.portal-side-section{margin-bottom:1rem}.portal-side-title{display:flex;align-items:center;gap:.45rem;color:var(--mut);font-size:.76rem;font-weight:1000;margin:0 0 .5rem;padding:0 .35rem}.portal-side-link{display:flex;align-items:center;gap:.55rem;min-height:2.7rem;border:1px solid transparent;border-radius:1rem;padding:.55rem .75rem;color:var(--txt);font-weight:900;margin:.22rem 0;transition:.18s ease}.portal-side-link:hover,.portal-side-link.is-active{background:rgba(14,165,233,.10);border-color:rgba(14,165,233,.22);color:var(--acc);transform:translateX(-.08rem)}.portal-side-link i{font-style:normal;width:1.6rem;height:1.6rem;display:grid;place-items:center;border-radius:.65rem;background:var(--panel2)}.portal-sidebar-footer{border-top:1px solid var(--line);padding-top:1rem;margin-top:1rem;color:var(--mut);font-size:.8rem}.portal-sidebar-footer form button{width:100%;margin-top:.65rem}.portal-content{min-width:0}.portal-topbar{position:sticky;top:1rem;z-index:30;display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;padding:.85rem;border:1px solid var(--line);border-radius:1.35rem;background:rgba(255,255,255,.84);backdrop-filter:blur(16px);box-shadow:0 1rem 2.5rem rgba(15,23,42,.07)}.dark .portal-topbar{background:rgba(16,28,46,.88);box-shadow:0 1rem 2.5rem rgba(0,0,0,.18)}.portal-page-title{display:flex;align-items:center;gap:.65rem;font-weight:1000;color:var(--txt)}.portal-page-title span{width:2.35rem;height:2.35rem;border-radius:.9rem;display:grid;place-items:center;background:linear-gradient(135deg,var(--acc2),var(--vio));color:#fff}.portal-tools{display:flex;gap:.55rem;align-items:center;flex-wrap:wrap}.icon-btn,.portal-topbar button,.portal-sidebar button{background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:13px;padding:9px 12px;font-family:inherit;cursor:pointer}.clock{font-weight:900;color:#fff;font-size:.86rem;white-space:nowrap;padding:9px 13px;border-radius:14px;background:linear-gradient(135deg,var(--clock1),var(--clock2));box-shadow:0 10px 24px rgba(14,165,233,.18)}.searchbox{position:relative;min-width:300px}.searchbox input{padding-right:38px;background:#fff}.dark .searchbox input{background:var(--panel2)}.searchbox:before{content:'🔎';position:absolute;right:12px;top:10px}.search-results,.drop-panel{display:none;position:absolute;top:46px;left:0;right:0;background:var(--panel);border:1px solid var(--line);border-radius:16px;box-shadow:0 20px 60px rgba(15,23,42,.18);overflow:hidden;z-index:60}.search-results a,.drop-panel a,.drop-row{display:block;padding:11px 13px;border-bottom:1px solid rgba(128,128,128,.14);color:var(--txt)}.search-results small,.drop-panel small{color:var(--mut);display:block}.drop-wrap{position:relative}.drop-panel{width:360px;right:auto}.drop-panel.show{display:block}.portal-main{width:100%;max-width:none}.portal-embed-content{width:100%;min-height:100vh;padding:0;background:var(--bg)}body.embed-mode{background:var(--bg)!important}.portal-guest-shell{min-height:100vh;display:grid;place-items:center;padding:1rem}.portal-guest-card{width:min(100%,34rem);border:1px solid var(--line);border-radius:1.5rem;background:var(--panel);box-shadow:0 1.4rem 3.5rem rgba(15,23,42,.10);padding:1.2rem}.file-picker{display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:var(--panel2);border:1px dashed var(--line);border-radius:14px;padding:8px;margin-top:6px}.file-picker input[type=file]{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}.file-picker-btn{background:linear-gradient(135deg,var(--acc2),var(--acc));color:#fff;border-radius:12px;padding:9px 13px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:6px}.file-picker-name{color:var(--mut);font-size:.84rem;overflow-wrap:anywhere}.file-picker-hint{color:var(--mut);font-size:.75rem;flex-basis:100%}
        
        /* ---------- تغییرات ریسپانسیو و نوار پایین ---------- */
        .sidebar-overlay { display: none; }
        .mobile-menu-btn { display: none; }
        .portal-bottom-nav { display: none; }

        @media(max-width: 980px) {
            .portal-shell{grid-template-columns:1fr;padding:.75rem}
            /* سایدبار کشویی موبایل */
            .portal-sidebar {
                position: fixed !important; top: 0 !important; right: -320px !important; bottom: 0 !important;
                width: 280px !important; height: 100vh !important; z-index: 1001 !important;
                margin: 0 !important; border-radius: 1.5rem 0 0 1.5rem !important; transition: right 0.3s ease !important;
            }
            body.sidebar-open .portal-sidebar { right: 0 !important; }
            /* بکگراند تاریک پشت منو */
            .sidebar-overlay {
                position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1000;
                display: none; backdrop-filter: blur(2px);
            }
            body.sidebar-open .sidebar-overlay { display: block; }
            /* دکمه همبرگری */
            .mobile-menu-btn { 
                display: inline-flex !important; align-items: center; justify-content: center; 
                background: transparent !important; border: none !important; font-size: 1.6rem; 
                color: var(--txt); cursor: pointer; padding: 0 8px; box-shadow: none !important; 
            }
        }

        @media(max-width: 640px) {
            body { padding-bottom: 80px !important; } /* فضای نوار پایین */
            
            /* هدر موبایل تراز شده */
            .portal-topbar { flex-direction: row !important; flex-wrap: nowrap !important; padding: 12px 15px !important; align-items: center !important; justify-content: space-between !important; }
            .portal-page-title b { display: none !important; } /* مخفی کردن متن طولانی هدر */
            .searchbox, .clock { display: none !important; } /* مخفی کردن سرچ و ساعت */
            .portal-tools { display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; width: auto !important; gap: 10px !important; justify-content: flex-end !important; align-items: center !important; }
            
            /* پاپ آپ اعلان و تیکت (وسط چین شدن) */
            .drop-panel { position: fixed !important; top: 75px !important; left: 15px !important; right: 15px !important; width: auto !important; box-shadow: 0 15px 40px rgba(0,0,0,0.2) !important; max-height: 75vh !important; overflow-y: auto !important; }
            
            /* تداخل دکمه ربات */
            #clubAssistantFloat { bottom: 85px !important; left: 15px !important; }
            #clubAssistantPanel { bottom: 150px !important; left: 10px !important; right: 10px !important; width: auto !important; }

            /* نوار پایین */
            .portal-bottom-nav {
                display: block !important; position: fixed; bottom: 0; left: 0; right: 0; height: 65px;
                background: var(--panel); border-top: 1px solid var(--line); z-index: 999;
                box-shadow: 0 -2px 15px rgba(0,0,0,0.05); padding-bottom: env(safe-area-inset-bottom);
            }
            .portal-bottom-nav-inner { display: flex; justify-content: space-around; align-items: center; height: 100%; max-width: 600px; margin: 0 auto; }
            .portal-nav-item { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: var(--mut); text-decoration: none; font-size: 0.7rem; font-weight: 800; flex: 1; transition: 0.2s; }
            .portal-nav-item.is-active { color: var(--acc); }
            .portal-nav-item i { font-style: normal; font-size: 1.35rem; }
            .portal-nav-item.is-active i { transform: translateY(-2px); }

            /* جداول */
            .table-wrap{overflow:visible!important}
            .table-wrap table{min-width:0!important;width:100%;border-collapse:separate;border-spacing:0}
            .table-wrap table thead{display:none}
            .table-wrap table tbody,.table-wrap table tr,.table-wrap table td{display:block;width:100%}
            .table-wrap table tr{background:var(--panel2);border:1px solid var(--line);border-radius:14px;padding:8px;margin-bottom:10px}
            .table-wrap table td{border-bottom:none;padding:5px 0;display:flex;justify-content:space-between;align-items:center}
            .table-wrap table td::before{content:attr(data-label);font-weight:900;color:var(--mut);font-size:.78rem;margin-left:10px}
        }
    </style>
    @php
        $clubMemberId = session('club_member_id');
        $isEmbed = request()->boolean('embed');
        $clubUnread = 0;
        $clubOpenTickets = 0;
        $clubNotes = collect();
        $clubTickets = collect();
        if ($clubMemberId) {
            try {
                $mem = \Modules\Loyalty\Entities\LoyaltyMember::find($clubMemberId);
                if ($mem) {
                    $clubUnread = \Modules\Loyalty\Entities\CustomerPortalNotification::where('customer_id', $mem->customer_id)->whereNull('read_at')->count();
                    $clubNotes = \Modules\Loyalty\Entities\CustomerPortalNotification::where('customer_id', $mem->customer_id)->latest('id')->limit(3)->get();
                    $clubOpenTickets = \Modules\Core\Entities\Ticket::where('customer_id', $mem->customer_id)->where('status', 'open')->count();
                    $clubTickets = \Modules\Core\Entities\Ticket::where('customer_id', $mem->customer_id)->latest('updated_at')->limit(3)->get();
                }
            } catch (\Exception $e) {}
        }
    @endphp
</head>
<body class="{{ $isEmbed ? 'embed-mode' : '' }}">

<!-- بک‌گراند تیره برای زمان باز بودن منوی همبرگری -->
<div class="sidebar-overlay" onclick="document.body.classList.remove('sidebar-open')"></div>

@if($clubMemberId && ! $isEmbed)
<div class="portal-shell">
    <aside class="portal-sidebar">
        <div class="portal-brand">
            <div class="portal-brand-mark">✦</div>
            <div>
                <b>باشگاه مشتریان</b>
                <span>پنل اختصاصی وفاداری</span>
            </div>
        </div>

        <nav>
            <section class="portal-side-section">
                <a class="portal-side-link {{ request()->routeIs('club.dashboard') ? 'is-active' : '' }}" href="{{ route('club.dashboard') }}"><i>🏠</i>پیشخوان</a>
                <a class="portal-side-link {{ request()->routeIs('club.profile') ? 'is-active' : '' }}" href="{{ route('club.profile') }}"><i>👤</i>پروفایل</a>
                <a class="portal-side-link {{ request()->routeIs('club.card') ? 'is-active' : '' }}" href="{{ route('club.card') }}"><i>💳</i>کارت عضویت</a>
                <a class="portal-side-link {{ request()->routeIs('club.orders') ? 'is-active' : '' }}" href="{{ route('club.orders') }}"><i>🛍️</i>خریدهای من</a>
                <a class="portal-side-link {{ request()->routeIs('club.journey') ? 'is-active' : '' }}" href="{{ route('club.journey') }}"><i>🧭</i>سفر من</a>
            </section>

            <section class="portal-side-section">
                <h3 class="portal-side-title">گیمیفیکیشن و پاداش</h3>
                <a class="portal-side-link {{ request()->routeIs('club.wheel') ? 'is-active' : '' }}" href="{{ route('club.wheel') }}"><i>🎡</i>گردونه شانس</a>
                <a class="portal-side-link {{ request()->routeIs('club.missions') ? 'is-active' : '' }}" href="{{ route('club.missions') }}"><i>🎯</i>مأموریت‌ها</a>
                <a class="portal-side-link {{ request()->routeIs('club.coupons') ? 'is-active' : '' }}" href="{{ route('club.coupons') }}"><i>٪</i>کدهای تخفیف</a>
                <a class="portal-side-link {{ request()->routeIs('club.rewards') ? 'is-active' : '' }}" href="{{ route('club.rewards') }}"><i>🎁</i>جایزه‌های من</a>
                <a class="portal-side-link {{ request()->routeIs('club.badges') ? 'is-active' : '' }}" href="{{ route('club.badges') }}"><i>🏅</i>نشان‌ها</a>
            </section>

            <section class="portal-side-section">
                <h3 class="portal-side-title">لیگ و رشد</h3>
                <a class="portal-side-link {{ request()->routeIs('club.referrals') ? 'is-active' : '' }}" href="{{ route('club.referrals') }}"><i>🤝</i>معرفی دوستان</a>
                <a class="portal-side-link {{ request()->routeIs('club.campaign.league') ? 'is-active' : '' }}" href="{{ route('club.campaign.league') }}"><i>🏆</i>لیگ دعوت</a>
                <a class="portal-side-link {{ request()->routeIs('club.leaderboard') ? 'is-active' : '' }}" href="{{ route('club.leaderboard') }}"><i>📈</i>برترین‌ها</a>
            </section>

            <section class="portal-side-section">
                <h3 class="portal-side-title">پشتیبانی</h3>
                <a class="portal-side-link {{ request()->routeIs('club.kb*') ? 'is-active' : '' }}" href="{{ route('club.kb') }}"><i>📚</i>راهنما</a>
                <a class="portal-side-link {{ request()->routeIs('club.assistant') ? 'is-active' : '' }}" href="{{ route('club.assistant') }}"><i>🤖</i>دستیار</a>
                <a class="portal-side-link {{ request()->routeIs('club.tickets*') ? 'is-active' : '' }}" href="{{ route('club.tickets') }}"><i>🎫</i>درخواست‌ها @if($clubOpenTickets) (@fa($clubOpenTickets)) @endif</a>
            </section>
        </nav>

        <div class="portal-sidebar-footer">
            <div>وضعیت حساب: فعال</div>
            <form method="post" action="{{ route('club.logout') }}">@csrf<button class="btn btn-ghost">خروج از باشگاه</button></form>
        </div>
    </aside>

    <main class="portal-content">
        <div class="portal-topbar">
            <div class="portal-page-title">
                <!-- دکمه همبرگری -->
                <button class="icon-btn mobile-menu-btn" onclick="document.body.classList.add('sidebar-open')">☰</button>
                <span>✦</span>
                <b>@yield('title', 'باشگاه مشتریان')</b>
            </div>
            <div class="portal-tools">
                <div class="searchbox"><input id="clubSearch" placeholder="جستجو در باشگاه، تیکت، تراکنش..."><div class="search-results" id="clubSearchResults"></div></div>
                <div class="drop-wrap"><button class="icon-btn dot" data-count="{{ $clubUnread ?: '' }}" onclick="toggleDrop('noteDrop')" title="اعلان‌ها">🔔</button><div class="drop-panel" id="noteDrop"><b class="drop-row">اعلان‌های اخیر</b>@forelse($clubNotes as $n)<a href="{{ route('club.notifications') }}"><b>{{ $n->title }}</b><small>{{ $n->body }}</small></a>@empty<div class="drop-row muted">اعلان جدیدی ندارید.</div>@endforelse<a href="{{ route('club.notifications') }}">مشاهده همه اعلان‌ها ←</a></div></div>
                <div class="drop-wrap"><button class="icon-btn dot" data-count="{{ $clubOpenTickets ?: '' }}" onclick="toggleDrop('ticketDrop')" title="درخواست‌ها">🎫</button><div class="drop-panel" id="ticketDrop"><b class="drop-row">درخواست‌های اخیر</b>@forelse($clubTickets as $t)<a href="{{ route('club.tickets.show',$t) }}"><b>{{ $t->subject }}</b><small>وضعیت: {{ $t->status }} · @jdatetime($t->updated_at)</small></a>@empty<div class="drop-row muted">درخواستی ندارید.</div>@endforelse<a href="{{ route('club.tickets') }}">مشاهده و ثبت درخواست ←</a></div></div>
                <button class="icon-btn theme-btn" onclick="toggleTheme()" title="روشن/تیره">◐</button>
                <div class="clock" id="liveClock">—</div>
            </div>
        </div>

        <div class="portal-main">
            @if(session('status'))<div class="card alert">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="card err">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </main>

    {{-- نوار پیمایش پایین فقط برای موبایل --}}
    <nav class="portal-bottom-nav">
        <div class="portal-bottom-nav-inner">
            <a href="{{ route('club.dashboard') }}" class="portal-nav-item {{ request()->routeIs('club.dashboard') ? 'is-active' : '' }}">
                <i>🏠</i><span>پیشخوان</span>
            </a>
            <a href="{{ route('club.wheel') }}" class="portal-nav-item {{ request()->routeIs('club.wheel') ? 'is-active' : '' }}">
                <i>🎡</i><span>گردونه</span>
            </a>
            <a href="{{ route('club.missions') }}" class="portal-nav-item {{ request()->routeIs('club.missions') ? 'is-active' : '' }}">
                <i>🎯</i><span>مأموریت</span>
            </a>
            <a href="{{ route('club.coupons') }}" class="portal-nav-item {{ request()->routeIs('club.coupons') ? 'is-active' : '' }}">
                <i>٪</i><span>تخفیف</span>
            </a>
            <a href="{{ route('club.profile') }}" class="portal-nav-item {{ request()->routeIs('club.profile') ? 'is-active' : '' }}">
                <i>👤</i><span>پروفایل</span>
            </a>
        </div>
    </nav>
</div>
@elseif($clubMemberId && $isEmbed)
<div class="portal-embed-content">
    @if(session('status'))<div class="card alert">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="card err">{{ $errors->first() }}</div>@endif
    @yield('content')
</div>
@else
<div class="portal-guest-shell">
    <div class="portal-guest-card">
        <div class="portal-brand" style="border-bottom:0;margin-bottom:.5rem"><div class="portal-brand-mark">✦</div><div><b>باشگاه مشتریان</b><span>ورود یا ثبت‌نام در باشگاه</span></div></div>
        @if(session('status'))<div class="card alert">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="card err">{{ $errors->first() }}</div>@endif
        @yield('content')
    </div>
</div>
@endif

@if(! $isEmbed)
<button id="clubAssistantFloat" onclick="document.body.classList.toggle('club-assistant-open')" title="دستیار مشتری‌یار">🤖</button>
<div id="clubAssistantPanel"><div class="assist-head"><b>دستیار مشتری‌یار</b><button onclick="document.body.classList.remove('club-assistant-open')">×</button></div><iframe src="{{ route('club.assistant', ['embed'=>1]) }}"></iframe></div>
<style>#clubAssistantFloat{position:fixed;left:22px;bottom:22px;z-index:9998;width:56px;height:56px;border:0;border-radius:20px;background:linear-gradient(135deg,var(--acc2),var(--ok));box-shadow:0 15px 40px rgba(14,165,233,.25);font-size:24px;cursor:pointer}#clubAssistantPanel{display:none;position:fixed;left:22px;bottom:88px;z-index:9998;width:min(480px,calc(100vw - 34px));height:620px;background:var(--panel);border:1px solid var(--line);border-radius:22px;overflow:hidden;box-shadow:0 25px 80px rgba(15,23,42,.25)}body.club-assistant-open #clubAssistantPanel{display:block}#clubAssistantPanel .assist-head{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-bottom:1px solid var(--line);background:var(--panel2)}#clubAssistantPanel .assist-head button{background:transparent;border:0;color:var(--txt);font-size:22px;cursor:pointer}#clubAssistantPanel iframe{width:100%;height:calc(100% - 48px);border:0}</style>
<div class="modal" id="ticketModal"><div class="modal-box"><div style="display:flex;justify-content:space-between;gap:10px"><h3 style="margin-top:0">درخواست‌های شما</h3><button class="btn btn-ghost" onclick="closeTicketPopup()">بستن</button></div><p class="muted">برای مشاهده و پاسخ به درخواست‌ها وارد بخش پشتیبانی شوید.</p><a class="btn" href="{{ $clubMemberId ? route('club.tickets') : '#' }}">مشاهده درخواست‌ها</a></div></div>
@endif
    <link rel="stylesheet" href="{{ asset('css/ui-readability-fix.css') }}">
<script src="{{ asset('js/jdatepicker.js') }}"></script>
<script>
(function(){
function setTheme(){var theme=localStorage.getItem('theme') || 'light';document.body.classList.toggle('dark',theme==='dark');document.body.classList.toggle('light',theme!=='dark')}
window.toggleTheme=function(){localStorage.setItem('theme',document.body.classList.contains('dark')?'light':'dark');setTheme()}; setTheme();
function tick(){var el=document.getElementById('liveClock'); if(!el) return; const d=new Date(); const date=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{day:'numeric',month:'long',year:'numeric'}).format(d); const time=new Intl.DateTimeFormat('fa-IR',{hour:'2-digit',minute:'2-digit',hour12:false}).format(d); el.textContent=date+'، ساعت '+time;} tick(); setInterval(tick,1000);
window.toggleDrop=function(id){document.querySelectorAll('.drop-panel').forEach(x=>{if(x.id!==id)x.classList.remove('show')});var el=document.getElementById(id); if(el) el.classList.toggle('show')};
document.addEventListener('click',e=>{if(!e.target.closest('.drop-wrap'))document.querySelectorAll('.drop-panel').forEach(x=>x.classList.remove('show'))});
window.openTicketPopup=function(){var el=document.getElementById('ticketModal'); if(el) el.classList.add('show')};
window.closeTicketPopup=function(){var el=document.getElementById('ticketModal'); if(el) el.classList.remove('show')};
const searchInput=document.getElementById('clubSearch'), searchResults=document.getElementById('clubSearchResults'); let searchTimer=null;
if(searchInput){searchInput.addEventListener('input',()=>{clearTimeout(searchTimer); let q=searchInput.value.trim(); if(q.length<2){searchResults.style.display='none';return;} searchTimer=setTimeout(()=>fetch('{{ route('club.search') }}?q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{searchResults.innerHTML=(d.items||[]).map(i=>`<a href=\"${i.url}\"><b>${i.title}</b><small>${i.type} · ${i.desc||''}</small></a>`).join('')||'<a>نتیجه‌ای یافت نشد</a>'; searchResults.style.display='block';}),250);});}
document.addEventListener('click',e=>{if(searchResults && !e.target.closest('.searchbox')) searchResults.style.display='none'});
if('serviceWorker' in navigator){navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(()=>{});}
function applyPortalTableLabels(){document.querySelectorAll('.table-wrap table').forEach(function(t){var heads=[...t.querySelectorAll('thead th')].map(th=>th.textContent.trim());t.querySelectorAll('tbody tr').forEach(function(tr){[...tr.children].forEach(function(td,i){if(!td.getAttribute('data-label'))td.setAttribute('data-label',heads[i]||'');});});});}
document.addEventListener('DOMContentLoaded',applyPortalTableLabels); applyPortalTableLabels();
})();
function enhanceFileInputs(){document.querySelectorAll('input[type=\"file\"]:not([data-file-enhanced])').forEach(function(input){input.setAttribute('data-file-enhanced','1');if(!input.id)input.id='file_'+Math.random().toString(36).slice(2);var wrap=document.createElement('div');wrap.className='file-picker';var label=document.createElement('label');label.className='file-picker-btn';label.setAttribute('for',input.id);label.innerHTML='📎 انتخاب فایل';var name=document.createElement('span');name.className='file-picker-name';name.textContent='فایلی انتخاب نشده است';var hint=document.createElement('div');hint.className='file-picker-hint';hint.textContent=input.dataset.hint || 'فرمت‌های مجاز: تصویر، PDF، Word، Excel، ZIP و متن';input.parentNode.insertBefore(wrap,input);wrap.appendChild(input);wrap.appendChild(label);wrap.appendChild(name);wrap.appendChild(hint);input.addEventListener('change',function(){name.textContent=input.files&&input.files[0]?input.files[0].name:'فایلی انتخاب نشده است';});});}
document.addEventListener('DOMContentLoaded',enhanceFileInputs); enhanceFileInputs();
</script>
</body>
</html>