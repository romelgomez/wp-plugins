<?php
/*
Plugin Name: MotoPress Content Editor Lite
Plugin URI: https://motopress.com/plugins/content-editor/
Description: Drag and drop frontend page builder for any theme.
Version: 2.5.1
Author: MotoPress
Author URI: https://motopress.com/
Text Domain: motopress-content-editor-lite
Domain Path: /lang
License: GPLv2 or later
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if(!is_plugin_active('motopress-content-editor/motopress-content-editor.php')) {
/*
 * Allow symlinked plugin for wordpress < 3.9
 */
global $wp_version;
if (version_compare($wp_version, '3.9', '<') && isset($network_plugin)) {
	$motopress_plugin_file = $network_plugin;
} else {
	$motopress_plugin_file = __FILE__;
}
$motopress_plugin_dir_path = plugin_dir_path($motopress_plugin_file);

require_once $motopress_plugin_dir_path . 'includes/Requirements.php';
require_once $motopress_plugin_dir_path . 'includes/settings.php';
require_once $motopress_plugin_dir_path . 'includes/compatibility.php';
require_once $motopress_plugin_dir_path . 'includes/functions.php';
require_once $motopress_plugin_dir_path . 'includes/MPCEUtils.php';
require_once $motopress_plugin_dir_path . 'includes/ce/MPCECustomStyleManager.php';
require_once $motopress_plugin_dir_path . 'includes/ce/shortcodes/post_grid/MPCEShortcodePostsGrid.php';
require_once $motopress_plugin_dir_path . 'includes/ce/shortcode/ShortcodeCommon.php';
require_once $motopress_plugin_dir_path . 'includes/ce/MPCEContentManager.php';

add_action('wp_head', 'motopressCEWpHead', 7);
//add_action('wp_enqueue_scripts', 'motopressCEWpHead');

add_action('plugins_loaded', 'motopressCELoadTextdomain');
function motopressCELoadTextdomain() {
	global $motopressCESettings;
	load_plugin_textdomain('motopress-content-editor-lite', false, $motopressCESettings['plugin_symlink_dir_name'] . '/lang/');
}

// Custom CSS [if exsists]
add_action('wp_head', 'motopressCECustomCSS', 999);
function motopressCECustomCSS(){
    global $motopressCESettings;
    if (!$motopressCESettings['wp_upload_dir_error']) {
        if ( file_exists($motopressCESettings['custom_css_file_path']) ) {
            echo "\n<!-- MotoPress Custom CSS Start -->\n<style type=\"text/css\">\n@import url('".$motopressCESettings['custom_css_file_url']."?".filemtime($motopressCESettings['custom_css_file_path'])."');\n</style>\n<!-- MotoPress Custom CSS End -->\n";
        }
    }
}
// Custom CSS END

function motopressCEGetWPScriptVer($script) {
    global $wp_version;
    $ver = false;
    $path = ABSPATH . WPINC;
    $versionPattern = '/v((\d+\.{1}){1}(\d+){1}(\.{1}\d+)?)/is';
    switch ($script) {
        case 'jQuery':
            $path .= '/js/jquery/jquery.js';
            break;
     case 'jQueryUI':
        if (version_compare($wp_version, '4.1', '<')) {
            $path .= '/js/jquery/ui/jquery.ui.core.min.js';
        } else {
            $path .= '/js/jquery/ui/core.min.js';
            $versionPattern = '/jQuery UI Core ((\d+\.{1}){1}(\d+){1}(\.{1}\d+)?)/is';
        }
        break;
    }

    if (is_file($path)) {
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content) {
                preg_match($versionPattern, $content, $matches);
                if (!empty($matches[1])) {
                    $ver = $matches[1];
                }
            }
        }
    }
    return $ver;
}

