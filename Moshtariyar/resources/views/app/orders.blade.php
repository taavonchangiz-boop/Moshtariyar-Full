@extends('layouts.app')
@section('title','سفارش‌ها')
@section('heading','بورد مدیریت سفارش‌ها')
@section('subtitle','پیگیری مرحله‌ای سفارش‌ها، پرداخت، فاکتور و آماده‌سازی ارسال')

@section('content')
<link rel="stylesheet" href="{{ asset('css/orders-board.css') }}">
<link rel="stylesheet" href="{{ asset('css/orders-kanban.css') }}">

@php
    $canManageOrders = auth()->user()?->hasPermission('orders.manage');
    $canIssueInvoice = auth()->user()?->hasPermission('orders.invoice');

    // نقشهٔ وضعیت‌ها به رنگ + برچسب
    $statusTitle = function ($status) use ($statuses) {
        $found = $statuses->firstWhere('key', $status);
        return $found['label'] ?? ($status ?: 'نامشخص');
    };
    $statusColor = function ($status) use ($statuses) {
        $found = $statuses->firstWhere('key', $status);
        return $found['color'] ?? '#64748b';
    };
    $sourceTitle = fn ($source) => $sourceLabels[$source] ?? ($source ?: 'نامشخص');
    $sourceClass = function ($source) {
        return match ($source) {
            'woocommerce' => 'is-source-woo',
            'website'     => 'is-source-web',
            'app'         => 'is-source-app',
            'telegram', 'whatsapp', 'eitaa', 'bale' => 'is-source-tel',
            'manual_pricing' => 'is-source-web',
            'manual'     => 'is-source-app',
            'lead'       => 'is-source-tel',
            default       => 'is-source',
        };
    };
    $منبع_کانبان = function ($source) {
        return match ($source) {
            'woocommerce'   => 'منبع-فروشگاه',
            'website'       => 'منبع-وب',
            'manual_pricing' => 'منبع-وب',
            'manual'        => 'منبع-دستی',
            'lead'          => 'منبع-سرنخ',
            default         => 'منبع-دستی',
        };
    };

    // گروه‌هایی که با فیلتر پیش‌فرض باز باشند
    $requestedStatus = request('status');
    $openGroups = [];
    if ($requestedStatus) {
        $openGroups = [$requestedStatus];
    } else {
        $openGroups = collect($statuses)
            ->filter(fn ($s) => in_array($s['key'], ['pending', 'processing', 'on-hold', 'awaiting-payment'], true))
            ->pluck('key')
            ->toArray();
        if (empty($openGroups)) {
            $openGroups = collect($statuses)->take(2)->pluck('key')->toArray();
        }
    }

    // نمای فعلی (جدولی یا تخته‌ای)
    $نمای_فعلی = request('view', 'board');
@endphp

