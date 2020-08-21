<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Mingzaily\Permission\Contracts\Role;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Mingzaily\Permission\Contracts\Permission;
use Illuminate\Contracts\Auth\Access\Authorizable;

class PermissionRegistrar
{
    /** @var Repository */
    protected $cache;

    /** @var CacheManager */
    protected $cacheManager;

    /** @var string */
    protected $permissionClass;

    /** @var string */
    protected $roleClass;

    /** @var Collection */
    protected $permissions;

    /** @var \DateInterval|int */
    public static $cacheExpirationTime;

    /** @var string */
    public static $cacheKey;

    /** @var string */
    public static $cacheModelKey;

    /**
     * PermissionRegistrar constructor.
     *
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->permissionClass = config('permission.models.permission');
        $this->roleClass = config('permission.models.role');

        $this->cacheManager = $cacheManager;
        $this->initializeCache();
    }

    protected function initializeCache()
    {
        self::$cacheExpirationTime = config('permission.cache.expiration_time', config('permission.cache_expiration_time'));

        self::$cacheKey = config('permission.cache.key');
        self::$cacheModelKey = config('permission.cache.model_key');

        $this->cache = $this->getCacheStoreFromConfig();
    }

    protected function getCacheStoreFromConfig(): Repository
    {
        // the 'default' fallback here is from the permission.php config file, where 'default' means to use config(cache.default)
        $cacheDriver = config('permission.cache.store', 'default');

        // when 'default' is specified, no action is required since we already have the default instance
        if ('default' === $cacheDriver) {
            return $this->cacheManager->store();
        }

        // if an undefined cache store is specified, fallback to 'array' which is Laravel's closest equiv to 'none'
        if (! \array_key_exists($cacheDriver, config('cache.stores'))) {
            $cacheDriver = 'array';
        }

        return $this->cacheManager->store($cacheDriver);
    }

    /**
     * Register the permission check method on the gate.
     * We resolve the Gate fresh here, for benefit of long-running instances.
     */
    public function registerPermissions(): bool
    {
        app(Gate::class)->before(function (Authorizable $user, string $ability) {

            $ability = false === strpos($ability, '|') ?
                $ability : ['route' => explode('|', $ability)[0], 'method' => explode('|', $ability)[1]];

            if (method_exists($user, 'checkPermission')) {
                return $user->checkPermission($ability) ?: null;
            }

            return null;
        });

        return true;
    }

    /**
     * Flush the cache.
     */
    public function forgetCachedPermissions()
    {
        $this->permissions = null;

        return $this->cache->forget(self::$cacheKey);
    }

    /**
     * Clear class permissions.
     * This is only intended to be called by the PermissionServiceProvider on boot,
     * so that long-running instances like Swoole don't keep old data in memory.
     */
    public function clearClassPermissions()
    {
        $this->permissions = null;
    }

    /**
     * Get the permissions based on the passed params.
     *
     * @param array $params
     * @return Collection
     */
    public function getPermissions(array $params = []): Collection
    {
        if (null === $this->permissions) {
            $this->permissions = $this->cache->remember(self::$cacheKey, self::$cacheExpirationTime, function () {
                return $this->getPermissionClass()
                    ->with('roles')
                    ->get();
            });
        }

        $permissions = clone $this->permissions;

        foreach ($params as $attr => $value) {
            $permissions = $permissions->where($attr, $value);
        }

        return $permissions;
    }

    /**
     * Get an instance of the permission class.
     */
    public function getPermissionClass(): Permission
    {
        return app($this->permissionClass);
    }

    public function setPermissionClass($permissionClass)
    {
        $this->permissionClass = $permissionClass;

        return $this;
    }

    /**
     * Get an instance of the role class.
     */
    public function getRoleClass(): Role
    {
        return app($this->roleClass);
    }

    /**
     * Get the instance of the Cache Store.
     */
    public function getCacheStore(): \Illuminate\Contracts\Cache\Store
    {
        return $this->cache->getStore();
    }
}
