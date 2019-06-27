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
Because we use dynamic routes, the middleware configuration is set on a controller property. Here are some examples:

```php
// You can set only one middleware for all actions
protected $middleware = 'auth';
// or many middlewares
protected $middleware = ['auth', 'verified'];
// or customized for different actions
protected $middleware = [
    'auth',
    'auth:api' => ['except' => 'index'],
    'verified' => ['only' => ['store', 'update', 'destroy']]
];
```

### Policy Classes
The policy classes are used automatically if you follow the convention. If the model of your controller is `User`, for example, laravel-bullet will try to use the `UserPolicy` policy class automatically. But if no policy is registered it just ignores the policy.

If you want a customized policy class to your controller you can set the property `$policy` in your controller. Like this:

```php
class UserController extends ResourceController
{
    protected $policy = \App\Policies\CustomUserPolicy::class;
}
```
>**NOTE:** if you set the `$policy` property and your policy class is not registered in your `AuthServiceProvider` an **exception will be thrown**.

### Validations and Requests
The validations are encouraged to be used in the request classes. The requests are automatically injected through conventions. If you have a model `User` in the controller and the current action is `store`, laravel-bullet will try to inject a request class in this following convention order:

```php
App\Http\Requests\Users\StoreRequest::class
App\Http\Requests\UserStoreRequest::class
```
If none of those classes above exists, it will inject the default laravel `Illuminate\Http\Request` class.

And if you want to customize the request class to a specific action, you can define it in the `requests` protected property in controller:

```php
use App\Http\Requests\MyCustomUserRequest;

class UserController extends ResourceController
{
    protected $requests = [
        'store' => MyCustomUserRequest::class,
    ];
}
```
Now the request `MyCustomUserRequest` class will be injected in the store action.

**Old School Validations**

If you don't like validations in request classes, or just prefer the laravel `validate` method, you can use in the action hooks:

```php
class UserController extends ResourceController
{
    protected function beforeStore($request, $attributes)
    {
        return $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);
    }
}
```
You can find more about action hooks in the next topic.

### Action Hooks
It is very common to perform some operations in case of success or fail of an action. For example emit events, log, dispatch jobs etc. All crud actions has a `before` and a `after` hook actions. 

They are very usefull, for example for encrypt passwords for users:

```php
class UserController extends ResourceController
{
    protected function beforeStore($request, $attributes): array
    {
        $attributes['password'] = bcrypt($attributes['password']);

        return $attributes;
    }
}
```

Or to emit an event when a user is created:

```php
use App\Events\UserCreated;

class UserController extends ResourceController
{
    protected function afterStore($request, $user)
    {
        event(new UserCreated($user));
    }
}
```

Here is a list of the declarative action hooks for each action:

```php
// Hooks for index action
protected function beforeIndex(Request $request);
protected function afterIndex(Request $request, Illuminate\Database\Eloquent\Builder $builder): Illuminate\Database\Eloquent\Builder;

// Hooks for store action
protected function beforeStore($request, $attributes): array; // should return the attributes for the model being stored.
protected function afterStore($request, $model);

// Hooks for update action
protected function beforeUpdate($request, $attributes, $model): array;
protected function afterUpdate($request, $model);

// Hooks for destroy action
protected function beforeDestroy($request, $model);
protected function afterDestroy($request, $model);

// Hooks for show action
protected function beforeShow($request, $model);
protected function afterShow($request, $model);

// Hooks for edit action
protected function beforeEdit($request, $model);
protected function afterEdit($request, $model);

// Hooks for forceDelete action
protected function beforeForceDelete($request, $model);
protected function afterForceDelete($request, $model);

// Hooks for restore action
protected function beforeRestore($request, $model);
protected function afterRestore($request, $model);
```

### Custom Configurations
Comming soon...
### Advanced Routes
Comming soon...
### Custom Action
Comming soon...
### Performance and Other Tips
Public methods are used for dynamic routes, use it wiselly.
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
