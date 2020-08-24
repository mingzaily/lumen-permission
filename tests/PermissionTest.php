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

use Mingzaily\Permission\Contracts\Permission;
use Mingzaily\Permission\Exceptions\PermissionAlreadyExists;

class PermissionTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_when_the_permission_already_exists()
    {
        $this->expectException(PermissionAlreadyExists::class);

        app(Permission::class)->create(['name' => 'test-permission']);
        app(Permission::class)->create(['name' => 'test-permission']);
    }

    /** @test */
    public function it_belongs_to_a_guard()
    {
        $permission = app(Permission::class)->create(['name' => 'can-edit', 'guard_name' => 'admin']);

        $this->assertEquals('admin', $permission->guard_name);
    }

    /** @test */
    public function it_belongs_to_the_default_guard_by_default()
    {
        $this->assertEquals(
            $this->app['config']->get('auth.defaults.guard'),
            $this->testPermission->guard_name
        );
    }

    /** @test */
    public function it_has_user_models_of_the_right_class()
    {
        $this->testAdmin->givePermissionTo($this->testAdminPermission);

        $this->testUser->givePermissionTo($this->testPermission);

        $this->assertCount(1, $this->testPermission->users);
        $this->assertTrue($this->testPermission->users->first()->is($this->testUser));
        $this->assertInstanceOf(User::class, $this->testPermission->users->first());
    }

    /** @test */
    public function it_is_retrievable_by_id()
    {
        $permission_by_id = app(Permission::class)->findById($this->testPermission->id);

        $this->assertEquals($this->testPermission->id, $permission_by_id->id);
    }
}
