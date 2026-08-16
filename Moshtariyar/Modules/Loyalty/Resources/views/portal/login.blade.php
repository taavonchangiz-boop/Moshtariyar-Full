@extends('layouts.customer_portal')
@section('title','ورود به باشگاه مشتریان - ورود امن و سریع')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/otp-popup.css') }}">
<div class="club-auth-page">
    <section class="club-auth-shell">
        <article class="club-auth-hero">
            <span class="club-auth-eyebrow">✨ ورود امن و سریع به باشگاه</span>
            <h1>به دنیای پاداش‌ها، امتیازها و تجربه خرید شخصی‌سازی شده خوش آمدید</h1>
            <p>با ورود به باشگاه مشتریان، تمام امتیازهای وفاداری، کیف پول هدیه، کدهای تخفیف اختصاصی، تاریخچه خریدها، گردونه شانس روزانه، مأموریت‌های جذاب و پشتیبانی اختصاصی شما در یک پنل زیبا و یکپارچه در اختیار شماست. ورود با رمز عبور یا کد یکبار مصرف پیامکی - هر دو امن و سریع.</p>
            <div class="club-auth-benefits">
                <div class="club-auth-mini-card" style="--mini-color:#0ea5e9;"><i>🎁</i><div><b>پاداش‌ها و گردونه شانس</b><small>هر روز شانس بردن جایزه - امتیاز و کد تخفیف</small></div></div>
                <div class="club-auth-mini-card" style="--mini-color:#10b981;"><i>٪</i><div><b>کدهای تخفیف اختصاصی</b><small>تبدیل امتیاز به تخفیف خرید - آنی و خودکار</small></div></div>
                <div class="club-auth-mini-card" style="--mini-color:#8b5cf6;"><i>🧭</i><div><b>سفر هوشمند وفاداری</b><small>مسیر کامل تعامل شما با برند - از اولین خرید تا امروز</small></div></div>
                <div class="club-auth-mini-card" style="--mini-color:#f59e0b;"><i>🎫</i><div><b>پشتیبانی اختصاصی باشگاه</b><small>پیگیری سریع درخواست‌ها - اولویت ویژه اعضای باشگاه</small></div></div>
            </div>
            <div style="margin-top:1.2rem; padding:.8rem; background:rgba(255,255,255,0.6); border-radius:1rem; border:1px solid rgba(255,255,255,0.4); font-size:.82rem; line-height:1.8; color:var(--mut);">
                <b style="color:var(--txt);">🔐 امنیت بالا:</b> تمام کدهای ورود فقط از طریق پیامک رسمی ارسال می‌شود و تا ۵ دقیقه معتبر است. کد را با کسی به اشتراک نگذارید.
            </div>
        </article>

        <article class="club-auth-card">
            <header>
                <div>
                    <span>ورود امن به حساب باشگاه</span>
                    <h2>چطور می‌خوای وارد بشی؟</h2>
                    <p>اگر رمز عبور داری، سریع وارد شو. اگر رمز را فراموش کردی یا ترجیح می‌دی با کد یکبار مصرف وارد بشی، شماره موبایلت را وارد کن.</p>
                </div>
            </header>

            @if(session('status'))
                <div class="club-auth-alert is-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="club-auth-alert is-error">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('club.login.password') }}" class="club-auth-form">
                @csrf
                <div>
                    <label>📱 موبایل یا 📧 ایمیل</label>
                    <input name="login" class="ltr" value="{{ old('login') }}" placeholder="مثلاً ۰۹۱۲۳۴۵۶۷۸۹ یا email@example.com" required autofocus>
                </div>
                <div>
                    <label>🔑 رمز عبور باشگاه</label>
                    <input type="password" name="password" required placeholder="رمز عبوری که هنگام ثبت‌نام انتخاب کردی">
                </div>
                <button class="btn">🚀 ورود به باشگاه مشتریان</button>
            </form>

            <div class="club-auth-divider">یا ورود سریع با کد یکبار مصرف</div>

            <form id="clubOtpRequestForm" method="post" action="{{ route('club.login.send') }}" class="club-auth-form">
                @csrf
                <div>
                    <label>📱 شماره موبایل برای دریافت کد</label>
                    <input id="clubPhoneInput" name="phone" class="ltr" placeholder="مثلاً ۰۹۱۲۳۴۵۶۷۸۹" required inputmode="numeric">
                    <small style="color:var(--mut); font-size:.78rem; margin-top:4px; display:block;">کد ۶ رقمی از طریق پیامک برایت ارسال می‌شود و تا ۵ دقیقه معتبر است. کد در صفحه نمایش داده نمی‌شود.</small>
                </div>
                <button class="btn btn-ghost" style="background: linear-gradient(135deg, #f8fafc, #eef2ff); border-color:#c7d2fe; color:#4338ca;" type="submit">📲 دریافت کد ورود یکبار مصرف</button>
            </form>

            <div class="club-auth-security">
                <b>🛡️ نکته امنیتی مهم</b>
                <span>کد ورود فقط از طریق پیامک رسمی ارسال می‌شود و به هیچ عنوان در صفحه نمایش داده نمی‌شود. اگر کسی از شما کد خواست، به او ندهید.</span>
            </div>

            <p class="club-auth-link-row">هنوز عضو باشگاه نیستی؟ <a href="{{ route('club.register') }}">✨ ثبت‌نام سریع در باشگاه - ۳۰ ثانیه‌ای</a></p>
        </article>
    </section>
