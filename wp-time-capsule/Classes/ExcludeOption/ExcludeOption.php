<?php

class Wptc_ExcludeOption extends Wptc_Exclude {
	protected $config;
	protected $logger;
	private $cron_server_curl;
	private $default_wp_folders;
	private $default_wp_files;
	private $db;
	private $default_exclude_files;
	private $processed_files;
	private $bulk_limit;
	private $default_wp_files_n_folders;
	private $excluded_files;
	private $included_files;
	private $excluded_tables;
	private $included_tables;
	private $max_table_size_allowed = 104857600; //100 MB
	private $max_file_size_allowed = 52428800; //50 MB
	private $key_recursive_seek;
	private $file_list;
	private $app_functions;
	private $analyze_files_response = array();
	private $skip_tables = array(
								'slim_stats',
								'statpress',
								'icl_languages_translations',
								'icl_string_positions',
								'icl_string_translations',
								'icl_strings',
								'redirection_logs',
								'Counterize',
								'Counterize_UserAgents',
								'Counterize_Referers',
								'adrotate_stats',
								'login_security_solution_fail',
								'wfHits',
								'wbz404_logs',
								'wbz404_redirects',
								'wp_wfFileMods',
								'tts_trafficstats',
								'tts_referrer_stats',
								'dmsguestbook',
								'relevanssi',
								'wponlinebackup_generations',
								'svisitor_stat',
								'simple_feed_stats',
								'itsec_log',
								'wp_rp_tags',
								'relevanssi_log',
								'blc_instances',
								'wysija_email_user_stat'
							);
	public function __construct() {
		$this->db = WPTC_Factory::db();
		$this->bulk_limit = 500;
		$this->processed_files = WPTC_Factory::get('processed-files');
		$this->default_exclude_files = get_dirs_to_exculde_wptc();
		$this->default_wp_folders = array(
						WPTC_RELATIVE_ABSPATH . 'wp-admin',
						WPTC_RELATIVE_ABSPATH . 'wp-includes',
						WPTC_RELATIVE_WP_CONTENT_DIR,
					);
		$this->default_wp_files = array(
						WPTC_RELATIVE_ABSPATH . 'favicon.ico',
						WPTC_RELATIVE_ABSPATH . 'index.php',
						WPTC_RELATIVE_ABSPATH . 'license.txt',
						WPTC_RELATIVE_ABSPATH . 'readme.html',
						WPTC_RELATIVE_ABSPATH . 'robots.txt',
						WPTC_RELATIVE_ABSPATH . 'sitemap.xml',
						WPTC_RELATIVE_ABSPATH . 'wp-activate.php',
						WPTC_RELATIVE_ABSPATH . 'wp-blog-header.php',
						WPTC_RELATIVE_ABSPATH . 'wp-comments-post.php',
						WPTC_RELATIVE_ABSPATH . 'wp-config-sample.php',
						WPTC_RELATIVE_ABSPATH . 'wp-config.php',
						WPTC_RELATIVE_ABSPATH . 'wp-cron.php',
						WPTC_RELATIVE_ABSPATH . 'wp-links-opml.php',
						WPTC_RELATIVE_ABSPATH . 'wp-load.php',
						WPTC_RELATIVE_ABSPATH . 'wp-login.php',
						WPTC_RELATIVE_ABSPATH . 'wp-mail.php',
						WPTC_RELATIVE_ABSPATH . 'wp-settings.php',
						WPTC_RELATIVE_ABSPATH . 'wp-signup.php',
						WPTC_RELATIVE_ABSPATH . 'wp-trackback.php',
						WPTC_RELATIVE_ABSPATH . 'wp-salt.php',//some people added this file in wp-config.php
						WPTC_RELATIVE_ABSPATH . 'xmlrpc.php',
						WPTC_RELATIVE_ABSPATH . '.htaccess',
						WPTC_RELATIVE_ABSPATH . 'google',//google analytics files
						WPTC_RELATIVE_ABSPATH . 'gd-config.php',//go daddy configuration file
						WPTC_RELATIVE_ABSPATH . 'wp',//including all wp files on root
						WPTC_RELATIVE_ABSPATH . '.user.ini',//User custom settings / WordFence Files
						WPTC_RELATIVE_ABSPATH . 'wordfence-waf.php',//WordFence Files
					);
		$this->force_exclude_folders = array(
						WPTC_RELATIVE_ABSPATH . 'wp-tcapsule-bridge',
		);
		$this->default_wp_files_n_folders = array_merge($this->default_wp_folders, $this->default_wp_files);
		$this->load_exc_inc_files();
		$this->load_exc_inc_tables();
		$this->config = WPTC_Base_Factory::get('Wptc_Exclude_Config');
		$this->file_list = WPTC_Factory::get('fileList');
		$this->app_functions = WPTC_Base_Factory::get('Wptc_App_Functions');
	}

