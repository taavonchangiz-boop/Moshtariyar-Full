@extends('layouts.customer_portal')
@section('title','تأیید ورود')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-auth.css') }}">
<div class="club-auth-page">
    <section class="club-auth-verify-shell">
        <article class="club-auth-card">
            <header>
                <div>
                    <span>تأیید ورود امن</span>
                    <h2>کد ۶ رقمی را وارد کنید</h2>
                    <p>کد ورود به شماره <b class="ltr">{{ $phone }}</b> ارسال شده است. اگر کد را دریافت نکردید، دوباره از صفحه ورود درخواست کد بدهید.</p>
                </div>
            </header>
            <form method="post" action="{{ route('club.verify') }}" class="club-auth-form">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}">
                <div>
                    <label>کد ۶ رقمی</label>
                    <input name="code" class="club-auth-code-input" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required autofocus>
                </div>
                <button class="btn">ورود به باشگاه</button>
            </form>
            <p class="club-auth-link-row"><a href="{{ route('club.login') }}">بازگشت به ورود</a></p>
        </article>
    </section>
</div>
@endsection