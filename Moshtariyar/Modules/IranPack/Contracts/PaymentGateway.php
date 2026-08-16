<?php

namespace Modules\IranPack\Contracts;

interface PaymentGateway
{
    /** درخواست پرداخت؛ خروجی: URL هدایت کاربر به درگاه */
    public function request(int $amount, string $callbackUrl, array $meta = []): string;

    /** تأیید پرداخت پس از بازگشت از درگاه */
    public function verify(array $request): PaymentResult;
}
