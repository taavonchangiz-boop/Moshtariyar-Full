@extends('layouts.app')

@section('title', 'پرونده ۳۶۰ — ' . ($customer->full_name ?? 'مشتری'))
@section('heading', $customer->full_name ?? 'مشتری')
@section('subtitle', 'نمای ۳۶۰ درجه مشتری — خریدها، وفاداری، سلامت و اقدام پیشنهادی')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer360-pro.css') }}">
<link rel="stylesheet" href="{{ asset('css/customer360-enhanced.css') }}">
@endpush

@section('content')

@php
    $health = $health ?? ['score' => 0, 'label' => 'نامشخص', 'color' => '#64748b'];
    $smartNextAction = $smart_next_action ?? ['title' => 'پیشنهاد ثبت نشده', 'body' => 'پیشنهاد هوشمند آماده نشده است.', 'color' => '#64748b'];
    $favoriteProducts = $favorite_products ?? collect();
    $favoriteCategories = $favorite_categories ?? collect();
    $segments = $customer->segments ?? collect();
    $operational = $operational ?? ['tasks' => collect(), 'open_tasks' => 0, 'overdue_tasks' => 0, 'today_tasks' => 0, 'active_journeys' => 0];
    $orders = $customer->orders ?? collect();
    $loyalty = $loyalty ?? [];
    $stats = $stats ?? [];
    $rfm = $rfm ?? [];
    $timeline = $timeline ?? collect();
    $churn_risk = $churn_risk ?? 0;
    $initial = mb_substr($customer->full_name ?? 'م', 0, 1);

    $statusMap = [
        'pending' => ['label' => 'در انتظار', 'color' => '#3b82f6'],
        'processing' => ['label' => 'در حال انجام', 'color' => '#f59e0b'],
        'on_hold' => ['label' => 'نیازمند بررسی', 'color' => '#8b5cf6'],
        'completed' => ['label' => 'تکمیل‌شده', 'color' => '#10b981'],
        'cancelled' => ['label' => 'لغو شده', 'color' => '#ef4444'],
        'refunded' => ['label' => 'مرجوعی', 'color' => '#06b6d4'],
        'failed' => ['label' => 'ناموفق', 'color' => '#64748b'],
    ];
@endphp

