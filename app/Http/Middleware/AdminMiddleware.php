<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $this->deny($request, 'Your session has expired. Please log in again.', 401);
        }

        if (! Auth::user()->isAdministrator()) {
            return $this->deny($request, 'You do not have permission to access this content.', 403);
        }

        return $next($request);
    }

    /**
     * JSON callers get a status code they can act on; browser navigation still
     * gets the login redirect.
     *
     * The previous implementation called Auth::logout() here, which destroyed
     * the session of any signed-in non-admin who touched an admin URL - in a
     * single-page app that means one stray request signs the user out entirely.
     * Refusing the request is enough.
     */
    protected function deny(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [],
            ], $status);
        }

        return redirect()->route('admin.login')->with('error', $message);
    }
}
