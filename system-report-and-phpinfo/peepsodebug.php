<?php
/**
 * Plugin Name: Developer Tools
 * Plugin URI: https://peepso.com
 * Description: Analyse PeepSo log, gather environment data, phpinfo and git branch information for debugging purposes
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 3.0.3
 * Copyright: (c) 2015 PeepSo, Inc. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepsodebug
 * Domain Path: /language
 *
 * We are Open Source. You can redistribute and/or modify this software under the terms of the GNU General Public License (version 2 or later)
 * as published by the Free Software Foundation. See the GNU General Public License or the LICENSE file for more details.
 * This software is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 *
 */


class PeepSoDeveloperTools
{
    static $_instance = NULL;

    public $pages= array();

    private function __construct()
    {
        // Catch and display "unexpected output" during plugin activation
        add_action('activated_plugin', array(&$this, 'action_catch_activation_errors'));
        add_action('admin_notices', array(&$this, 'action_display_activation_errors'));

        // Handle the wp-admin pages: menus, contents and exports
        add_action('admin_menu', 			array(&$this, 'action_admin_menu'));
        add_action('admin_init',			array(&$this, 'action_admin_export'));


        add_action( 'wp_ajax_peepso_log', function() {

            if(class_exists('PeepSo') && PeepSo::is_admin()) {

                $path = PeepSo::get_peepso_dir().'peepso.log';
                $trans = 'peepso_log_'.$_GET['hash'];

                if(!strlen($seek = get_transient($trans))) {
                    $seek = 0;
                }

                $handle = fopen($path, 'r');

                if ($seek > 0) {
                    fseek($handle, $seek);
                }

                while (($line = fgets($handle, 4096)) !== false) {
                    echo $line;
                }

                set_transient($trans, ftell($handle), 24*3600);

                exit();
            }
        });

        require_once(plugin_dir_path(__FILE__).'pages'.DIRECTORY_SEPARATOR.'page.php');

        $this->pages_config = array(
            'home',
            'peepsolog',
            'report',
            'phpinfo',
            'unexpectedoutput',
            #'transients',
            'git',
        );

        foreach($this->pages_config as $page) {
            require_once(plugin_dir_path(__FILE__) . 'pages' . DIRECTORY_SEPARATOR . 'page_'.$page.'.php');
            $class='PeepSoDeveloperToolsPage'.$page;
            $this->pages[$page] = new $class();

            if(isset($_GET['page']) && 'peepsodebug_'.$page == $_GET['page']) {
                add_filter('peepsodebug_buttons',array($class,'peepsodebug_buttons'));
            }
        }
    }

    public static function get_instance()
    {
        if (NULL === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    # # # # # # # # # # Unexpected Output Handling # # # # # # # # # #
    /**
     * Catches "unexpected output" during any plugin activation and stores it in options as array
     */
    function action_catch_activation_errors(){
        // Unexpected output will reside in the buffer
        $output = ob_get_contents();

        // If something is caught, stick it in an array
        if($output) {
            $errors = get_user_option('peepsodebug_plugin_activation_error');
            $errors[] = $output;
            update_user_option(get_current_user_id(), 'peepsodebug_plugin_activation_error', $errors);
        }
    }

    /**
     * Prints"unexpexted output" debug to admin_notices
     * @return void
     */
    function action_display_activation_errors() {

        if(isset($_POST['peepsodebug_plugin_activation_error_reset'])) {
            update_user_option(get_current_user_id(), 'peepsodebug_plugin_activation_error', array());
            ?>
            <div class="updated">
                <?php _e('"Unexpected Output" debug purged','peepsodebug');?>
            </div>
            <?php
        }

        $screen = get_current_screen();
        if('plugins' != $screen->base) {
            return;
        }

        if ( count($errors = get_user_option('peepsodebug_plugin_activation_error')) && is_array($errors)) {
            ?>
            <div class="updated">
                <h5>
                    <i class="wp-menu-image dashicons-before dashicons-admin-plugins"></i>
                    <?php _e('Unexpected output during plugin activation','peepsodebug');?>
                    <small>
                        <a href="<?php menu_page_url('peepsodebug_unexpected_output');?>">
                            <?php _e(' by Developer Tools','peepsodebug');?>
                        </a>
                    </small>
                </h5>

                <form method="POST" action="<?php echo $_SERVER['REQUEST_URI'];?>">
                    <input class="button button-secondary" type="submit" name="peepsodebug_plugin_activation_error_reset" value="<?php _e('✕ Clean up','peepsodebug');?>" />
                </form>

                <?php echo implode('<hr>',$errors);?>
            </div>
            <?php
        }
    }

    # # # # # # # # # # Admin Pages Rendering & Export # # # # # # # # # #

    /**
     * Builds wp-admin menus and hooks handler classes
     * @return void
     */
    public function action_admin_menu() {

        add_menu_page('Developer Tools', $this->pages['home']->title, 'manage_options', 'peepsodebug_home', array( $this->pages['home'], 'page'));

        foreach($this->pages_config as $page) {
            if('home'!=$page) {
                $class = $this->pages[$page];
                add_submenu_page('peepsodebug_home', $class->title, $class->title, 'manage_options', 'peepsodebug_'.$page, array($class, 'page'));
            }
        }
    }

    /**
     * Handles the file export
     * @return void
     */
    public function action_admin_export()
    {
        // Check if the export is happening and the handler class is loaded
        if(!isset($_POST['system_report_export']) || !key_exists($name = $_POST['export_content'], $this->pages)) {
            return;
        }

        // The handler class is already initialized
        $class = $this->pages[$name];

        // Output the page data as a file
        $this->file_export($class->page_data(), $name, $class->file_mime, $class->file_extension);
    }

    private function file_export($content, $file_name, $file_mime, $file_extension)
    {
        $file = sanitize_title_with_dashes(get_bloginfo('name') . ' ' . $file_name, '', 'save') . '.' . $file_extension;
        nocache_headers();
        header("Content-type: $file_mime");
        header('Content-Disposition: attachment; filename="' . $file . '"');
        exit($content);
    }
    # # # # # # # # # # Utils # # # # # # # # # #

    /**
     * Returns the assets directory path
     * @return string
     */
    public static function assets_path()
    {
        return plugin_dir_url(__FILE__) . 'assets'.DIRECTORY_SEPARATOR;
    }

    public static function num_convt( $v ) {
        $l   = substr( $v, -1 );
        $ret = substr( $v, 0, -1 );

        switch ( strtoupper( $l ) ) {
            case 'P': // fall-through
            case 'T': // fall-through
            case 'G': // fall-through
            case 'M': // fall-through
            case 'K': // fall-through
                $ret *= 1024;
                break;
            default:
                break;
        }

        return $ret;
    }
}

$PeepSoDevTools = PeepSoDeveloperTools::get_instance();

// EOF