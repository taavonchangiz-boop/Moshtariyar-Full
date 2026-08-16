@php
    $statusMap = [
        'open' => ['باز', 'is-warn', '#f59e0b'],
        'pending' => ['در انتظار بررسی', 'is-muted', '#64748b'],
        'answered' => ['پاسخ داده شده', 'is-ok', '#10b981'],
        'closed' => ['بسته شده', 'is-muted', '#64748b'],
    ];
    $priorityMap = [
        'low' => ['کم', 'is-muted'],
        'normal' => ['عادی', 'is-ok'],
        'high' => ['زیاد', 'is-warn'],
        'urgent' => ['فوری', 'is-bad'],
    ];
    [$statusLabel, $statusClass, $statusColor] = $statusMap[$ticket->status] ?? [$ticket->status, 'is-muted', '#64748b'];
    [$priorityLabel, $priorityClass] = $priorityMap[$ticket->priority] ?? [$ticket->priority, 'is-muted'];
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/club-portal-support.css') }}">
</head>
<body class="support-ticket-standalone support-ticket-embed">
<div class="support-ticket-shell">
    <section class="support-ticket-head" style="--support-row-color: {{ $statusColor }};">
        <div>
            <span class="support-eyebrow">گفتگوی پشتیبانی</span>
            <h1>{{ $ticket->subject }}</h1>
            <p>دپارتمان: {{ $ticket->department }} · ایجاد: @jdatetime($ticket->created_at)</p>
        </div>
        <aside class="support-ticket-status-box">
            <span class="support-badge {{ $statusClass }}">{{ $statusLabel }}</span>
            <span class="support-badge {{ $priorityClass }}">اولویت: {{ $priorityLabel }}</span>
        </aside>
    </section>

    <section class="support-card">
        <header>
            <div>
                <span>پیام‌ها</span>
                <h2>تاریخچه گفتگو</h2>
            </div>
        </header>
        <div class="support-reply-list">
            @forelse($ticket->replies as $reply)
                <article class="support-reply-card {{ $reply->author === 'staff' ? 'is-staff' : 'is-customer' }}" style="--support-row-color: {{ $reply->author === 'staff' ? '#10b981' : '#0ea5e9' }};">
                    <div class="support-reply-avatar">{{ $reply->author === 'staff' ? 'پ' : 'ش' }}</div>
                    <div>
                        <div class="support-reply-top">
                            <h3>{{ $reply->author === 'customer' ? 'شما' : ($reply->author_name ?? 'پشتیبانی') }}</h3>
                            <small>@jdatetime($reply->created_at)</small>
                        </div>
                        <div class="support-reply-message">{{ $reply->message }}</div>
                        @if($reply->attachment)
                            <a class="support-attachment-link" href="{{ route('club.tickets.attachment', $reply) }}">📎 {{ $reply->attachment_name ?: 'دریافت فایل پیوست' }}</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="support-empty">هنوز پیامی برای این درخواست ثبت نشده است.</div>
            @endforelse
        </div>
    </section>

    @if($ticket->status !== 'closed')
        <section class="support-card is-accent" style="--support-card-color:#10b981;">
            <header>
                <div>
                    <span>پاسخ جدید</span>
                    <h2>ارسال پاسخ به پشتیبانی</h2>
                </div>
            </header>
            <form method="post" action="{{ route('club.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="support-form-grid">
                @csrf
                <div>
                    <label>متن پاسخ</label>
                    <textarea name="message" required rows="4" placeholder="پاسخ خود را بنویسید..."></textarea>
                </div>
                <div>
                    <label>پیوست، اختیاری</label>
                    <div class="support-file-picker">
                        <input id="ticketModalFile" type="file" name="attachment">
                        <label for="ticketModalFile">📎 انتخاب فایل</label>
                        <span id="ticketModalFileName">فایلی انتخاب نشده است</span>
                    </div>
                </div>
                <button class="btn">ارسال پاسخ</button>
            </form>
        </section>
    @else
        <section class="support-card"><div class="support-empty">این درخواست بسته شده است. در صورت نیاز، درخواست جدید ثبت کنید.</div></section>
    @endif
</div>
<script>
const ticketModalFile = document.getElementById('ticketModalFile');
const ticketModalFileName = document.getElementById('ticketModalFileName');
if (ticketModalFile) {
    ticketModalFile.addEventListener('change', function () {
        ticketModalFileName.textContent = ticketModalFile.files && ticketModalFile.files[0] ? ticketModalFile.files[0].name : 'فایلی انتخاب نشده است';
    });
}
</script>
</body>
</html>