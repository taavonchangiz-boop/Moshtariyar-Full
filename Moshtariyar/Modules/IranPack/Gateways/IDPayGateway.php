<?php
namespace Modules\IranPack\Gateways;
use GuzzleHttp\Client;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Contracts\PaymentResult;
use Modules\Core\Entities\Setting;

class IDPayGateway implements PaymentGateway
{
    private Client $http; private string $apiKey;
    public function __construct(){ $this->apiKey=(string)Setting::get('idpay_api_key',env('IDPAY_API_KEY','')); $this->http=new Client(['timeout'=>20]); }
    public function request(int $amount,string $cb,array $meta=[]):string{
        $res=$this->http->post('https://api.idpay.ir/v1.1/payment',['headers'=>['X-API-KEY'=>$this->apiKey,'Content-Type'=>'application/json'],
            'json'=>['order_id'=>uniqid(),'amount'=>$amount,'callback'=>$cb,'phone'=>$meta['mobile']??null,'desc'=>$meta['description']??'']]);
        $d=json_decode((string)$res->getBody(),true);
        if(empty($d['link'])) throw new \RuntimeException('خطا در اتصال به درگاه آیدی‌پی');
        return $d['link'];
    }
    public function verify(array $r):PaymentResult{
        $res=$this->http->post('https://api.idpay.ir/v1.1/payment/verify',['headers'=>['X-API-KEY'=>$this->apiKey,'Content-Type'=>'application/json'],
            'json'=>['id'=>$r['id']??null,'order_id'=>$r['order_id']??null]]);
        $d=json_decode((string)$res->getBody(),true);
        return ((int)($d['status']??0)===100||(int)($d['status']??0)===200)
            ? new PaymentResult(true,(string)($d['track_id']??''),'پرداخت موفق')
            : new PaymentResult(false,null,'پرداخت ناموفق');
    }
}
