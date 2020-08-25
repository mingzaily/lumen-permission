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
use Mingzaily\Permission\Exceptions\PermissionNotMenu;

class PermissionTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_when_the_permission_already_exists_same_name()
    {
        $this->expectException(PermissionAlreadyExists::class);

        app(Permission::class)->create(['name' => 'test.permission', 'display_name' => 'test.permission', 'route' => '/permission', 'method' => 'POST']);
        app(Permission::class)->create(['name' => 'test.permission', 'display_name' => 'test.permission', 'route' => '/permission', 'method' => 'POST']);
    }

    /** @test */
    public function it_throws_an_exception_when_the_permission_already_exists_same_route_method()
    {
        $this->expectException(PermissionAlreadyExists::class);

        app(Permission::class)->create(['name' => 'test.permission', 'display_name' => 'test.permission', 'route' => '/permission', 'method' => 'POST']);
        app(Permission::class)->create(['name' => 'test.permission2', 'display_name' => 'test.permission2', 'route' => '/permission', 'method' => 'POST']);
    }

    /** @test */
    public function it_throws_an_exception_when_the_permission_not_menu()
    {
        $this->expectException(PermissionNotMenu::class);

        app(Permission::class)->create(['name' => 'test.permission', 'display_name' => 'test.permission']);
    }

    /** @test */
    public function it_not_throws_an_exception_when_the_permission_not_menu()
    {
        app(Permission::class)->create(['name' => 'test.permission', 'display_name' => 'test.permission', 'is_menu' => 1]);

        $permission = app(Permission::class)->findByName('test.permission');

        $this->assertEquals('test.permission', $permission->name);
    }

    /** @test */
    public function it_is_retrievable_by_id()
    {
        $permission_by_id = app(Permission::class)->findById($this->testPermission->id);

        $this->assertEquals($this->testPermission->id, $permission_by_id->id);
    }
}
