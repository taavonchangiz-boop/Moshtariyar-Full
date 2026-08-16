@extends('layouts.app')
@section('title', 'ویرایش محصول')
@section('heading', 'ویرایش حرفه‌ای محصول')
@section('subtitle', 'تکمیل اطلاعات، تصویرها، قیمت‌گذاری، موجودی، ویژگی‌ها و محتوای محصول')

@section('content')
<link rel="stylesheet" href="{{ asset('css/product-edit.css') }}">

@php
    $canManageProducts = auth()->user()?->hasPermission('orders.manage');
    $gallery = is_array($product->gallery) ? $product->gallery : [];
    $attributes = is_array($product->attributes) ? $product->attributes : [];
    $attributesText = collect($attributes)->map(function ($attribute) {
        $name = trim((string) ($attribute['name'] ?? ''));
        $options = $attribute['options'] ?? [];
        if (! is_array($options)) {
            $options = [$options];
        }
        $values = implode('، ', array_filter(array_map(fn ($item) => trim((string) $item), $options)));
        return $name !== '' ? $name . ': ' . $values : null;
    })->filter()->implode("\n");
    $woo = data_get($product->meta, 'woocommerce', []);
    $woo = is_array($woo) ? $woo : [];
    $metalTaxRate = old('metal_tax_rate', data_get($product->meta, 'metal_tax_rate', $product->formula_type === 'silver_jewelry' ? $defaultSilverTax : $defaultGoldTax));
    $currentImage = $product->image ? (str_starts_with($product->image, 'http') ? $product->image : asset($product->image)) : null;
    $sourceLabel = $product->source === 'woocommerce' ? 'خوانده‌شده از فروشگاه' : 'ثبت دستی';
    $wooMaps = isset($wooMaps) ? $wooMaps : collect();
    $wooConnections = isset($wooConnections) ? $wooConnections : collect();
    $wooMap = $wooMaps->first();
    $wooConnection = $wooMap ? $wooConnections->firstWhere('id', $wooMap->connection_id) : null;
    $isConnectedToWoo = (bool) $wooMap;
    $lastPushAt = data_get($product->meta, 'woocommerce_last_push_at');
    $lastPushStatus = data_get($product->meta, 'woocommerce_last_push_status');
    $lastPushError = data_get($product->meta, 'woocommerce_last_push_error');
    $wooModifiedAt = data_get($woo, 'date_modified');
    $wooCreatedAt = data_get($woo, 'date_created');
@endphp

@if(! $canManageProducts)
    <div class="product-edit-denied">
        <h3>دسترسی ویرایش ندارید</h3>
        <p>برای ویرایش محصول باید مجوز مدیریت سفارش‌ها و کالاها را داشته باشید.</p>
        <a class="btn" href="{{ url('/app/products/' . $product->id) }}">بازگشت به مشاهده محصول</a>
    </div>
