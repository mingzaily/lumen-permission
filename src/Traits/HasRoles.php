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
use Mingzaily\Permission\Contracts\Role;
use Illuminate\Database\Eloquent\Builder;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Contracts\Permission;
use Mingzaily\Permission\Exceptions\RoleAlreadyExists;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Mingzaily\Permission\Exceptions\UnauthorizedException;

/**
 * Trait HasRoles.
 *
 * @property Collection|Role[] $roles
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
    public function roles(): MorphToMany
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
     * Scope the model query to certain roles only.
     *
     * @param Builder $query
     * @param string|array|Role|Collection $roles
     * @param string $guard
     *
     * @return Builder
     */
    public function scopeRole(Builder $query, $roles, $guard = null): Builder
    {
        if ($roles instanceof Collection) {
            $roles = $roles->all();
        }

        if (! is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_map(function ($role) {
            if ($role instanceof Role) {
                return $role;
            }

            $method = is_numeric($role) ? 'findById' : 'findByName';

            return $this->getRoleClass()->{$method}($role);
        }, $roles);

        return $query->whereHas('roles', function (Builder $subQuery) use ($roles) {
            $subQuery->whereIn(config('permission.table_names.roles').'.id', array_column($roles, 'id'));
        });
    }

    /**
     * Return a model have one of the role.
     * alias roles().
     *
     * @return Role
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
        return $this->roles;
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
     * alias to hasRole().
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

        return $roles->intersect($this->getRoleNames()) == $roles;
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

        if ($this->roles()->count() >= 1
            && $given
            && $this->getStoredRole($roles->first())->id !== $this->getFirstRole()->id
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
    public function checkPermissionViaRole($permission)
    {
        return $this->roles->map(function (Role $role) use ($permission) {
            return $role->checkPermissionTo($permission);
        })->filter(function ($isOk) {
            return $isOk;
        })->isNotEmpty();
    }

    /**
     * Traverse all roles and view role permissions
     * get permissions.
     *
     * @return Collection
     */
    public function getPermissionsViaRole()
    {
        return $this->roles->map(function (Role $role) {
            return $role->getAllPermissions();
        })->flatten();
    }

    /**
     * Forget the cached permissions.
     */
    protected function forgetCachedPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
