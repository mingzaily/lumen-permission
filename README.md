# RBAC

Thank for [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
Change based on permission

### Change

- One User One Role (Can't support Multi-role)
- Change Permission Table
    - Add filed `route`,`method`,`display_name`
    - Delete filed `guard_name`
- Change Role Table
    - Add filed `display_name`
    - Delete filed `guard_name`

### Start

Same as [spatie/laravel-permission](https://github.com/spatie/laravel-permission)

#### Laravel

- `composer require mingzaily/laravel-permission`
- `php artisan vendor:publish --provider="Mingzaily\Permission\PermissionServiceProvider" --tag="migrations"`
- `php artisan migrate`
- `php artisan vendor:publish --provider="Mingzaily\Permission\PermissionServiceProvider" --tag="config"`

#### Lumen

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