@else
<div class="product-edit-page">
    <section class="product-edit-hero">
        <div>
            <span>پرونده ویرایش محصول</span>
            <h2>{{ $product->name }}</h2>
            <p>در این بخش همه اطلاعات محصول به‌صورت یکپارچه ویرایش می‌شود. تصویرها هنگام بارگذاری بهینه می‌شوند و اطلاعات دریافت‌شده از فروشگاه هم بدون حذف، در پرونده محصول باقی می‌ماند.</p>
            <div class="product-edit-tags">
                <b>{{ $sourceLabel }}</b>
                <b>{{ $product->sku ?: 'بدون کد کالا' }}</b>
                <b>{{ $product->category ?: 'بدون دسته‌بندی' }}</b>
            </div>
        </div>
        <div class="product-edit-hero-actions">
            <a class="btn btn-ghost" href="{{ url('/app/products/' . $product->id) }}">مشاهده محصول</a>
            <a class="btn btn-ghost" href="{{ url('/app/products') }}">بازگشت به بورد محصولات</a>
            @if($isConnectedToWoo)
                <form method="post" action="{{ url('/app/products/' . $product->id . '/push-woocommerce') }}" class="product-edit-hero-form">
                    @csrf
                    <button class="btn" type="submit">ارسال فوری به فروشگاه</button>
                </form>
            @endif
        </div>
    </section>

    @if($errors->any())
        <section class="product-edit-alert">
            <h3>چند مورد نیاز به اصلاح دارد</h3>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <form method="post" action="{{ url('/app/products/' . $product->id) }}" enctype="multipart/form-data" class="product-edit-form" id="productEditForm">
        @csrf
        @method('PUT')

        @if($isConnectedToWoo)
            <section class="product-edit-woo-warning">
                <div>
                    <b>این محصول به فروشگاه وصل است</b>
                    <span>اگر گزینه «ذخیره و ارسال به فروشگاه» را بزنید، اطلاعات اصلی محصول بلافاصله به فروشگاه برگردانده می‌شود.</span>
                </div>
            </section>
        @endif

        <section class="product-edit-layout">
            <aside class="product-edit-side">
                <div class="product-edit-image-card">
                    <h3>تصویر شاخص</h3>
                    <div class="product-edit-image-preview" id="mainImagePreview">
                        @if($currentImage)
                            <img src="{{ $currentImage }}" alt="{{ $product->name }}">
                        @else
                            <span>📦</span>
                        @endif
                    </div>
                    @if($product->image)
                        <label class="product-edit-check">
                            <input type="checkbox" name="remove_image" value="1">
                            حذف تصویر شاخص فعلی
                        </label>
                    @endif
                    <label class="product-edit-file">
                        <span>بارگذاری تصویر جدید</span>
                        <input type="file" name="image" accept="image/*" id="mainImageInput">
                    </label>
                    <small>تصویر جدید به‌صورت بهینه ذخیره می‌شود.</small>
                </div>

                <div class="product-edit-summary-card">
                    <h3>خلاصه سریع</h3>
                    <div><span>شناسه محصول</span><b>@fa($product->id)</b></div>
                    <div><span>قیمت فعلی</span><b>@money($product->displayPrice()) @unit</b></div>
                    <div><span>موجودی فعلی</span><b>@fa($product->stock ?? 0)</b></div>
                    <div><span>وضعیت</span><b>{{ $product->is_active ? 'فعال' : 'غیرفعال' }}</b></div>
                    @if(data_get($woo, 'id'))
                        <div><span>شناسه فروشگاه</span><b>@fa(data_get($woo, 'id'))</b></div>
                    @endif
                </div>

                <div class="product-edit-connection-card {{ $isConnectedToWoo ? 'is-connected' : 'is-manual' }}">
                    <h3>وضعیت اتصال فروشگاه</h3>
                    @if($isConnectedToWoo)
                        <div class="product-edit-connection-badge">متصل به فروشگاه</div>
                        <div class="product-edit-connection-list">
                            <div><span>فروشگاه</span><b>{{ $wooConnection?->name ?? 'فروشگاه نامشخص' }}</b></div>
                            <div><span>شناسه محصول در فروشگاه</span><b class="ltr">@fa($wooMap->woo_id)</b></div>
                            <div><span>آخرین بروزرسانی فروشگاه</span><b>{{ $wooModifiedAt ?: 'ثبت نشده' }}</b></div>
                            <div><span>آخرین ارسال از مشتری‌یار</span><b>{{ $lastPushAt ?: 'هنوز ارسالی ثبت نشده' }}</b></div>
                            <div><span>نتیجه آخرین ارسال</span><b>{{ $lastPushStatus === 'success' ? 'موفق' : ($lastPushStatus === 'failed' ? 'ناموفق' : 'ثبت نشده') }}</b></div>
                        </div>
                        @if($lastPushError)
                            <div class="product-edit-connection-error">{{ $lastPushError }}</div>
                        @endif
                    @else
                        <div class="product-edit-connection-badge">ثبت دستی</div>
                        <p>این محصول هنوز به محصولی در فروشگاه وصل نیست. اگر از ووکامرس خوانده شود، شناسه اتصال به‌صورت خودکار ثبت می‌شود.</p>
                    @endif
                </div>
            </aside>

            <main class="product-edit-main">
                <section class="product-edit-card">
                    <header>
                        <span>۱</span>
                        <div>
                            <h3>اطلاعات پایه</h3>
                            <p>نام، کد، نامک، دسته‌بندی و وضعیت نمایش محصول</p>
                        </div>
                    </header>
                    <div class="product-edit-grid">
                        <div class="product-edit-field product-edit-field-wide">
                            <label>نام محصول</label>
                            <input name="name" value="{{ old('name', $product->name) }}" required>
                        </div>
                        <div>
                            <label>کد کالا</label>
                            <input name="sku" value="{{ old('sku', $product->sku) }}" class="ltr" placeholder="مثلاً ۱۰۰۱">
                        </div>
                        <div>
                            <label>نامک</label>
                            <input name="slug" value="{{ old('slug', $product->slug) }}" class="ltr" placeholder="product-slug">
                        </div>
                        <div>
                            <label>دسته‌بندی</label>
                            <input name="category" value="{{ old('category', $product->category) }}" list="productCategories" placeholder="مثلاً پوشاک">
                            <datalist id="productCategories">
                                @foreach($categories as $category)
                                    <option value="{{ $category }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label>نوع محصول</label>
                            <select name="product_type">
                                <option value="simple" @selected(old('product_type', $product->product_type) === 'simple')>محصول ساده</option>
                                <option value="variable" @selected(old('product_type', $product->product_type) === 'variable')>محصول متغیر</option>
                                <option value="grouped" @selected(old('product_type', $product->product_type) === 'grouped')>محصول گروهی</option>
                                <option value="external" @selected(old('product_type', $product->product_type) === 'external')>محصول بیرونی</option>
                                <option value="service" @selected(old('product_type', $product->product_type) === 'service')>خدمت</option>
                                <option value="digital" @selected(old('product_type', $product->product_type) === 'digital')>محصول دیجیتال</option>
                                <option value="custom" @selected(old('product_type', $product->product_type) === 'custom')>محصول سفارشی</option>
                            </select>
                        </div>
                        <div>
                            <label>وضعیت نمایش</label>
                            <select name="status">
                                <option value="publish" @selected(old('status', $product->status) === 'publish')>منتشرشده</option>
                                <option value="draft" @selected(old('status', $product->status) === 'draft')>پیش‌نویس</option>
                                <option value="pending" @selected(old('status', $product->status) === 'pending')>در انتظار بررسی</option>
                                <option value="private" @selected(old('status', $product->status) === 'private')>خصوصی</option>
                                <option value="trash" @selected(old('status', $product->status) === 'trash')>حذف‌شده</option>
                            </select>
                        </div>
                        <div class="product-edit-field product-edit-field-wide">
                            <label>پیوند بیرونی محصول</label>
                            <input name="external_url" value="{{ old('external_url', $product->external_url) }}" class="ltr" placeholder="https://...">
                        </div>
                    </div>
                </section>

                <section class="product-edit-card">
                    <header>
                        <span>۲</span>
                        <div>
                            <h3>محتوای محصول</h3>
                            <p>توضیح کوتاه، توضیح کامل و ویژگی‌های قابل نمایش</p>
                        </div>
                    </header>
                    <div class="product-edit-grid">
                        <div class="product-edit-field product-edit-field-wide">
                            <label>توضیح کوتاه</label>
                            <textarea name="short_description" rows="4">{{ old('short_description', $product->short_description) }}</textarea>
                        </div>
                        <div class="product-edit-field product-edit-field-wide">
                            <label>توضیح کامل</label>
                            <textarea name="description" rows="8">{{ old('description', $product->description) }}</textarea>
                        </div>
                        <div class="product-edit-field product-edit-field-wide">
                            <label>ویژگی‌ها</label>
                            <textarea name="attributes" rows="6" placeholder="رنگ: قرمز، آبی&#10;سایز: کوچک، متوسط، بزرگ">{{ old('attributes', $attributesText) }}</textarea>
                            <small>هر ویژگی را در یک خط بنویسید. نام ویژگی و مقدارها را با دونقطه جدا کنید.</small>
                        </div>
                    </div>
                </section>

                <section class="product-edit-card">
                    <header>
                        <span>۳</span>
                        <div>
                            <h3>قیمت‌گذاری</h3>
                            <p>قیمت ثابت، محاسبه فرمولی یا محاسبه وابسته به نرخ روز طلا و نقره</p>
                        </div>
                    </header>
                    <div class="product-edit-grid">
                        <div>
                            <label>روش قیمت‌گذاری</label>
                            <select id="formulaTypeSelect" name="formula_type" onchange="toggleFormulaInputs()">
                                <option value="standard" @selected(old('formula_type', $product->formula_type) === 'standard')>قیمت ثابت</option>
                                <option value="gold_jewelry" @selected(old('formula_type', $product->formula_type) === 'gold_jewelry')>طلا</option>
                                <option value="silver_jewelry" @selected(old('formula_type', $product->formula_type) === 'silver_jewelry')>نقره</option>
                                <option value="standard_math" @selected(old('formula_type', $product->formula_type) === 'standard_math')>سایر کالاهای محاسباتی</option>
                            </select>
                        </div>
                    </div>

                    <div id="standardBox" class="product-edit-dynamic-box">
                        <div>
                            <label>قیمت اصلی به @unit</label>
                            <input name="regular_price" type="number" step="1" value="{{ old('regular_price', $product->regular_price) }}" class="ltr">
                        </div>
                        <div>
                            <label>قیمت ویژه به @unit</label>
                            <input name="sale_price" type="number" step="1" value="{{ old('sale_price', $product->sale_price) }}" class="ltr">
                        </div>
                        <div>
                            <label>قیمت فروش نهایی به @unit</label>
                            <input name="price" type="number" step="1" value="{{ old('price', $product->price) }}" class="ltr">
                        </div>
                        <div>
                            <label>قیمت خرید به @unit</label>
                            <input name="cost_price" type="number" step="1" value="{{ old('cost_price', $product->cost_price) }}" class="ltr">
                        </div>
                        <div>
                            <label>نرخ مالیات عمومی</label>
                            <input name="tax_rate" type="number" step="0.01" value="{{ old('tax_rate', $product->tax_rate) }}" class="ltr">
                        </div>
                    </div>

                    <div id="metalBox" class="product-edit-dynamic-box">
                        <div class="product-edit-dynamic-title">
                            <b id="metalBoxTitle">مشخصات کالای فلزی</b>
                            <span id="metalBoxHint">نرخ روز هنگام محاسبه خوانده می‌شود.</span>
                        </div>
                        <div>
                            <label id="metalWeightLabel">وزن قطعه</label>
                            <input name="live_gold_weight" type="number" step="0.001" value="{{ old('live_gold_weight', $product->live_gold_weight) }}" class="ltr">
                        </div>
                        <div>
                            <label id="metalAjratLabel">اجرت ساخت</label>
                            <input name="live_gold_ajrat" type="number" step="1" value="{{ old('live_gold_ajrat', $product->live_gold_ajrat) }}" class="ltr">
                        </div>
                        <div>
                            <label id="metalProfitLabel">سود فروشگاه</label>
                            <input id="metalProfitInput" name="live_gold_profit" type="number" step="0.01" value="{{ old('live_gold_profit', $product->live_gold_profit) }}" class="ltr">
                        </div>
                        <div>
                            <label id="metalTaxLabel">مالیات</label>
                            <input id="metalTaxInput" name="metal_tax_rate" type="number" step="0.01" value="{{ $metalTaxRate }}" class="ltr">
                        </div>
                    </div>

                    <div id="mathBox" class="product-edit-dynamic-box">
                        <div class="product-edit-dynamic-title">
                            <b>فرمول قیمت</b>
                            <span>برای محصول‌هایی که با ابعاد، تعداد یا متغیرهای اختصاصی محاسبه می‌شوند.</span>
                        </div>
                        <div class="product-edit-field-wide">
                            <label>فرمول</label>
                            <input name="formula" value="{{ old('formula', $product->formula) }}" class="ltr" placeholder="مثلاً: (طول * عرض * 180000) + 50000">
                        </div>
                        <div class="product-edit-ready-fields">
                            @foreach($customFormulaFields as $fieldName)
                                <span>{{ $fieldName }}</span>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="product-edit-card">
                    <header>
                        <span>۴</span>
                        <div>
                            <h3>انبار و وضعیت موجودی</h3>
                            <p>موجودی، هشدار کمبود، وضعیت فروش و مدیریت موجودی</p>
                        </div>
                    </header>
                    <div class="product-edit-grid">
                        <div>
                            <label>موجودی</label>
                            <input name="stock" type="number" value="{{ old('stock', $product->stock) }}" class="ltr">
                        </div>
                        <div>
                            <label>هشدار کمبود</label>
                            <input name="min_stock" type="number" value="{{ old('min_stock', $product->min_stock) }}" class="ltr">
                        </div>
                        <div>
                            <label>وضعیت موجودی</label>
                            <select name="stock_status">
                                <option value="instock" @selected(old('stock_status', $product->stock_status) === 'instock')>موجود</option>
                                <option value="outofstock" @selected(old('stock_status', $product->stock_status) === 'outofstock')>ناموجود</option>
                                <option value="onbackorder" @selected(old('stock_status', $product->stock_status) === 'onbackorder')>قابل پیش‌سفارش</option>
                            </select>
                        </div>
                        <label class="product-edit-check product-edit-check-large">
                            <input type="checkbox" name="manage_stock" value="1" @checked(old('manage_stock', $product->manage_stock))>
                            مدیریت موجودی فعال باشد
                        </label>
                    </div>
                </section>

                <section class="product-edit-card">
                    <header>
                        <span>۵</span>
                        <div>
                            <h3>گالری تصاویر</h3>
                            <p>افزودن تصویرهای جدید و حذف تصویرهای قبلی گالری</p>
                        </div>
                    </header>

                    @if($gallery)
                        <div class="product-edit-gallery-current">
                            @foreach($gallery as $image)
                                @php $gallerySrc = str_starts_with($image, 'http') ? $image : asset($image); @endphp
                                <label>
                                    <img src="{{ $gallerySrc }}" alt="{{ $product->name }}">
                                    <span>
                                        <input type="checkbox" name="remove_gallery[]" value="{{ $image }}">
                                        حذف این تصویر
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="product-edit-empty">هنوز تصویری در گالری ثبت نشده است.</div>
                    @endif

                    <label class="product-edit-file product-edit-file-wide">
                        <span>افزودن تصویرهای جدید به گالری</span>
                        <input type="file" name="gallery[]" accept="image/*" multiple>
                    </label>
                </section>

                <section class="product-edit-actions">
                    <button class="btn" type="submit" name="save_action" value="save">ذخیره تغییرات</button>
                    @if($isConnectedToWoo)
                        <button class="btn product-edit-push-submit" type="submit" name="save_action" value="save_and_push">ذخیره و ارسال به فروشگاه</button>
                    @endif
                    <a class="btn btn-ghost" href="{{ url('/app/products/' . $product->id) }}">انصراف و بازگشت</a>
                </section>
            </main>
        </section>
    </form>
