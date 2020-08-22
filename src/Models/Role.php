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

use Illuminate\Database\Eloquent\Model;
use Mingzaily\Permission\Contracts\Permission;
use Mingzaily\Permission\Traits\HasPermissions;
use Mingzaily\Permission\Exceptions\PermissionIsMenu;
use Mingzaily\Permission\Exceptions\RoleDoesNotExist;
use Mingzaily\Permission\Exceptions\RoleAlreadyExists;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Mingzaily\Permission\Contracts\Role as RoleContract;
use Mingzaily\Permission\Traits\RefreshesPermissionCache;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Mingzaily\Permission\Models\Role.
 *
 * @property int                                                         $id
 * @property string                                                      $name
 * @property string                                                      $display_name
 * @property \Illuminate\Support\Carbon|null                             $created_at
 * @property \Illuminate\Support\Carbon|null                             $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property int|null                                                    $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Role extends Model implements RoleContract
{
    use HasPermissions;
    use RefreshesPermissionCache;

    protected $guarded = ['id'];

    protected $hidden = ['pivot'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.roles'));
    }

    public static function create(array $attributes = [])
    {
        if (static::where('name', $attributes['name'])->first()) {
            throw RoleAlreadyExists::create($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A role belongs to some users of the model.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('auth.providers.users.model'),
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * Find a role by its name.
     *
     * @param string $name
     * @return RoleContract|Role
     */
    public static function findByName(string $name): RoleContract
    {
        $role = static::where('name', $name)->first();

        if (! $role) {
            throw RoleDoesNotExist::named($name);
        }

        return $role;
    }

    /**
     * Find a role by its id.
     *
     * @param int $id
     * @return RoleContract|Role
     */
    public static function findById(int $id): RoleContract
    {
        $role = static::where('id', $id)->first();

        if (! $role) {
            throw RoleDoesNotExist::withId($id);
        }

        return $role;
    }

    /**
     * Find or create role by its name.
     *
     * @param array $attributes
     * @return RoleContract
     */
    public static function findOrCreate(array $attributes = []): RoleContract
    {
        $role = static::where(['name' => $attributes['name']])->first();

        if (! $role) {
            return static::query()->create($attributes);
        }

        return $role;
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|int|Permission|array $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName($permission);
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission);
        }

        if (is_array($permission) && isset($permission['route']) && isset($permission['method'])) {
            $permission = $permissionClass->findByRouteAndMethod($permission['route'], $permission['method']);
        }

        if (! $permission instanceof Permission) {
            throw new PermissionDoesNotExist();
        }

        $bool = $this->permissions->contains('id', $permission->id);

        if ($permission->is_menu) {
            throw PermissionIsMenu::isMenu($permission->name);
        }

        return $bool;
    }
}
