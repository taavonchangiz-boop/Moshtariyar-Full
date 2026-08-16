/**
 * پاپ‌آپ OTP حرفه‌ای - ۶ باکس با AutoFocus, Paste, Timer دایره‌ای, WebOTP AutoFill
 */

(function(){

  window.OtpPopup = {
    timer: null,
    timeLeft: 60,
    totalTime: 60,
    canResend: false,

    init: function({overlayId, formId, inputsContainerId, phoneDisplayId, resendBtnId, timerTextId, circleFgId, barFillId, onComplete}) {
      this.overlay = document.getElementById(overlayId);
      this.form = document.getElementById(formId);
      this.inputsContainer = document.getElementById(inputsContainerId);
      this.phoneDisplay = document.getElementById(phoneDisplayId);
      this.resendBtn = document.getElementById(resendBtnId);
      this.timerText = document.getElementById(timerTextId);
      this.circleFg = document.getElementById(circleFgId);
      this.barFill = document.getElementById(barFillId);
      this.onComplete = onComplete || null;

      if(!this.overlay || !this.inputsContainer) return;

      this.inputs = Array.from(this.inputsContainer.querySelectorAll('.otp-input'));
      this.bindEvents();
      this.setupWebOTP();
    },

    bindEvents: function(){
      const self = this;

      this.inputs.forEach((input, idx) => {
        input.addEventListener('input', function(e){
          let val = e.target.value.replace(/\D/g, '').slice(-1);
          e.target.value = val;

          e.target.classList.toggle('is-filled', !!val);
          e.target.classList.remove('is-error');

          if(val && idx < self.inputs.length - 1){
            self.inputs[idx + 1].focus();
            self.inputs[idx + 1].select();
          }

          self.checkComplete();
        });

        input.addEventListener('keydown', function(e){
          if(e.key === 'Backspace' && !e.target.value && idx > 0){
            self.inputs[idx - 1].focus();
            self.inputs[idx - 1].value = '';
            self.inputs[idx - 1].classList.remove('is-filled');
            e.preventDefault();
          }
          if(e.key === 'ArrowLeft' && idx > 0){
            self.inputs[idx - 1].focus();
          }
          if(e.key === 'ArrowRight' && idx < self.inputs.length - 1){
            self.inputs[idx + 1].focus();
          }
          if(e.key === 'Enter'){
            e.preventDefault();
            self.submit();
          }
        });

        input.addEventListener('focus', function(e){
          e.target.select();
        });

        // Paste کامل کد ۶ رقمی
        input.addEventListener('paste', function(e){
          e.preventDefault();
          const pasted = (e.clipboardData || window.clipboardData).getData('text');
          const digits = pasted.replace(/\D/g, '').slice(0, 6).split('');
          if(digits.length === 0) return;

          self.inputs.forEach((inp, i) => {
            inp.value = digits[i] || '';
            inp.classList.toggle('is-filled', !!inp.value);
          });

          const lastFilledIdx = Math.min(digits.length - 1, self.inputs.length - 1);
          if(digits.length < 6){
            self.inputs[lastFilledIdx + 1]?.focus();
          } else {
            self.inputs[lastFilledIdx]?.focus();
            self.checkComplete(true);
          }
        });
      });
    },

    open: function(phone, onResend){
      if(!this.overlay) return;
      this.phone = phone || '';

      if(this.phoneDisplay){
        this.phoneDisplay.textContent = phone || '';
      }

      this.overlay.classList.add('is-open');
      document.body.style.overflow = 'hidden';

      // ریست ورودی‌ها
      this.inputs.forEach(inp => {
        inp.value = '';
        inp.classList.remove('is-filled', 'is-error');
      });
      this.inputs[0]?.focus();

      // ذخیره callback ارسال مجدد
      this.onResendCallback = onResend || null;

      this.startTimer();
    },

    close: function(){
      if(!this.overlay) return;
      this.overlay.classList.remove('is-open');
      document.body.style.overflow = '';
      this.stopTimer();
    },

    startTimer: function(){
      const self = this;
      this.timeLeft = this.totalTime;
      this.canResend = false;
      if(this.resendBtn) this.resendBtn.disabled = true;

      this.updateTimerUI();

      this.stopTimer();
      this.timer = setInterval(function(){
        self.timeLeft--;
        self.updateTimerUI();

        if(self.timeLeft <= 0){
          self.stopTimer();
          self.canResend = true;
          if(self.resendBtn) self.resendBtn.disabled = false;
          if(self.timerText) self.timerText.textContent = '۰۰:۰۰';
        }
      }, 1000);
    },

    stopTimer: function(){
      if(this.timer){
        clearInterval(this.timer);
        this.timer = null;
      }
    },

    updateTimerUI: function(){
      const minutes = Math.floor(this.timeLeft / 60);
      const seconds = this.timeLeft % 60;
      const faDigits = n => String(n).padStart(2,'0').replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);

      if(this.timerText){
        this.timerText.textContent = faDigits(minutes) + ':' + faDigits(seconds);
      }

      const progress = (this.timeLeft / this.totalTime) * 100;
      const circumference = 100; // برای stroke-dasharray 100

      if(this.circleFg){
        this.circleFg.style.strokeDashoffset = (100 - progress);
        // تغییر رنگ بعد از 20 ثانیه آخر
        if(this.timeLeft <= 10){
          this.circleFg.style.stroke = '#ef4444';
        } else if(this.timeLeft <= 20){
          this.circleFg.style.stroke = '#f59e0b';
        } else {
          this.circleFg.style.stroke = '#0ea5e9';
        }
      }

      if(this.barFill){
        this.barFill.style.width = progress + '%';
        if(this.timeLeft <= 10){
          this.barFill.style.background = '#ef4444';
        } else if(this.timeLeft <= 20){
          this.barFill.style.background = '#f59e0b';
        } else {
          this.barFill.style.background = 'linear-gradient(90deg, #0ea5e9, #38bdf8)';
        }
      }
    },

    checkComplete: function(autoSubmit){
      const code = this.inputs.map(i => i.value).join('');
      if(code.length === 6){
        if(autoSubmit !== false){
          // تأخیر کوتاه برای UX
          setTimeout(() => this.submit(), 250);
        }
        return true;
      }
      return false;
    },

    getCode: function(){
      return this.inputs.map(i => i.value).join('');
    },

    submit: function(){
      const code = this.getCode();
      if(code.length !== 6){
        this.inputs.forEach(inp => {
          if(!inp.value) inp.classList.add('is-error');
        });
        this.inputs[0]?.focus();
        // ویبره در موبایل
        if(navigator.vibrate) navigator.vibrate(100);
        return;
      }

      if(this.onComplete){
        this.onComplete(code);
      } else if(this.form){
        // پر کردن اینپوت مخفی و سابمیت فرم
        let hiddenCode = this.form.querySelector('input[name=\"code\"]');
        if(!hiddenCode){
          hiddenCode = document.createElement('input');
          hiddenCode.type = 'hidden';
          hiddenCode.name = 'code';
          this.form.appendChild(hiddenCode);
        }
        hiddenCode.value = code;
        this.form.submit();
      }
    },

    resend: function(){
      if(!this.canResend) return;
      if(this.onResendCallback){
        this.onResendCallback();
      }
      this.startTimer();
    },

    showSuccess: function(){
      const successEl = this.overlay?.querySelector('.otp-success');
      const formEl = this.overlay?.querySelector('.otp-form-wrap');
      if(successEl) successEl.classList.add('is-show');
      if(formEl) formEl.style.display = 'none';
    },

    setupWebOTP: function(){
      // WebOTP API برای تشخیص خودکار کد پیامک در اندروید
      if('OTPCredential' in window){
        const self = this;
        try{
          navigator.credentials.get({
            otp: { transport:['sms'] },
            signal: AbortSignal.timeout(60000)
          }).then(otp => {
            if(otp && otp.code){
              const digits = otp.code.replace(/\D/g, '').slice(0,6).split('');
              self.inputs.forEach((inp, i) => {
                inp.value = digits[i] || '';
                inp.classList.toggle('is-filled', !!inp.value);
              });
              self.checkComplete(true);
            }
          }).catch(()=>{});
        }catch(e){}
      }

      // همچنین گوش دادن به autocomplete one-time-code
      const firstInput = this.inputs?.[0];
      if(firstInput){
        firstInput.setAttribute('autocomplete', 'one-time-code');
      }
    },

    setError: function(){
      this.inputs.forEach(inp => inp.classList.add('is-error'));
      if(navigator.vibrate) navigator.vibrate([100,50,100]);
    }
  };

})();
</script>
