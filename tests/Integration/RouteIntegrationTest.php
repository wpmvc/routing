<?php

namespace WpMVC\Routing\Tests\Integration;

use WpMVC\Routing\Route;
use WpMVC\Routing\Ajax;
use WpMVC\Routing\Router;
use WpMVC\Routing\Response;
use WpMVC\Routing\Providers\RouteServiceProvider;
use WpMVC\Routing\DataBinder;
use WP_UnitTestCase;
use WP_REST_Request;

class RouteIntegrationTest extends WP_UnitTestCase
{
    protected $container;

    public function set_up() {
        parent::set_up();
        
        Router::clear();
        Route::clear();
        Ajax::clear();
        Ajax::$should_exit = false;
        
        // We need a basic container for the tests
        $this->container = new class {
            protected $instances = [];

            public function get( $id ) { 
                if ( ! isset( $this->instances[$id] ) && class_exists( $id ) ) {
                    $this->instances[$id] = new $id();
                }
                return $this->instances[$id] ?? null; 
            }

            public function set( $id, $instance ) {
                $this->instances[$id] = $instance; }

            public function call( $callback, array $args = [] ) { 
                if ( empty( $args ) ) {
                    $request = $this->instances['WP_REST_Request'] ?? null;
                    if ( $request instanceof \WP_REST_Request ) {
                        $args = $request->get_params();
                    }
                }
                if ( is_array( $callback ) ) {
                    $class  = is_object( $callback[0] ) ? $callback[0] : $this->get( $callback[0] );
                    $method = $callback[1];
                    $ref    = new \ReflectionMethod( $class, $method );
                } else {
                    $ref = new \ReflectionFunction( $callback );
                }

                $resolved = [];
                foreach ( $ref->getParameters() as $param ) {
                    $name      = $param->getName();
                    $type      = $param->getType();
                    $type_name = ( $type && ! $type->isBuiltin() ) ? $type->getName() : null;
                    
                    // 1. Try by name
                    if ( isset( $args[$name] ) ) {
                        $resolved[] = $args[$name];
                        unset( $args[$name] );
                        continue;
                    }

                    // 2. Try by type from args
                    if ( $type_name ) {
                        foreach ( $args as $key => $val ) {
                            if ( is_object( $val ) && is_a( $val, $type_name ) ) {
                                $resolved[] = $val;
                                unset( $args[$key] );
                                continue 2;
                            }
                        }

                        // 3. Try by type from container
                        if ( $type_name === 'WP_REST_Request' ) {
                            $resolved[] = $this->get( 'WP_REST_Request' );
                            continue;
                        }
                        $instance = $this->get( $type_name );
                        if ( $instance ) {
                            $resolved[] = $instance;
                            continue;
                        }
                    }

                    // 4. Positional fallback for builtin/untyped
                    if ( ! empty( $args ) ) {
                        $resolved[] = array_shift( $args );
                        continue;
                    }

                    // 5. Default value
                    if ( $param->isDefaultValueAvailable() ) {
                        $resolved[] = $param->getDefaultValue();
                    } else {
                        $resolved[] = null;
                    }
                }

                if ( is_array( $callback ) ) {
                    return $ref->invokeArgs( is_object( $callback[0] ) ? $callback[0] : $this->get( $callback[0] ), $resolved );
                }
                return $ref->invokeArgs( $resolved );
            }
        };

        $data_binder = new DataBinder();
        $data_binder->set_namespace( 'wpmvc' );
        $this->container->set( DataBinder::class, $data_binder );
        
        RouteServiceProvider::set_container( $this->container );
    }

    /**
     * Test that routes are registered correctly in WordPress.
     */
    public function test_route_registration_in_wordpress() {
        Route::get(
            'test-route', function() {
                return Response::send( ['success' => true] );
            }
        )->name( 'test.route' );

        // Trigger deferred registration
        Route::register_all( 'rest' );

        $request  = new WP_REST_Request( 'GET', '/wpmvc/test-route' );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( ['success' => true], $response->get_data() );
    }

