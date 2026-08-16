<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\WooBridge\Entities\SyncLog;

class ExportController extends Controller
{
    /** خروجی CSV با BOM (برای نمایش صحیح فارسی در Excel) */
    private function csv(string $filename, array $headers, \Closure $rows): StreamedResponse
    {
        return response()->stream(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, $headers);
            $rows($out);
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function customers(): StreamedResponse
    {
        return $this->csv('customers.csv',
            ['نام', 'ایمیل', 'موبایل', 'منبع', 'ارزش طول عمر'],
            function ($out) {
                Customer::chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $c) {
                        fputcsv($out, [$c->full_name, $c->email, $c->phone, $c->source, $c->lifetime_value]);
                    }
                });
            });
    }

    public function orders(): StreamedResponse
    {
        return $this->csv('orders.csv',
            ['شماره', 'مشتری', 'وضعیت', 'مبلغ', 'مالیات', 'منبع', 'تاریخ'],
            function ($out) {
                Order::with('customer')->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $o) {
                        fputcsv($out, [
                            $o->number, $o->customer->full_name ?? '', $o->status,
                            $o->total, $o->tax_total, $o->source,
                            optional($o->placed_at)->format('Y-m-d H:i'),
                        ]);
                    }
                });
            });
    }

    public function syncLogs(): StreamedResponse
    {
        return $this->csv('sync-logs.csv',
            ['ردیف', 'فروشگاه', 'نوع داده', 'شناسه در فروشگاه', 'مسیر', 'نتیجه', 'زمان', 'خطا'],
            function ($out) {
                SyncLog::with('connection')->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $l) {
                        fputcsv($out, [
                            $l->id,
                            $l->connection?->name,
                            $l->entityLabel(),
                            $l->woo_id,
                            $l->directionLabel(),
                            $l->statusLabel(),
                            $l->created_at?->format('Y-m-d H:i'),
                            $l->error,
                        ]);
                    }
                });
            });
    }
}
