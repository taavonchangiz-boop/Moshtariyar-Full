@extends('layouts.app')
@section('title','پشتیبانی')
@section('heading','مرکز ارتباط با پشتیبانی')
@section('subtitle','ثبت پیام پشتیبانی و مشاهده راه‌های ارتباطی با تیم')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tickets-board.css') }}">

<div class="tickets-page">

    {{-- هدر --}}
    <section class="tickets-hero">
        <div>
            <span class="tickets-eyebrow">مرکز ارتباط با پشتیبانی</span>
            <h2>پیام خود را ثبت کن تا به‌صورت تیکت قابل پیگیری شود</h2>
            <p>پیام‌های این بخش در مرکز تیکت‌ها ذخیره می‌شوند و تیم پشتیبانی می‌تواند وضعیت، اولویت و پاسخ‌ها را به‌صورت منظم پیگیری کند.</p>
            <div class="tickets-hero-actions">
                <a class="btn" href="#supportQuickForm">ارسال پیام جدید</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets') }}">مشاهده تیکت‌های من</a>
                <a class="btn btn-ghost" href="{{ url('/app/tickets/sla') }}">گزارش پاسخگویی</a>
            </div>
        </div>
        <div class="tickets-score">
            <div style="--metric-color:#3b82f6;">
                <span>ساعات پاسخگویی</span>
                <b>۹ تا ۱۷</b>
                <small>شنبه تا چهارشنبه</small>
            </div>
            <div style="--metric-color:#10b981;">
                <span>ثبت پیام</span>
                <b>۲۴</b>
                <small>ساعته در سامانه</small>
            </div>
            <div style="--metric-color:#8b5cf6;">
                <span>روش پیگیری</span>
                <b>تیکت</b>
                <small>قابل بررسی در پنل</small>
            </div>
            <div style="--metric-color:#f59e0b;">
                <span>میانگین پاسخ</span>
                <b>۲ ساعت</b>
                <small>در ساعات کاری</small>
            </div>
        </div>
    </section>

    <section class="tickets-layout">

        {{-- بخش اصلی: فرم ثبت پیام --}}
        <div class="tickets-main">

            <article class="tickets-card is-accent" style="--card-color:#8b5cf6;" id="supportQuickForm">
                <header>
                    <div>
                        <span>ثبت پیام سریع</span>
                        <h2>پیام یا درخواست خود را برای پشتیبانی ارسال کن</h2>
                        <p>موضوع را کوتاه و شرح را دقیق بنویس. در صورت نیاز فایل ضمیمه هم اضافه کن. پیام شما بلافاصله در بورد تیکت‌ها ثبت می‌شود.</p>
                    </div>
                </header>

                <form method="post" action="{{ url('/app/support') }}" enctype="multipart/form-data" class="ticket-detail-reply-form">
                    @csrf
                    <input type="hidden" name="department" value="پشتیبانی">

                    <div class="ticket-detail-field">
                        <label>موضوع پیام</label>
                        <input name="subject" required placeholder="موضوع را کوتاه و گویا بنویس">
                    </div>

                    <div class="ticket-detail-field">
                        <label>اولویت</label>
                        <select name="priority">
                            <option value="low">کم — درخواست عمومی، بدون فوریت</option>
                            <option value="normal" selected>عادی — پیگیری در وقت مقرر</option>
                            <option value="high">زیاد — نیازمند بررسی سریع</option>
                            <option value="urgent">فوری — مشکل بلاکه است، پاسخ اورژانسی</option>
                        </select>
                    </div>

                    <div class="ticket-detail-field">
                        <label>متن پیام</label>
                        <textarea name="message" rows="8" required placeholder="شرح کامل مشکل یا درخواست خود را بنویس. هر چه دقیق‌تر بنویسی، پاسخ سریع‌تر و مفیدتر است."></textarea>
                        <small>اگر خطای فنی داری، متن دقیق خطا و مراحل بازتولید آن را ذکر کن.</small>
                    </div>

                    <div class="ticket-detail-field">
                        <label>پیوست فایل (اختیاری)</label>
                        <input type="file" name="attachment">
                        <small>تصویر، PDF، Word، Excel، فایل فشرده یا متن — حداکثر ۵ مگابایت</small>
                    </div>

                    <button type="submit" class="btn">ارسال پیام</button>
                </form>
            </article>

        </div>

        {{-- بخش زیر: راه‌های ارتباطی + راهنما --}}
        <div class="tickets-below">

            <article class="tickets-card is-accent" style="--card-color:#0ea5e9;">
                <header>
                    <div>
                        <span>راه‌های ارتباطی</span>
                        <h2>ارتباط با تیم {{ config('brand.owner') ?? config('brand.name') ?? 'پشتیبانی' }}</h2>
                    </div>
                </header>

                <div class="support-contact-list">
                    @if(config('brand.support'))
                        <a class="support-contact-item" href="mailto:{{ config('brand.support') }}">
                            <div class="support-contact-icon" style="background:rgba(14, 165, 233, 0.14); color:#0284c7;">✉</div>
                            <div>
                                <b>ایمیل پشتیبانی</b>
                                <small style="direction:ltr;">{{ config('brand.support') }}</small>
                            </div>
                            <span class="tickets-pill is-dept">ایمیل</span>
                        </a>
                    @endif

                    @if(config('brand.url'))
                        <a class="support-contact-item" href="{{ config('brand.url') }}" target="_blank" rel="noopener">
                            <div class="support-contact-icon" style="background:rgba(16, 185, 129, 0.14); color:#047857;">🌐</div>
                            <div>
                                <b>وب‌سایت رسمی</b>
                                <small style="direction:ltr;">{{ config('brand.domain') ?? config('brand.url') }}</small>
                            </div>
                            <span class="tickets-pill is-status-answered">وب</span>
                        </a>
                    @endif

                    @if(config('brand.phone'))
                        <a class="support-contact-item" href="tel:{{ config('brand.phone') }}">
                            <div class="support-contact-icon" style="background:rgba(139, 92, 246, 0.14); color:#6d28d9;">📞</div>
                            <div>
                                <b>تماس تلفنی</b>
                                <small style="direction:ltr;">@fa(config('brand.phone'))</small>
                            </div>
                            <span class="tickets-pill" style="background:rgba(139, 92, 246, 0.14); color:#6d28d9;">تماس</span>
                        </a>
                    @endif
                </div>

                @if(config('brand.name'))
                    <div class="support-brand-footer">
                        <b>{{ config('brand.name') }}</b>
                        @if(config('brand.tagline'))
                            <small>{{ config('brand.tagline') }}</small>
                        @endif
                        @if(config('brand.owner'))
                            <small>محصولی از {{ config('brand.owner') }}</small>
                        @endif
                    </div>
                @endif
            </article>

            <article class="tickets-card is-accent" style="--card-color:#f59e0b;">
                <header>
                    <div>
                        <span>راهنمای ثبت تیکت مؤثر</span>
                        <h2>چطور پیام بدهی که سریع‌تر پاسخ بگیری</h2>
                    </div>
                </header>

                <div class="response-guide-list">
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(59, 130, 246, 0.14); color:#1d4ed8;">۱</div>
                        <div>
                            <b>موضوع را کوتاه و مشخص بنویس</b>
                            <small>به‌جای «مشکل دارم» بنویس «خطا در پرداخت آنلاین درگاه زرین‌پال» تا سریع‌تر ارجاع شود.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(245, 158, 11, 0.14); color:#b45309;">۲</div>
                        <div>
                            <b>اولویت درست را انتخاب کن</b>
                            <small>اولویت فوری فقط برای موارد بلاکه‌کننده - این کمک می‌کند تیم واقعاً به موارد اضطراری اولویت بدهد.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(16, 185, 129, 0.14); color:#047857;">۳</div>
                        <div>
                            <b>متن پیام را دقیق بنویس</b>
                            <small>مراحل بازتولید مشکل، متن خطا، شماره سفارش و هر جزئیات مرتبط را ذکر کن تا پاسخ اول کامل باشد.</small>
                        </div>
                    </div>
                    <div class="response-guide-item">
                        <div class="response-guide-icon" style="background:rgba(139, 92, 246, 0.14); color:#6d28d9;">۴</div>
                        <div>
                            <b>فایل ضمیمه بگذار</b>
                            <small>عکس صفحه خطا، ویدئوی مشکل یا فایل گزارش می‌تواند زمان تشخیص را نصف کند.</small>
                        </div>
                    </div>
                </div>
            </article>

        </div>
    </section>

</div>
@endsection
