<?php

defined('ABSPATH') || exit;

class SPWC_Shortcodes
{
    public function __construct()
    {
        add_shortcode('spwc_test', [$this, 'spwc_test_callback']);
    }

    public function spwc_test_callback()
    {
        if (false === function_exists('get_core_updates')) {
            require_once(ABSPATH . 'wp-admin/includes/update.php');
        }
        global $spwc_security_scanner;
        echo '<pre>';
        print_r($spwc_security_scanner);
        echo '</pre>';
    }
}
