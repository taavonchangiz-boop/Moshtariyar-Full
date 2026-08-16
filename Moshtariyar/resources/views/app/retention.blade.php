@extends('layouts.app')
@section('title','مرکز هوشمند بازگشت و حفظ مشتری - مشتری‌یار')
@section('description','مرکز هوشمند بازگشت و حفظ مشتری - پیش‌بینی ریزش، ارزش یک سال آینده، درآمد قابل بازگشت و ارسال کمپین بازگشت با یک کلیک')
@section('heading','مرکز هوشمند بازگشت و حفظ مشتری')
@section('subtitle','پیش‌بینی ریزش، تمرکز بر ارزشمندها و بازگرداندن هوشمند با امتیاز دو برابر')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/retention-center.css') }}">
@endpush

@php
if (!function_exists('به_ارقام_فارسی_مرکز')) {
  function به_ارقام_فارسی_مرکز($عدد){
    if ($عدد===null || $عدد==='') return '۰';
    $عدد = (string)$عدد;
    $انگلیسی = ['0','1','2','3','4','5','6','7','8','9','.',','];
    $فارسی = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٫','٬'];
    return str_replace($انگلیسی,$فارسی,$عدد);
  }
}
if (!function_exists('فرمت_تومان_مرکز')) {
  function فرمت_تومان_مرکز($مبلغ){
    if (!$مبلغ) return '۰ تومان';
    return به_ارقام_فارسی_مرکز(number_format((int)$مبلغ)).' تومان';
  }
}
$کد_فیلتر = $filter ?? 'valuable';
$جستجو = $search ?? '';
@endphp

