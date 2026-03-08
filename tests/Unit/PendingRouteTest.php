<?php

namespace WpMVC\Routing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpMVC\Routing\PendingRoute;

class PendingRouteTest extends TestCase
{
    public function test_fluent_chaining() {
        $route = new PendingRoute( 'GET', 'users', function() { return 'ok'; } );
        
        $returned = $route->name( 'user.index' )->middleware( ['auth'] );
        
        $this->assertSame( $route, $returned );
        
        $details = $route->get_details();
        $this->assertEquals( 'user.index', $details['name'] );
        $this->assertEquals( ['auth'], $details['middleware'] );
    }

    public function test_group_middleware_merging() {
        $route = new PendingRoute( 'GET', 'profile', 'callback', ['group-mid'] );
        $route->middleware( ['route-mid'] );
        
        $details = $route->get_details();
        $this->assertEquals( ['group-mid', 'route-mid'], $details['middleware'] );
    }

    public function test_prefix_appending() {
        $route = new PendingRoute( 'GET', 'settings', 'callback' );
        $route->prefix( 'user' );
        
        $details = $route->get_details();
        $this->assertEquals( 'user', $details['prefix'] );
    }
}
