# lumen-permission (RBAC Frame)
[![Build Status](https://travis-ci.org/mingzaily/lumen-permission.svg?branch=master)](https://travis-ci.org/mingzaily/lumen-permission)[![GitHub license](https://img.shields.io/github/license/mingzaily/lumen-permission)](https://github.com/mingzaily/lumen-permission/blob/master/LICENSE) ![StyleCI](https://github.styleci.io/repos/287014448/shield)

[简体中文](./README.md)  | English

## Start

### Installation

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

Then, in `bootstrap/app.php`, uncomment the `auth` middleware, and register this package's middleware:

```php
$app->routeMiddleware([
    'auth'       => App\Http\Middleware\Authenticate::class,
    'permission' => Mingzaily\Permission\Middlewares\PermissionMiddleware::class,
    'auth-permission' => Mingzaily\Permission\Middlewares\PermissionMiddleware::class,
    'role'       => Mingzaily\Permission\Middlewares\RoleMiddleware::class,
]);
```

and in the same file, in the `ServiceProviders` section, register the package configuration, service provider, and cache alias:

```php
// register permission config file
$app->configure('permission');
// register frame's cacheManager
$app->alias('cache', \Illuminate\Cache\CacheManager::class);  // if you don't have this already
// register lumen-permission ServiceProvider
$app->register(Mingzaily\Permission\PermissionServiceProvider::class);
// register AuthServiceProvider
$app->register(App\Providers\AuthServiceProvider::class);
```

Ensure your database configuration is set in your `.env` (or `config/database.php` if you have one).

Run the migrations to create the tables for this package:

```bash
php artisan migrate
```

### Use

Model assign，remove，sync role.

```php
$user = Auth::user();
// assign role, also can be written as role id
$user->assignRole('test');
// if deploy multiple roles
$user->assignRole('test1','test2');
// remove role
$user->removeRole('test');
// sync role => remove all current roles and set the given ones.
$user->syncRole('test2');
```

Model get first role.

```php
$role = $user->getFirstRole();
```

Model get all roles.

```php
$role = $user->roles;
// or
$role = $user->getAllRoles();
```

Model determines whether there is a role.

```php
$user->hasRole('test');
$user->hasAnyRole('test','test2');// Return true as long as one exists
$user->hasAllRoles('test','test2');// All roles exist before returning true
```

Role give and revoke permissions.

```php
$role->givePermissionTo('view.user');
// or be written as permission id
$role->givePermissionTo(1);
// revoke
$role->revokePermissionTo('view.user');
// sync => remove all current permissions and set the given ones.
$role->syncPermissionTo(1,2,3)
```

Role get permissions.

```php
$role->getAllPermissions();
// tree struct
$role->getTreePermissions();
```

Role determines whether there is a permission.

```php
$role->hasPermissionTo('view.user'); // $role->checkPermissionTo('view.user')
$role->hasAnyPermission('view.user','edit.user');
$role->hasAllPermissions('view.user','edit.user')
```

### Middleware

PermissionRouteMiddleware

```
Route::group(['middleware' => 'permission_route'], function () {
    //
});
```

PermissionMiddleware

```
Route::group(['middleware' => ['permission:view.user']], function () {
    //
});
```

RoleMiddleware

```
Route::group(['middleware' => ['role:test']], function () {
    //
});
```

## Thank

Thank for and Change base on [spatie/laravel-permission](https://github.com/spatie/laravel-permission)

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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
