@extends('layouts.app')
@section('title', 'باشگاه مشتریان - مدیریت کمپین‌ها')
@section('heading', '🎁 باشگاه مشتریان و بازی‌سازی (گیمیفیکیشن)')
@section('subtitle', 'مدیریت کمپین‌ها، مأموریت‌ها، پیوندهای دعوت و سیستم پاداش‌دهی')

@section('content')

<style>
    /* Modern UI Styles for Campaigns */
    .modern-card {
        background: var(--panel);
        border-radius: 1.25rem;
        border: 1px solid var(--line);
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    .modern-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        border-color: rgba(37, 99, 235, 0.2);
    }
    .stat-box {
        position: relative;
        overflow: hidden;
        padding: 1.5rem;
        border-radius: 1rem;
        background: linear-gradient(145deg, var(--panel), var(--panel2));
        border: 1px solid var(--line);
        text-align: right;
        transition: 0.3s;
    }
    .stat-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
    .stat-box .stat-icon {
        position: absolute;
        left: -1rem;
        bottom: -1rem;
        font-size: 5rem;
        opacity: 0.04;
        transform: rotate(-15deg);
    }
    .stat-box .stat-value {
        font-size: 2rem;
        font-weight: 900;
        color: var(--txt);
        margin-bottom: 0.25rem;
    }
    .stat-box .stat-label {
        color: var(--mut);
        font-size: 0.85rem;
        font-weight: 600;
    }
    .modern-table-wrap {
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid var(--line);
    }
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--panel);
    }
    .modern-table th {
        background: var(--panel2);
        color: var(--mut);
        font-weight: 700;
        padding: 1rem;
        text-align: right;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--line);
    }
    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--line);
        color: var(--txt);
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .modern-table tbody tr:hover {
        background: rgba(37, 99, 235, 0.02);
    }
    .btn-modern {
        background: linear-gradient(135deg, var(--acc2), var(--acc));
        color: #fff;
        border: none;
        border-radius: 0.75rem;
        padding: 0.6rem 1.25rem;
        font-weight: 800;
        font-size: 0.85rem;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
        color: #fff;
    }
    .btn-outline {
        background: transparent;
        color: var(--txt);
        border: 1px solid var(--line);
        border-radius: 0.75rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-outline:hover {
        background: var(--line);
        color: var(--txt);
    }
    .btn-danger-outline {
        color: var(--bad);
        border-color: rgba(239, 68, 68, 0.3);
    }
    .btn-danger-outline:hover {
        background: rgba(239, 68, 68, 0.1);
        color: var(--bad);
    }
</style>

@if(session('status'))
<div class="card alert" style="background: rgba(16, 185, 129, 0.1); border-color: var(--ok); color: var(--ok); font-weight: 800; padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem;">
    ✓ {{ session('status') }}
</div>
@endif
@if($errors->any())
<div class="card alert" style="background: rgba(239, 68, 68, 0.1); border-color: var(--bad); color: var(--bad); font-weight: 800; padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem;">
    ✕ {{ $errors->first() }}
</div>
@endif

<!-- کارت‌های آمار کلان باشگاه -->
<div class="grid grid-4" style="margin-bottom: 1.5rem;">
    <div class="stat-box" style="border-bottom: 4px solid var(--acc2);">
        <div class="stat-icon">📊</div>
        <div class="stat-value" style="color: var(--acc2);">{{ $totalCampaigns }}</div>
        <div class="stat-label">تعداد کل کمپین‌ها</div>
    </div>
    <div class="stat-box" style="border-bottom: 4px solid var(--ok);">
        <div class="stat-icon">✅</div>
        <div class="stat-value" style="color: var(--ok);">{{ $activeCampaigns }}</div>
        <div class="stat-label">کمپین‌های در حال اجرا</div>
    </div>
    <div class="stat-box" style="border-bottom: 4px solid var(--warn);">
        <div class="stat-icon">⭐</div>
        <div class="stat-value" style="color: var(--warn);">آماده‌سازی</div>
        <div class="stat-label">امتیازات توزیع‌شده</div>
    </div>
    <div class="stat-box" style="border-bottom: 4px solid var(--clock2);">
        <div class="stat-icon">🪙</div>
        <div class="stat-value" style="color: var(--clock2);">آماده‌سازی</div>
        <div class="stat-label">سکه‌های پاداش داده‌شده</div>
    </div>
</div>

<!-- باکس لیست کمپین‌ها -->
<div class="modern-card" style="padding: 0;">
    <div style="padding: 1.5rem; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; flex-wrap:wrap; gap: 1rem;">
        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
            <h3 style="margin: 0; font-size: 1.1rem;">لیست کمپین‌ها و مأموریت‌ها</h3>
            <span style="color: var(--mut); font-size: 0.8rem;">فهرست رویدادهای تشویقی و قوانینی که برای مشتریان تعریف کرده‌اید.</span>
        </div>
        <div style="display:flex; gap: 0.75rem; flex-wrap:wrap; align-items: center;">
            <form class="global-search" method="GET" style="margin:0; height: 42px; min-width: 250px;">
                <input type="text" name="q" placeholder="جستجوی نام کمپین..." value="{{ request('q') }}" style="height: 100%; border-radius: 0.75rem;">
            </form>
            <button class="btn-modern" onclick="document.getElementById('modal-add-campaign').classList.add('show')">
                <span>➕</span> ایجاد کمپین جدید
            </button>
        </div>
    </div>

    <div class="modern-table-wrap" style="border-radius: 0; border: none; border-bottom-left-radius: 1.25rem; border-bottom-right-radius: 1.25rem;">
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">#</th>
                    <th>نام کمپین</th>
                    <th>بازه زمانی اجرا</th>
                    <th style="text-align: center;">مأموریت‌ها</th>
                    <th style="text-align: center;">وضعیت</th>
                    <th>عملیات و مدیریت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $camp)
                <tr>
                    <td style="text-align: center; color: var(--mut);">{{ $camp->id }}</td>
                    <td style="font-weight: 800; color: var(--txt);">{{ $camp->title }}</td>
                    <td dir="ltr" style="text-align: right; font-size: 0.85rem; color: var(--mut);">
                        <span style="display: inline-block; direction: rtl;">
                            از {{ $camp->starts_at ? \jdate($camp->starts_at)->format('Y/m/d') : 'نامشخص' }}<br>
                            تا {{ $camp->ends_at ? \jdate($camp->ends_at)->format('Y/m/d') : 'نامشخص' }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge" style="background: rgba(56, 189, 248, 0.1); color: var(--acc2);">
                            {{ $camp->rewardRules ? $camp->rewardRules->count() : 0 }} مأموریت
                        </span>
                    </td>
                    <td style="text-align: center;">
                        @if($camp->status === 'active')
                            <span class="badge b-ok">در حال اجرا</span>
                        @else
                            <span class="badge b-mut">متوقف شده</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:0.5rem; flex-wrap: wrap;">
                            <!-- مدیریت ماموریت‌ها -->
                            <button type="button" class="btn-modern" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;" onclick="openMissionModal({{ $camp->id }}, '{{ $camp->title }}')">
                                تعریف مأموریت 🎯
                            </button>

                            <!-- توقف / فعال‌سازی -->
                            <form action="{{ url('/app/loyalty/campaigns/'.$camp->id.'/toggle') }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-outline" style="padding: 0.4rem 0.8rem;">
                                    {{ $camp->status === 'active' ? 'توقف' : 'فعال‌سازی' }}
                                </button>
                            </form>

                            <!-- حذف -->
                            <form action="{{ url('/app/loyalty/campaigns/'.$camp->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('آیا از حذف این کمپین مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-outline btn-danger-outline" style="padding: 0.4rem 0.8rem;">
                                    حذف
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: var(--mut);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎁</div>
                        هنوز هیچ کمپینی ایجاد نشده است. برای شروع، یک کمپین جدید بسازید.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())
    <div style="padding: 1rem; border-top: 1px solid var(--line);">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>

