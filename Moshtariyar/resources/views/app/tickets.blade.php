@extends('layouts.app')

@section('title', 'تیکت‌ها')
@section('heading', '🎫 مرکز پشتیبانی و مدیریت تیکت‌ها')
@section('subtitle', 'نمای کانبان و لیستی — مدیریت حرفه‌ای با اولویت، مسئول پیگیری، مهلت پاسخ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/tickets-kanban.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tickets-board.css') }}">
@endpush

@section('content')

@php
    $canManageTickets = auth()->user()?->hasPermission('tickets.manage');

    $priorityLabel = [
        'low'    => 'کم',
        'normal' => 'عادی',
        'high'   => 'زیاد',
        'urgent' => 'فوری',
    ];

    $priorityClass = [
        'low'    => 'is-low',
        'normal' => 'is-normal',
        'high'   => 'is-high',
        'urgent' => 'is-urgent',
    ];

    $priorityColor = [
        'urgent' => '#ef4444',
        'high'   => '#f97316',
        'normal' => '#3b82f6',
        'low'    => '#94a3b8',
    ];

    $statusTitle = function ($status) use ($statuses) {
        $found = $statuses->firstWhere('key', $status);
        return $found['label'] ?? ($status ?: 'نامشخص');
    };

    $statusColor = function ($status) use ($statuses) {
        $found = $statuses->firstWhere('key', $status);
        return $found['color'] ?? '#64748b';
    };

    $statusClass = function ($status) {
        return match ($status) {
            'open'     => 'is-status-open',
            'pending'  => 'is-status-pending',
            'answered' => 'is-status-answered',
            'closed'   => 'is-status-closed',
            default    => '',
        };
    };

    $statusHint = [
        'open'     => 'تیکت‌های تازه که هنوز پاسخ نگرفته‌اند',
        'pending'  => 'تیکت‌های در حال بررسی توسط تیم پشتیبانی',
        'answered' => 'تیکت‌هایی که پاسخ داده‌اند و منتظر بازخورد مشتری هستند',
        'closed'   => 'تیکت‌های بسته‌شده و پایان‌یافته',
    ];

    $openGroups = ['open', 'pending'];
    if (request('status')) {
        $openGroups = [request('status')];
    }

    $avatarPalette = [
        ['bg' => 'rgba(139, 92, 246, 0.14)', 'fg' => '#6d28d9'],
        ['bg' => 'rgba(16, 185, 129, 0.14)',  'fg' => '#047857'],
        ['bg' => 'rgba(59, 130, 246, 0.14)',  'fg' => '#1d4ed8'],
        ['bg' => 'rgba(245, 158, 11, 0.14)',  'fg' => '#b45309'],
        ['bg' => 'rgba(239, 68, 68, 0.14)',   'fg' => '#b91c1c'],
        ['bg' => 'rgba(14, 165, 233, 0.14)',  'fg' => '#0284c7'],
    ];

    $pickAvatar = function ($name) use ($avatarPalette) {
        $idx = crc32((string) $name) % count($avatarPalette);
        return $avatarPalette[abs($idx)];
    };

    $slaHealth = function ($ticket) {
        if ($ticket->first_response_at) {
            return ['percent' => 100, 'color' => '#10b981', 'class' => 'is-answered', 'text' => 'پاسخ داده'];
        }
        if (! $ticket->sla_due_at) {
            return ['percent' => 100, 'color' => '#94a3b8', 'class' => '', 'text' => 'بدون مهلت'];
        }

        $created      = $ticket->created_at ?: now();
        $due          = $ticket->sla_due_at;
        $now          = now();
        $totalMinutes = max(1, $created->diffInMinutes($due));
        $passed       = $created->diffInMinutes($now);
        $percent      = min(100, max(0, round(($passed / $totalMinutes) * 100)));
        $isOverdue    = $now->gt($due);

        if ($isOverdue) {
            $lateMinutes = $now->diffInMinutes($due);
            $text = $lateMinutes < 60
                ? '⏰ ' . fa_num($lateMinutes) . ' دق تأخیر'
                : '⏰ ' . fa_num(round($lateMinutes / 60, 1)) . ' سا تأخیر';
            return ['percent' => 100, 'color' => '#ef4444', 'class' => 'is-danger', 'text' => $text];
        }

        if ($percent >= 75) {
            $remaining = $now->diffInMinutes($due);
            $text = $remaining < 60
                ? fa_num($remaining) . ' دق مانده'
                : fa_num(round($remaining / 60, 1)) . ' سا مانده';
            return ['percent' => $percent, 'color' => '#f59e0b', 'class' => 'is-warn', 'text' => $text];
        }

        $remaining = $now->diffInMinutes($due);
        $text = $remaining < 60
            ? fa_num($remaining) . ' دق مانده'
            : fa_num(round($remaining / 60, 1)) . ' سا مانده';
        return ['percent' => $percent, 'color' => '#10b981', 'class' => '', 'text' => $text];
    };

    if (! function_exists('fa_num')) {
        function fa_num($n) {
            return str_replace(
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
                (string) $n
            );
        }
    }
@endphp

