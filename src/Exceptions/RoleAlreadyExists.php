<?php

namespace Mingzaily\Permission\Exceptions;

use InvalidArgumentException;

class RoleAlreadyExists extends InvalidArgumentException
{
    public static function create(string $roleName)
    {
        return new static("A role `{$roleName}`.");
    }

    public static function assign()
    {
        return new static("Non support multiple_roles. You can change config in permission.php");
    }
}
