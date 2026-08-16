<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\CrmRole;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $کاربر_فعلی = auth()->user();
        $شناسه_کسب = $کاربر_فعلی->business_id;
        $پرسش = User::query();
        if (! $کاربر_فعلی->isSuperAdmin()) {
            $پرسش->where('business_id', $شناسه_کسب);
        }
        $کاربران = $پرسش->latest()->paginate(20);
        $users = $کاربران;
        $roleRows = CrmRole::with('permissions')->orderBy('id')->get();
        return view('app.users', [
            'users' => $users,
            'roles' => Roles::labels(),
            'roleRows' => $roleRows,
            'permissionCatalog' => Roles::catalog(),
            'flatPermissions' => Roles::flatCatalog(),
        ]);
    }

    public function store(Request $request)
    {
        $کاربر_فعلی = auth()->user();
        $data = $request->validate([
            'name'     => 'required|string|max:191',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:32|unique:users,phone',
            'password' => 'required|string|min:6',
            'role'     => ['required', Rule::in(Roles::all())],
        ]);

        $شناسه_کسب = $کاربر_فعلی->business_id;

        if (! $شناسه_کسب) {
            $کسب = Business::where('is_active', true)->orderBy('id')->first() ?? Business::orderBy('id')->first();
            if (! $کسب) {
                DB::transaction(function () use (&$کسب) {
                    $کسب = Business::create([
                        'name' => 'کسب‌وکار اصلی',
                        'is_active' => true,
                    ]);
                    BusinessSubscription::create([
                        'business_id' => $کسب->id,
                        'plan_name' => 'کامل',
                        'active_modules' => ['customers','orders','products','loyalty','tickets','workflows','reports'],
                        'starts_at' => now(),
                        'ends_at' => now()->addYears(5),
                        'is_active' => true,
                    ]);
                });
                $کسب = Business::orderBy('id')->first();
            }
            $شناسه_کسب = $کسب?->id;
            if ($شناسه_کسب && ! $کاربر_فعلی->business_id) {
                $کاربر_فعلی->business_id = $شناسه_کسب;
                $کاربر_فعلی->save();
            }
        }

        if (! $شناسه_کسب) {
            return back()->withErrors(['name' => 'حساب شما به هیچ کسب‌وکاری متصل نیست، امکان ساخت کاربر وجود ندارد.'])->withInput();
        }

        $data['is_active'] = true;
        $data['business_id'] = $شناسه_کسب;
        $data['is_superadmin'] = false;
        if (! empty($data['phone'])) {
            $data['phone'] = $this->normalizePhone($data['phone']);
        }
        User::create($data);
        return back()->with('status', 'کاربر جدید با موفقیت ایجاد شد و به کسب‌وکار شما متصل شد. حالا می‌تواند وارد شود.');
    }

    public function update(Request $request, User $user)
    {
        $کاربر_فعلی = auth()->user();
        if (! $کاربر_فعلی->isSuperAdmin() && $user->business_id !== $کاربر_فعلی->business_id) {
            return back()->withErrors(['role' => 'شما اجازه ویرایش کاربر کسب‌وکار دیگری را ندارید.']);
        }
        $data = $request->validate([
            'role'      => ['required', Rule::in(Roles::all())],
            'phone'     => ['nullable','string','max:32', Rule::unique('users','phone')->ignore($user->id)],
            'is_active' => 'nullable|boolean',
            'password'  => 'nullable|string|min:6',
        ]);
        $user->role = $data['role'];
        $user->phone = ! empty($data['phone']) ? $this->normalizePhone($data['phone']) : null;
        $user->is_active = $request->boolean('is_active');
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        if (! $user->business_id) {
            $user->business_id = $کاربر_فعلی->business_id ?? Business::orderBy('id')->value('id');
        }
        $user->save();
        return back()->with('status', 'کاربر به‌روزرسانی شد.');
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'key' => 'nullable|string|max:30|unique:crm_roles,key',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
        ]);
        $key = $data['key'] ?: Str::slug($data['label']);
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key) ?: 'role_' . time();
        $role = CrmRole::create([
            'key' => $key,
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'is_active' => true,
        ]);
        $this->syncPermissions($role, $request->input('permissions', []));
        return back()->with('status', 'نقش سفارشی ساخته شد.');
    }

    public function updateRole(Request $request, CrmRole $role)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);
        $role->update([
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'is_active' => $role->is_system ? true : $request->boolean('is_active'),
        ]);
        $this->syncPermissions($role, $request->input('permissions', []));
        return back()->with('status', 'نقش به‌روزرسانی شد.');
    }

    public function destroyRole(CrmRole $role)
    {
        if ($role->is_system) return back()->with('status', 'نقش سیستمی قابل حذف نیست.');
        if (User::where('role', $role->key)->exists()) return back()->with('status', 'این نقش به کاربر اختصاص داده شده و قابل حذف نیست.');
        $role->delete();
        return back()->with('status', 'نقش حذف شد.');
    }

    private function syncPermissions(CrmRole $role, array $permissions): void
    {
        $allowed = array_keys(Roles::flatCatalog());
        $permissions = array_values(array_intersect($permissions, array_merge($allowed, ['*'])));
        if ($role->key === Roles::ADMIN && ! in_array('*', $permissions, true)) $permissions[] = '*';
        $role->permissions()->delete();
        foreach ($permissions as $permission) $role->permissions()->create(['permission' => $permission]);
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        $value = trim($value);
        $map = ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
        $value = strtr($value, $map);
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) return $value;
        $phone = preg_replace('/\D+/', '', $value);
        if (str_starts_with($phone, '98')) $phone = '0'.substr($phone,2);
        if (str_starts_with($phone,'9') && strlen($phone)===10) $phone='0'.$phone;
        return $phone ?: $value;
    }
}