</div>

<script>
const metalConfig = {
    gold_jewelry: {
        title: 'مشخصات کالای طلا',
        hint: 'نرخ روز طلا هنگام محاسبه خوانده می‌شود.',
        weightLabel: 'وزن قطعه طلا، گرم',
        ajratLabel: 'اجرت ساخت به {{ $unitLabel }}',
        profitLabel: 'سود فروشگاه',
        taxLabel: 'مالیات',
        profit: '{{ old('live_gold_profit', $product->live_gold_profit ?: $defaultGoldProfit) }}',
        tax: '{{ old('metal_tax_rate', $metalTaxRate ?: $defaultGoldTax) }}'
    },
    silver_jewelry: {
        title: 'مشخصات کالای نقره',
        hint: 'نرخ روز نقره هنگام محاسبه خوانده می‌شود.',
        weightLabel: 'وزن قطعه نقره، گرم',
        ajratLabel: 'اجرت ساخت به {{ $unitLabel }}',
        profitLabel: 'سود فروشگاه',
        taxLabel: 'مالیات',
        profit: '{{ old('live_gold_profit', $product->live_gold_profit ?: $defaultSilverProfit) }}',
        tax: '{{ old('metal_tax_rate', $metalTaxRate ?: $defaultSilverTax) }}'
    }
};

