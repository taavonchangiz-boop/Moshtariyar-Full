@extends('layouts.app')
@section('title', ($invoice->invoice_kind ?? 'official') === 'proforma' ? 'جزئیات پیش‌فاکتور' : 'جزئیات فاکتور رسمی')
@section('heading', ($invoice->invoice_kind ?? 'official') === 'proforma' ? 'جزئیات پیش‌فاکتور' : 'جزئیات فاکتور رسمی')
@section('subtitle', 'نمایش کامل سند، اقلام، جمع مالی و چاپ رسمی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/finance-board.css') }}">
<link rel="stylesheet" href="{{ asset('css/finance-invoice-show.css') }}">

@php
    use Modules\Core\Support\Jalali;
    use Modules\Core\Support\Money;
    use Modules\Core\Entities\Setting;

    // نوع سند
    $isProforma = ($invoice->invoice_kind ?? 'official') === 'proforma';
    $kindLabel  = $isProforma ? 'پیش‌فاکتور' : 'فاکتور رسمی';
    $kindClass  = $isProforma ? 'is-proforma' : 'is-official';

    // نگاشت وضعیت
    $statusMap = [
        'draft'     => ['پیش‌نویس',    'is-muted'],
        'queued'    => ['در صف ارسال', 'is-warn'],
        'sent'      => ['ارسال شده',   'is-ok'],
        'confirmed' => ['تأیید شده',   'is-ok'],
        'rejected'  => ['رد شده',      'is-bad'],
        'failed'    => ['ناموفق',      'is-bad'],
        'paid'      => ['پرداخت شده',  'is-ok'],
        'expired'   => ['منقضی شده',   'is-bad'],
    ];
    [$statusText, $statusClass] = $statusMap[$invoice->status] ?? [($invoice->status ?: 'نامشخص'), 'is-muted'];

    // اقلام سند
    $items = [];
    if (is_array($invoice->items)) {
        $items = $invoice->items;
    } elseif (is_string($invoice->items) && $invoice->items !== '') {
        $decoded = json_decode($invoice->items, true);
        if (is_array($decoded)) $items = $decoded;
    }
    if (empty($items)) {
        $items = [[
            'title'          => $invoice->title ?? 'شرح سند',
            'quantity'       => 1,
            'unit_price'     => $invoice->subtotal ?? $invoice->payable ?? 0,
            'discount_value' => $invoice->discount ?? 0,
            'tax_amount'     => $invoice->vat_amount ?? 0,
            'row_total'      => $invoice->payable ?? 0,
        ]];
    }

    // جمع مالی
    $subtotal  = (int) ($invoice->subtotal ?? array_sum(array_map(function($i){
        return (int)($i['unit_price'] ?? 0) * (int)($i['quantity'] ?? 1);
    }, $items)));
    $discount  = (int) ($invoice->discount ?? 0);
    $vat       = (int) ($invoice->vat_amount ?? 0);
    $payable   = (int) ($invoice->payable ?? max(0, $subtotal - $discount + $vat));

    // درصد مالیات (برای نمایش)
    $vatPercent = ($subtotal - $discount) > 0
        ? round(($vat / ($subtotal - $discount)) * 100, 1)
        : 0;

    // ---------- اطلاعات کسب‌وکار ----------
    $bizName     = Setting::get('business_name',     'مشتری‌یار');
    $bizPhone    = Setting::get('business_phone',    '');
    $bizAddress  = Setting::get('business_address',  '');
    $bizEconomic = Setting::get('business_economic_code', '');
    $bizNational = Setting::get('business_national_id',   '');
    $bizPostal   = Setting::get('business_postal_code',   '');
    $bizRegNo    = Setting::get('business_registration_no','');
    $bizWebsite  = Setting::get('business_website',  'ayarpro.ir');
    $bizEmail    = Setting::get('business_email',    '');
    $bizBranch   = Setting::get('business_branch',   'دفتر مرکزی');

    // ---------- تنظیمات ظاهر فاکتور (اگر روی سرور ذخیره شده باشند) ----------
    $desTheme      = Setting::get('invoice_theme',      'classic');   // classic | modern
    $desPrimary    = Setting::get('invoice_primary',    '#0f172a');   // رنگ اصلی
    $desShowLogo   = Setting::get('invoice_show_logo',  '1');         // نمایش لوگو
    $desShowSig    = Setting::get('invoice_show_sig',   '1');         // نمایش امضا
    $desFooterTxt  = Setting::get('invoice_footer_text','با تشکر از خرید شما');
    $desTermsTxt   = Setting::get('invoice_terms_text', 'مبلغ ثبت شده در این سند بر پایه توافق طرفین است و پس از پرداخت قطعی می‌گردد.');
    $desSellerSig  = Setting::get('invoice_seller_sig', $bizName);
    $desLogoText   = Setting::get('invoice_logo_text',  mb_substr($bizName, 0, 12));

    // مشتری
    $cust = $invoice->customer;
    $custName    = $cust->full_name   ?? 'مشتری نامشخص';
    $custPhone   = $cust->phone       ?? '';
    $custEmail   = $cust->email       ?? '';
    $custAddress = $cust->address     ?? '';
    $custNational= $cust->national_id ?? '';
    $custEconomic= $cust->economic_code ?? '';
    $custPostal  = $cust->postal_code ?? '';

    // شماره سند و تاریخ‌ها
    $serial       = $invoice->serial ?: ($invoice->tax_id ?: ('IVN-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT)));
    $issuedAt     = $invoice->issued_at ?: $invoice->created_at;
    $issuedShamsi = $issuedAt ? Jalali::datetime($issuedAt) : '—';
    $issuedDate   = $issuedAt ? Jalali::date($issuedAt) : '—';
    $dueShamsi    = $invoice->due_at ? Jalali::date($invoice->due_at) : null;

    $minRows   = 8;
    $fillerCnt = max(0, $minRows - count($items));
@endphp

<div class="invoice-show-page" id="invoiceShowPage">

    {{-- هدر بالای صفحه --}}
    <section class="invoice-hero no-print">
        <div>
            <span class="invoice-eyebrow {{ $kindClass }}">{{ $kindLabel }}</span>
            <h2>{{ $kindLabel }} شماره <span style="direction:ltr; display:inline-block;">{{ $serial }}</span></h2>
            <p>{{ $invoice->title ?? 'سند مالی' }} — مشتری: {{ $custName }}</p>
            <div class="invoice-hero-meta">
                <span class="invoice-chip {{ $statusClass }}">وضعیت: <b>{{ $statusText }}</b></span>
                <span class="invoice-chip">تاریخ صدور: <b>{{ $issuedShamsi }}</b></span>
                @if($dueShamsi)
                    <span class="invoice-chip is-warn">اعتبار تا: <b>{{ $dueShamsi }}</b></span>
                @endif
                <span class="invoice-chip">قابل پرداخت: <b>{{ Money::show($payable) }} {{ Money::unitLabel() }}</b></span>
                @if($vatPercent > 0)
                    <span class="invoice-chip">مالیات: <b>{{ $vatPercent }}٪</b></span>
                @endif
            </div>
        </div>
        <div class="invoice-hero-actions">
            <button class="btn" type="button" onclick="openPrintPreview()">🖨 چاپ سند</button>
            <button class="btn btn-ghost" type="button" onclick="openPrintPreview()">پیش‌نمایش چاپ</button>
            <button class="btn btn-ghost" type="button" onclick="openDesignPanel()">🎨 طراحی ظاهر</button>
            <a class="btn btn-ghost" href="{{ url('/app/tax-invoices') }}">← بازگشت به فهرست</a>
        </div>
    </section>

    {{-- طرفین سند --}}
    <section class="invoice-parties no-print">
        <article class="invoice-party-card">
            <header>
                <h3>اطلاعات فروشنده</h3>
                <span class="invoice-chip">{{ $bizBranch }}</span>
            </header>
            <div class="party-rows">
                <div class="party-row"><span>نام کسب‌وکار</span><b>{{ $bizName }}</b></div>
                @if($bizEconomic)<div class="party-row ltr"><span>کد اقتصادی</span><b>{{ $bizEconomic }}</b></div>@endif
                @if($bizNational)<div class="party-row ltr"><span>شناسه ملی</span><b>{{ $bizNational }}</b></div>@endif
                @if($bizRegNo)<div class="party-row ltr"><span>شماره ثبت</span><b>{{ $bizRegNo }}</b></div>@endif
                @if($bizPhone)<div class="party-row ltr"><span>تلفن</span><b>{{ $bizPhone }}</b></div>@endif
                @if($bizAddress)<div class="party-row"><span>نشانی</span><b>{{ $bizAddress }}</b></div>@endif
                @if($bizPostal)<div class="party-row ltr"><span>کد پستی</span><b>{{ $bizPostal }}</b></div>@endif
                @if($bizWebsite)<div class="party-row ltr"><span>وب‌سایت</span><b>{{ $bizWebsite }}</b></div>@endif
            </div>
        </article>

        <article class="invoice-party-card">
            <header>
                <h3>اطلاعات مشتری</h3>
                @if($cust)
                    <a class="invoice-chip" href="{{ url('/app/customers/'.$cust->id) }}">پرونده مشتری</a>
                @else
                    <span class="invoice-chip is-muted">بدون مشتری</span>
                @endif
            </header>
            <div class="party-rows">
                <div class="party-row"><span>نام مشتری</span><b>{{ $custName }}</b></div>
                @if($custPhone)<div class="party-row ltr"><span>موبایل</span><b>{{ $custPhone }}</b></div>@endif
                @if($custEmail)<div class="party-row ltr"><span>ایمیل</span><b>{{ $custEmail }}</b></div>@endif
                @if($custNational)<div class="party-row ltr"><span>کد ملی</span><b>{{ $custNational }}</b></div>@endif
                @if($custEconomic)<div class="party-row ltr"><span>کد اقتصادی</span><b>{{ $custEconomic }}</b></div>@endif
                @if($custAddress)<div class="party-row"><span>نشانی</span><b>{{ $custAddress }}</b></div>@endif
                @if($custPostal)<div class="party-row ltr"><span>کد پستی</span><b>{{ $custPostal }}</b></div>@endif
                @if(!$custPhone && !$custEmail && !$custNational && !$custAddress)
                    <div class="party-row"><span>—</span><b>اطلاعات تکمیلی مشتری ثبت نشده است.</b></div>
                @endif
            </div>
        </article>
    </section>

    {{-- اقلام --}}
    <section class="invoice-items-card no-print">
        <header>
            <h3>اقلام و شرح سند</h3>
            <small>مجموع اقلام: {{ Money::show($subtotal) }} {{ Money::unitLabel() }}</small>
        </header>

        <div class="invoice-items-wrap">
            <table class="invoice-items-table">
                <thead>
                    <tr>
                        <th style="width:3rem;">ردیف</th>
                        <th>شرح کالا یا خدمات</th>
                        <th style="width:5rem;">تعداد</th>
                        <th style="width:9rem;">مبلغ واحد</th>
                        <th style="width:8rem;">تخفیف</th>
                        <th style="width:7rem;">مالیات</th>
                        <th style="width:10rem;">قابل پرداخت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $i => $it)
                        @php
                            $qty      = (int) ($it['quantity']       ?? 1);
                            $unit     = (int) ($it['unit_price']     ?? 0);
                            $rowDisc  = (int) ($it['discount_value'] ?? 0);
                            $rowTax   = (int) ($it['tax_amount']     ?? 0);
                            $rowTotal = (int) ($it['row_total']      ?? max(0, $qty * $unit - $rowDisc + $rowTax));
                            $rowTitle = $it['title'] ?? ($it['name'] ?? 'شرح');
                        @endphp
                        <tr>
                            <td class="row-index">{{ $i + 1 }}</td>
                            <td>{{ $rowTitle }}</td>
                            <td class="qty">{{ $qty }}</td>
                            <td class="num">{{ Money::show($unit) }} {{ Money::unitLabel() }}</td>
                            <td class="num">{{ $rowDisc > 0 ? Money::show($rowDisc) . ' ' . Money::unitLabel() : '—' }}</td>
                            <td class="num">{{ $rowTax > 0 ? Money::show($rowTax) . ' ' . Money::unitLabel() : '—' }}</td>
                            <td class="num">{{ Money::show($rowTotal) }} {{ Money::unitLabel() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="invoice-totals">
            <div class="invoice-total-box" style="--total-color:#0ea5e9;">
                <span>جمع اقلام</span>
                <b>{{ Money::show($subtotal) }} {{ Money::unitLabel() }}</b>
            </div>
            <div class="invoice-total-box" style="--total-color:#f59e0b;">
                <span>تخفیف کل</span>
                <b>{{ Money::show($discount) }} {{ Money::unitLabel() }}</b>
            </div>
            <div class="invoice-total-box" style="--total-color:#8b5cf6;">
                <span>مالیات{{ $vatPercent > 0 ? ' ('.$vatPercent.'٪)' : '' }}</span>
                <b>{{ Money::show($vat) }} {{ Money::unitLabel() }}</b>
            </div>
            <div class="invoice-total-box is-primary">
                <span>مبلغ قابل پرداخت</span>
                <b>{{ Money::show($payable) }} {{ Money::unitLabel() }}</b>
            </div>
        </div>

        @if(!empty($invoice->notes))
            <div class="invoice-note">
                <span>یادداشت سند:</span>
                {{ $invoice->notes }}
            </div>
        @endif

        <div class="invoice-footer-actions" style="margin-top:1rem;">
            <a class="btn btn-ghost" href="{{ url('/app/tax-invoices') }}">← بازگشت به فهرست</a>
            <div style="display:flex; gap:0.55rem; flex-wrap:wrap;">
                <button class="btn" type="button" onclick="openPrintPreview()">🖨 چاپ سند</button>
                <button class="btn btn-ghost" type="button" onclick="openDesignPanel()">🎨 طراحی ظاهر</button>
            </div>
        </div>
    </section>
</div>

{{-- ==================== پاپ‌آپ پیش‌نمایش چاپ ==================== --}}
<div class="invoice-preview-modal" id="invoicePreviewModal" onclick="closePrintPreview(event)">
    <div class="invoice-preview-shell" onclick="event.stopPropagation()">
        <header class="invoice-preview-header">
            <div>
                <span class="invoice-chip {{ $kindClass }}">پیش‌نمایش چاپ</span>
                <h3>{{ $kindLabel }} — {{ $serial }}</h3>
            </div>
            <div class="invoice-preview-toolbar">
                <div class="theme-switch">
                    <button type="button" class="theme-btn is-active" data-theme="classic" onclick="applyTheme('classic')">طرح کلاسیک</button>
                    <button type="button" class="theme-btn" data-theme="modern" onclick="applyTheme('modern')">طرح مدرن</button>
                </div>
                <button class="btn" type="button" onclick="doPrintNow()">🖨 چاپ</button>
                <button class="btn btn-ghost" type="button" onclick="closePrintPreview()">بستن</button>
            </div>
        </header>

        <div class="invoice-preview-body">
            {{-- برگه چاپ A4 --}}
            <div class="invoice-print-sheet theme-{{ $desTheme }}" id="invoicePrintSheet"
                 style="--print-primary: {{ $desPrimary }};">
                <div class="print-frame">

                    {{-- هدر رسمی --}}
                    <div class="print-head">
                        <div class="brand" data-show-logo="{{ $desShowLogo }}">
                            <div class="logo" id="printLogo">{{ $desLogoText }}</div>
                            <div class="brand-text">
                                <p class="name" id="printBizName">{{ $bizName }}</p>
                                @if($bizAddress)<p class="desc">{{ $bizAddress }}</p>@endif
                                @if($bizPhone)<p class="desc" style="direction:ltr; text-align:right;">تلفن: {{ $bizPhone }}</p>@endif
                            </div>
                        </div>

                        <div class="title-box">
                            <h1>{{ $kindLabel }}</h1>
                            <span class="kind {{ $kindClass }}">{{ $isProforma ? 'اعلام قیمت اولیه' : 'سند رسمی مالیاتی' }}</span>
                        </div>

                        <div class="serial-box">
                            <div><span class="label">شماره سند:</span></div>
                            <div class="value">{{ $serial }}</div>
                            <span class="date">تاریخ صدور: {{ $issuedDate }}</span>
                            @if($dueShamsi)<span class="date">اعتبار تا: {{ $dueShamsi }}</span>@endif
                        </div>
                    </div>

                    {{-- طرفین --}}
                    <div class="print-parties">
                        <div class="print-party">
                            <h3>مشخصات فروشنده</h3>
                            <div class="row"><span>نام</span><b>{{ $bizName }}</b></div>
                            @if($bizEconomic)<div class="row ltr"><span>کد اقتصادی</span><b>{{ $bizEconomic }}</b></div>@endif
                            @if($bizNational)<div class="row ltr"><span>شناسه ملی</span><b>{{ $bizNational }}</b></div>@endif
                            @if($bizRegNo)<div class="row ltr"><span>شماره ثبت</span><b>{{ $bizRegNo }}</b></div>@endif
                            @if($bizPhone)<div class="row ltr"><span>تلفن</span><b>{{ $bizPhone }}</b></div>@endif
                            @if($bizAddress)<div class="row"><span>نشانی</span><b>{{ $bizAddress }}</b></div>@endif
                            @if($bizPostal)<div class="row ltr"><span>کد پستی</span><b>{{ $bizPostal }}</b></div>@endif
                        </div>

                        <div class="print-party">
                            <h3>مشخصات خریدار</h3>
                            <div class="row"><span>نام</span><b>{{ $custName }}</b></div>
                            @if($custNational)<div class="row ltr"><span>کد ملی</span><b>{{ $custNational }}</b></div>@endif
                            @if($custEconomic)<div class="row ltr"><span>کد اقتصادی</span><b>{{ $custEconomic }}</b></div>@endif
                            @if($custPhone)<div class="row ltr"><span>تلفن</span><b>{{ $custPhone }}</b></div>@endif
                            @if($custAddress)<div class="row"><span>نشانی</span><b>{{ $custAddress }}</b></div>@endif
                            @if($custPostal)<div class="row ltr"><span>کد پستی</span><b>{{ $custPostal }}</b></div>@endif
                            @if(!$custPhone && !$custNational && !$custAddress && !$custEconomic && !$custPostal)
                                <div class="row"><span>توضیح</span><b>اطلاعات تکمیلی خریدار ثبت نشده است.</b></div>
                            @endif
                        </div>
                    </div>

                    {{-- عنوان جدول اقلام --}}
                    <div class="print-section-title">اقلام و شرح خدمات / کالا</div>

                    {{-- جدول اقلام --}}
                    <table class="print-items">
                        <thead>
                            <tr>
                                <th class="col-row">ردیف</th>
                                <th class="col-desc">شرح کالا یا خدمات</th>
                                <th class="col-qty">تعداد</th>
                                <th class="col-price">مبلغ واحد (تومان)</th>
                                <th class="col-disc">تخفیف (تومان)</th>
                                <th class="col-tax">مالیات (تومان)</th>
                                <th class="col-total">قابل پرداخت (تومان)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $i => $it)
                                @php
                                    $qty      = (int) ($it['quantity']       ?? 1);
                                    $unit     = (int) ($it['unit_price']     ?? 0);
                                    $rowDisc  = (int) ($it['discount_value'] ?? 0);
                                    $rowTax   = (int) ($it['tax_amount']     ?? 0);
                                    $rowTotal = (int) ($it['row_total']      ?? max(0, $qty * $unit - $rowDisc + $rowTax));
                                    $rowTitle = $it['title'] ?? ($it['name'] ?? 'شرح');
                                @endphp
                                <tr>
                                    <td class="row-num">{{ $i + 1 }}</td>
                                    <td class="text">{{ $rowTitle }}</td>
                                    <td>{{ $qty }}</td>
                                    <td>{{ Money::show($unit) }}</td>
                                    <td>{{ $rowDisc > 0 ? Money::show($rowDisc) : '—' }}</td>
                                    <td>{{ $rowTax > 0 ? Money::show($rowTax) : '—' }}</td>
                                    <td>{{ Money::show($rowTotal) }}</td>
                                </tr>
                            @endforeach

                            @for($f = 0; $f < $fillerCnt; $f++)
                                <tr class="filler">
                                    <td>{{ count($items) + $f + 1 }}</td>
                                    <td class="text">—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                    {{-- جمع مالی و یادداشت --}}
                    <div class="print-summary">
                        <div class="notes">
                            <h4>یادداشت و شرایط</h4>
                            <p id="printNoteText">{{ $invoice->notes ?: 'شرایط عمومی: مبلغ ثبت شده در این سند بر پایه توافق طرفین است.' }}</p>
                            <div class="conditions" id="printTermsText">
                                @if($isProforma)
                                    این سند پیش‌فاکتور و فقط اعلام قیمت اولیه است و بار مالیاتی رسمی ندارد.
                                    اعتبار سند تا تاریخ درج شده در بالا معتبر خواهد بود.
                                @else
                                    {{ $desTermsTxt }}
                                @endif
                            </div>
                        </div>

                        <div class="totals">
                            <div class="line"><span>جمع اقلام</span><b>{{ Money::show($subtotal) }} تومان</b></div>
                            <div class="line"><span>تخفیف کل</span><b>{{ Money::show($discount) }} تومان</b></div>
                            <div class="line"><span>مالیات{{ $vatPercent > 0 ? ' ('.$vatPercent.'٪)' : '' }}</span><b>{{ Money::show($vat) }} تومان</b></div>
                            <div class="line grand"><span>قابل پرداخت</span><b>{{ Money::show($payable) }} تومان</b></div>
                        </div>
                    </div>

                    {{-- امضاها --}}
                    <div class="print-signatures" data-show-sig="{{ $desShowSig }}">
                        <div class="sig">
                            <div class="title">مهر و امضای فروشنده</div>
                            <div class="name" id="printSellerSig">{{ $desSellerSig }}</div>
                        </div>
                        <div class="sig">
                            <div class="title">امضای خریدار</div>
                            <div class="name">{{ $custName }}</div>
                        </div>
                        <div class="sig">
                            <div class="title">تاریخ صدور</div>
                            <div class="name">{{ $issuedDate }}</div>
                        </div>
                    </div>

                    {{-- فوتر --}}
                    <div class="print-foot">
                        <div class="contact" id="printFooterText">
                            {{ $desFooterTxt }}
                            @if($bizPhone) — <b>تلفن:</b> <span style="direction:ltr;">{{ $bizPhone }}</span>@endif
                            @if($bizWebsite) — <b>وب‌سایت:</b> <span style="direction:ltr;">{{ $bizWebsite }}</span>@endif
                        </div>
                        <div class="page-no">صفحه ۱ از ۱</div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- ==================== پاپ‌آپ طراحی ظاهر ==================== --}}
