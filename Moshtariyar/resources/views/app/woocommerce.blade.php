@extends('layouts.app')
@section('title','اتصال فروشگاه')
@section('heading','مرکز اتصال فروشگاه')
@section('subtitle','مدیریت فروشگاه‌های متصل، دریافت داده‌ها و بررسی سلامت همگام‌سازی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/woocommerce-board.css') }}">

@php
    $totalConnections = $connections->count();
    $activeConnections = $connections->where('is_active', true)->count();
    $directConnections = $connections->filter(fn($connection) => $connection->hasApiCredentials())->count();
    $fileOnlyConnections = $connections->filter(fn($connection) => ! $connection->hasApiCredentials())->count();
    $failedDayCount = \Modules\WooBridge\Entities\SyncLog::where('created_at', '>=', now()->subDay())->where('status', 'failed')->count();
@endphp

<div class="store-page">
    <section class="store-hero">
        <div class="store-hero-main">
            <div class="store-eyebrow">مرکز اتصال فروشگاه</div>
            <h2>کنترل همه فروشگاه‌های متصل، دریافت اطلاعات و رفع خطا در یک صفحه</h2>
            <p>در روش درست، مشتری‌یار اطلاعات فروشگاه ووکامرسی را می‌خواند و وارد سامانه می‌کند. افزونه فقط کلید اتصال و رمز اتصال را می‌سازد و اینجا تست اتصال و دریافت اطلاعات انجام می‌شود.</p>
        </div>
        <div class="store-hero-metrics">
            <div class="store-metric-card">
                <span>کل فروشگاه‌ها</span>
                <strong>@fa($totalConnections)</strong>
            </div>
            <div class="store-metric-card">
                <span>اتصال فعال</span>
                <strong>@fa($activeConnections)</strong>
            </div>
            <div class="store-metric-card">
                <span>اتصال مستقیم</span>
                <strong>@fa($directConnections)</strong>
            </div>
            <div class="store-metric-card">
                <span>خطای ۲۴ ساعت اخیر</span>
                <strong>@fa($failedDayCount)</strong>
            </div>
        </div>
    </section>

    <section class="store-insight-strip">
        <div>
            <span class="store-dot store-dot-green"></span>
            <b>@fa($directConnections)</b>
            <small>فروشگاه آماده دریافت مستقیم</small>
        </div>
        <div>
            <span class="store-dot store-dot-orange"></span>
            <b>@fa($fileOnlyConnections)</b>
            <small>فروشگاه مناسب ورود فایل داده</small>
        </div>
        <div>
            <span class="store-dot store-dot-red"></span>
            <b>@fa($failedDayCount)</b>
            <small>خطای نیازمند بررسی</small>
        </div>
    </section>

    <details class="store-create-panel" open>
        <summary>
            <span>➕ افزودن فروشگاه جدید</span>
            <small>اطلاعات ساخته‌شده در افزونه ووکامرس را اینجا وارد کنید</small>
        </summary>

        <div class="store-new-connection-box" style="margin-top:0;">
            <div class="store-new-badge">راهنمای اتصال درست</div>
            <p>ابتدا در سایت ووکامرسی، از بخش تنظیمات افزونه مشتری‌یار، دکمه «ساخت کلید اتصال» را بزنید. سپس نشانی فروشگاه، کلید اتصال و رمز اتصال را از افزونه کپی کرده و در فرم زیر وارد کنید.</p>
        </div>

        <form method="post" action="{{ url('/app/woocommerce') }}" class="store-create-form">
            @csrf
            <div>
                <label>نام فروشگاه</label>
                <input name="name" required placeholder="مثلاً: فروشگاه اصلی">
            </div>
            <div class="store-url-field">
                <label>نشانی فروشگاه</label>
                <input name="store_url" required placeholder="https://shop.example.ir" class="ltr">
            </div>
            <div>
                <label>کلید اتصال</label>
                <input name="consumer_key" required placeholder="ck_..." class="ltr">
            </div>
            <div>
                <label>رمز اتصال</label>
                <input name="consumer_secret" required placeholder="cs_..." class="ltr">
            </div>
            <div>
                <label>کلید امن اختیاری</label>
                <input name="webhook_secret" placeholder="در صورت وجود از افزونه کپی کنید" class="ltr">
            </div>
            <button class="btn store-create-button">ساخت اتصال</button>
        </form>

        @if(session('new_connection'))
            @php $newConnection = session('new_connection'); @endphp
            <div class="store-new-connection-box">
                <div class="store-new-badge">اتصال ساخته شد</div>
                <p>{{ $newConnection['message'] ?? 'اتصال ساخته شد. حالا از کارت فروشگاه، آزمون اتصال و دریافت اطلاعات را اجرا کنید.' }}</p>
                <div>
                    <span>شناسه اتصال در مشتری‌یار</span>
                    <code>{{ $newConnection['connection_id'] ?? '—' }}</code>
                </div>
                <div>
                    <span>نشانی فروشگاه ثبت‌شده</span>
                    <code>{{ $newConnection['store_url'] ?? '—' }}</code>
                </div>
            </div>
        @endif
    </details>

    <section class="store-help-panel">
        <div>
            <b>۱. ساخت کلید در افزونه</b>
            <span>در وردپرس، افزونه مشتری‌یار را باز کنید و کلید اتصال و رمز اتصال را بسازید.</span>
        </div>
        <div>
            <b>۲. ثبت در مشتری‌یار</b>
            <span>نشانی فروشگاه، کلید اتصال و رمز اتصال را در فرم بالا وارد کنید.</span>
        </div>
        <div>
            <b>۳. تست و دریافت اطلاعات</b>
            <span>بعد از ساخت اتصال، از کارت فروشگاه دکمه آزمون دریافت و سپس دریافت کامل را بزنید.</span>
        </div>
    </section>

    <section class="store-toolbar">
        <div>
            <h3>فروشگاه‌های متصل</h3>
            <p>برای هر فروشگاه، آزمون اتصال، دریافت سفارش، دریافت مشتری، دریافت کالا، دریافت کامل و ورود فایل داده در دسترس است.</p>
        </div>
        <a class="btn btn-ghost" href="{{ url('/app/sync-logs') }}">مشاهده لاگ همگام‌سازی</a>
    </section>

    <section class="store-board-shell">
        <div class="store-board">
            @forelse($connections as $connection)
                @php
                    $mapCustomers = $connection->idMaps()->where('entity', 'customer')->count();
                    $mapOrders = $connection->idMaps()->where('entity', 'order')->count();
                    $mapProducts = $connection->idMaps()->where('entity', 'product')->count();
                    $pendingLogs = $connection->syncLogs()->where('status', 'pending')->count();
                    $failedLogs = $connection->syncLogs()->where('status', 'failed')->where('created_at', '>=', now()->subDay())->count();
                    $connectionColor = $failedLogs > 0 ? '#ef4444' : ($connection->hasApiCredentials() ? '#10b981' : '#f59e0b');
                @endphp
                <details class="store-card" style="--store-status-color: {{ $connectionColor }};" {{ $loop->first ? 'open' : '' }}>
                    <summary class="store-card-summary">
                        <span class="store-summary-arrow">⌄</span>
                        <span class="store-summary-main">
                            <b>{{ $connection->name }}</b>
                            <small>{{ $connection->connectionStatusLabel() }}</small>
                        </span>
                        @if($failedLogs > 0)
                            <span class="store-alert-badge">خطا</span>
                        @else
                            <span class="store-ok-badge">سالم</span>
                        @endif
                    </summary>

                    <div class="store-card-body">
                        <div class="store-card-top">
                            <h4>{{ $connection->name }}</h4>
                            <span class="ltr">{{ $connection->store_url }}</span>
                        </div>

                        <div class="store-status-box">
                            <span>آخرین دریافت موفق</span>
                            <b>{{ $connection->last_sync_at ? \Modules\Core\Support\Jalali::datetime($connection->last_sync_at) : 'هنوز ثبت نشده' }}</b>
                        </div>

                        <div class="store-mini-grid">
                            <div>
                                <span>مشتری‌ها</span>
                                <b>@fa($mapCustomers)</b>
                            </div>
                            <div>
                                <span>سفارش‌ها</span>
                                <b>@fa($mapOrders)</b>
                            </div>
                            <div>
                                <span>کالاها</span>
                                <b>@fa($mapProducts)</b>
                            </div>
                            <div>
                                <span>در صف</span>
                                <b>@fa($pendingLogs)</b>
                            </div>
                        </div>

                        <div class="store-actions-grid">
                            <button type="button" class="store-small-action" onclick="toggleEdit({{ $connection->id }})">ویرایش اتصال</button>

                            <form method="post" action="{{ url('/app/woocommerce/'.$connection->id.'/pull-test') }}">
                                @csrf
                                <button class="store-small-action">آزمون دریافت</button>
                            </form>

                            <form method="post" action="{{ url('/app/woocommerce/'.$connection->id.'/pull-all-now') }}">
                                @csrf
                                <input type="hidden" name="per_page" value="20">
                                <button class="store-small-action store-small-action-primary">دریافت کامل</button>
                            </form>

                            <form method="post" action="{{ url('/app/woocommerce/'.$connection->id) }}" onsubmit="return confirm('این فروشگاه حذف شود؟')">
                                @csrf
                                @method('DELETE')
                                <button class="store-small-action store-small-action-danger">حذف</button>
                            </form>
                        </div>

                        <div class="store-pull-row">
                            <form method="post" action="{{ url('/app/woocommerce/'.$connection->id.'/pull-now') }}">
                                @csrf
                                <input type="hidden" name="per_page" value="20">
                                <button class="store-line-action">دریافت سفارش‌ها</button>
                            </form>

                            <form method="post" action="{{ url('/app/woocommerce/'.$connection->id.'/pull-customers-now') }}">
                                @csrf
                                <input type="hidden" name="per_page" value="20">
                                <button class="store-line-action">دریافت مشتری‌ها</button>
                            </form>

                            <form method="post" action="{{ url('/app/woocommerce/'.$connection->id.'/pull-products-now') }}">
                                @csrf
                                <input type="hidden" name="per_page" value="20">
                                <button class="store-line-action">دریافت کالاها</button>
                            </form>
                        </div>

                        <form method="post" action="{{ url('/app/woocommerce/'.$connection->id.'/import-file') }}" enctype="multipart/form-data" class="store-import-form">
                            @csrf
                            <label>ورود فایل داده فروشگاه</label>
                            <input type="file" name="export_file" accept=".json,application/json,text/plain" required data-hint="فایل خروجی فروشگاه را اینجا انتخاب کنید.">
                            <button class="btn btn-ghost">ورود فایل داده</button>
                        </form>
                    </div>
                </details>

                <div id="edit-{{ $connection->id }}" class="store-edit-panel">
                    <form method="post" action="{{ url('/app/woocommerce/'.$connection->id) }}">
                        @csrf
                        @method('PUT')
                        <div>
                            <label>نام فروشگاه</label>
                            <input name="name" value="{{ $connection->name }}" required>
                        </div>
                        <div>
                            <label>نشانی فروشگاه</label>
                            <input name="store_url" value="{{ $connection->store_url }}" required class="ltr">
                        </div>
                        <div>
                            <label>کلید اتصال تازه</label>
                            <input name="consumer_key" class="ltr" placeholder="کلید تازه">
                        </div>
                        <div>
                            <label>رمز اتصال تازه</label>
                            <input name="consumer_secret" class="ltr" placeholder="رمز تازه">
                        </div>
                        <div>
                            <label>کلید امن اختیاری</label>
                            <input name="webhook_secret" value="{{ $connection->webhook_secret }}" class="ltr">
                        </div>
                        <label class="store-active-check">
                            <input type="checkbox" name="is_active" value="1" @checked($connection->is_active)>
                            فعال باشد
                        </label>
                        <button class="btn">ذخیره تغییرات</button>
                    </form>
                </div>
            @empty
                <div class="store-empty-state">
                    <div>🛒</div>
                    <h3>هنوز فروشگاهی متصل نشده است</h3>
                    <p>از فرم بالا اولین فروشگاه را اضافه کنید. اطلاعات لازم را باید از افزونه مشتری‌یار در سایت ووکامرسی بگیرید.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>

<script>
function toggleEdit(id) {
    const row = document.getElementById('edit-' + id);
    if (!row) return;
    row.classList.toggle('show');
}
</script>
@endsection