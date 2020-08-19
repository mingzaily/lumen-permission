<?php

namespace Mingzaily\Permission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;

interface Permission
{
    /**
     * A permission can be applied to roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany;

    /**
     * Find a permission by its name.
     *
     * @param string $name
     *
     * @return Permission
     *@throws PermissionDoesNotExist
     *
     */
    public static function findByName(string $name): self;

    /**
     * Find a permission by its id.
     *
     * @param int $id
     *
     * @return Permission
     *@throws PermissionDoesNotExist
     *
     */
    public static function findById(int $id): self;

    /**
     * Find a permission by its route and method
     *
     * @param array $permission
     *
     * @return Permission
     *@throws PermissionDoesNotExist
     *
     */
    public static function findByRouteAndMethod(array $permission): self;

    /**
     * Find or Create a permission by its name and guard name.
     *
     * @param array $attributes
     *
     * @return Permission
     */
    public static function findOrCreate(array $attributes): self;
}
