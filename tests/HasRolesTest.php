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
use Mingzaily\Permission\Exceptions\RoleDoesNotExist;
use Mingzaily\Permission\Exceptions\RoleAlreadyExists;

class HasRolesTest extends TestCase
{
    /** @test */
    public function it_can_determine_that_the_user_does_not_have_a_role()
    {
        $this->assertFalse($this->testUser->hasRole('testRole'));

        $role = app(Role::class)->findOrCreate(['name' => 'testRole3']);

        $this->assertFalse($this->testUser->hasRole($role));

        $this->testUser->assignRole($role);
        $this->assertTrue($this->testUser->hasRole($role));
        $this->assertTrue($this->testUser->hasRole($role->name));
        $this->assertTrue($this->testUser->hasRole([$role->name, 'fakeRole']));
        $this->assertTrue($this->testUser->hasRole($role->id));

        $role = app(Role::class)->findOrCreate(['name' => 'testRole4', 'display_name' => 'testRole4']);
        $this->assertFalse($this->testUser->hasRole($role));
    }

    /** @test */
    public function it_can_assign_and_remove_a_role()
    {
        $this->assertFalse($this->testUser->hasRole('testRole'));

        $this->testUser->assignRole('testRole');

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $this->testUser->removeRole('testRole');

        $this->assertFalse($this->testUser->hasRole('testRole'));
    }

    /** @test */
    public function it_removes_a_role_and_returns_roles()
    {
        $this->testUser->assignRole('testRole');

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $roles = $this->testUser->removeRole('testRole');

        $this->assertFalse($roles->hasRole('testRole'));
    }

    /** @test */
    public function it_can_assign_a_role_using_an_object()
    {
        $this->testUser->assignRole($this->testRole);

        $this->assertTrue($this->testUser->hasRole($this->testRole));
    }

    /** @test */
    public function it_can_assign_a_role_using_an_id()
    {
        $this->testUser->assignRole($this->testRole->id);

        $this->assertTrue($this->testUser->hasRole($this->testRole));
    }

    /** @test */
    public function it_cannot_assign_multiple_roles_at_once_when_config_is_false()
    {
        $this->expectException(RoleAlreadyExists::class);

        $this->testUser->assignRole($this->testRole->id, 'testRole2');
    }

    /** @test */
    public function it_cannot_assign_multiple_roles_using_an_array_when_config_is_false()
    {
        $this->expectException(RoleAlreadyExists::class);

        $this->testUser->assignRole([$this->testRole->id, 'testRole2']);
    }

    /** @test */
    public function it_cannot_assign_multiple_roles_when_it_has_role_and_config_is_false()
    {
        $this->expectException(RoleAlreadyExists::class);

        $this->testUser->assignRole($this->testRole->id);
        $this->testUser->assignRole('testRole2');
    }

    /** @test */
    public function it_can_assign_multiple_roles_at_once_when_config_is_true()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->assignRole($this->testRole->id, 'testRole2');

        $this->assertTrue($this->testUser->hasRole('testRole'));
        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_can_assign_multiple_roles_using_an_array_when_config_is_true()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->assignRole([$this->testRole->id, 'testRole2']);

        $this->assertTrue($this->testUser->hasRole('testRole'));
        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_can_assign_multiple_roles_when_it_has_role_and_config_is_true()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->assignRole($this->testRole->id);
        $this->testUser->assignRole('testRole2');

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_does_not_remove_already_associated_roles_when_assigning_new_roles_and_config_is_true()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->assignRole($this->testRole->id);

        $this->testUser->assignRole('testRole2');

