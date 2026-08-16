@extends('layouts.app')
@section('title','مرکز کمپین‌های هوشمند')
@section('heading','مرکز کمپین‌های هوشمند و رشد فروش')
@section('subtitle','طراحی، پایش، بهینه‌سازی و اجرای کمپین‌های بازگشت مشتری، خرید مجدد و معرفی دوستان')

@section('content')
<link rel="stylesheet" href="{{ asset('css/campaigns-smart-growth.css') }}">

@php
    // نقشه وضعیت‌ها به برچسب فارسی + کلاس + توضیح
    $statusMap = [
        'draft'   => ['پیش‌نویس',    'is-draft',   'در انتظار تکمیل و بررسی'],
        'queued'  => ['در صف ارسال', 'is-queued',  'آماده ارسال توسط سامانه'],
        'sending' => ['در حال ارسال','is-sending', 'ارسال فعال است'],
        'sent'    => ['ارسال‌شده',   'is-sent',    'ارسال به پایان رسیده است'],
    ];

    // گروه‌های بورد
    $boardGroups = collect([
        ['key' => 'draft',    'label' => 'پیش‌نویس',           'color' => '#94a3b8', 'hint' => 'کمپین‌های نیازمند تکمیل'],
        ['key' => 'ready',    'label' => 'آماده اجرا',         'color' => '#0ea5e9', 'hint' => 'پیش‌نویس‌های قابل بررسی و ارسال'],
        ['key' => 'active',   'label' => 'در حال اجرا',        'color' => '#f59e0b', 'hint' => 'در صف یا در حال ارسال'],
        ['key' => 'optimize', 'label' => 'نیازمند بهینه‌سازی', 'color' => '#ef4444', 'hint' => 'کلیک یا تبدیل ضعیف دارد'],
        ['key' => 'sent',     'label' => 'پایان‌یافته',        'color' => '#10b981', 'hint' => 'ارسال‌شده و قابل تحلیل'],
    ]);

    // تعیین گروه هر کمپین
    $campaignBucket = function ($campaign) {
        if (in_array($campaign->status, ['queued', 'sending'], true)) return 'active';
        if ($campaign->status === 'sent') {
            if ((int) ($campaign->clicks_count ?? 0) === 0
                || (int) ($campaign->conversions_count ?? 0) < (int) ($campaign->clicks_count ?? 0)) {
                return 'optimize';
            }
            return 'sent';
        }
        if ($campaign->status === 'draft'
            && trim((string) $campaign->message) !== ''
            && trim((string) $campaign->segment) !== '') {
            return 'ready';
        }
        return 'draft';
    };
    $boardItems = collect($campaignBoard ?? [])->groupBy($campaignBucket);
    $totalBoard = collect($campaignBoard ?? [])->count();
    $conversionRate = $totalClicks > 0 ? round(($totalConversions / max(1, $totalClicks)) * 100) : 0;

    // گروه‌های باز به صورت پیش‌فرض بر اساس فیلتر
    $openGroups = ['active', 'ready'];
    if (request('status') === 'draft')   { $openGroups = ['draft']; }
    if (request('status') === 'sent')    { $openGroups = ['sent', 'optimize']; }
    if (request('needs_optimization'))   { $openGroups = ['optimize']; }

    // آدرس پایه رفرال (برای پیش‌نمایش لینک)
    $referralBase = rtrim(url('/club/c/'), '/') . '/';
@endphp

