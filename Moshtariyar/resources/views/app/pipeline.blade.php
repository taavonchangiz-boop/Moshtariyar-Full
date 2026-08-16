@extends('layouts.app')

@section('title', 'قیف فروش')
@section('heading', '🎯 قیف فروش')
@section('subtitle', 'بورد عملیاتی فرصت‌های فروش، پیگیری‌ها و تبدیل سرنخ به مشتری')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pipeline-board.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pipeline-kanban.css') }}">
@endpush

@section('content')

@php
    $canManageLeads = auth()->user()?->hasPermission('leads.manage');

    $sourceLabels = [
        'manual'        => 'ثبت دستی',
        'website-form'  => 'فرم سایت',
        'form'          => 'فرم سایت',
        'woocommerce'   => 'فروشگاه',
        'club_portal'   => 'باشگاه مشتریان',
        'instagram'     => 'اینستاگرام',
        'phone'         => 'تماس تلفنی',
    ];

    $sourceTitle = fn ($source) => $sourceLabels[$source] ?? ($source ?: 'نامشخص');

    $sourceBadgeClass = function ($source) {
        return match ($source) {
            'website-form', 'form' => 'pl-source-website',
            'woocommerce'          => 'pl-source-woocommerce',
            'club_portal'          => 'pl-source-club',
            'instagram'            => 'pl-source-instagram',
            'phone'                => 'pl-source-phone',
            default                => 'pl-source-manual',
        };
    };

    if (!function_exists('fa_num')) {
        function fa_num($n) {
            return str_replace(
                ['0','1','2','3','4','5','6','7','8','9'],
                ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'],
                (string) $n
            );
        }
    }
@endphp

