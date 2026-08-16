@extends('layouts.app')

@section('title', 'مرکز پیام‌رسانی')
@section('heading', '📨 مرکز پیام‌رسانی هوشمند')
@section('subtitle', 'ارسال گروهی و شخصی‌سازی‌شده پیامک، ایمیل و پیام‌رسان به مشتریان')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/messaging-center.css') }}">
@endpush

@section('content')

@php
    $segments = $segments ?? [];
    $selectedSegment = $selectedSegment ?? 'all';
    $recipientCount = $recipientCount ?? 0;
    $templates = $templates ?? [];
    $recentMessages = $recentMessages ?? collect();
    $preSelectedCustomers = $preSelectedCustomers ?? [];
@endphp

<div class="msg-page" dir="rtl">

    <section class="msg-hero">
        <div>
            <h2>📨 مرکز پیام‌رسانی هوشمند</h2>
            <p>به گروه‌های مختلف مشتریان پیامک، ایمیل یا پیام‌رسان ارسال کن. قالب آماده انتخاب کن یا متن اختصاصی بنویس.</p>
        </div>
        <div class="msg-hero-stats">
            <div><span>مشتریان قابل ارسال</span><b>@fa($recipientCount)</b></div>
            <div><span>ارسال‌های امروز</span><b>@fa($sentToday ?? 0)</b></div>
        </div>
    </section>

    <div class="msg-layout">

        <div class="msg-main">
            <form method="post" action="{{ url('/app/messages/send-bulk') }}" class="msg-form" id="msgForm">
                @csrf
                <input type="hidden" name="selected_ids" id="selectedIdsInput" value="">

                <div class="msg-block">
                    <div class="msg-block-header">
                        <h4>👥 انتخاب گروه مشتریان</h4>
                        <small>مشخص کن پیام به کدام دسته از مشتریان ارسال شود — می‌توانی گروه آماده انتخاب کنی یا مشتریان را دستی برگزینی</small>
                    </div>
                    <div class="msg-segment-grid">
                        <label class="msg-segment-card {{ $selectedSegment === 'all' ? 'active' : '' }}" data-count="{{ $segments['all']['count'] ?? 0 }}" onclick="pickSegment('all', this, {{ $segments['all']['count'] ?? 0 }})">
                            <input type="radio" name="segment" value="all" {{ $selectedSegment === 'all' ? 'checked' : '' }}>
                            <div class="msg-segment-icon">🌐</div><b>همه مشتریان</b><small>@fa($segments['all']['count'] ?? 0) مشتری</small>
                        </label>
                        <label class="msg-segment-card {{ $selectedSegment === 'club' ? 'active' : '' }}" data-count="{{ $segments['club']['count'] ?? 0 }}" onclick="pickSegment('club', this, {{ $segments['club']['count'] ?? 0 }})">
                            <input type="radio" name="segment" value="club" {{ $selectedSegment === 'club' ? 'checked' : '' }}>
                            <div class="msg-segment-icon">⭐</div><b>اعضای باشگاه</b><small>@fa($segments['club']['count'] ?? 0) عضو</small>
                        </label>
                        <label class="msg-segment-card {{ $selectedSegment === 'recent' ? 'active' : '' }}" data-count="{{ $segments['recent']['count'] ?? 0 }}" onclick="pickSegment('recent', this, {{ $segments['recent']['count'] ?? 0 }})">
                            <input type="radio" name="segment" value="recent" {{ $selectedSegment === 'recent' ? 'checked' : '' }}>
                            <div class="msg-segment-icon">🛒</div><b>خریداران ۳۰ روز</b><small>@fa($segments['recent']['count'] ?? 0) مشتری</small>
                        </label>
                        <label class="msg-segment-card {{ $selectedSegment === 'inactive' ? 'active' : '' }}" data-count="{{ $segments['inactive']['count'] ?? 0 }}" onclick="pickSegment('inactive', this, {{ $segments['inactive']['count'] ?? 0 }})">
                            <input type="radio" name="segment" value="inactive" {{ $selectedSegment === 'inactive' ? 'checked' : '' }}>
                            <div class="msg-segment-icon">💤</div><b>مشتریان کم‌فعال</b><small>@fa($segments['inactive']['count'] ?? 0) مشتری</small>
                        </label>
                        <label class="msg-segment-card {{ $selectedSegment === 'vip' ? 'active' : '' }}" data-count="{{ $segments['vip']['count'] ?? 0 }}" onclick="pickSegment('vip', this, {{ $segments['vip']['count'] ?? 0 }})">
                            <input type="radio" name="segment" value="vip" {{ $selectedSegment === 'vip' ? 'checked' : '' }}>
                            <div class="msg-segment-icon">👑</div><b>مشتریان VIP</b><small>@fa($segments['vip']['count'] ?? 0) مشتری</small>
                        </label>
                        <label class="msg-segment-card {{ $selectedSegment === 'selected' ? 'active' : '' }} msg-segment-pick" data-count="0" onclick="pickSegment('selected', this, selectedCustomers.length)" style="grid-column: 1 / -1;">
                            <input type="radio" name="segment" value="selected" {{ $selectedSegment === 'selected' ? 'checked' : '' }}>
                            <div class="msg-segment-icon">🎯</div><b>🔍 مشتریان انتخابی (دستی)</b><small>مشتریانی که خودت انتخاب می‌کنی</small>
                        </label>
                    </div>

                    {{-- پنل جستجوی مشتری — فقط وقتی «مشتریان انتخابی» فعال است --}}
                    <div class="msg-pick-panel {{ $selectedSegment === 'selected' ? 'active' : '' }}" id="pickPanel">
                        <div class="msg-pick-search">
                            <input type="text" id="customerSearchInput" placeholder="🔍 جستجوی مشتری با نام، شماره، یا ایمیل..." autocomplete="off" oninput="searchCustomers(this.value)">
                            <div class="msg-pick-results" id="pickResults"></div>
                        </div>
                        <div class="msg-pick-chips" id="pickChips">
                            @if(!empty($preSelectedCustomers))
                                @foreach($preSelectedCustomers as $c)
                                    <span class="pick-chip" data-id="{{ $c['id'] }}">
                                        <span class="pick-chip-avatar">{{ mb_substr($c['name'], 0, 1) }}</span>
                                        <span class="pick-chip-name">{{ $c['name'] }}</span>
                                        <span class="pick-chip-phone">{{ $c['phone'] ?? '' }}</span>
                                        <button type="button" class="pick-chip-remove" onclick="removePick(this)" title="حذف">×</button>
                                    </span>
                                @endforeach
                            @endif
                        </div>
                        <div class="msg-pick-footer" id="pickFooter">
                            <span>تعداد انتخاب‌شده: <b id="pickCount">@fa(count($preSelectedCustomers))</b> مشتری</span>
                            <button type="button" class="btn btn-ghost" onclick="clearAllPicks()" id="clearPicksBtn" style="font-size:0.75rem;">🗑️ حذف همه</button>
                        </div>
                    </div>
                </div>

                <div class="msg-block">
                    <div class="msg-block-header">
                        <h4>📡 کانال ارسال</h4>
                        <small>می‌توانی چند کانال را همزمان انتخاب کنی</small>
                    </div>
                    <div class="msg-channels-modern">
                        <label class="mch-card active" data-channel="sms">
                            <input type="checkbox" name="channels[]" value="sms" checked onchange="toggleChannel(this)">
                            <span class="mch-icon-wrap"><span class="mch-icon">💬</span></span>
                            <div class="mch-info"><b>پیامک</b><small>ارسال از طریق پنل اس‌ام‌اس</small></div>
                            <span class="mch-check">✓</span>
                        </label>
                        <label class="mch-card" data-channel="email">
                            <input type="checkbox" name="channels[]" value="email" onchange="toggleChannel(this)">
                            <span class="mch-icon-wrap mch-email"><span class="mch-icon">✉️</span></span>
                            <div class="mch-info"><b>ایمیل</b><small>ارسال از طریق SMTP</small></div>
                            <span class="mch-check">✓</span>
                        </label>
                        <label class="mch-card" data-channel="telegram">
                            <input type="checkbox" name="channels[]" value="telegram" onchange="toggleChannel(this)">
                            <span class="mch-icon-wrap mch-telegram"><span class="mch-icon">✈️</span></span>
                            <div class="mch-info"><b>تلگرام</b><small>ارسال از طریق ربات</small></div>
                            <span class="mch-check">✓</span>
                        </label>
                        <label class="mch-card" data-channel="bale">
                            <input type="checkbox" name="channels[]" value="bale" onchange="toggleChannel(this)">
                            <span class="mch-icon-wrap mch-bale"><span class="mch-icon">💬</span></span>
                            <div class="mch-info"><b>بله</b><small>پیام‌رسان ایرانی</small></div>
                            <span class="mch-check">✓</span>
                        </label>
                        <label class="mch-card" data-channel="whatsapp">
                            <input type="checkbox" name="channels[]" value="whatsapp" onchange="toggleChannel(this)">
                            <span class="mch-icon-wrap mch-whatsapp"><span class="mch-icon">💚</span></span>
                            <div class="mch-info"><b>واتساپ</b><small>WhatsApp Business</small></div>
                            <span class="mch-check">✓</span>
                        </label>
                        <label class="mch-card" data-channel="eitaa">
                            <input type="checkbox" name="channels[]" value="eitaa" onchange="toggleChannel(this)">
                            <span class="mch-icon-wrap mch-eitaa"><span class="mch-icon">🌐</span></span>
                            <div class="mch-info"><b>ایتا</b><small>پیام‌رسان ایرانی</small></div>
                            <span class="mch-check">✓</span>
                        </label>
                        <label class="mch-card" data-channel="rubika">
                            <input type="checkbox" name="channels[]" value="rubika" onchange="toggleChannel(this)">
                            <span class="mch-icon-wrap mch-rubika"><span class="mch-icon">🤖</span></span>
                            <div class="mch-info"><b>روبیکا</b><small>پیام‌رسان ایرانی</small></div>
                            <span class="mch-check">✓</span>
                        </label>
                    </div>
                </div>

                <div class="msg-block">
                    <div class="msg-block-header">
                        <h4>📋 قالب‌های آماده</h4>
                        <small>با یک کلیک متن را با متغیرهای هوشمند پر کن</small>
                    </div>
                    <div class="msg-template-row">
                        <button type="button" class="msg-template-chip" onclick="useTemplate('{نام} عزیز، 🌹\n\nخرید شما با موفقیت در سامانه ثبت شد. سفارش شما به زودی آماده و ارسال خواهد شد.\n\nشماره پیگیری: {کد}\n\nاز اعتماد شما سپاسگزاریم. 😊')"><span class="mtc-icon">🛒</span><div><b>تأیید سفارش</b><small>مناسب بعد از خرید</small></div></button>
                        <button type="button" class="msg-template-chip" onclick="useTemplate('🎁 {نام} عزیز،\n\nپیشنهاد ویژه فقط برای شما:\n🔥 {کد}\nبا این کد از ۱۵٪ تخفیف روی خرید بعدی بهره‌مند شوید.\n\n⏰ فرصت محدود — همین امروز اقدام کنید.\n\nمنتظرتان هستیم 🌹')"><span class="mtc-icon">🎁</span><div><b>پیشنهاد تخفیف ۱۵٪</b><small>کد تخفیف اختصاصی</small></div></button>
                        <button type="button" class="msg-template-chip" onclick="useTemplate('سلام {نام} عزیز 👋\n\nمدتی از آخرین خرید شما می‌گذرد. دلمان برایتان تنگ شده! 🥺\n\n✨ کد بازگشت ویژه شما: {کد}\nبا این کد از ۲۰٪ تخفیف بهره‌مند شوید.\n\n🥰 همیشه منتظر حضور گرمتان هستیم.')"><span class="mtc-icon">🔄</span><div><b>بازگشت مشتری</b><small>برای مشتریان کم‌فعال</small></div></button>
                        <button type="button" class="msg-template-chip" onclick="useTemplate('🎂 {نام} عزیز، تولدتان مبارک! 🎉\n\nبه مناسبت این روز ویژه، یک هدیه کوچک برایتان داریم:\n🎀 کد هدیه: {کد}\n\nبا این کد از ۲۵٪ تخفیف ویژه برخوردار شوید.\n\nبرای دریافت هدیه به باشگاه مشتریان مراجعه کنید. 😍')"><span class="mtc-icon">🎂</span><div><b>تبریک تولد</b><small>کد تخفیف ۲۵٪</small></div></button>
                        <button type="button" class="msg-template-chip" onclick="useTemplate('🏅 {نام} عزیز، تبریک می‌گوییم!\n\nشما به سطح {سطح} در باشگاه مشتریان ارتقا یافتید.\n\n✨ امتیاز فعلی شما: {امتیاز} امتیاز\n🎁 امتیاز خود را به کد تخفیف تبدیل کنید.\n\nبرای مشاهده مزایای سطح جدید به پنل باشگاه مراجعه کنید.')"><span class="mtc-icon">🏅</span><div><b>ارتقای سطح</b><small>تبریک به اعضای باشگاه</small></div></button>
                        <button type="button" class="msg-template-chip" onclick="useTemplate('{نام} عزیز، سلام! 🌸\n\nبه خانواده مشتری‌یار خوش آمدید. 🥳\n\n✨ کد هدیه ثبت‌نام: {کد}\nبا این کد از ۱۰٪ تخفیف خرید اول بهره‌مند شوید.\n\n👥 با معرفی دوستان خود امتیاز بیشتری کسب کنید.\n\nمنتظر اولین خریدتان هستیم 😊')"><span class="mtc-icon">👋</span><div><b>خوش‌آمد گویی</b><small>مناسب مشتریان جدید</small></div></button>
                    </div>
                </div>

                <div class="msg-block">
                    <div class="msg-block-header">
                        <h4>✏️ متن پیام</h4>
                        <small>کاراکتر: <b id="msgCharCount">۰</b> | گیرنده: <b id="msgRecipientCount">@fa($recipientCount)</b> | هزینه تقریبی هر پیامک: <b>{{ $smsCost ?? '۱۵' }} تومان</b></small>
                    </div>
                    <div class="msg-emoji-bar" id="emojiBar">
                        <button type="button" class="emoji-cat-btn active" data-cat="all" onclick="filterEmoji('all',this)">⭐ همه</button>
                        <button type="button" class="emoji-cat-btn" data-cat="face" onclick="filterEmoji('face',this)">😊 چهره</button>
                        <button type="button" class="emoji-cat-btn" data-cat="hand" onclick="filterEmoji('hand',this)">👋 دست</button>
                        <button type="button" class="emoji-cat-btn" data-cat="heart" onclick="filterEmoji('heart',this)">❤️ قلب</button>
                        <button type="button" class="emoji-cat-btn" data-cat="star" onclick="filterEmoji('star',this)">⭐ ستاره</button>
                        <button type="button" class="emoji-cat-btn" data-cat="object" onclick="filterEmoji('object',this)">🎁 اشیاء</button>
                        <button type="button" class="emoji-cat-btn" data-cat="nature" onclick="filterEmoji('nature',this)">🌸 طبیعت</button>
                        <button type="button" class="emoji-cat-btn" data-cat="food" onclick="filterEmoji('food',this)">🍕 خوراک</button>
                    </div>
                    <div class="msg-emoji-grid" id="emojiGrid"></div>
                    <textarea name="message" id="msgText" rows="6" placeholder="متن پیام را اینجا بنویسید... روی ایموجی‌های بالا کلیک کنید. از متغیرهای {نام} {موبایل} هم می‌توانید استفاده کنید." oninput="updateCharCount()" required></textarea>
                </div>

                <div class="msg-block" id="msgPreviewBlock" style="display:none;">
                    <div class="msg-block-header"><h4>👁️ پیش‌نمایش پیام</h4></div>
                    <div class="msg-preview-box" id="msgPreview"></div>
                </div>

                <div class="msg-actions">
                    <button type="button" class="btn btn-ghost" onclick="togglePreview()">👁️ پیش‌نمایش</button>
                    <button type="submit" class="btn" id="submitBtn" onclick="return confirmSend()">🚀 ارسال به <span id="submitCount">@fa($recipientCount)</span> مشتری</button>
                </div>
            </form>
        </div>

        <div class="msg-side">
            <div class="msg-side-card">
                <h4>💡 متغیرهای هوشمند</h4>
                <p style="font-size:0.72rem;color:var(--mut);margin:0 0 0.5rem;">برای کپی کلیک کن 👇</p>
                <div class="msg-guide-list">
                    <div class="msg-var-row" onclick="copyVar('{نام}')"><code>{نام}</code><span>← نام مشتری</span><small class="msg-copied-hint">کپی شد ✓</small></div>
                    <div class="msg-var-row" onclick="copyVar('{موبایل}')"><code>{موبایل}</code><span>← شماره موبایل</span><small class="msg-copied-hint">کپی شد ✓</small></div>
                    <div class="msg-var-row" onclick="copyVar('{کد}')"><code>{کد}</code><span>← کد تخفیف یکتا</span><small class="msg-copied-hint">کپی شد ✓</small></div>
                    <div class="msg-var-row" onclick="copyVar('{امتیاز}')"><code>{امتیاز}</code><span>← امتیاز باشگاه</span><small class="msg-copied-hint">کپی شد ✓</small></div>
                    <div class="msg-var-row" onclick="copyVar('{سطح}')"><code>{سطح}</code><span>← سطح عضویت</span><small class="msg-copied-hint">کپی شد ✓</small></div>
                </div>
            </div>
            <div class="msg-side-card">
                <h4>📜 آخرین ارسال‌ها</h4>
                @if($recentMessages->isNotEmpty())
                    <div class="msg-history-list">
                        @foreach($recentMessages->take(10) as $msg)
                            <div class="msg-history-row">
                                <div><b>{{ $msg->customer->full_name ?? 'گروهی' }}</b><small>@jdatetime($msg->created_at ?? now())</small></div>
                                @php $chMap=['sms'=>'پیامک','email'=>'ایمیل','telegram'=>'تلگرام','bale'=>'بله','whatsapp'=>'واتساپ','eitaa'=>'ایتا','rubika'=>'روبیکا']; @endphp
                                <span class="msg-channel-badge">{{ $chMap[$msg->channel??'sms']??'پیامک' }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="msg-empty-mini">هنوز پیامی ارسال نشده است.</div>
                @endif
            </div>
            <div class="msg-side-card">
                <h4>⚠️ نکات مهم</h4>
                <ul class="msg-tips">
                    <li>برای ارسال پیامک، سرویس پیامک باید در تنظیمات فعال باشد.</li>
                    <li>هر پیامک فارسی حداکثر ۷۰ کاراکتر است.</li>
                    <li>ارسال به گروه‌های بزرگ ممکن است چند دقیقه طول بکشد.</li>
                    <li>متغیرها در لحظه ارسال با اطلاعات واقعی جایگزین می‌شوند.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
var selectedCustomers = [];
var pickTimer = null;

@if(!empty($preSelectedCustomers))
    @foreach($preSelectedCustomers as $c)
        selectedCustomers.push({id: {{ $c['id'] }}, name: '{{ addslashes($c['name']) }}', phone: '{{ addslashes($c['phone'] ?? '') }}'});
    @endforeach
@endif

function pickSegment(val, card, count) {
    document.querySelectorAll('.msg-segment-card').forEach(function(c){c.classList.remove('active');});
    card.classList.add('active');
    card.querySelector('input[type="radio"]').checked = true;
    var panel = document.getElementById('pickPanel');
    if (val === 'selected') {
        panel.classList.add('active');
        updatePickCount();
        document.getElementById('selectedIdsInput').value = selectedCustomers.map(function(x){return x.id;}).join(',');
    } else {
        panel.classList.remove('active');
        document.getElementById('selectedIdsInput').value = '';
        var el = document.getElementById('msgRecipientCount');
        var submitEl = document.getElementById('submitCount');
        if (el) el.textContent = faNum(count);
        if (submitEl) submitEl.textContent = faNum(count);
    }
}

function faNum(n){return String(n).replace(/[0-9]/g,function(d){return['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'][d];});}

function updatePickCount() {
    var cnt = selectedCustomers.length;
    var el = document.getElementById('pickCount');
    if (el) el.textContent = faNum(cnt);
    var recEl = document.getElementById('msgRecipientCount');
    if (recEl) recEl.textContent = faNum(cnt);
    var subEl = document.getElementById('submitCount');
    if (subEl) subEl.textContent = faNum(cnt);
    document.getElementById('selectedIdsInput').value = selectedCustomers.map(function(x){return x.id;}).join(',');
    var clr = document.getElementById('clearPicksBtn');
    if (clr) clr.style.display = cnt > 0 ? '' : 'none';
}

function renderChips() {
    var container = document.getElementById('pickChips');
    if (!container) return;
    container.innerHTML = selectedCustomers.map(function(c){
        return '<span class="pick-chip" data-id="'+c.id+'">'+
            '<span class="pick-chip-avatar">'+c.name.charAt(0)+'</span>'+
            '<span class="pick-chip-name">'+c.name+'</span>'+
            '<span class="pick-chip-phone">'+(c.phone||'')+'</span>'+
            '<button type="button" class="pick-chip-remove" onclick="removePick(this)" title="حذف">×</button>'+
        '</span>';
    }).join('');
    updatePickCount();
}

function addPick(customer) {
    var exists = selectedCustomers.find(function(x){return x.id === customer.id;});
    if (exists) {
        var chip = document.querySelector('.pick-chip[data-id="'+customer.id+'"]');
        if (chip) { chip.style.background = 'rgba(16,185,129,0.12)'; setTimeout(function(){chip.style.background='';},600); }
        return;
    }
    selectedCustomers.push(customer);
    renderChips();
}

function removePick(btn) {
    var chip = btn.closest('.pick-chip');
    if (!chip) return;
    var id = parseInt(chip.dataset.id);
    selectedCustomers = selectedCustomers.filter(function(x){return x.id !== id;});
    renderChips();
}

function clearAllPicks() {
    if (!selectedCustomers.length) return;
    if (!confirm('همه ' + selectedCustomers.length + ' مشتری انتخاب‌شده حذف شوند؟')) return;
    selectedCustomers = [];
    renderChips();
}

function searchCustomers(term) {
    clearTimeout(pickTimer);
    var results = document.getElementById('pickResults');
    if (!term || term.trim().length < 2) {
        results.innerHTML = '';
        results.classList.remove('visible');
        return;
    }
    pickTimer = setTimeout(function(){
        fetch('/app/messages/search-customers?q=' + encodeURIComponent(term), {
            headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
        })
        .then(function(r){return r.json();})
        .then(function(data){
            if (data.customers && data.customers.length) {
                results.innerHTML = data.customers.map(function(c){
                    return '<div class="pick-result-row" onclick="addPick({id:'+c.id+',name:\''+c.full_name.replace(/'/g,"\\\\'")+'\',phone:\''+(c.phone||'')+'\'})">'+
                        '<b>'+c.full_name+'</b><small>'+(c.phone||'')+'</small>'+
                    '</div>';
                }).join('');
                results.classList.add('visible');
            } else {
                results.innerHTML = '<div class="pick-result-row pick-no-result">مشتری یافت نشد</div>';
                results.classList.add('visible');
            }
        })
        .catch(function(){
            results.innerHTML = '<div class="pick-result-row pick-no-result">خطا در جستجو</div>';
            results.classList.add('visible');
        });
    }, 300);
}

function confirmSend() {
    var seg = document.querySelector('input[name="segment"]:checked');
    if (!seg) { alert('لطفاً یک گروه مشتری انتخاب کنید.'); return false; }
    if (seg.value === 'selected') {
        if (selectedCustomers.length === 0) {
            alert('حداقل یک مشتری را برای ارسال انتخاب کنید.');
            return false;
        }
        return confirm('آیا از ارسال پیام به ' + selectedCustomers.length + ' مشتری انتخاب‌شده اطمینان دارید؟\n\nاین عملیات قابل بازگشت نیست.');
    }
    var cnt = seg.closest('.msg-segment-card') ? (seg.closest('.msg-segment-card').dataset.count || 0) : 0;
    return confirm('آیا از ارسال پیام به ' + faNum(cnt) + ' مشتری اطمینان دارید؟\n\nاین عملیات قابل بازگشت نیست.');
}

document.addEventListener('click', function(e){
    var results = document.getElementById('pickResults');
    if (!results) return;
    if (!e.target.closest('#customerSearchInput') && !e.target.closest('#pickResults')) {
        results.classList.remove('visible');
    }
});

document.getElementById('customerSearchInput').addEventListener('focus', function(){
    var results = document.getElementById('pickResults');
    if (results && results.children.length > 0) results.classList.add('visible');
});

var allEmojis = {
    face:['😊','😂','🥰','😍','🤩','😎','🥳','😇','🤗','😌','🙂','😉','😋','🤔','😅','😁','😢','😤','🥺','😴','🤐','🫡','😶‍🌫️','🫠'],
    hand:['👋','👍','👎','👏','🙌','🤝','💪','✌️','🤞','👌','🤌','🫶','🙏','✋','👆','👇','👉','👈','🤙','🖐️'],
    heart:['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💕','💞','💓','💗','💖','💘','💝','♥️','🫶','💌','💟'],
    star:['⭐','🌟','✨','💫','🎯','🔥','💥','🎉','🎊','🎀','🏆','🥇','🥈','🥉','💎','🔔','📢','💡','💯','✅'],
    object:['🎁','🎂','🍰','🎈','💐','🌸','🌹','💍','📱','💻','🎵','🎶','📷','🎬','📦','💰','💳','🛒','📌','📍'],
    nature:['🌸','🌺','🌻','🌹','🍀','🌈','☀️','🌙','⚡','💧','🔥','❄️','🌊','🍃','🌿','🪷','💮','🏵️','🌼','🌷'],
    food:['🍕','🍔','🍩','☕','🍰','🧁','🍫','🍿','🍉','🍇','🥤','🧃','🍹','🍪','🥐','🍞','🧀','🍗','🥗','🍜']
};

function renderEmojiGrid(cat){
    var g=document.getElementById('emojiGrid');if(!g)return;
    var e=cat==='all'?Object.values(allEmojis).flat():(allEmojis[cat]||[]);
    g.innerHTML=e.map(function(x){return'<button type="button" class="emoji-item" onclick="insertEmoji(\''+x+'\')" title="'+x+'">'+x+'</button>';}).join('');
}
function filterEmoji(cat,btn){
    document.querySelectorAll('.emoji-cat-btn').forEach(function(b){b.classList.remove('active');});
    if(btn)btn.classList.add('active');renderEmojiGrid(cat);
}
function insertEmoji(e){
    var ta=document.getElementById('msgText');if(!ta)return;
    var s=ta.selectionStart,en=ta.selectionEnd;
    var b=ta.value.substring(0,s),a=ta.value.substring(en);
    ta.value=b+e+a;ta.selectionStart=ta.selectionEnd=s+e.length;ta.focus();updateCharCount();
}
function toggleChannel(cb){
    var card=cb.closest('.mch-card');if(!card)return;
    card.classList.toggle('active',cb.checked);
}
document.querySelectorAll('.mch-card input[type="checkbox"]').forEach(function(cb){toggleChannel(cb);cb.addEventListener('change',function(){toggleChannel(cb);});});

function copyVar(text){
    navigator.clipboard.writeText(text).then(function(){
        document.querySelectorAll('.msg-var-row').forEach(function(r){r.classList.remove('copied');});
        document.querySelectorAll('.msg-copied-hint').forEach(function(h){h.classList.remove('show');});
        var rows=document.querySelectorAll('.msg-var-row');
        rows.forEach(function(row){
            var code=row.querySelector('code');
            if(code&&code.textContent.trim()===text.trim()){
                var hint=row.querySelector('.msg-copied-hint');if(hint)hint.classList.add('show');row.classList.add('copied');
                setTimeout(function(){hint.classList.remove('show');row.classList.remove('copied');},1500);
            }
        });
        var ta=document.getElementById('msgText');if(ta){ta.value+=text;ta.focus();updateCharCount();}
    }).catch(function(){var ta=document.getElementById('msgText');if(ta){ta.value+=text;ta.focus();updateCharCount();}});
}
function updateCharCount(){
    var t=document.getElementById('msgText').value||'';
    var e=document.getElementById('msgCharCount');if(e)e.textContent=faNum(t.length);
}
function useTemplate(text){
    var ta=document.getElementById('msgText');if(ta){ta.value=text;updateCharCount();ta.focus();}
    window.scrollTo({top:ta.offsetTop-100,behavior:'smooth'});
}
function togglePreview(){
    var blk=document.getElementById('msgPreviewBlock'),prev=document.getElementById('msgPreview');
    var t=document.getElementById('msgText').value;if(!blk||!prev)return;
    if(blk.style.display==='block'){blk.style.display='none';return;}
    prev.innerHTML=(t||'متنی وارد نشده').replace(/\{نام\}/g,'<b style="color:var(--acc);">مشتری نمونه</b>').replace(/\{موبایل\}/g,'<b style="color:var(--acc);">۰۹۱۲۳۴۵۶۷۸۹</b>').replace(/\{کد\}/g,'<b style="color:#10b981;">MY-ABC123</b>').replace(/\{امتیاز\}/g,'<b style="color:#f59e0b;">۱۲۰۰</b>').replace(/\{سطح\}/g,'<b style="color:#8b5cf6;">طلایی</b>').replace(/\n/g,'<br>')||'<span style="color:var(--mut);">متنی وارد نشده است.</span>';
    blk.style.display='block';
}
document.getElementById('msgText').addEventListener('input',updateCharCount);
updateCharCount();renderEmojiGrid('all');updatePickCount();
</script>
@endsection