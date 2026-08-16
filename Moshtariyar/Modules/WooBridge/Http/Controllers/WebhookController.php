<?php

namespace Modules\WooBridge\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Entities\SyncLog;
use Modules\WooBridge\Jobs\ProcessWooWebhook;

class WebhookController extends Controller
{
    public function ping(WooConnection $connection)
    {
        return response()->json([
            'ok' => true,
            'route' => 'woobridge.test',
            'connection_id' => $connection->id,
            'connection_active' => (bool) $connection->is_active,
            'message' => 'مسیر آزمون آماده است. برای آزمون کامل باید درخواست با امضای امنیتی درست از افزونه وردپرس فرستاده شود.',
        ]);
    }

    public function test(Request $request, WooConnection $connection)
    {
        if (! $connection->is_active) {
            return response()->json(['ok' => false, 'error' => 'این اتصال غیرفعال است.'], 403);
        }
        $raw = $request->getContent();
        if (! $this->verifySignature($request, $connection, $raw)) {
            return response()->json(['ok' => false, 'error' => 'امضای امنیتی درست نیست.'], 401);
        }
        $connection->update(['last_sync_at' => now()]);
        return response()->json([
            'ok' => true,
            'message' => 'آزمون اتصال با موفقیت انجام شد.',
            'connection_id' => $connection->id,
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * دریافت Webhook ووکامرس.
     * مسیر: POST /api/v1/woobridge/webhook/{connection}
     *
     * نکتهٔ cPanel: پاسخ را سریع برمی‌گردانیم و پردازش سنگین را به صف می‌سپاریم
     * تا ووکامرس timeout نشود.
     */
    public function handle(Request $request, WooConnection $connection)
    {
        if (! $connection->is_active) {
            return response()->json(['error' => 'این اتصال غیرفعال است.'], 403);
        }

        $raw = $request->getContent();

        // 1) تأیید امضای HMAC (امنیت)
        if (! $this->verifySignature($request, $connection, $raw)) {
            return response()->json(['error' => 'امضای امنیتی درست نیست.'], 401);
        }

        // 2) شناسایی نوع رویداد و idempotency
        $topic      = $request->header('x-wc-webhook-topic', 'unknown');   // مثل order.updated
        $deliveryId = $request->header('x-wc-webhook-delivery-id');
        $entity     = $this->entityFromTopic($topic);

        // اگر این delivery قبلاً با موفقیت پردازش شده، نادیده بگیر
        if ($deliveryId && SyncLog::where('delivery_id', $deliveryId)->where('status', 'success')->exists()) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $payload = json_decode($raw, true) ?: [];

        $connection->update(['last_sync_at' => now()]);

        // 3) ثبت لاگ pending
        $log = SyncLog::create([
            'connection_id' => $connection->id,
            'entity'        => $entity,
            'woo_id'        => $payload['id'] ?? null,
            'delivery_id'   => $deliveryId,
            'direction'     => 'in',
            'status'        => 'pending',
            'payload'       => $payload,
        ]);

        // 4) سپردن به صف (database driver → توسط Cron پردازش می‌شود)
        ProcessWooWebhook::dispatch($log->id);

        // 5) پاسخ سریع
        return response()->json(['ok' => true, 'queued' => true], 202);
    }

    private function verifySignature(Request $request, WooConnection $connection, string $raw): bool
    {
        $signature = $request->header('x-wc-webhook-signature');
        if (! $signature) {
            return false;
        }
        $expected = base64_encode(hash_hmac('sha256', $raw, $connection->webhook_secret, true));

        return hash_equals($expected, $signature);
    }

    private function entityFromTopic(string $topic): string
    {
        return match (true) {
            str_starts_with($topic, 'order')    => 'order',
            str_starts_with($topic, 'customer') => 'customer',
            str_starts_with($topic, 'product')  => 'product',
            default                             => 'order',
        };
    }
}
