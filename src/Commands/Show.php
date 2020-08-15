<?php

namespace Mingzaily\Permission\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Mingzaily\Permission\Contracts\Role as RoleContract;
use Mingzaily\Permission\Contracts\Permission as PermissionContract;

class Show extends Command
{
    protected $signature = 'permission:show
            {style? : The display style (default|borderless|compact|box)}';

    protected $description = 'Show a table of roles and permissions';

    public function handle()
    {
        $permissionClass = app(PermissionContract::class);
        $roleClass = app(RoleContract::class);

        $style = $this->argument('style') ?? 'default';

        $roles = $roleClass::orderBy('name')->get()->mapWithKeys(function ($role) {
            return [$role->name => $role->permissions->pluck('name')];
        });

        $permissions = $permissionClass::orderBy('name')->pluck('name');

        $body = $permissions->map(function ($permission) use ($roles) {
            return $roles->map(function (Collection $role_permissions) use ($permission) {
                return $role_permissions->contains($permission) ? ' ✔' : ' ·';
            })->prepend($permission);
        });

        $this->table(
            $roles->keys()->prepend('')->toArray(),
            $body->toArray(),
            $style
        );

    }
}
