@extends('layouts.app')
@section('title','مرکز اتوماسیون هوشمند')
@section('heading','مرکز اتوماسیون هوشمند')
@section('subtitle','ساخت و مدیریت قانون‌های خودکار برای فروش، پشتیبانی، باشگاه مشتریان و بازگشت مشتری')

@section('content')
<link rel="stylesheet" href="{{ asset('css/workflows-board.css') }}">

@php
    $canManageAutomation = auth()->user()?->hasPermission('automation.manage');
    $groupList = collect([
        ['key' => 'instant',   'label' => 'فعال فوری',      'color' => '#00c875', 'hint' => 'بدون تأخیر اجرا می‌شود'],
        ['key' => 'delayed',   'label' => 'فعال زمان‌دار',  'color' => '#f59e0b', 'hint' => 'با تأخیر مشخص اجرا می‌شود'],
        ['key' => 'messenger', 'label' => 'پیام‌رسان‌ها',   'color' => '#2563eb', 'hint' => 'ارسال در کانال‌های پیام‌رسان'],
        ['key' => 'inactive',  'label' => 'خاموش',          'color' => '#94a3b8', 'hint' => 'فعلاً اجرا نمی‌شود'],
    ]);
    // گروه‌هایی که با توجه به فیلتر پیش‌فرض باز باشند
    $openGroups = ['instant', 'delayed'];
    if (request('status') === 'active')    { $openGroups = ['instant','delayed','messenger']; }
    if (request('status') === 'inactive')  { $openGroups = ['inactive']; }
    if (request('action') === 'messenger') { $openGroups = ['messenger']; }
@endphp

