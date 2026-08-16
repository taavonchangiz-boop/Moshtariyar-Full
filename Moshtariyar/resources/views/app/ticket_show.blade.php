@extends(request('embed') ? 'layouts.embed' : 'layouts.app')
@section('title','تیکت شماره '.$ticket->id)
@section('heading',$ticket->subject)
@section('subtitle','پرونده کامل تیکت، گفتگوها، تنظیمات و پاسخ سریع')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tickets-board.css') }}">

@php
    $priorityLabel = ['low' => 'کم', 'normal' => 'عادی', 'high' => 'زیاد', 'urgent' => 'فوری'];
    $statusMap = [
        'open'     => ['label' => 'تازه',          'color' => '#3b82f6', 'class' => 'is-status-open'],
        'pending'  => ['label' => 'در حال بررسی',  'color' => '#f59e0b', 'class' => 'is-status-pending'],
        'answered' => ['label' => 'پاسخ داده‌شده', 'color' => '#10b981', 'class' => 'is-status-answered'],
        'closed'   => ['label' => 'بسته‌شده',      'color' => '#64748b', 'class' => 'is-status-closed'],
    ];
    $currentStatus = $statusMap[$ticket->status] ?? ['label' => $ticket->status, 'color' => '#64748b', 'class' => ''];
    $currentPriorityLabel = $priorityLabel[$ticket->priority] ?? 'عادی';

    $customerName  = $ticket->customer->full_name ?? 'مشتری نامشخص';
    $customerPhone = $ticket->customer->phone ?? '';
    $customerEmail = $ticket->customer->email ?? '';
    $customerFirst = mb_substr($customerName, 0, 1);

    $isOverdue = ! $ticket->first_response_at
                 && $ticket->sla_due_at
                 && now()->gt($ticket->sla_due_at)
                 && $ticket->status !== 'closed';

    $palette = [
        ['bg' => 'rgba(139, 92, 246, 0.14)', 'fg' => '#6d28d9'],
        ['bg' => 'rgba(16, 185, 129, 0.14)', 'fg' => '#047857'],
        ['bg' => 'rgba(59, 130, 246, 0.14)', 'fg' => '#1d4ed8'],
        ['bg' => 'rgba(245, 158, 11, 0.14)', 'fg' => '#b45309'],
        ['bg' => 'rgba(239, 68, 68, 0.14)',  'fg' => '#b91c1c'],
    ];
    $customerColor = $palette[abs(crc32($customerName)) % count($palette)];

    // متن اصلی تیکت = اولین ردیف replies از سمت مشتری
    // (چون در این ساختار، خود جدول tickets فیلد body ندارد و متن اصلی در ticket_replies ذخیره می‌شود)
    $allReplies = $ticket->replies ?? collect();
    $firstMessage = $allReplies->first();
    $otherReplies = $allReplies->count() > 1 ? $allReplies->slice(1) : collect();
    $totalMessages = $allReplies->count();
@endphp