function motopressCEWpHead() {
    global $motopressCESettings;

	$suffix = $motopressCESettings['script_suffix'];
	$pUrl = $motopressCESettings['plugin_dir_url'];
    $pVer = $motopressCESettings['plugin_version'];
	$vendorInFooter = $frontInFooter = $editorInFooter = $noConflictInFooter = true;

    wp_register_style('mpce-bootstrap-grid', $pUrl . 'bootstrap/bootstrap-grid.min.css', array(), $pVer);

//    wp_register_style('mpce-bootstrap-responsive-utility', $pUrl . 'bootstrap-responsive-utility.min.css', array(), $pVer);
//    wp_enqueue_style('mpce-bootstrap-responsive-utility');

    wp_register_style('mpce-theme', $pUrl . 'includes/css/theme' . $suffix . '.css', array(), $pVer);

    /*
    if (
        ($post && !empty($post->post_content) && has_shortcode($post->post_content, 'mp_row')) ||
        MPCEShortcode::isContentEditor()
    ) {
        wp_enqueue_style('mpce-bootstrap-grid');
        wp_enqueue_style('mpce-theme');
    }
    */

    if (!wp_script_is('jquery')) {
        wp_enqueue_script('jquery');
    }

    wp_register_style('mpce-flexslider', $pUrl . 'vendors/flexslider/flexslider.min.css', array(), $pVer);
    wp_register_script('mpce-flexslider', $pUrl . 'vendors/flexslider/jquery.flexslider-min.js', array('jquery'), $pVer, $vendorInFooter);
    wp_register_style('mpce-font-awesome', $pUrl . 'fonts/font-awesome/css/font-awesome.min.css', array(), '4.7.0');

	// Unused
    /*wp_register_script('mpce-theme', $pUrl . 'includes/js/theme.js', array('jquery'), $pVer);
    wp_enqueue_script('mpce-theme');*/

    wp_register_script('google-charts-api', 'https://www.google.com/jsapi', array(), null, $vendorInFooter);
//    wp_register_script('mp-google-charts', $pUrl . 'includes/js/mp-google-charts' . $suffix . '.js', array('jquery','google-charts-api'), $pVer); // old front
//    wp_register_script('mp-social-share', $pUrl . 'includes/js/mp-social-share' . $suffix . '.js' , array('jquery'), $pVer, $vendorInFooter); // old front
//    wp_register_script('mp-row-fullwidth', $pUrl . 'includes/js/mp-row-fullwidth' . $suffix . '.js', array('jquery'), $pVer); // old front
//    wp_register_script('mp-video-background', $pUrl . 'includes/js/mp-video-background' . $suffix . '.js', array('jquery'), $pVer); // old front
    wp_register_script('mp-youtube-api', '//www.youtube.com/player_api', array(), null, $vendorInFooter);
    wp_register_script('stellar', $pUrl . 'vendors/stellar/jquery.stellar.min.js', array('jquery'), $pVer, $vendorInFooter);
//    wp_register_script('mp-row-parallax', $pUrl . 'includes/js/mp-row-parallax' . $suffix . '.js', array('jquery', 'stellar'), $pVer); // old front
    wp_register_script('mpce-magnific-popup', $pUrl . 'vendors/magnific-popup/jquery.magnific-popup.min.js', array('jquery'), $pVer, $vendorInFooter);
//    wp_register_script('mp-lightbox', $pUrl . 'includes/js/mp-lightbox' . $suffix . '.js', array('jquery', 'mpce-magnific-popup'), $pVer); // old front
	wp_register_script('mp-js-cookie', $pUrl . 'vendors/js-cookie/js.cookie.min.js', array(), $pVer);
//    wp_register_script('mp-grid-gallery', $pUrl . 'includes/js/mp-grid-gallery' . $suffix . '.js', array('jquery'), $pVer); // old front

    wp_register_script('mpce-countdown-plugin', $pUrl . 'vendors/keith-wood-countdown-timer/js/jquery.plugin_countdown.min.js', array('jquery'), $pVer, $vendorInFooter);
    wp_register_script('mpce-countdown-timer', $pUrl . 'vendors/keith-wood-countdown-timer/js/jquery.countdown.min.js', array('jquery'), $pVer, $vendorInFooter);
	// Unused
	//wp_register_style('mpce-countdown-timer', $pUrl . 'vendors/keith-wood-countdown-timer/css/countdown.min.css', null, $pVer);
	
	// add language file
	$mp_keith_wood_countdown_timer_languages = array("sq"=>"sq","ar"=>"ar","hy"=>"hy","bn-BD"=>"bn","bs-BA"=>"bs","bg-BG"=>"bg","ca"=>"ca","hr"=>"hr","cs-CZ"=>"cs","da-DK"=>"da","nl-NL"=>"nl","et"=>"et","fo"=>"fo","fi"=>"fi","gl-ES"=>"gl","de-DE"=>"de","el"=>"el","gu"=>"gu","he-IL"=>"he","hu-HU"=>"hu","is-IS"=>"is","id-ID"=>"id","ja"=>"ja","kn"=>"kn","ko-KR"=>"ko","lv"=>"lv","lt-LT"=>"lt","ms-MY"=>"ms","ms-MY"=>"ml","ml-IN"=>"ml","fa-IR"=>"fa","pl-PL"=>"pl","ro-RO"=>"ro","ru-RU"=>"ru","sr-RS"=>"sr","sr-RS"=>"sr-SR","sk-SK"=>"sk","sl-SI"=>"sl","sv-SE"=>"sv","th"=>"th","tr-TR"=>"tr","uk"=>"uk","ur"=>"ur","uz-UZ"=>"uz","vi"=>"vi","cy"=>"cy");
	$wp_lang = get_bloginfo('language');
	$keith_wood_timer_lang = array_key_exists( $wp_lang, $mp_keith_wood_countdown_timer_languages) ? $mp_keith_wood_countdown_timer_languages[$wp_lang] : 'en';
	if ( $keith_wood_timer_lang != 'en' ) {
		wp_register_script(
			'keith-wood-countdown-language',
			$pUrl . 'vendors/keith-wood-countdown-timer/js/lang/jquery.countdown-' . $keith_wood_timer_lang . '.js',
	    	array('mpce-countdown-plugin', 'mpce-countdown-timer'),
			$pVer,
			$vendorInFooter
		);
	}
	
    wp_register_script('mpce-waypoints', $pUrl . 'vendors/imakewebthings-waypoints/jquery.waypoints.min.js', array('jquery'), $pVer, $vendorInFooter);
//    wp_register_script('mp-waypoint-animations', $pUrl . 'includes/js/mp-waypoint-animations' . $suffix . '.js', array('jquery', 'mpce-waypoints'), $pVer); // old front
//    wp_register_script('mp-posts-grid', $pUrl . 'includes/js/mp-posts-grid' . $suffix . '.js', array('jquery'), $pVer); // old front

	wp_register_script('mp-frontend', $pUrl . 'includes/js/mp-frontend' . $suffix . '.js', array('jquery'), $pVer, $frontInFooter);

//	wp_localize_script('mp-posts-grid', 'MPCEPostsGrid', array(
	wp_localize_script('mp-frontend', 'MPCEPostsGrid', array(
		'admin_ajax' => admin_url('admin-ajax.php'),
		'nonces' => array(
			'motopress_ce_posts_grid_filter' => wp_create_nonce('wp_ajax_motopress_ce_posts_grid_filter'),
			'motopress_ce_posts_grid_turn_page' => wp_create_nonce('wp_ajax_motopress_ce_posts_grid_turn_page'),
			'motopress_ce_posts_grid_load_more' => wp_create_nonce('wp_ajax_motopress_ce_posts_grid_load_more')
		)
	));

	wp_localize_script('mp-frontend', 'MPCEVars', array(
		'fixed_row_width' => get_option('motopress-ce-fixed-row-width', $motopressCESettings['default_fixed_row_width']),
	));

    $mpGoogleChartsSwitch = array('motopressCE' => '0');
    wp_enqueue_style('mpce-theme');
	motopressCEAddFixedRowWidthStyle('mpce-theme');
    wp_enqueue_style('mpce-bootstrap-grid');

    if (MPCEContentManager::isBuilderRunning()) {
	    // Unused
//        wp_deregister_style('mpce-bootstrap-responsive-utility');

        global $wp_scripts;
        $migrate = false;
        if (version_compare($wp_scripts->registered['jquery']->ver, MPCERequirements::MIN_JQUERY_VER, '<')) {
            $wpjQueryVer = motopressCEGetWPScriptVer('jQuery');
            wp_deregister_script('jquery');
            wp_register_script('jquery', includes_url('js/jquery/jquery.js'), array(), $wpjQueryVer);
            wp_enqueue_script('jquery');

            if (version_compare($wpjQueryVer, '1.9.0', '>')) {
                if (wp_script_is('jquery-migrate', 'registered')) {
                    wp_enqueue_script('jquery-migrate', array('jquery'));
                    $migrate = true;
                }
            }
        }

	    // Load IFrame styles
	    wp_enqueue_style('mpce-bootstrap-datetimepicker', $pUrl . 'bootstrap/datetimepicker/bootstrap-datetimepicker.min.css', array(), $pVer);
	    wp_enqueue_style('mpce-select2', $pUrl . 'vendors/select2/select2.min.css', array(), $pVer);
	    wp_enqueue_style('mpce-bootstrap-select', $pUrl . 'bootstrap/select/bootstrap-select.min.css', array(), $pVer);
	    wp_enqueue_style('mpce-iframe', $pUrl . 'mp/ce/css/ceIframe.css', array(), $pVer);
	    wp_enqueue_style('mpce-jquery-ui-dialog', includes_url('css/jquery-ui-dialog.css') , array(), $pVer);
	    wp_enqueue_style('mpce-bootstrap-icon', $pUrl . 'bootstrap/bootstrap-icon.min.css', array(), $pVer);
	    wp_enqueue_style('mpce-spectrum-theme', $pUrl . 'vendors/bgrins-spectrum/build/spectrum_theme.css', array(), $pVer);

	    // --- Load IFrame scripts ---

	    // Fix jQueryUI (must be enqueued before jQueryUI)
        wp_enqueue_script('mpce-pre-bootstrap', $pUrl . 'mp/ce/iframeProd/pre-bootstrap' . $suffix . '.js', array('jquery'), $pVer, $editorInFooter);

	    // jQueryUI components
	    wp_enqueue_script('mpce-jquery-ui', $motopressCESettings['load_scripts_url'], array('jquery'), $pVer, true);

	    // Load CanJS
	    wp_register_script('mpce-canjs', $pUrl . 'vendors/canjs/can.custom.min.js', array('jquery'), $motopressCESettings['canjs_version'], $vendorInFooter);
        wp_enqueue_script('mpce-canjs');

	    if ($motopressCESettings['lang']['select2'] !== 'en') {
		    wp_enqueue_script('mpce-select2-locale', $pUrl . 'vendors/select2/select2_locale_' . $motopressCESettings['lang']['select2'] . '.js', array('jquery', 'mpce-select2'), $pVer, $vendorInFooter);
	    }
        wp_enqueue_script('mpce-bootstrap2-custom', $pUrl . 'bootstrap/bootstrap2-custom.min.js', array('jquery'), $pVer, $vendorInFooter);
        wp_enqueue_script('mpce-bootstrap-select', $pUrl . 'bootstrap/select/bootstrap-select.min.js', array('jquery'), $pVer, $vendorInFooter);
        wp_enqueue_script('mpce-jquery-fonticonpicker', $pUrl . 'vendors/fontIconPicker/jquery.fonticonpicker.min.js', array('jquery'), $pVer, $vendorInFooter);
        wp_enqueue_script('mpce-spectrum', $pUrl . 'vendors/bgrins-spectrum/build/spectrum-min.js', array('jquery'), $pVer, $vendorInFooter);
        wp_enqueue_script('mpce-select2', $pUrl . 'vendors/select2/select2.min.js', array('jquery'), $pVer, $vendorInFooter);
	    wp_enqueue_script('mpce-bootstrapx-clickover', $pUrl . 'bootstrap/clickover/bootstrapx-clickover.min.js', array('jquery'), $pVer, $vendorInFooter);
	    wp_enqueue_script('mpce-moment', $pUrl . 'vendors/moment.js/moment.min.js', array('jquery'), $pVer, $vendorInFooter);
	    wp_enqueue_script('mpce-bootstrap-datetimepicker', $pUrl . 'bootstrap/datetimepicker/bootstrap-datetimepicker.min.js', array('jquery'), $pVer, $vendorInFooter);

	    wp_enqueue_script('mpce-editor', $pUrl . 'mp/ce/iframeProd/editor' . $suffix . '.js', array('jquery'), $pVer, $editorInFooter);
	    wp_localize_script('mpce-editor', 'MP', array());
	    wp_localize_script('mpce-editor', 'CE', array());

        wp_register_script('mpce-no-conflict', $pUrl . 'mp/core/noConflict/noConflict' . $suffix . '.js', array('jquery'), $pVer, $noConflictInFooter);
        wp_enqueue_script('mpce-no-conflict');
        $jQueryOffset = array_search('jquery', $wp_scripts->queue) + 1;
        $index = ($migrate) ? array_search('jquery-migrate', $wp_scripts->queue) : array_search('mpce-no-conflict', $wp_scripts->queue);
        $length = $index - $jQueryOffset;
        $slice = array_splice($wp_scripts->queue, $jQueryOffset, $length);
        $wp_scripts->queue = array_merge($wp_scripts->queue, $slice);

/*
        $wpjQueryUIVer = motopressCEGetWPScriptVer('jQueryUI');
        foreach (MPCERequirements::$jQueryUIComponents as $component) {
            if (wp_script_is($component)) {
                if (version_compare($wp_scripts->registered[$component]->ver, MPCERequirements::MIN_JQUERYUI_VER, '<')) {
                    wp_deregister_script($component);
                }
            }
        }
        wp_register_script('mpce-jquery-ui', $motopressCESettings['admin_url'].'load-scripts.php?c=0&load='.implode(',', MPCERequirements::$jQueryUIComponents), array('mpce-no-conflict'), $wpjQueryUIVer);
        wp_enqueue_script('mpce-jquery-ui');
*/

        if (wp_script_is('jquery-ui.min')) wp_dequeue_script('jquery-ui.min'); //fix for theme1530

        wp_register_script('mpce-tinymce', $pUrl . 'vendors/tinymce/tinymce.min.js', array(), $pVer, $vendorInFooter);
        wp_enqueue_script('mpce-tinymce');

        wp_enqueue_style('mpce-flexslider');
        wp_enqueue_script('mpce-flexslider');

		wp_enqueue_script('mpce-magnific-popup');

        wp_enqueue_script('google-charts-api');
//        wp_enqueue_script('mp-google-charts'); // old front

        wp_enqueue_style('wp-mediaelement');
        wp_enqueue_script('wp-mediaelement');

        wp_enqueue_script('stellar');
//        wp_enqueue_script('mp-row-parallax'); // old front
        wp_enqueue_script('mp-youtube-api');
//	    wp_enqueue_script('mp-row-fullwidth'); // old front
//        wp_enqueue_script('mp-video-background'); // old front
//        wp_enqueue_script('mp-grid-gallery'); // old front

        wp_enqueue_script('mpce-countdown-plugin');
        wp_enqueue_script('mpce-countdown-timer');
	    if (wp_script_is('keith-wood-countdown-language', 'registered')) {
		    wp_enqueue_script('keith-wood-countdown-language');
	    }

	    // TODO: Is it needed in editor ?
	    wp_enqueue_script('mpce-waypoints');

        wp_enqueue_style('mpce-font-awesome');

	    wp_enqueue_script('mp-frontend');

        if (is_plugin_active('motopress-slider/motopress-slider.php') || is_plugin_active('motopress-slider-lite/motopress-slider.php')) {
            global $mpsl_settings;
            if (version_compare($mpsl_settings['plugin_version'], '1.1.2', '>=')) {
	            /** @var MPSlider $mpSlider */
                global $mpSlider;
                $mpSlider->enqueueScriptsStyles();
            }
        }

        $mpGoogleChartsSwitch = array('motopressCE' => '1');

        do_action('mpce_add_custom_scripts');
        do_action('mpce_add_custom_styles');
    }

//    wp_localize_script('mp-google-charts', 'motopressGoogleChartsPHPData', $mpGoogleChartsSwitch);
	wp_localize_script('mp-frontend', 'motopressGoogleChartsPHPData', $mpGoogleChartsSwitch);
}