	private function load_exc_inc_files(){
		$this->excluded_files = $this->get_exlcuded_files_list();
		$this->included_files = $this->get_included_files_list();
	}

	private function load_exc_inc_tables(){
		$this->excluded_tables = $this->get_exlcuded_tables_list();
		$this->included_tables = $this->get_included_tables_list();
	}

	public function insert_default_excluded_files(){
		$status = $this->config->get_option('insert_default_excluded_files');
		if ($status) {
			return false;
		}
		$files = $this->format_excluded_files($this->default_exclude_files);
		foreach ($files as $file) {
			$this->exclude_file_list($file, true);
		}
		$this->config->set_option('insert_default_excluded_files', true);
	}

	private function format_excluded_files($files){
		$selected_files = array();
		if (empty($files)) {
			return false;
		}
		foreach ($files as $file) {
			if (wptc_is_dir($file)) {
				$selected_files[] = array(
								"id" => NULL,
								"file" => $file,
								"isdir" => 1,
							);
			} else {
				$selected_files[] = array(
								"id" => NULL,
								"file" => $file,
								"isdir" => 0,
							);
			}
		}
		return $selected_files;
	}

	public function update_default_excluded_files_list(){
		$uploadDir = get_uploadDir();
		$upload_dir_path = wp_normalize_path($uploadDir['basedir']);

		$files_index = array(
			'1.5.3' => 'wptc_1_5_3',
			'1.8.0' => 'wptc_1_8_0',
			'1.8.2' => 'wptc_1_8_2',
			'1.9.0' => 'wptc_1_9_0',
			'1.9.4' => 'wptc_1_9_4',
			'1.11.1' => 'wptc_1_11_1',
			'1.14.0' => 'wptc_1_14_0',
			);

		$wptc_1_5_3 = array(
			WPTC_RELATIVE_WP_CONTENT_DIR . "/nfwlog",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/debug.log",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/wflogs",
			$uploadDir['basedir'] . "/siteorigin-widgets",
			$uploadDir['basedir'] . "/wp-hummingbird-cache",
			$uploadDir['basedir'] . "/wp-security-audit-log",
			$uploadDir['basedir'] . "/freshizer",
			$uploadDir['basedir'] . "/db-backup",
			$uploadDir['basedir'] . "/backupbuddy_backups",
			$uploadDir['basedir'] . "/vcf",
			$uploadDir['basedir'] . "/pb_backupbuddy",
			WPTC_RELATIVE_ABSPATH . "wp-admin/error_log",
			WPTC_RELATIVE_ABSPATH . "wp-admin/php_errorlog",
			);

		$wptc_1_8_0 = array(
			WPTC_RELATIVE_WP_CONTENT_DIR . "/DE_cl_dev_log_auto_update.txt",
			);

		$wptc_1_8_2 = array(
			WPTC_RELATIVE_WP_CONTENT_DIR . "/Dropbox_Backup",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/backup-db",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/updraft",
			$uploadDir['basedir'] . "/report-cache",
			);

		$wptc_1_9_0 = array(
			WPTC_RELATIVE_WP_CONTENT_DIR . "/w3tc-config",
			$uploadDir['basedir'] . "/ithemes-security",
			$uploadDir['basedir'] . "/cache",
			$uploadDir['basedir'] . "/et_temp",
			);

		$wptc_1_9_4 = array(
			WPTC_RELATIVE_WP_CONTENT_DIR . "/aiowps_backups",
			);

		$wptc_1_11_1 = array(
			$uploadDir['basedir']."/wptc_restore_logs",
			);

		$wptc_1_14_0 = array(
			WPTC_RELATIVE_WP_CONTENT_DIR . "/wptc-server-request-logs.txt",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/wptc-logs.txt",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/wptc-memory-peak.txt",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/wptc-memory-usage.txt",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/wptc-time-taken.txt",
			WPTC_RELATIVE_WP_CONTENT_DIR . "/wptc-cpu-usage.txt",
			);

		$prev_wptc_version =  $this->config->get_option('prev_installed_wptc_version');

		if (empty($prev_wptc_version)) {
			return false;
		}

		$required_files = array();
		foreach ($files_index as $key => $value) {
			if (version_compare($prev_wptc_version, $key, '<') && version_compare(WPTC_VERSION, $key, '>=')) {
				$required_files = array_merge($required_files, ${$files_index[$key]});
			}
		}
		return $required_files;
	}

