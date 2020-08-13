<?php

namespace Mingzaily\Permission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Permission
{
    /**
     * A permission can be applied to roles.
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany;

    /**
     * Find a permission by its name.
     *
     * @param string $name
     *
     * @throws \Mingzaily\Permission\Exceptions\PermissionDoesNotExist
     *
     * @return \Mingzaily\Permission\Contracts\Permission
     */
    public static function findByName(string $name): self;

    /**
     * Find a permission by its id.
     *
     * @param int $id
     *
     * @throws \Mingzaily\Permission\Exceptions\PermissionDoesNotExist
     *
     * @return \Mingzaily\Permission\Contracts\Permission
     */
    public static function findById(int $id): self;

    /**
     * Find a permission by its route and method
     *
     * @param string $route
     * @param string $method
     *
     * @throws \Mingzaily\Permission\Exceptions\PermissionDoesNotExist
     *
     * @return \Mingzaily\Permission\Contracts\Permission
     */
    public static function findByRouteAndMethod(string $route, string $method): self;

    /**
     * Find or Create a permission by its name and guard name.
     *
     * @param array $attributes
     *
     * @return \Mingzaily\Permission\Contracts\Permission
     */
    public static function findOrCreate(array $attributes): self;
}
