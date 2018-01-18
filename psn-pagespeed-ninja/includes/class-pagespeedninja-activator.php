<?php

class PagespeedNinja_Activator
{
    /**
     * @param $plugin_name string
     * @param $version string
     */
    public static function activate($plugin_name, $version) {
        self::ping(1, $version);

        add_option('pagespeedninja_config', array());
        /** @var array $config */
        $config = get_option('pagespeedninja_config');
        if (!is_array($config)) {
            $config = array();
        }

        // @todo implement version-dependent update (previous version in $config['version'], new one in $version)
        $config['version'] = $version;

        // @todo add timestamp

        if (!isset($config['afterinstall_popup'])) {
            $config['afterinstall_popup'] = '0';
        }

        if (!isset($config['distribmode'])) {
            $config['distribmode'] = 'php';
            if (self::isApache()) {
                // Apache
                $config['distribmode'] = self::suggestedDistribMode();
                // @todo check that it doesn't affect response code (200->50x)
                if (!isset($config['htaccess_gzip'])) {
                    $config['htaccess_gzip'] = '1';
                }
                if (!isset($config['htaccess_caching'])) {
                    $config['htaccess_caching'] = '1';
                }
            }
        }

        if (!isset($config['caching'])) {
            $config['caching'] = !(defined('WP_CACHE') && WP_CACHE);
            if ($config['caching']) {
                $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
                if (in_array('woocommerce/woocommerce.php', $active_plugins, true)) {
                    $config['caching'] = false;
                }
            }
        }

        /** @var array $options */
        $options = file_get_contents(dirname(__FILE__) . '/options.json.php');
        $options = str_replace('\\\'', '\'', $options);
        $options = json_decode($options);
        foreach ($options as $section) {
            if (isset($section->id)) {
                $name = 'psi_' . $section->id;
                if (!isset($config[$name])) {
                    $config[$name] = '1';
                }
            }
            if (isset($section->items)) {
                /** @var array $section->items */
                foreach ($section->items as $item) {
                    if (!isset($config[$item->name])) {
                        $config[$item->name] = (string)$item->default;
                    }
                }
            }
        }

        update_option('pagespeedninja_config', $config);

        // create /s directory
        $staticDir = rtrim(ABSPATH, '/') . $config['staticdir'];
        if (!is_dir($staticDir) && !@mkdir($staticDir, 0755, true) && !is_dir($staticDir)) {
            trigger_error('PageSpeed Ninja: cannot create directory ' . var_export($staticDir, true));
        }

        if (!wp_next_scheduled('pagespeedninja_daily_event')) {
            wp_schedule_event(time(), 'daily', 'pagespeedninja_daily_event');
        }

        // @todo: make amdd-free version without device detection
        include_once dirname(__FILE__) . '/class-pagespeedninja-amdd.php';
        $ress_dir = dirname(dirname(__FILE__)) . '/ress';
        PagespeedNinja_Amdd::updateDatabaseFromFile($ress_dir . '/setup/amdd_data.gz');

        self::createTables();
    }

    /**
     * @param $plugin_name string
     * @param $version string
     */
    public static function deactivate($plugin_name, $version) {
        self::ping(2, $version);

        $file = ABSPATH . 'wp-config.php';
        if (!file_exists($file)) {
            $file = dirname(ABSPATH) . '/wp-config.php';
        }

        wp_clear_scheduled_hook('pagespeedninja_daily_event');

        $config = file_get_contents($file);
        $regex = '/^\s*define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,[^)]+\)\s*;\s*(?:\/\/.*?)?(?>\r\n|\n|\r)/m';
        if (preg_match($regex, $config)) {
            $config = preg_replace($regex, '', $config);
            // @todo check if file is not saved (directory is not writeable, file is not writeable, other write error)
            @file_put_contents($file, $config, LOCK_EX);
        }
    }

    /**
     * @param $status int (1-activate, 2-deactivate)
     * @param $version string
     */
    private static function ping($status, $version)
    {
        global $wp_version;
        // activate/deactivate domain name in getcss service
        $data = array(
            's' => $status,
            'v' => $version,
            'wp' => $wp_version,
            'host' => get_option( 'siteurl' )
        );

        include_once ABSPATH . 'wp-admin/includes/admin.php';

        $tmp_filename = download_url( 'http://pagespeed.ninja/api.php?' . http_build_query($data), 5 );
        if (is_string($tmp_filename)) {
            @unlink($tmp_filename);
        }
    }

    /**
     * @return bool
     */
    private static function isApache()
    {
        return isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false;
    }

    /**
     * @return string
     */
    private static function suggestedDistribMode()
    {
        if (function_exists('apache_get_modules')) {
            // Apache module
            $apache_modules = apache_get_modules();
            if (in_array('mod_rewrite', $apache_modules, true)) {
                if (in_array('mod_mime', $apache_modules, true) && in_array('mod_headers', $apache_modules, true)) {
                    return 'apache';
                }
                return 'rewrite';
            }
        } else {
            // FastCGI
            // @todo rename htaccess.txt to .htaccess and rename back after checking (security)
            $testurl = plugins_url('assets/apachetest/a.htm', dirname(__FILE__));
            $tmp_filename = download_url( $testurl, 5 );
            if (is_string($tmp_filename)) {
                $check = @file_get_contents($tmp_filename);
                @unlink($tmp_filename);
                switch ($check) {
                    case 'B':
                        return 'rewrite';
                    case 'C':
                        return 'apache';
                    default:
                        break;
                }
            }
        }
        return 'php';
    }

    private static function createTables()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'psninja_urls';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
          `url` varchar(4096) NOT NULL,
          `hash` binary(20) NOT NULL,
          `time` datetime NOT NULL,
          `type` tinyint NOT NULL,
          PRIMARY KEY(`hash`)
        ) $charset_collate;";

        $wpdb->query($sql);
    }
}
