<?php
/**
 * Response helper for routing.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Class Response
 *
 * Provides utility methods for preparing and sending API responses.
 *
 * @package WpMVC\Routing
 */
class Response
{
    /**
     * Prepare a standard response array.
     *
     * @param mixed $data        The response data.
     * @param int   $status_code The HTTP status code.
     * @param array $headers     Optional custom headers.
     * @return array The prepared response array.
     */
    public static function send( $data, int $status_code = 200, array $headers = [] ): array {
        static::set_headers( $headers );
        return compact( 'data', 'status_code' );
    }

    /**
     * Set HTTP headers for the response.
     *
     * @param array $headers An associative array of headers.
     * @param bool  $default Whether to include default JSON headers.
     * @return void
     */
    public static function set_headers( array $headers, bool $default = true ): void {
        if ( headers_sent() ) {
            return;
        }

        if ( $default ) {
            $default_headers = [
                'Content-Type' => 'application/json',
                'charset'      => get_option( 'blog_charset' )
            ];
            $headers         = array_merge( $default_headers, $headers );
        }

        foreach ( $headers as $key => $value ) {
            header( "{$key}: {$value}" );
        }
    }
}