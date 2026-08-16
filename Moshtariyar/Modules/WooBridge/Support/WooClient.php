<?php

namespace Modules\WooBridge\Support;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Modules\WooBridge\Entities\WooConnection;

/**
 * کلاینت ارتباط با ووکامرس.
 * مشتری‌یار با این کلاس اطلاعات فروشگاه را می‌خواند و در موارد لازم تغییرات تأییدشده را به فروشگاه برمی‌گرداند.
 */
class WooClient
{
    private Client $http;

    public function __construct(private WooConnection $connection)
    {
        $this->http = new Client([
            'base_uri' => rtrim($connection->store_url, '/') . '/wp-json/wc/v3/',
            'auth' => [$connection->consumer_key, $connection->consumer_secret],
            'timeout' => 25,
            'connect_timeout' => 10,
            'force_ip_resolve' => 'v4',
            'http_errors' => true,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'MoshtariYar-WooBridge/1.1',
            ],
        ]);
    }

    public function get(string $path, array $query = []): array
    {
        $response = $this->http->get($path, ['query' => $query]);
        return json_decode((string) $response->getBody(), true) ?: [];
    }

    public function put(string $path, array $payload = []): array
    {
        $response = $this->http->put($path, ['json' => $payload]);
        return json_decode((string) $response->getBody(), true) ?: [];
    }

    /** خواندن صفحه‌ای سفارش‌ها */
    public function getOrders(int $page = 1, int $perPage = 50, ?Carbon $modifiedAfter = null): array
    {
        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'orderby' => 'modified',
            'order' => 'desc',
        ];

        if ($modifiedAfter) {
            $query['modified_after'] = $modifiedAfter->copy()->utc()->toIso8601String();
        }

        return $this->get('orders', $query);
    }

    public function getCustomers(int $page = 1, int $perPage = 50, ?Carbon $modifiedAfter = null): array
    {
        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'orderby' => 'id',
            'order' => 'desc',
        ];

        if ($modifiedAfter) {
            $query['modified_after'] = $modifiedAfter->copy()->utc()->toIso8601String();
        }

        return $this->get('customers', $query);
    }

    public function getProducts(int $page = 1, int $perPage = 50, ?Carbon $modifiedAfter = null): array
    {
        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'orderby' => 'modified',
            'order' => 'desc',
        ];

        if ($modifiedAfter) {
            $query['modified_after'] = $modifiedAfter->copy()->utc()->toIso8601String();
        }

        return $this->get('products', $query);
    }

    /** برگرداندن وضعیت سفارش به ووکامرس */
    public function updateOrderStatus(int $wooOrderId, string $status): array
    {
        return $this->put("orders/{$wooOrderId}", ['status' => $status]);
    }

    /** برگرداندن موجودی کالا به ووکامرس */
    public function updateProductStock(int $wooProductId, int $stock): array
    {
        return $this->put("products/{$wooProductId}", ['stock_quantity' => $stock, 'manage_stock' => true]);
    }

    /** برگرداندن اطلاعات اصلی کالا به ووکامرس */
    public function updateProduct(int $wooProductId, array $payload): array
    {
        return $this->put("products/{$wooProductId}", $payload);
    }
}