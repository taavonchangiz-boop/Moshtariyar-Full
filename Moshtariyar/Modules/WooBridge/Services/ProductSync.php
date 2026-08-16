<?php

namespace Modules\WooBridge\Services;

use Illuminate\Support\Str;
use Modules\Core\Entities\Product;
use Modules\Core\Services\ImageOptimizerService;
use Modules\WooBridge\Entities\IdMap;
use Modules\WooBridge\Support\SyncGuard;

class ProductSync
{
    public function __construct(private ?ImageOptimizerService $imageOptimizer = null)
    {
    }

    public function upsert(int $connectionId, array $woo): Product
    {
        $wooId = (int) ($woo['id'] ?? 0);
        $sku = trim((string) ($woo['sku'] ?? ''));
        $name = trim((string) ($woo['name'] ?? 'محصول'));

        return SyncGuard::muted(function () use ($connectionId, $woo, $wooId, $sku, $name) {
            $product = null;

            if ($wooId) {
                $crmId = IdMap::resolveCrmId($connectionId, 'product', $wooId);
                if ($crmId) {
                    $product = Product::find($crmId);
                }
            }

            if (! $product && $sku !== '') {
                $product = Product::where('sku', $sku)->first();
            }

            if (! $product && $name !== '') {
                $sameNameCount = Product::where('name', $name)->count();
                if ($sameNameCount === 1) {
                    $product = Product::where('name', $name)->first();
                }
            }

            $product ??= new Product();

            if ($sku !== '') {
                $product->sku = $sku;
            } elseif (! $product->exists) {
                $product->sku = null;
            }

            $images = $this->extractImages($woo['images'] ?? []);
            $images = $this->localizeImages($images);
            $attributes = $this->extractAttributes($woo['attributes'] ?? []);
            $categories = $this->extractNamedRows($woo['categories'] ?? []);
            $tags = $this->extractNamedRows($woo['tags'] ?? []);
            $regularPrice = (float) ($woo['regular_price'] ?? $woo['price'] ?? $product->regular_price ?? 0);
            $salePrice = isset($woo['sale_price']) && $woo['sale_price'] !== '' ? (float) $woo['sale_price'] : null;
            $finalPrice = (float) ($woo['price'] ?? ($salePrice ?: $regularPrice));

            $existingMeta = is_array($product->meta) ? $product->meta : [];
            $existingMeta['woocommerce'] = $this->buildWooCommerceSnapshot($woo, $images, $attributes, $categories, $tags);

            $product->name = $name !== '' ? $name : ($product->name ?? 'محصول');
            $product->slug = $woo['slug'] ?? ($product->slug ?: Str::slug($product->name));
            $product->category = $categories[0]['name'] ?? ($product->category ?? null);
            $product->product_type = $woo['type'] ?? ($product->product_type ?? 'simple');
            $product->status = $woo['status'] ?? ($product->status ?? 'publish');
            $product->short_description = array_key_exists('short_description', $woo) ? $woo['short_description'] : $product->short_description;
            $product->description = array_key_exists('description', $woo) ? $woo['description'] : $product->description;
            $product->image = $images['main'] ?? $product->image;
            $product->gallery = $images['gallery'] ?: $product->gallery;
            $product->attributes = $attributes ?: $product->attributes;
            $product->regular_price = $regularPrice;
            $product->sale_price = $salePrice;
            $product->price = $finalPrice;
            $product->stock = array_key_exists('stock_quantity', $woo) && $woo['stock_quantity'] !== null
                ? (int) $woo['stock_quantity']
                : $product->stock;
            $product->manage_stock = (bool) ($woo['manage_stock'] ?? $product->manage_stock ?? true);
            $product->stock_status = $woo['stock_status'] ?? ($product->stock_status ?? 'instock');
            $product->source = 'woocommerce';
            $product->external_url = $woo['permalink'] ?? $product->external_url;
            $product->is_active = (($woo['status'] ?? 'publish') === 'publish');
            $product->meta = $existingMeta;
            $product->save();

            if ($wooId) {
                IdMap::link($connectionId, 'product', $wooId, $product->id);
            }

            return $product;
        });
    }

