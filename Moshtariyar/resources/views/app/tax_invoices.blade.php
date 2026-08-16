@extends('layouts.app')
@section('title','فاکتورهای رسمی و پیش‌فاکتور')
@section('heading','مدیریت فاکتورهای رسمی و پیش‌فاکتور')
@section('subtitle','اتصال به سامانه مودیان، صدور فاکتور رسمی و ثبت پیش‌فاکتور')

@section('content')
<link rel="stylesheet" href="{{ asset('css/finance-board.css') }}">

@php
    $statusMap = [
        'draft'     => ['پیش‌نویس',    'is-muted', '#64748b'],
        'queued'    => ['در صف ارسال', 'is-warn',  '#f59e0b'],
        'sent'      => ['ارسال شده',   'is-ok',    '#10b981'],
        'confirmed' => ['تأیید شده',   'is-ok',    '#10b981'],
        'rejected'  => ['رد شده',      'is-bad',   '#ef4444'],
        'failed'    => ['ناموفق',      'is-bad',   '#ef4444'],
    ];

    $invoiceCount    = method_exists($invoices, 'total')
                        ? $invoices->total()
                        : (method_exists($invoices, 'count') ? $invoices->count() : 0);
    $filterKind      = $activeFilter['kind']   ?? request('kind');
    $filterStatus    = $activeFilter['status'] ?? request('status');
    $serialPrefix    = \Modules\Core\Entities\Setting::get('proforma_serial_prefix', 'پف');
    $serialSeparator = \Modules\Core\Entities\Setting::get('proforma_serial_separator', '-');
    $serialPadding   = \Modules\Core\Entities\Setting::get('proforma_serial_padding', 5);
    $serialDate      = \Modules\Core\Entities\Setting::get('proforma_serial_date', 'jalali');
@endphp

