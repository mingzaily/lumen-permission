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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Mingzaily\Permission\Exceptions\PermissionIsMenu;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Contracts\Permission;
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
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            $model->permissions()->detach();
        });
    }

    public function getPermissionClass()
    {
        if (!isset($this->permissionClass)) {
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
     */
    public function checkPermissionTo($permission): bool
    {
        try {
            return $this->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist $e) {
            return false;
        } catch (PermissionIsMenu $e) {
            return true;
        }
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     *
     * @throws Exception
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
     *
     * @throws Exception
     */
    public function hasAllPermissions(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if (!$this->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grant the given permission(s) to a role.
     *
     * @param string|array|Permission|Collection $permissions
     *
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        $permissions = collect($permissions)
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
            ->map(function ($permission) {
                //TODO：判断父节点是否已在范围之内
                dd($permission->map->pid);
            })
            ->map->id
            ->all();

        $model = $this->getModel();

        if ($model->exists) {
            $this->permissions()->sync($permissions, false);
            $model->load('permissions');
        } else {
            $class = \get_class($model);

            $class::saved(
                function ($object) use ($permissions, $model) {
                    static $modelLastFiredOn;
                    if (null !== $modelLastFiredOn && $modelLastFiredOn === $model) {
                        return;
                    }
                    $object->permissions()->sync($permissions, false);
                    $object->load('permissions');
                    $modelLastFiredOn = $object;
                }
            );
        }

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Revoke the given permission.
     *
     * @param Permission|Permission[]|string|string[] $permission
     *
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
     * Return all the permissions the model has via roles.
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissions()->getResults();
    }

    /**
     * Return all the tree permissions the model has via roles.
     *
     * @param null $pid
     * @param null $allPermissions
     */
    public function getTreePermissions($pid = null, $allPermissions = null): Collection
    {
        //TODO 修复子节点未在权限仍然显示的bug
//        return $this->permissions()->with('childrenPermissions')
//            ->whereNull('pid')
//            ->orderBy('weight', 'desc')
//            ->getResults();
        if (is_null($allPermissions)) {
            // 从数据库中一次性取出所有类目
            $allPermissions = $this->getAllPermissions();
        }

        return $allPermissions
            // 从所有类目中挑选出父类目 ID 为 $parentId 的类目
            ->where('pid', $pid)
            // 遍历这些类目，并用返回值构建一个新的集合
            ->map(function (Permission $permission) use ($allPermissions) {
                $data = $permission;
                // 如果当前类目不是父类目，则直接返回
                if (!$permission->is_menu) {
                    return $data;
                }
                // 否则递归调用本方法，将返回值放入 children 字段中
                $data['children'] = $this->getTreePermissions($permission->id, $allPermissions);

                return $data;
            });
    }

    /**
     * Get Permission Model By Name,Id,RouteMethod.
     *
     * @param string|array|Permission|Collection $permissions
     *
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
            return $permissionClass->findByRouteAndMethod($permissions);
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
