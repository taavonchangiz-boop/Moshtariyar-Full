<?php

namespace Modules\IranPack\Sms;

use GuzzleHttp\Client;
use Modules\IranPack\Contracts\SmsProvider;
use Modules\Core\Entities\Setting;

class KavenegarProvider implements SmsProvider
{
    private string $apiKey;
    private string $sender;
    private Client $http;

    public function __construct()
    {
        $this->apiKey = $this->resolveApiKey();
        $this->sender = $this->resolveSender();
        $this->http = new Client(['timeout' => 20]);
    }

    private function resolveApiKey(): string
    {
        $keys = ['kavenegar_api_key','sms_kavenegar_api_key','kavenegar_key','sms_kavenegar_key','kavenegar_api'];
        foreach ($keys as $k) {
            $v = (string) Setting::get($k, '');
            if ($v !== '') return $v;
        }
        return (string) env('KAVENEGAR_API_KEY', '');
    }

    private function resolveSender(): string
    {
        $keys = ['kavenegar_sender','sms_kavenegar_sender','kavenegar_line','sms_sender','sms_line','kavenegar_from'];
        foreach ($keys as $k) {
            $v = (string) Setting::get($k, '');
            if ($v !== '') return $v;
        }
        return (string) env('KAVENEGAR_SENDER', '');
    }

    public function send(string $to, string $message): bool
    {
        if (empty($this->apiKey)) {
            \Illuminate\Support\Facades\Log::warning('Kavenegar تنظیمات ناقص: api_key خالی');
            return false;
        }

        try {
            $url = "https://api.kavenegar.com/v1/{$this->apiKey}/sms/send.json";
            $res = $this->http->post($url, [
                'form_params' => [
                    'receptor' => $to,
                    'sender'   => $this->sender,
                    'message'  => $message,
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            return ($data['return']['status'] ?? 0) === 200;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Kavenegar ارسال ناموفق: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPattern(string $to, string $patternCode, array $tokens): bool
    {
        if (empty($this->apiKey)) return false;
        try {
            $url = "https://api.kavenegar.com/v1/{$this->apiKey}/verify/lookup.json";
            $params = ['receptor' => $to, 'template' => $patternCode];
            $i = 1;
            foreach ($tokens as $value) {
                $params['token' . ($i === 1 ? '' : $i)] = $value;
                $i++;
            }
            $res = $this->http->post($url, ['form_params' => $params]);
            $data = json_decode((string) $res->getBody(), true);
            return ($data['return']['status'] ?? 0) === 200;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Kavenegar الگو ناموفق: ' . $e->getMessage());
            return false;
        }
    }
}

