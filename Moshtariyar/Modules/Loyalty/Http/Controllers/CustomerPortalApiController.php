<?php

namespace Modules\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\Entities\Customer;
use Modules\Loyalty\Entities\CustomerPortalLoginCode;
use Modules\Loyalty\Entities\CustomerPortalToken;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Services\LoyaltyService;

class CustomerPortalApiController extends Controller
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function sendCode(Request $request)
    {
        $data = $request->validate(['phone'=>'required|string|max:32']);
        $customer = Customer::where('phone', $data['phone'])->first();
        if (! $customer) return response()->json(['ok'=>false,'message'=>'مشتری یافت نشد.'], 404);
        $code = (string) random_int(100000, 999999);
        CustomerPortalLoginCode::create(['phone'=>$data['phone'],'code_hash'=>Hash::make($code),'expires_at'=>now()->addMinutes(5),'ip'=>$request->ip(),'user_agent'=>substr((string)$request->userAgent(),0,255)]);
        // اپلیکیشن‌ها می‌توانند ارسال واقعی را از مسیر وب/تنظیمات پیامک استفاده کنند؛ در debug کد برگردانده می‌شود.
        return response()->json(['ok'=>true,'message'=>'کد ورود ساخته شد.'] + (config('app.debug') ? ['debug_code'=>$code] : []));
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['phone'=>'required|string|max:32','code'=>'required|string|size:6']);
        $row = CustomerPortalLoginCode::where('phone', $data['phone'])->whereNull('used_at')->where('expires_at','>=',now())->latest('id')->first();
        if (! $row || ! Hash::check($data['code'], $row->code_hash)) return response()->json(['ok'=>false,'message'=>'کد نادرست است.'], 422);
        $customer = Customer::where('phone', $data['phone'])->firstOrFail();
        $member = $this->loyalty->ensureMember($customer);
        $plain = Str::random(64);
        CustomerPortalToken::create(['member_id'=>$member->id,'token_hash'=>hash('sha256',$plain),'expires_at'=>now()->addDays(30)]);
        $row->update(['used_at'=>now()]);
        return response()->json(['ok'=>true,'token'=>$plain,'member'=>$this->memberPayload($member)]);
    }

    public function me(Request $request)
    {
        $member = $this->memberFromToken($request);
        return response()->json(['ok'=>true,'member'=>$this->memberPayload($member)]);
    }

    public function transactions(Request $request)
    {
        $member = $this->memberFromToken($request);
        return response()->json(['ok'=>true,'data'=>$member->transactions()->limit(50)->get()]);
    }

    public function referrals(Request $request)
    {
        $member = $this->memberFromToken($request);
        return response()->json(['ok'=>true,'data'=>$member->referralsMade()->with('referredCustomer')->limit(50)->get()]);
    }

    private function memberFromToken(Request $request): LoyaltyMember
    {
        $token = (string) $request->bearerToken();
        abort_if($token === '', 401);
        $row = CustomerPortalToken::where('token_hash', hash('sha256', $token))->where(function($q){$q->whereNull('expires_at')->orWhere('expires_at','>=',now());})->firstOrFail();
        $row->update(['last_used_at'=>now()]);
        return LoyaltyMember::with(['customer','tier'])->findOrFail($row->member_id);
    }

    private function memberPayload(LoyaltyMember $member): array
    {
        $member->loadMissing(['customer','tier']);
        return [
            'id'=>$member->id,'customer'=>$member->customer,'points'=>$member->points,
            'wallet_balance'=>$member->wallet_balance,'tier'=>$member->tier,
            'referral_code'=>$member->referral_code,'referral_link'=>route('club.referral',['code'=>$member->referral_code]),
        ];
    }
}
