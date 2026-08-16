@extends('layouts.app')

@section('title', 'صندوق فروش سریع')
@section('heading', '💳 صندوق فروش سریع')
@section('subtitle', 'فروش حضوری با اسکن بارکد، تخفیف، امتیاز باشگاه و چاپ فاکتور - سریع و حرفه‌ای')
@section('description', 'صندوق فروش سریع مشتری‌یار - ثبت سفارش حضوری در چند ثانیه با انتخاب انبار، مشتری، تخفیف و پرداخت')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/quick-sale-pos.css') }}">
<link rel="stylesheet" href="{{ asset('css/pos-retention-alert.css') }}">
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endpush

@section('content')
<div class="pos-page" id="posApp">

    <section class="pos-hero">
        <div>
            <span class="pos-eyebrow">صندوق فروش</span>
            <h2>فروش حضوری سریع - مثل صندوق فروشگاهی حرفه‌ای</h2>
            <p>کالا را اسکن کن یا جستجو کن، به سبد اضافه کن، مشتری را انتخاب کن، تخفیف و امتیاز اعمال کن و با یک کلیک فاکتور صادر کن. موجودی از انبار انتخابی کسر می‌شود و امتیاز باشگاه به مشتری داده می‌شود. اگر مشتری ارزشمند در معرض ریزش باشد، هشدار هوشمند نمایش داده می‌شود.</p>
        </div>
        <div class="pos-hero-tools">
            <select id="posWarehouseSelect" class="pos-warehouse-select">
                @forelse($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ $wh->is_default?'selected':'' }}>{{ $wh->name }} - موجودی: {{ $wh->total_items }} قلم</option>
                @empty
                    <option value="">انباری وجود ندارد - ابتدا از بخش انبارها انبار بسازید</option>
                @endforelse
            </select>
            <button class="btn btn-ghost" onclick="location.reload()" style="display:flex;align-items:center;justify-content:center;min-height:2.8rem">🔄 تازه‌سازی</button>
            <a class="btn btn-ghost" href="{{ url('/app/orders') }}" style="display:flex;align-items:center;justify-content:center;min-height:2.8rem">📋 سفارش‌ها</a>
            <a class="btn" href="{{ url('/app/warehouses') }}" style="display:flex;align-items:center;justify-content:center;min-height:2.8rem">📦 مدیریت انبارها</a>
        </div>
    </section>

    <div class="pos-layout">

        <div class="pos-products-panel">
            <div class="pos-search-row">
                <div class="pos-search-box">
                    <input type="text" id="posProductSearch" placeholder="جستجوی کالا - نام، کد، بارکد..." autocomplete="off">
                    <button class="pos-scan-btn" onclick="startBarcodeScan()" title="اسکن بارکد با دوربین">📷</button>
                </div>
                <div class="pos-categories" id="posCategories">
                    <button class="pos-cat-btn active" data-cat="all">همه</button>
                    @foreach($categories as $cat)
                        <button class="pos-cat-btn" data-cat="{{ $cat }}">{{ $cat }}</button>
                    @endforeach
                </div>
            </div>

            <div class="pos-products-grid" id="posProductsGrid">
                @forelse($products as $p)
                    <div class="pos-product-card" data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-price="{{ $p->price }}" data-stock="{{ $p->stock }}" data-cat="{{ $p->category ?? 'متفرقه' }}" data-sku="{{ $p->sku }}" onclick="addToCart({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->price }}, {{ $p->stock }})">
                        @if($p->image)
                            <img src="{{ $p->image }}" alt="{{ $p->name }}" class="pos-product-img" loading="lazy">
                        @else
                            <div class="pos-product-img-placeholder">📦</div>
                        @endif
                        <div class="pos-product-info">
                            <b class="pos-product-name">{{ $p->name }}</b>
                            <small class="pos-product-sku">{{ $p->sku ?: 'بدون کد' }}</small>
                            <span class="pos-product-price">@money($p->price) @unit</span>
                            <span class="pos-product-stock {{ $p->stock < 5 ? 'is-low' : 'is-ok' }}">{{ $p->stock }} عدد</span>
                        </div>
                    </div>
                @empty
                    <div class="pos-empty">محصول فعالی برای فروش سریع نیست - از انبار موجودی اضافه کنید</div>
                @endforelse
            </div>
        </div>

        <div class="pos-cart-panel">

            <div class="pos-cart-header">
                <h3>🛒 سبد خرید</h3>
                <div class="pos-cart-header-actions">
                    <span id="posItemCountBadge" class="pos-badge">۰ قلم</span>
                    <button class="btn btn-ghost btn-small" onclick="clearCart()" title="خالی کردن سبد">🗑️ پاک کردن</button>
                </div>
            </div>

            <div class="pos-block">
                <label class="pos-block-label">👤 مشتری (اختیاری) - جستجو هوشمند با تحلیل ریزش</label>
                <div class="pos-customer-search-wrap">
                    <input type="text" id="posCustomerSearch" placeholder="نام یا موبایل مشتری را بنویسید - حداقل ۲ حرف" autocomplete="off">
                    <div class="pos-customer-results" id="posCustomerResults"></div>
                </div>
                <input type="hidden" id="posCustomerId">
                <div id="posSelectedCustomer" class="pos-selected-customer hidden">
                    <div>
                        <b id="posCustomerName"></b>
                        <small id="posCustomerMeta"></small>
                    </div>
                    <button onclick="removeCustomer()" class="pos-remove-customer">×</button>
                </div>

                {{-- هشدار هوشمند نجات مشتری - صندوق --}}
                <div id="posRetentionAlert" class="pos-retention-alert">
                    {{-- با جاوااسکریپت پر می‌شود --}}
                </div>

                <div class="pos-customer-quick">
                    <input type="text" id="posQuickCustomerName" placeholder="نام مشتری جدید">
                    <input type="text" id="posQuickCustomerPhone" placeholder="موبایل جدید">
                </div>
            </div>

            <div class="pos-cart-items" id="posCartItems">
                <div class="pos-cart-empty">سبد خالی است - کالایی را از سمت راست انتخاب کنید</div>
            </div>

            <div class="pos-block">
                <div class="pos-discount-row is-main">
                    <div class="pos-field is-type">
                        <label>نوع تخفیف</label>
                        <select id="posDiscountType" class="pos-discount-type-select">
                            <option value="fixed">@unit</option>
                            <option value="percent">درصد ٪</option>
                        </select>
                    </div>
                    <div class="pos-field is-amount">
                        <label>مقدار تخفیف</label>
                        <input type="number" id="posDiscountValue" min="0" step="1" placeholder="">
                    </div>
                </div>
                <div class="pos-discount-row is-loyalty">
                    <div class="pos-field">
                        <label>امتیاز استفاده</label>
                        <input type="number" id="posUsePoints" min="0" placeholder="">
                    </div>
                    <div class="pos-field">
                        <label>کیف پول استفاده (@unit)</label>
                        <input type="number" id="posUseWallet" min="0" placeholder="">
                    </div>
                </div>
            </div>

            <div class="pos-summary">
                <div class="pos-summary-row"><span>جمع جزء:</span><b id="posSubtotal">۰ @unit</b></div>
                <div class="pos-summary-row is-discount"><span>تخفیف:</span><b id="posDiscount">۰ @unit</b></div>
                <div class="pos-summary-row is-discount"><span>کسر از امتیاز و کیف پول:</span><b id="posLoyaltyDeduction">۰ @unit</b></div>
                <div class="pos-summary-row is-total"><span>قابل پرداخت:</span><b id="posTotal">۰ @unit</b></div>
            </div>

            <div class="pos-block">
                <label class="pos-block-label">💳 روش پرداخت</label>
                <div class="pos-payment-methods">
                    <label class="pos-pay-option"><input type="radio" name="payment_method" value="cash" checked><span>نقدی</span></label>
                    <label class="pos-pay-option"><input type="radio" name="payment_method" value="card"><span>کارت بانکی</span></label>
                    <label class="pos-pay-option"><input type="radio" name="payment_method" value="transfer"><span>انتقال</span></label>
                    <label class="pos-pay-option"><input type="radio" name="payment_method" value="combined"><span>ترکیبی</span></label>
                </div>
                <textarea id="posNote" placeholder="یادداشت فاکتور (اختیاری)" rows="2"></textarea>
            </div>

            <div class="pos-actions">
                <button class="btn btn-primary pos-checkout-btn" id="posCheckoutBtn" onclick="checkout()" disabled>💰 ثبت نهایی و چاپ فاکتور</button>
                <div class="pos-actions-hint">با فشردن Enter هم می‌توانید ثبت کنید</div>
            </div>

        </div>

    </div>

    <div class="pos-modal-overlay hidden" id="posConfirmModal">
        <div class="pos-modal">
            <header><h3>تایید نهایی فروش</h3><button onclick="closePosModal()">×</button></header>
            <div class="pos-modal-body" id="posModalBody"></div>
            <footer>
                <button class="btn btn-ghost" onclick="closePosModal()">انصراف</button>
                <button class="btn btn-primary" onclick="confirmCheckout()">تایید و ثبت</button>
            </footer>
        </div>
    </div>

    <div class="pos-modal-overlay hidden" id="barcodeModal">
        <div class="pos-modal">
            <header><h3>📷 اسکن بارکد کالا</h3><button onclick="closeBarcodeModal()">×</button></header>
            <div id="barcodeReader" style="width:100%;min-height:16rem;background:#000;border-radius:.6rem;overflow:hidden"></div>
            <div style="margin-top:.8rem;display:grid;gap:.5rem">
                <small style="color:var(--mut);text-align:center;line-height:1.8">دوربین را روی بارکد نگه دارید تا به صورت خودکار خوانده شود. اگر دوربین در دسترس نیست، بارکد را دستی وارد کنید.</small>
                <input type="text" id="barcodeManualInput" placeholder="بارکد را دستی وارد کنید - مثلا 123456789" style="width:100%;min-height:2.8rem;padding:.5rem .8rem;border:1px solid var(--line);border-radius:.6rem;background:var(--panel);font-family:inherit;font-size:.9rem">
                <button class="btn" onclick="submitManualBarcode()" style="width:100%;min-height:2.8rem">🔍 جستجوی بارکد</button>
                <button class="btn btn-ghost" onclick="closeBarcodeModal()" style="width:100%">بستن دوربین</button>
            </div>
        </div>
    </div>

    <div class="pos-toast hidden" id="posToast"></div>