<div class="orders-page">

    {{-- ═══════════ هدر ═══════════ --}}
    <section class="orders-hero">
        <div>
            <span class="orders-eyebrow">مرکز عملیات سفارش‌ها</span>
            <h2>سفارش‌ها را مرحله‌به‌مرحله کنترل کن؛ از ثبت تا پرداخت، فاکتور و ارسال</h2>
            <p>هر سفارش در گروه وضعیت خود قرار می‌گیرد. با کلیک روی هر سفارش، جزئیات اقلام، خلاصهٔ مالی، دکمه‌های پرداخت و صدور فاکتور نمایان می‌شود.</p>
            <div class="orders-hero-actions">
                <a class="btn" href="{{ url('/app/products') }}">ثبت سفارش جدید</a>
                <button class="btn btn-ghost" type="button" onclick="askOrdersAssistant('یک سفارش سریع بساز', this)">ثبت با دستیار</button>
                <a class="btn btn-ghost" href="{{ url('/app/orders/export') }}">دریافت خروجی</a>
                <a class="btn btn-ghost" href="{{ url('/app/reports') }}">گزارش فروش</a>
            </div>
        </div>
        <div class="orders-score">
            <a href="{{ url('/app/orders') }}" style="--metric-color:#3b82f6;">
                <span>کل سفارش‌ها</span><b>@fa($summary['count'] ?? 0)</b><small>در نمای فعلی</small>
            </a>
            <a href="{{ url('/app/orders') }}" style="--metric-color:#10b981;">
                <span>ارزش سفارش‌ها</span><b>@money($summary['value'] ?? 0)</b><small>@unit</small>
            </a>
            <a href="{{ url('/app/orders?status=pending') }}" style="--metric-color:#f59e0b;">
                <span>نیازمند پیگیری</span><b>@fa($summary['pending'] ?? 0)</b><small>در انتظار اقدام</small>
            </a>
            <a href="{{ url('/app/orders?status=completed') }}" style="--metric-color:#047857;">
                <span>تکمیل‌شده</span><b>@fa($summary['completed'] ?? 0)</b><small>پایان‌یافته</small>
            </a>
        </div>
    </section>

    {{-- ═══════════ نوار فیلترها ═══════════ --}}
    <form method="get" class="orders-toolbar">
        <div>
            <label>جستجو</label>
            <input name="q" value="{{ request('q') }}" placeholder="شمارهٔ سفارش، مشتری، موبایل یا منبع">
        </div>
        <div>
            <label>وضعیت</label>
            <select name="status">
                <option value="">همهٔ وضعیت‌ها</option>
                @foreach($statuses as $status)
                    <option value="{{ $status['key'] }}" @selected((string) request('status') === (string) $status['key'])>{{ $status['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>روش ثبت</label>
            <select name="source">
                <option value="">همهٔ روش‌ها</option>
                @foreach($sources as $source)
                    <option value="{{ $source }}" @selected((string) request('source') === (string) $source)>{{ $sourceTitle($source) }}</option>
                @endforeach
            </select>
        </div>
        <div class="orders-toolbar-actions">
            <button class="btn">اعمال فیلتر</button>
            @if(request()->hasAny(['q','status','source']))
                <a class="btn btn-ghost" href="{{ url('/app/orders') }}">پاک کردن</a>
            @endif
        </div>
        <a class="btn btn-ghost" href="{{ url('/app/orders/export') }}">خروجی</a>
    </form>

    {{-- ═══════════ نوار خلاصه ═══════════ --}}
    <section class="orders-insight">
        <div><span class="orders-dot is-blue"></span><b>@fa($ordersFlat->count())</b><small>سفارش قابل نمایش</small></div>
        <div><span class="orders-dot is-ok"></span><b>@money($ordersFlat->where('status', 'completed')->sum('total'))</b><small>درآمد تکمیل‌شده به @unit</small></div>
        <div><span class="orders-dot is-warn"></span><b>@money($ordersFlat->sum('tax_total'))</b><small>مالیات ثبت‌شده به @unit</small></div>
        <div><span class="orders-dot is-red"></span><b>@fa($summary['pending'] ?? 0)</b><small>نیازمند پیگیری فوری</small></div>
    </section>

    {{-- ═══════════ نوار تغییر نما (جدولی / تخته‌ای) ═══════════ --}}
    <div class="کانبان-نوار-نما">
        <button class="کانبان-تب-نما {{ $نمای_فعلی === 'board' ? 'فعال' : '' }}" onclick="تغییر_نما('board')" title="نمای تخته‌ای">
            🗂️ تخته‌ای
        </button>
        <button class="کانبان-تب-نما {{ $نمای_فعلی === 'table' ? 'فعال' : '' }}" onclick="تغییر_نما('table')" title="نمای جدولی">
            📋 جدولی
        </button>
        <span class="راهنمای-نما">
            @if($نمای_فعلی === 'board')
                🖱️ روی کارت کلیک کن تا باز شود • برای جزئیات روی «مشاهده» بزن
            @else
                🖱️ برای دیدن جزئیات، روی هر سفارش کلیک کن
            @endif
        </span>
    </div>

    {{-- ═══════════════════════════════════════════════ --}}
    {{--                  نمای تخته‌ای (Kanban)          --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <section class="نمای-تخته‌ای" id="نمای_تخته‌ای">
        <div class="کانبان-بستر" id="کانبان_بستر">

            @foreach($statuses as $ستون)
                @php
                    $سفارش‌های_ستون = $orders->get($ستون['key'], collect());
                    $خلاصه_ستون = $totals[$ستون['key']] ?? ['count' => 0, 'value' => 0, 'tax' => 0];
                    $رنگ_ستون = $ستون['color'] ?? '#64748b';
                    $شناسه_ستون = 'ستون_' . Str::slug($ستون['key']);
                @endphp

                <div class="کانبان-ستون"
                     id="{{ $شناسه_ستون }}"
                     style="--ستون-رنگ:{{ $رنگ_ستون }};"
                     data-status="{{ $ستون['key'] }}"
                     ondragover="مجاز_کردن_رهاسازی(event)"
                     ondragleave="ترک_ستون(event)"
                     ondrop="رهاسازی_در_ستون(event, '{{ $ستون['key'] }}')">

                    {{-- سربرگ ستون --}}
                    <div class="کانبان-سرستون">
                        <span class="کانبان-نقطه"></span>
                        <span class="کانبان-نام-ستون">{{ $ستون['label'] }}</span>
                        <span class="کانبان-شمار">@fa($خلاصه_ستون['count'])</span>
                        @if($خلاصه_ستون['value'] > 0)
                            <span class="کانبان-جمع-ستون">@money($خلاصه_ستون['value'])</span>
                        @endif
                    </div>

                    {{-- کارت‌های سفارش — فشرده و بازشونده --}}
                    <div class="کانبان-کارت‌ها" id="کارت‌های_{{ $شناسه_ستون }}">
                        @forelse($سفارش‌های_ستون as $سفارش)
                            @php
                                $نام_مشتری = $سفارش->customer->full_name ?? 'مشتری نامشخص';
                                $حرف_اول = mb_substr($نام_مشتری, 0, 1);
                                $تعداد_اقلام = $سفارش->items_count ?? ($سفارش->items ? $سفارش->items->count() : 0);
                                $تاریخ_سفارش = $سفارش->placed_at ?? $سفارش->created_at;
                                $منبع_نشان = $منبع_کانبان($سفارش->source);
                                $نشانی_نمایش = url('/app/orders/' . $سفارش->id . '?embed=1');
                                $عنوان_نمایش = 'سفارش شمارهٔ ' . ($سفارش->number ?: $سفارش->id);
                            @endphp

                            <div class="کانبان-کارت"
                                 id="کارت_{{ $سفارش->id }}"
                                 draggable="true"
                                 data-order-id="{{ $سفارش->id }}"
                                 data-current-status="{{ $سفارش->status }}"
                                 ondragstart="شروع_کشیدن(event, '{{ $سفارش->id }}')"
                                 ondragend="پایان_کشیدن(event, '{{ $سفارش->id }}')">

                                {{-- ردیف ۱: شماره + مبلغ + فلش بازشدن --}}
                                <div class="کانبان-سرکارت" onclick="بازکردن_بستن_کارت(event, '{{ $سفارش->id }}')">
                                    <span class="کانبان-شماره-سفارش">@fa($سفارش->number ?: $سفارش->id)</span>
                                    <span class="کانبان-مبلغ">@money($سفارش->total)</span>
                                    <span class="کانبان-فلش-بازشدن">▼</span>
                                </div>

                                {{-- ردیف ۲: مشتری --}}
                                <div class="کانبان-مشتری" onclick="بازکردن_بستن_کارت(event, '{{ $سفارش->id }}')">
                                    <div class="کانبان-آواتار">{{ $حرف_اول }}</div>
                                    <span class="کانبان-نام-مشتری">{{ $نام_مشتری }}</span>
                                </div>

                                {{-- بخش جزئیات: نشان‌ها + عملیات — فقط در حالت باز نمایش داده می‌شود --}}
                                <div class="کانبان-بخش-جزئیات">

                                    {{-- ردیف ۳: نشان‌ها --}}
                                    <div class="کانبان-نشان‌ها">
                                        <span class="کانبان-نشان {{ $منبع_نشان }}">{{ $sourceTitle($سفارش->source) }}</span>
                                        @if($تعداد_اقلام > 0)
                                            <span class="کانبان-نشان اقلام">@fa($تعداد_اقلام) قلم</span>
                                        @endif
                                        <span class="کانبان-نشان تاریخ">@jdate($تاریخ_سفارش)</span>
                                    </div>

                                    {{-- ردیف ۴: عملیات سریع --}}
                                    <div class="کانبان-عملیات">
                                        <a href="#" class="کانبان-دکمه اصلی" onclick="event.preventDefault(); بازکردن_کشو('{{ $نشانی_نمایش }}', '{{ $عنوان_نمایش }}'); return false;">مشاهده</a>
                                        <a href="{{ url('/app/orders/' . $سفارش->id . '/invoice') }}" class="کانبان-دکمه">فاکتور</a>
                                        @if($canManageOrders)
                                            <form method="post" action="{{ url('/app/orders/' . $سفارش->id . '/status') }}" class="کانبان-تغییر-وضعیت" onclick="event.stopPropagation()">
                                                @csrf
                                                <select name="status" aria-label="تغییر سریع وضعیت">
                                                    @foreach($statuses as $گزینه)
                                                        <option value="{{ $گزینه['key'] }}" @selected($گزینه['key'] === $سفارش->status)>{{ $گزینه['label'] }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" title="ثبت">✓</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="کانبان-خالی">
                                <span class="کانبان-خالی-آیکن">📭</span>
                                <span>سفارشی در این وضعیت نیست</span>
                            </div>
                        @endforelse
                    </div>

                    {{-- دکمهٔ افزودن --}}
                    <a href="{{ url('/app/products') }}" class="کانبان-افزودن">
                        + افزودن سفارش به «{{ $ستون['label'] }}»
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════ --}}
    {{--                  نمای جدولی (اصلی)             --}}
    {{-- ═══════════════════════════════════════════════ --}}
    <section class="نمای-جدولی" id="نمای_جدولی">
        <div class="orders-layout">
            <div class="orders-main">
                <article class="orders-card is-accent" style="--card-color:#10b981;">
                    <header>
                        <div>
                            <span>بورد سفارش‌ها</span>
                            <h2>مدیریت گروهی سفارش‌ها بر اساس وضعیت عملیاتی</h2>
                            <p>روی هر سفارش کلیک کن تا اقلام، خلاصهٔ مالی، دکمه‌های پرداخت و صدور فاکتور باز شود.</p>
                        </div>
                        <a class="orders-link" href="{{ url('/app/products') }}">سفارش جدید</a>
                    </header>

                    <div class="orders-board">
                        @foreach($statuses as $status)
                            @php
                                $groupOrders = $orders->get($status['key'], collect());
                                $groupTotal  = $totals[$status['key']] ?? ['count' => 0, 'value' => 0, 'tax' => 0];
                                $ratio       = $maxColumnCount > 0
                                                 ? round(($groupTotal['count'] / max(1, $maxColumnCount)) * 100)
                                                 : 0;
                                $isOpen      = in_array($status['key'], $openGroups, true) && $groupTotal['count'] > 0;
                                $groupClass  = $isOpen ? 'is-open' : 'is-collapsed';
                            @endphp

                            <div class="orders-group {{ $groupClass }}" style="--group-color:{{ $status['color'] }}; --group-ratio:{{ $ratio }}%;">
                                <button type="button" class="orders-group-title" onclick="toggleOrdersGroup(this)">
                                    <span class="og-caret">◀</span>
                                    <span class="og-mark"></span>
                                    <span class="og-title-block">
                                        <b>{{ $status['label'] }}</b>
                                        <small>{{ $status['hint'] ?? 'گروه سفارش‌های ' . $status['label'] }}</small>
                                    </span>
                                    <span class="og-count">@fa($groupTotal['count'])</span>
                                    <span class="og-progress"></span>
                                    <span class="og-ratio">@fa($ratio)٪</span>
                                </button>

                                <div class="orders-group-summary">
                                    <span>مجموع مبلغ: <b>@money($groupTotal['value'])</b> @unit</span>
                                    <span>مالیات: <b>@money($groupTotal['tax'])</b> @unit</span>
                                    <span>تعداد: <b>@fa($groupTotal['count'])</b></span>
                                </div>

                                <div class="orders-table-wrap">
                                    <div class="orders-table">
                                        <div class="orders-thead">
                                            <div>شماره</div>
                                            <div>مشتری</div>
                                            <div>مبلغ</div>
                                            <div>وضعیت</div>
                                            <div>اقلام</div>
                                            <div>منبع</div>
                                            <div>تاریخ</div>
                                            <div>عملیات</div>
                                        </div>

                                        @forelse($groupOrders as $order)
                                            @php
                                                $customerName  = $order->customer->full_name ?? 'مشتری نامشخص';
                                                $customerPhone = $order->customer->phone ?? '';
                                                $firstChar     = mb_substr($customerName, 0, 1);
                                                $orderItems    = $order->items ?? collect();
                                                $itemsCount    = $order->items_count ?? ($orderItems instanceof \Countable ? $orderItems->count() : 0);
                                                $نشانی_جزئیات = url('/app/orders/' . $order->id . '?embed=1');
                                                $عنوان_جزئیات = 'سفارش شمارهٔ ' . ($order->number ?: $order->id);
                                            @endphp

                                            <details class="orders-row">
                                                <summary>
                                                    <div class="orders-cell">
                                                        <span class="orders-cell-number">@fa($order->number ?: $order->id)</span>
                                                    </div>
                                                    <div class="orders-cell">
                                                        <div class="orders-cell-person">
                                                            <div class="orders-avatar">{{ $firstChar }}</div>
                                                            <div>
                                                                <b>{{ $customerName }}</b>
                                                                <small>{{ $customerPhone ?: 'بدون شماره' }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="orders-cell">
                                                        <span class="orders-pill is-money">@money($order->total)</span>
                                                    </div>
                                                    <div class="orders-cell">
                                                        <span class="orders-pill is-status" style="--status-color:{{ $statusColor($order->status) }};">
                                                            {{ $statusTitle($order->status) }}
                                                        </span>
                                                    </div>
                                                    <div class="orders-cell">
                                                        <span class="orders-pill is-items">@fa($itemsCount)</span>
                                                    </div>
                                                    <div class="orders-cell">
                                                        <span class="orders-pill {{ $sourceClass($order->source) }}">{{ $sourceTitle($order->source) }}</span>
                                                    </div>
                                                    <div class="orders-cell">
                                                        <small style="color:var(--mut); font-size:0.75rem;">@jdate($order->placed_at ?? $order->created_at)</small>
                                                    </div>
                                                    <div class="orders-cell">
                                                        <div class="orders-cell-actions">
                                                            <a href="#" onclick="event.preventDefault(); بازکردن_کشو('{{ $نشانی_جزئیات }}', '{{ $عنوان_جزئیات }}'); return false;">مشاهده</a>
                                                            <a href="{{ url('/app/orders/' . $order->id . '/invoice') }}">فاکتور</a>
                                                        </div>
                                                    </div>
                                                </summary>

                                                {{-- پنل جزئیات کشویی --}}
                                                <div class="orders-detail-panel">
                                                    <div class="orders-detail-block">
                                                        <h4>اقلام سفارش <span>@fa($itemsCount) قلم</span></h4>
                                                        @if($itemsCount > 0)
                                                            <div class="orders-items-table">
                                                                <div class="orders-items-head">
                                                                    <div>نام کالا</div>
                                                                    <div>تعداد</div>
                                                                    <div>قیمت واحد</div>
                                                                    <div>جمع</div>
                                                                </div>
                                                                @foreach($orderItems as $item)
                                                                    <div class="orders-items-row">
                                                                        <div>
                                                                            <b>{{ $item->product_name ?? $item->name ?? 'کالای بدون نام' }}</b>
                                                                            @if(!empty($item->sku))
                                                                                <small>کد: {{ $item->sku }}</small>
                                                                            @endif
                                                                        </div>
                                                                        <div>@fa($item->quantity ?? 1)</div>
                                                                        <div class="oi-money">@money($item->unit_price ?? $item->price ?? 0)</div>
                                                                        <div class="oi-money">@money($item->total ?? (($item->unit_price ?? 0) * ($item->quantity ?? 1)))</div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="orders-items-empty">قلمی برای این سفارش ثبت نشده است.</div>
                                                        @endif

                                                        @if($canManageOrders)
                                                            <form method="post" action="{{ url('/app/orders/' . $order->id . '/status') }}" class="orders-status-form">
                                                                @csrf
                                                                <select name="status" aria-label="تغییر وضعیت سفارش">
                                                                    @foreach($statuses as $stageOption)
                                                                        <option value="{{ $stageOption['key'] }}" @selected($stageOption['key'] === $order->status)>{{ $stageOption['label'] }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <button type="submit">ثبت وضعیت</button>
                                                            </form>
                                                        @endif
                                                    </div>

                                                    <div class="orders-detail-block">
                                                        <h4>خلاصهٔ مالی <span>{{ $sourceTitle($order->source) }}</span></h4>
                                                        <div class="orders-money-grid">
                                                            <div class="orders-money-row">
                                                                <span>جمع کالاها</span>
                                                                <b>@money($order->subtotal ?? ($order->total - ($order->tax_total ?? 0) - ($order->shipping_total ?? 0)))</b>
                                                            </div>
                                                            @if(($order->discount_total ?? 0) > 0)
                                                                <div class="orders-money-row">
                                                                    <span>تخفیف</span>
                                                                    <b>− @money($order->discount_total)</b>
                                                                </div>
                                                            @endif
                                                            @if(($order->shipping_total ?? 0) > 0)
                                                                <div class="orders-money-row">
                                                                    <span>هزینهٔ ارسال</span>
                                                                    <b>@money($order->shipping_total)</b>
                                                                </div>
                                                            @endif
                                                            @if(($order->tax_total ?? 0) > 0)
                                                                <div class="orders-money-row">
                                                                    <span>مالیات</span>
                                                                    <b>@money($order->tax_total)</b>
                                                                </div>
                                                            @endif
                                                            <div class="orders-money-row is-total">
                                                                <span>مبلغ نهایی</span>
                                                                <b>@money($order->total) @unit</b>
                                                            </div>
                                                        </div>

                                                        <div class="orders-detail-actions">
                                                            <a href="#" onclick="event.preventDefault(); بازکردن_کشو('{{ $نشانی_جزئیات }}', '{{ $عنوان_جزئیات }}'); return false;">پروندهٔ کامل</a>
                                                            <a href="{{ url('/app/orders/' . $order->id . '/invoice') }}" class="is-primary">فاکتور</a>
                                                            @if($canManageOrders && $order->status !== 'completed')
                                                                <form method="post" action="{{ url('/app/orders/' . $order->id . '/pay') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="gateway" value="zarinpal">
                                                                    <button type="submit" class="is-primary">ثبت پرداخت</button>
                                                                </form>
                                                            @endif
                                                            @if($canIssueInvoice)
                                                                <form method="post" action="{{ url('/app/orders/' . $order->id . '/tax-invoice') }}">
                                                                    @csrf
                                                                    <button type="submit">فاکتور رسمی</button>
                                                                </form>
                                                            @endif
                                                            <button type="button" onclick="askOrdersAssistant('برای سفارش شماره {{ $order->number ?: $order->id }} یک یادآور پیگیری برای فردا بگذار', this)">پیگیری با دستیار</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </details>
                                        @empty
                                            <div class="orders-group-empty">فعلاً سفارشی در این وضعیت نیست.</div>
                                        @endforelse
                                    </div>
                                </div>

                                <a class="orders-group-add" href="{{ url('/app/products') }}">+ ثبت سفارش جدید در گروه «{{ $status['label'] }}»</a>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>

            <div class="orders-below">
                <article class="orders-card is-accent" style="--card-color:#8b5cf6;">
                    <header>
                        <div>
                            <span>پیشنهاد دستیار</span>
                            <h2>اقدامات سریع مدیریتی</h2>
                        </div>
                    </header>
                    <div class="orders-suggest-list">
                        <button type="button" onclick="askOrdersAssistant('گزارش سفارش‌های امروز را بده و مهم‌ترین اقدامات لازم را بگو', this)">
                            <b>گزارش سفارش‌های امروز</b>
                            <small>مرور سفارش‌های جدید و اقدامات مورد نیاز</small>
                        </button>
                        <button type="button" onclick="askOrdersAssistant('سفارش‌های نیازمند پیگیری را فهرست کن و برای هر کدام یادآور بگذار', this)">
                            <b>پیگیری سفارش‌های عقب‌افتاده</b>
                            <small>ثبت خودکار یادآور برای سفارش‌های در انتظار</small>
                        </button>
                        <button type="button" onclick="askOrdersAssistant('برای سفارش‌های تکمیل‌شدهٔ امروز پیام تشکر ارسال کن', this)">
                            <b>ارسال پیام تشکر</b>
                            <small>ارسال پیام رضایت‌سنجی به مشتریان</small>
                        </button>
                        <button type="button" onclick="askOrdersAssistant('گزارش درآمد و مالیات این هفته را نمایش بده', this)">
                            <b>گزارش درآمد و مالیات</b>
                            <small>خلاصهٔ مالی هفتهٔ جاری</small>
                        </button>
                        <button type="button" onclick="askOrdersAssistant('فهرست سفارش‌های در انتظار پرداخت که بیش از ۲۴ ساعت گذشته را بده', this)">
                            <b>سفارش‌های در انتظار پرداخت طولانی</b>
                            <small>پیگیری پرداخت‌های عقب‌افتاده</small>
                        </button>
                    </div>
                </article>

                <article class="orders-card is-accent" style="--card-color:#0ea5e9;" id="ordersQuickPanel">
                    <header>
                        <div>
                            <span>ثبت سفارش سریع</span>
                            <h2>ثبت پیش‌نویس سفارش برای مشتری</h2>
                        </div>
                    </header>
                    <form method="post" action="{{ url('/app/orders') }}" class="orders-quick-form">
                        @csrf
                        <div>
                            <label>جستجوی مشتری</label>
                            <input name="customer_search" placeholder="نام، شماره یا ایمیل مشتری" autocomplete="off">
                        </div>
                        <div class="orders-form-grid">
                            <div>
                                <label>روش ثبت</label>
                                <select name="source">
                                    @foreach($sources as $src)
                                        <option value="{{ $src }}">{{ $sourceTitle($src) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>وضعیت اولیه</label>
                                <select name="status">
                                    @foreach($statuses as $st)
                                        <option value="{{ $st['key'] }}">{{ $st['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="orders-form-grid">
                            <div>
                                <label>تاریخ سفارش (شمسی)</label>
                                <input name="placed_at" class="jdate" placeholder="۱۴۰۵/۰۵/۰۱" autocomplete="off">
                            </div>
                            <div>
                                <label>مبلغ اولیه (تومان)</label>
                                <input name="total" type="number" min="0" step="1000" placeholder="۰">
                            </div>
                        </div>
                        <div>
                            <label>یادداشت</label>
                            <input name="notes" placeholder="یادداشت داخلی سفارش">
                        </div>
                        <div class="orders-form-wide">
                            <label>توضیح اقلام</label>
                            <textarea name="items_description" rows="3" placeholder="نام کالاها و تعداد؛ مثلاً: پردهٔ زبرا ۲ متر، نصب رایگان"></textarea>
                            <small>پس از ثبت، می‌توانی از پروندهٔ سفارش اقلام دقیق را با قیمت اضافه کنی.</small>
                        </div>
                        <button class="btn">ثبت پیش‌نویس سفارش</button>
                    </form>
                </article>
            </div>
        </div>
    </section>
</div>

{{-- پیام تأیید (Toast) --}}
<div class="orders-toast" id="ordersToast"></div>

<script>
    // ═══════════════════════════════════════════════
    // ۱. تغییر نما (جدولی ↔ تخته‌ای)
    // ═══════════════════════════════════════════════

    (function() {
        var نمای_ذخیره = localStorage.getItem('ordersView') || 'board';
        اعمال_نما(نمای_ذخیره);
    })();

    function تغییر_نما(نما) {
        localStorage.setItem('ordersView', نما);
        اعمال_نما(نما);

        document.querySelectorAll('.کانبان-تب-نما').forEach(function(تب) {
            تب.classList.remove('فعال');
        });
        document.querySelectorAll('.کانبان-تب-نما').forEach(function(تب) {
            if ((نما === 'board' && تب.textContent.includes('تخته‌ای')) ||
                (نما === 'table' && تب.textContent.includes('جدولی'))) {
                تب.classList.add('فعال');
            }
        });
    }

    function اعمال_نما(نما) {
        if (نما === 'board') {
            document.body.classList.add('نمای-کانبان');
        } else {
            document.body.classList.remove('نمای-کانبان');
        }
    }

    // ═══════════════════════════════════════════════
    // ۲. باز و بسته کردن کارت‌های تخته‌ای
    // ═══════════════════════════════════════════════

    function بازکردن_بستن_کارت(رویداد, شناسه_سفارش) {
        if (رویداد.target.closest('button') || رویداد.target.closest('select') || رویداد.target.closest('a')) {
            return;
        }
        رویداد.stopPropagation();
        var کارت = document.getElementById('کارت_' + شناسه_سفارش);
        if (!کارت) return;
        کارت.classList.toggle('باز');
    }

    // ═══════════════════════════════════════════════
    // ۳. باز و بسته کردن گروه‌های جدولی
    // ═══════════════════════════════════════════════

    function toggleOrdersGroup(btn) {
        var group = btn.closest('.orders-group');
        if (!group) return;
        group.classList.toggle('is-collapsed');
        group.classList.toggle('is-open');
    }

    // ═══════════════════════════════════════════════
    // ۴. پرسش از دستیار
    // ═══════════════════════════════════════════════

    function askOrdersAssistant(text, btn) {
        text = text || 'گزارش سفارش‌ها را بده';

        if (btn) {
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            btn.innerHTML = '<b style="color:#047857">در حال باز کردن دستیار...</b><small>لطفاً چند لحظه صبر کنید</small>';
        }

        var assistantUrl = '{{ url("/app/assistant") }}'
            + '?prompt=' + encodeURIComponent(text)
            + '&context_type=orders_center'
            + '&context_title=' + encodeURIComponent('مرکز مدیریت سفارش‌ها')
            + '&context_url='   + encodeURIComponent(window.location.href);

        window.location.href = assistantUrl;
    }

    // ═══════════════════════════════════════════════
    // ۵. کشیدن و رها کردن (Drag & Drop) — نمای تخته‌ای
    // ═══════════════════════════════════════════════

    var سفارش_در_حال_کشیدن = null;

    function شروع_کشیدن(رویداد, شناسه_سفارش) {
        سفارش_در_حال_کشیدن = شناسه_سفارش;
        var کارت = document.getElementById('کارت_' + شناسه_سفارش);
        if (کارت) {
            کارت.classList.add('در-حال-کشیدن');
        }
        رویداد.dataTransfer.effectAllowed = 'move';
        رویداد.dataTransfer.setData('text/plain', شناسه_سفارش);
    }

    function پایان_کشیدن(رویداد, شناسه_سفارش) {
        var کارت = document.getElementById('کارت_' + شناسه_سفارش);
        if (کارت) {
            کارت.classList.remove('در-حال-کشیدن');
        }
        document.querySelectorAll('.کانبان-ستون').forEach(function(ستون) {
            ستون.classList.remove('مقصد-کشیدن');
        });
        سفارش_در_حال_کشیدن = null;
    }

    function مجاز_کردن_رهاسازی(رویداد) {
        رویداد.preventDefault();
        رویداد.dataTransfer.dropEffect = 'move';
        var ستون = رویداد.currentTarget;
        if (ستون && !ستون.classList.contains('مقصد-کشیدن')) {
            ستون.classList.add('مقصد-کشیدن');
        }
    }

    function ترک_ستون(رویداد) {
        var ستون = رویداد.currentTarget;
        if (ستون) {
            ستون.classList.remove('مقصد-کشیدن');
        }
    }

    function رهاسازی_در_ستون(رویداد, وضعیت_جدید) {
        رویداد.preventDefault();
        var ستون = رویداد.currentTarget;
        if (ستون) {
            ستون.classList.remove('مقصد-کشیدن');
        }

        var شناسه_سفارش = سفارش_در_حال_کشیدن || رویداد.dataTransfer.getData('text/plain');
        if (!شناسه_سفارش) return;

        var کارت = document.getElementById('کارت_' + شناسه_سفارش);
        if (!کارت) return;

        var وضعیت_فعلی = کارت.getAttribute('data-current-status');
        if (وضعیت_فعلی === وضعیت_جدید) return;

        var فرم_داده = new FormData();
        فرم_داده.append('_token', '{{ csrf_token() }}');
        فرم_داده.append('status', وضعیت_جدید);

        fetch('/app/orders/' + شناسه_سفارش + '/status', {
            method: 'POST',
            body: فرم_داده,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(پاسخ) { return پاسخ.json(); })
        .then(function(داده) {
            if (داده.ok) {
                var کارت‌های_مقصد = document.getElementById('کارت‌های_ستون_' + وضعیت_جدید.replace(/-/g, '_'));
                if (کارت‌های_مقصد) {
                    var خالی = کارت‌های_مقصد.querySelector('.کانبان-خالی');
                    if (خالی) خالی.remove();

                    کارت‌های_مقصد.appendChild(کارت);
                    کارت.setAttribute('data-current-status', وضعیت_جدید);
                    به‌روزرسانی_شمارش_ستون‌ها();
                }

                نمایش_پیام('✅ وضعیت سفارش به «' + وضعیت_جدید + '» تغییر کرد');
            } else {
                نمایش_پیام('❌ خطا در تغییر وضعیت: ' + (داده.message || 'نامشخص'), true);
            }
        })
        .catch(function(خطا) {
            نمایش_پیام('❌ خطای شبکه — لطفاً دوباره تلاش کنید', true);
        });

        سفارش_در_حال_کشیدن = null;
    }

    function به‌روزرسانی_شمارش_ستون‌ها() {
        document.querySelectorAll('.کانبان-ستون').forEach(function(ستون) {
            var کارت‌ها = ستون.querySelector('.کانبان-کارت‌ها');
            var نشان = ستون.querySelector('.کانبان-شمار');
            if (کارت‌ها && نشان) {
                var تعداد = کارت‌ها.querySelectorAll('.کانبان-کارت').length;
                نشان.textContent = تعداد;
            }
        });
    }

    function نمایش_پیام(متن, خطا) {
        var toast = document.getElementById('ordersToast');
        if (!toast) return;
        toast.textContent = متن;
        toast.className = 'orders-toast show' + (خطا ? ' is-bad' : '');
        setTimeout(function() {
            toast.classList.remove('show');
        }, 2800);
    }
</script>
@endsection