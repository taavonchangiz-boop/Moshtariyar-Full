{{-- partial: 360/quick-actions --}}
@php
    $loyaltyMember = \Modules\Loyalty\Entities\LoyaltyMember::where('customer_id', $customer->id)->first();
@endphp

<div class="customer360-quick-card">
    <div class="customer360-quick-head">
        <div>
            <span>اقدامات سریع</span>
            <h3>کارهای فوری روی پرونده مشتری</h3>
        </div>
        <div class="customer360-quick-count">@fa(count($quick_actions ?? [])) اقدام</div>
    </div>

    <div class="customer360-quick-grid">
        @forelse($quick_actions ?? [] as $action)
            @php
                $label = $action['label'] ?? 'اقدام';
                $icon = $action['icon'] ?? '⚡';
                $isMessage = $label === 'ارسال پیام';
                $isActivity = $label === 'ثبت فعالیت';
                $isPoints = $label === 'تخصیص امتیاز';
                $isOrder = $label === 'ایجاد سفارش جدید';
            @endphp

            @if($isMessage)
                <a href="javascript:void(0)" onclick="openMessageModal()" class="customer360-quick-action customer360-quick-primary">
                    <span class="customer360-quick-icon">{{ $icon }}</span>
                    <span class="customer360-quick-text">
                        <b>{{ $label }}</b>
                        <small>ارسال پیام مستقیم به مشتری</small>
                    </span>
                </a>
            @elseif($isActivity)
                <a href="javascript:void(0)" onclick="openActivityModal()" class="customer360-quick-action">
                    <span class="customer360-quick-icon">{{ $icon }}</span>
                    <span class="customer360-quick-text">
                        <b>{{ $label }}</b>
                        <small>ثبت یادداشت یا پیگیری زمان‌دار</small>
                    </span>
                </a>
            @elseif($isPoints)
                <a href="javascript:void(0)" onclick="openPointsModal()" class="customer360-quick-action">
                    <span class="customer360-quick-icon">{{ $icon }}</span>
                    <span class="customer360-quick-text">
                        <b>{{ $label }}</b>
                        <small>افزایش یا کسر امتیاز باشگاه</small>
                    </span>
                </a>
            @elseif($isOrder)
                <a href="javascript:void(0)" onclick="openOrderModal()" class="customer360-quick-action">
                    <span class="customer360-quick-icon">{{ $icon }}</span>
                    <span class="customer360-quick-text">
                        <b>{{ $label }}</b>
                        <small>باز کردن ابزار ثبت سفارش سریع</small>
                    </span>
                </a>
            @elseif(!empty($action['onclick']))
                <a href="javascript:void(0)" onclick="{{ $action['onclick'] }}" class="customer360-quick-action">
                    <span class="customer360-quick-icon">{{ $icon }}</span>
                    <span class="customer360-quick-text">
                        <b>{{ $label }}</b>
                        <small>اجرای سریع روی همین مشتری</small>
                    </span>
                </a>
            @else
                <a href="{{ $action['url'] ?? '#' }}" class="customer360-quick-action">
                    <span class="customer360-quick-icon">{{ $icon }}</span>
                    <span class="customer360-quick-text">
                        <b>{{ $label }}</b>
                        <small>رفتن به بخش مربوط</small>
                    </span>
                </a>
            @endif
        @empty
            <div class="customer360-quick-empty">اقدام سریعی برای این مشتری تعریف نشده است.</div>
        @endforelse
    </div>
</div>

