<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>آموزش باشگاه مشتریان — تمام قسمت‌ها جزء به جزء</title>
<meta name="description" content="آموزش کامل باشگاه مشتریان: سطوح، قوانین امتیاز، کیف پول، پاداش، کوپن، ماموریت، گردونه شانس، جدول امتیازی، لیگ، معرفی دوستان، سفارش‌ها، تراکنش‌ها، سفر مشتری، پشتیبانی، ورود با رمز یکبار مصرف شیشه‌ای">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<style>
:root{--bg:#f8fafc;--panel:#fff;--line:#e2e8f0;--txt:#0f172a;--mut:#64748b;--acc:#8b5cf6;--acc2:#10b981;--shadow:0 8px 24px rgba(15,23,42,.06);--top-h:58px;--side-w:300px}
*{box-sizing:border-box}html{scroll-behavior:smooth;scroll-padding-top:calc(var(--top-h) + 12px);-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--txt);font-family:var(--font-fa,Tahoma,sans-serif);line-height:2;font-size:clamp(13.5px, 1.2vw, 14.5px);overflow-x:hidden}
a{color:var(--acc);text-decoration:none}
img{max-width:100%;height:auto}
.top{position:sticky;top:0;z-index:40;background:rgba(255,255,255,.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--line);padding:10px 12px;display:flex;justify-content:space-between;align-items:center;gap:8px;min-height:var(--top-h)}
.brand{display:flex;gap:8px;align-items:center;font-weight:1000;flex:0 0 auto}
.mark{width:32px;height:32px;border-radius:9px;background:var(--acc);color:#fff;display:grid;place-items:center;font-weight:1000;flex:0 0 32px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--txt);font-weight:800;font-size:.84rem;cursor:pointer;transition:.18s cubic-bezier(.16,1,.3,1);text-decoration:none;white-space:nowrap;min-height:38px}
.btn:hover{transform:translateY(-1px);box-shadow:var(--shadow)}
.btn-primary{background:var(--acc);color:#fff;border-color:color-mix(in srgb, var(--acc) 84%, black 16%)}
.btn-icon{display:none;width:40px;height:40px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--txt);font-size:18px;cursor:pointer;flex:0 0 40px;align-items:center;justify-content:center}
.layout{display:grid;grid-template-columns:var(--side-w) minmax(0,1fr);min-height:calc(100vh - var(--top-h))}
.side{border-left:1px solid var(--line);background:#fff;padding:12px;position:sticky;top:var(--top-h);height:calc(100vh - var(--top-h));overflow-y:auto;-webkit-overflow-scrolling:touch}
.main{padding:20px 16px;max-width:960px;margin:0 auto;width:100%;min-width:0}
.group{margin-bottom:18px}
.group b{display:block;color:var(--mut);font-size:.76rem;margin:10px 6px 6px}
.link{display:flex;gap:8px;align-items:center;padding:9px 10px;border-radius:10px;color:var(--txt);font-weight:700;font-size:.86rem;border:1px solid transparent;text-decoration:none;line-height:1.55}
.link .icon{width:20px;text-align:center;flex:0 0 20px}
.link:hover{background:#f1f5f9;border-color:var(--line);text-decoration:none}
.link.active{background:rgba(139,92,246,.10);color:#5b21b6;border-color:rgba(139,92,246,.22)}
.hero{background:linear-gradient(135deg, rgba(139,92,246,.10), rgba(16,185,129,.08), #fff);border:1px solid var(--line);border-radius:18px;padding:18px 14px;margin-bottom:18px;overflow:hidden}
.hero h1{margin:8px 0;font-size:clamp(1.35rem, 4vw, 2rem);line-height:1.35;font-weight:1000;overflow-wrap:anywhere}
.card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px 14px;box-shadow:var(--shadow);margin-bottom:16px;scroll-margin-top:calc(var(--top-h) + 16px);overflow:hidden;min-width:0}
.card h2{margin:0 0 10px;font-size:clamp(1.02rem, 2.5vw, 1.2rem);font-weight:1000;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.card h3{margin:14px 0 8px;font-size:clamp(.92rem, 2vw, 1rem);font-weight:900}
.mut{color:var(--mut);overflow-wrap:anywhere}
.kbd{display:inline-block;padding:2px 7px;border:1px solid var(--line);border-bottom-width:2px;border-radius:7px;background:#fff;font-family:monospace;font-size:.78rem;direction:ltr;word-break:break-all}
.table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:12px;margin:10px 0;-webkit-overflow-scrolling:touch}
.table{width:100%;border-collapse:collapse;font-size:.82rem;min-width:560px}
.table th{text-align:right;padding:8px 10px;background:#f1f5f9;color:var(--mut);font-weight:900;border-bottom:1px solid var(--line);white-space:nowrap;font-size:.76rem}
.table td{padding:8px 10px;border-bottom:1px solid var(--line);vertical-align:top;font-size:.82rem}
.alert{padding:12px 14px;border-radius:12px;border:1px solid;margin:12px 0;line-height:1.9;font-size:.88rem;overflow-wrap:anywhere}
.alert.ok{background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.2);color:#065f46}
.alert.info{background:rgba(139,92,246,.08);border-color:rgba(139,92,246,.2);color:#5b21b6}
.alert.warn{background:rgba(245,158,11,.09);border-color:rgba(245,158,11,.22);color:#92400e}
.grid2{display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:12px}
.checklist{list-style:none;padding:0;margin:8px 0}
.checklist li{display:flex;gap:8px;align-items:flex-start;padding:8px 10px;border:1px solid var(--line);border-radius:10px;margin-bottom:8px;background:#fff;overflow-wrap:anywhere}
.checklist li i{width:22px;height:22px;border-radius:6px;border:1px solid var(--line);display:grid;place-items:center;flex:0 0 22px;font-style:normal}
.card img{width:100%;height:auto;border-radius:12px;border:1px solid var(--line);margin:12px 0;display:block;background:#fff;object-fit:cover}
.progress{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin:10px 0}
.progress div{height:100%;background:var(--acc);width:0%;transition:.4s}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(4px);z-index:45;opacity:0;transition:.22s}
.sidebar-overlay.show{display:block;opacity:1}
@@media(max-width:900px){
  .top{padding:8px 10px;gap:6px}
  .top > div[style*="max-width:320px"]{display:none}
  .btn-icon{display:inline-flex}
  .layout{grid-template-columns:1fr}
  .side{
    position:fixed; top:var(--top-h); right:-100%; left:auto; bottom:0; width:min(320px, 86vw);
    height:calc(100vh - var(--top-h)); z-index:46; border-left:0; border-right:1px solid var(--line);
    border-radius:18px 0 0 18px; box-shadow:0 18px 56px rgba(15,23,42,.18);
    transition:right .28s cubic-bezier(.16,1,.3,1); padding:12px 10px 80px;
  }
  .side.open{right:0}
  .main{padding:14px 10px; max-width:100%}
  .hero{padding:14px 12px; border-radius:14px}
  .card{padding:14px 10px; border-radius:14px}
  .grid2{grid-template-columns:1fr; gap:10px}
  .table{min-width:0; font-size:.8rem}
  .table thead{display:none}
  .table tbody, .table tr, .table td{display:block; width:100%}
  .table tr{background:#f8fafc; border:1px solid var(--line); border-radius:12px; padding:8px; margin-bottom:10px}
  .table td{border:0 !important; display:flex; justify-content:space-between; gap:8px; padding:8px 4px !important; text-align:left !important; direction:rtl}
  .table td::before{content:attr(data-label); font-weight:900; color:var(--mut); text-align:right; min-width:90px; max-width:45%; font-size:.76rem; flex:0 0 auto}
}
@@media(max-width:480px){
  .top{padding:6px 8px}
  .brand{font-size:.9rem}
  .mark{width:30px;height:30px;font-size:13px}
  .hero h1{font-size:1.25rem}
  .card h2{font-size:1.02rem}
  .btn{padding:8px 10px; font-size:.8rem; min-height:36px}
}
</style>
</head>
<body>
<header class="top">
<button class="btn-icon" id="mobileMenuBtn" aria-label="منو" onclick="toggleSide()">☰</button>
<a class="brand" href="/"><span class="mark">✦</span> آموزش باشگاه</a>
<div style="flex:1; max-width:320px"><div class="progress"><div id="progressBar"></div></div><small class="mut" id="progressText">۰٪ تکمیل</small></div>
<div style="display:flex;gap:6px;align-items:center">
<a class="btn" href="/amoozesh">پنل</a>
<a class="btn" href="/docs">راهنما</a>
<a class="btn btn-primary" href="/club/login">باشگاه</a>
</div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSide()"></div>

<div class="layout">
<aside class="side" id="side">
<div class="group"><b>فهرست آموزش باشگاه — تمام قسمت‌ها</b>
<a class="link active" href="#c0">۰. باشگاه چیست و چطور کار می‌کند</a>
<a class="link" href="#c1">۱. ورود و ثبت‌نام با رمز یکبار مصرف شیشه‌ای</a>
<a class="link" href="#c2">۲. پیشخوان باشگاه — امتیاز و کیف پول</a>
<a class="link" href="#c3">۳. سطوح مشتری — برنزی، نقره‌ای، طلایی</a>
<a class="link" href="#c4">۴. قوانین امتیاز — چطور امتیاز بدهیم</a>
<a class="link" href="#c5">۵. کیف پول — شارژ و برداشت</a>
<a class="link" href="#c6">۶. پاداش و کوپن — ساخت و مدیریت</a>
<a class="link" href="#c7">۷. ماموریت — شرط و پاداش</a>
<a class="link" href="#c8">۸. گردونه شانس — جایزه و شانس</a>
<a class="link" href="#c9">۹. سفارش‌های باشگاه</a>
<a class="link" href="#c10">۱۰. تراکنش‌های امتیاز و کیف پول</a>
<a class="link" href="#m11">۱۱. معرفی دوستان — لینک اختصاصی</a>
<a class="link" href="#m12">۱۲. جدول امتیازی و لیگ</a>
<a class="link" href="#m13">۱۳. سفر مشتری در باشگاه</a>
<a class="link" href="#m14">۱۴. پشتیبانی باشگاه و تیکت</a>
<a class="link" href="#m15">۱۵. پروفایل و رشد و ماموریت‌های من</a>
<a class="link" href="#m16">۱۶. تنظیمات باشگاه در پنل مدیریت</a>
</div>
</aside>

<main class="main">

<div class="hero">
<h1>آموزش کامل باشگاه مشتریان — از ثبت‌نام تا گردونه و جدول امتیازی</h1>
<p class="mut">این صفحه تمام قسمت‌های باشگاه را جزء به جزء، با مسیر دقیق، فیلدهای فرم، دکمه‌ها، تصویر واقعی صفحه داخلی، نکته و چک‌لیست توضیح می‌دهد. بعد از خواندن این راهنما می‌توانی باشگاه را کامل مدیریت کنی، سطح بسازی، قانون امتیاز بگذاری، ماموریت و گردونه بسازی و گزارش بگیری.</p>
</div>

<div class="card" id="c0"><h2>۰. باشگاه چیست و چطور کار می‌کند</h2>
<p>باشگاه مشتریان یک پنل جدا با آدرس <span class="kbd">/club/login</span> است که مشتری با موبایل و رمز یکبار مصرف وارد می‌شود. داخل باشگاه امتیازش، کیف پولش، کوپن‌هایش، ماموریت‌هایش، گردونه شانس، سفارش‌هایش، تراکنش‌هایش، سفرش و معرفی دوستانش را می‌بیند. هر خرید در سامانه اصلی امتیاز می‌دهد، کیف پول را شارژ می‌کند و سطح را به‌روز می‌کند.</p>
<div class="grid2">
<div class="alert info"><b>جریان امتیاز:</b><br>سفارش کامل می‌شود → قانون امتیاز چک می‌شود → اگر بازگشت بعد از چهل و پنج روز باشد، امتیاز دو برابر → تراکنش امتیاز ثبت می‌شود → مجموع امتیاز → سطح به‌روز می‌شود → ماموریت‌ها چک می‌شود → اگر شرط ماموریت کامل شد، پاداش ماموریت داده می‌شود.</div>
<div class="alert ok"><b>جریان کیف پول:</b><br>سفارش یا معرفی یا ماموریت → تراکنش کیف پول بستانکار → موجودی کیف پول بالا می‌رود → مشتری می‌تواند در خرید بعدی یا در باشگاه خرج کند.</div>
</div>
</div>

<div class="card" id="c1"><h2>۱. ورود و ثبت‌نام با رمز یکبار مصرف شیشه‌ای شش کادری</h2>
<h3>مسیر مشتری:</h3>
<ol>
<li>رفتن به <span class="kbd">/club/login</span> — صفحه ورود باشگاه</li>
<li>وارد کردن شماره موبایل — دکمه ارسال کد</li>
<li>پاپ‌آپ شیشه‌ای باز می‌شود: پشت تار، کارت شفاف، شش کادر خالی، تایمر دایره‌ای شصت ثانیه‌ای کنار کادرها</li>
<li>پیامک با رمز شش رقمی می‌آید — رمز خودکار در شش کادر جاگذاری می‌شود (تشخیص خودکار) یا دستی تایپ می‌کنی — با هر تایپ، فوکوس به کادر بعدی می‌رود</li>
<li>بعد از شش رقم، خودکار ارسال می‌شود و انیمیشن موفقیت می‌آید و وارد پیشخوان باشگاه می‌شوی</li>
<li>اگر رمز نیامد، بعد از پایان تایمر دکمه ارسال دوباره فعال می‌شود</li>
</ol>
<h3>مسیر ثبت‌نام:</h3>
<ol>
<li><span class="kbd">/club/register</span> — فرم نام، نام خانوادگی، موبایل، ایمیل اختیاری، رمز عبور اختیاری</li>
<li>بعد از پر کردن، به صفحه تایید رمز پیامکی و ایمیل همزمان می‌روی — <span class="kbd">/register/verify</span> — شش کادر برای پیامک و در صورت وارد کردن ایمیل، فیلد کد ایمیل هم</li>
<li>هر دو کد را وارد می‌کنی — ثبت‌نام کامل می‌شود</li>
</ol>
<picture><source srcset="/img/real-club-auth-1404.webp" type="image/webp"><img src="/img/real-club-auth-1404.jpg" alt="ورود شیشه‌ای باشگاه"></picture>
<h3>تنظیم در پنل مدیریت:</h3>
<p>مسیر: <span class="kbd">تنظیمات → پیامک</span> — ارائه‌دهنده پیامک با روش تایید، کلید، شماره فرستنده، شناسه قالب تایید مثل ۹۶۹۹۷۵. هر دو روش کلید با پیشوند و بدون پیشوند خوانده می‌شود.</p>
<ul class="checklist"><li><i>☐</i> ورود با موبایل خودت تست شد — پاپ‌آپ شیشه‌ای باز شد</li><li><i>☐</i> رمز خودکار از پیامک جاگذاری شد</li></ul>
</div>

<div class="card" id="c2"><h2>۲. پیشخوان باشگاه — امتیاز و کیف پول در یک نگاه</h2>
<p>مسیر بعد از ورود: <span class="kbd">/club</span> — پیشخوان</p>
<h3>چه می‌بینی:</h3>
<ul>
<li>بالا: نام و نام خانوادگی، سطح فعلی با رنگ (برنزی، نقره‌ای، طلایی)، امتیاز کل، موجودی کیف پول به تومان و معادل دلاری</li>
<li>چهار کاشی میانبر: پاداش‌ها، ماموریت‌های فعال، تراکنش‌ها، تخفیف‌ها — هر کدام با آیکون و تعداد فعال و وضعیت</li>
<li>بخش ماموریت‌های فعال: هر ماموریت با نوار پیشرفت — مثلا خرید از دیجی‌کالا دو از دو — تیک سبز و پاداش دویست و پنجاه امتیاز</li>
<li>بخش دعوت دوست: پیشرفت دعوت یک از دو و پاداش ششصد امتیاز</li>
<li>بخش پاداش‌های آماده دریافت: مثلا بیست درصد تخفیف اسنپ صد امتیاز، قهوه رایگان هشتصد امتیاز — دکمه دریافت</li>
<li>منوی پایین: خانه، باشگاه من، کیف پول، پروفایل</li>
</ul>
<h3>تنظیم:</h3><p>پیشخوان خودکار است — امتیاز و کیف پول از تراکنش‌ها می‌آید. نیاز به تنظیم دستی ندارد.</p>
</div>

<div class="card" id="c3"><h2>۳. سطوح مشتری — برنزی، نقره‌ای، طلایی و بالاتر</h2>
<p>مسیر مدیریت: <span class="kbd">باشگاه مشتریان → سطوح و قوانین → تب سطوح</span> — <span class="kbd">/app/loyalty/settings</span></p>
<h3>ساخت سطح جدید — فیلد به فیلد:</h3>
<ol>
<li>دکمه افزودن سطح</li>
<li>نام سطح: مثلا برنزی، نقره‌ای، طلایی، پلاتینیوم — فارسی عامیانه</li>
<li>حداقل امتیاز برای رسیدن به این سطح: مثلا برنزی صفر، نقره‌ای هزار، طلایی پنج هزار</li>
<li>رنگ سطح: انتخاب رنگ — در پروفایل و پیشخوان با همان رنگ دیده می‌شود</li>
<li>توضیح: این سطح چه مزیتی دارد — مثلا طلایی‌ها ده درصد تخفیف بیشتر</li>
<li>ذخیره — سطح ساخته می‌شود و مشتری‌ها خودکار بر اساس مجموع امتیاز در سطح مناسب قرار می‌گیرند</li>
</ol>
<div class="table-wrap"><table class="table"><thead><tr><th>نام سطح</th><th>حداقل امتیاز</th><th>رنگ</th><th>مزیت نمونه</th></tr></thead>
<tbody>
<tr><td>برنزی</td><td>۰</td><td>قهوه‌ای</td><td>شروع باشگاه — یک برابر امتیاز</td></tr>
<tr><td>نقره‌ای</td><td>۱,۰۰۰</td><td>نقره‌ای</td><td>پنج درصد تخفیف بیشتر در گردونه</td></tr>
<tr><td>طلایی</td><td>۵,۰۰۰</td><td>طلایی</td><td>ده درصد تخفیف و دو برابر شانس گردونه</td></tr>
</tbody></table></div>
<ul class="checklist"><li><i>☐</i> سه سطح برنزی، نقره‌ای، طلایی ساخته شد</li><li><i>☐</i> یک مشتری آزمایشی با پنج هزار امتیاز — چک شد طلایی شد</li></ul>
</div>

<div class="card" id="c4"><h2>۴. قوانین امتیاز — چطور به مشتری امتیاز بدهیم</h2>
<p>مسیر مدیریت: <span class="kbd">باشگاه → سطوح و قوانین → تب قوانین امتیاز</span></p>
<h3>ساخت قانون جدید:</h3>
<ol>
<li>دکمه افزودن قانون</li>
<li>عنوان قانون: مثلا خرید اول، خرید بالای پانصد هزار تومان</li>
<li>شرط: نوع شرط — مبلغ خرید حداقل، تعداد خرید، عضویت، معرفی موفق</li>
<li>مقدار شرط: مثلا پانصد هزار تومان</li>
<li>مقدار امتیاز: مثلا پنجاه امتیاز</li>
<li>آیا کیف پول هم بدهد؟ اگر بله، مبلغ کیف پول — مثلا ده هزار تومان</li>
<li>آیا فعال باشد؟ تیک فعال</li>
<li>ذخیره</li>
</ol>
<h3>قوانین آماده پیشنهادی:</h3>
<div class="table-wrap"><table class="table"><thead><tr><th>عنوان</th><th>شرط</th><th>امتیاز</th><th>کیف پول</th></tr></thead>
<tbody>
<tr><td>ثبت‌نام</td><td>عضویت موفق</td><td>۱۰۰</td><td>۰</td></tr>
<tr><td>اولین خرید</td><td>اولین سفارش کامل</td><td>۲۰۰</td><td>۲۰,۰۰۰ تومان</td></tr>
<tr><td>خرید بزرگ</td><td>مبلغ خرید بالای ۵۰۰,۰۰۰ تومان</td><td>۵۰</td><td>۰</td></tr>
<tr><td>معرفی دوست</td><td>معرفی موفق و خرید دوست</td><td>۳۰۰</td><td>۵۰,۰۰۰ تومان</td></tr>
<tr><td>بازگشت بعد از دوری</td><td>خرید بعد از ۴۵ روز دوری</td><td>امتیاز قانون ×۲ خودکار</td><td>۰</td></tr>
</tbody></table></div>
<div class="alert ok"><b>نکته طلایی:</b> اگر خرید بازگشت باشد (بعد از چهل و پنج روز دوری)، سیستم خودکار امتیاز این قانون را دو برابر حساب می‌کند — نیاز به قانون جدا نیست.</div>
</div>

<div class="card" id="c5"><h2>۵. کیف پول — شارژ و برداشت و خرج</h2>
<p>مسیر مدیریت: فهرست مشتری → پروفایل → تب تراکنش‌ها و کیف پول — و مسیر باشگاه: کیف پول مشتری در پیشخوان باشگاه</p>
<h3>چطور کیف پول شارژ می‌شود:</h3>
<ul>
<li>از قانون امتیاز که کیف پول دارد</li>
<li>از معرفی موفق دوست</li>
<li>از ماموریت که پاداش کیف پول دارد</li>
<li>از شارژ دستی توسط مدیر در پروفایل مشتری → دکمه شارژ کیف پول → مبلغ و علت</li>
</ul>
<h3>چطور خرج می‌شود:</h3>
<ul>
<li>در صندوق فروش سریع: بخش وفاداری → استفاده از کیف پول → مبلغ را وارد کن</li>
<li>در باشگاه: مشتری می‌تواند در صفحه تسویه سفارش جدید، تیک استفاده از کیف پول بزند</li>
</ul>
</div>

<div class="card" id="c6"><h2>۶. پاداش و کوپن — ساخت و مدیریت</h2>
<p>مسیر مدیریت: <span class="kbd">باشگاه → گزارش‌ها و پاداش‌ها → تب پاداش</span> — <span class="kbd">/app/loyalty/reports</span> یا <span class="kbd">کمپین‌ها → پاداش‌ها</span></p>
<h3>ساخت پاداش جدید:</h3>
<ol>
<li>دکمه افزودن پاداش</li>
<li>عنوان: مثلا ده درصد تخفیف اسنپ، قهوه رایگان</li>
<li>توضیح: این پاداش چیست و چطور استفاده می‌شود</li>
<li>نوع پاداش: کوپن تخفیف، کیف پول، محصول فیزیکی، امتیاز</li>
<li>مقدار: اگر کوپن درصد، مثلا ۲۰٪ — اگر مبلغ ثابت، مثلا ۵۰,۰۰۰ تومان</li>
<li>هزینه امتیازی برای دریافت: مثلا ۱۰۰ امتیاز برای اسنپ، ۸۰۰ امتیاز برای قهوه</li>
<li>تعداد موجود: مثلا ۱۰۰ عدد</li>
<li>تاریخ انقضا: تا کی قابل دریافت است</li>
<li>ذخیره — پاداش در باشگاه در بخش پاداش‌های آماده دیده می‌شود</li>
</ol>
<h3>مدیریت کوپن:</h3>
<p>هر پاداش از نوع کوپن یک کد تخفیف یکتا می‌سازد که مشتری بعد از پرداخت امتیاز، کد را می‌بیند و می‌تواند کپی کند و در خرید بعدی استفاده کند. لیست کدها در گزارش پاداش‌ها قابل دیدن است.</p>
</div>

<div class="card" id="c7"><h2>۷. ماموریت — شرط و پاداش قدم به قدم</h2>
<p>مسیر مدیریت: <span class="kbd">باشگاه → کمپین‌ها و ماموریت‌ها</span> — <span class="kbd">/app/loyalty/campaigns</span> تب ماموریت</p>
<h3>ساخت ماموریت جدید:</h3>
<ol>
<li>دکمه افزودن ماموریت</li>
<li>عنوان: مثلا خرید از دیجی‌کالا، دعوت دوست</li>
<li>توضیح: کاربر باید چه کند — فارسی عامیانه</li>
<li>نوع شرط: تعداد خرید، مبلغ خرید، معرفی دوست، تکمیل پروفایل، اولین خرید</li>
<li>مقدار شرط: مثلا ۲ بار خرید یا ۲ نفر معرفی</li>
<li>پاداش: امتیاز — مثلا ۲۵۰ امتیاز، و/یا کیف پول — مثلا ۲۰,۰۰۰ تومان</li>
<li>تاریخ شروع و پایان: از کی تا کی فعال باشد</li>
<li>آیا فعال باشد: تیک فعال</li>
<li>ذخیره — ماموریت در باشگاه در بخش ماموریت‌های فعال با نوار پیشرفت دیده می‌شود — مثلا ۱ از ۲</li>
</ol>
<h3>مثال‌های آماده:</h3>
<div class="table-wrap"><table class="table"><thead><tr><th>عنوان ماموریت</th><th>شرط</th><th>پاداش</th></tr></thead>
<tbody>
<tr><td>خرید از دسته لبنیات</td><td>۲ بار خرید از دسته لبنیات</td><td>۲۵۰ امتیاز</td></tr>
<tr><td>دعوت دوست</td><td>۲ معرفی موفق</td><td>۶۰۰ امتیاز</td></tr>
<tr><td>تکمیل پروفایل</td><td>نام، تاریخ تولد، آدرس کامل</td><td>۱۰۰ امتیاز + ۱۰,۰۰۰ تومان کیف پول</td></tr>
</tbody></table></div>
</div>

<div class="card" id="c8"><h2>۸. گردونه شانس — جایزه‌ها، شانس، تاریخچه چرخش</h2>
<p>مسیر مدیریت: <span class="kbd">باشگاه → گردونه شانس</span> — <span class="kbd">/app/loyalty/wheel</span></p>
<h3>ساخت جایزه گردونه:</h3>
<ol>
<li>دکمه افزودن جایزه</li>
<li>عنوان جایزه: مثلا ده درصد تخفیف، بیست هزار تومان کیف پول، صد امتیاز، شانس دوباره، پوچ</li>
<li>نوع جایزه: کوپن، کیف پول، امتیاز، شانس دوباره، پوچ</li>
<li>مقدار: بسته به نوع</li>
<li>احتمال برنده شدن به درصد: مثلا ده درصد تخفیف ۳۰٪، کیف پول ۱۰٪، پوچ ۲۰٪ — جمع همه باید صد شود</li>
<li>رنگ جایزه در گردونه</li>
<li>تعداد موجود و تاریخ انقضا</li>
<li>ذخیره</li>
</ol>
<h3>تنظیم گردونه:</h3>
<ul>
<li>هزینه هر چرخش به امتیاز: مثلا ۱۰۰ امتیاز برای هر چرخش</li>
<li>چند بار در روز می‌تواند بچرخاند: مثلا ۱ بار</li>
<li>آیا فقط برای سطح طلایی باشد؟ تیک</li>
</ul>
<h3>دیدن تاریخچه چرخش:</h3>
<p>پایین صفحه گردونه، جدول تاریخچه چرخش‌ها با نام مشتری، جایزه برنده شده، تاریخ شمسی، وضعیت تحویل.</p>
</div>

<div class="card" id="c9"><h2>۹. سفارش‌های باشگاه</h2>
<p>مسیر باشگاه: <span class="kbd">سفارش‌های من</span> — <span class="kbd">/club/orders</span> — مشتری تمام سفارش‌هایش را با وضعیت رنگی می‌بیند: در انتظار پرداخت، در حال انجام، کامل، لغو شده.</p>
<p>مسیر مدیریت: <span class="kbd">سفارش‌ها</span> — <span class="kbd">/app/orders</span> — فیلتر بر اساس مشتری باشگاهی.</p>
</div>

<div class="card" id="m10"><h2>۱۰. تراکنش‌های امتیاز و کیف پول</h2>
<p>مسیر باشگاه: <span class="kbd">تراکنش‌ها</span> — <span class="kbd">/club/transactions</span> — مشتری تمام تراکنش‌هایش را می‌بیند: تاریخ شمسی، نوع (افزایش/کاهش)، مقدار، علت (خرید، معرفی، ماموریت، گردونه، شارژ دستی)، موجودی بعد از تراکنش.</p>
<p>مسیر مدیریت: پروفایل مشتری → تب تراکنش‌ها — با فیلتر نوع و تاریخ.</p>
</div>

<div class="card" id="m11"><h2>۱۱. معرفی دوستان — لینک اختصاصی و پاداش دو طرفه</h2>
<p>مسیر باشگاه: <span class="kbd">معرفی دوستان</span> — <span class="kbd">/club/referrals</span></p>
<ol>
<li>هر عضو یک لینک اختصاصی دارد مثل <span class="kbd">/club/register?ref=MY-8A2X</span> و کد کوتاه.</li>
<li>دکمه کپی لینک و دکمه اشتراک در واتساپ، تلگرام.</li>
<li>جدول معرفی‌های من: نام دوست معرفی شده، تاریخ ثبت‌نام، آیا خرید کرده یا نه، وضعیت پاداش (در انتظار خرید دوست، پرداخت شده)</li>
<li>وقتی دوست با لینک ثبت‌نام کند و اولین خریدش را کامل کند، هر دو امتیاز و کیف پول می‌گیرند — مقدار از قانون معرفی در بخش قوانین امتیاز می‌آید.</li>
</ol>
<p>مسیر مدیریت: <span class="kbd">باشگاه → گزارش‌ها → ارجاع‌ها</span> — فهرست همه معرفی‌ها با فیلتر.</p>
</div>

<div class="card" id="m12"><h2>۱۲. جدول امتیازی و لیگ — رقابت سالم</h2>
<p>مسیر باشگاه: <span class="kbd">جدول امتیازی</span> و <span class="kbd">لیگ</span> — <span class="kbd">/club/leaderboard</span> و <span class="kbd">/club/league</span></p>
<ul>
<li>جدول امتیازی: فهرست مشتریان به ترتیب امتیاز کل — سه نفر اول با سکو (طلایی، نقره‌ای، برنزی) و بقیه فهرست با رتبه، نام، امتیاز، سطح.</li>
<li>لیگ: لیگ هفتگی یا ماهانه — امتیاز فقط در بازه لیگ حساب می‌شود — جایزه برای سه نفر اول لیگ.</li>
<li>لیگ کمپینی: لیگ ویژه یک کمپین خاص مثل کمپین یلدا — فقط امتیازهای آن کمپین.</li>
</ul>
</div>

<div class="card" id="m13"><h2>۱۳. سفر مشتری در باشگاه</h2>
<p>مسیر باشگاه: <span class="kbd">سفر من</span> — <span class="kbd">/club/journey</span></p>
<p>نمودار خطی مراحل سفر: عضویت → اولین خرید → دومین خرید → وفادار → قهرمان. هر مرحله با تیک سبز اگر انجام شده، و مرحله بعدی با دکمه اقدام. مثلا اگر هنوز اولین خرید نکرده، دکمه «رفتن به فروشگاه» می‌آید.</p>
<p>مسیر مدیریت: <span class="kbd">سفر مشتری</span> — <span class="kbd">/app/journeys</span> — ایجاد سفر جدید با مراحل و شرط هر مرحله.</p>
</div>

<div class="card" id="m14"><h2>۱۴. پشتیبانی باشگاه و تیکت</h2>
<p>مسیر باشگاه: <span class="kbd">پشتیبانی</span> — <span class="kbd">/club/support</span> — مشتری تیکت جدید با موضوع و بخش و متن و فایل پیوست می‌سازد و پاسخ‌ها را می‌بیند.</p>
<p>مسیر مدیریت: <span class="kbd">پشتیبانی و دانش → درخواست‌ها</span> — <span class="kbd">/app/tickets</span> — پاسخ، تغییر وضعیت، پاسخ آماده.</p>
</div>

<div class="card" id="m15"><h2>۱۵. پروفایل و رشد و ماموریت‌های من</h2>
<p>مسیر باشگاه: <span class="kbd">پروفایل من</span> — <span class="kbd">/club/profile</span> — ویرایش نام، تاریخ تولد شمسی، آدرس‌ها. تب رشد: امتیاز این ماه، امتیاز کل، رتبه در جدول، پیشرفت تا سطح بعدی با نوار. تب ماموریت‌های من: ماموریت‌های فعال با نوار پیشرفت و دکمه اقدام.</p>
</div>

<div class="card" id="m16"><h2>۱۶. تنظیمات باشگاه در پنل مدیریت — جمع‌بندی</h2>
<p>مسیر: <span class="kbd">باشگاه مشتریان → سطوح و قوانین</span> — <span class="kbd">/app/loyalty/settings</span></p>
<h3>چک‌لیست نهایی باشگاه:</h3>
<ul class="checklist">
<li><i>☐</i> سه سطح برنزی، نقره‌ای، طلایی با حداقل امتیاز و رنگ ساخته شد</li>
<li><i>☐</i> پنج قانون امتیاز: ثبت‌نام، اولین خرید، خرید بزرگ، معرفی دوست، بازگشت بعد از دوری — قانون بازگشت دو برابر خودکار است</li>
<li><i>☐</i> سه پاداش: ده درصد تخفیف با صد امتیاز، قهوه رایگان با هشتصد امتیاز، بیست هزار تومان کیف پول با پانصد امتیاز</li>
<li><i>☐</i> دو ماموریت فعال: خرید از دسته لبنیات دو بار و دعوت دو دوست</li>
<li><i>☐</i> گردونه با پنج جایزه و شانس‌ها جمع صد درصد، هزینه هر چرخش صد امتیاز، روزی یک بار</li>
<li><i>☐</i> تست کامل با یک مشتری آزمایشی: ثبت‌نام → ورود شیشه‌ای → پیشخوان امتیاز و کیف پول → ماموریت → گردونه → معرفی دوست → خرید و گرفتن امتیاز دو برابر بازگشت</li>
</ul>
<div class="alert ok">بعد از این چک‌لیست، باشگاه آماده است. مشتری می‌تواند از <span class="kbd">/club/login</span> وارد شود و تمام بخش‌های بالا را ببیند.</div>
</div>

<div class="card" style="text-align:center">
<h2>آموزش باشگاه تمام شد</h2>
<p class="mut">حالا می‌توانی باشگاه را کامل مدیریت کنی. برای دیدن آموزش پنل اصلی (مشتری، سفارش، صندوق، بازگشت، گزارشات) به صفحه آموزش پنل برو.</p>
<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:12px">
<a class="btn btn-primary" href="/amoozesh">رفتن به آموزش پنل</a>
<a class="btn" href="/club">رفتن به باشگاه</a>
<a class="btn" href="/app/loyalty">مدیریت باشگاه در پنل</a>
</div>
</div>

</main>
</div>

<script>
var navLinks=document.querySelectorAll('.side .link');
var cards=document.querySelectorAll('.main .card[id]');
var progressBar=document.getElementById('progressBar');
var progressText=document.getElementById('progressText');
var observer=new IntersectionObserver(function(entries){
  entries.forEach(function(entry){
    if(entry.isIntersecting){
      var id=entry.target.id;
      navLinks.forEach(function(l){ l.classList.toggle('active', l.getAttribute('href')==='#'+id); });
      history.replaceState(null,null,'#'+id);
      updateProgress();
    }
  });
},{rootMargin:'-20% 0px -70% 0px', threshold:0});
cards.forEach(function(c){ observer.observe(c); });
function updateProgress(){
  var total=cards.length;
  var seen=0;
  cards.forEach(function(c){ if(c.getBoundingClientRect().top < window.innerHeight) seen++; });
  var pct=Math.round(seen/total*100);
  if(progressBar) progressBar.style.width=pct+'%';
  if(progressText) progressText.textContent=pct+'٪ تکمیل';
}
document.addEventListener('scroll', updateProgress);
document.getElementById('side').addEventListener('click', function(e){
  var a=e.target.closest('a.link');
  if(!a) return;
  var href=a.getAttribute('href');
  if(href && href.startsWith('#')){
    e.preventDefault();
    var t=document.querySelector(href);
    if(t){ t.scrollIntoView({behavior:'smooth', block:'start'}); history.pushState(null,null,href); }
    closeSide();
  }
});
function toggleSide(){
  var sb=document.getElementById('side');
  var ov=document.getElementById('sidebarOverlay');
  if(!sb) return;
  sb.classList.toggle('open');
  if(ov) ov.classList.toggle('show', sb.classList.contains('open'));
  document.body.style.overflow = sb.classList.contains('open') ? 'hidden' : '';
}
function closeSide(){
  var sb=document.getElementById('side');
  var ov=document.getElementById('sidebarOverlay');
  if(sb) sb.classList.remove('open');
  if(ov) ov.classList.remove('show');
  document.body.style.overflow='';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeSide(); });
</script>
</body>
</html>
