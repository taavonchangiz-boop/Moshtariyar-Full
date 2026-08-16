@extends('layouts.customer_portal')
@section('title','پروفایل باشگاه')
@section('content')
<link rel="stylesheet" href="{{ asset('css/club-portal-rewards.css') }}">
<link rel="stylesheet" href="{{ asset('css/club-portal-profile.css') }}">
@php
    $meta = $member->customer->meta ?: [];
    $isEditing = request()->boolean('edit') || ! $member->profile_completed;
    $avatar = $meta['avatar'] ?? null;
    $genderLabel = ['female' => 'خانم', 'male' => 'آقا'][$meta['gender'] ?? ''] ?? 'ثبت نشده';
    $recentWheelSpins = \Modules\Loyalty\Entities\WheelSpin::with(['coupon', 'prize'])->where('member_id', $member->id)->latest('id')->limit(4)->get();
    $typeLabels = \Modules\Loyalty\Entities\WheelPrize::TYPES;
@endphp

<div class="club-profile-page">
    <section class="club-profile-hero">
        <div class="club-profile-identity">
            <div class="club-profile-avatar">
                @if($avatar)
                    <img src="{{ asset($avatar) }}" alt="{{ $member->customer->full_name }}">
                @else
                    <span>{{ mb_substr($member->customer->full_name ?: 'م', 0, 1) }}</span>
                @endif
            </div>
            <div>
                <span class="club-profile-eyebrow">پروفایل باشگاه مشتریان</span>
                <h1>{{ $member->customer->full_name ?: 'مشتری عزیز' }}</h1>
                <p>اطلاعات هویتی، نشانی، تصویر پروفایل و وضعیت تکمیل حساب شما در این بخش مدیریت می‌شود.</p>
                <div class="club-profile-actions">
                    @if($isEditing)
                        <a class="btn btn-ghost" href="{{ route('club.profile') }}">نمایش پروفایل</a>
                    @else
                        <a class="btn" href="{{ route('club.profile', ['edit' => 1]) }}">ویرایش پروفایل</a>
                    @endif
                    <a class="btn btn-ghost" href="{{ route('club.journey') }}">سفر من</a>
                </div>
            </div>
        </div>
        <div class="club-profile-score">
            <div><span>وضعیت تکمیل</span><b>{{ $member->profile_completed ? 'تکمیل شده' : 'نیازمند تکمیل' }}</b><small>برای دریافت پاداش‌های پروفایل</small></div>
            <div><span>امتیاز فعلی</span><b>@fa(number_format($member->points))</b><small>امتیاز قابل استفاده شما</small></div>
            <div><span>سطح عضویت</span><b>{{ $member->tier?->name ?? 'بدون سطح' }}</b><small>جایگاه فعلی شما</small></div>
        </div>
    </section>

    @if(! $isEditing)
        <section class="club-profile-grid">
            <article class="club-profile-card is-accent" style="--profile-card-color:#0ea5e9;">
                <header>
                    <div>
                        <span>اطلاعات ذخیره‌شده</span>
                        <h2>مشخصات حساب</h2>
                    </div>
                    <a class="btn btn-ghost" href="{{ route('club.profile', ['edit' => 1]) }}">ویرایش</a>
                </header>
                <div class="club-profile-info-grid">
                    <div><span>نام و نام خانوادگی</span><b>{{ $member->customer->full_name ?: 'ثبت نشده' }}</b></div>
                    <div><span>شماره موبایل</span><b class="ltr">{{ $member->customer->phone ?: 'ثبت نشده' }}</b></div>
                    <div><span>ایمیل</span><b class="ltr">{{ $member->customer->email ?: 'ثبت نشده' }}</b></div>
                    <div><span>تاریخ تولد</span><b>{{ $meta['birthday'] ?? 'ثبت نشده' }}</b></div>
                    <div><span>جنسیت</span><b>{{ $genderLabel }}</b></div>
                    <div><span>استان</span><b>{{ $meta['province'] ?? 'ثبت نشده' }}</b></div>
                    <div><span>شهر</span><b>{{ $meta['city'] ?? 'ثبت نشده' }}</b></div>
                    <div class="club-profile-wide"><span>آدرس</span><b>{{ $meta['address'] ?? 'ثبت نشده' }}</b></div>
                </div>
            </article>

            <article class="club-profile-card is-accent" style="--profile-card-color:#10b981;">
                <header>
                    <div>
                        <span>تصویر پروفایل</span>
                        <h2>نمایش حساب شما</h2>
                    </div>
                </header>
                <div class="club-profile-avatar-large">
                    @if($avatar)
                        <img src="{{ asset($avatar) }}" alt="{{ $member->customer->full_name }}">
                    @else
                        <span>{{ mb_substr($member->customer->full_name ?: 'م', 0, 1) }}</span>
                    @endif
                </div>
                <p class="muted">برای تغییر تصویر، وارد حالت ویرایش شوید. تصویر جدید پس از تنظیم داخل قاب، بهینه و فقط با فرمت webp ذخیره می‌شود.</p>
                <div class="club-profile-actions"><a class="btn btn-ghost" href="{{ route('club.profile', ['edit' => 1]) }}">تغییر تصویر</a></div>
            </article>
        </section>
    @else
        <section class="club-profile-card is-accent" style="--profile-card-color:#0ea5e9;">
            <header>
                <div>
                    <span>ویرایش پروفایل</span>
                    <h2>{{ $member->profile_completed ? 'اطلاعات خود را ویرایش کنید' : 'پروفایل را تکمیل کنید و پاداش بگیرید' }}</h2>
                </div>
                @if($member->profile_completed)
                    <a class="btn btn-ghost" href="{{ route('club.profile') }}">انصراف</a>
                @endif
            </header>
            <form method="post" action="{{ route('club.profile.update') }}" enctype="multipart/form-data" id="clubProfileForm">
                @csrf
                <input type="hidden" name="avatar_data" id="avatarDataInput">
                <div class="club-profile-form-grid">
                    <div class="club-profile-avatar-editor">
                        <label>تصویر پروفایل</label>
                        <div class="club-avatar-cropper" id="avatarCropper">
                            <img id="avatarCropImage" src="{{ $avatar ? asset($avatar) : '' }}" alt="پیش‌نمایش تصویر" @if(!$avatar) style="display:none" @endif>
                            <div id="avatarCropPlaceholder" @if($avatar) style="display:none" @endif>تصویر را انتخاب کنید</div>
                        </div>
                        <div class="club-avatar-controls">
                            <label>بزرگ‌نمایی تصویر</label>
                            <input type="range" id="avatarZoom" min="1" max="3" step="0.01" value="1">
                            <label>جابجایی افقی</label>
                            <input type="range" id="avatarMoveX" min="-100" max="100" step="1" value="0">
                            <label>جابجایی عمودی</label>
                            <input type="range" id="avatarMoveY" min="-100" max="100" step="1" value="0">
                        </div>
                        <input type="file" name="avatar" id="avatarFileInput" accept="image/*" data-hint="تصویر پروفایل؛ پس از تنظیم داخل قاب به webp تبدیل و ذخیره می‌شود.">
                        <small class="club-profile-help">تصویر را انتخاب کنید، داخل قاب مربع تنظیم کنید و سپس ذخیره را بزنید.</small>
                    </div>

                    <div class="club-profile-fields">
                        <div class="grid grid-2">
                            <div><label>نام و نام خانوادگی</label><input name="full_name" value="{{ old('full_name', $member->customer->full_name) }}" required></div>
                            <div><label>شماره موبایل</label><input name="phone" value="{{ old('phone', $member->customer->phone) }}" class="ltr" required></div>
                            <div><label>ایمیل معتبر</label><input name="email" value="{{ old('email', $member->customer->email) }}" class="ltr" type="email" required></div>
                            <div><label>تاریخ تولد</label><input name="birthday" value="{{ old('birthday', $meta['birthday'] ?? '') }}" class="jdate" required placeholder="۱۳۷۰/۰۱/۰۱"></div>
                            <div><label>جنسیت</label><select name="gender"><option value="">انتخاب کنید</option><option value="female" @selected(old('gender', $meta['gender'] ?? '') === 'female')>خانم</option><option value="male" @selected(old('gender', $meta['gender'] ?? '') === 'male')>آقا</option></select></div>
                            <div><label>استان</label><select name="province" id="provinceSelect" data-current="{{ old('province', $meta['province'] ?? '') }}"><option value="">انتخاب استان</option></select></div>
                            <div><label>شهر</label><select name="city" id="citySelect" data-current="{{ old('city', $meta['city'] ?? '') }}"><option value="">ابتدا استان را انتخاب کنید</option></select></div>
                            <div class="club-profile-wide"><label>آدرس</label><input name="address" value="{{ old('address', $meta['address'] ?? '') }}" placeholder="خیابان، کوچه، پلاک..."></div>
                            <div><label>رمز عبور جدید، اختیاری</label><input type="password" name="password" placeholder="اگر نمی‌خواهید تغییر کند خالی بگذارید"></div>
                            <div><label>تکرار رمز عبور جدید</label><input type="password" name="password_confirmation"></div>
                        </div>
                        <button class="btn" style="margin-top:18px;width:100%">ذخیره پروفایل</button>
                    </div>
                </div>
            </form>
        </section>
    @endif

    <section class="club-card-pro" style="margin-top:16px">
        <header>
            <div>
                <span class="club-reward-eyebrow">جایزه‌های گردونه من</span>
                <h2>آخرین جایزه‌های ثبت‌شده در پروفایل</h2>
            </div>
            <a href="{{ route('club.rewards') }}">مشاهده همه ←</a>
        </header>
        <div class="club-reward-list">
            @forelse($recentWheelSpins as $spin)
                <article class="club-reward-item">
                    <div class="club-reward-icon">
                        @if($spin->prize_image)
                            <img src="{{ asset($spin->prize_image) }}" alt="{{ $spin->prize_title }}">
                        @else
                            🎁
                        @endif
                    </div>
                    <div>
                        <h3>{{ $spin->prize_title ?: 'جایزه گردونه' }}</h3>
                        <p>{{ $typeLabels[$spin->prize_type] ?? 'جایزه' }} · @jdatetime($spin->created_at)</p>
                        @if($spin->coupon)
                            <div class="club-code-box"><input value="{{ $spin->coupon->code }}" readonly onclick="this.select()"><button class="btn btn-ghost" type="button" onclick="copyClubCode(this)">کپی</button></div>
                        @endif
                    </div>
                    <span class="club-reward-status">{{ $spin->deliveryStatusLabel() }}</span>
                </article>
            @empty
                <div class="club-empty-state">هنوز جایزه‌ای از گردونه در پروفایل شما ثبت نشده است.</div>
            @endforelse
        </div>
        <div class="club-reward-actions"><a class="btn" href="{{ route('club.wheel') }}">رفتن به گردونه شانس</a><a class="btn btn-ghost" href="{{ route('club.rewards') }}">جایزه‌های من</a></div>
    </section>
