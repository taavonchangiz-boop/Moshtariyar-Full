{{-- partial: dashboard/revenue-trend --}}
<div class="card">
    <h3 style="margin:0 0 10px;">📈 روند درآمد (۳۰ روز اخیر)</h3>
    
    @if(isset($revenue_trend) && !empty($revenue_trend['labels']))
        <canvas id="revenueChart" height="90"></canvas>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($revenue_trend['labels']),
                    datasets: [{
                        label: 'درآمد',
                        data: @json($revenue_trend['values']),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: '#334155' }, ticks: { color: '#64748b' } },
                        x: { grid: { color: '#334155' }, ticks: { color: '#64748b' } }
                    }
                }
            });
        });
        </script>
    @else
        <p class="muted">داده روند درآمد موجود نیست.</p>
    @endif
</div>
