<?php

namespace Mingzaily\Permission\Contracts;

interface Rbac
{
    /**
     * @return \Mingzaily\Permission\Models\Role
     */
    public function role(): \Mingzaily\Permission\Models\Role;
}