    /**
     * Test named route URL generation using the real rest_url().
     */
    public function test_named_route_url_generation() {
        Route::get( 'users/{id}', function() { return 'ok'; } )->name( 'user.show' );
        
        $url = Router::url( 'user.show', ['id' => 123] );
        
        $this->assertTrue(
            strpos( $url, '/wpmvc/users/123' ) !== false || strpos( $url, 'rest_route=/wpmvc/users/123' ) !== false,
            "URL does not contain expected route path: $url"
        );
    }

    /**
     * Test route groups with prefixes and nested registration.
     */
    public function test_route_groups_integration() {
        Route::group(
            'v1', function() {
                Route::get( 'ping', function() { return 'pong'; } )->name( 'v1.ping' );
            }
        );

        Route::register_all( 'rest' );

        $request  = new WP_REST_Request( 'GET', '/wpmvc/v1/ping' );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 'pong', $response->get_data() );
    }

    /**
     * Test Resource routing registration.
     */
    public function test_resource_routing() {
        Route::resource( 'posts', TestController::class );
        Route::register_all( 'rest' );

        // Test index (GET /posts)
        $request  = new WP_REST_Request( 'GET', '/wpmvc/posts' );
        $response = rest_do_request( $request );
        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 'index', $response->get_data() );

        // Test store (POST /posts)
        $request  = new WP_REST_Request( 'POST', '/wpmvc/posts' );
        $response = rest_do_request( $request );
        $this->assertEquals( 201, $response->get_status() );
        $this->assertEquals( 'store', $response->get_data() );

        // Test show (GET /posts/123)
        $request  = new WP_REST_Request( 'GET', '/wpmvc/posts/123' );
        $response = rest_do_request( $request );
        $this->assertEquals( 200, $response->get_status() );
        $data = $response->get_data();
        $this->assertEquals( '123', $data['id'] );
        $this->assertTrue( $data['has_request'] );
    }

    /**
     * Test Controller Injection and parameter order.
     */
    public function test_dynamic_parameter_injection_order() {
        Route::get( 'posts/{post_id}/comments/{comment_id}', [TestController::class, 'multipart'] );
        Route::register_all( 'rest' );

        $request  = new WP_REST_Request( 'GET', '/wpmvc/posts/10/comments/20' );
        $response = rest_do_request( $request );

        $this->assertEquals( 200, $response->get_status() );
        $data = $response->get_data();
        
        // We need to verify that Route::callback is actually passing parameters to the container.
        $this->assertEquals( '10', $data['1'], "First parameter (post_id) should be '10'" );
        $this->assertEquals( '20', $data['2'], "Second parameter (comment_id) should be '20'" );
    }

    /**
     * Test Middleware enforcement.
     */
    public function test_middleware_enforcement() {
        // Register middleware in the provider list
        \WpMVC\Routing\Middleware::set_middleware_list(
            [
                'auth' => AuthMiddleware::class
            ]
        );

        Route::get( 'secure', function() { return Response::send( 'secret-data' ); } )->middleware( 'auth' );
        
        Route::register_all( 'rest' );

        // Test Forbidden
        $request  = new WP_REST_Request( 'GET', '/wpmvc/secure' );
        $response = rest_do_request( $request );
        $this->assertEquals( 403, $response->get_status() );

        // Test Allowed
        $request->set_param( 'token', 'secret' );
        $response = rest_do_request( $request );
        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 'secret-data', $response->get_data() );
    }

    /**
     * Test Multi-level Nested Groups.
     */
    public function test_multi_level_groups() {
        Route::group(
            'api', function() {
                Route::group(
                    'v1', function() {
                        Route::get( 'users', function() { return Response::send( 'users' ); } )->name( 'api.v1.users' );
                    }
                );
            }
        );

        Route::register_all( 'rest' );

        $url = Router::url( 'api.v1.users' );
        $this->assertTrue(
            strpos( $url, 'wpmvc/api/v1/users' ) !== false,
            "URL does not contain expected_path: $url"
        );

        $request  = new WP_REST_Request( 'GET', '/wpmvc/api/v1/users' );
        $response = rest_do_request( $request );
        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 'users', $response->get_data() );
    }

    /**
     * Test optional parameters.
     */
    public function test_optional_parameters() {
        Route::get(
            'profile/{tab?}', function( $tab = 'overview' ) {
                return Response::send( $tab );
            }
        );
        Route::register_all( 'rest' );

        // Test without optional param
        $request  = new WP_REST_Request( 'GET', '/wpmvc/profile' );
        $response = rest_do_request( $request );
        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 'overview', $response->get_data() );

        // Test with optional param
        $request  = new WP_REST_Request( 'GET', '/wpmvc/profile/settings' );
        $response = rest_do_request( $request );
        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 'settings', $response->get_data() );
    }

    /**
     * Test Resource filtering with 'only' and 'except'.
     */
    public function test_resource_filtering() {
        // Only index
        Route::resource( 'tags', TestController::class )->only( ['index'] );
        
        // Except delete
        Route::resource( 'comments', TestController::class )->except( ['delete'] );

        Route::register_all( 'rest' );

        // Tags should have index but not store
        $this->assertEquals( 200, rest_do_request( new WP_REST_Request( 'GET', '/wpmvc/tags' ) )->get_status() );
        $this->assertEquals( 404, rest_do_request( new WP_REST_Request( 'POST', '/wpmvc/tags' ) )->get_status() );

        // Comments should have index and show, but NOT delete
        $this->assertEquals( 200, rest_do_request( new WP_REST_Request( 'GET', '/wpmvc/comments' ) )->get_status() );
        $this->assertEquals( 200, rest_do_request( new WP_REST_Request( 'GET', '/wpmvc/comments/1' ) )->get_status() );
        $this->assertEquals( 404, rest_do_request( new WP_REST_Request( 'DELETE', '/wpmvc/comments/1' ) )->get_status() );
    }

    /**
     * Test nested middleware inheritance.
     */
    public function test_nested_middleware_inheritance() {
        \WpMVC\Routing\Middleware::set_middleware_list(
            [
                'auth'  => AuthMiddleware::class,
                'admin' => class_exists( 'AdminMiddleware' ) ? 'AdminMiddleware' : AuthMiddleware::class // Reuse for simplicity
            ]
        );

        Route::group(
            'admin', function() {
                Route::get( 'dashboard', function() { return 'ok'; } )->middleware( 'auth' );
            }
        )->middleware( ['admin'] );

        Route::register_all( 'rest' );

        $request = new WP_REST_Request( 'GET', '/wpmvc/admin/dashboard' );
        
        // Should require both 'admin' and 'auth'. 
        // In our case both check for 'token=secret'.
        $response = rest_do_request( $request );
        $this->assertEquals( 403, $response->get_status() );

        $request->set_param( 'token', 'secret' );
        $response = rest_do_request( $request );
        $this->assertEquals( 200, $response->get_status() );
    }

    /**
     * Test Ajax error handling (401 and 404).
     */
    public function test_ajax_error_handling() {
        global $wp;
        
        // 401 Unauthorized
        \WpMVC\Routing\Middleware::set_middleware_list( ['auth' => AuthMiddleware::class] );
        Route::get( 'ajax-secure', function() { return 'ok'; } )->middleware( 'auth' );
        
        $wp = new class { public $request = 'wpmvc/ajax-secure'; };
        ob_start();
        Ajax::register_all( 'ajax' );
        $output = ob_get_clean();
        $this->assertStringContainsString( 'ajax_forbidden', $output );

        // 404 Not Found
        Ajax::clear(); // Reset state for the next check

        $wp = new class { public $request = 'wpmvc/non-existent'; };
        ob_start();
        // RouteServiceProvider normally handles the 404 logic if Ajax::$route_found is false
        // But for direct Ajax::register_all calling, we just check route_found
        Ajax::register_all( 'ajax' );
        ob_get_clean();
        $this->assertFalse( Ajax::$route_found );
    }

    /**
     * Test Ajax route matching.
     */
    public function test_ajax_route_matching() {
        global $wp;

        $wp = new class { public $request = 'wpmvc/ajax-test'; };

        Route::get( 'ajax-test', function() { return Response::send( ['ajax' => true] ); } )->name( 'ajax.test' );

        ob_start();
        // We use Ajax::register_all() for "ajax" type
        Ajax::register_all( 'ajax' );
        $output = ob_get_clean();

        $this->assertTrue( Ajax::$route_found );
        $this->assertStringContainsString( '{"ajax":true}', $output );
    }
}
