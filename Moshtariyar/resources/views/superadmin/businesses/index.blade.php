@extends('layouts.app')
@section('title', 'مدیریت هلدینگ - لیست کسب‌وکارها')
@section('heading', '🏢 مدیریت کل هلدینگ')
@section('subtitle', 'نظارت بر تمامی کسب‌وکارهای پلتفرم SaaS شما')

@section('content')

<!-- کارت‌های آمار کلان -->
<div class="grid grid-4" style="margin-bottom: 1.5rem;">
    <div class="card stat" style="border-top: 4px solid var(--acc2);">
        <div class="muted">تعداد کل کسب‌وکارها</div>
        <div class="num">{{ $totalBusinesses }}</div>
    </div>
    <div class="card stat" style="border-top: 4px solid var(--ok);">
        <div class="muted">لایسنس‌های فعال</div>
        <div class="num">{{ $activeSubscriptions }}</div>
    </div>
    <div class="card stat" style="border-top: 4px solid var(--clock2);">
        <div class="muted">کل کاربران پلتفرم</div>
        <div class="num">{{ $totalUsers }}</div>
    </div>
    <div class="card stat" style="border-top: 4px solid var(--warn);">
        <div class="muted">درآمد پلتفرم (ماه جاری)</div>
        <div class="num">بزودی</div>
    </div>
</div>

<!-- لیست کسب‌وکارها -->
<div class="card" style="padding: 0;">
    <div style="padding: 1.25rem; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-wrap:wrap; gap: 1rem;">
        <h3 style="margin: 0;">لیست مشترکین سرویس (Tenant)</h3>
        <div style="display:flex; gap: 0.5rem; flex-wrap:wrap; flex:1; justify-content: flex-end;">
            <form class="global-search" method="GET" style="margin:0; height: 42px; min-width: 250px;">
                <input type="text" name="search" placeholder="جستجوی نام یا دامنه..." value="{{ request('search') }}" style="height: 100%; border-radius: 0.75rem;">
            </form>
            <button class="btn" style="height: 42px; display:flex; align-items:center;" onclick="document.getElementById('modal-add-business').classList.add('show')">+ ایجاد کسب‌وکار جدید</button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نام کسب‌وکار</th>
                    <th>دامنه/زیردامنه</th>
                    <th>کاربران</th>
                    <th>وضعیت لایسنس</th>
                    <th>وضعیت سیستم</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($businesses as $b)
                <tr>
                    <td>{{ $b->id }}</td>
                    <td style="font-weight: 800; color: var(--txt);">{{ $b->name }}</td>
                    <td dir="ltr" style="text-align: right; font-family: monospace;">{{ $b->domain ?? '-' }}</td>
                    <td>{{ $b->users_count }} کاربر</td>
                    <td>
                        @if($b->subscription && $b->subscription->is_active && $b->subscription->ends_at > now())
                            <span class="badge b-ok">فعال تا {{ \jdate($b->subscription->ends_at)->format('Y/m/d') }}</span>
                        @else
                            <span class="badge b-bad">منقضی یا فاقد لایسنس</span>
                        @endif
                    </td>
                    <td>
                        @if($b->is_active)
                            <span class="badge b-ok">سیستم فعال</span>
                        @else
                            <span class="badge b-bad">مسدود شده</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ url('/superadmin/businesses/'.$b->id.'/toggle') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-ghost" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                {{ $b->is_active ? 'مسدود کردن' : 'فعال‌سازی' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--mut);">هنوز هیچ کسب‌وکاری در پلتفرم ثبت نشده است.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($businesses->hasPages())
    <div style="padding: 1rem; border-top: 1px solid var(--line);">
        {{ $businesses->links() }}
    </div>
    @endif
</div>

