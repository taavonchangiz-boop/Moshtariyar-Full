@extends('layouts.app')
@section('title','دسته‌بندی دانش')
@section('heading','دسته‌بندی پایگاه دانش')
@section('subtitle','مدیریت ساختار محتوای آموزشی و منابع پاسخ‌گویی دستیار')

@section('content')
<link rel="stylesheet" href="{{ asset('css/kb-command-center.css') }}">

<div class="kb-command-page">
    <section class="kb-hero">
        <div class="kb-hero-main">
            <span class="kb-eyebrow">ساختار دانش</span>
            <h2>دسته‌بندی درست، پیدا کردن پاسخ درست را برای تیم و دستیار سریع‌تر می‌کند</h2>
            <p>دسته‌ها کمک می‌کنند مقاله‌ها بر اساس فروش، پشتیبانی، باشگاه مشتریان، مالی، محصولات و ووکامرس مرتب شوند.</p>
            <div class="kb-hero-actions"><a class="btn btn-ghost" href="{{ url('/app/kb') }}">بازگشت به مرکز دانش</a><a class="btn" href="{{ url('/app/kb/create') }}">افزودن مقاله</a></div>
        </div>
        <div class="kb-hero-metrics">
            <div class="kb-metric-card" style="--metric-color:#2563eb;"><span>کل دسته‌ها</span><strong>@fa(number_format($summary['total'] ?? 0))</strong><small>ساختار محتوایی</small></div>
            <div class="kb-metric-card" style="--metric-color:#10b981;"><span>فعال</span><strong>@fa(number_format($summary['active'] ?? 0))</strong><small>قابل استفاده</small></div>
            <div class="kb-metric-card" style="--metric-color:#64748b;"><span>غیرفعال</span><strong>@fa(number_format($summary['inactive'] ?? 0))</strong><small>موقتاً مخفی</small></div>
            <div class="kb-metric-card" style="--metric-color:#8b5cf6;"><span>مقاله‌ها</span><strong>@fa(number_format($summary['articles'] ?? 0))</strong><small>محتوای متصل</small></div>
        </div>
    </section>

    <section class="kb-form-layout">
        <article class="kb-list-card is-accent" style="--card-color:#2563eb;">
            <header><div><span>افزودن دسته</span><h2>ساخت دسته‌بندی جدید</h2></div></header>
            <form method="post" action="{{ url('/app/kb/categories') }}" class="kb-article-form">
                @csrf
                <div><label>عنوان</label><input name="title" required placeholder="مثلاً راهنمای سفارش"></div>
                <div><label>نام کوتاه در نشانی</label><input name="slug" class="ltr" placeholder="اختیاری"></div>
                <div><label>توضیح</label><input name="description" placeholder="این دسته برای چه محتوایی است؟"></div>
                <div><label>ترتیب نمایش</label><input name="order" class="ltr" value="0"></div>
                <button class="btn">ثبت دسته‌بندی</button>
            </form>
        </article>

        <article class="kb-list-card is-accent" style="--card-color:#10b981;">
            <header><div><span>فهرست دسته‌ها</span><h2>دسته‌بندی‌های ثبت‌شده</h2></div></header>
            <div class="kb-category-list">
                @forelse($categories as $category)
                    <article class="kb-category-item">
                        <div><h3>{{ $category->title }}</h3><p>{{ $category->description ?: 'توضیحی ثبت نشده است.' }}</p><small>نشانی: {{ $category->slug }} · ترتیب: @fa($category->order ?? 0)</small></div>
                        <div class="kb-category-actions"><span>@fa($category->articles_count) مقاله</span><form method="post" action="{{ url('/app/kb/categories/'.$category->id.'/toggle') }}">@csrf<button class="kb-badge {{ $category->is_active ? 'is-ok' : 'is-muted' }}">{{ $category->is_active ? 'فعال' : 'غیرفعال' }}</button></form></div>
                    </article>
                @empty
                    <div class="kb-empty">دسته‌بندی ثبت نشده است.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection