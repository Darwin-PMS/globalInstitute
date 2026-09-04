<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('permission:students.view')
     *        ->middleware('permission:students.create,students.edit')
     *
     * The second parameter `$requireAll` controls ANY vs ALL:
     *   'permission:students.view,students.edit'  → ANY (default)
     *   'permission:students.view,students.edit,requireAll' → ALL
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return $this->deny($request, 'Please log in.', 401);
        }

        $user = Auth::user();

        // Super Admin bypass
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Determine if ALL permissions are required
        $requireAll = in_array('requireAll', $permissions);
        $permissions = array_filter($permissions, fn ($p) => $p !== 'requireAll');

        $service = app(PermissionService::class);

        $granted = $requireAll
            ? $service->userHasAllPermissions($user, $permissions)
            : $service->userHasAnyPermission($user, $permissions);

        if (!$granted) {
            AuditLog::log([
                'user' => $user,
                'action' => 'permission_denied',
                'module' => $permissions[0] ?? null,
                'permission_slug' => implode(', ', $permissions),
                'result' => 'denied',
                'notes' => 'Required: ' . ($requireAll ? 'ALL' : 'ANY') . ' of [' . implode(', ', $permissions) . ']',
            ]);

            return $this->deny($request, 'You do not have permission to perform this action.', 403);
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
