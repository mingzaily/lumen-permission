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

interface Permission
{
    /**
     * A permission can be applied to roles.
     */
    public function roles(): BelongsToMany;

    /**
     * Find a permission by its name.
     *
     * @param string $name
     * @return Permission
     */
    public static function findByName(string $name): self;

    /**
     * Find a permission by its id.
     *
     * @param int $id
     * @return Permission
     */
    public static function findById(int $id): self;

    /**
     * Find a permission by its route and method.
     *
     * @param string $route
     * @param string $method
     * @return Permission
     */
    public static function findByRouteAndMethod(string $route, string $method): self;

    /**
     * Find or Create a permission by its name.
     *
     * @param array $attributes
     * @return Permission
     */
    public static function findOrCreate(array $attributes): self;
}
