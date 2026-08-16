@extends('layouts.app')
@section('title','گزارش پاسخ‌گویی')
@section('heading','گزارش زمان پاسخ‌گویی تیکت‌ها')
@section('subtitle','پایش کیفیت پشتیبانی، مهلت پاسخ و تیکت‌های نیازمند اقدام فوری')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tickets-board.css') }}">

@php
    $priorityFa = [
        'urgent' => 'فوری',
        'high'   => 'زیاد',
        'normal' => 'عادی',
        'low'    => 'کم',
    ];
    $priorityColor = [
        'urgent' => '#ef4444',
        'high'   => '#f59e0b',
        'normal' => '#3b82f6',
        'low'    => '#94a3b8',
    ];
@endphp

<div class="tickets-page">

    {{-- هدر --}}
    <section class="tickets-hero">
        <div>
            <span class="tickets-eyebrow">پایش کیفیت پاسخگویی</span>
            <h2>گزارش مدیریتی مهلت پاسخ و تیکت‌های فوری</h2>
            <p>این صفحه کمک می‌کند تیکت‌های عقب‌افتاده را سریع پیدا کنی، عملکرد پاسخ اولیه را بسنجی و فشار کاری تیم پشتیبانی را کنترل کنی.</p>
            <div class="tickets-hero-actions">
                <a class="btn" href="{{ url('/app/tickets?overdue=1') }}">مشاهده تیکت‌های خارج مهلت</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets') }}">← بازگشت به بورد</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/responses') }}">پاسخ‌های آماده</a>
            </div>
        </div>
        <div class="tickets-score">
            <a href="{{ url('/app/tickets') }}" style="--metric-color:#3b82f6;">
                <span>تیکت‌های باز</span><b>@fa(number_format($open))</b><small>نیازمند پیگیری</small>
            </a>
            <a href="{{ url('/app/tickets?overdue=1') }}" style="--metric-color:#ef4444;">
                <span>خارج مهلت</span><b>@fa(number_format($overdue))</b><small>پاسخ فوری لازم</small>
            </a>
            <div style="--metric-color:#10b981;">
                <span>میانگین اولین پاسخ</span><b>@fa($avgFirstMinutes ? round($avgFirstMinutes) : 0)</b><small>دقیقه</small>
            </div>
            <div style="--metric-color:#8b5cf6;">
                <span>پاسخ‌های ثبت‌شده</span><b>@fa(number_format($answered ?? 0))</b><small>مجموع تعامل</small>
            </div>
        </div>
    </section>

    <section class="tickets-layout">

        {{-- بخش بالا: تحلیل بر اساس اولویت و قوانین مهلت --}}
        <div class="tickets-main">

            <article class="tickets-card is-accent" style="--card-color:#0ea5e9;">
                <header>
                    <div>
                        <span>تحلیل بر اساس اولویت</span>
                        <h2>وضعیت پاسخ‌گویی به تفکیک هر سطح اولویت</h2>
                        <p>سنجش سلامت پاسخگویی در هر سطح - چقدر تیکت داری و چند تا خارج مهلت است.</p>
                    </div>
                </header>

                <div class="tickets-table-wrap">
                    <div class="tickets-table" style="min-width:auto;">
                        <div class="tickets-thead" style="grid-template-columns: 1.3fr 1fr 1fr 1fr 1.2fr;">
                            <div>سطح اولویت</div>
                            <div>کل تیکت‌ها</div>
                            <div>خارج مهلت</div>
                            <div>نرخ نقض</div>
                            <div>وضعیت</div>
                        </div>

                        @foreach($byPriority as $row)
                            @php
                                $priorityKey = $row->priority ?? 'normal';
                                $priorityText = $priorityFa[$priorityKey] ?? $priorityKey;
                                $color = $priorityColor[$priorityKey] ?? '#64748b';
                                $total = (int) ($row->total ?? 0);
                                $lateCount = (int) ($row->overdue ?? 0);
                                $ratePct = $total > 0 ? round(($lateCount / $total) * 100) : 0;
                                $isBad = $lateCount > 0;
                            @endphp
                            <div class="tickets-row">
                                <div class="tickets-cell" style="display:grid; grid-template-columns:1.3fr 1fr 1fr 1fr 1.2fr; padding:0;">
                                    <div class="tickets-cell">
                                        <span class="tickets-priority is-{{ $priorityKey === 'urgent' ? 'urgent' : ($priorityKey === 'high' ? 'high' : ($priorityKey === 'low' ? 'low' : 'normal')) }}">{{ $priorityText }}</span>
                                    </div>
                                    <div class="tickets-cell">
                                        <b style="color:var(--txt); font-weight:1000; font-size:0.95rem;">@fa($total)</b>
                                    </div>
                                    <div class="tickets-cell">
                                        @if($lateCount > 0)
                                            <span class="tickets-pill is-status-pending" style="background:rgba(239, 68, 68, 0.14); color:#b91c1c;">@fa($lateCount)</span>
                                        @else
                                            <span class="tickets-pill is-status-answered">۰</span>
                                        @endif
                                    </div>
                                    <div class="tickets-cell">
                                        <div class="tickets-sla" style="align-items:flex-start;">
                                            <span class="tickets-sla-time {{ $ratePct >= 30 ? 'is-danger' : ($ratePct > 0 ? 'is-warn' : 'is-answered') }}">@fa($ratePct)٪</span>
                                            <span class="tickets-sla-bar" style="--sla-percent:{{ $ratePct }}%; --sla-color:{{ $ratePct >= 30 ? '#ef4444' : ($ratePct > 0 ? '#f59e0b' : '#10b981') }};"><span></span></span>
                                        </div>
                                    </div>
                                    <div class="tickets-cell">
                                        @if($isBad)
                                            <span class="tickets-pill" style="background:rgba(239, 68, 68, 0.14); color:#b91c1c;">نیازمند اقدام</span>
                                        @else
                                            <span class="tickets-pill is-status-answered">مطلوب</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($byPriority->isEmpty())
                            <div class="tickets-group-empty">داده‌ای برای تحلیل اولویت‌ها وجود ندارد.</div>
                        @endif
                    </div>
                </div>
            </article>

            {{-- فهرست تیکت‌های خارج مهلت --}}
            <article class="tickets-card is-accent" style="--card-color:#ef4444;">
                <header>
                    <div>
                        <span>تیکت‌های خارج مهلت</span>
                        <h2>لیست تیکت‌های نیازمند پاسخ فوری</h2>
                    </div>
                    <span class="tickets-pill" style="background:rgba(239, 68, 68, 0.14); color:#b91c1c;">@fa(number_format($lateTickets->count())) مورد</span>
                </header>

                <div class="tickets-table-wrap">
                    <div class="tickets-table" style="min-width:auto;">
                        <div class="tickets-thead" style="grid-template-columns: 2fr 1.3fr 1.2fr 1.1fr 1fr;">
                            <div>موضوع</div>
                            <div>مشتری</div>
                            <div>مسئول</div>
                            <div>مهلت پاسخ</div>
                            <div>عملیات</div>
                        </div>

                        @forelse($lateTickets as $ticket)
                            @php
                                $custName = $ticket->customer?->full_name ?? 'مشتری نامشخص';
                                $assName  = $ticket->assignee?->name ?? 'واگذار نشده';
                            @endphp
                            <div class="tickets-row is-overdue">
                                <div class="tickets-cell" style="display:grid; grid-template-columns:2fr 1.3fr 1.2fr 1.1fr 1fr; padding:0;">
                                    <div class="tickets-cell">
                                        <div class="tickets-cell-subject">
                                            <span class="ticket-unread-dot" style="background:#ef4444; box-shadow:0 0 0 3px rgba(239, 68, 68, 0.2);"></span>
                                            <div>
                                                <b>{{ $ticket->subject ?: 'بدون موضوع' }}</b>
                                                <small><span class="ticket-num">#{{ $ticket->number ?? $ticket->id }}</span>@if($ticket->department)<span class="tickets-pill is-dept">{{ $ticket->department }}</span>@endif</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tickets-cell">
                                        <div class="tickets-cell-customer">
                                            <div class="ticket-avatar">{{ mb_substr($custName, 0, 1) }}</div>
                                            <div>
                                                <b>{{ $custName }}</b>
                                                <small>{{ $ticket->customer?->phone ?? '—' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tickets-cell">
                                        <div class="tickets-cell-assignee">
                                            @if($ticket->assignee)
                                                <div class="assignee-avatar">{{ mb_substr($assName, 0, 1) }}</div>
                                                <b>{{ $assName }}</b>
                                            @else
                                                <div class="assignee-unassigned">👤</div>
                                                <small>واگذار نشده</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tickets-cell">
                                        <span class="tickets-sla-time is-danger">
                                            @if($ticket->sla_due_at)
                                                {{ \Modules\Core\Support\Jalali::datetime($ticket->sla_due_at) }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                    <div class="tickets-cell">
                                        <div class="tickets-cell-actions">
                                            <a href="{{ url('/app/tickets/'.$ticket->id) }}" class="is-primary">پاسخ فوری</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="tickets-group-empty">تیکتی خارج از مهلت پاسخ وجود ندارد. وضعیت پشتیبانی مناسب است. ✓</div>
                        @endforelse
                    </div>
                </div>
            </article>

        </div>

        {{-- کارت‌های زیر: قوانین مهلت + تحلیل عملکرد --}}
        <div class="tickets-below">

            <article class="tickets-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>قوانین پاسخگویی</span>
                        <h2>تعهد زمانی پاسخ اولیه</h2>
                        <p>حداکثر مهلت پاسخ اولیه به تفکیک اولویت.</p>
                    </div>
                </header>

                <div class="ticket-info-grid">
                    <div>
                        <span>اولویت فوری</span>
                        <b style="color:#b91c1c;">۲ ساعت</b>
                    </div>
                    <div>
                        <span>اولویت زیاد</span>
                        <b style="color:#b45309;">۶ ساعت</b>
                    </div>
                    <div>
                        <span>اولویت عادی</span>
                        <b style="color:#1d4ed8;">۲۴ ساعت</b>
                    </div>
                    <div>
                        <span>اولویت کم</span>
                        <b style="color:#475569;">۴۸ ساعت</b>
                    </div>
                </div>
            </article>

            <article class="tickets-card is-accent" style="--card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>پیشنهاد دستیار</span>
                        <h2>اقدامات پیشنهادی برای بهبود کیفیت پاسخ</h2>
                    </div>
                </header>

                <div class="tickets-suggest-list">
                    <button type="button" onclick="askSlaAssistant('لیست تیکت‌های خارج مهلت را بده و به بهترین اپراتور واگذار کن', this)">
                        <b>واگذاری خودکار خارج مهلت‌ها</b>
                        <small>توزیع هوشمند تیکت‌های عقب‌افتاده</small>
                    </button>
                    <button type="button" onclick="askSlaAssistant('عملکرد کارشناسان این ماه را با هم مقایسه کن', this)">
                        <b>مقایسه عملکرد کارشناسان</b>
                        <small>بررسی میانگین پاسخ هر اپراتور</small>
                    </button>
                    <button type="button" onclick="askSlaAssistant('روند مهلت پاسخ در ۳۰ روز اخیر را نمایش بده', this)">
                        <b>روند ماهانه پاسخگویی</b>
                        <small>بررسی بهبود یا افت کیفیت</small>
                    </button>
                    <button type="button" onclick="askSlaAssistant('پیشنهاد کن قوانین مهلت پاسخ چطور بازنگری شود', this)">
                        <b>بازنگری قوانین مهلت پاسخ</b>
                        <small>پیشنهاد سطوح جدید بر اساس داده‌ها</small>
                    </button>
                </div>
            </article>

        </div>
    </section>
</div>

<script>
function askSlaAssistant(text, btn) {
    text = text || 'گزارش مهلت پاسخ را بده';
    if (btn) {
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
        var oh = btn.innerHTML;
        btn.innerHTML = '<b style="color:#b91c1c">در حال باز کردن دستیار...</b><small>لطفاً صبر کنید</small>';
    }
    var url = '{{ url("/app/assistant") }}'
        + '?prompt=' + encodeURIComponent(text)
        + '&context_type=tickets_sla'
        + '&context_title=' + encodeURIComponent('گزارش پاسخگویی تیکت‌ها')
        + '&context_url=' + encodeURIComponent(window.location.href);
    window.location.href = url;
}
</script>
@endsection