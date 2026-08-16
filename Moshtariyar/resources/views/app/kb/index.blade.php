@extends('layouts.app')
@section('title','مرکز دانش هوشمند')
@section('heading','مرکز دانش و آموزش هوشمند')
@section('subtitle','مدیریت مقاله‌ها، پرسش‌های پرتکرار، آموزش داخلی و منابع پاسخ‌گویی دستیار')

@section('content')
<link rel="stylesheet" href="{{ asset('css/kb-command-center.css') }}">

@php
    $boardMeta = [
        'published' => ['label' => 'منتشرشده عمومی', 'color' => '#10b981', 'hint' => 'قابل نمایش برای مشتریان و باشگاه'],
        'internal' => ['label' => 'داخلی همکاران', 'color' => '#2563eb', 'hint' => 'دانش مخصوص تیم و مدیریت'],
        'review' => ['label' => 'نیازمند بازبینی', 'color' => '#f59e0b', 'hint' => 'پرسش یا پاسخ کوتاه و ناقص'],
        'popular' => ['label' => 'پرتکرار و پربازدید', 'color' => '#8b5cf6', 'hint' => 'مناسب برای پیشنهاد به دستیار'],
        'draft' => ['label' => 'پیش‌نویس یا غیرفعال', 'color' => '#64748b', 'hint' => 'هنوز آماده انتشار نیست'],
    ];
@endphp

