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

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mingzaily\Permission\Contracts\Permission;
use Mingzaily\Permission\Middlewares\PermissionRouteMiddleware;
use Mingzaily\Permission\Middlewares\RoleMiddleware;
use Mingzaily\Permission\Exceptions\UnauthorizedException;
use Mingzaily\Permission\Middlewares\PermissionMiddleware;

class MiddlewareTest extends TestCase
{
    protected $roleMiddleware;
    protected $permissionMiddleware;
    protected $permissionRouteMiddleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->roleMiddleware = new RoleMiddleware();

        $this->permissionMiddleware = new PermissionMiddleware();

        $this->permissionRouteMiddleware = new PermissionRouteMiddleware();
    }

    /** @test */
    public function a_guest_cannot_access_a_route_protected_by_the_permission_middleware()
    {
        $this->assertEquals(
            401, $this->runMiddleware(
                $this->permissionMiddleware, 'edit.news'
            ));
    }

    /** @test */
    public function a_guest_cannot_access_a_route_protected_by_role_middleware()
    {
        $this->assertEquals(
            401, $this->runMiddleware(
                $this->roleMiddleware, 'testRole'
            ));
    }

    /** @test */
    public function a_guest_cannot_access_a_route_protected_by_the_permission_route_middleware()
    {
        $this->assertEquals(
            401, $this->runMiddleware(
            $this->permissionRouteMiddleware, '/news@PUT'
        ));
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_role_middleware_if_have_this_role()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole('testRole');

        $this->assertEquals(
            200, $this->runMiddleware(
                $this->roleMiddleware, 'testRole'
            ));
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_this_role_middleware_if_have_one_of_the_roles()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole('testRole');

        $this->assertEquals(
            200, $this->runMiddleware(
                $this->roleMiddleware, 'testRole|testRole2'
            ));

        $this->assertEquals(
            200, $this->runMiddleware(
                $this->roleMiddleware, ['testRole2', 'testRole']
            ));
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_the_role_middleware_if_have_a_different_role()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole(['testRole']);

        $this->assertEquals(
            403, $this->runMiddleware(
                $this->roleMiddleware, 'testRole2'
            ));
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_role_middleware_if_have_not_roles()
    {
        Auth::login($this->testUser);

        $this->assertEquals(
            403, $this->runMiddleware(
                $this->roleMiddleware, 'testRole|testRole2'
            ));
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_role_middleware_if_role_is_undefined()
    {
        Auth::login($this->testUser);

        $this->assertEquals(
            403, $this->runMiddleware(
                $this->roleMiddleware, ''
            ));
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_the_permission_middleware()
    {
        Auth::login($this->testUser);

        $this->testRole->givePermissionTo('edit.articles');
        $this->testUser->assignRole($this->testRole);

        $this->assertEquals(
            200, $this->runMiddleware(
                $this->permissionMiddleware, 'edit.articles'
            ));

        $this->assertEquals(
            403, $this->runMiddleware(
                $this->permissionMiddleware, 'edit.news'
            ));
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_the_permission_route_middleware()
    {
        Auth::login($this->testUser);

        $this->testRole->givePermissionTo('edit.articles');
        $this->testUser->assignRole($this->testRole);

        $this->assertEquals(
            200, $this->runMiddleware(
            $this->permissionRouteMiddleware, "/articles@PUT"
        ));

        $this->assertEquals(
            403, $this->runMiddleware(
            $this->permissionRouteMiddleware, "/news@PUT"
        ));
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_permission_middleware_if_have_this_permission()
    {
        Auth::login($this->testUser);

        $this->testRole->givePermissionTo('edit.articles');
        $this->testUser->assignRole($this->testRole);

        $this->assertEquals(
            200, $this->runMiddleware(
                $this->permissionMiddleware, 'edit.articles'
            ));
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_permission_route_middleware_if_have_this_permission()
    {
        Auth::login($this->testUser);

        $this->testRole->givePermissionTo('edit.articles');
        $this->testUser->assignRole($this->testRole);

        $this->assertEquals(
            200, $this->runMiddleware(
            $this->permissionRouteMiddleware, '/articles@PUT'
        ));
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_this_permission_middleware_if_have_one_of_the_permissions()
    {
        Auth::login($this->testUser);

        $this->testRole->givePermissionTo('edit.articles');
        $this->testUser->assignRole($this->testRole);

        $this->assertEquals(
            200, $this->runMiddleware(
                $this->permissionMiddleware, 'edit.news|edit.articles'
            ));

        $this->assertEquals(
            200, $this->runMiddleware(
                $this->permissionMiddleware, ['edit.news', 'edit.articles']
            ));
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_the_permission_middleware_if_have_a_different_permission()
    {
        Auth::login($this->testUser);

        $this->testRole->givePermissionTo('edit.articles');
        $this->testUser->assignRole($this->testRole);

        $this->assertEquals(
            403, $this->runMiddleware(
                $this->permissionMiddleware, 'edit.news'
            ));
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_permission_middleware_if_have_not_permissions()
    {
        Auth::login($this->testUser);

        $this->assertEquals(
            403, $this->runMiddleware(
                $this->permissionMiddleware, 'edit.articles|edit.news'
            ));
    }

    protected function runMiddleware($middleware, $parameter)
    {
        try {
            return $middleware->handle(new Request(), function () {
                return (new Response())->setContent('');
            }, $parameter)->status();
        } catch (UnauthorizedException $e) {
            return $e->getStatusCode();
        }
    }
}
