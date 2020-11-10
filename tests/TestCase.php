<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission\Test;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Mingzaily\Permission\Contracts\Role;
use Illuminate\Database\Schema\Blueprint;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Contracts\Permission;
use Orchestra\Testbench\TestCase as Orchestra;
use Mingzaily\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @var User */
    protected $testUser;

    /** @var \Mingzaily\Permission\Models\Role */
    protected $testRole;

    /** @var \Mingzaily\Permission\Models\Permission */
    protected $testPermission;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        // Note: this also flushes the cache from within the migration
        $this->setUpDatabase($this->app);

        $this->testUser = User::query()->first();
        $this->testRole = app(Role::class)->find(1);
        $this->testPermission = app(Permission::class)->find(1);
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
        ];
    }

    /**
     * Set up the environment.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set-up users
        $app['config']->set('auth.guards.api', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);

        $app['config']->set('cache.prefix', 'mingzaily_tests---');

        //
        $app['config']->set('permission.model_has_multiple_roles', false);
    }

    /**
     * Set up the database.
     *
     * @param Application $app
     * @throws Exception
     */
    protected function setUpDatabase($app)
    {
        $app['config']->set('permission.column_names.model_morph_key', 'model_test_id');

        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        if (Cache::getStore() instanceof \Illuminate\Cache\DatabaseStore ||
            $app[PermissionRegistrar::class]->getCacheStore() instanceof \Illuminate\Cache\DatabaseStore) {
            $this->createCacheTable();
        }

        include_once __DIR__.'/../database/migrations/2020_01_01_000000_create_permission_tables.php';

        (new \CreatePermissionTables())->up();

        User::create(['email' => 'test@user.com']);
        User::create(['email' => 'test2@user.com']);
        $app[Role::class]->create(['name' => 'testRole', 'display_name' => 'testRole']);
        $app[Role::class]->create(['name' => 'testRole2', 'display_name' => 'testRole2']);
        $app[Role::class]->create(['name' => 'testRole3', 'display_name' => 'testRole3']);
        $app[Permission::class]->create(['name' => 'edit.articles', 'display_name' => 'edit-articles', 'route' => '/articles', 'method' => 'PUT']);
        $app[Permission::class]->create(['name' => 'edit.news', 'display_name' => 'edit-news', 'route' => '/news', 'method' => 'PUT']);
        $app[Permission::class]->create(['name' => 'edit.blog', 'display_name' => 'edit-blog', 'route' => '/blog', 'method' => 'PUT']);
        $app[Permission::class]->create(['name' => 'admin.permission', 'display_name' => 'admin-permission', 'route' => '/permission', 'method' => 'PUT']);
    }

    /**
     * Reload the permissions.
     */
    protected function reloadPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function createCacheTable()
    {
        Schema::create('cache', function ($table) {
            $table->string('key')->unique();
            $table->text('value');
            $table->integer('expiration');
        });
    }
}
