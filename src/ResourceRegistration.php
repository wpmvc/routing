<?php
/**
 * Resource route registration handling.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Class ResourceRegistration
 * 
 * Manages fluent method chaining for resource-based routes.
 * 
 * @package WpMVC\Routing
 */
class ResourceRegistration
{
    /**
     * The registered resource routes.
     *
     * @var PendingRoute[]
     */
    protected array $routes;

    /**
     * Create a new ResourceRegistration instance.
     *
     * @internal Recommended to use Route::resource() instead.
     * @param PendingRoute[] $routes The registered resource route instances.
     */
    public function __construct( array $routes ) {
        $this->routes = $routes;
    }

    /**
     * Restrict the resource to only the specified actions.
     *
     * @param array $items The actions to keep (e.g., ['index', 'show']).
     * @return $this Returns the current instance for method chaining.
     */
    public function only( array $items ): self {
        foreach ( $this->routes as $method => $route ) {
            if ( ! in_array( $method, $items ) ) {
                Router::remove_route( $route );
                unset( $this->routes[$method] );
            }
        }

        return $this;
    }

    /**
     * Exclude specific actions from the resource registration.
     *
     * @param array $items The actions to remove.
     * @return $this Returns the current instance for method chaining.
     */
    public function except( array $items ): self {
        foreach ( $this->routes as $method => $route ) {
            if ( in_array( $method, $items ) ) {
                Router::remove_route( $route );
                unset( $this->routes[$method] );
            }
        }

        return $this;
    }

    /**
     * Assign middleware to all routes in the current resource.
     *
     * @param array|string $middleware The middleware name(s).
     * @return $this Returns the current instance for method chaining.
     */
    public function middleware( $middleware ): self {
        foreach ( $this->routes as $route ) {
            $route->middleware( $middleware );
        }

        return $this;
    }

    /**
     * Assign a base name for the resource routes.
     *
     * @param string $name The base name.
     * @return $this Returns the current instance for method chaining.
     */
    public function name( string $name ): self {
        // Resource naming is usually done per route, 
        // but this allows for a base name if needed.
        return $this;
    }
}
