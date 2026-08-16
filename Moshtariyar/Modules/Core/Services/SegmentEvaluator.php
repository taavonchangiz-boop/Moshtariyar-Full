<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Segment;
use Modules\Core\Entities\SegmentCondition;
use Modules\Core\Support\Money;

class SegmentEvaluator
{
    /**
     * فیلدهای قابل استفاده در شرط‌ها — برچسب‌ها پویا بر اساس واحد پول تنظیمات
     */
    public static function fieldLabels(): array
    {
        $unit = Money::unitLabel(); // تومان یا ریال
        return [
            'total_spent'    => 'مجموع خرید (' . $unit . ')',
            'orders_count'   => 'تعداد خرید',
            'last_order_days'=> 'روز از آخرین خرید',
            'created_at_days'=> 'روز از تاریخ عضویت',
            'rfm_group'      => 'گروه رفتاری',
            'source'         => 'منبع جذب',
            'city'           => 'شهر',
            'lifetime_value' => 'ارزش طول عمر (' . $unit . ')',
        ];
    }

    public const OPERATOR_LABELS = [
        '>='       => 'بزرگتر یا مساوی',
        '<='       => 'کوچکتر یا مساوی',
        '>'        => 'بزرگتر از',
        '<'        => 'کوچکتر از',
        '='        => 'مساوی',
        '!='       => 'مخالف',
        'between'  => 'بین',
        'in'       => 'یکی از',
        'days_ago' => 'در X روز گذشته',
        'older_than_days' => 'بیشتر از X روز پیش',
    ];

    public const RFM_GROUPS = [
        'champions'   => 'قهرمانان',
        'loyal'       => 'وفاداران',
        'potential'   => 'در حال رشد',
        'new'         => 'تازه‌وارد',
        'at_risk'     => 'در آستانه ریزش',
        'hibernating' => 'خفته',
        'lost'        => 'از دست رفته',
        'no_purchase' => 'بدون خرید',
    ];

    public function evaluate(Segment $segment): int
    {
        $conditions = $segment->conditions;
        if ($conditions->isEmpty()) {
            $segment->update(['members_count' => 0, 'evaluated_at' => now()]);
            return 0;
        }

        $logic = $segment->logic;
        DB::table('segment_member')->where('segment_id', $segment->id)->delete();

        $ids = $this->evaluateConditions($conditions, $logic);

        if (empty($ids)) {
            $segment->update(['members_count' => 0, 'evaluated_at' => now()]);
            return 0;
        }

        $inserts = [];
        foreach ($ids as $cid) {
            $inserts[] = ['segment_id' => $segment->id, 'customer_id' => $cid, 'created_at' => now(), 'updated_at' => now()];
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('segment_member')->insert($chunk);
        }

        $count = count($ids);
        $segment->update(['members_count' => $count, 'evaluated_at' => now()]);
        return $count;
    }

    private function evaluateConditions($conditions, string $logic): array
    {
        $baseSql = $this->rfmBaseSql();
        $whereParts = [];
        $bindings = [];

        foreach ($conditions as $cond) {
            $part = $this->buildConditionSql($cond, $bindings);
            if ($part) $whereParts[] = $part;
        }

        if (empty($whereParts)) return [];

        $glue = $logic === 'or' ? ' OR ' : ' AND ';
        $whereClause = implode($glue, $whereParts);
        $sql = "SELECT DISTINCT x.id FROM ({$baseSql}) x WHERE {$whereClause}";
        $results = DB::select($sql, $bindings);
        return array_map(fn($r) => $r->id, $results);
    }

    private function buildConditionSql(SegmentCondition $cond, array &$bindings): ?string
    {
        $field = $cond->field;
        $op    = $cond->operator;
        $val   = $cond->value;
        $val2  = $cond->value2;

        return match ($field) {
            'total_spent'    => $this->numericCondition('total_spent', $op, $val, $val2, $bindings),
            'orders_count'   => $this->numericCondition('orders_count', $op, $val, $val2, $bindings),
            'lifetime_value' => $this->numericCondition('total_spent', $op, $val, $val2, $bindings),
            'last_order_days'=> $this->daysCondition('last_order_days', $op, $val, $bindings),
            'created_at_days'=> $this->daysCondition('created_at_days', $op, $val, $bindings),
            'rfm_group'      => $this->rfmGroupCondition($val, $bindings),
            'source'         => $this->stringCondition('source', $op, $val, $bindings),
            'city'           => $this->stringCondition('city', $op, $val, $bindings),
            default          => null,
        };
    }

    private function numericCondition(string $col, string $op, $val, $val2, array &$bindings): ?string
    {
        $v = (float) $val;
        if ($op === 'between' && $val2 !== null) { $v2 = (float) $val2; $bindings[] = $v; $bindings[] = $v2; return "{$col} BETWEEN ? AND ?"; }
        $bindings[] = $v;
        return "{$col} {$op} ?";
    }

    private function daysCondition(string $col, string $op, $val, array &$bindings): ?string
    {
        $days = (int) $val;
        if ($op === 'days_ago') { $bindings[] = $days; return "{$col} <= ?"; }
        if ($op === 'older_than_days') { $bindings[] = $days; return "{$col} > ?"; }
        return $this->numericCondition($col, $op, $val, null, $bindings);
    }

    private function rfmGroupCondition($val, array &$bindings): ?string
    {
        return match ($val) {
            'no_purchase' => 'orders_count = 0',
            'champions'   => 'orders_count >= 8 AND total_spent >= 50000000 AND last_order_at_days <= 30',
            'loyal'       => 'orders_count >= 4 AND last_order_at_days <= 30',
            'potential'   => 'orders_count BETWEEN 1 AND 3 AND last_order_at_days <= 30',
            'new'         => 'orders_count = 1 AND last_order_at_days <= 30',
            'at_risk'     => 'orders_count >= 3 AND last_order_at_days > 60 AND last_order_at_days <= 120',
            'hibernating' => 'orders_count > 0 AND last_order_at_days > 120 AND last_order_at_days <= 365',
            'lost'        => 'orders_count > 0 AND last_order_at_days > 365',
            default       => null,
        };
    }

    private function stringCondition(string $col, string $op, $val, array &$bindings): ?string
    {
        if ($op === 'in') {
            $values = array_map('trim', explode(',', $val));
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            foreach ($values as $v) $bindings[] = $v;
            return "{$col} IN ({$placeholders})";
        }
        $bindings[] = $val;
        return "{$col} {$op} ?";
    }

    private function rfmBaseSql(): string
    {
        return "SELECT c.id, c.full_name, c.phone, c.email, c.source, c.city, c.created_at, c.lifetime_value, o.last_order_at, o.first_order_at, COALESCE(o.orders_count, 0) orders_count, COALESCE(o.total_spent, 0) total_spent, CASE WHEN o.last_order_at IS NULL THEN 99999 ELSE DATEDIFF(NOW(), o.last_order_at) END AS last_order_at_days, DATEDIFF(NOW(), c.created_at) AS created_at_days FROM customers c LEFT JOIN (SELECT customer_id, MAX(placed_at) last_order_at, MIN(placed_at) first_order_at, COUNT(*) orders_count, COALESCE(SUM(total), 0) total_spent FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id) o ON o.customer_id = c.id";
    }
}