<div id="activityModal" class="customer360-modal">
    <div class="customer360-modal-card">
        <div class="customer360-modal-head">
            <div>
                <span>ثبت فعالیت</span>
                <h3>ثبت پیگیری برای {{ $customer->full_name }}</h3>
            </div>
            <button type="button" onclick="closeActivityModal()">×</button>
        </div>
        <form method="post" action="{{ url('/app/activities') }}" class="customer360-modal-body">
            @csrf
            <input type="hidden" name="subject_type" value="customer">
            <input type="hidden" name="subject_id" value="{{ $customer->id }}">
            <div class="customer360-modal-grid">
                <div>
                    <label>نوع فعالیت</label>
                    <select name="type">
                        @foreach(\Modules\Core\Entities\Activity::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($key === 'note')>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>تاریخ یادآوری، اختیاری</label>
                    <input name="due" class="jdate" placeholder="انتخاب تاریخ">
                </div>
            </div>
            <div>
                <label>توضیح فعالیت</label>
                <textarea name="body" rows="4" required placeholder="مثلاً تماس برای پیگیری سفارش، ارسال پیش‌فاکتور یا یادداشت داخلی"></textarea>
            </div>
            <div class="customer360-modal-actions">
                <button type="button" class="customer360-modal-cancel" onclick="closeActivityModal()">لغو</button>
                <button class="customer360-modal-submit">ثبت فعالیت</button>
            </div>
        </form>
    </div>
</div>

<div id="pointsModal" class="customer360-modal">
    <div class="customer360-modal-card">
        <div class="customer360-modal-head">
            <div>
                <span>تخصیص امتیاز</span>
                <h3>مدیریت امتیاز باشگاه مشتری</h3>
            </div>
            <button type="button" onclick="closePointsModal()">×</button>
        </div>
        @if($loyaltyMember)
            <form method="post" action="{{ url('/app/loyalty/' . $loyaltyMember->id . '/adjust') }}" class="customer360-modal-body">
                @csrf
                <input type="hidden" name="kind" value="point">
                <div class="customer360-points-current">
                    <span>امتیاز فعلی</span>
                    <b>@fa($loyaltyMember->points)</b>
                </div>
                <div class="customer360-modal-grid">
                    <div>
                        <label>مقدار امتیاز</label>
                        <input name="amount" type="number" class="ltr" value="50" required>
                    </div>
                    <div>
                        <label>نوع عملیات</label>
                        <select onchange="syncPointSign(this)">
                            <option value="credit">افزایش امتیاز</option>
                            <option value="debit">کسر امتیاز</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>علت ثبت امتیاز</label>
                    <textarea name="reason" rows="3" required placeholder="مثلاً پاداش تماس موفق، جبران نارضایتی یا هدیه باشگاه">تخصیص دستی امتیاز از پرونده مشتری</textarea>
                </div>
                <div class="customer360-modal-actions">
                    <button type="button" class="customer360-modal-cancel" onclick="closePointsModal()">لغو</button>
                    <button class="customer360-modal-submit">ثبت امتیاز</button>
                </div>
            </form>
        @else
            <div class="customer360-modal-body">
                <div class="customer360-modal-empty">
                    <b>این مشتری هنوز عضو باشگاه مشتریان نیست.</b>
                    <span>برای تخصیص امتیاز، ابتدا باید این مشتری عضو باشگاه باشد یا بعد از اولین خرید، عضویت او ساخته شود.</span>
                </div>
                <div class="customer360-modal-actions">
                    <button type="button" class="customer360-modal-cancel" onclick="closePointsModal()">بستن</button>
                    <a href="{{ url('/app/loyalty') }}" class="customer360-modal-submit">رفتن به باشگاه</a>
                </div>
            </div>
        @endif
    </div>
</div>

<div id="orderModal" class="customer360-modal customer360-order-modal">
    <div class="customer360-modal-card customer360-modal-card-wide">
        <div class="customer360-modal-head">
            <div>
                <span>ایجاد سفارش جدید</span>
                <h3>انتخاب کالا و ثبت سفارش برای {{ $customer->full_name }}</h3>
            </div>
            <button type="button" onclick="closeOrderModal()">×</button>
        </div>
        <div class="customer360-order-frame-wrap">
            <iframe id="orderModalFrame" src="" title="ایجاد سفارش جدید"></iframe>
        </div>
    </div>
</div>

<style>
.customer360-quick-card {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 1.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-sm);
}

