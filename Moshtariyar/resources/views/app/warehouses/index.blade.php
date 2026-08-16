@extends('layouts.app')
@section('title','مرکز انبار و موجودی')
@section('heading','مرکز انبار چندگانه و تامین')
@section('subtitle','مدیریت چند انبار، رسید خرید، انتقال بین انبارها و اسقاط - الهام از بورد عملیاتی')

@section('content')
<link rel="stylesheet" href="{{ asset('css/warehouses-board.css') }}">

@php
    if (!function_exists('fa_num_wh')) {
        function fa_num_wh($n) {
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return str_replace($en, $fa, (string) $n);
        }
    }
    if (!function_exists('fa_money_wh')) {
        function fa_money_wh($n) { return fa_num_wh(number_format((int) $n)); }
    }
@endphp

<div class="warehouses-page">

    <section class="wh-hero">
        <div>
            <span class="wh-eyebrow">اتاق عملیات انبار</span>
            <h2>موجودی را نه حدس بزن، ببین و جابجا کن</h2>
            <p>هر کالا در هر انبار موجودی جدا دارد. رسید خرید موجودی را زیاد می‌کند، انتقال موجودی را جابجا می‌کند، اسقاط کم می‌کند. همه با تاریخ شمسی و ثبت دقیق مسئول.</p>
            <div class="wh-actions">
                <button class="btn" onclick="openModal('warehouseModal')">ساخت انبار جدید</button>
                <button class="btn btn-ghost" onclick="openModal('supplyModal')">ثبت رسید خرید</button>
                <button class="btn btn-ghost" onclick="openModal('transferModal')">انتقال بین انبارها</button>
                <button class="btn btn-ghost" onclick="openModal('writeOffModal')">ثبت اسقاط</button>
                <a class="btn btn-ghost" href="{{ url('/app/products') }}">بورد محصولات</a>
            </div>
        </div>
        <div class="wh-score">
            <div class="wh-score-item" style="--metric-color:#3b82f6;"><span>تعداد انبارها</span><b>{{ fa_num_wh($summary['warehouses'] ?? 0) }}</b><small>فعال</small></div>
            <div class="wh-score-item" style="--metric-color:#10b981;"><span>ارزش کل موجودی</span><b>{{ fa_money_wh($summary['stock_value'] ?? 0) }}</b><small>تومان</small></div>
            <div class="wh-score-item" style="--metric-color:#ef4444;"><span>کالای نیازمند تامین</span><b>{{ fa_num_wh($summary['low_stock'] ?? 0) }}</b><small>هشدار کمبود</small></div>
            <div class="wh-score-item" style="--metric-color:#f59e0b;"><span>موجودی کل محصولات</span><b>{{ fa_num_wh($summary['total_stock'] ?? 0) }}</b><small>عدد در انبارها</small></div>
            <div class="wh-score-item" style="--metric-color:#8b5cf6;"><span>تامین‌کنندگان</span><b>{{ fa_num_wh($summary['suppliers'] ?? 0) }}</b><small>فعال</small></div>
        </div>
    </section>

    <div class="wh-layout">
        <div class="wh-board">
            @forelse($warehouses as $wh)
                <div class="wh-column" style="--col-color:#3b82f6;">
                    <header>
                        <div>
                            <b>{{ $wh->name }}</b>
                            <small>{{ $wh->location ?: 'بدون آدرس' }} • {{ $wh->code ?: 'بدون کد' }}</small>
                        </div>
                        <div class="wh-col-badges">
                            <span class="wh-badge is-ok">{{ fa_num_wh($wh->total_items) }} قلم</span>
                            @if($wh->low_stock_count>0)
                                <span class="wh-badge is-warn">{{ fa_num_wh($wh->low_stock_count) }} هشدار</span>
                            @endif
                            @if($wh->is_default)
                                <span class="wh-badge is-primary">پیش‌فرض</span>
                            @endif
                        </div>
                    </header>

                    <div class="wh-col-summary">
                        <div><span>ارزش موجودی</span><b>{{ fa_money_wh($wh->stock_value) }} تومان</b></div>
                        <div><span>تعداد اقلام</span><b>{{ fa_num_wh($wh->total_items) }}</b></div>
                    </div>

                    <div class="wh-items">
                        @forelse($wh->products as $wp)
                            @php
                                $p = $wp->product;
                                $isLow = $wp->stock <= $wp->min_stock && $wp->min_stock>0;
                                $isEmpty = $wp->stock <= 0;
                            @endphp
                            <div class="wh-item {{ $isEmpty?'is-empty':'' }} {{ $isLow?'is-low':'' }}">
                                <div class="wh-item-main">
                                    <b>{{ $p->name ?? 'کالای حذف شده' }}</b>
                                    <small>{{ $p->sku ?? 'بدون کد' }}</small>
                                </div>
                                <div class="wh-item-stock">
                                    <span class="wh-stock-num {{ $isEmpty?'is-empty':($isLow?'is-low':'is-ok') }}">{{ fa_num_wh($wp->stock) }}</span>
                                    <small>حداقل: {{ fa_num_wh($wp->min_stock) }}</small>
                                </div>
                                <div class="wh-item-cost">
                                    <small>میانگین بهای تمام شده</small>
                                    <b>{{ fa_money_wh($wp->avg_cost) }} تومان</b>
                                </div>
                            </div>
                        @empty
                            <div class="wh-empty">هنوز کالایی در این انبار ثبت نشده</div>
                        @endforelse
                    </div>

                    <footer>
                        <form method="post" action="{{ url('/app/warehouses/'.$wh->id.'/delete') }}" onsubmit="return confirm('انبار دارای موجودی نباشد - حذف شود؟')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-small">حذف انبار</button>
                        </form>
                        <small>{{ \Modules\Core\Support\Jalali::date($wh->created_at) }}</small>
                    </footer>
                </div>
            @empty
                <div class="wh-empty-full">
                    <h3>هنوز انباری نساخته‌ای</h3>
                    <p>اولین انبار را بساز تا بتوانی موجودی را تفکیک کنی - مثلا انبار مرکزی و شعبه تهران</p>
                    <button class="btn" onclick="openModal('warehouseModal')">ساخت اولین انبار</button>
                </div>
            @endforelse

            <div class="wh-column is-add" onclick="openModal('warehouseModal')">
                <div class="wh-add-inner">+<b>ساخت انبار جدید</b><small>مثلا انبار شعبه جدید</small></div>
            </div>
        </div>

        <aside class="wh-side">
            <div class="wh-panel">
                <h4>تامین‌کنندگان فعال</h4>
                <div class="wh-suppliers">
                    @forelse($suppliers as $sp)
                        <div class="wh-supplier">
                            <b>{{ $sp->name }}</b>
                            <small>{{ $sp->phone ?: 'بدون موبایل' }}</small>
                            <small>{{ fa_money_wh($sp->total_purchases) }} تومان خرید کل</small>
                        </div>
                    @empty
                        <div class="wh-empty-small">تامین‌کننده‌ای ثبت نشده</div>
                    @endforelse
                </div>
                <button class="btn btn-ghost btn-small" onclick="openModal('supplierModal')" style="margin-top:.6rem;width:100%">افزودن تامین‌کننده</button>
            </div>

            <div class="wh-panel">
                <h4>آخرین گردش‌های انبار</h4>
                <div class="wh-movements">
                    @forelse($recentMovements as $mv)
                        <div class="wh-movement">
                            <span class="wh-mov-type {{ $mv->type }}">{{ $mv->type==='in'?'ورود':($mv->type==='out'?'خروج':'اصلاح') }}</span>
                            <b>{{ $mv->product->name ?? 'کالا' }}</b>
                            <small>{{ fa_num_wh($mv->qty) }} عدد • {{ $mv->reason ?: '—' }}</small>
                            <small>{{ \Modules\Core\Support\Jalali::datetime($mv->created_at) }}</small>
                        </div>
                    @empty
                        <div class="wh-empty-small">گردشی ثبت نشده</div>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>