	public function update_default_excluded_files(){
		$status = $this->config->get_option('update_default_excluded_files');
		if ($status) {
			return false;
		}
		$new_default_exclude_files = $this->update_default_excluded_files_list();
		if (empty($new_default_exclude_files)) {
			$this->config->set_option('update_default_excluded_files', true);
			return false;
		}
		$files = $this->format_excluded_files($new_default_exclude_files);
		foreach ($files as $file) {
			$this->exclude_file_list($file, true);
		}
		$this->config->set_option('update_default_excluded_files', true);
	}

	public function get_tables($exc_wp_tables = false) {
		$tables = $this->processed_files->get_all_tables();
		if ($exc_wp_tables && !$this->config->get_option('non_wp_tables_excluded')) {
			$this->exclude_non_wp_tabes($tables);
			$this->exclude_content_for_default_log_tables($tables);
			$this->load_exc_inc_tables();
			$this->config->set_option('non_wp_tables_excluded', true);
		}
		$tables_arr = array();
		wptc_log($tables, '---------------$tables-----------------');
		foreach ($tables as $table) {
			$table_status = $this->is_excluded_table($table);
			if ($table_status === 'table_included') {
				$temp = array(
					'title' => $table,
					'key' => $table,
					'content_excluded' => 0,
					'size' => $this->processed_files->get_table_size($table),
					'preselected' => true,
				);
			} else if ($table_status === 'content_excluded') {
				$temp = array(
					'title' => $table,
					'key' => $table,
					'content_excluded' => 1,
					'size' => $this->processed_files->get_table_size($table),
					'preselected' => true,
				);
			} else  {
				$temp = array(
					'title' => $table,
					'key' => $table,
					'size' => $this->processed_files->get_table_size($table),
					'preselected' => false,
				);
			}
			$temp['size_in_bytes'] = $this->processed_files->get_table_size($table, 0);
			$tables_arr[] = $temp;
		}
		die(json_encode($tables_arr));
	}

	public function get_root_files($exc_wp_files = false) {
		// $this->got_exclude_files(2);
		$path = get_tcsanitized_home_path();
		$result_obj = $this->get_files_by_path($path);
		if ($exc_wp_files && !$this->config->get_option('non_wp_files_excluded')) {
			$this->exclude_non_wp_files($result_obj);
			$this->load_exc_inc_files();
			$this->config->set_option('non_wp_files_excluded', true);
		}

		$result = $this->format_result_data($result_obj);
		die(json_encode($result));
	}

	public function update_default_files_n_tables(){
		$this->config->set_option('insert_default_excluded_files', false);

		$this->insert_default_excluded_files();

		// //files
		// $path = get_tcsanitized_home_path();
		// $result_obj = $this->get_files_by_path($path);
		// $this->exclude_non_wp_files($result_obj);
		// $this->load_exc_inc_files();

		// //tables
		// $tables = $this->processed_files->get_all_tables();
		// $this->exclude_non_wp_tabes($tables);
		// $this->load_exc_inc_tables();
	}

	private function exclude_non_wp_files($file_obj){
		$selected_files = array();
		foreach ($file_obj as $Ofiles) {
			$file_path = $Ofiles->getPathname();
			$file_name = basename($file_path);
			if ($file_name == '.' || $file_name == '..') {
				continue;
			}
			if(!$this->is_wp_file($file_path)){
				$isdir = wptc_is_dir($file_path);
				$this->exclude_file_list(array('file'=> $file_path, 'isdir' => $isdir ), true);
			}
		}
	}

	private function exclude_non_wp_tabes($tables){
		foreach ($tables as $table) {
			if (!$this->is_wp_table($table)) {
				$this->exclude_table_list(array('file' => $table), true);
			}
		}
	}

	public function get_files_by_key($path) {
		$result_obj = $this->get_files_by_path($path);
		$result = $this->format_result_data($result_obj);
		die(json_encode($result));
	}

	public function get_files_by_path($path, $deep = 0){
		$path = rtrim($path, '/');
		$source = realpath($path);
		$obj = null;
		$basename = basename($path);
		if ($basename == '..' || $basename == '.') {
			return false;
		}

		if (empty($source)) {
			return false;
		}

		if (!is_readable($source)) {
			return false;
		}

		if(empty($source)){
			return array();
		}

		if($deep){
				$obj =  new RecursiveIteratorIterator(
			  new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD
			);
		}else {
			$obj =  new RecursiveIteratorIterator(
			  new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::CATCH_GET_CHILD
			);
		}

		return $obj;
	}