body.light .customer360-quick-card {
    background: #ffffff;
    border-color: #e5edf7;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
}

.customer360-quick-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.85rem;
    margin-bottom: 0.85rem;
    border-bottom: 1px solid var(--line);
}

.customer360-quick-head span {
    display: inline-flex;
    color: var(--acc);
    background: rgba(37, 99, 235, 0.10);
    border-radius: 999px;
    padding: 0.18rem 0.55rem;
    font-size: 0.7rem;
    font-weight: 900;
    margin-bottom: 0.35rem;
}

.customer360-quick-head h3 {
    margin: 0;
    color: var(--txt);
    font-size: 1rem;
    line-height: 1.6;
}

.customer360-quick-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    border-radius: 999px;
    padding: 0.3rem 0.7rem;
    color: var(--mut);
    background: var(--panel2);
    border: 1px solid var(--line);
    font-size: 0.75rem;
    font-weight: 900;
}

body.light .customer360-quick-count {
    background: #f8fafc;
}

.customer360-quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
    gap: 0.75rem;
}

.customer360-quick-action {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    border-radius: 1rem;
    border: 1px solid var(--line);
    background: var(--panel2);
    padding: 0.8rem;
    text-decoration: none;
    color: var(--txt);
    transition: 0.18s ease;
}

body.light .customer360-quick-action {
    background: #f8fafc;
    border-color: #e2e8f0;
}

.customer360-quick-action:hover {
    transform: translateY(-2px);
    border-color: var(--acc);
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

.customer360-quick-primary {
    background: rgba(37, 99, 235, 0.10);
    border-color: rgba(37, 99, 235, 0.22);
}

.customer360-quick-icon {
    display: inline-grid;
    place-items: center;
    width: 2.35rem;
    height: 2.35rem;
    flex: 0 0 auto;
    border-radius: 0.85rem;
    background: var(--panel);
    border: 1px solid var(--line);
    font-size: 1.15rem;
}

body.light .customer360-quick-icon {
    background: #ffffff;
}

.customer360-quick-text {
    display: grid;
    gap: 0.12rem;
    min-width: 0;
}

.customer360-quick-text b {
    color: var(--txt);
    font-size: 0.84rem;
    line-height: 1.6;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.customer360-quick-text small {
    color: var(--mut);
    font-size: 0.7rem;
    line-height: 1.6;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.customer360-quick-empty {
    grid-column: 1 / -1;
    display: grid;
    place-items: center;
    color: var(--mut);
    border: 1px dashed var(--line);
    border-radius: 1rem;
    padding: 1.5rem;
}

.customer360-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 5000;
    background: rgba(15, 23, 42, 0.56);
    backdrop-filter: blur(8px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.customer360-modal.show {
    display: flex;
}

.customer360-modal-card {
    width: min(34rem, 100%);
    max-height: 92vh;
    overflow: hidden;
    border-radius: 1.25rem;
    background: var(--panel);
    border: 1px solid var(--line);
    box-shadow: 0 26px 60px rgba(15, 23, 42, 0.30);
    animation: customer360ModalIn 0.18s ease-out;
}

body.light .customer360-modal-card {
    background: #ffffff;
    border-color: #e5edf7;
}

.customer360-modal-card-wide {
    width: min(72rem, 98vw);
}

.customer360-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--line);
    background: var(--panel2);
}

body.light .customer360-modal-head {
    background: #f8fafc;
}

.customer360-modal-head span {
    display: inline-flex;
    color: var(--acc);
    background: rgba(37, 99, 235, 0.10);
    border-radius: 999px;
    padding: 0.16rem 0.55rem;
    font-size: 0.7rem;
    font-weight: 900;
    margin-bottom: 0.35rem;
}

.customer360-modal-head h3 {
    margin: 0;
    color: var(--txt);
    font-size: 1rem;
    line-height: 1.6;
}

.customer360-modal-head button {
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 0.75rem;
    border: 1px solid var(--line);
    background: var(--panel);
    color: var(--txt);
    font-size: 1.4rem;
    cursor: pointer;
}

.customer360-modal-body {
    display: grid;
    gap: 0.9rem;
    padding: 1rem;
    max-height: calc(92vh - 5rem);
    overflow: auto;
}

.customer360-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.customer360-modal-body label {
    font-size: 0.76rem;
    font-weight: 900;
}

.customer360-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.65rem;
    flex-wrap: wrap;
    padding-top: 0.25rem;
}

.customer360-modal-submit,
.customer360-modal-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.35rem;
    border-radius: 0.8rem;
    padding: 0.45rem 1rem;
    font-family: inherit;
    font-weight: 900;
    font-size: 0.8rem;
    text-decoration: none;
    cursor: pointer;
}

