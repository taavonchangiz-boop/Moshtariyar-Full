@extends('layouts.app')
@section('title', $product->name)
@section('heading', $product->name)
@section('subtitle', 'پرونده کامل محصول، تصویرها، گالری، محتوای فروشگاه، محاسبه قیمت و ثبت سفارش سریع')

@section('content')
<link rel="stylesheet" href="{{ asset('css/product-detail.css') }}">

@php
    $isMetal = in_array($product->formula_type, ['gold_jewelry', 'silver_jewelry'], true);
    $isSilver = $product->formula_type === 'silver_jewelry';
    $isFormula = $product->is_formula_based && !empty($product->formula) && !$isMetal;
    $metalName = $isSilver ? 'نقره' : 'طلا';
    $liveRate = $isSilver ? $liveSilverPrice : $liveGoldPrice;
    $metalTaxRate = (float) data_get($product->meta, 'metal_tax_rate', 0.10);
    $fixedUnitPrice = (float) $product->displayPrice();
    $canManageProducts = auth()->user()?->hasPermission('orders.manage');
    $editProductUrl = url('/app/products/' . $product->id . '/edit');
    $pushProductUrl = url('/app/products/' . $product->id . '/push-woocommerce');
    $quickCustomerId = request('quick_customer_id');
    $quickCustomer = $quickCustomerId ? $customers->firstWhere('id', (int) $quickCustomerId) : null;
    $woo = data_get($product->meta, 'woocommerce', []);
    $woo = is_array($woo) ? $woo : [];
    $wooRaw = data_get($woo, 'raw', []);
    $wooRaw = is_array($wooRaw) ? $wooRaw : [];

    $productTypeLabels = [
        'simple' => 'محصول ساده',
        'variable' => 'محصول متغیر',
        'grouped' => 'محصول گروهی',
        'external' => 'محصول بیرونی',
        'service' => 'خدمت',
        'digital' => 'محصول دیجیتال',
        'custom' => 'محصول سفارشی',
    ];
    $statusLabels = [
        'publish' => 'منتشرشده',
        'draft' => 'پیش‌نویس',
        'pending' => 'در انتظار بررسی',
        'private' => 'خصوصی',
        'trash' => 'حذف‌شده',
    ];
    $stockLabels = [
        'instock' => 'موجود',
        'outofstock' => 'ناموجود',
        'onbackorder' => 'قابل پیش‌سفارش',
    ];
    $visibilityLabels = [
        'visible' => 'نمایش در فروشگاه و جست‌وجو',
        'catalog' => 'فقط فهرست فروشگاه',
        'search' => 'فقط جست‌وجو',
        'hidden' => 'مخفی',
    ];
    $taxStatusLabels = [
        'taxable' => 'مشمول مالیات',
        'shipping' => 'فقط ارسال',
        'none' => 'بدون مالیات',
    ];
    $backorderLabels = [
        'no' => 'غیرفعال',
        'notify' => 'فعال با اطلاع‌رسانی',
        'yes' => 'فعال',
    ];

    $yesNo = fn ($value) => is_null($value) ? 'ثبت نشده' : ($value ? 'بله' : 'خیر');
    $safeText = fn ($value, $fallback = 'ثبت نشده') => trim((string) $value) !== '' ? trim((string) $value) : $fallback;
    $plainText = function ($value, $fallback = 'ثبت نشده') {
        $text = trim(strip_tags((string) $value));
        return $text !== '' ? $text : $fallback;
    };
    $formatWooDate = function ($value) {
        if (empty($value)) {
            return 'ثبت نشده';
        }
        try {
            return \Modules\Core\Support\Jalali::datetime(\Carbon\Carbon::parse($value));
        } catch (\Throwable $e) {
            return 'ثبت نشده';
        }
    };
    $numberText = fn ($value) => is_numeric($value) ? \Modules\Core\Support\Num::fa(number_format((float) $value, ((float) $value == (int) $value ? 0 : 2))) : $safeText($value);
    $listIds = fn ($items) => is_array($items) && count($items) ? \Modules\Core\Support\Num::fa(implode('، ', array_map('strval', $items))) : 'ثبت نشده';

    $normalizeImageUrl = function ($image) {
        if (is_array($image)) {
            $image = $image['src'] ?? $image['url'] ?? '';
        }
        $image = trim((string) $image);
        if ($image === '') {
            return null;
        }
        return str_starts_with($image, 'http') ? $image : asset($image);
    };

    $wooImageItems = data_get($woo, 'images.items', []);
    $wooImageItems = is_array($wooImageItems) ? $wooImageItems : [];
    $galleryItems = [];

    if ($wooImageItems) {
        foreach ($wooImageItems as $index => $imageItem) {
            if (! is_array($imageItem)) {
                continue;
            }
            $src = $normalizeImageUrl($imageItem['src'] ?? null);
            if (! $src) {
                continue;
            }
            $galleryItems[] = [
                'src' => $src,
                'alt' => $safeText($imageItem['alt'] ?? '', $product->name),
                'name' => $safeText($imageItem['name'] ?? '', $index === 0 ? 'تصویر شاخص' : 'تصویر گالری'),
                'is_main' => $index === 0,
            ];
        }
    }

    if (! $galleryItems) {
        $mainImage = $normalizeImageUrl($product->image);
        if ($mainImage) {
            $galleryItems[] = [
                'src' => $mainImage,
                'alt' => $product->name,
                'name' => 'تصویر شاخص',
                'is_main' => true,
            ];
        }

        $storedGallery = is_array($product->gallery) ? $product->gallery : [];
        foreach ($storedGallery as $image) {
            $src = $normalizeImageUrl($image);
            if (! $src) {
                continue;
            }
            $galleryItems[] = [
                'src' => $src,
                'alt' => $product->name,
                'name' => 'تصویر گالری',
                'is_main' => false,
            ];
        }
    }

    $heroImage = $galleryItems[0]['src'] ?? $normalizeImageUrl($product->image);
    $attributes = data_get($woo, 'attributes', []);
    $attributes = is_array($attributes) && count($attributes) ? $attributes : (is_array($product->attributes) ? $product->attributes : []);
    $categories = data_get($woo, 'categories', []);
    $categories = is_array($categories) ? $categories : [];
    if (! $categories && $product->category) {
        $categories = [['name' => $product->category]];
    }
    $tags = data_get($woo, 'tags', []);
    $tags = is_array($tags) ? $tags : [];
    $defaultAttributes = data_get($woo, 'default_attributes', []);
    $defaultAttributes = is_array($defaultAttributes) ? $defaultAttributes : [];
    $metaRows = data_get($woo, 'meta_data', []);
    $metaRows = is_array($metaRows) ? $metaRows : [];
    $downloads = data_get($woo, 'downloads', []);
    $downloads = is_array($downloads) ? $downloads : [];
    $variations = data_get($woo, 'variations', []);
    $variations = is_array($variations) ? $variations : [];
    $groupedProducts = data_get($woo, 'grouped_products', []);
    $groupedProducts = is_array($groupedProducts) ? $groupedProducts : [];

    $descriptionHtml = $product->description ?: data_get($woo, 'description');
    $shortDescriptionHtml = $product->short_description ?: data_get($woo, 'short_description');
    $rawWooJson = $wooRaw ? json_encode($wooRaw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

    $renderValue = function ($value) use (&$renderValue) {
        if (is_bool($value)) {
            return $value ? 'بله' : 'خیر';
        }
        if (is_null($value) || $value === '') {
            return 'ثبت نشده';
        }
        if (is_array($value)) {
            if (! count($value)) {
                return 'ثبت نشده';
            }
            $parts = [];
            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    $parts[] = is_string($key) ? $key . ': ' . $renderValue($item) : $renderValue($item);
                } else {
                    $parts[] = is_string($key) ? $key . ': ' . $renderValue($item) : $renderValue($item);
                }
            }
            return implode('، ', array_filter($parts));
        }
        return strip_tags((string) $value);
    };
