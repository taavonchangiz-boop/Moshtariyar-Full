@extends('layouts.app')
@section('title', 'گزارش تحلیلی کمپین')
@section('heading', 'گزارش تحلیلی و میزان سودآوری کمپین')
@section('subtitle', 'بررسی دقیق بازدهی و عملکرد کمپین «' . $campaign->name . '»')

@section('content')
@php
    $refUrl = url('/club/c/' . ($campaign->referral_slug ?: $campaign->id));
    $sent = $campaign->sent;
    $total = $campaign->total;
    $clicks = $campaign->clicks_count;
    $conversions = $campaign->conversions_count;
    $revenue = $campaign->roi_revenue;

    $deliverRate = $total > 0 ? round(($sent / $total) * 100) : 0;
    $clickRate   = $sent > 0 ? round(($clicks / $sent) * 100, 1) : 0;
    $convRate    = $clicks > 0 ? round(($conversions / $clicks) * 100, 1) : 0;

    $channelLabels = [
        'whatsapp' => 'واتساپ',
        'telegram' => 'تلگرام',
        'bale' => 'بله',
        'eitaa' => 'ایتا',
        'email' => 'ایمیل',
        'sms' => 'پیامک',
    ];

    $segmentLabels = [
        'all' => 'همه مشتریان',
        'customers' => 'همه مشتریان',
        'buyers' => 'خریداران',
        'inactive' => 'غیرفعال‌ها',
        'vip' => 'مشتریان ویژه',
        'new' => 'مشتریان جدید',
    ];

    $rfmLabels = [
        'all' => 'همه گروه‌ها',
        'champions' => 'خریداران خیلی وفادار',
        'loyal' => 'خریداران وفادار',
        'potential' => 'خریداران رو به رشد',
        'new' => 'خریداران تازه',
        'at_risk' => 'در خطر ریزش',
        'hibernating' => 'کم‌تحرک',
        'lost' => 'از دست رفته',
        'no_purchase' => 'بدون خرید',
    ];

    $channelLabel = $channelLabels[$campaign->channel] ?? 'پیام‌رسان';
    $segmentLabel = $segmentLabels[$campaign->segment] ?? $campaign->segment;
    $rfmLabel = $rfmLabels[$campaign->rfm_group] ?? $campaign->rfm_group;
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <a href="{{ url('/app/campaigns') }}" class="btn btn-ghost" style="padding:10px 18px;font-size:.95rem;font-weight:800;border-radius:12px;">← بازگشت به فهرست کمپین‌ها</a>
    <div style="display:flex;gap:10px;">
        <button onclick="window.print()" class="btn" style="padding:10px 18px;font-size:.95rem;font-weight:800;border-radius:12px;">🖨️ چاپ یا ذخیره گزارش</button>
    </div>
</div>

<div class="card" style="border-radius:24px;padding:30px;background:var(--panel);box-shadow:0 12px 35px rgba(0,0,0,.15);margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px;border-bottom:1px solid var(--line);padding-bottom:20px;margin-bottom:24px;">
        <div>
            <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(56,189,248,.12);color:#38bdf8;font-weight:900;font-size:.85rem;border-radius:999px;margin-bottom:10px;">
                کمپین پیشرفته (ارسال از طریق {{ $channelLabel }})
            </div>
            <h2 style="margin:0 0 6px;font-size:1.6rem;font-weight:900;color:var(--txt);">{{ $campaign->name }}</h2>
            <div class="muted" style="font-size:.95rem;">گروه دریافت‌کنندگان: <b style="color:var(--acc);">{{ $segmentLabel }}</b> · سابقه خرید: <b style="color:#f59e0b;">{{ $rfmLabel }}</b></div>
        </div>
        <div style="background:var(--panel2);border:1px solid var(--line);padding:14px 20px;border-radius:16px;text-align:right;">
            <div class="muted" style="font-size:.78rem;margin-bottom:4px;font-weight:700;">لینک دعوت پایه</div>
            <code style="font-size:.95rem;color:var(--acc);direction:ltr;display:block;">{{ $refUrl }}</code>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <div style="background:var(--panel2);border:1px solid var(--line);padding:22px;border-radius:20px;border-bottom:4px solid #38bdf8;">
            <div class="muted" style="font-size:.85rem;font-weight:700;margin-bottom:6px;">مجموع پیام‌های ارسالی</div>
            <div style="font-size:1.8rem;font-weight:900;color:var(--txt);">@fa($sent) <span style="font-size:.9rem;color:var(--mut);font-weight:500;">از @fa($total)</span></div>
            <div style="font-size:.8rem;color:#38bdf8;font-weight:800;margin-top:6px;">تحویل موفق @fa($deliverRate)٪</div>
        </div>

        <div style="background:var(--panel2);border:1px solid var(--line);padding:22px;border-radius:20px;border-bottom:4px solid #f59e0b;">
            <div class="muted" style="font-size:.85rem;font-weight:700;margin-bottom:6px;">کلیک روی لینک دعوت</div>
            <div style="font-size:1.8rem;font-weight:900;color:var(--txt);">@fa($clicks) <span style="font-size:.9rem;color:var(--mut);font-weight:500;">نفر</span></div>
            <div style="font-size:.8rem;color:#f59e0b;font-weight:800;margin-top:6px;">درصد بازدید @fa($clickRate)٪</div>
        </div>

        <div style="background:var(--panel2);border:1px solid var(--line);padding:22px;border-radius:20px;border-bottom:4px solid #10b981;">
            <div class="muted" style="font-size:.85rem;font-weight:700;margin-bottom:6px;">ثبت‌نام یا خرید موفق نهایی</div>
            <div style="font-size:1.8rem;font-weight:900;color:var(--txt);">@fa($conversions) <span style="font-size:.9rem;color:var(--mut);font-weight:500;">مورد</span></div>
            <div style="font-size:.8rem;color:#10b981;font-weight:800;margin-top:6px;">درصد تبدیل @fa($convRate)٪</div>
        </div>

        <div style="background:var(--panel2);border:1px solid var(--line);padding:22px;border-radius:20px;border-bottom:4px solid #a78bfa;">
            <div class="muted" style="font-size:.85rem;font-weight:700;margin-bottom:6px;">سودآوری کل کمپین</div>
            <div style="font-size:1.8rem;font-weight:900;color:#38bdf8;">@money($revenue)</div>
            <div style="font-size:.8rem;color:#a78bfa;font-weight:800;margin-top:6px;">درآمد مستقیم به‌دست‌آمده</div>
        </div>
    </div>
