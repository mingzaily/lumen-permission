<?php

namespace Mingzaily\Permission\Exceptions;

use InvalidArgumentException;

class PermissionDoesNotExist extends InvalidArgumentException
{
    public static function create(string $permissionName)
    {
        return new static("There is no permission named `{$permissionName}`.");
    }

    public static function withId(int $permissionId)
    {
        return new static("There is no [permission] with id `{$permissionId}`.");
    }

    public static function withRouteAndMethod(string $route, string $method)
    {
        return new static("There is no [permission] with route `{$route}` and method `{$method}`.");
    }
}
