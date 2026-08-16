<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\OrderItem;
use Modules\Core\Entities\Product;
use Modules\Core\Entities\Setting;
use Modules\Core\Services\OrderLifecycleService;
use Modules\Core\Services\ImageOptimizerService;
use Modules\Core\Support\Money;
use Modules\IranPack\FinTech\GoldPriceFeeder;
use Modules\WooBridge\Entities\IdMap;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Services\OutboundSync;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(fn ($row) => $row
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%"));
        }

        if ($request->filled('category')) {
            $query->where('category', (string) $request->get('category'));
        }

        if ($request->filled('formula_type')) {
            $query->where('formula_type', (string) $request->get('formula_type'));
        }

        if ($request->get('low')) {
            $query->whereNotNull('stock')->whereColumn('stock', '<=', 'min_stock');
        }

        if ($request->get('formula')) {
            $query->where('is_formula_based', true);
        }

        $products = $query->orderBy('name')->paginate(48)->withQueryString();
        $products->getCollection()->each(function (Product $product) {
            $product->board_bucket = $this->productBoardBucket($product);
        });

        $productGroups = $products->getCollection()->groupBy('board_bucket');

        $lowCount = Product::whereNotNull('stock')->whereColumn('stock', '<=', 'min_stock')->count();
        $unitLabel = Money::unitLabel();
        $customFormulaFields = $this->customFormulaFields();
        $defaultGoldProfit = (float) Setting::get('gold_default_profit', '0.07');
        $defaultGoldTax = (float) Setting::get('gold_default_tax', Setting::get('vat_rate', '0.10'));
        $defaultSilverProfit = (float) Setting::get('silver_default_profit', '0.07');
        $defaultSilverTax = (float) Setting::get('silver_default_tax', Setting::get('vat_rate', '0.10'));

        $categories = Product::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $summary = [
            'total' => Product::count(),
            'low' => $lowCount,
            'formula' => Product::where('is_formula_based', true)->count(),
            'active' => Product::where('is_active', true)->count(),
            'stock_value' => (float) Product::query()->selectRaw('COALESCE(SUM(COALESCE(stock,0) * COALESCE(price,0)),0) as total')->value('total'),
        ];

        return view('app.products', compact(
            'products',
            'productGroups',
            'lowCount',
            'unitLabel',
            'customFormulaFields',
            'defaultGoldProfit',
            'defaultGoldTax',
            'defaultSilverProfit',
            'defaultSilverTax',
            'categories',
            'summary'
        ));
    }

    public function store(Request $request, ImageOptimizerService $images)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'sku' => 'nullable|string|max:80',
            'slug' => 'nullable|string|max:191',
            'category' => 'nullable|string|max:80',
            'product_type' => 'required|string|max:40',
            'status' => 'required|string|max:30',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:10000',
            'price' => 'nullable|numeric',
            'regular_price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric',
            'tax_rate' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
            'manage_stock' => 'nullable|boolean',
            'stock_status' => 'nullable|string|max:30',
            'formula_type' => 'required|in:standard,standard_math,gold_jewelry,silver_jewelry',
            'formula' => 'nullable|string',
            'live_gold_weight' => 'nullable|numeric',
            'live_gold_ajrat' => 'nullable|numeric',
            'live_gold_profit' => 'nullable|numeric',
            'metal_tax_rate' => 'nullable|numeric',
            'attributes' => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:5120',
            'gallery.*' => 'nullable|image|max:5120',
        ]);

        $isFormula = $data['formula_type'] !== 'standard';
        $variables = null;

        if ($data['formula_type'] === 'standard_math' && ! empty($data['formula'])) {
            preg_match_all('/[\p{Arabic}\p{L}_][\p{Arabic}\p{L}\p{N}_]*/u', $data['formula'], $matches);
            $extracted = array_values(array_unique($matches[0] ?? []));
            $variables = [];
            foreach ($extracted as $name) {
                $variables[$name] = 1.0;
            }
        }

        $defaultTax = $data['formula_type'] === 'silver_jewelry'
            ? (float) Setting::get('silver_default_tax', Setting::get('vat_rate', '0.10'))
            : (float) Setting::get('gold_default_tax', Setting::get('vat_rate', '0.10'));

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $images->storeAsWebp($request->file('image'), 'img/products');
        }

        $gallery = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                if ($file) {
                    $gallery[] = $images->storeAsWebp($file, 'img/products/gallery');
                }
            }
        }

        $attributes = $this->parseAttributes($data['attributes'] ?? null);
        $regularPrice = (float) ($data['regular_price'] ?? $data['price'] ?? 0);
        $salePrice = isset($data['sale_price']) && $data['sale_price'] !== '' ? (float) $data['sale_price'] : null;
        $finalPrice = $salePrice && $salePrice > 0 ? $salePrice : $regularPrice;

        Product::create([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'category' => $data['category'] ?? null,
            'product_type' => $data['product_type'] ?? 'simple',
            'status' => $data['status'] ?? 'publish',
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $imagePath,
            'gallery' => $gallery ?: null,
            'attributes' => $attributes ?: null,
            'price' => $finalPrice,
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,
            'cost_price' => $data['cost_price'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'stock' => $data['stock'] ?? null,
            'min_stock' => $data['min_stock'] ?? null,
            'manage_stock' => $request->boolean('manage_stock', true),
            'stock_status' => $data['stock_status'] ?? (((int) ($data['stock'] ?? 0)) > 0 ? 'instock' : 'outofstock'),
            'source' => 'manual',
            'is_formula_based' => $isFormula,
            'formula_type' => $data['formula_type'],
            'formula' => $data['formula'] ?? null,
            'formula_variables' => $variables,
            'live_gold_weight' => empty($data['live_gold_weight']) ? null : (float) $data['live_gold_weight'],
            'live_gold_ajrat' => empty($data['live_gold_ajrat']) ? null : (float) $data['live_gold_ajrat'],
            'live_gold_profit' => empty($data['live_gold_profit'])
                ? ($data['formula_type'] === 'silver_jewelry'
                    ? (float) Setting::get('silver_default_profit', '0.07')
                    : (float) Setting::get('gold_default_profit', '0.07'))
                : (float) $data['live_gold_profit'],
            'meta' => [
                'metal_tax_rate' => empty($data['metal_tax_rate']) ? $defaultTax : (float) $data['metal_tax_rate'],
            ],
            'is_active' => ($data['status'] ?? 'publish') === 'publish',
        ]);

        return back()->with('status', 'کالا با موفقیت ثبت شد.');
    }

    public function show(Product $product)
    {
        $product->load('movements');

        $calcResult = $product->calculateFinalPrice();
        $liveGoldPrice = GoldPriceFeeder::getLiveGoldPrice18k();
        $liveSilverPrice = GoldPriceFeeder::getLiveSilverPrice();
        $unitLabel = Money::unitLabel();
        $customFormulaFields = $this->customFormulaFields();
        $customers = Customer::orderBy('full_name')->limit(200)->get(['id', 'full_name', 'phone']);
        $priceSourceLabel = GoldPriceFeeder::sourceModeLabel();

        return view('app.product_show', compact(
            'product',
            'calcResult',
            'liveGoldPrice',
            'liveSilverPrice',
            'unitLabel',
            'customFormulaFields',
            'customers',
            'priceSourceLabel'
        ));
    }



    public function edit(Product $product)
    {
        $unitLabel = Money::unitLabel();
        $customFormulaFields = $this->customFormulaFields();
        $defaultGoldProfit = (float) Setting::get('gold_default_profit', '0.07');
        $defaultGoldTax = (float) Setting::get('gold_default_tax', Setting::get('vat_rate', '0.10'));
        $defaultSilverProfit = (float) Setting::get('silver_default_profit', '0.07');
        $defaultSilverTax = (float) Setting::get('silver_default_tax', Setting::get('vat_rate', '0.10'));

        $categories = Product::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $wooMaps = IdMap::query()
            ->where('entity', 'product')
            ->where('crm_id', $product->id)
            ->get();

        $wooConnections = WooConnection::query()
            ->whereIn('id', $wooMaps->pluck('connection_id')->unique()->values())
            ->orderBy('name')
            ->get(['id', 'name', 'store_url', 'is_active', 'last_sync_at']);

        return view('app.products.edit', compact(
            'product',
            'unitLabel',
            'customFormulaFields',
            'defaultGoldProfit',
            'defaultGoldTax',
            'defaultSilverProfit',
            'defaultSilverTax',
            'categories',
            'wooMaps',
            'wooConnections'
        ));
    }

    public function update(Request $request, Product $product, ImageOptimizerService $images, OutboundSync $outbound)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'sku' => 'nullable|string|max:80',
            'slug' => 'nullable|string|max:191',
            'category' => 'nullable|string|max:191',
            'product_type' => 'required|string|max:40',
            'status' => 'required|string|max:30',
            'short_description' => 'nullable|string|max:3000',
            'description' => 'nullable|string|max:30000',
            'price' => 'nullable|numeric|min:0',
            'regular_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer|min:0',
            'manage_stock' => 'nullable|boolean',
            'stock_status' => 'required|string|max:30',
            'formula_type' => 'required|in:standard,standard_math,gold_jewelry,silver_jewelry',
            'formula' => 'nullable|string|max:2000',
            'live_gold_weight' => 'nullable|numeric|min:0',
            'live_gold_ajrat' => 'nullable|numeric|min:0',
            'live_gold_profit' => 'nullable|numeric|min:0',
            'metal_tax_rate' => 'nullable|numeric|min:0',
            'attributes' => 'nullable|string|max:10000',
            'external_url' => 'nullable|url|max:500',
            'image' => 'nullable|image|max:5120',
            'gallery.*' => 'nullable|image|max:5120',
            'remove_image' => 'nullable|boolean',
            'remove_gallery' => 'nullable|array',
            'remove_gallery.*' => 'nullable|string|max:1000',
        ]);

        $formulaType = $data['formula_type'];
        $isFormula = $formulaType !== 'standard';
        $variables = null;

        if ($formulaType === 'standard_math' && ! empty($data['formula'])) {
            preg_match_all('/[\p{Arabic}\p{L}_][\p{Arabic}\p{L}\p{N}_]*/u', $data['formula'], $matches);
            $extracted = array_values(array_unique($matches[0] ?? []));
            $variables = [];
            foreach ($extracted as $name) {
                $variables[$name] = (float) data_get($product->formula_variables, $name, 1.0);
            }
        }

        $defaultTax = $formulaType === 'silver_jewelry'
            ? (float) Setting::get('silver_default_tax', Setting::get('vat_rate', '0.10'))
            : (float) Setting::get('gold_default_tax', Setting::get('vat_rate', '0.10'));

        $imagePath = $product->image;
        if ($request->boolean('remove_image')) {
            $imagePath = null;
        }
        if ($request->hasFile('image')) {
            $imagePath = $images->storeAsWebp($request->file('image'), 'img/products');
        }

        $gallery = is_array($product->gallery) ? array_values($product->gallery) : [];
        $removeGallery = array_values(array_filter((array) $request->input('remove_gallery', [])));
        if ($removeGallery) {
            $gallery = array_values(array_filter($gallery, fn ($item) => ! in_array((string) $item, $removeGallery, true)));
        }
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                if ($file) {
                    $gallery[] = $images->storeAsWebp($file, 'img/products/gallery');
                }
            }
        }

        $attributes = $this->parseAttributes($data['attributes'] ?? null);
        $regularPrice = isset($data['regular_price']) && $data['regular_price'] !== '' ? (float) $data['regular_price'] : 0;
        $salePrice = isset($data['sale_price']) && $data['sale_price'] !== '' ? (float) $data['sale_price'] : null;
        $manualPrice = isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : 0;
        $finalPrice = $salePrice && $salePrice > 0 ? $salePrice : ($regularPrice > 0 ? $regularPrice : $manualPrice);

        $meta = is_array($product->meta) ? $product->meta : [];
        $meta['metal_tax_rate'] = isset($data['metal_tax_rate']) && $data['metal_tax_rate'] !== ''
            ? (float) $data['metal_tax_rate']
            : $defaultTax;

        $product->update([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'slug' => ! empty($data['slug']) ? $data['slug'] : Str::slug($data['name']),
            'category' => $data['category'] ?? null,
            'product_type' => $data['product_type'] ?? 'simple',
            'status' => $data['status'] ?? 'publish',
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $imagePath,
            'gallery' => $gallery ?: null,
            'attributes' => $attributes ?: null,
            'price' => $finalPrice,
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,
            'cost_price' => $data['cost_price'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'stock' => isset($data['stock']) && $data['stock'] !== '' ? (int) $data['stock'] : null,
            'min_stock' => isset($data['min_stock']) && $data['min_stock'] !== '' ? (int) $data['min_stock'] : 0,
            'manage_stock' => $request->boolean('manage_stock'),
            'stock_status' => $data['stock_status'] ?? 'instock',
            'external_url' => array_key_exists('external_url', $data) ? $data['external_url'] : $product->external_url,
            'is_formula_based' => $isFormula,
            'formula_type' => $formulaType,
            'formula' => $formulaType === 'standard_math' ? ($data['formula'] ?? null) : null,
            'formula_variables' => $variables,
            'live_gold_weight' => empty($data['live_gold_weight']) ? null : (float) $data['live_gold_weight'],
            'live_gold_ajrat' => empty($data['live_gold_ajrat']) ? null : (float) $data['live_gold_ajrat'],
            'live_gold_profit' => empty($data['live_gold_profit'])
                ? ($formulaType === 'silver_jewelry'
                    ? (float) Setting::get('silver_default_profit', '0.07')
                    : (float) Setting::get('gold_default_profit', '0.07'))
                : (float) $data['live_gold_profit'],
            'meta' => $meta,
            'is_active' => ($data['status'] ?? 'publish') === 'publish',
        ]);

        if ($request->input('save_action') === 'save_and_push') {
            $hasMap = IdMap::where('entity', 'product')->where('crm_id', $product->id)->exists();
            if (! $hasMap) {
                return redirect('/app/products/' . $product->id)->with('status', 'تغییرات محصول ذخیره شد، اما این محصول هنوز به محصولی در فروشگاه وصل نیست.');
            }

            try {
                $outbound->pushProduct($product->fresh());
                return redirect('/app/products/' . $product->id)->with('status', 'تغییرات محصول ذخیره و به فروشگاه ارسال شد. نتیجه دقیق در لاگ همگام‌سازی ثبت شده است.');
            } catch (\Throwable $e) {
                return redirect('/app/products/' . $product->id)->with('status', 'تغییرات محصول ذخیره شد، اما ارسال به فروشگاه ناموفق بود: ' . $e->getMessage());
            }
        }

        return redirect('/app/products/' . $product->id)->with('status', 'تغییرات محصول با موفقیت ذخیره شد.');
    }


    public function pushToWooCommerce(Request $request, Product $product, OutboundSync $outbound)
    {
        $hasMap = IdMap::where('entity', 'product')
            ->where('crm_id', $product->id)
            ->exists();

        if (! $hasMap) {
            return back()->with('status', 'این محصول هنوز به محصولی در فروشگاه وصل نیست. ابتدا محصول باید از فروشگاه خوانده شود یا شناسه اتصال داشته باشد.');
        }

        try {
            $outbound->pushProduct($product->fresh());
            return back()->with('status', 'ارسال فوری محصول به فروشگاه انجام شد. نتیجه دقیق را می‌توانید در لاگ همگام‌سازی ببینید.');
        } catch (\Throwable $e) {
            return back()->with('status', 'ارسال فوری محصول به فروشگاه ناموفق بود: ' . $e->getMessage());
        }
    }

    public function calculateAjax(Request $request, Product $product)
    {
        return response()->json($product->calculateFinalPrice($request->all()));
    }

    public function createQuickOrder(Request $request, Product $product)
    {
        try {
            // ۱. اعتبارسنجی نرم (بدون Crash کردن سیستم)
            $validator = Validator::make($request->all(), [
                'customer_id' => 'nullable|integer',
                'customer_name' => 'nullable|string|max:191',
                'customer_phone' => 'nullable|string|max:32',
                'qty' => 'required|integer|min:1|max:9999',
                'weight' => 'nullable|numeric',
                'ajrat' => 'nullable|numeric',
                'profit_rate' => 'nullable|numeric',
                'tax_rate' => 'nullable|numeric',
                'formula_inputs_json' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return back()->with('error', 'خطا در ثبت سفارش: لطفاً تعداد کالا را وارد کنید و مشتری را انتخاب نمایید.')
                             ->withErrors($validator);
            }

            $data = $validator->validated();
            $qty = max(1, (int) $data['qty']);
            $customer = $this->resolveCustomer($data);

            if (! $customer) {
                return back()->with('error', 'لطفاً یک مشتری انتخاب کنید یا نام و شماره او را وارد کنید.');
            }

            $calculation = $this->buildCalculationSnapshot($product, $request);
            $unitPrice = (float) $calculation['unit_price'];
            $lineTotal = (float) $calculation['line_total'] * $qty;
            $taxTotal = (float) $calculation['tax_total'] * $qty;
            $snapshot = $calculation['snapshot'];
            $snapshot['تعداد'] = $qty;
            $snapshot['مبلغ_واحد'] = $unitPrice;
            $snapshot['مبلغ_کل'] = $lineTotal;
            $snapshot['مالیات_کل'] = $taxTotal;
            $snapshot['زمان_ثبت'] = now()->toDateTimeString();
            $snapshot['واحد_پول'] = Money::unitLabel();

            $order = DB::transaction(function () use ($customer, $product, $qty, $unitPrice, $lineTotal, $taxTotal, $snapshot) {
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'number' => null,
                    'status' => 'processing',
                    'total' => $lineTotal,
                    'tax_total' => $taxTotal,
                    'currency' => Money::unit() === 'rial' ? 'IRR' : 'IRT',
                    'source' => 'manual_pricing',
                    'placed_at' => now(),
                ]);

                $order->update([
                    'number' => now()->format('Ymd') . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'meta' => $snapshot,
                ]);

                return $order;
            });

            // اجرای چرخه حیات سفارش (اینجاست که احتمالاً خطا رخ می‌دهد)
            app(OrderLifecycleService::class)->sync($order);

            $redirectUrl = '/app/orders/' . $order->id;
            if ($request->boolean('embed')) {
                $redirectUrl .= '?embed=1';
            }

            return redirect($redirectUrl)->with('status', 'سفارش با موفقیت ثبت شد، مبلغ آن قفل شد و موجودی هم به‌روز شد.');

        } catch (Exception $e) {
            // اگر هر خطایی رخ داد، به جای صفحه سفید ۵۰۰، پیام خطا را برمی‌گردانیم
            return back()->with('error', 'خطای سیستمی هنگام ثبت سفارش: ' . $e->getMessage());
        }
    }

    public function move(Request $request, Product $product)
    {
        $data = $request->validate([
            'type'   => 'required|in:in,out,adjust',
            'qty'    => 'required|integer',
            'reason' => 'nullable|string|max:120',
        ]);

        $product->applyMovement($data['type'], $data['qty'], $data['reason'] ?? null, optional($request->user())->id);

        return back()->with('status', 'موجودی انبار به‌روز شد.');
    }

    private function resolveCustomer(array $data): ?Customer
    {
        if (! empty($data['customer_id'])) {
            $customer = Customer::find((int) $data['customer_id']);
            if ($customer) {
                return $customer;
            }
        }

        $phone = trim((string) ($data['customer_phone'] ?? ''));
        $name = trim((string) ($data['customer_name'] ?? ''));

        if ($phone !== '') {
            $existing = Customer::where('phone', $phone)->first();
            if ($existing) {
                if ($name !== '' && $existing->full_name !== $name) {
                    $existing->update(['full_name' => $name]);
                }
                return $existing;
            }
        }

        if ($name === '' && $phone === '') {
            return null;
        }

        return Customer::create([
            'type' => 'individual',
            'full_name' => $name !== '' ? $name : 'مشتری بدون نام',
            'phone' => $phone !== '' ? $phone : null,
            'source' => 'manual',
            'lifetime_value' => 0,
        ]);
    }

    private function buildCalculationSnapshot(Product $product, Request $request): array
    {
        $qty = max(1, (int) $request->input('qty', 1));

        if (in_array($product->formula_type, ['gold_jewelry', 'silver_jewelry'], true)) {
            $inputs = [
                'weight' => (float) $request->input('weight', $product->live_gold_weight ?? 0),
                'ajrat' => (float) $request->input('ajrat', $product->live_gold_ajrat ?? 0),
                'profit_rate' => (float) $request->input('profit_rate', $product->live_gold_profit ?? 0.07),
                'tax_rate' => (float) $request->input('tax_rate', data_get($product->meta, 'metal_tax_rate', 0.10)),
            ];
            $result = $product->calculateFinalPrice($inputs);
            $baseAmount = ($result['raw_metal_value'] ?? 0) + ($result['ajrat_cost'] ?? 0) + ($result['store_profit'] ?? 0);

            return [
                'unit_price' => (float) ($result['final_price'] ?? 0),
                'line_total' => (float) ($result['final_price'] ?? 0),
                'tax_total' => (float) ($result['tax_amount'] ?? 0),
                'snapshot' => [
                    'نوع_محاسبه' => ($result['metal_name'] ?? 'فلز') . ' با نرخ روز',
                    'نام_فلز' => $result['metal_name'] ?? 'فلز',
                    'وزن' => $result['weight_grams'] ?? 0,
                    'نرخ_روز' => $result['live_metal_rate'] ?? 0,
                    'ارزش_خام' => $result['raw_metal_value'] ?? 0,
                    'اجرت' => $result['ajrat_cost'] ?? 0,
                    'سود' => $result['store_profit'] ?? 0,
                    'مالیات' => $result['tax_amount'] ?? 0,
                    'مبلغ_پایه' => $baseAmount,
                    'مبلغ_نهایی_واحد' => $result['final_price'] ?? 0,
                ],
            ];
        }

        if ($product->is_formula_based && ! empty($product->formula)) {
            $formulaInputs = json_decode((string) $request->input('formula_inputs_json', '{}'), true);
            $formulaInputs = is_array($formulaInputs) ? $formulaInputs : [];
            $result = $product->calculateFinalPrice($formulaInputs);

            return [
                'unit_price' => (float) ($result['final_price'] ?? 0),
                'line_total' => (float) ($result['final_price'] ?? 0),
                'tax_total' => 0,
                'snapshot' => [
                    'نوع_محاسبه' => 'کالای محاسباتی',
                    'فرمول' => $product->formula,
                    'فرمول_نهایی' => $result['evaluated'] ?? '',
                    'ورودی‌ها' => $formulaInputs,
                    'مبلغ_نهایی_واحد' => $result['final_price'] ?? 0,
                ],
            ];
        }

        $unitPrice = (float) $product->price;

        return [
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice,
            'tax_total' => 0,
            'snapshot' => [
                'نوع_محاسبه' => 'قیمت ثابت',
                'مبلغ_نهایی_واحد' => $unitPrice,
            ],
        ];
    }

        private function parseAttributes(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $rows = preg_split('/[\r\n]+/u', $raw) ?: [];
        $attributes = [];

        foreach ($rows as $row) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }

            [$name, $values] = array_pad(preg_split('/[:：]/u', $row, 2), 2, '');
            $name = trim($name);
            $values = trim($values);

            if ($name === '') {
                continue;
            }

            $attributes[] = [
                'name' => $name,
                'options' => array_values(array_filter(array_map('trim', preg_split('/[,،|]+/u', $values) ?: []))),
                'visible' => true,
                'variation' => false,
            ];
        }

        return $attributes;
    }

    private function productBoardBucket(Product $product): string
    {
        if (method_exists($product, 'isLowStock') && $product->isLowStock()) {
            return 'low';
        }

        if (in_array($product->formula_type, ['gold_jewelry', 'silver_jewelry'], true)) {
            return 'metal';
        }

        if ($product->is_formula_based) {
            return 'formula';
        }

        return 'standard';
    }

    private function customFormulaFields(): array
    {
        $raw = (string) Setting::get('product_formula_fields', "طول\nعرض\nارتفاع\nتعداد\nمساحت");
        $items = preg_split('/[\r\n,،]+/u', $raw) ?: [];
        $items = array_values(array_filter(array_map(fn ($item) => trim((string) $item), $items)));

        return $items ?: ['طول', 'عرض', 'ارتفاع', 'تعداد', 'مساحت'];
    }
}

