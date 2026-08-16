<?php

namespace Modules\IranPack\Sms;

use GuzzleHttp\Client;
use Modules\IranPack\Contracts\SmsProvider;
use Modules\Core\Entities\Setting;

/**
 * پنل پیامک SMS.IR (نسخهٔ API v1).
 * پیش‌فرض برنامه.
 */
class SmsIrProvider implements SmsProvider
{
    private string $apiKey;
    private string $lineNumber;
    private Client $http;

    public function __construct()
    {
        $this->apiKey = $this->resolveApiKey();
        $this->lineNumber = $this->resolveLineNumber();
        $this->http = new Client([
            'base_uri' => 'https://api.sms.ir/v1/',
            'timeout'  => 20,
            'headers'  => [
                'X-API-KEY'    => $this->apiKey,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function resolveApiKey(): string
    {
        $keys = [
            'smsir_api_key', 'sms_smsir_api_key', 'sms_smsir_api',
            'smsir_api', 'sms_api_key_smsir', 'smsir_key'
        ];
        foreach ($keys as $k) {
            $v = (string) Setting::get($k, '');
            if ($v !== '') return $v;
        }
        return (string) env('SMSIR_API_KEY', '');
    }

    private function resolveLineNumber(): string
    {
        $keys = [
            'smsir_line_number', 'sms_smsir_line_number', 'smsir_line',
            'sms_sender', 'sms_line', 'smsir_sender'
        ];
        foreach ($keys as $k) {
            $v = (string) Setting::get($k, '');
            if ($v !== '') return $v;
        }
        return (string) env('SMSIR_LINE_NUMBER', '');
    }

    public function send(string $to, string $message): bool
    {
        if (empty($this->apiKey) || empty($this->lineNumber)) {
            \Illuminate\Support\Facades\Log::warning('SMS.IR تنظیمات ناقص: api_key یا lineNumber خالی است');
            return false;
        }

        try {
            $res = $this->http->post('send/bulk', [
                'json' => [
                    'lineNumber'  => $this->lineNumber,
                    'messageText' => $message,
                    'mobiles'     => [$to],
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            return (int) ($data['status'] ?? 0) === 1;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SMS.IR ارسال ناموفق: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPattern(string $to, string $patternCode, array $tokens): bool
    {
        if (empty($this->apiKey)) return false;
        try {
            $parameters = [];
            foreach ($tokens as $name => $value) {
                $parameters[] = ['name' => (string) $name, 'value' => (string) $value];
            }

            $res = $this->http->post('send/verify', [
                'json' => [
                    'mobile'     => $to,
                    'templateId' => (int) $patternCode,
                    'parameters' => $parameters,
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            return (int) ($data['status'] ?? 0) === 1;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SMS.IR الگو ناموفق: ' . $e->getMessage());
            return false;
        }
    }
}

