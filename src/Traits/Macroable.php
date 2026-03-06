<?php
/**
 * Trait to allow runtime extension of classes.
 *
 * @package WpMVC\Routing\Traits
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing\Traits;

defined( 'ABSPATH' ) || exit;

use Closure;
use BadMethodCallException;

/**
 * Trait Macroable
 * 
 * Provides a mechanism to dynamically add methods to a class at runtime.
 * 
 * @package WpMVC\Routing\Traits
 */
trait Macroable
{
    /**
     * The registered macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * Register a custom macro.
     *
     * @param string          $name  The name of the macro.
     * @param object|callable $macro The macro callback.
     * @return void
     */
    public static function macro( $name, $macro ) {
        static::$macros[$name] = $macro;
    }

    /**
     * Determine if a macro is registered with the given name.
     *
     * @param string $name The name of the macro.
     * @return bool True if registered, false otherwise.
     */
    public static function has_macro( $name ) {
        return isset( static::$macros[$name] );
    }

    /**
     * Dynamically handle static calls to registered macros.
     *
     * @param string $method     The name of the macro to call.
     * @param array  $parameters The arguments for the macro.
     * @return mixed The result of the macro execution.
     *
     * @throws BadMethodCallException If the macro does not exist.
     */
    public static function __callStatic( $method, $parameters ) {
        if ( ! static::has_macro( $method ) ) {
            throw new BadMethodCallException(
                sprintf(
                    'Method %s::%s does not exist.', static::class, $method
                ) 
            );
        }

        $macro = static::$macros[$method];

        if ( $macro instanceof Closure ) {
            return call_user_func_array( Closure::bind( $macro, null, static::class ), $parameters );
        }

        return call_user_func_array( $macro, $parameters );
    }
}
