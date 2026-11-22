<?php
defined('ABSPATH') || exit;

class SPWC_Loader
{
    public function init()
    {
        $this->load_dependencies();
        $this->init_classes();
        $this->hooks();
    }

    private function load_dependencies()
    {
        $class_file_names = ['admin', 'public', 'ajax', 'rest-api', 'shortcodes'];
        foreach ($class_file_names as $file) {
            require_once SPWC_PATH . 'includes/classes/class-spwc-' . $file . '.php';
        }
    }

    private function init_classes()
    {
        $classes = ['SPWC_Admin', 'SPWC_Public', 'SPWC_Ajax', 'SPWC_REST_API', 'SPWC_Shortcodes'];
        foreach ($classes as $class) {
            new $class();
        }
    }

    private function hooks() {}
}
