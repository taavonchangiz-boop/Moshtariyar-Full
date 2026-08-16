@extends('layouts.app')
@section('title','پرداخت‌ها و تراکنش‌ها')
@section('heading','مرکز تراکنش‌های پرداخت')
@section('subtitle','پایش پرداخت‌ها، درگاه‌ها، وضعیت تراکنش و کدهای رهگیری')

@section('content')
<link rel="stylesheet" href="{{ asset('css/finance-board.css') }}">

@php
    $statusMap = [
        'pending'  => ['در انتظار', 'is-warn',  '#f59e0b'],
        'paid'     => ['موفق',      'is-ok',    '#10b981'],
        'failed'   => ['ناموفق',    'is-bad',   '#ef4444'],
        'canceled' => ['لغو شده',   'is-muted', '#64748b'],
    ];
    $gatewayLabels = [
        'zarinpal' => 'زرین‌پال',
        'zibal'    => 'زیبال',
        'sep'      => 'سپ',
        'saman'    => 'سامان',
        'mellat'   => 'ملت',
        'parsian'  => 'پارسیان',
    ];
    $visiblePayments = $payments->getCollection();
    $visibleAmount   = $visiblePayments->sum(fn($p) => (float) $p->amount);
    $paidAmount      = $visiblePayments->where('status', 'paid')->sum(fn($p) => (float) $p->amount);
    $paidCount       = $visiblePayments->where('status', 'paid')->count();
    $pendingCount    = $visiblePayments->where('status', 'pending')->count();
    $failedCount     = $visiblePayments->whereIn('status', ['failed', 'canceled'])->count();
    $totalCount      = method_exists($payments, 'total') ? $payments->total() : $visiblePayments->count();
@endphp

