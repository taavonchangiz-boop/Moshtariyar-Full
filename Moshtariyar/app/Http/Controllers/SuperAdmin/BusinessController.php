<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Routing\Controller;
use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{
    /**
     * نمایش لیست تمام کسب‌وکارهای پلتفرم
     */
    public function index(Request $request)
    {
        // مدیر کل همه را می‌بیند.
        $query = Business::with('subscription')->withCount('users');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
        }

        $businesses = $query->latest()->paginate(15);
        $totalBusinesses = Business::count();
        $totalUsers = User::where('is_superadmin', false)->count();
        $activeSubscriptions = BusinessSubscription::where('is_active', true)
                                ->where('ends_at', '>', now())
                                ->count();

        return view('superadmin.businesses.index', compact('businesses', 'totalBusinesses', 'totalUsers', 'activeSubscriptions'));
    }

    /**
     * ذخیره کسب‌وکار جدید در دیتابیس
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'domain' => 'nullable|string|max:191|unique:businesses,domain',
            'admin_name' => 'required|string|max:191',
            'admin_phone' => 'required|string|max:32|unique:users,phone',
            'admin_email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'plan_name' => 'required|string|max:100',
            'duration_months' => 'required|integer|min:1',
            'modules' => 'nullable|array',
        ], [
            'admin_phone.unique' => 'این شماره موبایل قبلاً در سیستم ثبت شده است.',
            'admin_email.unique' => 'این ایمیل قبلاً در سیستم ثبت شده است.',
            'domain.unique' => 'این دامنه قبلاً برای کسب‌وکار دیگری ثبت شده است.'
        ]);

        DB::transaction(function () use ($data) {
            $business = Business::create([
                'name' => $data['name'],
                'domain' => $data['domain'],
                'is_active' => true,
            ]);

            BusinessSubscription::create([
                'business_id' => $business->id,
                'plan_name' => $data['plan_name'],
                'active_modules' => $data['modules'] ?? [],
                'starts_at' => now(),
                'ends_at' => now()->addMonths($data['duration_months']),
                'is_active' => true,
            ]);

            User::create([
                'business_id' => $business->id,
                'name' => $data['admin_name'],
                'phone' => $data['admin_phone'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['password']),
                'role' => 'admin',
                'is_superadmin' => false,
                'is_active' => true,
            ]);
        });

        return back()->with('status', 'کسب‌وکار با موفقیت ایجاد شد و لایسنس به آن اختصاص یافت.');
    }

    /**
     * تغییر وضعیت فعال/غیرفعال بودن یک کسب‌وکار
     */
    public function toggle(Business $business)
    {
        $business->update(['is_active' => !$business->is_active]);
        $status = $business->is_active ? 'فعال' : 'غیرفعال';
        return back()->with('status', "کسب‌وکار مورد نظر {$status} شد.");
    }
}