/**
 *  Add fixed row width styles to a registered stylesheet.
 *
 * @param string $handle Name of the stylesheet to add the extra styles to. Must be lowercase.
 */
function motopressCEAddFixedRowWidthStyle($handle){
	global $motopressCESettings;
	$fixedRowWidth = get_option('motopress-ce-fixed-row-width', $motopressCESettings['default_fixed_row_width']);

	$style = '.mp-row-fixed-width {'
			. 'max-width:' . $fixedRowWidth . 'px;'
			. '}';
	wp_add_inline_style($handle, $style);
}

$mpceCustomStyleManager = MPCECustomStyleManager::getInstance();
$shortcode = new MPCEShortcode();
$shortcode->register();

/**
 * @param WP_Admin_Bar $wp_admin_bar
 */
function motopressCEAdminBarMenu($wp_admin_bar) {
    if (is_admin_bar_showing() && !is_admin() && !is_preview()) {
        global $wp_the_query, $motopressCESettings;
        $current_object = $wp_the_query->get_queried_object();
        if (!empty($current_object) &&
            !empty($current_object->post_type) &&
            ($post_type_object = get_post_type_object($current_object->post_type)) &&
            $post_type_object->show_ui && $post_type_object->show_in_admin_bar
        ) {
            require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/Access.php';
            $ceAccess = new MPCEAccess();

            $postType = get_post_type();
            $postTypes = get_option('motopress-ce-options', array('post', 'page'));

            if (in_array($postType, $postTypes) && post_type_supports($postType, 'editor') && $ceAccess->hasAccess($current_object->ID)) {
                $isHideLinkEditWith = apply_filters('mpce_hide_link_edit_with', false);
                if (!$isHideLinkEditWith) {
                    $wp_admin_bar->add_menu(array(
                        'href' => get_edit_post_link($current_object->ID) . '&motopress-ce-auto-open=true',
                        'parent' => false,
                        'id' => 'motopress-edit',
                        'title' => __("Visual Builder", 'motopress-content-editor-lite'),
                        'meta' => array(
                            'title' => __("Visual Builder", 'motopress-content-editor-lite')
                        )
                    ));
                }
            }
        }
    }
}
add_action('admin_bar_menu', 'motopressCEAdminBarMenu', 81);