function setMetalBox(type) {
    const data = metalConfig[type];
    if (!data) return;
    document.getElementById('metalBoxTitle').textContent = data.title;
    document.getElementById('metalBoxHint').textContent = data.hint;
    document.getElementById('metalWeightLabel').textContent = data.weightLabel;
    document.getElementById('metalAjratLabel').textContent = data.ajratLabel;
    document.getElementById('metalProfitLabel').textContent = data.profitLabel;
    document.getElementById('metalTaxLabel').textContent = data.taxLabel;
    if (!document.getElementById('metalProfitInput').value) document.getElementById('metalProfitInput').value = data.profit;
    if (!document.getElementById('metalTaxInput').value) document.getElementById('metalTaxInput').value = data.tax;
}

function toggleFormulaInputs() {
    const value = document.getElementById('formulaTypeSelect').value;
    document.querySelectorAll('.product-edit-dynamic-box').forEach(box => box.style.display = 'none');

    if (value === 'gold_jewelry' || value === 'silver_jewelry') {
        document.getElementById('metalBox').style.display = 'grid';
        setMetalBox(value);
    } else if (value === 'standard_math') {
        document.getElementById('mathBox').style.display = 'grid';
    } else {
        document.getElementById('standardBox').style.display = 'grid';
    }
}

toggleFormulaInputs();

document.getElementById('mainImageInput')?.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (event) {
        document.getElementById('mainImagePreview').innerHTML = '<img src="' + event.target.result + '" alt="پیش‌نمایش تصویر">';
    };
    reader.readAsDataURL(file);
});
</script>
@endif
@endsection