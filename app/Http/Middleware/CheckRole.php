<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            abort(403, 'Unauthenticated');
        }

        $allowedRoles = array_map(fn ($role) => UserRole::from($role), $roles);

        if (! in_array($request->user()->role, $allowedRoles, true)) {
            abort(403, 'Accès refusé. Rôles requis : '.implode(', ', array_map(fn ($r) => $r->value, $allowedRoles)));
        }

        return $next($request);
    }
}
