<?php

namespace Modules\WooBridge\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Customer;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyTransaction;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\WooBridge\Entities\WooConnection;

class WalletController extends Controller
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function balance(Request $request, WooConnection $connection)
    {
        if (! $this->verify($request, $connection)) return response()->json(['error'=>'invalid signature'], 401);
        $member = $this->member($request);
        if (! $member) return response()->json(['ok'=>false,'message'=>'customer not found','balance'=>0,'points'=>0], 404);
        $member->load('tier');
        return response()->json(['ok'=>true,'balance'=>(int)$member->wallet_balance,'points'=>(int)$member->points,'tier'=>$member->tier?->name,'referral_code'=>$member->referral_code]);
    }

    public function debit(Request $request, WooConnection $connection)
    {
        if (! $this->verify($request, $connection)) return response()->json(['error'=>'invalid signature'], 401);
        $data = $request->validate(['phone'=>'nullable|string','email'=>'nullable|email','order_id'=>'required|integer','amount'=>'required|integer|min:1']);
        $member = $this->member($request);
        if (! $member) return response()->json(['ok'=>false,'message'=>'customer not found'], 404);

        $exists = LoyaltyTransaction::where('member_id',$member->id)->where('kind','wallet')->where('direction','debit')->where('ref_type','woo_wallet')->where('ref_id',$data['order_id'])->first();
        if ($exists) return response()->json(['ok'=>true,'duplicate'=>true,'balance'=>(int)$member->wallet_balance]);

        if ((int)$member->wallet_balance < (int)$data['amount']) {
            return response()->json(['ok'=>false,'message'=>'insufficient balance','balance'=>(int)$member->wallet_balance], 422);
        }
        $this->loyalty->adjustWallet($member, -1 * (int)$data['amount'], 'استفاده از کیف پول در سفارش ووکامرس #'.$data['order_id'], 'woo_wallet', $data['order_id']);
        $member->refresh();
        return response()->json(['ok'=>true,'balance'=>(int)$member->wallet_balance]);
    }

    public function refund(Request $request, WooConnection $connection)
    {
        if (! $this->verify($request, $connection)) return response()->json(['error'=>'invalid signature'], 401);
        $data = $request->validate(['phone'=>'nullable|string','email'=>'nullable|email','order_id'=>'required|integer','amount'=>'required|integer|min:1']);
        $member = $this->member($request);
        if (! $member) return response()->json(['ok'=>false,'message'=>'customer not found'], 404);

        $debit = LoyaltyTransaction::where('member_id',$member->id)->where('kind','wallet')->where('direction','debit')->where('ref_type','woo_wallet')->where('ref_id',$data['order_id'])->first();
        if (! $debit) return response()->json(['ok'=>true,'skipped'=>true,'message'=>'no debit found']);
        $refunded = LoyaltyTransaction::where('member_id',$member->id)->where('kind','wallet')->where('direction','credit')->where('ref_type','woo_refund')->where('ref_id',$data['order_id'])->exists();
        if ($refunded) return response()->json(['ok'=>true,'duplicate'=>true,'balance'=>(int)$member->wallet_balance]);

        $this->loyalty->adjustWallet($member, (int)$data['amount'], 'برگشت کیف پول سفارش ووکامرس #'.$data['order_id'], 'woo_refund', $data['order_id']);
        $member->refresh();
        return response()->json(['ok'=>true,'balance'=>(int)$member->wallet_balance]);
    }

    private function member(Request $request): ?LoyaltyMember
    {
        $phone = trim((string)$request->input('phone'));
        $email = trim((string)$request->input('email'));
        $customer = Customer::query()
            ->when($phone !== '', fn($q)=>$q->orWhere('phone',$this->normalizePhone($phone)))
            ->when($email !== '', fn($q)=>$q->orWhere('email',$email))
            ->first();
        return $customer ? $this->loyalty->ensureMember($customer) : null;
    }

    private function verify(Request $request, WooConnection $connection): bool
    {
        if (! $connection->is_active) return false;
        $sig = $request->header('x-wc-webhook-signature');
        if (! $sig) return false;
        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $connection->webhook_secret, true));
        return hash_equals($expected, $sig);
    }

    private function normalizePhone(string $phone): string
    {
        return strtr(trim($phone), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
    }
}
