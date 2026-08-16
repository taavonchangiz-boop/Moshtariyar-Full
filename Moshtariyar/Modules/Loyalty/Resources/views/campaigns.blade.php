@extends('layouts.app')
@section('title','کمپین‌های باشگاه')
@section('heading','مرکز مدیریت کمپین، دعوت و پاداش')
@section('subtitle','ساخت کمپین، کانال دعوت، قانون پاداش، پایش سلامت و مدیریت پاداش‌های شرط‌دار')

@section('content')
<link rel="stylesheet" href="{{ asset('css/loyalty-campaigns-board.css') }}">
<link rel="stylesheet" href="{{ asset('css/campaigns-smart-growth.css') }}">

@php
    $canManageLoyalty = auth()->user()?->hasPermission('loyalty.manage');
    $campaignRows = $campaigns->getCollection();
    $campaignGroups = $campaignRows->groupBy('status');
    $statusCards = collect([
        ['key' => 'draft', 'label' => $statuses['draft'] ?? 'پیش‌نویس', 'color' => '#64748b', 'hint' => 'نیازمند تکمیل تنظیمات و قوانین'],
        ['key' => 'active', 'label' => $statuses['active'] ?? 'فعال', 'color' => '#10b981', 'hint' => 'در حال اجرا و آماده جذب مشتری'],
        ['key' => 'paused', 'label' => $statuses['paused'] ?? 'متوقف', 'color' => '#f59e0b', 'hint' => 'موقتاً متوقف شده و نیازمند تصمیم'],
        ['key' => 'expired', 'label' => $statuses['expired'] ?? 'منقضی', 'color' => '#ef4444', 'hint' => 'زمان کمپین تمام شده یا باید تمدید شود'],
    ]);

    $totalVisibleClicks = (int) $campaignRows->sum('clicks_count');
    $totalVisibleReferrals = (int) $campaignRows->sum('referrals_count');
    $totalVisibleRewards = (int) $campaignRows->sum('rewards_count');
    $activeVisibleCampaigns = (int) $campaignRows->where('status', 'active')->count();
    $conversionRate = $totalVisibleClicks > 0 ? round(($totalVisibleReferrals / max(1, $totalVisibleClicks)) * 100, 1) : 0;
    $rulesCount = (int) $campaignRows->sum(fn ($campaign) => $campaign->rewardRules?->count() ?? 0);
    $channelsCount = (int) $campaignRows->sum(fn ($campaign) => $campaign->channels?->count() ?? 0);

    $statusTitle = function ($status) use ($statusCards, $statuses) {
        $found = $statusCards->firstWhere('key', $status);
        return $found['label'] ?? ($statuses[$status] ?? ($status ?: 'نامشخص'));
    };

    $statusColor = function ($status) use ($statusCards) {
        $found = $statusCards->firstWhere('key', $status);
        return $found['color'] ?? '#64748b';
    };

    $campaignHealth = function ($campaign, int $pendingRewards, int $releasedRewards) {
        $clicks = (int) ($campaign->clicks_count ?? 0);
        $referrals = (int) ($campaign->referrals_count ?? 0);
        $rules = $campaign->rewardRules?->count() ?? 0;
        $conversion = $clicks > 0 ? round(($referrals / max(1, $clicks)) * 100, 1) : 0;

        if ($pendingRewards > 0) {
            return ['label' => 'نیازمند بررسی پاداش', 'color' => '#f59e0b', 'score' => min(100, 55 + ($conversion * 2))];
        }

        if ($rules === 0) {
            return ['label' => 'بدون قانون پاداش', 'color' => '#ef4444', 'score' => 25];
        }

        if ($clicks === 0 && $campaign->status === 'active') {
            return ['label' => 'بدون نتیجه اولیه', 'color' => '#ef4444', 'score' => 30];
        }

        if ($conversion >= 25 || $releasedRewards > 0) {
            return ['label' => 'سالم و پربازده', 'color' => '#10b981', 'score' => min(100, 78 + $conversion)];
        }

        if ($conversion > 0) {
            return ['label' => 'در حال رشد', 'color' => '#3b82f6', 'score' => min(100, 52 + ($conversion * 2))];
        }

        return ['label' => 'آماده توسعه', 'color' => '#64748b', 'score' => 45];
    };
@endphp

