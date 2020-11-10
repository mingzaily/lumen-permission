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

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Contracts\Menu as MenuContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mingzaily\Permission\Traits\RefreshesPermissionCache;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;
use Mingzaily\Permission\Exceptions\PermissionAlreadyExists;

class Menu extends Model implements MenuContract
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

        $this->setTable(config('permission.table_names.menus'));
    }

    /**
     * create permission.
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Builder|Model|Menu|Permission
     */
    public static function create(array $attributes = [])
    {
        $permission = static::getPermissions(['name' => $attributes['name']])->first();

        if ($permission) {
            throw PermissionAlreadyExists::name($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A menu can be belongs to any roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_menus'),
            'permission_id',
            'role_id'
        );
    }

    /**
     * Find a permission by its name (and optionally guardName).
     *
     * @param string $name
     * @return MenuContract
     */
    public static function findByName(string $name): MenuContract
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
     * @return MenuContract
     */
    public static function findById(int $id): MenuContract
    {
        $permission = static::getPermissions(['id' => $id])->first();

        if (! $permission) {
            throw PermissionDoesNotExist::withId($id);
        }

        return $permission;
    }

    /**
     * Find or create permission by its name.
     * @param array $attributes
     * @return MenuContract
     */
    public static function findOrCreate(array $attributes): MenuContract
    {
        $permission = static::getPermissions(['name' => $attributes['name']])->first();

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
            ->setMenuClass(static::class)
            ->getMenus($params);
    }
}