<div class="campaign-page">

    {{-- هدر اصلی --}}
    <section class="campaign-hero">
        <div>
            <span class="campaign-eyebrow">اتاق رشد فروش و وفاداری</span>
            <h2>کمپین‌ها را مثل یک جریان رشد مدیریت کن؛ از ایده تا اجرا، تحلیل و بهینه‌سازی</h2>
            <p>این مرکز برای ساخت کمپین‌های بازگشت مشتری، خرید مجدد، معرفی دوستان، فعال‌سازی باشگاه و فروش هدفمند طراحی شده است. دستیار می‌تواند شرایط کمپین را از مدیر بپرسد، پیش‌نمایش بدهد و پس از تأیید، کمپین را بسازد.</p>
            <div class="campaign-hero-actions">
                <a class="btn" href="#campaignCreator">ساخت کمپین جدید</a>
                <button class="btn btn-ghost" type="button" onclick="askCampaignAssistant('یک کمپین جدید بساز', this)">ساخت با دستیار</button>
                <a class="btn btn-ghost" href="{{ url('/app/reports/rfm') }}">تحلیل رفتار مشتریان</a>
            </div>
        </div>
        <div class="campaign-score">
            <a href="{{ url('/app/campaigns') }}" style="--metric-color:#2563eb;">
                <span>کل کمپین‌ها</span><b>@fa(number_format($summary['total'] ?? 0))</b><small>در نمای فعلی</small>
            </a>
            <a href="{{ url('/app/campaigns?status=draft') }}" style="--metric-color:#94a3b8;">
                <span>پیش‌نویس</span><b>@fa(number_format($summary['draft'] ?? 0))</b><small>نیازمند تکمیل</small>
            </a>
            <a href="{{ url('/app/campaigns?status=sending') }}" style="--metric-color:#f59e0b;">
                <span>در حال اجرا</span><b>@fa(number_format($summary['active'] ?? 0))</b><small>صف ارسال یا فعال</small>
            </a>
            <a href="{{ url('/app/campaigns?needs_optimization=1') }}" style="--metric-color:#ef4444;">
                <span>نیازمند بهینه‌سازی</span><b>@fa(number_format($summary['needs_optimization'] ?? 0))</b><small>کلیک یا تبدیل ضعیف</small>
            </a>
        </div>
    </section>

    {{-- نوار فیلترها --}}
    <form method="get" class="campaign-toolbar">
        <div>
            <label>جستجو</label>
            <input name="q" value="{{ request('q') }}" placeholder="نام، پیام، موضوع یا لینک دعوت">
        </div>
        <div>
            <label>وضعیت</label>
            <select name="status">
                <option value="">همه</option>
                @foreach($statusMap as $key => $status)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $status[0] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>کانال</label>
            <select name="channel">
                <option value="">همه کانال‌ها</option>
                @foreach($channels as $key => $label)
                    <option value="{{ $key }}" @selected(request('channel') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>گروه هدف</label>
            <select name="segment">
                <option value="">همه گروه‌ها</option>
                @foreach($segments as $key => $label)
                    <option value="{{ $key }}" @selected(request('segment') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>رفتار خرید</label>
            <select name="rfm_group">
                <option value="">همه رفتارها</option>
                @foreach($rfmGroups as $key => $label)
                    <option value="{{ $key }}" @selected(request('rfm_group') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <label class="campaign-toolbar-check">
            <input type="checkbox" name="needs_optimization" value="1" @checked(request('needs_optimization'))>
            <span>فقط نیازمند بهینه‌سازی</span>
        </label>
        <div class="campaign-toolbar-actions">
            <button class="btn">اعمال فیلتر</button>
            @if(request()->hasAny(['q','status','channel','segment','rfm_group','needs_optimization']))
                <a class="btn btn-ghost" href="{{ url('/app/campaigns') }}">حذف فیلترها</a>
            @endif
        </div>
    </form>

    {{-- نوار خلاصه عملکرد --}}
    <section class="campaign-insight">
        <div><span class="campaign-dot is-blue"></span><b>@money($totalRevenue)</b><small>درآمد ثبت‌شده از کمپین‌ها</small></div>
        <div><span class="campaign-dot is-warn"></span><b>@fa(number_format($totalClicks))</b><small>کلیک روی لینک‌ها</small></div>
        <div><span class="campaign-dot is-ok"></span><b>@fa(number_format($totalConversions))</b><small>تبدیل ثبت‌شده</small></div>
        <div><span class="campaign-dot is-purple"></span><b>@fa($conversionRate)٪</b><small>نرخ تبدیل تقریبی</small></div>
    </section>

    {{-- بورد کمپین‌ها (تمام‌عرض بالا) + کارت‌های زیر آن --}}
    <section class="campaign-layout">

        {{-- بورد اصلی تمام‌عرض --}}
        <div class="campaign-main">
            <article class="campaign-card is-accent" style="--card-color:#2563eb;">
                <header>
                    <div>
                        <span>بورد کمپین‌ها</span>
                        <h2>مدیریت مرحله‌ای کمپین‌ها بر اساس وضعیت و سلامت اجرا</h2>
                        <p>روی هر ردیف کلیک کن تا ماموریت‌ها، جدول پاداش، پیام و آمار کامل آن باز شود.</p>
                    </div>
                    <a class="campaign-link" href="#campaignCreator">کمپین جدید</a>
                </header>

                <div class="campaign-board">
                    @foreach($boardGroups as $group)
                        @php
                            $items = $boardItems->get($group['key'], collect());
                            $ratio = $totalBoard > 0 ? round(($items->count() / max(1, $totalBoard)) * 100) : 0;
                            $isOpen = in_array($group['key'], $openGroups, true);
                            $groupClass = $isOpen ? 'is-open' : 'is-collapsed';
                        @endphp

                        <div class="campaign-group {{ $groupClass }}" style="--group-color:{{ $group['color'] }}; --group-ratio:{{ $ratio }}%;">
                            <button type="button" class="campaign-group-title" onclick="toggleCampaignGroup(this)">
                                <span class="cg-caret">◀</span>
                                <span class="cg-mark"></span>
                                <span class="cg-title-block">
                                    <b>{{ $group['label'] }}</b>
                                    <small>{{ $group['hint'] }}</small>
                                </span>
                                <span class="cg-count">@fa($items->count())</span>
                                <span class="cg-progress"></span>
                                <span class="cg-ratio">@fa($ratio)٪</span>
                            </button>

                            <div class="campaign-table-wrap">
                                <div class="campaign-table">
                                    {{-- سربرگ ستون‌ها --}}
                                    <div class="campaign-thead">
                                        <div>کمپین</div>
                                        <div>وضعیت</div>
                                        <div>کانال</div>
                                        <div>گروه هدف</div>
                                        <div>ارسال</div>
                                        <div>لینک رفرال</div>
                                        <div>عملکرد</div>
                                        <div>عملیات</div>
                                    </div>

                                    @forelse($items as $campaign)
                                        @php
                                            [$statusLabel, $statusClass, $statusTip] = $statusMap[$campaign->status] ?? [$campaign->status, 'is-draft', ''];
                                            $sentRate = $campaign->total > 0 ? round(($campaign->sent / max(1, $campaign->total)) * 100) : 0;
                                            $campaignConversion = $campaign->clicks_count > 0 ? round(($campaign->conversions_count / max(1, $campaign->clicks_count)) * 100) : 0;
                                            $referralSlug = $campaign->referral_slug ?: $campaign->id;
                                            $referralFull = $referralBase . $referralSlug;
                                            $missions = $campaign->missions ?? collect();
                                            $rewards  = $campaign->rewards  ?? collect();
                                        @endphp

                                        <details class="campaign-row">
                                            <summary>
                                                <div class="campaign-cell">
                                                    <div class="campaign-cell-name">
                                                        <b>{{ $campaign->name }}</b>
                                                        <small>{{ $campaign->subject ?: \Illuminate\Support\Str::limit(strip_tags($campaign->message ?? ''), 55) }}</small>
                                                    </div>
                                                </div>
                                                <div class="campaign-cell">
                                                    <span class="campaign-pill {{ $statusClass }}" title="{{ $statusTip }}">{{ $statusLabel }}</span>
                                                </div>
                                                <div class="campaign-cell">
                                                    <span class="campaign-pill is-channel">{{ $channels[$campaign->channel] ?? $campaign->channel }}</span>
                                                </div>
                                                <div class="campaign-cell">
                                                    <span class="campaign-pill is-segment">{{ $segments[$campaign->segment] ?? $campaign->segment }}</span>
                                                    @if($campaign->rfm_group && $campaign->rfm_group !== 'all')
                                                        <br><small style="color:var(--mut); font-size:0.7rem; line-height:1.55;">{{ $rfmGroups[$campaign->rfm_group] ?? '' }}</small>
                                                    @endif
                                                </div>
                                                <div class="campaign-cell">
                                                    <small class="campaign-progress-label">@fa(number_format($campaign->sent ?? 0)) از @fa(number_format($campaign->total ?? 0))</small>
                                                    <div class="campaign-progress-bar"><span style="width: {{ $sentRate }}%"></span></div>
                                                </div>
                                                <div class="campaign-cell">
                                                    <div class="campaign-referral">
                                                        <code>/club/c/{{ $referralSlug }}</code>
                                                        <button type="button" onclick="event.preventDefault(); copyReferralLink('{{ $referralFull }}', this);" title="کپی لینک">کپی</button>
                                                    </div>
                                                </div>
                                                <div class="campaign-cell">
                                                    <div class="campaign-cell-name">
                                                        <b>@fa(number_format($campaign->clicks_count ?? 0)) کلیک</b>
                                                        <small>@fa(number_format($campaign->conversions_count ?? 0)) تبدیل · @fa($campaignConversion)٪</small>
                                                    </div>
                                                </div>
                                                <div class="campaign-cell">
                                                    <div class="campaign-cell-actions">
                                                        <a href="{{ url('/app/campaigns/' . $campaign->id . '/report') }}">گزارش</a>
                                                        @if($campaign->status === 'draft')
                                                            <form method="post" action="{{ url('/app/campaigns/' . $campaign->id . '/send') }}" onclick="event.stopPropagation();">
                                                                @csrf<button class="is-primary" onclick="event.stopPropagation();">ارسال</button>
                                                            </form>
                                                        @endif
                                                        <form method="post" action="{{ url('/app/campaigns/' . $campaign->id) }}" onsubmit="event.stopPropagation(); return confirm('کمپین حذف شود؟')">
                                                            @csrf @method('DELETE')
                                                            <button class="is-danger" onclick="event.stopPropagation();">حذف</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </summary>

                                            {{-- جزئیات کشویی --}}
                                            <div class="campaign-detail-panel">
                                                {{-- ستون چپ: ماموریت‌ها --}}
                                                <div class="campaign-detail-block">
                                                    <h4>ماموریت‌های کمپین <span>@fa($missions->count()) مورد</span></h4>
                                                    <div class="campaign-missions">
                                                        @forelse($missions as $mission)
                                                            <div class="campaign-mission">
                                                                <div class="campaign-mission-icon">@fa($loop->iteration)</div>
                                                                <div>
                                                                    <b>{{ $mission->title ?? $mission->name ?? 'ماموریت بدون عنوان' }}</b>
                                                                    <small>{{ $mission->description ?? 'بدون توضیح' }}</small>
                                                                </div>
                                                                <span class="campaign-mission-reward">
                                                                    @if(($mission->reward_type ?? '') === 'coin')
                                                                        @fa(number_format($mission->reward_value ?? 0)) سکه
                                                                    @elseif(($mission->reward_type ?? '') === 'discount')
                                                                        تخفیف @fa($mission->reward_value ?? 0)٪
                                                                    @else
                                                                        @fa(number_format($mission->reward_value ?? 0)) امتیاز
                                                                    @endif
                                                                </span>
                                                                <span class="campaign-mission-status {{ ($mission->is_active ?? true) ? '' : 'is-off' }}" title="{{ ($mission->is_active ?? true) ? 'فعال' : 'خاموش' }}">
                                                                    {{ ($mission->is_active ?? true) ? '✓' : '−' }}
                                                                </span>
                                                            </div>
                                                        @empty
                                                            <div class="campaign-mission-empty">
                                                                ماموریتی برای این کمپین ثبت نشده است. با دستیار می‌توانی ماموریت جدید بسازی.
                                                            </div>
                                                        @endforelse
                                                    </div>

                                                    {{-- آمار کمپین --}}
                                                    <div class="campaign-mini-stats">
                                                        <div><span>مخاطب</span><b>@fa(number_format($campaign->total ?? 0))</b></div>
                                                        <div><span>ارسال</span><b>@fa(number_format($campaign->sent ?? 0))</b></div>
                                                        <div><span>نرخ تبدیل</span><b>@fa($campaignConversion)٪</b></div>
                                                    </div>
                                                </div>

                                                {{-- ستون راست: جدول پاداش + متن پیام --}}
                                                <div class="campaign-detail-block">
                                                    <h4>جدول پاداش <span>@fa($rewards->count()) قانون</span></h4>
                                                    <div class="campaign-reward-table">
                                                        @forelse($rewards as $reward)
                                                            <div class="campaign-reward-row">
                                                                <div>
                                                                    <b>{{ $reward->title ?? $reward->event ?? 'پاداش' }}</b>
                                                                    <small>{{ $reward->description ?? '' }}</small>
                                                                </div>
                                                                @php
                                                                    $rewardClass = match($reward->type ?? 'point') {
                                                                        'coin'     => 'is-coin',
                                                                        'discount' => 'is-disc',
                                                                        default    => '',
                                                                    };
                                                                    $rewardUnit = match($reward->type ?? 'point') {
                                                                        'coin'     => 'سکه',
                                                                        'discount' => '٪ تخفیف',
                                                                        default    => 'امتیاز',
                                                                    };
                                                                @endphp
                                                                <span class="campaign-reward-value {{ $rewardClass }}">
                                                                    @fa(number_format($reward->value ?? 0)) {{ $rewardUnit }}
                                                                </span>
                                                            </div>
                                                        @empty
                                                            <div class="campaign-mission-empty">
                                                                جدول پاداشی تعریف نشده. با دستیار می‌توانی پاداش ثبت معرفی، اولین خرید و ... را اضافه کنی.
                                                            </div>
                                                        @endforelse
                                                    </div>

                                                    @if(trim((string)($campaign->message ?? '')) !== '')
                                                        <h4 style="margin-top:1rem;">متن پیام <span>{{ $channels[$campaign->channel] ?? $campaign->channel }}</span></h4>
                                                        <div class="campaign-message">{{ $campaign->message }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </details>
                                    @empty
                                        <div class="campaign-group-empty">در این گروه کمپینی وجود ندارد.</div>
                                    @endforelse
                                </div>
                            </div>

                            <a class="campaign-group-add" href="#campaignCreator">+ افزودن کمپین در گروه «{{ $group['label'] }}»</a>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>

        {{-- بخش زیر بورد: پیشنهاد دستیار (راست) + فرم ساخت کمپین (چپ) --}}
        <div class="campaign-below">
            <article class="campaign-card is-accent" style="--card-color:#10b981;">
                <header>
                    <div>
                        <span>پیشنهاد دستیار</span>
                        <h2>سناریوهای سریع رشد</h2>
                    </div>
                </header>
                <div class="campaign-suggest-list">
                    <button type="button" onclick="askCampaignAssistant('یک کمپین برای بازگرداندن مشتریان غیرفعال بساز', this)">
                        <b>بازگشت مشتریان غیرفعال</b>
                        <small>مشتریان بدون خرید اخیر، با تخفیف یا امتیاز باشگاه</small>
                    </button>
                    <button type="button" onclick="askCampaignAssistant('یک کمپین معرفی دوستان بساز که برای عضو جدید ۱۰۰ امتیاز و برای دعوت‌کننده ۱۰ سکه بدهد', this)">
                        <b>معرفی دوستان (رفرال)</b>
                        <small>پاداش معرف و عضو جدید برای رشد ارگانیک</small>
                    </button>
                    <button type="button" onclick="askCampaignAssistant('یک کمپین خرید مجدد بساز', this)">
                        <b>افزایش خرید مجدد</b>
                        <small>پیشنهاد اختصاصی برای مشتریان دارای سابقه خرید</small>
                    </button>
                    <button type="button" onclick="askCampaignAssistant('یک کمپین با ماموریت روزانه برای فعال‌سازی مشتریان بساز', this)">
                        <b>ماموریت روزانه فعال‌سازی</b>
                        <small>ماموریت‌های کوچک روزانه با پاداش امتیاز و سکه</small>
                    </button>
                </div>
            </article>

            <article class="campaign-card is-accent" style="--card-color:#8b5cf6;" id="campaignCreator">
                <header>
                    <div>
                        <span>فرم ساخت کمپین</span>
                        <h2>ساخت پیش‌نویس کمپین هدفمند</h2>
                    </div>
                </header>
                <form method="post" action="{{ url('/app/campaigns') }}" class="campaign-create-form">
                    @csrf
                    <div><label>نام کمپین</label><input name="name" required placeholder="مثلاً بازگشت مشتریان غیرفعال"></div>
                    <div><label>لینک دعوت (رفرال)، اختیاری</label><input name="referral_slug" placeholder="مثلاً comeback" class="ltr"></div>

                    <div class="campaign-form-grid">
                        <div><label>تاریخ شروع (شمسی)</label><input name="start_date" class="jdate" placeholder="۱۴۰۵/۰۵/۰۱" autocomplete="off"></div>
                        <div><label>تاریخ پایان (شمسی)</label><input name="end_date" class="jdate" placeholder="۱۴۰۵/۰۶/۰۱" autocomplete="off"></div>
                    </div>

                    <div class="campaign-form-grid">
                        <div>
                            <label>کانال</label>
                            <select name="channel" required>
                                @foreach($channels as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>رفتار خرید</label>
                            <select name="rfm_group">
                                <option value="all">همه رفتارها</option>
                                @foreach($rfmGroups as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>گروه هدف</label>
                        <select name="segment" id="campaignSegment" required onchange="toggleCampaignSegment()">
                            @foreach($segments as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="campaignSegmentIdWrap" style="display:none">
                        <label>گروه ذخیره‌شده</label>
                        <select name="segment_id">
                            @foreach($dynamicSegments as $segment)
                                <option value="{{ $segment->id }}">{{ $segment->name }} - @fa($segment->members_count ?? 0) عضو</option>
                            @endforeach
                        </select>
                    </div>

                    <div><label>موضوع پیام</label><input name="subject" placeholder="برای پیامک اختیاری است"></div>

                    <div class="campaign-form-grid">
                        <div>
                            <label>پاداش عضو جدید</label>
                            <select name="reward_event">
                                <option value="referral_registered">ثبت معرفی</option>
                                <option value="first_purchase">اولین خرید معرفی‌شده</option>
                                <option value="campaign_click">کلیک روی لینک</option>
                            </select>
                        </div>
                        <div>
                            <label>ارزش پاداش</label>
                            <input name="reward_value" type="number" min="0" step="1" placeholder="مثلاً ۱۰۰">
                        </div>
                    </div>

                    <div class="campaign-form-wide">
                        <label>متن پیام</label>
                        <textarea name="message" rows="6" required placeholder="سلام {نام}، یک پیشنهاد ویژه برای شما داریم..."></textarea>
                        <small>متغیرهای قابل استفاده: {نام}، {موبایل}، {لینک_دعوت}، {نام_کمپین}</small>
                    </div>

                    <button class="btn">ثبت پیش‌نویس کمپین</button>
                </form>
            </article>
        </div>
    </section>
</div>

<script>
    // باز و بسته کردن گروه‌های بورد کمپین
    function toggleCampaignGroup(btn) {
        const group = btn.closest('.campaign-group');
        if (!group) return;
        group.classList.toggle('is-collapsed');
        group.classList.toggle('is-open');
    }

    // نمایش گروه ذخیره‌شده
    function toggleCampaignSegment() {
        const select = document.getElementById('campaignSegment');
        const wrap   = document.getElementById('campaignSegmentIdWrap');
        if (!select || !wrap) return;
        wrap.style.display = select.value === 'custom_segs' ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', toggleCampaignSegment);

    // کپی لینک رفرال
    function copyReferralLink(link, btn) {
        try {
            navigator.clipboard.writeText(link).then(function () {
                const original = btn.textContent;
                btn.textContent = 'کپی شد ✓';
                btn.style.color = '#10b981';
                setTimeout(function () {
                    btn.textContent = original;
                    btn.style.color = '';
                }, 1400);
            });
        } catch (e) {
            const input = document.createElement('input');
            input.value = link;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            btn.textContent = 'کپی شد ✓';
            setTimeout(function () { btn.textContent = 'کپی'; }, 1400);
        }
    }

    // پرسش از دستیار برای ساخت کمپین
    // روش: مستقیم به صفحه دستیار می‌رویم و پیام را در URL می‌فرستیم.
    // اسکریپت داخل admin_assistant.blade.php پیام را از URL می‌خواند و ارسال می‌کند.
    function askCampaignAssistant(text, btn) {
        text = text || 'یک کمپین بساز';

        // بازخورد بصری روی دکمه
        if (btn) {
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            var originalHtml = btn.innerHTML;
            btn.innerHTML = '<b style="color:var(--acc)">در حال باز کردن دستیار...</b><small>لطفاً چند لحظه صبر کنید</small>';
        }

        // ساخت آدرس صفحه دستیار همراه با پیام و اطلاعات صفحه
        var assistantUrl = '{{ url("/app/assistant") }}'
            + '?prompt=' + encodeURIComponent(text)
            + '&context_type=campaign_center'
            + '&context_title=' + encodeURIComponent('مرکز کمپین‌های هوشمند')
            + '&context_url='   + encodeURIComponent(window.location.href);

        // انتقال به صفحه دستیار
        window.location.href = assistantUrl;
    }
</script>
@endsection
