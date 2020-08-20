<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission\Traits;

use Illuminate\Support\Collection;
use Mingzaily\Permission\Models\Role;
use Mingzaily\Permission\Models\Permission;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Exceptions\RoleAlreadyExists;
use Mingzaily\Permission\Exceptions\UnauthorizedException;

/**
 * Trait HasRoles.
 *
 * @property Collection|Role[] $roles
 * @property int|null          $roles_count
 */
trait HasRoles
{
    private $roleClass;

    public static function bootHasRoles()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->roles()->detach();
        });
    }

    public function getRoleClass()
    {
        if (! isset($this->roleClass)) {
            $this->roleClass = app(PermissionRegistrar::class)->getRoleClass();
        }

        return $this->roleClass;
    }

    /**
     * A model may have multiple roles.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            'role_id'
        );
    }

    /**
     * Return a model have one of the role.
     * alias roles().
     */
    public function getFirstRole(): Role
    {
        if ($role = $this->getAllRoles()->first()) {
            return $role;
        }
        throw UnauthorizedException::notAssignRole();
    }

    /**
     * Return a model have all roles.
     * alias roles().
     *
     * @return Collection|Role[]
     */
    public function getAllRoles()
    {
        return $this->roles()->getResults();
    }

    /**
     * Assign the given role to the model.
     *
     * @param string|Role ...$roles
     * @return $this
     */
    public function assignRole(...$roles)
    {
        $roles = $this->checkMultipleRole($roles)
            ->map(function ($role) {
                if (empty($role)) {
                    return false;
                }

                return $this->getStoredRole($role);
            })
            ->filter(function ($role) {
                return $role instanceof Role;
            })
            ->map->id
            ->all();

        $this->roles()->sync($roles, false);
        $this->load('roles');

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Revoke the given role from the model.
     *
     * @param string|int|Role $role
     *
     * @return $this
     */
    public function removeRole($role)
    {
        $this->roles()->detach($this->getStoredRole($role));

        $this->load('roles');

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove all current roles and set the given ones.
     *
     * @param array|string|Role ...$roles
     *
     * @return $this
     */
    public function syncRoles(...$roles)
    {
        $roles = $this->checkMultipleRole($roles, false);

        $this->roles()->detach();

        return $this->assignRole($roles);
    }

    /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param array|string|int|Role|Collection $roles
     * @return bool
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->getAllRoles()->contains('name', $roles);
        }

        if (is_int($roles)) {
            return $this->getAllRoles()->contains('id', $roles);
        }

        if ($roles instanceof Role) {
            return $this->getAllRoles()->contains('id', $roles->id);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return $roles->intersect($this->getAllRoles())->isNotEmpty();
    }

    /**
     * Determine if the model has any of the given role(s).
     *
     * Alias to hasRole()
     *
     * @param array|string|int|Role|\Illuminate\Database\Eloquent\Collection $roles
     * @return bool
     */
    public function hasAnyRole(...$roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all of the given role(s).
     *
     * @param array|string|int|Role|Collection $roles
     * @return bool
     */
    public function hasAllRoles($roles): bool
    {
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->getAllRoles()->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $this->getAllRoles()->contains('id', $roles->id);
        }

        $roles = collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return $roles->intersect($this->getRoleNames()) === $roles;
    }

    public function getRoleNames(): Collection
    {
        return $this->getAllRoles()->pluck('name');
    }

    protected function getStoredRole($role): Role
    {
        $roleClass = $this->getRoleClass();

        if (is_numeric($role)) {
            return $roleClass->findById($role);
        }

        if (is_string($role)) {
            return $roleClass->findByName($role);
        }

        return $role;
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }

    /**
     * @param string|array|Role|Collection $roles
     * @param bool $given
     * @return Collection
     */
    protected function checkMultipleRole($roles, bool $given = true): Collection
    {
        $roles = collect($roles)->flatten();

        if ($roles->count() > 1
            && ! config('permission.model_has_multiple_roles')) {
            throw RoleAlreadyExists::assign();
        }

        if ($this->getAllRoles()->count() >= 1
            && $given
            && ! in_array($this->getFirstRole()->name, $roles->toArray())
            && ! config('permission.model_has_multiple_roles')) {
            throw RoleAlreadyExists::assignExits($this->getFirstRole()->name);
        }

        return $roles;
    }

    /**
     * Traverse all roles and view role permissions
     * check permissions.
     *
     * @param string|array|int|Permission $permission
     * @return bool
     */
    public function checkPermission($permission)
    {
        return $this->roles()->getResults()->map(function ($role) use ($permission) {
            return $role->checkPermissionTo($permission);
        })->filter(function ($isOk) {
            return $isOk;
        })->isNotEmpty();
    }

    /**
     * Forget the cached permissions.
     */
    protected function forgetCachedPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
