<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Mingzaily\Permission\PermissionRegistrar;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mingzaily\Permission\Traits\RefreshesPermissionCache;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;
use Mingzaily\Permission\Exceptions\PermissionAlreadyExists;
use Mingzaily\Permission\Contracts\Permission as PermissionContract;

class Permission extends Model implements PermissionContract
{
    use RefreshesPermissionCache;

    protected $guarded = ['id'];

    protected $hidden = ['pivot'];

    /**
     * Permission constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.permissions'));
    }

    /**
     * create permission.
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Builder|Model|Permission
     */
    public static function create(array $attributes = [])
    {
        $permission = static::getPermissions([
            'route' => $attributes['route'],
            'method' => $attributes['method'],
        ])->first();

        if ($permission) {
            throw PermissionAlreadyExists::routeMethod($attributes['route'], $attributes['method']);
        }

        $permission = static::getPermissions(['name' => $attributes['name']])->first();

        if ($permission) {
            throw PermissionAlreadyExists::name($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A permission can be belongs to any roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions'),
            'permission_id',
            'role_id'
        );
    }

    /**
     * Find a permission by its name (and optionally guardName).
     *
     * @param string $name
     * @return PermissionContract
     */
    public static function findByName(string $name): PermissionContract
    {
        $permission = static::getPermissions(['name' => $name])->first();

        if (! $permission) {
            throw PermissionDoesNotExist::create($name);
        }

        return $permission;
    }

    /**
     * Find a permission by its id.
     *
     * @param int $id
     * @return PermissionContract
     */
    public static function findById(int $id): PermissionContract
    {
        $permission = static::getPermissions(['id' => $id])->first();

        if (! $permission) {
            throw PermissionDoesNotExist::withId($id);
        }

        return $permission;
    }

    /**
     * Find a permission by its route and method.
     *
     * @param string $route
     * @param string $method
     * @return PermissionContract
     */
    public static function findByRouteAndMethod(string $route, string $method): PermissionContract
    {
        $ability = ['route' => Str::start($route, '/'), 'method' => strtoupper($method)];
        $permission = static::getPermissions($ability)->first();

        if (! $permission) {
            throw PermissionDoesNotExist::withRouteAndMethod($ability);
        }

        return $permission;
    }

    /**
     * Find or create permission by its name.
     * @param array $attributes
     * @return PermissionContract
     */
    public static function findOrCreate(array $attributes): PermissionContract
    {
        $permission = static::getPermissions(['name' => $attributes['name']])->first();

        if (! $permission
            && isset($attributes['route'])
            && isset($attributes['method'])) {
            $permission = static::getPermissions([
                'route' => $attributes['route'],
                'method' => $attributes['method'],
            ])->first();
        }

        if (! $permission) {
            return static::create($attributes);
        }

        return $permission;
    }

    /**
     * Get the current cached permissions.
     * @param array $params
     * @return Collection
     */
    protected static function getPermissions(array $params = []): Collection
    {
        return app(PermissionRegistrar::class)
            ->setPermissionClass(static::class)
            ->getPermissions($params);
    }
}
