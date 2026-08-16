<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Customer;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = Customer::query();
        if ($s = $request->get('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('full_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }
        return $q->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:191',
            'email'     => 'nullable|email|unique:customers,email',
            'phone'     => 'nullable|string|max:32|unique:customers,phone',
        ]);
        $data['source'] = 'manual';
        return response()->json(Customer::create($data), 201);
    }

    public function edit(Customer $customer)
    {
        return view('app.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'full_name'     => 'required|string|max:191',
            'company_name'  => 'nullable|string|max:191',
            'email'         => 'nullable|email|unique:customers,email,'.$customer->id,
            'phone'         => 'nullable|string|max:32|unique:customers,phone,'.$customer->id,
            'national_id'   => 'nullable|string|max:20',
            'economic_code' => 'nullable|string|max:20',
            'source'        => 'nullable|string|max:50',
        ]);

        $customer->update($data);

        return redirect('/app/customers/'.$customer->id)->with('status', 'اطلاعات مشتری بروزرسانی شد.');
    }

    /** ارسال کمپین بازگشت فوری برای مشتری ارزشمند در معرض ریزش */
    public function sendReturnCampaign(Customer $customer)
    {
        try {
            $engine = app(\Modules\Automation\Services\WorkflowEngine::class);
            $engine->fire('no_purchase_45d', $customer);
            
            if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                \Modules\Core\Entities\Activity::create([
                    'subject_type' => 'customer',
                    'subject_id' => $customer->id,
                    'type' => 'task',
                    'body' => 'کمپین بازگشت خودکار برای مشتری ارزشمند در معرض ریزش ارسال شد - ' . $customer->full_name . ' - ریسک بالا - نیاز به تماس تلفنی',
                    'user_id' => auth()->id(),
                    'due_at' => now()->addHours(2),
                    'done' => false,
                ]);
            }

            return redirect()->back()->with('status', 'کمپین بازگشت برای ' . $customer->full_name . ' با موفقیت از طریق تمام کانال‌های فعال ارسال شد و وظیفه پیگیری برای تیم فروش ساخته شد.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'خطا در ارسال کمپین: ' . $e->getMessage());
        }
    }

    /** نمای ۳۶۰ درجه مشتری */
    public function show360(Customer $customer)
    {
        $customer->load(['addresses', 'orders.items']);

        $timeline = $customer->orders->map(fn ($o) => [
            'type' => 'order',
            'at'   => optional($o->placed_at)->toDateString(),
            'desc' => "سفارش #{$o->number} - وضعیت: {$o->status} - مبلغ: {$o->total}",
        ])->values();

        return response()->json([
            'customer' => $customer,
            'orders'   => $customer->orders,
            'tickets'  => [],
            'timeline' => $timeline,
            'stats'    => [
                'lifetime_value' => $customer->lifetime_value,
                'orders_count'   => $customer->orders->count(),
                'aov'            => $customer->orders->count()
                    ? round($customer->orders->sum('total') / $customer->orders->count())
                    : 0,
            ],
        ]);
    }
}