<div class="finance-page">

    {{-- هدر --}}
    <section class="finance-hero">
        <div>
            <span class="finance-eyebrow">مرکز پرداخت‌ها</span>
            <h2>همه تراکنش‌های پرداخت، وضعیت درگاه و رهگیری در یک نمای شفاف</h2>
            <p>پرداخت‌های ثبت‌شده از درگاه‌ها را بررسی کن، وضعیت موفق یا ناموفق را ببین و سفارش‌های مرتبط با هر پرداخت را سریع پیگیری کن.</p>
            <div class="finance-actions">
                <a class="btn" href="{{ url('/app/orders') }}">← بازگشت به سفارش‌ها</a>
                <a class="btn btn-ghost" href="{{ url('/app/tax-invoices') }}">فاکتورها</a>
                <a class="btn btn-ghost" href="{{ url('/app/reports?type=payments_by_gateway') }}">گزارش درگاه‌ها</a>
            </div>
        </div>
        <div class="finance-score">
            <div style="--metric-color:#0ea5e9;">
                <span>کل تراکنش‌ها</span>
                <b>@fa(number_format($totalCount))</b>
                <small>ثبت‌شده در سامانه</small>
            </div>
            <div style="--metric-color:#10b981;">
                <span>پرداخت موفق</span>
                <b>@fa(number_format($paidCount))</b>
                <small>مبلغ: @money($paidAmount) @unit</small>
            </div>
            <div style="--metric-color:#f59e0b;">
                <span>در انتظار</span>
                <b>@fa(number_format($pendingCount))</b>
                <small>در حال بررسی</small>
            </div>
            <div style="--metric-color:#ef4444;">
                <span>ناموفق یا لغو</span>
                <b>@fa(number_format($failedCount))</b>
                <small>نیازمند بررسی</small>
            </div>
        </div>
    </section>

    {{-- دو ستون: فهرست + خلاصه سلامت --}}
    <section class="finance-grid">

        <article class="finance-card is-accent" style="--finance-color:#0ea5e9;">
            <header>
                <div>
                    <span>فهرست پرداخت‌ها</span>
                    <h2>تراکنش‌های ثبت‌شده</h2>
                    <p>لیست کامل تراکنش‌ها با درگاه، مبلغ، وضعیت و کد رهگیری.</p>
                </div>
                <span class="finance-badge">@fa(number_format($totalCount)) تراکنش</span>
            </header>

            <div class="finance-list">
                @forelse($payments as $payment)
                    @php
                        [$statusText, $statusClass, $statusColor] = $statusMap[$payment->status] ?? [$payment->status ?: 'نامشخص', 'is-muted', '#64748b'];
                        $gatewayName = $gatewayLabels[$payment->gateway] ?? $payment->gateway;
                    @endphp
                    <article class="finance-row" style="--row-color:{{ $statusColor }};">
                        <div class="finance-icon">💳</div>
                        <div>
                            <span class="finance-chip">{{ $gatewayName }}</span>
                            <h3>تراکنش شماره @fa($payment->id)</h3>
                            <p>
                                سفارش: #@fa($payment->order->number ?? '—') ·
                                مبلغ: @money($payment->amount) @unit
                            </p>
                            <small>
                                کد رهگیری: <span style="direction:ltr;">{{ $payment->ref_id ?: '—' }}</span>
                                · زمان: @jdatetime($payment->paid_at ?? $payment->created_at)
                            </small>
                        </div>
                        <div class="finance-row-actions">
                            <span class="finance-badge {{ $statusClass }}">{{ $statusText }}</span>
                            @if($payment->order_id ?? false)
                                <a class="finance-badge" href="{{ url('/app/orders/' . $payment->order_id) }}">سفارش</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="finance-empty">
                        <div style="font-size:2.4rem; margin-bottom:0.5rem;">💳</div>
                        <h3>هنوز تراکنشی ثبت نشده است</h3>
                        <p style="margin:0.5rem 0 0; line-height:2;">تراکنش‌های پرداخت از درگاه‌های آنلاین به‌صورت خودکار در این صفحه ثبت می‌شوند.</p>
                    </div>
                @endforelse
            </div>

            @if(method_exists($payments, 'links') && $totalCount > 0)
                <div style="margin-top:1rem;">{{ $payments->links() }}</div>
            @endif
        </article>

        <aside class="finance-card is-accent" style="--finance-color:#10b981;">
            <header>
                <div>
                    <span>خلاصه سلامت پرداخت</span>
                    <h2>وضعیت درگاه‌ها</h2>
                    <p>تحلیل کلی وضعیت پرداخت‌ها در این صفحه.</p>
                </div>
            </header>

            <div class="finance-list">
                <article class="finance-guide-row" style="--row-color:#10b981;">
                    <div class="finance-icon">✓</div>
                    <div>
                        <h3>پرداخت‌های موفق</h3>
                        <p>@fa(number_format($paidCount)) تراکنش موفق در این صفحه</p>
                        <small>مبلغ: @money($paidAmount) @unit</small>
                    </div>
                    <div class="finance-row-actions">
                        <span class="finance-badge is-ok">موفق</span>
                    </div>
                </article>

                <article class="finance-guide-row" style="--row-color:#f59e0b;">
                    <div class="finance-icon">⏳</div>
                    <div>
                        <h3>در انتظار نتیجه</h3>
                        <p>@fa(number_format($pendingCount)) تراکنش هنوز در انتظار نتیجه از درگاه است.</p>
                    </div>
                    <div class="finance-row-actions">
                        <span class="finance-badge is-warn">در انتظار</span>
                    </div>
                </article>

                <article class="finance-guide-row" style="--row-color:#ef4444;">
                    <div class="finance-icon">!</div>
                    <div>
                        <h3>ناموفق یا لغو</h3>
                        <p>@fa(number_format($failedCount)) تراکنش نیازمند بررسی یا پیگیری با مشتری است.</p>
                    </div>
                    <div class="finance-row-actions">
                        <span class="finance-badge is-bad">پیگیری</span>
                    </div>
                </article>

                <a class="finance-guide-row finance-clickable-row" href="{{ url('/app/reports?type=payments_by_gateway') }}" style="--row-color:#0ea5e9;">
                    <div class="finance-icon">📊</div>
                    <div>
                        <h3>گزارش تفکیک درگاه‌ها</h3>
                        <p>مقایسه عملکرد درگاه‌های مختلف و نرخ موفقیت هر یک.</p>
                    </div>
                    <div class="finance-row-actions">
                        <span class="finance-badge">مشاهده</span>
                    </div>
                </a>
            </div>
        </aside>

    </section>

    {{-- جدول کامل تراکنش‌ها --}}
    <section class="finance-card is-accent" style="--finance-color:#8b5cf6;">
        <header>
            <div>
                <span>نمای جدولی</span>
                <h2>خروجی سریع برای بررسی مالی</h2>
                <p>همه تراکنش‌های این صفحه به‌صورت جدولی برای بررسی سریع مالی.</p>
            </div>
            <span class="finance-badge">@fa(number_format($visiblePayments->count())) ردیف</span>
        </header>

        <div class="finance-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ردیف</th>
                        <th>سفارش</th>
                        <th>درگاه</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>کد رهگیری</th>
                        <th>تاریخ و زمان</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        @php
                            [$statusText, $statusClass] = $statusMap[$payment->status] ?? [$payment->status, 'is-muted'];
                            $gatewayName = $gatewayLabels[$payment->gateway] ?? $payment->gateway;
                        @endphp
                        <tr>
                            <td>@fa($payment->id)</td>
                            <td>#@fa($payment->order->number ?? '—')</td>
                            <td>{{ $gatewayName }}</td>
                            <td>@money($payment->amount) @unit</td>
                            <td><span class="finance-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                            <td class="ltr">{{ $payment->ref_id ?: '—' }}</td>
                            <td>@jdatetime($payment->paid_at ?? $payment->created_at)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">تراکنشی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection