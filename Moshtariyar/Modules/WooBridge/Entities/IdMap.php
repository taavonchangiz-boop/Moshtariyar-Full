<?php

namespace Modules\WooBridge\Entities;

use Illuminate\Database\Eloquent\Model;

class IdMap extends Model
{
    protected $table = 'wb_id_map';

    protected $fillable = ['connection_id', 'entity', 'woo_id', 'crm_id'];

    /** crm_id را برای یک woo_id مشخص پیدا کن (یا null) */
    public static function resolveCrmId(int $connectionId, string $entity, int $wooId): ?int
    {
        return static::where('connection_id', $connectionId)
            ->where('entity', $entity)
            ->where('woo_id', $wooId)
            ->value('crm_id');
    }

    public static function link(int $connectionId, string $entity, int $wooId, int $crmId): void
    {
        static::updateOrCreate(
            ['connection_id' => $connectionId, 'entity' => $entity, 'woo_id' => $wooId],
            ['crm_id' => $crmId],
        );
    }
}