<!-- مُدال ایجاد کمپین جدید -->
<div id="modal-add-campaign" class="modal" onclick="if(event.target===this) this.classList.remove('show')">
    <div class="modal-box" style="max-width: 500px; border-radius: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 1rem; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 800;">ایجاد کمپین جدید</h2>
            <button type="button" class="icon-btn" onclick="document.getElementById('modal-add-campaign').classList.remove('show')" style="border: none; background: rgba(239, 68, 68, 0.1); color: var(--bad);">×</button>
        </div>
        
        <form action="{{ url('/app/loyalty/campaigns') }}" method="POST">
            @csrf
            
            <label style="font-weight: 700; color: var(--txt);">عنوان کمپین</label>
            <input type="text" name="name" required placeholder="مثال: جشنواره فروش تابستانه یا دعوت دوستان" style="margin-bottom: 1rem;">
            
            <div class="grid grid-2" style="margin-bottom: 1rem;">
                <div>
                    <label style="font-weight: 700; color: var(--txt);">تاریخ شروع (اختیاری)</label>
                    <input type="date" name="starts_at" class="jdate" placeholder="سال/ماه/روز">
                </div>
                <div>
                    <label style="font-weight: 700; color: var(--txt);">تاریخ پایان (اختیاری)</label>
                    <input type="date" name="ends_at" class="jdate" placeholder="سال/ماه/روز">
                </div>
            </div>

            <label style="font-weight: 700; color: var(--txt);">توضیحات و قوانین کمپین</label>
            <textarea name="description" rows="3" placeholder="قوانین این کمپین را در اینجا بنویسید..." style="margin-bottom: 1rem; resize: vertical;"></textarea>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn-outline" onclick="document.getElementById('modal-add-campaign').classList.remove('show')">انصراف</button>
                <button type="submit" class="btn-modern">ثبت و ایجاد کمپین</button>
            </div>
        </form>
    </div>
