@extends('layouts.app')
@section('title','ثبت تیکت جدید')
@section('heading','ثبت تیکت پشتیبانی جدید')
@section('subtitle','ثبت درخواست پشتیبانی برای مشتری یا پیگیری داخلی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tickets-board.css') }}">

<div class="tickets-page">

    {{-- هدر --}}
    <section class="tickets-hero">
        <div>
            <span class="tickets-eyebrow">ثبت تیکت جدید</span>
            <h2>درخواست پشتیبانی را با اطلاعات کامل و قابل پیگیری ثبت کن</h2>
            <p>با انتخاب مشتری، اولویت و دپارتمان مناسب، تیکت از همان ابتدا در مسیر درست قرار می‌گیرد و زمان پاسخگویی دقیق‌تر محاسبه می‌شود.</p>
            <div class="tickets-hero-actions">
                <a class="btn btn-ghost" href="{{ url('/app/tickets') }}">← بازگشت به بورد تیکت‌ها</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/responses') }}">پاسخ‌های آماده</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/sla') }}">قوانین مهلت پاسخ</a>
            </div>
        </div>
        <div class="tickets-score">
            <div style="--metric-color:#ef4444;">
                <span>اولویت فوری</span><b>۲ ساعت</b><small>مهلت پاسخ اولیه</small>
            </div>
            <div style="--metric-color:#f59e0b;">
                <span>اولویت زیاد</span><b>۶ ساعت</b><small>مهلت پاسخ اولیه</small>
            </div>
            <div style="--metric-color:#3b82f6;">
                <span>اولویت عادی</span><b>۲۴ ساعت</b><small>مهلت پاسخ اولیه</small>
            </div>
            <div style="--metric-color:#94a3b8;">
                <span>اولویت کم</span><b>۴۸ ساعت</b><small>مهلت پاسخ اولیه</small>
            </div>
        </div>
    </section>

    <section class="tickets-layout">

        {{-- فرم اصلی ثبت تیکت --}}
        <div class="tickets-main">

            <article class="tickets-card is-accent" style="--card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>اطلاعات تیکت جدید</span>
                        <h2>فرم ثبت درخواست پشتیبانی</h2>
                        <p>هرچه اطلاعات دقیق‌تری وارد کنی، پاسخگویی سریع‌تر و مؤثرتر خواهد بود.</p>
                    </div>
                    <span class="tickets-pill is-status-open">جدید</span>
                </header>

                <form method="post" action="{{ url('/app/tickets') }}" enctype="multipart/form-data" class="ticket-detail-reply-form">
                    @csrf

                    <div class="ticket-detail-field">
                        <label>مشتری (اختیاری)</label>
                        <select name="customer_id">
                            <option value="">— بدون مشتری (تیکت داخلی) —</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->full_name }}
                                    @if($c->phone) - {{ $c->phone }}@endif
                                </option>
                            @endforeach
                        </select>
                        <small>اگر تیکت برای پیگیری داخلی است، بدون مشتری بگذار.</small>
                    </div>

                    <div class="ticket-detail-field">
                        <label>موضوع تیکت</label>
                        <input name="subject" required placeholder="موضوع را کوتاه، مشخص و گویا بنویس">
                        <small>مثال خوب: «خطای ۵۰۰ در پرداخت درگاه زرین‌پال» — مثال ضعیف: «مشکل دارم»</small>
                    </div>

                    <div class="tickets-form-grid">
                        <div class="ticket-detail-field">
                            <label>اولویت</label>
                            <select name="priority">
                                @if(!empty($priorities))
                                    @foreach($priorities as $k => $v)
                                        <option value="{{ $k }}" @selected($k === 'normal')>{{ $v }}</option>
                                    @endforeach
                                @else
                                    <option value="low">کم</option>
                                    <option value="normal" selected>عادی</option>
                                    <option value="high">زیاد</option>
                                    <option value="urgent">فوری</option>
                                @endif
                            </select>
                        </div>

                        <div class="ticket-detail-field">
                            <label>دپارتمان</label>
                            <select name="department">
                                @if(!empty($departments) && (is_array($departments) ? count($departments) : $departments->count()) > 0)
                                    @foreach($departments as $d)
                                        <option value="{{ $d }}">{{ $d }}</option>
                                    @endforeach
                                @else
                                    <option value="پشتیبانی">پشتیبانی</option>
                                    <option value="فنی">فنی</option>
                                    <option value="مالی">مالی</option>
                                    <option value="فروش">فروش</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="ticket-detail-field">
                        <label>متن پیام</label>
                        <textarea name="message" rows="8" required placeholder="شرح دقیق درخواست، مشکل یا پیگیری را بنویس. مراحل بازتولید، خطای مشاهده‌شده، شماره سفارش و هر جزئیات مرتبط را ذکر کن."></textarea>
                        <small>هرچه دقیق‌تر بنویسی، پاسخ اول کامل‌تر است و رفت‌وبرگشت کمتری لازم می‌شود.</small>
                    </div>

                    <div class="ticket-detail-field">
                        <label>پیوست فایل (اختیاری)</label>
                        <input type="file" name="attachment">
                        <small>تصویر، PDF، Word، Excel، فایل فشرده یا متن — حداکثر ۵ مگابایت. تصویر خطا یا سند مرتبط بسیار کمک‌کننده است.</small>
                    </div>

                    <button type="submit" class="btn">ثبت درخواست پشتیبانی</button>
                </form>
            </article>

        </div>

        {{-- بخش زیر: راهنما + پیشنهاد دستیار --}}
        <div class="tickets-below">

            <article class="tickets-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>راهنمای ثبت بهتر</span>
                        <h2>چه اطلاعاتی بنویسیم که تیکت مؤثر باشد</h2>
                    </div>
                </header>

                <div class="response-guide-list">
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(59, 130, 246, 0.14); color:#1d4ed8;">۱</div>
                        <div>
                            <b>موضوع کوتاه و دقیق</b>
                            <small>موضوع باید در یک نگاه نشان دهد مشکل یا درخواست درباره چیست. از کلمات کلیدی مثل «خطا»، «درخواست»، «سؤال» استفاده کن.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(245, 158, 11, 0.14); color:#b45309;">۲</div>
                        <div>
                            <b>اولویت واقع‌بینانه</b>
                            <small>اولویت را بر اساس فوریت واقعی انتخاب کن. اولویت فوری فقط برای موارد بلاکه‌کننده که کسب‌وکار را متوقف می‌کند.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(139, 92, 246, 0.14); color:#6d28d9;">۳</div>
                        <div>
                            <b>دپارتمان درست</b>
                            <small>دپارتمان مناسب باعث می‌شود تیکت مستقیم به کارشناس مربوطه برسد و در صف بلاتکلیف نماند.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(16, 185, 129, 0.14); color:#047857;">۴</div>
                        <div>
                            <b>متن پیام جامع</b>
                            <small>مراحل بازتولید مشکل، متن دقیق خطا، شماره سفارش یا شناسه مشتری، تاریخ و زمان رخداد را ذکر کن.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(239, 68, 68, 0.14); color:#b91c1c;">۵</div>
                        <div>
                            <b>پیوست کاربردی</b>
                            <small>تصویر صفحه خطا، ویدئوی کوتاه از مشکل یا فایل گزارش می‌تواند زمان تشخیص را نصف کند.</small>
                        </div>
                    </div>
                </div>
            </article>

            <article class="tickets-card is-accent" style="--card-color:#0ea5e9;">
                <header>
                    <div>
                        <span>پیشنهاد دستیار</span>
                        <h2>کمک هوشمند در ساخت تیکت</h2>
                    </div>
                </header>

                <div class="tickets-suggest-list">
                    <button type="button" onclick="askCreateAssistant('برای یک مشتری VIP یک تیکت پیگیری فوری بساز', this)">
                        <b>تیکت پیگیری VIP</b>
                        <small>ساخت تیکت با اولویت بالا برای مشتریان مهم</small>
                    </button>
                    <button type="button" onclick="askCreateAssistant('یک تیکت داخلی برای بررسی مشکل عمومی سایت بساز', this)">
                        <b>تیکت داخلی (بدون مشتری)</b>
                        <small>ثبت پیگیری داخلی یا مشکل زیرساختی</small>
                    </button>
                    <button type="button" onclick="askCreateAssistant('برای تمام مشتریانی که در ۷ روز اخیر تماس گرفتند یک تیکت بازخوردسنجی بساز', this)">
                        <b>تیکت بازخوردسنجی گروهی</b>
                        <small>ثبت تیکت رضایت‌سنجی خودکار</small>
                    </button>
                    <button type="button" onclick="askCreateAssistant('یک تیکت فوری برای پیگیری سفارش رها شده بساز', this)">
                        <b>پیگیری سفارش رها شده</b>
                        <small>تیکت خودکار برای تبدیل به فروش</small>
                    </button>
                </div>

                <div style="margin-top:0.85rem; padding:0.75rem; background:rgba(14, 165, 233, 0.06); border:1px dashed rgba(14, 165, 233, 0.28); border-radius:0.65rem; color:var(--txt); font-size:0.8rem; line-height:1.85;">
                    💡 <b>نکته:</b> با دستیار می‌توانی تیکت را با گفتگو بسازی. دستیار سؤالات لازم را می‌پرسد و پس از تأیید تو، تیکت را ثبت می‌کند.
                </div>
            </article>

        </div>
    </section>

</div>

<script>
function askCreateAssistant(text, btn) {
    text = text || 'یک تیکت جدید بساز';
    if (btn) {
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
        var oh = btn.innerHTML;
        btn.innerHTML = '<b style="color:#b91c1c">در حال باز کردن دستیار...</b><small>لطفاً صبر کنید</small>';
    }
    var url = '{{ url("/app/assistant") }}'
        + '?prompt=' + encodeURIComponent(text)
        + '&context_type=tickets_create'
        + '&context_title=' + encodeURIComponent('ساخت تیکت جدید')
        + '&context_url=' + encodeURIComponent(window.location.href);
    window.location.href = url;
}
</script>
@endsection
