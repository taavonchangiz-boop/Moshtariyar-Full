<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Routing\Controller;
use App\Models\User;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * نمایش لیست تمام کاربران در تمام کسب‌وکارهای پلتفرم
     */
    public function index(Request $request)
    {
        // در این بخش چون مربوط به مدیر کل است، اطلاعات تمام کاربران فراخوانی می‌شود
        $query = User::with('business')->where('is_superadmin', false);

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($business_id = $request->get('business_id')) {
            $query->where('business_id', $business_id);
        }

        $users = $query->latest()->paginate(15);
        $totalUsers = User::where('is_superadmin', false)->count();
        $businesses = Business::select('id', 'name')->orderBy('name')->get();

        return view('superadmin.users.index', compact('users', 'totalUsers', 'businesses'));
    }

    /**
     * تغییر وضعیت مسدودی کاربر
     */
    public function toggleStatus(User $user)
    {
        if ($user->is_superadmin) {
            return back()->withErrors(['error' => 'شما نمی‌توانید وضعیت مدیر کل سیستم را تغییر دهید.']);
        }
        
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'فعال' : 'مسدود';
        
        return back()->with('status', "کاربر {$user->name} با موفقیت {$status} شد.");
    }

    /**
     * تغییر رمز عبور اجباری توسط مدیر کل (در صورت نیاز)
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:6']);
        
        if ($user->is_superadmin) {
            return back()->withErrors(['error' => 'امکان تغییر رمز مدیر کل از این بخش وجود ندارد.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('status', "رمز عبور کاربر {$user->name} با موفقیت تغییر کرد.");
    }

    /**
     * لاگین به عنوان یک کاربر خاص (Login As) - برای پشتیبانی فنی
     */
    public function loginAs(User $user)
    {
        if ($user->is_superadmin) {
            return back()->withErrors(['error' => 'امکان ورود به اکانت مدیر کل دیگر وجود ندارد.']);
        }

        // برای امنیت بیشتر، آیدی مدیر کل فعلی را در نشست ذخیره می‌کنیم تا بتواند بعداً برگردد
        session()->put('superadmin_id_before_impersonation', Auth::id());
        
        Auth::login($user);
        
        return redirect('/app')->with('status', "شما با موفقیت به عنوان {$user->name} وارد شدید.");
    }
}