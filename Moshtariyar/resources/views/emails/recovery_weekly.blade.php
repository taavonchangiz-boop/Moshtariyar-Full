<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>گزارش هفتگی بازگشت و حفظ - {{ $jToday }}</title>
<style>
/* فونت فارسی برای کلاینت‌هایی که پشتیبانی می‌کنند - fallback به تاهوما */
body, table, td, div, p, a, span, b, small { font-family: 'Estedad', 'Vazirmatn', 'Vazirmatn FD', Tahoma, sans-serif !important; }
</style>
</head>
<body style="margin:0; padding:0; background:#f4f7fa; direction:rtl; font-family: 'Estedad', 'Vazirmatn', Tahoma, sans-serif;">

@php
if (!function_exists('fa_num_email')) {
  function fa_num_email($n){
    $en=['0','1','2','3','4','5','6','7','8','9',','];
    $fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٬'];
    return str_replace($en,$fa,(string)$n);
  }
}
if (!function_exists('fa_money_email')) {
  function fa_money_email($n){ return fa_num_email(number_format((int)$n)); }
}
$monthlyLabels = $data['monthly_labels'] ?? [];
$monthlyRecovered = $data['monthly_recovered'] ?? [];
$monthlyCount = $data['monthly_count'] ?? [];
$maxRevenue = !empty($monthlyRecovered) ? max($monthlyRecovered) : 1;
$maxCount = !empty($monthlyCount) ? max($monthlyCount) : 1;
$recoveredList = $data['recovered_list'] ?? array_slice($data['recovered_in_range'] ?? [], 0, 5);
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fa; padding:24px 0;">
<tr><td align="center">
<table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0; max-width:92%;">
<tr>
<td style="background: linear-gradient(135deg, #10b981, #0ea5e9); background-color:#10b981; padding:20px 24px; color:#ffffff;">
  <div style="font-size:22px; font-weight:900; line-height:1.4;">🛟 گزارش هفتگی بازگشت و حفظ - {{ $jToday }}</div>
  <div style="font-size:13px; opacity:.9; margin-top:6px; line-height:1.6;">بازه: {{ $jFrom }} تا {{ $jTo }} • نرخ بازگشت {{ fa_num_email($data['recovery_rate'] ?? 0) }}٪ • سیستم فعال و خودکار امتیاز دو برابر می‌دهد</div>
</td>
</tr>

<tr>
<td style="padding:20px 24px;">
  <div style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:12px;">📊 خلاصه عملکرد این هفته</div>
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td width="25%" style="padding:4px;">
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px; text-align:center;">
          <div style="font-size:11px; color:#64748b; font-weight:700;">بازگشته این هفته</div>
          <div style="font-size:20px; font-weight:900; color:#059669; margin-top:4px;">{{ fa_num_email($data['recovered_7d'] ?? 0) }} نفر</div>
          <div style="font-size:11px; color:#059669; margin-top:2px;">{{ fa_money_email($data['recovered_revenue_7d'] ?? 0) }} تومان</div>
        </div>
      </td>
      <td width="25%" style="padding:4px;">
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:12px; text-align:center;">
          <div style="font-size:11px; color:#64748b; font-weight:700;">بازگشته ۳۰ روزه</div>
          <div style="font-size:20px; font-weight:900; color:#059669; margin-top:4px;">{{ fa_num_email($data['recovered_30d'] ?? 0) }} نفر</div>
          <div style="font-size:11px; color:#059669; margin-top:2px;">{{ fa_money_email($data['recovered_revenue_30d'] ?? 0) }} تومان</div>
        </div>
      </td>
      <td width="25%" style="padding:4px;">
        <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:12px; text-align:center;">
          <div style="font-size:11px; color:#64748b; font-weight:700;">نرخ بازگشت</div>
          <div style="font-size:20px; font-weight:900; color:#0ea5e9; margin-top:4px;">{{ fa_num_email($data['recovery_rate'] ?? 0) }}٪</div>
          <div style="font-size:11px; color:#64748b; margin-top:2px;">از {{ fa_num_email($data['total_at_risk'] ?? 0) }} در خطر</div>
        </div>
      </td>
      <td width="25%" style="padding:4px;">
        <div style="background:#fef3c7; border:1px solid #fde68a; border-radius:12px; padding:12px; text-align:center;">
          <div style="font-size:11px; color:#64748b; font-weight:700;">کل ۱۲ ماه</div>
          <div style="font-size:16px; font-weight:900; color:#d97706; margin-top:4px;">{{ fa_money_email($data['total_recovered_12m'] ?? 0) }}</div>
          <div style="font-size:11px; color:#92400e; margin-top:2px;">تومان بازگشتی</div>
        </div>
      </td>
    </tr>
  </table>