require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/Library.php';

function motopressCEWPInit() {
    if (!is_admin()) {
        if (!isset($motopressCERequirements)) {
            global $motopressCERequirements;
            $motopressCERequirements = new MPCERequirements();
        }
		$motopressCELibrary = MPCELibrary::getInstance();
	}	
}
add_action('init', 'motopressCEWPInit');

function motopressSetBrandName(){
    global $motopressCESettings;
    $motopressCESettings['brand_name'] = apply_filters('mpce_brand_name', 'MotoPress');
}
add_action('after_setup_theme', 'motopressSetBrandName');

if (!is_admin()) {
    add_action('wp', array('MPCEShortcode', 'setCurPostData'));
    return;
}

require_once $motopressCESettings['plugin_dir_path'] . 'contentEditor.php';
require_once $motopressCESettings['plugin_dir_path'] . 'motopressOptions.php';
//require_once $motopressCESettings['plugin_dir_path'] . 'includes/settings.php';
require_once $motopressCESettings['plugin_dir_path'] . 'includes/Flash.php';
require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/Tutorials.php';

add_action('admin_init', 'motopressCEInit');
add_action('admin_menu', 'motopressCEMenu', 11);


function motopressCEInit() {
	global $motopressCESettings;

	$suffix = $motopressCESettings['script_suffix'];
	$pUrl = $motopressCESettings['plugin_dir_url'];
	$pVer = $motopressCESettings['plugin_version'];
	$brwsrDetectInFooter = false;

    wp_register_style('mpce-style', $pUrl . 'includes/css/style' . $suffix . '.css', array(), $pVer);
    wp_register_script('mpce-detect-browser', $pUrl.'mp/core/detectBrowser/detectBrowser' . $suffix . '.js', array(), $pVer, $brwsrDetectInFooter);

    wp_enqueue_script('mpce-detect-browser');

    if (!is_array(get_option('motopress_google_font_classes'))){
        add_action('admin_notices', 'motopress_google_font_not_writable_notice');
        $fontClasses = array(
            'opensans' => array(
                'family' => 'Open Sans',
                'variants' => array('300', 'regular', '700')
            )
        );
        saveGoogleFontClasses($fontClasses);
    }
}