</div>

@if($isEditing)
<script src="{{ asset('js/iran-cities.js') }}"></script>
<script>
const ps = document.getElementById('provinceSelect');
const cs = document.getElementById('citySelect');
function fillProvinces() {
    if (!ps || !cs) return;
    const currentProvince = ps.dataset.current || '';
    const cities = window.IR_CITIES || {};
    ps.innerHTML = '';
    ps.add(new Option('انتخاب استان', ''));
    Object.keys(cities).forEach(function (province) {
        const option = new Option(province, province);
        if (province === currentProvince) option.selected = true;
        ps.add(option);
    });
    if (currentProvince && !cities[currentProvince]) {
        const option = new Option(currentProvince, currentProvince);
        option.selected = true;
        ps.add(option);
    }
    fillCities();
}
function fillCities() {
    if (!ps || !cs) return;
    const province = ps.value;
    const currentCity = cs.dataset.current || '';
    const cities = window.IR_CITIES || {};
    cs.innerHTML = '';
    if (!province) {
        cs.add(new Option('ابتدا استان را انتخاب کنید', ''));
        return;
    }
    cs.add(new Option('انتخاب شهر', ''));
    (cities[province] || []).forEach(function (city) {
        const option = new Option(city, city);
        if (city === currentCity) option.selected = true;
        cs.add(option);
    });
    if (currentCity && !(cities[province] || []).includes(currentCity)) {
        const option = new Option(currentCity, currentCity);
        option.selected = true;
        cs.add(option);
    }
}
if (ps) ps.addEventListener('change', function () { cs.dataset.current = ''; fillCities(); });
fillProvinces();

