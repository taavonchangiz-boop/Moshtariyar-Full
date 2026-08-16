@php
    $statusMap = ['open'=>['باز','is-warn','#3b82f6'],'pending'=>['در حال بررسی','is-muted','#f59e0b'],'answered'=>['پاسخ داده‌شده','is-ok','#10b981'],'closed'=>['بسته‌شده','is-muted','#64748b']];
    $priorityMap = ['low'=>['کم','is-muted','#64748b'],'normal'=>['عادی','is-ok','#10b981'],'high'=>['زیاد','is-warn','#f59e0b'],'urgent'=>['فوری','is-bad','#ef4444']];
    [$statusLabel, $statusClass, $statusColor] = $statusMap[$ticket->status] ?? [$ticket->status, 'is-muted', '#64748b'];
    [$priorityLabel, $priorityClass, $priorityColor] = $priorityMap[$ticket->priority] ?? [$ticket->priority, 'is-muted', '#64748b'];
    $isOverdue = ! $ticket->first_response_at && $ticket->sla_due_at && now()->gt($ticket->sla_due_at) && $ticket->status !== 'closed';
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tickets-board.css') }}">
</head>
<body class="ticket-modal-body">
<div class="ticket-detail-page ticket-detail-modal tickets-ui">
    <section class="ticket-detail-hero is-modal">
        <div class="ticket-detail-hero-text">
            <span class="ticket-detail-eyebrow">پرونده سریع تیکت</span>
            <h2>{{ $ticket->subject }}</h2>
            <p>مشتری: {{ $ticket->customer->full_name ?? 'مشتری نامشخص' }} · دپارتمان: {{ $ticket->department ?: 'پشتیبانی' }} · ایجاد: @jdatetime($ticket->created_at)</p>
        </div>
        <div class="ticket-detail-score">
            <div><span>وضعیت</span><b>{{ $statusLabel }}</b><small>مرحله فعلی</small></div>
            <div><span>اولویت</span><b>{{ $priorityLabel }}</b><small>درجه فوریت</small></div>
        </div>
    </section>

    <section class="ticket-detail-card is-accent" style="--ticket-detail-color:{{ $statusColor }};">
        <header>
            <div><span>تنظیمات</span><h2>مدیریت سریع تیکت</h2></div>
            <span class="ticket-detail-badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </header>
        <form method="post" action="{{ url('/app/tickets/'.$ticket->id.'/status') }}" class="ticket-detail-settings-form">
            @csrf
            <div><label>وضعیت</label><select name="status"><option value="open" @selected($ticket->status==='open')>باز</option><option value="pending" @selected($ticket->status==='pending')>در حال بررسی</option><option value="answered" @selected($ticket->status==='answered')>پاسخ داده‌شده</option><option value="closed" @selected($ticket->status==='closed')>بسته‌شده</option></select></div>
            <div><label>اولویت</label><select name="priority">@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($ticket->priority===$key)>{{ $label }}</option>@endforeach</select></div>
            <div><label>دپارتمان</label><select name="department">@foreach(['پشتیبانی','فروش','مالی','فنی'] as $department)<option value="{{ $department }}" @selected($ticket->department===$department)>{{ $department }}</option>@endforeach</select></div>
            <div><label>مسئول</label><select name="assigned_to"><option value="">تعیین نشده</option>@foreach(($staff ?? collect()) as $user)<option value="{{ $user->id }}" @selected($ticket->assigned_to===$user->id)>{{ $user->name }}</option>@endforeach</select></div>
            <button class="btn btn-ghost">ذخیره تنظیمات</button>
        </form>
    </section>

    <section class="ticket-modal-grid">
        <article class="ticket-detail-card is-accent" style="--ticket-detail-color:#10b981;">
            <header>
                <div><span>گفتگوها</span><h2>پیام‌ها و یادداشت‌ها</h2></div>
                <span class="ticket-detail-badge">@fa(number_format($ticket->replies->count())) پیام</span>
            </header>
            <div class="ticket-thread">
                @forelse($ticket->replies as $reply)
                    <article class="ticket-message {{ $reply->is_internal ? 'is-internal' : ($reply->author === 'staff' ? 'is-staff' : 'is-customer') }}">
                        <div class="ticket-message-avatar">{{ $reply->is_internal ? 'ی' : ($reply->author === 'staff' ? 'پ' : 'م') }}</div>
                        <div class="ticket-message-body">
                            <div class="ticket-message-top"><b>{{ $reply->is_internal ? 'یادداشت داخلی' : ($reply->author === 'staff' ? ($reply->author_name ?? 'کارشناس') : ($reply->author_name ?? 'مشتری')) }}</b><span>@jdatetime($reply->created_at)</span></div>
                            <div class="ticket-message-text">{{ $reply->message }}</div>
                            @if($reply->attachment)<div class="ticket-attachment-row"><a class="ticket-detail-badge is-muted" href="{{ url('/app/tickets/replies/'.$reply->id.'/attachment') }}">دریافت پیوست: {{ $reply->attachment_name ?: 'فایل' }}</a></div>@endif
                        </div>
                    </article>
                @empty
                    <div class="ticket-detail-empty">هنوز پیامی ثبت نشده است.</div>
                @endforelse
            </div>
        </article>

        <aside class="ticket-detail-card is-accent" style="--ticket-detail-color:#8b5cf6;">
            <header><div><span>پاسخ جدید</span><h2>ارسال پاسخ یا یادداشت داخلی</h2></div></header>
            <form method="post" action="{{ url('/app/tickets/'.$ticket->id.'/reply') }}" enctype="multipart/form-data" class="ticket-reply-form">
                @csrf
                @if(($cannedResponses ?? collect())->count())
                    <div><label>پاسخ آماده</label><select id="cannedResponse" onchange="insertCannedResponse()"><option value="">انتخاب پاسخ آماده...</option>@foreach($cannedResponses as $response)<option value="{{ e($response->body) }}">{{ $response->department ?: 'عمومی' }} - {{ $response->title }}</option>@endforeach</select></div>
                @endif
                <div><label>متن پاسخ</label><textarea id="replyMessage" name="message" rows="6" required placeholder="پاسخ خود را بنویسید..."></textarea></div>
                <label class="ticket-internal-check"><input type="checkbox" name="is_internal" value="1"><span>یادداشت داخلی، مشتری نمی‌بیند</span></label>
                <div><label>پیوست فایل</label><input type="file" name="attachment" data-hint="پیوست پاسخ تیکت؛ تصویر، PDF، Word، Excel، ZIP یا متن؛ حداکثر ۵ مگابایت."></div>
                <button class="btn">ارسال پاسخ</button>
            </form>
        </aside>
    </section>
</div>
<script>
function insertCannedResponse(){var s=document.getElementById('cannedResponse'),t=document.getElementById('replyMessage');if(!s||!t||!s.value)return;t.value=t.value?(t.value+'\n\n'+s.value):s.value;t.focus();}
</script>
</body>
</html>