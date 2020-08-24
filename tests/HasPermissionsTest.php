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
use Mingzaily\Permission\Exceptions\PermissionDoesNotExist;

class HasPermissionsTest extends TestCase
{
    /** @test */
    public function it_can_given_permission_via_roles_of_user()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $roleModel = app(Role::class);
        $roleModel->findByName('testRole2')->givePermissionTo('edit.news');

        $this->testRole->givePermissionTo('edit.articles');
        $this->testUser->assignRole('testRole', 'testRole2');

        $this->assertEquals(
            collect(['edit.articles', 'edit.news']),
            $this->testUser->getPermissionsViaRole()->pluck('name')
        );
    }

    /** @test */
    public function it_can_given_permission_of_role()
    {
        $this->testRole->givePermissionTo(['edit.articles', 'edit.news']);

        $this->assertEquals(
            collect(['edit.articles', 'edit.news']),
            $this->testRole->getAllPermissions()->flatten()->pluck('name')
        );
    }

    /** @test */
    public function it_can_given_and_remove_a_permission()
    {
        $this->assertFalse($this->testRole->hasPermissionTo('edit.news'));

        $this->testRole->givePermissionTo('edit.news');

        $this->assertTrue($this->testRole->hasPermissionTo('edit.news'));

        $this->testRole->revokePermissionTo('edit.news');

        $this->assertFalse($this->testRole->hasPermissionTo('edit.news'));
    }

    /** @test */
    public function it_can_given_a_permission_using_an_object()
    {
        $this->testRole->givePermissionTo($this->testPermission);

        $this->assertTrue($this->testRole->hasPermissionTo($this->testPermission));
    }

    /** @test */
    public function it_can_given_a_permission_using_an_id()
    {
        $this->testRole->givePermissionTo($this->testPermission->id);

        $this->assertTrue($this->testRole->hasPermissionTo($this->testPermission));
    }

    /** @test */
    public function it_can_sync_permissions_from_a_string()
    {
        $this->testRole->givePermissionTo('edit.news');

        $this->testRole->syncPermissions('edit.articles');

        $this->assertFalse($this->testRole->hasPermissionTo('edit.news'));

        $this->assertTrue($this->testRole->hasPermissionTo('edit.articles'));
    }

    /** @test */
    public function it_can_sync_permissions_by_array()
    {
        $this->testRole->syncPermissions(['edit.news', 'edit.articles']);

        $this->assertTrue($this->testRole->hasPermissionTo('edit.news'));

        $this->assertTrue($this->testRole->hasPermissionTo('edit.articles'));
    }

    /** @test */
    public function it_will_remove_all_permissions_when_an_empty_array()
    {
        $this->testRole->givePermissionTo('edit.articles', 'edit.articles');

        $this->assertTrue($this->testRole->hasPermissionTo('edit.articles'));

        $this->testRole->syncPermissions([]);

        $this->assertFalse($this->testRole->hasPermissionTo('edit.articles'));
    }

    /** @test */
    public function it_throws_an_exception_when_permission_does_not()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $this->testRole->givePermissionTo('test.test');
    }
}
