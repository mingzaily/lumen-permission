<?php

namespace Mingzaily\Permission\Exceptions;

use InvalidArgumentException;

class PermissionIsMenu extends InvalidArgumentException
{
    public static function isMenu(string $permissionName)
    {
        return new static("There is [menu] `{$permissionName}`, not a [permission].");
    }
}
