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
        return new static(401, 'User is not logged in.', null, []);
    }

    public static function notAssignRole(): self
    {
        return new static(403, 'User is not assigned a role.', null, []);
    }
}
