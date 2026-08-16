<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Lead;

/**
 * نقاط پایانی REST برای اپ موبایل (محافظت با Sanctum).
 */
class MobileApiController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'customers' => Customer::count(),
            'orders'    => Order::count(),
            'revenue'   => (float) Order::where('status', 'completed')->sum('total'),
            'leads'     => Lead::count(),
        ]);
    }

    public function customers(Request $request)
    {
        $q = Customer::query();
        if ($s = $request->get('search')) {
            $q->where('full_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%");
        }
        return response()->json($q->latest()->paginate(20));
    }

    public function customer(Customer $customer)
    {
        $customer->load(['orders', 'addresses']);
        return response()->json($customer);
    }

    public function orders()
    {
        return response()->json(Order::with('customer:id,full_name')->latest('placed_at')->paginate(20));
    }

    public function createLead(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:191',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email',
            'value' => 'nullable|numeric',
        ]);
        $data['source'] = 'mobile-app';
        return response()->json(Lead::create($data), 201);
    }
}
