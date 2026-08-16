<?php
namespace Modules\IranPack\Gateways;
use GuzzleHttp\Client;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Contracts\PaymentResult;
use Modules\Core\Entities\Setting;

class NextPayGateway implements PaymentGateway
{
    private Client $http; private string $apiKey;
    public function __construct(){ $this->apiKey=(string)Setting::get('nextpay_api_key',env('NEXTPAY_API_KEY','')); $this->http=new Client(['timeout'=>20]); }
    public function request(int $amount,string $cb,array $meta=[]):string{
        $res=$this->http->post('https://nextpay.org/nx/gateway/token',['json'=>['api_key'=>$this->apiKey,'amount'=>$amount,'order_id'=>uniqid(),'callback_uri'=>$cb]]);
        $d=json_decode((string)$res->getBody(),true);
        if((int)($d['code']??-1)!==-1||empty($d['trans_id'])) throw new \RuntimeException('خطا در اتصال به درگاه نکست‌پی');
        return 'https://nextpay.org/nx/gateway/payment/'.$d['trans_id'];
    }
    public function verify(array $r):PaymentResult{
        $res=$this->http->post('https://nextpay.org/nx/gateway/verify',['json'=>['api_key'=>$this->apiKey,'trans_id'=>$r['trans_id']??null,'amount'=>$r['amount']??0]]);
        $d=json_decode((string)$res->getBody(),true);
        return (int)($d['code']??-99)===0 ? new PaymentResult(true,(string)($r['trans_id']??''),'پرداخت موفق') : new PaymentResult(false,null,'پرداخت ناموفق');
    }
}
