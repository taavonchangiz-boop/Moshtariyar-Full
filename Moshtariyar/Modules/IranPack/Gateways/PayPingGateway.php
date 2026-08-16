<?php
namespace Modules\IranPack\Gateways;
use GuzzleHttp\Client;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Contracts\PaymentResult;
use Modules\Core\Entities\Setting;

class PayPingGateway implements PaymentGateway
{
    private Client $http; private string $token;
    public function __construct(){ $this->token=(string)Setting::get('payping_token',env('PAYPING_TOKEN','')); $this->http=new Client(['timeout'=>20]); }
    public function request(int $amount,string $cb,array $meta=[]):string{
        // پی‌پینگ مبلغ را به تومان می‌گیرد
        $res=$this->http->post('https://api.payping.ir/v2/pay',['headers'=>['Authorization'=>'Bearer '.$this->token,'Content-Type'=>'application/json'],
            'json'=>['amount'=>(int)round($amount/10),'returnUrl'=>$cb,'payerIdentity'=>$meta['mobile']??null,'description'=>$meta['description']??'']]);
        $d=json_decode((string)$res->getBody(),true);
        if(empty($d['code'])) throw new \RuntimeException('خطا در اتصال به درگاه پی‌پینگ');
        return 'https://api.payping.ir/v2/pay/gotoipg/'.$d['code'];
    }
    public function verify(array $r):PaymentResult{
        $res=$this->http->post('https://api.payping.ir/v2/pay/verify',['headers'=>['Authorization'=>'Bearer '.$this->token,'Content-Type'=>'application/json'],
            'json'=>['refId'=>$r['refid']??($r['refId']??null),'amount'=>(int)round(($r['amount']??0)/10)]]);
        return $res->getStatusCode()===200 ? new PaymentResult(true,(string)($r['refid']??''),'پرداخت موفق') : new PaymentResult(false,null,'پرداخت ناموفق');
    }
}
