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

class RoleAlreadyExists extends InvalidArgumentException
{
    public static function create(string $roleName)
    {
        return new static("A role `{$roleName}` exits.");
    }

    public static function assign()
    {
        return new static('Non support multiple roles');
    }

    public static function assignExits(string $roleName)
    {
        return new static("Model already has a role `{$roleName}`");
    }
}
