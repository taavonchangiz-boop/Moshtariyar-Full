@extends('layouts.app')
@section('title','تحلیل هوشمند')
@section('heading','تحلیل هوشمند مشتریان')
@section('subtitle','سگمنت‌بندی، ریزش و مشتریان وفادار')

@section('content')
@php
    $colors = ['مشتری وفادار ویژه'=>'#10b981','مشتری خوب'=>'#38bdf8','عادی'=>'#a78bfa','در خطر ریزش'=>'#f59e0b','کم‌فعال'=>'#ef4444','غیرفعال'=>'#94a3b8'];
    $total = array_sum($distribution) ?: 1;
    // ساخت conic-gradient برای نمودار دایره‌ای
    $stops = []; $acc = 0;
    foreach ($distribution as $seg => $cnt) {
        $start = $acc / $total * 360; $acc += $cnt; $end = $acc / $total * 360;
        $col = $colors[$seg] ?? '#64748b';
        $stops[] = "{$col} {$start}deg {$end}deg";
    }
    $gradient = count($stops) ? 'conic-gradient('.implode(',', $stops).')' : '#334155';
@endphp

<div class="grid grid-2">
    <div class="card">
        <h3 style="margin-top:0">گروه‌بندی مشتریان بر پایه رفتار خرید</h3>
        <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
            <div style="width:160px;height:160px;border-radius:50%;background:{{ $gradient }};flex-shrink:0;
                        display:flex;align-items:center;justify-content:center">
                <div style="width:90px;height:90px;border-radius:50%;background:var(--panel);display:flex;flex-direction:column;align-items:center;justify-content:center">
                    <b style="font-size:1.3rem">@fa(number_format($total))</b><span class="muted" style="font-size:.72rem">مشتری</span>
                </div>
            </div>
            <div style="flex:1;min-width:180px">
                @forelse($distribution as $seg => $cnt)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0">
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $colors[$seg] ?? '#64748b' }};margin-left:6px"></span>{{ $seg }}</span>
                        <span class="muted">@fa($cnt) (@fa(round($cnt/$total*100))٪)</span>
                    </div>
                @empty
                    <div class="empty">داده‌ای موجود نیست.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0">💡 این بخش چه می‌گوید؟</h3>
        <p class="muted" style="line-height:2">این تحلیل بر اساس سه معیار انجام می‌شود:
        <b>تازگی آخرین خرید</b>، <b>تعداد خرید</b> و <b>مجموع مبلغ خرید</b>.
        مشتریان به‌صورت خودکار در گروه‌های وفادار، خوب، عادی، در خطر ریزش و کم‌فعال دسته‌بندی می‌شوند
        تا بدانید روی چه کسانی تمرکز کنید.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3 style="margin-top:0">⚠️ مشتریان در خطر ریزش</h3>
        <div class="table-wrap"><table>
            <thead><tr><th>مشتری</th><th>ریسک ریزش</th><th>آخرین خرید</th></tr></thead>
            <tbody>
            @forelse($atRisk as $row)
                <tr>
                    <td><a href="{{ url('/app/customers/'.$row['c']->id) }}">{{ $row['c']->full_name }}</a></td>
                    <td>
                        <div style="background:#172033;border-radius:6px;height:8px;width:80px;overflow:hidden;display:inline-block;vertical-align:middle">
                            <div style="height:100%;width:{{ $row['i']['churn_risk'] }}%;background:#ef4444"></div>
                        </div>
                        <span class="muted" style="margin-right:6px">@fa($row['i']['churn_risk'])٪</span>
                    </td>
                    <td class="muted">@fa($row['i']['rfm']['recency_days']) روز پیش</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">مشتری پرخطری شناسایی نشد.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <h3 style="margin-top:0">⭐ مشتریان وفادار ویژه</h3>
        <div class="table-wrap"><table>
            <thead><tr><th>مشتری</th><th>تعداد خرید</th><th>مجموع خرید</th></tr></thead>
            <tbody>
            @forelse($vip as $row)
                <tr>
                    <td><a href="{{ url('/app/customers/'.$row['c']->id) }}">{{ $row['c']->full_name }}</a></td>
                    <td class="ltr">@fa($row['i']['rfm']['frequency'])</td>
                    <td>@money($row['i']['rfm']['monetary'])</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">مشتری وفاداری شناسایی نشد.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