    private function buildWooCommerceSnapshot(array $woo, array $images, array $attributes, array $categories, array $tags): array
    {
        return [
            'id' => $woo['id'] ?? null,
            'parent_id' => $woo['parent_id'] ?? null,
            'name' => $woo['name'] ?? null,
            'slug' => $woo['slug'] ?? null,
            'permalink' => $woo['permalink'] ?? null,
            'type' => $woo['type'] ?? null,
            'status' => $woo['status'] ?? null,
            'featured' => $woo['featured'] ?? null,
            'catalog_visibility' => $woo['catalog_visibility'] ?? null,
            'description' => $woo['description'] ?? null,
            'short_description' => $woo['short_description'] ?? null,
            'sku' => $woo['sku'] ?? null,
            'price' => $woo['price'] ?? null,
            'regular_price' => $woo['regular_price'] ?? null,
            'sale_price' => $woo['sale_price'] ?? null,
            'on_sale' => $woo['on_sale'] ?? null,
            'purchasable' => $woo['purchasable'] ?? null,
            'total_sales' => $woo['total_sales'] ?? null,
            'virtual' => $woo['virtual'] ?? null,
            'downloadable' => $woo['downloadable'] ?? null,
            'downloads' => $woo['downloads'] ?? [],
            'download_limit' => $woo['download_limit'] ?? null,
            'download_expiry' => $woo['download_expiry'] ?? null,
            'external_url' => $woo['external_url'] ?? null,
            'button_text' => $woo['button_text'] ?? null,
            'tax_status' => $woo['tax_status'] ?? null,
            'tax_class' => $woo['tax_class'] ?? null,
            'manage_stock' => $woo['manage_stock'] ?? null,
            'stock_quantity' => $woo['stock_quantity'] ?? null,
            'stock_status' => $woo['stock_status'] ?? null,
            'backorders' => $woo['backorders'] ?? null,
            'backorders_allowed' => $woo['backorders_allowed'] ?? null,
            'backordered' => $woo['backordered'] ?? null,
            'sold_individually' => $woo['sold_individually'] ?? null,
            'weight' => $woo['weight'] ?? null,
            'dimensions' => $woo['dimensions'] ?? [],
            'shipping_required' => $woo['shipping_required'] ?? null,
            'shipping_taxable' => $woo['shipping_taxable'] ?? null,
            'shipping_class' => $woo['shipping_class'] ?? null,
            'shipping_class_id' => $woo['shipping_class_id'] ?? null,
            'reviews_allowed' => $woo['reviews_allowed'] ?? null,
            'average_rating' => $woo['average_rating'] ?? null,
            'rating_count' => $woo['rating_count'] ?? null,
            'related_ids' => $woo['related_ids'] ?? [],
            'upsell_ids' => $woo['upsell_ids'] ?? [],
            'cross_sell_ids' => $woo['cross_sell_ids'] ?? [],
            'purchase_note' => $woo['purchase_note'] ?? null,
            'categories' => $categories,
            'tags' => $tags,
            'images' => $images,
            'attributes' => $attributes,
            'default_attributes' => $woo['default_attributes'] ?? [],
            'variations' => $woo['variations'] ?? [],
            'grouped_products' => $woo['grouped_products'] ?? [],
            'menu_order' => $woo['menu_order'] ?? null,
            'price_html' => $woo['price_html'] ?? null,
            'date_created' => $woo['date_created'] ?? null,
            'date_modified' => $woo['date_modified'] ?? null,
            'date_on_sale_from' => $woo['date_on_sale_from'] ?? null,
            'date_on_sale_to' => $woo['date_on_sale_to'] ?? null,
            'meta_data' => $woo['meta_data'] ?? [],
            'raw' => $woo,
        ];
    }

    private function extractImages(array $images): array
    {
        $items = [];
        $urls = [];

        foreach ($images as $index => $image) {
            if (empty($image['src'])) {
                continue;
            }

            $src = trim((string) $image['src']);
            if ($src === '') {
                continue;
            }

            $urls[] = $src;
            $items[] = [
                'id' => $image['id'] ?? null,
                'src' => $src,
                'original_src' => $src,
                'name' => $image['name'] ?? null,
                'alt' => $image['alt'] ?? null,
                'position' => $index,
                'is_local' => false,
                'date_created' => $image['date_created'] ?? null,
                'date_modified' => $image['date_modified'] ?? null,
            ];
        }

        return [
            'main' => $urls[0] ?? null,
            'gallery' => array_values(array_slice($urls, 1)),
            'all' => $urls,
            'items' => $items,
        ];
    }

    private function localizeImages(array $images): array
    {
        if (! $this->imageOptimizer || empty($images['items']) || ! is_array($images['items'])) {
            return $images;
        }

        $localizedItems = [];
        $localizedUrls = [];

        foreach ($images['items'] as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $originalSrc = trim((string) ($item['original_src'] ?? $item['src'] ?? ''));
            $finalSrc = trim((string) ($item['src'] ?? ''));

            if ($originalSrc !== '' && str_starts_with($originalSrc, 'http')) {
                try {
                    $directory = $index === 0 ? 'img/products' : 'img/products/gallery';
                    $storedPath = $this->imageOptimizer->storeRemoteAsWebp($originalSrc, $directory);
                    if ($storedPath) {
                        $finalSrc = $storedPath;
                        $item['src'] = $storedPath;
                        $item['local_src'] = $storedPath;
                        $item['is_local'] = true;
                    }
                } catch (\Throwable $e) {
                    $item['is_local'] = false;
                    $item['localize_error'] = $e->getMessage();
                }
            }

            if ($finalSrc !== '') {
                $localizedUrls[] = $finalSrc;
            }
            $localizedItems[] = $item;
        }

        return [
            'main' => $localizedUrls[0] ?? ($images['main'] ?? null),
            'gallery' => array_values(array_slice($localizedUrls, 1)),
            'all' => $localizedUrls ?: ($images['all'] ?? []),
            'items' => $localizedItems,
        ];
    }

    private function extractAttributes(array $attributes): array
    {
        $out = [];
        foreach ($attributes as $attribute) {
            $name = trim((string) ($attribute['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $options = $attribute['options'] ?? [];
            if (! is_array($options)) {
                $options = [$options];
            }

            $out[] = [
                'id' => $attribute['id'] ?? null,
                'name' => $name,
                'options' => array_values(array_filter(array_map(fn ($option) => trim((string) $option), $options), fn ($option) => $option !== '')),
                'position' => $attribute['position'] ?? null,
                'visible' => (bool) ($attribute['visible'] ?? true),
                'variation' => (bool) ($attribute['variation'] ?? false),
            ];
        }

        return $out;
    }

    private function extractNamedRows(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $out[] = [
                'id' => $row['id'] ?? null,
                'name' => $name,
                'slug' => $row['slug'] ?? null,
            ];
        }

        return $out;
    }
}