</div>
@endsection

@push('scripts')
<script>
let cart = [];
let selectedCustomer = null;
let lastCustomers = [];
let warehouseId = document.getElementById('posWarehouseSelect')?.value || 0;
const unitLabel = "@unit";

function numberToFa(n){
    const fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ',').replace(/\d/g, d=>fa[d]);
}
function toFaDigits(str){
  if(str===null||str===undefined) return '';
  return (''+str).replace(/\d/g, d=> '۰۱۲۳۴۵۶۷۸۹'[d]);
}

document.getElementById('posWarehouseSelect')?.addEventListener('change', function(){
    warehouseId = this.value;
    searchProducts();
});

const productSearch = document.getElementById('posProductSearch');
if(productSearch){
    productSearch.addEventListener('input', function(){
        const term = this.value.trim();
        if(term.length===0){
            document.querySelectorAll('.pos-product-card').forEach(c=>c.style.display='');
            return;
        }
        if(term.length>=2){
            searchProducts(term);
        }
        const lower = term.toLowerCase();
        document.querySelectorAll('.pos-product-card').forEach(card=>{
            const name = (card.dataset.name||'').toLowerCase();
            const sku = (card.dataset.sku||'').toLowerCase();
            const cat = (card.dataset.cat||'').toLowerCase();
            card.style.display = (name.includes(lower)||sku.includes(lower)||cat.includes(lower)) ? '' : 'none';
        });
    });
}

