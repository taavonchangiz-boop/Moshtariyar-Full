@extends('layouts.app')
@section('title','مرکز پایش همگام‌سازی')
@section('heading','مرکز پایش همگام‌سازی')
@section('subtitle','ردگیری خواندن و ارسال اطلاعات بین مشتری‌یار و فروشگاه')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sync-logs-board.css') }}">

@php
    $statusCards = collect([
        ['key' => 'failed', 'label' => 'نیازمند بررسی', 'color' => '#ef4444', 'hint' => 'موردهایی که باید دوباره بررسی یا ارسال شوند'],
        ['key' => 'pending', 'label' => 'در صف پردازش', 'color' => '#f59e0b', 'hint' => 'موردهایی که هنوز نتیجه نهایی نگرفته‌اند'],
        ['key' => 'success', 'label' => 'انجام‌شده', 'color' => '#10b981', 'hint' => 'موردهایی که با موفقیت ثبت شده‌اند'],
    ]);

    $visibleLogs = collect($logs->items());
    $groupedLogs = $visibleLogs->groupBy('status');
    $directionOptions = [
        'in' => 'از فروشگاه به مشتری‌یار',
        'out' => 'از مشتری‌یار به فروشگاه',
    ];
    $entityOptions = [
        'order' => 'سفارش',
        'customer' => 'مشتری',
        'product' => 'محصول',
        'connection' => 'اتصال فروشگاه',
    ];
@endphp

<div class="sync-page">
    <section class="sync-hero">
        <div class="sync-hero-main">
            <span>مرکز کنترل ارتباط فروشگاه</span>
            <h2>همه رفت‌وآمدهای اطلاعاتی فروشگاه را در یک بورد مدیریتی ببینید</h2>
            <p>هر دریافت یا ارسال اطلاعات در این بخش ثبت می‌شود تا بتوانید وضعیت موفق، ناموفق و در صف را سریع بررسی کنید و موردهای ناموفق را دوباره در جریان پردازش قرار دهید.</p>
        </div>
        <div class="sync-hero-stats">
            <div>
                <span>کل موردها</span>
                <b>@fa($summary['total'] ?? 0)</b>
            </div>
            <div>
                <span>موفق</span>
                <b>@fa($summary['success'] ?? 0)</b>
            </div>
            <div>
                <span>ناموفق</span>
                <b>@fa($summary['failed'] ?? 0)</b>
            </div>
            <div>
                <span>در صف</span>
                <b>@fa($summary['pending'] ?? 0)</b>
            </div>
        </div>
    </section>

    <section class="sync-toolbar">
        <form method="get" class="sync-filter-form">
            <div class="sync-field sync-field-wide">
                <label>فروشگاه</label>
                <select name="connection_id">
                    <option value="">همه فروشگاه‌ها</option>
                    @foreach($connections as $connection)
                        <option value="{{ $connection->id }}" @selected(request('connection_id') == $connection->id)>{{ $connection->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sync-field">
                <label>نتیجه</label>
                <select name="status">
                    <option value="">همه نتیجه‌ها</option>
                    <option value="success" @selected(request('status') === 'success')>موفق</option>
                    <option value="failed" @selected(request('status') === 'failed')>ناموفق</option>
                    <option value="pending" @selected(request('status') === 'pending')>در صف</option>
                </select>
            </div>
            <div class="sync-field">
                <label>مسیر</label>
                <select name="direction">
                    <option value="">همه مسیرها</option>
                    <option value="in" @selected(request('direction') === 'in')>از فروشگاه به مشتری‌یار</option>
                    <option value="out" @selected(request('direction') === 'out')>از مشتری‌یار به فروشگاه</option>
                </select>
            </div>
            <div class="sync-field">
                <label>نوع داده</label>
                <select name="entity">
                    <option value="">همه داده‌ها</option>
                    <option value="order" @selected(request('entity') === 'order')>سفارش</option>
                    <option value="customer" @selected(request('entity') === 'customer')>مشتری</option>
                    <option value="product" @selected(request('entity') === 'product')>محصول</option>
                </select>
            </div>
            <button class="btn sync-filter-button">اعمال فیلتر</button>
            <a class="btn btn-ghost sync-clear-button" href="{{ url('/app/sync-logs') }}">پاک کردن</a>
            <a class="btn btn-ghost sync-export-button" href="{{ url('/app/sync-logs/export') }}">دریافت خروجی</a>
        </form>
    </section>

    <section class="sync-board-shell">
        <div class="sync-board">
            @foreach($statusCards as $statusCard)
                @php
                    $columnLogs = $groupedLogs->get($statusCard['key'], collect());
                    $columnCount = $columnLogs->count();
                    $columnRatio = $visibleLogs->count() ? round(($columnCount / max(1, $visibleLogs->count())) * 100) : 0;
                @endphp
                <section class="sync-column" style="--sync-color: {{ $statusCard['color'] }}; --sync-ratio: {{ $columnRatio }}%;">
                    <header class="sync-column-header">
                        <div>
                            <span></span>
                            <h3>{{ $statusCard['label'] }}</h3>
                        </div>
                        <b>@fa($columnCount)</b>
                    </header>
                    <p>{{ $statusCard['hint'] }}</p>
                    <div class="sync-progress"><span></span></div>

                    <div class="sync-card-list">
                        @forelse($columnLogs as $log)
                            <article class="sync-log-card">
                                <header>
                                    <div>
                                        <span>{{ $log->entityLabel() }}</span>
                                        <h4>لاگ شماره @fa($log->id)</h4>
                                    </div>
                                    <b class="sync-status-pill sync-status-{{ $log->status }}">{{ $log->statusLabel() }}</b>
                                </header>

                                <div class="sync-log-meta">
                                    <div>
                                        <span>فروشگاه</span>
                                        <b>{{ $log->connection?->name ?? '—' }}</b>
                                    </div>
                                    <div>
                                        <span>مسیر</span>
                                        <b>{{ $directionOptions[$log->direction] ?? $log->directionLabel() }}</b>
                                    </div>
                                    <div>
                                        <span>شناسه فروشگاه</span>
                                        <b class="ltr">@fa($log->woo_id ?? '—')</b>
                                    </div>
                                    <div>
                                        <span>زمان</span>
                                        <b>@jdatetime($log->created_at)</b>
                                    </div>
                                </div>

                                <div class="sync-log-summary">
                                    <span>خلاصه</span>
                                    <p>{{ \Modules\Core\Support\Num::fa($log->payloadSummary()) }}</p>
                                </div>

                                @if($log->status === 'failed')
                                    <div class="sync-log-error">
                                        <span>علت خطا</span>
                                        <p>{{ \Modules\Core\Support\Num::fa($log->shortError()) }}</p>
                                    </div>
                                @endif

                                <footer>
                                    <a class="btn btn-ghost" href="{{ url('/app/sync-logs/' . $log->id) }}">جزئیات</a>
                                    @if($log->status === 'failed')
                                        <form method="post" action="{{ url('/app/sync-logs/' . $log->id . '/resend') }}">
                                            @csrf
                                            <button class="btn">ارسال دوباره</button>
                                        </form>
                                    @endif
                                </footer>
                            </article>
                        @empty
                            <div class="sync-empty-column">موردی در این گروه وجود ندارد.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>

    @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
        <div class="sync-pagination">{{ $logs->links() }}</div>
    @endif
</div>
@endsection