</td>
</tr>

<tr>
<td style="padding:0 24px 20px;">
  <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
    <div style="font-size:14px; font-weight:900; color:#0f172a; margin-bottom:4px;">📈 نمودار ماهانه شمسی - درآمد بازگشتی - ۱۲ ماه اخیر (یونیک با سال)</div>
    <div style="font-size:11px; color:#64748b; margin-bottom:12px; line-height:1.6;">هر ستون یک ماه شمسی واقعی با سال فارسی - بازگشت بعد از ۴۵ روز غیبت = بازگشتی - بدون تکرار ماه</div>

    <table width="100%" cellpadding="0" cellspacing="0">
      @forelse(array_map(null, $monthlyLabels, $monthlyCount, $monthlyRecovered) as $idx => $row)
        @php
          $label = $row[0] ?? 'ماه';
          $cnt = $row[1] ?? 0;
          $rev = $row[2] ?? 0;
          $widthRev = $maxRevenue > 0 ? max(8, ($rev / $maxRevenue) * 100) : 8;
          $widthCnt = $maxCount > 0 ? max(8, ($cnt / $maxCount) * 100) : 8;
        @endphp
        <tr>
          <td style="padding:8px 0; border-bottom:1px solid #f1f5f9; width:110px; font-size:12px; font-weight:800; color:#0f172a; vertical-align:top;">{{ $label }}</td>
          <td style="padding:8px 0; border-bottom:1px solid #f1f5f9; vertical-align:top;">
            <div style="display:flex; flex-direction:column; gap:4px;">
              <div style="display:flex; align-items:center; gap:6px;">
                <div style="font-size:10px; color:#64748b; width:90px; text-align:left; flex-shrink:0;">درآمد بازگشتی</div>
                <div style="flex:1; background:#f1f5f9; border-radius:999px; height:14px; overflow:hidden; position:relative;">
                  <div style="background: linear-gradient(90deg, #10b981, #34d399); height:100%; width:{{ $widthRev }}%; border-radius:999px;"></div>
                </div>
                <div style="font-size:11px; font-weight:800; color:#059669; width:80px; text-align:right; flex-shrink:0;">{{ fa_money_email($rev) }}</div>
              </div>
              <div style="display:flex; align-items:center; gap:6px;">
                <div style="font-size:10px; color:#64748b; width:90px; text-align:left; flex-shrink:0;">تعداد بازگشته</div>
                <div style="flex:1; background:#f1f5f9; border-radius:999px; height:10px; overflow:hidden;">
                  <div style="background:#0ea5e9; height:100%; width:{{ $widthCnt }}%; border-radius:999px;"></div>
                </div>
                <div style="font-size:11px; font-weight:700; color:#0ea5e9; width:80px; text-align:right; flex-shrink:0;">{{ fa_num_email($cnt) }} نفر</div>
              </div>
            </div>
          </td>
        </tr>
      @empty
        <tr><td style="padding:16px; text-align:center; color:#64748b;">هنوز بازگشتی ثبت نشده</td></tr>
      @endforelse
    </table>

    <div style="margin-top:12px; display:flex; gap:12px; font-size:11px; color:#64748b;">
      <div style="display:flex; align-items:center; gap:4px;"><div style="width:12px; height:8px; background:#10b981; border-radius:4px;"></div>درآمد بازگشتی (تومان)</div>
      <div style="display:flex; align-items:center; gap:4px;"><div style="width:12px; height:8px; background:#0ea5e9; border-radius:4px;"></div>تعداد بازگشته</div>
    </div>
  </div>
</td>
</tr>

