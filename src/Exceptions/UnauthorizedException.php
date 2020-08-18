<?php

namespace Mingzaily\Permission\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    public static function forRoles(): self
    {
        $message = 'User does not have the right roles.';

        return new static(403, $message, null, []);
    }

    public static function forPermissions(): self
    {
        $message = 'User does not have the right permissions.';

        return new static(403, $message, null, []);
    }

    public static function notLoggedIn(): self
    {
        return new static(403, 'User is not logged in.', null, []);
    }
}
