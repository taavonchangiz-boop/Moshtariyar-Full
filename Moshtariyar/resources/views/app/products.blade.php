@extends('layouts.app')
@section('title', 'مدیریت کالاها و انبار')
@section('heading', 'بورد محصولات و انبار')
@section('subtitle', 'کنترل موجودی، قیمت‌گذاری، کالاهای محاسباتی، طلا و نقره و ثبت سریع سفارش')

@section('content')
<link rel="stylesheet" href="{{ asset('css/products-board.css') }}">
<link rel="stylesheet" href="{{ asset('css/products-card-view.css') }}">

@php
    $canManageProducts = auth()->user()?->hasPermission('orders.manage');

    $bucketCards = collect([
        ['key' => 'low',      'label' => 'نیازمند تأمین',   'color' => '#ef4444', 'hint' => 'کالاهایی که موجودی آن‌ها به هشدار رسیده است'],
        ['key' => 'metal',    'label' => 'طلا و نقره',      'color' => '#f59e0b', 'hint' => 'کالاهای وابسته به نرخ روز فلزات'],
        ['key' => 'formula',  'label' => 'محاسباتی',        'color' => '#10b981', 'hint' => 'کالاهایی که قیمتشان با فرمول محاسبه می‌شود'],
        ['key' => 'standard', 'label' => 'قیمت ثابت',       'color' => '#3b82f6', 'hint' => 'کالاهای ساده با قیمت مشخص'],
    ]);

    $formulaLabel = function ($type) {
        return match ($type) {
            'gold_jewelry'   => 'طلا',
            'silver_jewelry' => 'نقره',
            'standard_math'  => 'محاسباتی',
            default          => 'قیمت ثابت',
        };
    };

    $formulaClass = function ($type) {
        return match ($type) {
            'gold_jewelry'   => 'is-gold',
            'silver_jewelry' => 'is-silver',
            'standard_math'  => 'is-formula',
            default          => 'is-standard',
        };
    };

    $openGroups = ['low'];
    if (request('low'))     { $openGroups = ['low']; }
    if (request('formula')) { $openGroups = ['formula']; }
    if (request('formula_type') === 'gold_jewelry'
        || request('formula_type') === 'silver_jewelry') { $openGroups = ['metal']; }
    if (request('formula_type') === 'standard')          { $openGroups = ['standard']; }
    if (request('formula_type') === 'standard_math')     { $openGroups = ['formula']; }
    if (empty(array_filter([request('low'), request('formula'), request('formula_type')]))) {
        $openGroups = ['low', 'metal', 'formula', 'standard'];
    }
@endphp

