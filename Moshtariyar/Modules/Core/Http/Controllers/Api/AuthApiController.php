<?php

namespace Modules\Core\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    /** ورود و دریافت توکن برای اپ موبایل */
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->is_active) {
            return response()->json(['message' => 'ایمیل یا رمز عبور نادرست است.'], 401);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->only('id', 'name', 'email', 'role'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['ok' => true]);
    }
}
