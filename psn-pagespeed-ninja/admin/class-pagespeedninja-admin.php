<?php

class PagespeedNinja_Admin
{
    const TAB_BASIC = 1;
    const TAB_ADVANCED = 2;

    /** @var string The ID of this plugin. */
    private $plugin_name;

    /** @var string The current version of this plugin. */
    private $version;

    /** @var array */
    protected $messages = array();

    /** @var int */
    protected $tab = 0;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function admin_init()
    {
        register_setting('pagespeedninja_config', 'pagespeedninja_config');
        wp_register_style('pagespeedninja_style', plugins_url('/assets/css/pagespeedninja.css', dirname(__FILE__)),
            array(), $this->version);
        wp_register_script('pagespeedninja_areyousure_script', plugins_url('/assets/js/jquery.are-you-sure.js', dirname(__FILE__)),
            array('jquery'), $this->version);
        wp_register_script('pagespeedninja_atfbundle_script', plugins_url('/assets/js/atfbundle.js', dirname(__FILE__)),
            array(), $this->version);
        wp_register_script('pagespeedninja_script', plugins_url('/assets/js/pagespeedninja.js', dirname(__FILE__)),
            array('jquery', 'pagespeedninja_areyousure_script', 'pagespeedninja_atfbundle_script'), $this->version);
    }

    public function admin_menu()
    {
        global $plugin_page;
        if ($plugin_page === $this->plugin_name) {
            $tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic';
            switch($tab){
                case 'advanced':
                    $this->tab = self::TAB_ADVANCED;
                    break;
                case 'basic':
                    $this->tab = self::TAB_BASIC;
                    break;
            }
        }

        $page_title = __('PageSpeed Ninja Options');
        if ($this->tab === self::TAB_ADVANCED) {
            $page_title = __('PageSpeed Ninja Advanced Options');
        }

        $hook = add_options_page($page_title, __('PageSpeed Ninja'), 'manage_options',
            $this->plugin_name, array($this, 'pagespeedninja_options'));

        add_action('admin_print_styles-' . $hook, array($this, 'admin_styles'));
        add_action('admin_print_scripts-' . $hook, array($this, 'admin_scripts'));
    }

    public function admin_head()
    {
        global $parent_file, $plugin_page;
        if (!isset($parent_file, $plugin_page) || $parent_file !== 'options-general.php' || $plugin_page !== $this->plugin_name) {
            $this->tab = 0;
            return;
        }

        add_action('admin_notices', array($this, 'admin_notices'));

        $config = get_option('pagespeedninja_config');

        if (defined('WP_CACHE') && WP_CACHE) {
            // Check caching-related conflicts
            $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

            if (!($config['psi_MainResourceServerResponseTime'] && $config['caching'])) {
                $this->enqueueMessage(__('Note that some PageSpeed Ninja features ("Scale large images" and "Remove IE conditionals") may not be compatible with caching plugin'));
            }

            if ($config['caching'] && in_array('woocommerce/woocommerce.php', $active_plugins, true)) {
                $this->enqueueMessage(__('PageSpeed Ninja\'s advanced caching is not compatible with WooCommerce plugin'));
            }
        }

        $logFilename = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'error_log.php';
        if (is_file($logFilename)) {
            $logSize = filesize($logFilename);
            if ($logSize > 10 * 1024 * 1024) {
                $logSize = number_format($logSize / (1024 * 1024), 1, '.', '');
                $this->enqueueMessage(sprintf(__('Size of %1$s file is %2$sMb.'), $logFilename, $logSize));
            }
        }

    }

    public function admin_styles()
    {
        wp_enqueue_style('pagespeedninja_google_fonts', '//fonts.googleapis.com/css?family=Montserrat:300,400,600', array(), null);
        wp_enqueue_style('pagespeedninja_style');
    }

    public function admin_scripts()
    {
        $cache_dir = dirname(dirname(__FILE__)) . '/cache';
        $cache_timestamp = @filemtime($cache_dir . '/pagecache.stamp');

        wp_enqueue_script('pagespeedninja_atfbundle_script');
        wp_enqueue_script('pagespeedninja_areyousure_script');
        wp_enqueue_script('pagespeedninja_script');
        wp_add_inline_script('pagespeedninja_script', 'var psn_cache_timestamp=' . (int)$cache_timestamp . ';');
        add_thickbox();
    }