</div>

{{-- پاپ‌آپ OTP حرفه‌ای باشگاه - دیجی‌کالا استایل --}}
<div class="otp-overlay" id="clubOtpOverlay">
  <div class="otp-card">
    <button class="otp-close" onclick="ClubOtp.close()" aria-label="بستن">×</button>
    <div class="otp-card-head">
      <div class="otp-logo">✨</div>
      <h3 class="otp-title">کد تأیید را وارد کنید</h3>
      <p class="otp-subtitle">کد ۶ رقمی به شماره زیر ارسال شد. این کد تا ۵ دقیقه معتبر است و فقط از طریق پیامک ارسال شده است.</p>
      <div class="otp-phone-row">
        <span id="clubOtpPhoneDisplay">---</span>
        <button onclick="ClubOtp.editPhone()">ویرایش شماره</button>
      </div>
    </div>

    <form id="clubOtpForm" method="post" action="{{ route('club.verify') }}">
      @csrf
      <input type="hidden" name="phone" id="clubOtpHiddenPhone">
      <div class="otp-inputs" id="clubOtpInputs">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
        <input class="otp-input" type="text" inputmode="numeric" maxlength="1">
      </div>
      <input type="hidden" name="code" id="clubOtpHiddenCode">

      <div class="otp-timer-wrap">
        <div class="otp-timer-left">
          <div class="otp-circle">
            <svg viewBox="0 0 36 36">
              <circle class="otp-circle-bg" cx="18" cy="18" r="16"></circle>
              <circle class="otp-circle-fg" id="clubOtpCircleFg" cx="18" cy="18" r="16" stroke-dasharray="100" stroke-dashoffset="0"></circle>
            </svg>
            <div class="otp-circle-text" id="clubOtpTimerText">01:00</div>
          </div>
          <div class="otp-timer-info">
            <b>زمان باقی‌مانده برای ورود کد</b>
            <small>بعد از اتمام می‌توانید ارسال مجدد بزنید</small>
          </div>
        </div>
        <div class="otp-timer-bar"><div class="otp-timer-bar-fill" id="clubOtpBarFill"></div></div>
      </div>

      <div class="otp-actions">
        <button type="submit" class="otp-btn">تأیید و ورود به باشگاه</button>
        <button type="button" class="otp-btn-ghost" onclick="ClubOtp.close()">انصراف</button>
      </div>
    </form>

    <div class="otp-resend">
      <span>کد را دریافت نکردید؟</span>
      <button id="clubOtpResendBtn" disabled>ارسال مجدد کد</button>
    </div>

    <div class="otp-success" id="clubOtpSuccess">
      <div class="otp-success-icon">✓</div>
      <b>تأیید شد!</b>
      <small>در حال ورود به باشگاه...</small>
    </div>
  </div>
</div>

<script src="{{ asset('js/otp-popup.js') }}"></script>
<script>
var ClubOtp = Object.create(OtpPopup);
ClubOtp.totalTime = 60;

document.getElementById('clubOtpRequestForm')?.addEventListener('submit', function(e){
  e.preventDefault();
  var phoneInput = document.getElementById('clubPhoneInput');
  var phone = phoneInput.value.trim();
  if(!phone){ alert('شماره موبایل را وارد کنید'); return; }

  var btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.textContent = 'در حال ارسال...';

  fetch(this.action, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    body: JSON.stringify({phone: phone})
  })
  .then(r => r.json().then(data => ({status: r.status, data: data})))
  .then(res => {
    btn.disabled = false;
    btn.textContent = '📲 دریافت کد ورود یکبار مصرف';
    if(res.status === 200 || res.data.message){
      ClubOtp.open(phone, function(){
        document.getElementById('clubPhoneInput').value = phone;
        document.getElementById('clubOtpRequestForm').dispatchEvent(new Event('submit', {cancelable:true}));
      });
    } else {
      alert(res.data.message || 'خطا در ارسال کد');
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.textContent = '📲 دریافت کد ورود یکبار مصرف';
    ClubOtp.open(phone);
  });
});

ClubOtp.init({
  overlayId: 'clubOtpOverlay',
  formId: 'clubOtpForm',
  inputsContainerId: 'clubOtpInputs',
  phoneDisplayId: 'clubOtpPhoneDisplay',
  resendBtnId: 'clubOtpResendBtn',
  timerTextId: 'clubOtpTimerText',
  circleFgId: 'clubOtpCircleFg',
  barFillId: 'clubOtpBarFill',
  onComplete: function(code){
    document.getElementById('clubOtpHiddenPhone').value = document.getElementById('clubPhoneInput').value.trim();
    document.getElementById('clubOtpHiddenCode').value = code;
    document.getElementById('clubOtpForm').submit();
  }
});

ClubOtp.editPhone = function(){
  this.close();
  document.getElementById('clubPhoneInput')?.focus();
};

document.getElementById('clubOtpForm')?.addEventListener('submit', function(e){
  var code = ClubOtp.getCode();
  if(code.length !== 6){
    e.preventDefault();
    ClubOtp.setError();
    return;
  }
  document.getElementById('clubOtpHiddenCode').value = code;
});
</script>

@endsection
