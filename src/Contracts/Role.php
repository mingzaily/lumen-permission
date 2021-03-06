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

interface Role
{
    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a role by its name.
     *
     * @param string $name
     * @return Role
     */
    public static function findByName(string $name): self;

    /**
     * Find a role by its id.
     *
     * @param int $id
     * @return Role
     */
    public static function findById(int $id): self;

    /**
     * Find or create a role by its name.
     *
     * @param array $attributes
     * @return Role
     */
    public static function findOrCreate(array $attributes): self;

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool;
}
