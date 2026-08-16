<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تأیید کد ورود — {{ config('brand.name') }}</title>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<style>
:root{--bg:#07111f;--panel:#101c2e;--line:#2b3b52;--txt:#edf5ff;--mut:#a8b6ca;--acc:#38bdf8;--acc2:#0ea5e9;--ok:#10b981;--bad:#ef4444}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(900px 500px at 80% -10%,rgba(56,189,248,.25),transparent),radial-gradient(650px 400px at 15% 0,rgba(16,185,129,.14),transparent),var(--bg);color:var(--txt);font-family:var(--font-fa,Tahoma,sans-serif);padding:18px}
.box{width:min(420px,100%);background:rgba(16,28,46,.86);border:1px solid rgba(255,255,255,.09);border-radius:28px;padding:28px;box-shadow:0 28px 80px rgba(0,0,0,.28)}
h1{font-size:1.3rem;margin:0 0 8px}
p{color:var(--mut);font-size:.88rem;line-height:1.8;margin:0 0 12px}
label{display:block;color:var(--mut);margin:14px 0 6px;font-size:.85rem}
input{background:#0d1728;border:1px solid var(--line);color:var(--txt);border-radius:14px;padding:14px;width:100%;font-family:inherit;font-size:18px;text-align:center;letter-spacing:.5rem;direction:ltr}
.btn{background:linear-gradient(135deg,var(--acc2),var(--acc));color:#001018;border:none;border-radius:14px;padding:13px;cursor:pointer;font-family:inherit;font-weight:900;font-size:15px;width:100%;margin-top:18px}
.btn-ghost{background:transparent;border:1px solid var(--line);color:var(--mut);border-radius:14px;padding:11px;width:100%;margin-top:10px;cursor:pointer;font-family:inherit}
.err{background:rgba(239,68,68,.12);border:1px solid var(--bad);color:#fca5a5;border-radius:12px;padding:10px 12px;font-size:.85rem;margin-top:14px}
.ok{background:rgba(16,185,129,.12);border:1px solid var(--ok);color:#86efac;border-radius:12px;padding:10px 12px;font-size:.85rem;margin-top:14px}
.muted{color:var(--mut);font-size:.85rem;text-align:center;margin-top:14px;display:block}
.muted a{color:var(--acc)}
</style>
</head>
<body>
<form class="box" method="post" action="{{ url('/login/verify-code') }}">
@csrf
<h1>کد تأیید را وارد کنید</h1>
<p>کد ۶ رقمی به شماره {{ session('admin_login_phone') ? '***'.substr(session('admin_login_phone'), -4) : 'شما' }} ارسال شد. این کد تا ۵ دقیقه معتبر است.</p>

@if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
@if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

<label>کد ۶ رقمی</label>
<input name="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required autofocus placeholder="------">

<button class="btn" type="submit">تأیید و ورود به پنل</button>

<div class="muted">
  <a href="{{ url('/login') }}">بازگشت به ورود با رمز عبور</a><br><br>
  کد را دریافت نکردید؟ <br>
  <form method="post" action="{{ url('/login/send-code') }}" style="display:inline;">
    @csrf
    <input type="hidden" name="login" value="{{ old('login', session('admin_login_phone')) }}">
    <button type="submit" style="background:none;border:none;color:var(--acc);cursor:pointer;font-family:inherit;font-size:.85rem;text-decoration:underline;">ارسال مجدد کد</button>
  </form>
</div>

</form>
</body>
</html>
