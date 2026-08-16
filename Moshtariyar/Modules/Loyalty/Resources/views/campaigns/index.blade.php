@extends('layouts.app')
@section('title', 'کمپین‌های باشگاه')
@section('heading', '🎁 باشگاه مشتریان و گیمیفیکیشن')
@section('subtitle', 'مدیریت کمپین‌ها، مأموریت‌ها، پیوندهای دعوت و سیستم پاداش‌دهی')

@section('content')

@php
    if (!function_exists('fa_num')) {
        function fa_num($number) {
            if ($number === null || $number === '') return '';
            $number = (string) $number;
            if (strpos($number, '.') !== false) {
                $number = rtrim(rtrim($number, '0'), '.');
            }
            return strtr($number, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
        }
    }
@endphp

<style>
    /* ===== پاپ‌آپ حرفه‌ای - چون در چیدمان اصلی وجود نداشت اینجا کامل اضافه کردیم ===== */
    .modal{
        display:none !important;
        position:fixed !important;
        inset:0 !important;
        background:rgba(15,23,42,.55) !important;
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index:99999 !important;
        align-items:center !important;
        justify-content:center !important;
        padding:1rem !important;
        overflow-y:auto !important;
    }
    .modal.show{
        display:flex !important;
        animation: ayModalIn .2s ease;
    }
    .modal-box{
        background: var(--panel);
        border:1px solid var(--line);
        border-radius:1.5rem;
        padding:1.25rem;
        width:100%;
        max-width:760px;
        max-height:90vh;
        overflow:auto;
        box-shadow:0 20px 60px rgba(0,0,0,.22);
        position:relative;
        margin:auto;
    }
    body.light .modal-box{ background:#fff; border-color:#e5edf7; }
    @keyframes ayModalIn{ from{ opacity:0; transform:translateY(14px) scale(.97); } to{ opacity:1; transform:translateY(0) scale(1); } }

    /* استایل‌های اختصاصی مدرن برای این صفحه، جایگزین فایل‌های خارجی و قدیمی */
    .modern-card { background: var(--panel); border-radius: 1.5rem; border: 1px solid var(--line); padding: 1.5rem; margin-bottom: 1.5rem; transition: 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    .stat-box { position: relative; overflow: hidden; padding: 1.5rem; border-radius: 1.25rem; background: var(--panel); border: 1px solid var(--line); text-align: right; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
    .stat-box:hover { transform: translateY(-4px); box-shadow: 0 12px 25px rgba(0,0,0,0.05); }
    .stat-box .stat-icon { position: absolute; left: -1rem; bottom: -1.5rem; font-size: 6rem; opacity: 0.04; transform: rotate(-15deg); }
    .stat-box .stat-value { font-size: 2.25rem; font-weight: 900; color: var(--txt); margin-bottom: 0.25rem; }
    .stat-box .stat-label { color: var(--mut); font-size: 0.9rem; font-weight: 700; }
    
    .modern-table-wrap { border-radius: 1rem; overflow: hidden; border: 1px solid var(--line); width: 100%; overflow-x: auto;}
    .modern-table { width: 100%; border-collapse: collapse; background: var(--panel); min-width: 800px;}
    .modern-table th { background: var(--panel2); color: var(--mut); font-weight: 800; padding: 1.25rem 1rem; text-align: right; border-bottom: 1px solid var(--line); font-size: 0.85rem; }
    .modern-table td { padding: 1.25rem 1rem; border-bottom: 1px solid var(--line); color: var(--txt); font-size: 0.95rem; vertical-align: middle; }
    
    .btn-modern { background: linear-gradient(135deg, var(--acc2), var(--acc)); color: #fff !important; border: none; border-radius: 0.75rem; padding: 0.75rem 1.25rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25); transition: 0.2s; white-space: nowrap; height: 42px;}
    .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35); }
    .btn-outline { background: transparent; color: var(--txt); border: 1px solid var(--line); border-radius: 0.75rem; padding: 0.5rem 1rem; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: 0.2s; white-space: nowrap;}
    .btn-danger-outline { color: var(--bad); border-color: rgba(239, 68, 68, 0.3); }

    /* کلاس‌های عمومی */
    .jdate { font-family: inherit; direction: rtl !important; }
    .jdate::placeholder { direction: rtl; text-align: right; }
    
    .search-modern { position: relative; min-width: 250px; margin: 0; }
    .search-modern input { width: 100%; height: 42px; border-radius: 0.75rem; padding: 0 1rem 0 2.5rem; border: 1px solid var(--line); background: var(--panel2); color: var(--txt); font-family: inherit;}
    .search-modern input:focus { outline: none; border-color: var(--acc); box-shadow: var(--glow-acc); }
    .search-modern .icon-search { position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: var(--mut); pointer-events: none; font-size: 1.2rem;}

    /* چیدمان دو ستونه حرفه‌ای برای بخش تعریف ماموریت - دسکتاپ کنار هم، موبایل زیر هم */
    .mission-dual-layout{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:1rem;
        margin-bottom:1.5rem;
        align-items:stretch;
    }
    .mission-dual-layout .mission-box{
        background: var(--bg);
        padding: 1.4rem;
        border-radius: 1.25rem;
        border: 1px solid var(--line);
        margin-bottom:0 !important;
        display:flex;
        flex-direction:column;
        gap:.9rem;
        min-width:0;
        transition:.18s;
    }
    .mission-dual-layout .mission-box:hover{
        border-color: rgba(139,92,246,.22);
        box-shadow: 0 8px 20px rgba(15,23,42,.06);
    }
    .mission-dual-layout .mission-box.is-action{
        border-right: 4px solid #38bdf8;
    }
    .mission-dual-layout .mission-box.is-reward{
        border-right: 4px solid #10b981;
    }
    .mission-box-title{
        margin:0;
        display:flex;
        align-items:center;
        gap:.55rem;
        font-size:1rem;
        font-weight:1000;
        line-height:1.6;
    }
    .mission-box-title.is-blue{ color:var(--acc2); }
    .mission-box-title.is-green{ color:var(--ok); }
    .mission-box-title span{ font-size:1.45rem; }

    /* ریسپانسیو اختصاصی برای جداول این صفحه */
    @media (max-width: 900px) {
        .top-action-bar { flex-direction: column !important; align-items: stretch !important; gap: 1rem !important; }
        .top-action-bar > div:last-child { flex-direction: column !important; width: 100% !important; align-items: stretch !important;}
        .search-modern { width: 100% !important; margin-bottom: 0.5rem !important; }
        .search-modern input { width: 100% !important; }
        .btn-modern { width: 100% !important; justify-content: center !important; }
        
        .modern-table-wrap { border: none !important; background: transparent !important; overflow-x: visible !important;}
        .modern-table { min-width: 0 !important; display: block; }
        .modern-table thead { display: none !important; }
        .modern-table tbody, .modern-table tr { display: block !important; width: 100%; }
        .modern-table tbody tr { background: var(--panel) !important; border: 1px solid var(--line) !important; border-radius: 1rem !important; margin-bottom: 1rem !important; box-shadow: 0 4px 6px rgba(0,0,0,0.02) !important; padding: 1rem !important; }
        .modern-table td { display: flex !important; justify-content: space-between !important; align-items: center !important; border: none !important; padding: 0.75rem 0 !important; border-bottom: 1px dashed var(--line) !important; text-align: left !important;}
        .modern-table td:last-child { border-bottom: none !important; display: flex !important; flex-direction: column !important; align-items: stretch !important; gap: 0.5rem !important; }
        .modern-table td::before { content: attr(data-label) !important; font-weight: 800 !important; color: var(--mut) !important; text-align: right !important; max-width: 45%; }
        .modern-table td .btn-outline { width: 100% !important; justify-content: center !important; margin-top: 0.25rem !important;}
        .mission-dual-layout{ grid-template-columns:1fr !important; }
        .modal{ padding:.6rem !important; align-items:flex-start !important; }
        .modal-box{ max-width:100% !important; width:calc(100vw - 1.2rem) !important; margin:.6rem auto !important; max-height:96vh !important; }
    }
    @media (max-width: 64rem){
        .mission-dual-layout{ grid-template-columns:1fr !important; }
    }
</style>

<div class="grid grid-4" style="margin-bottom: 2rem;">
    <div class="stat-box" style="border-right: 4px solid var(--acc2);">
        <div class="stat-icon">📊</div>
        <div class="stat-value" style="color: var(--acc2);">{{ fa_num($totalCampaigns) }}</div>
        <div class="stat-label">تعداد کل کمپین‌ها</div>
    </div>
    <div class="stat-box" style="border-right: 4px solid var(--ok);">
        <div class="stat-icon">✅</div>
        <div class="stat-value" style="color: var(--ok);">{{ fa_num($activeCampaigns) }}</div>
        <div class="stat-label">کمپین‌های در حال اجرا</div>
    </div>
    <div class="stat-box" style="border-right: 4px solid var(--warn);">
        <div class="stat-icon">⭐</div>
        <div class="stat-value" style="color: var(--warn);">۰</div>
        <div class="stat-label">امتیازات توزیع‌شده</div>
    </div>
    <div class="stat-box" style="border-right: 4px solid var(--clock2);">
        <div class="stat-icon">🪙</div>
        <div class="stat-value" style="color: var(--clock2);">۰</div>
        <div class="stat-label">سکه‌های پاداش داده‌شده</div>
    </div>
</div>

<div class="modern-card" style="padding: 0;">
    <div class="top-action-bar" style="padding: 1.5rem; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
            <h3 style="margin: 0; font-size: 1.2rem;">فهرست کمپین‌ها و مأموریت‌ها</h3>
            <span style="color: var(--mut); font-size: 0.85rem;">رویدادهای تشویقی و قوانینی که برای مشتریان تعریف کرده‌اید را از اینجا مدیریت کنید.</span>
        </div>
        <div style="display:flex; gap: 0.75rem; align-items: center;">
            <form class="search-modern" method="GET">
                <span class="icon-search">🔎</span>
                <input type="text" name="q" placeholder="جستجوی نام کمپین..." value="{{ request('q') }}">
            </form>
            <button class="btn-modern" onclick="document.getElementById('modal-add-campaign').classList.add('show'); document.body.style.overflow='hidden';">
                <span style="font-size:1.2rem;">+</span> ایجاد کمپین جدید
            </button>
        </div>
    </div>

    <div class="modern-table-wrap" style="border-radius: 0; border: none; border-bottom-left-radius: 1.5rem; border-bottom-right-radius: 1.5rem;">
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">شناسه</th>
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
                    <td data-label="شناسه" style="text-align: center; color: var(--mut); font-weight: 800;">{{ fa_num($camp->id) }}</td>
                    <td data-label="نام کمپین" style="font-weight: 800; color: var(--txt); font-size: 1.05rem;">{{ $camp->title }}</td>
                    <td data-label="بازه زمانی" dir="ltr" style="text-align: right; color: var(--mut);">
                        <span style="display: inline-block; direction: rtl;">
                            از {{ $camp->starts_at ? fa_num(\jdate($camp->starts_at)->format('Y/m/d')) : 'نامشخص' }}<br>
                            تا {{ $camp->ends_at ? fa_num(\jdate($camp->ends_at)->format('Y/m/d')) : 'نامشخص' }}
                        </span>
                    </td>
                    <td data-label="تعداد مأموریت" style="text-align: center;">
                        <span style="display:inline-block; padding:0.25rem 0.75rem; border-radius:999px; font-weight:800; background: rgba(56, 189, 248, 0.1); color: var(--acc2);">
                            {{ fa_num($camp->rewardRules ? $camp->rewardRules->count() : 0) }} مأموریت
                        </span>
                    </td>
                    <td data-label="وضعیت اجرا" style="text-align: center;">
                        @if($camp->status === 'active')
                            <span style="display:inline-block; padding:0.25rem 0.75rem; border-radius:999px; font-weight:800; background: rgba(16, 185, 129, 0.15); color: var(--ok);">در حال اجرا</span>
                        @else
                            <span style="display:inline-block; padding:0.25rem 0.75rem; border-radius:999px; font-weight:800; background: rgba(148, 163, 184, 0.15); color: var(--mut);">متوقف شده</span>
                        @endif
                    </td>
                    <td data-label="عملیات و مدیریت">
                        <div style="display:flex; gap:0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn-outline" style="color: var(--acc2); border-color: rgba(56, 189, 248, 0.4);" onclick="openMissionModal({{ $camp->id }}, '{{ addslashes($camp->title) }}')">
                                تعریف مأموریت 🎯
                            </button>
                            <form action="{{ url('/app/loyalty/campaigns/'.$camp->id.'/toggle') }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-outline">
                                    {{ $camp->status === 'active' ? 'توقف کمپین' : 'فعال‌سازی مجدد' }}
                                </button>
                            </form>
                            <form action="{{ url('/app/loyalty/campaigns/'.$camp->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('آیا از حذف این کمپین مطمئن هستید؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-outline btn-danger-outline">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 4rem; color: var(--mut);">
                        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🎁</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--txt);">هیچ کمپینی یافت نشد!</h4>
                        <p style="margin: 0;">برای شروع جذب مشتریان وفادار، اولین کمپین خود را ایجاد کنید.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())
    <div style="padding: 1.5rem; border-top: 1px solid var(--line);">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>

<!-- مُدال ایجاد کمپین جدید - بصورت پاپ آپ وسط صفحه -->
<div id="modal-add-campaign" class="modal" onclick="if(event.target===this){ this.classList.remove('show'); document.body.style.overflow=''; }">
    <div class="modal-box" style="max-width: 560px; border-radius: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 1.25rem; margin-bottom: 1.5rem; gap:1rem;">
            <h2 style="margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--txt);">ایجاد کمپین جدید</h2>
            <button type="button" class="icon-btn" onclick="document.getElementById('modal-add-campaign').classList.remove('show'); document.body.style.overflow='';" style="border: none; background: rgba(239, 68, 68, 0.1); color: var(--bad); flex:0 0 auto;">×</button>
        </div>
        
        <form action="{{ url('/app/loyalty/campaigns') }}" method="POST">
            @csrf
            
            <label style="font-weight: 800; color: var(--txt); margin-bottom: 0.5rem; display:block;">عنوان کمپین</label>
            <input type="text" name="name" required placeholder="مثال: جشنواره فروش پاییزه یا دعوت دوستان" style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; border-radius: 0.75rem;">
            
            <div class="grid grid-2" style="margin-bottom: 1.5rem;">
                <div>
                    <label style="font-weight: 800; color: var(--txt); margin-bottom: 0.5rem; display:block;">تاریخ شروع (اختیاری)</label>
                    <input type="text" name="starts_at" class="jdate" placeholder="۱۴۰۴/۰۴/۰۱" style="padding: 0.75rem 1rem; border-radius: 0.75rem; text-align:right;">
                </div>
                <div>
                    <label style="font-weight: 800; color: var(--txt); margin-bottom: 0.5rem; display:block;">تاریخ پایان (اختیاری)</label>
                    <input type="text" name="ends_at" class="jdate" placeholder="۱۴۰۴/۱۲/۲۹" style="padding: 0.75rem 1rem; border-radius: 0.75rem; text-align:right;">
                </div>
            </div>

            <label style="font-weight: 800; color: var(--txt); margin-bottom: 0.5rem; display:block;">توضیحات و قوانین کمپین</label>
            <textarea name="description" rows="4" placeholder="قوانین این کمپین را به صورت کامل و شفاف برای مشتریان بنویسید..." style="margin-bottom: 1.5rem; resize: vertical; padding: 0.75rem 1rem; border-radius: 0.75rem;"></textarea>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid var(--line); flex-wrap:wrap;">
                <button type="button" class="btn-outline" onclick="document.getElementById('modal-add-campaign').classList.remove('show'); document.body.style.overflow='';" style="padding: 0.75rem 1.5rem; min-height:2.8rem;">انصراف</button>
                <button type="submit" class="btn-modern" style="padding: 0.75rem 2rem; min-height:2.8rem;">ثبت و ایجاد کمپین</button>
            </div>
        </form>
    </div>
</div>

<!-- مُدال تعریف مأموریت - دو بخش رفتار مشتری و پاداش و جایزه در دسکتاپ کنار هم -->
<div id="modal-add-mission" class="modal" onclick="if(event.target===this){ this.classList.remove('show'); document.body.style.overflow=''; }">
    <div class="modal-box" style="max-width: 880px; border-radius: 1.5rem; width:min(96vw, 880px);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 1.25rem; margin-bottom: 1.5rem; gap:1rem;">
            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--txt); line-height:1.7;">تعریف مأموریت برای: <span id="mission-camp-name" style="color:var(--acc)"></span></h2>
            <button type="button" class="icon-btn" onclick="document.getElementById('modal-add-mission').classList.remove('show'); document.body.style.overflow='';" style="border: none; background: rgba(239, 68, 68, 0.1); color: var(--bad); flex:0 0 auto;">×</button>
        </div>
        
        <form id="form-add-mission" action="" method="POST">
            @csrf
            
            <div class="mission-dual-layout">
                <div class="mission-box is-action">
                    <h4 class="mission-box-title is-blue"><span>🎯</span> رفتار مشتری (مأموریت)</h4>
                    
                    <div style="display:flex;flex-direction:column;gap:.4rem;">
                        <label style="font-weight: 800; color: var(--txt); font-size:.88rem;">هنگامی که مشتری این کار را انجام داد:</label>
                        <select name="action_type" required style="padding: 0.75rem 1rem; border-radius: 0.75rem; min-height:2.9rem;">
                            <option value="referral_registered">معرفی مشتری جدید با پیوند اختصاصی (دعوت دوستان)</option>
                            <option value="first_purchase">ثبت اولین سفارش موفق در سامانه</option>
                            <option value="profile_complete">تکمیل اطلاعات نمایه کاربری</option>
                        </select>
                    </div>
                    
                    <div style="display:flex;flex-direction:column;gap:.4rem; margin-top:auto;">
                        <label style="font-weight: 800; color: var(--txt); font-size:.88rem;">شرط تکمیلی (اختیاری):</label>
                        <input type="text" name="condition_details" placeholder="مثال: تنها در صورتی که شخص دعوت‌شده حداقل یک‌بار خرید انجام دهد..." style="padding: 0.75rem 1rem; border-radius: 0.75rem;">
                        <small style="color:var(--mut);font-size:.74rem;line-height:1.8;">مثال: این ماموریت فقط برای مشتریانی که بالای ۵۰۰ هزار تومان خرید کرده‌اند فعال شود</small>
                    </div>
                </div>

                <div class="mission-box is-reward">
                    <h4 class="mission-box-title is-green"><span>🎁</span> پاداش و جایزه</h4>
                    <div style="display:flex;flex-direction:column;gap:.4rem;">
                        <label style="font-weight: 800; color: var(--txt); font-size:.88rem;">نوع پاداش اعطایی</label>
                        <select name="reward_type" required style="padding: 0.75rem 1rem; border-radius: 0.75rem; min-height:2.9rem;">
                            <option value="points_fixed">امتیاز (جهت تبدیل به تخفیف)</option>
                            <option value="cashback_fixed">سکه مجازی / بازگشت وجه (مبلغ ثابت)</option>
                            <option value="coupon_percent">کد تخفیف درصدی</option>
                        </select>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:.4rem; margin-top:auto;">
                        <label style="font-weight: 800; color: var(--txt); font-size:.88rem;">مقدار پاداش</label>
                        <input type="number" name="reward_value" min="1" required placeholder="مثال: ۱۰۰" style="padding: 0.75rem 1rem; border-radius: 0.75rem; min-height:2.9rem;">
                        <small style="color:var(--mut);font-size:.74rem;line-height:1.8;">مثال: ۱۰۰ امتیاز یا ۵۰ هزار تومان کیف پول یا ۲۰ درصد تخفیف</small>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid var(--line); flex-wrap:wrap;">
                <button type="button" class="btn-outline" onclick="document.getElementById('modal-add-mission').classList.remove('show'); document.body.style.overflow='';" style="padding: 0.75rem 1.5rem; min-height:2.8rem;">بستن</button>
                <button type="submit" class="btn-modern" style="padding: 0.75rem 2rem; min-height:2.8rem;">ثبت مأموریت در سامانه</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMissionModal(campaignId, campaignName) {
        document.getElementById('mission-camp-name').innerText = campaignName;
        document.getElementById('form-add-mission').action = "{{ url('/app/loyalty/campaigns') }}/" + campaignId + "/reward-rules";
        document.getElementById('modal-add-mission').classList.add('show');
        document.body.style.overflow='hidden';
    }
    document.addEventListener('keydown', function(e){
        if(e.key==='Escape'){
            document.querySelectorAll('.modal.show').forEach(function(m){ m.classList.remove('show'); });
            document.body.style.overflow='';
        }
    });
</script>
@endsection