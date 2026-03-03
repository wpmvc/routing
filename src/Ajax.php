<?php
/**
 * AJAX routing handler.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

use WpMVC\Routing\Providers\RouteServiceProvider;
use WP_REST_Request;
use WP_REST_Server;
use WP;

/**
 * Class Ajax
 *
 * Handles matching and executing routes during a WordPress AJAX request.
 *
 * @package WpMVC\Routing
 */
class Ajax extends Route {
    /**
     * @var bool
     */
    public static bool $route_found = false;

    /**
     * @var bool
     */
    public static bool $should_exit = true;

    /**
     * Clear the static state.
     * 
     * Primarily used for isolation during testing.
     *
     * @return void
     */
    public static function clear(): void {
        parent::clear();
        static::$route_found = false;
        static::$should_exit = true;
    }

    /**
     * Dispatch and register all pending routes for AJAX handling.
     *
     * @param string $type The type of route (defaults to 'ajax').
     * @return void
     */
    public static function register_all( string $type = 'ajax' ) {
        foreach ( Router::get_routes() as $route ) {
            static::register_with_wordpress_ajax( $route );
        }

        Router::clear_pending();
    }

    /**
     * Physically match and execute a PendingRoute for the current AJAX request.
     *
     * @param PendingRoute $route The route instance to match.
     * @return void
     */
    protected static function register_with_wordpress_ajax( PendingRoute $route ): void {
        $details = $route->get_details();
        $method  = $details['methods'];

        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        if ( $method !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        $final_route = static::get_final_route_path( $details['uri'], $details['prefix'] );
        $middleware  = $details['middleware'];
        $callback    = $details['callback'];
       
        global $wp;
        /** @var WP $wp */

        $match = preg_match( '@^' . $final_route . '$@i', rtrim( '/' . $wp->request, '/' ), $matches );

        if ( ! $match ) {
            return;
        }

        static::$route_found = true;

        $url_params = [];

        foreach ( $matches as $param => $value ) {
            if ( ! is_int( $param ) ) {
                $url_params[ $param ] = $value;
            }
        }

        /**
         * Fire admin init if the current API has admin middleware
         */
        static::admin_init( $middleware );

        $request = static::get_wp_rest_request( $method, $url_params );

        $is_allowed = Middleware::is_user_allowed( $middleware, $request );

        if ( is_wp_error( $is_allowed ) || ! $is_allowed ) {
            status_header( 401 );
            Response::set_headers( [] );
            echo wp_json_encode(
                [
                    'code'    => 'ajax_forbidden', 
                    'message' => 'Sorry, you are not allowed to do that.'
                ] 
            );
            if ( static::$should_exit ) {
                exit;
            }
            return;
        }

        $response = static::callback( $callback );

        echo wp_json_encode( $response );
        
        if ( static::$should_exit ) {
            exit;
        }
    }

    /**
     * Initialize WordPress administration APIs if required by middleware.
     * 
     * @param array $middleware The list of middleware for the route.
     * @return void
     */
    protected static function admin_init( array $middleware ): void {

        if ( ! in_array( 'admin', $middleware ) ) {
            return;
        }
        
        if ( ! defined( 'WP_ADMIN' ) ) {
            define( 'WP_ADMIN', true );
        }

        /** Load WordPress Administration APIs */
        require_once ABSPATH . 'wp-admin/includes/admin.php';

        /** This action is documented in wp-admin/admin.php */
        do_action( 'admin_init' );
    }

    /**
     * Create a WP_REST_Request object populated with current request data.
     *
     * @param string $method     The HTTP request method.
     * @param array  $url_params Parameters extracted from the URL.
     * @return \WP_REST_Request The populated request object.
     */
    protected static function get_wp_rest_request( string $method, array $url_params = [] ): \WP_REST_Request {

        $wp_rest_request = new \WP_REST_Request( $method, '/' );
        $wp_rest_server  = new \WP_REST_Server;

        $wp_rest_request->set_url_params( $url_params );
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $wp_rest_request->set_query_params( wp_unslash( $_GET ) );
        //phpcs:ignore WordPress.Security.NonceVerification.Missing
        $wp_rest_request->set_body_params( wp_unslash( $_POST ) );
        $wp_rest_request->set_file_params( $_FILES );
        $wp_rest_request->set_headers( $wp_rest_server->get_headers( wp_unslash( $_SERVER ) ) );
        $wp_rest_request->set_body( $wp_rest_server->get_raw_data() );

        RouteServiceProvider::get_container()->set( \WP_REST_Request::class, $wp_rest_request );

        return $wp_rest_request;
    }
}