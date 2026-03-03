<?php
/**
 * Router class for managing route collection.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Class Router
 *
 * Manages the collection of all registered routes and provides URL generation.
 *
 * @package WpMVC\Routing
 */
class Router
{
    /**
     * The collection of pending routes.
     *
     * @var PendingRoute[]
     */
    protected static array $routes = [];

    /**
     * The collection of named routes.
     *
     * @var array
     */
    protected static array $named_routes = [];

    /**
     * Add a pending route to the collection.
     *
     * @param PendingRoute $route The route instance to add.
     * @return void
     */
    public static function add_route( PendingRoute $route ) {
        static::$routes[] = $route;
    }

    /**
     * Remove a pending route from the collection.
     * 
     * @param PendingRoute $route The route instance to remove.
     * @return void
     */
    public static function remove_route( PendingRoute $route ) {
        foreach ( static::$routes as $key => $r ) {
            if ( $r === $route ) {
                unset( static::$routes[$key] );
                static::$routes = array_values( static::$routes );
                break;
            }
        }
    }

    /**
     * Map a unique name to a route instance.
     *
     * @param string       $name  The unique name.
     * @param PendingRoute $route The route instance.
     * @return void
     */
    public static function add_named_route( string $name, PendingRoute $route ) {
        static::$named_routes[ $name ] = $route;
    }

    /**
     * Get all registered routes.
     *
     * @return PendingRoute[] An array of registered route instances.
     */
    public static function get_routes(): array {
        return static::$routes;
    }

    /**
     * Retrieve a route instance by its unique name.
     *
     * @param string $name The name of the route.
     * @return PendingRoute|null The route instance if found, otherwise null.
     */
    public static function get_named_route( string $name ): ?PendingRoute {
        return static::$named_routes[ $name ] ?? null;
    }

    /**
     * Generate a fully qualified URL for a named route.
     *
     * @param string $name       The name of the route.
     * @param array  $parameters Optional parameters to replace in the URI.
     * @return string The generated URL, or an empty string if required parameters are missing.
     */
    public static function url( string $name, array $parameters = [] ): string {
        $route = static::get_named_route( $name );

        if ( ! $route ) {
            return '';
        }

        $details = $route->get_details();
        
        // Use the Route class to get the final path (including namespace/version/prefix)
        $uri = Route::get_final_route_path( $details['uri'], $details['prefix'], false );

        // Replace required parameters {param}
        foreach ( $parameters as $key => $value ) {
            $uri = str_replace( '{' . $key . '}', $value, $uri );
        }

        // Replace optional parameters {param?}
        foreach ( $parameters as $key => $value ) {
            $uri = str_replace( '{' . $key . '?}', $value, $uri );
        }

        // Check for UNRESOLVED required parameters
        if ( preg_match( '/\{[^\?\}]+\}/', $uri ) ) {
            // Some required parameters were not replaced.
            return '';
        }

        // Remove any remaining optional parameter placeholder
        $uri = preg_replace( '/\/\{[^\}]+\?\}/', '', $uri );

        return rest_url( $uri );
    }

    /**
     * Clear the registry.
     *
     * @param bool $clear_routes
     * @param bool $clear_named_routes
     * @return void
     */
    public static function clear( bool $clear_routes = true, bool $clear_named_routes = true ) {
        if ( $clear_routes ) {
            static::$routes = [];
        }
        if ( $clear_named_routes ) {
            static::$named_routes = [];
        }
    }

    /**
     * Clear only pending routes from the collection.
     *
     * Useful after routes have been physically registered with WordPress.
     *
     * @return void
     */
    public static function clear_pending() {
        static::clear( true, false );
    }
}
