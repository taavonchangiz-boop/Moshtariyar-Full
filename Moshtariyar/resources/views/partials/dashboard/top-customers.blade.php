{{-- partial: dashboard/top-customers --}}
<div class="card">
    <h3 style="margin:0 0 12px; display:flex; align-items:center; gap:8px;">
        🏆 مشتریان برتر
    </h3>
    
    @if(isset($top_customers) && $top_customers->count())
        <table style="width:100%; font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid #334155;">
                    <th style="text-align:right; padding:6px;">مشتری</th>
                    <th style="text-align:center;">سفارش</th>
                    <th style="text-align:left;">ارزش</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($top_customers as $c)
                <tr style="border-bottom:1px solid #1e2937;">
                    <td style="padding:6px 0;">{{ $c->full_name }}</td>
                    <td style="text-align:center; padding:6px 0;">@fa($c->orders_count ?? 0)</td>
                    <td style="text-align:left; padding:6px 0; font-weight:600;">@money($c->total_spent ?? 0)</td>
                    <td style="padding:6px 0; text-align:left;">
                        <a href="{{ url('/app/customers/'.$c->id) }}" style="font-size:11px;">۳۶۰ ←</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">داده‌ای موجود نیست.</p>
    @endif
</div>
