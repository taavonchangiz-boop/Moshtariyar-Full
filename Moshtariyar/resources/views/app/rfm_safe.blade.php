@extends('layouts.app')
@section('title','تحلیل رفتار مشتریان')
@section('heading','تحلیل رفتار مشتریان')
@section('subtitle','گروه‌بندی ساده مشتریان بر اساس خرید و تعامل')

@section('content')
<style>
.rfm-hero{background:linear-gradient(135deg,rgba(14,165,233,.18),rgba(16,185,129,.12));border-color:rgba(56,189,248,.28)}
.seg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}.seg{border:1px solid var(--line);border-radius:16px;padding:13px;background:var(--panel2);display:flex;justify-content:space-between;gap:10px;align-items:center}.seg-dot{width:12px;height:12px;border-radius:50%;display:inline-block;margin-left:8px}.rfm-list{display:grid;gap:12px}.rfm-row{background:var(--panel2);border:1px solid var(--line);border-radius:18px;padding:14px}.rfm-top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}.rfm-chip{display:inline-flex;padding:4px 10px;border-radius:999px;font-size:.78rem;font-weight:800}.score{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px}.scorebox{background:rgba(255,255,255,.04);border:1px solid rgba(148,163,184,.16);border-radius:12px;padding:9px}.bars{display:flex;gap:3px;margin-top:6px}.bars i{height:7px;flex:1;border-radius:999px;background:#334155}.bars i.on{background:linear-gradient(90deg,var(--acc2),var(--ok))}.meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;margin-top:12px;color:var(--mut)}@media(max-width:640px){.score{grid-template-columns:1fr}.rfm-row{padding:12px}.rfm-top{display:block}.meta{grid-template-columns:1fr}}
</style>

<div class="card rfm-hero">
    <h2 style="margin:0 0 8px">تحلیل رفتار خرید مشتریان</h2>
    <p class="muted" style="margin:0">مشتریان بر اساس تازگی خرید، تعداد خرید و ارزش خرید گروه‌بندی می‌شوند تا بدانید برای هر گروه چه اقدامی مناسب‌تر است.</p>
</div>

<div class="grid grid-4">
    <div class="card stat"><div class="lbl">کل مشتریان</div><div class="num">@fa(number_format($stats['customers']))</div></div>
    <div class="card stat"><div class="lbl">مشتریان خریدکرده</div><div class="num">@fa(number_format($stats['buyers']))</div></div>
    <div class="card stat"><div class="lbl">فعال در ۳۰ روز اخیر</div><div class="num">@fa(number_format($stats['active']))</div></div>
    <div class="card stat"><div class="lbl">نیازمند بازگشت</div><div class="num">@fa(number_format($stats['at_risk']))</div></div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <h3 style="margin:0">گروه‌های مشتریان</h3>
        <a class="btn btn-ghost" href="{{ url('/app/reports/rfm/export?segment='.$selectedSegment) }}">دریافت فایل خروجی</a>
    </div>
    <div class="seg-grid">
        <a class="seg" href="{{ url('/app/reports/rfm') }}">
            <span><span class="seg-dot" style="background:#38bdf8"></span>همه مشتریان</span>
            <b>@fa($stats['customers'])</b>
        </a>
        @foreach($segments as $s)
            <a class="seg" href="{{ url('/app/reports/rfm?segment='.$s['key']) }}" style="{{ $selectedSegment===$s['key'] ? 'border-color:'.$s['color'] : '' }}">
                <span><span class="seg-dot" style="background:{{ $s['color'] }}"></span>{{ $s['label'] }}</span>
                <b>@fa($s['count'])</b>
            </a>
        @endforeach
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0">جزئیات مشتریان</h3>
    <div class="rfm-list">
        @forelse($rows as $r)
            <div class="rfm-row">
                <div class="rfm-top">
                    <div>
                        <a class="customer-link" href="{{ $r['url'] }}" style="font-weight:900;color:var(--acc)">{{ $r['name'] }}</a>
                        <div class="muted">{{ $r['phone'] ?: ($r['email'] ?: 'بدون اطلاعات تماس') }}</div>
                    </div>
                    <span class="rfm-chip" style="background:{{ $r['color'] }}22;color:{{ $r['color'] }}">{{ $r['segment'] }}</span>
                </div>

                <div class="meta">
                    <div>آخرین خرید: <b>{{ $r['last_order_j'] }}</b></div>
                    <div>فاصله از آخرین خرید: <b>{{ is_numeric($r['days_since']) ? \Modules\Core\Support\Num::fa($r['days_since']).' روز' : 'بدون خرید' }}</b></div>
                    <div>تعداد خرید: <b>@fa(number_format($r['orders_count']))</b></div>
                    <div>مجموع خرید: <b>@money($r['total_spent'])</b></div>
                </div>

                <div class="score">
                    @foreach(['r'=>'تازگی خرید','f'=>'تعداد خرید','m'=>'ارزش خرید'] as $k=>$label)
                        <div class="scorebox">
                            <b>{{ $label }}</b>
                            <div class="bars">
                                @for($i=1;$i<=5;$i)
                                    <i class="{{ $i <= $r[$k] ? 'on' : '' }}"></i>
                                @endfor
                            </div>
                            <small class="muted">@fa($r[$k]) از ۵</small>
                        </div>
                    @endforeach
                </div>

                <div class="card" style="margin:12px 0 0;padding:10px;background:rgba(56,189,248,.08)">
                    <b>پیشنهاد اقدام:</b> <span class="muted">{{ $r['suggestion'] }}</span>
                </div>
            </div>
        @empty
            <div class="empty">داده‌ای برای نمایش وجود ندارد.</div>
        @endforelse
    </div>
    <div style="margin-top:14px">{{ $rows->links() }}</div>
</div>
@endsection
