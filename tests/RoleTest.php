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

use Mingzaily\Permission\Contracts\Role;
use Mingzaily\Permission\Models\Permission;
use Mingzaily\Permission\Exceptions\RoleDoesNotExist;
use Mingzaily\Permission\Exceptions\GuardDoesNotMatch;
use Mingzaily\Permission\Exceptions\RoleAlreadyExists;
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;

class RoleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'admin.test1', 'display_name' => 'admin.test1', 'route' => '/test1', 'method' => 'PUT']);

        Permission::create(['name' => 'admin.test2', 'display_name' => 'admin.test2', 'route' => '/test2', 'method' => 'PUT']);
    }

    /** @test */
    public function it_has_user_models_of_the_right_class()
    {
        $this->testUser->assignRole($this->testRole);

        $this->testUser->assignRole($this->testRole);

        $this->assertCount(1, $this->testRole->users);
        $this->assertTrue($this->testRole->users->first()->is($this->testUser));
        $this->assertInstanceOf(User::class, $this->testRole->users->first());
    }

    /** @test */
    public function it_throws_an_exception_when_the_role_already_exists()
    {
        $this->expectException(RoleAlreadyExists::class);

        app(Role::class)->create(['name' => 'test-role', 'display_name' => 'test-role']);
        app(Role::class)->create(['name' => 'test-role', 'display_name' => 'test-role']);
    }

    /** @test */
    public function it_can_be_given_a_permission()
    {
        $this->testRole->givePermissionTo('edit.articles');

        $this->assertTrue($this->testRole->hasPermissionTo('edit.articles'));
    }

    /** @test */
    public function it_throws_an_exception_when_given_a_permission_that_does_not_exist()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $this->testRole->givePermissionTo('create.evil.empire');
    }

    /** @test */
    public function it_can_be_given_multiple_permissions_using_an_array()
    {
        $this->testRole->givePermissionTo(['edit.articles', 'edit.news']);

        $this->assertTrue($this->testRole->hasPermissionTo('edit.articles'));
        $this->assertTrue($this->testRole->hasPermissionTo('edit.news'));
    }

    /** @test */
    public function it_can_be_given_multiple_permissions_using_multiple_arguments()
    {
        $this->testRole->givePermissionTo('edit.articles', 'edit.news');

        $this->assertTrue($this->testRole->hasPermissionTo('edit.articles'));
        $this->assertTrue($this->testRole->hasPermissionTo('edit.news'));
    }

    /** @test */
    public function it_can_sync_permissions()
    {
        $this->testRole->givePermissionTo('edit.articles');

        $this->testRole->syncPermissions('edit.news');

        $this->assertFalse($this->testRole->hasPermissionTo('edit.articles'));

        $this->assertTrue($this->testRole->hasPermissionTo('edit.news'));
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_do_not_exist()
    {
        $this->testRole->givePermissionTo('edit.articles');

        $this->expectException(PermissionDoesNotExist::class);

        $this->testRole->syncPermissions('permission.does.not.exist');
    }

    /** @test */
    public function it_will_remove_all_permissions_when_passing_an_empty_array_to_sync_permissions()
    {
        $this->testRole->givePermissionTo('edit.articles');

        $this->testRole->givePermissionTo('edit.news');

        $this->testRole->syncPermissions([]);

        $this->assertFalse($this->testRole->hasPermissionTo('edit.articles'));

        $this->assertFalse($this->testRole->hasPermissionTo('edit.news'));
    }

    /** @test */
    public function it_can_revoke_a_permission()
    {
        $this->testRole->givePermissionTo('edit.articles');

        $this->assertTrue($this->testRole->hasPermissionTo('edit.articles'));

        $this->testRole->revokePermissionTo('edit.articles');

        $this->testRole = $this->testRole->fresh();

        $this->assertFalse($this->testRole->hasPermissionTo('edit.articles'));
    }

    /** @test */
    public function it_can_be_given_a_permission_using_objects()
    {
        $this->testRole->givePermissionTo($this->testPermission);

        $this->assertTrue($this->testRole->hasPermissionTo($this->testPermission));
    }

    /** @test */
    public function it_throws_an_exception_if_the_permission_does_not_exist()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $this->testRole->hasPermissionTo('doesnt.exist');
    }

    /** @test */
    public function it_returns_false_if_it_does_not_have_a_permission_object()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $permission = app(Permission::class)->findByName('other.permission');

        $this->assertFalse($this->testRole->hasPermissionTo($permission));
    }

    /** @test */
    public function it_creates_permission_object_with_findOrCreate_if_it_does_not_have_a_permission_object()
    {
        $permission = app(Permission::class)->findOrCreate(['name' => 'another.permission', 'display_name' => 'another.permission', 'route' => '/another.permission', 'method' => 'PUT']);

        $this->assertFalse($this->testRole->hasPermissionTo($permission));

        $this->testRole->givePermissionTo($permission);

        $this->testRole = $this->testRole->fresh();

        $this->assertTrue($this->testRole->hasPermissionTo('another.permission'));
    }

    /** @test */
    public function it_creates_a_role_with_findOrCreate_if_the_named_role_does_not_exist()
    {
        $this->expectException(RoleDoesNotExist::class);

        $role1 = app(Role::class)->findByName('non.existing.role');

        $this->assertNull($role1);

        $role2 = app(Role::class)->findOrCreate('yet.another.role');

        $this->assertInstanceOf(Role::class, $role2);
    }
}
