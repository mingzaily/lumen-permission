<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Mingzaily\Permission\Exceptions\UnauthorizedException;

class PermissionRouteMiddleware
{
    public function handle($request, Closure $next)
    {
        if (app('auth')->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $ability = $request->getPathInfo().'|'.$request->getMethod();
        if (app('auth')->user()->can('hasPermission', $ability)) {
            return $next($request);
        }

        throw UnauthorizedException::forPermissions();
    }
}
