<?php

namespace WpMVC\Routing\Tests\Integration;

use WP_UnitTestCase;

class SampleIntegrationTest extends WP_UnitTestCase
{
    public function test_wp_is_loaded() {
        $this->assertTrue( function_exists( 'get_bloginfo' ) );
        $this->assertTrue( true );
    }
}
