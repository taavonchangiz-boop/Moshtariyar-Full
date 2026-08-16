@extends('layouts.app')
@section('title',$article->exists ? 'ویرایش مقاله دانش' : 'افزودن مقاله دانش')
@section('heading',$article->exists ? 'ویرایش مقاله پایگاه دانش' : 'افزودن مقاله پایگاه دانش')
@section('subtitle','ثبت دانش قابل استفاده برای پشتیبانی، مشتریان، باشگاه و دستیار هوشمند')

@section('content')
<link rel="stylesheet" href="{{ asset('css/kb-command-center.css') }}">

<div class="kb-command-page">
    <section class="kb-hero kb-form-hero">
        <div class="kb-hero-main">
            <span class="kb-eyebrow">{{ $article->exists ? 'ویرایش دانش' : 'دانش جدید' }}</span>
            <h2>{{ $article->exists ? 'مقاله را کامل، خوانا و قابل استفاده برای دستیار نگه دارید' : 'یک منبع دانش دقیق برای پاسخ‌گویی سریع بسازید' }}</h2>
            <p>عنوان روشن، پرسش مشخص، پاسخ کامل و برچسب‌های درست باعث می‌شوند دستیار در زمان پاسخ‌گویی، مقاله مناسب را سریع‌تر پیدا کند.</p>
            <div class="kb-hero-actions"><a class="btn btn-ghost" href="{{ url('/app/kb') }}">بازگشت به مرکز دانش</a><a class="btn btn-ghost" href="{{ url('/app/kb/categories') }}">مدیریت دسته‌ها</a></div>
        </div>
        <div class="kb-writing-guide">
            <div><b>۱</b><span>عنوان کوتاه و دقیق بنویسید.</span></div>
            <div><b>۲</b><span>پرسش پرتکرار را مثل سؤال مشتری ثبت کنید.</span></div>
            <div><b>۳</b><span>پاسخ را مرحله‌ای، واضح و قابل اجرا بنویسید.</span></div>
            <div><b>۴</b><span>برچسب‌ها را برای جستجوی دستیار کامل کنید.</span></div>
        </div>
    </section>

    <section class="kb-form-layout">
        <article class="kb-list-card is-accent" style="--card-color:#2563eb;">
            <header><div><span>فرم مقاله</span><h2>{{ $article->exists ? 'ویرایش محتوا' : 'ثبت محتوای جدید' }}</h2></div></header>
            <form method="post" action="{{ $article->exists ? url('/app/kb/'.$article->id) : url('/app/kb') }}" class="kb-article-form">
                @csrf
                @if($article->exists) @method('PUT') @endif
                <div class="kb-form-grid">
                    <div><label>عنوان</label><input name="title" value="{{ old('title',$article->title) }}" required placeholder="مثلاً راهنمای پیگیری سفارش"></div>
                    <div><label>نام کوتاه در نشانی</label><input name="slug" class="ltr" value="{{ old('slug',$article->slug) }}" placeholder="اختیاری"></div>
                    <div><label>دسته</label><select name="category_id"><option value="">بدون دسته</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id',$article->category_id)==$category->id)>{{ $category->title }}</option>@endforeach</select></div>
                    <div><label>نوع نمایش</label><select name="visibility"><option value="public" @selected(old('visibility',$article->visibility ?: 'public')==='public')>عمومی برای مشتری</option><option value="staff" @selected(old('visibility',$article->visibility)==='staff')>داخلی برای همکاران</option></select></div>
                </div>
                <div><label>پرسش، اختیاری</label><input name="question" value="{{ old('question',$article->question) }}" placeholder="مثلاً چطور سفارش خود را پیگیری کنم؟"></div>
                <div><label>پاسخ یا متن دانش</label><textarea name="answer" rows="12" required placeholder="پاسخ را واضح، مرحله‌ای و قابل اجرا بنویسید...">{{ old('answer',$article->answer) }}</textarea></div>
                <div><label>برچسب‌ها، با ویرگول جدا کنید</label><input name="tags_text" value="{{ old('tags_text', implode(',', $article->tags ?? [])) }}" placeholder="سفارش، پیگیری، پشتیبانی"></div>
                <label class="kb-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$article->is_active ?? true))><span>مقاله فعال باشد</span></label>
                <div class="kb-form-actions"><button class="btn">ذخیره مقاله</button><a class="btn btn-ghost" href="{{ url('/app/kb') }}">انصراف</a></div>
            </form>
        </article>

        <aside class="kb-list-card is-accent" style="--card-color:#10b981;">
            <header><div><span>راهنمای کیفیت</span><h2>چک‌لیست مقاله مناسب دستیار</h2></div></header>
            <div class="kb-quality-list">
                <div><b>پرسش واقعی</b><p>پرسش را شبیه چیزی بنویسید که مشتری یا کارشناس واقعاً می‌پرسد.</p></div>
                <div><b>پاسخ مرحله‌ای</b><p>اگر پاسخ شامل چند قدم است، آن را با ترتیب روشن توضیح دهید.</p></div>
                <div><b>دامنه کاربرد</b><p>مشخص کنید مقاله برای مشتری عمومی است یا فقط همکاران باید آن را ببینند.</p></div>
                <div><b>برچسب کاربردی</b><p>برچسب‌های کوتاه باعث می‌شوند دستیار سریع‌تر محتوای درست را پیدا کند.</p></div>
            </div>
        </aside>
    </section>
</div>
@endsection