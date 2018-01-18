<?php

class Wptc_Auto_Backup extends WPTC_Privileges {
	private $config,
			$db,
			$extra_mins,
			$auto_backup_slots;

	public function __construct(){
		$this->db = WPTC_Factory::db();
		$this->extra_mins = 55;
		$this->config = WPTC_Pro_Factory::get('Wptc_Auto_Backup_Config');
		$this->auto_backup_slots = array(
			'every_12_hours' => array(
								'name' => 'Every 12h',
								'interval_sec' => 43200, // 60 * 60 * 12
								),
			'every_6_hours' => array(
								'name' => 'Every 6h',
								'interval_sec' => 360, // 60 * 60 * 6
								),
			'every_1_hour' => array(
								'name' => 'Every 1h',
								'interval_sec' => 3600, // 60 * 60 * 1
								),
			);
	}

	public function init() {
		if ($this->is_privileged_feature(get_class($this)) && $this->is_switch_on()) {
			$supposed_hooks_class = get_class($this) . '_Hooks';
			WPTC_Pro_Factory::get($supposed_hooks_class)->register_hooks();
		}
	}

	private function is_switch_on() {
		return true;
	}

	public function update_handler($data) {
	}

	public function update_handler_filters($data, $dets) {
		return $data;
	}

	public function install_handler_filters($data, $dets) {
		return $data;
	}

	public function upload_handler($data) {
	}

	public function upload_handler_filters($data) {

		//we have improved iterator faster in v1.14.0 so even real time no need to log the changed paths in table
		return $data;

		$files_arr = array();

		$type = $this->get_auto_backup_upload_type($data['type']);
		$file = dirname($data['file']);
		$files_arr[] = $file;

		wptc_log($file, "--------upload_handler_filters file--------");

		if (empty($file) || empty($files_arr)) {
			return $data;
		}

		foreach ($files_arr as $k => $v) {
			$this->insert_auto_backup_record_db($type, $v);
		}

		return $data;
	}

	public function plugin_theme_install_update_handler($updated_data, $options) {
		if (empty($options) || empty($options['action']) || empty($options['type'])) {
			return true;
		}

		wptc_log($options, "--------options--------");

		$files_arr = array();
		$is_bulk = false;

		if (isset($options['bulk']) && $options['bulk'] === true) {
			$is_bulk = true;
		}

		if ($options['action'] == 'install') {
			$type = $this->get_auto_backup_install_type($options['type']);

			if ($options['type'] == 'core') {
				$file = get_tcsanitized_home_path();
			} else {
				$file = $this->get_file_from_updated_data($updated_data);
			}
			$files_arr[] = $file;
		} else if ($options['action'] == 'update') {
			$type = $this->get_auto_backup_update_type($options['type']);

			if (!empty($options['plugins'])) {
				$files_arr = $this->get_file_arr_from_options($options['plugins'], 'plugins');
			} else if (!empty($options['themes'])) {
				$files_arr = $this->get_file_arr_from_options($options['themes'], 'themes');
			} else if ($options['type'] == 'core') {
				$files_arr[] = get_tcsanitized_home_path();
			} else {
				$file = $this->get_file_from_updated_data($updated_data);
				$files_arr[] = $file;
			}
		}

		wptc_log($files_arr, "--------files_arr--------");

		if (empty($files_arr)) {
			return true;
		}

		foreach ($files_arr as $k => $v) {
			$this->insert_auto_backup_record_db($type, $v);
		}
	}

	private function get_file_from_updated_data($updated_data) {
		$updated_data = get_object_vars($updated_data);

		if (!empty($updated_data) && !empty($updated_data['result']) && !empty($updated_data['result']['destination'])) {
			$file = $updated_data['result']['destination'];
			return $file;
		}
		return false;
	}

	private function get_file_arr_from_options($arr, $bulk_type) {

		$plugin_theme_slugs = array();

		if ($bulk_type == 'plugins') {
			foreach ($arr as $k => $v) {
				$temp = WPTC_PLUGIN_DIR . $v;
				wptc_log($temp, "--------temp--------");
				$plugin_theme_slugs[] = dirname($temp);
			}
		} elseif ($bulk_type == 'themes') {
			foreach ($arr as $k => $v) {
				$temp = WPTC_WP_CONTENT_DIR . '/' . 'themes' . '/' . $v;
				$plugin_theme_slugs[] = $temp;
			}
		}

		return $plugin_theme_slugs;

	}

