<?php

namespace Modules\WooBridge\Observers;

use Modules\Core\Entities\Product;
use Modules\WooBridge\Jobs\PushProduct;
use Modules\WooBridge\Jobs\PushProductStock;
use Modules\WooBridge\Support\SyncGuard;

class ProductObserver
{
    public function updated(Product $product): void
    {
        if (SyncGuard::isMuted()) {
            return;
        }

        $productFields = [
            'name',
            'sku',
            'slug',
            'product_type',
            'status',
            'short_description',
            'description',
            'price',
            'regular_price',
            'sale_price',
            'manage_stock',
            'stock',
            'stock_status',
            'external_url',
            'is_active',
        ];

        if ($product->wasChanged($productFields)) {
            PushProduct::dispatch($product->id);
            return;
        }

        if ($product->wasChanged('stock')) {
            PushProductStock::dispatch($product->id);
        }
    }
}