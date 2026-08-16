<?php

namespace Modules\IranPack\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxInvoice extends Model
{
    protected $table = 'tax_invoices';

    protected $fillable = [
        'order_id', 'customer_id', 'serial', 'tax_id', 'reference_number',
        'invoice_kind', 'invoice_type', 'invoice_pattern', 'settlement_type',
        'total_amount', 'discount', 'vat_amount', 'payable',
        'status', 'items', 'response', 'error', 'notes', 'issued_at', 'due_at',
    ];

    protected $casts = [
        'items'     => 'array',
        'response'  => 'array',
        'issued_at' => 'datetime',
        'due_at'    => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Entities\Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Entities\Customer::class);
    }

    public function isProforma(): bool
    {
        return ($this->invoice_kind ?? 'official') === 'proforma';
    }
}