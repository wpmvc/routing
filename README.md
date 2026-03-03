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
    - [Installation](#installation)
	- [Register Routes In Route File](#register-routes-in-route-file)
		- [Rest Route](#rest-route)
			- [Write your first route](#write-your-first-route)
			- [With Controller](#with-controller)
			- [Other HTTP Methods](#other-http-methods)
			- [Dynamic Routing](#dynamic-routing)
			- [Regular Expression Constraints](#regular-expression-constraints)
			- [Named Routes](#named-routes)
			- [Route Grouping](#route-grouping)
			- [Resource Controller](#resource-controller)
				- [Actions Handled By Resource Controller](#actions-handled-by-resource-controller)
		- [Ajax Route](#ajax-route)
	- [Middleware](#middleware)
    - [Requirement & Configuration (Standalone Usage)](#requirement--configuration-standalone-usage)
	- [License](#license)


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

You may also pass a custom HTTP status code and an array of custom headers to `Response::send()`:

```php
return Response::send(['error' => 'Not Found'], 404, ['X-Custom-Header' => 'Value']);
```
#### With Controller
If you prefer to use a controller for your route logic, you can specify the controller and method as an array, as shown below:

```php
Route::get('user', [UserController::class, 'index']);
```
Here, we're using the `get()` method of the Route class to define a `GET request` to the /user endpoint. We're specifying the controller class and method as an array, where `UserController::class` refers to the class name and `index` is the method name.

#### Other HTTP Methods
Along with `get`, WpMVC Routing provides methods for all common HTTP verbs: `post`, `put`, `patch`, and `delete`.

```php
Route::post('user', [UserController::class, 'store']);
Route::put('user/{id}', [UserController::class, 'update']);
Route::patch('user/{id}', [UserController::class, 'update']);
Route::delete('user/{id}', [UserController::class, 'destroy']);
```

If you need a route to respond to multiple verbs, you can use the `match` method. Or, if you want a route to respond to all HTTP verbs, use the `any` method:

```php
Route::match(['GET', 'POST'], '/', function () {
    //
});

Route::any('/', function () {
    //
});
```

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

#### Regular Expression Constraints
You may constrain the format of your route parameters using the `where` method on a route instance. The `where` method accepts the name of the parameter and a regular expression defining how the parameter should be constrained:

```php
Route::get('users/{name}', [UserController::class, 'show'])->where('name', '[A-Za-z]+');

Route::get('users/{id}', [UserController::class, 'show'])->where('id', '[0-9]+');
```

You can constrain multiple parameters at once by passing an array to the `wheres` method:

```php
Route::get('users/{id}/{name}', [UserController::class, 'show'])->wheres([
    'id'   => '[0-9]+',
    'name' => '[a-z]+'
]);
```

#### Route Prefixing
You may prefix the URI of an individual route by chaining the `prefix` method onto the route definition:

```php
Route::get('profile', [UserProfileController::class, 'show'])->prefix('user');
// Matches: /user/profile
```

#### Named Routes
Named routes allow the convenient generation of URLs to specific routes. You may specify a name for a route by chaining the `name` method onto the route definition:

```php
// routes/rest/api.php or routes/ajax/api.php
Route::get('user/profile', [UserProfileController::class, 'show'])->name('profile');

// With parameters...
Route::get('user/{id}/profile', [UserProfileController::class, 'edit'])->name('profile.edit');
```

Once you have assigned a name to a given route, you may use the route's name when generating URLs via the `Router::url` method:

```php
// Inside a Controller method or route callback
use WpMVC\Routing\Router;

// Generating URLs...
$url = Router::url('profile');

$url = Router::url('profile.edit', ['id' => 1]);
```

> [!IMPORTANT]
> `Router::url()` will only work inside route callbacks (such as inside a Controller method or a route closure). If you need to generate an API URL outside of a route callback, you should use the native WordPress `get_rest_url()` function as demonstrated below.

#### Get Api Endpoint

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

#### Route Grouping
You can group related routes together using the `group()` method. This allows you to apply attributes such as prefixes and middleware to multiple routes at once without defining them on each route. You can create `nested groups` as well, as shown below:

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
} )->middleware('admin');
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

If you only need to bind a portion of the resource actions to a controller, you can use the `only` and `except` methods:

```php
Route::resource('user', UserController::class)->only(['index', 'show']);

Route::resource('user', UserController::class)->except(['store', 'update', 'delete']);
```

You can also chain `middleware()` and `name()` directly onto the resource registration to apply them to all generated routes:

```php
Route::resource('user', UserController::class)->middleware('auth')->name('users');
```

With resource routing, you don't have to define each route separately. Instead, you can handle all of the CRUD operations in a single controller class, making it easier to organize your code and keep your routes consistent.

#### Route Macros
The `Route` class uses the `Macroable` trait, which allows you to easily add your own custom methods to the router. Once a macro is defined, you can use it just like any other method on the `Route` class.

```php
use WpMVC\Routing\Route;
use WpMVC\Routing\Response;

Route::macro('status', function (string $uri) {
    return Route::get($uri, function () {
        return Response::send(['status' => 'OK']);
    });
});

// Usage
Route::status('system/status');
```

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

## Middleware

To create a middleware, you need to implement the `Middleware` interface. The `handle` method of the middleware will receive the current `WP_REST_Request` and a `$next` closure representing the pipeline payload.

If the handle method returns `false`, or a `WP_Error`, the pipeline stops immediately. Returning `true` or calling `$next($request)` allows the request to continue.

Here is an example of creating a middleware class named `EnsureIsUserAdmin`:

```php
<?php

namespace MyPlugin\App\Http\Middleware;

defined( 'ABSPATH' ) || exit;

use WpMVC\Routing\Contracts\Middleware;
use WP_REST_Request;
use WP_Error;

class EnsureIsUserAdmin implements Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  WP_REST_Request  $wp_rest_request The current request instance.
     * @param  mixed           $next            The next middleware closure in the stack.
     * @return bool|WP_Error Returns true to continue, false to forbid, or WP_Error.
     */
    public function handle( WP_REST_Request $wp_rest_request, $next )
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        
        return $next($wp_rest_request);
    }
}
```

Once you have created the middleware, you need to register it in your `config/app.php` file. You can do this by adding the middleware class to the `middleware` array.

```php
// config/app.php
return [
    // ...
    'middleware' => [
        'admin' => \MyPlugin\App\Http\Middleware\EnsureIsUserAdmin::class
    ]
];
```

To use the middleware in a route, you can chain the `middleware` method onto the route definition. You may assign multiple middleware by passing an array of names:

```php
// Assign single middleware
Route::get('/admin',  [AdminController::class, 'index'])->middleware('admin');

// Assign multiple middleware
Route::get('/admin',  [AdminController::class, 'index'])->middleware(['admin', 'auth']);
```

## Requirement & Configuration (Standalone Usage)

> [!NOTE]
> If you are using WpMVC, these configurations and requirements are already handled for you by the framework. The following instructions are only necessary if you are integrating the `wpmvc/routing` package independently into another WordPress plugin ecosystem.

### Requirement

WpMVC routing requires a dependency injection (DI) container. We do not use any hard-coded library, so you can choose to use any DI library you prefer. However, it is important to follow our DI structure, which includes having the `set` and `get` methods in your DI container.

We recommend using [wpmvc/container](https://github.com/wpmvc/container) as it already has these 2 methods implemented in the package and supports PHP 7.4 to 8.5.

#### Methods structure
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

### Configuration
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

7. (Optional) You can define global hooks to intercept REST API responses and permissions across all routes. Add these to your `$properties` array:

	```php
	parent::$properties = [
	    'rest'       => [
	        'namespace' => 'myplugin',
	        'versions'  => ['v1']
	    ],
	    'ajax'       => [
	        'namespace' => 'myplugin',
	        'versions'  => []
	    ],
	    // Global hooks (optional)
	    'rest_response_action_hook'   => 'myplugin_rest_response_action',
	    'rest_response_filter_hook'   => 'myplugin_rest_response_filter',
	    'rest_permission_filter_hook' => 'myplugin_rest_permission_filter',
	    'middleware' => [],
	    'routes-dir' => ABSPATH . 'wp-content/plugins/my-plugin/routes'
	];
	```
	
	*   `rest_response_action_hook`: Fires an action before the response is returned. Passes `WP_REST_Request $request` and `string $final_route`.
	*   `rest_response_filter_hook`: Filters the final response data. Passes `mixed $response`, `WP_REST_Request $request`, and `string $final_route`.
	*   `rest_permission_filter_hook`: Filters the boolean/WP_Error result of the middleware pipeline. Passes `bool|WP_Error $permission`, `array $middleware`, and `string $final_route`.

	**Example hook usage:**

	```php
	// Action Hook Example
	add_action('myplugin_rest_response_action', function (\WP_REST_Request $request, string $final_route) {
		// Log the request
		error_log("Route {$final_route} accessed.");
	}, 10, 2);

	// Response Filter Example
	add_filter('myplugin_rest_response_filter', function ($response, \WP_REST_Request $request, string $final_route) {
		// Modify the response data
		if (is_array($response)) {
    		$response['timestamp'] = time();
		}
		return $response;
	}, 10, 3);
	
	// Permission Filter Example
	add_filter('myplugin_rest_permission_filter', function ($permission, array $middleware, string $final_route) {
		// E.g. Deny access if a specific option is enabled
		if (get_option('myplugin_maintenance_mode')) {
			return new \WP_Error('maintenance', 'Site is under maintenance', ['status' => 503]);
		}
		return $permission;
	}, 10, 3);
	```

8. Finally, execute the `boot` method of your `RouteServiceProvider` class using the `init` action hook, like so:

	```php
	add_action('init', function() {
		$route_service_provider = new \MyPlugin\Providers\RouteServiceProvider;
		$route_service_provider->boot();
	});
	```
That's it! Your plugin is now configured with WpMVC Routing system, and you can start creating your own routes and handling requests with ease.

## License

WpMVC Routing is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
