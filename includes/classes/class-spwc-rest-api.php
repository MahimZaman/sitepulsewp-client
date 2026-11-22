<?php

defined('ABSPATH') || exit;

class SPWC_REST_API
{
    public function __construct()
    {
        $this->load_dependencies();
        add_action('rest_api_init', [$this, 'spwc_register_rest_api_routes']);
    }

    private function load_dependencies()
    {
        $dependencies = ['monitor-updates', 'monitor-security'];

        foreach ($dependencies as $dependency) {
            require_once SPWC_PATH . 'includes/classes/class-spwc-' . $dependency . '.php';
        }
    }

    public function spwc_register_rest_api_routes()
    {
        register_rest_route('spwc/v1', '/uptime', [
            'methods'   => 'GET',
            'callback'  => [$this, 'spwc_site_uptime'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('spwc/v1', '/updates', [
            'methods'   => 'GET',
            'callback'  => [$this, 'spwc_update_data'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('spwc/v1', '/security-scan', [
            'methods' => 'GET',
            'callback' => [$this, 'spwc_security_scan'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function spwc_security_scan(WP_REST_Request $request)
    {
        $scanner = new SPWC_Monitor_Security();
        $data = $scanner->get_security_report();

        return rest_ensure_response($data);
    }

    public function spwc_site_uptime(WP_REST_Request $request)
    {
        $response = [
            'status'        => 'online',
            'timestamp'     => time(),
            'site'          => get_site_url(),
            'sitepulse_ver' => defined('SPWC_VER') ? SPWC_VER : '1.0.0',
            'wp_version'    => get_bloginfo('version'),
            'php_version'   => phpversion(),
        ];

        return new WP_REST_Response($response, 200);
    }

    public function spwc_update_data(WP_REST_Request $request)
    {
        $response = [
            'core'        => SPWC_Monitor_Updates::get_core_data(),
            'plugins'     => SPWC_Monitor_Updates::get_plugin_info(),
            'themes'      => SPWC_Monitor_Updates::get_theme_info(),
        ];

        return new WP_REST_Response($response, 200);
    }
}
