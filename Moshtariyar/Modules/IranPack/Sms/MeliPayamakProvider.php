<?php

namespace Modules\IranPack\Sms;

use GuzzleHttp\Client;
use Modules\IranPack\Contracts\SmsProvider;
use Modules\Core\Entities\Setting;

class MeliPayamakProvider implements SmsProvider
{
    private string $username;
    private string $password;
    private string $from;
    private Client $http;

    public function __construct()
    {
        $this->username = $this->resolveUsername();
        $this->password = $this->resolvePassword();
        $this->from     = $this->resolveFrom();
        $this->http = new Client(['timeout' => 20]);
    }

    private function resolveUsername(): string
    {
        $keys = ['melipayamak_username','sms_melipayamak_username','sms_melipayamak_user','melipayamak_user','sms_faraz_user','melipayamak_api'];
        foreach ($keys as $k) {
            $v = (string) Setting::get($k, '');
            if ($v !== '') return $v;
        }
        return (string) env('MELIPAYAMAK_USERNAME', '');
    }

    private function resolvePassword(): string
    {
        $keys = ['melipayamak_password','sms_melipayamak_password','sms_melipayamak_pass','melipayamak_pass'];
        foreach ($keys as $k) {
            $v = (string) Setting::get($k, '');
            if ($v !== '') return $v;
        }
        return (string) env('MELIPAYAMAK_PASSWORD', '');
    }

    private function resolveFrom(): string
    {
        $keys = ['melipayamak_from','sms_melipayamak_from','melipayamak_line','sms_sender','sms_line','melipayamak_sender'];
        foreach ($keys as $k) {
            $v = (string) Setting::get($k, '');
            if ($v !== '') return $v;
        }
        return (string) env('MELIPAYAMAK_FROM', '');
    }

    public function send(string $to, string $message): bool
    {
        if (empty($this->username) || empty($this->password)) {
            \Illuminate\Support\Facades\Log::warning('ملی‌پیامک تنظیمات ناقص: username یا password خالی');
            return false;
        }

        try {
            $res = $this->http->post('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
                'form_params' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'to'       => $to,
                    'from'     => $this->from,
                    'text'     => $message,
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            return (int) ($data['RetStatus'] ?? 0) === 1;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ملی‌پیامک ارسال ناموفق: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPattern(string $to, string $patternCode, array $tokens): bool
    {
        if (empty($this->username) || empty($this->password)) return false;
        try {
            $res = $this->http->post('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber', [
                'form_params' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'to'       => $to,
                    'bodyId'   => (int) $patternCode,
                    'text'     => implode(';', array_values($tokens)),
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            return isset($data['Value']) && (int) $data['Value'] > 0;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ملی‌پیامک الگو ناموفق: ' . $e->getMessage());
            return false;
        }
    }
}