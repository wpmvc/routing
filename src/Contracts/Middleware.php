<?php
/**
 * Middleware contract for routing.
 *
 * @package WpMVC\Routing\Contracts
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing\Contracts;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_Error;

/**
 * Interface Middleware
 *
 * Defines the contract that all routing middleware must implement.
 *
 * @package WpMVC\Routing\Contracts
 */
interface Middleware {
    /**
     * Handle an incoming request and determine authorization.
     *
     * @param WP_REST_Request $wp_rest_request The current request instance.
     * @param mixed           $next            The next middleware closure in the stack.
     * @return bool|WP_Error Returns true to continue, false to forbid, or WP_Error.
     */
    public function handle( WP_REST_Request $wp_rest_request, $next );
}