function motopressCEAfterUpdate() {

	global $motopressCESettings;

	$pluginVer = $motopressCESettings['plugin_version'];
	// `2.3.0` is a version when updates were integrated
	$dbVer = get_option('motopress_db_version', '2.3.0');
	$versionsToUpdate = $motopressCESettings['versions_to_update'];

	if ($dbVer !== $pluginVer) {

		foreach ($versionsToUpdate as $verToUpdate) {

			if (version_compare($dbVer, $verToUpdate, '<') && version_compare($verToUpdate, $pluginVer, '<=')) {

				$updaterFile = $motopressCESettings['plugin_dir_path'] . "includes/updates/{$verToUpdate}.php";

				if (file_exists($updaterFile)) {
					include($updaterFile);
				}

				update_option('motopress_db_version', $verToUpdate);

			}

		}

		update_option('motopress_db_version', $pluginVer);

	}

}
add_action('admin_init', 'motopressCEAfterUpdate');


function motopress_google_font_not_writable_notice(){
    $error = motopress_check_google_font_dir_permissions();
    if (isset($error['error'])) {
        echo '<div class="error"><p>' . __("Error while installing the default set of Google Fonts.", 'motopress-content-editor-lite') . '</p><p>' . $error['error'] . '</p></div>';
    }
}

