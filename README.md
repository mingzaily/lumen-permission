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

- `composer require mingzaily/laravel-permission`
- `php artisan vendor:publish --provider="Mingzaily\Permission\PermissionServiceProvider" --tag="migrations"`
- `php artisan migrate`
- `php artisan vendor:publish --provider="Mingzaily\Permission\PermissionServiceProvider" --tag="config"`

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
