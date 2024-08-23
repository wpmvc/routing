<p align="center">
<a href="https://packagist.org/packages/wpmvc/routing"><img src="https://img.shields.io/packagist/dt/wpmvc/routing" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/wpmvc/routing"><img src="https://img.shields.io/packagist/v/wpmvc/routing" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/wpmvc/routing"><img src="https://img.shields.io/packagist/l/wpmvc/routing" alt="License"></a>
</p>

# About WpMVC Routing

WpMVC Routing is a powerful routing system for WordPress plugins that is similar to the popular PHP framework Laravel. This package makes use of the WordPress REST route system and includes its own custom route system, known as the `Ajax Route`.

One of the key features of WpMVC Routing is its support for middleware. Middleware allows you to perform additional actions before each request.

By using WpMVC Routing in your WordPress plugin, you can easily create custom routes and middleware to handle a wide variety of requests, including AJAX requests, with ease. This makes it an excellent tool for developing modern and dynamic WordPress plugins that require advanced routing capabilities and additional security measures.

- [About WpMVC Routing](#about-wpmvc-routing)
	- [Requirement](#requirement)
		- [Methods structure](#methods-structure)
	- [Installation](#installation)
	- [Configuration](#configuration)
	- [Register Routes In Route File](#register-routes-in-route-file)
		- [Rest Route](#rest-route)
			- [Write your first route](#write-your-first-route)
			- [With Controller](#with-controller)
			- [Dynamic Routing](#dynamic-routing)
			- [Route Grouping](#route-grouping)
			- [Resource Controller](#resource-controller)
				- [Actions Handled By Resource Controller](#actions-handled-by-resource-controller)
		- [Ajax Route](#ajax-route)
		- [Get Api Endpoint](#get-api-endpoint)
	- [Middleware](#middleware)
	- [License](#license)


## Requirement

WpMVC routing requires a dependency injection (DI) container. We do not use any hard-coded library, so you can choose to use any DI library you prefer. However, it is important to follow our DI structure, which includes having the `set`, `get`, and `call` methods in your DI container.

We recommend using [PHP-DI](https://php-di.org/) as it already has these 3 methods implemented in the package.

### Methods structure
Here is the structure of the methods that your DI container should have in order to work with WpMVC routing:

1. `set` method

	```php
	/**
     * Define an object or a value in the container.
     *
     * @param string $name Entry name
     * @param mixed $value Value, define objects
     */
    public function set(string $name, $value) {}
	```
2. `get` method

	```php
	
    /**
     * Returns an entry of the container by its name.
     *
     * @template T
     * @param string|class-string<T> $name Entry name or a class name.
     *
     * @return mixed|T
     */
    public function get($name) {}
	```
3. `call` method
	```php
	 /**
     * Call the given function using the given parameters.
     *
     * Missing parameters will be resolved from the container.
     *
     * @param callable $callable   Function to call.
	 * 
     * @return mixed Result of the function.
     */
    public function call($callable) {}
	```
## Installation

```
composer require wpmvc/routing
```
## Configuration
1. Your plugin must include a `routes` folder. This folder will contain all of your plugin's route files.

2. Within the `routes` folder, create two subfolders: `ajax` and `rest`. These folders will contain your plugin's route files for AJAX and REST requests, respectively.

3. If you need to support different versions of your routes, you can create additional files within the `ajax` and `rest` subfolders. For example, you might create `v1.php` and `v2.php` files within the `ajax` folder to support different versions of your AJAX routes.

4. Folder structure example:
	```
    routes:
        rest:
            api.php
            v1.php
            v2.php
        ajax:
           api.php
           v1.php
	```
5. In your `RouteServiceProvider` class, set the necessary properties for your route system. This includes setting the `rest and ajax namespaces`, the versions of your routes, any middleware you want to use, and the directory where your route files are located. Here's an example:
	```php

    <?php

    namespace MyPlugin\Providers;

    use MyPlugin\Container;
    use WpMVC\Routing\Providers\RouteServiceProvider as WpMVCRouteServiceProvider;

    class RouteServiceProvider extends WpMVCRouteServiceProvider {

        public function boot() {

            /**
             * Set Di Container
             */
            parent::$container = new Container;

            /**
             * OR you use PHP-Container 
             * Uses https://php-di.org/doc/getting-started.html
             */
            // parent::$container = new DI\Container();


            /**
             * Set required properties
             */
            parent::$properties = [
                'rest'       => [
                    'namespace' => 'myplugin',
                    'versions'  => ['v1', 'v2']
                ],
                'ajax'       => [
                    'namespace' => 'myplugin',
                    'versions'  => []
                ],
                'middleware' => [],
                'routes-dir' => ABSPATH . 'wp-content/plugins/my-plugin/routes'
            ];

            parent::boot();
        }
    }

	```
6. Finally, execute the `boot` method of your `RouteServiceProvider` class using the `init` action hook, like so:

	```php
	add_action('init', function() {
		$route_service_provider = new \MyPlugin\Providers\RouteServiceProvider;
		$route_service_provider->boot();
	});
	```
That's it! Your plugin is now configured with WpMVC Routing system, and you can start creating your own routes and handling requests with ease.

## Register Routes In Route File

### Rest Route 
`routes/rest/api.php`

#### Write your first route
To create your first RESTful route in WordPress, you can use the `Route` and `Response` classes from the `WpMVC\Routing` namespace, as shown below:
```php
<?php

use WpMVC\Routing\Route;
use WpMVC\Routing\Response;
use WP_REST_Request;

defined('ABSPATH') || exit;

Route::get('user', function(WP_REST_Request $wp_rest_request) {
	return Response::send(['ID' => 1, 'name' => 'john']);
});
```

In this example, we're using the `get()` method of the Route class to define a `GET request` to the /user endpoint. The closure passed as the second argument returns a response using the `Response::send()` method, which takes an array of data to be returned in `JSON format`.
#### With Controller
If you prefer to use a controller for your route logic, you can specify the controller and method as an array, as shown below:

```php
Route::get('user', [UserController::class, 'index']);
```
Here, we're using the `get()` method of the Route class to define a `GET request` to the /user endpoint. We're specifying the controller class and method as an array, where `UserController::class` refers to the class name and `index` is the method name.

#### Dynamic Routing
You can use dynamic routing to handle requests to endpoints with dynamic parameters. To define a route with a required parameter, use curly braces around the parameter name, as shown below:

```php
// Required id
Route::get('users/{id}', [UserController::class, 'index']);
```

To define a route with an optional parameter, you can add a question mark after the parameter name, as shown below:

```php
// Optional id
Route::get('users/{id?}', [UserController::class, 'index']);
```
#### Route Grouping
You can group related routes together using the `group()` method. This allows you to apply middleware or other attributes to multiple routes at once. You can create `nested groups` as well, as shown below:

```php
Route::group('admin', function() {

    Route::get('/',  [UserController::class, 'index']);

    Route::group('user', function() {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::patch('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'delete']);
    } );
} );
```

#### Resource Controller
Resource routing is a powerful feature that allows you to quickly assign CRUD `(create, read, update, delete)` routes to a controller with a single line of code. To create resource routes, you can use the `resource()` method. Here is an example:

```php
Route::resource('user', UserController::class);
```

Resource routing automatically generates the typical CRUD routes for your controller, as shown in the table below:

##### Actions Handled By Resource Controller

| Verb   | URI           | Action |
|--------|---------------|--------|
| GET    | /users        | index  |
| POST   | /users        | store  |
| GET    | /users/{user} | show   |
| PATCH  | /users/{user} | update |
| DELETE | /users/{user} | delete |

With resource routing, you don't have to define each route separately. Instead, you can handle all of the CRUD operations in a single controller class, making it easier to organize your code and keep your routes consistent.

### Ajax Route

`routes/ajax/api.php`

Sometimes third-party plugins don't load when using the WordPress rest route. To fix this issue we are creating our own route system (Ajax Route).

Registering an AJAX route is similar to registering a REST route. Instead of using the `Route` class, you need to use the `Ajax` class.

Here is an example of registering an AJAX route to get a user's data:
```php
use WpMVC\Routing\Ajax;
use WP_REST_Request;

Ajax::get('user', function(WP_REST_Request $wp_rest_request) {
	return Response::send(['ID' => 1, 'name' => 'john']);
});
```

To route to `WordPress admin`, your route must use a middleware with the name `admin`. If you apply this middleware to your Ajax route, WpMVC will load the WP admin code. Check out the [Middleware Docs](#middleware) to see the middleware use process.

### Get Api Endpoint

The `get_rest_url()` function can be used to get the REST API endpoint for the current site. To use this function, you need to provide the current site ID and the `namespace` for your plugin.

```php
$site_id   = get_current_blog_id();
$namespace = 'myplugin';

$rest_route_path = get_rest_url($site_id, $namespace);

$user_rest_route = $rest_route_path . '/user';
```

Similarly, you can use the `get_site_url()` function to get the URL of the current site, and then append your namespace to it to create the AJAX API endpoint URL.

```php
$ajax_route_path = get_site_url($site_id) . '/' . $namespace;

$user_ajax_route = $ajax_route_path . '/user';
```

## Middleware

To create a middleware, you need to implement the `Middleware` interface. The `handle` method of the middleware must return a boolean value. If the handle method returns `false`, the request will be stopped immediately.

Here is an example of creating a middleware class named `EnsureIsUserAdmin`:

```php
<?php

namespace MyPlugin\App\Http\Middleware;

use WpMVC\Routing\Contracts\Middleware;
use WP_REST_Request;

class EnsureIsUserAdmin implements Middleware
{
    /**
    * Handle an incoming request.
    *
    * @param  WP_REST_Request  $wp_rest_request
    * @return bool
    */
    public function handle( WP_REST_Request $wp_rest_request ): bool
    {
        return current_user_can( 'manage_options' );
    }
}
```

Once you have created the middleware, you need to register it in the RouteServiceProvider [Configuration](#configuration). You can do this by adding the middleware class to the `middleware` array in the `$properties` array of the RouteServiceProvider class.

```php
parent::$properties = [
	...
	'middleware' => [
		'admin' => \MyPlugin\App\Http\Middleware\EnsureIsUserAdmin::class
	]
];
```

To use the middleware in a route, you can add the middleware name as the last argument of the route definition, as shown below:

```php
Route::get('/admin',  [AdminController::class, 'index'], ['admin']);
```

## License

WpMVC Routing is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
