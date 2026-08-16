<?php

namespace Modules\IranPack\Contracts;

class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $refId = null,
        public ?string $message = null,
    ) {
    }
}