</div>

<div class="card" style="border-radius:24px;padding:30px;background:var(--panel);box-shadow:0 12px 35px rgba(0,0,0,.15);margin-bottom:24px;">
    <h3 style="margin:0 0 16px;font-size:1.3rem;font-weight:900;color:var(--txt);">🔻 مسیر تبدیل مشتری</h3>
    <p class="muted" style="margin:0 0 24px;font-size:.9rem;">روند تعامل مخاطبان از لحظهٔ دریافت پیام تا نهایی‌سازی ثبت‌نام و خرید.</p>

    <div style="display:grid;gap:14px;max-width:850px;margin:0 auto;">
        <div style="display:flex;align-items:center;background:var(--panel2);border-radius:16px;padding:16px 22px;border:1px solid var(--line);overflow:hidden;position:relative;">
            <div style="position:absolute;top:0;bottom:0;right:0;width:100%;background:rgba(56,189,248,.08);z-index:1;"></div>
            <div style="font-size:1.6rem;margin-left:16px;z-index:2;">📨</div>
            <div style="flex:1;z-index:2;">
                <div style="font-size:1.05rem;font-weight:900;color:var(--txt);">۱. ارسال و تحویل پیام</div>
                <div class="muted" style="font-size:.85rem;margin-top:2px;">پیام به دریافت‌کنندگان موردنظر تحویل داده شد</div>
            </div>
            <div style="z-index:2;text-align:left;">
                <div style="font-size:1.3rem;font-weight:900;color:#38bdf8;">@fa($sent) <span style="font-size:.85rem;color:var(--mut);">نفر</span></div>
                <div style="font-size:.8rem;font-weight:700;color:var(--mut);">@fa($deliverRate)٪ کل</div>
            </div>
        </div>

        @php $w2 = $sent > 0 ? max(20, round(($clicks / $sent) * 100)) : 0; @endphp
        <div style="display:flex;align-items:center;background:var(--panel2);border-radius:16px;padding:16px 22px;border:1px solid var(--line);overflow:hidden;position:relative;">
            <div style="position:absolute;top:0;bottom:0;right:0;width:{{ $w2 }}%;background:rgba(245,158,11,.12);z-index:1;transition:width .5s;"></div>
            <div style="font-size:1.6rem;margin-left:16px;z-index:2;">🔗</div>
            <div style="flex:1;z-index:2;">
                <div style="font-size:1.05rem;font-weight:900;color:var(--txt);">۲. بازدید از طریق لینک دعوت</div>
                <div class="muted" style="font-size:.85rem;margin-top:2px;">مخاطبان روی لینک اختصاصی خود کلیک کرده و وارد باشگاه شدند</div>
            </div>
            <div style="z-index:2;text-align:left;">
                <div style="font-size:1.3rem;font-weight:900;color:#f59e0b;">@fa($clicks) <span style="font-size:.85rem;color:var(--mut);">نفر</span></div>
                <div style="font-size:.8rem;font-weight:700;color:var(--mut);">@fa($clickRate)٪ تبدیل</div>
            </div>
        </div>

        @php $w3 = $clicks > 0 ? max(15, round(($conversions / $clicks) * 100)) : 0; @endphp
        <div style="display:flex;align-items:center;background:var(--panel2);border-radius:16px;padding:16px 22px;border:1px solid var(--line);overflow:hidden;position:relative;">
            <div style="position:absolute;top:0;bottom:0;right:0;width:{{ $w3 }}%;background:rgba(16,185,129,.15);z-index:1;transition:width .5s;"></div>
            <div style="font-size:1.6rem;margin-left:16px;z-index:2;">🎉</div>
            <div style="flex:1;z-index:2;">
                <div style="font-size:1.05rem;font-weight:900;color:var(--txt);">۳. ثبت‌نام یا خرید موفق نهایی</div>
                <div class="muted" style="font-size:.85rem;margin-top:2px;">کاربران جدید با لینک معرفی ثبت‌نام کرده و پاداش‌ها اهدا شد</div>
            </div>
            <div style="z-index:2;text-align:left;">
                <div style="font-size:1.3rem;font-weight:900;color:#10b981;">@fa($conversions) <span style="font-size:.85rem;color:var(--mut);">نفر</span></div>
                <div style="font-size:.8rem;font-weight:700;color:var(--mut);">@fa($convRate)٪ نهایی</div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="border-radius:24px;padding:30px;background:var(--panel);box-shadow:0 12px 35px rgba(0,0,0,.15);">
    <h3 style="margin:0 0 12px;font-size:1.2rem;font-weight:800;color:var(--txt);">📄 متن پیام ارسال‌شده در این کمپین</h3>
    <div style="background:var(--panel2);border:1px solid var(--line);border-radius:16px;padding:20px;font-size:1.05rem;line-height:2;color:var(--txt);white-space:pre-wrap;">{{ $campaign->message }}</div>
</div>
@endsection