	private function format_result_data($file_obj){
		$files_arr	= array();
		if (empty($file_obj)) {
			return false;
		}
		foreach ($file_obj as $Ofiles) {

			$file_path = $Ofiles->getPathname();
			$file_name = basename($file_path);

			if ($file_name == '.' || $file_name == '..') {
				continue;
			}

			if (!$Ofiles->isReadable()) {
				continue;
			}

			$file_size = $Ofiles->getSize();

			$temp = array(
					'title' => basename($file_name),
					'key' => $file_path,
					'size' => $this->processed_files->convert_bytes_to_hr_format($file_size),
				);

			$is_dir = wptc_is_dir($file_path);


			if ($is_dir) {
				$is_excluded = $this->is_excluded_file($file_path, true);
				$temp['folder'] = true;
				$temp['lazy'] = true;
				$temp['size'] = '';
			} else {
				$is_excluded = $this->is_excluded_file($file_path, false);

				if (!$is_excluded) {
					$is_excluded = ( $this->file_list->in_ignore_list($file_path) && !$this->is_included_file($file_path) ) ? true : false;
				}

				if (!$is_excluded) {
					$is_excluded = $this->app_functions->is_bigger_than_allowed_file_size($file_path) ? true : false;
				}

				$temp['false'] = false;
				$temp['folder'] = false;
				$temp['size_in_bytes'] = $Ofiles->getSize();
			}

			if($is_excluded){
				$temp['partial'] = false;
				$temp['preselected'] = false;
			} else {
				$temp['preselected'] = true;
			}

			$files_arr[] = $temp;
		}

		$this->sort_by_folders($files_arr);

		return $files_arr;
	}

	private function sort_by_folders(&$files_arr) {
		if (empty($files_arr) || !is_array($files_arr)) {
			return false;
		}
		foreach ($files_arr as $key => $row) {
			$volume[$key]  = $row['folder'];
		}
		array_multisort($volume, SORT_DESC, $files_arr);
	}

	public function exclude_file_list($data, $do_not_die = false){

		$data = stripslashes_deep($data);

		if (empty($data['file']) || WPTC_ABSPATH ===  wptc_add_trailing_slash($data['file'])) {
			wptc_log(array(), '--------Matches abspath--------');
			return false;
		}

		$data['file'] = wp_normalize_path($data['file']);
		if ($data['isdir']) {
			$this->remove_include_files($data['file'], 1);
			$this->remove_exclude_files($data['file'], 1);
		} else {
			$this->remove_exclude_files($data['file']);
			$this->remove_include_files($data['file']);
		}

		wptc_remove_abspath($data['file']);

		$result = $this->db->insert("{$this->db->base_prefix}wptc_excluded_files", $data);

		if($do_not_die){
			return true;
		}

		if ($result) {
			die_with_json_encode(array('status' => 'success'));
		}
		die_with_json_encode(array('status' => 'error'));
	}

