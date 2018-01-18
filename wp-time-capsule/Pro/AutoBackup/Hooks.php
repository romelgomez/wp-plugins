<?php
class Wptc_Auto_Backup_Hooks extends Wptc_Base_Hooks{
	public $hooks_handler_obj,
			$config,
			$autobackup;

	public function __construct() {
		$this->config = WPTC_Pro_Factory::get('Wptc_Auto_Backup_Config');
		$this->hooks_handler_obj = WPTC_Pro_Factory::get('Wptc_Auto_Backup_Hooks_Hanlder');
		$this->autobackup = WPTC_Pro_Factory::get('Wptc_Auto_Backup');
	}

	public function register_hooks() {
		if (!$this->autobackup->is_auto_backup_enabled()) {
			$this->register_wptc_filters();
			$this->register_wptc_actions();
			return false;
		}

		$this->register_wptc_filters();
		$this->register_actions();
		$this->register_filters();
		$this->register_wptc_actions();
	}


	public function register_actions(){
		//Update actions
		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		// add_action('automatic_updates_complete', array($this->hooks_handler_obj, 'update_handler'));

		//install actions
		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		// add_action('upgrader_process_complete', array($this->hooks_handler_obj, 'plugin_theme_install_update_handler'), 10, 2);

		//Upload and Attachment actions

		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		// add_action('add_attachment', array($this->hooks_handler_obj, 'upload_handler'));
		// add_action('delete_attachment', array($this->hooks_handler_obj, 'upload_handler'));
		// add_action('edit_attachment', array($this->hooks_handler_obj, 'upload_handler'));
		// add_action('media_upload_audio', array($this->hooks_handler_obj, 'upload_handler'));
		// add_action('media_upload_file', array($this->hooks_handler_obj, 'upload_handler'));
		// add_action('media_upload_gallery', array($this->hooks_handler_obj, 'upload_handler'));
		// add_action('media_upload_image', array($this->hooks_handler_obj, 'upload_handler'));
		// add_action('media_upload_video', array($this->hooks_handler_obj, 'upload_handler'));

		//Edited actions
		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		// add_action('updated_option', array($this->hooks_handler_obj, 'edit_handler_options_table'), 10, 3);
	}

	public function register_filters() {

		//Update actions
		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		// add_filter('update_plugin_complete_actions', array($this->hooks_handler_obj, 'update_handler_filters'), 1, 2); //working
		// add_filter('update_bulk_plugin_complete_actions', array($this->hooks_handler_obj, 'update_handler_filters'), 1, 2); //working
		// add_filter('update_theme_complete_actions', array($this->hooks_handler_obj, 'update_handler_filters'), 1, 2);//working
		// add_filter('update_bulk_theme_complete_actions', array($this->hooks_handler_obj, 'update_handler_filters'), 1, 2);//working
		// add_filter('update_translations_complete_actions', array($this->hooks_handler_obj, 'update_handler_filters'), 10, 1);

		//install actions
		// add_filter('upgrader_post_install', array($this->hooks_handler_obj, 'install_handler_filters'), 10, 3);//working

		//Upload and Attachment actions
		// add_filter('wp_handle_upload', array($this->hooks_handler_obj, 'upload_handler_filters'), 10);//working
		//add_filter('wp_handle_upload_prefilter', array($this->hooks_handler_obj, 'upload_handler_filters'), 10);

		//make up
		// add_filter('async_update_translation', array($this->hooks_handler_obj, 'translation_update_make_up'), 1, 2);

		//query listener
		// add_filter('update_plugin_complete_actions', array($this->hooks_handler_obj, 'wtc_record_query'), 10);
	}

	public function add_query_filter_wptc(){
		//Do not enable this until we need to store all the executed queries this may slow down the site.
		// add_filter('query', array($this->hooks_handler_obj, 'wtc_record_query'), 10);
	}

	public function register_wptc_actions() {
		//query listener
		add_action('add_query_filter_wptc', array($this, 'add_query_filter_wptc'));
		add_action('record_auto_backup_complete_wptc', array($this->hooks_handler_obj, 'record_auto_backup_complete'), 10, 1);
		add_action('update_auto_backup_record_db_wptc', array($this->hooks_handler_obj, 'update_auto_backup_record_db'));
		add_action('start_auto_backup_wptc', array($this->hooks_handler_obj, 'start_auto_backup'));
		// add_action('finish_auto_backup', array($this->hooks_handler_obj, 'finish_auto_backup'));
	}

	public function register_wptc_filters() {
		add_filter('add_auto_backup_record_to_backup_wptc', array($this->hooks_handler_obj, 'add_auto_backup_record_to_backup'), 10);
		// add_filter('set_default_backup_constant_wptc_h', array($this->hooks_handler_obj, 'set_default_backup_constant_wptc_h'), 10);
		add_filter('inside_backup_type_settings_wptc_h', array($this->hooks_handler_obj, 'inside_backup_type_settings_wptc_h'), 10);
		add_filter('force_stop_reset_autobackup_wptc_h', array($this->hooks_handler_obj, 'force_stop_reset_autobackup_wptc_h'), 10);
		add_filter('is_auto_backup_running_wptc', array($this->hooks_handler_obj, 'is_auto_backup_running'), 10);
		add_filter('get_backup_slots_wptc', array($this->hooks_handler_obj, 'get_backup_slots'), 10, 1);
		add_filter('check_requirements_auto_backup_wptc', array($this->hooks_handler_obj, 'check_requirements'), 10);
		add_filter('validate_auto_backup_wptc', array($this->hooks_handler_obj, 'validate_auto_backup'), 10, 1);
	}
}