{{-- مودال‌ها --}}

<div id="warehouseModal" class="wh-modal">
    <div class="wh-modal-box">
        <header><h3>ساخت انبار جدید</h3><button onclick="closeModal('warehouseModal')">×</button></header>
        <form method="post" action="{{ url('/app/warehouses') }}">
            @csrf
            <div class="wh-form-grid">
                <div class="wh-field col-span-2"><label>نام انبار *</label><input name="name" required maxlength="120" placeholder="مثلا انبار مرکزی"></div>
                <div class="wh-field"><label>کد کوتاه</label><input name="code" maxlength="40" placeholder="مثلا WH-01"></div>
                <div class="wh-field col-span-2"><label>آدرس یا موقعیت</label><input name="location" maxlength="191" placeholder="تهران - خیابان ..."></div>
                <label class="wh-check col-span-2"><input type="checkbox" name="is_default" value="1"><span>این انبار پیش‌فرض باشد</span><small>رسیدها پیش‌فرض به اینجا می‌آیند</small></label>
            </div>
            <div class="wh-form-footer"><button class="btn">ساخت انبار</button><button type="button" class="btn btn-ghost" onclick="closeModal('warehouseModal')">انصراف</button></div>
        </form>
    </div>
</div>

<div id="supplyModal" class="wh-modal">
    <div class="wh-modal-box is-large">
        <header><h3>ثبت رسید خرید از تامین‌کننده</h3><button onclick="closeModal('supplyModal')">×</button></header>
        <form method="post" action="{{ url('/app/warehouses/supplies') }}">
            @csrf
            <div class="wh-form-grid">
                <div class="wh-field"><label>انبار مقصد *</label><select name="warehouse_id" required>@foreach($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select></div>
                <div class="wh-field"><label>تامین‌کننده</label><select name="supplier_id"><option value="">بدون تامین‌کننده</option>@foreach($suppliers as $sp)<option value="{{ $sp->id }}">{{ $sp->name }}</option>@endforeach</select></div>
                <div class="wh-field"><label>تاریخ دریافت شمسی</label><input name="received_at" class="jdate" placeholder="۱۴۰۳/۰۴/۰۱"></div>
                <div class="wh-field col-span-3"><label>یادداشت</label><input name="note" maxlength="1000" placeholder="توضیح اضافه"></div>
            </div>
            <div class="wh-items-editor" id="supplyItems">
                <div class="wh-items-header"><b>اقلام رسید</b><button type="button" class="btn btn-ghost btn-small" onclick="addSupplyRow()">+ افزودن کالا</button></div>
                <div class="wh-items-rows" id="supplyRows"></div>
            </div>
            <div class="wh-form-footer"><button class="btn">ثبت رسید و افزایش موجودی</button></div>
        </form>
    </div>
</div>

<div id="transferModal" class="wh-modal">
    <div class="wh-modal-box is-large">
        <header><h3>انتقال بین انبارها</h3><button onclick="closeModal('transferModal')">×</button></header>
        <form method="post" action="{{ url('/app/warehouses/transfers') }}">
            @csrf
            <div class="wh-form-grid">
                <div class="wh-field"><label>از انبار *</label><select name="from_warehouse_id" required>@foreach($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select></div>
                <div class="wh-field"><label>به انبار *</label><select name="to_warehouse_id" required>@foreach($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select></div>
                <div class="wh-field col-span-2"><label>یادداشت</label><input name="note" placeholder="دلیل انتقال"></div>
            </div>
            <div class="wh-items-editor">
                <div class="wh-items-header"><b>اقلام انتقال</b><button type="button" class="btn btn-ghost btn-small" onclick="addTransferRow()">+ افزودن کالا</button></div>
                <div class="wh-items-rows" id="transferRows"></div>
            </div>
            <div class="wh-form-footer"><button class="btn">ثبت انتقال</button></div>
        </form>
    </div>
</div>

<div id="writeOffModal" class="wh-modal">
    <div class="wh-modal-box is-large">
        <header><h3>ثبت اسقاط کالا</h3><button onclick="closeModal('writeOffModal')">×</button></header>
        <form method="post" action="{{ url('/app/warehouses/write-offs') }}">
            @csrf
            <div class="wh-form-grid">
                <div class="wh-field"><label>انبار *</label><select name="warehouse_id" required>@foreach($warehouses as $wh)<option value="{{ $wh->id }}">{{ $wh->name }}</option>@endforeach</select></div>
                <div class="wh-field"><label>دلیل اسقاط</label><select name="reason_id"><option value="">انتخاب دلیل</option>@foreach($reasons as $r)<option value="{{ $r->id }}">{{ $r->title }}</option>@endforeach</select></div>
                <div class="wh-field col-span-2"><label>یادداشت</label><input name="note" placeholder="مثلا شکست در حمل"></div>
            </div>
            <div class="wh-items-editor">
                <div class="wh-items-header"><b>اقلام اسقاط</b><button type="button" class="btn btn-ghost btn-small" onclick="addWriteOffRow()">+ افزودن کالا</button></div>
                <div class="wh-items-rows" id="writeOffRows"></div>
            </div>
            <div class="wh-form-footer"><button class="btn btn-danger">ثبت اسقاط و کسر از موجودی</button></div>
        </form>
    </div>
</div>

<div id="supplierModal" class="wh-modal">
    <div class="wh-modal-box">
        <header><h3>افزودن تامین‌کننده</h3><button onclick="closeModal('supplierModal')">×</button></header>
        <form method="post" action="{{ url('/app/warehouses/suppliers') }}">
            @csrf
            <div class="wh-form-grid">
                <div class="wh-field col-span-2"><label>نام تامین‌کننده *</label><input name="name" required maxlength="191" placeholder="مثلا شرکت پخش ..."></div>
                <div class="wh-field"><label>موبایل</label><input name="phone" maxlength="30" placeholder="۰۹۱۲..."></div>
                <div class="wh-field"><label>ایمیل</label><input name="email" type="email" maxlength="191"></div>
                <div class="wh-field col-span-2"><label>آدرس</label><input name="address" maxlength="1000"></div>
            </div>
            <div class="wh-form-footer"><button class="btn">ذخیره تامین‌کننده</button></div>
        </form>
    </div>
</div>

<script>
function openModal(id){ document.getElementById(id)?.classList.add('show'); document.body.style.overflow='hidden'; }
function closeModal(id){ document.getElementById(id)?.classList.remove('show'); document.body.style.overflow=''; }
window.addEventListener('click', function(e){ if(e.target.classList.contains('wh-modal')){ e.target.classList.remove('show'); document.body.style.overflow=''; } });
document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ document.querySelectorAll('.wh-modal.show').forEach(m=>m.classList.remove('show')); document.body.style.overflow=''; } });