/**
 * Check permissions for writing Google Font's style files.
 *
 * @param boolean $mkdir creates the necessary directories
 * @return array $error
 */
function motopress_check_google_font_dir_permissions($mkdir = false){
    global $motopressCESettings;
    $error = array();
    if ( !is_dir($motopressCESettings['google_font_classes_dir'])) {
        if (!is_dir($motopressCESettings['plugin_upload_dir_path'])) {
            if (is_writable($motopressCESettings['wp_upload_dir'])){
                if ($mkdir) {
                    mkdir($motopressCESettings['plugin_upload_dir_path'], 0777);
                    mkdir($motopressCESettings['google_font_classes_dir'], 0777);
                }
            } else {
                $error['error'] = sprintf(__("Note: you are not able to edit Google Fonts because the directory %s not found or not writable.", 'motopress-content-editor-lite'), $motopressCESettings['wp_upload_dir']);
            }
        } elseif(is_writable($motopressCESettings['plugin_upload_dir_path'])){
            if ($mkdir) {
                mkdir($motopressCESettings['google_font_classes_dir'], 0777);
            }
        } else {
            $error['error'] =  sprintf(__("Note: you are not able to edit Google Fonts because the directory %s not found or not writable.", 'motopress-content-editor-lite'), $motopressCESettings['plugin_upload_dir_path']);
        }
    }
    if (!isset($error['error']) && !is_writable($motopressCESettings['google_font_classes_dir'])){
        $error['error'] = sprintf(__("Note: you are not able to edit Google Fonts because the directory %s not found or not writable.", 'motopress-content-editor-lite'), $motopressCESettings['google_font_classes_dir']);
    }

    return $error;
}

