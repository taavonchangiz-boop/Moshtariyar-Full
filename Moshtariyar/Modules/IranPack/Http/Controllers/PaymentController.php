<?php

namespace Modules\IranPack\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Order;
use Modules\IranPack\Entities\Payment;
use Modules\IranPack\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service)
    {
    }

    /** فهرست تراکنش‌ها */
    public function index()
    {
        $payments = Payment::with('order')->latest('id')->paginate(20);
        return view('app.payments', compact('payments'));
    }

    /** شروع پرداخت برای یک سفارش (از پنل) */
    public function start(Request $request, Order $order)
    {
        $gateway = $request->input('gateway'); // اختیاری
        $callback = url('/app/payments/callback');

        try {
            [$payment, $redirectUrl] = $this->service->start($order, $callback, $gateway);
            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            return back()->with('status', 'در ساخت درخواست پرداخت خطا پیش آمد: ' . $e->getMessage());
        }
    }

    /** بازگشت از درگاه */
    public function callback(Request $request)
    {
        $payment = Payment::find($request->query('payment'));
        if (! $payment) {
            return redirect('/app/payments')->with('status', 'تراکنش یافت نشد.');
        }

        $result = $this->service->verify($payment, $request->all());

        $msg = $result->success
            ? "پرداخت موفق بود. کد رهگیری: {$result->refId}"
            : "پرداخت ناموفق بود: {$result->message}";

        return redirect('/app/payments')->with('status', $msg);
    }
}