var products = @json($products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku])->values());

function rowHtml(type){
    var opts = products.map(function(p){ return '<option value="'+p.id+'">'+p.name+' ('+(p.sku||'بدون کد')+')</option>'; }).join('');
    var qtyLabel = type==='supply' ? 'تعداد ورودی' : 'تعداد';
    var extra = type==='supply' ? '<div class="wh-field"><label>بهای واحد (تومان)</label><input name="items['+Date.now()+'][unit_cost]" type="number" min="0" step="1" placeholder="۰"></div>' : '';
    return '<div class="wh-item-row">'
        + '<div class="wh-field"><label>کالا *</label><select name="items['+Date.now()+'][product_id]" required>'+opts+'</select></div>'
        + '<div class="wh-field"><label>'+qtyLabel+' *</label><input name="items['+Date.now()+'][qty]" type="number" min="1" required placeholder="۱"></div>'
        + extra
        + '<button type="button" class="btn btn-ghost btn-small" onclick="this.parentElement.remove()">حذف</button>'
        + '</div>';
}

function addSupplyRow(){ document.getElementById('supplyRows').insertAdjacentHTML('beforeend', rowHtml('supply')); }
function addTransferRow(){ document.getElementById('transferRows').insertAdjacentHTML('beforeend', rowHtml('transfer')); }
function addWriteOffRow(){ document.getElementById('writeOffRows').insertAdjacentHTML('beforeend', rowHtml('writeoff')); }

document.addEventListener('DOMContentLoaded', function(){
    addSupplyRow(); addTransferRow(); addWriteOffRow();
});
</script>

@endsection