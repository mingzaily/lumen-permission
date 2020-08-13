<?php

namespace Mingzaily\Permission\Models;

use Mingzaily\Permission\Guard;
use Illuminate\Support\Collection;
use Mingzaily\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Traits\RefreshesPermissionCache;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mingzaily\Permission\Exceptions\PermissionAlreadyExists;
use Mingzaily\Permission\Contracts\Permission as PermissionContract;

class Permission extends Model implements PermissionContract
{
    use HasRoles;
    use RefreshesPermissionCache;

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.permissions'));
    }

    public static function create(array $attributes = [])
    {
        $permission = static::getPermissions([
            'name' => $attributes['name'],
            'display_name' => $attributes['display_name'],
            'route' => $attributes['route'],
            'method' => $attributes['method'],
        ])->first();

        if ($permission) {
            throw PermissionAlreadyExists::create($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A permission can be applied to roles.
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
     *
     * @throws \Mingzaily\Permission\Exceptions\PermissionDoesNotExist
     *
     * @return \Mingzaily\Permission\Contracts\Permission
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
     * Find a permission by its id (and optionally guardName).
     *
     * @param int $id
     *
     * @throws \Mingzaily\Permission\Exceptions\PermissionDoesNotExist
     *
     * @return \Mingzaily\Permission\Contracts\Permission
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
     * @param string $route
     * @param string $method
     *
     * @return \Mingzaily\Permission\Contracts\Permission
     */
    public static function findByRouteAndMethod(string $route, string $method): PermissionContract
    {
        $permission = static::getPermissions(['route' => $route, 'method' => $method])->first();

        if (! $permission) {
            throw PermissionDoesNotExist::withRouteAndMethod($route, $method);
        }

        return $permission;
    }

    /**
     * Find or create permission by its name (and optionally guardName).
     *
     * @param array $attributes
     *
     * @return \Mingzaily\Permission\Contracts\Permission
     */
    public static function findOrCreate(array $attributes): PermissionContract
    {
        $permission = static::getPermissions([
            'name' => $attributes['name'],
            'display_name' => $attributes['display_name'],
            'route' => $attributes['route'],
            'method' => $attributes['method'],
        ])->first();

        if (! $permission) {
            return static::query()->create([
                'name' => $attributes['name'],
                'display_name' => $attributes['display_name'],
                'route' => $attributes['route'],
                'method' => $attributes['method'],
            ]);
        }

        return $permission;
    }

    /**
     * Get the current cached permissions.
     *
     * @param array $params
     *
     * @return Collection
     */
    protected static function getPermissions(array $params = []): Collection
    {
        return app(PermissionRegistrar::class)
            ->setPermissionClass(static::class)
            ->getPermissions($params);
    }
}
