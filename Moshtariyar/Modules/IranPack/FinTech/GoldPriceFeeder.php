<?php

namespace Modules\IranPack\FinTech;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Entities\Setting;
use Modules\Core\Support\Money;

class GoldPriceFeeder
{
    private const CACHE_KEY = 'fintech_live_gold_price_18k';
    private const CACHE_TTL = 120;

    public static function getLiveGoldPrice18k(): float
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::resolvePrice('gold');
        });
    }

    public static function getLiveSilverPrice(): float
    {
        return Cache::remember('fintech_live_silver_price', self::CACHE_TTL, function () {
            return self::resolvePrice('silver');
        });
    }

    private static function resolvePrice(string $metal): float
    {
        $modeKey = $metal === 'silver' ? 'silver_price_source_mode' : 'gold_price_source_mode';
        $mode = (string) Setting::get($modeKey, Setting::get('metal_price_source_mode', 'api_then_json'));

        $sequences = match ($mode) {
            'api' => ['api'],
            'json' => ['json'],
            'json_then_api' => ['json', 'api'],
            'manual' => [],
            default => ['api', 'json'],
        };

        foreach ($sequences as $source) {
            $price = $source === 'api'
                ? self::fetchFromApi($metal)
                : self::fetchFromJson($metal);

            if ($price !== null && $price > 0) {
                return $price;
            }
        }

        return self::fallbackPrice($metal);
    }

    private static function fetchFromApi(string $metal): ?float
    {
        try {
            $client = new Client(['timeout' => 5]);
            $res = $client->get('https://api.tgju.org/v1/widget/all');
            $data = json_decode((string) $res->getBody(), true);

            if (! is_array($data)) {
                return null;
            }

            if ($metal === 'gold' && isset($data['geram18']['p'])) {
                $raw = str_replace(',', '', (string) $data['geram18']['p']);
                $priceInToman = (float) $raw;
                if ($priceInToman > 10000000) {
                    $priceInToman = round($priceInToman / 10);
                }
                return Money::fromToman($priceInToman);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private static function fetchFromJson(string $metal): ?float
    {
        $urlKey = $metal === 'silver' ? 'silver_price_json_url' : 'gold_price_json_url';
        $url = trim((string) Setting::get($urlKey, Setting::get('metal_price_json_url', '')));
        if ($url === '') {
            return null;
        }

        $path = $metal === 'gold'
            ? (string) Setting::get('gold_price_json_path', Setting::get('metal_price_json_gold_path', 'gold'))
            : (string) Setting::get('silver_price_json_path', Setting::get('metal_price_json_silver_path', 'silver'));

        $unitKey = $metal === 'silver' ? 'silver_price_json_unit' : 'gold_price_json_unit';
        $jsonUnit = (string) Setting::get($unitKey, Setting::get('metal_price_json_unit', 'toman'));

        try {
            $client = new Client(['timeout' => 5]);
            $res = $client->get($url);
            $data = json_decode((string) $res->getBody(), true);
            if (! is_array($data)) {
                return null;
            }

            $value = self::readByPath($data, $path);
            if ($value === null || $value === '') {
                return null;
            }

            $number = (float) str_replace(',', '', (string) $value);
            if ($number <= 0) {
                return null;
            }

            if ($jsonUnit === 'rial' && Money::unit() === 'toman') {
                return round($number / 10);
            }

            if ($jsonUnit === 'toman' && Money::unit() === 'rial') {
                return round($number * 10);
            }

            return $number;
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private static function readByPath(array $data, string $path): mixed
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        $current = $data;
        foreach (explode('.', $path) as $part) {
            $part = trim($part);
            if ($part === '') {
                return null;
            }

            if (! is_array($current) || ! array_key_exists($part, $current)) {
                return null;
            }

            $current = $current[$part];
        }

        return $current;
    }

    private static function fallbackPrice(string $metal): float
    {
        if ($metal === 'silver') {
            $fallback = Setting::get('fintech_live_silver_fallback_price');
            if ($fallback !== null && $fallback !== '') {
                return (float) $fallback;
            }
            return Money::fromToman(85000);
        }

        $fallback = Setting::get('fintech_live_gold_fallback_price');
        if ($fallback !== null && $fallback !== '') {
            return (float) $fallback;
        }

        return Money::fromToman(4580000);
    }

    public static function sourceModeLabel(string $metal = 'gold'): string
    {
        $modeKey = $metal === 'silver' ? 'silver_price_source_mode' : 'gold_price_source_mode';
        return match ((string) Setting::get($modeKey, Setting::get('metal_price_source_mode', 'api_then_json'))) {
            'api' => 'فقط از ای‌پی‌آی',
            'json' => 'فقط از جیسون',
            'json_then_api' => 'اول جیسون، بعد ای‌پی‌آی',
            'manual' => 'فقط نرخ دستی',
            default => 'اول ای‌پی‌آی، بعد جیسون',
        };
    }

    public static function calculateMetalPrice(
        float $liveRate,
        float $weightGrams,
        float $ajratPerGram,
        bool $ajratIsPercent = false,
        float $profitRate = 0.07,
        float $taxRate = 0.10
    ): array {
        $rawMetalValue = $weightGrams * $liveRate;
        $totalAjrat = $ajratIsPercent
            ? ($rawMetalValue * ($ajratPerGram / 100))
            : ($weightGrams * $ajratPerGram);

        $storeProfit = ($rawMetalValue + $totalAjrat) * $profitRate;
        $taxableAmount = $totalAjrat + $storeProfit;
        $taxAmount = $taxableAmount * $taxRate;
        $finalPrice = round($rawMetalValue + $totalAjrat + $storeProfit + $taxAmount);

        return [
            'weight_grams'    => $weightGrams,
            'live_metal_rate' => $liveRate,
            'raw_metal_value' => round($rawMetalValue),
            'ajrat_cost'      => round($totalAjrat),
            'store_profit'    => round($storeProfit),
            'tax_amount'      => round($taxAmount),
            'final_price'     => $finalPrice,
        ];
    }

    public static function calculateJewelryPrice(
        float $weightGrams,
        float $ajratPerGram,
        bool $ajratIsPercent = false,
        float $profitRate = 0.07,
        float $taxRate = 0.10
    ): array {
        return self::calculateMetalPrice(
            self::getLiveGoldPrice18k(),
            $weightGrams,
            $ajratPerGram,
            $ajratIsPercent,
            $profitRate,
            $taxRate
        );
    }
}