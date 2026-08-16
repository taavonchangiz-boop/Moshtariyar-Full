@extends(request('embed') ? 'layouts.embed' : 'layouts.app')
@section('title','دستیار مدیریت')
@section('heading','دستیار مدیریت مشتری‌یار')
@section('subtitle','همکار هوشمند مدیر برای گزارش‌گیری، پیگیری و اجرای اقدام‌های تأییدشده')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-assistant-pro.css') }}">

@php
    $assistantContext = [
        'type' => request('context_type', 'page'),
        'id' => request('context_id'),
        'title' => request('context_title'),
        'url' => request('context_url'),
    ];
@endphp

@if(request('embed'))
<div class="admin-assistant-page admin-assistant-floating" data-context-type="{{ $assistantContext['type'] }}" data-context-id="{{ $assistantContext['id'] }}" data-context-title="{{ $assistantContext['title'] }}" data-context-url="{{ $assistantContext['url'] }}">
    <article class="assistant-chat-card is-accent" style="--card-color:#2563eb;">
        <header>
            <div>
                <span>گفت‌وگو با دستیار</span>
                <h2>درخواست مدیریتی خود را بنویسید</h2>
            </div>
        </header>

        @if($assistantContext['type'] && $assistantContext['type'] !== 'page')
            <section class="assistant-context-strip is-floating-context">
                <span>زمینه فعال</span>
                <b>{{ $assistantContext['title'] ?: 'رکورد فعلی' }}</b>
            </section>
        @endif

        <div class="assistant-pro-quick-row" aria-label="پرسش‌های سریع">
            <button type="button" onclick="quickAsk('کی هستی؟')">معرفی</button>
            <button type="button" onclick="quickAsk('چکار می‌تونی انجام بدی؟')">توانایی‌ها</button>
            <button type="button" onclick="quickAsk('گزارش جامع وضعیت امروز را بده')">گزارش امروز</button>
            <button type="button" onclick="quickAsk('این مورد را تحلیل کن')">تحلیل این مورد</button>
            <button type="button" onclick="quickAsk('یک کمپین بساز')">ساخت کمپین</button>
        </div>

        <div id="adminChatBox" class="assistant-pro-chat-box">
            <div class="bot-msg">سلام! من دستیار مدیریت مشتری‌یار هستم. سؤال مدیریتی خود را بنویسید یا یک مسئولیت مثل بررسی مشتری، پاسخ به تیکت، ساخت کمپین یا ثبت پیش‌فاکتور به من بدهید.</div>
        </div>

        <form id="adminChatForm" class="assistant-pro-chat-form">
            @csrf
            <input id="adminChatInput" class="chat-text" placeholder="مثلاً: این مشتری را تحلیل کن" autocomplete="off" required>
            <input id="adminChatFile" class="chat-file" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt" data-hint="پیوست گفت‌وگو؛ تصویر، سند، فایل فشرده یا متن؛ حداکثر ۵ مگابایت.">
            <button class="btn">ارسال</button>
        </form>
    </article>