</div>

<!-- مُدال تعریف مأموریت و جدول پاداش -->
<div id="modal-add-mission" class="modal" onclick="if(event.target===this) this.classList.remove('show')">
    <div class="modal-box" style="max-width: 600px; border-radius: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 1rem; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 800;">تعریف مأموریت برای: <span id="mission-camp-name" style="color:var(--acc)"></span></h2>
            <button type="button" class="icon-btn" onclick="document.getElementById('modal-add-mission').classList.remove('show')" style="border: none; background: rgba(239, 68, 68, 0.1); color: var(--bad);">×</button>
        </div>
        
        <form id="form-add-mission" action="" method="POST">
            @csrf
            
            <div style="background: var(--bg); padding: 1.25rem; border-radius: 1rem; border: 1px solid var(--line); margin-bottom: 1rem;">
                <h4 style="margin-top:0; margin-bottom: 1rem; color:var(--acc2); display: flex; align-items: center; gap: 0.5rem;"><span style="font-size: 1.25rem;">🎯</span> رفتار مشتری (مأموریت)</h4>
                <label style="font-weight: 700; color: var(--txt);">هنگامی که مشتری این کار را انجام داد:</label>
                <select name="action_type" required style="margin-bottom: 1rem;">
                    <option value="referral_registered">معرفی مشتری جدید با پیوند اختصاصی (دعوت دوستان)</option>
                    <option value="first_purchase">ثبت اولین سفارش موفق در سامانه</option>
                    <option value="profile_complete">تکمیل اطلاعات نمایه کاربری</option>
                </select>
                
                <label style="font-weight: 700; color: var(--txt);">شرط تکمیلی (اختیاری):</label>
                <input type="text" name="condition_details" placeholder="مثال: تنها در صورتی که شخص دعوت‌شده خرید انجام دهد...">
            </div>

            <div style="background: var(--bg); padding: 1.25rem; border-radius: 1rem; border: 1px solid var(--line);">
                <h4 style="margin-top:0; margin-bottom: 1rem; color:var(--ok); display: flex; align-items: center; gap: 0.5rem;"><span style="font-size: 1.25rem;">🎁</span> پاداش و جایزه</h4>
                <div class="grid grid-2">
                    <div>
                        <label style="font-weight: 700; color: var(--txt);">نوع پاداش اعطایی</label>
                        <select name="reward_type" required>
                            <option value="points_fixed">امتیاز (جهت تبدیل به تخفیف)</option>
                            <option value="cashback_fixed">سکه مجازی / بازگشت وجه (مبلغ ثابت)</option>
                            <option value="coupon_percent">کد تخفیف درصدی</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight: 700; color: var(--txt);">مقدار پاداش</label>
                        <input type="number" name="reward_value" min="1" required placeholder="مثال: ۱۰۰">
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn-outline" onclick="document.getElementById('modal-add-mission').classList.remove('show')">بستن</button>
                <button type="submit" class="btn-modern">ثبت مأموریت در سامانه</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMissionModal(campaignId, campaignName) {
        document.getElementById('mission-camp-name').innerText = campaignName;
        document.getElementById('form-add-mission').action = "{{ url('/app/loyalty/campaigns') }}/" + campaignId + "/reward-rules";
        document.getElementById('modal-add-mission').classList.add('show');
    }
</script>
@endsection