<div class="pipeline-page" data-money-unit="{{ \Modules\Core\Support\Money::unitLabel() }}">

    {{-- هدر --}}
    <section class="pipeline-hero">
        <div class="pipeline-hero-main">
            <div class="pipeline-eyebrow">مرکز فرصت‌های فروش</div>
            <h2>تصویر زنده از مسیر تبدیل سرنخ به مشتری</h2>
            <p>هر کارت یک فرصت فروش است. آن را بین مرحله‌ها جابه‌جا کنید، ارزش فرصت را ببینید و با یک کلیک جزئیات کامل را باز کنید.</p>
        </div>
        <div class="pipeline-hero-metrics">
            <div class="pipeline-metric-card">
                <span>کل سرنخ‌ها</span>
                <strong>@fa($summary['count'] ?? 0)</strong>
            </div>
            <div class="pipeline-metric-card">
                <span>ارزش فرصت‌ها</span>
                <strong>@money($summary['value'] ?? 0)</strong>
                <small>@unit</small>
            </div>
            <div class="pipeline-metric-card">
                <span>نرخ تبدیل</span>
                <strong>@fa($summary['conversion_rate'] ?? 0)٪</strong>
            </div>
            <div class="pipeline-metric-card">
                <span>میانگین امتیاز</span>
                <strong>@fa($summary['average_score'] ?? 0)</strong>
            </div>
        </div>
    </section>

    {{-- نوار فیلترها --}}
    <section class="pipeline-toolbar">
        <form method="get" class="pipeline-filter-form">
            <div class="pipeline-search-field">
                <span>🔎</span>
                <input name="q" value="{{ request('q') }}" placeholder="جستجو بر اساس نام، موبایل، ایمیل یا منبع">
            </div>
            <div class="pipeline-filter-field">
                <label>مرحله</label>
                <select name="status_id">
                    <option value="">همه مرحله‌ها</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected((string) request('status_id') === (string) $status->id)>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="pipeline-filter-field">
                <label>منبع</label>
                <select name="source">
                    <option value="">همه منابع</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" @selected((string) request('source') === (string) $source)>
                            {{ $sourceTitle($source) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn pipeline-filter-button">اعمال فیلتر</button>
            @if (request()->hasAny(['q', 'status_id', 'source']))
                <a class="btn btn-ghost pipeline-clear-button" href="{{ url('/app/pipeline') }}">پاک کردن</a>
            @endif
        </form>
    </section>

    {{-- ثبت سرنخ جدید --}}
    @if ($canManageLeads)
        <details class="pipeline-create-card" {{ request()->hasAny(['q', 'status_id', 'source']) ? '' : 'open' }}>
            <summary>
                <span>➕ افزودن فرصت تازه</span>
                <small>ثبت سریع سرنخ جدید داخل بورد فروش</small>
            </summary>
            <form method="post" action="{{ url('/app/pipeline') }}" class="pipeline-create-form">
                @csrf
                <div>
                    <label>نام سرنخ</label>
                    <input name="name" required placeholder="مثلاً: شرکت آریا">
                </div>
                <div>
                    <label>موبایل</label>
                    <input name="phone" class="ltr" placeholder="۰۹۱۲...">
                </div>
                <div>
                    <label>ایمیل</label>
                    <input name="email" class="email" placeholder="در صورت وجود">
                </div>
                <div>
                    <label>ارزش فرصت به @unit</label>
                    <input name="value" class="ltr" inputmode="numeric" placeholder="مثلاً ۵۰۰۰۰۰۰">
                </div>
                <div>
                    <label>منبع جذب</label>
                    <select name="source">
                        <option value="manual">ثبت دستی</option>
                        <option value="website-form">فرم سایت</option>
                        <option value="woocommerce">فروشگاه</option>
                        <option value="club_portal">باشگاه مشتریان</option>
                        <option value="instagram">اینستاگرام</option>
                        <option value="phone">تماس تلفنی</option>
                    </select>
                </div>
                <div>
                    <label>مرحله شروع</label>
                    <select name="status_id">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" @selected($firstStatusId == $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn pipeline-create-button">ثبت در بورد</button>
            </form>
        </details>
    @endif

    {{-- نوار خلاصه --}}
    <section class="pipeline-insight-strip">
        <div>
            <span class="insight-dot insight-dot-blue"></span>
            <b>@fa($summary['stages'] ?? 0)</b>
            <small>مرحله فعال در قیف فروش</small>
        </div>
        <div>
            <span class="insight-dot insight-dot-green"></span>
            <b>@fa($summary['converted'] ?? 0)</b>
            <small>سرنخ تبدیل‌شده به مشتری</small>
        </div>
        <div>
            <span class="insight-dot insight-dot-orange"></span>
            <b>@money(($summary['value'] ?? 0) * (($summary['conversion_rate'] ?? 0) / 100))</b>
            <small>برآورد درآمد قابل اتکا به @unit</small>
        </div>
    </section>

    {{-- ═══════ تغییر نما: اصلی / کانبان ═══════ --}}
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
        <button type="button" class="pl-view-btn active" data-view="list" onclick="switchPipelineView('list')">
            📃 نمای اصلی
        </button>
        <button type="button" class="pl-view-btn" data-view="kanban" onclick="switchPipelineView('kanban')">
            📋 نمای کانبان
        </button>
        <span style="margin-right:auto; font-size:0.75rem; color:var(--mut); font-weight:600;">
            @fa($summary['count'] ?? 0) سرنخ در {{ $summary['stages'] ?? 0 }} مرحله
        </span>
    </div>

    {{-- ═══════════════ نمای کانبان ═══════════════ --}}
    <div id="pipelineKanbanView" style="display:none;">
        <div class="pl-kanban-board" id="plKanbanBoard">
            @foreach ($statuses as $status)
                @php
                    $columnLeads = $leads->get($status->id, collect());
                    $columnTotal = $totals[$status->id] ?? ['count' => 0, 'value' => 0];
                @endphp
                <div class="pl-column"
                     data-status-id="{{ $status->id }}"
                     style="--col-color: {{ $status->color }};">

                    <div class="pl-col-header">
                        <span class="pl-col-dot"></span>
                        <span class="pl-col-title">{{ $status->name }}</span>
                        <span class="pl-col-count">{{ fa_num($columnTotal['count']) }}</span>
                    </div>

                    <div class="pl-col-meta">
                        <span>ارزش: <b>{{ number_format($columnTotal['value']) }}</b></span>
                    </div>

                    <div class="pl-col-cards"
                         ondragover="event.preventDefault(); this.classList.add('pl-drag-over')"
                         ondragleave="this.classList.remove('pl-drag-over')"
                         ondrop="handlePlDrop(event, {{ $status->id }})">

                        @forelse ($columnLeads as $lead)
                            @php
                                $isConverted = (bool) $lead->converted_customer_id;
                            @endphp

                            <div class="pl-card {{ $isConverted ? 'pl-converted' : '' }}"
                                 draggable="{{ $canManageLeads ? 'true' : 'false' }}"
                                 data-id="{{ $lead->id }}"
                                 data-value="{{ (float) $lead->value }}"
                                 ondragstart="handlePlDragStart(event, {{ $lead->id }})"
                                 ondragend="handlePlDragEnd(event)"
                                 onclick="openLeadDrawer('{{ url('/app/pipeline/' . $lead->id) }}', '{{ $lead->name ?: 'سرنخ بدون نام' }}')">

                                <div class="pl-card-top">
                                    <h4>{{ $lead->name ?: 'سرنخ بدون نام' }}</h4>
                                    <span class="pl-card-num">#{{ $lead->id }}</span>
                                </div>

                                <div class="pl-card-value">
                                    <span>ارزش فرصت</span>
                                    <b>@money($lead->value) @unit</b>
                                </div>

                                <div class="pl-card-contact">
                                    @if ($lead->phone)
                                        <span>📱 {{ $lead->phone }}</span>
                                    @endif
                                    @if ($lead->email)
                                        <span>✉️ {{ $lead->email }}</span>
                                    @endif
                                </div>

                                <div class="pl-card-badges">
                                    <span class="pl-badge {{ $sourceBadgeClass($lead->source) }}">
                                        {{ $sourceTitle($lead->source) }}
                                    </span>
                                    <span class="pl-badge pl-score">
                                        ⭐ {{ (int) $lead->score }}
                                    </span>
                                </div>

                                <div class="pl-card-footer">
                                    <div class="pl-score-bar" style="--sc: {{ $lead->score >= 70 ? '#10b981' : ($lead->score >= 40 ? '#f59e0b' : '#ef4444') }};">
                                        <span style="width: {{ max(0, min(100, (int) $lead->score)) }}%;"></span>
                                    </div>
                                    @if ($isConverted)
                                        <span class="pl-converted-badge">✅ تبدیل‌شده</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="pl-col-empty">
                                <span>📭</span>
                                <p>سرنخی در این مرحله نیست</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ نمای اصلی (بورد موجود) ═══════════════ --}}
    <div id="pipelineMainView">
        <section class="pipeline-board-shell">
            <div class="pipeline-board" id="pipelineBoard">
                @foreach ($statuses as $status)
                    @php
                        $columnLeads  = $leads->get($status->id, collect());
                        $columnTotal  = $totals[$status->id] ?? ['count' => 0, 'value' => 0, 'score' => 0];
                        $columnRatio  = $maxColumnCount ? round(($columnTotal['count'] / $maxColumnCount) * 100) : 0;
                    @endphp
                    <section class="pipeline-column"
                             data-status-id="{{ $status->id }}"
                             style="--stage-color: {{ $status->color }}; --column-ratio: {{ $columnRatio }}%;">
                        <header class="pipeline-column-header">
                            <div>
                                <span class="stage-color-mark"></span>
                                <h3>{{ $status->name }}</h3>
                            </div>
                            <span class="stage-count" data-count-for="{{ $status->id }}">@fa($columnTotal['count'])</span>
                        </header>
                        <div class="pipeline-column-meta">
                            <span>ارزش: <b data-total-for="{{ $status->id }}">@money($columnTotal['value'])</b> @unit</span>
                            <span>امتیاز میانگین: <b>@fa($columnTotal['score'])</b></span>
                        </div>
                        <div class="pipeline-column-progress"><span></span></div>

                        <div class="pipeline-drop-zone" data-status-id="{{ $status->id }}">
                            @forelse ($columnLeads as $lead)
                                <article class="deal-card"
                                    data-lead-id="{{ $lead->id }}"
                                    data-value="{{ (float) $lead->value }}"
                                    data-detail-url="{{ url('/app/pipeline/' . $lead->id) }}?embed=1"
                                    data-title="{{ $lead->name ?: 'سرنخ بدون نام' }}"
                                    data-move-url="{{ url('/app/pipeline/' . $lead->id . '/move') }}"
                                    draggable="{{ $canManageLeads ? 'true' : 'false' }}">
                                    <div class="deal-card-top">
                                        <div>
                                            <h4>{{ $lead->name ?: 'سرنخ بدون نام' }}</h4>
                                            <span class="deal-source">{{ $sourceTitle($lead->source) }}</span>
                                        </div>
                                        <button type="button" class="deal-open-button">جزئیات</button>
                                    </div>

                                    <div class="deal-value-row">
                                        <span>ارزش فرصت</span>
                                        <b>@money($lead->value) @unit</b>
                                    </div>

                                    <div class="deal-contact-grid">
                                        <div>
                                            <span>موبایل</span>
                                            <b class="ltr">@fa($lead->phone ?: '—')</b>
                                        </div>
                                        <div>
                                            <span>ایمیل</span>
                                            <b class="email">{{ $lead->email ?: '—' }}</b>
                                        </div>
                                    </div>

                                    <div class="deal-score-row">
                                        <span>امتیاز آمادگی</span>
                                        <div class="deal-score-bar" style="--score-width: {{ max(0, min(100, (int) $lead->score)) }}%;">
                                            <span></span>
                                        </div>
                                        <b>@fa($lead->score)</b>
                                    </div>

                                    <div class="deal-card-footer">
                                        @if ($lead->converted_customer_id)
                                            <span class="deal-chip deal-chip-done">تبدیل‌شده</span>
                                        @else
                                            <span class="deal-chip">در جریان</span>
                                        @endif
                                        <span class="deal-last-touch">
                                            آخرین پیگیری:
                                            @if ($lead->last_activity_at)
                                                @jdatetime($lead->last_activity_at)
                                            @else
                                                ثبت نشده
                                            @endif
                                        </span>
                                    </div>

                                    @if ($canManageLeads)
                                        <form method="post" action="{{ url('/app/pipeline/' . $lead->id . '/move') }}" class="deal-stage-form">
                                            @csrf
                                            <select name="status_id" aria-label="تغییر مرحله">
                                                @foreach ($statuses as $stageOption)
                                                    <option value="{{ $stageOption->id }}" @selected($stageOption->id == $lead->status_id)>
                                                        {{ $stageOption->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-ghost">انتقال</button>
                                        </form>
                                    @endif
                                </article>
                            @empty
                                <div class="pipeline-empty-column">فعلاً کارتی در این مرحله نیست.</div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    </div>
</div>

{{-- Toast --}}
<div class="pl-toast hidden" id="plToast"></div>

<script>
(function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ═══════ تغییر نما ═══════
    window.switchPipelineView = function (view) {
        document.querySelectorAll('.pl-view-btn').forEach(function (b) { b.classList.remove('active'); });
        document.querySelector('.pl-view-btn[data-view="' + view + '"]').classList.add('active');
        document.getElementById('pipelineKanbanView').style.display = (view === 'kanban') ? '' : 'none';
        document.getElementById('pipelineMainView').style.display = (view === 'list') ? '' : 'none';
    };

    // ═══════ کانبان: درگ‌اَند دراپ ═══════
    window.handlePlDragStart = function (event, id) {
        event.target.classList.add('pl-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', id);
    };

    window.handlePlDragEnd = function (event) {
        event.target.classList.remove('pl-dragging');
        document.querySelectorAll('.pl-col-cards').forEach(function (c) { c.classList.remove('pl-drag-over'); });
    };

    window.handlePlDrop = function (event, newStatusId) {
        event.preventDefault();
        var container = event.currentTarget;
        container.classList.remove('pl-drag-over');
        var leadId = event.dataTransfer.getData('text/plain');
        if (!leadId) return;

        var card = document.querySelector('.pl-card[data-id="' + leadId + '"]');
        if (!card) return;

        var oldCol = card.closest('.pl-column');
        if (!oldCol || oldCol.dataset.statusId == newStatusId) return;

        card.remove();
        var empty = container.querySelector('.pl-col-empty');
        if (empty) empty.remove();
        container.appendChild(card);

        var oldCards = oldCol.querySelector('.pl-col-cards');
        if (oldCards && oldCards.querySelectorAll('.pl-card').length === 0) {
            oldCards.innerHTML = '<div class="pl-col-empty"><span>📭</span><p>سرنخی در این مرحله نیست</p></div>';
        }

        refreshPlCounters();

        fetch('/app/pipeline/' + leadId + '/move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status_id: newStatusId })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) showPlToast('✅ مرحله سرنخ تغییر کرد', 'ok');
            else showPlToast('❌ خطا در تغییر مرحله', 'bad');
        })
        .catch(function () { showPlToast('❌ خطا در ارتباط با سرور', 'bad'); });
    };

    function refreshPlCounters() {
        document.querySelectorAll('.pl-column').forEach(function (col) {
            var cnt = col.querySelectorAll('.pl-card').length;
            var el = col.querySelector('.pl-col-count');
            if (el) el.textContent = plFa(cnt);
            var cards = col.querySelectorAll('.pl-card');
            var total = 0;
            cards.forEach(function (c) { total += Number(c.dataset.value || 0); });
            var metaEl = col.querySelector('.pl-col-meta b');
            if (metaEl) metaEl.textContent = new Intl.NumberFormat('fa-IR').format(Math.round(total));
        });
    }

    function showPlToast(msg, type) {
        var t = document.getElementById('plToast');
        t.textContent = msg;
        t.className = 'pl-toast pl-toast-' + (type || 'ok');
        t.classList.remove('hidden');
        setTimeout(function () { t.classList.add('hidden'); }, 3000);
    }

    function plFa(n) {
        return String(n).replace(/[0-9]/g, function (d) {
            return ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'][d];
        });
    }

    // ═══════ باز کردن کشوی جزئیات ═══════
    window.openLeadDrawer = function (url, title) {
        if (typeof openDrawer === 'function') {
            openDrawer(url + (url.includes('?') ? '&' : '?') + 'embed=1', title || 'جزئیات سرنخ');
            return;
        }
        window.location.href = url;
    };

    // ═══════ بورد اصلی: درگ‌اَند دراپ (موجود) ═══════
    const board = document.getElementById('pipelineBoard');
    if (board) {
        let draggedCard = null;

        board.addEventListener('click', function (event) {
            const card = event.target.closest('.deal-card');
            if (!card) return;
            if (event.target.closest('form, button, a, input, select, textarea')) {
                if (event.target.classList.contains('deal-open-button')) {
                    openLeadDrawer(card.dataset.detailUrl.replace('?embed=1', ''), card.dataset.title);
                }
                return;
            }
            openLeadDrawer(card.dataset.detailUrl.replace('?embed=1', ''), card.dataset.title);
        });

        board.addEventListener('dragstart', function (event) {
            const card = event.target.closest('.deal-card');
            if (!card || card.getAttribute('draggable') !== 'true') return;
            draggedCard = card;
            card.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.leadId);
        });

        board.addEventListener('dragend', function () {
            if (draggedCard) draggedCard.classList.remove('is-dragging');
            document.querySelectorAll('.pipeline-drop-zone').forEach(function (zone) { zone.classList.remove('is-over'); });
            draggedCard = null;
        });

        board.addEventListener('dragover', function (event) {
            const zone = event.target.closest('.pipeline-drop-zone');
            if (!zone || !draggedCard) return;
            event.preventDefault();
            zone.classList.add('is-over');
        });

        board.addEventListener('dragleave', function (event) {
            const zone = event.target.closest('.pipeline-drop-zone');
            if (!zone) return;
            zone.classList.remove('is-over');
        });

        board.addEventListener('drop', function (event) {
            const zone = event.target.closest('.pipeline-drop-zone');
            if (!zone || !draggedCard) return;
            event.preventDefault();
            zone.classList.remove('is-over');
            const oldZone = draggedCard.closest('.pipeline-drop-zone');
            const newStatus = zone.dataset.statusId;
            const moveUrl = draggedCard.dataset.moveUrl;

            zone.appendChild(draggedCard);
            const select = draggedCard.querySelector('.deal-stage-form select');
            if (select) select.value = newStatus;
            refreshMainCounters();

            fetch(moveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status_id: newStatus })
            })
            .then(function (response) {
                if (!response.ok) throw new Error('خطا');
                return response.json();
            })
            .then(function () { showPlToast('مرحله سرنخ با موفقیت تغییر کرد.', true); })
            .catch(function () {
                if (oldZone) oldZone.appendChild(draggedCard);
                refreshMainCounters();
                showPlToast('تغییر مرحله انجام نشد. دوباره تلاش کنید.', false);
            });
        });

        document.querySelectorAll('.deal-stage-form select').forEach(function (select) {
            select.addEventListener('change', function () { this.closest('form').submit(); });
        });

        function refreshMainCounters() {
            document.querySelectorAll('.pipeline-column').forEach(function (column) {
                const statusId = column.dataset.statusId;
                const cards = column.querySelectorAll('.deal-card');
                const countNode = document.querySelector('[data-count-for="' + statusId + '"]');
                const totalNode = document.querySelector('[data-total-for="' + statusId + '"]');
                let total = 0;
                cards.forEach(function (card) { total += Number(card.dataset.value || 0); });
                if (countNode) countNode.textContent = new Intl.NumberFormat('fa-IR').format(cards.length);
                if (totalNode) totalNode.textContent = new Intl.NumberFormat('fa-IR').format(Math.round(total));
            });
        }
    }
})();
</script>
@endsection