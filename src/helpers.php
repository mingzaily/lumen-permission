<?php

if (!function_exists('getUserModel')) {
    /**
     * @return string|null
     */
    function getUserModel()
    {
        return config("auth.providers.users.model");
    }
}
