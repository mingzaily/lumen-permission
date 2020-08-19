<?php

namespace Mingzaily\Permission\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Mingzaily\Permission\Exceptions\UnauthorizedException;
use Mingzaily\Permission\PermissionRegistrar;

class PermissionRouteMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (app('auth')->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $ability = [$request->path(), $request->method()];
        if (app('auth')->user()->can('hasPermission', implode('|', $ability))) {
            return $next($request);
        }

        throw UnauthorizedException::forPermissions();
    }
}