<div class="tickets-page">

    {{-- نوار اقدام گروهی --}}
    <div class="tickets-bulk-bar" id="ticketsBulkBar">
        <b><span id="ticketsBulkCount">۰</span> تیکت انتخاب‌شده</b>
        <select id="ticketsBulkAction">
            <option value="">اقدام گروهی...</option>
            <option value="status:pending">تغییر به «در حال بررسی»</option>
            <option value="status:answered">تغییر به «پاسخ داده‌شده»</option>
            <option value="status:closed">بستن تیکت‌ها</option>
            <option value="priority:urgent">اولویت فوری</option>
            <option value="priority:high">اولویت بالا</option>
        </select>
        <button type="button" onclick="applyBulkAction()">اعمال</button>
        <button type="button" class="clear-btn" onclick="clearTicketSelection()" title="پاک کردن انتخاب">✕</button>
    </div>

    {{-- هدر --}}
    <section class="tickets-hero">
        <div>
            <span class="tickets-eyebrow">اتاق فرمان پشتیبانی</span>
            <h2>تیکت‌ها را سریع، تمیز و بدون آشفتگی مدیریت کن</h2>
            <p>بورد پشتیبانی حرفه‌ای با اولویت، مسئول پیگیری، تایمر مهلت پاسخ، پاسخ سریع و اقدام گروهی.</p>
            <div class="tickets-hero-actions">
                <a class="btn" href="{{ url('/app/tickets/create') }}">ثبت تیکت جدید</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/responses') }}">پاسخ‌های آماده</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/sla') }}">گزارش مهلت پاسخ</a>
            </div>
        </div>
        <div class="tickets-score">
            <a href="{{ url('/app/tickets') }}" style="--metric-color:#3b82f6;">
                <span>کل تیکت‌ها</span><b>@fa($summary['total'] ?? 0)</b><small>در نمای فعلی</small>
            </a>
            <a href="{{ url('/app/tickets?unread=1') }}" style="--metric-color:#f59e0b;">
                <span>خوانده‌نشده</span><b>@fa($summary['unread'] ?? 0)</b><small>نیازمند توجه</small>
            </a>
            <a href="{{ url('/app/tickets?overdue=1') }}" style="--metric-color:#ef4444;">
                <span>خارج مهلت</span><b>@fa($summary['overdue'] ?? 0)</b><small>پاسخ فوری</small>
            </a>
            <a href="{{ url('/app/tickets?priority=urgent') }}" style="--metric-color:#dc2626;">
                <span>اولویت فوری</span><b>@fa($summary['urgent'] ?? 0)</b><small>پیگیری ویژه</small>
            </a>
        </div>
    </section>

    {{-- نوار فیلترها --}}
    <form method="get" class="tickets-toolbar">
        <div>
            <label>جستجو</label>
            <input name="q" value="{{ request('q') }}" placeholder="موضوع، مشتری، موبایل یا مسئول">
        </div>
        <div>
            <label>وضعیت</label>
            <select name="status">
                <option value="">همه وضعیت‌ها</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status['key'] }}" @selected((string) request('status') === (string) $status['key'])>
                        {{ $status['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>دپارتمان</label>
            <select name="department">
                <option value="">همه دپارتمان‌ها</option>
                @foreach ($departments as $department)
                    <option value="{{ $department }}" @selected(request('department') === $department)>
                        {{ $department }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>اولویت</label>
            <select name="priority">
                <option value="">همه اولویت‌ها</option>
                @foreach ($priorityLabel as $key => $label)
                    <option value="{{ $key }}" @selected(request('priority') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="tickets-toolbar-actions">
            <button class="btn">اعمال فیلتر</button>
            @if (request()->hasAny(['q', 'status', 'department', 'priority', 'unread', 'overdue', 'mine']))
                <a class="btn btn-ghost" href="{{ url('/app/tickets') }}">پاک کردن</a>
            @endif
        </div>
    </form>

    {{-- فیلترهای سریع --}}
    <div class="tickets-quick-filters">
        <a href="{{ url('/app/tickets') }}"
           class="{{ ! request()->hasAny(['unread', 'overdue', 'mine', 'priority']) ? 'is-active' : '' }}">
            🗂 همه <span class="filter-count">@fa($summary['total'] ?? 0)</span>
        </a>
        <a href="{{ url('/app/tickets?mine=1') }}" class="{{ request('mine') ? 'is-active' : '' }}">
            👤 تیکت‌های من
        </a>
        <a href="{{ url('/app/tickets?unread=1') }}" class="{{ request('unread') ? 'is-active' : '' }}">
            🔔 خوانده‌نشده <span class="filter-count">@fa($summary['unread'] ?? 0)</span>
        </a>
        <a href="{{ url('/app/tickets?overdue=1') }}" class="{{ request('overdue') ? 'is-active' : '' }}">
            ⏰ خارج مهلت <span class="filter-count">@fa($summary['overdue'] ?? 0)</span>
        </a>
        <a href="{{ url('/app/tickets?priority=urgent') }}"
           class="{{ request('priority') === 'urgent' ? 'is-active' : '' }}">
            🚨 فوری <span class="filter-count">@fa($summary['urgent'] ?? 0)</span>
        </a>
        <a href="{{ url('/app/tickets?priority=high') }}"
           class="{{ request('priority') === 'high' ? 'is-active' : '' }}">
            ⚡ اولویت زیاد
        </a>
    </div>

    {{-- نوار خلاصه --}}
    <section class="tickets-insight">
        @foreach ($statuses as $status)
            @php $countInStatus = $totals[$status['key']]['count'] ?? 0; @endphp
            <div>
                <span class="tickets-dot is-{{ $status['key'] === 'open' ? 'open' : ($status['key'] === 'pending' ? 'pending' : ($status['key'] === 'answered' ? 'answered' : 'urgent')) }}"></span>
                <b>@fa($countInStatus)</b>
                <small>{{ $status['label'] }}</small>
            </div>
        @endforeach
    </section>

    {{-- ═══════ تغییر نما: کانبان / لیستی ═══════ --}}
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
        <button type="button" class="tk-view-btn active" data-view="list" onclick="switchTicketView('list')">
            📃 نمای لیستی
        </button>
        <button type="button" class="tk-view-btn" data-view="kanban" onclick="switchTicketView('kanban')">
            📋 نمای کانبان
        </button>
        <span style="margin-right:auto; font-size:0.75rem; color:var(--mut); font-weight:600;">
            @fa($summary['total'] ?? 0) تیکت
        </span>
    </div>

    {{-- ═══════════════ نمای کانبان ═══════════════ --}}
    <div id="ticketKanbanView" style="display:none;">
        <div class="tk-kanban-board" id="tkKanbanBoard">
            @foreach ($statuses as $status)
                @php
                    $groupTickets = $tickets->get($status['key'], collect());
                    $groupTotals  = $totals[$status['key']] ?? ['count' => 0];
                @endphp
                <div class="tk-column"
                     data-status="{{ $status['key'] }}"
                     style="--col-color:{{ $status['color'] }};">

                    <div class="tk-col-header">
                        <span class="tk-col-dot"></span>
                        <span class="tk-col-title">{{ $status['label'] }}</span>
                        <span class="tk-col-count">{{ fa_num($groupTotals['count']) }}</span>
                    </div>

                    <div class="tk-col-cards"
                         ondragover="event.preventDefault(); this.classList.add('tk-drag-over')"
                         ondragleave="this.classList.remove('tk-drag-over')"
                         ondrop="handleTicketDrop(event, '{{ $status['key'] }}')">

                        @forelse ($groupTickets as $ticket)
                            @php
                                $customerName  = $ticket->customer->full_name ?? 'مشتری نامشخص';
                                $customerFirst = mb_substr($customerName, 0, 1);
                                $customerPal   = $pickAvatar($customerName);
                                $assigneeName  = $ticket->assignee->name ?? null;
                                $assigneeFirst = $assigneeName ? mb_substr($assigneeName, 0, 1) : '';
                                $assigneePal   = $assigneeName ? $pickAvatar($assigneeName) : null;
                                $sla           = $slaHealth($ticket);
                                $isUnread      = (bool) $ticket->staff_unread;
                                $ticketNumber  = $ticket->number ?? $ticket->id;
                                $repliesCount  = $ticket->replies_count ?? 0;
                            @endphp

                            <div class="tk-card {{ $isUnread ? 'tk-unread' : '' }}"
                                 draggable="true"
                                 data-id="{{ $ticket->id }}"
                                 ondragstart="handleDragStart(event, {{ $ticket->id }})"
                                 ondragend="handleDragEnd(event)"
                                 onclick="openTicketDrawer('{{ url('/app/tickets/' . $ticket->id) }}', 'تیکت شماره {{ $ticketNumber }}')">

                                <div class="tk-card-top">
                                    <div class="tk-card-subject">
                                        @if ($isUnread)
                                            <span class="tk-unread-dot"></span>
                                        @endif
                                        <b>{{ $ticket->subject ?: 'بدون موضوع' }}</b>
                                    </div>
                                    <span class="tk-card-num">#{{ $ticketNumber }}</span>
                                </div>

                                <div class="tk-card-customer">
                                    <div class="tk-avatar" style="background:{{ $customerPal['bg'] }}; color:{{ $customerPal['fg'] }};">
                                        {{ $customerFirst }}
                                    </div>
                                    <span>{{ $customerName }}</span>
                                </div>

                                <div class="tk-card-badges">
                                    <span class="tk-badge tk-priority"
                                          style="background:{{ $priorityColor[$ticket->priority] ?? '#94a3b8' }};">
                                        {{ $priorityLabel[$ticket->priority] ?? 'عادی' }}
                                    </span>
                                    @if ($ticket->department)
                                        <span class="tk-badge tk-dept">{{ $ticket->department }}</span>
                                    @endif
                                    @if ($repliesCount > 0)
                                        <span class="tk-badge tk-replies">💬 {{ fa_num($repliesCount) }}</span>
                                    @endif
                                </div>

                                <div class="tk-card-footer">
                                    <div class="tk-sla {{ $sla['class'] }}">
                                        <span class="tk-sla-text">{{ $sla['text'] }}</span>
                                        <span class="tk-sla-bar" style="--sla-p:{{ $sla['percent'] }}%; --sla-c:{{ $sla['color'] }};">
                                            <span></span>
                                        </span>
                                    </div>
                                    @if ($assigneeName)
                                        <div class="tk-assignee"
                                             style="background:{{ $assigneePal['bg'] }}; color:{{ $assigneePal['fg'] }};"
                                             title="{{ $assigneeName }}">
                                            {{ $assigneeFirst }}
                                        </div>
                                    @else
                                        <div class="tk-assignee tk-unassigned" title="واگذار نشده">👤</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="tk-col-empty">
                                <span>📭</span>
                                <p>تیکتی در این وضعیت نیست</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ نمای لیستی (اصلی) ═══════════════ --}}
    <div id="ticketListView">
        <section class="tickets-layout">
            <div class="tickets-main">
                <article class="tickets-card is-accent" style="--card-color:#ef4444;">
                    <header>
                        <div>
                            <span>بورد تیکت‌ها</span>
                            <h2>مدیریت گروهی تیکت‌ها بر اساس وضعیت پاسخگویی</h2>
                            <p>روی هر تیکت کلیک کن تا پیام کامل، اطلاعات مشتری، تایمر مهلت پاسخ، پاسخ سریع و تاریخچه فعالیت باز شود.</p>
                        </div>
                        <a class="tickets-link" href="{{ url('/app/tickets/create') }}">تیکت جدید</a>
                    </header>

                    <div class="tickets-board">
                        @foreach ($statuses as $status)
                            @php
                                $groupTickets = $tickets->get($status['key'], collect());
                                $groupTotals  = $totals[$status['key']] ?? ['count' => 0, 'unread' => 0, 'overdue' => 0];
                                $ratio        = $maxColumnCount > 0 ? round(($groupTotals['count'] / max(1, $maxColumnCount)) * 100) : 0;
                                $isOpen       = in_array($status['key'], $openGroups, true) && $groupTotals['count'] > 0;
                                $groupClass   = $isOpen ? 'is-open' : 'is-collapsed';
                            @endphp

                            <div class="tickets-group {{ $groupClass }}"
                                 style="--group-color:{{ $status['color'] }}; --group-ratio:{{ $ratio }}%;">
                                <button type="button" class="tickets-group-title" onclick="toggleTicketsGroup(this)">
                                    <span class="tg-caret">◀</span>
                                    <span class="tg-mark"></span>
                                    <span class="tg-title-block">
                                        <b>{{ $status['label'] }}</b>
                                        <small>{{ $statusHint[$status['key']] ?? '' }}</small>
                                    </span>
                                    <span class="tg-count">@fa($groupTotals['count'])</span>
                                    <span class="tg-progress"></span>
                                    <span class="tg-ratio">@fa($ratio)٪</span>
                                </button>

                                <div class="tickets-group-summary">
                                    <span>تعداد: <b>@fa($groupTotals['count'])</b></span>
                                    @if (($groupTotals['unread'] ?? 0) > 0)
                                        <span class="is-unread">🔔 خوانده‌نشده: <b>@fa($groupTotals['unread'])</b></span>
                                    @endif
                                    @if (($groupTotals['overdue'] ?? 0) > 0)
                                        <span class="is-overdue">⏰ خارج مهلت: <b>@fa($groupTotals['overdue'])</b></span>
                                    @endif
                                </div>

                                <div class="tickets-table-wrap">
                                    <div class="tickets-table">
                                        <div class="tickets-thead">
                                            <div></div>
                                            <div>موضوع</div>
                                            <div>مشتری</div>
                                            <div>اولویت</div>
                                            <div>مسئول پیگیری</div>
                                            <div>وضعیت</div>
                                            <div>مهلت پاسخ</div>
                                            <div>عملیات</div>
                                        </div>

                                        @forelse ($groupTickets as $ticket)
                                            @php
                                                $customerName  = $ticket->customer->full_name ?? 'مشتری نامشخص';
                                                $customerFirst = mb_substr($customerName, 0, 1);
                                                $customerPal   = $pickAvatar($customerName);
                                                $assigneeName  = $ticket->assignee->name ?? null;
                                                $assigneeFirst = $assigneeName ? mb_substr($assigneeName, 0, 1) : '';
                                                $assigneePal   = $assigneeName ? $pickAvatar($assigneeName) : null;
                                                $sla           = $slaHealth($ticket);
                                                $isUnread      = (bool) $ticket->staff_unread;
                                                $isOverdueRow  = ! $ticket->first_response_at
                                                    && $ticket->sla_due_at
                                                    && now()->gt($ticket->sla_due_at)
                                                    && $ticket->status !== 'closed';
                                                $rowClasses    = 'tickets-row'
                                                    . ($isUnread ? ' is-unread' : '')
                                                    . ($isOverdueRow ? ' is-overdue' : '');
                                                $ticketNumber  = $ticket->number ?? $ticket->id;
                                                $repliesCount  = $ticket->replies_count ?? 0;
                                            @endphp

                                            <details class="{{ $rowClasses }}">
                                                <summary>
                                                    <div class="tickets-cell tickets-check">
                                                        <input type="checkbox" class="ticket-select"
                                                               value="{{ $ticket->id }}"
                                                               onclick="event.stopPropagation(); onTicketSelect();">
                                                    </div>
                                                    <div class="tickets-cell">
                                                        <div class="tickets-cell-subject">
                                                            @if ($isUnread)
                                                                <span class="ticket-unread-dot" title="خوانده‌نشده"></span>
                                                            @endif
                                                            <div>
                                                                <b>{{ $ticket->subject ?: 'تیکت بدون موضوع' }}</b>
                                                                <small>
                                                                    <span class="ticket-num">#{{ $ticketNumber }}</span>
                                                                    @if ($ticket->department)
                                                                        <span class="tickets-pill is-dept">{{ $ticket->department }}</span>
                                                                    @endif
                                                                    @if ($repliesCount > 0)
                                                                        <span class="ticket-replies">@fa($repliesCount)</span>
                                                                    @endif
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="tickets-cell">
                                                        <div class="tickets-cell-customer">
                                                            <div class="ticket-avatar"
                                                                 style="background:{{ $customerPal['bg'] }}; color:{{ $customerPal['fg'] }};">
                                                                {{ $customerFirst }}
                                                            </div>
                                                            <div>
                                                                <b>{{ $customerName }}</b>
                                                                <small>{{ $ticket->customer->phone ?? '—' }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="tickets-cell">
                                                        <span class="tickets-priority {{ $priorityClass[$ticket->priority] ?? 'is-normal' }}">
                                                            {{ $priorityLabel[$ticket->priority] ?? 'عادی' }}
                                                        </span>
                                                    </div>
                                                    <div class="tickets-cell">
                                                        <div class="tickets-cell-assignee">
                                                            @if ($assigneeName)
                                                                <div class="assignee-avatar"
                                                                     style="background:{{ $assigneePal['bg'] }}; color:{{ $assigneePal['fg'] }};">
                                                                    {{ $assigneeFirst }}
                                                                </div>
                                                                <b>{{ $assigneeName }}</b>
                                                            @else
                                                                <div class="assignee-unassigned" title="واگذار نشده">👤</div>
                                                                <small>واگذار نشده</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="tickets-cell">
                                                        <span class="tickets-pill {{ $statusClass($ticket->status) }}">
                                                            {{ $statusTitle($ticket->status) }}
                                                        </span>
                                                    </div>
                                                    <div class="tickets-cell">
                                                        <div class="tickets-sla">
                                                            <span class="tickets-sla-time {{ $sla['class'] }}">{{ $sla['text'] }}</span>
                                                            <span class="tickets-sla-bar"
                                                                  style="--sla-percent:{{ $sla['percent'] }}%; --sla-color:{{ $sla['color'] }};">
                                                                <span></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="tickets-cell">
                                                        <div class="tickets-cell-actions">
                                                            <button type="button"
                                                                    onclick="event.stopPropagation(); openTicketDrawer('{{ url('/app/tickets/' . $ticket->id) }}', 'تیکت شماره {{ $ticketNumber }}');">
                                                                مشاهده
                                                            </button>
                                                            @if ($ticket->status !== 'closed')
                                                                <button type="button" class="is-primary"
                                                                        onclick="event.stopPropagation(); openTicketDrawer('{{ url('/app/tickets/' . $ticket->id) }}#reply', 'پاسخ به تیکت {{ $ticketNumber }}');">
                                                                    پاسخ
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </summary>

                                                {{-- پنل جزئیات --}}
                                                <div class="tickets-detail-panel">
                                                    <div class="tickets-detail-block">
                                                        <h4>پیام تیکت <span>#{{ $ticketNumber }}</span></h4>
                                                        @php
                                                            $firstTicketMsg  = $ticket->replies->first();
                                                            $ticketFirstText = trim((string) ($firstTicketMsg->message ?? ''));
                                                        @endphp
                                                        <div class="ticket-message-box">
                                                            @if ($ticketFirstText !== '')
                                                                {!! nl2br(e($ticketFirstText)) !!}
                                                            @else
                                                                <span style="color:var(--mut); font-style:italic;">متن پیام اولیه ثبت نشده است.</span>
                                                            @endif
                                                        </div>

                                                        @if ($ticket->replies->count() > 1)
                                                            <div style="margin-top:0.85rem; padding-top:0.85rem; border-top:1px dashed var(--line);">
                                                                <h4 style="margin:0 0 0.6rem;">
                                                                    پاسخ‌های ثبت‌شده
                                                                    <span>@fa($ticket->replies->count() - 1) پاسخ</span>
                                                                </h4>
                                                                <div style="display:grid; gap:0.5rem; max-height:14rem; overflow-y:auto;">
                                                                    @foreach ($ticket->replies->slice(1) as $r)
                                                                        @php
                                                                            $isStaff    = ($r->author ?? '') === 'staff';
                                                                            $isInternal = (bool) ($r->is_internal ?? false);
                                                                            $rAuthor    = $isInternal
                                                                                ? 'یادداشت داخلی'
                                                                                : ($isStaff ? ($r->author_name ?? 'کارشناس') : ($r->author_name ?? 'مشتری'));
                                                                            $rText   = trim((string) ($r->message ?? ''));
                                                                            $rBg     = $isInternal
                                                                                ? 'rgba(245,158,11,0.08)'
                                                                                : ($isStaff ? 'rgba(16,185,129,0.06)' : 'rgba(59,130,246,0.06)');
                                                                            $rBorder = $isInternal
                                                                                ? 'rgba(245,158,11,0.28)'
                                                                                : ($isStaff ? 'rgba(16,185,129,0.24)' : 'rgba(59,130,246,0.22)');
                                                                        @endphp
                                                                        <div style="padding:0.6rem 0.7rem; border:1px solid {{ $rBorder }}; border-radius:0.55rem; background:{{ $rBg }};">
                                                                            <div style="display:flex; justify-content:space-between; gap:0.5rem; align-items:center; margin-bottom:0.3rem;">
                                                                                <b style="color:var(--txt); font-weight:1000; font-size:0.78rem;">{{ $rAuthor }}</b>
                                                                                <small style="color:var(--mut); font-size:0.7rem;">@jdatetime($r->created_at)</small>
                                                                            </div>
                                                                            <div style="color:var(--txt); font-size:0.82rem; line-height:1.95; font-weight:500; white-space:pre-wrap; word-break:break-word;">
                                                                                {!! nl2br(e($rText)) !!}
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if ($canManageTickets && $ticket->status !== 'closed')
                                                            <form method="post"
                                                                  action="{{ url('/app/tickets/' . $ticket->id . '/reply') }}"
                                                                  class="ticket-quick-reply">
                                                                @csrf
                                                                <label style="margin-bottom:0.2rem;">پاسخ سریع</label>
                                                                <textarea name="body" required placeholder="پاسخ خود را بنویسید..."></textarea>

                                                                <div class="ticket-quick-reply-toolbar">
                                                                    <button type="button" class="canned"
                                                                            onclick="insertCanned(this, 'با سلام و احترام،&#10;درخواست شما دریافت شد و در حال بررسی است.')">
                                                                        👋 خوش‌آمد
                                                                    </button>
                                                                    <button type="button" class="canned"
                                                                            onclick="insertCanned(this, 'با تشکر از پیگیری شما،&#10;موضوع شما به دپارتمان مربوطه ارجاع شد و در سریع‌ترین زمان پاسخ می‌گیرید.')">
                                                                        📤 ارجاع
                                                                    </button>
                                                                    <button type="button" class="canned"
                                                                            onclick="insertCanned(this, 'موضوع شما بررسی شد و مشکل برطرف گردید. در صورت نیاز به راهنمایی بیشتر با ما در ارتباط باشید.')">
                                                                        ✅ حل شد
                                                                    </button>
                                                                </div>

                                                                <div style="display:flex; gap:0.5rem; margin-top:0.4rem;">
                                                                    <button type="submit" class="btn">ارسال پاسخ</button>
                                                                    <label style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.78rem; color:var(--mut); font-weight:900;">
                                                                        <input type="checkbox" name="change_status" value="1" checked style="width:auto; min-height:auto;">
                                                                        تغییر وضعیت به «پاسخ داده‌شده»
                                                                    </label>
                                                                </div>
                                                            </form>
                                                        @endif
                                                    </div>

                                                    <div class="tickets-detail-block">
                                                        <h4>اطلاعات تیکت <span>{{ $statusTitle($ticket->status) }}</span></h4>
                                                        <div class="ticket-info-grid">
                                                            <div><span>مشتری</span><b>{{ $customerName }}</b></div>
                                                            <div><span>تماس</span><b style="direction:ltr;">{{ $ticket->customer->phone ?? '—' }}</b></div>
                                                            <div><span>اولویت</span><b>{{ $priorityLabel[$ticket->priority] ?? 'عادی' }}</b></div>
                                                            <div><span>دپارتمان</span><b>{{ $ticket->department ?: '—' }}</b></div>
                                                            <div><span>مسئول</span><b>{{ $assigneeName ?: 'واگذار نشده' }}</b></div>
                                                            <div><span>پاسخ‌ها</span><b>@fa($repliesCount)</b></div>
                                                            <div><span>ثبت شده</span><b>@jdate($ticket->created_at)</b></div>
                                                            <div><span>مهلت پاسخ</span><b>{{ $ticket->sla_due_at ? \Modules\Core\Support\Jalali::datetime($ticket->sla_due_at) : '—' }}</b></div>
                                                        </div>

                                                        @if ($canManageTickets)
                                                            <h4 style="margin-top:0.9rem;">مدیریت</h4>
                                                            <form method="post"
                                                                  action="{{ url('/app/tickets/' . $ticket->id . '/status') }}"
                                                                  class="ticket-manage-form">
                                                                @csrf
                                                                <select name="status">
                                                                    @foreach ($statuses as $st)
                                                                        <option value="{{ $st['key'] }}" @selected($st['key'] === $ticket->status)>
                                                                            {{ $st['label'] }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <select name="assigned_to">
                                                                    <option value="">بدون مسئول</option>
                                                                    @foreach ($staff as $person)
                                                                        <option value="{{ $person->id }}" @selected($ticket->assigned_to == $person->id)>
                                                                            {{ $person->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <button type="submit">ذخیره تغییرات</button>
                                                            </form>
                                                        @endif

                                                        <h4 style="margin-top:0.9rem;">تاریخچه</h4>
                                                        <div class="ticket-timeline">
                                                            <div class="ticket-timeline-item">
                                                                <div class="tt-icon">📝</div>
                                                                <div><b>تیکت ثبت شد</b><small>توسط {{ $customerName }}</small></div>
                                                                <span class="tt-time">@jdate($ticket->created_at)</span>
                                                            </div>
                                                            @if ($ticket->first_response_at)
                                                                <div class="ticket-timeline-item">
                                                                    <div class="tt-icon is-reply">💬</div>
                                                                    <div><b>اولین پاسخ ثبت شد</b><small>{{ $assigneeName ?: 'مسئول پشتیبانی' }}</small></div>
                                                                    <span class="tt-time">@jdate($ticket->first_response_at)</span>
                                                                </div>
                                                            @endif
                                                            @if ($repliesCount > 0)
                                                                <div class="ticket-timeline-item">
                                                                    <div class="tt-icon is-reply">💬</div>
                                                                    <div><b>@fa($repliesCount) پاسخ ثبت‌شده</b><small>مجموع پیام‌های رد و بدل شده</small></div>
                                                                    <span class="tt-time">فعال</span>
                                                                </div>
                                                            @endif
                                                            @if ($ticket->status === 'answered')
                                                                <div class="ticket-timeline-item">
                                                                    <div class="tt-icon is-status">✓</div>
                                                                    <div><b>وضعیت: پاسخ داده‌شده</b><small>منتظر بازخورد مشتری</small></div>
                                                                    <span class="tt-time">@jdate($ticket->updated_at)</span>
                                                                </div>
                                                            @endif
                                                            @if ($ticket->status === 'closed')
                                                                <div class="ticket-timeline-item">
                                                                    <div class="tt-icon is-close">🔒</div>
                                                                    <div><b>تیکت بسته شد</b><small>فرآیند پیگیری پایان یافت</small></div>
                                                                    <span class="tt-time">@jdate($ticket->updated_at)</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </details>
                                        @empty
                                            <div class="tickets-group-empty">
                                                تیکتی در وضعیت «{{ $status['label'] }}» وجود ندارد.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <a class="tickets-group-add" href="{{ url('/app/tickets/create') }}">+ ثبت تیکت جدید</a>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>

            {{-- بخش زیر --}}
            <div class="tickets-below">
                <article class="tickets-card is-accent" style="--card-color:#8b5cf6;">
                    <header>
                        <div><span>پیشنهاد دستیار</span><h2>اقدامات هوشمند مرکز پشتیبانی</h2></div>
                    </header>
                    <div class="tickets-suggest-list">
                        <button type="button" onclick="askTicketsAssistant('لیست تیکت‌های خارج مهلت را بده و برای هر کدام پاسخ پیشنهادی بنویس', this)">
                            <b>پیگیری تیکت‌های خارج مهلت</b><small>شناسایی مهلت‌های گذشته و پاسخ سریع</small>
                        </button>
                        <button type="button" onclick="askTicketsAssistant('برای تیکت‌های تازه پاسخ خودکار خوشامد بفرست تا مشتری منتظر نماند', this)">
                            <b>پاسخ خوشامد خودکار</b><small>اطمینان‌بخشی به مشتریان تازه</small>
                        </button>
                        <button type="button" onclick="askTicketsAssistant('گزارش عملکرد تیم پشتیبانی این هفته را بده', this)">
                            <b>گزارش عملکرد تیم</b><small>میانگین پاسخ، رضایت‌سنجی، تیکت‌های فعال</small>
                        </button>
                        <button type="button" onclick="askTicketsAssistant('برای تیکت‌های تکراری یا مشابه، پاسخ آماده پیشنهاد بده', this)">
                            <b>شناسایی سؤالات تکراری</b><small>ساخت پاسخ آماده از تیکت‌های مشابه</small>
                        </button>
                        <button type="button" onclick="askTicketsAssistant('تیکت‌های فوری بدون مسئول را به بهترین اپراتور واگذار کن', this)">
                            <b>واگذاری هوشمند فوری</b><small>توزیع تیکت‌های اورژانسی بین تیم</small>
                        </button>
                    </div>
                </article>

                <article class="tickets-card is-accent" style="--card-color:#10b981;">
                    <header>
                        <div><span>ثبت تیکت سریع</span><h2>ایجاد تیکت جدید برای مشتری</h2></div>
                    </header>
                    <form method="post" action="{{ url('/app/tickets') }}" class="tickets-quick-form">
                        @csrf
                        <div>
                            <label>جستجوی مشتری</label>
                            <input name="customer_search" placeholder="نام، شماره یا ایمیل مشتری" autocomplete="off">
                        </div>
                        <div class="tickets-form-grid">
                            <div>
                                <label>دپارتمان</label>
                                <select name="department">
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept }}">{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>اولویت</label>
                                <select name="priority">
                                    <option value="normal">عادی</option>
                                    <option value="high">زیاد</option>
                                    <option value="urgent">فوری</option>
                                    <option value="low">کم</option>
                                </select>
                            </div>
                        </div>
                        <div class="tickets-form-grid">
                            <div>
                                <label>مسئول پیگیری</label>
                                <select name="assigned_to">
                                    <option value="">واگذاری خودکار</option>
                                    @foreach ($staff as $person)
                                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>مهلت پاسخ (شمسی)</label>
                                <input name="sla_due_at" class="jdate" placeholder="۱۴۰۵/۰۵/۰۱" autocomplete="off">
                            </div>
                        </div>
                        <div>
                            <label>موضوع تیکت</label>
                            <input name="subject" required placeholder="موضوع کوتاه و گویا">
                        </div>
                        <div class="tickets-form-wide">
                            <label>متن تیکت</label>
                            <textarea name="body" rows="4" required placeholder="شرح کامل درخواست مشتری..."></textarea>
                            <small>پس از ثبت، در صف تیکت‌های تازه قرار می‌گیرد و بر اساس اولویت پیگیری می‌شود.</small>
                        </div>
                        <button class="btn">ثبت تیکت</button>
                    </form>
                </article>
            </div>
        </section>
    </div>
</div>

{{-- Toast --}}
<div class="tk-toast hidden" id="tkToast"></div>

<script>
function switchTicketView(view) {
    document.querySelectorAll('.tk-view-btn').forEach(function (b) { b.classList.remove('active'); });
    document.querySelector('.tk-view-btn[data-view="' + view + '"]').classList.add('active');
    document.getElementById('ticketKanbanView').style.display = (view === 'kanban') ? '' : 'none';
    document.getElementById('ticketListView').style.display = (view === 'list') ? '' : 'none';
}

function handleDragStart(event, id) {
    event.target.classList.add('tk-dragging');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', id);
}

function handleDragEnd(event) {
    event.target.classList.remove('tk-dragging');
    document.querySelectorAll('.tk-col-cards').forEach(function (c) { c.classList.remove('tk-drag-over'); });
}

function handleTicketDrop(event, newStatus) {
    event.preventDefault();
    var container = event.currentTarget;
    container.classList.remove('tk-drag-over');

    var ticketId = event.dataTransfer.getData('text/plain');
    if (! ticketId) return;

    var card = document.querySelector('.tk-card[data-id="' + ticketId + '"]');
    if (! card) return;

    var oldCol = card.closest('.tk-column');
    if (! oldCol || oldCol.dataset.status === newStatus) return;

    card.remove();

    var empty = container.querySelector('.tk-col-empty');
    if (empty) empty.remove();
    container.appendChild(card);

    var oldCards = oldCol.querySelector('.tk-col-cards');
    if (oldCards && oldCards.querySelectorAll('.tk-card').length === 0) {
        oldCards.innerHTML = '<div class="tk-col-empty"><span>📭</span><p>تیکتی در این وضعیت نیست</p></div>';
    }

    document.querySelectorAll('.tk-column').forEach(function (col) {
        var cnt = col.querySelectorAll('.tk-card').length;
        var el  = col.querySelector('.tk-col-count');
        if (el) el.textContent = fa(cnt);
    });

    fetch('/app/tickets/' + ticketId + '/status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.ok) showToast('✅ وضعیت تیکت تغییر کرد', 'ok');
        else showToast('❌ خطا در تغییر وضعیت', 'bad');
    })
    .catch(function () { showToast('❌ خطا در ارتباط با سرور', 'bad'); });
}

function showToast(msg, type) {
    var t = document.getElementById('tkToast');
    t.textContent = msg;
    t.className = 'tk-toast tk-toast-' + (type || 'ok');
    t.classList.remove('hidden');
    setTimeout(function () { t.classList.add('hidden'); }, 3000);
}

function fa(n) {
    return String(n).replace(/[0-9]/g, function (d) {
        return ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'][d];
    });
}

function toggleTicketsGroup(btn) {
    var group = btn.closest('.tickets-group');
    if (! group) return;
    group.classList.toggle('is-collapsed');
    group.classList.toggle('is-open');
}

function openTicketDrawer(url, title) {
    if (typeof openDrawer === 'function') {
        openDrawer(url + (url.includes('?') ? '&' : '?') + 'embed=1', title || 'جزئیات تیکت');
        return;
    }
    openTicketPopup(url, title);
}

function openTicketPopup(url, title) {
    var popup = document.getElementById('ticketPopup');
    if (! popup) {
        popup = document.createElement('div');
        popup.id = 'ticketPopup';
        popup.className = 'ticket-popup';
        popup.innerHTML =
            '<div class="ticket-popup-backdrop" onclick="closeTicketPopup()"></div>' +
            '<div class="ticket-popup-panel">' +
                '<div class="ticket-popup-head">' +
                    '<b id="ticketPopupTitle">جزئیات تیکت</b>' +
                    '<button type="button" onclick="closeTicketPopup()" title="بستن">✕</button>' +
                '</div>' +
                '<iframe id="ticketPopupFrame" src=""></iframe>' +
            '</div>';
        document.body.appendChild(popup);
    }
    document.getElementById('ticketPopupTitle').textContent = title || 'جزئیات تیکت';
    document.getElementById('ticketPopupFrame').src = url + (url.includes('?') ? '&' : '?') + 'embed=1';
    document.body.classList.add('is-ticket-popup-open');
}

function closeTicketPopup() {
    document.body.classList.remove('is-ticket-popup-open');
    var fr = document.getElementById('ticketPopupFrame');
    if (fr) fr.src = '';
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeTicketPopup();
});

function onTicketSelect() {
    var selected = document.querySelectorAll('.ticket-select:checked');
    var bar   = document.getElementById('ticketsBulkBar');
    var count = document.getElementById('ticketsBulkCount');
    if (selected.length > 0) {
        bar.classList.add('is-visible');
        count.textContent = fa(selected.length);
    } else {
        bar.classList.remove('is-visible');
    }
}

function clearTicketSelection() {
    document.querySelectorAll('.ticket-select:checked').forEach(function (cb) { cb.checked = false; });
    onTicketSelect();
}

function applyBulkAction() {
    var action = document.getElementById('ticketsBulkAction').value;
    var ids = Array.from(document.querySelectorAll('.ticket-select:checked')).map(function (cb) { return cb.value; });
    if (! action || ids.length === 0) return;
    var parts = action.split(':');
    askTicketsAssistant('برای تیکت‌های شماره ' + ids.join('، ') + ' اقدام گروهی «' + parts[0] + ': ' + parts[1] + '» را انجام بده');
}

function insertCanned(btn, text) {
    var form = btn.closest('form');
    if (! form) return;
    var ta = form.querySelector('textarea[name="body"]');
    if (ta) {
        ta.value = (ta.value ? ta.value + '\n\n' : '') + text;
        ta.focus();
    }
}

function askTicketsAssistant(text, btn) {
    text = text || 'گزارش تیکت‌ها را بده';
    if (btn) {
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
        btn.innerHTML = '<b style="color:#b91c1c">در حال باز کردن دستیار...</b>';
    }
    window.location.href = '{{ url("/app/assistant") }}?prompt=' + encodeURIComponent(text)
        + '&context_type=tickets_center'
        + '&context_title=' + encodeURIComponent('مرکز پشتیبانی و تیکت‌ها')
        + '&context_url=' + encodeURIComponent(window.location.href);
}
</script>
@endsection