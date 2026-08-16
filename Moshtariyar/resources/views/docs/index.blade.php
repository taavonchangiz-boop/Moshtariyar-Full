<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>مستندات مشتری‌یار — راهنمای کامل و کاربردی تمام بخش‌ها</title>
<meta name="description" content="مستندات جامع مشتری‌یار: راهنمای دقیق تمام بخش‌ها از مشتری و سفارش و صندوق هوشمند و مرکز بازگشت و حفظ و درآمد بازگشتی ماهانه یکتا و آرشیو گزارش هفتگی و گزارشات نه بخشی و ارزش طول عمر و بخش‌بندی خودکار و سفر مشتری و باشگاه وفاداری با سطوح و قوانین و کیف پول و پاداش و ماموریت و گردونه و جدول امتیازی و معرفی دوستان و پیامک تایید و مرکز پیام و اتصال فروشگاه">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<style>
:root{--bg:#f8fafc;--panel:#fff;--panel2:#f1f5f9;--line:#e2e8f0;--txt:#0f172a;--mut:#64748b;--acc:#10b981;--acc2:#0ea5e9;--top-h:58px;--side-w:320px}
*{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:calc(var(--top-h) + 14px);-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--txt);font-family:var(--font-fa,Tahoma, sans-serif);line-height:1.95;font-size:14.5px;overflow-x:hidden;-webkit-font-smoothing:antialiased}
a{color:var(--acc2);text-decoration:none}
img{max-width:100%;height:auto}
.topbar{position:sticky;top:0;z-index:40;backdrop-filter:blur(16px) saturate(1.2);background:rgba(255,255,255,.92);border-bottom:1px solid var(--line);padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:var(--top-h)}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000;color:var(--txt);text-decoration:none;flex:0 0 auto}
.brand-mark{width:34px;height:34px;border-radius:10px;background:var(--acc);color:#fff;display:grid;place-items:center;font-weight:1000;font-size:16px}
.search-wrap{flex:1;max-width:480px;position:relative;min-width:0}
.search-wrap input{width:100%;padding:10px 40px 10px 14px;border:1px solid var(--line);border-radius:999px;background:#fff;color:var(--txt);font-family:inherit;font-size:.9rem}
.search-wrap input:focus{outline:none;border-color:var(--acc2);box-shadow:0 0 0 3px rgba(14,165,233,.14)}
.search-wrap::before{content:'🔍';position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none}
.actions{display:flex;gap:6px;align-items:center;flex:0 0 auto}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--txt);font-weight:800;font-size:.84rem;cursor:pointer;transition:.18s;white-space:nowrap;min-height:38px;text-decoration:none}
.btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(15,23,42,.06)}
.btn-primary{background:var(--acc);color:#fff;border-color:color-mix(in srgb, var(--acc) 84%, black 16%)}
.btn-icon{display:none;width:40px;height:40px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--txt);font-size:18px;cursor:pointer;flex:0 0 40px;align-items:center;justify-content:center}
.layout{display:grid;grid-template-columns:var(--side-w) minmax(0,1fr);min-height:calc(100vh - var(--top-h));gap:0}
.sidebar{border-right:1px solid var(--line);background:#fff;padding:12px 10px;position:sticky;top:var(--top-h);height:calc(100vh - var(--top-h));overflow-y:auto;-webkit-overflow-scrolling:touch}
.main{padding:22px 18px;max-width:920px;margin:0 auto;width:100%;min-width:0}
.group{margin-bottom:18px}
.group-title{display:block;font-size:.72rem;color:var(--mut);font-weight:900;letter-spacing:.04em;margin:14px 8px 6px}
.nav-link{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:10px;color:var(--txt);font-weight:700;font-size:.86rem;transition:.15s;text-decoration:none;border:1px solid transparent;line-height:1.55;cursor:pointer}
.nav-link .icon{width:20px;text-align:center;flex:0 0 20px;font-size:14px}
.nav-link:hover{background:#f1f5f9;border-color:var(--line);text-decoration:none}
.nav-link.active{background:rgba(16,185,129,.10);color:#065f46;border-color:rgba(16,185,129,.22)}
.hero{background:linear-gradient(135deg, rgba(16,185,129,.10), rgba(14,165,233,.08), #fff);border:1px solid var(--line);border-radius:18px;padding:20px 16px;margin-bottom:18px;overflow:hidden}
.hero h1{margin:8px 0 10px;font-size:clamp(1.35rem, 4vw, 2.05rem);line-height:1.35;font-weight:1000;overflow-wrap:anywhere}
.hero p{margin:0;color:var(--mut);line-height:2.1;max-width:100%;font-size:clamp(.88rem, 2vw, .93rem)}
.badge{display:inline-flex;padding:4px 10px;border-radius:999px;background:#f1f5f9;border:1px solid var(--line);font-size:.70rem;font-weight:800;color:var(--mut);white-space:nowrap}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px 14px;box-shadow:0 8px 24px rgba(15,23,42,.05);margin-bottom:18px;scroll-margin-top:calc(var(--top-h) + 16px);overflow:hidden;min-width:0}
.card h2{margin:0 0 10px;font-size:clamp(1.05rem, 2.5vw, 1.22rem);font-weight:1000;line-height:1.5;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.card h3{margin:16px 0 8px;font-size:clamp(.95rem, 2vw, 1.02rem);font-weight:900}
.card p{margin:0 0 10px;color:var(--txt);font-size:clamp(.86rem, 1.8vw, .92rem)}
.card ul, .card ol{margin:8px 0 10px;padding:0 20px 0 0}
.card li{margin:6px 0;font-size:clamp(.84rem, 1.8vw, .9rem)}
.card img{width:100%;height:auto;border-radius:12px;border:1px solid var(--line);margin:12px 0;display:block;background:#fff;object-fit:cover}
.alert{padding:12px 14px;border-radius:12px;border:1px solid;margin:14px 0;line-height:1.9;font-size:.88rem;overflow-wrap:anywhere}
.alert.info{background:rgba(14,165,233,.07);border-color:rgba(14,165,233,.20);color:#075985}
.alert.ok{background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.20);color:#065f46}
.alert.warn{background:rgba(245,158,11,.09);border-color:rgba(245,158,11,.22);color:#92400e}
.grid2{display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:12px}
.table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:12px;margin:12px 0;-webkit-overflow-scrolling:touch}
.table{width:100%;border-collapse:collapse;font-size:.84rem;min-width:540px}
.table th{text-align:right;padding:9px 10px;background:#f1f5f9;color:var(--mut);font-weight:900;border-bottom:1px solid var(--line);white-space:nowrap;font-size:.78rem}
.table td{padding:9px 10px;border-bottom:1px solid var(--line);vertical-align:top;font-size:.84rem}
.kbd{display:inline-block;padding:2px 7px;border:1px solid var(--line);border-bottom-width:2px;border-radius:7px;background:#fff;font-family:monospace;font-size:.78rem;direction:ltr;word-break:break-all}
.footer{text-align:center;color:var(--mut);padding:20px;border-top:1px solid var(--line);margin-top:20px;font-size:.82rem}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(4px);z-index:45;opacity:0;transition:.22s}
.sidebar-overlay.show{display:block;opacity:1}
@@media(max-width:900px){
  :root{--top-h:56px}
  .topbar{padding:8px 10px;gap:8px;flex-wrap:wrap}
  .brand{font-size:.92rem}
  .search-wrap{order:3;max-width:100%;flex:1 1 100%;margin-top:2px}
  .actions .btn:not(.btn-icon):not(.btn-primary){display:none}
  .btn-icon{display:inline-flex;align-items:center;justify-content:center}
  .layout{grid-template-columns:1fr}
  .sidebar{
    position:fixed; top:var(--top-h); right:-100%; left:auto; bottom:0; width:min(320px, 86vw);
    height:calc(100vh - var(--top-h)); z-index:46; border-right:1px solid var(--line); border-left:1px solid var(--line);
    border-radius:18px 0 0 18px; box-shadow:0 18px 56px rgba(15,23,42,.18);
    transition:right .28s cubic-bezier(.16,1,.3,1); padding:14px 10px 80px;
  }
  .sidebar.open{right:0}
  .main{padding:14px 10px; max-width:100%}
  .hero{padding:16px 12px; border-radius:16px}
  .card{padding:14px 10px; border-radius:14px}
  .grid2{grid-template-columns:1fr; gap:10px}
  .table{min-width:0; font-size:.82rem}
  .table-wrap{border-radius:10px}
  .table thead{display:none}
  .table tbody, .table tr, .table td{display:block; width:100%}
  .table tr{background:#f8fafc; border:1px solid var(--line); border-radius:12px; padding:8px; margin-bottom:10px}
  .table td{border:0 !important; display:flex; justify-content:space-between; gap:8px; padding:8px 4px !important; text-align:left !important; direction:rtl}
  .table td::before{content:attr(data-label); font-weight:900; color:var(--mut); text-align:right; min-width:90px; max-width:45%; font-size:.78rem; flex:0 0 auto}
}
@@media(max-width:480px){
  .topbar{padding:8px}
  .hero h1{font-size:1.28rem}
  .card h2{font-size:1.02rem}
  .btn{padding:8px 10px; font-size:.82rem; min-height:38px}
}
</style>
</head>
<body>

<header class="topbar">
  <button class="btn-icon" id="mobileMenuBtn" aria-label="منو" onclick="toggleSidebar()">☰</button>
  <a class="brand" href="/"><span class="brand-mark">✦</span><span>مستندات مشتری‌یار</span></a>
  <div class="search-wrap"><input id="docSearch" type="text" placeholder="جستجوی سریع در مستندات... مثلا: مشتری، سفارش، صندوق، بازگشت، باشگاه، پیامک"></div>
  <div class="actions">
    <a class="btn" href="/">خانه</a>
    <a class="btn" href="/amoozesh">آموزش</a>
    <a class="btn btn-primary" href="/app">پنل</a>
  </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="group"><span class="group-title">شروع سریع</span>
      <a class="nav-link active" href="#quickstart"><span class="icon">🚀</span> شروع ده دقیقه‌ای</a>
      <a class="nav-link" href="#concepts"><span class="icon">📖</span> اصطلاحات به زبان ساده</a>
    </div>
    <div class="group"><span class="group-title">مدیریت مشتری و فروش</span>
      <a class="nav-link" href="#customers"><span class="icon">👥</span> مشتریان — پرونده کامل</a>
      <a class="nav-link" href="#customers-add"><span class="icon">➕</span> افزودن مشتری</a>
      <a class="nav-link" href="#orders"><span class="icon">🛒</span> سفارش‌ها و فاکتور</a>
      <a class="nav-link" href="#orders-add"><span class="icon">➕</span> ثبت سفارش دستی</a>
      <a class="nav-link" href="#pipeline"><span class="icon">📊</span> قیف فروش و سرنخ</a>
      <a class="nav-link" href="#activities"><span class="icon">⏰</span> یادآوری و فعالیت</a>
      <a class="nav-link" href="#products"><span class="icon">📦</span> محصولات و دسته‌بندی</a>
      <a class="nav-link" href="#warehouses"><span class="icon">🏬</span> انبار و موجودی</a>
      <a class="nav-link" href="#pos"><span class="icon">💳</span> صندوق هوشمند و هشدار ریزش</a>
      <a class="nav-link" href="#pos-cashier"><span class="icon">💵</span> گزارش صندوق و شیفت</a>
    </div>
    <div class="group"><span class="group-title">بازگشت و نگهداری — هسته اصلی</span>
      <a class="nav-link" href="#retention"><span class="icon">🛟</span> مرکز بازگشت و حفظ</a>
      <a class="nav-link" href="#recovery-revenue"><span class="icon">💰</span> درآمد بازگشتی ماهانه یکتا</a>
      <a class="nav-link" href="#recovery-history"><span class="icon">🗂️</span> آرشیو گزارش هفتگی</a>
      <a class="nav-link" href="#recovery-widget"><span class="icon">🧩</span> ویجت داشبورد همیشه روشن</a>
      <a class="nav-link" href="#automation-recovery"><span class="icon">⚙️</span> اتوماسیون امتیاز دو برابر</a>
    </div>
    <div class="group"><span class="group-title">تحلیل و گزارش</span>
      <a class="nav-link" href="#dashboard"><span class="icon">📊</span> داشبورد فرماندهی</a>
      <a class="nav-link" href="#reports"><span class="icon">📈</span> مرکز گزارشات نه بخشی</a>
      <a class="nav-link" href="#clv"><span class="icon">💎</span> ارزش طول عمر و پیش‌بینی ریزش</a>
      <a class="nav-link" href="#rfm"><span class="icon">👥</span> رفتار خرید و بخش‌بندی خودکار</a>
      <a class="nav-link" href="#segments"><span class="icon">🎯</span> بخش‌بندی — اجرای خودکار</a>
      <a class="nav-link" href="#journeys"><span class="icon">🗺️</span> سفر مشتری</a>
    </div>
    <div class="group"><span class="group-title">باشگاه مشتریان</span>
      <a class="nav-link" href="#loyalty-overview"><span class="icon">🎁</span> باشگاه چیست</a>
      <a class="nav-link" href="#loyalty-levels"><span class="icon">🏆</span> سطوح — برنزی، نقره‌ای، طلایی</a>
      <a class="nav-link" href="#loyalty-rules"><span class="icon">📜</span> قوانین امتیاز</a>
      <a class="nav-link" href="#wallet"><span class="icon">👛</span> کیف پول</a>
      <a class="nav-link" href="#rewards"><span class="icon">🎟️</span> پاداش و کوپن</a>
      <a class="nav-link" href="#missions"><span class="icon">🎯</span> ماموریت</a>
      <a class="nav-link" href="#wheel"><span class="icon">🎡</span> گردونه شانس</a>
      <a class="nav-link" href="#leaderboard"><span class="icon">🏆</span> جدول امتیازی و لیگ</a>
      <a class="nav-link" href="#referrals"><span class="icon">🤝</span> معرفی دوستان</a>
      <a class="nav-link" href="#club-orders"><span class="icon">🛒</span> سفارش‌های باشگاه</a>
      <a class="nav-link" href="#club-transactions"><span class="icon">💳</span> تراکنش‌ها</a>
      <a class="nav-link" href="#club-auth"><span class="icon">🔐</span> ورود رمز یکبار مصرف شیشه‌ای</a>
    </div>
    <div class="group"><span class="group-title">فروشگاه و مالی</span>
      <a class="nav-link" href="#tax-invoices"><span class="icon">🧾</span> فاکتور رسمی و برندینگ فاکتور</a>
      <a class="nav-link" href="#woocommerce"><span class="icon">🔌</span> فروشگاه اینترنتی و همگام‌سازی</a>
      <a class="nav-link" href="#sync-logs"><span class="icon">🔄</span> لاگ همگام‌سازی</a>
      <a class="nav-link" href="#payments"><span class="icon">💰</span> پرداخت‌ها و درگاه‌ها</a>
    </div>
    <div class="group"><span class="group-title">پیام و پشتیبانی</span>
      <a class="nav-link" href="#messaging"><span class="icon">📨</span> مرکز پیام چندکاناله</a>
      <a class="nav-link" href="#sms"><span class="icon">📱</span> پنل پیامک تایید</a>
      <a class="nav-link" href="#campaigns"><span class="icon">📣</span> کمپین‌ها</a>
      <a class="nav-link" href="#workflows"><span class="icon">⚙️</span> گردش کار</a>
      <a class="nav-link" href="#tickets"><span class="icon">🎫</span> تیکت و پاسخ آماده و گزارش تاخیر</a>
      <a class="nav-link" href="#kb"><span class="icon">📚</span> پایگاه دانش</a>
      <a class="nav-link" href="#assistant"><span class="icon">🤖</span> دستیار هوشمند</a>
    </div>
    <div class="group"><span class="group-title">مدیریت سیستم</span>
      <a class="nav-link" href="#users"><span class="icon">👥</span> کاربران و نقش‌ها</a>
      <a class="nav-link" href="#settings-brand"><span class="icon">🎨</span> تنظیم برند و رنگ و لوگو</a>
      <a class="nav-link" href="#settings-notif"><span class="icon">🔔</span> اعلان و گزارش خودکار</a>
      <a class="nav-link" href="#settings-backup"><span class="icon">💾</span> پشتیبان و امنیت دو مرحله‌ای</a>
      <a class="nav-link" href="#buttons"><span class="icon">🎨</span> ظاهر دکمه‌ها</a>
    </div>
  </aside>

  <main class="main">

    <div class="hero">
      <span class="badge">تمام تاریخ‌ها شمسی • اعداد فارسی • واکنش‌گرا کامل • جستجوی زنده • تصاویر واقعی</span>
      <h1>مستندات کامل مشتری‌یار — هر بخش با توضیح دقیق، مثال و تصویر واقعی</h1>
      <p>این صفحه تمام قسمت‌های برنامه را با مسیر دقیق منو، آدرس صفحه، هدف، فیلدهای فرم، دکمه‌ها، تصویر واقعی صفحه داخلی، نکته کاربردی و مثال قابل فهم برای همه توضیح می‌دهد. برای پیدا کردن سریع بخش، از جستجوی بالا استفاده کن یا از فهرست کناری سمت راست.</p>
    </div>

    <div class="card" id="quickstart"><h2>🚀 شروع ده دقیقه‌ای</h2>
      <p>اگر اولین بار است وارد می‌شوی، همین ده دقیقه کافی است تا سیستم آماده کار شود.</p>
      <div class="grid2"><div><h3>گام ۱ تا ۶ — پایه</h3><ol>
        <li><b>برند و رنگ:</b> تنظیمات → طراحی سایت → لوگو، رنگ اصلی. مسیر <span class="kbd">/app/settings?tab=design</span></li>
        <li><b>کاربران:</b> تنظیمات → کاربران → افزودن کاربر با نقش. <span class="kbd">/app/users</span></li>
        <li><b>پیامک:</b> تنظیمات → پیامک → ارائه‌دهنده پیامک با روش تایید، کلید، شماره فرستنده، شناسه قالب ۹۶۹۹۷۵.</li>
        <li><b>فروشگاه:</b> فروشگاه و مالی → اتصال فروشگاه → آدرس و کلید. <span class="kbd">/app/woocommerce</span></li>
        <li><b>باشگاه:</b> باشگاه مشتریان → سطوح و قوانین. <span class="kbd">/app/loyalty/settings</span></li>
        <li><b>صندوق:</b> محصولات → انبارها → انبار پیش‌فرض → فروش سریع. <span class="kbd">/app/pos</span></li>
      </ol></div>
      <div><h3>گام ۷ تا ۱۲ — رشد</h3><ol start="7">
        <li><b>مشتری آزمایشی:</b> مشتریان → افزودن مشتری با نام و موبایل و منبع.</li>
        <li><b>سفارش آزمایشی:</b> سفارش‌ها → افزودن سفارش → انتخاب مشتری → افزودن محصول → ثبت.</li>
        <li><b>بخش‌بندی خودکار:</b> <span class="kbd">/app/segments/auto</span> — پنج بخش طلایی.</li>
        <li><b>مرکز بازگشت:</b> <span class="kbd">/app/retention</span> — دیدن کم‌فعال‌ها.</li>
        <li><b>صندوق هوشمند:</b> جستجوی مشتری با موبایل → دیدن هشدار حفظ.</li>
        <li><b>آرشیو:</b> <span class="kbd">/app/reports/recovery/history</span> — جستجو شمسی و نمودار روند.</li>
      </ol></div></div>
    </div>

    <div class="card" id="concepts"><h2>📖 اصطلاحات به زبان ساده</h2>
      <div class="table-wrap"><table class="table"><thead><tr><th>اصطلاح</th><th>به زبان ساده چیست؟</th><th>مثال</th></tr></thead>
        <tbody>
          <tr><td data-label="اصطلاح"><b>مشتری</b></td><td data-label="به زبان ساده چیست؟">هر کسی که شماره‌اش را داری — پرونده کامل از خرید و امتیاز و پیام و تیکت</td><td data-label="مثال">احمد رضایی — ۰۹۱۲۱۲۳۴۵۶۷ — سه سفارش</td></tr>
          <tr><td data-label="اصطلاح"><b>ارزش طول عمر</b></td><td data-label="به زبان ساده چیست؟">پیش‌بینی اینکه هر مشتری در آینده چقدر سود می‌دهد و احتمال رفتنش چقدر است</td><td data-label="مثال">میانگین ارزش دوازده ماه آینده ۲،۳۰۰،۰۰۰ تومان — احتمال ریزش ۶۸٪</td></tr>
          <tr><td data-label="اصطلاح"><b>بازگشت</b></td><td data-label="به زبان ساده چیست؟">خرید بعد از چهل و پنج روز دوری — سامانه خودکار می‌فهمد بازگشته</td><td data-label="مثال">آخرین خرید ۶۸ روز پیش → خرید امروز = بازگشت با امتیاز دو برابر</td></tr>
          <tr><td data-label="اصطلاح"><b>درآمد بازگشتی</b></td><td data-label="به زبان ساده چیست؟">جمع پول سفارش‌هایی که بعد از دوری برگشته‌اند — ماه به ماه با ماه شمسی یکتا</td><td data-label="مثال">مرداد همراه سال: سه نفر برگشتند، ۱،۲۰۰،۰۰۰ تومان</td></tr>
        </tbody>
      </table></div>
    </div>

    <div class="card" id="customers"><h2>👥 مشتریان — پرونده کامل سیصد و شصت درجه</h2>
      <p><b>مسیر:</b> <span class="kbd">مشتریان و فروش → مشتریان</span> — <span class="kbd">/app/customers</span></p>
      <h3>فهرست مشتریان چه دارد:</h3><ul>
        <li>جستجوی سریع با نام یا موبایل — نتیجه بازشونده با کش کوتاه</li>
        <li>فیلتر منبع، تاریخ عضویت، برچسب، امتیاز</li>
        <li>کارت مشتری با نام، موبایل، امتیاز کل، کیف پول، آخرین خرید با تاریخ شمسی</li>
        <li>خروجی فایل صفحه گسترده</li>
      </ul>
      <h3>پرونده کامل هر مشتری — تب‌ها:</h3><ul>
        <li>سفارش‌ها: هر سفارش با وضعیت رنگی، منبع، مبلغ، تاریخ شمسی</li>
        <li>تراکنش‌ها: پرداخت‌ها با درگاه و وضعیت</li>
        <li>امتیاز و کیف پول: هر تراکنش با علت — خرید، معرفی، ماموریت، گردونه، شارژ دستی</li>
        <li>یادداشت و فعالیت: یادآوری با تاریخ سررسید</li>
        <li>تیکت‌ها: تمام درخواست‌های پشتیبانی این مشتری</li>
        <li>سفر مشتری: مرحله فعلی و بعدی</li>
        <li>دکمه ارسال پیام بازگشت مستقیم اگر مشتری در خطر باشد</li>
      </ul>
      <picture><source srcset="/img/real-dashboard-1404.webp" type="image/webp"><img src="/img/real-dashboard-1404.jpg" alt="پروفایل مشتری واقعی"></picture>
    </div>

    <div class="card" id="customers-add"><h2>➕ افزودن مشتری — فیلد به فیلد با مثال</h2>
      <p><b>مسیر:</b> دکمه افزودن مشتری در <span class="kbd">/app/customers</span></p>
      <ol>
        <li><b>نام و نام خانوادگی:</b> فارسی عامیانه — مثلا «احمد رضایی» — الزامی</li>
        <li><b>موبایل:</b> یازده رقم با صفر اول — مثلا «۰۹۱۲۱۲۳۴۵۶۷» — الزامی و یکتا</li>
        <li><b>ایمیل اختیاری:</b> مثلا <span class="kbd">ahmad@example.com</span></li>
        <li><b>منبع:</b> فروشگاه اینترنتی، حضوری، سرنخ، تلگرام</li>
        <li><b>برچسب:</b> مثلا طلایی، جدید، در خطر</li>
        <li><b>تاریخ تولد شمسی:</b> با تقویم شمسی — برای کمپین تولد خودکار</li>
        <li><b>آدرس‌ها:</b> عنوان آدرس، استان، شهر، متن آدرس، کد پستی</li>
        <li>دکمه ذخیره</li>
      </ol>
      <div class="alert ok"><b>مثال:</b> نام: مریم حسینی — موبایل: ۰۹۱۲۱۲۳۴۵۶۸ — منبع: حضوری — برچسب: جدید — تاریخ تولد: ۱۳۷۰/۰۵/۱۰ — ذخیره</div>
    </div>

    <div class="card" id="orders"><h2>🛒 سفارش‌ها — فهرست، جزئیات و فاکتور</h2>
      <p><b>مسیر:</b> <span class="kbd">مشتریان و فروش → سفارش‌ها</span> — <span class="kbd">/app/orders</span></p>
      <ul>
        <li>هر سفارش: شماره یکتا، مشتری با لینک پرونده، وضعیت با رنگ، منبع فروش، مبلغ کل با تومان، تاریخ شمسی</li>
        <li>جزئیات سفارش: اقلام با نام، تعداد، قیمت واحد، جمع، تخفیف، مالیات، جمع کل، یادداشت، تاریخچه تغییر وضعیت</li>
        <li>تغییر وضعیت: کشویی وضعیت جدید + دکمه ذخیره</li>
        <li>صدور فاکتور رسمی: دکمه صدور فاکتور رسمی — سریال و کد یکتا و پیش‌نمایش زنده</li>
      </ul>
    </div>

    <div class="card" id="orders-add"><h2>➕ ثبت سفارش دستی — قدم به قدم</h2>
      <ol>
        <li>دکمه افزودن سفارش</li>
        <li>انتخاب مشتری: جستجو با موبایل یا نام — اگر مشتری جدید است، نام و موبایل سریع را بزن</li>
        <li>افزودن اقلام: انتخاب محصول از فهرست — تعداد — قیمت واحد خودکار ولی قابل ویرایش</li>
        <li>تخفیف کلی: نوع درصد یا مبلغ ثابت و مقدار</li>
        <li>منبع فروش: حضوری، فروشگاه اینترنتی، صندوق و...</li>
        <li>ثبت — سفارش با وضعیت در انتظار پرداخت ساخته می‌شود</li>
      </ol>
    </div>

    <div class="card" id="pipeline"><h2>📊 قیف فروش و سرنخ — چطور سرنخ را تبدیل کنیم</h2>
      <p><b>مسیر:</b> <span class="kbd">مشتریان و فروش → قیف فروش</span> — <span class="kbd">/app/pipeline</span></p>
      <ul>
        <li>ستون‌های سرنخ: جدید، در حال مذاکره، پیشنهاد فرستاده شده، بسته شده، باخته</li>
        <li>هر کارت: نام، موبایل، مبلغ احتمالی، احتمال تبدیل، تاریخ، کاربر مسئول</li>
        <li>جابجایی با کشیدن و رها کردن، تغییر مرحله با یک کلیک، تبدیل سرنخ به مشتری و سفارش با دکمه تبدیل</li>
        <li>ایجاد سرنخ جدید با نام و موبایل و منبع</li>
      </ul>
    </div>

    <div class="card" id="activities"><h2>⏰ یادآوری و فعالیت — چطور یادآوری بسازیم</h2>
      <p><b>مسیر:</b> <span class="kbd">مشتریان و فروش → یادآوری‌ها</span> — <span class="kbd">/app/reminders</span></p>
      <p>هر یادآوری یک فعالیت است با نوع یادداشت، تماس، جلسه، تاریخ سررسید، کاربر مسئول، مشتری مرتبط، تیک انجام شد یا نه.</p>
      <h3>افزودن یادآوری:</h3><ol><li>دکمه افزودن فعالیت</li><li>عنوان، توضیح، نوع، تاریخ سررسید با تقویم شمسی، کاربر مسئول، مشتری مرتبط</li><li>ذخیره — در فهرست یادآوری‌ها و در پرونده مشتری دیده می‌شود</li></ol>
    </div>

    <div class="card" id="products"><h2>📦 محصولات و دسته‌بندی — چطور محصول اضافه کنیم</h2>
      <p><b>مسیر:</b> <span class="kbd">محصولات</span> — <span class="kbd">/app/products</span></p>
      <ol>
        <li>دکمه افزودن محصول</li>
        <li>نام: فارسی عامیانه</li>
        <li>دسته: مثلا لبنیات، نوشیدنی</li>
        <li>بارکد اختیاری</li>
        <li>قیمت خرید، هزینه حمل، درصد سود — قیمت فروش خودکار حساب می‌شود ولی قابل ویرایش است</li>
        <li>موجودی در هر انبار و نقطه سفارش</li>
        <li>ذخیره</li>
      </ol>
    </div>

    <div class="card" id="warehouses"><h2>🏬 انبار و موجودی و انتقال — چطور انبار بسازیم</h2>
      <p><b>مسیر:</b> <span class="kbd">انبارها</span> — <span class="kbd">/app/warehouses</span></p>
      <ul>
        <li>انبار: نام، کد، آدرس، کاربر مسئول، موجودی اولیه</li>
        <li>انتقال بین انبار: مبدا، مقصد، محصول، تعداد، یادداشت — با تایید</li>
      </ul>
    </div>

    <div class="card" id="pos"><h2>💳 صندوق فروش هوشمند و هشدار ریزش در لحظه</h2>
      <p><b>مسیر:</b> <span class="kbd">مشتریان و فروش → فروش سریع</span> — <span class="kbd">/app/pos</span></p>
      <ol>
        <li><b>انتخاب انبار:</b> بالا — کشویی انبارها</li>
        <li><b>جستجو و انتخاب مشتری:</b> کادر جستجوی مشتری — موبایل یا نام — فهرست بازشونده — انتخاب — کادر سبز «مشتری انتخاب شده»</li>
        <li><b>هشدار حفظ مشتری:</b> اگر مشتری با ارزش و مدت زیادی نیامده، همان لحظه نوار نارنجی یا قرمز بالای سبد می‌آید — متن فارسی عامیانه — دو دکمه: اعمال تخفیف پانزده درصد و رد کردن</li>
        <li><b>افزودن محصول:</b> از گرید محصول بزن — به سبد راست اضافه می‌شود</li>
        <li><b>تخفیف کلی، مالیات، جمع کل با عدد فارسی و تومان</b></li>
        <li><b>روش پرداخت:</b> تیک‌های گرد نقدی، کارت، انتقال، نسیه</li>
        <li><b>ثبت و پرداخت:</b> دکمه بزرگ سبز تمام عرض</li>
      </ol>
      <picture><source srcset="/img/real-pos-1404.webp" type="image/webp"><img src="/img/real-pos-1404.jpg" alt="صندوق هوشمند واقعی با هشدار"></picture>
    </div>

    <div class="card" id="pos-cashier"><h2>💵 گزارش صندوق و شیفت — چطور شیفت را ببندیم</h2>
      <p><b>مسیر:</b> <span class="kbd">گزارش صندوق</span> — <span class="kbd">/app/pos/cashier</span></p>
      <ul>
        <li>شیفت‌های باز و بسته با زمان شروع و پایان شمسی، کاربر، جمع فروش نقدی و کارتی</li>
        <li>دکمه بستن شیفت — کسری و موجودی صندوق</li>
      </ul>
    </div>

    <div class="card" id="retention"><h2>🛟 مرکز بازگشت و حفظ مشتری</h2>
      <p><b>مسیر:</b> <span class="kbd">مشتریان و فروش → مرکز بازگشت و حفظ</span> — <span class="kbd">/app/retention</span></p>
      <picture><source srcset="/img/real-retention-1404.webp" type="image/webp"><img src="/img/real-retention-1404.jpg" alt="مرکز بازگشت واقعی"></picture>
      <ul>
        <li>چهار کارت بالا: امتیاز سلامت عملیات، تعداد کم‌فعال، نرخ ریزش فعلی، درآمد بازگردانی شده کل</li>
        <li>جدول مشتریان در معرض خطر با سطح خطر و احتمال ریزش و اقدام پیشنهادی و صفحه‌بندی</li>
        <li>نمودار روند حفظ مشتری با ماه‌های شمسی</li>
        <li>اقدام گروهی: چند مشتری را تیک بزن، دکمه ارسال پیام گروهی بازگشت</li>
      </ul>
    </div>

    <div class="card" id="recovery-revenue"><h2>💰 درآمد بازگشتی ماهانه یکتا — بدون تکرار</h2>
      <p><b>مسیر:</b> <span class="kbd">گزارش‌ها → درآمد بازگشتی</span> — <span class="kbd">/app/reports?tab=recovery</span></p>
      <picture><source srcset="/img/real-recovery-1404.webp" type="image/webp"><img src="/img/real-recovery-1404.jpg" alt="درآمد بازگشتی یکتا"></picture>
      <p>هر ماه شمسی واقعی با برچسب یکتا بدون تکرار — نمودار میله‌ای با دو محور تومان و تعداد</p>
    </div>

    <div class="card" id="recovery-history"><h2>🗂️ آرشیو گزارش هفتگی خودکار با گراف داخل ایمیل</h2>
      <p><b>مسیر:</b> <span class="kbd">مشتریان و فروش → آرشیو گزارش‌های بازگشت</span> — <span class="kbd">/app/reports/recovery/history</span></p>
      <ul>
        <li>شش کارت بالا: کل گزارش‌های آرشیو، مجموع بازگشت، کل درآمد، میانگین نرخ، سی روز اخیر، رکورد یک هفته</li>
        <li>جستجو با تاریخ شمسی، فیلتر بازه، نمودار خطی روند دوازده گزارش آخر</li>
        <li>جدول با صفحه‌بندی و عملیات مشاهده کامل و ارسال دوباره</li>
        <li>دکمه‌ها: تولید گزارش همین الان، دانلود فایل صفحه گسترده آرشیو</li>
      </ul>
      <h3>زمان‌بندی قابل تنظیم:</h3><p>تنظیمات → اعلان‌ها → بلوک گزارش خودکار بازگشت: فعال یا غیر فعال، ساعت صبح، عصر، روزهای هفته شنبه تا جمعه، ایمیل اضافی</p>
    </div>

    <div class="card" id="recovery-widget"><h2>🧩 ویجت داشبورد درآمد بازگشتی همیشه روشن</h2>
      <p>مسیر: <span class="kbd">/app</span> — پیشخوان — ویجت درآمد بازگشتی همیشه نمایش حتی با صفر و پیام «هنوز بازگشتی ثبت نشده»</p>
    </div>

    <div class="card" id="automation-recovery"><h2>⚙️ اتوماسیون امتیاز دو برابر بازگشت</h2>
      <p>هنگام کامل شدن سفارش اگر فاصله از سفارش قبلی چهل و پنج روز یا بیشتر باشد، امتیاز دو برابر می‌شود و یادداشت بازگشت برای تیم فروش ثبت می‌شود.</p>
    </div>

    <div class="card" id="dashboard"><h2>📊 داشبورد فرماندهی و ویجت درآمد بازگشتی همیشه روشن</h2>
      <p><b>مسیر:</b> <span class="kbd">/app</span> — پیشخوان</p>
      <picture><source srcset="/img/real-dashboard-1404.webp" type="image/webp"><img src="/img/real-dashboard-1404.jpg" alt="داشبورد واقعی"></picture>
      <ul><li>ویجت سلامت عملیات با حلقه درصد رنگی</li><li>شش کارت کوچک: فروش کل، تعداد سفارش، مشتری فعال، مشتری جدید با درصد تغییر</li><li>نوار اقدام امروز، فرصت رشد، بینش‌ها، دستیار کناری</li><li>ویجت درآمد بازگشتی همیشه نمایش حتی با صفر</li></ul>
    </div>

    <div class="card" id="reports"><h2>📈 مرکز گزارشات نه بخشی — چطور هر گزارش را ببینیم</h2>
      <p><b>مسیر:</b> <span class="kbd">گزارش‌ها</span> — <span class="kbd">/app/reports</span></p>
      <ul>
        <li><b>نمای کلی:</b> روند فروش روزانه/هفتگی/ماهانه، میانگین سفارش، وضعیت سفارش‌ها</li>
        <li><b>گزارش فروش:</b> ساعت‌های طلایی، روزهای هفته شنبه تا جمعه، منبع فروش</li>
        <li><b>گروه‌بندی رفتار خرید:</b> شش دسته قهرمانان، وفادار، مستعد، جدید، در معرض خطر، از دست رفته</li>
        <li><b>ارزش طول عمر:</b> شش کارت، نمودار توزیع ریسک، جدول بخش‌بندی، پنجاه مشتری برتر و بیست ارزشمند در خطر</li>
        <li><b>درآمد بازگشتی:</b> نمودار ماهانه یکتا، فهرست بازگشت‌های بازه</li>
        <li><b>پرفروش‌ترین‌ها:</b> بیست محصول و ده دسته و بیست مشتری برتر بازه</li>
        <li><b>کانال‌های فروش:</b> سهم هر کانال با نمودار دایره‌ای</li>
        <li><b>پرداخت‌ها:</b> مجموع پرداخت، تعداد درگاه، توزیع درگاه و وضعیت</li>
        <li><b>پشتیبانی:</b> کل تیکت، باز، بسته، میانگین پاسخ، تفکیک بخش</li>
        <li><b>خروجی:</b> دانلود فایل صفحه گسترده</li>
      </ul>
    </div>

    <div class="card" id="clv"><h2>💎 ارزش طول عمر مشتری با پیش‌بینی احتمال رفتن — چطور آمار متفاوت را بررسی کنیم</h2>
      <p><b>مسیر:</b> <span class="kbd">گزارش‌ها → ارزش طول عمر</span> — <span class="kbd">/app/reports?tab=clv</span></p>
      <h3>شش کارت بالا:</h3>
      <div class="table-wrap"><table class="table"><thead><tr><th>شاخص</th><th>چیست و چطور ببینی</th></tr></thead>
        <tbody>
          <tr><td data-label="شاخص">میانگین ارزش دوازده ماه آینده</td><td data-label="چیست">میانگین پیش‌بینی درآمد هر مشتری در یک سال آینده — برای تصمیم بودجه</td></tr>
          <tr><td data-label="شاخص">میانگین احتمال ریزش</td><td data-label="چیست">میانگین درصد احتمال عدم بازگشت همه مشتریان</td></tr>
          <tr><td data-label="شاخص">ارزشمند در معرض خطر</td><td data-label="چیست">ارزش بالا و احتمال چهل تا هشتاد و پنج درصد — طلایی‌هایی که دارند می‌روند — دکمه کمپین بازگشت کنار هر ردیف</td></tr>
        </tbody>
      </table></div>
    </div>

    <div class="card" id="rfm"><h2>👥 رفتار خرید و بخش‌بندی خودکار</h2><p>بخش‌بندی با تازگی، تعداد، مبلغ: تازگی چند روز پیش آخرین خرید، تعداد چند بار خرید کرده، مبلغ چقدر خرج کرده.</p></div>

    <div class="card" id="segments"><h2>🎯 بخش‌بندی — اجرای خودکار</h2>
      <p>مسیر: <span class="kbd">بخش‌بندی مشتریان</span> — <span class="kbd">/app/segments</span> و اجرای دستی <span class="kbd">/app/segments/auto</span></p>
      <p>سیستم با تحلیل ارزش طول عمر، مشتری‌ها را خودکار در پنج بخش طلایی می‌گذارد و کش گزارشات را پاک می‌کند.</p>
    </div>

    <div class="card" id="journeys"><h2>🗺️ سفر مشتری</h2><p>مسیر: <span class="kbd">سفر مشتری</span> — <span class="kbd">/app/journeys</span> — ایجاد سفر، نمایش، ویرایش، حذف، روشن خاموش.</p></div>

    <div class="card" id="loyalty-overview"><h2>🎁 باشگاه مشتریان چیست و چطور کار می‌کند</h2>
      <p>باشگاه یک پنل جدا با آدرس <span class="kbd">/club/login</span> است که مشتری با موبایل و رمز یکبار مصرف وارد می‌شود. داخل باشگاه امتیازش، کیف پولش، کوپن‌هایش، ماموریت‌هایش، گردونه شانس، سفارش‌هایش، تراکنش‌هایش، سفرش و معرفی دوستانش را می‌بیند.</p>
    </div>

    <div class="card" id="loyalty-levels"><h2>🏆 سطوح مشتری — برنزی، نقره‌ای، طلایی و بالاتر — چطور بسازیم</h2>
      <p>مسیر: <span class="kbd">باشگاه مشتریان → سطوح و قوانین → تب سطوح</span> — <span class="kbd">/app/loyalty/settings</span></p>
      <ol>
        <li>دکمه افزودن سطح</li>
        <li>نام سطح: مثلا برنزی، نقره‌ای، طلایی — فارسی عامیانه</li>
        <li>حداقل امتیاز: مثلا برنزی صفر، نقره‌ای هزار، طلایی پنج هزار</li>
        <li>رنگ سطح</li>
        <li>توضیح مزیت</li>
        <li>ذخیره — مشتری‌ها خودکار بر اساس مجموع امتیاز در سطح مناسب قرار می‌گیرند</li>
      </ol>
    </div>

    <div class="card" id="loyalty-rules"><h2>📜 قوانین امتیاز — چطور به مشتری امتیاز بدهیم</h2>
      <p>مسیر: <span class="kbd">باشگاه → سطوح و قوانین → تب قوانین امتیاز</span></p>
      <ol>
        <li>دکمه افزودن قانون</li>
        <li>عنوان: مثلا خرید اول، خرید بالای پانصد هزار تومان</li>
        <li>شرط: نوع شرط — مبلغ خرید حداقل، تعداد خرید، عضویت، معرفی موفق</li>
        <li>مقدار شرط: مثلا پانصد هزار تومان</li>
        <li>مقدار امتیاز: مثلا پنجاه امتیاز</li>
        <li>آیا کیف پول هم بدهد؟ اگر بله، مبلغ کیف پول</li>
        <li>ذخیره</li>
      </ol>
    </div>

    <div class="card" id="wallet"><h2>👛 کیف پول — شارژ و برداشت و خرج</h2>
      <h3>شارژ:</h3><ul><li>از قانون امتیاز که کیف پول دارد</li><li>از معرفی موفق دوست</li><li>از ماموریت که پاداش کیف پول دارد</li><li>از شارژ دستی توسط مدیر در پروفایل مشتری → دکمه شارژ کیف پول → مبلغ و علت</li></ul>
      <h3>خرج:</h3><ul><li>در صندوق فروش سریع: بخش وفاداری → استفاده از کیف پول</li><li>در باشگاه: مشتری در صفحه تسویه سفارش جدید، تیک استفاده از کیف پول بزند</li></ul>
    </div>

    <div class="card" id="rewards"><h2>🎟️ پاداش و کوپن — ساخت و مدیریت — چطور کوپن بسازیم</h2>
      <p>مسیر: <span class="kbd">باشگاه → گزارش‌ها و پاداش‌ها → تب پاداش</span></p>
      <ol>
        <li>دکمه افزودن پاداش</li>
        <li>عنوان: مثلا ده درصد تخفیف اسنپ، قهوه رایگان</li>
        <li>نوع پاداش: کوپن تخفیف، کیف پول، محصول فیزیکی، امتیاز</li>
        <li>مقدار و هزینه امتیازی برای دریافت</li>
        <li>تعداد موجود و تاریخ انقضا — تقویم شمسی</li>
        <li>ذخیره — پاداش در باشگاه در بخش پاداش‌های آماده دیده می‌شود</li>
      </ol>
    </div>

    <div class="card" id="missions"><h2>🎯 ماموریت — شرط و پاداش قدم به قدم با مثال</h2>
      <p>مسیر: <span class="kbd">باشگاه → کمپین‌ها و ماموریت‌ها</span> — <span class="kbd">/app/loyalty/campaigns</span></p>
      <ol>
        <li>دکمه افزودن ماموریت</li>
        <li>عنوان و توضیح عامیانه</li>
        <li>نوع شرط: تعداد خرید، مبلغ خرید، معرفی دوست، تکمیل پروفایل</li>
        <li>مقدار شرط: مثلا ۲ بار خرید یا ۲ نفر معرفی</li>
        <li>پاداش: امتیاز و/یا کیف پول</li>
        <li>تاریخ شروع و پایان</li>
        <li>ذخیره — ماموریت در باشگاه با نوار پیشرفت دیده می‌شود</li>
      </ol>
    </div>

    <div class="card" id="wheel"><h2>🎡 گردونه شانس — جایزه‌ها، شانس، تاریخچه چرخش</h2>
      <p>مسیر: <span class="kbd">باشگاه → گردونه شانس</span> — <span class="kbd">/app/loyalty/wheel</span></p>
      <ol>
        <li>دکمه افزودن جایزه — عنوان، نوع جایزه، مقدار، احتمال درصد جمع صد، رنگ، تعداد موجود</li>
        <li>تنظیم گردونه: هزینه هر چرخش به امتیاز مثلا ۱۰۰، چند بار در روز مثلا ۱ بار</li>
        <li>تاریخچه چرخش‌ها پایین صفحه با نام مشتری، جایزه برنده شده، تاریخ شمسی</li>
      </ol>
    </div>

    <div class="card" id="leaderboard"><h2>🏆 جدول امتیازی و لیگ — رقابت سالم</h2>
      <p>مسیر باشگاه: <span class="kbd">جدول امتیازی</span> و <span class="kbd">لیگ</span> — <span class="kbd">/club/leaderboard</span></p>
      <ul>
        <li>جدول امتیازی: فهرست مشتریان به ترتیب امتیاز کل — سه نفر اول با سکو</li>
        <li>لیگ: لیگ هفتگی یا ماهانه — امتیاز فقط در بازه لیگ — جایزه برای سه نفر اول</li>
      </ul>
    </div>

    <div class="card" id="referrals"><h2>🤝 معرفی دوستان — لینک اختصاصی و پاداش دو طرفه</h2>
      <p>مسیر باشگاه: <span class="kbd">معرفی دوستان</span> — <span class="kbd">/club/referrals</span></p>
      <ol>
        <li>هر عضو یک لینک اختصاصی دارد مثل <span class="kbd">/club/register?ref=MY-8A2X</span> و کد کوتاه</li>
        <li>دکمه کپی لینک و دکمه اشتراک در واتساپ، تلگرام</li>
        <li>جدول معرفی‌های من: نام دوست، تاریخ ثبت‌نام، آیا خرید کرده، وضعیت پاداش</li>
        <li>وقتی دوست با لینک ثبت‌نام کند و اولین خریدش را کامل کند، هر دو امتیاز و کیف پول می‌گیرند</li>
      </ol>
    </div>

    <div class="card" id="club-orders"><h2>🛒 سفارش‌های باشگاه</h2>
      <p>مسیر باشگاه: <span class="kbd">سفارش‌های من</span> — <span class="kbd">/club/orders</span> — مشتری تمام سفارش‌هایش را با وضعیت رنگی می‌بیند</p>
    </div>

    <div class="card" id="club-transactions"><h2>💳 تراکنش‌های امتیاز و کیف پول</h2>
      <p>مسیر باشگاه: <span class="kbd">تراکنش‌ها</span> — <span class="kbd">/club/transactions</span> — تاریخ شمسی، نوع افزایش/کاهش، مقدار، علت، موجودی بعد</p>
    </div>

    <div class="card" id="club-auth"><h2>🔐 ورود باشگاه با رمز یکبار مصرف شیشه‌ای شش کادری</h2>
      <p>مسیر مشتری: <span class="kbd">/club/login</span> — وارد کردن موبایل — پاپ‌آپ شیشه‌ای: پشت تار، کارت شفاف، شش کادر خالی، تایمر دایره‌ای شصت ثانیه‌ای — پیامک می‌آید و رمز خودکار جاگذاری می‌شود یا دستی تایپ می‌کنی — بعد از شش رقم خودکار ارسال می‌شود</p>
      <picture><source srcset="/img/real-club-auth-1404.webp" type="image/webp"><img src="/img/real-club-auth-1404.jpg" alt="ورود شیشه‌ای باشگاه"></picture>
    </div>

    <div class="card" id="tax-invoices"><h2>🧾 فاکتور رسمی و برندینگ فاکتور — چطور فاکتور رسمی بسازیم</h2>
      <p>مسیر: <span class="kbd">فروشگاه و مالی → فاکتورهای رسمی</span> — <span class="kbd">/app/tax-invoices</span></p>
      <ul>
        <li>فهرست فاکتورها با وضعیت پیش‌نویس، در صف، ارسال شده، تایید شده، رد شده، ناموفق</li>
        <li>صدور پیش‌فاکتور: مشتری، اقلام، جمع مبلغ، مالیات، قابل پرداخت</li>
        <li>تنظیمات برندینگ فاکتور با پیش‌نمایش زنده فاکتور کوچک: اندازه کاغذ، جهت، فونت، حاشیه، مهر، واترمارک، بارکد و QR</li>
        <li>صدور فاکتور رسمی از سفارش با دکمه صدور — سریال و کد یکتا</li>
      </ul>
    </div>

    <div class="card" id="woocommerce"><h2>🔌 فروشگاه اینترنتی و همگام‌سازی — چطور سایت را متصل کنیم</h2>
      <p>مسیر: <span class="kbd">فروشگاه و مالی → اتصال فروشگاه</span> — <span class="kbd">/app/woocommerce</span></p>
      <ol>
        <li>دکمه افزودن اتصال — آدرس فروشگاه اینترنتی، کلید مصرف‌کننده، راز مصرف‌کننده</li>
        <li>تست کشش — دکمه تست کشش — اگر موفق باشد پیام موفقیت می‌آید</li>
        <li>کشش الان، کشش مشتریان الان، کشش محصولات الان، کشش همه الان — هر کدام با لاگ</li>
        <li>ورود فایل — فایل صفحه گسترده مشتری یا محصول را آپلود کن</li>
      </ol>
    </div>

    <div class="card" id="sync-logs"><h2>🔄 لاگ همگام‌سازی و ارسال دوباره — چطور خطا را ببینیم</h2>
      <p>مسیر: <span class="kbd">فروشگاه و مالی → لاگ همگام‌سازی</span> — <span class="kbd">/app/sync-logs</span></p>
      <p>هر عملیات همگام‌سازی یک لاگ دارد با وضعیت موفق یا ناموفق، پیام، زمان، اتصال مربوطه و دکمه ارسال دوباره — اگر ناموفق بود، دکمه ارسال دوباره بزن.</p>
    </div>

    <div class="card" id="payments"><h2>💰 پرداخت‌ها و درگاه‌ها — چطور تراکنش‌ها را ببینیم</h2>
      <p>مسیر: <span class="kbd">فروشگاه و مالی → تراکنش‌های پرداخت</span> — <span class="kbd">/app/payments</span></p>
      <p>تراکنش‌ها با مبلغ، درگاه، وضعیت، تاریخ شمسی، مشتری. جمع کل پرداخت و توزیع درگاه با نمودار میله‌ای.</p>
    </div>

    <div class="card" id="messaging"><h2>📨 مرکز پیام چندکاناله — چطور پیام گروهی بفرستیم</h2>
      <p>مسیر: <span class="kbd">مشتریان و فروش → مرکز پیام‌رسانی</span> — <span class="kbd">/app/messages</span></p>
      <ul>
        <li>ارسال تکی: انتخاب مشتری با جستجو موبایل، نوشتن پیام، انتخاب کانال (پیامک، تلگرام، بله، ایتا، روبیکا، واتساپ) — ارسال</li>
        <li>ارسال گروهی: انتخاب بخش، نوشتن پیام با الگو (نام مشتری با <span class="kbd">{name}</span> جایگزین می‌شود)، زمان‌بندی (الان یا بعدا)، انتخاب کانال — ارسال گروهی</li>
      </ul>
    </div>

    <div class="card" id="sms"><h2>📱 پنل پیامک تایید و رمز یکبار مصرف — چطور تنظیم کنیم</h2>
      <p>مسیر: <span class="kbd">تنظیمات → پیامک</span> — <span class="kbd">/app/settings?tab=sms</span></p>
      <ol>
        <li>ارائه‌دهنده را بگذار پیامک با روش تایید</li>
        <li>کلید برنامه‌نویسی و شماره فرستنده — سیستم هم کلید با پیشوند و هم بدون پیشوند را می‌خواند</li>
        <li>شناسه قالب تایید مثل ۹۶۹۹۷۵ — باکس‌ها هم‌تراز و در یک راستا</li>
        <li>ذخیره و تست ارسال</li>
      </ol>
    </div>

    <div class="card" id="campaigns"><h2>📣 کمپین‌ها — چطور کمپین بازگشت بسازیم</h2>
      <p>مسیر: <span class="kbd">اتوماسیون → کمپین‌ها</span> — <span class="kbd">/app/campaigns</span></p>
      <ul>
        <li>کمپین پیام‌رسانی با الگو و زمان‌بندی و بخش هدف</li>
        <li>کمپین باشگاه: شرط و پاداش — مثلا کمپین یلدا: خرید بالای پانصد هزار تومان در آذر → صد امتیاز</li>
      </ul>
    </div>

    <div class="card" id="workflows"><h2>⚙️ گردش کار و اتوماسیون — چطور گردش کار بسازیم</h2>
      <p>مسیر: <span class="kbd">اتوماسیون → گردش کارها</span> — <span class="kbd">/app/workflows</span></p>
      <p>گردش کار: شرط و اقدام — مثلا اگر سفارش کامل شد و بازگشت بود، امتیاز دو برابر. ایجاد گردش کار جدید با عنوان، شرط (وضعیت سفارش، فاصله خرید)، اقدام (افزودن امتیاز، ارسال پیام، افزودن به بخش).</p>
    </div>

    <div class="card" id="tickets"><h2>🎫 تیکت و پاسخ آماده و گزارش تاخیر — چطور تیکت را پاسخ دهیم</h2>
      <p>مسیر: <span class="kbd">پشتیبانی و دانش → درخواست‌ها (تیکت‌ها)</span> — <span class="kbd">/app/tickets</span></p>
      <ul>
        <li>فهرست تیکت‌ها با موضوع، بخش، اولویت، وضعیت (باز، در حال بررسی، بسته، حل شده)، مشتری، کاربر مسئول، تاریخ شمسی</li>
        <li>جزئیات تیکت: تاریخچه پیام‌ها، فایل پیوست، پاسخ آماده انتخابی</li>
        <li>پاسخ: متن پاسخ، فایل پیوست، انتخاب پاسخ آماده، تغییر وضعیت</li>
        <li>پاسخ آماده: عنوان و متن، روشن خاموش، حذف — مسیر <span class="kbd">/app/tickets/responses</span></li>
        <li>گزارش تاخیر پاسخ: میانگین پاسخ به دقیقه، تفکیک بخش، تعداد کل، باز، بسته — مسیر <span class="kbd">/app/tickets/sla</span></li>
      </ul>
    </div>

    <div class="card" id="kb"><h2>📚 پایگاه دانش — چطور مقاله اضافه کنیم</h2>
      <p>مسیر: <span class="kbd">پشتیبانی و دانش → پایگاه دانش</span> — <span class="kbd">/app/kb</span></p>
      <ol>
        <li>دکمه افزودن مقاله</li>
        <li>عنوان: مثلا چطور رمز یکبار مصرف را فعال کنم</li>
        <li>محتوا: متن کامل با ویرایشگر</li>
        <li>دسته‌بندی: مثلا عمومی، باشگاه، صندوق</li>
        <li>وضعیت انتشار: پیش‌نویس یا منتشر شده</li>
        <li>ذخیره — مقاله در پایگاه دانش و در دستیار هوشمند قابل جستجو می‌شود</li>
      </ol>
      <p>دسته‌بندی: <span class="kbd">/app/kb/categories</span> — افزودن دسته با نام و توضیح و روشن خاموش</p>
    </div>

    <div class="card" id="assistant"><h2>🤖 دستیار هوشمند — چطور چت کنیم و تنظیم کنیم</h2>
      <p>مسیر: <span class="kbd">دستیار هوشمند → چت با دستیار</span> — <span class="kbd">/app/assistant</span></p>
      <ul>
        <li>چت: باکس چت با پیام کاربر و ربات، پیشنهادهای سریع، فرم ورودی متن و فایل — متن را بنویس و دکمه ارسال — ربات با دانش پایگاه دانش و داده‌های زنده پاسخ می‌دهد</li>
        <li>تنظیمات مغز: انتخاب مدل، کلید برنامه‌نویسی، تست اتصال — مسیر <span class="kbd">/app/assistant/settings</span></li>
        <li>تنظیمات ظاهر ابزارک: رنگ، لوگو، موقعیت، متن خوشامد — مسیر <span class="kbd">/app/assistant/widget-settings</span></li>
        <li>تاریخچه گفتگو: <span class="kbd">/app/kb/chat-logs</span> — فهرست همه گفتگوها با کاربر و تاریخ شمسی — مشاهده هر گفتگو</li>
        <li>اسکریپت ویجت: <span class="kbd">/widget/assistant.js</span> — کپی کن و در سایت اصلی قرار بده تا ویجت چت در سایت اصلی بیاید</li>
        <li>پنل شناور در مدیریت: دکمه شناور با ربات پایین راست + پس‌زمینه تار + پنل با قاب داخلی دستیار</li>
      </ul>
    </div>

    <div class="card" id="users"><h2>👥 کاربران و نقش‌ها — چطور کاربر جدید اضافه کنیم</h2>
      <p>مسیر: <span class="kbd">تنظیمات → کاربران و دسترسی‌ها</span> — <span class="kbd">/app/users</span></p>
      <ol>
        <li>دکمه افزودن کاربر</li>
        <li>نام، ایمیل، موبایل، رمز عبور</li>
        <li>نقش اصلی: مدیر، فروش، پشتیبانی</li>
        <li>نقش‌های سفارشی: تیک دسترسی‌های ریز مثل مشتری دیدن، سفارش مدیریت</li>
        <li>ذخیره — کاربر ساخته می‌شود و می‌تواند با ایمیل و رمز یا رمز پیامکی وارد شود</li>
        <li>ویرایش کاربر: تغییر نقش و دسترسی</li>
        <li>نقش سفارشی: افزودن نقش جدید با نام و تیک دسترسی‌ها — ویرایش و حذف نقش</li>
      </ol>
    </div>

    <div class="card" id="settings-brand"><h2>🎨 تنظیم برند و رنگ و لوگو — چطور ظاهر فروشگاه را عوض کنیم</h2>
      <p>مسیر: <span class="kbd">تنظیمات → طراحی سایت</span> — <span class="kbd">/app/settings?tab=design</span></p>
      <ul>
        <li>لوگو: پیش‌نمایش مربع با گوشه نرم، کنار باکس آپلود — فاوآیکن هم کنارش — هر دو کنار هم در دسکتاپ — عرض پیش‌نمایش چهار واحد</li>
        <li>نام برند، شعار، نماد، رنگ اصلی — ورودی رنگ عرض کامل تا پنج واحد، ارتفاع دو و سه چهارم واحد، گوشه نرم</li>
        <li>سه فیلد تم، نماد و رنگ در یک ردیف تراز کامل و ته‌چین با فاصله استاندارد</li>
        <li>بعد ذخیره، رنگ اصلی در متغیر رنگ برند می‌رود و دکمه‌ها، آیکون بالای صفحه، نوار کناری و حلقه فوکوس همرنگ می‌شوند</li>
      </ul>
    </div>

    <div class="card" id="settings-notif"><h2>🔔 تنظیم اعلان و گزارش خودکار — چطور زمان‌بندی کنیم</h2>
      <p>مسیر: <span class="kbd">تنظیمات → اعلان‌ها</span> — <span class="kbd">/app/settings?tab=notifications</span></p>
      <ul>
        <li>رویدادهای اعلان: دو ستونه، هر رویداد با تیک — مثلا سفارش جدید، تیکت جدید، مشتری جدید</li>
        <li>بلوک گزارش خودکار بازگشت: فعال یا غیر فعال، ساعت صبح مثلا نه صبح، عصر فعال یا نه و ساعتش مثلا نه شب، روزهای هفته شنبه تا جمعه تیک، ایمیل اضافی</li>
        <li>ذخیره — بررسی هر ساعت با تلورانس سی دقیقه و کش بیست و پنج ساعته برای جلوگیری از دو بار ارسال</li>
      </ul>
    </div>

    <div class="card" id="settings-backup"><h2>💾 پشتیبان و امنیت دو مرحله‌ای — چطور پشتیبان بگیریم</h2>
      <p>مسیر: <span class="kbd">تنظیمات → پشتیبان‌گیری</span> — <span class="kbd">/app/backups</span></p>
      <ul>
        <li>پشتیبان پایگاه داده: دکمه دانلود فوری — فایل با تاریخ شمسی</li>
        <li>پشتیبان تنظیمات: خروجی فایل تنظیمات</li>
        <li>امنیت دو مرحله‌ای: تنظیمات → امنیت دو مرحله‌ای — روش‌های پیامک، ایمیل، برنامه احراز هویت — هر روش با تیک — نیاز به تایید با کد</li>
        <li>رویدادهای اعلان دو ستونه بالا</li>
      </ul>
    </div>

    <div class="card" id="buttons"><h2>🎨 ظاهر دکمه‌ها — پایان رنگ‌آمیزی بچه‌گانه — چطور دکمه‌ها مدرن شدند</h2>
      <p>فایل اصلی و لایه نهایی بعد از همه بوردها لود می‌شود تا گرادینت شلوغ را بازنویسی کند. دکمه‌ها: اصلی با رنگ برند و متن سفید با تضاد بالا، فرعی سفید با حاشیه، شفاف، خطر قرمز خالص، موفقیت سبز، نرم ده درصد برند، اندازه کوچک و بزرگ و فقط آیکون، حالت بارگذاری چرخان و غیر فعال کم‌رنگ. هاور با بالا رفتن یک پیکسل و سایه نرم و کمی روشن‌تر، کلیک با کوچک شدن، فوکوس با حلقه. سرعت هزار کاربر همزمان زیر دو ثانیه با کش کوتاه.</p>
    </div>

    <div class="card" style="text-align:center">
      <h2>مستندات تمام شد — حالا نوبت عمل</h2>
      <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap; margin-top:12px">
        <a class="btn btn-primary" href="/app">ورود به پنل</a>
        <a class="btn" href="/amoozesh">رفتن به آموزش</a>
        <a class="btn" href="/app/retention">مرکز بازگشت</a>
      </div>
    </div>

  </main>
</div>

<script>
document.getElementById('docSearch')?.addEventListener('input', function(e){
  var q=e.target.value.toLowerCase().trim();
  var cards=document.querySelectorAll('.main .card');
  if(!q){cards.forEach(function(c){c.style.display='';});return;}
  cards.forEach(function(c){c.style.display=c.innerText.toLowerCase().includes(q)?'':'none';});
});
(function(){
  var tocList=document.getElementById('tocList');
  if(!tocList)return;
  document.querySelectorAll('.main .card[id]').forEach(function(card){
    var a=document.createElement('a');a.href='#'+card.id;a.textContent=card.querySelector('h2')?.innerText||card.id;a.setAttribute('data-id',card.id);tocList.appendChild(a);
  });
})();
var navLinks=document.querySelectorAll('.sidebar .nav-link');
var tocLinks=document.querySelectorAll('.toc a');
var observer=new IntersectionObserver(function(entries){
  entries.forEach(function(entry){
    if(entry.isIntersecting){
      var id=entry.target.id;
      navLinks.forEach(function(l){ l.classList.toggle('active', l.getAttribute('href')==='#'+id); });
      tocLinks.forEach(function(l){ l.classList.toggle('active', l.getAttribute('data-id')===id); });
      history.replaceState(null,null,'#'+id);
    }
  });
},{rootMargin:'-15% 0px -70% 0px', threshold:0});
document.querySelectorAll('.main .card[id]').forEach(function(c){ observer.observe(c); });
function toggleSidebar(){
  var sb=document.querySelector('.sidebar');
  var ov=document.getElementById('sidebarOverlay');
  if(!sb) return;
  sb.classList.toggle('open');
  if(ov) ov.classList.toggle('show', sb.classList.contains('open'));
  document.body.style.overflow = sb.classList.contains('open') ? 'hidden' : '';
}
function closeSidebar(){
  var sb=document.querySelector('.sidebar');
  var ov=document.getElementById('sidebarOverlay');
  if(sb) sb.classList.remove('open');
  if(ov) ov.classList.remove('show');
  document.body.style.overflow='';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeSidebar(); });
document.querySelectorAll('.sidebar .nav-link, .toc a').forEach(function(a){
  a.addEventListener('click', function(e){
    var href=a.getAttribute('href');
    if(href && href.startsWith('#')){
      var target=document.querySelector(href);
      if(target){
        e.preventDefault();
        target.scrollIntoView({behavior:'smooth', block:'start'});
        history.pushState(null,null,href);
        closeSidebar();
      }
    }
  });
});
</script>

</body>
</html>
