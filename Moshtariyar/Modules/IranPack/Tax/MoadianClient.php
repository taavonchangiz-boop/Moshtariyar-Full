<?php

namespace Modules\IranPack\Tax;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Modules\IranPack\Entities\TaxInvoice;

/**
 * کلاینت اتصال به سامانهٔ مودیان (پایانه‌های فروشگاهی).
 *
 * ── جریان کامل ارسال فاکتور به سامانهٔ مودیان ──
 *
 *   ۱. دریافت nonce از سرور (getNonce)
 *   ۲. ساخت بستهٔ استاندارد صورت‌حساب (buildPacket)
 *   ۳. امضای دیجیتال با کلید خصوصی به فرمت JWS (sign)
 *   ۴. ارسال به سامانه و دریافت شمارهٔ مرجع (sendInvoice)
 *   ۵. استعلام وضعیت با شمارهٔ مرجع (inquiry)
 *
 * 🔑 پیش‌نیازها (در فایل .env):
 *   MOADIAN_MEMORY_ID       = شناسهٔ ۶ رقمی حافظهٔ مالیاتی
 *   MOADIAN_PRIVATE_KEY_PATH = مسیر فایل کلید خصوصی (PEM)
 *   MOADIAN_CERTIFICATE_PATH = مسیر فایل گواهی (PEM) — اختیاری
 *   MOADIAN_BASE_URL        = آدرس پایهٔ سامانه (پیش‌فرض: tp.tax.gov.ir)
 */
class MoadianClient
{
    private string $base;
    private string $memoryId;
    private ?string $privateKeyPath;
    private ?string $certificatePath;
    private Client $http;

