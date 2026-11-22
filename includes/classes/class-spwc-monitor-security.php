<?php

defined('ABSPATH') || exit;

class SPWC_Monitor_Security
{

    private $core_checksum_transient = 'spwc_core_checksums';     // stores WP.org checksums cache
    private $local_baseline_option   = 'spwc_core_baseline_hash'; // stores local fallback baseline
    private $cache_ttl               = DAY_IN_SECONDS;            // 24 hours

    public function __construct() {}

    public function get_security_report()
    {

        return [
            'core_integrity' => $this->scan_core_integrity(),
            'file_changes'   => $this->scan_file_changes(),
            'suspicious_php' => $this->scan_suspicious_php(),
        ];
    }

    private function scan_core_integrity()
    {

        $official_checksums = $this->get_wporg_checksums();

        if (!$official_checksums) {
            return [
                'status' => 'fallback-baseline',
                'using_wporg' => false,
                'results' => $this->scan_against_local_baseline()
            ];
        }

        // Compare core files with official WP checksums
        $results = $this->compare_core_files($official_checksums);

        // Save local baseline for future fallback use
        $this->save_local_baseline($official_checksums);

        return [
            'status' => 'verified-against-wporg',
            'using_wporg' => true,
            'results' => $results
        ];
    }


    /**
     * Fetch checksums from WordPress.org (remote API)
     */
    private function get_wporg_checksums()
    {
        $cached = get_transient($this->core_checksum_transient);
        if ($cached) return $cached;

        $version = get_bloginfo('version');
        $locale  = get_locale();

        $url = "https://api.wordpress.org/core/checksums/1.0/?version={$version}&locale={$locale}";

        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) return false;

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['checksums'])) return false;

        set_transient($this->core_checksum_transient, $body['checksums'], $this->cache_ttl);

        return $body['checksums'];
    }


    /**
     * Compare local WP core files to official checksums
     */
    private function compare_core_files($checksums)
    {
        $root = ABSPATH;
        $results = [];

        foreach ($checksums as $file => $expected_hash) {

            $local_file = $root . $file;

            if (!file_exists($local_file)) {
                $results[] = [
                    'file' => $file,
                    'status' => 'missing'
                ];
                continue;
            }

            $actual_hash = md5_file($local_file);

            if ($actual_hash !== $expected_hash) {
                $results[] = [
                    'file' => $file,
                    'status' => 'modified',
                    'expected' => $expected_hash,
                    'actual'   => $actual_hash
                ];
            }
        }

        return $results ?: 'clean';
    }

    private function save_local_baseline($checksums)
    {
        update_option($this->local_baseline_option, $checksums, false);
    }

    private function scan_against_local_baseline()
    {
        $baseline = get_option($this->local_baseline_option, []);

        if (!$baseline) {
            return ['warning' => 'No baseline found. First scan required.'];
        }

        return $this->compare_core_files($baseline);
    }

    public function scan_file_changes()
    {
        $directory = ABSPATH;

        $files = $this->get_all_php_files($directory);
        $hashes = [];

        foreach ($files as $file) {
            $hashes[$file] = md5_file($file);
        }

        return $hashes;
    }

    private function get_all_php_files($dir)
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $files = [];

        foreach ($rii as $file) {
            if ($file->isDir()) continue;

            if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function scan_suspicious_php()
    {
        $directory = ABSPATH;
        $files = $this->get_all_php_files($directory);
        $flags = [];

        $patterns = [
            'eval\(',
            'base64_decode\(',
            'system\(',
            'exec\(',
            'assert\(',
            'passthru\(',
            'shell_exec\(',
            'php:\/\/input',
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            foreach ($patterns as $pattern) {
                if (preg_match("/$pattern/i", $content)) {
                    $flags[] = [
                        'file'  => $file,
                        'match' => $pattern
                    ];
                    break;
                }
            }
        }

        return $flags;
    }
}
