@extends('layouts.app')
@section('title','سرنخ')
@section('heading',$lead->name)
@section('subtitle','جزئیات سرنخ و فعالیت‌ها')

@section('content')
<div class="grid grid-2">
    <div class="card">
        <h3 style="margin-top:0">اطلاعات سرنخ</h3>
        <p>موبایل: <span class="ltr">@fa($lead->phone ?? '—')</span></p>
        <p>ایمیل: <span class="email">{{ $lead->email ?? '—' }}</span></p>
        <p>ارزش: @money($lead->value) @unit</p>
        <p>منبع: <span class="muted">{{ $lead->source }}</span></p>
        @if($lead->converted_customer_id)
            <p><span class="badge b-ok">تبدیل‌شده به مشتری</span> <a href="{{ url('/app/customers/'.$lead->converted_customer_id) }}">مشاهدهٔ مشتری ←</a></p>
        @else
            <form method="post" action="{{ url('/app/pipeline/'.$lead->id.'/convert') }}" style="margin-top:10px">
                @csrf<button class="btn">تبدیل به مشتری</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0">ثبت فعالیت / یادآوری</h3>
        <form method="post" action="{{ url('/app/activities') }}">
            @csrf
            <input type="hidden" name="subject_type" value="lead">
            <input type="hidden" name="subject_id" value="{{ $lead->id }}">
            <label>نوع</label>
            <select name="type" style="margin-bottom:10px">
                @foreach(\Modules\Core\Entities\Activity::TYPES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
            <label>یادداشت</label><textarea name="body" rows="3" style="margin-bottom:10px"></textarea>
            <label>یادآوری (تاریخ شمسی، اختیاری)</label><input name="due" class="ltr jdate" readonly placeholder="انتخاب تاریخ" style="margin-bottom:12px">
            <button class="btn">ثبت</button>
        </form>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0">تاریخچهٔ فعالیت‌ها</h3>
    @forelse($activities as $a)
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)">
            <div>
                <span class="badge b-mut">{{ \Modules\Core\Entities\Activity::TYPES[$a->type] ?? $a->type }}</span>
                <span style="margin-right:8px">{{ $a->body }}</span>
                @if($a->due_at)<div class="muted" style="font-size:.78rem;margin-top:4px">یادآوری: @jdatetime($a->due_at) {!! $a->done ? '<span class="badge b-ok">انجام‌شده</span>' : '' !!}</div>@endif
            </div>
            <span class="muted ltr">@jdate($a->created_at)</span>
        </div>
    @empty
        <div class="empty">فعالیتی ثبت نشده.</div>
    @endforelse
</div>
@endsection
