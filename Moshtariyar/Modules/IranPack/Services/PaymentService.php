<?php

namespace Modules\IranPack\Services;

use Modules\Core\Entities\Order;
use Modules\IranPack\Entities\Payment;
use Modules\IranPack\Managers\PaymentManager;
use Modules\IranPack\Contracts\PaymentResult;

/**
 * هماهنگ‌کنندهٔ جریان پرداخت: ساخت تراکنش، هدایت به درگاه، تأیید بازگشت.
 */
class PaymentService
{
    public function __construct(private PaymentManager $gateways)
    {
    }

    /**
     * شروع پرداخت برای یک سفارش.
     * خروجی: [Payment $payment, string $redirectUrl]
     */
    public function start(Order $order, string $callbackUrl, ?string $gatewayName = null): array
    {
        $gatewayName = $gatewayName ?: $this->gateways->default();
        $order->loadMissing('customer');

        // مبلغ به ریال (در صورت ذخیرهٔ تومان × ۱۰ — اینجا فرض بر ریال بودن total)
        $amount = (int) round($order->total);

        $payment = Payment::create([
            'order_id'    => $order->id,
            'customer_id' => $order->customer_id,
            'gateway'     => $gatewayName,
            'amount'      => $amount,
            'status'      => 'pending',
            'description' => "پرداخت سفارش #{$order->number}",
            'mobile'      => $order->customer->phone ?? null,
        ]);

        // callback شامل شناسهٔ پرداخت تا در بازگشت بدانیم کدام تراکنش است
        $cb = $callbackUrl . (str_contains($callbackUrl, '?') ? '&' : '?') . 'payment=' . $payment->id;

        $gateway = $this->gateways->driver($gatewayName);
        $redirectUrl = $gateway->request($amount, $cb, [
            'description' => $payment->description,
            'mobile'      => $payment->mobile,
        ]);

        // ذخیرهٔ authority از URL بازگشتی (برای زرین‌پال در انتهای URL است؛ برای اطمینان در verify هم می‌گیریم)
        return [$payment, $redirectUrl];
    }

    /**
     * تأیید پرداخت پس از بازگشت از درگاه.
     */
    public function verify(Payment $payment, array $request): PaymentResult
    {
        if ($payment->status === 'paid') {
            return new PaymentResult(true, $payment->ref_id, 'قبلاً پرداخت شده');
        }

        $gateway = $this->gateways->driver($payment->gateway);

        // پارامترهای لازم هر درگاه را تکمیل می‌کنیم
        $request['amount'] = $payment->amount;

        $result = $gateway->verify($request);

        if ($result->success) {
            $payment->update([
                'status'  => 'paid',
                'ref_id'  => $result->refId,
                'paid_at' => now(),
                'meta'    => $request,
            ]);

            // به‌روزرسانی وضعیت سفارش → observer خودکار به ووکامرس هم اطلاع می‌دهد
            if ($payment->order) {
                $payment->order->update(['status' => 'processing']);
                // اجرای قوانین اتوماسیون رویداد «پرداخت موفق»
                try {
                    app(\Modules\Automation\Services\WorkflowEngine::class)->onPaymentPaid($payment->order);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } else {
            $payment->update(['status' => 'failed', 'meta' => $request]);
        }

        return $result;
    }
}
