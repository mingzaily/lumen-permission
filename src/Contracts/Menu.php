<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mingzaily\Permission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Menu
{
    /**
     * A Menu can be applied to roles.
     */
    public function roles(): BelongsToMany;

    /**
     * Find a Menu by its name.
     *
     * @param string $name
     * @return Menu
     */
    public static function findByName(string $name): self;

    /**
     * Find a Menu by its id.
     *
     * @param int $id
     * @return Menu
     */
    public static function findById(int $id): self;

    /**
     * Find or Create a Menu by its name.
     *
     * @param array $attributes
     * @return Menu
     */
    public static function findOrCreate(array $attributes): self;
}