function motopressCEMenu() {
	global $motopressCESettings;
    require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/Access.php';
    $ceAccess = new MPCEAccess();

    if ( !$ceAccess->isCEDisabledForCurRole() ) {
        global $motopressCERequirements;
        $motopressCERequirements = new MPCERequirements();
        global $motopressCEIsjQueryVer;
        $motopressCEIsjQueryVer = motopressCECheckjQueryVer();

        $isHideMenu = apply_filters( 'mpce_hide_menu_page', false );
        if (!$isHideMenu) {
            $mainMenuSlug = 'motopress';

            $mainMenuExists = has_action('admin_menu', 'motopressMenu');
            if (!$mainMenuExists) {
                $iconSrc = apply_filters('mpce_menu_icon_src', $motopressCESettings['plugin_dir_url'] . 'images/menu-icon.png');
                $mainPage = add_menu_page($motopressCESettings['brand_name'], $motopressCESettings['brand_name'], 'read', $mainMenuSlug, 'motopressCE', $iconSrc);
            } else {
                $optionsHookname = get_plugin_page_hookname('motopress_options', $mainMenuSlug);
                remove_action($optionsHookname, 'motopressOptions');
                remove_submenu_page('motopress', 'motopress_options');
            }
            $proMenuTitle = __("Visual Builder", 'motopress-content-editor-lite');
            $liteMenuTitle = __("Visual Builder Lite", 'motopress-content-editor-lite');
            $menuTitle = apply_filters('mpce_submenu_title', $liteMenuTitle);
            $mainPage = add_submenu_page($mainMenuSlug, $menuTitle, $menuTitle, 'read', $mainMenuExists ? 'motopress_content_editor' : 'motopress', 'motopressCE');
            $hideOptions = get_site_option('motopress-ce-hide-options-on-subsites', '0');
            if ($hideOptions === '0' || (is_multisite() && is_main_site()) ) {
                $optionsPage = add_submenu_page($mainMenuSlug, __("Settings", 'motopress-content-editor-lite'), __("Settings", 'motopress-content-editor-lite'), 'manage_options', 'motopress_options', 'motopressCEOptions');
                add_action('load-' . $optionsPage, 'motopressCESettingsSave');
                add_action('admin_print_styles-' . $optionsPage, 'motopressCEAdminStylesAndScripts');
	            do_action('admin_mpce_settings_init', $optionsPage);
            }

            $isHideLicensePage = false;
            if (!$isHideLicensePage && is_main_site()) {
	            $licenseMenuSlug = 'motopress_license';
                $licensePage = add_submenu_page($mainMenuSlug, __("Licenses", 'motopress-content-editor-lite'), __("Licenses", 'motopress-content-editor-lite'), 'manage_options', $licenseMenuSlug, 'motopressCELicense');
                add_action('load-' . $licensePage, 'motopressCELicenseLoad');
                add_action('admin_print_styles-' . $licensePage, 'motopressCEAdminStylesAndScripts');
                do_action('admin_mpce_license_init', $optionsPage);
	            motopressCESetLicenseTabs();
	            if (!count($motopressCESettings['license_tabs'])) remove_submenu_page($mainMenuSlug, $licenseMenuSlug);
            }
            add_action('admin_print_styles-' . $mainPage, 'motopressCEAdminStylesAndScripts');
        }

        add_action('admin_print_scripts-post.php', 'motopressCEAddTools');
        add_action('admin_print_scripts-post-new.php', 'motopressCEAddTools');
    }
}

function motopressCESetLicenseTabs() {
    global $motopressCESettings;

	$_tabs = array();
	$tabs = apply_filters('admin_mpce_license_tabs', $_tabs);
	$tabs = is_array($tabs) ? $tabs : array();

	uasort($tabs, 'motopressCESortTabs');
	$motopressCESettings['license_tabs'] = $tabs;
}

function motopressCESortTabs($a, $b) {
    return $a['priority'] - $b['priority'];
}

function motopressCEAdminStylesAndScripts() {
	global $motopressCESettings;
	$pluginId = isset($_GET['plugin']) ? $_GET['plugin'] : $motopressCESettings['plugin_short_name'];

    wp_enqueue_style('mpce-style');
	do_action('admin_mpce_settings_print_styles-' . $pluginId);
}

function motopressCE() {
    motopressCEShowWelcomeScreen();
}

function motopressCEShowWelcomeScreen() {
    global $motopressCESettings;
    echo '<div class="motopress-title-page">';
    $logoLargeSrc = apply_filters('mpce_large_logo_src', $motopressCESettings['plugin_dir_url'].'images/logo-large.png?ver='.$motopressCESettings['plugin_version']);
    echo '<img id="motopress-logo" src="' . esc_url($logoLargeSrc) . '" />';
    $siteUrl = apply_filters('mpce_wl_site_url', 'https://motopress.com');
    $siteName = apply_filters('mpce_wl_site_name', 'motopress.com');
    echo '<p class="motopress-description">' . sprintf(__("%s Visual Builder makes the process of post building easy and fast. Its Drag&Drop functionality helps to manage your pages, add built-in content elements, edit them visually and see the result right away. For more information visit <a href='%s' target='_blank'>%s</a>", 'motopress-content-editor-lite'), $motopressCESettings['brand_name'], $siteUrl, $siteName) . '</p>';

    global $motopressCEIsjQueryVer;
    if (!$motopressCEIsjQueryVer) {
        MPCEFlash::setFlash(sprintf(__("Minimum jQuery version - %s. Minimum jQuery UI version - %s. Please update WordPress to 3.5 or higher.", 'motopress-content-editor-lite'), MPCERequirements::MIN_JQUERY_VER, MPCERequirements::MIN_JQUERYUI_VER), 'error');
    }

    echo '<p><div class="alert alert-error" id="motopress-browser-support-msg" style="display:none;">'.__("Your browser is not supported. We recommend one of these supported browsers: <a href='http://www.mozilla.org/en-US/firefox/new' target='_blank'>Firefox</a>, <a href='http://www.google.com/chrome' target='_blank'>Chrome</a>.", 'motopress-content-editor-lite').'</div></p>';

    $foundCEButtonDesc = apply_filters('mpce_found_button_description', __("<b>Visual Builder button can be found in the Pages and Posts section.</b>", 'motopress-content-editor-lite'));
    echo '<div class="motopress-block"><p class="motopress-title">' . $foundCEButtonDesc . '</p>';
    $foundButtonImageSrc = apply_filters('mpce_found_button_img_src', $motopressCESettings['plugin_dir_url'].'images/ce/ce.png?ver='.$motopressCESettings['plugin_version']);
    echo '<a href="'.admin_url('post-new.php?post_type=page').'" target="_self" id="motopress-ce-link"><img id="motopress-ce" src="' . esc_url($foundButtonImageSrc) . '" /></a></div>';

	?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (MPCEBrowser.IE || MPCEBrowser.Opera) {
                $('.motopress-block #motopress-ce-link')
                    .attr('href', 'javascript:void(0);')
                    .css({ cursor: 'default' });
                $('#motopress-browser-support-msg').show();
            }
        });
    </script>
    <?php
}