	public function edit_handler_options_table($option_name, $old, $new) {

		if ($option_name != 'recently_edited') {
			return false;
		}

		wptc_log($option_name, '---------Plugin edited------------');
		$type = 'file-edit';
		$file = dirname($new[0]);
		wptc_log($file, "------edit_handler_options_table--file--------");
		$this->insert_auto_backup_record_db($type, $file);
	}

	private function get_auto_backup_install_type($class_name, $is_bulk = false) {
		$default_types 	= array(
			'upload' 	=> 'upload',
			'plugin' 	=> 'plugin-install',
			'theme' 	=> 'theme-install',
			'core' 		=> 'core-install',
			'other' 	=> 'other-install',
		);

		if (!empty($class_name) && !empty($default_types[$class_name])) {
			$to_return = $default_types[$class_name];
			return $to_return;
		}

		return $default_types['other'];

	}

	private function get_auto_backup_update_type($class_name, $is_bulk = false) {
		$default_types 	= array(
			'upload' 	=> 'upload',
			'plugin' 	=> 'plugin-update',
			'theme' 	=> 'theme-update',
			'translation' => 'translation-update',
			'core' 		=> 'core-update',
			'other' 	=> 'other-update',
		);

		if (empty($class_name) || empty($default_types[$class_name])) {
			return $default_types['other'];
		}

		$to_return = $default_types[$class_name];

		if ($is_bulk) {
			$to_return = 'bulk-' . $to_return;
		}

		return $to_return;

	}

	private function get_auto_backup_upload_type($class_name = null) {

		if (strripos($class_name, 'video')) {
			$class_name = 'video';
		} else if (strripos($class_name, 'image')) {
			$class_name = 'image';
		}

		$default_types = array(
			'image' => 'img-upload',
			'video' => 'video-upload',
			'other' => 'other-upload',
		);

		if (!empty($class_name) && !empty($default_types[$class_name])) {
			return $default_types[$class_name];
		}

		return $default_types['other'];
	}

	public function append_auto_update_history_db() {

		$old_data = $this->config->get_option('auto_update_history');

		if (empty($old_data)) {
			$old_data = array();
		} else {
			$old_data = unserialize($old_data);
		}

		$last_backup_time = $this->config->get_option('last_backup_time');

		if (!empty($last_backup_time)) {
			$old_data[$last_backup_time] = 1;
		}

		$this->config->set_option('auto_update_history', unserialize($old_data));
	}

	public function get_auto_update_history_db() {

		$old_data = $this->config->get_option('auto_update_history');

		if (empty($old_data)) {
			$old_data = array();
		} else {
			$old_data = unserialize($old_data);
		}

		return $old_data;

	}

	private function insert_auto_backup_record_db($type, $file = null, $backup_status = false) {

		$file = wp_normalize_path($file);
		if (empty($file)) {
			return true;
		}

		$is_already_recorded = $this->is_auto_backup_already_exists_db($file);
		if (!empty($is_already_recorded)) {
			return true;
		}

		$last_backup_time = WPTC_Factory::get('config')->get_option('last_backup_time');

		if (empty($last_backup_time)) {
			wptc_log(array(), '--------Last backup time is empty we cannot add--------');
			return false;
		}

		$file = wptc_remove_trailing_slash($file);

		$data = array(
			'timestamp' => time(),
			'type' 		=> $type, //['upload','plugin-update','theme-update','core-update','other-update','bulk-plugin-update','bulk-theme-update','plugin-install','theme-install','core-install', 'other-install', 'file-edit', 'img-upload'],
			'file' 		=> wptc_remove_abspath( $file ),
			'backup_status' => 'noted', //['noted','queued','backed_up']
			'prev_backup_id' => $last_backup_time,
		);

		$this_insert_result = $this->db->insert("{$this->db->base_prefix}wptc_auto_backup_record", $data);
	}

	private function is_auto_backup_already_exists_db($file) {

		$prepared_query = $this->db->prepare("SELECT ID FROM " . $this->db->base_prefix . "wptc_auto_backup_record WHERE backup_status = 'noted' AND file = '%s'", $file);

		wptc_log($prepared_query, "--------prepared_query-is_auto_backup_already_exists_db-------");

		$result = $this->db->get_results($prepared_query);

		wptc_log($result, "--------is_auto_backup_already_exists_db--------");

		if ($result === false || empty($result)) {
			wptc_log(array(), "--------error--------");
			return false;
		}
		return true;
	}

