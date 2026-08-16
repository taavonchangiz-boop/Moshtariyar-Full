<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Modules\Core\Support\Jalali;
use Modules\Core\Entities\Product;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\OrderItem;
use Modules\Core\Entities\Warehouse;
use Modules\Core\Entities\WarehouseProduct;
use Modules\Core\Entities\StockMovement;
use Modules\Core\Services\OrderLifecycleService;
use Modules\Core\Services\ClvAdvancedService;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $this->currentBusinessId();

        $warehouses = Warehouse::query()
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->when($businessId && Schema::hasColumn('products', 'business_id'), fn($q) => $q->where('business_id', $businessId))
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit(200)
            ->get();

        $categories = Product::query()
            ->when($businessId && Schema::hasColumn('products', 'business_id'), fn($q) => $q->where('business_id', $businessId))
            ->selectRaw('COALESCE(category, "متفرقه") as cat')
            ->distinct()
            ->pluck('cat');

        return view('app.quick-sale', compact('warehouses', 'products', 'categories'));
    }

    public function searchProducts(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        $warehouseId = (int) $request->get('warehouse_id', 0);
        $businessId = $this->currentBusinessId();

        $query = Product::query()
            ->when($businessId && Schema::hasColumn('products', 'business_id'), fn($q) => $q->where('business_id', $businessId))
            ->where('is_active', true);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%")
                  ->orWhere('barcode', 'like', "%{$term}%")
                  ->orWhere('category', 'like', "%{$term}%");
            });
        }

        $products = $query->orderBy('name')->limit(50)->get(['id', 'name', 'sku', 'price', 'stock', 'image', 'category']);

        if ($warehouseId) {
            $stockMap = WarehouseProduct::where('warehouse_id', $warehouseId)->whereIn('product_id', $products->pluck('id'))->pluck('stock', 'product_id');
            $products = $products->map(function ($p) use ($stockMap) {
                $p->warehouse_stock = $stockMap[$p->id] ?? $p->stock;
                return $p;
            });
        }

        return response()->json(['products' => $products]);
    }

    public function searchCustomers(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        $businessId = $this->currentBusinessId();

        if (mb_strlen($term) < 2) return response()->json(['customers' => []]);

        $customers = Customer::query()
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orderBy('full_name')
            ->limit(12)
            ->get(['id', 'full_name', 'phone', 'lifetime_value']);

        $customers = $customers->map(function ($c) {
            try {
                $member = \Modules\Loyalty\Entities\LoyaltyMember::where('customer_id', $c->id)->first();
                $c->points = $member?->points ?? 0;
                $c->wallet = $member?->wallet_balance ?? 0;
            } catch (\Throwable $e) {
                $c->points = 0;
                $c->wallet = 0;
            }

            // تحلیل ریسک ریزش در لحظه صندوق - هسته هوشمند نجات
            try {
                if (class_exists(ClvAdvancedService::class)) {
                    $analysis = app(ClvAdvancedService::class)->analyzeCustomer($c);
                    $c->churn_probability = $analysis['churn_probability'] ?? 0;
                    $c->churn_label = $analysis['churn_label'] ?? 'نامشخص';
                    $c->churn_color = $analysis['churn_color'] ?? '#6b7280';
                    $c->recency_days = $analysis['recency_days'] ?? 0;
                    $c->predicted_clv_12m = $analysis['predicted_clv_12m'] ?? 0;
                    $c->segment_label = $analysis['segment_label'] ?? '';
                    $c->segment_key = $analysis['segment_key'] ?? '';
                    $c->avg_order = $analysis['avg_order'] ?? 0;
                    $c->order_count = $analysis['order_count'] ?? 0;

                    $c->is_valuable_at_risk = ($analysis['segment_key'] ?? '') === 'valuable_at_risk';
                    $c->is_at_risk = in_array($analysis['segment_key'] ?? '', ['valuable_at_risk','at_risk','churning']);
                    $c->is_returning = ($analysis['recency_days'] ?? 0) >= 45 && ($analysis['recency_days'] ?? 0) < 9999;

                    try {
                        if (!empty($analysis['last_order'])) {
                            $j = Jalali::fromCarbon($analysis['last_order']);
                            $c->last_order_fa = $j[0].'/'.str_pad($j[1],2,'0',STR_PAD_LEFT).'/'.str_pad($j[2],2,'0',STR_PAD_LEFT);
                        } else {
                            $c->last_order_fa = 'بدون خرید قبلی';
                        }
                    } catch (\Throwable $e) {
                        $c->last_order_fa = '---';
                    }

                    if ($c->is_valuable_at_risk) {
                        $c->suggested_discount = 15;
                        $c->suggested_message = 'مشتری ارزشمند در معرض ریزش - با ۱۵٪ تخفیف ویژه حفظش کنید';
                    } elseif ($c->is_at_risk) {
                        $c->suggested_discount = 10;
                        $c->suggested_message = 'مشتری کم‌فعال در آستانه ریزش - پیشنهاد بازگشت ۱۰٪ بدهید';
                    } elseif ($c->is_returning) {
                        $c->suggested_discount = 0;
                        $c->suggested_message = 'مشتری بعد از '.$c->recency_days.' روز برگشته - امتیاز دو برابر خودکار فعال می‌شود';
                    } else {
                        $c->suggested_discount = 0;
                        $c->suggested_message = '';
                    }

                } else {
                    $c->churn_probability = 0;
                    $c->churn_label = 'نامشخص';
                    $c->churn_color = '#6b7280';
                    $c->recency_days = 0;
                    $c->predicted_clv_12m = 0;
                    $c->segment_label = '';
                    $c->segment_key = '';
                    $c->is_at_risk = false;
                    $c->is_valuable_at_risk = false;
                    $c->is_returning = false;
                    $c->last_order_fa = '---';
                    $c->suggested_discount = 0;
                    $c->suggested_message = '';
                }
            } catch (\Throwable $e) {
                $c->churn_probability = 0;
                $c->churn_label = 'خطا در تحلیل';
                $c->churn_color = '#9ca3af';
                $c->recency_days = 0;
                $c->predicted_clv_12m = 0;
                $c->segment_label = '';
                $c->segment_key = '';
                $c->is_at_risk = false;
                $c->is_valuable_at_risk = false;
                $c->is_returning = false;
                $c->last_order_fa = '---';
                $c->suggested_discount = 0;
                $c->suggested_message = '';
            }

            return $c;
        });

        return response()->json(['customers' => $customers]);
    }

    public function customerRetentionDetail(Customer $customer)
    {
        try {
            if (class_exists(ClvAdvancedService::class)) {
                $analysis = app(ClvAdvancedService::class)->analyzeCustomer($customer);
                $member = \Modules\Loyalty\Entities\LoyaltyMember::where('customer_id', $customer->id)->first();
                try {
                    $j = $analysis['last_order'] ? Jalali::fromCarbon($analysis['last_order']) : null;
                    $lastFa = $j ? $j[0].'/'.str_pad($j[1],2,'0',STR_PAD_LEFT).'/'.str_pad($j[2],2,'0',STR_PAD_LEFT) : 'بدون خرید';
                } catch (\Throwable $e) { $lastFa = '---'; }

                return response()->json([
                    'ok' => true,
                    'customer_id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone' => $customer->phone,
                    'points' => $member?->points ?? 0,
                    'wallet' => $member?->wallet_balance ?? 0,
                    'churn_probability' => $analysis['churn_probability'] ?? 0,
                    'churn_label' => $analysis['churn_label'] ?? '',
                    'churn_color' => $analysis['churn_color'] ?? '#6b7280',
                    'recency_days' => $analysis['recency_days'] ?? 0,
                    'predicted_clv_12m' => $analysis['predicted_clv_12m'] ?? 0,
                    'segment_label' => $analysis['segment_label'] ?? '',
                    'segment_key' => $analysis['segment_key'] ?? '',
                    'avg_order' => $analysis['avg_order'] ?? 0,
                    'order_count' => $analysis['order_count'] ?? 0,
                    'total_spent' => $analysis['total_spent'] ?? 0,
                    'last_order_fa' => $lastFa,
                    'is_valuable_at_risk' => ($analysis['segment_key'] ?? '') === 'valuable_at_risk',
                    'is_at_risk' => in_array($analysis['segment_key'] ?? '', ['valuable_at_risk','at_risk','churning']),
                    'is_returning' => ($analysis['recency_days'] ?? 0) >= 45,
                ]);
            }
        } catch (\Throwable $e) {}
        return response()->json(['ok' => false]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:191',
            'customer_phone' => 'nullable|string|max:30',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1|max:100000',
            'items.*.price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_value' => 'nullable|numeric|min:0|max:1000000000',
            'use_points' => 'nullable|integer|min:0',
            'use_wallet' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,transfer,combined',
            'note' => 'nullable|string|max:1000',
        ], [
            'warehouse_id.required' => 'انبار را انتخاب کنید',
            'items.required' => 'سبد خرید خالی است',
        ]);

        $businessId = $this->currentBusinessId();

        try {
            $order = DB::transaction(function () use ($data, $businessId) {
                $customer = $this->resolveCustomer($data);
                $subtotal = 0;
                foreach ($data['items'] as $item) { $subtotal += (float) $item['price'] * (int) $item['qty']; }
                $discount = 0;
                if (!empty($data['discount_value'])) {
                    if (($data['discount_type'] ?? 'fixed') === 'percent') { $discount = $subtotal * ((float) $data['discount_value'] / 100); }
                    else { $discount = (float) $data['discount_value']; }
                }
                $discount = min($discount, $subtotal);
                $afterDiscount = $subtotal - $discount;
                $pointsUsed = (int) ($data['use_points'] ?? 0);
                $walletUsed = (float) ($data['use_wallet'] ?? 0);

                if ($customer && $pointsUsed > 0) {
                    $member = \Modules\Loyalty\Entities\LoyaltyMember::where('customer_id', $customer->id)->first();
                    if ($member && $member->points >= $pointsUsed) {
                        app(\Modules\Loyalty\Services\LoyaltyService::class)->addPoints($member, -$pointsUsed, 'استفاده در صندوق فروش سریع', 'pos', null);
                    } else { $pointsUsed = 0; }
                }
                if ($customer && $walletUsed > 0) {
                    $member = \Modules\Loyalty\Entities\LoyaltyMember::where('customer_id', $customer->id)->first();
                    if ($member && $member->wallet_balance >= $walletUsed) {
                        app(\Modules\Loyalty\Services\LoyaltyService::class)->adjustWallet($member, -$walletUsed, 'استفاده در صندوق فروش سریع', 'pos', null);
                    } else { $walletUsed = 0; }
                }
                $payable = max(0, $afterDiscount - $walletUsed - $pointsUsed);

                $order = Order::create([
                    'business_id' => $businessId,
                    'customer_id' => $customer?->id,
                    'number' => 'POS-' . Jalali::date(Carbon::now(), 'Ymd') . '-' . random_int(1000, 9999),
                    'status' => 'completed',
                    'total' => $payable,
                    'tax_total' => 0,
                    'currency' => 'IRT',
                    'source' => 'pos',
                    'placed_at' => now(),
                    'meta' => [
                        'pos' => true,
                        'warehouse_id' => $data['warehouse_id'],
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'discount_type' => $data['discount_type'] ?? 'fixed',
                        'discount_value' => $data['discount_value'] ?? 0,
                        'points_used' => $pointsUsed,
                        'wallet_used' => $walletUsed,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'note' => $data['note'] ?? null,
                    ],
                ]);

                foreach ($data['items'] as $item) {
                    $productId = (int) $item['id'];
                    $qty = (int) $item['qty'];
                    $price = (float) $item['price'];
                    OrderItem::create(['order_id' => $order->id,'product_id' => $productId,'name' => Product::find($productId)?->name ?? 'محصول','sku' => Product::find($productId)?->sku,'qty' => $qty,'unit_price' => $price,'line_total' => $price * $qty,'meta' => ['warehouse_id' => $data['warehouse_id']],]);
                    $wp = WarehouseProduct::where('warehouse_id', $data['warehouse_id'])->where('product_id', $productId)->first();
                    if ($wp) {
                        if ($wp->stock < $qty) throw new \Exception('موجودی محصول ' . ($wp->product->name ?? '') . ' در انبار انتخابی کافی نیست');
                        $wp->decrement('stock', $qty);
                        StockMovement::create(['business_id' => $businessId,'product_id' => $productId,'warehouse_id' => $data['warehouse_id'],'type' => 'out','qty' => $qty,'balance_after' => $wp->fresh()->stock,'reason' => 'فروش صندوق سریع - سفارش ' . $order->number,'user_id' => Auth::id(),]);
                    }
                    $product = Product::find($productId);
                    if ($product) { $product->decrement('stock', $qty); }
                }

                $this->addToOpenShift($order, $data['payment_method'] ?? 'cash', $discount);
                if ($customer) { try { $loyalty = app(\Modules\Loyalty\Services\LoyaltyService::class); $loyalty->onPurchase($order); } catch (\Throwable $e) {} }
                return $order;
            });

            return response()->json(['ok' => true,'message' => 'سفارش با موفقیت ثبت شد','order_id' => $order->id,'order_number' => $order->number,'url' => url('/app/orders/' . $order->id),'thermal_url' => url('/app/pos/' . $order->id . '/thermal'),]);

        } catch (\Throwable $e) {
            return response()->json(['ok' => false,'message' => 'خطا در ثبت سفارش: ' . $e->getMessage(),], 422);
        }
    }

    public function thermal(Order $order)
    {
        $order->load(['customer', 'items.product']);
        $businessName = \Modules\Core\Entities\Setting::get('business_name', 'فروشگاه');
        $businessPhone = \Modules\Core\Entities\Setting::get('business_phone', '');
        $businessAddress = \Modules\Core\Entities\Setting::get('business_address', '');
        $invoiceFooter = \Modules\Core\Entities\Setting::get('invoice_footer_text', 'با تشکر از خرید شما');
        return view('app.pos.thermal', compact('order', 'businessName', 'businessPhone', 'businessAddress', 'invoiceFooter'));
    }

    public function cashierReport(Request $request)
    {
        $businessId = $this->currentBusinessId();
        $userId = Auth::id();
        $openShift = \Modules\Core\Entities\PosShift::where('user_id', $userId)->where('status', 'open')->latest('opened_at')->first();
        $today = Carbon::today();
        $todayOrders = Order::where('source', 'pos')->when($businessId, fn($q) => $q->where('business_id', $businessId))->whereDate('placed_at', $today)->where('status', 'completed')->get();
        $stats = [
            'total_sales' => (float) $todayOrders->sum('total'),
            'orders_count' => $todayOrders->count(),
            'cash_sales' => (float) $todayOrders->filter(fn($o) => ($o->meta['payment_method'] ?? 'cash') === 'cash')->sum('total'),
            'card_sales' => (float) $todayOrders->filter(fn($o) => ($o->meta['payment_method'] ?? '') === 'card')->sum('total'),
            'transfer_sales' => (float) $todayOrders->filter(fn($o) => ($o->meta['payment_method'] ?? '') === 'transfer')->sum('total'),
            'total_discount' => (float) $todayOrders->sum(fn($o) => $o->meta['discount'] ?? 0),
        ];
        $shifts = \Modules\Core\Entities\PosShift::when($businessId, fn($q) => $q->where('business_id', $businessId))->latest('opened_at')->limit(20)->get();
        return view('app.pos.cashier', compact('openShift', 'stats', 'shifts', 'todayOrders'));
    }

    public function openShift(Request $request)
    {
        $data = $request->validate(['warehouse_id' => 'nullable|exists:warehouses,id','opening_cash' => 'required|numeric|min:0','note' => 'nullable|string|max:500',]);
        $businessId = $this->currentBusinessId();
        $userId = Auth::id();
        $existingOpen = \Modules\Core\Entities\PosShift::where('user_id', $userId)->where('status', 'open')->first();
        if ($existingOpen) { return back()->with('error', 'شما یک شیفت باز دارید - ابتدا آن را ببندید'); }
        \Modules\Core\Entities\PosShift::create(['business_id' => $businessId,'user_id' => $userId,'warehouse_id' => $data['warehouse_id'] ?? null,'opened_at' => now(),'opening_cash' => $data['opening_cash'],'status' => 'open','note' => $data['note'] ?? null,]);
        return back()->with('status', 'شیفت جدید با موفقیت باز شد');
    }

    public function closeShift(Request $request, \Modules\Core\Entities\PosShift $shift)
    {
        $data = $request->validate(['closing_cash' => 'required|numeric|min:0','note' => 'nullable|string|max:500',]);
        if ($shift->user_id !== Auth::id() && !Auth::user()->isSuperAdmin()) { return back()->with('error', 'شما اجازه بستن این شیفت را ندارید'); }
        if ($shift->status === 'closed') { return back()->with('error', 'این شیفت قبلا بسته شده'); }
        $businessId = $this->currentBusinessId();
        $orders = Order::where('source', 'pos')->when($businessId, fn($q) => $q->where('business_id', $businessId))->where('created_at', '>=', $shift->opened_at)->where('status', 'completed')->get();
        $shift->update(['closed_at' => now(),'closing_cash' => $data['closing_cash'],'cash_sales' => (float) $orders->filter(fn($o) => ($o->meta['payment_method'] ?? 'cash') === 'cash')->sum('total'),'card_sales' => (float) $orders->filter(fn($o) => ($o->meta['payment_method'] ?? '') === 'card')->sum('total'),'transfer_sales' => (float) $orders->filter(fn($o) => in_array($o->meta['payment_method'] ?? '', ['transfer', 'combined']))->sum('total'),'total_sales' => (float) $orders->sum('total'),'total_discount' => (float) $orders->sum(fn($o) => $o->meta['discount'] ?? 0),'orders_count' => $orders->count(),'status' => 'closed','note' => $data['note'] ?? $shift->note,]);
        return back()->with('status', 'شیفت با موفقیت بسته شد - گزارش نهایی ثبت شد');
    }

    protected function addToOpenShift(Order $order, string $paymentMethod, float $discount): void
    {
        try {
            $openShift = \Modules\Core\Entities\PosShift::where('user_id', Auth::id())->where('status', 'open')->latest('opened_at')->first();
            if (!$openShift) return;
            $field = match ($paymentMethod) {
                'cash' => 'cash_sales',
                'card' => 'card_sales',
                'transfer', 'combined' => 'transfer_sales',
                default => 'cash_sales',
            };
            $openShift->increment($field, $order->total);
            $openShift->increment('total_sales', $order->total);
            $openShift->increment('total_discount', $discount);
            $openShift->increment('orders_count', 1);
        } catch (\Throwable $e) {}
    }

    private function resolveCustomer(array $data): ?Customer
    {
        if (!empty($data['customer_id'])) { return Customer::find((int) $data['customer_id']); }
        $phone = trim((string) ($data['customer_phone'] ?? ''));
        $name = trim((string) ($data['customer_name'] ?? ''));
        if ($phone !== '') {
            $existing = Customer::where('phone', $phone)->first();
            if ($existing) {
                if ($name !== '' && $existing->full_name !== $name) $existing->update(['full_name' => $name]);
                return $existing;
            }
        }
        if ($name !== '' || $phone !== '') {
            return Customer::create(['business_id' => $this->currentBusinessId(),'type' => 'individual','full_name' => $name !== '' ? $name : 'مشتری صندوق','phone' => $phone !== '' ? $phone : null,'source' => 'pos','lifetime_value' => 0,]);
        }
        $businessId = $this->currentBusinessId();
        $walkIn = Customer::where('full_name', 'مشتری متفرقه')->when($businessId, fn($q) => $q->where('business_id', $businessId))->first();
        if ($walkIn) return $walkIn;
        return Customer::create(['business_id' => $businessId,'type' => 'individual','full_name' => 'مشتری متفرقه','phone' => null,'source' => 'pos','lifetime_value' => 0,]);
    }

    protected function currentBusinessId(): ?int
    {
        try {
            $user = Auth::user();
            if (!$user) return null;
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return null;
            return $user->business_id ?? null;
        } catch (\Throwable $e) { return null; }
    }
}