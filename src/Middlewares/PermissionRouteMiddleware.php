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

        $ability = $request->getPathInfo(). '|'. $request->getMethod();
        if (app('auth')->user()->can('hasPermission', $ability)) {
            return $next($request);
        }

        throw UnauthorizedException::forPermissions();
    }
}
