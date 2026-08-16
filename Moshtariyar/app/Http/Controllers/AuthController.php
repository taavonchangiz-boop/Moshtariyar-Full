<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect('/app');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', 
            'password' => 'required|string',
        ], [
            'login.required' => 'لطفاً شماره موبایل یا ایمیل خود را وارد کنید.',
            'password.required' => 'لطفاً رمز عبور را وارد کنید.'
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $loginValue = $this->normalizePhone($request->login);
        $user = User::where($loginField, $loginValue)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'login' => 'شماره موبایل/ایمیل یا رمز عبور اشتباه است.',
            ])->withInput($request->only('login'));
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'login' => 'حساب کاربری شما غیرفعال شده است.',
            ]);
        }

        if (! $user->isSuperAdmin()) {

            $business = null;

            if ($user->business_id) {
                $business = Business::find($user->business_id);
            }

            if (! $business) {
                $business = Business::where('is_active', true)->orderBy('id')->first() ?? Business::orderBy('id')->first();
            }

            if (! $business) {
                try {
                    DB::transaction(function () use (&$business) {
                        $business = Business::create([
                            'name' => 'کسب‌وکار اصلی',
                            'domain' => null,
                            'is_active' => true,
                        ]);
                        BusinessSubscription::create([
                            'business_id' => $business->id,
                            'plan_name' => 'کامل',
                            'active_modules' => ['customers','orders','products','loyalty','tickets','workflows','reports','customers.view','orders.view','products.view','loyalty.view','dashboard.view'],
                            'starts_at' => now(),
                            'ends_at' => now()->addYears(5),
                            'is_active' => true,
                        ]);
                    });
                    $business = Business::find($business->id);
                } catch (\Throwable $e) {
                }
            }

            if ($business) {
                if ($user->business_id !== $business->id) {
                    $user->business_id = $business->id;
                    $user->save();
                }
                if (! $business->is_active) {
                    $business->update(['is_active' => true]);
                }

                $subscription = $business->subscription;
                if (! $subscription) {
                    $subscription = BusinessSubscription::create([
                        'business_id' => $business->id,
                        'plan_name' => 'کامل',
                        'active_modules' => ['customers','orders','products','loyalty','tickets','workflows','reports'],
                        'starts_at' => now(),
                        'ends_at' => now()->addYears(5),
                        'is_active' => true,
                    ]);
                }
                if (! $subscription->is_active || ($subscription->ends_at && $subscription->ends_at->isPast())) {
                    $subscription->update([
                        'is_active' => true,
                        'starts_at' => $subscription->starts_at ?? now(),
                        'ends_at' => now()->addYears(5),
                    ]);
                }
            }

            $user->refresh();
            if (! $user->business || ! $user->business_id) {
                return back()->withErrors([
                    'login' => 'حساب شما به هیچ کسب‌وکاری متصل نیست. لطفاً با پشتیبانی تماس بگیرید.',
                ]);
            }
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        return redirect()->intended('/app');
    }

    /** ورود با پیامک - فرم ارسال کد */
    public function sendLoginCode(Request $request)
    {
        $data = $request->validate(['login'=>'required|string|max:191']);
        $loginRaw = trim($data['login']);
        $login = $this->normalizePhone($loginRaw);
        $field = filter_var($loginRaw, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $field === 'email' ? $loginRaw : $login)->first();

        if (! $user) {
            return back()->withErrors(['login'=>'کاربری با این مشخصات پیدا نشد.'])->onlyInput('login');
        }
        if (! $user->is_active) {
            return back()->withErrors(['login'=>'حساب کاربری شما غیرفعال است.']);
        }

        $throttleKey = 'admin_login_otp:' . $user->id;
        if (Cache::has($throttleKey)) {
            return back()->withErrors(['login'=>'کد قبلاً ارسال شده، لطفاً ۶۰ ثانیه صبر کنید.'])->onlyInput('login');
        }

        $code = (string) random_int(100000, 999999);
        Cache::put('admin_login_code_'.$user->id, Hash::make($code), now()->addMinutes(5));
        Cache::put($throttleKey, true, now()->addSeconds(60));
        session(['admin_login_user_id'=>$user->id, 'admin_login_phone'=>$user->phone]);

        $sent = false;

        if (!empty($user->phone)) {
            try {
                $sms = app(\Modules\IranPack\Managers\SmsManager::class);
                $sent = $sms->sendOtp($user->phone, $code);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ارسال کد ورود مدیریت ناموفق: '.$e->getMessage());
            }
        }

        $message = 'کد ورود شما به پنل مدیریت مشتری‌یار: '.$code.' - این کد تا ۵ دقیقه معتبر است.';

        if (!$sent && !empty($user->email)) {
            try {
                \Illuminate\Support\Facades\Mail::raw($message, function($mail) use ($user){
                    $mail->to($user->email)->subject('کد ورود به پنل مدیریت');
                });
                $sent = true;
            } catch (\Throwable $e) {}
        }

        $status = $sent ? 'کد ورود ارسال شد. لطفاً پیامک خود را بررسی کنید.' : 'کد ساخته شد، اما ارسال پیام ناموفق بود. لطفاً تنظیمات پیامک را بررسی کنید یا با پشتیبانی تماس بگیرید.';
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('کد ورود مدیریت (حالت توسعه) برای ' . ($user->phone ?? $user->email) . ': ' . $code);
        }

        return redirect('/login/verify-code')->with('status', $status);
    }

    public function showVerifyCodeForm()
    {
        abort_unless(session('admin_login_user_id'), 404);
        return view('auth.verify_code');
    }

    public function verifyLoginCode(Request $request)
    {
        $data = $request->validate([
            'code'=>'required|string|size:6',
        ]);

        $userId = session('admin_login_user_id');
        abort_unless($userId, 404);
        $user = User::findOrFail($userId);

        $hash = Cache::get('admin_login_code_'.$user->id);
        if (! $hash || ! Hash::check($data['code'], $hash)) {
            return back()->withErrors(['code'=>'کد ورود نادرست یا منقضی شده است.']);
        }

        if (!$user->is_active) {
            return back()->withErrors(['code'=>'حساب شما غیرفعال است.']);
        }

        if (! $user->isSuperAdmin()) {
            $business = $user->business_id ? \App\Models\Business::find($user->business_id) : null;
            if (! $business) {
                $business = \App\Models\Business::where('is_active', true)->orderBy('id')->first() ?? \App\Models\Business::orderBy('id')->first();
            }
            if ($business && $user->business_id !== $business->id) {
                $user->business_id = $business->id;
                $user->save();
            }
        }

        Cache::forget('admin_login_code_'.$user->id);
        session()->forget(['admin_login_user_id','admin_login_phone']);

        Auth::login($user, true);
        $request->session()->regenerate();
        return redirect()->intended('/app');
    }

    public function forgotForm(){ return view('auth.forgot'); }

    public function sendResetCode(Request $request)
    {
        $data = $request->validate(['login'=>'required|string|max:191']);
        $login = $this->normalizePhone($data['login']);
        $user = User::where('email',$data['login'])->orWhere('phone',$login)->first();
        if (! $user) return back()->withErrors(['login'=>'کاربری با این مشخصات پیدا نشد.'])->onlyInput('login');
        $code = (string) random_int(100000, 999999);
        Cache::put('admin_reset_'.$user->id, Hash::make($code), now()->addMinutes(10));
        session(['admin_reset_user_id'=>$user->id]);

        $sent = false;
        if (!empty($user->phone)) {
            try {
                $sms = app(\Modules\IranPack\Managers\SmsManager::class);
                $sent = $sms->sendOtp($user->phone, $code);
            } catch (\Throwable $e) {}
        }

        $status = $sent ? 'کد بازیابی ارسال شد. لطفاً پیامک خود را بررسی کنید.' : 'کد بازیابی ساخته شد. اگر پیامک دریافت نکردید، با پشتیبانی تماس بگیرید.';
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('کد بازیابی مدیریت (حالت توسعه) برای ' . ($user->phone ?? $user->email) . ': ' . $code);
        }

        return redirect('/reset-password')->with('status', $status);
    }

    public function resetForm()
    {
        abort_unless(session('admin_reset_user_id'), 404);
        return view('auth.reset');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate(['code'=>'required|string|size:6','password'=>'required|string|min:6|confirmed']);
        $user = User::findOrFail(session('admin_reset_user_id'));
        $hash = Cache::get('admin_reset_'.$user->id);
        if (! $hash || ! Hash::check($data['code'], $hash)) return back()->withErrors(['code'=>'کد بازیابی نادرست یا منقضی شده است.']);
        $user->update(['password'=> $data['password']]);
        Cache::forget('admin_reset_'.$user->id); session()->forget('admin_reset_user_id');
        return redirect('/login')->with('status','رمز عبور با موفقیت تغییر کرد.');
    }

    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/login');
    }

    private function normalizePhone(string $value): string
    {
        $value = trim($value); $map = ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
        $value = strtr($value, $map); if (filter_var($value, FILTER_VALIDATE_EMAIL)) return $value;
        $phone = preg_replace('/\D+/', '', $value); if (str_starts_with($phone, '98')) $phone = '0'.substr($phone,2); if (str_starts_with($phone,'9') && strlen($phone)===10) $phone='0'.$phone; return $phone ?: $value;
    }
}