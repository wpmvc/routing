<?php
/**
 * Pipeline pattern implementation for middleware execution.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

use Closure;

/**
 * Class Pipeline
 * 
 * Orchestrates the execution of a chain of middleware/pipes using a $next closure.
 * 
 * @package WpMVC\Routing
 */
class Pipeline
{
    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of pipes (middleware).
     *
     * @var array
     */
    protected array $pipes = [];

    /**
     * Set the object being sent through the pipeline.
     *
     * @param mixed $passable The object to pass through the pipes.
     * @return $this Returns the current instance for method chaining.
     */
    public function send( $passable ): self {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the list of pipes (middleware) to pass the object through.
     *
     * @param array $pipes An array of middleware class names or callables.
     * @return $this Returns the current instance for method chaining.
     */
    public function through( array $pipes ): self {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Run the pipeline with a final destination closure.
     *
     * @param Closure $destination The final closure to execute after all pipes.
     * @return mixed The result of the pipeline execution.
     */
    public function then( Closure $destination ) {
        $pipeline = array_reduce(
            array_reverse( $this->pipes ),
            $this->carry(),
            $destination
        );

        return $pipeline( $this->passable );
    }

    /**
     * Get a closure that represents a single slice of the pipeline.
     *
     * @return Closure
     */
    protected function carry(): Closure {
        return function ( $stack, $pipe ) {
            return function ( $passable ) use ( $stack, $pipe ) {
                if ( is_callable( $pipe ) ) {
                    return $pipe( $passable, $stack );
                }

                $container  = Providers\RouteServiceProvider::get_container();
                $middleware = $container->get( $pipe );

                return $container->call( [$middleware, 'handle'], [$passable, $stack] );
            };
        };
    }
}
