{{-- partial: dashboard/rfm-distribution -- لژیون‌مانند --}}
<div class="card">
    <h3 style="margin:0 0 12px; display:flex; align-items:center; gap:8px; font-size:15px;">
        📊 توزیع سگمنت‌های RFM
    </h3>
    
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        @foreach(($rfm_distribution ?? []) as $seg => $count)
            <div style="flex:1; min-width:88px; background:#1e2937; padding:10px 8px; border-radius:10px; text-align:center; border:1px solid #334155;">
                <div style="font-size:22px; font-weight:800; color:#bae6fd;">@fa($count)</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:2px;">{{ $seg }}</div>
            </div>
        @endforeach
    </div>
    
    <div style="margin-top:12px; font-size:12px; color:#64748b;">
        گروه‌بندی خودکار بر اساس رفتار خرید (RFM)
    </div>
    
    <a href="{{ url('/app/rfm') }}" style="display:inline-block; margin-top:8px; font-size:12px; color:#38bdf8;">جزئیات کامل تحلیل RFM →</a>
</div>
