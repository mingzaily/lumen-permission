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

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Mingzaily\Permission\Contracts\Role as RoleContract;
use Mingzaily\Permission\Contracts\Permission as PermissionContract;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistrar $permissionLoader, Filesystem $filesystem)
    {
        // Please copy files manually
        // config/permission.php
        // database/migrations/create_permission_tables.php.stub

        $this->registerMacroHelpers();

        $this->commands([
            Commands\CacheReset::class,
            Commands\Show::class,
        ]);

        $this->registerModelBindings();

        $permissionLoader->clearClassPermissions();
        $permissionLoader->registerPermissions();

        $this->app->singleton(PermissionRegistrar::class, function ($app) use ($permissionLoader) {
            return $permissionLoader;
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/permission.php',
            'permission'
        );
    }

    protected function registerModelBindings()
    {
        $config = $this->app->config['permission.models'];

        if (!$config) {
            return;
        }

        $this->app->bind(PermissionContract::class, $config['permission']);
        $this->app->bind(RoleContract::class, $config['role']);
    }

    protected function registerMacroHelpers()
    {
        if (!method_exists(Route::class, 'macro')) { // Lumen
            return;
        }

        Route::macro('role', function ($roles = []) {
            if (!is_array($roles)) {
                $roles = [$roles];
            }

            $roles = implode('|', $roles);

            $this->middleware("role:$roles");

            return $this;
        });

        Route::macro('permission', function ($permissions = []) {
            if (!is_array($permissions)) {
                $permissions = [$permissions];
            }

            $permissions = implode('|', $permissions);

            $this->middleware("permission:$permissions");

            return $this;
        });
    }
}
