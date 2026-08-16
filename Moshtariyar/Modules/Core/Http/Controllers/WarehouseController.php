<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Modules\Core\Support\Jalali;
use Modules\Core\Entities\Warehouse;
use Modules\Core\Entities\WarehouseProduct;
use Modules\Core\Entities\Product;
use Modules\Core\Entities\Supplier;
use Modules\Core\Entities\Supply;
use Modules\Core\Entities\StockMovement;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $this->currentBusinessId();

        $warehouses = Warehouse::query()
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->withCount(['products as total_items' => fn($q) => $q->where('stock', '>', 0)])
            ->with(['products' => fn($q) => $q->with('product')->latest('id')->limit(30)])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::query()
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get();

        $products = Product::query()
            ->when($businessId && Schema::hasColumn('products', 'business_id'), fn($q) => $q->where('business_id', $businessId))
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'sku', 'stock']);

        $summary = [
            'warehouses' => $warehouses->count(),
            'total_stock' => (int) Product::when($businessId && Schema::hasColumn('products', 'business_id'), fn($q) => $q->where('business_id', $businessId))->sum('stock'),
            'low_stock' => (int) Product::when($businessId && Schema::hasColumn('products', 'business_id'), fn($q) => $q->where('business_id', $businessId))->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0)->count(),
            'stock_value' => (float) Product::when($businessId && Schema::hasColumn('products', 'business_id'), fn($q) => $q->where('business_id', $businessId))->selectRaw('SUM(stock * COALESCE(price,0)) as v')->value('v'),
            'suppliers' => $suppliers->count(),
        ];

        $recentMovements = StockMovement::query()
            ->when($businessId && Schema::hasColumn('stock_movements', 'business_id'), fn($q) => $q->where('business_id', $businessId))
            ->with(['product'])
            ->latest('id')
            ->limit(30)
            ->get();

        $reasons = DB::table('write_off_reasons')->when($businessId, fn($q) => $q->where('business_id', $businessId))->where('is_active', true)->get();

        $users = \App\Models\User::query()
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name']);

        return view('app.warehouses.index', compact('warehouses', 'suppliers', 'products', 'summary', 'recentMovements', 'reasons', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:40',
            'type' => 'nullable|in:main,return,quarantine,branch,production',
            'location' => 'nullable|string|max:191',
            'phone' => 'nullable|string|max:30',
            'manager_id' => 'nullable|exists:users,id',
            'capacity' => 'nullable|integer|min:0|max:10000000',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
        ], [
            'name.required' => 'نام انبار الزامی است',
        ]);

        $businessId = $this->currentBusinessId();

        if (!empty($data['is_default'])) {
            Warehouse::when($businessId, fn($q) => $q->where('business_id', $businessId))->update(['is_default' => false]);
        }

        Warehouse::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'type' => $data['type'] ?? 'main',
            'location' => $data['location'] ?? null,
            'phone' => $data['phone'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'description' => $data['description'] ?? null,
            'is_default' => !empty($data['is_default']),
            'is_active' => true,
        ]);

        return back()->with('status', 'انبار جدید با موفقیت ساخته شد');
    }

    public function storeSupply(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'received_at' => 'nullable|string|max:20',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1|max:100000',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        $businessId = $this->currentBusinessId();
        $receivedAt = $data['received_at'] ? Jalali::parse($data['received_at']) : now();

        DB::transaction(function () use ($data, $businessId, $receivedAt) {
            $totalCost = 0;
            foreach ($data['items'] as $item) {
                $totalCost += (float) ($item['unit_cost'] ?? 0) * (int) $item['qty'];
            }

            $supply = Supply::create([
                'business_id' => $businessId,
                'warehouse_id' => $data['warehouse_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'number' => 'رسید-' . Jalali::date(now()) . '-' . random_int(1000, 9999),
                'total_cost' => $totalCost,
                'note' => $data['note'] ?? null,
                'received_at' => $receivedAt,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);

                \DB::table('supply_items')->insert([
                    'supply_id' => $supply->id,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $qty * $unitCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $wp = WarehouseProduct::firstOrCreate(
                    ['warehouse_id' => $data['warehouse_id'], 'product_id' => $productId],
                    ['stock' => 0, 'min_stock' => 0, 'avg_cost' => $unitCost]
                );

                $newStock = (int) $wp->stock + $qty;
                $newAvg = $unitCost > 0 ? (($wp->avg_cost * $wp->stock + $unitCost * $qty) / max(1, $newStock)) : $wp->avg_cost;

                $wp->update(['stock' => $newStock, 'avg_cost' => $newAvg]);

                $product = Product::find($productId);
                if ($product) {
                    $product->increment('stock', $qty);
                }

                StockMovement::create([
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'warehouse_id' => $data['warehouse_id'],
                    'supply_id' => $supply->id,
                    'type' => 'in',
                    'qty' => $qty,
                    'balance_after' => $newStock,
                    'reason' => 'رسید خرید از تامین‌کننده',
                    'user_id' => Auth::id(),
                ]);
            }
        });

        return back()->with('status', 'رسید خرید ثبت شد و موجودی انبار افزایش یافت');
    }

    public function storeTransfer(Request $request)
    {
        $data = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1|max:100000',
        ]);

        $businessId = $this->currentBusinessId();

        DB::transaction(function () use ($data, $businessId) {
            $transferId = DB::table('warehouse_transfers')->insertGetId([
                'business_id' => $businessId,
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'number' => 'انتقال-' . random_int(1000, 9999),
                'status' => 'received',
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];

                DB::table('warehouse_transfer_items')->insert([
                    'transfer_id' => $transferId,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $fromWp = WarehouseProduct::where('warehouse_id', $data['from_warehouse_id'])->where('product_id', $productId)->first();
                if ($fromWp && $fromWp->stock >= $qty) {
                    $fromWp->decrement('stock', $qty);
                } else {
                    throw new \Exception('موجودی کافی در انبار مبدا وجود ندارد');
                }

                $toWp = WarehouseProduct::firstOrCreate(
                    ['warehouse_id' => $data['to_warehouse_id'], 'product_id' => $productId],
                    ['stock' => 0, 'min_stock' => 0, 'avg_cost' => $fromWp->avg_cost ?? 0]
                );
                $toWp->increment('stock', $qty);

                StockMovement::create([
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'warehouse_id' => $data['from_warehouse_id'],
                    'transfer_id' => $transferId,
                    'type' => 'out',
                    'qty' => $qty,
                    'balance_after' => $fromWp->fresh()->stock,
                    'reason' => 'انتقال به انبار دیگر',
                    'user_id' => Auth::id(),
                ]);

                StockMovement::create([
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'warehouse_id' => $data['to_warehouse_id'],
                    'transfer_id' => $transferId,
                    'type' => 'in',
                    'qty' => $qty,
                    'balance_after' => $toWp->fresh()->stock,
                    'reason' => 'دریافت از انتقال انبار',
                    'user_id' => Auth::id(),
                ]);
            }
        });

        return back()->with('status', 'انتقال بین انبارها با موفقیت انجام شد');
    }

    public function storeWriteOff(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'reason_id' => 'nullable|exists:write_off_reasons,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1|max:100000',
        ]);

        $businessId = $this->currentBusinessId();

        DB::transaction(function () use ($data, $businessId) {
            $writeOffId = DB::table('write_offs')->insertGetId([
                'business_id' => $businessId,
                'warehouse_id' => $data['warehouse_id'],
                'reason_id' => $data['reason_id'] ?? null,
                'number' => 'اسقاط-' . random_int(1000, 9999),
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];

                DB::table('write_off_items')->insert([
                    'write_off_id' => $writeOffId,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $wp = WarehouseProduct::where('warehouse_id', $data['warehouse_id'])->where('product_id', $productId)->first();
                if ($wp) {
                    $wp->decrement('stock', $qty);
                }

                $product = Product::find($productId);
                if ($product) {
                    $product->decrement('stock', $qty);
                }

                StockMovement::create([
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'warehouse_id' => $data['warehouse_id'],
                    'write_off_id' => $writeOffId,
                    'type' => 'out',
                    'qty' => $qty,
                    'balance_after' => $wp?->fresh()->stock ?? 0,
                    'reason' => 'اسقاط کالا',
                    'user_id' => Auth::id(),
                ]);
            }
        });

        return back()->with('status', 'اسقاط کالا با موفقیت ثبت شد و از موجودی کسر شد');
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:40',
            'category' => 'nullable|in:material,packaging,service,other',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string|max:1000',
            'bank_account' => 'nullable|string|max:100',
            'payment_type' => 'nullable|in:cash,credit,cheque',
            'credit_limit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        Supplier::create([
            'business_id' => $this->currentBusinessId(),
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'category' => $data['category'] ?? 'material',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'bank_account' => $data['bank_account'] ?? null,
            'payment_type' => $data['payment_type'] ?? 'cash',
            'credit_limit' => $data['credit_limit'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('status', 'تامین‌کننده جدید اضافه شد');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->products()->where('stock', '>', 0)->exists()) {
            return back()->with('error', 'انبار دارای موجودی است و قابل حذف نیست - ابتدا موجودی را انتقال دهید');
        }

        $warehouse->delete();
        return back()->with('status', 'انبار حذف شد');
    }

    protected function currentBusinessId(): ?int
    {
        try {
            $user = Auth::user();
            if (!$user) return null;
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return null;
            return $user->business_id ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}