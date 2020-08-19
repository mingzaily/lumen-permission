<?php

namespace Mingzaily\Permission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mingzaily\Permission\Exceptions\RoleDoesNotExist;

interface Role
{
    /**
     * A role may be given various permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     *
     * @return Role
     *
     * @throws RoleDoesNotExist
     */
    public static function findByName(string $name): self;

    /**
     * Find a role by its id and guard name.
     *
     * @param int $id
     *
     * @return Role
     *
     * @throws RoleDoesNotExist
     */
    public static function findById(int $id): self;

    /**
     * Find or create a role by its name and guard name.
     *
     * @param string $name
     *
     * @return Role
     */
    public static function findOrCreate(string $name): self;

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission): bool;
}
