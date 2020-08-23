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
use Mingzaily\Permission\Contracts\Permission;

class HasPermissionsTest extends TestCase
{
    /** @test */
    public function it_can_list_all_the_permissions_via_roles_of_user()
    {
        $roleModel = app(Role::class);
        $roleModel->findByName('testRole2')->givePermissionTo('edit.news');

        $this->testUserRole->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole', 'testRole2');

        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getPermissionsViaRoles()->pluck('name')
        );
    }

    /** @test */
    public function it_can_list_all_the_coupled_permissions_both_directly_and_via_roles()
    {
        $this->testUser->givePermissionTo('edit-news');

        $this->testUserRole->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole');

        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getAllPermissions()->pluck('name')->sort()->values()
        );
    }

    /** @test */
    public function it_can_sync_multiple_permissions()
    {
        $this->testUser->givePermissionTo('edit-news');

        $this->testUser->syncPermissions('edit-articles', 'edit-blog');

        $this->assertTrue($this->testUser->hasDirectPermission('edit-articles'));

        $this->assertTrue($this->testUser->hasDirectPermission('edit-blog'));

        $this->assertFalse($this->testUser->hasDirectPermission('edit-news'));
    }

    /** @test */
    public function it_can_sync_multiple_permissions_by_id()
    {
        $this->testUser->givePermissionTo('edit-news');

        $ids = app(Permission::class)::whereIn('name', ['edit-articles', 'edit-blog'])->pluck('id');

        $this->testUser->syncPermissions($ids);

        $this->assertTrue($this->testUser->hasDirectPermission('edit-articles'));

        $this->assertTrue($this->testUser->hasDirectPermission('edit-blog'));

        $this->assertFalse($this->testUser->hasDirectPermission('edit-news'));
    }

    /** @test */
    public function sync_permission_ignores_null_inputs()
    {
        $this->testUser->givePermissionTo('edit-news');

        $ids = app(Permission::class)::whereIn('name', ['edit-articles', 'edit-blog'])->pluck('id');

        $ids->push(null);

        $this->testUser->syncPermissions($ids);

        $this->assertTrue($this->testUser->hasDirectPermission('edit-articles'));

        $this->assertTrue($this->testUser->hasDirectPermission('edit-blog'));

        $this->assertFalse($this->testUser->hasDirectPermission('edit-news'));
    }

    /** @test */
    public function it_does_not_remove_already_associated_permissions_when_assigning_new_permissions()
    {
        $this->testUser->givePermissionTo('edit-news');

        $this->testUser->givePermissionTo('edit-articles');

        $this->assertTrue($this->testUser->fresh()->hasDirectPermission('edit-news'));
    }

    /** @test */
    public function it_does_not_throw_an_exception_when_assigning_a_permission_that_is_already_assigned()
    {
        $this->testUser->givePermissionTo('edit-news');

        $this->testUser->givePermissionTo('edit-news');

        $this->assertTrue($this->testUser->fresh()->hasDirectPermission('edit-news'));
    }

    /** @test */
    public function it_can_sync_permissions_to_a_model_that_is_not_persisted()
    {
        $user = new User(['email' => 'test@user.com']);
        $user->syncPermissions('edit-articles');
        $user->save();

        $this->assertTrue($user->hasPermissionTo('edit-articles'));

        $user->syncPermissions('edit-articles');
        $this->assertTrue($user->hasPermissionTo('edit-articles'));
        $this->assertTrue($user->fresh()->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function calling_givePermissionTo_before_saving_object_doesnt_interfere_with_other_objects()
    {
        $user = new User(['email' => 'test@user.com']);
        $user->givePermissionTo('edit-news');
        $user->save();

        $user2 = new User(['email' => 'test2@user.com']);
        $user2->givePermissionTo('edit-articles');
        $user2->save();

        $this->assertTrue($user2->fresh()->hasPermissionTo('edit-articles'));
        $this->assertFalse($user2->fresh()->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function calling_syncPermissions_before_saving_object_doesnt_interfere_with_other_objects()
    {
        $user = new User(['email' => 'test@user.com']);
        $user->syncPermissions('edit-news');
        $user->save();

        $user2 = new User(['email' => 'test2@user.com']);
        $user2->syncPermissions('edit-articles');
        $user2->save();

        $this->assertTrue($user2->fresh()->hasPermissionTo('edit-articles'));
        $this->assertFalse($user2->fresh()->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_retrieve_permission_names()
    {
        $this->testUser->givePermissionTo('edit-news', 'edit-articles');
        $this->assertEquals(
            collect(['edit-news', 'edit-articles']),
            $this->testUser->getPermissionNames()
        );
    }

    /** @test */
    public function it_can_check_many_direct_permissions()
    {
        $this->testUser->givePermissionTo(['edit-articles', 'edit-news']);
        $this->assertTrue($this->testUser->hasAllDirectPermissions(['edit-news', 'edit-articles']));
        $this->assertTrue($this->testUser->hasAllDirectPermissions('edit-news', 'edit-articles'));
        $this->assertFalse($this->testUser->hasAllDirectPermissions(['edit-articles', 'edit-news', 'edit-blog']));
        $this->assertFalse($this->testUser->hasAllDirectPermissions(['edit-articles', 'edit-news'], 'edit-blog'));
    }

    /** @test */
    public function it_can_check_if_there_is_any_of_the_direct_permissions_given()
    {
        $this->testUser->givePermissionTo(['edit-articles', 'edit-news']);
        $this->assertTrue($this->testUser->hasAnyDirectPermission(['edit-news', 'edit-blog']));
        $this->assertTrue($this->testUser->hasAnyDirectPermission('edit-news', 'edit-blog'));
        $this->assertFalse($this->testUser->hasAnyDirectPermission('edit-blog', 'Edit News', ['Edit News']));
    }
}