<div class="campaigns-page">

    <section class="campaigns-hero">
        <div class="campaigns-hero-main">
            <div class="campaigns-eyebrow">مرکز رشد، دعوت و پاداش</div>
            <h2>کمپین‌ها، لینک‌های دعوت، کانال‌ها و پاداش‌ها را در یک مرکز کنترل حرفه‌ای مدیریت کنید</h2>
            <p>هر کمپین می‌تواند چند کانال دعوت، قانون پاداش، زمان شروع و پایان و روش آزادسازی پاداش داشته باشد. همه تاریخ‌ها شمسی هستند و همه اعداد فارسی نمایش داده می‌شوند.</p>
        </div>
        <div class="campaigns-hero-metrics">
            <div class="campaigns-metric-card">
                <span>کمپین فعال</span>
                <strong>@fa($activeVisibleCampaigns)</strong>
                <small>در صفحه فعلی</small>
            </div>
            <div class="campaigns-metric-card">
                <span>نرخ تبدیل</span>
                <strong>@fa($conversionRate)٪</strong>
                <small>ثبت‌نام نسبت به کلیک</small>
            </div>
            <div class="campaigns-metric-card">
                <span>پاداش‌های در انتظار</span>
                <strong>@fa($pendingRewards)</strong>
                <small>نیازمند آزادسازی</small>
            </div>
            <div class="campaigns-metric-card">
                <span>قانون پاداش</span>
                <strong>@fa($rulesCount)</strong>
                <small>در کمپین‌های این صفحه</small>
            </div>
        </div>
    </section>

    <section class="campaigns-insight-strip">
        <div><span class="campaigns-dot campaigns-dot-blue"></span><b>@fa($campaignRows->count())</b><small>کمپین در این صفحه</small></div>
        <div><span class="campaigns-dot campaigns-dot-green"></span><b>@fa($totalVisibleClicks)</b><small>کلیک روی لینک‌ها</small></div>
        <div><span class="campaigns-dot campaigns-dot-purple"></span><b>@fa($totalVisibleReferrals)</b><small>ثبت‌نام یا دعوت</small></div>
        <div><span class="campaigns-dot campaigns-dot-orange"></span><b>@fa($totalVisibleRewards)</b><small>کل پاداش‌ها</small></div>
        <div><span class="campaigns-dot campaigns-dot-red"></span><b>@fa($channelsCount)</b><small>کانال فعال</small></div>
    </section>

    @if($canManageLoyalty)
        <section class="campaigns-create-section">
            <header class="campaigns-create-header">
                <div>
                    <span class="campaigns-create-eyebrow">ساخت کمپین جدید</span>
                    <h3>کمپین رشد هوشمند بسازید</h3>
                    <p>عنوان، نام کوتاه، نوع، وضعیت شروع، بازه زمانی شمسی و کانال‌های دعوت را مشخص کنید. بعد از ساخت، از داخل کارت کمپین قانون پاداش تعریف کنید.</p>
                </div>
                <div class="campaigns-create-badge">فرم حرفه‌ای و ریسپانسیو</div>
            </header>

            <form method="post" action="{{ url('/app/loyalty/campaigns') }}" class="campaigns-create-form-pro">
                @csrf

                <div class="cc-form-grid">
                    <div class="cc-field">
                        <label>عنوان کمپین *</label>
                        <input name="title" required maxlength="120" placeholder="مثلا دعوت دوستان تابستانه">
                        <small>نامی که مدیر می‌بیند - کوتاه و قابل فهم</small>
                    </div>
                    <div class="cc-field">
                        <label>نام کوتاه در نشانی (انگلیسی)</label>
                        <input name="slug" class="ltr" maxlength="60" placeholder="مثلا davat-tabestan">
                        <small>فقط حروف انگلیسی، عدد و خط تیره - برای لینک دعوت</small>
                    </div>
                    <div class="cc-field">
                        <label>نوع کمپین</label>
                        <select name="type">
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cc-field">
                        <label>وضعیت شروع</label>
                        <select name="status">
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($key === 'active')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cc-field">
                        <label>تاریخ شروع شمسی</label>
                        <input name="starts_at" class="jdate" placeholder="۱۴۰۳/۰۴/۰۱ - انتخاب از تقویم">
                    </div>
                    <div class="cc-field">
                        <label>تاریخ پایان شمسی</label>
                        <input name="ends_at" class="jdate" placeholder="۱۴۰۳/۰۶/۳۱ - انتخاب از تقویم">
                    </div>
                    <div class="cc-field col-span-2">
                        <label>توضیح کمپین</label>
                        <textarea name="description" rows="3" placeholder="هدف کمپین، مخاطب، قانون کلی و توضیحات داخلی برای مدیران"></textarea>
                        <small>این توضیح فقط برای مدیران است و به مشتری نمایش داده نمی‌شود</small>
                    </div>
                </div>

                <div class="cc-block">
                    <div class="cc-block-header">
                        <b>کانال‌های فعال برای لینک دعوت</b>
                        <small>مشتری از کدام مسیر می‌تواند دعوت کند؟</small>
                    </div>
                    <div class="cc-channels">
                        @foreach($channels as $key => $label)
                            <label class="cc-channel">
                                <input type="checkbox" name="channels[]" value="{{ $key }}" @checked(in_array($key, ['direct','whatsapp','telegram']))>
                                <span class="cc-channel-dot" style="--c:#8b5cf6"></span>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="cc-block is-tip">
                    <b>💡 پیشنهاد حرفه‌ای</b>
                    <span>بعد از ساخت کمپین، از داخل کارت همان کمپین قانون پاداش تعریف کنید؛ مثلا اگر عضو جدید ثبت‌نام کرد ۱۰۰ امتیاز، اگر اولین خرید کرد ۵۰۰ امتیاز و یک کد تخفیف.</span>
                </div>

                <div class="cc-form-footer">
                    <button class="btn">ساخت کمپین جدید</button>
                    <small>بعد از ساخت به همین صفحه برمی‌گردید و می‌توانید قانون پاداش اضافه کنید</small>
                </div>

            </form>
        </section>
    @endif

    <section class="campaigns-reward-toolbar">
        <div>
            <h3>پاداش‌های شرط‌دار کمپین‌ها</h3>
            <p>پاداش‌ها می‌توانند فوری، بعد از چند روز یا پس از تایید دستی آزاد شوند.</p>
        </div>
        @if($canManageLoyalty)
            <form method="post" action="{{ url('/app/loyalty/campaign-rewards/release-due') }}">
                @csrf
                <button class="btn">آزادسازی پاداش‌های سررسید شده</button>
            </form>
        @endif
    </section>

    <section class="campaigns-board-shell">
        <div class="campaigns-board">
            @foreach($statusCards as $statusCard)
                @php
                    $columnCampaigns = $campaignGroups->get($statusCard['key'], collect());
                    $columnCount = $columnCampaigns->count();
                    $columnClicks = (int) $columnCampaigns->sum('clicks_count');
                    $columnReferrals = (int) $columnCampaigns->sum('referrals_count');
                    $ratio = $campaignRows->count() ? round(($columnCount / max(1, $campaignRows->count())) * 100) : 0;
                @endphp
                <section class="campaigns-column" style="--campaign-status-color: {{ $statusCard['color'] }}; --column-ratio: {{ $ratio }}%;">
                    <header class="campaigns-column-header">
                        <div>
                            <span class="campaigns-stage-mark"></span>
                            <h3>{{ $statusCard['label'] }}</h3>
                        </div>
                        <span class="campaigns-stage-count">@fa($columnCount)</span>
                    </header>
                    <div class="campaigns-column-meta">
                        <span>{{ $statusCard['hint'] }}</span>
                        <span>کلیک: <b>@fa($columnClicks)</b> · ثبت‌نام: <b>@fa($columnReferrals)</b></span>
                    </div>
                    <div class="campaigns-column-progress"><span></span></div>

                    <div class="campaigns-drop-zone">
                        @forelse($columnCampaigns as $campaign)
                            @php
                                $clicks = (int) ($campaign->clicks_count ?? 0);
                                $referrals = (int) ($campaign->referrals_count ?? 0);
                                $rewards = (int) ($campaign->rewards_count ?? 0);
                                $conversion = $clicks > 0 ? round(($referrals / max(1, $clicks)) * 100, 1) : 0;
                                $campaignPendingRewards = $campaign->rewards()->where('status', 'pending')->count();
                                $campaignReleasedRewards = $campaign->rewards()->where('status', 'released')->count();
                                $health = $campaignHealth($campaign, $campaignPendingRewards, $campaignReleasedRewards);
                            @endphp
                            <details class="campaign-card" style="--campaign-status-color: {{ $statusColor($campaign->status) }}; --campaign-health-color: {{ $health['color'] }}; --health-score: {{ (int) $health['score'] }}%;">
                                <summary class="campaign-card-summary">
                                    <span class="campaign-summary-arrow">⌄</span>
                                    <span class="campaign-summary-main">
                                        <b>{{ $campaign->title }}</b>
                                        <small>{{ $types[$campaign->type] ?? $campaign->type }} · {{ $statusTitle($campaign->status) }}</small>
                                    </span>
                                    <span class="campaign-summary-count">@fa($referrals)</span>
                                </summary>

                                <div class="campaign-card-body">
                                    <div class="campaign-card-top">
                                        <h4>{{ $campaign->title }}</h4>
                                        <span>{{ $campaign->description ?: 'توضیحی برای این کمپین ثبت نشده است.' }}</span>
                                    </div>

                                    <div class="campaign-health-box">
                                        <div>
                                            <span>سلامت کمپین</span>
                                            <b>{{ $health['label'] }}</b>
                                        </div>
                                        <div class="campaign-health-ring"><span>@fa((int) $health['score'])</span></div>
                                    </div>

                                    <div class="campaign-link-box">
                                        <span>شناسه کمپین</span>
                                        <b class="ltr">{{ $campaign->slug }}</b>
                                    </div>

                                    <div class="campaign-mini-grid campaign-mini-grid-wide">
                                        <div><span>کلیک</span><b>@fa($clicks)</b></div>
                                        <div><span>ثبت‌نام</span><b>@fa($referrals)</b></div>
                                        <div><span>نرخ تبدیل</span><b>@fa($conversion)٪</b></div>
                                        <div><span>پاداش</span><b>@fa($rewards)</b></div>
                                        <div><span>در انتظار</span><b>@fa($campaignPendingRewards)</b></div>
                                        <div><span>آزادشده</span><b>@fa($campaignReleasedRewards)</b></div>
                                    </div>

                                    <div class="campaign-time-row">
                                        <div><span>شروع</span><b>@jdate($campaign->starts_at)</b></div>
                                        <div><span>پایان</span><b>@jdate($campaign->ends_at)</b></div>
                                    </div>

                                    <div class="campaign-channel-list">
                                        @forelse($campaign->channels as $channel)
                                            <span>{{ $channel->label }}</span>
                                        @empty
                                            <span>کانالی ثبت نشده</span>
                                        @endforelse
                                    </div>

                                    <div class="campaign-actions-row">
                                        <a class="campaign-small-action" href="{{ url('/app/loyalty/campaigns/' . $campaign->id . '/report') }}">گزارش کمپین</a>
                                        @if($canManageLoyalty)
                                            <form method="post" action="{{ url('/app/loyalty/campaigns/' . $campaign->id . '/toggle') }}">
                                                @csrf
                                                <button class="campaign-small-action campaign-small-action-primary">{{ $campaign->status === 'active' ? 'توقف کمپین' : 'فعال‌سازی کمپین' }}</button>
                                            </form>
                                            <form method="post" action="{{ url('/app/loyalty/campaigns/' . $campaign->id) }}" onsubmit="return confirm('کمپین حذف شود؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="campaign-small-action campaign-small-action-danger">حذف کمپین</button>
                                            </form>
                                        @endif
                                    </div>

                                    <details class="campaign-rules-panel">
                                        <summary>قوانین پاداش کمپین</summary>
                                        <div class="campaign-rules-list">
                                            @forelse($campaign->rewardRules as $rule)
                                                <div class="campaign-rule-item {{ $rule->is_active ? 'is-active' : 'is-inactive' }}">
                                                    <div><b>{{ $rewardEvents[$rule->event] ?? $rule->event }}</b><span>{{ $beneficiaries[$rule->beneficiary] ?? $rule->beneficiary }}</span></div>
                                                    <div><b>{{ $rewardTypes[$rule->reward_type] ?? $rule->reward_type }}</b><span>@fa(number_format($rule->reward_value))</span></div>
                                                    <div><b>{{ $releasePolicies[$rule->release_policy] ?? $rule->release_policy }}</b><span>@if($rule->release_days) @fa($rule->release_days) روز @else بدون تأخیر @endif</span></div>
                                                    @if($canManageLoyalty)
                                                        <form method="post" action="{{ url('/app/loyalty/campaign-reward-rules/' . $rule->id . '/toggle') }}">
                                                            @csrf
                                                            <button class="campaign-rule-toggle">{{ $rule->is_active ? 'فعال' : 'غیرفعال' }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="campaign-empty-rules">هنوز قانونی ثبت نشده است.</div>
                                            @endforelse
                                        </div>

                                        @if($canManageLoyalty)
                                            <form method="post" action="{{ url('/app/loyalty/campaigns/' . $campaign->id . '/reward-rules') }}" class="campaign-rule-form-pro">
                                                @csrf
                                                <div><label>رویداد</label><select name="event">@foreach($rewardEvents as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                                                <div><label>ذی‌نفع</label><select name="beneficiary">@foreach($beneficiaries as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                                                <div><label>نوع پاداش</label><select name="reward_type">@foreach($rewardTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                                                <div><label>مقدار</label><input name="reward_value" class="ltr" value="0"></div>
                                                <div><label>آزادسازی</label><select name="release_policy">@foreach($releasePolicies as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                                                <div><label>روز تأخیر</label><input name="release_days" class="ltr" value="0"></div>
                                                <button class="btn">افزودن قانون</button>
                                            </form>
                                        @endif
                                    </details>
                                </div>
                            </details>
                        @empty
                            <div class="campaigns-empty-column">در این وضعیت کمپینی وجود ندارد.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>

    <div class="campaigns-pagination">
        {{ $campaigns->links() }}
    </div>
</div>
@endsection