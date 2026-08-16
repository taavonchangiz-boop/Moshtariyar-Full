@extends('layouts.customer_portal')
@section('title','راهنما و سوالات پرتکرار')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-support.css') }}">
<div class="support-kb-page">
    <section class="support-hero">
        <div>
            <span class="support-eyebrow">پایگاه دانش باشگاه</span>
            <h1>پاسخ سریع قبل از ثبت درخواست 📚</h1>
            <p>راهنماها، سوالات پرتکرار و آموزش‌های باشگاه را جستجو کنید. اگر پاسخ کافی نبود، از همین مسیر درخواست پشتیبانی ثبت کنید.</p>
            <form method="get" class="support-kb-search">
                <input name="search" value="{{ request('search') }}" placeholder="جستجو در راهنما، کد تخفیف، امتیاز، سفارش...">
            </form>
        </div>
        <div class="support-score-grid">
            <div><span>دسته‌های راهنما</span><b>@fa(number_format($categories->count()))</b><small>موضوعات آموزشی</small></div>
            <div><span>نتایج جستجو</span><b>@fa(number_format(($articles ?? collect())->count()))</b><small>بر اساس عبارت واردشده</small></div>
            <div><span>نیاز به کمک بیشتر؟</span><b>پشتیبانی</b><small>درخواست جدید ثبت کنید</small></div>
        </div>
    </section>

    @if(request('search'))
        <section class="support-card" style="margin-bottom:1rem">
            <header>
                <div>
                    <span>نتایج جستجو</span>
                    <h2>مطالب پیشنهادی</h2>
                </div>
                <a class="btn btn-ghost" href="{{ route('club.kb') }}">پاک کردن جستجو</a>
            </header>
            <div class="support-kb-list">
                @forelse($articles as $article)
                    <a class="support-kb-row" style="--support-row-color:#0ea5e9; text-decoration:none" href="{{ route('club.kb.show', $article) }}">
                        <div class="support-kb-icon">❔</div>
                        <div>
                            <h3>{{ $article->title }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($article->answer), 140) }}</p>
                        </div>
                        <span class="support-badge">مشاهده</span>
                    </a>
                @empty
                    <div class="support-empty">نتیجه‌ای یافت نشد. می‌توانید از دستیار بپرسید یا درخواست پشتیبانی ثبت کنید.</div>
                @endforelse
            </div>
        </section>
    @endif

    <section class="support-kb-category-grid">
        @forelse($categories as $category)
            <article class="support-card is-accent" style="--support-card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>دسته راهنما</span>
                        <h2>{{ $category->title }}</h2>
                    </div>
                    <span class="support-badge">@fa(number_format($category->articles->count())) مقاله</span>
                </header>
                <p>{{ $category->description }}</p>
                <div class="support-kb-list" style="margin-top:.85rem">
                    @forelse($category->articles as $article)
                        <a class="support-kb-row" style="--support-row-color:#8b5cf6; text-decoration:none" href="{{ route('club.kb.show', $article) }}">
                            <div class="support-kb-icon">📄</div>
                            <div><h3>{{ $article->title }}</h3><small>برای مشاهده پاسخ کامل کلیک کنید.</small></div>
                            <span class="support-badge is-muted">بازکردن</span>
                        </a>
                    @empty
                        <div class="support-empty">موردی ثبت نشده است.</div>
                    @endforelse
                </div>
            </article>
        @empty
            <div class="support-card"><div class="support-empty">هنوز راهنمایی ثبت نشده است.</div></div>
        @endforelse
    </section>

    <section class="support-card" style="margin-top:1rem">
        <header>
            <div>
                <span>هنوز نیاز به کمک دارید؟</span>
                <h2>ارتباط مستقیم با پشتیبانی</h2>
            </div>
        </header>
        <div class="support-actions-row">
            <a class="btn" href="{{ route('club.tickets') }}">ثبت درخواست پشتیبانی</a>
            <a class="btn btn-ghost" href="{{ route('club.assistant') }}">پرسیدن از دستیار</a>
        </div>
    </section>
</div>
@endsection