	public function update_auto_backup_record_db() {

		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		return ;

		$cur_backup_id = getTcCookie('backupID');
		$data['backup_status'] = 'queued';
		$data['cur_backup_id'] = $cur_backup_id;

		$query = $this->db->prepare("UPDATE {$this->db->base_prefix}wptc_auto_backup_record SET backup_status = 'queued', cur_backup_id = %f WHERE backup_status = 'noted' AND cur_backup_id <= %f ", $cur_backup_id, $cur_backup_id);

		$update_result = $this->db->query($query);
		if ($update_result === false) {
			wptc_log($query, "--------update_auto_backup_record_db failed--------");
			return false;
		}
	}

	private function update_auto_backup_record_complete_db($cur_backup_id) {

		$query = $this->db->prepare("UPDATE {$this->db->base_prefix}wptc_auto_backup_record SET backup_status = 'backed_up' WHERE backup_status = 'queued' AND cur_backup_id = %f ", $cur_backup_id);

		$update_result = $this->db->query($query);
		if ($update_result === false) {
			wptc_log($query, "--------update_auto_backup_record_complete_db failed--------");
			return false;
		}
	}

	private function update_auto_backup_record_complete_full_db($cur_backup_id) {

		$query = $this->db->prepare("UPDATE {$this->db->base_prefix}wptc_auto_backup_record SET backup_status = 'backed_up' WHERE `timestamp` <= %f ", $cur_backup_id);

		$update_result = $this->db->query($query);
		if ($update_result === false) {
			wptc_log($query, "--------update_auto_backup_record_complete_full_db failed--------");
			return false;
		}
	}

	public function get_auto_backup_record_db() {
		$backup_id = getTcCookie('backupID');
		if (empty($backup_id)) {
			$backup_id = time();
		}
		$backup_id = time();
		$prepared_query = $this->db->prepare("SELECT file FROM " . $this->db->base_prefix . "wptc_auto_backup_record WHERE backup_status = 'noted' AND prev_backup_id <= %d;", $backup_id);
		wptc_log($prepared_query, "--------prepared_query--------");
		$auto_backup_queue = $this->db->get_results($prepared_query);
		wptc_log($auto_backup_queue, "--------auto_backup_queue--------");

		return $auto_backup_queue;
	}

	public function translation_update_make_up($update, $language_update) {
		return true;
	}


	public function start_auto_backup() {
		// if (!$this->config->get_option('in_progress_restore') && !$this->config->get_option('is_running') && !$this->config->get_option('auto_backup_running') &&  time() > $this->add_extra_mins_with_last_backup_time() && !is_any_other_wptc_process_going_on()) {
		$this->config->set_option('auto_backup_running', true);
		$this->config->set_option('wptc_current_backup_type', 'S');
		$this->config->set_option('last_auto_backup_started', time());
		start_fresh_backup_tc_callback_wptc('sub_cycle');
		// } else {
			// send_response_wptc('auto_backup_declined', 'AUTOBACKUP');
		// }
	}

	// public function add_extra_mins_with_last_backup_time(){
	// 	$recent_time = $this->config->get_option('last_auto_backup_started') + ($this->extra_mins * 60);
	// 	$current_time = time();
	// 	wptc_log($current_time - $recent_time, '---------$recent_time - $current_time------------');
	// 	return $this->config->get_option('last_auto_backup_started') + ($this->extra_mins * 60);
	// }

	public function record_auto_backup_complete($backup_id) {

		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		return ;

		if (!$this->config->get_option('auto_backup_running')) {
			return false;
		}
		if (empty($backup_id)) {
			$backup_id = getTcCookie('backupID');
		}
		$this->update_auto_backup_record_complete_db($backup_id);
		$this->update_auto_backup_record_complete_full_db($backup_id);
	}

	public function add_auto_backup_record_to_backup() {
		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		return ;

		$modified_files = array();

		$auto_backup_queue = $this->get_auto_backup_record_db();

		if (empty($auto_backup_queue) || !is_array($auto_backup_queue)) {
			return $modified_files;
		}

		foreach ($auto_backup_queue as $k => $dir) {

			if (empty($dir) || empty($dir->file)) {
				continue;
			}

			$source_dir = wptc_add_abspath($dir->file, false);
			$source_dir = rtrim($source_dir, '/');

			wptc_log($source_dir, '---------------$source_dir-----------------');
			wptc_log($dir->file, '---------------$dir->file-----------------');

			if (!empty($source_dir) && is_readable($source_dir)) {
				$modified_files[] = $dir->file;
			}
		}

		return $modified_files;
	}

