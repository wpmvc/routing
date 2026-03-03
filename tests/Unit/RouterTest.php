<?php

namespace WpMVC\Routing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpMVC\Routing\Router;
use WpMVC\Routing\PendingRoute;

class RouterTest extends TestCase
{
    protected function setUp(): void {
        Router::clear();
    }

    public function test_add_and_get_routes() {
        $route = new PendingRoute( 'GET', 'test', 'cb' );
        Router::add_route( $route );
        
        $this->assertCount( 1, Router::get_routes() );
        $this->assertSame( $route, Router::get_routes()[0] );
    }

    public function test_named_route_registry() {
        $route = new PendingRoute( 'GET', 'test', 'cb' );
        Router::add_named_route( 'my.route', $route );
        
        $this->assertSame( $route, Router::get_named_route( 'my.route' ) );
        $this->assertNull( Router::get_named_route( 'non.existent' ) );
    }

    public function test_clear_pending() {
        $route = new PendingRoute( 'GET', 'test', 'cb' );
        Router::add_route( $route );
        Router::add_named_route( 'name', $route );
        
        Router::clear_pending();
        
        $this->assertEmpty( Router::get_routes() );
        $this->assertNotNull( Router::get_named_route( 'name' ) );
    }
}
