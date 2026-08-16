<?php

namespace Modules\IranPack\Gateways;

use GuzzleHttp\Client;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Contracts\PaymentResult;

class ZibalGateway implements PaymentGateway
{
    private string $base = 'https://gateway.zibal.ir';
    private string $merchant;
    private Client $http;

    public function __construct()
    {
        // در حالت تست می‌توان از merchant = 'zibal' استفاده کرد
        $this->merchant = (string) env('ZIBAL_MERCHANT', 'zibal');
        $this->http = new Client(['timeout' => 20]);
    }

    public function request(int $amount, string $callbackUrl, array $meta = []): string
    {
        $res = $this->http->post($this->base . '/v1/request', [
            'json' => [
                'merchant'    => $this->merchant,
                'amount'      => $amount,            // ریال
                'callbackUrl' => $callbackUrl,
                'description' => $meta['description'] ?? 'پرداخت سفارش',
                'mobile'      => $meta['mobile'] ?? null,
            ],
        ]);
        $data = json_decode((string) $res->getBody(), true);

        // result = 100 یعنی موفق
        if (($data['result'] ?? null) !== 100 || empty($data['trackId'])) {
            throw new \RuntimeException('خطا در ایجاد تراکنش زیبال: ' . ($data['message'] ?? 'نامشخص'));
        }

        return $this->base . '/start/' . $data['trackId'];
    }

    public function verify(array $request): PaymentResult
    {
        $trackId = $request['trackId'] ?? null;

        $res = $this->http->post($this->base . '/v1/verify', [
            'json' => [
                'merchant' => $this->merchant,
                'trackId'  => $trackId,
            ],
        ]);
        $data = json_decode((string) $res->getBody(), true);

        // result = 100 (موفق) یا 201 (قبلاً تأیید شده)
        if (in_array(($data['result'] ?? null), [100, 201], true)) {
            return new PaymentResult(true, (string) ($data['refNumber'] ?? $trackId), 'پرداخت موفق');
        }

        return new PaymentResult(false, null, $data['message'] ?? 'پرداخت ناموفق');
    }
}