<div class="kb-command-page">
    <section class="kb-hero">
        <div class="kb-hero-main">
            <span class="kb-eyebrow">مرکز دانش سازمانی</span>
            <h2>دانش پشتیبانی، فروش و باشگاه مشتریان را تبدیل به پاسخ سریع دستیار کنید</h2>
            <p>هر مقاله می‌تواند به مشتری، کارشناس و دستیار کمک کند. مقاله‌های عمومی برای باشگاه مشتریان و مقاله‌های داخلی برای تیم مدیریت و پشتیبانی استفاده می‌شوند.</p>
            <div class="kb-hero-actions">
                <a class="btn" href="{{ url('/app/kb/create') }}">افزودن مقاله</a>
                <a class="btn btn-ghost" href="{{ url('/app/kb/categories') }}">دسته‌بندی‌ها</a>
                <a class="btn btn-ghost" href="{{ url('/app/kb/chat-logs') }}">گفت‌وگوهای دستیار</a>
            </div>
        </div>
        <div class="kb-hero-metrics">
            <a class="kb-metric-card" href="{{ url('/app/kb') }}" style="--metric-color:#2563eb;"><span>کل مقاله‌ها</span><strong>@fa(number_format($summary['total'] ?? 0))</strong><small>دانش ثبت‌شده</small></a>
            <a class="kb-metric-card" href="{{ url('/app/kb?visibility=public&status=active') }}" style="--metric-color:#10b981;"><span>عمومی فعال</span><strong>@fa(number_format($summary['public'] ?? 0))</strong><small>قابل نمایش برای مشتری</small></a>
            <a class="kb-metric-card" href="{{ url('/app/kb?status=review') }}" style="--metric-color:#f59e0b;"><span>نیازمند بازبینی</span><strong>@fa(number_format($summary['review'] ?? 0))</strong><small>برای تکمیل محتوا</small></a>
            <a class="kb-metric-card" href="{{ url('/app/kb?status=popular') }}" style="--metric-color:#8b5cf6;"><span>پربازدید</span><strong>@fa(number_format($summary['popular'] ?? 0))</strong><small>مناسب پیشنهاد دستیار</small></a>
        </div>
    </section>

    <section class="kb-toolbar">
        <form method="get" class="kb-filter-form">
            <div class="kb-search-field"><label>جستجو</label><input name="search" value="{{ request('search') }}" placeholder="عنوان، پرسش، پاسخ یا برچسب"></div>
            <div><label>دسته</label><select name="category_id"><option value="">همه دسته‌ها</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->title }}</option>@endforeach</select></div>
            <div><label>نمایش</label><select name="visibility"><option value="">همه</option><option value="public" @selected(request('visibility') === 'public')>عمومی</option><option value="staff" @selected(request('visibility') === 'staff')>داخلی</option></select></div>
            <div><label>وضعیت</label><select name="status"><option value="">همه</option><option value="active" @selected(request('status') === 'active')>فعال</option><option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option><option value="review" @selected(request('status') === 'review')>نیازمند بازبینی</option><option value="popular" @selected(request('status') === 'popular')>پربازدید</option></select></div>
            <div class="kb-toolbar-actions"><button class="btn">اعمال فیلتر</button>@if(request()->hasAny(['search','category_id','visibility','status']))<a class="btn btn-ghost" href="{{ url('/app/kb') }}">حذف فیلتر</a>@endif</div>
        </form>
    </section>

    <section class="kb-insight-strip">
        <div><span class="kb-dot is-blue"></span><b>@fa(number_format($summary['views'] ?? 0))</b><small>کل بازدید مقاله‌ها</small></div>
        <div><span class="kb-dot is-ok"></span><b>@fa(number_format($summary['categories'] ?? 0))</b><small>دسته‌بندی دانش</small></div>
        <div><span class="kb-dot is-purple"></span><b>@fa(number_format($summary['staff'] ?? 0))</b><small>مقاله داخلی فعال</small></div>
        <div><span class="kb-dot is-warn"></span><b>@fa(number_format($summary['inactive'] ?? 0))</b><small>غیرفعال یا پیش‌نویس</small></div>
    </section>

    <section class="kb-board-shell">
        <div class="kb-board">
            @foreach($boardMeta as $key => $meta)
                @php $items = $boardGroups->get($key, collect()); @endphp
                <section class="kb-column" style="--kb-stage-color:{{ $meta['color'] }};">
                    <header class="kb-column-header"><div><span></span><h3>{{ $meta['label'] }}</h3></div><b>@fa($items->count())</b></header>
                    <small>{{ $meta['hint'] }}</small>
                    <div class="kb-column-body">
                        @forelse($items as $article)
                            <article class="kb-article-card">
                                <div class="kb-article-top"><h4>{{ $article->title }}</h4><span>{{ $article->visibility === 'public' ? 'عمومی' : 'داخلی' }}</span></div>
                                <p>{{ $article->question ?: \Illuminate\Support\Str::limit(strip_tags($article->answer), 95) }}</p>
                                <div class="kb-article-meta"><span>{{ $article->category?->title ?? 'بدون دسته' }}</span><span>@fa(number_format($article->views ?? 0)) بازدید</span><span>{{ $article->is_active ? 'فعال' : 'غیرفعال' }}</span></div>
                                <div class="kb-article-actions"><a href="{{ url('/app/kb/'.$article->id.'/edit') }}">ویرایش</a><form method="post" action="{{ url('/app/kb/'.$article->id) }}" onsubmit="return confirm('مقاله حذف شود؟')">@csrf @method('DELETE')<button>حذف</button></form></div>
                            </article>
                        @empty
                            <div class="kb-empty">مقاله‌ای در این بخش نیست.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>

    <section class="kb-list-card is-accent" style="--card-color:#2563eb;">
        <header><div><span>فهرست کامل مقاله‌ها</span><h2>مقاله‌ها و پرسش‌های ثبت‌شده</h2></div><a class="kb-link" href="{{ url('/app/kb/create') }}">مقاله جدید</a></header>
        <div class="kb-table-wrap">
            <table>
                <thead><tr><th>عنوان</th><th>دسته</th><th>نمایش</th><th>بازدید</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td><b>{{ $article->title }}</b><small>{{ $article->question ?: \Illuminate\Support\Str::limit(strip_tags($article->answer), 90) }}</small></td>
                        <td>{{ $article->category?->title ?? '—' }}</td>
                        <td>{{ $article->visibility === 'public' ? 'عمومی' : 'داخلی' }}</td>
                        <td>@fa(number_format($article->views ?? 0))</td>
                        <td><span class="kb-badge {{ $article->is_active ? 'is-ok' : 'is-muted' }}">{{ $article->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                        <td class="kb-table-actions"><a href="{{ url('/app/kb/'.$article->id.'/edit') }}">ویرایش</a><form method="post" action="{{ url('/app/kb/'.$article->id) }}" onsubmit="return confirm('حذف شود؟')">@csrf @method('DELETE')<button>حذف</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6">مقاله‌ای ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="kb-pagination">{{ $articles->links() }}</div>
    </section>
</div>
@endsection