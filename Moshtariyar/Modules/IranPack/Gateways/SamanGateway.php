<?php
namespace Modules\IranPack\Gateways;
use GuzzleHttp\Client;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Contracts\PaymentResult;
use Modules\Core\Entities\Setting;

/**
 * درگاه saman (بانکی). تنظیمات: ترمینال/کلید در بخش تنظیمات.
 * نکته: این درگاه‌های بانکی نیازمند پیکربندی ترمینال هستند؛ ساختار آماده است
 * و در زمان اتصال واقعی با مستندات بانک تکمیل می‌شود.
 */
class SamanGateway implements PaymentGateway
{
    private Client $http; private string $terminal; private string $key;
    public function __construct(){
        $this->terminal=(string)Setting::get('saman_terminal','');
        $this->key=(string)Setting::get('saman_key','');
        $this->http=new Client(['timeout'=>25]);
    }
    public function request(int $amount,string $cb,array $meta=[]):string{
        if(!$this->terminal) throw new \RuntimeException('درگاه saman پیکربندی نشده است.');
        // ساختار آماده برای اتصال واقعی به API بانک
        throw new \RuntimeException('اتصال درگاه saman نیازمند پیکربندی ترمینال بانکی است.');
    }
    public function verify(array $r):PaymentResult{
        return new PaymentResult(false,null,'verify درگاه saman باید مطابق مستندات بانک تکمیل شود.');
    }
}
