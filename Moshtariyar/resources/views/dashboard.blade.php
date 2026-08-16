<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — سامانه مدیریت مشتریان با اتصال ووکامرس</title>
    <style>
        :root { --bg:#0f172a; --card:#1e293b; --acc:#38bdf8; --txt:#e2e8f0; --mut:#94a3b8; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Tahoma, sans-serif; background:var(--bg); color:var(--txt); }
        .wrap { max-width: 920px; margin: 0 auto; padding: 48px 20px; }
        .hero { text-align:center; padding: 40px 0; }
        .hero h1 { font-size: 2rem; margin: 0 0 12px; }
        .hero p { color: var(--mut); font-size: 1.05rem; }
        .badge { display:inline-block; background:rgba(56,189,248,.15); color:var(--acc);
                 padding:4px 12px; border-radius:999px; font-size:.85rem; margin-bottom:16px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:16px; margin-top:28px; }
        .card { background:var(--card); border:1px solid #334155; border-radius:14px; padding:20px; }
        .card h3 { margin:0 0 8px; color:var(--acc); font-size:1.05rem; }
        .card p { margin:0; color:var(--mut); font-size:.92rem; line-height:1.8; }
        code { background:#0b1220; color:#7dd3fc; padding:2px 6px; border-radius:6px; font-size:.85rem; direction:ltr; display:inline-block; }
        .foot { text-align:center; color:var(--mut); margin-top:40px; font-size:.85rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <span class="badge">سازگار با هاست اشتراکی و مناسب بازار ایران</span>
            <h1>{{ config('app.name') }}</h1>
            <p>سامانه مدیریت مشتریان با اتصال دوطرفه به ووکامرس و ابزارهای فروش، وفاداری و پشتیبانی</p>
        </div>

        <div class="grid">
            <div class="card"><h3>اتصال ووکامرس</h3><p>همگام‌سازی سفارش، مشتری و کالا به‌صورت خودکار و مطمئن.</p></div>
            <div class="card"><h3>جلوگیری از ثبت تکراری</h3><p>با نگه‌داشتن شناسه‌های اصلی، از ساخته شدن دوباره مشتری و سفارش جلوگیری می‌شود.</p></div>
            <div class="card"><h3>هماهنگ با نیازهای ایران</h3><p>درگاه پرداخت، پیامک، مالیات و موارد موردنیاز کسب‌وکارهای ایرانی در نظر گرفته شده است.</p></div>
            <div class="card"><h3>کارهای خودکار</h3><p>پیام تشکر، پیگیری مشتری و کارهای تکراری دیگر را می‌توان خودکار انجام داد.</p></div>
            <div class="card"><h3>پرونده کامل مشتری</h3><p>خریدها، ارزش خرید، میانگین خرید و سابقه ارتباط با مشتری در یک صفحه دیده می‌شود.</p></div>
            <div class="card"><h3>سازگار با هاست معمولی</h3><p>بدون نیاز به ابزارهای سنگین سروری، روی هاست‌های معمولی هم قابل استفاده است.</p></div>
        </div>

        <p class="foot">برای راه‌اندازی، فایل <code>README.md</code> را ببینید.</p>
    </div>
</body>
</html>
