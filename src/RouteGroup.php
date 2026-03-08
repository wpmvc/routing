<?php
/**
 * Route group handling.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Class RouteGroup
 * 
 * Manages fluent method chaining for a group of routes.
 * 
 * @package WpMVC\Routing
 */
class RouteGroup
{
    /**
     * The routes in the group.
     *
     * @var PendingRoute[]
     */
    protected array $routes;

    /**
     * Create a new RouteGroup instance.
     *
     * @internal Recommended to use Route::group() instead.
     * @param PendingRoute[] $routes The route instances in the group.
     */
    public function __construct( array $routes ) {
        $this->routes = $routes;
    }

    /**
     * Assign middleware to all routes within the group.
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
}