<div class="c360-page" dir="rtl">

    {{-- ═══════ هدر پروفایل ═══════ --}}
    <section class="c360-hero">
        <div class="c360-avatar" style="background:{{ $rfm['color'] ?? '#3b82f6' }}; color:#fff;">
            {{ $initial }}
        </div>
        <div class="c360-hero-info">
            <div class="c360-hero-top">
                <h2>{{ $customer->full_name ?? 'بدون نام' }}</h2>
                <span class="c360-type-badge">{{ $customer->type === 'company' ? 'مشتری شرکتی' : 'مشتری شخصی' }}</span>
            </div>
            <div class="c360-hero-contact">
                @if($customer->phone)<span>📱 {{ $customer->phone }}</span>@endif
                @if($customer->email)<span>✉️ {{ $customer->email }}</span>@endif
                <span>📅 ثبت: @jdate($customer->created_at)</span>
            </div>
        </div>
        <div class="c360-hero-actions">
            <button class="btn" onclick="openMessageModal()">✉️ ارسال پیام</button>
            <a class="btn btn-ghost" href="{{ url('/app/customers/' . $customer->id . '/edit') }}">✏️ ویرایش</a>
            <a class="btn btn-ghost" href="{{ url('/app/pos') }}?customer_id={{ $customer->id }}">🛒 فروش سریع</a>
        </div>
    </section>

    {{-- ═══════ کارت‌های KPI ═══════ --}}
    <section class="c360-kpi-row">
        <div class="c360-kpi-card" style="--k-color:#2563eb;">
            <span>ارزش طول عمر</span>
            <b>@money($stats['lifetime_value'] ?? 0) @unit</b>
            <small>{{ $stats['completed_orders'] ?? 0 }} سفارش تکمیل‌شده</small>
        </div>
        <div class="c360-kpi-card" style="--k-color:#10b981;">
            <span>میانگین سفارش</span>
            <b>@money($stats['aov'] ?? 0) @unit</b>
            <small>آخرین خرید: {{ $stats['days_since_purchase'] ?? 999 }} روز پیش</small>
        </div>
        <div class="c360-kpi-card" style="--k-color:{{ $rfm['color'] ?? '#f59e0b' }};">
            <span>گروه RFM</span>
            <b>{{ $rfm['segment'] ?? 'نامشخص' }}</b>
            <small>امتیاز: @fa($rfm['score'] ?? 0)</small>
        </div>
        <div class="c360-kpi-card" style="--k-color:{{ $health['color'] ?? '#64748b' }};">
            <span>سلامت مشتری</span>
            <b>{{ $health['label'] ?? 'نامشخص' }} (@fa($health['score'] ?? 0))</b>
            <small>ریسک ریزش: @fa($churn_risk ?? 0)٪</small>
        </div>
        @if(isset($loyalty['points']))
        <div class="c360-kpi-card" style="--k-color:#8b5cf6;">
            <span>باشگاه مشتریان</span>
            <b>⭐ @fa($loyalty['points'] ?? 0)</b>
            <small>{{ $loyalty['tier'] ?? 'بدون سطح' }}</small>
        </div>
        @endif
    </section>

    {{-- ═══════ پیشنهاد هوشمند ═══════ --}}
    <section class="c360-smart" style="--smart-color:{{ $smartNextAction['color'] ?? '#2563eb' }};">
        <div class="c360-smart-icon">💡</div>
        <div class="c360-smart-body">
            <b>{{ $smartNextAction['title'] ?? 'اقدام پیشنهادی' }}</b>
            <p>{{ $smartNextAction['body'] ?? 'پیشنهاد مشخصی ثبت نشده است.' }}</p>
        </div>
    </section>

    {{-- ═══════ تب‌ها ═══════ --}}
    <div class="c360-tabs">
        <button class="c360-tab active" data-tab="orders" onclick="switchC360Tab('orders')">🛒 سفارش‌ها</button>
        <button class="c360-tab" data-tab="activities" onclick="switchC360Tab('activities')">📝 فعالیت‌ها</button>
        <button class="c360-tab" data-tab="club" onclick="switchC360Tab('club')">⭐ باشگاه</button>
        <button class="c360-tab" data-tab="products" onclick="switchC360Tab('products')">📦 محصولات</button>
        <button class="c360-tab" data-tab="timeline" onclick="switchC360Tab('timeline')">📅 خط زمانی</button>
    </div>

    {{-- ═══════ تب ۱: سفارش‌ها ═══════ --}}
    <div class="c360-tab-content active" id="tab-orders">
        @if($orders->isNotEmpty())
            <div class="c360-table-wrap">
                <table class="c360-table">
                    <thead>
                        <tr>
                            <th>شماره</th>
                            <th>تاریخ</th>
                            <th>وضعیت</th>
                            <th>اقلام</th>
                            <th>مبلغ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders->take(20) as $order)
                            @php
                                $s = $statusMap[$order->status] ?? ['label' => $order->status, 'color' => '#64748b'];
                            @endphp
                            <tr onclick="window.location='{{ url('/app/orders/' . $order->id) }}'" style="cursor:pointer;">
                                <td><b style="color:var(--acc);">#{{ $order->number ?: $order->id }}</b></td>
                                <td style="font-size:0.75rem;">@jdatetime($order->placed_at ?? $order->created_at)</td>
                                <td>
                                    <span class="c360-status-pill" style="background:{{ $s['color'] }};">
                                        {{ $s['label'] }}
                                    </span>
                                </td>
                                <td style="text-align:center;">@fa($order->items->count() ?? 0)</td>
                                <td><b style="color:var(--txt);">@money($order->total ?? 0) @unit</b></td>
                                <td>
                                    <a href="{{ url('/app/orders/' . $order->id) }}" class="c360-link" onclick="event.stopPropagation();">👁️</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="c360-empty"><div class="c360-empty-icon">🛒</div>این مشتری هنوز سفارشی ثبت نکرده است.</div>
        @endif
    </div>

    {{-- ═══════ تب ۲: فعالیت‌ها ═══════ --}}
    <div class="c360-tab-content" id="tab-activities" style="display:none;">
        @if($operational['tasks']->isNotEmpty())
            <div class="c360-task-list">
                @foreach($operational['tasks'] as $task)
                    @php
                        $isOverdue = $task->due_at && $task->due_at->isPast() && !$task->done;
                    @endphp
                    <div class="c360-task-row">
                        <div class="c360-task-icon {{ $isOverdue ? 'is-overdue' : '' }}">
                            {{ $task->done ? '✅' : ($isOverdue ? '🔴' : '📝') }}
                        </div>
                        <div class="c360-task-body">
                            <b>{{ $task->body ?: 'وظیفه بدون توضیح' }}</b>
                            <small>
                                @if($task->due_at)
                                    سررسید: @jdatetime($task->due_at)
                                    @if($isOverdue)<span style="color:#ef4444;font-weight:800;"> · عقب‌افتاده</span>@endif
                                @else
                                    بدون زمان‌بندی
                                @endif
                            </small>
                        </div>
                        @if(!$task->done)
                            <form method="post" action="{{ url('/app/activities/' . $task->id . '/toggle') }}">
                                @csrf
                                <button type="submit" class="c360-action-small">انجام شد</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="c360-task-summary">
                <span>🔴 عقب‌افتاده: <b>@fa($operational['overdue_tasks'] ?? 0)</b></span>
                <span>📝 امروز: <b>@fa($operational['today_tasks'] ?? 0)</b></span>
                <span>🔄 سفر فعال: <b>@fa($operational['active_journeys'] ?? 0)</b></span>
            </div>
        @else
            <div class="c360-empty"><div class="c360-empty-icon">📝</div>برای این مشتری وظیفه بازی ثبت نشده است.</div>
        @endif
    </div>

    {{-- ═══════ تب ۳: باشگاه مشتریان ═══════ --}}
    <div class="c360-tab-content" id="tab-club" style="display:none;">
        @if(isset($loyalty['member']) && $loyalty['member'])
            <div class="c360-club-grid">
                <div class="c360-club-card">
                    <span>⭐ امتیاز فعلی</span>
                    <b>@fa($loyalty['points'] ?? 0)</b>
                </div>
                <div class="c360-club-card">
                    <span>🏅 سطح</span>
                    <b>{{ $loyalty['tier'] ?? 'بدون سطح' }}</b>
                </div>
                <div class="c360-club-card">
                    <span>💰 کیف پول</span>
                    <b>@money($loyalty['wallet_balance'] ?? 0) @unit</b>
                </div>
                @if($loyalty['next_tier'])
                    <div class="c360-club-card">
                        <span>🎯 سطح بعدی</span>
                        <b>{{ $loyalty['next_tier'] }}</b>
                        <small>@fa($loyalty['next_tier_gap'] ?? 0) امتیاز مانده</small>
                    </div>
                @endif
            </div>
            @if(isset($loyalty['tier_progress']))
                <div class="c360-progress-bar-wrap">
                    <span>پیشرفت به سطح بعدی: @fa($loyalty['tier_progress'] ?? 0)٪</span>
                    <div class="c360-progress-bar">
                        <span style="width:{{ $loyalty['tier_progress'] }}%; background:{{ $loyalty['tier_progress'] >= 80 ? '#10b981' : ($loyalty['tier_progress'] >= 40 ? '#f59e0b' : '#3b82f6') }};"></span>
                    </div>
                </div>
            @endif
            @if(isset($loyalty['transactions']) && ($loyalty['transactions']->count() ?? 0) > 0)
                <h4 style="margin:0.85rem 0 0.55rem; font-size:0.85rem; font-weight:900;">آخرین تراکنش‌ها</h4>
                <div class="c360-table-wrap">
                    <table class="c360-table">
                        <thead><tr><th>تاریخ</th><th>نوع</th><th>مبلغ</th><th>توضیح</th></tr></thead>
                        <tbody>
                            @foreach($loyalty['transactions']->take(8) as $tx)
                                <tr>
                                    <td style="font-size:0.75rem;">@jdatetime($tx->created_at)</td>
                                    <td>
                                        <span class="c360-status-pill" style="background:{{ $tx->direction === 'credit' ? '#10b981' : '#ef4444' }}; font-size:0.65rem;">
                                            {{ $tx->direction === 'credit' ? '➕ دریافت' : '➖ مصرف' }}
                                        </span>
                                    </td>
                                    <td><b>@fa($tx->amount)</b></td>
                                    <td style="font-size:0.73rem; color:var(--mut);">{{ $tx->reason ?? $tx->ref_type ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="c360-empty"><div class="c360-empty-icon">⭐</div>این مشتری عضو باشگاه نیست.</div>
        @endif
    </div>

    {{-- ═══════ تب ۴: محصولات محبوب ═══════ --}}
    <div class="c360-tab-content" id="tab-products" style="display:none;">
        <div class="c360-two-col">
            <div class="c360-products-card">
                <h4>محصول‌های پرتکرار</h4>
                @forelse($favoriteProducts as $item)
                    <div class="c360-fav-row">
                        <b>{{ $item->name ?: 'محصول' }}</b>
                        <span>@fa($item->qty_sum ?? 0) عدد · @money($item->total_sum ?? 0) @unit</span>
                    </div>
                @empty
                    <div class="c360-empty" style="padding:1.5rem;">محصول پرتکراری ثبت نشده است.</div>
                @endforelse
            </div>
            <div class="c360-products-card">
                <h4>دسته‌بندی‌های محبوب</h4>
                @forelse($favoriteCategories as $item)
                    <div class="c360-fav-row">
                        <b>{{ $item->category ?: 'بدون دسته‌بندی' }}</b>
                        <span>@fa($item->qty_sum ?? 0) عدد · @money($item->total_sum ?? 0) @unit</span>
                    </div>
                @empty
                    <div class="c360-empty" style="padding:1.5rem;">دسته‌بندی محبوبی ثبت نشده است.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══════ تب ۵: خط زمانی ═══════ --}}
    <div class="c360-tab-content" id="tab-timeline" style="display:none;">
        @if($timeline->isNotEmpty())
            <div class="c360-timeline-wrap">
                <div class="c360-timeline-v">
                    @foreach($timeline->take(20) as $item)
                        @php
                            $tlType = $item['type'] ?? 'activity';
                            $tlIcon = $item['icon'] ?? '📌';
                            $tlTitle = $item['title'] ?? 'رویداد';
                            $tlAmount = $item['amount'] ?? null;
                            $tlStatus = $item['status'] ?? null;
                            $tlDate = $item['date'] ?? now();
                            $tlLink = $item['link'] ?? null;

                            if ($tlType === 'order') {
                                $tlMarkerClass = 'order';
                            } elseif ($tlType === 'loyalty') {
                                $tlMarkerClass = 'loyalty';
                            } else {
                                $tlMarkerClass = 'activity';
                            }

                            $tlBadgeClass = match($tlStatus) {
                                'completed' => 'completed',
                                'processing' => 'pending',
                                'active' => 'completed',
                                'done' => 'completed',
                                'credit' => 'completed',
                                'debit' => 'cancelled',
                                default => '',
                            };
                        @endphp
                        <div class="c360-tl-item" @if($tlLink) onclick="window.location='{{ $tlLink }}'" style="cursor:pointer;" @endif>
                            <div class="c360-tl-marker {{ $tlMarkerClass }}">{{ $tlIcon }}</div>
                            <div class="c360-tl-card">
                                <b>{{ $tlTitle }}</b>
                                <div class="c360-tl-meta">
                                    <span class="c360-tl-date">@jdatetime($tlDate)</span>
                                    <span style="display:flex;align-items:center;gap:0.4rem;">
                                        @if($tlAmount)
                                            <span class="c360-tl-amount">@money($tlAmount) @unit</span>
                                        @endif
                                        @if($tlBadgeClass && $tlStatus && $tlStatus !== 'active' && $tlStatus !== 'done')
                                            <span class="c360-tl-badge {{ $tlBadgeClass }}">
                                                @php
                                                    $tlBadges = ['completed' => 'تکمیل', 'pending' => 'در جریان', 'cancelled' => 'لغو', 'credit' => 'دریافت', 'debit' => 'مصرف'];
                                                @endphp
                                                {{ $tlBadges[$tlBadgeClass] ?? $tlStatus }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="c360-empty"><div class="c360-empty-icon">📅</div>رویدادی برای نمایش وجود ندارد.</div>
        @endif
    </div>

    {{-- ═══════ بخش‌بندی ═══════ --}}
    @if($segments->isNotEmpty())
        <section class="c360-segments">
            <h4>🎯 بخش‌های فعال مشتری</h4>
            <div class="c360-segment-tags">
                @foreach($segments as $segment)
                    <span class="c360-segment-tag">{{ $segment->name ?? 'بخش' }}</span>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══════ بازگشت ═══════ --}}
    <div class="c360-return-row">
        <a href="{{ url('/app/customers') }}" class="btn btn-ghost">⬅️ بازگشت به لیست مشتریان</a>
    </div>

    {{-- ═══════ مودال ارسال پیام ═══════ --}}
    <div id="messageModal" class="c360-modal-overlay" style="display:none;" onclick="if(event.target===this)closeMessageModal()">
        <div class="c360-modal">
            <div class="c360-modal-head">
                <b>✉️ ارسال پیام به {{ $customer->full_name }}</b>
                <button onclick="closeMessageModal()">×</button>
            </div>
            <div class="c360-modal-body">
                <form action="{{ route('messages.send') }}" method="POST">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <div class="c360-form-group">
                        <label>روش ارسال</label>
                        <select name="channel">
                            <option value="sms">پیامک</option>
                            <option value="email">ایمیل</option>
                            <option value="messenger">پیام‌رسان</option>
                        </select>
                    </div>
                    <div class="c360-form-group">
                        <label>متن پیام</label>
                        <textarea name="message" rows="4" placeholder="متن پیام..." required></textarea>
                    </div>
                    <div class="c360-modal-actions">
                        <button type="button" onclick="closeMessageModal()" class="btn btn-ghost">لغو</button>
                        <button type="submit" class="btn">ارسال</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function switchC360Tab(t) {
    document.querySelectorAll('.c360-tab').forEach(function(b){b.classList.remove('active')});
    document.querySelector('.c360-tab[data-tab="'+t+'"]').classList.add('active');
    document.querySelectorAll('.c360-tab-content').forEach(function(c){c.style.display='none';c.classList.remove('active')});
    var el=document.getElementById('tab-'+t);
    if(el){el.style.display='';el.classList.add('active');}
}
function openMessageModal(){document.getElementById('messageModal').style.display='flex';}
function closeMessageModal(){document.getElementById('messageModal').style.display='none';}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMessageModal();});
</script>
@endsection