.customer360-modal-submit {
    border: 0;
    background: var(--acc);
    color: #ffffff;
}

.customer360-modal-cancel {
    border: 1px solid var(--line);
    background: transparent;
    color: var(--txt);
}

.customer360-points-current,
.customer360-modal-empty {
    border-radius: 1rem;
    border: 1px solid var(--line);
    background: var(--panel2);
    padding: 0.85rem;
    display: grid;
    gap: 0.25rem;
}

body.light .customer360-points-current,
body.light .customer360-modal-empty {
    background: #f8fafc;
}

.customer360-points-current span,
.customer360-modal-empty span {
    color: var(--mut);
    font-size: 0.75rem;
    line-height: 1.8;
}

.customer360-points-current b,
.customer360-modal-empty b {
    color: var(--txt);
    font-size: 1rem;
}

.customer360-order-frame-wrap {
    height: min(76vh, 48rem);
    background: var(--bg);
}

.customer360-order-frame-wrap iframe {
    width: 100%;
    height: 100%;
    border: 0;
    display: block;
    background: var(--bg);
}

@keyframes customer360ModalIn {
    from { transform: translateY(18px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@media (max-width: 48rem) {
    .customer360-quick-card {
        border-radius: 1.05rem;
        padding: 0.85rem;
    }

    .customer360-quick-head,
    .customer360-modal-grid,
    .customer360-modal-actions {
        flex-direction: column;
        grid-template-columns: 1fr;
    }

    .customer360-quick-count,
    .customer360-modal-submit,
    .customer360-modal-cancel {
        width: 100%;
    }

    .customer360-quick-grid {
        grid-template-columns: 1fr;
    }

    .customer360-order-frame-wrap {
        height: 78vh;
    }
}
</style>

<script>
function openMessageModal() {
    const modal = document.getElementById('messageModal');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        alert('خطا: پنجره ارسال پیام پیدا نشد.');
    }
}

function openActivityModal() {
    document.getElementById('activityModal')?.classList.add('show');
}

function closeActivityModal() {
    document.getElementById('activityModal')?.classList.remove('show');
}

function openPointsModal() {
    document.getElementById('pointsModal')?.classList.add('show');
}

function closePointsModal() {
    document.getElementById('pointsModal')?.classList.remove('show');
}

function openOrderModal() {
    const modal = document.getElementById('orderModal');
    const frame = document.getElementById('orderModalFrame');
    if (frame && !frame.getAttribute('src')) {
        frame.setAttribute('src', '{{ url('/app/products?embed=1') }}');
    }
    modal?.classList.add('show');
}

function closeOrderModal() {
    document.getElementById('orderModal')?.classList.remove('show');
}

function syncPointSign(select) {
    const form = select.closest('form');
    const input = form ? form.querySelector('input[name="amount"]') : null;
    if (!input) return;
    const value = Math.abs(parseInt(input.value || '0', 10));
    input.value = select.value === 'debit' ? -value : value;
}

['activityModal', 'pointsModal', 'orderModal'].forEach(function(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
});
</script>