<div class="finance-page">

    {{-- هدر --}}
    <section class="finance-hero">
        <div>
            <span class="finance-eyebrow">مرکز مالی: فاکتور و پیش‌فاکتور</span>
            <h2>مدیریت فاکتورهای رسمی، پیش‌فاکتور و اتصال به سامانه مودیان</h2>
            <p>فاکتورهای رسمی سفارش‌ها، پیش‌فاکتورهای دستی، وضعیت ارسال، شناسه‌های رهگیری و مسیر فعال‌سازی اتصال رسمی به سامانه مودیان همه در یک صفحه.</p>
            <div class="finance-actions">
                <button class="btn" type="button" onclick="openProformaModal()">+ ثبت پیش‌فاکتور</button>
                <a class="btn btn-ghost" href="{{ url('/app/orders') }}">← سفارش‌ها</a>
                <a class="btn btn-ghost" href="{{ url('/app/payments') }}">پرداخت‌ها</a>
                <a class="btn btn-ghost" href="{{ url('/app/settings') }}">تنظیمات سیستم</a>
            </div>
        </div>
        <div class="finance-score">
            <a href="{{ url('/app/tax-invoices') }}" class="finance-score-link {{ ! $filterKind && ! $filterStatus ? 'is-active' : '' }}" style="--metric-color:#10b981;">
                <span>کل اسناد</span>
                <b>@fa(number_format($stats['total'] ?? $invoiceCount))</b>
                <small>رسمی و پیش‌فاکتور</small>
            </a>
            <a href="{{ url('/app/tax-invoices?kind=proforma') }}" class="finance-score-link {{ $filterKind === 'proforma' ? 'is-active' : '' }}" style="--metric-color:#8b5cf6;">
                <span>پیش‌فاکتور</span>
                <b>@fa(number_format($stats['proforma'] ?? 0))</b>
                <small>ثبت‌شده دستی</small>
            </a>
            <a href="{{ url('/app/tax-invoices?kind=official') }}" class="finance-score-link {{ $filterKind === 'official' ? 'is-active' : '' }}" style="--metric-color:#0ea5e9;">
                <span>فاکتور رسمی</span>
                <b>@fa(number_format($stats['official'] ?? 0))</b>
                <small>اسناد رسمی سفارش</small>
            </a>
            <a href="{{ url('/app/tax-invoices?status=draft') }}" class="finance-score-link {{ $filterStatus === 'draft' ? 'is-active' : '' }}" style="--metric-color:#f59e0b;">
                <span>پیش‌نویس</span>
                <b>@fa(number_format($stats['draft'] ?? 0))</b>
                <small>نیازمند تکمیل</small>
            </a>
        </div>
    </section>

    {{-- فیلتر فعال --}}
    @if($filterKind || $filterStatus)
        <div class="finance-card" style="padding:0.85rem 1rem; margin-bottom:1rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div style="color:var(--mut); font-size:0.85rem; font-weight:900;">
                    فیلتر فعال:
                    {{ $filterKind === 'proforma' ? 'پیش‌فاکتور' : ($filterKind === 'official' ? 'فاکتور رسمی' : 'همه نوع‌ها') }}
                    @if($filterStatus)
                        · وضعیت: {{ $statusMap[$filterStatus][0] ?? $filterStatus }}
                    @endif
                </div>
                <a class="btn btn-ghost" href="{{ url('/app/tax-invoices') }}">پاک کردن فیلتر</a>
            </div>
        </div>
    @endif

    {{-- دو ستون: وضعیت اتصال + خلاصه اسناد --}}
    <section class="finance-grid">

        <article class="finance-card is-accent" style="--finance-color:#f59e0b;">
            <header>
                <div>
                    <span>وضعیت اتصال مودیان</span>
                    <h2>حالت فعلی سامانه مالیاتی</h2>
                    <p>وضعیت اتصال به سامانه مودیان و مسیر ثبت اسناد رسمی و پیش‌فاکتور.</p>
                </div>
                <span class="finance-badge is-warn">حالت ساده</span>
            </header>

            <div class="finance-list">
                <article class="finance-guide-row" style="--row-color:#f59e0b;">
                    <div class="finance-icon">⚠</div>
                    <div>
                        <h3>اتصال رسمی مودیان هنوز فعال نیست</h3>
                        <p>در حالت فعلی، پیش‌فاکتورها به‌صورت داخلی صادر می‌شوند. برای ارسال رسمی سند به سامانه مودیان، در بخش تنظیمات، اطلاعات مالیاتی و کلید امضا را وارد کن.</p>
                        <small>راهنما: تنظیمات سیستم → اطلاعات مالیاتی</small>
                    </div>
                    <div class="finance-row-actions">
                        <a class="finance-badge is-warn" href="{{ url('/app/settings') }}">فعال‌سازی</a>
                    </div>
                </article>

                <article class="finance-guide-row" style="--row-color:#0ea5e9;">
                    <div class="finance-icon">🧾</div>
                    <div>
                        <h3>الگوی شماره‌گذاری پیش‌فاکتور</h3>
                        <p>پیشوند: <b>{{ $serialPrefix }}</b> · جداکننده: <b>{{ $serialSeparator }}</b> · تعداد رقم: <b>{{ $serialPadding }}</b> · تاریخ: <b>{{ $serialDate === 'jalali' ? 'شمسی' : 'میلادی' }}</b></p>
                        <small>نمونه: {{ $serialPrefix }}{{ $serialSeparator }}{{ str_repeat('۰', max(0,$serialPadding-2)) }}۱۲</small>
                    </div>
                    <div class="finance-row-actions">
                        <button type="button" class="finance-badge" onclick="showFinanceNotice('راهنمای شماره‌گذاری','برای تغییر پیشوند و ساختار شماره‌گذاری پیش‌فاکتور، از فرم زیر همین صفحه استفاده کن.')">راهنما</button>
                    </div>
                </article>
            </div>
        </article>

        <article class="finance-card is-accent" style="--finance-color:#10b981;">
            <header>
                <div>
                    <span>خلاصه اسناد مالی</span>
                    <h2>آنچه در سیستم ثبت شده</h2>
                    <p>مسیرهای سریع برای دیدن انواع اسناد بر اساس نوع و وضعیت.</p>
                </div>
                <span class="finance-badge is-ok">فعال</span>
            </header>

            <div class="finance-list">
                <a class="finance-guide-row finance-clickable-row" href="{{ url('/app/tax-invoices?kind=official') }}" style="--row-color:#10b981;">
                    <div class="finance-icon">📄</div>
                    <div>
                        <h3>فاکتورهای رسمی</h3>
                        <p>اسنادی که از سفارش‌های ثبت‌شده صادر می‌شوند.</p>
                        <small>مسیر: سفارش‌ها → صدور فاکتور رسمی</small>
                    </div>
                    <div class="finance-row-actions"><span class="finance-badge is-ok">{{ number_format($stats['official'] ?? 0) }}</span></div>
                </a>

                <a class="finance-guide-row finance-clickable-row" href="{{ url('/app/tax-invoices?kind=proforma') }}" style="--row-color:#8b5cf6;">
                    <div class="finance-icon">📋</div>
                    <div>
                        <h3>پیش‌فاکتورها</h3>
                        <p>اسنادی که به‌صورت دستی برای اعلام قیمت اولیه ثبت می‌شوند.</p>
                        <small>دکمه بالا: + ثبت پیش‌فاکتور</small>
                    </div>
                    <div class="finance-row-actions"><span class="finance-badge">{{ number_format($stats['proforma'] ?? 0) }}</span></div>
                </a>

                <a class="finance-guide-row finance-clickable-row" href="{{ url('/app/tax-invoices?status=draft') }}" style="--row-color:#f59e0b;">
                    <div class="finance-icon">📝</div>
                    <div>
                        <h3>پیش‌نویس‌ها</h3>
                        <p>اسنادی که تکمیل نشده‌اند و نیاز به بازبینی دارند.</p>
                        <small>پاک کن یا تکمیل کن</small>
                    </div>
                    <div class="finance-row-actions"><span class="finance-badge is-warn">{{ number_format($stats['draft'] ?? 0) }}</span></div>
                </a>
            </div>
        </article>
    </section>

    {{-- تنظیمات شماره‌گذاری --}}
    <section class="finance-card" style="margin-bottom:1rem;">
        <header>
            <div>
                <span>تنظیمات شماره‌گذاری پیش‌فاکتور</span>
                <h2>الگوی صدور شماره سریال</h2>
                <p>پیشوند، جداکننده، تعداد رقم و نوع تاریخ در شماره‌گذاری پیش‌فاکتورها.</p>
            </div>
        </header>

        <form method="post" action="{{ url('/app/tax-invoices/settings') }}" class="finance-settings-form">
            @csrf
            <div>
                <label>پیشوند</label>
                <input name="proforma_serial_prefix" value="{{ $serialPrefix }}" placeholder="مثلاً پف">
            </div>
            <div>
                <label>جداکننده</label>
                <input name="proforma_serial_separator" value="{{ $serialSeparator }}" placeholder="مثلاً -">
            </div>
            <div>
                <label>تعداد رقم</label>
                <input class="ltr" name="proforma_serial_padding" type="number" min="1" max="10" value="{{ (int)$serialPadding }}">
            </div>
            <div>
                <label>نوع تاریخ در شماره</label>
                <select name="proforma_serial_date">
                    <option value="none" @selected($serialDate==='none')>بدون تاریخ</option>
                    <option value="jalali" @selected($serialDate==='jalali')>شمسی</option>
                    <option value="gregorian" @selected($serialDate==='gregorian')>میلادی</option>
                </select>
            </div>
            <div style="grid-column:1 / -1; display:flex; justify-content:flex-end;">
                <button class="btn">ذخیره تنظیمات</button>
            </div>
        </form>
    </section>

    {{-- فهرست اسناد --}}
    <section class="finance-card" id="documentsList">
        <header>
            <div>
                <span>فهرست اسناد</span>
                <h2>{{ $filterKind === 'proforma' ? 'پیش‌فاکتورها' : ($filterKind === 'official' ? 'فاکتورهای رسمی' : 'همه اسناد مالی') }}</h2>
                <p>مرتب‌شده بر اساس جدیدترین صدور. برای دیدن جزئیات یا چاپ روی «مشاهده» بزن.</p>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a class="btn" href="{{ url('/app/tax-invoices/export?kind=' . urlencode((string) $filterKind) . '&status=' . urlencode((string) $filterStatus)) }}">دریافت خروجی</a>
                <button class="btn btn-ghost" type="button" onclick="openProformaModal()">+ پیش‌فاکتور جدید</button>
            </div>
        </header>

        @if($invoiceCount > 0)
            <div class="finance-list">
                @foreach($invoices as $invoice)
                    @php
                        $isProforma = ($invoice->invoice_kind ?? 'official') === 'proforma';
                        [$statusText, $statusClass, $statusColor] = $statusMap[$invoice->status] ?? [$invoice->status ?: 'نامشخص', 'is-muted', '#64748b'];
                        $rowColor = $isProforma ? '#8b5cf6' : $statusColor;
                        $firstItem = is_array($invoice->items) ? ($invoice->items[0] ?? []) : [];
                        $title = $isProforma ? 'پیش‌فاکتور' : 'فاکتور';
                        $discountValue = $firstItem['discount_value'] ?? $invoice->discount;
                        $discountType  = $firstItem['discount_type']  ?? 'fixed';
                    @endphp
                    <article class="finance-invoice-row" style="--row-color:{{ $rowColor }};">
                        <div class="finance-icon">{{ $isProforma ? '📋' : '📄' }}</div>
                        <div>
                            <h3>{{ $title }} شماره {{ $invoice->serial ?: ($invoice->tax_id ?: $invoice->id) }}</h3>
                            <p>مشتری: {{ $invoice->customer->full_name ?? 'مشتری نامشخص' }} · قابل پرداخت: @money($invoice->payable) @unit</p>

                            @if($isProforma && ($invoice->discount > 0))
                                <small>
                                    @if($discountType === 'percent')
                                        درصد تخفیف:
                                        {{ \Modules\Core\Support\Money::show($invoice->discount) }} {{ \Modules\Core\Support\Money::unitLabel() }}
                                    @else
                                        · مبلغ تخفیف: @money($invoice->discount) @unit
                                    @endif
                                </small>
                            @endif

                            <small>
                                تاریخ صدور: {{ $invoice->issued_at ? \Modules\Core\Support\Jalali::datetime($invoice->issued_at) : \Modules\Core\Support\Jalali::datetime($invoice->created_at) }}
                                @if($invoice->due_at)
                                    · اعتبار تا: {{ \Modules\Core\Support\Jalali::date($invoice->due_at) }}
                                @endif
                            </small>

                            @if($invoice->reference_number || $invoice->tax_id)
                                <small>شناسه: <span style="direction:ltr;">{{ $invoice->reference_number ?: $invoice->tax_id }}</span></small>
                            @endif

                            @if($invoice->notes)
                                <small>یادداشت: {{ $invoice->notes }}</small>
                            @endif
                        </div>
                        <div class="finance-row-actions">
                            <span class="finance-badge {{ $statusClass }}">{{ $isProforma ? 'پیش‌فاکتور' : $statusText }}</span>
                            <a class="finance-badge" href="{{ url('/app/tax-invoices/'.$invoice->id) }}">مشاهده</a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if(method_exists($invoices, 'links'))
                <div style="margin-top:1rem;">{{ $invoices->links() }}</div>
            @endif
        @else
            <div class="finance-empty">
                <div style="font-size:2.4rem; margin-bottom:0.5rem;">📄</div>
                <h3>هیچ فاکتور یا پیش‌فاکتوری ثبت نشده است</h3>
                <p style="margin:0.5rem 0 0; line-height:2;">برای شروع می‌توانی از دکمه ثبت پیش‌فاکتور استفاده کنی یا از مسیر سفارش‌ها فاکتور رسمی صادر کنی.</p>
            </div>
        @endif
    </section>
