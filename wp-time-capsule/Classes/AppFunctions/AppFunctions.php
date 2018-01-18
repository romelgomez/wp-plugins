<?php

class Wptc_App_Functions extends Wptc_App_Functions_Init {
	private $config,
			$wpdb,
			$logger,
			$utils_base,
			$current_iterator_table,
			$exclude_option,
			$wp_version,
			$allowed_free_disk_space;

	const RESET_CHUNK_UPLOAD_ON_FAILURE_LIMIT = 4;

	public function __construct(){
		//using common config here for not making config list complex
		$this->config = WPTC_Factory::get('config');
		$this->current_iterator_table = new WPTC_Processed_iterator();
		$this->allowed_free_disk_space = 1024 * 1024 * 10; //10 MB
		$this->retry_allowed_http_status_codes = array(5, 6, 7);
		$this->logger = WPTC_Factory::get('logger');
		$this->utils_base = new Utils_Base();
		$this->init_db();
	}

	public function init_db(){
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function set_user_to_access(){
		if ( ! function_exists( 'wp_get_current_user' ) )
			include_once ABSPATH.'wp-includes/pluggable.php';
		$user_id = $this->get_current_user_id();
		$oneyear = 60 * 60 * 24 * 365;
		$cookiepath = $this->get_cookiepath();
		setcookie('wptc_wl_allowed_user_id', $user_id, time() + $oneyear, $cookiepath);
	}

	private function get_cookiepath(){
		if (defined('COOKIEPATH')) {
			return COOKIEPATH;
		}

		if (function_exists('get_home_url')) {
			return get_home_url();
		}

		return home_url();
	}

	public function get_current_user_id(){
		if ( ! function_exists( 'wp_get_current_user' ) )
			include_once ABSPATH.'wp-includes/pluggable.php';
		$user = wp_get_current_user();
		return $user->data->ID;
	}

	public function shortern_plugin_slug($full_slug){
		if (strpos($full_slug, '/') !== false) {
			return substr($full_slug, 0, strrpos($full_slug, "/"));
		} else if(strpos($full_slug, '.') !== false){
			return substr($full_slug, 0, strrpos($full_slug, "."));
		}
	}

	public function is_user_purchased_this_class($classname = false){
		if (empty($classname)) return false;

		$data = $this->config->get_option('privileges_wptc');

		if (empty($data)) return false;

		$data = json_decode($data);

		if (empty($data)) return false;

		if (!empty($data->pro)) {
			$pro_arr = $data->pro;
		}

		if (!empty($data->lite)) {
			$pro_arr = $data->lite;
		}

		if (empty($pro_arr)) return false;

		$pro_arr_values = array_values($pro_arr);

		if (empty($pro_arr_values))	return false;

		if (in_array($classname, $pro_arr_values)) {
			return true;
		}

		return false;
	}

	public function is_free_user_wptc(){
		if($this->is_user_purchased_this_class('Wptc_Weekly_Backups') || !$this->is_user_purchased_this_class('Wptc_Daily_Backups')){
			return true;
		} else {
			return false;
		}
	}

	public function validate_dropbox_upgrade(){
		if($this->config->get_option('default_repo') != 'dropbox')
			return ;

		//check upgraded is successfull then return here
		if($this->verify_dropbox_api2_upgrade() === true)
			return ;

		//try upgrade if possible
		if($this->upgrade_dropbox_api1_to_api2() === false)
			return $this->remove_dropbox_api1_flags();

		//check upgrade status once again.
		if($this->verify_dropbox_api2_upgrade() === true)
			return ;

		//try upgrade if possible
		if($this->upgrade_dropbox_api1_to_api2() === false)
			return $this->remove_dropbox_api1_flags();

		//check upgrade status once again.
		if($this->verify_dropbox_api2_upgrade() === true)
			return ;

		return $this->remove_dropbox_api1_flags();
	}


	private function remove_dropbox_api1_flags(){
		$this->config->delete_option('access_token');
		$this->config->delete_option('access_token_secret');
		$this->config->delete_option('request_token');
		$this->config->delete_option('request_token_secret');
		$this->config->delete_option('oauth_state');
		$this->config->set_option('default_repo', '');
	}

	private function verify_dropbox_api2_upgrade(){
		//API-2 flags
		if ($this->config->get_option('dropbox_access_token') && $this->config->get_option('dropbox_oauth_state') === 'access' ){
			return true;
		}

		return false;
	}

	private function upgrade_dropbox_api1_to_api2(){
		//API-1 flags
		if (!$this->config->get_option('access_token') || !$this->config->get_option('access_token_secret')){
			return false;
		}

		//try upgrade once again
		$dropbox = WPTC_Factory::get('dropbox');
		$dropbox->migrate_to_v2();
	}

	public function die_with_json_encode($msg = 'empty data', $escape = 0){
		switch ($escape) {
			case 1:
			die(json_encode($msg, JSON_UNESCAPED_SLASHES));
			case 2:
			die(json_encode($msg, JSON_UNESCAPED_UNICODE));
		}
		die(json_encode($msg));
	}

	public function die_with_msg($msg){
		die($msg);
	}

	public function verify_ajax_requests(){

		//verify its ajax request
		if (empty($_POST['action'])) {
			return false;
		}


		//Verifies the Ajax request to prevent processing requests external of the site
		check_ajax_referer( 'wptc_nonce', 'security' );

		//Check request made by admin
		if (!current_user_can('activate_plugins')) {
			$this->die_with_msg('you are not authorized');
		}
	}

	public function server_has_free_space(){
		if (!function_exists('disk_free_space')) {
			return true;
		}

		$available_bytes = disk_free_space(ABSPATH);

		if (empty($available_bytes)) {
			return true;
		}

		$available_bytes = (int) $available_bytes; //typecasting to int because disk_free_space returns floating values

		if ($available_bytes > $this->allowed_free_disk_space) {
			return true;
		}

		return false;
	}

	public function is_retry_allowed_curl_status($code){
		return in_array($code, $this->retry_allowed_http_status_codes);
	}

	public function reset_chunk_upload_on_failure($file, $err_msg){

		wptc_log(func_get_args(), __FUNCTION__);

		$backup_controller = new WPTC_BackupController();

		if (empty($file)) {
			$this->log_activity('backup', 'Chunk Failed and File path is empty so Backup stopped!');
			return false;
		}

		wptc_remove_abspath($file);

		$limit = $this->get_chunk_upload_on_failure_count($file);


		$allow_retry = false;

		if (++$limit < self::RESET_CHUNK_UPLOAD_ON_FAILURE_LIMIT) {
			$allow_retry = true;
		}

		$backup_id = $this->get_cur_backup_id();

		$this->update_chunk_upload_on_failure_count($file, $limit);

		//delete from wptc_processed_files
		$sql = "DELETE FROM `" . $this->wpdb->base_prefix . "wptc_processed_files` WHERE backupID= " . $backup_id . " AND file ='" . $file . "'";
		$this->wpdb->query($sql);

		//get current file id
		$sql = "SELECT id FROM `" . $this->wpdb->base_prefix . "wptc_current_process` WHERE file_path = '" . $file . "'";
		wptc_log($sql, '---------------$sql-----------------');

		$file_id = $this->wpdb->get_var($sql);
		wptc_log($file_id, '---------------$file_id-----------------');

		if (empty($file_id)) {
			$this->log_activity('backup', 'Chunk reset file id is empty so Backup stopped!');
			$backup_controller->proper_backup_force_complete_exit('reset_chunk_upload_on_failure file id empty so stopping backup');
		}

		if ($allow_retry) {

			//update in wptc_current_process
			$sql = "UPDATE `" . $this->wpdb->base_prefix . "wptc_current_process` SET status = 'Q' WHERE file_path ='" . $file . "'";
			$result = $this->wpdb->query($sql);

			global $current_process_file_id;
			$current_process_file_id = $this->config->set_option('current_process_file_id', $file_id);

			//end the request
			send_response_wptc('Failure on chunk upload - File has been reset !');

		}

		//update in wptc_current_process to skip this file
		$sql = "UPDATE `" . $this->wpdb->base_prefix . "wptc_current_process` SET status = 'S' WHERE file_path = '" . $file . "'";
		$result = $this->wpdb->query($sql);

		global $current_process_file_id;
		$current_process_file_id = $this->config->set_option('current_process_file_id', $file_id + 1);

		//chunk failed more than the limit so stop the backup
		$this->log_activity('backup', 'Chunk Failed more than the limit  - '.$limit.' So File skipped!');

		$error_array = array(
			'file_name' => $file,
			'error' => $err_msg,
		);

		$this->config->append_option_arr_bool_compat('mail_backup_errors', $error_array, 'unable_to_backup');

		send_response_wptc('Unable to upload chunk so file skipped');
	}

	private function get_chunk_upload_on_failure_count($file){
		$limit = $this->config->get_option('reset_chunk_upload_on_failure_count');
		if (empty($limit)) {
			return 0;
		}

		$limit = unserialize($limit);

		if (empty($limit)) {
			return 0;
		}

		if (!isset($limit[$file])) {
			return 0;
		}

		return $limit[$file];
	}

	private function update_chunk_upload_on_failure_count($file, $count){
		$limit = $this->config->get_option('reset_chunk_upload_on_failure_count');

		if (empty($limit)) {
			$limit = array($file => $count);
		} else {
			$limit = unserialize($limit);
			$limit[$file] =  $count;
		}

		$this->config->set_option('reset_chunk_upload_on_failure_count', serialize($limit));
	}

	public function get_cur_backup_id(){
		return getTcCookie('backupID');
	}

	public function log_activity($type = false, $msg = false){
		switch ($type) {
			case 'backup':
				$backup_id = $this->get_cur_backup_id();
				break;
		}
		$this->logger->log(__($msg, 'wptc'), 'backup_progress', $backup_id);
	}

	public function is_wptc_installed(){
		//check wptc_options table present if yes then its not a fresh install
		$result = $this->wpdb->get_results("SHOW TABLES LIKE '".$this->wpdb->base_prefix."wptc_options'", ARRAY_N);
		return empty($result) ? false : true;
	}

	public function get_server_info() {
		$anonymous = array();
		$anonymous['server']['PHP_VERSION'] = phpversion();
		$anonymous['server']['PHP_CURL_VERSION'] = curl_version();
		$anonymous['server']['PHP_WITH_OPEN_SSL'] = function_exists('openssl_verify');
		$anonymous['server']['PHP_MAX_EXECUTION_TIME'] = ini_get('max_execution_time');
		$anonymous['server']['MYSQL_VERSION'] = $this->wpdb->get_var("select version() as V");
		$anonymous['server']['OS'] = php_uname('s');
		$anonymous['server']['OSVersion'] = php_uname('v');
		$anonymous['server']['Machine'] = php_uname('m');

		$anonymous['server']['PHPDisabledFunctions'] = explode(',', ini_get('disable_functions'));
		array_walk($anonymous['server']['PHPDisabledFunctions'], 'trim_value_wptc');

		$anonymous['server']['PHPDisabledClasses'] = explode(',', ini_get('disable_classes'));
		array_walk($anonymous['server']['PHPDisabledClasses'], 'trim_value_wptc');

		return $anonymous;
	}

	public function set_start_time(){
		global $wptc_ajax_start_time, $wptc_profiling_start;
		$wptc_profiling_start = $wptc_ajax_start_time = time();
	}

	public function run_deactivate_plugin( $plugin, $prefix ) {

		if(is_multisite()){
			$this->run_deactivate_plugin_multi_site($plugin, $prefix);
		}

		$sql = "SELECT option_value FROM `" . $prefix . "options` WHERE option_name = 'active_plugins'";

		$active_plugins = $this->wpdb->get_var($sql);

		if (empty($active_plugins)) {
			return false;
		}

		$active_plugins = unserialize($active_plugins);


		$key = array_search($plugin, $active_plugins);

		if($key === false || $key === NULL){
			return false;
		}

		unset($active_plugins[$key]);

		sort( $active_plugins );

		unset($active_plugins[$plugin]);

		$sql = 'UPDATE `'.$prefix."options` SET option_value = '".serialize($active_plugins)."' WHERE option_name = 'active_plugins'";

		$result = $this->wpdb->query($sql);
	}

	public function run_deactivate_plugin_multi_site( $plugin, $prefix ) {

		$sql = "SELECT meta_value FROM `" . $prefix . "sitemeta` WHERE meta_key = 'active_sitewide_plugins'";

		$active_plugins = $this->wpdb->get_var($sql);

		if (empty($active_plugins)) {
			return false;
		}

		$active_plugins = unserialize($active_plugins);

		unset($active_plugins[$plugin]);

		$sql = 'UPDATE `'.$prefix."sitemeta` SET meta_value = '".serialize($active_plugins)."' WHERE meta_key = 'active_sitewide_plugins'";

		$result = $this->wpdb->query($sql);
	}

	public function run_activate_plugin( $plugin, $prefix ) {

		if(is_multisite()){
			$this->run_activate_plugin_multi_site($plugin, $prefix);
		}

		$sql = "SELECT option_value FROM `" . $prefix. "options` WHERE option_name = 'active_plugins'";

		$current = $this->wpdb->get_var($sql);

		if (!empty($current)) {
			$current = unserialize($current);
		} else {
			$current = array();
		}

		if ( in_array( $plugin, $current ) ) {
			return false;
		}

		$current[] = $plugin;
		sort( $current );
		$sql = 'UPDATE `'. $prefix. "options` SET option_value = '".serialize($current)."' WHERE option_name = 'active_plugins'";
		$result = $this->wpdb->query($sql);
	}

	public function run_activate_plugin_multi_site($plugin, $prefix){
		wptc_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		$sql = "SELECT meta_value FROM `" . $prefix. "sitemeta` WHERE meta_key = 'active_sitewide_plugins'";

		wptc_log($sql, '--------$sql--------');

		$current = $this->wpdb->get_var($sql);

		wptc_log($current, '--------$current--------');
		if (!empty($current)) {
			$current = unserialize($current);
		} else {
			$current = array();
		}
		wptc_log($current, '--------$current--------');

		$current_plugins = array_keys($current);

		wptc_log($current_plugins, '--------$current_plugins--------');

		if ( in_array( $plugin, $current_plugins ) ) {
			return false;
		}

		$current[$plugin] = time();

		wptc_log($current, '--------$current before sort--------');

		wptc_log($current, '--------$current sort--------');

		$sql = 'UPDATE `'. $prefix. "sitemeta` SET meta_value = '".serialize($current)."' WHERE meta_key = 'active_sitewide_plugins'";
		wptc_log($sql, '--------$sql--------');
		$result = $this->wpdb->query($sql);

		wptc_log($result, '--------$result--------');

		wptc_log($this->wpdb->last_error, '--------$this->wpdb->last_error--------');
	}

	public function mkdir_by_path($path, $recursive = true){
		if (empty($path)) {
			return false;
		}
		$path = wp_normalize_path($path);

		if (file_exists($path)) {
			return false;
		}

		$this->utils_base->createRecursiveFileSystemFolder($path);
	}

	public function check_timeout_iter_file($path, &$temp_counter, &$timeout_limit, &$qry, &$offset){
		// if (++$temp_counter < $timeout_limit) {
		// 	return false;
		// }

		// $temp_counter = 0;

		$break = is_wptc_timeout_cut();

		if (!$break) {
			return ;
		}

		if (!empty($qry)) {
			$this->insert_into_current_process($qry);
			$qry = '';
		}

		$this->current_iterator_table->update_iterator($path, $offset);

		if(is_any_ongoing_wptc_backup_process()){
			send_current_backup_response_to_server();
		} else {
			$this->die_with_json_encode(array("status" => "continue", 'msg' => 'Processing files ' . $path, "path" => $path, "offset" => $offset, 'percentage' => 75), 1);
		}

	}

	public function insert_into_current_process($qry){
		$sql = "insert into " . $this->wpdb->base_prefix . "wptc_current_process (file_path, status, file_hash) values $qry";
		$result = $this->wpdb->query($sql);
	}

	public function get_processing_files_count($type){
		$dir = $this->current_iterator_table->get_unfnished_folder();

		if (empty($dir)) {
			return false;
		}

		$copying_file = str_replace(WPTC_ABSPATH, '', $dir->name);

		switch ($type) {
			case 'internal_staging':
				$msg = 'Copying  - ';
				break;
			case 'backup':
				$msg = ' ';
				break;
			case 'restore':
				$msg = 'Preparing files to restore - ';
				break;
		}

		if(wptc_is_dir($copying_file) && !empty($dir->offset)){
			// return $msg . $copying_file . ' ('.$dir->offset.')';
			$folders_processed = substr($dir->offset, 0, strpos($dir->offset, '-'));
			$folders_processed = empty($folders_processed) ? '' : ' ( processed ' . $folders_processed . ' folders )';
			return $msg . $copying_file . $folders_processed;
		}

		return $msg . $copying_file;
	}

	public function fancytree_format($data, $type){
		$format_result = array();
		foreach ($data as $key => $item) {
			$format_result[] = array(
				'title' => $item['name'],
				'key' => $item['slug'],
				'preselected' => $item['selected'],
				'unselectable' => empty($item['unselectable']) ? false : $item['unselectable'],
			);
		}
		return $format_result;
	}

	public function get_all_plugins_data($specific = false, $attr = false){

		if (!function_exists('get_plugins')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		$plugins = array();
		if (!$specific) {
			return $all_plugins;
		}

		if ($attr === 'slug') {
			foreach ($all_plugins as $slug => $plugin) {
				$plugins[] = $slug;
			}
		}

		return $plugins;
	}

	public function get_all_themes_data($specific = false, $attr = false){
		if (!function_exists('wp_get_themes')) {
			include_once ABSPATH . 'wp-includes/theme.php';
		}

		$all_themes = wp_get_themes();
		$themes = array();
		if (!$specific) {
			return $all_themes;
		}

		if ($attr === 'slug') {
			foreach ($all_themes as $slug => $theme) {
				$themes[] = $slug;
			}
		}

		return $themes;
	}

	public function update_default_vulns_settings($fresh = false){
		$is_enabled = $this->config->get_option('is_autoupdate_vulns_settings_enabled');

		$settings['status'] = ($is_enabled || $fresh === true ) ? 'yes' : 'no';
		$settings['core']['status'] = true;
		$settings['plugins']['status'] = true;
		$settings['plugins']['excluded'] = array();
		$settings['themes']['status'] = true;
		$settings['themes']['excluded'] = array();

		$this->config->set_option('vulns_settings', serialize($settings));
	}

	public function update_staging_enable_admin_key($fresh = false){
		$current_setting = $this->config->get_option('internal_staging_disable_admin_login');
		$this->config->set_option('internal_staging_enable_admin_login', $current_setting);
		$this->config->delete_option('internal_staging_disable_admin_login');
	}

	public function make_this_fresh_site(){
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_activity_log`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_backup_names`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_backups`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_current_process`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_debug_log`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_options`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_included_files`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_included_tables`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_processed_iterator`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_processed_files`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_processed_restored_files`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_excluded_files`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_excluded_tables`");

		$this->set_fresh_install_flags();
		$this->die_with_json_encode(array('status' => 'success'));
	}

	public function make_this_original_site(){
		$this->refresh_cached_paths();

		$this->config->set_option('is_site_migrated', true);

		$app_id = $this->config->get_option('appID');

		$email = trim($this->config->get_option('main_account_email', true));

		$post_arr = array(
			'app_id' => $app_id,
			'email' => base64_encode(md5($email)),
		);

		$push_result = do_cron_call_wptc('replace-old-urls', $post_arr, 'POST');

		wptc_log($push_result, '--------$push_result--------');
		$this->config->set_option('admin_notices', false);

		//sync to service to make changes on new site
		$this->config->is_main_account_authorized( $email = null, $pwd = null, $ui_request = false, $send_sub_action = false );

		$this->die_with_json_encode(array('status' => 'success'));

	}

	public function refresh_cached_paths(){
		$this->config->delete_option('backup_db_path');

		//Used for staging purpose
		$this->config->delete_option('site_abspath');

		$this->config->choose_db_backup_path();
	}

	public function set_fresh_install_flags(){
		$this->config->set_option('database_version', WPTC_DATABASE_VERSION);
		$this->config->set_option('wptc_version', WPTC_VERSION);
		$this->config->set_option('activity_log_lazy_load_limit', WPTC_ACTIVITY_LOG_LAZY_LOAD_LIMIT);
		$this->config->set_option('backup_type_setting', 'SCHEDULE');
		$this->config->set_option('backup_before_update_setting', 'everytime');
		$this->config->set_option('revision_limit', WPTC_FALLBACK_REVISION_LIMIT_DAYS);
		$this->config->set_option('run_init_setup_bbu', true);
		WPTC_Base_Factory::get('Wptc_ExcludeOption')->insert_default_excluded_files();
		$this->set_user_to_access();
		$this->config->set_option('internal_staging_db_rows_copy_limit', WPTC_STAGING_DEFAULT_COPY_DB_ROWS_LIMIT);
		$this->config->set_option('internal_staging_file_copy_limit', WPTC_STAGING_DEFAULT_FILE_COPY_LIMIT);
		$this->config->set_option('dropbox_oauth_upgraded', true);
		$this->config->set_option('internal_staging_enable_admin_login', true);
		$this->config->set_option('backup_slot', 'daily');
		$this->config->set_option('user_excluded_extenstions', '.zip, .mp4, .mp3, .avi, .mov, .mpg, .pdf, .log, .DS_Store, .git, .gitignore, .gitmodules, .svn, .dropbox, .sass-cache');
		$this->config->set_option('user_excluded_files_more_than_size', 52428800); //50MB
		$this->config->set_option('update_prev_backups_1_14_10', true); //set it like it already done for new users
		$this->update_default_vulns_settings($fresh = true);
	}

	public function save_server_response($site_info){

		if (empty($site_info)) {
			return ;
		}

		unset($site_info->slot_info->raw_data);

		$this->config->process_subs_info_wptc($site_info);

		if(empty($site_info->subscription_features)){
			return ;
		}

		$this->config->reset_plans();

		$privileges_args = array();
		$privileged_feature = array();

		foreach ($site_info->subscription_features as $subscription) {
			$privileged_feature[$subscription->type][] = 'Wptc_' . ucfirst( $subscription->feature );
			$privileges_args['Wptc_' . ucfirst( $subscription->feature )] = !empty( $subscription->args ) ? $subscription->args : array();
		}

		//Remove on production
		$privileged_feature['pro'][] = 'Wptc_Restore_To_Staging';
		$privileges_args['Wptc_Restore_To_Staging'] = array();

		$this->config->set_option('privileges_args', json_encode($privileges_args));
		$this->config->set_option('privileges_wptc', json_encode($privileged_feature));

		do_action('update_white_labling_settings_wptc', $site_info);

		if (empty($site_info->disable)) {
			return ;
		}

		//get plans html from the service if site got disabled
		$this->config->is_main_account_authorized(null, null, false, false);
	}

	public function is_backup_request_timeout($return = false, $print_time = false) {
		global $wptc_ajax_start_time;

		if ((time() - $wptc_ajax_start_time) >= WPTC_TIMEOUT) {

			if ($return) return true;

			WPTC_Factory::get('logger')->log(__("Preparing for next call from server.", 'wptc'), 'backup_progress', getTcCookie('backupID'));
			send_current_backup_response_to_server();
		}

		if ($print_time) {
			wptc_log(time() - $wptc_ajax_start_time, '------------I still have time--------------------');
		}

		return false;
	}

	public function can_show_this_page(){

		include_once ( WPTC_PLUGIN_DIR . 'Views/wptc-options-helper.php' );
		$options_helper = new Wptc_Options_Helper();

		if( !$options_helper->get_is_user_logged_in() ||
			$options_helper->is_show_privilege_box() ||
			!WPTC_Factory::get('config')->get_option('wptc_server_connected') ||
			!(WPTC_Factory::get('config')->get_option('privileges_wptc')) ){
			wordpress_time_capsule_admin_menu_contents();
			return false;
		}

		return true;
	}

	public function get_issue_data($id) {

		if (empty($id)) {
			return array();
		}

		$specficlog = $this->wpdb->get_row('SELECT * FROM ' . $this->wpdb->base_prefix . 'wptc_activity_log WHERE id = ' . $id, OBJECT);

		if (!$specficlog) {
			return array();
		}

		if (empty($specficlog->action_id)) {
			return $specficlog->log_data;
		}

		$action_log = $this->wpdb->get_results('SELECT * FROM ' . $this->wpdb->base_prefix . 'wptc_activity_log WHERE action_id = ' . $specficlog->action_id, OBJECT);

		if (!count($action_log)) {
			return $specficlog->log_data;
		}

		foreach ($action_log as $all) {
			$report[] = $all->log_data;
		}

		return $report;
	}

	public function send_report(){

		if (empty($_REQUEST['data']) || empty($_REQUEST['data']['log_id'])) {
			$this->die_with_json_encode(array('error' => true));
		}

		$report_issue_data = $this->get_server_info();
		$report_issue_data['server']['browser'] = $_SERVER['HTTP_USER_AGENT'];
		$report_issue_data['server']['reportTime'] = time();

		$plugin_data['url'] = home_url();
		$plugin_data['main_account_email'] = $this->config->get_option('main_account_email');
		$plugin_data['appID'] = $this->config->get_option('appID');
		$plugin_data['wptc_version'] = $this->config->get_option('wptc_version');
		$plugin_data['wptc_database_version'] = $this->config->get_option('database_version');

		$issue_data = $this->get_issue_data($_REQUEST['data']['log_id']);
		$logs['issue']['issue_data'] = serialize($issue_data);
		$logs['issue']['plugin_info'] = serialize($plugin_data);
		$logs['issue']['server_info'] = serialize($report_issue_data);


		$final_log = serialize($logs);
		wptc_log($final_log,'--------------final log-------------');

		$post_arr = array(
			'type' => 'issue',
			'issue' => $final_log,
			'useremail' => $plugin_data['main_account_email'],
			'title' => $_REQUEST['data']['description'],
			'rand' => generate_random_string_wptc(),
			'name' => 'Admin',
		);

		$response = $this->make_request(WPTC_APSERVER_URL . "/report_issue/index.php", 'POST', $post_arr);

		if (!$response) {
			$this->die_with_json_encode(array('error' => true));
		}

		if ( strpos($response, 'insert_success') !== false ) {
			$this->die_with_json_encode(array('success' => true));
		}

		$this->die_with_json_encode(array('error' => true));
	}

	public function make_request($url, $type, $data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			WPTC_DEFAULT_CURL_CONTENT_TYPE,
		));

		$result = curl_exec($ch);

		wptc_log($result, "--------curl result report issue--------");

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlErr = curl_errno($ch);

		wptc_log($httpCode, '--------$httpCode--------');
		wptc_log($curlErr, '--------$curlErr--------');

		curl_close($ch);

		if (!empty($curlErr) || $httpCode != 200) {
			return false;
		}

		return $result;
	}

	public function truncate_activity_log(){
		if ($this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_activity_log`")) {
			$this->die_with_json_encode(array('success' => true));
		}

		$this->die_with_json_encode(array('error' => true));
	}

	public function convert_mb_to_bytes($size){
		$size = trim($size);
		return $size * pow( 1024, 2 );
	}

	public function convert_bytes_to_mb($size){
		$size = trim($size);
		return ( ($size / 1024 ) / 1024 );
	}

	private function init_exclude_option(){
		if ( !empty($this->exclude_option) ) {
			return ;
		}

		$this->exclude_option = WPTC_Base_Factory::get('Wptc_ExcludeOption');
	}

	public function is_bigger_than_allowed_file_size($file){

		$this->init_exclude_option();

		$allowed_size = $this->config->get_option('user_excluded_files_more_than_size');

		if (empty($allowed_size)) {
			return false;
		}

		if ( $this->exclude_option->is_included_file($file) ) {
			return false;
		}

		if (filesize($file) > $allowed_size) {
			return true;
		}

		return false;
	}

	public function get_meta_backup_tables($filter = false){

		$structure_tables = array(
			$this->wpdb->prefix . 'wptc_activity_log',
			$this->wpdb->prefix . 'wptc_auto_backup_record',
			$this->wpdb->prefix . 'wptc_current_process',
			$this->wpdb->prefix . 'wptc_debug_log',
			$this->wpdb->prefix . 'wptc_processed_iterator',
			$this->wpdb->prefix . 'wptc_processed_restored_files',
		);

		$full_tables = array(
			$this->wpdb->prefix . 'wptc_backups',
			$this->wpdb->prefix . 'wptc_backup_names',
			$this->wpdb->prefix . 'wptc_excluded_files',
			$this->wpdb->prefix . 'wptc_excluded_tables',
			$this->wpdb->prefix . 'wptc_included_files',
			$this->wpdb->prefix . 'wptc_included_tables',
			$this->wpdb->prefix . 'wptc_options',
			$this->wpdb->prefix . 'wptc_processed_files',
		);

		switch ($filter) {
			case 'structure':
				return $structure_tables;
			case 'full':
				return $full_tables;
			default:
				return array_merge($structure_tables, $full_tables);
		}

	}


	public function is_meta_table_excluded($table){

		$structure_tables = $this->get_meta_backup_tables($filer = 'structure');

		if (in_array( $table, $structure_tables) ) {
			return 'content_excluded';
		}

		$full_tables = $this->get_meta_backup_tables($filer = 'full');

		if (in_array( $table , $full_tables ) ) {
			return 'table_included';
		}

		return 'table_excluded';
	}

	public function get_wp_core_version(){

		if ($this->wp_version) {
			return $this->wp_version;
		}

		@include( ABSPATH . WPINC . '/version.php' );
		$this->wp_version = $wp_version;

		return $this->wp_version;
	}

	public function update_prev_backups(){
		if(!$this->config->get_option('update_prev_backups_1_14_10')){
			include_once ( WPTC_PLUGIN_DIR . 'updates/update_1_14_10.php' );
			new Wptc_Update_1_14_10($this, $this->wpdb, $this->config);
		}

	}
}