@extends('layouts.customer_portal')
@section('title','تأیید ثبت‌نام - باشگاه مشتریان')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-auth.css') }}">
<div class="club-auth-page">
    <section class="club-auth-verify-shell">
        <article class="club-auth-card">
            <header>
                <div>
                    <span>تأیید ثبت‌نام</span>
                    <h2>کد تأیید را وارد کنید</h2>
                    <p>کد ۶ رقمی تأیید به شماره <b class="ltr">{{ $phone }}</b> از طریق <b>پیامک</b> و همچنین به ایمیل شما از طریق <b>ایمیل</b> همزمان ارسال شد. لطفاً هر دو را بررسی کنید. این کد تا ۵ دقیقه معتبر است.</p>
                </div>
            </header>

            @if(session('status'))
                <div class="club-auth-alert is-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="club-auth-alert is-error">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('club.register.verify') }}" class="club-auth-form">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}">
                <div>
                    <label>کد ۶ رقمی تأیید (ارسال شده به موبایل و ایمیل)</label>
                    <input name="code" class="club-auth-code-input" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required autofocus placeholder="------">
                    <small style="color:var(--mut); margin-top:6px; display:block; line-height:1.7;">پیامک را دریافت نکردید؟ پوشه اسپم ایمیل خود را هم بررسی کنید. کد به هر دو ارسال شده است.</small>
                </div>
                <button class="btn">تأیید و تکمیل ثبت‌نام</button>
            </form>

            <div class="club-auth-security">
                <b>💡 نکته</b>
                <span>کد تأیید به صورت همزمان از طریق پیامک و ایمیل ارسال شده تا حتی اگر یکی دریافت نشد، از طریق دیگری وارد شوید. این کد را با کسی به اشتراک نگذارید.</span>
            </div>

            <p class="club-auth-link-row">
                <a href="{{ route('club.register') }}">بازگشت به فرم ثبت‌نام</a> • 
                <a href="{{ route('club.login') }}">ورود به باشگاه</a>
            </p>
        </article>
    </section>
</div>
@endsection
