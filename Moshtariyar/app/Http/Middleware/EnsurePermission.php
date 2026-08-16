<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * میدلور بررسی مجوز: usage → ->middleware('can.do:customers.view')
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return redirect('/login');
        }

        // مدیر کل و مدیر ارشد کسب‌وکار به همه چیز دسترسی دارند
        if (($user->role ?? '') === 'admin' || $user->isSuperAdmin() || $user->isBusinessAdmin()) {
            return $next($request);
        }

        if (! $user->hasPermission($permission)) {
            abort(403, 'شما به این بخش دسترسی ندارید.');
        }

        return $next($request);
    }
}