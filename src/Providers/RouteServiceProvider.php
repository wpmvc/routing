<?php
/**
 * Route service provider for bootstrapping the routing system.
 *
 * @package WpMVC\Routing\Providers
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing\Providers;

defined( 'ABSPATH' ) || exit;

use WpMVC\Routing\Response;
use WpMVC\Routing\DataBinder;
use WpMVC\Routing\Ajax;
use WpMVC\Routing\Route;
use WpMVC\Routing\Middleware;
use Wp;

/**
 * Class RouteServiceProvider
 *
 * Handles the registration of REST and AJAX routes with WordPress hooks.
 *
 * @package WpMVC\Routing\Providers
 */
abstract class RouteServiceProvider
{
    /**
     * The container instance.
     *
     * @var mixed
     */
    protected static $container;

    /**
     * The configuration properties.
     *
     * @var array
     */
    protected static $properties;

    /**
     * Set the container instance.
     *
     * @param  mixed  $container
     * @return void
     */
    public static function set_container( $container ): void {
        static::$container = $container;
    }

    /**
     * Get the container instance.
     *
     * @return mixed
     */
    public static function get_container() {
        return static::$container;
    }

    /**
     * Set the configuration properties.
     *
     * @param  array  $properties
     * @return void
     */
    public static function set_properties( array $properties ): void {
        static::$properties = $properties;
    }

    /**
     * Bootstrap the routing service.
     *
     * Registers actions for both REST API and general request parsing (AJAX).
     *
     * @return void
     */
    public function boot(): void {
        add_action( 'rest_api_init', [$this, 'action_rest_api_init'] );
        add_action( 'parse_request', [$this, 'action_ajax_api_init'], 1 );
    }

    /**
     * Initialize AJAX routes during request parsing.
     *
     * @param WP $wp Current WordPress environment instance.
     * @return void
     */
    public function action_ajax_api_init( WP $wp ): void {
        if ( ! isset( $wp->request ) || 1 !== preg_match( "@^" . static::$properties['ajax']['namespace'] . "/(.*)/?@i", $wp->request ) ) {
            return;
        }

        static::init_routes( 'ajax' );

        if ( ! Ajax::$route_found ) {
            status_header( 404 );
            Response::set_headers( [] );
            echo wp_json_encode(
                [
                    'code'    => 'ajax_no_route', 
                    'message' => 'No route was found matching the URL and request method.'
                ] 
            );
        }
        exit;
    }

    /**
     * Initialize REST API routes.
     * 
     * @return void
     */
    public function action_rest_api_init(): void {
        static::init_routes( 'rest' );
    }

    /**
     * Retrieve the current configuration properties.
     *
     * @return array
     */
    public static function get_properties(): array {
        return static::$properties ?? [];
    }

    /**
     * Scan and initialize route files.
     *
     * @param string $type The routing context ('rest' or 'ajax').
     * @return void
     */
    protected static function init_routes( string $type ): void {
        Middleware::set_middleware_list( static::$properties['middleware'] );

        $data_binder = static::$container->get( DataBinder::class );
        
        $data_binder->set_namespace( static::$properties[$type]['namespace'] );

        include static::$properties['routes-dir'] . "/{$type}/api.php";

        $versions = static::$properties[$type]['versions'];

        if ( is_array( $versions ) ) {

            foreach ( $versions as $version ) {
                $version_file = static::$properties['routes-dir'] . "/{$type}/{$version}/api.php";

                if ( is_file( $version_file ) ) {
                    $data_binder->set_version( $version );
                    include $version_file;
                }
            }
        }

        if ( 'rest' === $type ) {
            Route::register_all( 'rest' );
        } else {
            Ajax::register_all( 'ajax' );
        }
    }
}