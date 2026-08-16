@extends('layouts.app')
@section('title','جزئیات گزارش بازگشت و حفظ - '.($history->j_today ?? ''))
@section('heading','جزئیات گزارش • '.$history->j_today)
@section('subtitle','مشاهده کامل گراف ماهانه شمسی یونیک، ۵ برتر و امکان ارسال مجدد')

@section('content')
<link rel="stylesheet" href="{{ asset('css/reports-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/recovery-history-center.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

@php
if (!function_exists('fa_num_det')) {
  function fa_num_det($n){ $en=['0','1','2','3','4','5','6','7','8','9']; $fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹']; return str_replace($en,$fa,(string)$n); }
}
if (!function_exists('fa_money_det')) {
  function fa_money_det($n){ return fa_num_det(number_format((int)$n)); }
}
$labels = $data['monthly_labels'] ?? $history->monthly_labels ?? [];
$recovered = $data['monthly_recovered'] ?? $history->monthly_recovered ?? [];
$count = $data['monthly_count'] ?? $history->monthly_count ?? [];
$list = $data['recovered_list'] ?? $history->recovered_list ?? [];
@endphp

<div class="recovery-history-page">

  <section class="rh-hero" style="grid-template-columns: minmax(0,1.2fr) minmax(18rem,.8fr);">
    <div>
      <span class="rh-eyebrow">گزارش شماره {{ fa_num_det($history->id) }} • آرشیو شده</span>
      <h2>گزارش {{ $history->j_today }} - {{ fa_num_det($history->recovered_7d) }} بازگشته • {{ fa_money_det($history->recovered_revenue_7d) }} تومان بازگشتی</h2>
      <p>بازه: {{ $history->j_from }} تا {{ $history->j_to }} • نرخ بازگشت {{ fa_num_det($history->recovery_rate) }}٪ • در معرض خطر {{ fa_num_det($history->total_at_risk) }} نفر • این گزارش به صورت خودکار تولید و به مدیران ایمیل شده و در آرشیو دائمی ذخیره شده.</p>

      <div class="rh-actions" style="margin-top:1rem">
        <div class="rh-toolbar">
          <a href="{{ route('reports.recovery.history') }}" class="btn btn-ghost">← بازگشت به آرشیو</a>
          <form method="post" action="{{ route('reports.recovery.history.resend', $history->id) }}" style="display:inline;">
            @csrf
            <button class="btn">📧 ارسال مجدد همین گزارش به مدیر</button>
          </form>
          <a href="{{ url('/app/retention') }}" class="btn btn-secondary">🛟 مرکز بازگشت و حفظ</a>
          <a href="{{ url('/app/reports?tab=recovery') }}" class="btn btn-ghost">📊 گزارش لحظه‌ای</a>
        </div>
        @if(session('status'))<div class="rh-flash">{{ session('status') }}</div>@endif
      </div>

      <div class="rh-summary-box">
        <pre style="white-space:pre-wrap; font-family:inherit; margin:0; line-height:2;">{{ $history->summary }}</pre>
      </div>

      @if(!empty($history->emails_sent))
      <div class="rh-email-box">
        <b>📧 ایمیل‌های ارسالی برای این گزارش:</b>
        <div style="margin-top:.4rem; display:flex; flex-wrap:wrap; gap:.35rem;">
          @foreach($history->emails_sent as $em)
            <span class="rh-badge" style="background:#f1f5f9; color:#334155; border:1px solid #e2e8f0;">{{ $em }}</span>
          @endforeach
        </div>
      </div>
      @endif
    </div>

    <div class="rh-score">
      <div class="rh-score-item" style="--metric-color:#10b981;"><span>بازگشته این هفته</span><b>{{ fa_num_det($history->recovered_7d) }}</b><small>{{ fa_money_det($history->recovered_revenue_7d) }} تومان</small></div>
      <div class="rh-score-item" style="--metric-color:#059669;"><span>بازگشته ۳۰ روزه</span><b>{{ fa_num_det($history->recovered_30d) }}</b><small>{{ fa_money_det($history->recovered_revenue_30d) }} تومان</small></div>
      <div class="rh-score-item" style="--metric-color:#0ea5e9;"><span>نرخ بازگشت</span><b>{{ fa_num_det($history->recovery_rate) }}٪</b><small>از {{ fa_num_det($history->total_at_risk) }} در خطر</small></div>
      <div class="rh-score-item" style="--metric-color:#8b5cf6;"><span>کل ۱۲ ماه</span><b>{{ fa_money_det($history->total_recovered_12m) }}</b><small>{{ fa_num_det($history->total_count_12m) }} نفر بازگشته</small></div>
    </div>
  </section>

  <div class="rh-grid-2">
    <section class="rh-card">
      <header><div><span class="rh-chip">📈 نمودار ماهانه شمسی یونیک</span><h3>درآمد بازگشتی - ۱۲ ماه اخیر بدون تکرار</h3><p>هر ستون یک ماه شمسی با سال مثل مرداد ۱۴۰۴ - بازگشت = خرید بعد از ۴۵ روز غیبت</p></div></header>
      <div class="rh-chart-wrap"><canvas id="detailRecoveryChart"></canvas></div>

      <div class="rh-table-wrap" style="margin-top:1rem">
        <table class="rh-table">
          <thead><tr><th>ماه شمسی</th><th>تعداد</th><th>درآمد بازگشتی</th></tr></thead>
          <tbody>
            @forelse(array_map(null, $labels, $count, $recovered) as $row)
              <tr><td><b>{{ $row[0] ?? '—' }}</b></td><td class="num">{{ fa_num_det($row[1] ?? 0) }} نفر</td><td class="num">{{ fa_money_det($row[2] ?? 0) }} تومان</td></tr>
            @empty
              <tr><td colspan="3"><div class="rh-empty"><h3>داده ماهانه موجود نیست</h3></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="rh-card">
      <header><div><span class="rh-chip">🎉 ۵ بازگشته برتر این گزارش</span><h3>چه کسانی امتیاز دو برابر گرفته‌اند؟</h3><p>این مشتریان بعد از ۴۵ روز غیبت برگشته‌اند و سیستم به صورت خودکار امتیاز دو برابر داده</p></div></header>

      <div class="rh-table-wrap">
        <table class="rh-table">
          <thead><tr><th>#</th><th>نام</th><th>موبایل</th><th>غیبت</th><th>مبلغ</th><th>تاریخ شمسی</th></tr></thead>
          <tbody>
            @forelse(array_slice($list,0,10) as $i=>$c)
              <tr>
                <td class="rank">{{ fa_num_det($i+1) }}</td>
                <td><b>{{ $c['full_name'] ?? '---' }}</b></td>
                <td>{{ fa_num_det($c['phone'] ?? '---') }}</td>
                <td class="num">{{ fa_num_det($c['gap_days'] ?? 0) }} روز</td>
                <td class="num">{{ fa_money_det($c['order_total'] ?? $c['total'] ?? 0) }}</td>
                <td class="num">{{ $c['returned_at_fa'] ?? $c['date_fa'] ?? '---' }}</td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="rh-empty"><h3>لیست برتر خالی است</h3><p>هنوز بازگشتی برای این بازه ثبت نشده</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="margin-top:1rem; padding:1rem; border-radius:.8rem; background: linear-gradient(135deg, rgba(16,185,129,.08), rgba(14,165,233,.06)); border:1px solid rgba(16,185,129,.18);">
        <b>✨ اتوماسیون امتیاز دو برابر</b>
        <p style="margin:.4rem 0 0; font-size:.85rem; line-height:2; color:var(--mut);">
          هر مشتری که بعد از ۴۵ روز غیبت برگردد، خودکار امتیاز دو برابر می‌گیرد و یادداشت بازگشت ثبت می‌شود. این گزارش در آرشیو دائمی ذخیره شده و هر وقت بخواهی می‌تونی مجدد ببینی یا ارسال کنی.
        </p>
      </div>
    </section>
  </div>

  <section class="rh-card" style="margin-top:1rem">
    <header><div><span class="rh-chip">🧾 داده خام JSON برای توسعه</span><h3>اطلاعات کامل گزارش به صورت JSON</h3><p>برای دیباگ یا اتصال به سیستم‌های دیگر - همه تاریخ‌ها شمسی با سال فارسی یونیک</p></div></header>
    <pre style="background:#0b1220; color:#7dd3fc; padding:1rem; border-radius:.8rem; overflow:auto; direction:ltr; text-align:left; font-size:.82rem; max-height:24rem;">{{ json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
  </section>

</div>

<script>
var chartFontDet = {family:"'Estedad', 'Vazirmatn', Tahoma, sans-serif", size:12, weight:'600'};
if(window.Chart){ try{ Chart.defaults.font.family = "'Estedad', 'Vazirmatn', Tahoma, sans-serif"; Chart.defaults.font.weight='600'; }catch(e){} }
function faDet(n){ if(n==null) return ''; var fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹']; return String(n).replace(/\d/g,function(d){return fa[d];}); }
function initDetailChart(){
  var el=document.getElementById('detailRecoveryChart');
  if(!el || !window.Chart) return;
  var labels = {!! json_encode($labels, JSON_UNESCAPED_UNICODE) !!};
  var rec = {!! json_encode($recovered) !!};
  var cnt = {!! json_encode($count) !!};
  new Chart(el, {
    type:'bar',
    data:{
      labels: labels,
      datasets:[
        {label:'درآمد بازگشتی (تومان)', data: rec, backgroundColor:'#10b981', borderRadius:6, yAxisID:'y'},
        {label:'تعداد بازگشته', data: cnt, backgroundColor:'#0ea5e9', borderRadius:6, yAxisID:'y1'}
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{mode:'index', intersect:false},
      plugins:{
        legend:{position:'bottom', labels:{font:chartFontDet}},
        tooltip:{callbacks:{label:function(ctx){ var l=ctx.dataset.label||''; var v=ctx.parsed.y; if(l.indexOf('درآمد')!==-1) return ' '+l+': '+faDet(v.toLocaleString())+' تومان'; return ' '+l+': '+faDet(v)+' نفر'; }}}
      },
      scales:{
        y:{type:'linear', position:'left', beginAtZero:true, ticks:{font:chartFontDet, callback:function(v){return faDet((v/1000).toFixed(0))+'k';}}, title:{display:true, text:'تومان', font:chartFontDet}},
        y1:{type:'linear', position:'right', beginAtZero:true, grid:{drawOnChartArea:false}, ticks:{font:chartFontDet, callback:function(v){return faDet(v);}}, title:{display:true, text:'نفر', font:chartFontDet}},
        x:{ticks:{font:chartFontDet, maxRotation:45}}
      }
    }
  });
}
document.addEventListener('DOMContentLoaded', function(){ setTimeout(initDetailChart, 200); });
</script>

@endsection