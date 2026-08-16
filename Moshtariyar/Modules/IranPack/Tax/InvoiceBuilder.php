<?php

namespace Modules\IranPack\Tax;

use Modules\Core\Entities\Order;
use Modules\IranPack\Entities\TaxInvoice;

/**
 * ساخت فاکتور رسمی (TaxInvoice) از روی یک سفارش CRM،
 * با محاسبهٔ ارزش افزوده و آماده‌سازی اقلام به فرمت مودیان.
 */
class InvoiceBuilder
{
    public function fromOrder(Order $order): TaxInvoice
    {
        $order->loadMissing(['items', 'customer']);

        $vatRate = Vat::rate();
        $items = [];
        $totalBeforeVat = 0;
        $totalVat = 0;

        foreach ($order->items as $line) {
            $lineBase = (float) $line->line_total;          // مبلغ ردیف (بدون مالیات)
            $lineVat  = Vat::amount($lineBase);
            $totalBeforeVat += $lineBase;
            $totalVat += $lineVat;

            $items[] = [
                'sstid' => $line->sku ?? '',                 // شناسهٔ کالا/خدمت
                'sstt'  => $line->name,                      // شرح
                'am'    => (float) $line->qty,               // تعداد
                'fee'   => (int) round($line->unit_price),   // فی واحد
                'prdis' => (int) round($lineBase),           // مبلغ پس از تخفیف
                'dis'   => 0,                                 // تخفیف
                'vra'   => $vatRate * 100,                    // نرخ مالیات (درصد)
                'vam'   => $lineVat,                          // مبلغ مالیات
                'tsstam'=> (int) round($lineBase + $lineVat), // جمع ردیف با مالیات
            ];
        }

        $serial = ((int) ($order->id)) + 100000;
        $issuedAt = now();

        $invoice = new TaxInvoice([
            'order_id'       => $order->id,
            'customer_id'    => $order->customer_id,
            'serial'         => (string) $serial,
            'invoice_type'   => 1,
            'invoice_pattern'=> 1,
            'settlement_type'=> 1,
            'total_amount'   => (int) round($totalBeforeVat),
            'discount'       => 0,
            'vat_amount'     => (int) round($totalVat),
            'payable'        => (int) round($totalBeforeVat + $totalVat),
            'status'         => 'draft',
            'items'          => $items,
            'issued_at'      => $issuedAt,
        ]);

        // تولید شناسهٔ یکتای مالیاتی
        $memoryId = (string) env('MOADIAN_MEMORY_ID', '000000');
        $invoice->tax_id = TaxIdGenerator::generate($memoryId, $serial, $issuedAt);
        $invoice->save();

        return $invoice;
    }
}