<div class="workflow-page">

    {{-- هدر اصلی --}}
    <section class="workflow-hero">
        <div>
            <span class="workflow-eyebrow">موتور کار خودکار</span>
            <h2>اگر اتفاقی در سامانه افتاد، اقدام مناسب را بدون فراموشی انجام بده</h2>
            <p>قانون‌های اتوماسیون پس از سفارش، پرداخت، ثبت تیکت، ثبت مشتری، رها شدن سبد خرید یا دوره‌های بدون خرید، پیام مناسب را ارسال می‌کنند و فشار کار تکراری مدیر را کم می‌کنند.</p>
            <div class="workflow-hero-actions">
                @if($canManageAutomation)<a class="btn" href="#workflowCreatePanel">ساخت قانون جدید</a>@endif
                <button class="btn btn-ghost" type="button" onclick="askWorkflowAssistant()">ساخت با دستیار</button>
                <a class="btn btn-ghost" href="{{ url('/app/journeys') }}">سفر مشتری</a>
            </div>
        </div>
        <div class="workflow-score">
            <a href="{{ url('/app/workflows') }}" style="--metric-color:#0ea5e9;">
                <span>کل قانون‌ها</span><b>@fa($summary['total'] ?? 0)</b><small>همه اتوماسیون‌ها</small>
            </a>
            <a href="{{ url('/app/workflows?status=active') }}" style="--metric-color:#10b981;">
                <span>فعال</span><b>@fa($summary['active'] ?? 0)</b><small>در حال اجرا</small>
            </a>
            <a href="{{ url('/app/workflows') }}" style="--metric-color:#f59e0b;">
                <span>زمان‌دار</span><b>@fa($summary['delayed'] ?? 0)</b><small>دارای تأخیر</small>
            </a>
            <a href="{{ url('/app/workflows?action=messenger') }}" style="--metric-color:#8b5cf6;">
                <span>پیام‌رسان</span><b>@fa($summary['messenger'] ?? 0)</b><small>ارسال در کانال‌ها</small>
            </a>
        </div>
    </section>

    {{-- نوار فیلترها --}}
    <form method="get" class="workflow-toolbar">
        <div>
            <label>جستجو</label>
            <input name="q" value="{{ request('q') }}" placeholder="نام، توضیح، رویداد یا اقدام">
        </div>
        <div>
            <label>رویداد</label>
            <select name="event">
                <option value="">همه رویدادها</option>
                @foreach($events as $key => $label)
                    <option value="{{ $key }}" @selected(request('event') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>اقدام</label>
            <select name="action">
                <option value="">همه اقدام‌ها</option>
                @foreach($actions as $key => $label)
                    <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>وضعیت</label>
            <select name="status">
                <option value="">همه</option>
                <option value="active" @selected(request('status') === 'active')>فعال</option>
                <option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option>
            </select>
        </div>
        <div class="workflow-toolbar-actions">
            <button class="btn">اعمال فیلتر</button>
            @if(request()->hasAny(['q','event','action','status']))
                <a class="btn btn-ghost" href="{{ url('/app/workflows') }}">حذف فیلتر</a>
            @endif
        </div>
    </form>

    {{-- نوار خلاصه --}}
    <section class="workflow-insight">
        <div><span class="workflow-dot is-ok"></span><b>@fa($workflows->count())</b><small>قانون قابل نمایش</small></div>
        <div><span class="workflow-dot is-warn"></span><b>@fa($summary['inactive'] ?? 0)</b><small>قانون خاموش</small></div>
        <div><span class="workflow-dot is-blue"></span><b>@fa(count($events))</b><small>رویداد آماده</small></div>
        <div><span class="workflow-dot is-purple"></span><b>@fa(count($actions))</b><small>نوع اقدام</small></div>
    </section>

    {{-- بورد قانون‌ها (تمام‌عرض بالا) + کارت‌های زیر آن --}}
    <section class="workflow-layout">

        {{-- بورد اصلی تمام‌عرض --}}
        <div class="workflow-main">
            <article class="workflow-card is-accent" style="--card-color:#0ea5e9;">
                <header>
                    <div>
                        <span>بورد قانون‌ها</span>
                        <h2>مدیریت گروهی قانون‌های خودکار</h2>
                        <p>هر گروه را باز کن تا قانون‌های آن را در یک جدول تمیز مشاهده و مدیریت کنی.</p>
                    </div>
                    @if($canManageAutomation)
                        <a class="workflow-link" href="#workflowCreatePanel">قانون جدید</a>
                    @endif
                </header>

                <div class="workflow-board">
                    @foreach($groupList as $group)
                        @php
                            $groupItems = $workflowGroups->get($group['key'], collect());
                            $ratio = $workflows->count()
                                ? round(($groupItems->count() / max(1, $workflows->count())) * 100)
                                : 0;
                            $isOpen = in_array($group['key'], $openGroups, true);
                            $groupClass = $isOpen ? 'is-open' : 'is-collapsed';
                        @endphp

                        <div class="workflow-group {{ $groupClass }}" style="--group-color:{{ $group['color'] }}; --group-ratio:{{ $ratio }}%;">
                            <button type="button" class="workflow-group-title" onclick="toggleWorkflowGroup(this)">
                                <span class="wf-caret">◀</span>
                                <span class="wf-mark"></span>
                                <span class="wf-title-block">
                                    <b>{{ $group['label'] }}</b>
                                    <small>{{ $group['hint'] }}</small>
                                </span>
                                <span class="wf-count">@fa($groupItems->count())</span>
                                <span class="wf-progress"></span>
                                <span class="wf-ratio">@fa($ratio)٪</span>
                            </button>

                            <div class="workflow-table-wrap">
                                <div class="workflow-table">
                                    <div class="workflow-thead">
                                        <div>نام قانون</div>
                                        <div>رویداد (وقتی)</div>
                                        <div>اقدام (انجام بده)</div>
                                        <div>کانال</div>
                                        <div>تأخیر</div>
                                        <div>وضعیت</div>
                                        <div>عملیات</div>
                                    </div>

                                    @forelse($groupItems as $workflow)
                                        <div class="workflow-row">
                                            <div class="workflow-cell">
                                                <div class="workflow-cell-name">
                                                    <b>{{ $workflow->name }}</b>
                                                    <small>{{ $workflow->description ?: 'بدون توضیح' }}</small>
                                                </div>
                                            </div>
                                            <div class="workflow-cell">
                                                <span class="workflow-pill is-event">{{ $events[$workflow->event] ?? $workflow->event }}</span>
                                            </div>
                                            <div class="workflow-cell">
                                                <span class="workflow-pill is-action">{{ $actions[$workflow->action] ?? $workflow->action }}</span>
                                            </div>
                                            <div class="workflow-cell">
                                                @if($workflow->action === 'messenger')
                                                    <span class="workflow-pill is-channel">{{ $channels[$workflow->channel] ?? 'همه کانال‌ها' }}</span>
                                                @else
                                                    <span class="workflow-pill is-empty">—</span>
                                                @endif
                                            </div>
                                            <div class="workflow-cell">
                                                @if((int)$workflow->delay_min > 0)
                                                    <span class="workflow-pill is-delay-timed">@fa($workflow->delay_min) دقیقه</span>
                                                @else
                                                    <span class="workflow-pill is-delay-instant">فوری</span>
                                                @endif
                                            </div>
                                            <div class="workflow-cell">
                                                <span class="workflow-pill {{ $workflow->is_active ? 'is-status-on' : 'is-status-off' }}">
                                                    {{ $workflow->is_active ? 'فعال' : 'خاموش' }}
                                                </span>
                                            </div>
                                            <div class="workflow-cell">
                                                <div class="workflow-cell-actions">
                                                    @if($canManageAutomation)
                                                        <form method="post" action="{{ url('/app/workflows/' . $workflow->id . '/toggle') }}">
                                                            @csrf
                                                            <button>{{ $workflow->is_active ? 'خاموش کن' : 'فعال کن' }}</button>
                                                        </form>
                                                        <form method="post" action="{{ url('/app/workflows/' . $workflow->id) }}" onsubmit="return confirm('این قانون حذف شود؟')">
                                                            @csrf @method('DELETE')
                                                            <button class="is-danger">حذف</button>
                                                        </form>
                                                    @else
                                                        <span class="workflow-pill is-empty">فقط نمایش</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="workflow-group-empty">در این گروه قانونی وجود ندارد.</div>
                                    @endforelse
                                </div>
                            </div>

                            @if($canManageAutomation)
                                <a class="workflow-group-add" href="#workflowCreatePanel">+ افزودن قانون در گروه «{{ $group['label'] }}»</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>
        </div>

        {{-- بخش زیر بورد: پیشنهادهای آماده (راست) + فرم ساخت قانون (چپ) --}}
        <div class="workflow-below">
            <article class="workflow-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>پیشنهادهای آماده</span>
                        <h2>قانون‌های پرکاربرد</h2>
                    </div>
                </header>
                <div class="workflow-suggest-list">
                    <button type="button" onclick="askWorkflowAssistant('برای سفارش تکمیل شده پیام تشکر بساز')">
                        <b>پیام تشکر بعد از سفارش</b><small>افزایش رضایت و تکرار خرید</small>
                    </button>
                    <button type="button" onclick="askWorkflowAssistant('برای تیکت جدید پیام اطلاع رسانی بساز')">
                        <b>اطلاع‌رسانی تیکت جدید</b><small>کاهش نگرانی مشتری</small>
                    </button>
                    <button type="button" onclick="askWorkflowAssistant('برای ۶۰ روز بدون خرید پیام بازگشت بساز')">
                        <b>بازگشت مشتری کم‌فعال</b><small>فعال‌سازی دوباره مشتری</small>
                    </button>
                </div>
            </article>

            @if($canManageAutomation)
            <article class="workflow-card is-accent" style="--card-color:#8b5cf6;" id="workflowCreatePanel">
                <header>
                    <div>
                        <span>ساخت قانون</span>
                        <h2>اگر این افتاد، این کار را انجام بده</h2>
                    </div>
                </header>
                <form method="post" action="{{ url('/app/workflows') }}" class="workflow-create-form">
                    @csrf
                    <div><label>نام قانون</label><input name="name" required placeholder="مثلاً پیام تشکر پس از سفارش"></div>
                    <div><label>توضیح کوتاه</label><input name="description" placeholder="هدف این قانون چیست؟"></div>
                    <div>
                        <label>رویداد</label>
                        <select name="event">
                            @foreach($events as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>اقدام</label>
                        <select name="action" id="workflowActionSelect">
                            @foreach($actions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="workflowChannelBox">
                        <label>کانال پیام‌رسان</label>
                        <select name="channel">
                            @foreach($channels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>تأخیر به دقیقه</label><input name="delay_min" value="0" inputmode="numeric"></div>
                    <div>
                        <label>متن پیام یا الگو</label>
                        <textarea name="template" rows="5" placeholder="سلام {نام}، سفارش شما با موفقیت ثبت شد."></textarea>
                        <small>متغیرهای نمونه: {نام}، {سفارش}، {مبلغ}، {وضعیت}، {برند}، {لینک_دعوت}</small>
                    </div>
                    <button class="btn">ساخت قانون</button>
                </form>
            </article>
            @endif
        </div>
    </section>

</div>

<script>
    // باز و بسته کردن گروه‌های بورد اتوماسیون
    function toggleWorkflowGroup(btn) {
        const group = btn.closest('.workflow-group');
        if (!group) return;
        group.classList.toggle('is-collapsed');
        group.classList.toggle('is-open');
    }

    // مخفی کردن کانال وقتی اقدام «پیام‌رسان» نیست
    (function () {
        const actionSelect = document.getElementById('workflowActionSelect');
        const channelBox   = document.getElementById('workflowChannelBox');
        if (actionSelect && channelBox) {
            function updateChannelBox() {
                channelBox.style.display = actionSelect.value === 'messenger' ? 'block' : 'none';
            }
            actionSelect.addEventListener('change', updateChannelBox);
            updateChannelBox();
        }
    })();

    // پرسش از دستیار برای ساخت قانون
    function askWorkflowAssistant(text) {
        window.MoshtariyarAssistantContext = {
            type: 'workflow_center',
            id: '',
            title: 'مرکز اتوماسیون هوشمند',
            url: window.location.href
        };
        if (typeof openAdminAssistantPanel === 'function') {
            openAdminAssistantPanel();
            setTimeout(function () {
                const frame = document.getElementById('adminAssistantFrame');
                try {
                    const input = frame.contentWindow.document.getElementById('adminChatInput');
                    const form  = frame.contentWindow.document.getElementById('adminChatForm');
                    if (input && form) {
                        input.value = text || 'گزارش اتوماسیون‌ها را بده';
                        form.dispatchEvent(new Event('submit', {cancelable: true}));
                    }
                } catch (e) {}
            }, 800);
        }
    }
</script>
@endsection