document.querySelectorAll('.pos-cat-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        document.querySelectorAll('.pos-cat-btn').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        const cat = this.dataset.cat;
        document.querySelectorAll('.pos-product-card').forEach(card=>{
            card.style.display = (cat==='all'||card.dataset.cat===cat) ? '' : 'none';
        });
    });
});

function searchProducts(term=''){
    const q = term || productSearch.value.trim();
    if(q.length<2 && q.length!==0) return;
    fetch(`/app/pos/search-products?q=${encodeURIComponent(q)}&warehouse_id=${warehouseId}`, {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(data=>{
        if(!q) return;
        const grid = document.getElementById('posProductsGrid');
        if(data.products && data.products.length>0 && q.length>=2){
            grid.innerHTML = data.products.map(p=>`
                <div class="pos-product-card" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-stock="${p.warehouse_stock||p.stock}" data-cat="${p.category||'متفرقه'}" data-sku="${p.sku||''}" onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price}, ${p.warehouse_stock||p.stock})">
                    <div class="pos-product-img-placeholder">📦</div>
                    <div class="pos-product-info">
                        <b class="pos-product-name">${p.name}</b>
                        <small class="pos-product-sku">${p.sku||'بدون کد'}</small>
                        <span class="pos-product-price">${numberToFa(p.price)} ${unitLabel}</span>
                        <span class="pos-product-stock ${(p.warehouse_stock||p.stock)<5?'is-low':'is-ok'}">${p.warehouse_stock||p.stock} عدد</span>
                    </div>
                </div>
            `).join('');
        }
    });
}

let customerTimer=null;
const customerSearch = document.getElementById('posCustomerSearch');
const customerResults = document.getElementById('posCustomerResults');
if(customerSearch){
    customerSearch.addEventListener('input', function(){
        clearTimeout(customerTimer);
        const term = this.value.trim();
        if(term.length<2){ customerResults.innerHTML=''; customerResults.classList.remove('visible'); return; }
        customerTimer=setTimeout(()=>searchCustomers(term),300);
    });
}
document.addEventListener('click', function(e){ if(!e.target.closest('.pos-customer-search-wrap')){ customerResults.classList.remove('visible'); } });

function searchCustomers(term){
    fetch(`/app/pos/search-customers?q=${encodeURIComponent(term)}`, {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(data=>{
        lastCustomers = data.customers || [];
        if(lastCustomers.length>0){
            customerResults.innerHTML = lastCustomers.map(c=>{
                const isValuable = c.is_valuable_at_risk;
                const isAtRisk = c.is_at_risk && !isValuable;
                const isReturning = c.is_returning;
                const rowClass = isValuable ? 'pos-customer-row has-risk is-valuable' : (isAtRisk ? 'pos-customer-row has-risk is-at-risk' : (isReturning ? 'pos-customer-row has-risk is-returning' : 'pos-customer-row'));
                const riskBadge = isValuable ? `<span class="pos-customer-risk"><span style="background:#fef3c7;color:#92400e;border-color:#fcd34d">💎 ارزشمند در خطر ${toFaDigits(c.churn_probability)}٪</span></span>` : (isAtRisk ? `<span class="pos-customer-risk"><span style="background:#fef3c7;color:#92400e;border-color:#fcd34d">⚠️ کم‌فعال ${toFaDigits(c.recency_days)} روز غیبت</span></span>` : (isReturning ? `<span class="pos-customer-risk"><span style="background:#d1fae5;color:#065f46;border-color:#6ee7b7">🎉 بازگشته پس از ${toFaDigits(c.recency_days)} روز</span></span>` : ''));
                const suggest = c.suggested_message ? `<div class="pos-customer-suggest">${c.suggested_message}</div>` : '';
                return `
                <div class="${rowClass}" onclick="selectCustomerById(${c.id})">
                    <div><b>${c.full_name}</b><small>${c.phone||''} • امتیاز: ${toFaDigits(c.points||0)} • کیف پول: ${toFaDigits(Number(c.wallet||0).toLocaleString())} ${unitLabel}</small></div>
                    ${riskBadge}
                    ${suggest}
                </div>
            `}).join('');
            customerResults.classList.add('visible');
        } else {
            customerResults.innerHTML='<div class="pos-customer-row muted">مشتری یافت نشد - می‌توانید با نام و موبایل جدید ثبت کنید</div>';
            customerResults.classList.add('visible');
        }
    });
}

function selectCustomerById(id){
    const c = lastCustomers.find(x=>x.id===id);
    if(!c){ return; }
    selectCustomerObject(c);
}

function selectCustomerObject(c){
    selectedCustomer=c;
    document.getElementById('posCustomerId').value=c.id;
    document.getElementById('posSelectedCustomer').classList.remove('hidden');
    document.getElementById('posCustomerName').textContent=c.full_name;
    document.getElementById('posCustomerMeta').textContent=(c.phone||'')+' • امتیاز: '+(c.points||0)+' • کیف پول: '+Number(c.wallet||0).toLocaleString()+' '+unitLabel+' • آخرین خرید: '+(c.last_order_fa||'---');
    customerSearch.value='';
    customerResults.innerHTML=''; customerResults.classList.remove('visible');
    showRetentionAlert(c);
    showToast('مشتری انتخاب شد: '+c.full_name,'ok');
}

function showRetentionAlert(c){
    const alertBox = document.getElementById('posRetentionAlert');
    if(!alertBox) return;

    let html = '';
    let typeClass = '';

    if(c.is_valuable_at_risk){
        typeClass = 'is-valuable';
        html = `
          <div class="pos-retention-head">
            <div class="pos-retention-icon">💎</div>
            <div class="pos-retention-title">
              <b>مشتری ارزشمند در معرض ریزش - اقدام فوری!</b>
              <small>این مشتری ${toFaDigits(c.recency_days)} روز است خرید نکرده و میانگین سبد ${toFaDigits(Math.round(c.avg_order||0).toLocaleString())} ${unitLabel} است. اگر امروز از دست برود، ارزش یک سال آینده ${toFaDigits(Number(c.predicted_clv_12m||0).toLocaleString())} ${unitLabel} از دست می‌رود.</small>
            </div>
          </div>
          <div class="pos-retention-badges">
            <span class="pos-retention-badge risk">ریسک ریزش ${toFaDigits(c.churn_probability)}٪ - ${c.churn_label}</span>
            <span class="pos-retention-badge days">غیبت ${toFaDigits(c.recency_days)} روز</span>
            <span class="pos-retention-badge clv">ارزش آینده ${toFaDigits(Number(c.predicted_clv_12m||0).toLocaleString())} ${unitLabel}</span>
          </div>
          <div class="pos-retention-body">
            <div class="pos-retention-message">💡 پیشنهاد هوشمند: با ${toFaDigits(c.suggested_discount||15)}٪ تخفیف ویژه و یادآوری امتیاز ${toFaDigits(c.points||0)} این مشتری را حفظ کنید. در صورت بازگشت، امتیاز دو برابر فعال می‌شود.</div>
            <div class="pos-retention-stats">
              <div class="pos-retention-stat"><span>تعداد خرید قبلی</span><b>${toFaDigits(c.order_count||0)} خرید</b></div>
              <div class="pos-retention-stat"><span>میانگین سبد</span><b>${toFaDigits(Number(c.avg_order||0).toLocaleString())} ${unitLabel}</b></div>
              <div class="pos-retention-stat"><span>آخرین خرید</span><b>${c.last_order_fa||'---'}</b></div>
            </div>
          </div>
          <div class="pos-retention-actions">
            <button class="btn" onclick="applySuggestedDiscount(${c.suggested_discount||15})">🎁 اعمال ${toFaDigits(c.suggested_discount||15)}٪ تخفیف ویژه</button>
            <button class="btn btn-ghost" onclick="goToRetention()">📊 پرونده نجات</button>
          </div>
        `;
    } else if(c.is_at_risk){
        typeClass = 'is-at-risk';
        html = `
          <div class="pos-retention-head">
            <div class="pos-retention-icon">🔄</div>
            <div class="pos-retention-title">
              <b>مشتری کم‌فعال در آستانه ریزش - نیازمند توجه</b>
              <small>${toFaDigits(c.recency_days)} روز بدون خرید - بخش: ${c.segment_label||'کم‌فعال'} - ریسک ${toFaDigits(c.churn_probability)}٪</small>
            </div>
          </div>
          <div class="pos-retention-badges">
            <span class="pos-retention-badge risk">${c.churn_label} ${toFaDigits(c.churn_probability)}٪</span>
            <span class="pos-retention-badge days">${toFaDigits(c.recency_days)} روز غیبت</span>
          </div>
          <div class="pos-retention-body">
            <div class="pos-retention-message">💡 پیشنهاد هوشمند: ${c.suggested_message||'با یک پیشنهاد کوچک ۱۰٪ و تشکر بابت همراهی، حس بازگشت را تقویت کنید.'}</div>
          </div>
          <div class="pos-retention-actions">
            <button class="btn" onclick="applySuggestedDiscount(${c.suggested_discount||10})">🎁 اعمال ${toFaDigits(c.suggested_discount||10)}٪ تخفیف بازگشت</button>
            <button class="btn btn-ghost" onclick="clearRetentionAlert()">بستن</button>
          </div>
        `;
    } else if(c.is_returning){
        typeClass = 'is-returning';
        html = `
          <div class="pos-retention-head">
            <div class="pos-retention-icon">🎉</div>
            <div class="pos-retention-title">
              <b>بازگشت مشتری پس از ${toFaDigits(c.recency_days)} روز - تبریک بگویید!</b>
              <small>این مشتری بعد از غیبت طولانی برگشته. امتیاز دو برابر به صورت خودکار برای این خرید فعال می‌شود.</small>
            </div>
          </div>
          <div class="pos-retention-badges">
            <span class="pos-retention-badge clv">🎉 بازگشته - امتیاز دو برابر</span>
            <span class="pos-retention-badge days">آخرین خرید ${c.last_order_fa||'---'}</span>
          </div>
          <div class="pos-retention-body">
            <div class="pos-retention-message">✨ این خرید به صورت خودکار امتیاز دو برابر می‌گیرد. به مشتری بگویید: «به خاطر بازگشتتون، امتیاز این خرید دو برابر شد!»</div>
          </div>
          <div class="pos-retention-actions">
            <button class="btn" onclick="clearRetentionAlert()">✅ متوجه شدم</button>
            <button class="btn btn-ghost" onclick="goToCustomer(${c.id})">👁️ پرونده مشتری</button>
          </div>
        `;
    } else {
        // مشتری عادی یا قهرمان
        if((c.churn_probability||0) < 20 && (c.order_count||0) > 3){
            typeClass = 'is-champion';
            html = `
              <div class="pos-retention-head">
                <div class="pos-retention-icon">🏆</div>
                <div class="pos-retention-title">
                  <b>قهرمان وفادار - قدردانی کنید</b>
                  <small>مشتری وفادار با ${toFaDigits(c.order_count)} خرید و ارزش ${toFaDigits(Number(c.predicted_clv_12m||0).toLocaleString())} ${unitLabel} در سال آینده</small>
                </div>
              </div>
              <div class="pos-retention-badges">
                <span class="pos-retention-badge clv">🏆 وفادار - ریسک ${toFaDigits(c.churn_probability)}٪</span>
              </div>
            `;
        } else {
            alertBox.className = 'pos-retention-alert';
            alertBox.classList.remove('show');
            alertBox.innerHTML = '';
            return;
        }
    }

    alertBox.className = 'pos-retention-alert show ' + typeClass;
    alertBox.innerHTML = html;
}

function clearRetentionAlert(){
    const alertBox = document.getElementById('posRetentionAlert');
    if(alertBox){ alertBox.classList.remove('show'); alertBox.className='pos-retention-alert'; alertBox.innerHTML=''; }
}
function goToRetention(){ window.open('/app/retention', '_blank'); }
function goToCustomer(id){ window.open('/app/customers/'+id, '_blank'); }

function applySuggestedDiscount(percent){
    document.getElementById('posDiscountType').value='percent';
    document.getElementById('posDiscountValue').value=percent;
    calcTotals();
    showToast(`✅ تخفیف ${toFaDigits(percent)}٪ ویژه نجات اعمال شد`,'ok');
}

// سازگاری با تابع قدیمی
function selectCustomer(id,name,phone,points,wallet){
    const found = lastCustomers.find(x=>x.id===id);
    if(found){ selectCustomerObject(found); return; }
    selectedCustomer={id,name,phone,points,wallet, churn_probability:0, recency_days:0};
    document.getElementById('posCustomerId').value=id;
    document.getElementById('posSelectedCustomer').classList.remove('hidden');
    document.getElementById('posCustomerName').textContent=name;
    document.getElementById('posCustomerMeta').textContent=(phone||'')+' • امتیاز: '+(points||0);
    customerSearch.value=''; customerResults.innerHTML=''; customerResults.classList.remove('visible');
    clearRetentionAlert();
}

function removeCustomer(){
    selectedCustomer=null;
    document.getElementById('posCustomerId').value='';
    document.getElementById('posSelectedCustomer').classList.add('hidden');
    clearRetentionAlert();
}

function addToCart(id,name,price,stock){
    const found = cart.find(i=>i.id===id);
    if(found){
        if(found.qty>=stock){ showToast('موجودی کافی نیست - حداکثر '+stock+' عدد','warn'); return; }
        found.qty++;
    } else {
        cart.push({id,name,price,qty:1,stock});
    }
    updateCart();
    showToast('به سبد اضافه شد: '+name,'ok');
}

function updateCart(){
    const container = document.getElementById('posCartItems');
    const countBadge = document.getElementById('posItemCountBadge');
    const checkoutBtn = document.getElementById('posCheckoutBtn');
    if(cart.length===0){
        container.innerHTML='<div class="pos-cart-empty">سبد خالی است - کالایی را از سمت راست انتخاب کنید</div>';
        countBadge.textContent='۰ قلم';
        checkoutBtn.disabled=true;
        document.getElementById('posSubtotal').textContent='۰ '+unitLabel;
        document.getElementById('posDiscount').textContent='۰ '+unitLabel;
        document.getElementById('posLoyaltyDeduction').textContent='۰ '+unitLabel;
        document.getElementById('posTotal').textContent='۰ '+unitLabel;
        return;
    }
    container.innerHTML = cart.map((item,idx)=>`
        <div class="pos-cart-item">
            <div class="pos-cart-item-main">
                <b>${item.name}</b>
                <small>${numberToFa(item.price)} ${unitLabel} × ${item.qty}</small>
            </div>
            <div class="pos-cart-item-qty">
                <button onclick="changeQty(${idx}, -1)">-</button>
                <span>${item.qty}</span>
                <button onclick="changeQty(${idx}, 1)">+</button>
            </div>
            <div class="pos-cart-item-total">${numberToFa(item.price*item.qty)} ${unitLabel}</div>
            <button class="pos-cart-item-remove" onclick="removeFromCart(${idx})">×</button>
        </div>
    `).join('');
    const totalQty = cart.reduce((s,i)=>s+i.qty,0);
    countBadge.textContent=totalQty+' قلم';
    checkoutBtn.disabled=false;
    calcTotals();
}

function changeQty(idx, delta){
    const item = cart[idx];
    if(!item) return;
    const newQty = item.qty + delta;
    if(newQty<=0){ cart.splice(idx,1); }
    else {
        if(newQty>item.stock){ showToast('موجودی کافی نیست','warn'); return; }
        item.qty=newQty;
    }
    updateCart();
}

function removeFromCart(idx){ cart.splice(idx,1); updateCart(); }
function clearCart(){ cart=[]; updateCart(); showToast('سبد خالی شد','warn'); }

function calcTotals(){
    let subtotal=0;
    cart.forEach(i=>subtotal+=i.price*i.qty);
    const discountVal = parseFloat(document.getElementById('posDiscountValue').value)||0;
    const discountType = document.getElementById('posDiscountType').value;
    let discount=0;
    if(discountVal>0){
        discount = discountType==='percent' ? subtotal*discountVal/100 : discountVal;
        discount = Math.min(discount, subtotal);
    }
    const points = parseInt(document.getElementById('posUsePoints').value)||0;
    const wallet = parseFloat(document.getElementById('posUseWallet').value)||0;
    const loyaltyDeduction = points + wallet;
    const total = Math.max(0, subtotal - discount - loyaltyDeduction);

    document.getElementById('posSubtotal').textContent=numberToFa(subtotal)+' '+unitLabel;
    document.getElementById('posDiscount').textContent=numberToFa(discount)+' '+unitLabel;
    document.getElementById('posLoyaltyDeduction').textContent=numberToFa(loyaltyDeduction)+' '+unitLabel;
    document.getElementById('posTotal').textContent=numberToFa(total)+' '+unitLabel;
}

['posDiscountValue','posDiscountType','posUsePoints','posUseWallet'].forEach(id=>{
    document.getElementById(id)?.addEventListener('input', calcTotals);
});

function showToast(msg,type='ok'){
    const toast=document.getElementById('posToast');
    toast.textContent=msg;
    toast.className='pos-toast show '+type;
    setTimeout(()=>{ toast.classList.remove('show'); }, 2500);
}

function checkout(){
    if(cart.length===0){ showToast('سبد خالی است','warn'); return; }
    const modalBody = document.getElementById('posModalBody');
    modalBody.innerHTML=`
        <div class="pos-confirm-summary">
            <div><span>تعداد اقلام:</span><b>${cart.length} کالا</b></div>
            <div><span>جمع جزء:</span><b>${document.getElementById('posSubtotal').textContent}</b></div>
            <div><span>تخفیف:</span><b>${document.getElementById('posDiscount').textContent}</b></div>
            <div><span>قابل پرداخت:</span><b>${document.getElementById('posTotal').textContent}</b></div>
            <div><span>انبار:</span><b>${document.getElementById('posWarehouseSelect').selectedOptions[0].text}</b></div>
            <div><span>مشتری:</span><b>${selectedCustomer?selectedCustomer.full_name: (document.getElementById('posQuickCustomerName').value||'بدون نام')}</b></div>
        </div>
        <div class="pos-confirm-items">
            ${cart.map(i=>`<div><span>${i.name} × ${i.qty}</span><span>${numberToFa(i.price*i.qty)} ${unitLabel}</span></div>`).join('')}
        </div>
    `;
    document.getElementById('posConfirmModal').classList.remove('hidden');
    document.getElementById('posConfirmModal').classList.add('show');
}

function closePosModal(){ document.getElementById('posConfirmModal').classList.add('hidden'); document.getElementById('posConfirmModal').classList.remove('show'); }

function confirmCheckout(){
    const btn = document.querySelector('#posConfirmModal .btn-primary');
    btn.disabled=true; btn.textContent='در حال ثبت...';
    const payload = {
        warehouse_id: parseInt(document.getElementById('posWarehouseSelect').value),
        customer_id: document.getElementById('posCustomerId').value ? parseInt(document.getElementById('posCustomerId').value) : null,
        customer_name: document.getElementById('posQuickCustomerName').value || (selectedCustomer?selectedCustomer.full_name:null),
        customer_phone: document.getElementById('posQuickCustomerPhone').value || (selectedCustomer?selectedCustomer.phone:null),
        items: cart.map(i=>({id:i.id, qty:i.qty, price:i.price})),
        discount_type: document.getElementById('posDiscountType').value,
        discount_value: parseFloat(document.getElementById('posDiscountValue').value)||0,
        use_points: parseInt(document.getElementById('posUsePoints').value)||0,
        use_wallet: parseFloat(document.getElementById('posUseWallet').value)||0,
        payment_method: document.querySelector('input[name="payment_method"]:checked')?.value||'cash',
        note: document.getElementById('posNote').value||null,
        _token: document.querySelector('meta[name="csrf-token"]').content
    };

    fetch('/app/pos/checkout', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':payload._token,'Accept':'application/json'}, body:JSON.stringify(payload)})
    .then(r=>r.json()).then(data=>{
        if(data.ok){
            showToast('✅ سفارش ثبت شد: '+data.order_number,'ok');
            closePosModal();
            cart=[]; updateCart(); removeCustomer();
            setTimeout(()=>{ window.open(data.url, '_blank'); }, 500);
        } else {
            showToast('خطا: '+(data.message||'خطای نامشخص'),'error');
        }
    }).catch(e=>{ showToast('خطا در ارتباط با سرور','error'); })
    .finally(()=>{ btn.disabled=false; btn.textContent='تایید و ثبت'; });
}

let html5QrcodeScanner = null;

function startBarcodeScan(){
    const modal = document.getElementById('barcodeModal');
    modal.classList.remove('hidden');
    modal.classList.add('show');
    document.getElementById('barcodeManualInput').value='';
    
    if(typeof Html5Qrcode === 'undefined'){
        showToast('کتابخانه بارکدخوان لود نشده - بارکد را دستی وارد کنید','warn');
        return;
    }
    
    if(!html5QrcodeScanner){
        html5QrcodeScanner = new Html5Qrcode("barcodeReader");
    }
    
    html5QrcodeScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: {width:250, height:250} },
        (decodedText)=>{
            document.getElementById('posProductSearch').value = decodedText;
            document.getElementById('barcodeManualInput').value = decodedText;
            searchProducts(decodedText);
            showToast('بارکد خوانده شد: '+decodedText,'ok');
            setTimeout(()=>closeBarcodeModal(), 800);
        },
        (error)=>{}
    ).catch(err=>{
        showToast('دوربین در دسترس نیست - بارکد را دستی وارد کنید','warn');
    });
}

function closeBarcodeModal(){
    const modal = document.getElementById('barcodeModal');
    modal.classList.add('hidden');
    modal.classList.remove('show');
    if(html5QrcodeScanner){
        html5QrcodeScanner.stop().then(()=>{}).catch(()=>{});
    }
}

function submitManualBarcode(){
    const val = document.getElementById('barcodeManualInput').value.trim();
    if(val){
        document.getElementById('posProductSearch').value = val;
        searchProducts(val);
        showToast('جستجوی بارکد: '+val,'ok');
        closeBarcodeModal();
    } else {
        showToast('لطفا بارکد را وارد کنید','warn');
    }
}

document.addEventListener('keydown', function(e){
    if(e.key==='Enter' && !e.target.matches('textarea')){
        if(cart.length>0 && !document.getElementById('posConfirmModal').classList.contains('show') && !document.getElementById('barcodeModal').classList.contains('show')){
            checkout();
        }
    }
    if(e.key==='Escape'){
        closeBarcodeModal();
    }
});
</script>
@endpush