</div>

{{-- مودال ثبت پیش‌فاکتور --}}
<div class="finance-modal" id="proformaModal">
    <div class="finance-modal-card">
        <header>
            <div>
                <span class="finance-chip">ثبت پیش‌فاکتور جدید</span>
                <h2>پیش‌فاکتور جدید</h2>
                <p>اطلاعات اولیه پیش‌فاکتور را وارد کن. این سند رسمی مالیاتی نیست و برای اعلام قیمت اولیه استفاده می‌شود.</p>
            </div>
            <button type="button" onclick="closeProformaModal()" title="بستن">×</button>
        </header>

        <form method="post" action="{{ url('/app/tax-invoices/proforma') }}" class="finance-modal-form">
            @csrf
            <div class="finance-modal-grid">
                <div>
                    <label>مشتری (اختیاری)</label>
                    <select name="customer_id">
                        <option value="">— بدون مشتری مشخص —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">
                                {{ $customer->full_name }}
                                @if($customer->phone) - {{ $customer->phone }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>عنوان پیش‌فاکتور</label>
                    <input name="title" required placeholder="مثلاً پیش‌فاکتور خرید محصول">
                </div>
                <div>
                    <label>مبلغ کل (تومان)</label>
                    <input name="total_amount" class="ltr" type="number" min="0" value="0" required>
                </div>
                <div>
                    <label>نوع تخفیف</label>
                    <select name="discount_type">
                        <option value="fixed">مبلغی</option>
                        <option value="percent">درصدی</option>
                    </select>
                </div>
                <div>
                    <label>مقدار تخفیف</label>
                    <input name="discount_value" class="ltr" type="number" min="0" value="0" placeholder="اگر درصدی است مثلاً ۱۰">
                </div>
                <div>
                    <label>درصد مالیات (٪)</label>
                    <input name="vat_percent" id="proformaVatPercent" class="ltr" type="number" min="0" max="100" step="0.1" value="9" placeholder="مثلاً ۹ برای ۹ درصد">
                    <small style="color:var(--mut); font-size:0.72rem; font-weight:800; margin-top:0.25rem;">
                        مبلغ مالیات: <b id="proformaVatCalc" style="color:var(--acc);">۰</b> تومان
                    </small>
                    <input name="vat_amount" id="proformaVatAmount" type="hidden" value="0">
                </div>
                <div>
                    <label>اعتبار تا تاریخ (شمسی، اختیاری)</label>
                    <input name="due_at" class="jdate" placeholder="۱۴۰۵/۰۱/۰۱" autocomplete="off">
                </div>
                <div>
                    <label>شماره پیگیری (اختیاری)</label>
                    <input name="reference_number" class="ltr" placeholder="اختیاری">
                </div>
                <div class="finance-modal-wide">
                    <label>یادداشت</label>
                    <textarea name="notes" rows="3" placeholder="توضیح کوتاه، شرایط پرداخت یا توضیح کالا و خدمات"></textarea>
                </div>
            </div>

            <div class="finance-modal-actions">
                <button class="btn btn-ghost" type="button" onclick="closeProformaModal()">انصراف</button>
                <button class="btn">ثبت پیش‌فاکتور</button>
            </div>
        </form>
    </div>
</div>

{{-- مودال پیام سیستم --}}
<div class="finance-modal" id="financeNoticeModal">
    <div class="finance-modal-card finance-notice-card">
        <header>
            <div>
                <span class="finance-chip" id="financeNoticeTitle">پیام سیستم</span>
                <h2 id="financeNoticeHeading">اطلاع‌رسانی</h2>
                <p id="financeNoticeBody">پیام</p>
            </div>
            <button type="button" onclick="closeFinanceNotice()" title="بستن">×</button>
        </header>
        <div class="finance-modal-actions" style="padding:1rem 1.15rem;">
            <button class="btn" type="button" onclick="closeFinanceNotice()">متوجه شدم</button>
        </div>
    </div>
</div>

<script>
    function openProformaModal() {
        document.getElementById('proformaModal')?.classList.add('show');
        recalcProformaVat();
    }
    function closeProformaModal() {
        document.getElementById('proformaModal')?.classList.remove('show');
    }

    // محاسبه خودکار مبلغ مالیات از درصد
    function toFa(n) {
        try {
            return Number(n).toLocaleString('fa-IR');
        } catch(e) { return String(n); }
    }
    function recalcProformaVat() {
        var form = document.querySelector('#proformaModal form');
        if (!form) return;
        var total     = parseFloat(form.querySelector('[name="total_amount"]')?.value || 0);
        var discType  = form.querySelector('[name="discount_type"]')?.value || 'fixed';
        var discVal   = parseFloat(form.querySelector('[name="discount_value"]')?.value || 0);
        var vatPct    = parseFloat(form.querySelector('[name="vat_percent"]')?.value || 0);

        var discountAmount = (discType === 'percent')
            ? Math.round(total * discVal / 100)
            : Math.round(discVal);

        var base = Math.max(0, total - discountAmount);
        var vat  = Math.round(base * vatPct / 100);

        var out = document.getElementById('proformaVatCalc');
        if (out) out.textContent = toFa(vat);

        var hidden = document.getElementById('proformaVatAmount');
        if (hidden) hidden.value = vat;
    }
    document.addEventListener('input', function(e) {
        if (!e.target || !e.target.closest) return;
        if (e.target.closest('#proformaModal')) recalcProformaVat();
    });
    document.addEventListener('change', function(e) {
        if (!e.target || !e.target.closest) return;
        if (e.target.closest('#proformaModal')) recalcProformaVat();
    });

    function showFinanceNotice(title, body) {
        document.getElementById('financeNoticeTitle').textContent = title;
        document.getElementById('financeNoticeHeading').textContent = title;
        document.getElementById('financeNoticeBody').textContent = body;
        document.getElementById('financeNoticeModal')?.classList.add('show');
    }
    function closeFinanceNotice() {
        document.getElementById('financeNoticeModal')?.classList.remove('show');
    }
    window.addEventListener('click', function (event) {
        if (event.target && event.target.classList && event.target.classList.contains('finance-modal')) {
            event.target.classList.remove('show');
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeProformaModal();
            closeFinanceNotice();
        }
    });
</script>
@endsection