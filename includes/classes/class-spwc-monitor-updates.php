<?php

defined('ABSPATH') || exit;

class SPWC_Monitor_Updates
{
    public static function get_plugin_info()
    {
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $update_plugins = get_site_transient('update_plugins');

        $data = [];

        foreach ($all_plugins as $plugin_slug => $plugin_data) {

            $is_active = in_array($plugin_slug, $active_plugins, true);

            $update_available = isset($update_plugins->response[$plugin_slug]);

            $new_version = $update_available
                ? $update_plugins->response[$plugin_slug]->new_version
                : $plugin_data['Version'];

            $data[] = [
                'name'             => $plugin_data['Name'],
                'slug'             => $plugin_slug,
                'version'          => $plugin_data['Version'],
                'new_version'      => $new_version,
                'update_available' => $update_available,
                'is_active'        => $is_active,
                'plugin_uri'       => $plugin_data['PluginURI'],
                'author'           => $plugin_data['Author'],
                'is_wp_org'        => strpos($plugin_data['PluginURI'], 'wordpress.org') !== false,
            ];
        }

        return $data;
    }

    public static function get_theme_info()
    {
        $themes = wp_get_themes();
        $update_themes = get_site_transient('update_themes');
        $active_theme = wp_get_theme();

        $theme_data = [];

        foreach ($themes as $slug => $theme) {

            $theme_info = [
                'name'             => $theme->get('Name'),
                'slug'             => $slug,
                'version'          => $theme->get('Version'),
                'is_active'        => ($active_theme->get_stylesheet() === $slug),
                'is_child_theme'   => $theme->get('Template') !== $slug,
                'theme_uri'        => $theme->get('ThemeURI'),
                'author'           => $theme->get('Author'),
            ];

            if (!empty($update_themes->response[$slug])) {
                $theme_info['update_available'] = true;
                $theme_info['new_version'] = $update_themes->response[$slug]['new_version'];
            } else {
                $theme_info['update_available'] = false;
                $theme_info['new_version'] = $theme_info['version'];
            }

            $theme_data[] = $theme_info;
        }

        return $theme_data;
    }

    public static function get_core_data()
    {
        if (false === function_exists('get_core_updates')) {
            require_once(ABSPATH . 'wp-admin/includes/update.php');
        }
        $core_update = get_core_updates();

        $core_data = [
            'wp_version'        => get_bloginfo('version'),
            'latest_version'    => $core_update[0]->current ?? get_bloginfo('version'),
            'update_available'  => !empty($core_update) && version_compare(get_bloginfo('version'), $core_update[0]->current, '<'),
        ];

        return $core_data;
    }
}
