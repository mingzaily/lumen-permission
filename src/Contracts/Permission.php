<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;

interface Permission
{
    /**
     * A permission can be applied to roles.
     */
    public function roles(): BelongsToMany;

    /**
     * Find a permission by its name.
     *
     *@throws PermissionDoesNotExist
     * @return Permission
     */
    public static function findByName(string $name): self;

    /**
     * Find a permission by its id.
     *
     *@throws PermissionDoesNotExist
     * @return Permission
     */
    public static function findById(int $id): self;

    /**
     * Find a permission by its route and method.
     *
     *@throws PermissionDoesNotExist
     * @return Permission
     */
    public static function findByRouteAndMethod(array $permission): self;

    /**
     * Find or Create a permission by its name and guard name.
     *
     * @return Permission
     */
    public static function findOrCreate(array $attributes): self;
}
