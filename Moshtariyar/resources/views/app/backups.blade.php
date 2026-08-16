@extends('layouts.app')
@section('title','پشتیبان‌گیری')
@section('heading','مرکز پشتیبان‌گیری و نگهداری سیستم')
@section('subtitle','دریافت نسخه پشتیبان پایگاه داده، تنظیمات و بررسی سلامت داده‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('css/backup-board.css') }}">
@php
    $tableCount = count($tableNames ?? []);
    $totalRows = (int) ($stats['total_rows'] ?? 0);
    $settingsCount = (int) ($stats['settings_count'] ?? 0);
    $databaseName = $stats['database'] ?? 'پایگاه داده';
    $topTables = collect($stats['top_tables'] ?? []);
@endphp
<div class="backup-page">
    <section class="backup-hero">
        <div>
            <span class="backup-eyebrow">نگهداری و امنیت داده</span>
            <h2>قبل از هر تغییر مهم، یک نسخه پشتیبان مطمئن داشته باشید</h2>
            <p>پایگاه داده، تنظیمات، مشتریان، سفارش‌ها، باشگاه مشتریان و ساختارهای اصلی سامانه از مهم‌ترین دارایی‌های شما هستند. این مرکز کمک می‌کند قبل از به‌روزرسانی، نصب فایل جدید یا تغییرات مهم، خروجی قابل نگهداری داشته باشید.</p>
            <div class="backup-actions">
                <a class="btn" href="{{ url('/app/backups/database') }}">دانلود پشتیبان پایگاه داده</a>
                <a class="btn btn-ghost" href="{{ url('/app/backups/settings') }}">دانلود تنظیمات</a>
                <a class="btn btn-ghost" href="{{ url('/app/settings') }}">رفتن به تنظیمات</a>
            </div>
        </div>
        <div class="backup-score">
            <div><span>جدول‌های پایگاه داده</span><b>@fa(number_format($tableCount))</b><small>ساختارهای قابل پشتیبان‌گیری</small></div>
            <div><span>ردیف‌های تخمینی</span><b>@fa(number_format($totalRows))</b><small>جمع ردیف‌های قابل شمارش</small></div>
            <div><span>تنظیمات غیرمحرمانه</span><b>@fa(number_format($settingsCount))</b><small>قابل خروجی گرفتن</small></div>
        </div>
    </section>

    <section class="backup-grid">
        <article class="backup-card is-accent" style="--backup-color:#0ea5e9;">
            <header>
                <div>
                    <span>پشتیبان پایگاه داده</span>
                    <h2>خروجی کامل SQL</h2>
                    <p>از همه جدول‌های پایگاه داده خروجی ساختار و داده گرفته می‌شود. این فایل برای بازگردانی دستی از طریق ابزار مدیریت پایگاه داده قابل استفاده است.</p>
                </div>
                <span class="backup-badge">اصلی</span>
            </header>
            <div class="backup-list">
                <article class="backup-row" style="--row-color:#0ea5e9;"><div class="backup-icon">🗄️</div><div><h3>{{ $databaseName }}</h3><p>پشتیبان شامل ساختار جدول‌ها و داده‌های ذخیره‌شده است.</p></div><span class="backup-badge">SQL</span></article>
                <article class="backup-row" style="--row-color:#10b981;"><div class="backup-icon">✓</div><div><h3>پیشنهاد قبل از تغییرات</h3><p>قبل از جایگذاری فایل‌ها، اجرای مهاجرت یا تغییرات بزرگ، این خروجی را دریافت کنید.</p></div><span class="backup-badge is-ok">مهم</span></article>
            </div>
            <div class="backup-actions"><a class="btn" href="{{ url('/app/backups/database') }}">دانلود پشتیبان پایگاه داده</a></div>
        </article>

        <aside class="backup-card is-accent" style="--backup-color:#10b981;">
            <header>
                <div>
                    <span>پشتیبان تنظیمات</span>
                    <h2>خروجی تنظیمات سامانه</h2>
                    <p>تنظیمات غیرمحرمانه برنامه در قالب فایل داده دریافت می‌شود. اطلاعات محرمانه مانند توکن‌ها و رمزها در خروجی عمومی قرار نمی‌گیرند.</p>
                </div>
                <span class="backup-badge is-ok">امن</span>
            </header>
            <div class="backup-list">
                <article class="backup-row" style="--row-color:#10b981;"><div class="backup-icon">⚙️</div><div><h3>تنظیمات قابل خروجی</h3><p>@fa(number_format($settingsCount)) مورد تنظیم غیرمحرمانه آماده دریافت است.</p></div><span class="backup-badge is-ok">JSON</span></article>
                <article class="backup-row" style="--row-color:#f59e0b;"><div class="backup-icon">🔐</div><div><h3>حفظ اطلاعات حساس</h3><p>کلیدهای محرمانه و رمزهای حساس در این خروجی عمومی نمایش داده نمی‌شوند.</p></div><span class="backup-badge is-warn">امنیت</span></article>
            </div>
            <div class="backup-actions"><a class="btn btn-ghost" href="{{ url('/app/backups/settings') }}">دانلود فایل تنظیمات</a></div>
        </aside>
    </section>

    <section class="backup-grid-reverse">
        <article class="backup-card is-accent" style="--backup-color:#8b5cf6;">
            <header>
                <div>
                    <span>جدول‌های پرحجم</span>
                    <h2>بزرگ‌ترین بخش‌های داده</h2>
                    <p>این بخش کمک می‌کند بدانید بیشترین حجم داده مربوط به کدام جدول‌هاست و هنگام پشتیبان‌گیری به چه بخش‌هایی توجه بیشتری داشته باشید.</p>
                </div>
            </header>
            <div class="backup-list">
                @forelse($topTables as $table)
                    <article class="backup-row" style="--row-color:#8b5cf6;">
                        <div class="backup-icon">▦</div>
                        <div><h3 class="ltr">{{ $table['name'] }}</h3><p>تعداد ردیف: @fa(number_format($table['rows']))</p></div>
                        <span class="backup-badge">جدول</span>
                    </article>
                @empty
                    <div class="backup-empty">هنوز آماری از جدول‌ها آماده نیست.</div>
                @endforelse
            </div>
        </article>

        <aside class="backup-card is-accent" style="--backup-color:#f59e0b;">
            <header>
                <div>
                    <span>راهنمای نگهداری</span>
                    <h2>چه زمانی پشتیبان بگیریم؟</h2>
                </div>
            </header>
            <div class="backup-list">
                <article class="backup-row" style="--row-color:#f59e0b;"><div class="backup-icon">۱</div><div><h3>قبل از به‌روزرسانی</h3><p>قبل از جایگذاری فایل‌های جدید یا اجرای تغییرات ساختاری، پشتیبان بگیرید.</p></div></article>
                <article class="backup-row" style="--row-color:#0ea5e9;"><div class="backup-icon">۲</div><div><h3>قبل از مهاجرت</h3><p>قبل از اجرای دستور مهاجرت، خروجی پایگاه داده دریافت کنید.</p></div></article>
                <article class="backup-row" style="--row-color:#10b981;"><div class="backup-icon">۳</div><div><h3>بعد از تغییرات موفق</h3><p>بعد از انجام تغییرات مهم و تست موفق، یک پشتیبان جدید نگهداری کنید.</p></div></article>
            </div>
        </aside>
    </section>

    <section class="backup-card is-accent" style="--backup-color:#64748b;">
        <header>
            <div>
                <span>جدول‌های موجود</span>
                <h2>فهرست ساختارهای پایگاه داده</h2>
            </div>
            <span class="backup-badge is-muted">@fa(number_format($tableCount)) جدول</span>
        </header>
        <div class="backup-table-tags">
            @foreach($tableNames as $tableName)
                <span class="ltr">{{ $tableName }}</span>
            @endforeach
        </div>
    </section>
</div>
@endsection