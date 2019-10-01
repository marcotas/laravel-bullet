![Laravel Bullet](https://raw.githubusercontent.com/marcoT89/laravel-bullet/master/laravel-bullet.png)

# Laravel Bullet

[![Latest Version on Packagist](https://img.shields.io/packagist/v/marcot89/laravel-bullet.svg?style=flat-square)](https://packagist.org/packages/marcot89/laravel-bullet)
[![Total Downloads](https://img.shields.io/packagist/dt/marcot89/laravel-bullet.svg?style=flat-square)](https://packagist.org/packages/marcot89/laravel-bullet)
<!-- [![Build Status](https://img.shields.io/travis/marcot89/laravel-bullet/master.svg?style=flat-square)](https://travis-ci.org/marcot89/laravel-bullet) -->
<!-- [![Quality Score](https://img.shields.io/scrutinizer/g/marcot89/laravel-bullet.svg?style=flat-square)](https://scrutinizer-ci.com/g/marcot89/laravel-bullet) -->

⚡️ Lightning fast CRUDs and routes registrations for Laravel Applications

This package gives you the power to make API Cruds to eloquent resources very fast, and you can use its dynamic routes registration based on conventions. If you don't like scaffolds, and don't like the repetitive crud operations and route registration for resources, this is the right package for you and your applications.

## Table of Contents

- [Installation](#Installation)
- [Basic Usage](#Basic-Usage)
- [Dynamic Routes](#Dynamic-Routes)
- [Middleware Configuration](#Middleware-Configuration)
- [Policy Classes](#Policy-Classes)
- [Validations and Requests](#Validations-and-Requests)
- [Action Hooks](#Action-Hooks)
- [API Resources (Presentation)](#API-Resources-Presentation)
- [Actions in Details](#Actions-in-Details)
    - [Pagination, Filters and Other Magics](#Pagination-Filters-and-Other-Magics)
    - [Custom Query Builder for Actions](#Custom-Query-Builder-for-Actions)
    - [Custom Actions](#Custom-Actions)
- [Advanced Routes](#Advanced-Routes)
    - [Custom HTTP Methods](#Custom-HTTP-Methods)
    - [Route params and dependency injection](#Route-params-and-dependency-injection)
- [Performance and Other Tips](#Performance-and-Other-Tips)
    - [Only use public methods for actions](#Only-use-public-methods-for-actions)
    - [Use `route:cache` command to increase performance](#Use-routecache-command-to-increase-performance)
    - [HTML and JSON responses](#HTML-and-JSON-responses)
- [Changelog](#Changelog)
- [Contributing](#Contributing)
- [Security](#Security)
- [Credits](#Credits)
- [License](#License)
- [Laravel Package Boilerplate](#Laravel-Package-Boilerplate)

## Installation

You can install the package via composer:

```bash
composer require marcot89/laravel-bullet
```

> **Recommended:** This package recommends the usage of [Laravel Query Builder](https://github.com/spatie/laravel-query-builder) from [Spatie](https://github.com/spatie) team for index actions.

## Basic Usage

Simply extend the `ResourceController` in your controller class:

```php
<?php

namespace App\Http\Controllers;

use MarcoT89\Bullet\Controllers\ResourceController;

class UserController extends ResourceController
{
}
```

Done! Now you have all of these crud actions for the model `User` in your controller class:

-   index
-   store
-   update
-   destroy
-   show
-   edit
-   forceDelete\*
-   restore\*

> **Important:** The methods `forceDelete` and `restore` is only displayed if the resource method use the laravel's trait `SoftDeletes`.

> **Convention:** The resource model is infered by the convention. If you have a controller called `PostController` it will infer the model `Post`. The convention for the controller name is something like: `[Model]Controller` and it **will not** try to resolve from plural to singular. So if you define a controller `PostsController` it will try to resolve the model `Posts` instead of `Post`. Keep this in mind when creating your controllers.

That's it! This is sufficient to add crud actions to your controller. But **what about define the routes dynamically?** Thats what we're going to see next.

## Dynamic Routes

Now that you created a `UserController` it is time to register the routes for the resource controller right?
But what if those routes could be registered automatically? Ok! Lets do it!

Use `Bullet::namespace` in any group of routes you want. Example:

```php
// routes/web.php
Route::middleware('auth', function () {
    Bullet::namespace('Resources'); // the default namespace is App\Http\Controllers
});
```

And that's it! Now **all** controllers created under `Resources` namespace will have their public methods registered automatically. This are the following routes:

| HTTP      | URL               | Route Name    | Controller@action                                     | Middleware |
| --------- | ----------------- | ------------- | ----------------------------------------------------- | ---------- |
| GET\|HEAD | users             | users.index   | App\Http\Controllers\Resources\UserController@index   | web,auth   |
| POST      | users             | users.store   | App\Http\Controllers\Resources\UserController@store   | web,auth   |
| PUT       | users/{user}      | users.update  | App\Http\Controllers\Resources\UserController@update  | web,auth   |
| DELETE    | users/{user}      | users.destroy | App\Http\Controllers\Resources\UserController@destroy | web,auth   |
| GET\|HEAD | users/{user}      | users.show    | App\Http\Controllers\Resources\UserController@show    | web,auth   |
| GET\|HEAD | users/{user}/edit | users.edit    | App\Http\Controllers\Resources\UserController@edit    | web,auth   |

If you want to hide some specific route or action you can use the `$only` or `$except` protected properties of the controller to do so:

```php
class UserController extends ResourceController
{
    protected $only = ['index', 'show'];
    // or
    protected $except = ['destroy'];
}
```
This will generate only the expected routes as defined.

>**NOTE:** keep in mind that the `$only` property has precedence over `$except` property and they cannot be used together.

>**IMPORTANT:** The `$only` property will hide all other actions including the dynamic ones.

## Middleware Configuration

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

## Policy Classes

The policy classes are used automatically if you follow the convention. If the model of your controller is `User`, for example, laravel-bullet will try to register a `UserPolicy` policy class automatically. But it will skip the authorization if the policy class doesn't exist.

If you want a customized policy class to your controller you can set the property `$policy` in your controller. Like this:

```php
class UserController extends ResourceController
{
    protected $policy = \App\Policies\CustomUserPolicy::class;
}
```
If you don't want a policy class to be registered even if it exists to a controller, you can just set the `$policy` property to `false`.

>**TIP:** Policy classes are registered automatically, you **don't need** to register it in the `AuthServiceProvider`.

## Validations and Requests

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

## Action Hooks

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
protected function beforeIndex($request);
protected function afterIndex($request, $builder);

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

## API Resources (Presentation)

To set your own API Resource you can use the protected `resource` property in the controller class. Example:

```php
use App\Http\Resources\UserResource;

class UserControlle extends ResourceController
{
    protected $resource = UserResource::class;
}
```

> **NOTE:** this resource will be used in all actions.

## Actions in Details

#### Pagination, Filters and Other Magics
If you use the recommended [Laravel Query Builder](https://github.com/spatie/laravel-query-builder) composer package you should be able to use all of it's features very easy. Before dive into the examples bellow read their documentation and be familiarized with their usage.

All of these properties are available for the **index** and **show** actions:

```php
protected $defaultSorts    = null;
protected $allowedFilters  = null;
protected $allowedIncludes = null;
protected $allowedSorts    = null;
protected $allowedFields   = null;
protected $allowedAppends  = null;
protected $defaultPerPage  = 15;
protected $maxPerPage      = 500;
protected $searchable      = true;
```

> **WARNING:** This set of properties only works with [Laravel Query Builder](https://github.com/spatie/laravel-query-builder) package. It **WILL NOT WORK** without it.

See how it works:

**$defaultSorts**
```php
// Sort your records from latest to oldest by default
protected $defaultSorts = '-created_at';
```

**$allowedFilters**
```php
protected $allowedFilters = ['name', 'email'];
```
Or you can use the `allowedFilters` method to a more complete case:
```php
protected function allowedFilters()
{
    return [
        'name',
        AllowedFilter::exact('id'),
        AllowedFilter::exact('email')->ignore(null),
        AllowedFilter::scope('with_trashed'),
        AllowedFilter::custom('permission', FilterUserPermission::class),
    ];
}
```

**$allowedIncludes**
```php
protected $allowedIncludes = ['posts.comments'];
```
Or you can use the `allowedIncludes` method to a more complete case:
```php
protected function allowedIncludes()
{
    $includes = ['posts.comments'];

    if (user()->isAdmin()) {
        $includes[] = 'created_by';
        $includes[] = 'logs';
    }

    return $includes;
}
```

**$allowedSorts**
```php
protected $allowedSorts = ['id', 'name', 'created_at'];
```
Or you can use the `allowedSorts` method to a more complete case:
```php
protected function allowedSorts()
{
    return [
        'id',
        'name',
        'created_at',
        Sort::field('street', 'actual_column_street'),
    ];
}
```

**$allowedFields**
```php
protected $allowedFields = ['id', 'name', 'email'];
```
Or you can use the `allowedFields` method to a more complete case:
```php
protected function allowedFields()
{
    return [
        'id',
        'name',
        'email',
    ];
}
```

**$allowedAppends**
```php
protected $allowedAppends = ['is_admin', 'is_published'];
```
Or you can use the `allowedAppends` method to a more complete case:
```php
protected function allowedAppends()
{
    return ['is_admin', 'is_published'];
}
```

**$defaultPerPage**
```php
protected $defaultPerPage = 15; // defaults to 15
```
This can be passed by the `per_page` or `perPage` query params.

**$maxPerPage**
```php
protected $maxPerPage = 500; // defaults to 500
```
But the `per_page` or `perPage` params cannot pass this `$maxPerPage` limit. If you pass a `per_page=1000` the pagination will limit to 300 if you have defined it. This is a protection to your queries. Use it wisely.

**$searchable**
```php
protected $searchable = true; // defaults to true
```
By default all index actions accepts the `search` query param, and it will try to use a `scopeSearch` scope in the model if it's present. If it's not present it just ignores it. Set it to false if you have a search scope in your model but you don't want to make it available in your index action.

#### Custom Query Builder for Actions
There are many times that you have a big scope for your queries. For example if you are developing a multitenant application with `teams` some times you want to list the users of the current user's team. For that case you could customize the queries for the controller. For now, each of the actions has its own query method.

For index action you should override the `getQuery` method, like this:

```php
protected function getQuery()
{
    return team()->users();
}
```
Here are the complete list of actions for the query builder to override when needed:

```php
protected function getQuery($request); // for index action
protected function getStoreQuery($request);
protected function getUpdateQuery($request);
protected function getDestroyQuery($request);
protected function getShowQuery($request);
protected function getEditQuery($request);
protected function getForceDeleteQuery($request);
protected function getRestoreQuery($request);
```

#### Custom Actions

To completly customize a crud action you just need to declare it as it is expected. For limitation reasons all routes that need `$id` to find a model is given with `$id` param instead of the typed model object. Also it's not possible to inject request classes due to its declaration. For example, if you want to customize the update method that receives an `$id` you should do:

```php
public function update($id)
{
    $user = User::findOrFail($id);
    ...
}
```

Now you can do whatever you want with the action method.
## Advanced Routes

When using the bullet dynamic routes you don't have to write any route manually in any of the route files. With bullet routes any public method in the controller becomes an action with a registered route. Example:

```php
class UserController extends ResourceController
{
    public function reports()
    {
        ...
    }
}
```

The **public** method will **automatically** register a route like this:

```php
Route::get('users/reports', 'Resources\UserController@reports')->name('users.reports');
```

#### Custom HTTP Methods

If you want to customize the **http method** of the route just prefix it with the name of the http method. Like this:

```php
public function postReports()
{
    ...
}
```

Now, the public method `postReports` will register a route with post http method:

```php
Route::post('users/reports', 'Resources\UserController@postReports')->name('users.reports');
```
> **HINT:** Note that the generated **route name** was `'users.reports'` instead of `'users.post-reports'`.

#### Route params and dependency injection

You can also pass arguments to the action and they will be converted to url params.

```php
use App\Models\User;
use App\Http\Requests\MyCustomRequest;

class UserController extends ResourceController
{
    public function postReports(MyCustomRequest $request,  User $user, $report, $param1, $param2) // as many as you want.
    {
        ...
    }
}
```

Now this public method will register a route like this:

```php
Route::post('users/reports/{user}/{report}/{param1}/{param2}', 'Resources\UserController@postReports')->name('users.post-reports');
```

> **NOTE:** Any typed param will be injected normally as expected. And request params will be injected but ignored in the route definition.

#### Excluding Controller From Dynamic Routes

Sometimes you have to define a very custom routes for one of your controllers. To exclude your controller from the dynamic routes you should use the `options` parameters on `namespace` method. Like this:

```php
Bullet::namespace('Api/V1', ['except' => 'InternalController']);
```
Now the routes for this controller won't be generated dynamically. You have to register its routes **manually**.

## Performance and Other Tips

#### Only use public methods for actions

Since the public methods in the controller will register a route automatically, it's a good practice to use `protected` or `private` visibility for other helper methods to not generate **trash routes**.

#### Use `route:cache` command to increase performance

Since the laravel-bullet uses a lot of reflection and IO for every controller classes, using `route:cache` command for production environments will **increase significantly the performance** of your requests.

#### HTML and JSON responses
The actions `index` responds to json if it's an AJAX request, or will try to return a view under `resources/views/users/index.blade.php`.

<!-- ### Testing

```bash
composer test
``` -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email marcotulio.avila@gmail.com instead of using the issue tracker.

## Credits

-   [Marco Túlio](https://github.com/marcot89)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
