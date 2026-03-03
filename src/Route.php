<?php
/**
 * Routing logic for the application.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

use WpMVC\Routing\Providers\RouteServiceProvider;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;

/**
 * Class Route
 *
 * Provides a fluent interface for registering REST and AJAX routes.
 *
 * @package WpMVC\Routing
 */
class Route
{
    use \WpMVC\Routing\Traits\Macroable;

    protected static string $route_prefix = '';

    protected static array $group_middleware = [];

    /**
     * Clear the static state.
     * 
     * Primarily used for isolation during testing.
     *
     * @return void
     */
    public static function clear() {
        static::$route_prefix     = '';
        static::$group_middleware = [];
    }

    /**
     * Create a group of routes with a common prefix and middleware.
     *
     * @param string $prefix
     * @param \Closure $callback
     * @return RouteGroup
     */
    public static function group( string $prefix, \Closure $callback ): RouteGroup {
        $previous_route_prefix     = static::$route_prefix;
        $previous_route_middleware = static::$group_middleware;

        static::$route_prefix .= '/' . trim( $prefix, '/' );
        
        $start_index = count( Router::get_routes() );

        call_user_func( $callback );
        
        $end_index  = count( Router::get_routes() );
        $new_routes = array_slice( Router::get_routes(), $start_index, $end_index - $start_index );

        static::$route_prefix     = $previous_route_prefix;
        static::$group_middleware = $previous_route_middleware;

        return new RouteGroup( $new_routes );
    }

    /**
     * Define a GET route.
     *
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    public static function get( string $route, $callback ): PendingRoute {
        return static::register_route( 'GET', $route, $callback );
    }

    /**
     * Define a POST route.
     *
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    public static function post( string $route, $callback ): PendingRoute {
        return static::register_route( 'POST', $route, $callback );
    }

    /**
     * Define a PUT route.
     *
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    public static function put( string $route, $callback ): PendingRoute {
        return static::register_route( 'PUT', $route, $callback );
    }

    /**
     * Define a PATCH route.
     *
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    public static function patch( string $route, $callback ): PendingRoute {
        return static::register_route( 'PATCH', $route, $callback );
    }

    /**
     * Define a DELETE route.
     *
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    public static function delete( string $route, $callback ): PendingRoute {
        return static::register_route( 'DELETE', $route, $callback );
    }

    /**
     * Match any HTTP verb.
     *
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    public static function any( string $route, $callback ): PendingRoute {
        return static::register_route( ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route, $callback );
    }

    /**
     * Match specific HTTP verbs.
     *
     * @param array $methods
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    public static function match( array $methods, string $route, $callback ): PendingRoute {
        return static::register_route( array_map( 'strtoupper', $methods ), $route, $callback );
    }

    /**
     * Define a group of resource routes.
     *
     * @param array $resources An associative array of resource names and controllers.
     * @return void
     */
    public static function resources( array $resources ) {
        foreach ( $resources as $resource => $callback ) {
            static::resource( $resource, $callback );
        }
    }

    /**
     * Define a resource route.
     *
     * @param string $route
     * @param mixed $callback
     * @return ResourceRegistration
     */
    public static function resource( string $route, $callback ): ResourceRegistration {
        $routes = [
            'index'  => [
                'method' => 'GET',
                'route'  => $route
            ],
            'store'  => [
                'method' => 'POST',
                'route'  => $route
            ],
            'show'   => [
                'method' => 'GET',
                'route'  => $route . '/{id}'
            ],
            'update' => [
                'method' => 'PATCH',
                'route'  => $route . '/{id}'
            ],
            'delete' => [
                'method' => 'DELETE',
                'route'  => $route . '/{id}'
            ],
        ];

        $pending_routes = [];
        foreach ( $routes as $callback_method => $args ) {
            $pending_routes[$callback_method] = static::register_route( $args['method'], $args['route'], [$callback, $callback_method] );
        }

        return new ResourceRegistration( $pending_routes );
    }

    /**
     * Internal method to register a route by adding it to the Router.
     *
     * @param string|array $method
     * @param string $route
     * @param mixed $callback
     * @return PendingRoute
     */
    protected static function register_route( $method, string $route, $callback ): PendingRoute {
        $pending_route = new PendingRoute( $method, $route, $callback );
        
        // Inherit group prefix and middleware
        $pending_route->prefix( static::$route_prefix );
        $pending_route->middleware( static::$group_middleware );

        Router::add_route( $pending_route );

        return $pending_route;
    }