</div>
@else
<div class="admin-assistant-page" data-context-type="{{ $assistantContext['type'] }}" data-context-id="{{ $assistantContext['id'] }}" data-context-title="{{ $assistantContext['title'] }}" data-context-url="{{ $assistantContext['url'] }}">
    <section class="assistant-pro-hero">
        <div class="assistant-pro-hero-main">
            <span class="assistant-pro-eyebrow">همکار هوشمند مدیریت</span>
            <h2>از دستیار بخواهید بررسی کند، سؤال تکمیلی بپرسد و بعد از تأیید شما اقدام کند</h2>
            <p>دستیار مدیریت می‌تواند وضعیت فروش، مشتریان، سفارش‌ها، تیکت‌ها، باشگاه مشتریان، محصولات، پیش‌فاکتورها و پایگاه دانش را بررسی کند. برای کارهای حساس، اول پیش‌نمایش می‌دهد و فقط بعد از تأیید مدیر اجرا می‌کند.</p>
            <div class="assistant-pro-hero-actions">
                <button type="button" class="btn" onclick="quickAsk('کی هستی؟')">معرفی دستیار</button>
                <button type="button" class="btn btn-ghost" onclick="quickAsk('چکار می‌تونی انجام بدی؟')">توانایی‌ها</button>
                <button type="button" class="btn btn-ghost" onclick="quickAsk('گزارش جامع وضعیت امروز را بده')">گزارش امروز</button>
            </div>
        </div>

        <div class="assistant-pro-scope-grid" aria-label="دامنه عملکرد دستیار">
            <div style="--scope-color:#2563eb;"><span>فروش و سفارش</span><b>گزارش و پیگیری</b><small>سفارش‌های باز، درآمد و روند فروش</small></div>
            <div style="--scope-color:#10b981;"><span>مشتریان</span><b>تحلیل و نگهداشت</b><small>ریزش، ارزش مشتری و یادآور پیگیری</small></div>
            <div style="--scope-color:#ef4444;"><span>پشتیبانی</span><b>تیکت و پاسخ</b><small>تیکت فوری، پاسخ پیشنهادی و تغییر وضعیت</small></div>
            <div style="--scope-color:#8b5cf6;"><span>باشگاه</span><b>کمپین و پاداش</b><small>مأموریت، امتیاز، تخفیف و معرفی دوستان</small></div>
        </div>
    </section>

    @if($assistantContext['type'] && $assistantContext['type'] !== 'page')
        <section class="assistant-context-strip">
            <span>زمینه فعال دستیار</span>
            <b>{{ $assistantContext['title'] ?: 'رکورد فعلی' }}</b>
            <small>از این به بعد می‌توانید بگویید «این مورد»، «همین تیکت»، «همین مشتری» یا «این سند» و دستیار منظور صفحه فعلی را می‌فهمد.</small>
        </section>
    @endif

    <section class="assistant-pro-layout">
        <article class="assistant-chat-card is-accent" style="--card-color:#2563eb;">
            <header>
                <div>
                    <span>گفت‌وگو با دستیار</span>
                    <h2>درخواست مدیریتی خود را بنویسید</h2>
                </div>
                <a class="assistant-pro-link" href="{{ url('/app') }}">بازگشت به داشبورد</a>
            </header>

            <div class="assistant-pro-quick-row" aria-label="پرسش‌های سریع">
                <button type="button" onclick="quickAsk('گزارش جامع وضعیت امروز را بده')">گزارش امروز</button>
                <button type="button" onclick="quickAsk('تیکت‌های فوری چندتاست؟')">تیکت‌های فوری</button>
                <button type="button" onclick="quickAsk('تحلیل مشتریان در حال ریزش')">مشتریان در ریزش</button>
                <button type="button" onclick="quickAsk('وضعیت باشگاه مشتریان')">باشگاه مشتریان</button>
                <button type="button" onclick="quickAsk('یک کمپین بساز')">ساخت کمپین</button>
                <button type="button" onclick="quickAsk('برای مشتری شماره ۱ یادآور بگذار فردا تماس بگیرم')">یادآور مشتری</button>
            </div>

            <div id="adminChatBox" class="assistant-pro-chat-box">
                <div class="bot-msg">سلام! من دستیار مدیریت مشتری‌یار هستم. می‌توانید بپرسید «کی هستی؟»، «چکار می‌تونی انجام بدی؟» یا یک مسئولیت مثل ساخت کمپین، ثبت یادآور، بررسی تیکت یا ساخت پیش‌فاکتور به من بدهید.</div>
            </div>

            <form id="adminChatForm" class="assistant-pro-chat-form">
                @csrf
                <input id="adminChatInput" class="chat-text" placeholder="مثلاً: یک کمپین برای بازگرداندن مشتریان غیرفعال بساز" autocomplete="off" required>
                <input id="adminChatFile" class="chat-file" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt" data-hint="پیوست گفت‌وگو؛ تصویر، سند، فایل فشرده یا متن؛ حداکثر ۵ مگابایت.">
                <button class="btn">ارسال</button>
            </form>
        </article>

        <aside class="assistant-pro-side">
            <article class="assistant-side-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>روش کار مسئولیت‌پذیر</span>
                        <h2>گزارش، سؤال تکمیلی، پیش‌نمایش، تأیید، اجرا</h2>
                    </div>
                </header>
                <div class="assistant-step-list">
                    <div><b>۱</b><p>اگر سؤال گزارشی باشد، دستیار از داده‌های داخلی سامانه جواب می‌دهد.</p></div>
                    <div><b>۲</b><p>اگر اطلاعات ناقص باشد، سؤال تکمیلی می‌پرسد.</p></div>
                    <div><b>۳</b><p>قبل از اقدام، پیش‌نمایش کامل می‌دهد.</p></div>
                    <div><b>۴</b><p>فقط بعد از تأیید مدیر، اقدام در سامانه ثبت می‌شود.</p></div>
                </div>
            </article>

            <article class="assistant-side-card is-accent" style="--card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>نمونه دستور</span>
                        <h2>ساخت مرحله‌ای کمپین</h2>
                    </div>
                </header>
                <div class="assistant-example-box">
                    <p>یک کمپین بساز</p>
                    <small>دستیار هدف، مخاطب، کانال، پاداش و متن پیام را می‌پرسد.</small>
                    <p>هدف بازگرداندن مشتریان غیرفعال است، مخاطب مشتریان دارای خرید، کانال پیامک، پاداش ۱۵ درصد تخفیف، متن را خودت پیشنهاد بده</p>
                    <small>دستیار پیش‌نمایش می‌دهد و منتظر تأیید می‌ماند.</small>
                </div>
            </article>
        </aside>
    </section>
</div>
@endif

