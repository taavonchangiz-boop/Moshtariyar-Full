<?php

namespace Modules\WooBridge\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Entities\SyncLog;
use Modules\WooBridge\Jobs\BulkImportOrders;

class ConnectionController extends Controller
{
    public function index()
    {
        return WooConnection::select('id', 'name', 'store_url', 'is_active', 'last_sync_at')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:120',
            'store_url'       => 'required|url',
            'consumer_key'    => 'nullable|string',
            'consumer_secret' => 'nullable|string',
        ]);

        $data['webhook_secret'] = Str::random(40);

        $connection = WooConnection::create($data);

        return response()->json([
            'connection'   => $connection->only('id', 'name', 'store_url'),
            'webhook_url'  => url("/api/v1/woobridge/webhook/{$connection->id}"),
            'webhook_secret' => $connection->webhook_secret, // فقط یک‌بار نشان داده می‌شود
        ], 201);
    }

    /** شروع import تاریخی */
    public function import(WooConnection $connection)
    {
        BulkImportOrders::dispatch($connection->id, 1, 50);

        return response()->json(['ok' => true, 'message' => 'وارد کردن سفارش‌های تاریخی آغاز شد']);
    }

    /** ارسال مجدد یک رکورد ناموفق */
    public function resend(SyncLog $log)
    {
        $log->update(['status' => 'pending', 'error' => null]);
        \Modules\WooBridge\Jobs\ProcessWooWebhook::dispatch($log->id);

        return response()->json(['ok' => true]);
    }
}