<!-- مُدال ساخت کسب‌وکار جدید -->
<div id="modal-add-business" class="modal" onclick="if(event.target===this) this.classList.remove('show')">
    <div class="modal-box" style="max-width: 800px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 1rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-size: 1.25rem;">ایجاد کسب‌وکار جدید در پلتفرم</h2>
            <button type="button" class="icon-btn" onclick="document.getElementById('modal-add-business').classList.remove('show')">×</button>
        </div>
        
        <form action="{{ url('/superadmin/businesses') }}" method="POST">
            @csrf
            
            <div class="grid grid-2">
                <!-- اطلاعات کسب‌وکار -->
                <div style="background: var(--panel2); padding: 1rem; border-radius: 1rem; border: 1px solid var(--line);">
                    <h4 style="margin-top: 0; color: var(--acc2);">۱. مشخصات کسب‌وکار</h4>
                    <label>نام فروشگاه/شرکت (فارسی)</label>
                    <input type="text" name="name" required placeholder="مثال: گالری طلا و جواهر احسان">
                    
                    <label>دامنه اختصاصی (اختیاری)</label>
                    <input type="text" name="domain" dir="ltr" placeholder="مثال: ehsangold.ir">
                </div>
                
                <!-- اطلاعات ادمین ارشد -->
                <div style="background: var(--panel2); padding: 1rem; border-radius: 1rem; border: 1px solid var(--line);">
                    <h4 style="margin-top: 0; color: var(--acc2);">۲. اطلاعات ورود مدیر ارشد</h4>
                    <label>نام و نام خانوادگی مدیر</label>
                    <input type="text" name="admin_name" required placeholder="مثال: احسان محمدی">
                    
                    <label>شماره موبایل مدیر (جهت ورود)</label>
                    <input type="text" name="admin_phone" required dir="ltr" placeholder="0912...">
                    
                    <label>ایمیل مدیر (اختیاری)</label>
                    <input type="email" name="admin_email" dir="ltr">
                    
                    <label>رمز عبور اولیه</label>
                    <input type="text" name="password" required value="123456" dir="ltr">
                </div>
            </div>

            <!-- اطلاعات لایسنس و اشتراک -->
            <div style="background: var(--panel2); padding: 1rem; border-radius: 1rem; border: 1px solid var(--line); margin-top: 1rem;">
                <h4 style="margin-top: 0; color: var(--acc2);">۳. تخصیص لایسنس و ماژول‌ها</h4>
                <div class="grid grid-2">
                    <div>
                        <label>نام پلن اشتراکی</label>
                        <select name="plan_name" required>
                            <option value="Enterprise">کامل (Enterprise) - شامل تمامی امکانات</option>
                            <option value="Standard">استاندارد - فقط مدیریت مشتریان و فاکتور</option>
                            <option value="AI_Only">فقط هوش مصنوعی - مختص پاسخگویی</option>
                        </select>
                    </div>
                    <div>
                        <label>اعتبار لایسنس (ماه)</label>
                        <input type="number" name="duration_months" value="12" min="1" required>
                    </div>
                </div>

                <label style="margin-top: 1rem;">ماژول‌های فعال برای این کسب‌وکار</label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="modules[]" value="chatbot" checked style="width: auto;"> 🤖 چت‌بات هوش مصنوعی
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="modules[]" value="loyalty" checked style="width: auto;"> 🎁 باشگاه مشتریان (گیمیفیکیشن)
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="modules[]" value="woocommerce" checked style="width: auto;"> 🛒 اتصال به ووکامرس
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="modules[]" value="automation" checked style="width: auto;"> ⚙️ اتوماسیون و سفر مشتری
                    </label>
                </div>
            </div>

            <div style="margin-top: 1.5rem; text-align: left;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-add-business').classList.remove('show')">انصراف</button>
                <button type="submit" class="btn">ثبت و ایجاد کسب‌وکار</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-box input[type="text"], .modal-box input[type="email"], .modal-box input[type="number"], .modal-box select {
    margin-bottom: 0.75rem;
}
</style>
@endsection