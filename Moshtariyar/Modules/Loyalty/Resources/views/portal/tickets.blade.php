@extends('layouts.customer_portal')
@section('title','درخواست‌های پشتیبانی')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-support.css') }}">
@php
    $statusMap = [
        'open' => ['باز', 'is-warn', '#f59e0b'],
        'pending' => ['در انتظار بررسی', 'is-muted', '#64748b'],
        'answered' => ['پاسخ داده شده', 'is-ok', '#10b981'],
        'closed' => ['بسته شده', 'is-muted', '#64748b'],
    ];
    $priorityMap = [
        'low' => ['کم', 'is-muted'],
        'normal' => ['عادی', 'is-ok'],
        'high' => ['زیاد', 'is-warn'],
        'urgent' => ['فوری', 'is-bad'],
    ];
    $departments = ['پشتیبانی','فروش','مالی','فنی'];
    $visibleTickets = $tickets->getCollection();
    $openCount = $visibleTickets->whereIn('status', ['open', 'pending', 'answered'])->count();
    $answeredCount = $visibleTickets->where('status', 'answered')->count();
    $closedCount = $visibleTickets->where('status', 'closed')->count();
@endphp

<div class="support-page">
    <section class="support-hero">
        <div>
            <span class="support-eyebrow">مرکز پشتیبانی باشگاه</span>
            <h1>درخواست‌ها، پاسخ‌ها و پیوست‌ها در یک مرکز حرفه‌ای 🎫</h1>
            <p>درخواست پشتیبانی ثبت کنید، گفتگو را بدون خروج از صفحه ببینید، پاسخ بفرستید و فایل پیوست کنید. این بخش برای تجربه سریع و شفاف مشتری طراحی شده است.</p>
            <div class="support-hero-actions">
                <a class="btn" href="#newTicketForm">ثبت درخواست جدید</a>
                <a class="btn btn-ghost" href="{{ route('club.kb') }}">جستجو در راهنما</a>
                <a class="btn btn-ghost" href="{{ route('club.assistant') }}">دستیار هوشمند</a>
            </div>
        </div>
        <div class="support-score-grid">
            <div><span>درخواست‌های باز این صفحه</span><b>@fa(number_format($openCount))</b><small>نیازمند پیگیری یا پاسخ</small></div>
            <div><span>پاسخ داده شده</span><b>@fa(number_format($answeredCount))</b><small>در انتظار بررسی شما</small></div>
            <div><span>بسته شده</span><b>@fa(number_format($closedCount))</b><small>درخواست‌های تکمیل‌شده</small></div>
        </div>
    </section>

    <section class="support-grid">
        <article class="support-card is-accent" style="--support-card-color:#0ea5e9;">
            <header>
                <div>
                    <span>درخواست‌های من</span>
                    <h2>بورد پیگیری پشتیبانی</h2>
                </div>
                <span class="support-badge">@fa(number_format($visibleTickets->count())) مورد</span>
            </header>
            <div class="support-ticket-list">
                @forelse($tickets as $ticket)
                    @php
                        [$statusLabel, $statusClass, $statusColor] = $statusMap[$ticket->status] ?? [$ticket->status, 'is-muted', '#64748b'];
                        [$priorityLabel, $priorityClass] = $priorityMap[$ticket->priority] ?? [$ticket->priority, 'is-muted'];
                    @endphp
                    <article class="support-ticket-card" style="--support-row-color: {{ $statusColor }};">
                        <div class="support-ticket-icon">🎫</div>
                        <div>
                            <h3>{{ $ticket->subject }}</h3>
                            <p>دپارتمان: {{ $ticket->department }} · اولویت: {{ $priorityLabel }}</p>
                            <small>آخرین بروزرسانی: @jdatetime($ticket->updated_at)</small>
                        </div>
                        <div class="support-ticket-side">
                            <span class="support-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            <button type="button" class="btn btn-ghost" onclick="openTicketFrame('{{ route('club.tickets.modal', $ticket) }}')">مشاهده گفتگو</button>
                        </div>
                    </article>
                @empty
                    <div class="support-empty">هنوز درخواستی ندارید. از فرم کنار صفحه، اولین درخواست خود را ثبت کنید.</div>
                @endforelse
            </div>
            <div style="margin-top:1rem">{{ $tickets->links() }}</div>
        </article>

        <aside class="support-card is-accent" style="--support-card-color:#10b981;" id="newTicketForm">
            <header>
                <div>
                    <span>درخواست جدید</span>
                    <h2>ثبت تیکت پشتیبانی</h2>
                </div>
                <span class="support-badge is-ok">سریع</span>
            </header>
            <form method="post" action="{{ route('club.tickets.store') }}" enctype="multipart/form-data" class="support-form-grid">
                @csrf
                <div>
                    <label>موضوع درخواست</label>
                    <input name="subject" value="{{ old('subject', request('subject')) }}" required placeholder="مثلاً مشکل در پرداخت یا سوال درباره سفارش">
                </div>
                <div class="support-form-two">
                    <div>
                        <label>دپارتمان</label>
                        <select name="department" required>
                            @foreach($departments as $department)
                                <option value="{{ $department }}">{{ $department }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>اولویت</label>
                        <select name="priority">
                            <option value="normal">عادی</option>
                            <option value="low">کم</option>
                            <option value="high">زیاد</option>
                            <option value="urgent">فوری</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>پیام شما</label>
                    <textarea name="message" required placeholder="لطفاً توضیح کامل درخواست را بنویسید...">{{ old('message', request('message')) }}</textarea>
                </div>
                <div>
                    <label>پیوست، اختیاری</label>
                    <input type="file" name="attachment" data-hint="پیوست درخواست پشتیبانی؛ تصویر، PDF، Word، Excel، ZIP یا متن؛ حداکثر ۵ مگابایت.">
                    <small class="support-file-note">اگر تصویر خطا، فاکتور یا فایل مرتبط دارید، اینجا بارگذاری کنید.</small>
                </div>
                <button class="btn" style="width:100%">ارسال درخواست پشتیبانی</button>
            </form>
        </aside>
    </section>

    <section class="support-grid-reverse">
        <article class="support-card">
            <header>
                <div>
                    <span>پیشنهاد سریع</span>
                    <h2>قبل از ثبت درخواست</h2>
                </div>
            </header>
            <div class="support-ticket-list">
                <div class="support-ticket-card" style="--support-row-color:#8b5cf6;">
                    <div class="support-ticket-icon">🤖</div>
                    <div><h3>پرسیدن از دستیار</h3><p>برای سوال‌های رایج درباره امتیاز، کد تخفیف، سفارش و باشگاه، دستیار سریع‌تر پاسخ می‌دهد.</p></div>
                    <a class="btn btn-ghost" href="{{ route('club.assistant') }}">شروع</a>
                </div>
                <div class="support-ticket-card" style="--support-row-color:#0ea5e9;">
                    <div class="support-ticket-icon">📚</div>
                    <div><h3>جستجو در راهنما</h3><p>پاسخ بسیاری از سوال‌های پرتکرار در پایگاه دانش ثبت شده است.</p></div>
                    <a class="btn btn-ghost" href="{{ route('club.kb') }}">راهنما</a>
                </div>
            </div>
        </article>

        <article class="support-card is-accent" style="--support-card-color:#8b5cf6;">
            <header>
                <div>
                    <span>وضعیت پاسخگویی</span>
                    <h2>شفاف و قابل پیگیری</h2>
                </div>
            </header>
            <p>هر درخواست یک گفتگو دارد. پاسخ‌ها، فایل‌های پیوست و زمان بروزرسانی به‌صورت مرتب نمایش داده می‌شود تا هیچ پیگیری‌ای گم نشود.</p>
            <div class="support-actions-row">
                <a class="btn btn-ghost" href="{{ route('club.notifications') }}">اعلان‌ها</a>
                <a class="btn btn-ghost" href="{{ route('club.dashboard') }}">پیشخوان</a>
            </div>
        </article>
    </section>
</div>

<div class="modal" id="ticketFrameModal">
    <div class="modal-box support-modal-box">
        <button class="btn btn-ghost support-modal-close" onclick="closeTicketFrame()">بستن</button>
        <iframe id="ticketFrame" class="support-ticket-frame" src="about:blank"></iframe>
    </div>
</div>

<script>
function openTicketFrame(url) {
    document.getElementById('ticketFrame').src = url;
    document.getElementById('ticketFrameModal').classList.add('show');
}
function closeTicketFrame() {
    document.getElementById('ticketFrameModal').classList.remove('show');
    document.getElementById('ticketFrame').src = 'about:blank';
}
</script>
@endsection