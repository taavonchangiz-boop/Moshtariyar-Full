<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryReportHistory extends Model
{
    protected $table = 'recovery_report_histories';

    protected $fillable = [
        'report_date',
        'j_today',
        'j_from',
        'j_to',
        'business_id',
        'recovered_7d',
        'recovered_revenue_7d',
        'recovered_30d',
        'recovered_revenue_30d',
        'recovery_rate',
        'total_at_risk',
        'total_recovered_12m',
        'total_count_12m',
        'monthly_labels',
        'monthly_recovered',
        'monthly_count',
        'recovered_list',
        'emails_sent',
        'summary',
        'insights_json',
    ];

    protected $casts = [
        'report_date' => 'date',
        'monthly_labels' => 'array',
        'monthly_recovered' => 'array',
        'monthly_count' => 'array',
        'recovered_list' => 'array',
        'emails_sent' => 'array',
        'insights_json' => 'array',
        'recovered_7d' => 'integer',
        'recovered_revenue_7d' => 'integer',
        'recovered_30d' => 'integer',
        'recovered_revenue_30d' => 'integer',
        'recovery_rate' => 'integer',
        'total_at_risk' => 'integer',
        'total_recovered_12m' => 'integer',
        'total_count_12m' => 'integer',
    ];

    public function getMonthlyDataAttribute(): array
    {
        $labels = $this->monthly_labels ?? [];
        $recovered = $this->monthly_recovered ?? [];
        $count = $this->monthly_count ?? [];
        $merged = [];
        foreach ($labels as $idx => $label) {
            $merged[] = [
                'label' => $label,
                'recovered' => $recovered[$idx] ?? 0,
                'count' => $count[$idx] ?? 0,
            ];
        }
        return $merged;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->recovered_7d > 0) return 'فعال و پربازده';
        if ($this->recovered_30d > 0) return 'پایدار';
        return 'بدون بازگشت این هفته';
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->recovered_7d > 5) return '#10b981';
        if ($this->recovered_7d > 0) return '#0ea5e9';
        if ($this->recovered_30d > 0) return '#f59e0b';
        return '#94a3b8';
    }

    public function scopeForBusiness($q, ?int $businessId)
    {
        if ($businessId) {
            return $q->where(function($qq) use ($businessId) {
                $qq->where('business_id', $businessId)->orWhereNull('business_id');
            });
        }
        return $q;
    }
}