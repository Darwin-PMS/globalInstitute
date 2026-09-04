<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:admin')
     *        ->middleware('role:admin,teacher')
     *
     * Checks if the user has ANY of the listed roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return $this->deny($request, 'Please log in.', 401);
        }

        $user = Auth::user();

        // Super Admin bypass
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        if (!$user->hasAnyRole($roles)) {
            return $this->deny($request, 'You do not have the required role to access this resource.', 403);
        }

        return $next($request);
    }

    private function deny(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        if ($status === 401) {
            return redirect()->guest('/admin/login')
                ->with('error', $message);
        }

        return response()->view('errors.403', [
            'message' => $message,
        ], $status);
    }
}
