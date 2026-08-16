<?php

namespace Modules\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Setting;
use Modules\Core\Entities\Ticket;
use Modules\Core\Entities\TicketReply;
use Modules\Core\Services\ImageOptimizerService;
use Modules\IranPack\Managers\SmsManager;
use Modules\IranPack\Messaging\MessagingChannels;
use Modules\Loyalty\Entities\CustomerPortalLoginCode;
use Modules\Loyalty\Entities\CustomerPortalNotification;
use Modules\Loyalty\Entities\LoyaltyBadge;
use Modules\Loyalty\Entities\LoyaltyCampaign;
use Modules\Loyalty\Entities\LoyaltyCampaignClick;
use Modules\Loyalty\Entities\LoyaltyCoupon;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyMission;
use Modules\Loyalty\Entities\LoyaltyMissionCompletion;
use Modules\Loyalty\Entities\LoyaltyRedemptionRule;
use Modules\Loyalty\Entities\LoyaltyTransaction;
use Modules\Loyalty\Entities\WheelPrize;
use Modules\Loyalty\Entities\WheelSpin;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Loyalty\Services\WheelService;

class CustomerPortalController extends Controller
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    public function loginForm(Request $request)
    {
        $this->ensurePortalEnabled();
        if (session('club_member_id')) {
            return redirect()->route('club.dashboard')->with('status', 'شما هم‌اکنون وارد باشگاه مشتریان هستید.');
        }
        if ($request->query('ref')) session(['club_ref' => strtoupper(trim($request->query('ref')))]);
        return view('loyalty::portal.login');
    }

    public function sendCode(Request $request)
    {
        $this->ensurePortalEnabled();
        if (session('club_member_id')) {
            return redirect()->route('club.dashboard')->with('status', 'شما هم‌اکنون وارد باشگاه مشتریان هستید.');
        }
        $data = $request->validate(['phone' => 'required|string|max:32']);
        $phone = $this->normalizePhone($data['phone']);

        $throttleKey = 'club_otp_send:' . $phone;
        $cooldown = (int) Setting::get('portal_otp_resend_seconds', 60);
        if (Cache::has($throttleKey)) {
            return back()->withErrors(['phone' => 'برای امنیت، کمی صبر کنید و دوباره درخواست کد بدهید.']);
        }

        $customer = Customer::where('phone', $phone)->first();
        if (! $customer) {
            return redirect()->route('club.register', ['phone' => $phone])->with('status', 'این شماره هنوز عضو نیست. لطفاً ثبت‌نام را تکمیل کنید.');
        }

        $code = (string) random_int(100000, 999999);
        $ttl = (int) Setting::get('portal_otp_ttl_minutes', 5);
        CustomerPortalLoginCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
        Cache::put($throttleKey, true, now()->addSeconds($cooldown));

        $message = str_replace(['{code}', '{minutes}'], [$code, $ttl], (string) Setting::get('portal_otp_message', 'کد ورود شما به باشگاه مشتریان: {code}\nاین کد تا {minutes} دقیقه معتبر است.'));
        $sent = false;
        $channel = Setting::get('portal_otp_channel', 'sms');

        try {
            if (in_array($channel, ['sms', 'both'], true)) {
                $smsManager = app(SmsManager::class);
                $sent = $smsManager->sendOtp($phone, $code) || $sent;

                if (!$sent) {
                    foreach (['kavenegar','smsir','melipayamak'] as $prov) {
                        try {
                            $providerSent = $smsManager->driver($prov)->send($phone, $message);
                            if ($providerSent) { $sent = true; break; }
                        } catch (\Throwable $e) { continue; }
                    }
                }
            }
            if (in_array($channel, ['whatsapp', 'both'], true) && MessagingChannels::isEnabled('whatsapp')) {
                try {
                    $sent = MessagingChannels::sendVia('whatsapp', $phone, $message) || $sent;
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) { report($e); }

        session(['club_login_phone' => $phone]);

        // پاسخ JSON برای پاپ‌آپ حرفه‌ای
        if ($request->wantsJson() || $request->ajax()) {
            if ($sent) {
                return response()->json([
                    'ok' => true,
                    'message' => 'کد ورود ارسال شد. لطفاً پیامک خود را بررسی کنید.',
                    'phone' => $phone,
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'کد ساخته شد، اما ارسال پیام ناموفق بود. تنظیمات پیامک را بررسی کنید.',
                ], 500);
            }
        }

        $status = $sent ? 'کد ورود ارسال شد. لطفاً پیامک خود را بررسی کنید.' : 'کد ساخته شد، اما ارسال پیام ناموفق بود. لطفاً تنظیمات پیامک/پیام‌رسان را بررسی کنید یا با پشتیبانی تماس بگیرید.';
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('کد ورود باشگاه (حالت توسعه) برای ' . $phone . ': ' . $code);
            session(['club_demo_code' => $code]);
        }
        return redirect()->route('club.verify.form')->with('status', $status);
    }

    public function passwordLogin(Request $request)
    {
        $this->ensurePortalEnabled();
        if (session('club_member_id')) {
            return redirect()->route('club.dashboard')->with('status', 'شما هم‌اکنون وارد باشگاه مشتریان هستید.');
        }
        $data = $request->validate([
            'login' => 'required|string|max:191',
            'password' => 'required|string',
        ]);
        $login = trim($data['login']);
        $normalized = $this->normalizePhone($login);
        $customer = Customer::where('email', $login)->orWhere('phone', $normalized)->first();
        if (! $customer || ! $customer->portal_password || ! Hash::check($data['password'], $customer->portal_password)) {
            return back()->withErrors(['login' => 'موبایل/ایمیل یا رمز عبور نادرست است.'])->onlyInput('login');
        }
        $member = $this->loyalty->ensureMember($customer);
        session(['club_customer_id' => $customer->id, 'club_member_id' => $member->id]);
        $request->session()->regenerate();
        return redirect()->route('club.dashboard');
    }

    public function verifyForm()
    {
        $this->ensurePortalEnabled();
        if (session('club_member_id')) {
            return redirect()->route('club.dashboard')->with('status', 'شما هم‌اکنون وارد باشگاه مشتریان هستید.');
        }
        abort_unless(session('club_login_phone'), 404);
        return view('loyalty::portal.verify', ['phone' => session('club_login_phone')]);
    }

    public function verify(Request $request)
    {
        $this->ensurePortalEnabled();
        $data = $request->validate(['phone' => 'required|string|max:32', 'code' => 'required|string|size:6']);
        $phone = $this->normalizePhone($data['phone']);

        $row = CustomerPortalLoginCode::where('phone', $phone)->whereNull('used_at')->where('expires_at', '>=', now())->latest('id')->first();
        if (! $row) return back()->withErrors(['code' => 'کد ورود منقضی شده است.']);

        $maxAttempts = (int) Setting::get('portal_otp_max_attempts', 5);
        if ($row->attempts >= $maxAttempts) return back()->withErrors(['code' => 'تعداد تلاش‌های ناموفق زیاد است. دوباره کد بگیرید.']);

        if (! Hash::check($data['code'], $row->code_hash)) {
            $row->increment('attempts');
            return back()->withErrors(['code' => 'کد ورود نادرست است.']);
        }

        $customer = Customer::where('phone', $phone)->firstOrFail();
        $member = $this->loyalty->ensureMember($customer);
        $row->update(['used_at' => now()]);
        session()->forget(['club_login_phone', 'club_demo_code']);
        session(['club_customer_id' => $customer->id, 'club_member_id' => $member->id]);
        $request->session()->regenerate();
        return redirect()->route('club.dashboard');
    }

    public function campaignReferral(Request $request, string $campaign, string $channel, string $code)
    {
        $this->ensurePortalEnabled();
        $camp = LoyaltyCampaign::where('slug', $campaign)->orWhere('id', $campaign)->firstOrFail();
        $member = LoyaltyMember::where('referral_code', strtoupper($code))->first();
        LoyaltyCampaignClick::create([
            'campaign_id' => $camp->id,
            'channel' => $channel,
            'referrer_member_id' => $member?->id,
            'referral_code' => strtoupper($code),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
        session([
            'club_ref' => strtoupper($code),
            'club_campaign_id' => $camp->id,
            'club_campaign_channel' => $channel,
        ]);
        return redirect()->route('club.register', ['ref' => strtoupper($code)]);
    }

    public function registerForm(Request $request)
    {
        $this->ensurePortalEnabled();
        if ($request->query('ref')) session(['club_ref' => strtoupper(trim($request->query('ref')))]);
        if (session('club_member_id')) {
            return redirect()->route('club.referrals')->with('status', 'شما وارد باشگاه هستید؛ برای دعوت دوستان از لینک معرفی خودتان استفاده کنید.');
        }
        return view('loyalty::portal.register', ['phone' => $request->query('phone'), 'ref' => session('club_ref')]);
    }

    public function register(Request $request)
    {
        $this->ensurePortalEnabled();
        if (session('club_member_id')) {
            return redirect()->route('club.dashboard')->with('status', 'شما هم‌اکنون وارد باشگاه مشتریان هستید.');
        }
        $data = $request->validate([
            'full_name' => 'required|string|max:255', 'phone' => 'required|string|max:32',
            'email' => 'required|email:rfc,dns|max:255', 'birthday' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed', 'referral_code' => 'nullable|string|max:20',
        ]);
        $phone = $this->normalizePhone($data['phone']);
        $email = trim($data['email']);

        // بررسی تکراری نبودن شماره
        if (Customer::where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'این شماره موبایل قبلاً ثبت‌نام کرده است. لطفاً وارد شوید.'])->withInput();
        }

        // جلوگیری از اسپم
        $throttleKey = 'club_register_send:' . $phone;
        $cooldown = (int) Setting::get('portal_otp_resend_seconds', 60);
        if (Cache::has($throttleKey)) {
            return back()->withErrors(['phone' => 'برای امنیت، کمی صبر کنید و دوباره تلاش کنید.'])->withInput();
        }

        // تولید کد ۶ رقمی
        $code = (string) random_int(100000, 999999);
        $ttl = (int) Setting::get('portal_otp_ttl_minutes', 5);

        // ذخیره کد برای تأیید
        CustomerPortalLoginCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        // ذخیره اطلاعات ثبت‌نام موقت در کش - ۱۰ دقیقه
        $registerData = [
            'full_name' => $data['full_name'],
            'phone' => $phone,
            'email' => $email,
            'birthday' => $data['birthday'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'referral_code' => $data['referral_code'] ?? session('club_ref', ''),
            'campaign_id' => session('club_campaign_id'),
            'campaign_channel' => session('club_campaign_channel'),
        ];
        Cache::put('club_register_data:' . $phone, $registerData, now()->addMinutes(10));
        Cache::put($throttleKey, true, now()->addSeconds($cooldown));
        session(['club_register_phone' => $phone]);

        // ارسال همزمان پیامک و ایمیل
        $message = "کد تأیید ثبت‌نام شما در باشگاه مشتریان: {$code}\nاین کد تا {$ttl} دقیقه معتبر است.";
        $emailSubject = 'کد تأیید ثبت‌نام باشگاه مشتریان - ' . config('brand.name', 'مشتری‌یار');
        $emailBody = "سلام {$data['full_name']} عزیز\n\nکد تأیید ثبت‌نام شما: {$code}\nاین کد تا {$ttl} دقیقه معتبر است.\n\nبا تشکر\n" . config('brand.name');

        $smsSent = false;
        $emailSent = false;

        try {
            $smsManager = app(SmsManager::class);
            $smsSent = $smsManager->sendOtp($phone, $code);
            if (!$smsSent) {
                // fallback به متن ساده
                $smsSent = $smsManager->send($phone, $message);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ارسال پیامک ثبت‌نام باشگاه ناموفق: ' . $e->getMessage());
        }

        try {
            \Illuminate\Support\Facades\Mail::raw($emailBody, function($mail) use ($email, $emailSubject){
                $mail->to($email)->subject($emailSubject);
            });
            $emailSent = true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ارسال ایمیل ثبت‌نام باشگاه ناموفق: ' . $e->getMessage());
        }

        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('کد ثبت‌نام باشگاه برای ' . $phone . ' / ' . $email . ': ' . $code);
        }

        $status = 'کد تأیید ۶ رقمی به شماره ' . $phone . ' و ایمیل ' . $email . ' ارسال شد. لطفاً کد را وارد کنید.';
        if (!$smsSent && !$emailSent) {
            $status = 'کد تأیید ساخته شد اما ارسال پیامک و ایمیل ناموفق بود. لطفاً تنظیمات پیامک و ایمیل را بررسی کنید. با پشتیبانی تماس بگیرید.';
        } elseif (!$smsSent) {
            $status = 'کد تأیید به ایمیل شما ارسال شد، اما پیامک ارسال نشد. لطفاً ایمیل خود را بررسی کنید.';
        } elseif (!$emailSent) {
            $status = 'کد تأیید به موبایل شما پیامک شد، اما ایمیل ارسال نشد. لطفاً پیامک خود را بررسی کنید.';
        }

        return redirect()->route('club.register.verify.form')->with('status', $status);
    }

    public function registerVerifyForm(Request $request)
    {
        $this->ensurePortalEnabled();
        if (session('club_member_id')) {
            return redirect()->route('club.dashboard');
        }
        $phone = session('club_register_phone') ?? $request->query('phone');
        abort_unless($phone, 404);
        return view('loyalty::portal.register_verify', ['phone' => $phone]);
    }

    public function verifyRegister(Request $request)
    {
        $this->ensurePortalEnabled();
        $data = $request->validate([
            'phone' => 'required|string|max:32',
            'code' => 'required|string|size:6',
        ]);
        $phone = $this->normalizePhone($data['phone']);

        $row = CustomerPortalLoginCode::where('phone', $phone)->whereNull('used_at')->where('expires_at', '>=', now())->latest('id')->first();
        if (! $row) {
            return back()->withErrors(['code' => 'کد تأیید منقضی شده است. لطفاً دوباره ثبت‌نام کنید.'])->withInput();
        }

        $maxAttempts = (int) Setting::get('portal_otp_max_attempts', 5);
        if ($row->attempts >= $maxAttempts) {
            return back()->withErrors(['code' => 'تعداد تلاش‌های ناموفق زیاد است. دوباره ثبت‌نام کنید.'])->withInput();
        }

        if (! Hash::check($data['code'], $row->code_hash)) {
            $row->increment('attempts');
            return back()->withErrors(['code' => 'کد تأیید نادرست است.'])->withInput();
        }

        $registerData = Cache::get('club_register_data:' . $phone);
        if (! $registerData) {
            return redirect()->route('club.register')->withErrors(['phone' => 'اطلاعات ثبت‌نام منقضی شده، لطفاً دوباره ثبت‌نام کنید.']);
        }

        // ایجاد مشتری
        $customer = Customer::create([
            'type' => 'individual',
            'full_name' => $registerData['full_name'],
            'phone' => $phone,
            'email' => $registerData['email'],
            'portal_password' => $registerData['password_hash'],
            'source' => 'club_portal_verified',
            'meta' => ['birthday' => $registerData['birthday'] ?? null],
        ]);

        $member = $this->loyalty->ensureMember($customer);

        try {
            app(\Modules\Automation\Services\WorkflowEngine::class)->onCustomerRegistered($customer);
        } catch (\Throwable $e) {
            report($e);
        }

        $ref = strtoupper(trim($registerData['referral_code'] ?? ''));
        if ($ref && (bool) Setting::get('portal_referral_enabled', true)) {
            $referral = $this->loyalty->applyReferral($member, $ref);
            if ($referral && !empty($registerData['campaign_id'])) {
                $referral->update([
                    'campaign_id' => $registerData['campaign_id'],
                    'campaign_channel' => $registerData['campaign_channel'] ?? null,
                ]);
                $this->loyalty->createCampaignRewardsForReferral($referral->fresh(), 'referral_registered');
            }
        }

        $row->update(['used_at' => now()]);
        Cache::forget('club_register_data:' . $phone);
        Cache::forget('club_register_send:' . $phone);
        session()->forget(['club_register_phone', 'club_ref', 'club_campaign_id', 'club_campaign_channel']);
        session(['club_customer_id' => $customer->id, 'club_member_id' => $member->id]);
        $request->session()->regenerate();

        return redirect()->route('club.profile')->with('status', 'ثبت‌نام شما با موفقیت تأیید شد! به باشگاه خوش آمدید. برای دریافت امتیاز، پروفایل خود را کامل کنید.');
    }

    public function dashboard()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        
        $member = $this->currentMember();
        $member->load(['customer','tier']);
        $transactions = $member->transactions()->limit(10)->get();
        $referralsCount = $member->referralsMade()->where('level', 1)->count();
        $unreadCount = CustomerPortalNotification::where('customer_id', $member->customer_id)->whereNull('read_at')->count();
        $referralLink = route('club.referral', ['code' => $member->referral_code]);
        $activePrizeCount = WheelPrize::where('is_active', true)->count();
        $latestWheelSpins = WheelSpin::with(['prize', 'coupon'])->where('member_id', $member->id)->latest('id')->limit(3)->get();
        $canSpinToday = ! WheelSpin::where('member_id', $member->id)->whereDate('created_at', today())->exists();
        return view('loyalty::portal.dashboard', compact('member','transactions','referralsCount','referralLink','unreadCount','activePrizeCount','latestWheelSpins','canSpinToday')); 
    }

    public function referrals()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        
        $member = $this->currentMember();
        $referrals = $member->referralsMade()->with(['referredCustomer','referredMember.tier','campaign'])->latest('id')->paginate(20);
        $referralLink = route('club.referral', ['code' => $member->referral_code]);
        $campaigns = LoyaltyCampaign::with('channels')->where('status','active')
            ->where(function($q){ $q->whereNull('starts_at')->orWhere('starts_at','<=',now()); })
            ->where(function($q){ $q->whereNull('ends_at')->orWhere('ends_at','>=',now()); })
            ->latest('id')->get();
        return view('loyalty::portal.referrals', compact('member','referrals','referralLink','campaigns'));
    }

    public function search(Request $request)
    {
        if (! session('club_member_id')) return response()->json(['items'=>[]]);
        
        $member = $this->currentMember();
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) return response()->json(['items'=>[]]);
        $items = [];
        foreach ($member->transactions()->where('reason','like',"%{$q}%")->limit(6)->get() as $t) {
            $items[] = ['type'=>'تراکنش','title'=>$t->reason,'desc'=>\Modules\Core\Support\Num::fa(number_format($t->amount)),'url'=>route('club.transactions')];
        }
        foreach (Ticket::where('customer_id',$member->customer_id)->where('subject','like',"%{$q}%")->limit(6)->get() as $t) {
            $items[] = ['type'=>'درخواست پشتیبانی','title'=>$t->subject,'desc'=>$this->customerTicketStatusLabel($t->status),'url'=>route('club.tickets.show',$t)];
        }
        foreach (CustomerPortalNotification::where('customer_id',$member->customer_id)->where('title','like',"%{$q}%")->limit(6)->get() as $n) {
            $items[] = ['type'=>'اعلان','title'=>$n->title,'desc'=>$n->body,'url'=>route('club.notifications')];
        }
        return response()->json(['items'=>$items]);
    }




    public function card()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);
        $referralLink = route('club.referral', ['code' => $member->ensureReferralCode()]);
        $activeCoupons = LoyaltyCoupon::where('member_id', $member->id)
            ->whereNull('used_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest('id')
            ->limit(6)
            ->get();
        $latestRewards = WheelSpin::with(['coupon', 'prize'])
            ->where('member_id', $member->id)
            ->latest('id')
            ->limit(5)
            ->get();
        $stats = [
            'orders' => Order::where('customer_id', $member->customer_id)->count(),
            'spent' => (float) Order::where('customer_id', $member->customer_id)->sum('total'),
            'referrals' => $member->referralsMade()->where('level', 1)->count(),
            'coupons' => $activeCoupons->count(),
        ];

        return view('loyalty::portal.card', compact('member', 'referralLink', 'activeCoupons', 'latestRewards', 'stats'));
    }

    public function journey()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);

        $orders = Order::where('customer_id', $member->customer_id)->latest('placed_at')->latest('id')->limit(8)->get();
        $transactions = $member->transactions()->limit(14)->get();
        $spins = WheelSpin::with(['coupon'])->where('member_id', $member->id)->latest('id')->limit(8)->get();
        $tickets = Ticket::where('customer_id', $member->customer_id)->latest('id')->limit(8)->get();
        $notifications = CustomerPortalNotification::where('customer_id', $member->customer_id)->latest('id')->limit(8)->get();

        $timeline = collect();

        foreach ($orders as $order) {
            $timeline->push([
                'type' => 'خرید',
                'title' => 'سفارش شماره ' . ($order->number ?: $order->id),
                'body' => 'مبلغ سفارش: ' . \Modules\Core\Support\Money::show($order->total) . ' ' . \Modules\Core\Support\Money::unitLabel(),
                'date' => $order->placed_at ?: $order->created_at,
                'icon' => '🛍️',
                'color' => '#0ea5e9',
                'url' => route('club.orders.show', $order),
            ]);
        }

        foreach ($transactions as $transaction) {
            $timeline->push([
                'type' => 'تراکنش وفاداری',
                'title' => $transaction->reason ?: 'تراکنش باشگاه',
                'body' => (($transaction->direction ?? '') === 'credit' ? 'افزایش ' : 'کاهش ') . \Modules\Core\Support\Num::fa(number_format($transaction->amount)) . ' · مانده ' . \Modules\Core\Support\Num::fa(number_format($transaction->balance_after)),
                'date' => $transaction->created_at,
                'icon' => ($transaction->direction ?? '') === 'credit' ? '➕' : '➖',
                'color' => ($transaction->direction ?? '') === 'credit' ? '#10b981' : '#ef4444',
                'url' => route('club.transactions'),
            ]);
        }

        foreach ($spins as $spin) {
            $timeline->push([
                'type' => 'گردونه شانس',
                'title' => $spin->prize_title ?: 'جایزه گردونه',
                'body' => $spin->coupon ? ('کد تخفیف: ' . $spin->coupon->code) : ('وضعیت ارسال: ' . $spin->deliveryStatusLabel()),
                'date' => $spin->created_at,
                'icon' => '🎡',
                'color' => '#f59e0b',
                'url' => route('club.rewards'),
            ]);
        }

        foreach ($tickets as $ticket) {
            $timeline->push([
                'type' => 'پشتیبانی',
                'title' => $ticket->subject,
                'body' => 'وضعیت: ' . $this->customerTicketStatusLabel($ticket->status),
                'date' => $ticket->updated_at ?: $ticket->created_at,
                'icon' => '🎫',
                'color' => '#8b5cf6',
                'url' => route('club.tickets.show', $ticket),
            ]);
        }

        foreach ($notifications as $notification) {
            $timeline->push([
                'type' => 'اعلان',
                'title' => $notification->title,
                'body' => $notification->body,
                'date' => $notification->created_at,
                'icon' => '🔔',
                'color' => '#64748b',
                'url' => $notification->url ?: route('club.notifications'),
            ]);
        }

        $timeline = $timeline->filter(fn ($item) => ! empty($item['date']))->sortByDesc('date')->values()->take(32);

        $stats = [
            'orders' => Order::where('customer_id', $member->customer_id)->count(),
            'spent' => (float) Order::where('customer_id', $member->customer_id)->sum('total'),
            'rewards' => WheelSpin::where('member_id', $member->id)->count(),
            'tickets' => Ticket::where('customer_id', $member->customer_id)->whereIn('status', ['open', 'pending', 'answered'])->count(),
        ];

        $nextActions = collect();
        if (! $member->profile_completed) {
            $nextActions->push(['title' => 'تکمیل پروفایل', 'body' => 'با تکمیل اطلاعات حساب، مأموریت پروفایل و پاداش‌های مربوط فعال می‌شود.', 'url' => route('club.profile'), 'icon' => '👤', 'color' => '#f59e0b']);
        }
        if ((int) $member->points > 0) {
            $nextActions->push(['title' => 'تبدیل امتیاز به کد تخفیف', 'body' => 'امتیازهای فعال خود را به کد تخفیف قابل استفاده در خرید بعدی تبدیل کنید.', 'url' => route('club.coupons'), 'icon' => '٪', 'color' => '#0ea5e9']);
        }
        if (! WheelSpin::where('member_id', $member->id)->whereDate('created_at', today())->exists()) {
            $nextActions->push(['title' => 'چرخاندن گردونه امروز', 'body' => 'شانس امروز شما هنوز استفاده نشده است؛ جایزه امروز را دریافت کنید.', 'url' => route('club.wheel'), 'icon' => '🎡', 'color' => '#10b981']);
        }
        if ($stats['orders'] === 0) {
            $nextActions->push(['title' => 'شروع مسیر خرید و پاداش', 'body' => 'با اولین خرید، امتیاز، سطح و پیشنهادهای اختصاصی شما فعال‌تر می‌شود.', 'url' => route('club.orders'), 'icon' => '🛍️', 'color' => '#8b5cf6']);
        }
        if ($nextActions->isEmpty()) {
            $nextActions->push(['title' => 'ادامه مسیر وفاداری', 'body' => 'با مأموریت‌ها، دعوت دوستان و خریدهای بعدی، سطح و پاداش‌های بیشتری دریافت می‌کنید.', 'url' => route('club.missions'), 'icon' => '🎯', 'color' => '#10b981']);
        }

        return view('loyalty::portal.journey', compact('member', 'timeline', 'stats', 'nextActions'));
    }

    public function orders()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        $orders = Order::with(['items'])
            ->where('customer_id', $member->customer_id)
            ->latest('placed_at')
            ->latest('id')
            ->paginate(12);

        $allOrdersQuery = Order::where('customer_id', $member->customer_id);
        $stats = [
            'count' => (clone $allOrdersQuery)->count(),
            'total' => (float) (clone $allOrdersQuery)->sum('total'),
            'last' => (clone $allOrdersQuery)->latest('placed_at')->latest('id')->first(),
            'points' => (int) $member->transactions()->where('ref_type', 'order_reward')->where('direction', 'credit')->sum('amount'),
        ];

        $favoriteItems = \Modules\Core\Entities\OrderItem::query()
            ->selectRaw('name, sku, sum(qty) as qty_sum, sum(line_total) as total_sum')
            ->whereHas('order', fn ($query) => $query->where('customer_id', $member->customer_id))
            ->groupBy('name', 'sku')
            ->orderByDesc('qty_sum')
            ->limit(5)
            ->get();

        return view('loyalty::portal.orders', compact('member', 'orders', 'stats', 'favoriteItems'));
    }

    public function showOrder(Order $order)
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        abort_unless((int) $order->customer_id === (int) $member->customer_id, 403);

        $order->load(['items']);
        $orderPoints = (int) $member->transactions()
            ->where('ref_type', 'order_reward')
            ->where('ref_id', $order->id)
            ->where('direction', 'credit')
            ->sum('amount');
        $relatedTransactions = $member->transactions()
            ->whereIn('ref_type', ['order_reward', 'rollback'])
            ->where('ref_id', $order->id)
            ->limit(10)
            ->get();

        $suggestion = $this->orderSuggestion($member, $order, $orderPoints);

        return view('loyalty::portal.order_show', compact('member', 'order', 'orderPoints', 'relatedTransactions', 'suggestion'));
    }

    public function transactions(Request $request)
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);

        $baseQuery = LoyaltyTransaction::where('member_id', $member->id);

        $stats = [
            'point_credit' => (int) (clone $baseQuery)->where('kind', 'point')->where('direction', 'credit')->sum('amount'),
            'point_debit' => (int) (clone $baseQuery)->where('kind', 'point')->where('direction', 'debit')->sum('amount'),
            'wallet_credit' => (int) (clone $baseQuery)->where('kind', 'wallet')->where('direction', 'credit')->sum('amount'),
            'wallet_debit' => (int) (clone $baseQuery)->where('kind', 'wallet')->where('direction', 'debit')->sum('amount'),
            'count' => (clone $baseQuery)->count(),
        ];

        $query = LoyaltyTransaction::where('member_id', $member->id)->latest('id');

        if ($request->filled('kind') && in_array($request->input('kind'), ['point', 'wallet'], true)) {
            $query->where('kind', $request->input('kind'));
        }

        if ($request->filled('direction') && in_array($request->input('direction'), ['credit', 'debit'], true)) {
            $query->where('direction', $request->input('direction'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($row) use ($search) {
                $row->where('reason', 'like', "%{$search}%")
                    ->orWhere('ref_type', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        $sourceSummary = LoyaltyTransaction::where('member_id', $member->id)
            ->selectRaw('coalesce(ref_type, ?) as source, count(*) as total_count, sum(amount) as total_amount', ['manual'])
            ->groupBy('source')
            ->orderByDesc('total_count')
            ->limit(8)
            ->get();

        return view('loyalty::portal.transactions', compact('member', 'transactions', 'stats', 'sourceSummary'));
    }

    public function profile()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        
        $member = $this->currentMember();
        $member->load('customer');
        return view('loyalty::portal.profile', compact('member'));
    }

    public function updateProfile(Request $request)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');
        
        $member = $this->currentMember();
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'email' => 'required|email:rfc,dns|max:255',
            'birthday' => 'required|string|max:20',
            'province' => 'nullable|string|max:80',
            'city' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:1000',
            'gender' => 'nullable|in:male,female',
            'password' => 'nullable|string|min:6|confirmed',
            'avatar' => 'nullable|image|max:4096',
            'avatar_data' => 'nullable|string',
        ]);

        $meta = array_merge($member->customer->meta ?: [], [
            'birthday' => $data['birthday'] ?? null,
            'province' => $data['province'] ?? null,
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'gender' => $data['gender'] ?? null,
        ]);

        if ($avatarPath = $this->storePortalAvatar($request)) {
            $meta['avatar'] = $avatarPath;
        }

        $update = [
            'full_name' => $data['full_name'],
            'phone' => $this->normalizePhone($data['phone']),
            'email' => $data['email'],
            'meta' => $meta,
        ];
        if (! empty($data['password'])) $update['portal_password'] = Hash::make($data['password']);
        $member->customer->update($update);
        $complete = filled($data['full_name']) && filled($data['phone']) && filled($data['email']) && filled($data['birthday']);
        if ($complete) {
            if (! $member->profile_completed) {
                $member->update(['profile_completed'=>true]);
                CustomerPortalNotification::create([
                    'customer_id' => $member->customer_id,
                    'title' => 'پروفایل شما کامل شد',
                    'body' => 'پروفایل شما با موفقیت کامل شد و پاداش‌های مربوطه اعمال شد.',
                    'type' => 'profile',
                    'url' => route('club.profile'),
                ]);
            }

            $this->loyalty->runRules('profile_complete', $member->fresh());
            $this->loyalty->checkMissions($member->fresh(), 'profile_complete');
        }
        return redirect()->route('club.profile')->with('status', 'پروفایل ذخیره شد.');
    }

    public function notifications(Request $request)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);

        $baseQuery = CustomerPortalNotification::where('customer_id', $member->customer_id);
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'unread' => (clone $baseQuery)->whereNull('read_at')->count(),
            'wheel' => (clone $baseQuery)->where('type', 'wheel')->count(),
            'ticket' => (clone $baseQuery)->where('type', 'ticket')->count(),
        ];

        $query = CustomerPortalNotification::where('customer_id', $member->customer_id)->latest('id');

        if ($request->input('status') === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->input('status') === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($row) use ($search) {
                $row->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(16)->withQueryString();
        $types = CustomerPortalNotification::where('customer_id', $member->customer_id)
            ->selectRaw('type, count(*) as total_count')
            ->groupBy('type')
            ->orderByDesc('total_count')
            ->get();

        CustomerPortalNotification::where('customer_id', $member->customer_id)->whereNull('read_at')->update(['read_at'=>now()]);

        return view('loyalty::portal.notifications', compact('member', 'items', 'stats', 'types'));
    }

    public function missions()
    {
        if (! session('club_member_id')) return redirect()->route('club.login');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);
        $missions = LoyaltyMission::where('is_active', true)
            ->where(function ($query) { $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->where(function ($query) { $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()); })
            ->latest('id')
            ->get();
        $doneRows = $member->missionCompletions()->get()->keyBy('mission_id');
        $done = $doneRows->keys()->all();

        $missionCards = $missions->map(function (LoyaltyMission $mission) use ($member, $doneRows) {
            $progress = $this->missionProgress($member, $mission);
            $isDone = $doneRows->has($mission->id);
            $target = max(1, (int) $mission->target);
            $percent = $isDone ? 100 : min(100, (int) floor(($progress['current'] / $target) * 100));
            return [
                'mission' => $mission,
                'is_done' => $isDone,
                'current' => $progress['current'],
                'target' => $target,
                'percent' => $percent,
                'label' => $progress['label'],
                'action_url' => $progress['action_url'],
                'action_label' => $progress['action_label'],
                'can_claim' => ! $isDone && $progress['current'] >= $target,
                'completed_at' => $doneRows[$mission->id]->completed_at ?? null,
            ];
        });

        return view('loyalty::portal.missions', compact('member', 'missions', 'done', 'doneRows', 'missionCards'));
    }

    public function claimMission(LoyaltyMission $mission)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');

        $member = $this->currentMember();
        abort_unless($mission->is_active, 404);

        $progress = $this->missionProgress($member, $mission);
        $target = max(1, (int) $mission->target);
        if ($progress['current'] < $target) {
            return redirect()->route('club.missions')->with('status', 'این مأموریت هنوز کامل نشده است.');
        }

        $alreadyDone = LoyaltyMissionCompletion::where('mission_id', $mission->id)->where('member_id', $member->id)->exists();
        if ($alreadyDone) {
            return redirect()->route('club.missions')->with('status', 'پاداش این مأموریت قبلاً دریافت شده است.');
        }

        LoyaltyMissionCompletion::create([
            'mission_id' => $mission->id,
            'member_id' => $member->id,
            'completed_at' => now(),
            'meta' => ['event' => $mission->event, 'current' => $progress['current'], 'target' => $target],
        ]);

        $rewardAmount = (int) round((float) $mission->reward_value);
        if ($rewardAmount > 0) {
            if ($mission->reward_type === 'points_fixed') {
                $this->loyalty->addPoints($member, $rewardAmount, 'پاداش مأموریت: ' . $mission->title, 'mission', $mission->id);
            } else {
                $this->loyalty->adjustWallet($member, $rewardAmount, 'پاداش مأموریت: ' . $mission->title, 'mission', $mission->id);
            }
        }

        CustomerPortalNotification::create([
            'customer_id' => $member->customer_id,
            'title' => 'پاداش مأموریت دریافت شد',
            'body' => 'پاداش مأموریت «' . $mission->title . '» با موفقیت برای شما ثبت شد.',
            'type' => 'mission',
            'url' => route('club.missions'),
        ]);

        return redirect()->route('club.missions')->with('status', 'پاداش مأموریت دریافت شد.');
    }

    public function campaignLeague()
    {
        if (! session('club_member_id')) return redirect()->route('club.login');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);
        $campaigns = LoyaltyCampaign::with('channels')->where('status','active')
            ->where(function($q){ $q->whereNull('starts_at')->orWhere('starts_at','<=',now()); })
            ->where(function($q){ $q->whereNull('ends_at')->orWhere('ends_at','>=',now()); })
            ->latest('id')->get();

        $selected = LoyaltyCampaign::where('slug', request('campaign'))->orWhere('id', request('campaign'))->first() ?: $campaigns->first();
        $leaders = collect();
        $topThree = collect();
        $myRank = null;
        $myCount = 0;
        $selectedChannels = collect();
        $stats = [
            'campaigns' => $campaigns->count(),
            'clicks' => 0,
            'referrals' => 0,
            'my_count' => 0,
            'top_count' => 0,
            'days_left' => null,
            'channels' => 0,
        ];

        if ($selected) {
            $selected->load('channels');
            $selectedChannels = $selected->channels->where('is_active', true)->values();
            $stats['clicks'] = \Illuminate\Support\Facades\DB::table('loyalty_campaign_clicks')->where('campaign_id', $selected->id)->count();
            $stats['referrals'] = \Illuminate\Support\Facades\DB::table('loyalty_referrals')->where('campaign_id', $selected->id)->where('level', 1)->count();
            $stats['channels'] = $selectedChannels->count();
            $stats['days_left'] = $selected->ends_at ? max(0, now()->diffInDays($selected->ends_at, false)) : null;

            $leaders = LoyaltyMember::with(['customer', 'tier'])
                ->select('loyalty_members.*')
                ->selectSub(function($q) use ($selected){
                    $q->from('loyalty_referrals')->selectRaw('count(*)')->whereColumn('loyalty_referrals.referrer_member_id','loyalty_members.id')->where('campaign_id',$selected->id)->where('level',1)->where('status', '<>', 'cancelled');
                }, 'campaign_referrals_count')
                ->orderByDesc('campaign_referrals_count')
                ->orderByDesc('points_lifetime')
                ->limit(50)
                ->get();

            $leaders = $leaders->filter(fn ($leader) => (int) $leader->campaign_referrals_count > 0 || $leader->id === $member->id)->values();
            $topThree = $leaders->take(3)->values();

            foreach ($leaders as $index => $leader) {
                if ($leader->id === $member->id) {
                    $myRank = $index + 1;
                    $myCount = (int) $leader->campaign_referrals_count;
                    break;
                }
            }

            if (! $myRank) {
                $myCount = \Illuminate\Support\Facades\DB::table('loyalty_referrals')
                    ->where('campaign_id', $selected->id)
                    ->where('referrer_member_id', $member->id)
                    ->where('level', 1)
                    ->where('status', '<>', 'cancelled')
                    ->count();
                $higherCount = $leaders->where('campaign_referrals_count', '>', $myCount)->count();
                $myRank = $myCount > 0 ? $higherCount + 1 : null;
            }

            $stats['my_count'] = $myCount;
            $stats['top_count'] = (int) ($leaders->max('campaign_referrals_count') ?: 0);
        }

        return view('loyalty::portal.campaign_league', compact('member','campaigns','selected','leaders','topThree','myRank','myCount','stats','selectedChannels'));
    }



    public function leaderboard()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);

        $members = LoyaltyMember::with(['customer', 'tier'])
            ->orderByDesc('points_lifetime')
            ->orderByDesc('points')
            ->limit(50)
            ->get();

        $myRank = null;
        $myRow = LoyaltyMember::with(['customer', 'tier'])->find($member->id);
        foreach ($members as $index => $row) {
            if ($row->id === $member->id) {
                $myRank = $index + 1;
                break;
            }
        }

        if (! $myRank) {
            $higherCount = LoyaltyMember::where('points_lifetime', '>', (int) $member->points_lifetime)->count();
            $myRank = $higherCount + 1;
        }

        $topThree = $members->take(3)->values();
        $nextTarget = LoyaltyMember::with(['customer', 'tier'])
            ->where('id', '<>', $member->id)
            ->where('points_lifetime', '>', (int) $member->points_lifetime)
            ->orderBy('points_lifetime')
            ->orderBy('points')
            ->first();

        $stats = [
            'members' => LoyaltyMember::count(),
            'top_score' => (int) ($members->max('points_lifetime') ?: 0),
            'my_score' => (int) $member->points_lifetime,
            'my_rank' => $myRank,
            'gap_to_next' => $nextTarget ? max(0, (int) $nextTarget->points_lifetime - (int) $member->points_lifetime) : 0,
        ];

        return view('loyalty::portal.leaderboard', compact('member', 'members', 'myRank', 'myRow', 'topThree', 'nextTarget', 'stats'));
    }

    public function badges()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);
        $badges = LoyaltyBadge::where('is_active', true)->latest('id')->get();
        $ownedRows = $member->badges()->get()->keyBy('badge_id');
        $owned = $ownedRows->keys()->all();

        $progressSources = [
            'points' => (int) $member->points_lifetime,
            'referrals' => (int) $member->referralsMade()->where('level', 1)->where('status', '<>', 'cancelled')->count(),
            'orders' => (int) Order::where('customer_id', $member->customer_id)->whereIn('status', ['processing', 'completed'])->count(),
            'manual' => 0,
        ];

        $badgeCards = $badges->map(function (LoyaltyBadge $badge) use ($ownedRows, $progressSources) {
            $hasBadge = $ownedRows->has($badge->id);
            $target = max(1, (int) $badge->condition_value);
            $current = $badge->condition_type === 'manual'
                ? ($hasBadge ? $target : 0)
                : (int) ($progressSources[$badge->condition_type] ?? 0);
            $percent = $hasBadge ? 100 : min(100, (int) floor(($current / $target) * 100));

            return [
                'badge' => $badge,
                'owned' => $hasBadge,
                'owned_row' => $ownedRows[$badge->id] ?? null,
                'current' => $current,
                'target' => $target,
                'percent' => $percent,
                'remaining' => max(0, $target - $current),
            ];
        });

        $nextBadge = $badgeCards->where('owned', false)->sortByDesc('percent')->first();
        $stats = [
            'owned' => $ownedRows->count(),
            'total' => $badges->count(),
            'points' => (int) $member->points_lifetime,
            'next_percent' => $nextBadge['percent'] ?? 100,
        ];

        return view('loyalty::portal.badges', compact('member', 'badges', 'owned', 'ownedRows', 'badgeCards', 'nextBadge', 'stats'));
    }

    public function coupons()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        
        $member = $this->currentMember();
        $rules = LoyaltyRedemptionRule::where('is_active', true)
            ->where(function ($query) { $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->where(function ($query) { $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()); })
            ->orderBy('points_required')
            ->get();
        $coupons = LoyaltyCoupon::where('member_id', $member->id)->latest('id')->paginate(15);

        return view('loyalty::portal.coupons', compact('member', 'rules', 'coupons'));
    }

    public function redeemCoupon(LoyaltyRedemptionRule $rule)
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        
        $member = $this->currentMember();
        if (! $rule->isAvailable()) {
            return redirect()->route('club.coupons')->with('status', 'این قانون تبدیل در حال حاضر فعال نیست.');
        }
        if ((int) $member->points < (int) $rule->points_required) {
            return redirect()->route('club.coupons')->with('status', 'امتیاز شما برای ساخت این کد تخفیف کافی نیست.');
        }

        $this->loyalty->addPoints($member, -1 * (int) $rule->points_required, 'تبدیل امتیاز به کد تخفیف: ' . $rule->title, 'redemption_rule', $rule->id);

        $code = strtoupper('MY-RD-' . Str::random(7));
        while (LoyaltyCoupon::where('code', $code)->exists()) {
            $code = strtoupper('MY-RD-' . Str::random(7));
        }

        $coupon = LoyaltyCoupon::create([
            'member_id' => $member->id,
            'redemption_rule_id' => $rule->id,
            'code' => $code,
            'title' => $rule->title,
            'discount_type' => $rule->discount_type,
            'discount_value' => $rule->discount_value,
            'min_order_total' => $rule->min_order_total,
            'expires_at' => now()->addDays((int) $rule->expires_days),
            'usage_limit' => (int) $rule->usage_limit,
            'source' => 'points_redemption',
            'points_spent' => (int) $rule->points_required,
        ]);

        CustomerPortalNotification::create([
            'customer_id' => $member->customer_id,
            'title' => 'کد تخفیف شما ساخته شد',
            'body' => 'کد تخفیف «' . $coupon->code . '» با موفقیت برای شما ساخته شد.',
            'type' => 'coupon',
            'url' => route('club.coupons'),
        ]);

        return redirect()->route('club.coupons')->with('status', 'کد تخفیف شما ساخته شد: ' . $coupon->code);
    }

    public function wheel()
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        
        $member = $this->currentMember();
        $member->load(['customer', 'tier']);
        $prizes = WheelPrize::where('is_active', true)->latest('id')->get();
        $spins = WheelSpin::with(['prize', 'coupon'])->where('member_id', $member->id)->latest('id')->limit(10)->get();
        $todaySpin = WheelSpin::with(['prize', 'coupon'])->where('member_id', $member->id)->whereDate('created_at', today())->latest('id')->first();
        $canSpinToday = ! $todaySpin;
        $wheelStats = [
            'active_prizes' => $prizes->count(),
            'total_spins' => WheelSpin::where('member_id', $member->id)->count(),
            'today_status' => $canSpinToday ? 'آماده چرخش' : 'چرخیده شده',
        ];

        return view('loyalty::portal.wheel', compact('member', 'prizes', 'spins', 'todaySpin', 'canSpinToday', 'wheelStats'));
    }

    public function spinWheel(Request $request, WheelService $wheelService)
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        
        $member = $this->currentMember();
        $alreadySpunToday = WheelSpin::where('member_id', $member->id)->whereDate('created_at', today())->exists();
        if ($alreadySpunToday) {
            return redirect()->route('club.wheel')->with('status', 'شما امروز گردونه را چرخانده‌اید. فردا دوباره شانس خود را امتحان کنید.');
        }

        $result = $wheelService->spin($member);
        if (! $result['ok']) {
            return redirect()->route('club.wheel')->with('status', $result['message']);
        }

        return redirect()->route('club.rewards')->with('status', 'تبریک! نتیجه گردونه شما ثبت شد: ' . $result['prize']);
    }

    public function rewards(Request $request)
    {
        if (! session('club_member_id')) return redirect()->route('club.login')->with('status', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');

        $member = $this->currentMember();
        $member->load(['customer', 'tier']);

        $spinBaseQuery = WheelSpin::with(['prize', 'coupon'])->where('member_id', $member->id);
        $spinQuery = WheelSpin::with(['prize', 'coupon'])->where('member_id', $member->id)->latest('id');

        if ($request->filled('type') && in_array($request->input('type'), ['point', 'wallet', 'coupon', 'nothing'], true)) {
            $spinQuery->where('prize_type', $request->input('type'));
        }

        if ($request->filled('delivery_status') && in_array($request->input('delivery_status'), ['pending', 'done', 'partial', 'failed', 'internal'], true)) {
            $spinQuery->where('delivery_status', $request->input('delivery_status'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $spinQuery->where(function ($row) use ($search) {
                $row->where('prize_title', 'like', "%{$search}%")
                    ->orWhereHas('coupon', fn ($coupon) => $coupon->where('code', 'like', "%{$search}%"));
            });
        }

        $spins = $spinQuery->paginate(12)->withQueryString();
        $wheelCoupons = LoyaltyCoupon::where('member_id', $member->id)->where('source', 'wheel')->latest('id')->get();
        $wheelTransactions = $member->transactions()->where('ref_type', 'wheel')->limit(12)->get();

        $stats = [
            'spins' => (clone $spinBaseQuery)->count(),
            'points' => (int) $member->transactions()->where('ref_type', 'wheel')->where('kind', 'point')->where('direction', 'credit')->sum('amount'),
            'wallet' => (int) $member->transactions()->where('ref_type', 'wheel')->where('kind', 'wallet')->where('direction', 'credit')->sum('amount'),
            'active_coupons' => $wheelCoupons->whereNull('used_at')->filter(fn($coupon) => ! $coupon->expires_at || $coupon->expires_at->isFuture())->count(),
            'used_coupons' => $wheelCoupons->whereNotNull('used_at')->count(),
            'coupon_total' => $wheelCoupons->count(),
        ];

        return view('loyalty::portal.rewards', compact('member', 'spins', 'wheelCoupons', 'wheelTransactions', 'stats'));
    }

    public function tickets()
    {
        if (! session('club_member_id')) return redirect()->route('club.login');
        
        $member = $this->currentMember();
        $tickets = Ticket::where('customer_id', $member->customer_id)->latest('id')->paginate(20);
        return view('loyalty::portal.tickets', compact('tickets'));
    }

    public function storeTicket(Request $request)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');
        
        $member = $this->currentMember();
        $data = $request->validate([
            'subject'=>'required|string|max:255',
            'priority'=>'required|in:low,normal,high,urgent',
            'department'=>'required|string|max:60',
            'message'=>'required|string|max:5000',
            'attachment'=>'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);
        $ticket = Ticket::create([
            'customer_id'=>$member->customer_id,
            'subject'=>$data['subject'],
            'priority'=>$data['priority'],
            'department'=>$data['department'],
            'status'=>'open',
            'staff_unread'=>true,
            'customer_unread'=>false,
            'last_reply_at'=>now(),
        ]);
        TicketReply::create(array_merge([
            'ticket_id'=>$ticket->id,
            'author'=>'customer',
            'author_name'=>$member->customer->full_name,
            'message'=>$data['message'],
        ], $this->storeTicketFile($request)));
        CustomerPortalNotification::create([
            'customer_id'=>$member->customer_id,
            'title'=>'درخواست پشتیبانی شما ثبت شد',
            'body'=>'درخواست «'.$ticket->subject.'» با موفقیت ثبت شد.',
            'type'=>'ticket',
            'url'=>route('club.tickets.show',$ticket),
        ]);
        return redirect()->route('club.tickets')->with('status', 'درخواست پشتیبانی ثبت شد.');
    }

    public function showTicket(Ticket $ticket)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');
        
        [$ticket, $priorities, $departments] = $this->prepareCustomerTicket($ticket);
        return view('loyalty::portal.ticket_show', compact('ticket', 'priorities', 'departments'));
    }

    public function showTicketModal(Ticket $ticket)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');
        
        [$ticket, $priorities, $departments] = $this->prepareCustomerTicket($ticket);
        return view('loyalty::portal.ticket_modal', compact('ticket', 'priorities', 'departments'));
    }

    private function prepareCustomerTicket(Ticket $ticket): array
    {
        $member = $this->currentMember();
        abort_unless($ticket->customer_id === $member->customer_id, 403);
        $ticket->load(['replies' => fn($q) => $q->where('is_internal', false)]);
        $ticket->update(['customer_unread' => false]);
        $priorities = ['low' => 'کم', 'normal' => 'عادی', 'high' => 'زیاد', 'urgent' => 'فوری'];
        $departments = ['پشتیبانی', 'فروش', 'مالی', 'فنی'];
        return [$ticket, $priorities, $departments];
    }

    private function customerTicketStatusLabel(string $status): string
    {
        return [
            'open' => 'باز',
            'pending' => 'در انتظار بررسی',
            'answered' => 'پاسخ داده شده',
            'closed' => 'بسته شده',
        ][$status] ?? $status;
    }

    public function replyTicket(Request $request, Ticket $ticket)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');
        
        $member = $this->currentMember();
        abort_unless($ticket->customer_id === $member->customer_id, 403);
        $data = $request->validate(['message'=>'required|string|max:5000','attachment'=>'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,txt']);
        TicketReply::create(array_merge([
            'ticket_id'=>$ticket->id,
            'author'=>'customer',
            'author_name'=>$member->customer->full_name,
            'message'=>$data['message'],
        ], $this->storeTicketFile($request)));
        $ticket->update(['status'=>'open','staff_unread'=>true,'customer_unread'=>false,'last_reply_at'=>now()]);
        return back()->with('status', 'پاسخ ثبت شد.');
    }

    public function downloadAttachment(\Modules\Core\Entities\TicketReply $reply)
    {
        if (! session('club_member_id')) return redirect()->route('club.login');
        
        $member = $this->currentMember();
        abort_unless($reply->ticket && $reply->ticket->customer_id === $member->customer_id, 403);
        abort_unless($reply->attachment && \Illuminate\Support\Facades\Storage::disk('public')->exists($reply->attachment), 404);
        return \Illuminate\Support\Facades\Storage::disk('public')->download($reply->attachment, $reply->attachment_name ?: basename($reply->attachment));
    }

    private function storeTicketFile(Request $request): array
    {
        if (! $request->hasFile('attachment')) return [];
        $file = $request->file('attachment');
        return [
            'attachment' => $file->store('tickets', 'public'),
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getClientMimeType(),
            'attachment_size' => $file->getSize(),
        ];
    }

    public function logout(Request $request)
    {
        session()->forget(['club_customer_id', 'club_member_id']);
        $request->session()->regenerateToken();
        return redirect()->route('club.login')->with('status', 'از پنل مشتری خارج شدید.');
    }



    private function storePortalAvatar(Request $request): ?string
    {
        $avatarData = (string) $request->input('avatar_data', '');
        if (str_starts_with($avatarData, 'data:image/')) {
            try {
                [$meta, $payload] = array_pad(explode(',', $avatarData, 2), 2, null);
                if ($payload) {
                    $binary = base64_decode($payload, true);
                    if ($binary && strlen($binary) <= 5 * 1024 * 1024) {
                        $tempPath = tempnam(sys_get_temp_dir(), 'club_avatar_');
                        file_put_contents($tempPath, $binary);
                        $uploaded = new UploadedFile($tempPath, 'club-avatar.webp', 'image/webp', null, true);
                        $path = app(ImageOptimizerService::class)->storeAsWebp($uploaded, 'img/club/avatars', 84);
                        @unlink($tempPath);
                        return $path;
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($request->hasFile('avatar')) {
            return app(ImageOptimizerService::class)->storeAsWebp($request->file('avatar'), 'img/club/avatars', 84);
        }

        return null;
    }


    private function missionProgress(LoyaltyMember $member, LoyaltyMission $mission): array
    {
        return match ($mission->event) {
            'profile_complete' => [
                'current' => $member->profile_completed ? 1 : 0,
                'label' => $member->profile_completed ? 'پروفایل کامل شده است' : 'پروفایل هنوز کامل نشده است',
                'action_url' => route('club.profile'),
                'action_label' => $member->profile_completed ? 'مشاهده پروفایل' : 'تکمیل پروفایل',
            ],
            'first_purchase' => [
                'current' => Order::where('customer_id', $member->customer_id)->exists() ? 1 : 0,
                'label' => 'خریدهای ثبت‌شده: ' . \Modules\Core\Support\Num::fa(number_format(Order::where('customer_id', $member->customer_id)->count())),
                'action_url' => route('club.orders'),
                'action_label' => 'مشاهده خریدها',
            ],
            'referrals_count' => [
                'current' => $member->referralsMade()->where('level', 1)->where('status', '<>', 'cancelled')->count(),
                'label' => 'معرفی مستقیم موفق: ' . \Modules\Core\Support\Num::fa(number_format($member->referralsMade()->where('level', 1)->where('status', '<>', 'cancelled')->count())),
                'action_url' => route('club.referrals'),
                'action_label' => 'دعوت دوستان',
            ],
            'orders_count' => [
                'current' => Order::where('customer_id', $member->customer_id)->whereIn('status', ['processing', 'completed'])->count(),
                'label' => 'سفارش معتبر: ' . \Modules\Core\Support\Num::fa(number_format(Order::where('customer_id', $member->customer_id)->whereIn('status', ['processing', 'completed'])->count())),
                'action_url' => route('club.orders'),
                'action_label' => 'مشاهده سفارش‌ها',
            ],
            default => [
                'current' => 0,
                'label' => 'پیشرفت این مأموریت پس از ثبت رویداد محاسبه می‌شود',
                'action_url' => route('club.journey'),
                'action_label' => 'مشاهده سفر من',
            ],
        };
    }

    private function orderSuggestion(LoyaltyMember $member, Order $order, int $orderPoints): array
    {
        if ($orderPoints > 0) {
            return [
                'title' => 'این خرید برای شما امتیاز ساخته است',
                'body' => 'از این سفارش ' . \Modules\Core\Support\Num::fa(number_format($orderPoints)) . ' امتیاز دریافت کرده‌اید. می‌توانید امتیازهای خود را در بخش کدهای تخفیف به مزیت خرید تبدیل کنید.',
                'url' => route('club.coupons'),
                'label' => 'تبدیل امتیاز',
                'color' => '#10b981',
            ];
        }

        if ((int) $member->points > 0) {
            return [
                'title' => 'برای خرید بعدی از امتیازها استفاده کنید',
                'body' => 'شما ' . \Modules\Core\Support\Num::fa(number_format((int) $member->points)) . ' امتیاز فعال دارید. با ساخت کد تخفیف، خرید بعدی می‌تواند جذاب‌تر شود.',
                'url' => route('club.coupons'),
                'label' => 'ساخت کد تخفیف',
                'color' => '#0ea5e9',
            ];
        }

        return [
            'title' => 'خرید بعدی، شروع مسیر پاداش بیشتر است',
            'body' => 'با خریدهای بعدی، امتیاز، سطح وفاداری و پیشنهادهای اختصاصی شما بهتر می‌شود.',
            'url' => route('club.missions'),
            'label' => 'دیدن مأموریت‌ها',
            'color' => '#8b5cf6',
        ];
    }

    private function currentMember(): LoyaltyMember
    {
        $this->ensurePortalEnabled();
        return LoyaltyMember::with('customer')->findOrFail(session('club_member_id'));
    }

    private function ensurePortalEnabled(): void
    {
        abort_if(! (bool) Setting::get('portal_enabled', true), 403, 'پنل مشتریان غیرفعال است.');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $map = ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
        return strtr($phone, $map);
    }
}