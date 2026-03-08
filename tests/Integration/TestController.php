<?php

namespace WpMVC\Routing\Tests\Integration;

use WpMVC\Routing\Response;
use WP_REST_Request;

class TestController
{
    public function index( WP_REST_Request $request ) {
        return Response::send( 'index' );
    }

    public function show( $id, WP_REST_Request $request ) {
        return Response::send(
            [
                'id'          => $id,
                'has_request' => $request instanceof WP_REST_Request
            ] 
        );
    }

    public function store() {
        return Response::send( 'store', 201 );
    }

    public function update( $id ) {
        return Response::send( "update-{$id}" );
    }

    public function delete( $id ) {
        return Response::send( "delete-{$id}" );
    }

    public function multipart( $first, $second, $third ) {
        return Response::send(
            [
                '1' => $first,
                '2' => $second,
                '3' => $third
            ] 
        );
    }
}
