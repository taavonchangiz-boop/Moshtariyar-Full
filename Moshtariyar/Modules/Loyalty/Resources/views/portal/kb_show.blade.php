@extends('layouts.customer_portal')
@section('title', $article->title)
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-support.css') }}">
<div class="support-kb-page">
    <section class="support-hero">
        <div>
            <span class="support-eyebrow">مقاله راهنما</span>
            <h1>{{ $article->title }}</h1>
            <p>{{ $article->category?->title }} · بازدید: @fa($article->views)</p>
            <div class="support-hero-actions">
                <a class="btn btn-ghost" href="{{ route('club.kb') }}">بازگشت به راهنما</a>
                <a class="btn" href="{{ route('club.tickets') }}">هنوز نیاز به کمک دارم</a>
            </div>
        </div>
        <div class="support-score-grid">
            <div><span>موضوع</span><b>{{ $article->category?->title ?: 'راهنما' }}</b><small>دسته‌بندی مقاله</small></div>
            <div><span>بازدید</span><b>@fa(number_format($article->views))</b><small>تعداد مشاهده</small></div>
            <div><span>مسیر بعدی</span><b>پشتیبانی</b><small>اگر پاسخ کافی نبود</small></div>
        </div>
    </section>

    @if($article->question)
        <section class="support-card is-accent" style="--support-card-color:#0ea5e9; margin-bottom:1rem">
            <header>
                <div>
                    <span>سوال</span>
                    <h2>صورت مسئله</h2>
                </div>
            </header>
            <p>{{ $article->question }}</p>
        </section>
    @endif

    <section class="support-card">
        <header>
            <div>
                <span>پاسخ کامل</span>
                <h2>راهنمای مرحله‌ای</h2>
            </div>
        </header>
        <div class="support-article-body">{{ $article->answer }}</div>
    </section>

    <section class="support-card" style="margin-top:1rem">
        <header>
            <div>
                <span>اقدام بعدی</span>
                <h2>اگر مشکل حل نشد</h2>
            </div>
        </header>
        <div class="support-actions-row">
            <a class="btn btn-ghost" href="{{ route('club.kb') }}">جستجوی دوباره</a>
            <a class="btn btn-ghost" href="{{ route('club.assistant') }}">پرسیدن از دستیار</a>
            <a class="btn" href="{{ route('club.tickets') }}">ثبت درخواست پشتیبانی</a>
        </div>
    </section>
</div>
@endsection