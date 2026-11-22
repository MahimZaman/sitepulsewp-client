<?php

/**
 * Plugin Name: SitePulse WP Client
 * Description: The plugin exposes REST API endpoints for our dashboard to fetch data of your wordpress site.
 * Plugin Version: 1.0.0
 * Author: Mahim Zaman (Condixia)
 * Author URI: https://condixia.com
 * Plugin URI: https://github.com/MahimZaman/sitepulsewp-client
 * Text Domain: spwc
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('SPWC_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('SPWC_URL', trailingslashit(plugin_dir_url(__FILE__)));
define('SPWC_BASE', __FILE__);
define('SPWC_VER', time());

require_once SPWC_PATH . 'includes/classes/class-spwc-loader.php';

add_action('plugins_loaded', function () {
    $loader = new SPWC_Loader();
    $loader->init();
});
