<?php

namespace Mingzaily\Permission\Models;

use Mingzaily\Permission\Guard;
use Illuminate\Database\Eloquent\Model;
use Mingzaily\Permission\Traits\HasPermissions;
use Mingzaily\Permission\Exceptions\RoleDoesNotExist;
use Mingzaily\Permission\Exceptions\GuardDoesNotMatch;
use Mingzaily\Permission\Exceptions\RoleAlreadyExists;
use Mingzaily\Permission\Contracts\Role as RoleContract;
use Mingzaily\Permission\Traits\RefreshesPermissionCache;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * A role belongs to some users of the model.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getUserModel(),
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     *
     * @return \Mingzaily\Permission\Contracts\Role|\Mingzaily\Permission\Models\Role
     *
     * @throws \Mingzaily\Permission\Exceptions\RoleDoesNotExist
     */
    public static function findByName(string $name): RoleContract
    {

        $role = static::where('name', $name)->first();

        if (! $role) {
            throw RoleDoesNotExist::named($name);
        }

        return $role;
    }

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
     * @param string $name
     *
     * @return \Mingzaily\Permission\Contracts\Role
     */
    public static function findOrCreate(string $name): RoleContract
    {
        $role = static::where('name', $name)->first();

        if (! $role) {
            return static::query()->create(['name' => $name]);
        }

        return $role;
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     *
     * @throws \Mingzaily\Permission\Exceptions\GuardDoesNotMatch
     */
    public function hasPermissionTo($permission): bool
    {
        if (config('permission.enable_wildcard_permission', false)) {
            return $this->hasWildcardPermission($permission, $this->getDefaultGuardName());
        }

        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName($permission);
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission);
        }

        return $this->permissions->contains('id', $permission->id);
    }
}