@section('content')
<div class="retention-wrap">

  {{-- هشدار کش --}}
  <div class="retention-notice">
    <div class="retention-notice-icon">💎</div>
    <div class="retention-notice-body">
      <b>مرکز بازگشت و حفظ با تحلیل ۵۰۰ مشتری برتر به‌روزرسانی می‌شود</b>
      <small>هر بار بخش‌بندی خودکار اجرا شود، این مرکز هم تازه می‌شود. کش ۵ دقیقه‌ای برای سرعت ۱۰۰۰ کاربر همزمان.</small>
    </div>
    <div class="retention-notice-actions">
      <form method="POST" action="{{ url('/app/segments/auto') }}" style="display:inline;">
        @csrf
        <button class="btn btn-sm">🔄 اجرای بخش‌بندی خودکار الان</button>
      </form>
      <a href="{{ url('/app/reports?tab=clv') }}" class="btn btn-ghost btn-sm">📊 گزارش ارزش آینده</a>
    </div>
  </div>

  {{-- ۴ کارت اصلی --}}
  <div class="retention-kpi-grid">
    <div class="retention-kpi kpi-gold">
      <div class="kpi-head"><span class="kpi-icon">💎</span><span>ارزشمند در معرض ریزش</span></div>
      <div class="kpi-value">{{ به_ارقام_فارسی_مرکز(count($valuable_at_risk ?? [])) }} نفر</div>
      <div class="kpi-sub">تمرکز اول - میانگین ریسک {{ به_ارقام_فارسی_مرکز((int)($analysis['avg_churn_probability'] ?? 0)) }}٪</div>
      <div class="kpi-progress"><span style="width: {{ min(100, (count($valuable_at_risk ?? [])*3)) }}%"></span></div>
    </div>

    <div class="retention-kpi kpi-blue">
      <div class="kpi-head"><span class="kpi-icon">💰</span><span>درآمد قابل بازگشت</span></div>
      <div class="kpi-value">{{ فرمت_تومان_مرکز($total_recoverable ?? 0) }}</div>
      <div class="kpi-sub">پیش‌بینی ۱۲ ماه آینده از مشتریان در معرض ریزش و کم‌فعال</div>
      <div class="kpi-progress"><span style="width: 78%"></span></div>
    </div>

    <div class="retention-kpi kpi-green">
      <div class="kpi-head"><span class="kpi-icon">🎉</span><span>بازگشته‌های ۳۰ روز اخیر</span></div>
      <div class="kpi-value">{{ به_ارقام_فارسی_مرکز($recovery['recovered_30d'] ?? 0) }} نفر</div>
      <div class="kpi-sub">{{ فرمت_تومان_مرکز($recovery['recovered_revenue_30d'] ?? 0) }} درآمد بازگشتی - نرخ بازگشت {{ به_ارقام_فارسی_مرکز($recovery['recovery_rate'] ?? 0) }}٪</div>
      <div class="kpi-progress"><span style="width: {{ min(100, ($recovery['recovery_rate'] ?? 0)*2) }}%"></span></div>
    </div>

    <div class="retention-kpi kpi-purple">
      <div class="kpi-head"><span class="kpi-icon">⚡</span><span>کمپین و جریان فعال</span></div>
      <div class="kpi-value">{{ به_ارقام_فارسی_مرکز(($retention_result['workflows_created'] ?? 0) + ($retention_result['campaigns_created'] ?? 0) + 5) }} فعال</div>
      <div class="kpi-sub">{{ به_ارقام_فارسی_مرکز($analysis['total_at_risk'] ?? 0) }} در معرض خطر - {{ به_ارقام_فارسی_مرکز($analysis['total_churned'] ?? 0) }} کم‌فعال و نیازمند پیگیری ویژه</div>
      <div class="kpi-progress"><span style="width: 65%"></span></div>
    </div>
  </div>

  {{-- نوار فیلترها --}}
  <div class="retention-toolbar">
    <div class="retention-filters">
      <a href="{{ url('/app/retention?filter=valuable') }}" class="rf-btn {{ $کد_فیلتر==='valuable'?'active':'' }}">💎 ارزشمند در خطر ({{ به_ارقام_فارسی_مرکز(count($valuable_at_risk)) }})</a>
      <a href="{{ url('/app/retention?filter=at_risk') }}" class="rf-btn {{ $کد_فیلتر==='at_risk'?'active':'' }}">⚠️ در معرض خطر ({{ به_ارقام_فارسی_مرکز(count($at_risk)) }})</a>
      <a href="{{ url('/app/retention?filter=churning') }}" class="rf-btn {{ $کد_فیلتر==='churning'?'active':'' }}">🔄 کم‌فعال در آستانه ریزش ({{ به_ارقام_فارسی_مرکز(count($churning)) }})</a>
      <a href="{{ url('/app/retention?filter=lost') }}" class="rf-btn {{ $کد_فیلتر==='lost'?'active':'' }}">💀 از دست رفته ({{ به_ارقام_فارسی_مرکز(count($lost)) }})</a>
      <a href="{{ url('/app/retention?filter=recovered') }}" class="rf-btn {{ $کد_فیلتر==='recovered'?'active':'' }}">🎉 بازگشته‌ها ({{ به_ارقام_فارسی_مرکز($recovery['recovered_30d'] ?? 0) }})</a>
    </div>
    <form method="GET" action="{{ url('/app/retention') }}" class="retention-search">
      <input type="hidden" name="filter" value="{{ $کد_فیلتر }}">
      <input name="q" value="{{ $جستجو }}" placeholder="جستجوی نام یا موبایل...">
      <button class="btn btn-ghost btn-sm">🔍 جستجو</button>
    </form>
  </div>

  {{-- جدول اصلی بر اساس فیلتر --}}
  <div class="card retention-card">
    <div class="card-head">
      <div>
        <b>
          @if($کد_فیلتر==='valuable') 💎 مشتریان ارزشمند در معرض ریزش - اقدام فوری با یک کلیک
          @elseif($کد_فیلتر==='at_risk') ⚠️ مشتریان در معرض خطر
          @elseif($کد_فیلتر==='churning') 🔄 مشتریان کم‌فعال در آستانه ریزش - نیازمند اقدام فوری هوشمند
          @elseif($کد_فیلتر==='lost') 💀 مشتریان از دست رفته
          @elseif($کد_فیلتر==='recovered') 🎉 مشتریانی که برگشته‌اند - امتیاز دو برابر خودکار فعال
          @else 📋 همه بخش‌ها
          @endif
        </b>
        <small class="muted">همه تاریخ‌ها شمسی - همه اعداد فارسی - هر خرید بازگشتی امتیاز دو برابر می‌گیرد</small>
      </div>
      <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
        <button class="btn btn-ghost btn-sm" onclick="انتخاب_همه(true)">✅ انتخاب همه</button>
        <button class="btn btn-ghost btn-sm" onclick="انتخاب_همه(false)">❌ لغو انتخاب</button>
      </div>
    </div>

    <form id="bulkForm" method="POST" action="{{ url('/app/retention/bulk-campaign') }}">
      @csrf
      <div style="padding: .75rem; display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; background: var(--panel2); border-bottom:1px solid var(--line);">
        <select name="message_type" class="input" style="max-width: 320px;">
          <option value="return_45">کمپین بازگشت ۴۵ روزه - پیشنهاد حفظ مشتری ارزشمند</option>
          <option value="return_60">آخرین فرصت بازگشت - پیشنهاد ویژه با مهلت محدود</option>
          <option value="wake_sleeping">فعال‌سازی مجدد مشتریان کم‌فعال با پیشنهاد ویژه بازگشت</option>
        </select>
        <button type="submit" class="btn btn-sm">📨 ارسال گروهی برای انتخاب‌شده‌ها</button>
        <small class="muted">حداکثر ۵۰ نفر در هر ارسال - فعالیت پیگیری ۲ ساعته خودکار ساخته می‌شود</small>
      </div>

      <div class="table-wrap responsive-table">
        <table class="retention-table">
          <thead>
            <tr>
              <th><input type="checkbox" id="checkAll" onclick="انتخاب_همه(this.checked)"></th>
              <th>مشتری</th>
              <th>آخرین خرید</th>
              <th>غیبت</th>
              <th>ریسک ریزش</th>
              <th>ارزش یک سال آینده</th>
              <th>عملیات</th>
            </tr>
          </thead>
          <tbody>
            @php
              $لیست_نمایش = [];
              if ($کد_فیلتر==='valuable') $لیست_نمایش = $valuable_at_risk;
              elseif ($کد_فیلتر==='at_risk') $لیست_نمایش = $at_risk;
              elseif ($کد_فیلتر==='churning') $لیست_نمایش = $churning;
              elseif ($کد_فیلتر==='lost') $لیست_نمایش = $lost;
              elseif ($کد_فیلتر==='recovered') $لیست_نمایش = $recovery['recovered_list'] ?? [];
              else $لیست_نمایش = array_merge($valuable_at_risk, $at_risk, $churning);

              if (!empty($جستجو)) {
                $لیست_نمایش = array_values(array_filter($لیست_نمایش, function($c) use ($جستجو){
                  $نام = $c['full_name'] ?? $c['name'] ?? '';
                  $موبایل = $c['phone'] ?? '';
                  return str_contains($نام, $جستجو) || str_contains($موبایل, $جستجو);
                }));
              }
            @endphp

            @forelse($لیست_نمایش as $مشتری)
              @php
                $شناسه = $مشتری['customer_id'] ?? $مشتری['id'] ?? 0;
                $نام = $مشتری['full_name'] ?? $مشتری['name'] ?? 'بدون نام';
                $موبایل = $مشتری['phone'] ?? '---';
                $آخرین = $مشتری['last_order'] ?? null;
                $غیبت = $مشتری['recency_days'] ?? $مشتری['gap_days'] ?? 0;
                $ریسک = $مشتری['churn_probability'] ?? $مشتری['gap_days'] ?? 0;
                $رنگ = $مشتری['churn_color'] ?? '#f59e0b';
                $ارزش = $مشتری['predicted_clv_12m'] ?? $مشتری['order_total'] ?? 0;
                $تاریخ_شمسی = '---';
                try {
                  if ($مشتری['last_order'] ?? false) {
                    $j = \Modules\Core\Support\Jalali::fromCarbon(\Carbon\Carbon::parse($مشتری['last_order']));
                    $تاریخ_شمسی = $j[0].'/'.str_pad($j[1],2,'0',STR_PAD_LEFT).'/'.str_pad($j[2],2,'0',STR_PAD_LEFT);
                  } elseif (isset($مشتری['returned_at_fa'])) {
                    $تاریخ_شمسی = $مشتری['returned_at_fa'];
                  } elseif (isset($مشتری['returned_at'])) {
                    $j = \Modules\Core\Support\Jalali::fromCarbon(\Carbon\Carbon::parse($مشتری['returned_at']));
                    $تاریخ_شمسی = $j[0].'/'.str_pad($j[1],2,'0',STR_PAD_LEFT).'/'.str_pad($j[2],2,'0',STR_PAD_LEFT);
                  }
                } catch (\Throwable $e) {
                  $تاریخ_شمسی = '---';
                }
              @endphp
              <tr>
                <td><input type="checkbox" name="customer_ids[]" value="{{ $شناسه }}" class="row-check"></td>
                <td>
                  <div style="display:flex; flex-direction:column;">
                    <b>{{ $نام }}</b>
                    <small class="muted">{{ $موبایل }}</small>
                  </div>
                </td>
                <td><span class="badge">{{ $تاریخ_شمسی }}</span></td>
                <td>{{ به_ارقام_فارسی_مرکز($غیبت) }} روز</td>
                <td>
                  <span class="risk-badge" style="background: {{ $رنگ }}22; color: {{ $رنگ }}; border-color: {{ $رنگ }}44;">
                    {{ به_ارقام_فارسی_مرکز($ریسک) }}٪ {{ $مشتری['churn_label'] ?? 'بازگشته' }}
                  </span>
                </td>
                <td>{{ فرمت_تومان_مرکز($ارزش) }}</td>
                <td style="display:flex; gap:.35rem; flex-wrap:wrap;">
                  <a href="{{ url('/app/customers/'.$شناسه) }}" class="btn btn-ghost btn-sm">👁️ پرونده</a>
                  @if($کد_فیلتر!=='recovered')
                    <form method="POST" action="{{ url('/app/customers/'.$شناسه.'/return-campaign') }}" style="display:inline;">
                      @csrf
                      <button class="btn btn-sm">📩 بازگشت</button>
                    </form>
                  @else
                    <span class="badge" style="background:#10b98122; color:#059669;">✨ دو برابر شد</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" style="text-align:center; padding:1.5rem;">
                <div style="opacity:.7;">
                  @if($کد_فیلتر==='recovered')
                    هنوز بازگشتی ثبت نشده. وقتی مشتری بعد از ۴۵ روز برگردد اینجا می‌آید و امتیاز دو برابر می‌گیرد.
                  @else
                    در این بخش مشتری وجود ندارد. بخش‌بندی خودکار را اجرا کنید.
                  @endif
                </div>
              </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </form>
  </div>

  {{-- دو ستونه: قهرمانان + توزیع ریزش - نسخه ریسپانسیو و نمودار کوچک --}}
  <div class="retention-dual">
    <div class="card">
      <b>🏆 قهرمانان وفادار - الگوی بازگشت</b>
      <small class="muted">مشتریانی که سالم هستند و می‌توان از رفتارشان برای کمپین بازگشت استفاده کرد</small>
      <div style="margin-top:.75rem; display:flex; flex-direction:column; gap:.5rem;">
        @forelse($champions as $قهرمان)
          <div class="mini-customer">
            <div>
              <b>{{ $قهرمان['full_name'] ?? 'بدون نام' }}</b>
              <small class="muted">{{ به_ارقام_فارسی_مرکز($قهرمان['order_count'] ?? 0) }} خرید - {{ فرمت_تومان_مرکز($قهرمان['total_spent'] ?? 0) }}</small>
            </div>
            <span class="badge" style="background:#10b98122; color:#059669;">{{ به_ارقام_فارسی_مرکز($قهرمان['churn_probability'] ?? 0) }}٪ ریسک</span>
          </div>
        @empty
          <small class="muted">هنوز قهرمان شناسایی نشده</small>
        @endforelse
      </div>
    </div>

    <div class="card chart-card">
      <b>📊 توزیع ریسک ریزش کل مشتریان</b>
      <small class="muted">نمودار خلاصه و ریسپانسیو - برای تمرکز تیم فروش</small>
      <div class="chart-wrap">
        <canvas id="churnDistributionMini"></canvas>
      </div>
      <div class="legend-grid">
        <div><span style="background:#10b981"></span> سالم و وفادار: {{ به_ارقام_فارسی_مرکز($churn_distribution['healthy'] ?? 0) }}</div>
        <div><span style="background:#3b82f6"></span> پایدار: {{ به_ارقام_فارسی_مرکز($churn_distribution['stable'] ?? 0) }}</div>
        <div><span style="background:#f59e0b"></span> در معرض خطر: {{ به_ارقام_فارسی_مرکز($churn_distribution['at_risk'] ?? 0) }}</div>
        <div><span style="background:#ef4444"></span> در حال ریزش: {{ به_ارقام_فارسی_مرکز($churn_distribution['churning'] ?? 0) }}</div>
        <div><span style="background:#991b1b"></span> از دست رفته: {{ به_ارقام_فارسی_مرکز($churn_distribution['lost'] ?? 0) }}</div>
      </div>
    </div>
  </div>

  {{-- راهنمای امتیاز دو برابر --}}
  <div class="card retention-guide">
    <b>✨ اتوماسیون امتیاز دو برابر چگونه کار می‌کند؟</b>
    <div class="guide-grid">
      <div class="guide-step"><span>۱</span><p><b>شناسایی بازگشت</b> اگر مشتری بعد از ۴۵ روز غیبت خرید کند، سیستم خودکار تشخیص می‌دهد که بازگشته است.</p></div>
      <div class="guide-step"><span>۲</span><p><b>پاداش دو برابر</b> امتیاز همان خرید دو برابر می‌شود. اگر بالای ۶۰ روز غیبت باشد، ۲ برابر اضافه + هدیه کیف پول.</p></div>
      <div class="guide-step"><span>۳</span><p><b>ثبت فعالیت</b> یادداشت خودکار برای فروش ثبت می‌شود تا تیم بداند مشتری برگشته و قدردانی شود.</p></div>
      <div class="guide-step"><span>۴</span><p><b>نمایش در اینجا</b> در تب بازگشته‌ها می‌بینید چه کسی برگشته و چقدر درآمد بازگشتی.</p></div>
    </div>
  </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function به_فارسی(عدد){
  if(عدد===null||عدد===undefined) return '۰';
  return (''+عدد).replace(/\d/g,d=> '۰۱۲۳۴۵۶۷۸۹'[d]);
}
function انتخاب_همه(وضعیت){
  document.querySelectorAll('.row-check').forEach(el=> el.checked = وضعیت);
  const all = document.getElementById('checkAll');
  if(all) all.checked = وضعیت;
}

(function(){
  const ctx = document.getElementById('churnDistributionMini');
  if(!ctx) return;
  const data = @json($churn_distribution ?? []);
  const labels = ['سالم و وفادار','پایدار','در معرض خطر','در حال ریزش','از دست رفته'];
  const values = [
    data.healthy || 0,
    data.stable || 0,
    data.at_risk || 0,
    data.churning || 0,
    data.lost || 0
  ];
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: values,
        backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ef4444','#991b1b'],
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 1,
      cutout: '62%',
      plugins: {
        legend: { display:false },
        tooltip: {
          callbacks: {
            label: (c)=> c.label + ': ' + به_فارسی(c.parsed) + ' نفر'
          }
        }
      },
      animation: { duration: 600 }
    }
  });
})();
</script>
@endpush
@endsection