<div class="products-page">

    {{-- هدر --}}
    <section class="products-hero">
        <div>
            <span class="products-eyebrow">مرکز محصولات و انبار</span>
            <h2>کالا، موجودی، قیمت‌گذاری و فروش سریع را در یک بورد عملیاتی مدیریت کن</h2>
            <p>کالاها بر اساس نیاز عملیاتی دسته‌بندی شده‌اند: موجودی رو به اتمام، طلا و نقره وابسته به نرخ روز، کالاهای محاسباتی و قیمت ثابت.</p>
            <div class="products-hero-actions">
                @if($canManageProducts)<a class="btn" href="#productQuickPanel">ثبت کالای جدید</a>@endif
                <button class="btn btn-ghost" type="button" onclick="askProductsAssistant('یک کالای جدید بساز', this)">ثبت با دستیار</button>
                <a class="btn btn-ghost" href="{{ url('/app/orders') }}">سفارش‌ها</a>
                <a class="btn btn-ghost" href="{{ url('/app/reports') }}">گزارش انبار</a>
            </div>
        </div>
        <div class="products-score">
            <a href="{{ url('/app/products') }}" style="--metric-color:#3b82f6;">
                <span>کل کالاها</span><b>@fa($summary['total'] ?? 0)</b><small>ثبت‌شده در انبار</small>
            </a>
            <a href="{{ url('/app/products') }}" style="--metric-color:#10b981;">
                <span>کالاهای فعال</span><b>@fa($summary['active'] ?? 0)</b><small>در فهرست فروش</small>
            </a>
            <a href="{{ url('/app/products?low=1') }}" style="--metric-color:#ef4444;">
                <span>نیازمند تأمین</span><b>@fa($summary['low'] ?? 0)</b><small>هشدار موجودی</small>
            </a>
            <a href="{{ url('/app/products') }}" style="--metric-color:#f59e0b;">
                <span>ارزش موجودی</span><b>@money($summary['stock_value'] ?? 0)</b><small>{{ $unitLabel }}</small>
            </a>
        </div>
    </section>

    {{-- نوار فیلترها --}}
    <form method="get" class="products-toolbar">
        <div>
            <label>جستجو</label>
            <input name="search" value="{{ request('search') }}" placeholder="نام، کد یا دسته‌بندی">
        </div>
        <div>
            <label>دسته‌بندی</label>
            <select name="category">
                <option value="">همه دسته‌ها</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>روش قیمت</label>
            <select name="formula_type">
                <option value="">همه روش‌ها</option>
                <option value="standard"       @selected(request('formula_type') === 'standard')>قیمت ثابت</option>
                <option value="gold_jewelry"   @selected(request('formula_type') === 'gold_jewelry')>طلا</option>
                <option value="silver_jewelry" @selected(request('formula_type') === 'silver_jewelry')>نقره</option>
                <option value="standard_math"  @selected(request('formula_type') === 'standard_math')>محاسباتی</option>
            </select>
        </div>
        <label class="products-toolbar-check">
            <input type="checkbox" name="low" value="1" @checked(request('low'))>
            <span>فقط موجودی کم</span>
        </label>
        <label class="products-toolbar-check">
            <input type="checkbox" name="formula" value="1" @checked(request('formula'))>
            <span>فقط محاسباتی</span>
        </label>
        <div class="products-toolbar-actions">
            <button class="btn">اعمال فیلتر</button>
            @if(request()->hasAny(['search','category','formula_type','low','formula']))
                <a class="btn btn-ghost" href="{{ url('/app/products') }}">پاک کردن</a>
            @endif
        </div>
    </form>

    {{-- نوار خلاصه --}}
    <section class="products-insight">
        <div><span class="products-dot is-blue"></span><b>@fa($products->count())</b><small>کالای قابل نمایش</small></div>
        <div><span class="products-dot is-ok"></span><b>@fa($summary['formula'] ?? 0)</b><small>کالای دارای فرمول قیمت</small></div>
        <div><span class="products-dot is-warn"></span><b>@fa($lowCount)</b><small>کالا با هشدار کمبود موجودی</small></div>
        <div><span class="products-dot is-red"></span><b>@fa(count($categories))</b><small>دسته‌بندی فعال</small></div>
    </section>

    {{-- ═══════ تغییر نما: لیستی / کارتی ═══════ --}}
    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
        <button type="button" class="pi-view-btn active" data-view="list" onclick="switchProductView('list')">📃 نمای لیستی</button>
        <button type="button" class="pi-view-btn" data-view="card" onclick="switchProductView('card')">🃏 نمای کارتی</button>
        <span style="margin-right:auto; font-size:0.75rem; color:var(--mut); font-weight:600;">@fa($products->count()) کالا</span>
    </div>

    {{-- ═══════════════ نمای کارتی ═══════════════ --}}
    <div id="productCardView" style="display:none;">
        <div class="pi-grid">
            @foreach($products as $product)
                @php
                    $stockVal    = (int) ($product->stock ?? 0);
                    $minStock    = (int) ($product->min_stock ?? 0);
                    $isMetal     = in_array($product->formula_type, ['gold_jewelry', 'silver_jewelry'], true);
                    $isFormula   = (bool) $product->is_formula_based;
                    $stockPercent = $minStock > 0 ? min(100, round(($stockVal / max(1, $minStock * 2)) * 100)) : 100;

                    if ($stockVal <= 0) {
                        $stockLevel = 'pi-stock-empty';
                        $stockLabel = 'ناموجود';
                    } elseif ($minStock > 0 && $stockVal <= $minStock) {
                        $stockLevel = 'pi-stock-low';
                        $stockLabel = 'هشدار';
                    } elseif ($minStock > 0 && $stockVal <= $minStock * 2) {
                        $stockLevel = 'pi-stock-warn';
                        $stockLabel = 'رو به اتمام';
                    } else {
                        $stockLevel = 'pi-stock-ok';
                        $stockLabel = 'موجود';
                    }

                    $imgSrc = $product->image
                        ? (str_starts_with($product->image, 'http') ? $product->image : asset($product->image))
                        : null;
                    $firstChar = mb_substr($product->name ?? 'ک', 0, 1);

                    if ($isMetal) {
                        $badgeClass = 'pi-badge-metal';
                        $badgeText  = $product->formula_type === 'gold_jewelry' ? 'طلا' : 'نقره';
                    } elseif ($isFormula) {
                        $badgeClass = 'pi-badge-formula';
                        $badgeText  = 'محاسباتی';
                    } elseif ($stockVal <= 0 || ($minStock > 0 && $stockVal <= $minStock)) {
                        $badgeClass = 'pi-badge-low';
                        $badgeText  = $stockVal <= 0 ? 'ناموجود' : 'هشدار';
                    } else {
                        $badgeClass = 'pi-badge-standard';
                        $badgeText  = 'قیمت ثابت';
                    }

                    $stockColor = '#10b981';
                    if ($stockVal <= 0) {
                        $stockColor = '#ef4444';
                    } elseif ($minStock > 0 && $stockVal <= $minStock) {
                        $stockColor = '#ef4444';
                    } elseif ($minStock > 0 && $stockVal <= $minStock * 2) {
                        $stockColor = '#f59e0b';
                    }
                @endphp

                <div class="pi-card" onclick="window.location='{{ url('/app/products/' . $product->id) }}'">
                    <div class="pi-card-image">
                        @if($imgSrc)
                            <img src="{{ $imgSrc }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <div class="pi-card-image-placeholder">{{ $firstChar }}</div>
                        @endif
                        <span class="pi-card-badge {{ $badgeClass }}">{{ $badgeText }}</span>
                    </div>
                    <div class="pi-card-body">
                        <div class="pi-card-name">{{ $product->name }}</div>
                        <div class="pi-card-sku">{{ $product->sku ?: '#' . $product->id }}</div>
                        <div class="pi-card-price">
                            @if($isMetal)
                                <span style="font-size:0.7rem;">وابسته به نرخ روز</span>
                            @elseif($isFormula)
                                <span style="font-size:0.7rem;">بر اساس فرمول</span>
                            @else
                                @money($product->price ?? 0) {{ $unitLabel }}
                            @endif
                        </div>
                        <div class="pi-stock-section">
                            <div class="pi-stock-header">
                                <span>موجودی</span>
                                <b style="color:{{ $stockColor }};">{{ $stockLabel }} · @fa($stockVal)</b>
                            </div>
                            <div class="pi-stock-bar {{ $stockLevel }}">
                                <span style="width:{{ $stockPercent }}%;"></span>
                            </div>
                        </div>
                        @if($product->category)
                            <div class="pi-card-category">📂 {{ $product->category }}</div>
                        @endif
                    </div>
                    <div class="pi-card-footer">
                        <a href="{{ url('/app/products/' . $product->id) }}" class="pi-btn pi-btn-primary" onclick="event.stopPropagation();">👁️</a>
                        <a href="{{ url('/app/pos') }}" class="pi-btn pi-btn-sell" onclick="event.stopPropagation();">💳 فروش</a>
                        @if($canManageProducts)
                            <a href="{{ url('/app/products/' . $product->id . '/edit') }}" class="pi-btn" onclick="event.stopPropagation();">✏️</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ نمای لیستی (بورد اصلی) ═══════════════ --}}
    <section class="products-layout" id="productListView">
        <div class="products-main">
            <article class="products-card is-accent" style="--card-color:#3b82f6;">
                <header>
                    <div>
                        <span>بورد محصولات</span>
                        <h2>مدیریت گروهی کالا بر اساس روش قیمت‌گذاری و وضعیت موجودی</h2>
                        <p>روی هر کالا کلیک کن تا جزئیات کامل قیمت، فرمول محاسبه، انبار و عملیات سریع باز شود.</p>
                    </div>
                    @if($canManageProducts)
                        <a class="products-link" href="#productQuickPanel">کالای جدید</a>
                    @endif
                </header>

                <div class="products-board">
                    @foreach($bucketCards as $bucket)
                        @php
                            $groupProducts = $productGroups->get($bucket['key'], collect());
                            $groupCount    = $groupProducts->count();
                            $groupStock    = $groupProducts->sum(fn ($p) => (int) ($p->stock ?? 0));
                            $groupValue    = $groupProducts->sum(fn ($p) => (float) (($p->stock ?? 0) * ($p->price ?? 0)));
                            $ratio         = $products->count() > 0 ? round(($groupCount / max(1, $products->count())) * 100) : 0;
                            $isOpen        = in_array($bucket['key'], $openGroups, true) && $groupCount > 0;
                            $groupClass    = $isOpen ? 'is-open' : 'is-collapsed';
                        @endphp

                        <div class="products-group {{ $groupClass }}" style="--group-color:{{ $bucket['color'] }}; --group-ratio:{{ $ratio }}%;">
                            <button type="button" class="products-group-title" onclick="toggleProductsGroup(this)">
                                <span class="pg-caret">◀</span>
                                <span class="pg-mark"></span>
                                <span class="pg-title-block">
                                    <b>{{ $bucket['label'] }}</b>
                                    <small>{{ $bucket['hint'] }}</small>
                                </span>
                                <span class="pg-count">@fa($groupCount)</span>
                                <span class="pg-progress"></span>
                                <span class="pg-ratio">@fa($ratio)٪</span>
                            </button>
                            <div class="products-group-summary">
                                <span>مجموع موجودی: <b>@fa($groupStock)</b></span>
                                <span>ارزش تقریبی: <b>@money($groupValue)</b> {{ $unitLabel }}</span>
                                <span>تعداد کالا: <b>@fa($groupCount)</b></span>
                            </div>
                            <div class="products-table-wrap">
                                <div class="products-table">
                                    <div class="products-thead">
                                        <div>تصویر</div><div>کالا</div><div>روش قیمت</div><div>قیمت پایه</div>
                                        <div>موجودی</div><div>هشدار</div><div>دسته</div><div>عملیات</div>
                                    </div>
                                    @forelse($groupProducts as $product)
                                        @php
                                            $isMetal    = in_array($product->formula_type, ['gold_jewelry', 'silver_jewelry'], true);
                                            $isFormula  = (bool) $product->is_formula_based;
                                            $isLow      = method_exists($product, 'isLowStock') ? $product->isLowStock() : (($product->stock ?? 0) <= ($product->min_stock ?? 0));
                                            $stockClass = ($product->stock ?? 0) <= 0 ? 'is-stock-out' : ($isLow ? 'is-stock-low' : 'is-stock-ok');
                                            $firstChar  = mb_substr($product->name ?? 'ک', 0, 1);
                                            $imgSrc     = $product->image ? (str_starts_with($product->image, 'http') ? $product->image : asset($product->image)) : null;
                                        @endphp
                                        <details class="products-row">
                                            <summary>
                                                <div class="products-cell"><div class="products-thumb">@if($imgSrc)<img src="{{ $imgSrc }}" alt="{{ $product->name }}" loading="lazy">@else{{ $firstChar }}@endif</div></div>
                                                <div class="products-cell"><div class="products-cell-name"><b>{{ $product->name }}</b><small class="sku">{{ $product->sku ?: 'بدون کد' }}</small></div></div>
                                                <div class="products-cell"><span class="products-pill {{ $formulaClass($product->formula_type) }}">{{ $formulaLabel($product->formula_type) }}</span></div>
                                                <div class="products-cell">
                                                    @if($isMetal)<span class="products-pill is-price-live">وابسته به نرخ روز</span>
                                                    @elseif($isFormula)<span class="products-pill is-price-calc">بر اساس فرمول</span>
                                                    @else<span class="products-pill is-price">@money($product->price ?? 0) {{ $unitLabel }}</span>@endif
                                                </div>
                                                <div class="products-cell"><span class="products-pill {{ $stockClass }}">@fa($product->stock ?? 0)</span></div>
                                                <div class="products-cell">@if(($product->min_stock ?? 0) > 0)<span class="products-pill is-empty">@fa($product->min_stock)</span>@else<span class="products-pill is-empty">—</span>@endif</div>
                                                <div class="products-cell">@if($product->category)<span class="products-pill is-category">{{ $product->category }}</span>@else<span class="products-pill is-empty">—</span>@endif</div>
                                                <div class="products-cell">
                                                    <div class="products-cell-actions">
                                                        <a href="{{ url('/app/products/' . $product->id) }}">مشاهده</a>
                                                        @if($canManageProducts)<a href="{{ url('/app/products/' . $product->id . '/edit') }}">ویرایش</a>@endif
                                                    </div>
                                                </div>
                                            </summary>
                                            <div class="products-detail-panel">
                                                <div class="products-detail-block">
                                                    <h4>قیمت‌گذاری <span>{{ $formulaLabel($product->formula_type) }}</span></h4>
                                                    <div class="products-detail-hero">
                                                        <div class="thumb-large">@if($imgSrc)<img src="{{ $imgSrc }}" alt="{{ $product->name }}" loading="lazy">@else{{ $firstChar }}@endif</div>
                                                        <div class="info">
                                                            <b>{{ $product->name }}</b>
                                                            <small>کد: <span dir="ltr">{{ $product->sku ?: 'ندارد' }}</span></small>
                                                            <small>دسته: {{ $product->category ?: 'بدون دسته' }}</small>
                                                        </div>
                                                    </div>
                                                    @if($isMetal)
                                                        <div class="products-price-grid">
                                                            <div class="is-gold"><span>وزن ثبت‌شده</span><b>@fa($product->live_gold_weight ?? 0) گرم</b></div>
                                                            <div><span>اجرت ساخت</span><b>@money($product->live_gold_ajrat ?? 0) {{ $unitLabel }}</b></div>
                                                            <div><span>سود فروشگاه</span><b>@fa(number_format(($product->live_gold_profit ?? 0) * 100, 1))٪</b></div>
                                                            <div><span>مالیات</span><b>@fa(number_format(($product->metal_tax_rate ?? 0) * 100, 1))٪</b></div>
                                                        </div>
                                                        <div class="products-formula-box">قیمت نهایی = (نرخ روز {{ $product->formula_type === 'gold_jewelry' ? 'طلا' : 'نقره' }} × وزن) + اجرت + سود + مالیات</div>
                                                    @elseif($isFormula)
                                                        <div class="products-price-grid">
                                                            <div class="is-highlight"><span>روش محاسبه</span><b>فرمول سفارشی</b></div>
                                                            <div><span>قیمت خرید</span><b>@money($product->cost_price ?? 0)</b></div>
                                                        </div>
                                                        <div class="products-formula-box">{{ $product->formula ?: '// فرمولی ثبت نشده است' }}</div>
                                                        @if(!empty($customFormulaFields))<small style="color:var(--mut); font-size:0.74rem; display:block; margin-top:0.35rem;">فیلدهای در دسترس: {{ implode('، ', $customFormulaFields) }}</small>@endif
                                                    @else
                                                        <div class="products-price-grid">
                                                            <div class="is-highlight"><span>قیمت فروش نهایی</span><b>@money($product->price ?? 0) {{ $unitLabel }}</b></div>
                                                            <div><span>قیمت اصلی</span><b>@money($product->regular_price ?? 0)</b></div>
                                                            <div><span>قیمت ویژه</span><b>@money($product->sale_price ?? 0)</b></div>
                                                            <div><span>قیمت خرید</span><b>@money($product->cost_price ?? 0)</b></div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="products-detail-block">
                                                    <h4>انبار و عملیات <span>{{ $product->stock_status ?? 'موجود' }}</span></h4>
                                                    <div class="products-stock-grid">
                                                        <div class="{{ $isLow ? 'is-alert' : '' }}"><span>موجودی فعلی</span><b>@fa($product->stock ?? 0)</b></div>
                                                        <div><span>هشدار کمبود</span><b>@fa($product->min_stock ?? 0)</b></div>
                                                        <div><span>ارزش موجودی</span><b>@money(($product->stock ?? 0) * ($product->price ?? 0))</b></div>
                                                    </div>
                                                    @if($product->short_description)<div class="products-formula-box" style="direction:rtl; font-weight:500;">{{ $product->short_description }}</div>@endif
                                                    <div class="products-detail-actions">
                                                        <a href="{{ url('/app/products/' . $product->id) }}" class="is-primary">پرونده کامل</a>
                                                        @if($canManageProducts)
                                                            <a href="{{ url('/app/products/' . $product->id . '/edit') }}">ویرایش</a>
                                                            <button type="button" onclick="askProductsAssistant('برای کالای {{ addslashes($product->name) }} یک تخفیف ۱۰ درصدی موقت اعمال کن', this)">+ تخفیف با دستیار</button>
                                                            <button type="button" onclick="askProductsAssistant('برای کالای {{ addslashes($product->name) }} یک هشدار موجودی کم تنظیم کن', this)">تنظیم هشدار</button>
                                                            <form method="post" action="{{ url('/app/products/' . $product->id) }}" onsubmit="return confirm('این کالا حذف شود؟')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="is-danger">حذف کالا</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </details>
                                    @empty
                                        <div class="products-group-empty">در این گروه کالایی وجود ندارد.</div>
                                    @endforelse
                                </div>
                            </div>
                            @if($canManageProducts)<a class="products-group-add" href="#productQuickPanel">+ افزودن کالا در گروه «{{ $bucket['label'] }}»</a>@endif
                        </div>
                    @endforeach
                </div>
                @if($products->hasPages())<div style="margin-top:1rem;">{{ $products->withQueryString()->links() }}</div>@endif
            </article>
        </div>

        <div class="products-below">
            <article class="products-card is-accent" style="--card-color:#8b5cf6;">
                <header><div><span>پیشنهاد دستیار</span><h2>اقدامات سریع مدیریت کالا</h2></div></header>
                <div class="products-suggest-list">
                    <button type="button" onclick="askProductsAssistant('لیست کالاهای رو به اتمام موجودی را بده و برای هر کدام هشدار بازخرید بگذار', this)"><b>هشدار کالاهای رو به اتمام</b><small>یادآور بازخرید برای کالاهای کم‌موجود</small></button>
                    <button type="button" onclick="askProductsAssistant('برای کالاهای طلا و نقره نرخ روز را به‌روز کن و قیمت‌های جدید را محاسبه کن', this)"><b>به‌روزرسانی نرخ طلا و نقره</b><small>محاسبه مجدد قیمت کالاهای وابسته به نرخ روز</small></button>
                    <button type="button" onclick="askProductsAssistant('برای کالاهای پرفروش یک تخفیف مناسبتی ۱۰ درصدی پیشنهاد بده', this)"><b>تخفیف مناسبتی کالاهای پرفروش</b><small>پیشنهاد کد تخفیف بر اساس تحلیل فروش</small></button>
                    <button type="button" onclick="askProductsAssistant('گزارش ارزش موجودی انبار و کالاهای راکد را بده', this)"><b>گزارش ارزش موجودی و کالاهای راکد</b><small>تحلیل انبار برای بهینه‌سازی سرمایه</small></button>
                    <button type="button" onclick="askProductsAssistant('یک کالای محاسباتی جدید برای خدمات پرده و بسته‌بندی با فرمول قیمت بساز', this)"><b>ساخت کالای محاسباتی جدید</b><small>کالای با فرمول ابعاد یا وزن</small></button>
                </div>
            </article>

            <article class="products-card is-accent" style="--card-color:#10b981;" id="productQuickPanel">
                <header><div><span>ثبت کالای جدید</span><h2>افزودن سریع کالا با انتخاب روش قیمت‌گذاری</h2></div></header>
                @if($canManageProducts)
                    <form method="post" action="{{ url('/app/products') }}" class="products-quick-form" enctype="multipart/form-data">
                        @csrf
                        <div><label>نام کالا</label><input name="name" required placeholder="مثلاً دستبند نقره یا پرده زبرا"></div>
                        <div class="products-form-grid">
                            <div><label>کد کالا (SKU)</label><input name="sku" class="ltr" placeholder="مثلاً ۱۰۱"></div>
                            <div><label>دسته‌بندی</label><input name="category" placeholder="مثلاً دکوراسیون" list="productsCategoryList"><datalist id="productsCategoryList">@foreach($categories as $cat)<option value="{{ $cat }}"></option>@endforeach</datalist></div>
                        </div>
                        <div class="products-form-grid">
                            <div><label>نوع محصول</label><select name="product_type"><option value="simple">محصول ساده</option><option value="variable">محصول متغیر</option><option value="service">خدمت</option><option value="digital">محصول دیجیتال</option><option value="custom">محصول سفارشی</option></select></div>
                            <div><label>وضعیت نمایش</label><select name="status"><option value="publish">منتشر شده</option><option value="draft">پیش‌نویس</option><option value="private">خصوصی</option></select></div>
                        </div>
                        <div><label>روش قیمت‌گذاری</label><select id="quickFormulaType" name="formula_type" onchange="toggleQuickFormula()"><option value="standard">قیمت ثابت</option><option value="gold_jewelry">طلا (وابسته به نرخ روز)</option><option value="silver_jewelry">نقره (وابسته به نرخ روز)</option><option value="standard_math">محاسباتی (با فرمول)</option></select></div>
                        <div id="quickStandardBox" class="products-price-box">
                            <div class="box-title"><b>قیمت ثابت</b><span>قیمت مشخص کالا</span></div>
                            <div class="products-form-grid">
                                <div><label>قیمت اصلی</label><input name="regular_price" type="number" class="ltr" value="0"></div>
                                <div><label>قیمت فروش ویژه</label><input name="sale_price" type="number" class="ltr" placeholder="اختیاری"></div>
                                <div><label>قیمت فروش نهایی</label><input name="price" type="number" class="ltr" value="0"></div>
                                <div><label>قیمت خرید</label><input name="cost_price" type="number" class="ltr" value="0"></div>
                            </div>
                        </div>
                        <div id="quickMetalBox" class="products-price-box" style="display:none;">
                            <div class="box-title"><b id="quickMetalTitle">مشخصات کالای طلا</b><span>نرخ روز هنگام محاسبه خوانده می‌شود</span></div>
                            <div class="products-form-grid">
                                <div><label id="quickMetalWeightLabel">وزن قطعه (گرم)</label><input name="live_gold_weight" type="number" step="0.001" class="ltr" placeholder="مثلاً ۴.۲۵۰"></div>
                                <div><label>اجرت ساخت</label><input name="live_gold_ajrat" type="number" class="ltr" placeholder="مثلاً ۳۵۰۰۰۰"></div>
                                <div><label>سود فروشگاه (نسبت)</label><input id="quickMetalProfit" name="live_gold_profit" type="number" step="0.01" class="ltr" value="{{ $defaultGoldProfit }}"></div>
                                <div><label>مالیات (نسبت)</label><input id="quickMetalTax" name="metal_tax_rate" type="number" step="0.01" class="ltr" value="{{ $defaultGoldTax }}"></div>
                            </div>
                        </div>
                        <div id="quickMathBox" class="products-price-box" style="display:none;">
                            <div class="box-title"><b>فرمول محاسباتی</b><span>برای پرده، چاپ، ابعاد یا خدمات سفارشی</span></div>
                            <div><label>فرمول قیمت</label><input name="formula" class="ltr" placeholder="مثلاً: (طول * عرض * 180000) + 50000">@if(!empty($customFormulaFields))<small>فیلدهای در دسترس: {{ implode('، ', $customFormulaFields) }}</small>@endif</div>
                        </div>
                        <div class="products-form-grid">
                            <div><label>موجودی اولیه</label><input name="stock" type="number" class="ltr" placeholder="مثلاً ۵۰"></div>
                            <div><label>هشدار کمبود</label><input name="min_stock" type="number" class="ltr" placeholder="مثلاً ۵"></div>
                        </div>
                        <div class="products-form-grid">
                            <div><label>وضعیت موجودی</label><select name="stock_status"><option value="instock">موجود</option><option value="outofstock">ناموجود</option><option value="onbackorder">قابل پیش‌سفارش</option></select></div>
                            <div><label>تصویر شاخص</label><input type="file" name="image" accept="image/*"></div>
                        </div>
                        <div><label>توضیح کوتاه</label><textarea name="short_description" rows="2" placeholder="خلاصه کوتاه محصول"></textarea></div>
                        <label class="products-toolbar-check"><input type="checkbox" name="manage_stock" value="1" checked><span>مدیریت موجودی فعال باشد</span></label>
                        <button type="submit" class="btn">ثبت کالا</button>
                    </form>
                @else
                    <p style="color:var(--mut); line-height:2;">برای ثبت کالای جدید نیاز به دسترسی «مدیریت سفارش‌ها» داری. لطفاً از مدیر سیستم بخواه.</p>
                @endif
            </article>
        </div>
    </section>