const avatarFileInput = document.getElementById('avatarFileInput');
const avatarCropImage = document.getElementById('avatarCropImage');
const avatarPlaceholder = document.getElementById('avatarCropPlaceholder');
const avatarZoom = document.getElementById('avatarZoom');
const avatarMoveX = document.getElementById('avatarMoveX');
const avatarMoveY = document.getElementById('avatarMoveY');
const avatarDataInput = document.getElementById('avatarDataInput');
const profileForm = document.getElementById('clubProfileForm');
let avatarImageReady = Boolean(avatarCropImage && avatarCropImage.getAttribute('src'));
function updateAvatarCrop() {
    if (!avatarCropImage) return;
    const zoom = avatarZoom ? avatarZoom.value : 1;
    const x = avatarMoveX ? avatarMoveX.value : 0;
    const y = avatarMoveY ? avatarMoveY.value : 0;
    avatarCropImage.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) scale(${zoom})`;
}
[avatarZoom, avatarMoveX, avatarMoveY].forEach(function (input) {
    if (input) input.addEventListener('input', updateAvatarCrop);
});
if (avatarFileInput) {
    avatarFileInput.addEventListener('change', function () {
        const file = avatarFileInput.files && avatarFileInput.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (event) {
            avatarCropImage.src = event.target.result;
            avatarCropImage.style.display = 'block';
            avatarPlaceholder.style.display = 'none';
            avatarImageReady = true;
            if (avatarZoom) avatarZoom.value = 1;
            if (avatarMoveX) avatarMoveX.value = 0;
            if (avatarMoveY) avatarMoveY.value = 0;
            updateAvatarCrop();
        };
        reader.readAsDataURL(file);
    });
}
function buildAvatarWebp() {
    return new Promise(function (resolve) {
        if (!avatarImageReady || !avatarCropImage || !avatarCropImage.complete || !avatarCropImage.naturalWidth) {
            resolve('');
            return;
        }
        const canvas = document.createElement('canvas');
        const size = 512;
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, size, size);
        const zoom = parseFloat(avatarZoom ? avatarZoom.value : 1);
        const moveX = parseFloat(avatarMoveX ? avatarMoveX.value : 0) * 2.2;
        const moveY = parseFloat(avatarMoveY ? avatarMoveY.value : 0) * 2.2;
        const naturalW = avatarCropImage.naturalWidth;
        const naturalH = avatarCropImage.naturalHeight;
        const baseScale = Math.max(size / naturalW, size / naturalH);
        const drawW = naturalW * baseScale * zoom;
        const drawH = naturalH * baseScale * zoom;
        const dx = (size - drawW) / 2 + moveX;
        const dy = (size - drawH) / 2 + moveY;
        ctx.drawImage(avatarCropImage, dx, dy, drawW, drawH);
        resolve(canvas.toDataURL('image/webp', 0.86));
    });
}
if (profileForm) {
    profileForm.addEventListener('submit', function (event) {
        event.preventDefault();
        buildAvatarWebp().then(function (dataUrl) {
            if (dataUrl && avatarDataInput) avatarDataInput.value = dataUrl;
            profileForm.submit();
        });
    });
}
updateAvatarCrop();
</script>
@endif
<script>
function copyClubCode(button){const input=button.closest('.club-code-box')?.querySelector('input'); if(!input)return; input.select(); document.execCommand('copy'); button.textContent='کپی شد'; setTimeout(()=>button.textContent='کپی',1200);}
</script>
@endsection