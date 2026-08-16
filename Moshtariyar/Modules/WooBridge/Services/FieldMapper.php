<?php

namespace Modules\WooBridge\Services;

use Modules\WooBridge\Entities\FieldMap;

/**
 * نگاشت دلخواه فیلدهای ووکامرس به فیلدهای CRM (طبق wb_field_maps).
 * اگر نگاشتی تعریف نشده باشد، payload بدون تغییر برمی‌گردد.
 */
class FieldMapper
{
    public function map(int $connectionId, string $entity, array $payload): array
    {
        $maps = FieldMap::where('connection_id', $connectionId)
            ->where('entity', $entity)
            ->get();

        if ($maps->isEmpty()) {
            return $payload;
        }

        $result = $payload;
        foreach ($maps as $m) {
            $value = data_get($payload, $m->woo_field);
            if ($value !== null) {
                data_set($result, $m->crm_field, $value);
            }
        }

        return $result;
    }

    /** کلیدهای یکتای تعریف‌شده برای یک موجودیت */
    public function uniqueKeys(int $connectionId, string $entity): array
    {
        return FieldMap::where('connection_id', $connectionId)
            ->where('entity', $entity)
            ->where('is_unique_key', true)
            ->pluck('crm_field')
            ->all();
    }
}
