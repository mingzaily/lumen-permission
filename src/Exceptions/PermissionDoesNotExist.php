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

class PermissionDoesNotExist extends InvalidArgumentException
{
    public static function create(string $permissionName)
    {
        return new static("There is no [permission] named `{$permissionName}`.");
    }

    public static function withId(int $permissionId)
    {
        return new static("There is no [permission] with id `{$permissionId}`.");
    }

    public static function withRouteAndMethod(array $permission)
    {
        return new static("There is no [permission] with route `{$permission['route']}` and method `{$permission['method']}`.");
    }

    public static function isMenu(string $permissionName)
    {
        return new static("There is [menu] `{$permissionName}`, not a [permission].");
    }
}