<div class="tickets-page">

    {{-- هدر خلاصه تیکت --}}
    <section class="tickets-hero">
        <div>
            <span class="tickets-eyebrow">پرونده کامل تیکت</span>
            <h2>{{ $ticket->subject ?: 'تیکت بدون موضوع' }}</h2>
            <p>مشاهده جزئیات کامل تیکت شماره <strong style="direction:ltr;">#{{ $ticket->number ?? $ticket->id }}</strong> از {{ $customerName }}</p>
            <div class="tickets-hero-actions">
                @if(!request('embed'))
                    <a class="btn btn-ghost" href="{{ url('/app/tickets') }}">← بازگشت به بورد</a>
                @endif
                <a class="btn" href="#ticketReplyForm">ارسال پاسخ</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/responses') }}">پاسخ‌های آماده</a>
            </div>
        </div>
        <div class="tickets-score">
            <div style="--metric-color:{{ $currentStatus['color'] }};">
                <span>وضعیت</span><b>{{ $currentStatus['label'] }}</b><small>مرحله فعلی</small>
            </div>
            <div style="--metric-color:#f59e0b;">
                <span>اولویت</span><b>{{ $currentPriorityLabel }}</b><small>درجه فوریت</small>
            </div>
            <div style="--metric-color:#3b82f6;">
                <span>پیام‌ها</span><b>@fa(number_format($totalMessages))</b><small>پیام ثبت‌شده</small>
            </div>
            <div style="--metric-color:{{ $isOverdue ? '#ef4444' : '#10b981' }};">
                <span>مهلت پاسخ</span>
                <b>{{ $ticket->sla_due_at ? \Modules\Core\Support\Jalali::datetime($ticket->sla_due_at) : 'ثبت نشده' }}</b>
                <small>{{ $isOverdue ? 'خارج از مهلت' : 'در محدوده مجاز' }}</small>
            </div>
        </div>
    </section>

    <section class="tickets-layout">

        <div class="tickets-main">

            {{-- گفتگوها --}}
            <article class="tickets-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>گفتگوها</span>
                        <h2>پیام‌های رد و بدل‌شده در این تیکت</h2>
                    </div>
                    <span class="tickets-pill is-status-answered">@fa(number_format($totalMessages)) پیام</span>
                </header>

                <div class="ticket-thread">
                    @if($totalMessages === 0)
                        <div class="ticket-timeline-empty" style="min-height:5rem;">
                            هنوز هیچ پیامی برای این تیکت ثبت نشده است.
                        </div>
                    @else
                        @foreach($allReplies as $index => $reply)
                            @php
                                $isStaffMsg = ($reply->author ?? '') === 'staff';
                                $isInternalMsg = (bool) ($reply->is_internal ?? false);
                                $isFirstCustomerMsg = ($index === 0 && !$isStaffMsg);

                                if ($isInternalMsg) {
                                    $msgAuthor = $reply->author_name ?? 'کارشناس (یادداشت داخلی)';
                                    $msgClass  = 'is-internal';
                                    $labelText = 'یادداشت داخلی';
                                    $labelCls  = 'is-status-pending';
                                } elseif ($isStaffMsg) {
                                    $msgAuthor = $reply->author_name ?? 'کارشناس پشتیبانی';
                                    $msgClass  = 'is-staff';
                                    $labelText = 'پاسخ کارشناس';
                                    $labelCls  = 'is-status-answered';
                                } else {
                                    $msgAuthor = $reply->author_name ?? $customerName;
                                    $msgClass  = 'is-customer';
                                    $labelText = $isFirstCustomerMsg ? 'پیام اولیه مشتری' : 'پیام مشتری';
                                    $labelCls  = 'is-dept';
                                }
                                $msgFirst = mb_substr($msgAuthor, 0, 1);
                                $msgColor = $palette[abs(crc32($msgAuthor)) % count($palette)];
                                $msgText = trim((string) ($reply->message ?? ''));
                            @endphp

                            <article class="ticket-message {{ $msgClass }}">
                                <div class="ticket-message-avatar" style="background:{{ $msgColor['bg'] }}; color:{{ $msgColor['fg'] }};">
                                    {{ $msgFirst }}
                                </div>
                                <div class="ticket-message-body">
                                    <div class="ticket-message-head">
                                        <div class="ticket-message-author">
                                            <b>{{ $msgAuthor }}</b>
                                            <span class="tickets-pill {{ $labelCls }}">{{ $labelText }}</span>
                                        </div>
                                        <small>@jdatetime($reply->created_at)</small>
                                    </div>

                                    @if($msgText !== '')
                                        <div class="ticket-message-text">{!! nl2br(e($msgText)) !!}</div>
                                    @else
                                        <div class="ticket-message-text" style="color:var(--mut); font-style:italic;">
                                            (متن این پیام خالی است)
                                        </div>
                                    @endif

                                    @if(!empty($reply->attachment))
                                        <div class="ticket-message-attach">
                                            <a href="{{ url('/app/tickets/replies/'.$reply->id.'/attachment') }}">
                                                📎 دریافت پیوست: {{ $reply->attachment_name ?: 'فایل' }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    @endif
                </div>
            </article>

            {{-- فرم پاسخ سریع --}}
            <article class="tickets-card is-accent" style="--card-color:#8b5cf6;" id="ticketReplyForm">
                <header>
                    <div>
                        <span>ارسال پاسخ سریع</span>
                        <h2>پاسخ به مشتری یا ثبت یادداشت داخلی</h2>
                    </div>
                </header>

                <form method="post" action="{{ url('/app/tickets/'.$ticket->id.'/reply') }}" enctype="multipart/form-data" class="ticket-detail-reply-form">
                    @csrf

                    @if(($cannedResponses ?? collect())->count())
                        <div class="ticket-detail-field">
                            <label>پاسخ آماده</label>
                            <select id="cannedResponseSelect" onchange="insertCannedFromSelect()">
                                <option value="">انتخاب از پاسخ‌های آماده...</option>
                                @foreach($cannedResponses as $response)
                                    <option value="{{ e($response->body) }}">{{ $response->department ?: 'عمومی' }} - {{ $response->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="ticket-detail-field">
                        <label>متن پاسخ</label>
                        <textarea id="replyMessageArea" name="message" rows="6" required placeholder="پاسخ خود را واضح، کوتاه و قابل پیگیری بنویسید..."></textarea>
                    </div>

                    <div class="ticket-quick-reply-toolbar">
                        <button type="button" class="canned" onclick="insertCannedDirect('با سلام و احترام،\nدرخواست شما دریافت شد و در حال بررسی است.')">👋 خوش‌آمد</button>
                        <button type="button" class="canned" onclick="insertCannedDirect('با تشکر از پیگیری شما،\nموضوع شما به دپارتمان مربوطه ارجاع شد.')">📤 ارجاع</button>
                        <button type="button" class="canned" onclick="insertCannedDirect('موضوع شما بررسی شد و مشکل برطرف گردید.')">✅ حل شد</button>
                    </div>

                    <label class="ticket-detail-check">
                        <input type="checkbox" name="is_internal" value="1">
                        <span>ثبت به‌عنوان یادداشت داخلی (مشتری این پیام را نمی‌بیند)</span>
                    </label>

                    <div class="ticket-detail-field">
                        <label>پیوست فایل (اختیاری)</label>
                        <input type="file" name="attachment">
                    </div>

                    <button type="submit" class="btn">ارسال پاسخ</button>
                </form>
            </article>
        </div>

        {{-- کارت‌های زیر: اطلاعات مشتری + تنظیمات مدیریت --}}
        <div class="tickets-below">

            <article class="tickets-card is-accent" style="--card-color:#0ea5e9;">
                <header>
                    <div>
                        <span>اطلاعات مشتری</span>
                        <h2>مشخصات و اطلاعات تماس</h2>
                    </div>
                </header>

                <div class="ticket-detail-profile">
                    <div class="ticket-detail-avatar" style="background:{{ $customerColor['bg'] }}; color:{{ $customerColor['fg'] }};">
                        {{ $customerFirst }}
                    </div>
                    <div>
                        <b>{{ $customerName }}</b>
                        @if($customerPhone)
                            <small>📞 <span style="direction:ltr;">{{ $customerPhone }}</span></small>
                        @endif
                        @if($customerEmail)
                            <small>✉ <span style="direction:ltr;">{{ $customerEmail }}</span></small>
                        @endif
                    </div>
                </div>

                <div class="ticket-info-grid">
                    <div><span>شماره تیکت</span><b style="direction:ltr;">#{{ $ticket->number ?? $ticket->id }}</b></div>
                    <div><span>دپارتمان</span><b>{{ $ticket->department ?: '—' }}</b></div>
                    <div><span>ثبت شده</span><b>@jdate($ticket->created_at)</b></div>
                    <div><span>آخرین فعالیت</span><b>@jdate($ticket->last_reply_at ?? $ticket->updated_at)</b></div>
                    <div><span>مسئول پیگیری</span><b>{{ $ticket->assignee->name ?? 'واگذار نشده' }}</b></div>
                    <div>
                        <span>وضعیت پاسخ</span>
                        <b style="color:{{ $isOverdue ? '#b91c1c' : '#047857' }};">
                            {{ $isOverdue ? '⏰ خارج از مهلت' : ($ticket->first_response_at ? '✓ پاسخ داده' : '⏳ در انتظار') }}
                        </b>
                    </div>
                </div>

                @if($isOverdue)
                    <div class="ticket-detail-warning">
                        ⚠ این تیکت از مهلت پاسخ گذشته است. لطفاً در اولین فرصت پاسخ دهید.
                    </div>
                @endif
            </article>

            <article class="tickets-card is-accent" style="--card-color:#f59e0b;">
                <header>
                    <div>
                        <span>مدیریت تیکت</span>
                        <h2>تغییر وضعیت، اولویت و مسئول</h2>
                    </div>
                </header>

                <form method="post" action="{{ url('/app/tickets/'.$ticket->id.'/status') }}" class="ticket-detail-reply-form">
                    @csrf

                    <div class="ticket-detail-field">
                        <label>وضعیت</label>
                        <select name="status">
                            @foreach($statusMap as $key => $st)
                                <option value="{{ $key }}" @selected($ticket->status === $key)>{{ $st['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ticket-detail-field">
                        <label>اولویت</label>
                        <select name="priority">
                            @foreach($priorityLabel as $key => $label)
                                <option value="{{ $key }}" @selected($ticket->priority === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ticket-detail-field">
                        <label>دپارتمان</label>
                        <select name="department">
                            @foreach(['پشتیبانی', 'فروش', 'مالی', 'فنی'] as $dept)
                                <option value="{{ $dept }}" @selected($ticket->department === $dept)>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ticket-detail-field">
                        <label>مسئول پیگیری</label>
                        <select name="assigned_to">
                            <option value="">واگذار نشده</option>
                            @foreach(($staff ?? collect()) as $user)
                                <option value="{{ $user->id }}" @selected($ticket->assigned_to === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn">ذخیره تغییرات</button>
                </form>
            </article>

        </div>
    </section>

</div>

<script>
    function insertCannedFromSelect() {
        var sel = document.getElementById('cannedResponseSelect');
        var ta  = document.getElementById('replyMessageArea');
        if (!sel || !ta || !sel.value) return;
        ta.value = ta.value ? (ta.value + '\n\n' + sel.value) : sel.value;
        ta.focus();
        sel.value = '';
    }
    function insertCannedDirect(text) {
        var ta = document.getElementById('replyMessageArea');
        if (!ta) return;
        ta.value = ta.value ? (ta.value + '\n\n' + text) : text;
        ta.focus();
    }
</script>
@endsection
