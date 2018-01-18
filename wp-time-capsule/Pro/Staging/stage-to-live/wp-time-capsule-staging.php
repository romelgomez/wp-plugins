<?php
/*
Plugin Name: WP Time Capsule Staging
Plugin URI: https://wptimecapsule.com
Description: WP Time Capsule Staging plugin.
Author: Revmakx
Version: 1.0.0
Author URI: http://www.revmakx.com
Tested up to: 4.8
/************************************************************
 * This plugin was modified by Revmakx
 * Copyright (c) 2017 Revmakx
 * www.revmakx.com
 ************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Time_Capsule_Staging{

	private $stage_to_live;

	public function __construct(){
		$this->include_constants_file();
		$this->include_files();
		$this->include_primary_files_wptc();
		$this->create_objects();
		$this->init_hooks();
	}

	/**
	 * Define WPTC Staging Constants.
	*/
	private function include_constants_file() {
		require_once dirname(__FILE__).  DIRECTORY_SEPARATOR  .'wptc-constants.php';
		$constants = new WPTC_Constants();
		$constants->init_staging_plugin();
	}

	private function include_files(){
		include_once ( WPTC_PLUGIN_DIR . 'includes/class-file-iterator.php' );
		include_once ( WPTC_PLUGIN_DIR . 'includes/class-stage-to-live.php' );
		include_once ( WPTC_PLUGIN_DIR . 'includes/class-stage-common.php' );
		include_once ( WPTC_PLUGIN_DIR . 'includes/common-functions.php' );
		include_once ( WPTC_PLUGIN_DIR . 'utils/g-wrapper-utils.php' );
		include_once ( WPTC_CLASSES_DIR . 'Extension/Base.php' );
		include_once ( WPTC_CLASSES_DIR . 'Extension/Manager.php' );
		include_once ( WPTC_CLASSES_DIR . 'Extension/DefaultOutput.php' );
		include_once ( WPTC_CLASSES_DIR . 'Processed/Base.php' );
		include_once ( WPTC_CLASSES_DIR . 'Processed/Files.php' );
		include_once ( WPTC_CLASSES_DIR . 'Processed/Restoredfiles.php' );
		include_once ( WPTC_CLASSES_DIR . 'Processed/iterator.php' );
		include_once ( WPTC_CLASSES_DIR . 'DatabaseBackup.php' );
		include_once ( WPTC_CLASSES_DIR . 'FileList.php' );
		include_once ( WPTC_CLASSES_DIR . 'Config.php' );
		include_once ( WPTC_CLASSES_DIR . 'Logger.php' );
		include_once ( WPTC_CLASSES_DIR . 'Factory.php' );
	}

	private function include_primary_files_wptc() {

		include_once( WPTC_PLUGIN_DIR.'Base/Factory.php' );

		include_once( WPTC_PLUGIN_DIR.'Base/init.php' );
		include_once( WPTC_PLUGIN_DIR.'Base/Hooks.php' );
		include_once( WPTC_PLUGIN_DIR.'Base/HooksHandler.php' );
		include_once( WPTC_PLUGIN_DIR.'Base/Config.php' );

		include_once( WPTC_PLUGIN_DIR.'Base/CurlWrapper.php' );

		include_once( WPTC_CLASSES_DIR.'CronServer/Config.php' );
		include_once( WPTC_CLASSES_DIR.'CronServer/CurlWrapper.php' );

		include_once( WPTC_CLASSES_DIR.'WptcBackup/init.php' );
		include_once( WPTC_CLASSES_DIR.'WptcBackup/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'WptcBackup/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'WptcBackup/Config.php' );

		include_once( WPTC_CLASSES_DIR.'Common/init.php' );
		include_once( WPTC_CLASSES_DIR.'Common/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'Common/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'Common/Config.php' );

		include_once( WPTC_CLASSES_DIR.'Analytics/init.php' );
		include_once( WPTC_CLASSES_DIR.'Analytics/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'Analytics/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'Analytics/Config.php' );
		include_once( WPTC_CLASSES_DIR.'Analytics/BackupAnalytics.php' );

		include_once( WPTC_CLASSES_DIR.'ExcludeOption/init.php' );
		include_once( WPTC_CLASSES_DIR.'ExcludeOption/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'ExcludeOption/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'ExcludeOption/Config.php' );
		include_once( WPTC_CLASSES_DIR.'ExcludeOption/ExcludeOption.php' );

		include_once( WPTC_CLASSES_DIR.'Settings/init.php' );
		include_once( WPTC_CLASSES_DIR.'Settings/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'Settings/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'Settings/Config.php' );
		include_once( WPTC_CLASSES_DIR.'Settings/Settings.php' );

		include_once( WPTC_CLASSES_DIR.'UpdateCommon/init.php' );
		include_once( WPTC_CLASSES_DIR.'UpdateCommon/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'UpdateCommon/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'UpdateCommon/Config.php' );
		include_once( WPTC_CLASSES_DIR.'UpdateCommon/UpdateCommon.php' );

		include_once( WPTC_CLASSES_DIR.'AppFunctions/init.php' );
		include_once( WPTC_CLASSES_DIR.'AppFunctions/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'AppFunctions/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'AppFunctions/Config.php' );
		include_once( WPTC_CLASSES_DIR.'AppFunctions/AppFunctions.php' );

		include_once( WPTC_CLASSES_DIR.'InitialSetup/init.php' );
		include_once( WPTC_CLASSES_DIR.'InitialSetup/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'InitialSetup/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'InitialSetup/Config.php' );
		include_once( WPTC_CLASSES_DIR.'InitialSetup/InitialSetup.php' );

		include_once( WPTC_CLASSES_DIR.'Sentry/init.php' );
		include_once( WPTC_CLASSES_DIR.'Sentry/Hooks.php' );
		include_once( WPTC_CLASSES_DIR.'Sentry/HooksHandler.php' );
		include_once( WPTC_CLASSES_DIR.'Sentry/Config.php' );
		include_once( WPTC_CLASSES_DIR.'Sentry/Sentry.php' );
		if(is_wptc_server_req() || is_admin()) {
			WPTC_Base_Factory::get('Wptc_Base')->init();
		}
	}

	private function create_objects(){
		$this->stage_to_live = new WPTC_Stage_To_Live();
	}

	private function init_hooks(){
		add_action('admin_enqueue_scripts', array($this, 'add_scripts'));
		add_action('wp_ajax_wptc_copy_stage_to_live', array($this->stage_to_live, 'to_live'));
		add_action('wp_before_admin_bar_render', array($this->stage_to_live, 'change_sitename'));
		add_action('init', array($this->stage_to_live, 'check_permissions'));
		$this->add_admin_menu_hook();
	}

	private function add_admin_menu_hook(){
		if ( is_multisite() ) {
			add_action('network_admin_menu', array($this, 'add_admin_menu'));
		} else{
			add_action('admin_menu', array($this, 'add_admin_menu'));
		}
	}

	public function add_scripts(){

		if(is_windows_machine_wptc()){
			$site_url = site_url();
			$wp_content = basename(WPTC_WP_CONTENT_DIR);
			$plugin_dir = $site_url . '/' . $wp_content . '/' . 'plugins';
		} else {
			$plugin_dir = plugins_url();
		}

		wp_enqueue_script('wptc-staging-js', $plugin_dir . '/' . basename(dirname(__FILE__)) . '/js/wptc-staging.js', array(), WPTC_VERSION);
		wp_enqueue_style('wptc-s2l-css', $plugin_dir . '/' . basename(dirname(__FILE__)) . '/css/wptc-s2l.css', array(), WPTC_VERSION);
		wp_enqueue_style('wptc-css', $plugin_dir . '/' . basename(dirname(__FILE__)) . '/wp-time-capsule.css', array(), WPTC_VERSION);
		wp_enqueue_style('wptc-ui-css', $plugin_dir . '/' . basename(dirname(__FILE__)) . '/tc-ui.css', array(), WPTC_VERSION);
		$this->add_nonce();
	}

	public function add_nonce(){
		$params = array(
			'ajax_nonce' => wp_create_nonce('wptc_nonce'),
		);
		wp_localize_script( 'wptc-staging-js', 'wptc_staging_ajax_object', $params );
	}

	public function add_admin_menu() {
		$text = __('WPTC Staging', 'wp-time-capsule-staging');
		add_menu_page($text, $text, 'activate_plugins', 'wp-time-capsule-staging', array($this, 'staging_page'), 'dashicons-wptc', '80.0564');
	}

	public function staging_page() {
		$stage_to_live = $this->stage_to_live;
		include_once 'views/wp-time-capsule-staging.php';
	}

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

}

new WP_Time_Capsule_Staging();