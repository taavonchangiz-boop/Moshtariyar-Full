@extends('layouts.app')
@section('title','مرکز وظایف و پیگیری')
@section('heading','مرکز وظایف و پیگیری هوشمند')
@section('subtitle','بورد روزانه مدیر برای پیگیری مشتری، تیکت، سفارش، پیش‌فاکتور و کارهای ساخته‌شده توسط دستیار')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tasks-command-board.css') }}">

@php
    $activityTypes = \Modules\Core\Entities\Activity::TYPES;
    $scopeCards = collect([
        ['key' => 'overdue', 'label' => 'عقب‌افتاده', 'color' => '#ef4444', 'hint' => 'باید فوری رسیدگی شود'],
        ['key' => 'today', 'label' => 'امروز', 'color' => '#f59e0b', 'hint' => 'کارهای مهم امروز'],
        ['key' => 'waiting', 'label' => 'در انتظار زمان‌بندی', 'color' => '#8b5cf6', 'hint' => 'وظیفه‌های بدون سررسید'],
        ['key' => 'upcoming', 'label' => 'برنامه‌ریزی‌شده', 'color' => '#10b981', 'hint' => 'پیگیری‌های آینده'],
        ['key' => 'done', 'label' => 'انجام‌شده', 'color' => '#64748b', 'hint' => 'انجام‌شده‌های اخیر'],
    ]);
    $toneClass = ['overdue' => 'is-danger', 'today' => 'is-warn', 'waiting' => 'is-purple', 'upcoming' => 'is-ok', 'done' => 'is-muted'];
@endphp

