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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Mingzaily\Permission\Contracts\Role;
use Mingzaily\Permission\PermissionRegistrar;
use Mingzaily\Permission\Contracts\Permission;

class CacheTest extends TestCase
{
    protected $cache_init_count = 0;
    protected $cache_load_count = 0;
    protected $cache_run_count = 2; // roles lookup, permissions lookup
    protected $cache_relations_count = 1;

    /**
     * @var PermissionRegistrar|mixed
     */
    protected $registrar;

    public function setUp(): void
    {
        parent::setUp();

        $this->registrar = app(PermissionRegistrar::class);

        $this->registrar->forgetCachedPermissions();

        DB::connection()->enableQueryLog();

        $cacheStore = $this->registrar->getCacheStore();

        switch (true) {
            case $cacheStore instanceof \Illuminate\Cache\DatabaseStore:
                $this->cache_init_count = 1;
                $this->cache_load_count = 1;
                // no break
            default:
        }
    }

    /** @test */
    public function it_can_cache_the_permissions()
    {
        $this->resetQueryCount();

        $this->registrar->getPermissions();

        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    /** @test */
    public function it_flushes_the_cache_when_creating_a_permission()
    {
        app(Permission::class)->create(['name' => 'new', 'display_name' => 'new', 'route' => '/new', 'method' => 'GET']);

        $this->resetQueryCount();

        $this->registrar->getPermissions();

        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    /** @test */
    public function it_flushes_the_cache_when_updating_a_permission()
    {
        $permission = app(Permission::class)->create(['name' => 'new', 'display_name' => 'new', 'route' => '/new', 'method' => 'GET']);

        $permission->name = 'other name';
        $permission->save();

        $this->resetQueryCount();

        $this->registrar->getPermissions();

        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    /** @test */
    public function it_flushes_the_cache_when_creating_a_role()
    {
        app(Role::class)->create(['name' => 'new', 'display_name' => 'new']);

        $this->resetQueryCount();

        $this->registrar->getPermissions();

        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    /** @test */
    public function it_flushes_the_cache_when_updating_a_role()
    {
        $role = app(Role::class)->create(['name' => 'new', 'display_name' => 'new']);

        $role->name = 'other name';
        $role->save();

        $this->resetQueryCount();

        $this->registrar->getPermissions();

        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    /** @test */
    public function it_flushes_the_cache_when_removing_a_role_from_a_user()
    {
        $this->testUser->assignRole('testRole');

        $this->registrar->getPermissions();

        $this->testUser->removeRole('testRole');

        $this->resetQueryCount();

        $this->registrar->getPermissions();

        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    /** @test */
    public function user_creation_should_not_flush_the_cache()
    {
        $this->registrar->getPermissions();

        User::create(['email' => 'new']);

        $this->resetQueryCount();

        $this->registrar->getPermissions();

        // should all be in memory, so no init/load required
        $this->assertQueryCount(0);
    }

    /** @test */
    public function it_flushes_the_cache_when_giving_a_permission_to_a_role()
    {
        $this->testUserRole->givePermissionTo($this->testUserPermission);

        $this->resetQueryCount();

        $this->registrar->getPermissions();

        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    /** @test */
    public function role_check_permission_to_should_use_the_cache()
    {
        $this->testUserRole->givePermissionTo(['edit.articles', 'edit.news', 'edit.blog']);

        $this->resetQueryCount();
        $this->assertTrue($this->testUserRole->hasPermissionTo('edit.articles'));
        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);

        $this->resetQueryCount();
        $this->assertTrue($this->testUserRole->hasPermissionTo('edit.news'));
        $this->assertQueryCount(0);

        $this->resetQueryCount();
        $this->assertTrue($this->testUserRole->hasPermissionTo('edit.blog'));
        $this->assertQueryCount(0);

        $this->resetQueryCount();
        $this->assertFalse($this->testUserRole->hasPermissionTo('admin.permission'));
        $this->assertQueryCount(0);
    }

    /** @test */
    public function user_check_permission_should_use_the_cache()
    {
        $this->testUserRole->givePermissionTo(['edit.articles', 'edit.news', 'edit.blog']);
        $this->testUser->assignRole('testRole');

        $this->resetQueryCount();
        $this->assertTrue($this->testUser->checkPermission('edit.articles'));
        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count + $this->cache_relations_count);

        $this->resetQueryCount();
        $this->assertTrue($this->testUser->checkPermission('edit.news'));
        $this->assertQueryCount(0);

        $this->resetQueryCount();
        $this->assertTrue($this->testUser->checkPermission('edit.blog'));
        $this->assertQueryCount(0);

        $this->resetQueryCount();
        $this->assertFalse($this->testUser->checkPermission('admin.permission'));
        $this->assertQueryCount(0);
    }

    /** @test */
    public function get_all_permissions_should_use_the_cache()
    {
        $this->testUserRole->givePermissionTo($expected = ['edit.articles', 'edit.news']);

        $this->resetQueryCount();
        $this->registrar->getPermissions();
        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);

        $this->resetQueryCount();
        $actual = $this->testUserRole->getAllPermissions()->pluck('name')->sort()->values();
        $this->assertEquals($actual, collect($expected));
        $this->assertQueryCount(0);
    }

    /** @test */
    public function it_can_reset_the_cache_with_artisan_command()
    {
        Artisan::call('permission:cache-reset');

        $this->resetQueryCount();
        $this->registrar->getPermissions();
        // assert that the cache had to be reloaded
        $this->assertQueryCount($this->cache_init_count + $this->cache_load_count + $this->cache_run_count);
    }

    protected function assertQueryCount(int $expected)
    {
        $this->assertCount($expected, DB::getQueryLog());
    }

    protected function resetQueryCount()
    {
        DB::flushQueryLog();
    }
}