<script>
const form = document.getElementById('adminChatForm');
const input = document.getElementById('adminChatInput');
const box = document.getElementById('adminChatBox');
const fileInput = document.getElementById('adminChatFile');
const pageRoot = document.querySelector('.admin-assistant-page');
const assistantContext = {
    type: pageRoot?.dataset.contextType || new URLSearchParams(window.location.search).get('context_type') || '',
    id: pageRoot?.dataset.contextId || new URLSearchParams(window.location.search).get('context_id') || '',
    title: pageRoot?.dataset.contextTitle || new URLSearchParams(window.location.search).get('context_title') || '',
    url: pageRoot?.dataset.contextUrl || new URLSearchParams(window.location.search).get('context_url') || ''
};

function add(cls, html) {
    const d = document.createElement('div');
    d.className = cls;
    d.innerHTML = html;
    box.appendChild(d);
    box.scrollTop = box.scrollHeight;
    return d;
}

function esc(s) {
    return String(s || '').replace(/[<>&]/g, function (x) {
        return {'<': '&lt;', '>': '&gt;', '&': '&amp;'}[x];
    });
}

function quickAsk(text) {
    input.value = text;
    form.dispatchEvent(new Event('submit', {cancelable: true}));
}

function renderResponse(text) {
    return esc(text || '').replace(/\n/g, '<br>');
}

form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    const fileNote = fileInput && fileInput.files[0] ? '<br><small>فایل پیوست شد</small>' : '';
    add('user-msg', renderResponse(msg) + fileNote);
    const loading = add('bot-msg is-loading', 'در حال بررسی داده‌ها و مسیر اقدام...');

    try {
        const fd = new FormData();
        fd.append('message', msg);
        if (assistantContext.type) fd.append('context_type', assistantContext.type);
        if (assistantContext.id) fd.append('context_id', assistantContext.id);
        if (assistantContext.title) fd.append('context_title', assistantContext.title);
        if (assistantContext.url) fd.append('context_url', assistantContext.url);
        if (fileInput && fileInput.files[0]) fd.append('attachment', fileInput.files[0]);
        if (fileInput) {
            fileInput.value = '';
            const pickerName = document.querySelector('.assistant-pro-chat-form .file-picker-name');
            if (pickerName) pickerName.textContent = 'فایلی انتخاب نشده است';
        }

        const response = await fetch('{{ url('/app/assistant/ask') }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: fd
        });
        const data = await response.json();

        let html = renderResponse(data.answer || 'پاسخی دریافت نشد.');
        if (data.url) {
            html += '<br><a class="assistant-chat-link" href="' + data.url + '">مشاهده بخش مرتبط</a>';
        }
        if (data.articles && data.articles.length) {
            html += '<br><br><b>مقاله‌های مرتبط:</b>';
            data.articles.forEach(function (article) {
                html += '<br><a class="assistant-chat-link" href="' + article.url + '">' + esc(article.title) + '</a>';
            });
        }

        loading.classList.remove('is-loading');
        loading.innerHTML = html;

        if (data.suggestions && data.suggestions.length) {
            const wrap = document.createElement('div');
            wrap.className = 'suggestion-chips';
            data.suggestions.forEach(function (suggestion) {
                const btn = document.createElement('button');
                btn.className = 'suggestion-chip';
                btn.type = 'button';
                btn.textContent = suggestion;
                btn.onclick = function () { quickAsk(suggestion); };
                wrap.appendChild(btn);
            });
            loading.appendChild(wrap);
        }
    } catch (error) {
        loading.classList.remove('is-loading');
        loading.innerHTML = 'خطا در ارتباط با دستیار. لطفاً دوباره تلاش کنید.';
    }
});

// اگر صفحه با پارامتر prompt در URL باز شود، خودکار در ورودی گذاشته و ارسال می‌شود.
// این قابلیت برای دکمه‌های «ساخت با دستیار» در سایر صفحات (کمپین‌ها، workflows، ...) استفاده می‌شود.
(function () {
    function runAutoPrompt() {
        try {
            var params = new URLSearchParams(window.location.search);
            var prompt = params.get('prompt');
            if (!prompt) return;

            var promptInput = document.getElementById('adminChatInput');
            var promptForm  = document.getElementById('adminChatForm');
            if (!promptInput || !promptForm) return;

            // پیام را در ورودی می‌گذاریم
            promptInput.value = prompt;
            promptInput.focus();

            // با کمی تأخیر ارسال می‌کنیم تا صفحه کامل آماده باشد
            setTimeout(function () {
                try {
                    promptForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                } catch (e) {
                    // fallback: click روی دکمه submit
                    var submitBtn = promptForm.querySelector('button[type="submit"], button:not([type])');
                    if (submitBtn) submitBtn.click();
                }
                // پارامتر را از URL پاک می‌کنیم که با refresh دوباره ارسال نشود
                try {
                    var newUrl = window.location.pathname
                        + (window.location.search.replace(/[?&]prompt=[^&]*/, '').replace(/^&/, '?') || '')
                        + window.location.hash;
                    window.history.replaceState({}, document.title, newUrl);
                } catch (e) {}
            }, 500);
        } catch (e) {
            console.warn('auto-prompt error:', e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runAutoPrompt);
    } else {
        runAutoPrompt();
    }
})();
</script>
@endsection