<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();
        if ($user->role !== 'admin') {
            Auth::logout();
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['authenticated' => false], 401);
        }
        return response()->json([
            'authenticated' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);
    }
}