<div class="tasks-page">
    <section class="tasks-hero">
        <div class="tasks-hero-main">
            <span class="tasks-eyebrow">اتاق پیگیری مدیر</span>
            <h2>همه مسئولیت‌ها، تماس‌ها، یادآوری‌ها و پیگیری‌ها در یک بورد قابل اقدام</h2>
            <p>این مرکز کمک می‌کند کارهای روزانه، عقب‌افتاده، برنامه‌ریزی‌شده و انجام‌شده را مثل یک بورد حرفه‌ای مدیریت کنید. دستیار هم می‌تواند برای مشتری، تیکت، سفارش یا پیش‌فاکتور وظیفه بسازد و گزارش بدهد.</p>
            <div class="tasks-hero-actions">
                <a class="btn" href="#newTaskPanel">ثبت وظیفه جدید</a>
                <a class="btn btn-ghost" href="{{ url('/app/assistant') }}">سپردن کار به دستیار</a>
                <a class="btn btn-ghost" href="{{ url('/app/reminders?scope=overdue') }}">عقب‌افتاده‌ها</a>
            </div>
        </div>
        <div class="tasks-hero-metrics">
            <a class="tasks-metric-card" href="{{ url('/app/reminders') }}" style="--metric-color:#2563eb;">
                <span>وظایف باز</span>
                <strong>@fa($summary['total'] ?? 0)</strong>
                <small>همه پیگیری‌های فعال</small>
            </a>
            <a class="tasks-metric-card" href="{{ url('/app/reminders?scope=overdue') }}" style="--metric-color:#ef4444;">
                <span>عقب‌افتاده</span>
                <strong>@fa($summary['overdue'] ?? 0)</strong>
                <small>نیازمند اقدام فوری</small>
            </a>
            <a class="tasks-metric-card" href="{{ url('/app/reminders?scope=today') }}" style="--metric-color:#f59e0b;">
                <span>امروز</span>
                <strong>@fa($summary['today'] ?? 0)</strong>
                <small>برنامه کاری امروز</small>
            </a>
            <a class="tasks-metric-card" href="{{ url('/app/reminders?mine=1') }}" style="--metric-color:#10b981;">
                <span>کارهای من</span>
                <strong>@fa($summary['mine'] ?? 0)</strong>
                <small>مسئولیت‌های مدیر فعلی</small>
            </a>
        </div>
    </section>

    <section class="tasks-toolbar">
        <form method="get" class="tasks-filter-form">
            <div class="tasks-search-field">
                <span>جستجو</span>
                <input name="q" value="{{ request('q') }}" placeholder="جستجو در توضیح، نوع کار یا مخاطب">
            </div>
            <div class="tasks-filter-field">
                <label>وضعیت زمانی</label>
                <select name="scope">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="overdue" @selected(request('scope') === 'overdue')>عقب‌افتاده</option>
                    <option value="today" @selected(request('scope') === 'today')>امروز</option>
                    <option value="waiting" @selected(request('scope') === 'waiting')>در انتظار زمان‌بندی</option>
                    <option value="upcoming" @selected(request('scope') === 'upcoming')>برنامه‌ریزی‌شده</option>
                    <option value="done" @selected(request('scope') === 'done')>انجام‌شده</option>
                </select>
            </div>
            <div class="tasks-filter-field">
                <label>نوع فعالیت</label>
                <select name="type">
                    <option value="">همه فعالیت‌ها</option>
                    @foreach($activityTypes as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tasks-filter-field">
                <label>مخاطب</label>
                <select name="subject_type">
                    <option value="">همه مخاطب‌ها</option>
                    <option value="customer" @selected(request('subject_type') === 'customer')>مشتری</option>
                    <option value="lead" @selected(request('subject_type') === 'lead')>سرنخ</option>
                </select>
            </div>
            <div class="tasks-check-field">
                <label>نمای سریع</label>
                <div class="tasks-checks">
                    <label><input type="checkbox" name="mine" value="1" @checked(request('mine'))><span>فقط کارهای من</span></label>
                    <label><input type="checkbox" name="assistant" value="1" @checked(request('assistant'))><span>ساخته‌شده توسط دستیار</span></label>
                </div>
            </div>
            <div class="tasks-toolbar-actions">
                <button class="btn">اعمال فیلتر</button>
                @if(request()->hasAny(['q','scope','type','subject_type','mine','assistant']))
                    <a class="btn btn-ghost" href="{{ url('/app/reminders') }}">حذف فیلترها</a>
                @endif
            </div>
        </form>
    </section>

    <details class="tasks-create-panel" id="newTaskPanel">
        <summary>
            <span>ثبت وظیفه یا پیگیری جدید</span>
            <small>برای مشتری یا سرنخ، وظیفه زمان‌دار یا بدون زمان بسازید</small>
        </summary>
        <form method="post" action="{{ url('/app/activities') }}" class="tasks-create-form">
            @csrf
            <div>
                <label>نوع مخاطب</label>
                <select name="subject_type" id="subjectType" onchange="toggleSubjectList()">
                    <option value="customer">مشتری</option>
                    <option value="lead">سرنخ</option>
                </select>
            </div>
            <div class="tasks-subject-field">
                <label>مخاطب</label>
                <select name="subject_id" id="customerList">
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->full_name }} @if($customer->phone) - {{ $customer->phone }} @endif</option>
                    @endforeach
                </select>
                <select name="subject_id" id="leadList" disabled style="display:none">
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}">{{ $lead->name ?: 'سرنخ شماره '.$lead->id }} @if($lead->phone) - {{ $lead->phone }} @endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع فعالیت</label>
                <select name="type">
                    @foreach($activityTypes as $key => $label)
                        <option value="{{ $key }}" @selected($key === 'task')>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>تاریخ سررسید، اختیاری</label>
                <input name="due" class="jdate" placeholder="اگر خالی بماند، در انتظار زمان‌بندی می‌شود">
            </div>
            <div class="tasks-body-field">
                <label>توضیح وظیفه</label>
                <textarea name="body" rows="3" required placeholder="مثلاً تماس برای پیگیری خرید مجدد، ارسال پیش‌فاکتور یا بررسی تیکت"></textarea>
            </div>
            <button class="btn tasks-create-button">ثبت وظیفه</button>
        </form>
    </details>

    <section class="tasks-insight-strip">
        <div><span class="tasks-dot is-danger"></span><b>@fa($grouped['overdue']->count())</b><small>عقب‌افتاده در نمای فعلی</small></div>
        <div><span class="tasks-dot is-warn"></span><b>@fa($grouped['today']->count())</b><small>مربوط به امروز</small></div>
        <div><span class="tasks-dot is-purple"></span><b>@fa($grouped['waiting']->count())</b><small>بدون زمان‌بندی</small></div>
        <div><span class="tasks-dot is-ok"></span><b>@fa($grouped['upcoming']->count())</b><small>برنامه‌ریزی‌شده</small></div>
    </section>

    <section class="tasks-board-shell">
        <div class="tasks-board">
            @foreach($scopeCards as $scopeCard)
                @php
                    $columnItems = $grouped[$scopeCard['key']] ?? collect();
                    $columnCount = $columnItems->count();
                    $ratio = $items->count() ? round(($columnCount / max(1, $items->count())) * 100) : 0;
                @endphp
                <section class="tasks-column" style="--task-color: {{ $scopeCard['color'] }}; --column-ratio: {{ $ratio }}%;">
                    <header class="tasks-column-header">
                        <div><span class="tasks-stage-mark"></span><h3>{{ $scopeCard['label'] }}</h3></div>
                        <span class="tasks-stage-count">@fa($columnCount)</span>
                    </header>
                    <div class="tasks-column-meta"><span>{{ $scopeCard['hint'] }}</span></div>
                    <div class="tasks-column-progress"><span></span></div>

                    <div class="tasks-drop-zone">
                        @forelse($columnItems as $activity)
                            <details class="task-card {{ $toneClass[$scopeCard['key']] ?? 'is-blue' }}" style="--task-color: {{ $scopeCard['color'] }};">
                                <summary class="task-card-summary">
                                    <span class="task-summary-arrow">⌄</span>
                                    <span class="task-summary-main">
                                        <b>{{ $activityTypes[$activity->type] ?? $activity->type }}</b>
                                        <small>{{ $activity->subject_title }}</small>
                                    </span>
                                    <span class="task-summary-date">{{ $activity->due_at ? \Modules\Core\Support\Jalali::date($activity->due_at) : 'بدون زمان' }}</span>
                                </summary>

                                <div class="task-card-body">
                                    <div class="task-card-top">
                                        <h4>{{ $activity->body ?: 'بدون توضیح' }}</h4>
                                        <span>{{ $activity->subject_type === 'lead' ? 'سرنخ' : 'مشتری' }}</span>
                                    </div>

                                    <div class="task-person-box">
                                        <span>مخاطب</span>
                                        <b>{{ $activity->subject_title }}</b>
                                        @if($activity->subject_phone)
                                            <small class="ltr">{{ $activity->subject_phone }}</small>
                                        @else
                                            <small>شماره ثبت نشده</small>
                                        @endif
                                    </div>

                                    <div class="task-mini-grid">
                                        <div><span>نوع کار</span><b>{{ $activityTypes[$activity->type] ?? $activity->type }}</b></div>
                                        <div><span>سررسید</span><b>{{ $activity->due_at ? \Modules\Core\Support\Jalali::datetime($activity->due_at) : 'زمان‌بندی نشده' }}</b></div>
                                        <div><span>وضعیت</span><b>{{ $activity->done ? 'انجام‌شده' : 'باز' }}</b></div>
                                        <div><span>مسئول</span><b>{{ $activity->user_id ? 'مدیر' : 'دستیار/سامانه' }}</b></div>
                                    </div>

                                    <div class="task-card-actions">
                                        @if($activity->subject_url)
                                            <a class="task-action-link" href="{{ $activity->subject_url }}">مشاهده مخاطب</a>
                                        @endif
                                        <form method="post" action="{{ url('/app/activities/' . $activity->id . '/toggle') }}">
                                            @csrf
                                            <button class="task-done-button">{{ $activity->done ? 'بازگردانی' : 'انجام شد' }}</button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="tasks-empty-column">در این بخش وظیفه‌ای وجود ندارد.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>
</div>

<script>
function toggleSubjectList() {
    const type = document.getElementById('subjectType').value;
    const customerList = document.getElementById('customerList');
    const leadList = document.getElementById('leadList');

    if (type === 'customer') {
        customerList.disabled = false;
        customerList.style.display = 'block';
        leadList.disabled = true;
        leadList.style.display = 'none';
        return;
    }

    customerList.disabled = true;
    customerList.style.display = 'none';
    leadList.disabled = false;
    leadList.style.display = 'block';
}
</script>
@endsection