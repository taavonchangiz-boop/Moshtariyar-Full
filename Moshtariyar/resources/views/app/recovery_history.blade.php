@extends('layouts.app')
@section('title','آرشیو گزارش‌های هفتگی بازگشت و حفظ')
@section('heading','تاریخچه گزارش‌های هفتگی بازگشت و حفظ')
@section('subtitle','آرشیو کامل، نمودار روند، جستجو و ارسال مجدد - تاریخ شمسی و اعداد فارسی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/reports-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/recovery-history-center.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

@php
if (!function_exists('fa_num_hist')) {
  function fa_num_hist($n){ $en=['0','1','2','3','4','5','6','7','8','9']; $fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹']; return str_replace($en,$fa,(string)$n); }
}
if (!function_exists('fa_money_hist')) {
  function fa_money_hist($n){ return fa_num_hist(number_format((int)$n)); }
}
@endphp

<div class="recovery-history-page">

  {{-- هدر --}}
  <section class="rh-hero">
    <div>
      <span class="rh-eyebrow">آرشیو خودکار • شمسی • فارسی</span>
      <h2>همه گزارش‌های هفتگی بازگشت و حفظ در یک نگاه</h2>
      <p>هر گزارشی که به صورت خودکار هر شنبه ساعت ۹ صبح تولید و به مدیران ایمیل شده، اینجا با گراف ماهانه شمسی یونیک (بدون تکرار مثل مرداد ۱۴۰۴) ذخیره شده. می‌تونی جستجو کنی، بازه انتخاب کنی، دوباره ببینی یا مجدد ارسال کنی.</p>

      <div class="rh-actions">
        <form method="get" action="{{ route('reports.recovery.history') }}" class="rh-search-row">
          <div class="rh-search-box">
            <input type="text" name="search" value="{{ $search }}" placeholder="جستجو تاریخ شمسی مثل ۱۴۰۴/۰۵ یا بخشی از خلاصه">
            <button class="btn btn-sm">🔍 جستجو</button>
          </div>
          <div class="rh-date-row">
            <div class="rh-field">
              <label>از تاریخ شمسی</label>
              <input type="text" name="from" value="{{ $from }}" placeholder="۱۴۰۴/۰۱/۰۱" class="jdate">
            </div>
            <div class="rh-field">
              <label>تا تاریخ شمسی</label>
              <input type="text" name="to" value="{{ $to }}" placeholder="۱۴۰۴/۱۲/۲۹" class="jdate">
            </div>
            <button class="btn btn-ghost btn-sm">اعمال بازه</button>
            <a href="{{ route('reports.recovery.history') }}" class="btn btn-ghost btn-sm">پاک کردن فیلتر</a>
          </div>
        </form>

        <div class="rh-toolbar">
          <form method="post" action="{{ route('reports.recovery.history.generate') }}" onsubmit="return confirm('گزارش جدید همین الان تولید و در آرشیو ذخیره شود؟')">
            @csrf
            <button class="btn">⚡ تولید گزارش همین الان</button>
          </form>
          <a href="{{ route('reports.recovery.history.export', request()->query()) }}" class="btn btn-secondary">📥 دانلود اکسل آرشیو</a>
          <a href="{{ url('/app/reports?tab=recovery') }}" class="btn btn-ghost">📊 گزارش لحظه‌ای بازگشت</a>
          <a href="{{ url('/app/retention') }}" class="btn btn-ghost">🛟 مرکز بازگشت و حفظ</a>
        </div>

        @if(session('status'))
          <div class="rh-flash">{{ session('status') }}</div>
        @endif
      </div>
    </div>

    <div class="rh-score">
      <div class="rh-score-item" style="--metric-color:#10b981;">
        <span>کل گزارش‌های آرشیو شده</span>
        <b>{{ fa_num_hist($stats['total_reports'] ?? 0) }}</b>
        <small>آخرین: {{ $stats['last_report_date'] ?? '—' }}</small>
      </div>
      <div class="rh-score-item" style="--metric-color:#0ea5e9;">
        <span>مجموع بازگشته ثبت شده</span>
        <b>{{ fa_num_hist($stats['total_recovered'] ?? 0) }}</b>
        <small>نفر در کل تاریخ</small>
      </div>
      <div class="rh-score-item" style="--metric-color:#059669;">
        <span>کل درآمد بازگشتی ثبت شده</span>
        <b>{{ fa_money_hist($stats['total_revenue'] ?? 0) }}</b>
        <small>تومان جمع</small>
      </div>
      <div class="rh-score-item" style="--metric-color:#f59e0b;">
        <span>میانگین نرخ بازگشت</span>
        <b>{{ fa_num_hist($stats['avg_rate'] ?? 0) }}٪</b>
        <small>میانگین کل آرشیو</small>
      </div>
      <div class="rh-score-item" style="--metric-color:#8b5cf6;">
        <span>۳۰ روز اخیر</span>
        <b>{{ fa_num_hist($stats['last30d'] ?? 0) }}</b>
        <small>گزارش تولید شده</small>
      </div>
      <div class="rh-score-item" style="--metric-color:#ec4899;">
        <span>رکورد درآمد یک هفته</span>
        <b>{{ fa_money_hist($stats['max_revenue'] ?? 0) }}</b>
        <small>تومان بیشترین</small>
      </div>
    </div>
  </section>

  {{-- نمودار روند آرشیو --}}
  @if($chartData->count() > 1)
  <section class="rh-card">
    <header>
      <div>
        <span class="rh-chip">📈 روند ۱۲ گزارش اخیر</span>
        <h3>رشد بازگشت مشتری در طول زمان - شمسی</h3>
        <p>محور افقی تاریخ شمسی گزارش، محور عمودی تعداد بازگشته و درآمد بازگشتی هفته - هر نقطه یک گزارش هفتگی</p>
      </div>
    </header>
    <div class="rh-chart-wrap"><canvas id="historyTrendChart"></canvas></div>
  </section>
  @endif

  {{-- جدول آرشیو --}}
  <section class="rh-card">
    <header>
      <div>
        <span class="rh-chip">📚 آرشیو کامل - صفحه‌بندی حرفه‌ای</span>
        <h3>لیست همه گزارش‌های تولید شده - {{ fa_num_hist($histories->total()) }} گزارش</h3>
        <p>روی مشاهده بزن تا گراف ماهانه شمسی یونیک، لیست ۵ برتر و جزئیات کامل همون گزارش را ببینی - می‌تونی مجدد ایمیل کنی</p>
      </div>
      <div class="rh-export-actions">
        <small style="color:var(--mut)">نمایش {{ fa_num_hist($histories->perPage()) }} تایی • صفحه {{ fa_num_hist($histories->currentPage()) }} از {{ fa_num_hist($histories->lastPage()) }}</small>
      </div>
    </header>

    @if($histories->count() > 0)
      <div class="rh-table-wrap">
        <table class="rh-table">
          <thead>
            <tr>
              <th>#</th>
              <th>تاریخ شمسی گزارش</th>
              <th>بازه گزارش</th>
              <th>بازگشته هفته</th>
              <th>درآمد هفته</th>
              <th>بازگشته ۳۰ روز</th>
              <th>نرخ بازگشت</th>
              <th>وضعیت</th>
              <th>ایمیل‌های ارسالی</th>
              <th>عملیات</th>
            </tr>
          </thead>
          <tbody>
            @foreach($histories as $idx => $h)
              <tr>
                <td class="rank">{{ fa_num_hist($histories->firstItem() + $idx) }}</td>
                <td><b>{{ $h->j_today ?? '—' }}</b><br><small class="ltr">{{ $h->report_date->format('Y-m-d') }}</small></td>
                <td><small>{{ $h->j_from ?? '—' }}<br>تا {{ $h->j_to ?? '—' }}</small></td>
                <td class="num"><b style="color:#059669">{{ fa_num_hist($h->recovered_7d) }}</b> نفر</td>
                <td class="num">{{ fa_money_hist($h->recovered_revenue_7d) }} <small>تومان</small></td>
                <td class="num">{{ fa_num_hist($h->recovered_30d) }} نفر<br><small>{{ fa_money_hist($h->recovered_revenue_30d) }} تومان</small></td>
                <td><span class="rh-badge" style="background: {{ $h->status_color }}22; color: {{ $h->status_color }};">{{ fa_num_hist($h->recovery_rate) }}٪</span></td>
                <td><span class="rh-badge" style="background: {{ $h->status_color }}14; color: {{ $h->status_color }}; border:1px solid {{ $h->status_color }}33;">{{ $h->status_label }}</span></td>
                <td><small>{{ implode('، ', array_slice($h->emails_sent ?? [], 0, 2)) }}{{ count($h->emails_sent ?? []) > 2 ? ' و '. fa_num_hist(count($h->emails_sent)-2).' مورد دیگر' : '' }}</small></td>
                <td>
                  <div class="rh-btn-group">
                    <a href="{{ route('reports.recovery.history.show', $h->id) }}" class="btn btn-sm">👁️ مشاهده کامل</a>
                    <form method="post" action="{{ route('reports.recovery.history.resend', $h->id) }}" style="display:inline;">
                      @csrf
                      <button class="btn btn-ghost btn-sm" onclick="return confirm('گزارش {{ $h->j_today }} مجدد به مدیر ارسال شود؟')">📧 ارسال مجدد</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="rh-pagination">
        {{ $histories->links('pagination::bootstrap-5') }}
      </div>
    @else
      <div class="rh-empty">
        <span>🗂️</span>
        <h3>هنوز گزارشی در آرشیو نیست</h3>
        <p>اولین گزارش خودکار هر شنبه ساعت ۹ صبح تولید می‌شود. می‌تونی الان به صورت دستی تولید کنی.</p>
        <form method="post" action="{{ route('reports.recovery.history.generate') }}" style="margin-top:1rem">
          @csrf
          <button class="btn btn-lg">⚡ تولید اولین گزارش همین الان</button>
        </form>
      </div>
    @endif
  </section>

</div>

<script>
var chartFontHist = {family:"'Estedad', 'Vazirmatn', Tahoma, sans-serif", size:12, weight:'600'};
if(window.Chart){
  try{ Chart.defaults.font.family = "'Estedad', 'Vazirmatn', Tahoma, sans-serif"; Chart.defaults.font.weight='600'; }catch(e){}
}
function faDigitHistory(n){ if(n==null) return ''; var fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹']; return String(n).replace(/\d/g,function(d){return fa[d];}); }

function initHistoryChart(){
  var el = document.getElementById('historyTrendChart');
  if(!el || !window.Chart) return;
  var labels = {!! json_encode($chartData->pluck('j_today')->toArray(), JSON_UNESCAPED_UNICODE) !!};
  var countData = {!! json_encode($chartData->pluck('recovered_7d')->toArray()) !!};
  var revenueData = {!! json_encode($chartData->pluck('recovered_revenue_7d')->toArray()) !!};

  new Chart(el, {
    type:'line',
    data:{
      labels: labels,
      datasets:[
        {label:'تعداد بازگشته هفته', data: countData, borderColor:'#0ea5e9', backgroundColor:'#0ea5e922', borderWidth:2.5, tension:.35, fill:true, pointRadius:3, yAxisID:'y'},
        {label:'درآمد بازگشتی هفته (تومان)', data: revenueData, borderColor:'#10b981', backgroundColor:'#10b98122', borderWidth:2.5, tension:.35, fill:true, pointRadius:3, yAxisID:'y1'}
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{mode:'index', intersect:false},
      plugins:{
        legend:{position:'bottom', labels:{font:chartFontHist}},
        tooltip:{callbacks:{label:function(ctx){ var l=ctx.dataset.label||''; var v=ctx.parsed.y; if(l.indexOf('درآمد')!==-1) return ' '+l+': '+faDigitHistory(v.toLocaleString())+' تومان'; return ' '+l+': '+faDigitHistory(v)+' نفر'; }}}
      },
      scales:{
        y:{type:'linear', position:'left', beginAtZero:true, ticks:{font:chartFontHist, callback:function(v){return faDigitHistory(v);}}, title:{display:true, text:'تعداد نفر', font:chartFontHist}},
        y1:{type:'linear', position:'right', beginAtZero:true, grid:{drawOnChartArea:false}, ticks:{font:chartFontHist, callback:function(v){return faDigitHistory((v/1000).toFixed(0))+'k';}}, title:{display:true, text:'تومان', font:chartFontHist}},
        x:{ticks:{font:chartFontHist, maxRotation:45}}
      }
    }
  });
}
document.addEventListener('DOMContentLoaded', function(){ setTimeout(initHistoryChart, 200); });
</script>

@endsection