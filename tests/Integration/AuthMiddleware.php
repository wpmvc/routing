<?php

namespace WpMVC\Routing\Tests\Integration;

use WpMVC\Routing\Contracts\Middleware as MiddlewareContract;
use WP_REST_Request;
use WP_Error;

class AuthMiddleware implements MiddlewareContract
{
    public function handle( WP_REST_Request $wp_rest_request, $next ) {
        if ( $wp_rest_request->get_param( 'token' ) === 'secret' ) {
            return $next( $wp_rest_request );
        }

        return new WP_Error( 'rest_forbidden', 'Forbidden', ['status' => 403] );
    }
}
