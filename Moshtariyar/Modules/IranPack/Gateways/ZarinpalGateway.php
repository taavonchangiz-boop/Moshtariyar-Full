<?php

namespace Modules\IranPack\Gateways;

use GuzzleHttp\Client;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Contracts\PaymentResult;

class ZarinpalGateway implements PaymentGateway
{
    private string $base;
    private string $merchantId;
    private Client $http;

    public function __construct()
    {
        $sandbox = (bool) env('ZARINPAL_SANDBOX', false);
        $this->base = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://payment.zarinpal.com';
        $this->merchantId = (string) env('ZARINPAL_MERCHANT_ID', '');
        $this->http = new Client(['timeout' => 20]);
    }

    public function request(int $amount, string $callbackUrl, array $meta = []): string
    {
        $res = $this->http->post($this->base . '/pg/v4/payment/request.json', [
            'json' => [
                'merchant_id' => $this->merchantId,
                'amount'      => $amount,            // ریال
                'callback_url'=> $callbackUrl,
                'description' => $meta['description'] ?? 'پرداخت سفارش',
                'metadata'    => array_filter([
                    'mobile' => $meta['mobile'] ?? null,
                    'email'  => $meta['email'] ?? null,
                ]),
            ],
        ]);
        $data = json_decode((string) $res->getBody(), true);
        $authority = $data['data']['authority'] ?? null;

        if (! $authority) {
            throw new \RuntimeException('خطا در ایجاد تراکنش زرین‌پال');
        }

        return $this->base . '/pg/StartPay/' . $authority;
    }

    public function verify(array $request): PaymentResult
    {
        $authority = $request['Authority'] ?? null;
        $amount    = (int) ($request['amount'] ?? 0);

        $res = $this->http->post($this->base . '/pg/v4/payment/verify.json', [
            'json' => [
                'merchant_id' => $this->merchantId,
                'amount'      => $amount,
                'authority'   => $authority,
            ],
        ]);
        $data = json_decode((string) $res->getBody(), true);
        $code = $data['data']['code'] ?? null;

        // 100 = موفق، 101 = قبلاً تأیید شده
        if (in_array($code, [100, 101], true)) {
            return new PaymentResult(true, (string) ($data['data']['ref_id'] ?? ''), 'پرداخت موفق');
        }

        return new PaymentResult(false, null, 'پرداخت ناموفق');
    }
}
