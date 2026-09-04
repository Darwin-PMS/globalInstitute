<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()->is_admin !== true) {
                Auth::logout();

                AuditLog::log([
                    'action' => 'login_failed',
                    'module' => 'auth',
                    'result' => 'denied',
                    'notes' => 'Non-admin user attempted admin login: ' . $credentials['email'],
                ]);

                return back()->withErrors([
                    'email' => 'Unauthorized access.',
                ]);
            }

            AuditLog::log([
                'action' => 'login',
                'module' => 'auth',
                'result' => 'success',
            ]);

            return redirect()->intended(route('admin.dashboard'));
        }

        AuditLog::log([
            'action' => 'login_failed',
            'module' => 'auth',
            'result' => 'failed',
            'notes' => 'Invalid credentials for: ' . $credentials['email'],
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        AuditLog::log([
            'action' => 'logout',
            'module' => 'auth',
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
