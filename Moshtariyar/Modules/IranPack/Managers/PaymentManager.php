<?php

namespace Modules\IranPack\Managers;

use Modules\Core\Entities\Setting;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Gateways\ZarinpalGateway;
use Modules\IranPack\Gateways\ZibalGateway;
use Modules\IranPack\Gateways\IDPayGateway;
use Modules\IranPack\Gateways\NextPayGateway;
use Modules\IranPack\Gateways\PayPingGateway;
use Modules\IranPack\Gateways\SadadGateway;
use Modules\IranPack\Gateways\BehpardakhtGateway;
use Modules\IranPack\Gateways\SamanGateway;
use Modules\IranPack\Gateways\ParsianGateway;

/**
 * مدیریت درگاه‌های پرداخت ایرانی.
 */
class PaymentManager
{
    public const GATEWAYS = [
        'zarinpal'    => ZarinpalGateway::class,
        'zibal'       => ZibalGateway::class,
        'idpay'       => IDPayGateway::class,
        'nextpay'     => NextPayGateway::class,
        'payping'     => PayPingGateway::class,
        'sadad'       => SadadGateway::class,
        'behpardakht' => BehpardakhtGateway::class,
        'saman'       => SamanGateway::class,
        'parsian'     => ParsianGateway::class,
    ];

    public function driver(?string $name = null): PaymentGateway
    {
        $name = $name ?: $this->default();
        if (! isset(self::GATEWAYS[$name])) {
            throw new \InvalidArgumentException("درگاه پرداخت [{$name}] پشتیبانی نمی‌شود.");
        }
        return app(self::GATEWAYS[$name]);
    }

    public function default(): string
    {
        return (string) Setting::get('payment_default', env('PAYMENT_DEFAULT', 'zarinpal'));
    }

    /** فهرست درگاه‌های موجود (برای انتخاب در تنظیمات) */
    public function available(): array
    {
        return [
            'zarinpal'    => 'زرین‌پال',
            'zibal'       => 'زیبال',
            'idpay'       => 'آیدی‌پی',
            'nextpay'     => 'نکست‌پی',
            'payping'     => 'پی‌پینگ',
            'sadad'       => 'سداد (بانک ملی)',
            'behpardakht' => 'به‌پرداخت (بانک ملت)',
            'saman'       => 'سامان (بانک سامان)',
            'parsian'     => 'پارسیان (بانک پارسیان)',
        ];
    }
}