</div>

<script>
function switchProductView(view) {
    document.querySelectorAll('.pi-view-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelector('.pi-view-btn[data-view="' + view + '"]').classList.add('active');
    document.getElementById('productCardView').style.display = (view === 'card') ? '' : 'none';
    document.getElementById('productListView').style.display = (view === 'list') ? '' : 'none';
}
function toggleProductsGroup(btn) {
    var group = btn.closest('.products-group');
    if (!group) return;
    group.classList.toggle('is-collapsed');
    group.classList.toggle('is-open');
}
var quickMetalConfig = {
    gold_jewelry:   { title: 'مشخصات کالای طلا', weightLabel: 'وزن قطعه طلا (گرم)',  profit: '{{ $defaultGoldProfit }}',   tax: '{{ $defaultGoldTax }}' },
    silver_jewelry: { title: 'مشخصات کالای نقره', weightLabel: 'وزن قطعه نقره (گرم)', profit: '{{ $defaultSilverProfit }}', tax: '{{ $defaultSilverTax }}' }
};
function toggleQuickFormula() {
    var select = document.getElementById('quickFormulaType');
    if (!select) return;
    var value = select.value;
    ['quickStandardBox','quickMetalBox','quickMathBox'].forEach(function(id) {
        var box = document.getElementById(id);
        if (box) box.style.display = 'none';
    });
    if (value === 'gold_jewelry' || value === 'silver_jewelry') {
        var box = document.getElementById('quickMetalBox');
        if (box) box.style.display = 'grid';
        var cfg = quickMetalConfig[value];
        if (cfg) {
            document.getElementById('quickMetalTitle').textContent = cfg.title;
            document.getElementById('quickMetalWeightLabel').textContent = cfg.weightLabel;
            document.getElementById('quickMetalProfit').value = cfg.profit;
            document.getElementById('quickMetalTax').value = cfg.tax;
        }
    } else if (value === 'standard_math') {
        var box = document.getElementById('quickMathBox');
        if (box) box.style.display = 'grid';
    } else {
        var box = document.getElementById('quickStandardBox');
        if (box) box.style.display = 'grid';
    }
}
document.addEventListener('DOMContentLoaded', toggleQuickFormula);
function askProductsAssistant(text, btn) {
    text = text || 'یک کالای جدید بساز';
    if (btn) {
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
        btn.innerHTML = '<b style="color:#1d4ed8">در حال باز کردن دستیار...</b><small>لطفاً چند لحظه صبر کنید</small>';
    }
    window.location.href = '{{ url("/app/assistant") }}?prompt=' + encodeURIComponent(text) + '&context_type=products_center&context_title=' + encodeURIComponent('مرکز محصولات و انبار') + '&context_url=' + encodeURIComponent(window.location.href);
}
</script>
@endsection