    public function __construct()
    {
        $this->base            = rtrim((string) env('MOADIAN_BASE_URL', 'https://tp.tax.gov.ir'), '/');
        $this->memoryId        = (string) env('MOADIAN_MEMORY_ID', '');
        $this->privateKeyPath  = env('MOADIAN_PRIVATE_KEY_PATH');
        $this->certificatePath = env('MOADIAN_CERTIFICATE_PATH');
        $this->http            = new Client([
            'base_uri' => $this->base . '/',
            'timeout'  => 30,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * شناسهٔ حافظهٔ مالیاتی
     */
    public function memoryId(): string
    {
        return $this->memoryId;
    }

    /**
     * آیا تمام پیش‌نیازهای اتصال فراهم است؟
     */
    public function isConfigured(): bool
    {
        return $this->memoryId !== ''
            && $this->privateKeyPath
            && is_file($this->privateKeyPath);
    }

    /**
     * دریافت nonce از سامانه برای شروع فرایند امضا.
     *
     * nonce یک رشتهٔ تصادفی است که سامانه برای هر درخواست امضا صادر می‌کند.
     *
     * @return string|null  nonce دریافتی یا null در صورت خطا
     */
    public function getNonce(): ?string
    {
        try {
            $res = $this->http->get('req/api/self-tsp/sync/GET_TOKEN', [
                'query' => ['timeToLive' => 30],
            ]);

            $data = json_decode((string) $res->getBody(), true);
            $nonce = $data['result']['nonce'] ?? ($data['nonce'] ?? null);

            if ($nonce) {
                Log::info('📡 nonce از سامانهٔ مودیان دریافت شد', ['nonce' => substr($nonce, 0, 12) . '...']);
            }

            return $nonce;
        } catch (\Throwable $e) {
            Log::error('❌ دریافت nonce از سامانهٔ مودیان ناموفق بود: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * ارسال صورت‌حساب به سامانهٔ مودیان.
     *
     * مراحل:
     *  ۱. دریافت nonce
     *  ۲. ساخت بستهٔ استاندارد
     *  ۳. امضای دیجیتال (JWS)
     *  ۴. ارسال به سامانه
     *
     * @param  TaxInvoice  $invoice  فاکتور آمادهٔ ارسال
     * @return array                پاسخ سامانه شامل referenceNumber
     *
     * @throws \RuntimeException  اگر پیکربندی ناقص باشد
     * @throws \Exception         اگر ارسال با خطا مواجه شود
     */
    public function sendInvoice(TaxInvoice $invoice): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(
                'اتصال سامانهٔ مودیان پیکربندی نشده است.' . PHP_EOL
                . 'لطفاً مقادیر MOADIAN_MEMORY_ID و MOADIAN_PRIVATE_KEY_PATH را در فایل .env تنظیم کنید.'
            );
        }

        // ── ۱. دریافت nonce ──
        $nonce = $this->getNonce();
        if (! $nonce) {
            throw new \RuntimeException(
                'دریافت nonce از سامانهٔ مودیان ناموفق بود. لطفاً اتصال شبکه و تنظیمات را بررسی کنید.'
            );
        }

        // ── ۲. ساخت بستهٔ استاندارد صورت‌حساب ──
        $packet = $this->buildPacket($invoice);

        // ── ۳. امضای دیجیتال بسته با nonce ──
        $signedPacket = $this->sign($packet, $nonce);

        // ── ۴. ارسال به سامانه ──
        try {
            $res = $this->http->post('req/api/self-tsp/async/normal-enqueue', [
                'json' => $signedPacket,
            ]);

            $body = (string) $res->getBody();
            $data = json_decode($body, true) ?: [];

            $statusCode = $res->getStatusCode();
            if ($statusCode >= 400) {
                $errorMsg = $data['errors'][0]['message']
                    ?? $data['error']
                    ?? 'خطای نامشخص از سامانهٔ مودیان';

                Log::error('❌ سامانهٔ مودیان خطا برگرداند', [
                    'status'   => $statusCode,
                    'response' => $body,
                ]);

                throw new \RuntimeException('خطای سامانهٔ مودیان (کد ' . $statusCode . '): ' . $errorMsg);
            }

            Log::info('✅ فاکتور با موفقیت به سامانهٔ مودیان ارسال شد', [
                'reference' => $data['result'][0]['referenceNumber'] ?? '—',
                'tax_id'    => $invoice->tax_id,
            ]);

            return $data;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $resBody = (string) $e->getResponse()->getBody();
            Log::error('❌ خطای کلاینت در ارسال به مودیان: ' . $resBody);

            throw new \RuntimeException('خطا در ارسال به سامانهٔ مودیان: ' . $resBody);
        }
    }

    /**
     * استعلام وضعیت یک یا چند صورت‌حساب با شمارهٔ مرجع.
     *
     * @param  string|array  $referenceNumbers  شمارهٔ مرجع یا آرایه‌ای از شماره‌های مرجع
     * @return array                            پاسخ سامانه
     */
    public function inquiry($referenceNumbers): array
    {
        $refs = is_array($referenceNumbers)
            ? implode(',', $referenceNumbers)
            : (string) $referenceNumbers;

        $res = $this->http->get('req/api/self-tsp/sync/inquiry-by-reference-id', [
            'query' => ['referenceIds' => $refs],
        ]);

        $data = json_decode((string) $res->getBody(), true) ?: [];

        Log::info('📋 استعلام وضعیت فاکتور از مودیان', [
            'refs'   => $refs,
            'status' => $data['result'][0]['status'] ?? '—',
        ]);

        return $data;
    }

    /**
     * استعلام وضعیت با شناسهٔ مالیاتی (taxid).
     *
     * @param  string  $taxId  شناسهٔ ۲۲ رقمی مالیاتی
     * @return array
     */
    public function inquiryByTaxId(string $taxId): array
    {
        $res = $this->http->get('req/api/self-tsp/sync/inquiry-by-uid', [
            'query' => ['uidList' => $taxId],
        ]);

        return json_decode((string) $res->getBody(), true) ?: [];
    }

    /**
     * دریافت اطلاعات مؤدی (شناسهٔ اقتصادی) برای اعتبارسنجی.
     *
     * @param  string  $economicCode  کد اقتصادی مؤدی
     * @return array
     */
    public function getTaxpayerInfo(string $economicCode): array
    {
        $res = $this->http->get('req/api/self-tsp/sync/GET_ECONOMIC_CODE_INFORMATION', [
            'query' => ['economicCode' => $economicCode],
        ]);

        return json_decode((string) $res->getBody(), true) ?: [];
    }

    // ═══════════════════════════════════════════════
    //  متدهای درونی (private)
    // ═══════════════════════════════════════════════

    /**
     * ساخت بستهٔ استاندارد صورت‌حساب الکترونیکی مطابق فرمت سازمان مالیاتی.
     */
    private function buildPacket(TaxInvoice $invoice): array
    {
        $totalAmount    = (int) ($invoice->total_amount ?? 0);
        $discount       = (int) ($invoice->discount ?? 0);
        $vatAmount      = (int) ($invoice->vat_amount ?? 0);
        $payable        = (int) ($invoice->payable ?? 0);
        $afterDiscount  = max(0, $totalAmount - $discount);

        $issuedAt = $invoice->issued_at instanceof \DateTimeInterface
            ? $invoice->issued_at->getTimestamp() * 1000
            : now()->getTimestamp() * 1000;

        $taxId = $invoice->tax_id
            ?: TaxIdGenerator::generate($this->memoryId, (int) ($invoice->id ?? 0) + 100000, $invoice->issued_at);

        return [
            'header' => [
                'taxid'   => $taxId,
                'indatim' => $issuedAt,
                'indati2m'=> $issuedAt,
                'inty'    => (int) ($invoice->invoice_type ?: 1),
                'inp'     => (int) ($invoice->invoice_pattern ?: 1),
                'ins'     => 1,
                'setm'    => (int) ($invoice->settlement_type ?: 1),
                'tprdis'  => $totalAmount,
                'tdis'    => $discount,
                'tadis'   => $afterDiscount,
                'tvam'    => $vatAmount,
                'tbill'   => $payable,
                'tonw'    => 0,
                'torv'    => 0,
                'tocv'    => 0,
            ],
            'body' => $this->normalizeItems($invoice->items ?? []),
            'payments' => [
                [
                    'iinn' => $this->memoryId,
                    'acn'  => $this->memoryId,
                    'trmn' => $this->memoryId,
                    'trn'  => $invoice->serial ?: ($taxId),
                    'pcn'  => $this->memoryId,
                    'pid'  => $this->memoryId,
                    'pdt'  => $issuedAt,
                ],
            ],
        ];
    }

    /**
     * همگام‌سازی اقلام فاکتور با فرمت مورد انتظار سامانهٔ مودیان.
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $amount      = (int) ($item['amount'] ?? $item['total_amount'] ?? $item['payable'] ?? 0);
            $discount    = (int) ($item['discount'] ?? 0);
            $vatAmount   = (int) ($item['vat'] ?? $item['vat_amount'] ?? 0);
            $lineTotal   = $amount - $discount + $vatAmount;
            $vatRate     = (float) ($item['vat_rate'] ?? Vat::rate());

            $normalized[] = [
                'sstid' => (string) ($item['sstid'] ?? $item['sku'] ?? $item['product_id'] ?? ''),
                'sstt'  => (string) ($item['title'] ?? $item['sstt'] ?? $item['name'] ?? 'کالا/خدمت'),
                'am'    => (int) ($item['am'] ?? $item['quantity'] ?? $item['qty'] ?? 1),
                'mu'    => (int) ($item['mu'] ?? 14),
                'fee'   => (int) ($item['fee'] ?? $item['unit_price'] ?? $item['price'] ?? 0),
                'prdis' => $amount,
                'dis'   => $discount,
                'vra'   => (int) round($vatRate * 100),
                'vam'   => $vatAmount,
                'tsstam'=> $lineTotal,
            ];
        }

        return $normalized;
    }

    /**
     * امضای دیجیتال بستهٔ مالیاتی با کلید خصوصی — JWS (JSON Web Signature).
     *
     * ── فرایند امضا مطابق استاندارد سامانهٔ مودیان ──
     *
     *  ۱. ساخت سرآیند (header) شامل alg=RS256 و typ=JWT
     *  ۲. ساخت محموله (payload) شامل nonce + بستهٔ اصلی + memoryId
     *  ۳. کدگذاری Base64URL سرآیند و محموله
     *  ۴. اتصال با نقطه: header.payload
     *  ۵. امضای رشته با openssl_sign و کلید خصوصی (SHA-256)
     *  ۶. کدگذاری Base64URL امضا
     *  ۷. ساخت JWS نهایی: header.payload.signature
     *
     * @param  array   $packet  بستهٔ استاندارد صورت‌حساب
     * @param  string  $nonce   nonce دریافتی از سامانه
     * @return array            بستهٔ امضاشده برای ارسال نهایی
     */
    private function sign(array $packet, string $nonce): array
    {
        // ═══ ۱. بارگذاری کلید خصوصی ═══
        $privateKey = $this->loadPrivateKey();
        if (! $privateKey) {
            throw new \RuntimeException(
                'بارگذاری کلید خصوصی ناموفق بود. لطفاً مسیر MOADIAN_PRIVATE_KEY_PATH را بررسی کنید.' . PHP_EOL
                . 'مسیر فعلی: ' . ($this->privateKeyPath ?: 'تنظیم نشده')
            );
        }

        // ═══ ۲. ساخت سرآیند JWS ═══
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $cert = $this->loadCertificate();
        if ($cert) {
            $header['x5c'] = [$cert];
        }

        // ═══ ۳. ساخت محموله JWS ═══
        $payload = [
            'nonce'    => $nonce,
            'data'     => $packet,
            'memoryId' => $this->memoryId,
        ];

        // ═══ ۴. کدگذاری Base64URL ═══
        $headerB64  = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // ═══ ۵. رشتهٔ قابل امضا ═══
        $signingInput = $headerB64 . '.' . $payloadB64;

        // ═══ ۶. امضای دیجیتال ═══
        $signature = '';
        $result = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $result) {
            $errorMsg = openssl_error_string() ?: 'خطای نامشخص در امضای دیجیتال';
            Log::error('❌ امضای دیجیتال بستهٔ مودیان ناموفق بود: ' . $errorMsg);
            throw new \RuntimeException('امضای دیجیتال ناموفق بود: ' . $errorMsg);
        }

        // ═══ ۷. کدگذاری Base64URL امضا ═══
        $signatureB64 = $this->base64UrlEncode($signature);

        // ═══ ۸. ساخت JWS نهایی ═══
        $jws = $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;

        Log::info('🔐 امضای JWS با موفقیت تولید شد', [
            'length' => strlen($jws),
            'memory' => $this->memoryId,
        ]);

        // ═══ ۹. بستهٔ نهایی برای ارسال ═══
        return [
            'packet'     => $packet,
            'signature'  => $jws,
            'signedData' => $jws,
            'memoryId'   => $this->memoryId,
        ];
    }

    /**
     * بارگذاری کلید خصوصی از فایل PEM.
     *
     * @return \OpenSSLAsymmetricKey|false
     */
    private function loadPrivateKey()
    {
        if (! $this->privateKeyPath || ! is_file($this->privateKeyPath)) {
            Log::error('❌ فایل کلید خصوصی یافت نشد', ['path' => $this->privateKeyPath]);
            return false;
        }

        $keyContent = file_get_contents($this->privateKeyPath);

        return openssl_pkey_get_private($keyContent);
    }

    /**
     * بارگذاری گواهی (certificate) از فایل PEM در صورت وجود.
     *
     * @return string|null  گواهی به صورت Base64 خالص یا null
     */
    private function loadCertificate(): ?string
    {
        if (! $this->certificatePath || ! is_file($this->certificatePath)) {
            return null;
        }

        $content = file_get_contents($this->certificatePath);
        $content = preg_replace('/-----.*?-----/', '', $content);
        $content = preg_replace('/\s+/', '', $content);

        return $content ?: null;
    }

    /**
     * کدگذاری Base64URL (سازگار با URL — بدون +، / و =).
     *
     * Base64URL تفاوتش با Base64 معمولی:
     *   +  →  -
     *   /  →  _
     *   =  →  (padding حذف می‌شود)
     *
     * @param  string  $data  دادهٔ خام برای کدگذاری
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}