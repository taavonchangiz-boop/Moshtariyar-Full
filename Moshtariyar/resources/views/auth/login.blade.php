<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود — {{ config('brand.name') }}</title>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/otp-popup.css') }}">
<style>
:root{--bg:#07111f;--panel:#101c2e;--line:#2b3b52;--txt:#edf5ff;--mut:#a8b6ca;--acc:#38bdf8;--acc2:#0ea5e9;--ok:#10b981;--bad:#ef4444}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(900px 500px at 80% -10%,rgba(56,189,248,.25),transparent),radial-gradient(650px 400px at 15% 0,rgba(16,185,129,.14),transparent),var(--bg);color:var(--txt);font-family:var(--font-fa,Tahoma,sans-serif);padding:18px}
.shell{width:min(980px,100%);display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:stretch}
.info,.box{background:rgba(16,28,46,.86);border:1px solid rgba(255,255,255,.09);border-radius:28px;padding:28px;box-shadow:0 28px 80px rgba(0,0,0,.28)}
.info{position:relative;overflow:hidden;background:linear-gradient(135deg,rgba(14,165,233,.22),rgba(16,185,129,.14)),rgba(16,28,46,.9)}
.info:before{content:'';position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(56,189,248,.22);left:-70px;bottom:-80px;filter:blur(5px)}
.logo{text-align:center;margin-bottom:18px}
.logo img{width:76px;height:76px;border-radius:22px}
.logo h1{font-size:1.45rem;margin:12px 0 4px}
.logo p,.info p{color:var(--mut);margin:0}
.badge{display:inline-flex;background:rgba(56,189,248,.15);border:1px solid rgba(56,189,248,.25);color:#9be5ff;border-radius:999px;padding:5px 12px;font-weight:800;font-size:.82rem}
.info h2{font-size:1.9rem;line-height:1.35;margin:16px 0 12px}
.features{display:grid;gap:10px;margin-top:22px}
.feat{background:rgba(7,17,31,.38);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:12px}
label{display:block;color:var(--mut);margin:14px 0 6px;font-size:.85rem}
input{background:#0d1728;border:1px solid var(--line);color:var(--txt);border-radius:14px;padding:13px;width:100%;font-family:inherit;font-size:14px}
.btn{background:linear-gradient(135deg,var(--acc2),var(--acc));color:#001018;border:none;border-radius:14px;padding:13px;cursor:pointer;font-family:inherit;font-weight:900;font-size:15px;width:100%;margin-top:18px}
.btn-ghost{background:transparent;border:1px solid var(--line);color:var(--mut);border-radius:14px;padding:12px;width:100%;margin-top:10px;cursor:pointer;font-family:inherit;font-weight:800}
.err{background:rgba(239,68,68,.12);border:1px solid var(--bad);color:#fca5a5;border-radius:12px;padding:10px 12px;font-size:.85rem;margin-top:14px}
.ok{background:rgba(16,185,129,.12);border:1px solid var(--ok);color:#86efac;border-radius:12px;padding:10px 12px;font-size:.85rem;margin-top:14px}
.remember{display:flex;align-items:center;gap:8px;margin-top:14px;color:var(--mut);font-size:.85rem}
.remember input{width:auto}
.links{text-align:center;margin-top:14px;color:var(--mut);font-size:.86rem}
.links a{color:var(--acc)}
.tabs{display:flex;gap:6px;margin-bottom:16px;background:rgba(7,17,31,.6);border:1px solid var(--line);border-radius:14px;padding:4px}
.tab-btn{flex:1;padding:10px;border-radius:10px;border:0;background:transparent;color:var(--mut);font-family:inherit;font-weight:800;cursor:pointer;transition:.2s}
.tab-btn.is-active{background:rgba(56,189,248,.18);color:#9be5ff;border:1px solid rgba(56,189,248,.28)}
.form-panel{display:none}
.form-panel.is-active{display:block}
.divider{display:flex;align-items:center;gap:10px;margin:16px 0;color:var(--mut);font-size:.8rem}
.divider:before,.divider:after{content:'';flex:1;height:1px;background:var(--line)}
@media(max-width:760px){.shell{grid-template-columns:1fr}.info{order:-1;padding:22px}.info h2{font-size:1.35rem}.box{padding:22px;border-radius:22px}}
</style>
</head>
<body>
<div class="shell">
<section class="info">
<span class="badge">ورود امن به مدیریت</span>
<h2>مدیریت مشتریان، فروش و بازگشت و حفظ در یک پنل حرفه‌ای</h2>
<p>با موبایل یا ایمیل و رمز عبور، یا با کد یکبار مصرف پیامکی وارد شوید. ورود با پیامک بدون نیاز به حفظ رمز عبور.</p>
<div class="features">
<div class="feat">🔐 ورود امن با موبایل یا ایمیل</div>
<div class="feat">📱 ورود با پیامک - بدون نیاز به حفظ رمز عبور</div>
<div class="feat">📊 دسترسی سریع به گزارش‌ها و فروش</div>
<div class="feat">🤖 دستیار مدیریت همیشه همراه شماست</div>
</div>
</section>

<div class="box">
<div class="logo">
<img src="{{ asset(config('brand.logo')) }}" alt="{{ config('brand.name') }}">
<h1>{{ config('brand.name') }}</h1>
<p>{{ config('brand.tagline') }}</p>
</div>

@if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
@if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

<div class="tabs">
<button class="tab-btn is-active" data-tab="password" onclick="switchTab('password')">🔑 رمز عبور</button>
<button class="tab-btn" data-tab="otp" onclick="switchTab('otp')">📱 پیامک</button>
</div>

<div class="form-panel is-active" data-panel="password">
<form method="post" action="{{ url('/login') }}">
@csrf
<label>موبایل یا ایمیل</label>
<input name="login" value="{{ old('login') }}" required autofocus placeholder="مثلاً ۰۹۱۲... یا نشانی ایمیل" style="direction:ltr">
<label>رمز عبور</label>
<input type="password" name="password" required>
<label class="remember"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
<button class="btn" type="submit">ورود به پنل مدیریت</button>
</form>
</div>

<div class="form-panel" data-panel="otp">
<form id="otpRequestForm" method="post" action="{{ url('/login/send-code') }}">
@csrf
<label>موبایل یا ایمیل حساب مدیریت</label>
<input id="otpLoginInput" name="login" value="{{ old('login') }}" required placeholder="مثلاً ۰۹۱۲... یا نشانی ایمیل" style="direction:ltr">
<p style="color:var(--mut);font-size:.8rem;line-height:1.7;margin:8px 0 0;">کد ۶ رقمی به موبایل یا ایمیل شما ارسال می‌شود و تا ۵ دقیقه معتبر است. برای امنیت، کد را با کسی به اشتراک نگذارید.</p>
<button class="btn" type="submit" id="otpRequestBtn">📱 دریافت کد ورود با پیامک</button>
</form>
</div>

<div class="links">
<a href="{{ url('/forgot-password') }}">رمز عبور را فراموش کرده‌اید؟</a> • 
<a href="{{ url('/club/login') }}" target="_blank">ورود به باشگاه مشتریان</a>
</div>

</div>
</div>

{{-- پاپ‌آپ OTP حرفه‌ای - پس‌زمینه تیره با Blur + کارت شیشه‌ای --}}
<div class="otp-overlay" id="adminOtpOverlay">
  <div class="otp-card">
    <button class="otp-close" onclick="AdminOtp.close()" aria-label="بستن">×</button>
    <div class="otp-card-head">
      <div class="otp-logo">
        <img src="{{ asset(config('brand.logo')) }}" alt="logo" onerror="this.parentElement.innerHTML='🔐'">
      </div>
      <h3 class="otp-title">کد تأیید را وارد کنید</h3>
      <p class="otp-subtitle">کد ۶ رقمی به شماره زیر ارسال شد. این کد تا ۵ دقیقه معتبر است و به هیچ عنوان در صفحه نمایش داده نمی‌شود.</p>
      <div class="otp-phone-row">
        <span id="adminOtpPhoneDisplay">---</span>
        <button onclick="AdminOtp.editPhone()">ویرایش شماره</button>
      </div>
    </div>

    <form id="adminOtpForm" method="post" action="{{ url('/login/verify-code') }}">
      @csrf
      <div class="otp-inputs" id="adminOtpInputs">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
      </div>
      <input type="hidden" name="code" id="adminOtpHiddenCode">

      <div class="otp-timer-wrap">
        <div class="otp-timer-left">
          <div class="otp-circle">
            <svg viewBox="0 0 36 36">
              <circle class="otp-circle-bg" cx="18" cy="18" r="16"></circle>
              <circle class="otp-circle-fg" id="adminOtpCircleFg" cx="18" cy="18" r="16" stroke-dasharray="100" stroke-dashoffset="0"></circle>
            </svg>
            <div class="otp-circle-text" id="adminOtpTimerText">01:00</div>
          </div>
          <div class="otp-timer-info">
            <b>زمان باقی‌مانده برای ورود کد</b>
            <small>بعد از اتمام می‌توانید ارسال مجدد بزنید</small>
          </div>
        </div>
        <div class="otp-timer-bar"><div class="otp-timer-bar-fill" id="adminOtpBarFill"></div></div>

        <div class="otp-actions">
          <button type="submit" class="otp-btn" id="adminOtpSubmitBtn">تأیید و ورود به پنل</button>
          <button type="button" class="otp-btn-ghost" onclick="AdminOtp.close()">انصراف</button>
        </div>
      </form>

      <div class="otp-resend">
        <span>کد را دریافت نکردید؟</span>
        <button id="adminOtpResendBtn" disabled onclick="AdminOtp.resend()">ارسال مجدد کد</button>
      </div>

      <div class="otp-success" id="adminOtpSuccess">
        <div class="otp-success-icon">✓</div>
        <b>تأیید شد!</b>
        <small>در حال ورود به پنل مدیریت...</small>
      </div>

    </div>
  </div>
</div>

<script src="{{ asset('js/otp-popup.js') }}"></script>
<script>
function switchTab(name){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.toggle('is-active', b.dataset.tab===name));
  document.querySelectorAll('.form-panel').forEach(p=>p.classList.toggle('is-active', p.dataset.panel===name));
  try{localStorage.setItem('admin_login_tab', name);}catch(e){}
}
try{
  var saved = localStorage.getItem('admin_login_tab');
  if(saved){ switchTab(saved); }
}catch(e){}

var AdminOtp = Object.create(OtpPopup);
AdminOtp.totalTime = 60;

document.getElementById('otpRequestForm')?.addEventListener('submit', function(e){
  e.preventDefault();
  var loginInput = document.getElementById('otpLoginInput');
  var login = loginInput.value.trim();
  if(!login){ alert('لطفاً موبایل یا ایمیل را وارد کنید'); return; }

  var btn = document.getElementById('otpRequestBtn');
  btn.disabled = true;
  btn.textContent = 'در حال ارسال...';

  fetch('{{ url("/login/send-code") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value,
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    body: JSON.stringify({login: login})
  })
  .then(r => r.json().then(data => ({status:r.status, data:data})))
  .then(res => {
    btn.disabled = false;
    btn.textContent = '📱 دریافت کد ورود با پیامک';
    if(res.data.ok){
      AdminOtp.open(res.data.phone_full || login, function(){
        // ارسال مجدد
        document.getElementById('otpLoginInput').value = login;
        document.getElementById('otpRequestForm').dispatchEvent(new Event('submit', {cancelable:true}));
      });
    } else {
      alert(res.data.message || 'خطا در ارسال کد');
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.textContent = '📱 دریافت کد ورود با پیامک';
    alert('خطا در ارتباط با سرور');
  });
});

AdminOtp.init({
  overlayId: 'adminOtpOverlay',
  formId: 'adminOtpForm',
  inputsContainerId: 'adminOtpInputs',
  phoneDisplayId: 'adminOtpPhoneDisplay',
  resendBtnId: 'adminOtpResendBtn',
  timerTextId: 'adminOtpTimerText',
  circleFgId: 'adminOtpCircleFg',
  barFillId: 'adminOtpBarFill',
  onComplete: function(code){
    document.getElementById('adminOtpHiddenCode').value = code;
    document.getElementById('adminOtpForm').submit();
  }
});

AdminOtp.editPhone = function(){
  this.close();
  document.getElementById('otpLoginInput')?.focus();
};

// اگر از صفحه verify-code قدیمی اومدیم، پاپ‌آپ را مستقیم باز کن
@if(session('admin_login_phone'))
  document.addEventListener('DOMContentLoaded', function(){
    setTimeout(function(){
      AdminOtp.open('{{ session("admin_login_phone") }}');
    }, 300);
  });
@endif

// اگر فرم verify مستقیم سابمیت شد و خطا داشت، پاپ‌آپ باز بماند
@if($errors->has('code'))
  document.addEventListener('DOMContentLoaded', function(){
    AdminOtp.open('{{ session("admin_login_phone", old("login")) }}');
    AdminOtp.inputs.forEach(i=>i.classList.add('is-error'));
  });
@endif
</script>

</body>
</html>
