@extends('layouts.customer_portal')
@section('title','اعلان‌ها')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-notifications.css') }}">
@php
    $typeLabels = [
        'info' => ['عمومی', '🔔', '#64748b'],
        'profile' => ['پروفایل', '👤', '#0ea5e9'],
        'wheel' => ['گردونه', '🎡', '#f59e0b'],
        'ticket' => ['پشتیبانی', '🎫', '#8b5cf6'],
        'coupon' => ['کد تخفیف', '٪', '#10b981'],
        'mission' => ['مأموریت', '🎯', '#ef4444'],
    ];
@endphp
<div class="club-notifications-page">
    <section class="club-notifications-hero">
        <div>
            <span class="club-notifications-eyebrow">مرکز پیام‌های باشگاه</span>
            <h1>اعلان‌ها، پاداش‌ها و پیام‌های مهم شما 🔔</h1>
            <p>همه پیام‌های مهم باشگاه، جایزه‌های گردونه، وضعیت درخواست‌ها، کدهای تخفیف و پاداش‌های مأموریت در این بخش ثبت می‌شود.</p>
            <div class="club-notifications-actions">
                <a class="btn" href="{{ route('club.rewards') }}">جایزه‌های من</a>
                <a class="btn btn-ghost" href="{{ route('club.tickets') }}">درخواست‌های پشتیبانی</a>
                <a class="btn btn-ghost" href="{{ route('club.journey') }}">سفر من</a>
            </div>
        </div>
        <div class="club-notifications-score">
            <div><span>کل اعلان‌ها</span><b>@fa(number_format($stats['total'] ?? 0))</b><small>همه پیام‌های ثبت‌شده</small></div>
            <div><span>خوانده‌نشده</span><b>@fa(number_format($stats['unread'] ?? 0))</b><small>پس از ورود به صفحه خوانده می‌شوند</small></div>
            <div><span>پشتیبانی و گردونه</span><b>@fa(number_format(($stats['ticket'] ?? 0) + ($stats['wheel'] ?? 0)))</b><small>پیام‌های مهم عملیاتی</small></div>
        </div>
    </section>

    <section class="club-notifications-grid">
        <article class="club-notification-card is-accent" style="--notification-color:#0ea5e9;">
            <header>
                <div>
                    <span>فهرست اعلان‌ها</span>
                    <h2>پیام‌های ثبت‌شده</h2>
                </div>
                <span class="club-notification-badge">@fa(number_format($items->total())) پیام</span>
            </header>

            <form method="get" class="club-notification-filter">
                <div><label>جستجو</label><input name="search" value="{{ request('search') }}" placeholder="جستجو در عنوان یا متن اعلان"></div>
                <div><label>وضعیت</label><select name="status"><option value="">همه</option><option value="unread" @selected(request('status')==='unread')>خوانده‌نشده</option><option value="read" @selected(request('status')==='read')>خوانده‌شده</option></select></div>
                <div><label>نوع پیام</label><select name="type"><option value="">همه</option>@foreach($types as $type)<option value="{{ $type->type }}" @selected(request('type')===$type->type)>{{ $typeLabels[$type->type][0] ?? $type->type }}</option>@endforeach</select></div>
                <button class="btn">اعمال</button>
                @if(request()->hasAny(['search','status','type']))<a class="btn btn-ghost" href="{{ route('club.notifications') }}">پاک کردن</a>@endif
            </form>

            <div class="club-notification-list">
                @forelse($items as $notification)
                    @php
                        [$typeLabel, $typeIcon, $typeColor] = $typeLabels[$notification->type] ?? [$notification->type ?: 'عمومی', '🔔', '#64748b'];
                        $isUnread = ! $notification->read_at;
                    @endphp
                    <article class="club-notification-row {{ $isUnread ? 'is-unread' : '' }}" style="--row-color: {{ $typeColor }};">
                        <div class="club-notification-icon">{{ $typeIcon }}</div>
                        <div>
                            <span class="club-notification-chip">{{ $typeLabel }}</span>
                            <h3>{{ $notification->title }}</h3>
                            <p>{{ $notification->body }}</p>
                            <small>زمان ثبت: @jdatetime($notification->created_at)</small>
                        </div>
                        @if($notification->url)
                            <a class="btn btn-ghost" href="{{ $notification->url }}">مشاهده</a>
                        @else
                            <span class="club-notification-badge {{ $isUnread ? 'is-ok' : 'is-muted' }}">{{ $isUnread ? 'جدید' : 'خوانده شده' }}</span>
                        @endif
                    </article>
                @empty
                    <div class="club-notification-empty">اعلانی با این شرایط پیدا نشد.</div>
                @endforelse
            </div>
            <div style="margin-top:1rem">{{ $items->links() }}</div>
        </article>

        <aside class="club-notification-card is-accent" style="--notification-color:#10b981;">
            <header>
                <div>
                    <span>دسته‌بندی پیام‌ها</span>
                    <h2>انواع اعلان‌های شما</h2>
                </div>
            </header>
            <div class="club-notification-type-list">
                @forelse($types as $type)
                    @php [$typeLabel, $typeIcon, $typeColor] = $typeLabels[$type->type] ?? [$type->type ?: 'عمومی', '🔔', '#64748b']; @endphp
                    <article class="club-notification-type-row" style="--row-color: {{ $typeColor }};">
                        <div class="club-notification-icon">{{ $typeIcon }}</div>
                        <div><h3>{{ $typeLabel }}</h3><p>@fa(number_format($type->total_count)) پیام ثبت‌شده</p></div>
                        <a class="club-notification-badge" href="{{ route('club.notifications', ['type' => $type->type]) }}">فیلتر</a>
                    </article>
                @empty
                    <div class="club-notification-empty">هنوز دسته‌ای برای اعلان‌ها ثبت نشده است.</div>
                @endforelse
                <article class="club-notification-guide-row" style="--row-color:#8b5cf6;">
                    <div class="club-notification-icon">✓</div>
                    <div><h3>نکته</h3><p>با ورود به این صفحه، اعلان‌های خوانده‌نشده به‌صورت خودکار خوانده‌شده می‌شوند.</p></div>
                    <span class="club-notification-badge is-muted">راهنما</span>
                </article>
            </div>
        </aside>
    </section>
</div>
@endsection