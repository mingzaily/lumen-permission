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

class PermissionIsMenu extends InvalidArgumentException
{
    public static function isMenu(string $permissionName)
    {
        return new static("There is [menu] `{$permissionName}`, not a [permission].");
    }
}
