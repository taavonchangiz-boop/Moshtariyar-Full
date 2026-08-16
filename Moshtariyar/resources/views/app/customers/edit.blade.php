@extends('layouts.app')

@section('title', 'ویرایش مشتری')
@section('heading', 'ویرایش اطلاعات مشتری')
@section('subtitle', $customer->full_name)

@section('content')
<div class="max-w-3xl mx-auto">
    <form method="post" action="{{ url('/app/customers/'.$customer->id) }}" class="card">
        @csrf
        @method('PUT')

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <div style="grid-column: span 2;">
                <label style="display:block; color:var(--mut); margin-bottom:6px; font-size:13px;">نام و نام خانوادگی *</label>
                <input name="full_name" value="{{ $customer->full_name }}" required style="width:100%; background:var(--panel2); border:1px solid var(--line); color:var(--txt); border-radius:10px; padding:10px; font-family:inherit;">
            </div>

            <div>
                <label style="display:block; color:var(--mut); margin-bottom:6px; font-size:13px;">ایمیل</label>
                <input name="email" type="email" value="{{ $customer->email }}" style="width:100%; background:var(--panel2); border:1px solid var(--line); color:var(--txt); border-radius:10px; padding:10px; font-family:inherit;">
            </div>

            <div>
                <label style="display:block; color:var(--mut); margin-bottom:6px; font-size:13px;">تلفن</label>
                <input name="phone" value="{{ $customer->phone }}" style="width:100%; background:var(--panel2); border:1px solid var(--line); color:var(--txt); border-radius:10px; padding:10px; font-family:inherit;">
            </div>

            <div>
                <label style="display:block; color:var(--mut); margin-bottom:6px; font-size:13px;">نام شرکت</label>
                <input name="company_name" value="{{ $customer->company_name }}" style="width:100%; background:var(--panel2); border:1px solid var(--line); color:var(--txt); border-radius:10px; padding:10px; font-family:inherit;">
            </div>

            <div>
                <label style="display:block; color:var(--mut); margin-bottom:6px; font-size:13px;">کد ملی / شناسنامه</label>
                <input name="national_id" value="{{ $customer->national_id }}" style="width:100%; background:var(--panel2); border:1px solid var(--line); color:var(--txt); border-radius:10px; padding:10px; font-family:inherit;">
            </div>

            <div>
                <label style="display:block; color:var(--mut); margin-bottom:6px; font-size:13px;">کد اقتصادی</label>
                <input name="economic_code" value="{{ $customer->economic_code }}" style="width:100%; background:var(--panel2); border:1px solid var(--line); color:var(--txt); border-radius:10px; padding:10px; font-family:inherit;">
            </div>

            <div>
                <label style="display:block; color:var(--mut); margin-bottom:6px; font-size:13px;">منبع جذب</label>
                <input name="source" value="{{ $customer->source }}" style="width:100%; background:var(--panel2); border:1px solid var(--line); color:var(--txt); border-radius:10px; padding:10px; font-family:inherit;">
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:24px; justify-content: flex-end;">
            <a href="{{ url('/app/customers/'.$customer->id) }}" class="btn btn-ghost">انصراف</a>
            <button type="submit" class="btn">💾 ذخیره تغییرات</button>
        </div>
    </form>
</div>
@endsection