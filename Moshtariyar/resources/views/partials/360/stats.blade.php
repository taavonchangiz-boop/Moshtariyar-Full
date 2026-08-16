{{-- partial: 360/stats --}}
@php
    $rawDays = $stats['days_since_purchase'] ?? 999;
    $days = max(0, (int) round(abs($rawDays)));
    $health = $health ?? ['score' => 0, 'label' => 'نامشخص', 'color' => '#64748b'];
@endphp

<section class="customer360-stats-grid">
    <article class="customer360-stat-card">
        <span>ارزش طول عمر</span>
        <b>@money($stats['lifetime_value'] ?? 0)</b>
        <small>مجموع خریدهای معتبر مشتری</small>
    </article>

    <article class="customer360-stat-card">
        <span>سفارش موفق</span>
        <b>@fa($stats['completed_orders'] ?? 0)</b>
        <small>تعداد سفارش‌های تکمیل‌شده</small>
    </article>

    <article class="customer360-stat-card">
        <span>میانگین هر سفارش</span>
        <b>@money(max(0, (int) ($stats['aov'] ?? 0)))</b>
        <small>ارزش متوسط هر خرید معتبر</small>
    </article>

    <article class="customer360-stat-card">
        <span>آخرین خرید موفق</span>
        <b>@fa($days) روز پیش</b>
        <small>
            @if($days === 0)
                امروز خرید کرده است
            @elseif($days <= 30)
                مشتری فعال است
            @elseif($days > 90)
                نیازمند پیگیری فوری
            @else
                نیازمند مراقبت نرم
            @endif
        </small>
    </article>

    <article class="customer360-stat-card customer360-stat-health" style="--health-color: {{ $health['color'] ?? '#64748b' }};">
        <span>امتیاز سلامت</span>
        <b>@fa($health['score'] ?? 0)</b>
        <small>{{ $health['label'] ?? 'نامشخص' }}</small>
    </article>
</section>