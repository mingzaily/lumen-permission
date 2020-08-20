# lumen-permission (RBAC Frame)
![Travis (.org)](https://img.shields.io/travis/mingzaily/lumen-permission?style=flat-square)
![StyleCI](https://github.styleci.io/repos/287014448/shield)
![GitHub repo size](https://img.shields.io/github/repo-size/mingzaily/lumen-permission?style=flat-square)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/mingzaily/lumen-permission?style=flat-square)
![Packagist Version](https://img.shields.io/packagist/v/mingzaily/lumen-permission?style=flat-square)
![Packagist License](https://img.shields.io/packagist/l/mingzaily/lumen-permission?style=flat-square)

简体中文 | [English](./README.en.md) 

## 开始

### 安装

第一步，`composer`安装

```shell script
composer require mingzaily/lumen-permission
```

安装成功后，复制以下文件

```shell script
cp vendor/mingzaily/lumen-permission/config/permission.php config/permission.php
cp vendor/mingzaily/lumen-permission/database/migrations/create_permission_tables.php.stub database/migrations/2020_0 1_01_000000_create_permission_tables.php
```

同时也需要把`lumen-framework`核心框架的`auth.php`配置文件复制出来

```shell script
cp vendor/laravel/lumen-framework/config/auth.php config/auth.php
```

然后在 `bootstrap/app.php`, 根据需要注册`PermissionMiddleware`，`PermissionRouteMiddleware`，`RoleMiddleware`（用法情况使用说明）

```php
$app->routeMiddleware([
    'auth'       => App\Http\Middleware\Authenticate::class,
    'permission' => Mingzaily\Permission\Middlewares\PermissionMiddleware::class,
    'permission_route' => Mingzaily\Permission\Middlewares\PermissionRouteMiddleware::class,
    'role'       => Mingzaily\Permission\Middlewares\RoleMiddleware::class,
]);
```

在同个文件下,  注册扩展包的配置文件，`lumen-permission`的服务器提供类，和`cache`别名

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

接着，在`.env`或`config/database.php`配置数据库连接参数

运行迁移文件为扩展包创建表：

```bash
php artisan migrate
```

### 使用

用户分配，删除，更换角色

```php
$user = Auth::user();
// assign role, also can be written as role id
$user->assignRole('test');
// if deploy multiple roles
$user->assignRole('test1','test2');
// remove role
$user->removeRole('test');
// sync role => Remove all current roles and set the given ones.
$user->syncRole('test2');
```

获取第一个角色

```php
$role = $user->getFirstRole();
```

获取所有角色

```php
$role = $user->roles;
// or
$role = $user->getAllRoles();
```

判断是否有该角色

```php
$user->hasRole('test');
$user->hasAnyRole('test','test2');// Return true as long as one exists
$user->hasAllRoles('test','test2');// All roles exist before returning true
```

角色分配，删除，同步权限

```php
$role->givePermissionTo('view.user');
// or be written as permission id
$role->givePermissionTo(1);
// revoke
$role->revokePermissionTo('view.user');
// sync => remove all current permissions and set the given ones.
$role->syncPermissionTo(1,2,3)
```

角色查看权限

```php
$role->getAllPermissions();
// also support tree
$role->getTreePermissions();
```

判断角色是否有该权限

```php
$role->hasPermissionTo('view.user'); // $role->checkPermissionTo('view.user')
$role->hasAnyPermission('view.user','edit.user');
$role->hasAllPermissions('view.user','edit.user')
```

也可以通过懒加载进行获取角色及相关权限

```php
$user->load('roles.permissions');
```

### 中间件

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

### 超级管理员设置
```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    ......

    public function register()
    {
        // super-admin no need to verify permissions
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }

    ......
}
```

## 感谢

本扩展基于 [spatie/laravel-permission](https://github.com/spatie/laravel-permission) 进行更改

### 不同点

- 支持配置一用户单角色或者一用户多角色
- 修改`permission`表结构
  - 添加`route`,`method`,`display_name`,`pid`,`is_menu`字段
  - 删除 `guard_name`字段
- 修改`role`表结构
  - 添加 `display_name`字段
  - Delete filed `guard_name`
- 移出`model_has_permission`表格，移出`model`的直接权限
- 移出Laravel blade模板支持（如需要，请使用 [spatie/laravel-permission](https://github.com/spatie/laravel-permission)）
- 移出Guard看守器配置
- 移出通配符权限设置

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
