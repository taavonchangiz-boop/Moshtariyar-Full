@extends('layouts.app')
@section('title','تنظیمات ظاهر ویجت')
@section('heading','تنظیمات ظاهر ویجت دستیار')
@section('subtitle','مدیریت رنگ، لوگو، عنوان، پیام خوش‌آمد و متن کنار آیکون')

@section('content')
<link rel="stylesheet" href="{{ asset('css/assistant-settings-pro.css') }}">

<div class="assistant-settings-page">
    @if(session('success'))
        <div class="assistant-alert is-ok">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="assistant-alert is-bad">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="assistant-settings-hero">
        <div class="assistant-settings-hero-main">
            <span class="assistant-settings-eyebrow">ظاهر ویجت دستیار</span>
            <h2>ویجت گفت‌وگو باید خوانا، هماهنگ با برند و قابل اعتماد باشد</h2>
            <p>در این صفحه ظاهر ویجت عمومی دستیار را تنظیم می‌کنید. اگر لوگو بارگذاری شود، تصویر به فرمت webp بهینه تبدیل می‌شود تا سرعت و کیفیت حفظ شود.</p>
            <div class="assistant-settings-actions">
                <a class="btn" href="{{ url('/app/assistant') }}">مشاهده دستیار</a>
                <a class="btn btn-ghost" href="{{ url('/app/assistant/settings') }}">تنظیمات مغز</a>
                <a class="btn btn-ghost" href="{{ url('/widget/assistant.js') }}" target="_blank">اسکریپت ویجت</a>
            </div>
        </div>
        <div class="assistant-widget-preview-card">
            <span>پیش‌نمایش زنده</span>
            <div class="assistant-widget-preview-box" style="--preview-color: {{ $data['color'] }};">
                <div class="assistant-widget-bubble">
                    @if($data['logo'])
                        <img src="{{ $data['logo'] }}" alt="لوگوی ویجت">
                    @else
                        <b>🤖</b>
                    @endif
                </div>
                @if((string) $data['label_enabled'] === '1')
                    <div class="assistant-widget-label">{{ $data['label'] }}</div>
                @endif
            </div>
            <p>{{ $data['welcome'] }}</p>
        </div>
    </section>

    <section class="assistant-settings-layout">
        <article class="assistant-settings-card is-accent" style="--card-color:#2563eb;">
            <header><div><span>فرم تنظیمات</span><h2>اطلاعات ظاهری ویجت</h2></div></header>
            <form action="{{ route('assistant.widget.save') }}" method="POST" enctype="multipart/form-data" class="assistant-settings-form">
                @csrf
                <div class="assistant-form-grid">
                    <div><label>عنوان ویجت</label><input type="text" name="title" value="{{ $data['title'] }}" required></div>
                    <div><label>متن کوتاه کنار آیکون</label><input type="text" name="label" value="{{ $data['label'] }}" required></div>
                    <div><label>رنگ اصلی</label><div class="assistant-color-row"><input type="color" name="color" value="{{ $data['color'] }}"><input type="text" value="{{ $data['color'] }}" readonly></div></div>
                    <div><label>نمایش متن کنار آیکون</label><select name="label_enabled"><option value="1" @selected($data['label_enabled'] == '1')>نمایش داده شود</option><option value="0" @selected($data['label_enabled'] == '0')>نمایش داده نشود</option></select></div>
                </div>

                <div class="assistant-logo-section">
                    <div>
                        <label>لوگوی ویجت، اختیاری</label>
                        <input type="file" name="logo_file" id="logo_file" accept="image/*" data-hint="لوگوی مربع؛ تصویر به webp تبدیل و بهینه می‌شود. حداکثر ۱ مگابایت.">
                    </div>
                    <div>
                        <label>یا نشانی لوگو</label>
                        <input type="text" name="logo_url" value="{{ $data['logo'] }}" placeholder="https://...">
                    </div>
                </div>

                <div><label>پیام خوش‌آمد</label><textarea name="welcome" rows="4" required>{{ $data['welcome'] }}</textarea></div>

                <div class="assistant-form-actions"><button class="btn">ذخیره ظاهر ویجت</button></div>
            </form>
        </article>

        <aside class="assistant-settings-side">
            <article class="assistant-settings-card is-accent" style="--card-color:#10b981;">
                <header><div><span>استاندارد تجربه کاربر</span><h2>ویجت باید سریع، روشن و خوانا باشد</h2></div></header>
                <div class="assistant-guide-list">
                    <div><b>رنگ خوانا</b><p>رنگ اصلی باید با متن سفید یا تیره خوانایی کافی داشته باشد.</p></div>
                    <div><b>لوگوی سبک</b><p>لوگو با فرمت webp ذخیره می‌شود تا سرعت بارگذاری حفظ شود.</p></div>
                    <div><b>پیام کوتاه</b><p>پیام خوش‌آمد بهتر است کوتاه، صمیمی و دعوت‌کننده باشد.</p></div>
                    <div><b>متن کنار آیکون</b><p>اگر صفحه شلوغ است، می‌توانید متن کنار آیکون را غیرفعال کنید.</p></div>
                </div>
            </article>
        </aside>
    </section>
</div>
@endsection