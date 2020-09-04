<?php

/*
 * This file is part of the mingzaily/lumen-permission.
 *
 * (c) mingzaily <mingzaily@163.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (! function_exists('setTree')) {
    /**
     * change list to tree.
     *
     * @param \Illuminate\Support\Collection $allPermissions
     * @param null $pid
     * @return \Illuminate\Support\Collection
     */
    function setTree(\Illuminate\Support\Collection $allPermissions, $pid = null): \Illuminate\Support\Collection
    {
        return $allPermissions
            ->where('pid', $pid)
            ->map(function ($permission) use ($allPermissions) {
                $data = $permission;

                if (! $permission->is_menu) {
                    return $data;
                }

                $data['children'] = setTree($allPermissions, $permission->id)
                    ->sortByDesc('weight')
                    ->values();

                return $data;
            })->sortByDesc('weight')->values();
    }
}