<tr>
<td style="padding:0 24px 20px;">
  <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
    <div style="font-size:14px; font-weight:900; color:#0f172a; margin-bottom:8px;">🎉 ۵ بازگشته برتر اخیر - امتیاز دو برابر گرفته‌اند</div>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
      <tr style="background:#f8fafc;">
        <td style="padding:8px; font-size:11px; font-weight:800; color:#64748b; border-bottom:1px solid #e2e8f0;">#</td>
        <td style="padding:8px; font-size:11px; font-weight:800; color:#64748b; border-bottom:1px solid #e2e8f0;">نام</td>
        <td style="padding:8px; font-size:11px; font-weight:800; color:#64748b; border-bottom:1px solid #e2e8f0;">غیبت</td>
        <td style="padding:8px; font-size:11px; font-weight:800; color:#64748b; border-bottom:1px solid #e2e8f0;">مبلغ</td>
        <td style="padding:8px; font-size:11px; font-weight:800; color:#64748b; border-bottom:1px solid #e2e8f0;">تاریخ شمسی</td>
      </tr>
      @forelse(array_slice($data['recovered_list'] ?? [], 0, 5) as $i=>$c)
        <tr>
          <td style="padding:8px; font-size:12px; border-bottom:1px solid #f1f5f9;">{{ fa_num_email($i+1) }}</td>
          <td style="padding:8px; font-size:12px; font-weight:700; border-bottom:1px solid #f1f5f9;">{{ $c['full_name'] ?? 'بدون نام' }}</td>
          <td style="padding:8px; font-size:12px; border-bottom:1px solid #f1f5f9;">{{ fa_num_email($c['gap_days'] ?? 0) }} روز</td>
          <td style="padding:8px; font-size:12px; border-bottom:1px solid #f1f5f9;">{{ fa_money_email($c['order_total'] ?? 0) }}</td>
          <td style="padding:8px; font-size:12px; border-bottom:1px solid #f1f5f9;">{{ $c['returned_at_fa'] ?? '---' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" style="padding:16px; text-align:center; color:#64748b; font-size:12px;">هنوز بازگشتی ثبت نشده - سیستم فعال است</td></tr>
      @endforelse
    </table>
  </div>
</td>
</tr>

<tr>
<td style="padding:0 24px 24px;">
  <div style="background: linear-gradient(135deg, #ecfdf5, #f0f9ff); border:1px solid #a7f3d0; border-radius:12px; padding:14px; text-align:center;">
    <div style="font-size:13px; font-weight:800; color:#065f46; margin-bottom:6px;">✨ اتوماسیون امتیاز دو برابر فعال است</div>
    <div style="font-size:11px; color:#047857; line-height:1.7;">هر مشتری که بعد از ۴۵ روز غیبت برگردد، خودکار امتیاز دو برابر می‌گیرد و یادداشت بازگشت برای تیم فروش ثبت می‌شود.</div>
    <div style="margin-top:12px;">
      <a href="{{ url('/app/retention') }}" style="display:inline-block; background:#10b981; color:#ffffff; text-decoration:none; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:800; margin:4px;">🛟 مرکز بازگشت و حفظ</a>
      <a href="{{ url('/app/reports?tab=recovery') }}" style="display:inline-block; background:#0ea5e9; color:#ffffff; text-decoration:none; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:800; margin:4px;">📊 گزارش کامل</a>
      <a href="{{ url('/app') }}" style="display:inline-block; background:#ffffff; color:#0f172a; border:1px solid #e2e8f0; text-decoration:none; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:800; margin:4px;">🏠 داشبورد</a>
    </div>
  </div>
</td>
</tr>

<tr>
<td style="background:#f8fafc; padding:12px 24px; text-align:center; border-top:1px solid #e2e8f0;">
  <div style="font-size:11px; color:#94a3b8; line-height:1.6;">این گزارش به صورت خودکار هر شنبه ساعت ۹ صبح تولید شده است.<br>برای لغو دریافت، از تنظیمات اعلان‌ها اقدام کنید. همه تاریخ‌ها شمسی و فونت‌ها وزیرمتن/استعداد هستند.</div>
</td>
</tr>

</table>
</td></tr>
</table>

</body>
</html>