// Plugin Activation
function motopressCEInstall($network_wide) {
    global $wpdb;
    if ( is_multisite() && $network_wide ) {
		global $wp_version;
		if (version_compare($wp_version, '3.7', '>=')) {
			if (version_compare($wp_version, '4.6', '<')) {
				$sites = wp_get_sites();
			} else {
				$sites = get_sites();
				$sites = array_map('get_object_vars', $sites);
			}

			if (function_exists('array_column')) {
				$blogids = array_column($sites, 'blog_id');
			} else {
				$blogids = array();
				foreach ($sites as $key => $site) {
					$blogids[$key] = $site['blog_id'];
				}
			}
		} else {
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		}
        foreach ($blogids as $blog_id) {
            motopressActivationDefaults($blog_id);
        }
    } else {
        motopressActivationDefaults();
    }
    $autoLicenseKey = apply_filters('mpce_auto_license_key', false);
    if ($autoLicenseKey) {
        motopressCESetAndActivateLicense($autoLicenseKey);
    }
}

/*
 * @param bool|int $blog_id Id of blog that need set defaults. FALSE for single site.
 */
function motopressActivationDefaults($blog_id = false) {
	if ($blog_id) {
//		add_blog_option($blog_id, 'motopress-ce-options', array('post', 'page'));
	} else {
//		add_option('motopress-ce-options', array('post', 'page'));
	}
}

function motopressSetDefaultsForNewBlog($blog_id, $user_id, $domain, $path, $site_id, $meta){
	motopressActivationDefaults($blog_id);
}
register_activation_hook(__FILE__, 'motopressCEInstall');
// Plugin Activation END
add_action('wpmu_new_blog', 'motopressSetDefaultsForNewBlog', 10, 6);

function motopressCECheckjQueryVer() {
    $jQueryVer = motopressCEGetWPScriptVer('jQuery');
    $jQueryUIVer = motopressCEGetWPScriptVer('jQueryUI');

    return (version_compare($jQueryVer, MPCERequirements::MIN_JQUERY_VER, '>=') && version_compare($jQueryUIVer, MPCERequirements::MIN_JQUERYUI_VER, '>=')) ? true : false;
}

function motopress_edit_link($actions, $post){
    global $motopressCESettings;
    require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/Access.php';
    $ceAccess = new MPCEAccess();
    $ceEnabledPostTypes = get_option('motopress-ce-options', array('post', 'page'));
    $isHideLinkEditWith = apply_filters('mpce_hide_link_edit_with', false);

    if (!$isHideLinkEditWith && $ceAccess->hasAccess($post->ID) && in_array( $post->post_type, $ceEnabledPostTypes ) ){
        $newActions = array();
        foreach ($actions as $action => $value) {
            $newActions[$action] = $value;
            if ($action === 'inline hide-if-no-js') {
	            $linkTitle = __("Visual Builder", 'motopress-content-editor-lite');
	            $linkUri = add_query_arg('motopress-ce-auto-open', 'true', get_edit_post_link($post->ID, true));
                $newActions['motopress_edit_link'] = '<a href="' . $linkUri . '" title="' . esc_attr($linkTitle) . '">' . $linkTitle . '</a>';
            }
        }
        return $newActions;
    } else {
        return $actions;
    }

}
add_filter('page_row_actions', 'motopress_edit_link', 10, 2);
add_filter('post_row_actions', 'motopress_edit_link', 10, 2);

// WARNING! Do not write code below this line , if you are not sure that it is actually necessary.
}
else {
	add_action( 'admin_notices', 'motopressCELiteConflictNotice' ); 
	if (is_multisite()) add_action('network_admin_notices', 'motopressCELiteConflictNotice');
}
function motopressCELiteConflictNotice() {
	$class = "error";
	$message = "<b>MotoPress Content Editor Lite</b> plugin and <b>MotoPress Content Editor</b> plugin do not work simultaneously. Deactivate <b>MotoPress Content Editor Lite</b> plugin.";
        echo"<div class=\"$class\"> <p>$message</p></div>"; 
}