    public function admin_plugin_settings_link($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=' . $this->plugin_name)) . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    public function admin_plugin_meta_links($links, $file)
    {
        $plugin_filename = '/' . $this->plugin_name . '.php';
        if (substr_compare($file, $plugin_filename, -strlen($plugin_filename)) === 0) {
            return array_merge(
                    $links,
                    array( sprintf( '<a target="_blank" href="https://wordpress.org/support/plugin/psn-pagespeed-ninja"><span class="dashicons dashicons-editor-help"></span> %s</a>',  __('Support') ) ),
                    array( sprintf( '<a target="_blank" href="https://www.facebook.com/groups/240066356467297/"><span class="dashicons dashicons-facebook"></span> %s</a>',  __('Get tips') ) ),
                    array( sprintf( '<a target="_blank" href="https://wordpress.org/support/plugin/psn-pagespeed-ninja/reviews/#new-post"><span class="dashicons dashicons-heart"></span> %s</a>',  __('Review') ) )
                );
        }
        return $links;
    }

    public function pagespeedninja_options()
    {
        $config = get_option('pagespeedninja_config');

        if ($config['afterinstall_popup'] !== '1') {
            include_once dirname(__FILE__) . '/partials/pagespeedninja-admin-popup.php';
            return;
        }

        switch ($this->tab) {
            case self::TAB_BASIC:
                include_once dirname(__FILE__) . '/partials/pagespeedninja-admin-basic.php';
                break;
            case self::TAB_ADVANCED:
                include_once dirname(__FILE__) . '/partials/pagespeedninja-admin-advanced.php';
                break;
        }
    }

    public function admin_notices()
    {
        foreach ($this->messages as $message) {
            echo '<div class="notice notice-alt notice-warning is-dismissible">' .
                '<p>' . esc_html($message) . '</p>' .
                '</div>';
        }
        if ($this->tab === self::TAB_BASIC) {
            echo '<div class="notice notice-alt notice-info is-dismissible hidden" id="pagespeedninja_atfcss_notice">' .
                '<p>' . esc_html(__('Above-the-fold CSS styles have been generated. Save changes to apply. You can view and edit generated styles in Advanced settings page.')) . '</p>' .
                '</div>';
        }
    }

    /**
     * @param $message string
     */
    protected function enqueueMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $title
     * @param string $tooltip
     */
    public function title($title, $tooltip = '')
    {
        echo '<div class="title"';
        if ($tooltip !== '') {
            echo ' data-tooltip="' . esc_attr($tooltip) . '"';
        }
        echo '>' . $title . '</div>';
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $class
     * @param string $enabledValue
     * @param string $disabledValue
     * @param bool $disabled
     */
    public function checkbox($id, $name, $value = '0', $class = '', $enabledValue = '1', $disabledValue = '0', $disabled = false)
    {
        echo "<input type=\"hidden\" name=\"$name\" value=\"$disabledValue\" />"
            . "<input type=\"checkbox\" id=\"$id\" name=\"$name\" value=\"$enabledValue\" "
            . ($value === $enabledValue ? 'checked="checked" ' : '')
            . ($class === '' ? '' : "class=\"$class\" ")
            . ($disabled ? ' disabled="disabled"' : '')
            . '/>'
            . "<label for=\"$id\"></label>";
    }

    /**
     * @param array $items
     * @return array
     */
    public function toList($items)
    {
        $result = array();
        foreach ($items as $item) {
            // a trick to get name and value of first object property
            // $item = (array)$item; // hhvm
            $result[key($item)] = current($item);
        }
        return $result;
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param array $values
     * @param string $class
     */
    public function select($id, $name, $value, $values, $class = '')
    {
        foreach ($values as $key => $title) {
            echo '<label>'
                . "<input type=\"radio\" id=\"$id\" name=\"$name\" value=\"" . esc_attr($key) . '" '
                . ($value === (string)$key ? 'checked="checked" ' : '')
                . ($class === '' ? '' : "class=\"$class\" ")
                . '/>'
                . esc_html($title)
                . '</label>';
        }
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $class
     * @param int $rows
     * @param int $cols
     */
    public function textarea($id, $name, $value, $class = '', $rows = 5, $cols = 80)
    {
        echo "<textarea id=\"$id\" name=\"$name\" rows=\"$rows\" cols=\"$cols\""
            . ($class === '' ? '' : "class=\"$class\" ")
            . '>'
            . esc_textarea($value)
            . '</textarea>';
    }

    /**
     * @param string $id
     * @param string $name
     * @param int $value
     * @param string $class
     */
    public function number($id, $name, $value, $class = '')
    {
        echo "<input type=\"number\" id=\"$id\" name=\"$name\" value=\"$value\" "
            . ($class === '' ? '' : "class=\"$class\" ")
            . '/>';
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param string $class
     */
    public function text($id, $name, $value, $class = '')
    {
        echo "<input type=\"text\" id=\"$id\" name=\"$name\" value=\"" . esc_attr($value) . '" '
            . ($class === '' ? '' : "class=\"$class\" ")
            . '/>';
    }

    /**
     * @param array $config
     * @param string $param
     */
    public function hidden($config, $param)
    {
        $id = 'pagespeedninja_config_' . $param;
        $name = 'pagespeedninja_config[' . $param . ']';
        if (!isset($config[$param])) {
            $this->enqueueMessage(sprintf(__('Configuration field doesn\'t exist: %s'), $param));
        }
        $value = $config[$param];
        echo "<input type=\"hidden\" id=\"$id\" name=\"$name\" value=\"" . esc_attr($value) . '" value="' . esc_attr($value) . '" />';
    }

    /**
     * @param string $type
     * @param string $param
     * @param array $config
     * @param stdClass $item
     */
    public function render($type, $param, $config, $item = null)
    {
        if (!isset($config[$param])) {
            // @todo enqueueMessage doesn't work here (admin_notices was triggered earlier)
            $this->enqueueMessage(sprintf(__('Configuration field doesn\'t exist: %s'), $param));
            return;
        }
        $id = 'pagespeedninja_config_' . $param;
        $name = 'pagespeedninja_config[' . $param . ']';
        $value = $config[$param];

        switch ($type) {
            case 'checkbox':
                ?><div class="field"><?php
                $this->checkbox($id, $name, $value);
                ?></div><?php
                break;
            case 'cachingcheckbox':
                ?><div class="field"><?php
                $disabled = ($value === '0' && defined('WP_CACHE') && WP_CACHE);
                $this->checkbox($id, $name, $value, '', '1', '0', $disabled);
                ?></div><?php
                break;
            case 'select':
                ?><div class="field"><?php
                $this->select($id, $name, $value, $this->toList($item->values), isset($item->class) ? $item->class : null);
                ?></div><?php
                break;
            case 'text':
                ?><div class="field"><?php
                $this->text($id, $name, $value);
                ?></div><?php
                break;
            case 'number':
                ?><div class="field"><?php
                $this->number($id, $name, (int)$value, 'small-text');
                if (isset($item->units)) {
                    echo $item->units;
                }
                ?></div><?php
                break;
            case 'textarea':
                ?><div class="field fullline"><?php
                $this->textarea($id, $name, $value);
                ?></div><?php
                break;
            case 'errorlogging':
                ?><div class="field"><?php
                $this->checkbox($id, $name, $value);
                ?></div><?php
                $logFilename = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'error_log.php';
                ?><div class="field fullline"><?php
                echo __('Log file:') . " <span class=\"filename\">$logFilename</span> " . (is_file($logFilename) ? sprintf(__('(%d bytes)'), filesize($logFilename)) : '');
                ?></div><?php
                break;
            case 'abovethefoldstyle':
                ?><div class="field fullline"><?php
                $this->textarea($id, $name, $value, 'large-text');
                ?><br><a href="#" class="button"
                         onclick="autoGenerateATF('pagespeedninja_config_<?php echo $item->name; ?>', document.getElementById('pagespeedninja_config_css_abovethefoldlocal').checked);return false;"><?php
                _e('&#x2191; Generate Above-the-fold CSS styles');
                ?></a></div><?php
                break;
            case 'imgdriver':
                ?><div class="field"><?php
                $values = $this->toList($item->values);
                foreach ($values as $key => $title) {
                    $disabled = false;
                    if ($key === 'imagick' && !extension_loaded('imagick')) {
                        $disabled = true;
                    }
                    echo '<label>'
                        . "<input type=\"radio\" id=\"$id\" name=\"$name\" value=\"" . esc_attr($key) . '" '
                        . ($value === (string)$key ? 'checked="checked" ' : '')
                        . ($disabled ? 'disabled="disabled" ' : '')
                        . '/>'
                        . esc_html($title)
                        . '</label>';
                }
                ?></div><?php
                break;
            case 'clear_cache':
                ?><div class="field"><input type="button" id="clear_cache" value="Clear Cache"/></div><?php
                break;
            case 'drop_database':
                ?><div class="field"><input type="button" id="clear_db_cache" value="Clear Database Cache"/></div><?php
                break;
            case 'exclude_js':
                ?><div class="field fullline"><?php
                $data = $this->getUrlsList();
                if (count($data) === 0) {
                    ?><i>no data</i><?php
                } else {
                    $regex = null;
                    $list = array();
                    if (!empty($config['js_excludelist'])) {
                        $list = explode("\n", trim($config['js_excludelist']));
                        foreach ($list as $i => $line) {
                            $line = trim($line);
                            if ($line === '') {
                                unset($list[$i]);
                            } else {
                                $list[$i] = $line;
                            }
                        }
                        if (count($list)) {
                            $regex = '/(?:' . implode('|', array_map('preg_quote', $list, array_fill(0, count($list), '/'))) . ')/';
                        }
                    }
                    ?><div class="excludelist"><table id="psn_excludejs"><tr><th><?php _e('URL'); ?></th><th><?php _e('Date added'); ?></th><th><?php _e('Excluded'); ?></th></tr><?php
                    foreach ($data as $row) {
                        if ($row->type == 1) {
                            $checked = ($regex === null) ? false : preg_match($regex, $row->url);
                            $disabled = $checked && !in_array($row->url, $list, true);
                            echo '<tr>'
                                . '<td>' . esc_html($row->url) . '</td>'
                                . '<td>' . $row->time . '</td>'
                                . '<td><input type="checkbox"' . ($checked ? ' checked="checked"' : '') . ($disabled ? ' disabled="disabled"' : '') . '/></td>'
                                . '</tr>';
                        }
                    }
                        ?></table></div><?php
                }
                ?></div><?php
                break;
            case 'exclude_css':
                ?><div class="field fullline"><?php
                $data = $this->getUrlsList();
                if (count($data) === 0) {
                    ?><i>no data</i><?php
                } else {
                    $regex = null;
                    $list = array();
                    if (!empty($config['css_excludelist'])) {
                        $list = explode("\n", trim($config['css_excludelist']));
                        foreach ($list as $i => $line) {
                            $line = trim($line);
                            if ($line === '') {
                                unset($list[$i]);
                            } else {
                                $list[$i] = $line;
                            }
                        }
                        if (count($list)) {
                            $regex = '/(?:' . implode('|', array_map('preg_quote', $list, array_fill(0, count($list), '/'))) . ')/';
                        }
                    }
                    ?><div class="excludelist"><table id="psn_excludecss"><tr><th><?php _e('URL'); ?></th><th><?php _e('Date added'); ?></th><th><?php _e('Excluded'); ?></th></tr><?php
                    foreach ($data as $row) {
                        if ($row->type == 2) {
                            $checked = ($regex === null) ? false : preg_match($regex, $row->url);
                            $disabled = $checked && !in_array($row->url, $list, true);
                            echo '<tr>'
                                . '<td>' . esc_html($row->url) . '</td>'
                                . '<td>' . $row->time . '</td>'
                                . '<td><input type="checkbox"' . ($checked ? ' checked="checked"' : '') . ($disabled ? ' disabled="disabled"' : '') . '/></td>'
                                . '</tr>';
                        }
                    }
                    ?></table></div><?php
                }
                ?></div><?php
                break;
            default:
                trigger_error('PageSpeed Ninja: unknown type (' . var_export($item->type, true) . ') in config file');
        }
    }

    protected function getUrlsList()
    {
        static $urls;
        if ($urls === null) {
            global $wpdb;
            $sql = "SELECT * FROM `{$wpdb->prefix}psninja_urls` ORDER BY `url`";
            $urls = $wpdb->get_results($sql);
        }
        return $urls;
    }

    /**
     * @param array $newConfig
     * @param array $oldConfig
     * @return array
     */
    public function validate_config($newConfig, $oldConfig)
    {
        // copy other subset of settings
        foreach ($oldConfig as $name => $value) {
            if (!isset($newConfig[$name])) {
                $newConfig[$name] = $value;
            }
        }
        if (trim($newConfig['staticdir'], '/') === '') {
            $newConfig['staticdir'] = '/s';
        }
        return $newConfig;
    }

    /**
     * @param array $oldConfig
     * @param array $newConfig
     */
    public function update_config($oldConfig, $newConfig)
    {
        $pluginDir = dirname(dirname(__FILE__));
        $srcDir = $pluginDir . '/assets/sample';
        $homeDir = rtrim(ABSPATH, '/');

        $destDir = $homeDir . $newConfig['staticdir'];
        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            trigger_error('PageSpeed Ninja: cannot create directory ' . var_export($destDir, true));
        }

        $staticHtaccess = $destDir . '/.htaccess';
        switch ($newConfig['distribmode']) {
            case 'direct':
                if (file_exists($staticHtaccess)) {
                    @unlink($staticHtaccess);
                }
                break;
            case 'apache':
                copy($srcDir . '/sample_apache.htaccess', $staticHtaccess);
                break;
            case 'rewrite':
                copy($srcDir . '/sample_php.htaccess', $staticHtaccess);
                $this->copyGetPhp($srcDir, $destDir);
                break;
            case 'php':
                if (file_exists($staticHtaccess)) {
                    @unlink($staticHtaccess);
                }
                $this->copyGetPhp($srcDir, $destDir);
                break;
            default:
                trigger_error('PageSpeed Ninja: unknown distribmode value ' . var_export($newConfig['distribmode'], true));
        }

        $caching = defined('WP_CACHE') && WP_CACHE;
        if ($newConfig['psi_MainResourceServerResponseTime'] && $newConfig['caching']) {
            $cache_dir = $pluginDir . '/cache';
            if (!is_dir($cache_dir)) {
                @mkdir($cache_dir);
            }

            $deviceDependent = ($newConfig['psi_MinifyHTML'] && $newConfig['html_removeiecond'])
                || ($newConfig['psi_OptimizeImages'] && ($newConfig['img_scaletype'] !== 'none'))
                || ($newConfig['psi_MinifyCss'] && in_array($newConfig['css_di_cssMinify'], array('ress', 'both'), true));

            $advanced_cache =
                "<?php\n" .
                "/* PageSpeed Ninja Caching */\n" .
                "defined('ABSPATH') || die();\n" .
                "define('PAGESPEEDNINJA_CACHE_DIR', '$cache_dir');\n" .
                "define('PAGESPEEDNINJA_CACHE_PLUGIN', '$pluginDir');\n" .
                "define('PAGESPEEDNINJA_CACHE_RESSDIR', '$pluginDir/ress');\n" .
                "define('PAGESPEEDNINJA_CACHE_DEVICEDEPENDENT', " . ($deviceDependent ? 'true' : 'false') . ");\n" .
                "define('PAGESPEEDNINJA_CACHE_TTL', " . ($newConfig['caching_ttl'] * 60) . ");\n" .
                "include '$pluginDir/public/advanced-cache.php';\n";

            // @todo check if file is not saved (directory is not writeable, file exists and is not writeable, other write error)
            file_put_contents(WP_CONTENT_DIR . '/advanced-cache.php', $advanced_cache, LOCK_EX);
            if (!$caching) {
                $this->update_WP_CACHE(true);
            }
        } else {
            if ($caching) {
                $this->update_WP_CACHE(false);
            }
        }

        $htaccess = '';
        if ($newConfig['psi_EnableGzipCompression'] && $newConfig['htaccess_gzip']) {
            $htaccess .= file_get_contents($pluginDir . '/assets/sample/gzip.htaccess');
        }
        if ($newConfig['psi_LeverageBrowserCaching'] && $newConfig['htaccess_caching']) {
            $htaccess .= file_get_contents($pluginDir . '/assets/sample/cache.htaccess');
        }

        $marker = 'Page Speed Ninja';
        $dirs = array(
                'wp-includes',
                'wp-content',
                'uploads'
            );
        foreach ($dirs as $dir) {
            // @todo remove "empty" (i.e. marker-only) .htaccess files
            if (is_dir($homeDir . '/' . $dir)) {
                insert_with_markers($homeDir . '/' . $dir . '/.htaccess', $marker, $htaccess);
            }
        }
    }

    /**
     * @param string $srcDir
     * @param string $destDir
     */
    private function copyGetPhp($srcDir, $destDir)
    {
        $plugin_dir = basename(dirname(dirname(__FILE__)));

        $content = file_get_contents($srcDir . '/f.php.sample');
        $content = preg_replace(
            '/^\$root\s*=.*?$/m',
            '$root = \'' . ABSPATH . '\';',
            $content
        );
        $content = preg_replace(
            '/^\$plugin_root\s*=.*?$/m',
            '$plugin_root = $root . \'/wp-content/plugins/' . $plugin_dir . '\';',
            $content
        );
        file_put_contents($destDir . '/f.php', $content, LOCK_EX);
    }

    /**
     * @param bool $enabled
     */
    private function update_WP_CACHE($enabled)
    {
        $file = ABSPATH . 'wp-config.php';
        if (!file_exists($file)) {
            $file = dirname(ABSPATH) . '/wp-config.php';
        }

        $config = file_get_contents($file);
        $config = preg_replace('/^\s*define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,[^)]+\)\s*;\s*(?:\/\/.*?)?(?>\r\n|\n|\r)/m', '', $config);
        if ($enabled) {
            $config = preg_replace('/^<\?php\b/', "<?php\ndefine('WP_CACHE', true);", $config);
        }
        // @todo check if file is not saved (directory is not writeable, file is not writeable, other write error)
        @file_put_contents($file, $config, LOCK_EX);
    }

    public function clear_cache()
    {
        /** @var array $options */
        $options = get_option('pagespeedninja_config');

        if (!class_exists('Ressio', false)) {
            include_once dirname(dirname(__FILE__)) . '/ress/ressio.php';
        }
        Ressio::registerAutoloading(true);

        $di = new Ressio_DI();
        $di->config = new stdClass;
        $di->config->cachedir = RESSIO_PATH . '/cache';
        $di->config->cachettl = 0;
        $di->config->webrootpath = rtrim(ABSPATH, '/');
        $di->config->staticdir = $options['staticdir'];
        $di->filesystem = new Ressio_Filesystem_Native();
        $di->filelock = new Ressio_FileLock_flock();
        $plugin = new Ressio_Plugin_FileCacheCleaner($di, null);

        wp_die();
        exit;
    }

    public function clear_db_cache()
    {
        include_once dirname(dirname(__FILE__)) . '/includes/class-pagespeedninja-amdd.php';
        PagespeedNinja_Amdd::clearCache();

        wp_die();
        exit;
    }

    /**
     */
    public function ajax_key()
    {
        $config = $_POST['pagespeedninja_config'];

        $json = json_encode($config);
        $key = sha1($json . NONCE_SALT);

        // @todo store key-value pair to Ressio's cache directory
        file_put_contents(dirname(__FILE__) . '/sessions/' . $key, $json, LOCK_EX);

        echo $key;
        wp_die();
    }
}
