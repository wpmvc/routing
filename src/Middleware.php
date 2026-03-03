<?php
/**
 * Middleware handling and execution.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

use WpMVC\Routing\Providers\RouteServiceProvider;
use WP_Error;

/**
 * Class Middleware
 *
 * Facilitates the registration and execution of middleware chains.
 *
 * @package WpMVC\Routing
 */
class Middleware {
    protected static array $middleware = [];

    /**
     * Set the global list of registered middleware.
     *
     * @param array $middleware An associative array of middleware names and class names.
     * @return void
     */
    public static function set_middleware_list( array $middleware ): void {
        static::$middleware = $middleware;
    }

    /**
     * Determine if the user is authorized for the given middleware list.
     *
     * Executes the middleware chain through the Pipeline.
     *
     * @param array                 $middleware         The names of middleware to execute.
     * @param \WP_REST_Request|null $wp_rest_request    The current request instance.
     * @param bool                  $default_permission The default permission if no middleware fails.
     * @return bool|WP_Error Returns true if allowed, false if forbidden, or a WP_Error on failure.
     */
    public static function is_user_allowed( array $middleware, $wp_rest_request = null, bool $default_permission = true ) {
        if ( null !== $wp_rest_request && ! $wp_rest_request instanceof \WP_REST_Request ) {
             return false;
        }

        if ( null === $wp_rest_request ) {
            $wp_rest_request = RouteServiceProvider::$container->get( \WP_REST_Request::class );
        }

        $pipes = [];

        foreach ( $middleware as $name ) {
            if ( isset( static::$middleware[$name] ) ) {
                $pipes[] = static::$middleware[$name];
            }
        }

        if ( empty( $pipes ) ) {
            return $default_permission;
        }

        $pipeline = new Pipeline();

        return $pipeline->send( $wp_rest_request )
            ->through( $pipes )
            ->then(
                function ( $request ) use ( $default_permission ) {
                    return $default_permission;
                } 
            );
    }
}