    /**
     * Dispatch and register all routes with WordPress.
     *
     * @param string $type The type of route (rest or ajax).
     * @return void
     */
    public static function register_all( string $type = 'rest' ) {
        foreach ( Router::get_routes() as $route ) {
            if ( 'rest' === $type ) {
                static::register_with_wordpress_rest( $route );
            }
        }
        
        // Clear routes after registration to avoid duplicate registration in multifile environments
        Router::clear_pending();
    }

    /**
     * Physically register a PendingRoute with the WordPress REST API.
     *
     * @param PendingRoute $route The route instance to register.
     * @return void
     */
    protected static function register_with_wordpress_rest( PendingRoute $route ) {
        $details = $route->get_details();
        
        $data_binder = RouteServiceProvider::get_container()->get( DataBinder::class );
        $namespace   = $data_binder->get_namespace();
        
        $callback    = $details['callback'];
        $final_route = static::get_final_route_path( $details['uri'], $details['prefix'], true, $details['wheres'] );
        $middleware  = $details['middleware'];
        $method      = $details['methods'];

        rest_get_server()->register_route(
            $namespace, $final_route, [
                [
                    'methods'             => $method,
                    'callback'            => function( WP_REST_Request $wp_rest_request ) use( $callback, $final_route ) {
                        RouteServiceProvider::get_container()->set( WP_REST_Request::class, $wp_rest_request );

                        $properties = RouteServiceProvider::get_properties();

                        if ( ! empty( $properties['rest_response_action_hook'] ) ) {
                            do_action( $properties['rest_response_action_hook'], $wp_rest_request, $final_route );
                        }

                        if ( ! empty( $properties['rest_response_filter_hook'] ) ) {
                            return apply_filters( $properties['rest_response_filter_hook'], static::callback( $callback ), $wp_rest_request, $final_route );
                        }

                        return static::callback( $callback );
                    },
                    'permission_callback' => function( \WP_REST_Request $wp_rest_request ) use( $middleware, $final_route ) {
                        $permission = Middleware::is_user_allowed( $middleware, $wp_rest_request );

                        $properties = RouteServiceProvider::get_properties();

                        if ( ! empty( $properties['rest_permission_filter_hook'] ) ) {
                            $permission = apply_filters( $properties['rest_permission_filter_hook'], $permission, $middleware, $final_route );
                        }

                        if ( $permission instanceof \WP_Error ) {
                            $data   = $permission->get_error_data();
                            $status = isset( $data['status'] ) ? $data['status'] : 500;
                            static::set_status_code( (int) $status );
                        }
                        return $permission;
                    }
                ]
            ] 
        );
    }

    /**
     * Handle the callback execution and response transformation.
     *
     * Resolves dependencies via the container and prepares the response.
     *
     * @param mixed $callback The route callback.
     * @return mixed The processed response data or WP_REST_Response.
     */
    protected static function callback( $callback ) {
        try {
            $request = RouteServiceProvider::get_container()->get( \WP_REST_Request::class );
            $params  = $request ? $request->get_url_params() : [];

            $response = RouteServiceProvider::get_container()->call( $callback, $params );

            // If it's the structure from Response::send()
            if ( is_array( $response ) && isset( $response['status_code'] ) && array_key_exists( 'data', $response ) ) {
                $status_code = intval( $response['status_code'] );
                $data        = $response['data'];
                
                // If in REST context, wrap in WP_REST_Response to ensure status is respected
                if ( class_exists( '\WP_REST_Response' ) && ( defined( 'REST_REQUEST' ) || RouteServiceProvider::get_container()->get( \WP_REST_Request::class ) ) ) {
                    return new \WP_REST_Response( $data, $status_code );
                }

                static::set_status_code( $status_code );
                $data = $response['data'];
            } else {
                // Otherwise, treat the entire response as data with 200 status
                $status_code = 200;
                static::set_status_code( $status_code );
                $data = $response;
            }

            return $data;

        } catch ( \Exception $ex ) {
            $status_code = intval( $ex->getCode() );
            if ( $status_code < 100 || $status_code > 599 ) {
                $status_code = 500;
            }
            static::set_status_code( $status_code );

            $response = [
                'data' => [
                    'status_code' => $status_code
                ]
            ];

            $message = $ex->getMessage();

            if ( ! empty( $message ) ) {
                $response['message'] = $message;
            } else {
                if ( method_exists( $ex, 'get_messages' ) ) {
                    $messages = $ex->get_messages();
                    if ( ! empty( $messages ) ) {
                        $response['messages'] = $messages;
                    } else {
                        $response['message'] = 'Something went wrong.';
                    }
                } else {
                    $response['message'] = 'Something went wrong.';
                }
            }

            return $response;
        }
    }

    /**
     * Set the HTTP status code and register a filter to ensure it's honored.
     *
     * @param int $status_code The HTTP status code to set.
     * @return void
     */
    protected static function set_status_code( int $status_code ) {
        status_header( $status_code );
        /**
         * Filters the REST API response.
         *
         * @param WP_HTTP_Response $result  Result to send to the client. Usually a <code>WP_REST_Response</code>.
         */
        add_filter(
            'rest_post_dispatch', function( WP_HTTP_Response $result ) use( $status_code ) {
                $result->set_status( $status_code );
                return $result;
            }
        );
    }

    /**
     * Construct the final route path including namespace, version, and prefix.
     *
     * @param string $route        The base route URI.
     * @param string $prefix       Optional prefix for the route.
     * @param bool   $format_regex Whether to convert parameters to regex.
     * @param array  $wheres       Optional regex constraints for parameters.
     * @return string The fully qualified route path.
     */
    public static function get_final_route_path( string $route, string $prefix = '', bool $format_regex = true, array $wheres = [] ) {
        if ( ! empty( $prefix ) ) {
            $route = rtrim( $prefix, '/' ) . '/' . ltrim( $route, '/' );
        }

        $route = trim( $route, '/' );
        
        if ( $format_regex ) {
            $route = static::format_route_regex( $route, $wheres ?? [] );
        }

        $data_binder = RouteServiceProvider::get_container()->get( DataBinder::class );
        $namespace   = $data_binder->get_namespace();
        $version     = $data_binder->get_version();

        if ( ! empty( $version ) ) {
            return "/{$namespace}/{$version}/{$route}";
        }
        return "/{$namespace}/{$route}";
    }

    /**
     * Format route parameters into regex patterns.
     *
     * @param string $route  The route URI containing parameters.
     * @param array  $wheres Optional parameter constraints.
     * @return string The route URI with parameters converted to regex.
     */
    protected static function format_route_regex( string $route, array $wheres = [] ): string {
        if ( strpos( $route, '}' ) === false ) {
            return $route;
        }

        preg_match_all( '#\{(.*?)\}#', $route, $params );

        if ( strpos( $route, '?}' ) !== false ) {
            return static::optional_param( $route, $params, $wheres );
        } else {
            return static::required_param( $route, $params, $wheres );
        }
    }

    /**
     * Handle optional parameter formatting.
     *
     * @param string $route  The route URI.
     * @param array  $params The matched parameters.
     * @param array  $wheres Optional constraints.
     * @return string
     */
    protected static function optional_param( string $route, array $params, array $wheres = [] ): string {
        foreach ( $params[0] as $key => $value ) {
            $param_name = str_replace( '?', '', $params[1][$key] );
            $regex      = $wheres[$param_name] ?? '[-\w]+';
            $route      = str_replace( '/' . $value, '(?:/(?P<' . $param_name . '>' . $regex . '))?', $route );
        }

        return $route;
    }

    /**
     * Handle required parameter formatting.
     *
     * @param string $route  The route URI.
     * @param array  $params The matched parameters.
     * @param array  $wheres Optional constraints.
     * @return string
     */
    protected static function required_param( string $route, array $params, array $wheres = [] ): string {
        foreach ( $params[0] as $key => $value ) {
            $param_name = $params[1][$key];
            $regex      = $wheres[$param_name] ?? '[-\w]+';
            $route      = str_replace( $value, '(?P<' . $param_name . '>' . $regex . ')', $route );
        }

        return $route;
    }
}