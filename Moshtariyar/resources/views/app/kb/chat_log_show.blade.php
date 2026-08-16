@extends('layouts.app')
@section('title','جزئیات گفت‌وگو')
@section('heading','جزئیات گفت‌وگوی دستیار')
@section('subtitle','بررسی پیام‌های ثبت‌شده و استخراج موضوعات مناسب برای پایگاه دانش')

@section('content')
<link rel="stylesheet" href="{{ asset('css/kb-command-center.css') }}">

<div class="kb-command-page">
    <section class="kb-hero kb-logs-hero">
        <div class="kb-hero-main">
            <span class="kb-eyebrow">گفت‌وگوی شماره @fa($session->id)</span>
            <h2>مسیر سؤال و پاسخ را بررسی کنید و اگر لازم بود مقاله آموزشی بسازید</h2>
            <p>کانال گفت‌وگو: {{ ['club'=>'باشگاه مشتریان','admin'=>'بخش مدیریت','widget'=>'پنجره گفت‌وگوی سایت'][$session->channel] ?? $session->channel }} · آخرین پیام: @jdatetime($session->last_message_at)</p>
            <div class="kb-hero-actions"><a class="btn btn-ghost" href="{{ url('/app/kb/chat-logs') }}">بازگشت به گفت‌وگوها</a><a class="btn" href="{{ url('/app/kb/create') }}">ساخت مقاله مرتبط</a></div>
        </div>
    </section>

    <section class="kb-chat-thread">
        @forelse($session->messages as $message)
            <article class="kb-chat-message {{ $message->sender === 'user' ? 'is-user' : 'is-bot' }}">
                <div class="kb-chat-avatar">{{ $message->sender === 'user' ? 'ک' : 'د' }}</div>
                <div class="kb-chat-body">
                    <div class="kb-chat-top"><b>{{ $message->sender === 'user' ? 'کاربر' : 'دستیار' }}</b><span>@jdatetime($message->created_at)</span></div>
                    <p>{{ $message->message }}</p>
                    @if($message->needs_ticket)<span class="kb-badge is-warn">نیازمند تیکت</span>@endif
                </div>
            </article>
        @empty
            <div class="kb-empty">پیامی برای این نشست ثبت نشده است.</div>
        @endforelse
    </section>
</div>
@endsection