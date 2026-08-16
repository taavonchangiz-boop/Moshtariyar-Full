{{-- partial: 360/journeys --}}
@if(isset($journeys) && $journeys->isNotEmpty())
<div class="card mb-6">
    <h3 style="margin:0 0 10px;">🚀 سفرهای فعال مشتری</h3>
    <div style="display:flex; flex-direction:column; gap:8px;">
        @foreach($journeys as $j)
            <div style="padding:10px 14px; background:#1e2937; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>{{ $j->workflow->name ?? 'سفر خودکار' }}</strong>
                    <div class="muted" style="font-size:12px; margin-top:1px;">
                        مرحله {{ $j->current_step_order ?? 1 }} • از {{ $j->created_at->diffForHumans() }}
                    </div>
                </div>
                <span class="badge b-ok" style="font-size:11px;">{{ $j->status }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif
