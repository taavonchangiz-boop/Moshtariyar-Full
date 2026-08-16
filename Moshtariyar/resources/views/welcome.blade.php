<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
<meta name="theme-color" content="#0a1220">
<meta name="robots" content="index, follow">
@php
$brandName = config('brand.name','مشتری‌یار');
$brandTagline = config('brand.tagline','سامانه بازگشت مشتری و رشد درآمد');
$brandOwner = config('brand.owner','تیم مشتری‌یار');
$brandUrl = config('brand.url','https://ayarpro.ir');
@endphp
<title>{{ $brandName }} — {{ $brandTagline }}</title>
<meta name="description" content="{{ $brandName }}؛ تنها سامانه‌ای که می‌فهمد چه مشتریانی در حال رفتن هستند، قبل از رفتن هشدار می‌دهد، با پیشنهاد هوشمند برمی‌گرداند، همان لحظه دو برابر امتیاز می‌دهد و سود بازگشت هر مشتری و هر ماه را جداگانه و با ماه شمسی یکتا بدون تکرار حساب می‌کند.">
<link rel="canonical" href="{{ url('/') }}">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<style>
:root{--bg:#070d1a;--panel:#0f1a30;--panel2:#14213d;--line:#1f2e4e;--txt:#eef5ff;--mut:#9cb0ca;--acc:#10b981;--acc2:#0ea5e9;--shadow:0 24px 80px rgba(0,0,0,.42);--site-primary:#10b981}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:radial-gradient(900px 600px at 92% -10%, rgba(16,185,129,.22), transparent 60%),radial-gradient(800px 500px at 0% 0%, rgba(14,165,233,.18), transparent 60%),linear-gradient(180deg,#070d1a 0%,#0a1324 55%,#070d1a 100%);color:var(--txt);font-family:var(--font-fa,Tahoma,sans-serif);line-height:1.95;overflow-x:hidden}
a{color:inherit;text-decoration:none}.container{width:min(1180px, calc(100% - 32px));margin:0 auto}
.top{position:sticky;top:10px;z-index:50;margin-top:10px}
.nav{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 14px;background:rgba(12,21,40,.74);backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,.08);border-radius:20px;box-shadow:0 12px 40px rgba(0,0,0,.24)}
.brand{display:flex;align-items:center;gap:10px;font-weight:1000;color:#fff}
.brand-mark{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:var(--site-primary);color:#fff;font-weight:1000}
.links{display:flex;gap:18px;color:var(--mut);font-size:.9rem;font-weight:700}.links a:hover{color:#fff}
.actions{display:flex;gap:8px;align-items:center}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 18px;border-radius:12px;border:1px solid transparent;font-weight:800;cursor:pointer;transition:.18s;white-space:nowrap}
.btn:hover{transform:translateY(-1px)}.btn:active{transform:translateY(0) scale(.985)}
.btn-primary{background:var(--site-primary);color:#fff;border-color:color-mix(in srgb, var(--site-primary) 84%, black 16%);box-shadow:0 0 0 1px rgba(255,255,255,.08) inset, 0 6px 18px rgba(16,185,129,.24)}
.btn-ghost{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.10);color:var(--txt)}
.eyebrow{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;background:linear-gradient(135deg, rgba(16,185,129,.18), rgba(14,165,233,.14));border:1px solid rgba(16,185,129,.26);color:#6ee7b7;font-size:.8rem;font-weight:900}
.hero{padding:72px 0 44px}.hero-grid{display:grid;grid-template-columns:1.06fr .94fr;gap:32px;align-items:center}
.hero h1{font-size:clamp(2.1rem, 5vw, 3.6rem);line-height:1.25;margin:14px 0;letter-spacing:-.03em;font-weight:1000}
.hero h1 span.grad{background:linear-gradient(135deg, #fff 10%, #6ee7b7 50%, #7dd3fc 90%);-webkit-background-clip:text;background-clip:text;color:transparent}
.lead{font-size:1.08rem;color:var(--mut);max-width:680px;line-height:2.15}
.hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
.hero-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:24px}
.hstat{background:rgba(15,26,48,.72);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:12px}
.hstat b{display:block;color:#fff;font-size:1.02rem}.hstat small{color:var(--mut);font-size:.8rem}
.mock{position:relative;background:linear-gradient(180deg, rgba(15,26,48,.92), rgba(10,19,36,.92));border:1px solid rgba(255,255,255,.10);border-radius:28px;padding:14px;box-shadow:0 24px 80px rgba(0,0,0,.42);overflow:hidden}
.mock img{width:100%;height:auto;border-radius:18px;display:block}
.mock-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.dots{display:flex;gap:6px}.dots i{width:9px;height:9px;border-radius:50%;background:var(--line)}
.float-chip{position:absolute;left:12px;bottom:12px;background:rgba(16,185,129,.14);border:1px solid rgba(16,185,129,.28);color:#6ee7b7;border-radius:999px;padding:6px 12px;font-size:.78rem;font-weight:800;backdrop-filter:blur(8px)}
.float-chip.warn{left:auto;right:12px;bottom:72px;background:rgba(245,158,11,.14);border-color:rgba(245,158,11,.28);color:#fde68a}
.section{padding:60px 0}.section-head{text-align:center;margin-bottom:28px}
.section-head h2{font-size:clamp(1.5rem, 3vw, 2.4rem);margin:0 0 10px;line-height:1.35;font-weight:1000}
.section-head p{color:var(--mut);max-width:780px;margin:0 auto;line-height:2.1}
.cards{display:grid;grid-template-columns:repeat(12,1fr);gap:14px}
.card{grid-column:span 4;background:rgba(15,26,48,.76);border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:20px;box-shadow:0 12px 40px rgba(0,0,0,.14);transition:.22s}
.card:hover{transform:translateY(-2px);border-color:rgba(16,185,129,.22)}
.card.big{grid-column:span 8}.card.wide{grid-column:span 12;display:grid;grid-template-columns:1.1fr .9fr;gap:18px;align-items:center}
.card .icon{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(135deg, rgba(16,185,129,.22), rgba(14,165,233,.18));border:1px solid rgba(255,255,255,.08);font-size:1.35rem;margin-bottom:12px}
.card h3{margin:0 0 8px;font-size:1.05rem;font-weight:900}.card p{margin:0;color:var(--mut);font-size:.92rem;line-height:1.95}
.card img{width:100%;height:auto;border-radius:16px;margin-top:12px;border:1px solid rgba(255,255,255,.08);background:#0a1220}
.badge-mini{display:inline-flex;padding:3px 8px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.10);font-size:.7rem;font-weight:800;color:var(--mut);margin-top:8px}
.workflow{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.step{background:rgba(15,26,48,.72);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:20px}
.step .n{width:36px;height:36px;border-radius:11px;background:var(--site-primary);color:#fff;display:grid;place-items:center;font-weight:1000;margin-bottom:12px}
.step h3{margin:0 0 6px}.step p{margin:0;color:var(--mut)}
.audience{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.aud{min-height:200px;border-radius:22px;padding:18px;background:linear-gradient(145deg, rgba(15,26,48,.95), rgba(10,19,36,.88));border:1px solid rgba(255,255,255,.08)}
.aud b{display:block;font-size:1.02rem;margin-bottom:10px}.aud ul{padding:0 16px 0 0;margin:0;color:var(--mut);font-size:.9rem}.aud li{margin:6px 0}
.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:14px}
.metrics div{text-align:center}.metrics b{display:block;font-size:1.25rem;color:#fff}.metrics small{color:var(--mut)}
.cta{background:linear-gradient(135deg, rgba(16,185,129,.18), rgba(14,165,233,.16));border:1px solid rgba(110,231,183,.24);border-radius:28px;padding:34px;text-align:center;box-shadow:0 24px 80px rgba(0,0,0,.24)}
.cta h2{margin:8px 0 10px;font-size:clamp(1.4rem, 2.6vw, 2.1rem);line-height:1.35}
.footer{padding:32px 0;color:var(--mut);text-align:center;border-top:1px solid rgba(255,255,255,.06);margin-top:32px}
@media(max-width:1000px){.hero-grid,.card.wide{grid-template-columns:1fr}.card{grid-column:span 6}.card.big{grid-column:span 12}.audience{grid-template-columns:1fr 1fr}.workflow{grid-template-columns:1fr}.metrics{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.container{width:min(100% - 20px,1240px)}.links{display:none}.actions .btn-ghost{display:none}.hero{padding:28px 0 24px}.hero-actions .btn{width:100%}.hero-stats{grid-template-columns:1fr}.cards{grid-template-columns:1fr}.card,.card.big,.card.wide{grid-column:span 1}.audience{grid-template-columns:1fr}.section{padding:36px 0}}
</style>
</head>
<body>
<header class="top container">
<nav class="nav">
<a class="brand" href="/"><span class="brand-mark">✦</span><span>{{ $brandName }}</span></a>
<div class="links">
<a href="#moshkel">مشکل چیست</a>
<a href="#rahehal">راه حل</a>
<a href="#emkanat">امکانات</a>
<a href="#chetor">چطور کار می‌کند</a>
<a href="#baraye">برای چه کسانی</a>
</div>
<div class="actions">
<a class="btn btn-ghost" href="/docs">راهنما</a>
<a class="btn btn-ghost" href="/amoozesh">آموزش</a>
<a class="btn btn-primary" href="/login">ورود به سامانه</a>
</div>
</nav>
</header>

<main>
<section class="hero container">
<div class="hero-grid">
<div>
<span class="eyebrow">تنها سامانه‌ای که بازگشت مشتری را به سود قابل شمارش تبدیل می‌کند</span>
<h1>هر مشتری که برمی‌گردد،<br><span class="grad">امتیاز دو برابر و سود قابل شمارش می‌سازد</span></h1>
<p class="lead">خیلی از کسب‌وکارها فقط می‌فهمند مشتری رفته. اینجا می‌فهمی چه کسی دارد می‌رود، چرا دارد می‌رود، کی باید برگردد، با چه پیشنهادی برمی‌گردد، و بعد از بازگشت چقدر سود داده. همه با تاریخ شمسی، عدد فارسی، طراحی ساده و قابل فهم برای همه. بدون کلمه خارجی و بدون توضیح اضافه.</p>
<div class="hero-actions">
<a class="btn btn-primary" href="/login">شروع رایگان</a>
<a class="btn btn-ghost" href="/docs">دیدن راهنمای کامل</a>
<a class="btn btn-ghost" href="/amoozesh">دیدن آموزش قدم به قدم</a>
</div>
<div class="hero-stats">
<div class="hstat"><b>تشخیص خودکار کسانی که در آستانه رفتن هستند</b><small>بر اساس فاصله خرید و رفتار</small></div>
<div class="hstat"><b>چهل و پنج روز معیار بازگشت</b><small>خرید بعد از چهل و پنج روز دوری یعنی بازگشت</small></div>
<div class="hstat"><b>گزارش خودکار هر هفته</b><small>صبح به مدیر ایمیل می‌شود و در آرشیو می‌ماند</small></div>
</div>
</div>
<div class="mock">
<div class="mock-top"><b>پیش‌نمایش پیشخوان مدیریت</b><div class="dots"><i></i><i></i><i></i></div></div>
<picture>
<source srcset="{{ asset('img/real-dashboard-1404.webp') }}" type="image/webp">
<img src="{{ asset('img/real-dashboard-1404.jpg') }}" alt="پیشخوان مدیریت مشتری‌یار" loading="eager" fetchpriority="high">
</picture>
<span class="float-chip">درآمد بازگشتی همین لحظه</span>
<span class="float-chip warn">سه مشتری با ارزش در آستانه رفتن</span>
</div>
</div>
</section>

<section class="container"><div class="metrics">
<div><b>دو برابر شدن انگیزه بازگشت</b><small>با امتیاز دو برابر</small></div>
<div><b>معیار چهل و پنج روزه</b><small>مبنای علمی تشخیص بازگشت</small></div>
<div><b>نمودار ماهانه یکتا</b><small>هر ماه شمسی بدون تکرار</small></div>
<div><b>ورود با شش کادر شیشه‌ای</b><small>رمز پیامکی با تشخیص خودکار</small></div>
</div></section>

<section class="section container" id="moshkel">
<div class="section-head">
<span class="eyebrow">مشکل کسب‌وکارها چیست؟</span>
<h2>مشتری می‌رود و نمی‌فهمیم کی، چرا و چقدر ضرر کردیم</h2>
<p>بیشتر سامانه‌ها فقط فهرست مشتری نگه می‌دارند. نمی‌گویند چه کسی دارد می‌رود، نمی‌گویند با چه پیشنهادی برمی‌گردد، نمی‌گویند بعد از بازگشت چقدر سود داده. ماه‌ها در گزارش تکراری است و نمودارها با فونت ناهماهنگ و بهم ریخته است.</p>
</div>
</section>

<section class="section container" id="rahehal">
<div class="section-head">
<span class="eyebrow">راه حل ما چیست؟</span>
<h2>از رفتن تا برگشتن و سود، همه را حساب می‌کنیم</h2>
<p>سامانه هر شب فاصله خرید هر مشتری را حساب می‌کند. اگر از میانگین خودش خیلی گذشته باشد، می‌گوید در خطر است. در صندوق، همان لحظه هشدار می‌دهد. بعد از خرید دوباره، دو برابر امتیاز می‌دهد، یادداشت بازگشت می‌گذارد و مبلغ را در درآمد بازگشتی حساب می‌کند. گزارش هفتگی با نمودار ساده داخل ایمیل می‌رود و در آرشیو می‌ماند.</p>
</div>
</section>

<section class="section container" id="emkanat">
<div class="section-head">
<span class="eyebrow">امکانات اصلی</span>
<h2>همه ابزارهای لازم برای برگرداندن مشتری در یک جا</h2>
<p>نیازی به چند برنامه جدا نیست. همه چیز به هم وصل است.</p>
</div>
<div class="cards">
<div class="card big">
<div class="icon">💎</div>
<h3>ارزش طول عمر مشتری با پیش‌بینی احتمال رفتن</h3>
<p>برای هر مشتری حساب می‌کنیم چقدر تا حالا خرید کرده، میانگین سبدش چقدر است، هر چند روز یک بار می‌آید، چند روز از آخرین خریدش گذشته، احتمال رفتنش چقدر است و در یک سال آینده چقدر سود می‌دهد. مشتری‌ها خودکار به پنج دسته تقسیم می‌شوند: سالم، پایدار، در خطر، در حال ریزش، از دست رفته. جدول پنجاه مشتری با بیشترین ارزش آینده و بیست مشتری با ارزش ولی در خطر با دکمه ارسال پیام بازگشت.</p>
<picture><source srcset="{{ asset('img/real-dashboard-1404.webp') }}" type="image/webp"><img src="{{ asset('img/real-dashboard-1404.jpg') }}" alt="ارزش طول عمر"></picture>
</div>
<div class="card">
<div class="icon">🛟</div>
<h3>مرکز بازگشت و نگهداری مشتری</h3>
<p>فهرست کامل کسانی که مدت زیادی نیامده‌اند و نیاز به نگهداری دارند، با میزان خطر و احتمال رفتن و پیشنهاد کاری. نمودار کوچک و سبک که در موبایل هم درست دیده می‌شود.</p>
<picture><source srcset="{{ asset('img/real-retention-1404.webp') }}" type="image/webp"><img src="{{ asset('img/real-retention-1404.jpg') }}" alt="مرکز بازگشت"></picture>
</div>
<div class="card">
<div class="icon">💳</div>
<h3>صندوق فروش با هشدار در لحظه</h3>
<p>موقع فروش وقتی شماره مشتری را می‌زنی، اگر آن مشتری با ارزش است و مدت زیادی نیامده، همان لحظه نوار هشدار بالا می‌آید و پیشنهاد تخفیف برای نگهداری می‌دهد. با یک دکمه تخفیف اعمال می‌شود.</p>
<picture><source srcset="{{ asset('img/real-pos-1404.webp') }}" type="image/webp"><img src="{{ asset('img/real-pos-1404.jpg') }}" alt="صندوق هوشمند"></picture>
</div>
<div class="card">
<div class="icon">📈</div>
<h3>درآمد بازگشتی با ماه شمسی یکتا</h3>
<picture><source srcset="{{ asset('img/real-recovery-1404.webp') }}" type="image/webp"><img src="{{ asset('img/real-recovery-1404.jpg') }}" alt="درآمد بازگشتی"></picture>
<p>قبلا ماه‌ها تکرار می‌شد. حالا هر ماه شمسی واقعی را با تبدیل دقیق حساب می‌کنیم و برچسب یکتا بدون تکرار می‌سازیم. نمودار داخل ایمیل هم بدون عکس خارجی ساخته می‌شود تا همه جا دیده شود.</p>
</div>
<div class="card">
<div class="icon">🗂️</div>
<h3>آرشیو گزارش‌های هفتگی</h3>
<p>هر هفته صبح خودکار گزارش ساخته می‌شود و به مدیر ایمیل می‌شود و در آرشیو دائمی می‌ماند. داخل آرشیو می‌توانی با تاریخ شمسی جستجو کنی، نمودار روند را ببینی، هر گزارش را کامل باز کنی، دوباره ایمیل کنی یا خروجی بگیری.</p>
</div>
<div class="card wide">
<div>
<div class="icon">🎁</div>
<h3>باشگاه مشتریان واقعی</h3>
<p>باشگاه فقط نمایش نیست. امتیاز واقعی، کیف پول واقعی، کوپن، ماموریت با شرط خرید، گردونه شانس، جدول امتیازی، سفارش‌ها، تراکنش‌ها، سفر مشتری، معرفی دوستان. ورود با رمز پیامکی شیشه‌ای شش کادری: پشت تار، کارت شفاف، شش کادر که با تایپ می‌پرند، جاگذاری خودکار، زمان‌سنج دایره‌ای شصت ثانیه‌ای، تشخیص خودکار رمز از پیامک، ارسال خودکار بعد از شش رقم، تمام صفحه در موبایل.</p>
</div>
<picture><source srcset="{{ asset('img/real-club-auth-1404.webp') }}" type="image/webp"><img src="{{ asset('img/real-club-auth-1404.jpg') }}" alt="باشگاه مشتریان"></picture>
</div>
</div>
</section>

<section class="section container" id="chetor">
<div class="section-head">
<span class="eyebrow">گردش کار</span>
<h2>از ورود اطلاعات تا سود بازگشتی در سه قدم ساده</h2>
</div>
<div class="workflow">
<div class="step"><div class="n">۱</div><h3>اطلاعات را وارد کن</h3><p>فروشگاه اینترنتی، فایل، ثبت دستی، صندوق، باشگاه — همه به یک پایگاه مرکزی می‌رسد.</p></div>
<div class="step"><div class="n">۲</div><h3>تحلیل خودکار</h3><p>هر شب خودکار دسته‌بندی و حساب فاصله خرید و احتمال رفتن.</p></div>
<div class="step"><div class="n">۳</div><h3>بازگشت و نگهداری خودکار</h3><p>پیام بازگشت، دو برابر امتیاز، یادداشت، نمودار شمسی، ایمیل هفتگی با زمان قابل تنظیم.</p></div>
</div>
</section>

<section class="section container" id="baraye">
<div class="section-head">
<h2>این سامانه برای چه کسانی ساخته شده</h2>
</div>
<div class="audience">
<div class="aud"><b>مدیران کسب‌وکار</b><ul><li>کاهش ریزش مشتری</li><li>افزایش خرید دوباره</li><li>گزارش قابل فهم برای تصمیم</li></ul></div>
<div class="aud"><b>فروش و بازاریابی</b><ul><li>کمپین برگشت هدفمند</li><li>معرفی دوستان با پاداش</li><li>دسته‌بندی خودکار مشتریان</li></ul></div>
<div class="aud"><b>فروشگاه‌های حضوری و اینترنتی</b><ul><li>همگام‌سازی سفارش و محصول</li><li>کوپن و کیف پول مشتری</li><li>مدیریت ساده بدون پیچیدگی</li></ul></div>
<div class="aud"><b>تیم پشتیبانی</b><ul><li>تیکت حرفه‌ای</li><li>پاسخ‌های آماده</li><li>پایگاه دانش و دستیار هوشمند</li></ul></div>
</div>
</section>

<section class="section container">
<div class="cta">
<span class="eyebrow">راهنما و آموزش کامل با تصویر واقعی</span>
<h2>هر بخش با تصویر واقعی همین برنامه توضیح داده شده</h2>
<p style="color:var(--mut);max-width:720px;margin:0 auto 18px;line-height:2">تمام عکس‌های این صفحه، تصویر واقعی صفحات داخلی همین برنامه است که ساخته شده — نه عکس آماده اینترنتی. هر بخش قدم به قدم با مثال قابل فهم برای همه توضیح داده شده.</p>
<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
<a class="btn btn-primary" href="/docs">دیدن راهنمای کامل</a>
<a class="btn btn-secondary" href="/amoozesh">دیدن آموزش صفر تا صد</a>
</div>
</div>
</section>

<section class="section container">
<div class="cta" style="background:linear-gradient(135deg, rgba(16,185,129,.20), rgba(14,165,233,.18));border-color:rgba(110,231,183,.30)">
<h2>آماده‌ای هر بازگشت را به سود قابل شمارش تبدیل کنی؟</h2>
<p style="color:var(--mut)">همین الان وارد سامانه شو و ببین چه مشتریانی در آستانه رفتن هستند.</p>
<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:16px">
<a class="btn btn-primary" href="/login">ورود به سامانه</a>
<a class="btn btn-ghost" href="/amoozesh">شروع آموزش</a>
</div>
</div>
</section>

</main>
<footer class="footer container">
<p><strong>{{ $brandName }}</strong> — {{ $brandTagline }}</p>
<p>ارزش طول عمر • درآمد بازگشتی همین لحظه • آرشیو ماه شمسی یکتا • صندوق با هشدار ریزش • ورود با رمز پیامکی شیشه‌ای</p>
<p style="margin-top:8px"><a href="/docs" style="color:#7dd3fc;font-weight:800">راهنما</a> • <a href="/amoozesh" style="color:#6ee7b7;font-weight:800">آموزش</a></p>
</footer>
</body>
</html>
