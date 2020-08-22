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

use Mingzaily\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Mingzaily\Permission\Models\Permission;

class CommandTest extends TestCase
{

    /** @test */
    public function it_can_show_permission_tables()
    {
        Artisan::call('permission:show');

        $output = Artisan::output();

        //  |               | testRole | testRole2 | testRole3
        $this->assertRegExp('/\|\s+\|\s+testRole\s+\|\s+testRole2\s+\|\s+testRole3\s+\|/', $output);

        // | admin.permission |  ·       |  ·        |  ·        |
        $this->assertRegExp('/\|\s+admin.permission\s+\|\s+·\s+\|\s+·\s+\|\s+·\s+\|/', $output);

        Role::findByName('testRole')->givePermissionTo('edit.articles');
        $this->reloadPermissions();

        Artisan::call('permission:show');

        $output = Artisan::output();

        // | edit-articles |  ·       |  ·        |
        $this->assertRegExp('/\|\s+edit.articles\s+\|\s+✔\s+\|\s+·\s+\|\s+·\s+\|/', $output);
    }
}
