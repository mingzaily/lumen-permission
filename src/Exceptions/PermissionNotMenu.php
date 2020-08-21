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

class PermissionNotMenu extends InvalidArgumentException
{
    public static function notMenu(string $permissionName)
    {
        return new static("There is [permission] `{$permissionName}`, route and method does not empty.");
    }
}
