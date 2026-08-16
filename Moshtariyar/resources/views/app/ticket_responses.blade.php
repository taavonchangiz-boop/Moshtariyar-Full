@extends('layouts.app')
@section('title','پاسخ‌های آماده')
@section('heading','کتابخانه پاسخ‌های آماده تیکت')
@section('subtitle','متن‌های آماده برای پاسخ سریع، دقیق و یکدست به تیکت‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tickets-board.css') }}">

@php
    $activeCount = $responses->getCollection()->where('is_active', true)->count();
    $inactiveCount = $responses->getCollection()->where('is_active', false)->count();
    $groupedResponses = $responses->getCollection()->groupBy(function ($r) {
        return $r->department ?: 'عمومی';
    });
@endphp

<div class="tickets-page">

    {{-- هدر --}}
    <section class="tickets-hero">
        <div>
            <span class="tickets-eyebrow">کتابخانه پاسخ‌های آماده</span>
            <h2>پاسخ‌های آماده، سرعت بیشتر و تجربه یکدست‌تر برای مشتری</h2>
            <p>برای هر دپارتمان متن‌های آماده بساز تا کارشناسان با لحن حرفه‌ای، بدون دوباره‌کاری و با خطای کمتر به مشتری پاسخ دهند.</p>
            <div class="tickets-hero-actions">
                <a class="btn" href="#newResponseForm">افزودن پاسخ آماده</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets') }}">← بازگشت به بورد</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/sla') }}">گزارش مهلت پاسخ</a>
            </div>
        </div>
        <div class="tickets-score">
            <a href="{{ url('/app/tickets/responses') }}" style="--metric-color:#3b82f6;">
                <span>کل پاسخ‌ها</span><b>@fa(number_format($responses->total()))</b><small>ثبت‌شده</small>
            </a>
            <div style="--metric-color:#10b981;">
                <span>فعال</span><b>@fa(number_format($activeCount))</b><small>قابل استفاده</small>
            </div>
            <div style="--metric-color:#64748b;">
                <span>غیرفعال</span><b>@fa(number_format($inactiveCount))</b><small>مخفی از کارشناس</small>
            </div>
            <div style="--metric-color:#f59e0b;">
                <span>دپارتمان</span><b>@fa($groupedResponses->count())</b><small>گروه پاسخ</small>
            </div>
        </div>
    </section>

    <section class="tickets-layout">

        <div class="tickets-main">

            {{-- فهرست پاسخ‌های آماده گروه‌بندی‌شده بر اساس دپارتمان --}}
            <article class="tickets-card is-accent" style="--card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>فهرست پاسخ‌های ثبت‌شده</span>
                        <h2>پاسخ‌های آماده به تفکیک دپارتمان</h2>
                        <p>هر پاسخ را می‌توان فعال/غیرفعال کرد یا حذف کرد. کارشناسان از فرم پاسخ تیکت به این متن‌ها دسترسی دارند.</p>
                    </div>
                    <span class="tickets-pill is-status-answered">@fa(number_format($responses->total())) مورد</span>
                </header>

                <div class="tickets-board">
                    @foreach($groupedResponses as $department => $group)
                        @php
                            $deptColor = match(true) {
                                str_contains($department, 'فنی') => '#3b82f6',
                                str_contains($department, 'مالی') => '#f59e0b',
                                str_contains($department, 'فروش') => '#10b981',
                                str_contains($department, 'پشتیبانی') => '#8b5cf6',
                                default => '#64748b',
                            };
                            $isOpen = $loop->first;
                        @endphp

                        <div class="tickets-group {{ $isOpen ? 'is-open' : 'is-collapsed' }}" style="--group-color:{{ $deptColor }}; --group-ratio:{{ $responses->total() > 0 ? round(($group->count() / $responses->total()) * 100) : 0 }}%;">
                            <button type="button" class="tickets-group-title" onclick="toggleResponseGroup(this)">
                                <span class="tg-caret">◀</span>
                                <span class="tg-mark"></span>
                                <span class="tg-title-block">
                                    <b>{{ $department }}</b>
                                    <small>پاسخ‌های آماده دپارتمان {{ $department }}</small>
                                </span>
                                <span class="tg-count">@fa($group->count())</span>
                                <span class="tg-progress"></span>
                                <span class="tg-ratio">@fa($responses->total() > 0 ? round(($group->count() / $responses->total()) * 100) : 0)٪</span>
                            </button>

                            <div class="tickets-group-summary">
                                <span>تعداد: <b>@fa($group->count())</b></span>
                                <span>فعال: <b>@fa($group->where('is_active', true)->count())</b></span>
                                <span>غیرفعال: <b>@fa($group->where('is_active', false)->count())</b></span>
                            </div>

                            <div class="response-list-wrap">
                                @foreach($group as $response)
                                    <details class="response-item {{ $response->is_active ? '' : 'is-inactive' }}">
                                        <summary class="response-item-head">
                                            <span class="response-caret">◀</span>
                                            <div class="response-item-title">
                                                <b>{{ $response->title }}</b>
                                                <small>{{ mb_substr(strip_tags($response->body), 0, 90) }}{{ mb_strlen(strip_tags($response->body)) > 90 ? '...' : '' }}</small>
                                            </div>
                                            <span class="tickets-pill {{ $response->is_active ? 'is-status-answered' : 'is-status-closed' }}">
                                                {{ $response->is_active ? 'فعال' : 'غیرفعال' }}
                                            </span>
                                        </summary>

                                        <div class="response-item-body">
                                            <div class="response-full-text">{!! nl2br(e($response->body)) !!}</div>

                                            <div class="response-item-actions">
                                                <form method="post" action="{{ url('/app/tickets/responses/'.$response->id.'/toggle') }}">
                                                    @csrf
                                                    <button type="submit">
                                                        {{ $response->is_active ? 'غیرفعال کن' : 'فعال کن' }}
                                                    </button>
                                                </form>
                                                <form method="post" action="{{ url('/app/tickets/responses/'.$response->id) }}" onsubmit="return confirm('این پاسخ حذف شود؟')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="is-danger">حذف</button>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if($responses->isEmpty())
                        <div class="tickets-group-empty">پاسخ آماده‌ای ثبت نشده است. از فرم زیر اولین پاسخ را بساز.</div>
                    @endif
                </div>

                @if($responses->hasPages())
                    <div style="margin-top:1rem;">{{ $responses->links() }}</div>
                @endif
            </article>

        </div>

        {{-- بخش زیر: راهنما + فرم افزودن پاسخ جدید --}}
        <div class="tickets-below">

            <article class="tickets-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>راهنمای پاسخگویی حرفه‌ای</span>
                        <h2>اصول ساخت پاسخ‌های آماده اثرگذار</h2>
                    </div>
                </header>

                <div class="response-guide-list">
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(16, 185, 129, 0.14); color:#047857;">۱</div>
                        <div>
                            <b>لحن یکسان و حرفه‌ای</b>
                            <small>پاسخ‌های آماده باعث می‌شوند همه کارشناسان با ادبیات هماهنگ و محترمانه پاسخ دهند.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(59, 130, 246, 0.14); color:#1d4ed8;">۲</div>
                        <div>
                            <b>سرعت بیشتر در پاسخ</b>
                            <small>کارشناس متن آماده را انتخاب می‌کند و فقط بخش‌های شخصی را ویرایش می‌کند - صرفه‌جویی در وقت.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(139, 92, 246, 0.14); color:#6d28d9;">۳</div>
                        <div>
                            <b>تجربه بهتر مشتری</b>
                            <small>پاسخ واضح، کوتاه و دارای قدم بعدی، پیگیری مشتری را ساده و رضایت او را زیاد می‌کند.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(245, 158, 11, 0.14); color:#b45309;">۴</div>
                        <div>
                            <b>گروه‌بندی بر اساس دپارتمان</b>
                            <small>پاسخ‌ها را بر اساس دپارتمان دسته‌بندی کن تا کارشناس هر بخش به مواردی که برایش مرتبط است دسترسی سریع داشته باشد.</small>
                        </div>
                    </div>
                </div>
            </article>

            <article class="tickets-card is-accent" style="--card-color:#0ea5e9;" id="newResponseForm">
                <header>
                    <div>
                        <span>افزودن پاسخ آماده</span>
                        <h2>ساخت متن پاسخ جدید</h2>
                    </div>
                </header>

                <form method="post" action="{{ url('/app/tickets/responses') }}" class="ticket-detail-reply-form">
                    @csrf

                    <div class="ticket-detail-field">
                        <label>عنوان کوتاه</label>
                        <input name="title" required placeholder="مثلاً: در حال بررسی">
                    </div>

                    <div class="ticket-detail-field">
                        <label>دپارتمان</label>
                        <select name="department">
                            <option value="">عمومی (همه دپارتمان‌ها)</option>
                            @if(!empty($departments) && (is_array($departments) ? count($departments) : $departments->count()) > 0)
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            @else
                                <option value="پشتیبانی">پشتیبانی</option>
                                <option value="فنی">فنی</option>
                                <option value="مالی">مالی</option>
                                <option value="فروش">فروش</option>
                            @endif
                        </select>
                    </div>

                    <div class="ticket-detail-field">
                        <label>متن پاسخ</label>
                        <textarea name="body" rows="8" required placeholder="متن پاسخ آماده را دقیق، محترمانه و قابل شخصی‌سازی بنویس. برای شخصی‌سازی می‌توانی از متغیرهایی مثل {نام} استفاده کنی."></textarea>
                        <small>نکته: از متغیرهایی مثل {نام}، {موضوع}، {شماره_تیکت} برای شخصی‌سازی خودکار استفاده کن.</small>
                    </div>

                    <button type="submit" class="btn">ثبت پاسخ آماده</button>
                </form>
            </article>

        </div>
    </section>
</div>

<script>
function toggleResponseGroup(btn) {
    const group = btn.closest('.tickets-group');
    if (!group) return;
    group.classList.toggle('is-collapsed');
    group.classList.toggle('is-open');
}
</script>
@endsection