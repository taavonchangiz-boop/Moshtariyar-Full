@extends('layouts.app')
@section('title','گفت‌وگوهای دستیار')
@section('heading','گفت‌وگوهای دستیار مشتری‌یار')
@section('subtitle','مرکز بررسی سؤال‌های کاربران، مشتریان و مدیران از دستیار')

@section('content')
<link rel="stylesheet" href="{{ asset('css/kb-command-center.css') }}">

<div class="kb-command-page">
    <section class="kb-hero kb-logs-hero">
        <div class="kb-hero-main">
            <span class="kb-eyebrow">لاگ گفت‌وگوها</span>
            <h2>سؤال‌های پرتکرار را ببینید و از آن‌ها برای تکمیل پایگاه دانش استفاده کنید</h2>
            <p>گفت‌وگوهای دستیار نشان می‌دهند مشتریان و مدیران چه چیزهایی می‌پرسند. سؤال‌های پرتکرار باید تبدیل به مقاله‌های دقیق در پایگاه دانش شوند.</p>
            <div class="kb-hero-actions"><a class="btn btn-ghost" href="{{ url('/app/kb') }}">مرکز دانش</a><a class="btn" href="{{ url('/app/kb/create') }}">ساخت مقاله از سؤال پرتکرار</a></div>
        </div>
        <div class="kb-hero-metrics">
            <div class="kb-metric-card" style="--metric-color:#2563eb;"><span>نشست‌های این صفحه</span><strong>@fa(number_format($sessions->count()))</strong><small>در نمای فعلی</small></div>
            <div class="kb-metric-card" style="--metric-color:#8b5cf6;"><span>کل نشست‌ها</span><strong>@fa(number_format($sessions->total()))</strong><small>ثبت‌شده در سامانه</small></div>
        </div>
    </section>

    <section class="kb-list-card is-accent" style="--card-color:#2563eb;">
        <header><div><span>فهرست نشست‌ها</span><h2>گفت‌وگوهای ثبت‌شده دستیار</h2></div></header>
        <div class="kb-table-wrap">
            <table>
                <thead><tr><th>شناسه</th><th>مشتری</th><th>راه ارتباطی</th><th>تعداد پیام</th><th>آخرین پیام</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>@fa($session->id)</td>
                        <td>@fa($session->customer_id ?? '—')</td>
                        <td>{{ ['club'=>'باشگاه مشتریان','admin'=>'بخش مدیریت','widget'=>'پنجره گفت‌وگوی سایت'][$session->channel] ?? $session->channel }}</td>
                        <td>@fa($session->messages_count)</td>
                        <td>@jdatetime($session->last_message_at)</td>
                        <td class="kb-table-actions"><a href="{{ url('/app/kb/chat-logs/'.$session->id) }}">مشاهده</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">گفت‌وگویی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="kb-pagination">{{ $sessions->links() }}</div>
    </section>
</div>
@endsection