<?php
/**
 * Representation of a route before registration.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Class PendingRoute
 *
 * Holds the configuration for a route that is yet to be registered with WordPress.
 *
 * @package WpMVC\Routing
 */
class PendingRoute
{
    /**
     * The HTTP methods for the route.
     *
     * @var string|array
     */
    protected $methods;

    /**
     * The URI pattern for the route.
     *
     * @var string
     */
    protected string $uri;

    /**
     * The callback for the route.
     *
     * @var mixed
     */
    protected $callback;

    /**
     * The middleware for the route.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * The name of the route.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The prefix for the route.
     *
     * @var string
     */
    protected string $prefix = '';

    /**
     * The regex constraints for parameters.
     *
     * @var array
     */
    protected array $wheres = [];

    /**
     * Create a new PendingRoute instance.
     *
     * @internal Recommended to use Route::get(), Route::post(), etc. instead.
     *
     * @param string|array $methods    The HTTP method(s) for the route.
     * @param string       $uri        The route URI pattern.
     * @param mixed        $callback   The route callback.
     * @param array        $middleware Optional initial middleware.
     */
    public function __construct( $methods, string $uri, $callback, array $middleware = [] ) {
        $this->methods    = $methods;
        $this->uri        = $uri;
        $this->callback   = $callback;
        $this->middleware = $middleware;
    }

    /**
     * Assign middleware to the route.
     *
     * @param array|string $middleware The middleware name(s).
     * @return $this Returns the current instance for method chaining.
     */
    public function middleware( $middleware ): self {
        if ( is_string( $middleware ) ) {
            $middleware = [ $middleware ];
        }

        $this->middleware = array_merge( $this->middleware, $middleware );

        return $this;
    }

    /**
     * Assign a unique name to the route.
     *
     * @param string $name The unique name.
     * @return $this Returns the current instance for method chaining.
     */
    public function name( string $name ): self {
        $this->name = $name;

        Router::add_named_route( $name, $this );

        return $this;
    }

    /**
     * Assign a prefix to the route URI.
     *
     * @param string $prefix The URI prefix.
     * @return $this Returns the current instance for method chaining.
     */
    public function prefix( string $prefix ): self {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Add a regex constraint for a route parameter.
     *
     * @param string $name       The parameter name.
     * @param string $expression The regex pattern.
     * @return $this Returns the current instance for method chaining.
     */
    public function where( string $name, string $expression ): self {
        $this->wheres[$name] = $expression;

        return $this;
    }

    /**
     * Add multiple regex constraints for route parameters.
     *
     * @param array $wheres An associative array of parameter names and expressions.
     * @return $this Returns the current instance for method chaining.
     */
    public function wheres( array $wheres ): self {
        $this->wheres = array_merge( $this->wheres, $wheres );

        return $this;
    }

    /**
     * Retrieve the route configuration details.
     *
     * @return array An associative array containing methods, uri, callback, middleware, name, prefix, and wheres.
     */
    public function get_details(): array {
        return [
            'methods'    => $this->methods,
            'uri'        => $this->uri,
            'callback'   => $this->callback,
            'middleware' => $this->middleware,
            'name'       => $this->name,
            'prefix'     => $this->prefix,
            'wheres'     => $this->wheres,
        ];
    }
}
