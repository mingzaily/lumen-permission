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

use Exception;
use Illuminate\Support\Collection;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Contracts\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Trait HasPermissions.
 *
 * @property Collection|Permission[] $permissions
 * @property int|null                $permissions_count
 */
trait HasPermissions
{
    private $permissionClass;

    public static function bootHasPermissions()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->permissions()->detach();
        });
    }

    public function getPermissionClass()
    {
        if (! isset($this->permissionClass)) {
            $this->permissionClass = app(PermissionRegistrar::class)->getPermissionClass();
        }

        return $this->permissionClass;
    }

    /**
     * A role may be has any permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * An alias to hasPermissionTo(), but avoids throwing an exception.
     *
     * @param string|array|int|Permission $permission
     * @return bool
     */
    public function checkPermissionTo($permission): bool
    {
        try {
            return $this->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     * @throws Exception
     * @return bool
     */
    public function hasAnyPermission(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if ($this->checkPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the model has all of the given permissions.
     *
     * @param array ...$permissions
     * @throws Exception
     * @return bool
     */
    public function hasAllPermissions(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if (! $this->checkPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grant the given permission(s) to a role.
     *
     * @param string|array|Permission|Collection $permissions
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        $allPermissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                if (empty($permission)) {
                    return false;
                }

                return $this->getStoredPermission($permission);
            })
            ->filter(function ($permission) {
                return $permission instanceof Permission;
            })
            ->map->id
            ->all();

        $this->permissions()->sync($allPermissions, false);
        $this->load('permissions');

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Revoke the given permission.
     *
     * @param Permission|Permission[]|string|string[] $permission
     * @return $this
     */
    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($this->getStoredPermission($permission));

        $this->forgetCachedPermissions();

        $this->load('permissions');

        return $this;
    }

    /**
     * Remove all current permissions and set the given ones.
     *
     * @param string|array|Permission|Collection $permissions
     * @return $this
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * Return all the permissions the model has via roles.
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissions->sortByDesc('weight');
    }

    /**
     * Return all the tree permissions the model has via roles.
     *
     * @param string $attributeName You want to set name.
     * @param array $columns You want to get columns.
     */
    public function getTreePermissions(string $attributeName = 'tree_permissions', array $columns = ['*'])
    {
        if (! in_array('*', $columns)) {
            $columns = array_merge($columns, ['id', 'pid', 'is_menu']);
        }

        $this->$attributeName = setTree($this->permissions()->get($columns));
    }

    /**
     * Get Permission Model By Name,Id,RouteMethod.
     *
     * @param string|array|Permission|Collection $permissions
     * @return Permission|Permission[]|Collection
     */
    protected function getStoredPermission($permissions)
    {
        $permissionClass = $this->getPermissionClass();

        if (is_numeric($permissions)) {
            return $permissionClass->findById($permissions);
        }

        if (is_string($permissions)) {
            return $permissionClass->findByName($permissions);
        }

        if (is_array($permissions) && isset($permissions['route']) && isset($permissions['method'])) {
            return $permissionClass->findByRouteAndMethod($permissions['route'], $permissions['method']);
        }

        if (is_array($permissions)) {
            return $permissionClass
                ->whereIn('name', $permissions)
                ->get();
        }

        return $permissions;
    }

    /**
     * Get All Permission Name.
     */
    public function getPermissionNames(): Collection
    {
        return $this->permissions->pluck('name');
    }

    /**
     * Get All Permission Display Name.
     */
    public function getPermissionDisplayName(): Collection
    {
        return $this->permissions->pluck('display_name');
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
