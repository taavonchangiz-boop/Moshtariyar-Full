{{-- partial: 360/header --}}
@php
    $initial = mb_substr($customer->full_name ?? 'م', 0, 1);
    $rfmColor = $rfm['color'] ?? '#64748b';
    $customerTypeLabel = ($customer->type ?? 'individual') === 'company' ? 'مشتری شرکتی' : 'مشتری شخصی';
    $sourceLabels = [
        'woocommerce' => 'فروشگاه',
        'manual' => 'ثبت دستی',
        'club_portal' => 'باشگاه مشتریان',
        'form' => 'فرم سایت',
        'website-form' => 'فرم سایت',
        'lead' => 'تبدیل از سرنخ',
    ];
    $sourceLabel = $sourceLabels[$customer->source ?? ''] ?? ($customer->source ?: 'نامشخص');
    $health = $health ?? ['score' => 0, 'label' => 'نامشخص', 'color' => '#64748b'];
    $loyalty = $loyalty ?? [];
    $stats = $stats ?? [];
    $lastOrderAt = $stats['last_order_at'] ?? null;
@endphp

<section class="customer360-hero-card">
    <div class="customer360-hero-main">
        <div class="customer360-avatar-wrap">
            <div class="customer360-avatar">{{ $initial }}</div>
            @if($customer->is_active ?? false)
                <span class="customer360-active-dot" title="فعال"></span>
            @endif
        </div>

        <div class="customer360-identity">
            <div class="customer360-kicker">پرونده کامل مشتری</div>
            <h2>{{ $customer->full_name }}</h2>
            <div class="customer360-contact-row">
                <span>📱 <b class="ltr">{{ $customer->phone ?: 'بدون شماره' }}</b></span>
                <span>✉️ <b class="email">{{ $customer->email ?: 'بدون ایمیل' }}</b></span>
            </div>
            <div class="customer360-tags">
                <span class="customer360-tag" style="--tag-color: {{ $rfmColor }};">{{ $rfm['segment'] ?? 'رفتار نامشخص' }}</span>
                <span class="customer360-tag" style="--tag-color: {{ $health['color'] ?? '#64748b' }};">سلامت: {{ $health['label'] ?? 'نامشخص' }}</span>
                <span class="customer360-tag customer360-tag-soft">{{ $customerTypeLabel }}</span>
                <span class="customer360-tag customer360-tag-soft">{{ $sourceLabel }}</span>
            </div>
        </div>
    </div>

    <div class="customer360-hero-dashboard">
        <div class="customer360-hero-mini">
            <span>ارزش مشتری</span>
            <b>@money($stats['lifetime_value'] ?? 0)</b>
        </div>
        <div class="customer360-hero-mini">
            <span>سفارش موفق</span>
            <b>@fa($stats['completed_orders'] ?? 0)</b>
        </div>
        <div class="customer360-hero-mini">
            <span>امتیاز باشگاه</span>
            <b>@fa($loyalty['points'] ?? 0)</b>
        </div>
        <div class="customer360-hero-mini">
            <span>آخرین خرید</span>
            <b>@if($lastOrderAt) @jdatetime($lastOrderAt) @else ثبت نشده @endif</b>
        </div>
    </div>

    <div class="customer360-actions">
        <a href="{{ url('/app/customers/'.$customer->id.'/edit') }}" class="customer360-action customer360-action-ghost">ویرایش اطلاعات</a>
        <a href="javascript:void(0)" onclick="openMessageModal()" class="customer360-action customer360-action-primary">ارسال پیام</a>
        <a href="javascript:void(0)" onclick="openOrderModal()" class="customer360-action customer360-action-success">ثبت سفارش</a>
    </div>
</section>