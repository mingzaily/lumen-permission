<?php

namespace Mingzaily\Permission\Models;

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

/**
 * Mingzaily\Permission\Models\Permission
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $route
 * @property string $method
 * @property int $pid
 * @property int $weight
 * @property int $is_menu
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Mingzaily\Permission\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereIsMenu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereRoute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Mingzaily\Permission\Models\Permission whereWeight($value)
 * @mixin \Eloquent
 */
class Permission extends Model implements PermissionContract
{
    use RefreshesPermissionCache;

    protected $guarded = ['id'];

    protected $hidden = ['pivot'];

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
     * A permission can be belongs to any roles.
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
     * Recursive hasMany Relationship with Unlimited Subcategories
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(config('permission.models.permission'), 'pid', 'id')->orderBy('weight', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childrenPermissions()
    {
        return $this->children()->with('childrenPermissions')->orderBy('weight', 'desc');
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
     * @param array $ability
     *
     * @return \Mingzaily\Permission\Contracts\Permission
     */
    public static function findByRouteAndMethod(array $ability): PermissionContract
    {
        $permission = static::getPermissions($ability)->first();

        if (! $permission) {
            throw PermissionDoesNotExist::withRouteAndMethod($ability);
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
