@extends('layouts.customer_portal')
@section('title','ثبت‌نام در باشگاه مشتریان - عضویت ۳۰ ثانیه‌ای')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-auth.css') }}">
<div class="club-auth-page">
    <section class="club-auth-shell">
        <article class="club-auth-hero">
            <span class="club-auth-eyebrow">🎉 عضویت سریع و دریافت پاداش خوش‌آمدگویی</span>
            <h1>به خانواده بزرگ مشتریان وفادار ما بپیوند - همین الان عضو شو و جایزه بگیر</h1>
            <p>فقط با وارد کردن نام، موبایل و ایمیل، عضو باشگاه مشتریان می‌شوی و بلافاصله کد تأیید ۶ رقمی را از طریق پیامک و ایمیل دریافت می‌کنی. بعد از تأیید، پنل اختصاصی‌ات با کد معرف اختصاصی، مأموریت‌های جذاب، گردونه شانس روزانه، کدهای تخفیف و امکان پیگیری سفارش‌ها و درخواست‌های پشتیبانی در اختیار توست.</p>
            <div class="club-auth-benefits">
                <div class="club-auth-mini-card" style="--mini-color:#0ea5e9;"><i>⭐</i><div><b>۱۰۰۰ امتیاز خوش‌آمدگویی</b><small>بلافاصله بعد از تأیید شماره موبایل و ایمیل</small></div></div>
                <div class="club-auth-mini-card" style="--mini-color:#10b981;"><i>🤝</i><div><b>کد معرف اختصاصی و درآمد از معرفی</b><small>با دعوت دوستان، هم خودت هم دوستت پاداش می‌گیرید</small></div></div>
                <div class="club-auth-mini-card" style="--mini-color:#f59e0b;"><i>🎡</i><div><b>گردونه شانس روزانه باشگاه</b><small>هر روز یک شانس چرخاندن و بردن جایزه نقدی و تخفیف</small></div></div>
                <div class="club-auth-mini-card" style="--mini-color:#8b5cf6;"><i>🎯</i><div><b>مأموریت‌های هوشمند و پاداش</b><small>با تکمیل پروفایل، خرید و معرفی دوستان امتیاز بگیر</small></div></div>
            </div>
            <div style="margin-top:1.2rem; display:grid; grid-template-columns: auto 1fr; gap:.6rem; align-items:center; padding:.8rem; background:rgba(16,185,129,0.10); border:1px solid rgba(16,185,129,0.18); border-radius:1rem; font-size:.84rem; line-height:1.8;">
                <span style="width:2.2rem; height:2.2rem; display:grid; place-items:center; border-radius:.7rem; background:#10b981; color:white; font-size:1.1rem;">✓</span>
                <div><b style="color:var(--txt);">تأیید دو مرحله‌ای امن:</b> <span style="color:var(--mut);">کد تأیید همزمان از طریق پیامک و ایمیل برایت ارسال می‌شود تا حتی اگر یکی را دریافت نکردی، از طریق دیگری وارد شوی. امنیت ۱۰۰٪.</span></div>
            </div>
        </article>

        <article class="club-auth-card">
            <header>
                <div>
                    <span>ساخت حساب باشگاه - ۳۰ ثانیه</span>
                    <h2>فرم عضویت سریع را پر کن</h2>
                    <p>ایمیل معتبر وارد کن چون کد تأیید هم به موبایل و هم به ایمیل ارسال می‌شود. تاریخ تولدت را هم وارد کن تا در روز تولدت سورپرایز ویژه داشته باشیم.</p>
                </div>
            </header>

            @if(session('status'))
                <div class="club-auth-alert is-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="club-auth-alert is-error">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('club.register.store') }}" class="club-auth-form">
                @csrf
                <div class="club-auth-form-grid">
                    <div><label>👤 نام و نام خانوادگی</label><input name="full_name" value="{{ old('full_name') }}" required placeholder="مثلاً سارا محمدی" autofocus></div>
                    <div><label>📱 شماره موبایل (برای دریافت کد تأیید)</label><input name="phone" class="ltr" value="{{ old('phone',$phone) }}" required placeholder="۰۹۱۲۳۴۵۶۷۸۹" inputmode="numeric"><small style="color:var(--mut); font-size:.72rem;">کد تأیید به این شماره پیامک می‌شود</small></div>
                    <div><label>📧 ایمیل معتبر (برای دریافت کد تأیید همزمان)</label><input name="email" class="ltr" type="email" value="{{ old('email') }}" required placeholder="email@example.com"><small style="color:var(--mut); font-size:.72rem;">کد تأیید به این ایمیل هم ارسال می‌شود</small></div>
                    <div><label>🎂 تاریخ تولد (اختیاری - برای هدیه تولد)</label><input name="birthday" class="jdate" value="{{ old('birthday') }}" placeholder="۱۴۰۰/۰۱/۰۱"></div>
                    <div><label>🔑 رمز عبور باشگاه (حداقل ۶ کاراکتر)</label><input name="password" type="password" required placeholder="یک رمز امن انتخاب کن"></div>
                    <div><label>🔑 تکرار رمز عبور</label><input name="password_confirmation" type="password" required placeholder="رمز عبور را دوباره وارد کن"></div>
                    <div class="club-auth-wide"><label>🤝 کد معرف (اختیاری - اگر از دوستت دعوت شدی)</label><input name="referral_code" class="ltr" value="{{ old('referral_code',$ref) }}" placeholder="مثلاً ABC123 - اگر داری وارد کن تا هر دو پاداش بگیرید"></div>
                </div>
                <button class="btn">✨ ثبت‌نام و دریافت کد تأیید (پیامک + ایمیل)</button>
                <p style="color:var(--mut); font-size:.78rem; text-align:center; line-height:1.8; margin:8px 0 0;">با ثبت‌نام، <a href="#" style="color:var(--acc);">قوانین باشگاه</a> را می‌پذیری. اطلاعات شما امن و محرمانه می‌ماند.</p>
            </form>

            <div class="club-auth-security">
                <b>🎁 بعد از تأیید چه می‌شود؟</b>
                <span>بلافاصله پنل اختصاصی شما ساخته می‌شود، ۱۰۰۰ امتیاز خوش‌آمدگویی می‌گیری، کد معرف اختصاصی‌ات فعال می‌شود و می‌تونی گردونه شانس امروز را بچرخونی. با تکمیل پروفایل هم امتیاز بیشتر می‌گیری.</span>
            </div>

            <p class="club-auth-link-row">قبلاً عضو شدی؟ <a href="{{ route('club.login') }}">🔐 ورود به باشگاه</a></p>
        </article>
    </section>
</div>
@endsection