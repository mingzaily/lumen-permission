# lumen-permission (RBAC Frame)
[![Build Status](https://travis-ci.org/mingzaily/lumen-permission.svg?branch=master)](https://travis-ci.org/mingzaily/lumen-permission)

### Thank
Thank for [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
Change based on permission

### Change

- Support Configure One User One Role Or One User Multiple Roles
- Change Permission Table
    - Add filed `route`,`method`,`display_name`
    - Delete filed `guard_name`
- Change Role Table
    - Add filed `display_name`
    - Delete filed `guard_name`
- Delete Model_Has_Permissions Table
- Delete Laravel Blade Support
- Delete Guard Config
- Delete Wildcard Permission

### Start

Same as [spatie/laravel-permission](https://github.com/spatie/laravel-permission)

#### Lumen

First, install from Composer
```shell script
composer require mingzaily/lumen-permission
```
Copy the files
```shell script
cp vendor/mingzaily/lumen-permission/config/permission.php config/permission.php
cp vendor/mingzaily/lumen-permission/database/migrations/create_permission_tables.php.stub database/migrations/2020_0 1_01_000000_create_permission_tables.php
```
You will also need the config/auth.php file. If you don't already have it, copy it from the vendor folder:
```shell script
cp vendor/laravel/lumen-framework/config/auth.php config/auth.php
```

#### 

```php
return $this->loadMissing('roles', 'roles.permissions')
    ->roles->flatMap(function ($role) {
        return $role->permissions;
    })->sort()->values();
```
## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
