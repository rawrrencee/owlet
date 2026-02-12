<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPagePermission
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized action.');
        }

        // Support OR syntax: "perm1|perm2" means user needs ANY of the listed permissions
        if (str_contains($permission, '|')) {
            $permissions = explode('|', $permission);
            $hasAny = collect($permissions)->contains(
                fn ($perm) => $this->permissionService->canAccessPage($user, trim($perm))
            );

            if (! $hasAny) {
                abort(403, 'You do not have permission to access this page.');
            }
        } elseif (! $this->permissionService->canAccessPage($user, $permission)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