@endphp

<div class="product-detail-page">
    <section class="product-detail-top-actions">
        <div>
            <span>اقدام سریع محصول</span>
            <b>برای اصلاح اطلاعات، تصویرها، قیمت و موجودی همین محصول</b>
        </div>
        <div class="product-detail-action-links">
            <a class="product-edit-primary-button" href="{{ $editProductUrl }}">ویرایش محصول</a>
            <form method="post" action="{{ $pushProductUrl }}" class="product-inline-form">
                @csrf
                <button type="submit" class="btn">ارسال به فروشگاه</button>
            </form>
        </div>
    </section>

    <section class="product-detail-hero product-detail-hero-full">
        <div class="product-detail-image-box product-detail-image-box-large">
            @if($heroImage)
                <img src="{{ $heroImage }}" alt="{{ $product->name }}">
            @else
                <div class="product-detail-image-empty">📦</div>
            @endif
        </div>

        <div class="product-detail-main">
            <div class="product-detail-kicker">پرونده کامل محصول</div>
            <h2>{{ $product->name }}</h2>
            <div class="product-detail-short-content">
                {!! $shortDescriptionHtml ?: '<span class="muted">توضیح کوتاهی برای این محصول ثبت نشده است.</span>' !!}
            </div>
            <div class="product-detail-tags">
                <span>{{ $productTypeLabels[$product->product_type ?? 'simple'] ?? $safeText($product->product_type, 'محصول') }}</span>
                <span>{{ $categories ? implode('، ', array_map(fn ($row) => $row['name'] ?? '', $categories)) : 'بدون دسته‌بندی' }}</span>
                <span>{{ $product->source === 'woocommerce' ? 'خوانده‌شده از فروشگاه' : 'ثبت دستی' }}</span>
                <span>{{ $stockLabels[$product->stock_status ?? 'instock'] ?? 'وضعیت نامشخص' }}</span>
                @if(data_get($woo, 'featured'))
                    <span>محصول شاخص فروشگاه</span>
                @endif
                @if(data_get($woo, 'on_sale'))
                    <span>دارای فروش ویژه</span>
                @endif
            </div>
        </div>

        <div class="product-detail-price-card">
            <span>مبلغ فعلی</span>
            @if($isMetal)
                <b>وابسته به نرخ روز</b>
                <small>نرخ روز هر گرم {{ $metalName }}: @money($liveRate) @unit</small>
            @elseif($isFormula)
                <b>فرمولی</b>
                <small>{{ $product->formula }}</small>
            @else
                <b>@money($fixedUnitPrice) @unit</b>
                @if($product->sale_price)
                    <small>قیمت اصلی: @money($product->regular_price) @unit</small>
                @endif
                @if(data_get($woo, 'price_html'))
                    <small>{!! data_get($woo, 'price_html') !!}</small>
                @endif
            @endif
            <div class="product-detail-action-links">
                <a class="product-external-link" href="{{ $editProductUrl }}">ویرایش محصول</a>
                <form method="post" action="{{ $pushProductUrl }}" class="product-inline-form">
                    @csrf
                    <button type="submit" class="product-external-link product-link-button">ارسال به فروشگاه</button>
                </form>
                @if($product->external_url)
                    <a class="product-external-link" href="{{ $product->external_url }}" target="_blank" rel="noopener">مشاهده در فروشگاه</a>
                @endif
            </div>
        </div>
    </section>

    <section class="product-detail-metrics product-detail-metrics-expanded">
        <div>
            <span>شناسه فروشگاه</span>
            <b>{{ data_get($woo, 'id') ? \Modules\Core\Support\Num::fa(data_get($woo, 'id')) : 'ثبت نشده' }}</b>
        </div>
        <div>
            <span>موجودی</span>
            <b>@fa($product->stock ?? 0)</b>
        </div>
        <div>
            <span>فروش ثبت‌شده در فروشگاه</span>
            <b>{{ data_get($woo, 'total_sales') !== null ? \Modules\Core\Support\Num::fa(number_format((float) data_get($woo, 'total_sales'))) : 'ثبت نشده' }}</b>
        </div>
        <div>
            <span>میانگین امتیاز</span>
            <b>{{ data_get($woo, 'average_rating') !== null ? \Modules\Core\Support\Num::fa(data_get($woo, 'average_rating')) : 'ثبت نشده' }}</b>
        </div>
        <div>
            <span>دیدگاه‌ها</span>
            <b>{{ data_get($woo, 'rating_count') !== null ? \Modules\Core\Support\Num::fa(number_format((float) data_get($woo, 'rating_count'))) : 'ثبت نشده' }}</b>
        </div>
        <div>
            <span>مدیریت موجودی</span>
            <b>{{ $product->manage_stock ? 'فعال' : 'غیرفعال' }}</b>
        </div>
    </section>

    @if($galleryItems)
        <section class="product-gallery-card product-gallery-card-rich">
            <header class="product-section-heading">
                <div>
                    <span>تصویر شاخص و گالری</span>
                    <h3>همه تصویرهای خوانده‌شده از فروشگاه</h3>
                </div>
                <b>{{ \Modules\Core\Support\Num::fa(count($galleryItems)) }} تصویر</b>
            </header>
            <div class="product-gallery-grid product-gallery-grid-rich">
                @foreach($galleryItems as $image)
                    <figure>
                        <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}">
                        <figcaption>
                            <b>{{ $image['name'] }}</b>
                            <span>{{ $image['is_main'] ? 'تصویر شاخص' : 'تصویر گالری' }}</span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @else
        <section class="product-gallery-card">
            <h3>تصویر شاخص و گالری</h3>
            <div class="product-empty-state">برای این محصول تصویری از فروشگاه دریافت نشده است.</div>
        </section>
    @endif

    <section class="product-info-grid product-info-grid-wide">
        <div class="product-info-card">
            <h3>مشخصات اصلی محصول</h3>
            <div class="product-info-list product-info-list-dense">
                <div><span>نامک</span><b class="ltr">{{ $product->slug ?? data_get($woo, 'slug') ?? 'ثبت نشده' }}</b></div>
                <div><span>کد کالا</span><b class="ltr">{{ $product->sku ?? 'بدون کد' }}</b></div>
                <div><span>نوع محصول</span><b>{{ $productTypeLabels[$product->product_type ?? 'simple'] ?? $safeText($product->product_type, 'محصول') }}</b></div>
                <div><span>وضعیت انتشار</span><b>{{ $statusLabels[$product->status ?? 'publish'] ?? $safeText($product->status) }}</b></div>
                <div><span>وضعیت نمایش</span><b>{{ $visibilityLabels[data_get($woo, 'catalog_visibility')] ?? $safeText(data_get($woo, 'catalog_visibility')) }}</b></div>
                <div><span>محصول شاخص</span><b>{{ $yesNo(data_get($woo, 'featured')) }}</b></div>
                <div><span>قابل خرید</span><b>{{ $yesNo(data_get($woo, 'purchasable')) }}</b></div>
                <div><span>فروش ویژه</span><b>{{ $yesNo(data_get($woo, 'on_sale')) }}</b></div>
                <div><span>مجازی</span><b>{{ $yesNo(data_get($woo, 'virtual')) }}</b></div>
                <div><span>دانلودی</span><b>{{ $yesNo(data_get($woo, 'downloadable')) }}</b></div>
                <div><span>یادداشت خرید</span><b>{{ $safeText(data_get($woo, 'purchase_note')) }}</b></div>
                <div><span>ترتیب نمایش</span><b>{{ data_get($woo, 'menu_order') !== null ? \Modules\Core\Support\Num::fa(data_get($woo, 'menu_order')) : 'ثبت نشده' }}</b></div>
            </div>
        </div>

        <div class="product-info-card">
            <h3>قیمت، مالیات و موجودی</h3>
            <div class="product-info-list product-info-list-dense">
                <div><span>قیمت فروش</span><b>@money($product->price ?? 0) @unit</b></div>
                <div><span>قیمت اصلی</span><b>@money($product->regular_price ?? 0) @unit</b></div>
                <div><span>قیمت ویژه</span><b>{{ $product->sale_price ? '' : 'ثبت نشده' }}@if($product->sale_price) @money($product->sale_price) @unit @endif</b></div>
                <div><span>قیمت خرید</span><b>@money($product->cost_price ?? 0) @unit</b></div>
                <div><span>وضعیت مالیات</span><b>{{ $taxStatusLabels[data_get($woo, 'tax_status')] ?? $safeText(data_get($woo, 'tax_status')) }}</b></div>
                <div><span>کلاس مالیات</span><b>{{ $safeText(data_get($woo, 'tax_class')) }}</b></div>
                <div><span>وضعیت موجودی</span><b>{{ $stockLabels[$product->stock_status ?? 'instock'] ?? 'نامشخص' }}</b></div>
                <div><span>تعداد موجودی فروشگاه</span><b>{{ data_get($woo, 'stock_quantity') !== null ? \Modules\Core\Support\Num::fa(number_format((float) data_get($woo, 'stock_quantity'))) : 'ثبت نشده' }}</b></div>
                <div><span>پیش‌سفارش</span><b>{{ $backorderLabels[data_get($woo, 'backorders')] ?? $safeText(data_get($woo, 'backorders')) }}</b></div>
                <div><span>پیش‌سفارش مجاز</span><b>{{ $yesNo(data_get($woo, 'backorders_allowed')) }}</b></div>
                <div><span>تک‌فروشی اجباری</span><b>{{ $yesNo(data_get($woo, 'sold_individually')) }}</b></div>
                <div><span>هشدار کمبود</span><b>@fa($product->min_stock ?? 0)</b></div>
            </div>
        </div>
    </section>

    <section class="product-info-grid product-info-grid-wide">
        <div class="product-info-card">
            <h3>دسته‌بندی‌ها و برچسب‌ها</h3>
            @if($categories)
                <div class="product-chip-list">
                    @foreach($categories as $category)
                        <span>{{ $category['name'] ?? 'دسته‌بندی' }}</span>
                    @endforeach
                </div>
            @else
                <div class="product-empty-state">دسته‌بندی برای این محصول ثبت نشده است.</div>
            @endif

            <h3 class="product-inner-title">برچسب‌ها</h3>
            @if($tags)
                <div class="product-chip-list">
                    @foreach($tags as $tag)
                        <span>{{ $tag['name'] ?? 'برچسب' }}</span>
                    @endforeach
                </div>
            @else
                <div class="product-empty-state product-empty-state-small">برچسبی برای این محصول دریافت نشده است.</div>
            @endif
        </div>

        <div class="product-info-card">
            <h3>ابعاد، وزن و ارسال</h3>
            <div class="product-info-list product-info-list-dense">
                <div><span>وزن</span><b>{{ $safeText(data_get($woo, 'weight')) }}</b></div>
                <div><span>طول</span><b>{{ $safeText(data_get($woo, 'dimensions.length')) }}</b></div>
                <div><span>عرض</span><b>{{ $safeText(data_get($woo, 'dimensions.width')) }}</b></div>
                <div><span>ارتفاع</span><b>{{ $safeText(data_get($woo, 'dimensions.height')) }}</b></div>
                <div><span>نیازمند ارسال</span><b>{{ $yesNo(data_get($woo, 'shipping_required')) }}</b></div>
                <div><span>ارسال مشمول مالیات</span><b>{{ $yesNo(data_get($woo, 'shipping_taxable')) }}</b></div>
                <div><span>کلاس ارسال</span><b>{{ $safeText(data_get($woo, 'shipping_class')) }}</b></div>
                <div><span>شناسه کلاس ارسال</span><b>{{ data_get($woo, 'shipping_class_id') !== null ? \Modules\Core\Support\Num::fa(data_get($woo, 'shipping_class_id')) : 'ثبت نشده' }}</b></div>
            </div>
        </div>
    </section>

    <section class="product-info-card product-content-card">
        <header class="product-section-heading">
            <div>
                <span>محتوای محصول</span>
                <h3>توضیح کوتاه و توضیح کامل فروشگاه</h3>
            </div>
        </header>
        <div class="product-content-grid">
            <div>
                <h4>توضیح کوتاه</h4>
                <div class="product-description-box">
                    {!! $shortDescriptionHtml ?: '<span class="muted">توضیح کوتاهی برای این محصول ثبت نشده است.</span>' !!}
                </div>
            </div>
            <div>
                <h4>توضیح کامل</h4>
                <div class="product-description-box product-description-box-tall">
                    {!! $descriptionHtml ?: '<span class="muted">توضیح کاملی برای این محصول ثبت نشده است.</span>' !!}
                </div>
            </div>
        </div>
    </section>

    <section class="product-info-grid product-info-grid-wide">
        <div class="product-info-card">
            <h3>ویژگی‌های محصول</h3>
            @if($attributes)
                <div class="product-attributes-list product-attributes-list-rich">
                    @foreach($attributes as $attribute)
                        <div>
                            <span>{{ $attribute['name'] ?? 'ویژگی' }}</span>
                            <b>{{ implode('، ', $attribute['options'] ?? []) ?: 'بدون مقدار' }}</b>
                            <small>{{ !empty($attribute['visible']) ? 'قابل نمایش' : 'پنهان' }} · {{ !empty($attribute['variation']) ? 'مبنای تنوع' : 'ویژگی عادی' }}</small>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="product-empty-state">ویژگی‌ای برای این محصول ثبت نشده است.</div>
            @endif
        </div>

        <div class="product-info-card">
            <h3>ویژگی‌های پیش‌فرض و تنوع‌ها</h3>
            <div class="product-info-list product-info-list-dense">
                <div><span>تعداد تنوع‌ها</span><b>{{ \Modules\Core\Support\Num::fa(count($variations)) }}</b></div>
                <div><span>شناسه تنوع‌ها</span><b>{{ $listIds($variations) }}</b></div>
                <div><span>محصول‌های گروهی</span><b>{{ $listIds($groupedProducts) }}</b></div>
                <div><span>محصول‌های پیشنهادی</span><b>{{ $listIds(data_get($woo, 'upsell_ids', [])) }}</b></div>
                <div><span>فروش مکمل</span><b>{{ $listIds(data_get($woo, 'cross_sell_ids', [])) }}</b></div>
                <div><span>محصول‌های مرتبط</span><b>{{ $listIds(data_get($woo, 'related_ids', [])) }}</b></div>
            </div>

            @if($defaultAttributes)
                <div class="product-attributes-list product-attributes-list-rich">
                    @foreach($defaultAttributes as $attribute)
                        <div>
                            <span>{{ $attribute['name'] ?? 'ویژگی پیش‌فرض' }}</span>
                            <b>{{ $attribute['option'] ?? 'ثبت نشده' }}</b>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="product-info-grid product-info-grid-wide">
        <div class="product-info-card">
            <h3>پرونده دانلودی و پیوند بیرونی</h3>
            <div class="product-info-list product-info-list-dense">
                <div><span>پیوند بیرونی محصول</span><b>{{ $safeText(data_get($woo, 'external_url')) }}</b></div>
                <div><span>متن دکمه بیرونی</span><b>{{ $safeText(data_get($woo, 'button_text')) }}</b></div>
                <div><span>حداکثر دانلود</span><b>{{ data_get($woo, 'download_limit') !== null ? \Modules\Core\Support\Num::fa(data_get($woo, 'download_limit')) : 'ثبت نشده' }}</b></div>
                <div><span>اعتبار دانلود</span><b>{{ data_get($woo, 'download_expiry') !== null ? \Modules\Core\Support\Num::fa(data_get($woo, 'download_expiry')) : 'ثبت نشده' }}</b></div>
            </div>

            @if($downloads)
                <div class="product-attributes-list product-attributes-list-rich">
                    @foreach($downloads as $download)
                        <div>
                            <span>{{ $download['name'] ?? 'پرونده دانلودی' }}</span>
                            <b class="ltr">{{ $download['file'] ?? 'ثبت نشده' }}</b>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="product-info-card">
            <h3>زمان‌های ثبت‌شده</h3>
            <div class="product-info-list product-info-list-dense">
                <div><span>ثبت در مشتری‌یار</span><b>@jdatetime($product->created_at)</b></div>
                <div><span>آخرین بروزرسانی مشتری‌یار</span><b>@jdatetime($product->updated_at)</b></div>
                <div><span>ساخت در فروشگاه</span><b>{{ $formatWooDate(data_get($woo, 'date_created')) }}</b></div>
                <div><span>آخرین بروزرسانی فروشگاه</span><b>{{ $formatWooDate(data_get($woo, 'date_modified')) }}</b></div>
                <div><span>شروع فروش ویژه</span><b>{{ $formatWooDate(data_get($woo, 'date_on_sale_from')) }}</b></div>
                <div><span>پایان فروش ویژه</span><b>{{ $formatWooDate(data_get($woo, 'date_on_sale_to')) }}</b></div>
            </div>
        </div>
    </section>

    @if($isMetal)
        <section class="product-calc-card product-calc-metal" style="--calc-color: {{ $isSilver ? '#10b981' : '#f59e0b' }};">
            <header>
                <div>
                    <span>{{ $isSilver ? '🥈' : '👑' }} محاسبه نرخ روز</span>
                    <h3>ماشین‌حساب قیمت {{ $metalName }}</h3>
                    <p>نرخ روز هنگام محاسبه خوانده می‌شود و مبلغ نهایی سفارش قفل خواهد شد.</p>
                </div>
                <div class="product-live-rate">
                    <span>نرخ روز هر گرم</span>
                    <b>@money($liveRate) @unit</b>
                </div>
            </header>

            <div class="product-calc-inputs">
                <div>
                    <label>وزن {{ $metalName }}، گرم</label>
                    <input id="calcWeight" type="number" step="0.001" value="{{ $product->live_gold_weight ?? 1.0 }}" oninput="runMetalCalculator()" class="ltr">
                </div>
                <div>
                    <label>اجرت ساخت به @unit</label>
                    <input id="calcAjrat" type="number" value="{{ $product->live_gold_ajrat ?? 0 }}" oninput="runMetalCalculator()" class="ltr">
                </div>
                <div>
                    <label>سود فروشگاه</label>
                    <input id="calcProfit" type="number" step="0.01" value="{{ $product->live_gold_profit ?? 0.07 }}" oninput="runMetalCalculator()" class="ltr">
                </div>
                <div>
                    <label>مالیات</label>
                    <input id="calcTax" type="number" step="0.01" value="{{ $metalTaxRate }}" oninput="runMetalCalculator()" class="ltr">
                </div>
            </div>

            <div class="product-calc-results">
                <div><span>ارزش خام</span><b id="lblRawMetal">—</b></div>
                <div><span>اجرت</span><b id="lblAjrat">—</b></div>
                <div><span>سود</span><b id="lblProfit">—</b></div>
                <div><span>مالیات</span><b id="lblTax">—</b></div>
            </div>

            <div class="product-grand-total">
                <span>مبلغ نهایی یک واحد</span>
                <b id="lblGrandTotal">—</b>
            </div>
        </section>
    @elseif($isFormula)
        <section class="product-calc-card product-calc-formula" style="--calc-color: #10b981;">
            <header>
                <div>
                    <span>🧮 محاسبه فرمولی</span>
                    <h3>ماشین‌حساب قیمت سفارشی</h3>
                    <p>مقدارهای لازم را وارد کنید تا مبلغ نهایی بر اساس فرمول محصول محاسبه شود.</p>
                </div>
                <div class="product-live-rate">
                    <span>فرمول</span>
                    <b>{{ $product->formula }}</b>
                </div>
            </header>

            <div id="mathInputsContainer" class="product-calc-inputs">
                @if($product->formula_variables)
                    @foreach($product->formula_variables as $fieldName => $fieldValue)
                        <div>
                            <label>{{ $fieldName }}</label>
                            <input data-math-var="{{ $fieldName }}" type="number" step="any" value="{{ $fieldValue }}" oninput="runMathCalculator()" class="ltr">
                        </div>
                    @endforeach
                @else
                    @foreach(array_slice($customFormulaFields, 0, 2) as $fieldName)
                        <div>
                            <label>{{ $fieldName }}</label>
                            <input data-math-var="{{ $fieldName }}" type="number" step="any" value="1" oninput="runMathCalculator()" class="ltr">
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="product-grand-total">
                <span>مبلغ نهایی یک واحد</span>
                <b id="lblMathResult">—</b>
            </div>
        </section>
    @else
        <section class="product-calc-card product-calc-fixed" style="--calc-color: #3b82f6;">
            <header>
                <div>
                    <span>💳 قیمت ثابت</span>
                    <h3>قیمت آماده ثبت سفارش</h3>
                    <p>این کالا قیمت ثابت دارد و مبلغ زیر برای هر واحد در سفارش ثبت می‌شود.</p>
                </div>
                <div class="product-live-rate">
                    <span>مبلغ هر واحد</span>
                    <b id="lblFixedPrice">@money($fixedUnitPrice) @unit</b>
                </div>
            </header>
        </section>
    @endif

    <section class="product-order-card">
        <header>
            <div>
                <span>ثبت سفارش سریع</span>
                <h3>قفل کردن مبلغ و ساخت سفارش</h3>
            </div>
            @if($quickCustomer)
                <div class="product-selected-customer">
                    <b>{{ $quickCustomer->full_name }}</b>
                    <small class="ltr">{{ $quickCustomer->phone ?? 'بدون شماره' }}</small>
                </div>
            @endif
        </header>

        <form method="post" action="{{ url('/app/products/'.$product->id.'/sell') }}" id="quickOrderForm">
            @csrf
            @if(request()->boolean('embed'))
                <input type="hidden" name="embed" value="1">
            @endif

            <div class="product-order-grid">
                @if($quickCustomer)
                    <input type="hidden" name="customer_id" value="{{ $quickCustomer->id }}">
                @else
                    <div>
                        <label>انتخاب مشتری از فهرست</label>
                        <select name="customer_id">
                            <option value="">یک مشتری را انتخاب کنید</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->full_name }} @if($customer->phone) — {{ $customer->phone }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label>تعداد</label>
                    <input type="number" name="qty" id="saleQty" min="1" value="1" oninput="updateSaleSummary()" class="ltr">
                </div>
                @unless($quickCustomer)
                    <div>
                        <label>اگر مشتری در فهرست نبود، نام او را بنویسید</label>
                        <input type="text" name="customer_name" placeholder="مثال: علی رضایی">
                    </div>
                    <div>
                        <label>شماره مشتری</label>
                        <input type="text" name="customer_phone" placeholder="مثال: 0912..." class="ltr">
                    </div>
                @endunless
            </div>

            <input type="hidden" name="weight" id="saleWeightInput">
            <input type="hidden" name="ajrat" id="saleAjratInput">
            <input type="hidden" name="profit_rate" id="saleProfitInput">
            <input type="hidden" name="tax_rate" id="saleTaxInput">
            <input type="hidden" name="formula_inputs_json" id="saleFormulaInputsJson">

            <div class="product-sale-summary">
                <div><span>مبلغ یک واحد</span><b id="saleUnitPriceLabel">—</b></div>
                <div><span>تعداد</span><b id="saleQtyLabel">۱</b></div>
                <div><span>مبلغ کل سفارش</span><b id="saleTotalLabel">—</b></div>
                <div><span>مالیات ثبت‌شده</span><b id="saleTaxLabel">—</b></div>
            </div>

            <div class="product-order-actions">
                <button type="submit" class="btn">ثبت سفارش و قفل کردن مبلغ</button>
            </div>
        </form>
    </section>

    <section class="product-stock-card">
        <header>
            <h3>حرکت‌های انبار</h3>
            <p>ورود، خروج و اصلاح موجودی محصول</p>
        </header>
        <form method="post" action="{{ url('/app/products/'.$product->id.'/move') }}" class="product-stock-form">
            @csrf
            <div>
                <label>نوع حرکت</label>
                <select name="type">
                    <option value="in">ورود کالا</option>
                    <option value="out">خروج کالا</option>
                    <option value="adjust">اصلاح موجودی</option>
                </select>
            </div>
            <div>
                <label>تعداد</label>
                <input name="qty" class="ltr" type="number" required placeholder="مثال: 20">
            </div>
            <div>
                <label>توضیح</label>
                <input name="reason" placeholder="مثال: رسید خرید یا فاکتور فروش">
            </div>
            <button class="btn">ثبت حرکت</button>
        </form>

        <div class="product-movements-list">
            @forelse($product->movements as $movement)
                <details class="product-movement-item">
                    <summary>
                        <span>{{ match($movement->type){ 'in'=>'ورود کالا', 'out'=>'خروج کالا', default=>'اصلاح موجودی' } }}</span>
                        <b>@fa($movement->qty)</b>
                        <em>@jdatetime($movement->created_at)</em>
                    </summary>
                    <div>
                        <span>موجودی بعد از ثبت: <b>@fa($movement->balance_after)</b></span>
                        <span>توضیح: <b>{{ $movement->reason ?? '—' }}</b></span>
                    </div>
                </details>
            @empty
                <div class="product-empty-state">هنوز حرکتی برای این کالا ثبت نشده است.</div>
            @endforelse
        </div>
    </section>
</div>

<script>
const productKind = @json($isMetal ? 'metal' : ($isFormula ? 'formula' : 'fixed'));
const liveRate = {{ $liveRate ?? 0 }};
const moneyUnit = @json($unitLabel);
const formulaText = @json((string) $product->formula);
const fixedAmount = {{ $fixedUnitPrice }};
let currentUnitPrice = fixedAmount;
let currentUnitTax = 0;

function formatMoney(value) {
    return new Intl.NumberFormat('fa-IR').format(Math.round(value)) + ' ' + moneyUnit;
}

function escapeForRegExp(text) {
    return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function runMetalCalculator() {
    const weight = parseFloat(document.getElementById('calcWeight').value) || 0;
    const ajrat = parseFloat(document.getElementById('calcAjrat').value) || 0;
    const profitRate = parseFloat(document.getElementById('calcProfit').value) || 0.07;
    const taxRate = parseFloat(document.getElementById('calcTax').value) || 0.10;

    const rawMetalValue = weight * liveRate;
    const totalAjrat = weight * ajrat;
    const storeProfit = (rawMetalValue + totalAjrat) * profitRate;
    const taxAmount = (totalAjrat + storeProfit) * taxRate;
    const grandTotal = Math.round(rawMetalValue + totalAjrat + storeProfit + taxAmount);

    document.getElementById('lblRawMetal').textContent = formatMoney(rawMetalValue);
    document.getElementById('lblAjrat').textContent = formatMoney(totalAjrat);
    document.getElementById('lblProfit').textContent = formatMoney(storeProfit);
    document.getElementById('lblTax').textContent = formatMoney(taxAmount);
    document.getElementById('lblGrandTotal').textContent = formatMoney(grandTotal);

    currentUnitPrice = grandTotal;
    currentUnitTax = taxAmount;

    document.getElementById('saleWeightInput').value = weight;
    document.getElementById('saleAjratInput').value = ajrat;
    document.getElementById('saleProfitInput').value = profitRate;
    document.getElementById('saleTaxInput').value = taxRate;

    updateSaleSummary(grandTotal, taxAmount);
}

function runMathCalculator() {
    let expression = formulaText;
    const values = {};

    document.querySelectorAll('#mathInputsContainer input').forEach(input => {
        const name = input.getAttribute('data-math-var');
        values[name] = parseFloat(input.value) || 0;
    });

    const sortedNames = Object.keys(values).sort((a, b) => b.length - a.length);
    sortedNames.forEach(name => {
        const pattern = new RegExp(escapeForRegExp(name), 'g');
        expression = expression.replace(pattern, values[name]);
    });

    try {
        const cleanExpression = expression.replace(/[^0-9\+\-\*\/\(\)\.\s]/g, '');
        const result = Math.round(Function('return (' + cleanExpression + ');')());
        document.getElementById('lblMathResult').textContent = formatMoney(result);
        currentUnitPrice = result;
        currentUnitTax = 0;
        document.getElementById('saleFormulaInputsJson').value = JSON.stringify(values);
        updateSaleSummary(result, 0);
    } catch (error) {
        document.getElementById('lblMathResult').textContent = '—';
        currentUnitPrice = 0;
        currentUnitTax = 0;
        document.getElementById('saleFormulaInputsJson').value = JSON.stringify(values);
        updateSaleSummary(0, 0);
    }
}

function updateSaleSummary(forcedUnitPrice = null, forcedTax = null) {
    const qty = Math.max(1, parseInt(document.getElementById('saleQty').value || '1', 10));
    let unitPrice = productKind === 'fixed' ? fixedAmount : currentUnitPrice;
    let unitTax = productKind === 'metal' ? currentUnitTax : 0;

    if (forcedUnitPrice !== null) unitPrice = forcedUnitPrice;
    if (forcedTax !== null) unitTax = forcedTax;

    document.getElementById('saleQtyLabel').textContent = new Intl.NumberFormat('fa-IR').format(qty);
    document.getElementById('saleUnitPriceLabel').textContent = formatMoney(unitPrice);
    document.getElementById('saleTotalLabel').textContent = formatMoney(unitPrice * qty);
    document.getElementById('saleTaxLabel').textContent = formatMoney(unitTax * qty);
}

window.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('calcWeight')) {
        runMetalCalculator();
    } else if (document.getElementById('lblMathResult')) {
        runMathCalculator();
    } else {
        updateSaleSummary(fixedAmount, 0);
    }
});
</script>
@endsection