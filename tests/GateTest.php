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

use Illuminate\Contracts\Auth\Access\Gate;

class GateTest extends TestCase
{
    /** @test */
    public function it_can_determine_if_a_user_does_not_have_a_permission()
    {
        $this->assertFalse($this->testUser->can('edit.articles'));

        $this->assertFalse($this->testUser->can(1));

        $this->assertFalse($this->testUser->can('/articles|PUT'));
    }

    /** @test */
    public function it_allows_before_callbacks_to_run_if_a_user_does_not_have_a_permission()
    {
        $this->assertFalse($this->testUser->can('edit.articles'));

        app(Gate::class)->before(function () {
            return true;
        });

        $this->assertTrue($this->testUser->can('edit.articles'));
    }

    /** @test */
    public function it_can_determine_if_a_user_has_a_permission_through_roles()
    {
        $this->testRole->givePermissionTo($this->testPermission);
        $this->testUser->assignRole('testRole');

        $this->assertTrue($this->testUser->can($this->testPermission->name));

        $this->assertTrue($this->testUser->can($this->testPermission->route.'@'.$this->testPermission->method));

        $this->assertFalse($this->testUser->can('non-existing-permission'));

        $this->assertFalse($this->testUser->can('admin-permission'));
    }
}
