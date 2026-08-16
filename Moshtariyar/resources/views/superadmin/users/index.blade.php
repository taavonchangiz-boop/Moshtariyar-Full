@extends('layouts.app')
@section('title', 'مدیریت هلدینگ - لیست کل کاربران')
@section('heading', '👥 مدیریت کل کاربران')
@section('subtitle', 'نظارت یکپارچه بر تمامی اپراتورها و کاربران در تمام کسب‌وکارها')

@section('content')

@if(session('status'))
<div class="card alert" style="background: rgba(16, 185, 129, 0.1); border-color: var(--ok); color: var(--ok); font-weight: 800; padding: 1rem;">
    ✓ {{ session('status') }}
</div>
@endif
@if($errors->any())
<div class="card alert" style="background: rgba(239, 68, 68, 0.1); border-color: var(--bad); color: var(--bad); font-weight: 800; padding: 1rem;">
    ✕ {{ $errors->first() }}
</div>
@endif

<!-- لیست کل کاربران -->
<div class="card" style="padding: 0;">
    <div style="padding: 1.25rem; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-wrap:wrap; gap: 1rem;">
        <h3 style="margin: 0;">کاربران پلتفرم (<span style="color: var(--acc);">{{ $totalUsers }}</span> نفر)</h3>
        
        <form class="global-search" method="GET" style="margin:0; height: 42px; display:flex; gap: 0.5rem; min-width: 350px;">
            <select name="business_id" style="height: 100%; border-radius: 0.75rem; width: 180px;">
                <option value="">همه کسب‌وکارها</option>
                @foreach($businesses as $b)
                    <option value="{{ $b->id }}" {{ request('business_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            <div style="position:relative; flex:1;">
                <input type="text" name="search" placeholder="جستجوی نام، موبایل..." value="{{ request('search') }}" style="height: 100%; border-radius: 0.75rem; width:100%;">
            </div>
            <button type="submit" class="btn" style="height: 100%; padding: 0 1rem;">جستجو</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نام کاربر</th>
                    <th>اطلاعات تماس</th>
                    <th>کسب‌وکار مربوطه</th>
                    <th>نقش کاربری</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td style="font-weight: 800; color: var(--txt);">{{ $user->name }}</td>
                    <td dir="ltr" style="text-align: right;">
                        <div style="font-family: monospace; font-size:0.9rem;">{{ $user->phone ?? '-' }}</div>
                        <div class="muted" style="font-size: 0.75rem;">{{ $user->email ?? '-' }}</div>
                    </td>
                    <td>
                        @if($user->business)
                            <span class="badge b-mut">{{ $user->business->name }}</span>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge b-warn">مدیر ارشد</span>
                        @else
                            <span class="badge b-acc">{{ $user->roleLabel() }}</span>
                        @endif
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge b-ok">فعال</span>
                        @else
                            <span class="badge b-bad">مسدود شده</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:0.25rem;">
                            <!-- ورود به عنوان کاربر (Login As) -->
                            <form action="{{ url('/superadmin/users/'.$user->id.'/login-as') }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: var(--acc2); color:white;" title="ورود به عنوان این کاربر">
                                    ورود 🕵️‍♂️
                                </button>
                            </form>

                            <!-- مسدود کردن / فعال‌سازی -->
                            <form action="{{ url('/superadmin/users/'.$user->id.'/toggle') }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn btn-ghost" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; {{ !$user->is_active ? 'color:var(--ok); border-color:var(--ok);' : 'color:var(--bad); border-color:var(--bad);' }}">
                                    {{ $user->is_active ? 'مسدود' : 'فعال' }}
                                </button>
                            </form>
                            
                            <!-- تغییر رمز عبور اجباری -->
                            <button type="button" class="btn btn-ghost" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="openPasswordModal({{ $user->id }}, '{{ $user->name }}')">
                                تغییر رمز
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--mut);">هیچ کاربری یافت نشد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div style="padding: 1rem; border-top: 1px solid var(--line);">
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- مُدال تغییر رمز عبور -->
<div id="modal-reset-password" class="modal" onclick="if(event.target===this) this.classList.remove('show')">
    <div class="modal-box" style="max-width: 400px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 1rem; margin-bottom: 1rem;">
            <h3 style="margin: 0;">تغییر رمز عبور <span id="pwd-user-name" style="color:var(--acc);"></span></h3>
            <button type="button" class="icon-btn" onclick="document.getElementById('modal-reset-password').classList.remove('show')">×</button>
        </div>
        
        <form id="pwd-form" action="" method="POST">
            @csrf
            <label>رمز عبور جدید</label>
            <input type="text" name="password" required dir="ltr" minlength="6" placeholder="حداقل ۶ کاراکتر">
            
            <div style="margin-top: 1.5rem; text-align: left;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-reset-password').classList.remove('show')">انصراف</button>
                <button type="submit" class="btn">ثبت تغییرات</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPasswordModal(userId, userName) {
        document.getElementById('pwd-user-name').innerText = userName;
        document.getElementById('pwd-form').action = "{{ url('/superadmin/users') }}/" + userId + "/reset-password";
        document.getElementById('modal-reset-password').classList.add('show');
    }
</script>
@endsection