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
use Mingzaily\Permission\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    public function handle($request, Closure $next, $permission)
    {
        if (app('auth')->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $permissions = is_array($permission)
            ? $permission
            : explode('|', (string) $permission);

        foreach ($permissions as $permission) {
            if (app('auth')->user()->can('hasPermission', $permission)) {
                return $next($request);
            }
        }

        throw UnauthorizedException::forPermissions();
    }
}