	private function remove_include_files($file, $force = false){
		if (empty($file)) {
			return false;
		}
		if ($force) {
			$re_sql = $this->db->query( $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_files WHERE file LIKE '%%%s%%'", $file) );

			wptc_remove_abspath($file);
			$re_sql = $this->db->query( $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_files WHERE file LIKE '%%%s%%'", $file) );

		} else{
			$re_sql = $this->db->query( $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_files WHERE file = %s", $file) );

			wptc_remove_abspath($file);
			$re_sql = $this->db->query( $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_files WHERE file = %s", $file) );
		}

		// $result = $this->db->query($re_sql);
	}

	public function include_file_list($data, $force_insert = false){

		$data = stripslashes_deep($data);

		if (empty($data['file'])) {
			return false;
		}

		$data['file'] = wp_normalize_path($data['file']);

		if ($data['isdir']) {
			$this->remove_exclude_files($data['file'], 1);
			$this->remove_include_files($data['file'], 1);
		} else {
			$this->remove_include_files($data['file']);
			$this->remove_exclude_files($data['file']);
		}

		if ( $this->is_wp_file($data['file'] ) && !$this->file_list->in_ignore_list( $data['file'] ) && !$this->app_functions->is_bigger_than_allowed_file_size( $data['file'] ) ) {
			wptc_log(array(), '---------------wordpress folder cannot be inserted ----------------');
			die_with_json_encode(array('status' => 'success'));
			return false;
		}

		wptc_remove_abspath($data['file']);

		$result = $this->db->insert("{$this->db->base_prefix}wptc_included_files", $data);

		if ($result) {
			die_with_json_encode(array('status' => 'success'));
		}
		die_with_json_encode(array('status' => 'error'));
	}

	private function remove_exclude_files($file, $force = false){
		if (empty($file)) {
			return false;
		}

		if ($force) {
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_files WHERE file LIKE '%%%s%%'", $file);

			wptc_remove_abspath($file);
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_files WHERE file LIKE '%%%s%%'", $file);
		} else{
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_files WHERE file = %s", $file);

			wptc_remove_abspath($file);
			$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_files WHERE file = %s", $file);
		}
		$result = $this->db->query($re_sql);
	}

	private function is_wp_file($file){
		if (empty($file)) {
			return false;
		}
		$file = wp_normalize_path($file);
		foreach ($this->default_wp_files_n_folders as $path) {

			wptc_add_abspath($path);

			if(strpos($file, $path) !== false){
				return true;
			}
		}
		return false;
	}

	public function is_excluded_file($file, $is_dir = false){
		if (empty($file)) {
			return true;
		}

		if( !$is_dir && $this->file_list->in_ignore_list( $file ) && !$this->is_included_file( $file ) ) {
			wptc_log($file, '---------------skip, file in ignore list-----------------');
			return true;
		}

		$file = wp_normalize_path($file);

		if ($this->froce_exclude_files($file)) {
			return true;
		}

		$found = false;
		if ($this->is_wp_file($file)) {
			return $this->exclude_file_check_deep($file);
		}
		if (!$this->is_included_file($file)) {
			return true;
		} else {
			return $this->exclude_file_check_deep($file);
		}
	}

	private function exclude_file_check_deep($file){
		foreach ($this->excluded_files as $value) {
			$value = str_replace('(', '-', $value);
			$value = str_replace(')', '-', $value);
			$file = str_replace('(', '-', $file);
			$file = str_replace(')', '-', $file);
			if(strpos($file.'/', $value.'/') === 0){
				return true;
			}
		}
		return false;
	}

	private function get_exlcuded_files_list(){
		$raw_data = $this->db->get_results("SELECT file FROM {$this->db->base_prefix}wptc_excluded_files", ARRAY_N);

		if (empty($raw_data)) {
			return array();
		}

		$result = array();

		foreach ($raw_data as $value) {
			wptc_add_abspath($value[0]);
			$result[] = $value[0];
		}
		return empty($result) ? array() : $result;
	}

	private function get_included_files_list(){
		$raw_data = $this->db->get_results("SELECT file FROM {$this->db->base_prefix}wptc_included_files", ARRAY_N);

		if (empty($raw_data)) {
			return array();
		}

		$result = array();

		foreach ($raw_data as $value) {
			wptc_add_abspath($value[0]);
			$result[] = $value[0];
		}
		return empty($result) ? array() : $result;
	}

	public function is_included_file($file, $is_dir = false){
		$found = false;
		$file = wp_normalize_path($file);
		foreach ($this->included_files as $value) {
			$value = str_replace('(', '-', $value);
			$value = str_replace(')', '-', $value);
			$file = str_replace('(', '-', $file);
			$file = str_replace(')', '-', $file);
			if(strpos($file.'/', $value.'/') === 0){
				$found = true;
				break;
			}
		}
		return $found;
	}

	private function is_included_file_deep($file, $is_dir = false){
		$found = false;
		foreach ($this->included_files as $value) {
			if ($value === $file) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	//table related functions
	public function exclude_table_list($data, $do_not_die = false){
		if (empty($data['file'])) {
			return false;
		}

		$this->remove_exclude_table($data['file']);
		$this->remove_include_table($data['file']);

		$table_arr['id'] = NULL;
		$table_arr['table_name'] = $data['file'];
		$result = $this->db->insert("{$this->db->base_prefix}wptc_excluded_tables", $table_arr);
		if ($do_not_die) {
			return false;
		}
		if ($result) {
			die_with_json_encode(array('status' => 'success'));
		}
		die_with_json_encode(array('status' => 'error'));
	}

	private function remove_include_table($table, $force = false){
		if (empty($table)) {
			return false;
		}
		$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_included_tables WHERE table_name = %s", $table);
		$result = $this->db->query($re_sql);
	}

	public function include_table_list($data){
		if (empty($data['file'])) {
			return false;
		}
		$this->remove_exclude_table($data['file']);
		$this->remove_include_table($data['file']);
		if ($this->is_wp_table($data['file'])) {
			wptc_log($data['file'], '---------------Wordpress table so cannot be inserted-----------------');
			die_with_json_encode(array('status' => 'success'));
		}
		$table_arr['id'] = NULL;
		$table_arr['table_name'] = $data['file'];
		$table_arr['backup_structure_only'] = 0;

		$result = $this->db->insert("{$this->db->base_prefix}wptc_included_tables", $table_arr);
		if ($result) {
			die_with_json_encode(array('status' => 'success'));
		}
		die_with_json_encode(array('status' => 'error'));
	}

	public function include_table_structure_only($data, $do_not_die = false){

		if (empty($data['file'])) {
			return false;
		}

		$this->remove_exclude_table($data['file']);
		$this->remove_include_table($data['file']);

		$table_arr['id'] = NULL;
		$table_arr['table_name'] = $data['file'];
		$table_arr['backup_structure_only'] = 1;
		$result = $this->db->insert("{$this->db->base_prefix}wptc_included_tables", $table_arr);

		if ($do_not_die) {
			return ;
		}

		if ($result) {
			die_with_json_encode(array('status' => 'success'));
		}

		die_with_json_encode(array('status' => 'error'));
	}

	private function remove_exclude_table($table, $force = false){
		if (empty($table)) {
			return false;
		}

		$re_sql = $this->db->prepare("DELETE FROM {$this->db->base_prefix}wptc_excluded_tables WHERE table_name = %s", $table);
		$result = $this->db->query($re_sql);
	}

	private function is_wp_table($table){
		if (preg_match('#^'.$this->db->base_prefix.'#', $table) === 1) {
			return true;
		}
		return false;
	}

	private function get_exlcuded_tables_list(){
		$raw_data = $this->db->get_results("SELECT table_name FROM {$this->db->base_prefix}wptc_excluded_tables", ARRAY_N);
		if (empty($raw_data)) {
			return array();
		}
		$result = array();
		foreach ($raw_data as $value) {
			$result[] = $value[0];
		}
		return empty($result) ? array() : $result;
	}

	private function get_included_tables_list(){
		$results = $this->db->get_results("SELECT table_name, backup_structure_only FROM {$this->db->base_prefix}wptc_included_tables");

		if (empty($results[0])) {
			return array();
		}

		$response = array();
		$counter = 0;

		foreach ($results as $table_meta) {
			$counter++;
			$response[$counter]['table_name'] = $table_meta->table_name;
			$response[$counter]['backup_structure_only'] = $table_meta->backup_structure_only;
		}

		return empty($response) ? array() : $response;
	}

	public function is_excluded_table($table){
		if (empty($table)) {
			return 'table_excluded';
		}

		if (wptc_is_meta_data_backup()) {
			return $this->app_functions->is_meta_table_excluded($table);
		}

		$is_wp_table = false;

		if($this->is_wp_table($table) ){
			if($this->exclude_table_check_deep($table)){
				return 'table_excluded';
			}

			$is_wp_table = true;
		}

		return $this->is_included_table($table, $is_wp_table);
	}

	private function exclude_table_check_deep($table){
		foreach ($this->excluded_tables as $value) {
			if (preg_match('#^'.$value.'#', $table) === 1 ) {
				return true;
			}
		}

		return false;
	}

	private function is_included_table($table, $is_wp_table){
		foreach ($this->included_tables as $table_meta) {
			if (preg_match('#^'.$table_meta['table_name'].'#', $table) === 1) {
				return $table_meta['backup_structure_only'] == 1 ? 'content_excluded' : 'table_included';
			}
		}
		return $is_wp_table === true ? 'table_included' : 'table_excluded';
	}

	public function update_1_14_0(){
		$this->update_1_14_0_replace_path_to_relative('wptc_excluded_files');
		$this->update_1_14_0_replace_path_to_relative('wptc_included_files');
	}

	public function update_1_14_0_replace_path_to_relative($table){

		$result = $this->db->get_results("SELECT * FROM {$this->db->base_prefix}" . $table . "");

		$query = '';

		foreach ($result as $value) {
			$query .= empty($query) ? "(" : ",(" ;
			$file = wptc_remove_abspath( $value->file , false);

			if ( empty($file) || $file == WPTC_RELATIVE_ABSPATH) {
				continue;
			}

			$query .= "NULL, '" . $file . "', " . $value->isdir . ")";
		}

		wptc_log($query, '--------$query--------');

		if (empty($query)) {
			return false;
		}

		$this->db->query("TRUNCATE TABLE " . $this->db->base_prefix . $table );

		$result = $this->db->query("INSERT INTO " . $this->db->base_prefix . $table . " (id, file, isdir) VALUES $query");

		wptc_log($result, '--------$result--------');
	}

	private function froce_exclude_files($file){
		if (empty($file)) {
			return false;
		}

		$file = wp_normalize_path($file);

		foreach ($this->force_exclude_folders as $path) {

			wptc_add_abspath($path);

			if(strpos($file, $path) !== false){
				return true;
			}
		}

		return false;
	}

	public function analyze_inc_exc(){
		$this->app_functions->set_start_time();

		// $this->analyze_files();
		// $this->config->set_option('suggest_files_offset', false);

		$excluded_tables = $this->analyze_tables();

		die_with_json_encode( array('status' => 'completed', 'files' => $this->analyze_files_response, 'tables' => $excluded_tables));
	}

	public function analyze_tables(){
		$tables = $this->processed_files->get_all_tables();
		$exclude_tables = array();
		$counter = 0;
		foreach ($tables as $table) {
			$table_status = $this->is_excluded_table($table);
			if ($table_status !== 'table_included') {
				continue;
			}

			$size = $this->processed_files->get_table_size($table, false);

			if ($size < $this->max_table_size_allowed) {
				continue;
			}

			$exclude_tables[$counter]['title'] = $table;
			$exclude_tables[$counter]['key'] = $table;
			$exclude_tables[$counter]['size_in_bytes'] = $size;
			$exclude_tables[$counter]['size'] = $this->processed_files->convert_bytes_to_hr_format($size);
			$exclude_tables[$counter]['preselected'] = true;
			$counter++;
		}

		return $exclude_tables;
	}

	private function is_log_table($table){
		foreach ($this->skip_tables as $skip_table) {
			if (stripos($table, $skip_table) !== false) {
				return true;
			}
		}

		return false;
	}

	public function analyze_files(){
		$seekable_iterator = new WPTC_Seek_Iterator();
		$iterator = $seekable_iterator->get_seekable_files_obj(WPTC_RELATIVE_ABSPATH);

		$offset = $this->config->get_option('suggest_files_offset');
		$offset = empty($offset) ? false : $offset;

		wptc_log($offset, '---------------$offset-----------------');

		$this->key_recursive_seek = empty($offset) ? array() : explode('-', $offset);

		$this->recursive_iterator($iterator, false);
	}

	public function recursive_iterator($iterator, $key_recursive) {

		$this->seek_offset($iterator);

		while ($iterator->valid()) {

			//Forming current path from iterator
			$recursive_path = $iterator->getPathname();

			//Mapping keys
			$key = ($key_recursive !== false ) ? $key_recursive . '-' . $iterator->key() : $iterator->key() ;

			//Do recursive iterator if its a dir
			if (!$iterator->isDot() && $iterator->isReadable() && $iterator->isDir() ) {

				//create new object for new dir
				$sub_iterator = new DirectoryIterator($recursive_path);

				$this->recursive_iterator($sub_iterator, $key);
			}

			//Ignore dots paths
			if(!$iterator->isDot()){
				$this->process_file( $iterator, $key );
			}

			//move to next file
			$iterator->next();
		}
	}

	private function seek_offset(&$iterator){

		if(!count($this->key_recursive_seek)){
			return false;
		}

		//Moving satelite into position.
		$iterator->seek($this->key_recursive_seek[0]);

		//remove positions from the array after moved satelite
		unset($this->key_recursive_seek[0]);

		//reset array index
		$this->key_recursive_seek = array_values($this->key_recursive_seek);

	}

	private function process_file($iterator, $key){

		if(is_wptc_timeout_cut()){
			$this->config->set_option('suggest_files_offset', $key);
			die_with_json_encode( array('status' => 'continue', 'files' => $this->analyze_files_response) );
		}

		if (!$iterator->isReadable()) {
			return ;
		}

		$file = $iterator->getPathname();

		if ($this->is_skip($file)) {
			return ;
		}


		$size = $iterator->getSize();
		// $extension = $iterator->getExtension();

		if ($size < $this->max_file_size_allowed) {
			return ;
		}

		$suggested_file['title'] = wptc_remove_abspath($file, false);
		$suggested_file['key'] = $file;
		$suggested_file['size_in_bytes'] = $size;
		$suggested_file['size'] = $this->processed_files->convert_bytes_to_hr_format($size);
		$suggested_file['preselected'] = true;
		$this->analyze_files_response[] = $suggested_file;
	}

	// private function is_extension_allowed($file, $extension){
	// 	$arr = array('mp4', 'mp3', 'avi', 'zip', 'log');
	// 	if (in_array($extension, $arr) === true) {
	// 		return false;
	// 	}

	// 	return true;
	// }

	private function is_skip($file){

		$basename = basename($file);

		if ($basename == '.' || $basename == '..') {
			return true;
		}

		if (!is_readable($file)) {
			return true;
		}

		if(wptc_is_dir($file)){
			return true;
		}

		if (is_wptc_file($file)) {
			return true;
		}

		//always include backup and backup-meta files
		if ( strpos($file, WPTC_WP_CONTENT_DIR) !== false && ( strpos($file, 'backup.sql') !== false || strpos($file, 'meta-data') !== false ) ) {
			return true;
		}

		if ($this->is_excluded_file($file)) {
			return true;
		}

		if (strpos($file, 'wptc_saved_queries.sql') !== false) {
			return true;
		}

		if (strpos($file, 'wptcrquery') !== false) {
			return true;
		}

		return false;
	}

	public function exclude_all_suggested_items($request){
		wptc_log(func_get_args(), __FUNCTION__);
		if (empty($request['data'])) {
			die_with_json_encode( array('status' => 'success' ) );
		}

		if (!empty($request['data']['tables']) || !is_array($request['data']['tables'])) {
			$query = '';
			foreach ($request['data']['tables'] as $table) {
				$query .= empty($query) ? "(" : ",(" ;
				$query .= $this->wpdb->prepare("NULL, %s, '1')", $table);
				$this->remove_exclude_table($table);
				$this->remove_include_table($table);
			}
			if (!empty($query)) {
				$query = "insert into " . $this->db->base_prefix . "wptc_included_tables (id, table_name, backup_structure_only) values $query";
				$this->db->query($query);
				wptc_log($query, '---------------$query-----------------');
			}
		}


		if (empty($request['data']['files']) || !is_array($request['data']['files'])) {
			die_with_json_encode( array('status' => 'success' ) );
		}

		$query = '';
		foreach ($request['data']['files'] as $file) {
			$query .= empty($query) ? "(" : ",(" ;
			$query .= $this->wpdb->prepare("NULL, %s, '0')",  wptc_remove_abspath($file));
			$this->remove_exclude_files($file);
			$this->remove_include_files($file);
		}

		if (empty($query)) {
			die_with_json_encode( array('status' => 'success' ) );
		}

		$query = "insert into " . $this->db->base_prefix . "wptc_excluded_files (id, file, isdir) values $query";
		$this->db->query($query);
		die_with_json_encode( array('status' => 'success' ) );
	}

	public function get_all_excluded_files(){
		$files = $this->get_exlcuded_files_list();

		if (empty($files)) {
			die_with_json_encode( array('status' => 'success', 'files' => array() ) );
		}

		$analyze_files_response = array();

		foreach ($files as $file) {

			if (!file_exists($file)) {
				continue;
			}

			$size = is_readable($file) ? filesize($file) : '-' ;

			$suggested_file['title'] = wptc_remove_abspath($file);
			$suggested_file['key'] 	= wptc_add_abspath($file);;
			$suggested_file['size_in_bytes'] = $size;
			$suggested_file['size'] = is_numeric($size) ? $this->processed_files->convert_bytes_to_hr_format($size) : $size;
			$suggested_file['preselected'] = false;
			$analyze_files_response[] = $suggested_file;
		}

		die_with_json_encode( array('status' => 'success', 'files' => $analyze_files_response) );
	}

	public function exclude_content_for_default_log_tables($tables = false){

		if($this->config->get_option('exclude_content_for_default_log_tables')){
			return ;
		}

		if (empty($tables)) {
			$tables = $this->processed_files->get_all_tables();
		}

		if (empty($tables)) {
			return $this->config->set_option('exclude_content_for_default_log_tables', true);
		}

		$request = array();

		foreach ($tables as $table) {
			if(!$this->is_log_table($table)){
				continue;
			}

			$request['file'] = $table;

			$this->include_table_structure_only($request, $do_not_die = true);
		}

		$this->config->set_option('exclude_content_for_default_log_tables', true);
	}

	public function get_user_excluded_files_more_than_size(){
		$size = $this->config->get_option('user_excluded_files_more_than_size');

		if (empty($size)) {
			return ;
		}

		return $this->app_functions->convert_bytes_to_mb($size);
	}

	public function save_settings($data){

		if (empty($data)) {
			return ;
		}

		if (!empty($data['user_excluded_extenstions'])) {
			$this->config->set_option('user_excluded_extenstions', $data['user_excluded_extenstions']);
		} else {
			$this->config->set_option('user_excluded_extenstions', false);
		}

		if (!empty($data['user_excluded_extenstions'])) {
			$size = $this->app_functions->convert_mb_to_bytes($data['user_excluded_files_more_than_size']);
		} else {
			$size = false;
		}

		$this->config->set_option('user_excluded_files_more_than_size', $size);
	}
}