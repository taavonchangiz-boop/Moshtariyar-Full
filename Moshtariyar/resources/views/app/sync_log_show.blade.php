@extends('layouts.app')
@section('title','جزئیات لاگ همگام‌سازی')
@section('heading','جزئیات لاگ همگام‌سازی')
@section('subtitle','بررسی داده ثبت‌شده، نتیجه همگام‌سازی و راهنمای اقدام')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sync-logs-board.css') }}">

@php
    $payload = $log->payload ?? [];
    if (! is_array($payload)) {
        $payload = ['value' => $payload];
    }

    $prettyPayload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (! $prettyPayload) {
        $prettyPayload = '—';
    }

    $statusColor = match ($log->status) {
        'success' => '#10b981',
        'failed' => '#ef4444',
        'pending' => '#f59e0b',
        default => '#64748b',
    };
@endphp

<div class="sync-show-page" style="--sync-color: {{ $statusColor }};">
    <section class="sync-show-hero">
        <div>
            <span>جزئیات همگام‌سازی</span>
            <h2>لاگ شماره @fa($log->id)</h2>
            <p>در این صفحه می‌توانید نتیجه ثبت‌شده، مسیر رفت‌وآمد اطلاعات، داده ذخیره‌شده و پیشنهاد اقدام را بررسی کنید.</p>
        </div>
        <div class="sync-show-actions">
            <a class="btn btn-ghost" href="{{ url('/app/sync-logs') }}">بازگشت به مرکز پایش</a>
            @if($log->status === 'failed')
                <form method="post" action="{{ url('/app/sync-logs/' . $log->id . '/resend') }}">
                    @csrf
                    <button class="btn">ارسال دوباره</button>
                </form>
            @endif
        </div>
    </section>

    <section class="sync-show-metrics">
        <div>
            <span>وضعیت</span>
            <b>{{ $log->statusLabel() }}</b>
        </div>
        <div>
            <span>نوع داده</span>
            <b>{{ $log->entityLabel() }}</b>
        </div>
        <div>
            <span>مسیر</span>
            <b>{{ $log->directionLabel() }}</b>
        </div>
        <div>
            <span>شناسه در فروشگاه</span>
            <b class="ltr">@fa($log->woo_id ?? '—')</b>
        </div>
    </section>

    <section class="sync-show-grid">
        <div class="sync-show-card">
            <h3>اطلاعات اصلی</h3>
            <div class="sync-show-list">
                <div><span>فروشگاه</span><b>{{ $log->connection?->name ?? '—' }}</b></div>
                <div><span>زمان ثبت</span><b>@jdatetime($log->created_at)</b></div>
                <div><span>آخرین بروزرسانی</span><b>@jdatetime($log->updated_at)</b></div>
                <div><span>شناسه تحویل</span><b class="ltr">{{ $log->delivery_id ?: '—' }}</b></div>
                <div><span>خلاصه</span><b>{{ \Modules\Core\Support\Num::fa($log->payloadSummary()) }}</b></div>
            </div>
        </div>

        <div class="sync-show-card">
            <h3>راهنمای اقدام</h3>
            <div class="sync-suggestion-box">
                {{ \Modules\Core\Support\Num::fa($log->suggestedAction()) }}
            </div>
            <h4>علت خطا</h4>
            <div class="sync-error-box">
                {{ \Modules\Core\Support\Num::fa($log->error ?: '—') }}
            </div>
        </div>
    </section>

    <section class="sync-show-card sync-payload-card">
        <header>
            <div>
                <span>داده ثبت‌شده</span>
                <h3>داده‌ای که برای این همگام‌سازی ذخیره شده است</h3>
            </div>
        </header>
        <pre>{{ $prettyPayload }}</pre>
    </section>
</div>
@endsection