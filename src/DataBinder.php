<?php
/**
 * Data binder for route metadata.
 *
 * @package WpMVC\Routing
 * @author  WpMVC
 * @license MIT
 */

namespace WpMVC\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Class DataBinder
 *
 * Persists the current namespace and version throughout the route registration process.
 *
 * @package WpMVC\Routing
 */
class DataBinder
{
    protected string $namespace;

    protected string $version = '';

    /**
     * Set the active namespace for subsequent routes.
     *
     * @param string $namespace The REST namespace.
     * @return void
     */
    public function set_namespace( string $namespace ): void {
        $this->namespace = $namespace;
    }

    /**
     * Set the active version for subsequent routes.
     *
     * @param string $version The API version (e.g., 'v1').
     * @return void
     */
    public function set_version( string $version ): void {
        $this->version = $version;
    }

    /**
     * Retrieve the currently active namespace.
     *
     * @return string
     */
    public function get_namespace(): string {
        return $this->namespace;
    }

    /**
     * Retrieve the currently active version.
     *
     * @return string
     */
    public function get_version(): string {
        return $this->version;
    }
}