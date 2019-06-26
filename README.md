# Laravel Bullet

<!-- [![Latest Version on Packagist](https://img.shields.io/packagist/v/marcot89/laravel-bullet.svg?style=flat-square)](https://packagist.org/packages/marcot89/laravel-bullet) -->
<!-- [![Build Status](https://img.shields.io/travis/marcot89/laravel-bullet/master.svg?style=flat-square)](https://travis-ci.org/marcot89/laravel-bullet) -->
<!-- [![Quality Score](https://img.shields.io/scrutinizer/g/marcot89/laravel-bullet.svg?style=flat-square)](https://scrutinizer-ci.com/g/marcot89/laravel-bullet) -->
[![Total Downloads](https://img.shields.io/packagist/dt/marcot89/laravel-bullet.svg?style=flat-square)](https://packagist.org/packages/marcot89/laravel-bullet)

⚡️ Lightning fast CRUDs and routes registrations for Laravel Applications

This package gives you the power to make API Cruds to eloquent resources very fast, and you can use its dynamic routes registration based on conventions with a little of configuration.

## Installation

You can install the package via composer:

```bash
composer require marcot89/laravel-bullet
```
>**Recommended:** This package recommends the usage of [Laravel Query Builder](https://github.com/spatie/laravel-query-builder) from [Spatie](https://github.com/spatie) team for index actions.

## Basic Usage

### Basic Controller
#### Creating the Resource Controller
Create a `ResourceController` and simply add the trait `CrudOperations` to it:
```php
<?php

namespace App\Http\Controllers;

use MarcoT89\Bullet\Traits\CrudOperations;

class ResourceController extends Controller
{
    use CrudOperations;
}
```
Now you have all of these actions in controller class:
 - index
 - store
 - update
 - destroy
 - show
 - edit
 - forceDelete*
 - restore*

>**Important:** The methods `forceDelete` and `restore` is only displayed if the resource method use the laravel's trait `SoftDeletes`.

#### Extending the Resource Controller
Now that you have a `ResourceController` class you can extend from it. Lets say that you want a complete crud operations for the model `User`. After creating your migrations and defined your `User` model you can create the controller like this:

```php
<?php

namespace App\Http\Controllers\Resources; // This namespace will be useful when we talk about the dynamic routes registrations, but it can really be any namespace you want.

use App\Http\Controllers\ResourceController;

class UsersController extends ResourceController
{
}
```
That's it! This is sufficient to add crud actions to your controller. But **what about define the routes dynamically?** Thats what we're going to see next.

### Dynamic Routes
Now that you created a `UsersController` controller you can define a namespace in your app's controllers folder to dynamically register the routes for all of your controllers under this namespace automatically! Yes, automatically. Let's see how it works.

Use `Bullet::controllers` in any route you want.
``` php
// routes/web.php
Route::middleware('auth', function () {
    Bullet::namespace('Resources'); // defaults to App\Http\Controllers
});
```
And done! Finally this will generate the following routes:

This will generate the following routes:

| HTTP      | URL               | Route Name    | Controller@action                                     | Middleware |
| --------- | ----------------- | ------------- | ----------------------------------------------------- | ---------- |
| GET\|HEAD | users             | users.index   | App\Http\Controllers\Resources\UserController@index   | web,auth   |
| POST      | users             | users.store   | App\Http\Controllers\Resources\UserController@store   | web,auth   |
| PUT       | users/{user}      | users.update  | App\Http\Controllers\Resources\UserController@update  | web,auth   |
| DELETE    | users/{user}      | users.destroy | App\Http\Controllers\Resources\UserController@destroy | web,auth   |
| GET\|HEAD | users/{user}      | users.show    | App\Http\Controllers\Resources\UserController@show    | web,auth   |
| GET\|HEAD | users/{user}/edit | users.edit    | App\Http\Controllers\Resources\UserController@edit    | web,auth   |

### Middleware Configuration
Comming soon...
### Policy Classes
Comming soon...
### Request Validations
Comming soon...
### Action Hooks
Comming soon...
### Custom Configurations
Comming soon...
### Advanced Routes
Comming soon...
### Custom Action
Comming soon...

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email marcotulio.avila@gmail.com instead of using the issue tracker.

## Credits

- [Marco Túlio](https://github.com/marcot89)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
