@extends('layouts.customer_portal')
@section('title','دستیار مشتری‌یار')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-support.css') }}">
@php $isEmbed = request()->boolean('embed'); @endphp
<div class="support-assistant-page {{ $isEmbed ? 'is-embed' : '' }}">
    @if(! $isEmbed)
        <section class="support-hero">
            <div>
                <span class="support-eyebrow">دستیار هوشمند باشگاه</span>
                <h1>سوالتان را بپرسید؛ پاسخ سریع بگیرید 🤖</h1>
                <p>دستیار می‌تواند درباره امتیاز، کیف پول، کد تخفیف، سفارش، تیکت، لینک معرف و راهنمای باشگاه به شما کمک کند. اگر پاسخ کافی نبود، مسیر ثبت درخواست پشتیبانی هم پیشنهاد می‌شود.</p>
                <div class="support-hero-actions">
                    <a class="btn btn-ghost" href="{{ route('club.kb') }}">راهنما</a>
                    <a class="btn btn-ghost" href="{{ route('club.tickets') }}">درخواست پشتیبانی</a>
                </div>
            </div>
            <div class="support-score-grid">
                <div><span>پاسخ سریع</span><b>هوشمند</b><small>برای سوال‌های باشگاه</small></div>
                <div><span>پیوست</span><b>فعال</b><small>امکان ارسال فایل همراه سوال</small></div>
                <div><span>مسیر جایگزین</span><b>تیکت</b><small>در صورت نیاز به بررسی انسانی</small></div>
            </div>
        </section>
    @endif

    <section class="support-assistant-shell {{ $isEmbed ? 'support-assistant-shell-embed' : '' }}">
        <article class="support-card support-assistant-main-card">
            @if(! $isEmbed)
                <header>
                    <div>
                        <span>گفتگو با دستیار</span>
                        <h2>پیام خود را بنویسید</h2>
                    </div>
                </header>
            @endif
            <div id="chatBox" class="support-assistant-chat {{ $isEmbed ? 'support-assistant-chat-embed' : '' }}">
                <div class="bot-msg">سلام! من دستیار مشتری‌یار هستم. درباره امتیاز، کد تخفیف، سفارش، باشگاه یا پشتیبانی سوال دارید؟</div>
            </div>
            <form id="chatForm" class="support-chat-form">
                @csrf
                <input id="chatInput" class="chat-text" placeholder="سوال خود را بنویسید..." autocomplete="off" required>
                <input id="chatFile" class="chat-file" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt" data-hint="پیوست گفتگو؛ تصویر، سند، فایل فشرده یا متن؛ حداکثر ۵ مگابایت.">
                <button class="btn">ارسال</button>
            </form>
        </article>

        @if(! $isEmbed)
            <aside class="support-card is-accent" style="--support-card-color:#8b5cf6;">
                <header>
                    <div>
                        <span>سوال‌های آماده</span>
                        <h2>می‌توانید سریع بپرسید</h2>
                    </div>
                </header>
                <div class="support-quick-list">
                    <button class="support-quick-chip" type="button" onclick="quickAsk('امتیاز من چقدر است؟')">امتیاز من چقدر است؟</button>
                    <button class="support-quick-chip" type="button" onclick="quickAsk('کیف پول من چقدر است؟')">کیف پول من چقدر است؟</button>
                    <button class="support-quick-chip" type="button" onclick="quickAsk('کدهای تخفیف من را نشان بده')">کدهای تخفیف من را نشان بده</button>
                    <button class="support-quick-chip" type="button" onclick="quickAsk('لینک معرف من را بده')">لینک معرف من را بده</button>
                    <button class="support-quick-chip" type="button" onclick="quickAsk('آخرین سفارش‌های من چیست؟')">آخرین سفارش‌های من چیست؟</button>
                    <button class="support-quick-chip" type="button" onclick="quickAsk('وضعیت تیکت‌های من چیست؟')">وضعیت تیکت‌های من چیست؟</button>
                </div>
            </aside>
        @endif
    </section>
</div>
<script>
const form = document.getElementById('chatForm');
const input = document.getElementById('chatInput');
const box = document.getElementById('chatBox');
function add(cls, html) {
    const item = document.createElement('div');
    item.className = cls;
    item.innerHTML = html;
    box.appendChild(item);
    box.scrollTop = box.scrollHeight;
    return item;
}
function quickAsk(text) {
    input.value = text;
    form.dispatchEvent(new Event('submit', {cancelable: true}));
}
form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const msg = input.value.trim();
    const file = document.getElementById('chatFile');
    if (!msg) return;
    input.value = '';
    add('user-msg', msg.replace(/[<>&]/g, s => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[s])) + (file && file.files[0] ? '<br><small>📎 فایل پیوست شد</small>' : ''));
    const loading = add('bot-msg', 'در حال بررسی...');
    try {
        const fd = new FormData();
        fd.append('message', msg);
        if (file && file.files[0]) fd.append('attachment', file.files[0]);
        if (file) file.value = '';
        const response = await fetch('{{ route('club.assistant.ask') }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: fd
        });
        const data = await response.json();
        let html = (data.answer || '').replace(/[<>&]/g, s => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[s]));
        if (data.articles && data.articles.length) {
            html += '<br><br><b>مقاله‌های پیشنهادی:</b>';
            data.articles.forEach(article => {
                html += `<a class="article-card" href="${article.url}"><b>${article.title}</b><div class="muted">${article.summary || ''}</div></a>`;
            });
        }
        if (data.url) {
            html += `<br><a class="btn" style="display:inline-block;margin-top:10px" href="${data.url}">مشاهده جزئیات</a>`;
        }
        if (data.needs_ticket) {
            html += `<br><a class="btn" style="display:inline-block;margin-top:10px" href="${data.ticket_url}">ثبت درخواست پشتیبانی</a>`;
        }
        loading.innerHTML = html;
    } catch (error) {
        loading.innerHTML = 'خطا در ارتباط با دستیار. لطفاً دوباره تلاش کنید.';
    }
});
</script>
@endsection