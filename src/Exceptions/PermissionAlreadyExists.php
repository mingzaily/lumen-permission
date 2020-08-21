<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission\Exceptions;

use InvalidArgumentException;

class PermissionAlreadyExists extends InvalidArgumentException
{
    public static function name(string $permissionName)
    {
        return new static("A permission name `{$permissionName}` already exists`.");
    }

    public static function routeMethod(string $route, string $method)
    {
        return new static("A route `{$route}` and method `{$method}` permission already exists`.");
    }
}
