<?php

if (! function_exists('setTree')) {
    /**
     * change list to tree.
     *
     * @param \Illuminate\Support\Collection $allPermissions
     * @param null $pid
     * @return \Illuminate\Support\Collection
     */
    function setTree($allPermissions, $pid = null): \Illuminate\Support\Collection
    {
        return $allPermissions
            ->where('pid', $pid)
            ->map(function ($permission) use ($allPermissions) {
                $data = $permission;

                if (! $permission->is_menu) {
                    return $data;
                }

                $data['children'] = $this->getPermissionsTree($permission->id, $allPermissions)
                    ->sortByDesc('weight')
                    ->values();

                return $data;
            })->sortByDesc('weight')->values();
    }
}