	public function force_stop_reset_autobackup(){
		//we have improved iterator faster in v1.14.0 so even real time no need to listen to file system changes
		return ;

		$backup_id = getTcCookie('backupID');
		$query = $this->db->prepare("UPDATE {$this->db->base_prefix}wptc_auto_backup_record SET backup_status = 'noted' , `cur_backup_id` = %d WHERE `cur_backup_id` = %f ", 0, $backup_id);
		wptc_log($query, "--------prepared_query--------");
		$auto_backup_queue = $this->db->get_results($query);
	}

	public function is_auto_backup_running(){
		$is_running = $this->config->get_option('auto_backup_running');
		return empty($is_running) ? false : true;
	}

	public function get_backup_slots($default_slotss){

		$auto_backup_slots = array();

		foreach ($this->auto_backup_slots as $key => $slot) {
			$auto_backup_slots[$key] = $slot['name'];
		}

		$backup_slots = array_merge($default_slotss, $auto_backup_slots);
		return $backup_slots;
	}

	public function check_requirements(){

		if(!$this->is_auto_backup_enabled()){
			return false;
		}

		$database = new WPTC_DatabaseBackup();

		if ($database->is_shell_exec_available()) {
			return false;
		}

		return $this->check_database_requirement();
	}

	private function check_database_requirement(){
		$processed_files = WPTC_Factory::get('processed-files');
		$exclude_class_obj = WPTC_Base_Factory::get('Wptc_ExcludeOption');

		$tables = $processed_files->get_all_tables();
		$total_size = 0;

		foreach ($tables as $table) {
			if($exclude_class_obj->is_excluded_table($table) != 'table_included'){
				continue;
			}

			$total_size += $processed_files->get_table_size($table, $convert_human_readable = false); //convert to human readable
		}

		if (WPTC_REAL_TIME_BACKUP_MAX_PHP_DUMP_DB_SIZE > $total_size) {
			return false;
		}

		return array(
			'title' => 'mysqldump not available',
			'message' => 'Real time backups may take more time to complete as your DB size (' . $processed_files->convert_bytes_to_hr_format($total_size) . ') is greater than (' . $processed_files->convert_bytes_to_hr_format(WPTC_REAL_TIME_BACKUP_MAX_PHP_DUMP_DB_SIZE) . ')  and also mysql dump is not available on your server.' ,
			'type' => 'warning'
			);
	}

	public function validate_auto_backup($die){

		if(!$this->is_auto_backup_enabled()){
			return ($die) ? send_response_wptc('Scheduled backup is completed ', WPTC_DEFAULT_CRON_TYPE) : false ;
		}

		$last_auto_backup_started = $this->config->get_option('last_auto_backup_started');

		//assume this is first auto backup
		if (empty($last_auto_backup_started)) {
			return true;
		}

		$current_backup_slot = $this->config->get_option('backup_slot');

		$interval_sec = $this->auto_backup_slots[$current_backup_slot]['interval_sec'];

		//Interval seconds not found so slot in the auto backup list
		if (empty($interval_sec)) {
			return ($die) ? send_response_wptc('Backup slot not in the list ', WPTC_DEFAULT_CRON_TYPE) : false ;
		}

		wptc_log(time(), '--------time() Current Time--------');
		wptc_log($last_auto_backup_started, '--------last_auto_backup_started--------');

		//last auto backup time exceeds next schedule so start now. (Time tolerence respects constant's value)
		if ( ( time() + WPTC_AUTO_BACKUP_CHECK_TIME_TOLERENCE ) > ( $last_auto_backup_started + $interval_sec ) || ( time() - WPTC_AUTO_BACKUP_CHECK_TIME_TOLERENCE ) > ( $last_auto_backup_started + $interval_sec )  ) {
			return true;
		}

		//last auto backup time not exceeds next schedule so do not start now.
		return  ($die) ? send_response_wptc('Auto backup is completed', WPTC_DEFAULT_CRON_TYPE) : false ;
	}

	public function is_auto_backup_enabled(){
		$current_backup_slot = $this->config->get_option('backup_slot');

		//Current slot not in the slot of auto backup
		if(!isset($this->auto_backup_slots[$current_backup_slot]) ){
			return false;
		}

		return true;
	}

}