<div class="invoice-preview-modal" id="invoiceDesignModal" onclick="closeDesignPanel(event)">
    <div class="invoice-design-shell" onclick="event.stopPropagation()">
        <header class="invoice-preview-header">
            <div>
                <span class="invoice-chip">طراحی ظاهر فاکتور</span>
                <h3>سفارشی‌سازی طرح و رنگ سند</h3>
            </div>
            <div class="invoice-preview-toolbar">
                <button class="btn" type="button" onclick="saveDesign()">ذخیره تنظیمات</button>
                <button class="btn btn-ghost" type="button" onclick="resetDesign()">بازگردانی پیش‌فرض</button>
                <button class="btn btn-ghost" type="button" onclick="closeDesignPanel()">بستن</button>
            </div>
        </header>

        <div class="invoice-design-body">
            <div class="design-form">
                <div class="design-group">
                    <label>طرح فاکتور</label>
                    <div class="theme-switch design-theme-switch">
                        <button type="button" class="theme-btn" data-design-theme="classic" onclick="setDesignTheme('classic')">کلاسیک رسمی</button>
                        <button type="button" class="theme-btn" data-design-theme="modern" onclick="setDesignTheme('modern')">مدرن مینیمال</button>
                    </div>
                    <small>طرح کلاسیک برای اسناد رسمی و طرح مدرن برای پیش‌فاکتور و اعلام قیمت مناسب است.</small>
                </div>

                <div class="design-group">
                    <label>رنگ اصلی سند</label>
                    <div class="color-picker">
                        <input type="color" id="designPrimary" value="{{ $desPrimary }}" oninput="setDesignPrimary(this.value)">
                        <div class="color-presets">
                            <button type="button" class="color-preset" style="background:#0f172a;" data-color="#0f172a" onclick="setDesignPrimary('#0f172a')" title="سرمه‌ای"></button>
                            <button type="button" class="color-preset" style="background:#10b981;" data-color="#10b981" onclick="setDesignPrimary('#10b981')" title="سبز"></button>
                            <button type="button" class="color-preset" style="background:#0369a1;" data-color="#0369a1" onclick="setDesignPrimary('#0369a1')" title="آبی"></button>
                            <button type="button" class="color-preset" style="background:#6d28d9;" data-color="#6d28d9" onclick="setDesignPrimary('#6d28d9')" title="بنفش"></button>
                            <button type="button" class="color-preset" style="background:#b45309;" data-color="#b45309" onclick="setDesignPrimary('#b45309')" title="نارنجی"></button>
                            <button type="button" class="color-preset" style="background:#b91c1c;" data-color="#b91c1c" onclick="setDesignPrimary('#b91c1c')" title="قرمز"></button>
                        </div>
                    </div>
                </div>

                <div class="design-group design-toggles">
                    <label class="design-check">
                        <input type="checkbox" id="designShowLogo" {{ $desShowLogo == '1' ? 'checked' : '' }} onchange="toggleLogo()">
                        <span>نمایش لوگو و نام کسب‌وکار در بالای فاکتور</span>
                    </label>
                    <label class="design-check">
                        <input type="checkbox" id="designShowSig" {{ $desShowSig == '1' ? 'checked' : '' }} onchange="toggleSig()">
                        <span>نمایش بخش امضاها (فروشنده، خریدار، تاریخ)</span>
                    </label>
                </div>

                <div class="design-group">
                    <label>متن جایگزین لوگو</label>
                    <input type="text" id="designLogoText" value="{{ $desLogoText }}" oninput="setLogoText(this.value)" placeholder="نام مختصر کسب‌وکار">
                    <small>حداکثر ۱۲ کاراکتر — اگر لوگوی تصویری ندارید، این متن در کادر لوگو نمایش داده می‌شود.</small>
                </div>

                <div class="design-group">
                    <label>نام و امضای فروشنده</label>
                    <input type="text" id="designSellerSig" value="{{ $desSellerSig }}" oninput="setSellerSig(this.value)" placeholder="نام مدیر فروش یا کسب‌وکار">
                </div>

                <div class="design-group">
                    <label>متن شرایط پیش‌فرض (پایین سند)</label>
                    <textarea id="designTerms" rows="3" oninput="setTermsText(this.value)" placeholder="مثلاً: مبلغ ثبت شده پس از پرداخت قطعی می‌گردد.">{{ $desTermsTxt }}</textarea>
                </div>

                <div class="design-group">
                    <label>متن فوتر (پایین برگه)</label>
                    <input type="text" id="designFooter" value="{{ $desFooterTxt }}" oninput="setFooterText(this.value)" placeholder="با تشکر از خرید شما">
                </div>
            </div>

            {{-- پیش‌نمایش زنده کوچک --}}
            <div class="design-preview">
                <div class="design-preview-label">پیش‌نمایش زنده</div>
                <div class="design-preview-frame">
                    <div class="design-preview-hint">
                        برای مشاهده تغییرات کامل، پس از ذخیره روی «پیش‌نمایش چاپ» بزنید.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ==================== پاپ‌آپ پیش‌نمایش ====================
    function openPrintPreview() {
        document.getElementById('invoicePreviewModal')?.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closePrintPreview(evt) {
        if (evt && evt.target && !evt.target.classList.contains('invoice-preview-modal')) return;
        document.getElementById('invoicePreviewModal')?.classList.remove('show');
        document.body.style.overflow = '';
    }
    function doPrintNow() {
        // برای چاپ فقط، body کلاس چاپ می‌گیرد
        document.body.classList.add('is-printing');
        setTimeout(function(){
            window.print();
            setTimeout(function(){ document.body.classList.remove('is-printing'); }, 100);
        }, 60);
    }

    // ==================== سوییچ طرح در پیش‌نمایش ====================
    function applyTheme(name) {
        var sheet = document.getElementById('invoicePrintSheet');
        if (!sheet) return;
        sheet.classList.remove('theme-classic','theme-modern');
        sheet.classList.add('theme-' + name);
        document.querySelectorAll('.invoice-preview-toolbar .theme-btn').forEach(function(b){
            b.classList.toggle('is-active', b.dataset.theme === name);
        });
    }

    // ==================== پاپ‌آپ طراحی ====================
    var DESIGN_KEY = 'ayarpro_invoice_design';

    function openDesignPanel() {
        document.getElementById('invoiceDesignModal')?.classList.add('show');
        document.body.style.overflow = 'hidden';
        loadDesignState();
    }
    function closeDesignPanel(evt) {
        if (evt && evt.target && !evt.target.classList.contains('invoice-preview-modal')) return;
        document.getElementById('invoiceDesignModal')?.classList.remove('show');
        document.body.style.overflow = '';
    }

    function setDesignTheme(name) {
        applyTheme(name);
        document.querySelectorAll('.design-theme-switch .theme-btn').forEach(function(b){
            b.classList.toggle('is-active', b.dataset.designTheme === name);
        });
    }
    function setDesignPrimary(color) {
        var sheet = document.getElementById('invoicePrintSheet');
        if (sheet) sheet.style.setProperty('--print-primary', color);
        var input = document.getElementById('designPrimary');
        if (input && input.value.toLowerCase() !== color.toLowerCase()) input.value = color;
        document.querySelectorAll('.color-preset').forEach(function(b){
            b.classList.toggle('is-active', b.dataset.color && b.dataset.color.toLowerCase() === color.toLowerCase());
        });
    }
    function toggleLogo() {
        var chk = document.getElementById('designShowLogo');
        var brand = document.querySelector('#invoicePrintSheet .print-head .brand');
        if (brand) brand.dataset.showLogo = chk.checked ? '1' : '0';
    }
    function toggleSig() {
        var chk = document.getElementById('designShowSig');
        var sig = document.querySelector('#invoicePrintSheet .print-signatures');
        if (sig) sig.dataset.showSig = chk.checked ? '1' : '0';
    }
    function setLogoText(v) {
        var el = document.getElementById('printLogo');
        if (el) el.textContent = v.substring(0, 12);
    }
    function setSellerSig(v) {
        var el = document.getElementById('printSellerSig');
        if (el) el.textContent = v;
    }
    function setTermsText(v) {
        var el = document.getElementById('printTermsText');
        if (el) el.textContent = v;
    }
    function setFooterText(v) {
        var el = document.getElementById('printFooterText');
        if (el) el.textContent = v;
    }

    // ==================== ذخیره تنظیمات ====================
    function saveDesign() {
        var state = {
            theme:      (document.querySelector('#invoicePrintSheet.theme-modern')) ? 'modern' : 'classic',
            primary:    document.getElementById('designPrimary').value,
            show_logo:  document.getElementById('designShowLogo').checked ? '1' : '0',
            show_sig:   document.getElementById('designShowSig').checked ? '1' : '0',
            logo_text:  document.getElementById('designLogoText').value,
            seller_sig: document.getElementById('designSellerSig').value,
            terms:      document.getElementById('designTerms').value,
            footer:     document.getElementById('designFooter').value,
        };
        try { localStorage.setItem(DESIGN_KEY, JSON.stringify(state)); } catch(e){}

        // ارسال به سرور برای ذخیره دائمی (اگر Controller این کلیدها را بپذیرد)
        var fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        fd.append('invoice_theme',       state.theme);
        fd.append('invoice_primary',     state.primary);
        fd.append('invoice_show_logo',   state.show_logo);
        fd.append('invoice_show_sig',    state.show_sig);
        fd.append('invoice_logo_text',   state.logo_text);
        fd.append('invoice_seller_sig',  state.seller_sig);
        fd.append('invoice_terms_text',  state.terms);
        fd.append('invoice_footer_text', state.footer);

        fetch('{{ url('/app/tax-invoices/settings') }}', { method: 'POST', body: fd, credentials: 'same-origin' })
            .catch(function(){})
            .finally(function(){
                alert('✅ تنظیمات طراحی سند ذخیره شد.');
            });
    }
    function resetDesign() {
        try { localStorage.removeItem(DESIGN_KEY); } catch(e){}
        location.reload();
    }
    function loadDesignState() {
        try {
            var raw = localStorage.getItem(DESIGN_KEY);
            if (!raw) {
                setDesignTheme('{{ $desTheme }}');
                setDesignPrimary('{{ $desPrimary }}');
                return;
            }
            var s = JSON.parse(raw);
            if (s.theme)      { setDesignTheme(s.theme); }
            if (s.primary)    { document.getElementById('designPrimary').value = s.primary; setDesignPrimary(s.primary); }
            if (s.show_logo !== undefined) { document.getElementById('designShowLogo').checked = (s.show_logo === '1'); toggleLogo(); }
            if (s.show_sig  !== undefined) { document.getElementById('designShowSig').checked  = (s.show_sig  === '1'); toggleSig();  }
            if (s.logo_text)  { document.getElementById('designLogoText').value = s.logo_text; setLogoText(s.logo_text); }
            if (s.seller_sig) { document.getElementById('designSellerSig').value = s.seller_sig; setSellerSig(s.seller_sig); }
            if (s.terms)      { document.getElementById('designTerms').value = s.terms; setTermsText(s.terms); }
            if (s.footer)     { document.getElementById('designFooter').value = s.footer; setFooterText(s.footer); }
        } catch(e){}
    }

    // ==================== Esc برای بستن ====================
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (document.getElementById('invoicePreviewModal')?.classList.contains('show')) {
            closePrintPreview();
        }
        if (document.getElementById('invoiceDesignModal')?.classList.contains('show')) {
            closeDesignPanel();
        }
    });

    // بارگذاری اولیه تنظیمات ذخیره‌شده در localStorage
    (function(){
        try {
            var raw = localStorage.getItem(DESIGN_KEY);
            if (!raw) { setDesignTheme('{{ $desTheme }}'); setDesignPrimary('{{ $desPrimary }}'); return; }
            var s = JSON.parse(raw);
            if (s.theme)      { setDesignTheme(s.theme); }
            if (s.primary)    { setDesignPrimary(s.primary); }
            if (s.logo_text)  { setLogoText(s.logo_text); }
            if (s.seller_sig) { setSellerSig(s.seller_sig); }
            if (s.terms)      { setTermsText(s.terms); }
            if (s.footer)     { setFooterText(s.footer); }
            var brand = document.querySelector('#invoicePrintSheet .print-head .brand');
            if (brand && s.show_logo !== undefined) brand.dataset.showLogo = s.show_logo;
            var sigs  = document.querySelector('#invoicePrintSheet .print-signatures');
            if (sigs && s.show_sig !== undefined) sigs.dataset.showSig = s.show_sig;
        } catch(e){}
    })();
</script>
@endsection