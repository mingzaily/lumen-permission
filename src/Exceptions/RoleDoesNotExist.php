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

class RoleDoesNotExist extends InvalidArgumentException
{
    public static function named(string $roleName)
    {
        return new static("There is no [role] named `{$roleName}`.");
    }

    public static function withId(int $roleId)
    {
        return new static("There is no [role] with id `{$roleId}`.");
    }
}