        $this->assertTrue($this->testUser->fresh()->hasRole('testRole'));
    }

    /** @test */
    public function it_does_not_throw_an_exception_when_assigning_a_role_that_is_already_assigned()
    {
        $this->testUser->assignRole($this->testRole->id);

        $this->testUser->assignRole($this->testRole->id);

        $this->assertTrue($this->testUser->fresh()->hasRole('testRole'));
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_role_that_does_not_exist()
    {
        $this->expectException(RoleDoesNotExist::class);

        $this->testUser->assignRole('evil-emperor');
    }

    /** @test */
    public function it_can_sync_roles_from_a_string()
    {
        $this->testUser->assignRole('testRole');

        $this->testUser->syncRoles('testRole2');

        $this->assertFalse($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_cannot_sync_multiple_roles_when_config_is_false()
    {
        $this->expectException(RoleAlreadyExists::class);

        $this->testUser->syncRoles('testRole', 'testRole2');
    }

    /** @test */
    public function it_can_sync_multiple_roles_when_config_is_true()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->syncRoles('testRole', 'testRole2');

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_can_sync_multiple_roles_from_an_array()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->syncRoles(['testRole', 'testRole2']);

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_will_remove_all_roles_when_an_empty_array_is_passed_to_sync_roles()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->assignRole('testRole');

        $this->testUser->assignRole('testRole2');

        $this->testUser->syncRoles([]);

        $this->assertFalse($this->testUser->hasRole('testRole'));

        $this->assertFalse($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_will_sync_roles_to_a_model_that_is_not_persisted()
    {
        $user = new User(['email' => 'test@user.com']);
        $user->save();
        $user->syncRoles([$this->testRole]);

        $this->assertTrue($user->hasRole($this->testRole));
    }

    /** @test */
    public function calling_syncRoles_before_saving_object_doesnt_interfere_with_other_objects()
    {
        $user = new User(['email' => 'test@user.com']);
        $user->save();
        $user->syncRoles('testRole');

        $user2 = new User(['email' => 'test2@user.com']);
        $user2->save();
        $user2->syncRoles('testRole2');

        $this->assertTrue($user2->fresh()->hasRole('testRole2'));
        $this->assertFalse($user2->fresh()->hasRole('testRole'));
    }

    /** @test */
    public function calling_assignRole_before_saving_object_doesnt_interfere_with_other_objects()
    {
        $user = new User(['email' => 'test@user.com']);
        $user->save();
        $user->assignRole('testRole');

        $admin_user = new User(['email' => 'admin@user.com']);
        $admin_user->save();
        $admin_user->assignRole('testRole2');

        $this->assertTrue($admin_user->fresh()->hasRole('testRole2'));
        $this->assertFalse($admin_user->fresh()->hasRole('testRole'));
    }

    /** @test */
    public function it_throws_an_exception_when_user_has_a_role()
    {
        $this->expectException(RoleDoesNotExist::class);

        $this->testUser->syncRoles('testAdminRole');
    }

    /** @test */
    public function it_deletes_pivot_table_entries_when_deleting_models()
    {
        $user = User::create(['email' => 'user@test.com']);

        $user->assignRole('testRole');

        $this->assertDatabaseHas('model_has_roles', [config('permission.column_names.model_morph_key') => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('model_has_roles', [config('permission.column_names.model_morph_key') => $user->id]);
    }

    /** @test */
    public function it_can_determine_that_a_user_has_one_of_the_given_roles()
    {
        $roleModel = app(Role::class);

        $roleModel->create(['name' => 'second role', 'display_name' => 'second role']);

        $this->assertFalse($this->testUser->hasRole($roleModel->all()));

        $this->testUser->assignRole($this->testRole);

        $this->assertTrue($this->testUser->hasRole($roleModel->all()));

        $this->assertTrue($this->testUser->hasAnyRole($roleModel->all()));

        $this->assertTrue($this->testUser->hasAnyRole('testRole'));

        $this->assertFalse($this->testUser->hasAnyRole('role does not exist'));

        $this->assertTrue($this->testUser->hasAnyRole(['testRole']));

        $this->assertTrue($this->testUser->hasAnyRole(['testRole', 'role does not exist']));

        $this->assertFalse($this->testUser->hasAnyRole(['role does not exist']));

        $this->assertTrue($this->testUser->hasAnyRole('testRole', 'role does not exist'));
    }

    /** @test */
    public function it_can_determine_that_a_user_has_all_of_the_given_roles()
    {
        $roleModel = app(Role::class);

        $this->assertFalse($this->testUser->hasAllRoles($roleModel->first()));

        $this->assertFalse($this->testUser->hasAllRoles('testRole'));

        $this->assertFalse($this->testUser->hasAllRoles($roleModel->all()));

        $roleModel->create(['name' => 'second role', 'display_name' => 'second role']);

        $this->testUser->assignRole($this->testRole);

        $this->assertTrue($this->testUser->hasAllRoles('testRole'));
        $this->assertFalse($this->testUser->hasAllRoles(['testRole', 'second role']));

        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->assignRole('second role');

        $this->assertTrue($this->testUser->hasAllRoles(['testRole', 'second role']));
    }

    /** @test */
    public function it_can_retrieve_role_names()
    {
        // change config
        $this->app['config']->set('permission.model_has_multiple_roles', true);

        $this->testUser->assignRole('testRole', 'testRole2');

        $this->assertEquals(
            collect(['testRole', 'testRole2']),
            $this->testUser->getRoleNames()
        );
    }
}
