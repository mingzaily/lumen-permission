<?php

namespace Mingzaily\Permission\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Mingzaily\Permission\Exceptions\UnauthorizedException;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        if (app('auth')->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roles = is_array($role)
            ? $role
            : explode('|', $role);

        if (! app('auth')->user()->hasAnyRole($roles)) {
            throw UnauthorizedException::forRoles();
        }

        return $next($request);
    }
}
