@extends('layouts.app')
@section('title','کاربران')
@section('heading','مدیریت کاربران، نقش‌ها و دسترسی‌ها')
@section('subtitle','مدیریت امن تیم، نقش‌های سفارشی و سطح دسترسی کاربران')

@section('content')
<link rel="stylesheet" href="{{ asset('css/users-board.css') }}">
@php
    $visibleUsers = $users->getCollection();
    $activeVisible = $visibleUsers->where('is_active', true)->values();
    $inactiveVisible = $visibleUsers->where('is_active', false)->values();
    $activeUsers = \App\Models\User::where('is_active', true)->count();
    $inactiveUsers = \App\Models\User::where('is_active', false)->count();
    $customRoles = $roleRows->where('is_system', false)->count();
    $systemRoles = $roleRows->where('is_system', true)->count();
@endphp
<div class="users-page">
    <section class="users-hero">
        <div>
            <span class="users-eyebrow">مرکز امنیت و دسترسی</span>
            <h2>مدیریت کاربران، نقش‌ها و مجوزها با نمای جدولی حرفه‌ای</h2>
            <p>کاربران در یک بورد گروه‌بندی‌شده و قابل جستجو نمایش داده می‌شوند؛ مثل یک مرکز عملیاتی مدرن که با زیاد شدن کاربران هم مرتب، سریع و قابل کنترل باقی می‌ماند.</p>
            <div class="users-hero-actions">
                <a class="btn" href="#newUserForm">افزودن کاربر</a>
                <a class="btn btn-ghost" href="#rolesPanel">نقش‌ها و مجوزها</a>
                <a class="btn btn-ghost" href="#usersBoardPanel">بورد کاربران</a>
            </div>
        </div>
        <div class="users-score">
            <div><span>کل کاربران</span><b>@fa(number_format($users->total()))</b><small>حساب‌های ثبت‌شده</small></div>
            <div><span>کاربران فعال</span><b>@fa(number_format($activeUsers))</b><small>قابل ورود به سامانه</small></div>
            <div><span>نقش سفارشی</span><b>@fa(number_format($customRoles))</b><small>ساخته‌شده توسط مدیر</small></div>
        </div>
    </section>

    <section class="users-admin-grid">
        <article class="users-card is-accent" style="--users-color:#0ea5e9;" id="newUserForm">
            <header>
                <div>
                    <span>افزودن کاربر</span>
                    <h2>ساخت حساب کاربری جدید</h2>
                    <p>کاربر جدید را بسازید و نقش مناسب را همان ابتدا به او اختصاص دهید.</p>
                </div>
                <span class="users-badge">کاربر جدید</span>
            </header>
            <form method="post" action="{{ url('/app/users') }}" class="users-form-stack">
                @csrf
                <div class="users-form-grid">
                    <div><label>نام</label><input name="name" required placeholder="نام و نام خانوادگی"></div>
                    <div><label>ایمیل</label><input name="email" type="email" required class="ltr" placeholder="user@example.com"></div>
                    <div><label>موبایل، اختیاری</label><input name="phone" class="ltr" placeholder="۰۹۱۲..."></div>
                    <div><label>رمز عبور</label><input name="password" type="password" required placeholder="حداقل ۶ کاراکتر"></div>
                    <div class="users-form-wide"><label>نقش</label><select name="role">@foreach($roles as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                </div>
                <button class="btn">ایجاد کاربر</button>
            </form>
        </article>

        <article class="users-card is-accent" style="--users-color:#8b5cf6;" id="rolesPanel">
            <header>
                <div>
                    <span>نقش‌ها و مجوزها</span>
                    <h2>مدیریت سطح دسترسی تیم</h2>
                    <p>نقش‌ها در همین بخش ساخته و ویرایش می‌شوند. برای امنیت، نقش مدیر کل همیشه دسترسی کامل دارد.</p>
                </div>
                <span class="users-badge">@fa(number_format($roleRows->count())) نقش</span>
            </header>

            <div class="users-role-summary-grid">
                <div><span>نقش سیستمی</span><b>@fa(number_format($systemRoles))</b></div>
                <div><span>نقش سفارشی</span><b>@fa(number_format($customRoles))</b></div>
                <div><span>گروه مجوز</span><b>@fa(number_format(count($permissionCatalog)))</b></div>
            </div>

            <details class="users-create-role-panel">
                <summary><span>ساخت نقش سفارشی جدید</span><b>＋</b></summary>
                <form method="post" action="{{ url('/app/roles') }}" class="users-form-stack">
                    @csrf
                    <div class="users-form-grid">
                        <div><label>نام نقش</label><input name="label" required placeholder="مثلاً مدیر فروشگاه"></div>
                        <div><label>کلید انگلیسی، اختیاری</label><input name="key" class="ltr" placeholder="store_manager"></div>
                        <div class="users-form-wide"><label>توضیح</label><input name="description" placeholder="شرح کوتاه نقش"></div>
                    </div>
                    <details class="users-permission-picker" open>
                        <summary>انتخاب مجوزها</summary>
                        <div class="users-permission-groups">
                            @foreach($permissionCatalog as $group=>$perms)
                                <section class="users-permission-group">
                                    <b>{{ $group }}</b>
                                    <div class="users-check-grid">
                                        @foreach($perms as $perm=>$label)
                                            <label class="users-check"><input type="checkbox" name="permissions[]" value="{{ $perm }}"> {{ $label }}</label>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </details>
                    <button class="btn">ساخت نقش</button>
                </form>
            </details>

            <div class="users-role-list">
                @foreach($roleRows as $role)
                    @php $rolePerms = $role->permissions->pluck('permission')->all(); @endphp
                    <details class="users-role-card" @if($loop->first) open @endif>
                        <summary>
                            <div>
                                <h3>{{ $role->label }}</h3>
                                <small class="muted ltr">{{ $role->key }}</small>
                            </div>
                            <div class="users-role-badges">
                                <span class="users-badge {{ $role->is_system ? 'is-ok' : 'is-muted' }}">{{ $role->is_system ? 'سیستمی' : 'سفارشی' }}</span>
                                <span class="users-badge {{ $role->is_active ? 'is-ok' : 'is-bad' }}">{{ $role->is_active ? 'فعال' : 'غیرفعال' }}</span>
                            </div>
                        </summary>
                        <div class="users-role-body">
                            <form method="post" action="{{ url('/app/roles/'.$role->id) }}" class="users-form-stack">
                                @csrf @method('PUT')
                                <div class="users-form-grid">
                                    <div><label>نام نقش</label><input name="label" value="{{ $role->label }}" required></div>
                                    <div><label>توضیح</label><input name="description" value="{{ $role->description }}"></div>
                                </div>
                                @if($role->key === 'admin')
                                    <div class="users-guide-row" style="--row-color:#10b981;margin-top:1rem"><div class="users-icon">✓</div><div><h3>مدیر کل</h3><p>مدیر کل همیشه دسترسی کامل دارد و محدود نمی‌شود.</p></div><span class="users-badge is-ok">کامل</span></div>
                                    <input type="hidden" name="permissions[]" value="*">
                                @else
                                    <div class="users-permission-groups">
                                        @foreach($permissionCatalog as $group=>$perms)
                                            <section class="users-permission-group">
                                                <b>{{ $group }}</b>
                                                <div class="users-check-grid">
                                                    @foreach($perms as $perm=>$label)
                                                        <label class="users-check"><input type="checkbox" name="permissions[]" value="{{ $perm }}" @checked(in_array($perm,$rolePerms,true))> {{ $label }}</label>
                                                    @endforeach
                                                </div>
                                            </section>
                                        @endforeach
                                    </div>
                                @endif
                                <label class="users-check" style="margin-top:1rem"><input type="checkbox" name="is_active" value="1" @checked($role->is_active) @disabled($role->is_system)> فعال باشد</label>
                                <button class="btn">ذخیره نقش</button>
                            </form>
                            @unless($role->is_system)
                                <form method="post" action="{{ url('/app/roles/'.$role->id) }}" onsubmit="return confirm('نقش حذف شود؟')" style="margin-top:.65rem">@csrf @method('DELETE')<button class="btn btn-ghost" style="color:var(--bad)!important">حذف نقش سفارشی</button></form>
                            @endunless
                        </div>
                    </details>
                @endforeach
            </div>
        </article>
    </section>

    <section class="users-card is-accent users-board-panel" style="--users-color:#10b981;" id="usersBoardPanel">
        <header>
            <div>
                <span>بورد کاربران</span>
                <h2>نمای جدولی و گروه‌بندی‌شده کاربران</h2>
                <p>کاربران فعال و غیرفعال جدا شده‌اند. هر ردیف مانند یک رکورد عملیاتی است و ویرایش سریع داخل همان ردیف انجام می‌شود.</p>
            </div>
            <span class="users-badge">@fa(number_format($users->total())) کاربر</span>
        </header>

        <div class="users-board-toolbar">
            <button class="btn" type="button" onclick="document.getElementById('newUserForm').scrollIntoView({behavior:'smooth'})">کاربر جدید</button>
            <div class="users-search-box">
                <span>🔎</span>
                <input id="usersQuickSearch" placeholder="جستجو در کاربران همین صفحه...">
            </div>
            <div class="users-toolbar-note">نمایش @fa(number_format($users->count())) کاربر در این صفحه</div>
        </div>

        <div class="users-monday-board" id="usersDirectoryList">
            @foreach([
                ['title' => 'کاربران فعال', 'color' => '#10b981', 'items' => $activeVisible],
                ['title' => 'کاربران غیرفعال', 'color' => '#ef4444', 'items' => $inactiveVisible],
            ] as $group)
                <section class="users-monday-group" style="--group-color: {{ $group['color'] }};">
                    <button class="users-group-title" type="button" onclick="this.closest('.users-monday-group').classList.toggle('is-collapsed')">
                        <span>⌄</span>
                        <b>{{ $group['title'] }}</b>
                        <em>@fa(number_format($group['items']->count()))</em>
                    </button>

                    <div class="users-monday-table">
                        <div class="users-monday-head">
                            <div>کاربر</div>
                            <div>ایمیل</div>
                            <div>موبایل</div>
                            <div>نقش</div>
                            <div>وضعیت</div>
                            <div>تاریخ ایجاد</div>
                            <div>عملیات</div>
                        </div>

                        @forelse($group['items'] as $user)
                            <details class="users-monday-row" data-user-search="{{ mb_strtolower($user->name.' '.$user->email.' '.$user->phone.' '.($roles[$user->role] ?? $user->role)) }}">
                                <summary>
                                    <div class="users-cell users-cell-person">
                                        <span class="users-avatar">{{ mb_substr($user->name ?: 'ک', 0, 1) }}</span>
                                        <b>{{ $user->name }}</b>
                                    </div>
                                    <div class="users-cell email">{{ $user->email }}</div>
                                    <div class="users-cell ltr">{{ $user->phone ?: '—' }}</div>
                                    <div class="users-cell"><span class="users-status-pill is-role">{{ $roles[$user->role] ?? $user->role }}</span></div>
                                    <div class="users-cell"><span class="users-status-pill {{ $user->is_active ? 'is-active' : 'is-inactive' }}">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</span></div>
                                    <div class="users-cell muted">@jdate($user->created_at)</div>
                                    <div class="users-cell"><span class="users-edit-pill">ویرایش</span></div>
                                </summary>
                                <div class="users-row-edit-panel">
                                    <form method="post" action="{{ url('/app/users/'.$user->id) }}" class="users-edit-form">
                                        @csrf @method('PUT')
                                        <div><label>نقش</label><select name="role">@foreach($roles as $key=>$label)<option value="{{ $key }}" @selected($user->role===$key)>{{ $label }}</option>@endforeach</select></div>
                                        <label class="users-check"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> فعال</label>
                                        <div><label>موبایل</label><input name="phone" value="{{ $user->phone }}" placeholder="موبایل" class="ltr"></div>
                                        <div><label>رمز جدید</label><input type="password" name="password" placeholder="اختیاری"></div>
                                        <button class="btn btn-ghost">ذخیره تغییرات</button>
                                    </form>
                                </div>
                            </details>
                        @empty
                            <div class="users-monday-empty">در این گروه کاربری وجود ندارد.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <div style="margin-top:1rem">{{ $users->links() }}</div>
    </section>

    <section class="users-card is-accent" style="--users-color:#f59e0b;">
        <header>
            <div>
                <span>راهنمای امنیت</span>
                <h2>آیا لازم است قابل تنظیم باشد؟</h2>
                <p>این راهنما یک چک‌لیست ثابت امنیتی است و فعلاً بهتر است قابل تنظیم نباشد. اصول امنیتی باید برای همه مدیران ثابت، ساده و قابل اتکا بماند. اگر بعداً خواستی، می‌توانیم آن را از تنظیمات سیستم قابل ویرایش کنیم.</p>
            </div>
            <span class="users-badge is-muted">راهنما</span>
        </header>
        <div class="users-security-grid">
            <article class="users-guide-row" style="--row-color:#10b981;"><div class="users-icon">✓</div><div><h3>اصل حداقل دسترسی</h3><p>برای هر کاربر فقط مجوزهای لازم همان نقش را فعال کنید.</p></div><span class="users-badge is-ok">امن</span></article>
            <article class="users-guide-row" style="--row-color:#0ea5e9;"><div class="users-icon">🔐</div><div><h3>رمز عبور قوی</h3><p>برای کاربران مدیریتی رمز قوی و منحصربه‌فرد تعیین کنید.</p></div><span class="users-badge">پیشنهاد</span></article>
            <article class="users-guide-row" style="--row-color:#f59e0b;"><div class="users-icon">!</div><div><h3>غیرفعال‌سازی به‌موقع</h3><p>حساب کاربران جداشده از تیم را فوراً غیرفعال کنید.</p></div><span class="users-badge is-muted">کنترل</span></article>
        </div>
    </section>
</div>
<script>
const usersQuickSearch = document.getElementById('usersQuickSearch');
const usersDirectoryList = document.getElementById('usersDirectoryList');
if (usersQuickSearch && usersDirectoryList) {
    usersQuickSearch.addEventListener('input', function () {
        const query = usersQuickSearch.value.trim().toLowerCase();
        usersDirectoryList.querySelectorAll('.users-monday-row').forEach(function (row) {
            row.style.display = row.dataset.userSearch.includes(query) ? '' : 'none';
        });
    });
}
</script>
@endsection