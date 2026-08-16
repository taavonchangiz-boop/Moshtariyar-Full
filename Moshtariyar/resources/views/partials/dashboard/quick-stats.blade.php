{{-- partial: dashboard/quick-stats -- لژیون‌مانند --}}
<div class="grid grid-4 mb-6">
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#38bdf8; font-size:1.75rem;">@fa($quick_stats['total_customers'] ?? 0)</div>
        <div class="lbl">کل مشتریان</div>
        <div style="font-size:11px; color:#64748b; margin-top:4px;">@fa($quick_stats['new_this_month'] ?? 0) جدید این ماه</div>
    </div>
    
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#10b981; font-size:1.75rem;">@fa($quick_stats['total_orders'] ?? 0)</div>
        <div class="lbl">تعداد سفارش‌ها</div>
        <div style="font-size:11px; color:#64748b; margin-top:4px;">@fa($quick_stats['today_orders'] ?? 0) سفارش امروز</div>
    </div>
    
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#f59e0b; font-size:1.75rem;">@money($quick_stats['total_revenue'] ?? 0)</div>
        <div class="lbl">درآمد کل</div>
        <div style="font-size:11px; color:#64748b; margin-top:4px;">@money($quick_stats['this_month_revenue'] ?? 0) این ماه</div>
    </div>
    
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#a78bfa; font-size:1.75rem;">@fa($quick_stats['active_customers'] ?? 0)</div>
        <div class="lbl">مشتریان فعال (۳۰ روز)</div>
        <div style="font-size:11px; color:#64748b; margin-top:4px;">ریسک ریزش: @fa($quick_stats['churn_risk'] ?? 0)٪</div>
    </div>
    
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#22c55e; font-size:1.75rem;">@fa($quick_stats['avg_order_value'] ?? 0)</div>
        <div class="lbl">میانگین ارزش سفارش</div>
    </div>
    
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#f43f5e; font-size:1.75rem;">@fa($quick_stats['pending_orders'] ?? 0)</div>
        <div class="lbl">سفارشات در حال پردازش</div>
    </div>
    
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#eab308; font-size:1.75rem;">@fa($quick_stats['repeat_customers'] ?? 0)</div>
        <div class="lbl">مشتریان تکراری</div>
    </div>
    
    <div class="card stat" style="background:#0f172a; border-color:#334155;">
        <div class="num" style="color:#3b82f6; font-size:1.75rem;">@fa($quick_stats['conversion_rate'] ?? 0)٪</div>
        <div class="lbl">نرخ تبدیل</div>
    </div>
</div>
