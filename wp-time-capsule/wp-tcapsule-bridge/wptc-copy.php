<?php
/*
------time capsule------
1.this file is totally used to move the files from the tempFolder to the actual root folder of wp
2.this file uses files from wordpress and also plugins to perform the copying actions
 */
/**
* created by thamaraiselvam
* created on 28-06-2017
*/

class WPTC_Copy{
	private $common_include_files;
	private	$restore_app_functions;
	private	$config;
	private	$restore_id;
	private	$file_iterator;
	private	$processed_files;
	private	$copied_files_count;
	private	$state_files_count;
	private	$utils_base;
	private	$post_data;

	const WPTC_PLUGIN_URL = 'https://downloads.wordpress.org/plugin/wp-time-capsule.zip';

	function __construct(){

		//create object for restore app common
		$this->init_restore_app_functions();

		//accept only wptc requests
		$this->restore_app_functions->verify_request();

		//enable PHP errors
		$this->restore_app_functions->enable_php_errors();

		//set global starting time
		$this->restore_app_functions->start_request_time();

		//define restore constant to override other functions
		$this->restore_app_functions->define_constants();

		//restore needed files
		$this->include_files();

		//start database connections
		$this->connect_db();

		$this->restore_app_functions->init_other_objects();

		$this->restore_app_functions->init_other_functions();

		//assume this is request from server
		set_server_req_wptc();

		//Init WP File system
		$this->set_fs();

		$this->config = WPTC_Factory::get('config');

		$this->log_recent_calls();

		$this->setup_restore();

		$this->check_request();

		$this->restore_app_functions->init_log_files();

		//start db dump and copy files
		$this->process_files();
	}

	private function delete_empty_folders($source){
		wptc_log(func_get_args(), __FUNCTION__);

		if (empty($source)) {
			return false;
		}

		$file_obj = $this->file_iterator->get_files_obj_by_path($source, false);

		foreach ($file_obj as $file_meta) {

			$path = $file_meta->getPathname();

			if (!wptc_is_dir($path)) {
				continue;
			}

			if(!$this->file_iterator->is_empty_folder($path)){
				wptc_log($path, '---------------Not empty-----------------');
				continue;
			}

			wptc_log($path, '---------------Deleted-----------------');
			$this->fs->delete($path, true);
		}

	}

	public function include_files(){
		require_once dirname(__FILE__). '/' ."common_include_files.php";
		$this->common_include_files = new Common_Include_Files('wptc-copy');
		$this->common_include_files->init();
	}

	private function init_restore_app_functions(){
		//common app functions for both ajax and tc-init
		require_once dirname(__FILE__). '/' ."wptc-restore-app-functions.php";
		$this->restore_app_functions = new WPTC_Restore_App_Functions();
	}

	private function connect_db(){
		$this->wpdb = $this->restore_app_functions->init_db_connection();
	}

	private function set_fs(){
		$this->fs = $this->restore_app_functions->init_file_system();
	}

	private function log_recent_calls(){
		$this->config->set_option('recent_restore_ping', time());
	}

	private function setup_restore(){
		$this->restore_id = $this->config->get_option('restore_action_id');
		$this->restore_app_functions->define('WPTC_SITE_ABSPATH', $this->config->get_option('site_abspath'));
		$this->file_iterator = new WPTC_File_Iterator();
		wptc_set_fallback_db_search_1_14_0();
		wptc_setlocale();
	}

	private function check_request(){
		$this->post_data = $this->restore_app_functions->decode_request();
	}

	private function process_files(){
		wptc_manual_debug('', 'start_wptc_copy');

		//update the options table to indicate that bridge process is going on , only on the first call
		$this->reset_flags();

		$this->is_restore_completed();

		$this->restore_app_functions->is_restore_to_staging();
		$this->restore_app_functions->set_old_prefix_restore_to_staging();

		$restore_temp_folder = $this->config->get_backup_dir(true) . '/tCapsule';
		$restore_db_dump_file = $this->get_sql_file($restore_temp_folder);

		//check if the db restore process is already completed
		if ($this->config->get_option('restore_db_process')) {

			//check if the sql file is selected during restore process, if it doesnt exist then we dont need to do the restore db process
			if (!$this->fs->exists($restore_db_dump_file)) {
				wptc_log(array(), '-----------db file not found in this restore-------------');
				$this->config->set_option('restore_db_process', false);
				$this->config->set_option('restore_db_index', 0);
				$this->restore_app_functions->die_with_msg('wptcs_callagain_wptce');
			}

			wptc_log($restore_db_dump_file, '-----------Sql file found-------------');

			$this->restore_app_functions->enable_maintenance_mode();

			wptc_manual_debug('', 'start_db_restore');

			$db_restore_result = $this->database_restore($restore_db_dump_file);

			wptc_manual_debug('', 'end_db_restore');

			if (!$db_restore_result) {
				$this->handle_restore_error_wptc($this->config);
				$err_obj = array();
				$err_obj['restore_db_dump_file'] = $restore_db_dump_file;
				$err_obj['mysql_error'] = $this->wpdb->last_error;
				$err = array('error' => $err_obj);
				$this->restore_app_functions->disable_maintenance_mode();
				WPTC_Base_Factory::get('Wptc_Backup_Analytics')->send_report_data($this->restore_id, 'RESTORE', 'FAILED');
				$this->restore_app_functions->die_with_msg($err);
			}

			//on db restore completion - set the following values
			$this->config->set_option('restore_db_process', false);
			$this->config->set_option('restore_db_index', 0);

			if ($this->fs->exists($restore_db_dump_file)) {
				@unlink($restore_db_dump_file);
			}

			$this->restore_app_functions->die_with_msg('wptcs_callagain_wptce');
		}

		//first delete the sql file then carryout the copying files process
		if ($this->fs->exists($restore_db_dump_file)) {
			$this->fs->delete($restore_db_dump_file);
		}

		$this->restore_app_functions->enable_maintenance_mode();

		$this->utils_base = new Utils_Base();

		$this->set_copied_files_count();
		// $this->processed_files = WPTC_Factory::get('processed-restoredfiles', true);

		if($this->config->get_option('is_bridge_restore')){
			$this->download_fresh_wptc_plugin($restore_temp_folder . '/' . WPTC_WP_CONTENT_BASENAME . '/plugins/' );
		}

		$full_copy_result = true;

		wptc_log($restore_temp_folder,'-----------$restore_temp_folder----------------');

		if(!$this->config->get_option('copy_files')){
			wptc_manual_debug('', 'start_copy_files');
			$full_copy_result = $this->move_dir($restore_temp_folder, ABSPATH);
			wptc_manual_debug('', 'end_copy_files');
		}


		$this->restore_app_functions->replace_links();

		$this->set_state_files_count();

		wptc_manual_debug('', 'start_deleting_state_files');
		$full_copy_result = $this->check_and_delete_state_files($restore_temp_folder);
		wptc_manual_debug('', 'end_deleting_state_files');

		$this->restore_app_functions->disable_maintenance_mode();

		if (!empty($full_copy_result) && is_array($full_copy_result) && array_key_exists('error', $full_copy_result)) {
			$this->restore_app_functions->die_with_msg($full_copy_result);
		} else {
			//if we set this value as false ; then the bridge process for copying is completed
			$this->config->set_option('is_bridge_process', false);
			$this->restore_complete();
		}

		$this->is_restore_completed();
	}

	private function check_and_delete_state_files($restore_temp_folder){
		wptc_log(func_get_args(), __FUNCTION__);

		$state_file = $restore_temp_folder . '/backups/wptc_current_files_state.txt';
		wptc_log($state_file, '---------------$state_file-----------------');

		if (!file_exists($state_file)) {
			wptc_log(array(), '----------------File not exists----------------');
			return ;
		}

		$handle = fopen($state_file, "rb");

		if (empty($handle)) {
			wptc_log(array(), '----------------cannot state open file----------------');

			$this->restore_app_functions->disable_maintenance_mode();

			WPTC_Base_Factory::get('Wptc_Backup_Analytics')->send_report_data($this->restore_id, 'RESTORE', 'FAILED');

			$this->restore_app_functions->die_with_msg(array('error' => 'Cannot state open database file'));
		}

		$loop_iteration = 0;

		while (($file = fgets($handle)) !== false) {

			$loop_iteration++;

			if ($loop_iteration <= $this->state_files_count ) {
				continue; //check index; if it already processed ; then continue;
			}

			wptc_manual_debug('', 'during_deleting_state_files', 100);

			$file = str_replace("\n", '', $file);

			if (empty($file)) {
				continue;
			}

			wptc_add_abspath($file);

			wptc_log($file, '---------------$file-----------------');

			if (!$this->fs->exists($file)) {
				wptc_log(array(), '----------------File not found----------------');
				continue;
			}

			wptc_log($file, '---------------$file got deleted-----------------');

			$result = $this->fs->delete($file);

			if (!$result) {
				wptc_log(error_get_last(), '---------------error_get_last()-----------------');
			}

			if(!$this->restore_app_functions->maybe_call_again_tc($return = true)){
				continue;
			}

			$this->config->set_option('restore_state_files_count', $this->state_files_count);
			$this->restore_app_functions->die_with_msg("wptcs_callagain_wptce");
		}

	}

	private function set_state_files_count(){
		$count = $this->config->get_option('restore_state_files_count');
		$this->state_files_count = ($count) ? $count : 0 ;
		wptc_log($this->state_files_count, '---------------$this->state_files_count-----------------');
	}

	private function set_copied_files_count(){
		$count = $this->config->get_option('restore_copied_files_count');
		$this->copied_files_count = ($count) ? $count : 0 ;
	}

	private function move_dir($source, $destination = '') {

		$source = trailingslashit($source);
		$destination = trailingslashit($destination);

		$file_obj = $this->file_iterator->get_files_obj_by_path($source, true);

		foreach ($file_obj as $file) {

			$source_file = $file->getPathname();

			if (wptc_is_dir($source_file)) {
				continue;
			}

			$this->copied_files_count++;

			$destination_file = str_replace($source, $destination, $source_file);

			if (!$this->move_file($source_file, $destination_file, true)) {

				$this->fs->chmod($destination_file, 0644);

				if (!$this->move_file($source_file, $destination_file, true)) {
					$file_err['error'] = 'cannot move file';
					$file_err['file'] = $destination_file;
					$this->restore_app_functions->log_data('files', $file_err);
				}
			}

			if(!$this->restore_app_functions->maybe_call_again_tc($return = true)){
				continue;
			}

			$this->config->set_option('restore_copied_files_count', $this->copied_files_count);
			$this->restore_app_functions->die_with_msg("wptcs_callagain_wptce");
		}

		$this->config->set_option('copy_files', true);

		return true;
	}

	private function move_file($source, $destination, $overwrite = true) {
		wptc_log(func_get_args(), __FUNCTION__);

		wptc_manual_debug('', 'during_copy_files', 100);

		$this->utils_base->createRecursiveFileSystemFolder(dirname($destination));

		if (!file_exists($source)) {
			wptc_log($source, '--------------Source not found------------------');
			return false;
		}

		$result = $this->fs->move($source, $destination, $overwrite);

		if (!$result) {
			wptc_log(error_get_last(), '---------------error_get_last()-----------------');
			return false;
		}

		return true;
	}

	private function get_sql_file($restore_temp_folder){

		$content_name = basename(wptc_get_tmp_dir());

		$site_db_name = $this->config->get_option('site_db_name');

		$relative_path = str_replace(WPTC_ABSPATH, '', $restore_temp_folder);

		$restore_db_dump_dir = $restore_temp_folder . '/' . $relative_path . '/backups/';

		$restore_db_dump_file = $restore_db_dump_dir . $site_db_name . '-backup.sql.gz';

		if (file_exists($restore_db_dump_file)) {
			return $restore_db_dump_file;
		}

		return $restore_db_dump_dir . $site_db_name . '-backup.sql';
	}

	private function reset_flags(){
		if (empty($this->post_data) || empty($this->post_data['initialize']) || $this->post_data['initialize'] != true) {
			return false;
		}

		$this->config->set_option('is_bridge_process', true);
		$this->config->set_option('restore_db_index', 0);
		$this->config->set_option('restore_saved_index', 0);
		$this->config->set_option('restore_db_process', true);
	}

	private function is_restore_completed(){
		if (!$this->config->get_option('is_bridge_process') && !$this->config->get_option('garbage_deleted')) {
			WPTC_Base_Factory::get('Wptc_Backup_Analytics')->send_report_data($this->restore_id, 'RESTORE', 'SUCCESS');
			$this->restore_app_functions->die_with_msg('wptcs_over_wptce');
		}
	}

	private function restore_complete($error = false) {

		wptc_manual_debug('', 'start_delete_empty_folders');

		$this->delete_empty_folders(WP_CONTENT_DIR . '/plugins'); //Remove invalid plugins
		$this->delete_empty_folders(WP_CONTENT_DIR . '/themes'); //Remove invalid themes

		wptc_manual_debug('', 'end_delete_empty_folders');

		$this->restore_app_functions->disable_maintenance_mode();

		$this->config->set_option('restore_completed_notice', 'yes');


		//delete the bridge files on completion
		$this->delete_bridge_folder();

		$this->config->set_option('in_progress_restore', false);
		$this->config->set_option('is_running_restore', false);
		$this->config->set_option('cur_res_b_id', false);
		$this->config->set_option('start_renaming_sql', false);
		$this->config->set_option('restore_db_index', 0);
		$this->config->set_option('got_files_list_for_restore_to_point', 0);
		$this->config->set_option('live_files_to_restore_table', 0);
		$this->config->set_option('recorded_files_to_restore_table', 0);
		$this->config->set_option('is_deleted_all_future_files', 0);
		$this->config->set_option('selected_files_temp_restore', 0);
		$this->config->set_option('selected_backup_type_restore', 0);
		$this->config->set_option('got_selected_files_to_restore', 0);
		$this->config->set_option('not_safe_for_write_files', 0);
		$this->config->set_option('recorded_this_selected_folder_restore', 0);
		$this->config->set_option('recent_restore_ping', false);
		$this->config->set_option('is_bridge_process', false);
		$this->config->set_option('get_recorded_files_to_restore_table', false);
		$this->config->set_option('restore_current_action', false);
		$this->config->set_option('sql_gz_uncompression', false);
		$this->config->set_option('restore_copied_files_count', false);
		$this->config->set_option('restore_state_files_count', false);
		$this->config->set_option('copy_files', false);
		$this->config->set_option('restore_downloaded_files_count', false);
		$this->config->set_option('delete_future_files_offset', false);
		$this->config->set_option('is_restore_to_staging', false);
		$this->config->set_option('replace_collation_for_this_restore', false);
		$this->config->set_option('restore_to_staging_details', false);
		$this->config->set_option('R2S_replace_links', false);
		$this->config->set_option('R2S_deep_links_completed', false);
		$this->config->set_option('is_bridge_restore', false);
		$this->config->reset_complete_flags();

		$processed_restore = new WPTC_Processed_Restoredfiles();
		$processed_restore->truncate();
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_current_process`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wptc_processed_iterator`");

		$this->config->remove_garbage_files(array('is_restore' => true));
		wptc_manual_debug('', 'remove_garbage_files');

		if (!empty($error)) {
			$this->restore_app_functions->disable_maintenance_mode();
			$this->restore_app_functions->die_with_msg($error);
		}

		WPTC_Base_Factory::get('Wptc_Backup_Analytics')->send_report_data($this->restore_id, 'RESTORE', 'SUCCESS');

		$failure_data = $this->restore_app_functions->get_failure_data();

		wptc_manual_debug('', 'restore_complete');

		if (empty($failure_data)) {
			$this->restore_app_functions->die_with_msg('wptcs_over_wptce');
		} else {
			$this->restore_app_functions->die_with_msg(array('status' => 'wptcs_over_wptce', 'failure_data' => $failure_data) );
		}
	}

	private function delete_bridge_folder(){

		$backup_db_path = wptc_get_tmp_dir();
		$backup_db_path = $this->config->wp_filesystem_safe_abspath_replace($backup_db_path. 'wptcrquery/wptc_saved_queries_restore.sql');

		if ($this->fs->exists($backup_db_path)) {
			$this->fs->delete($backup_db_path);
		}
	}

	private function handle_restore_error_wptc() {
		$this->config->remove_garbage_files(array('is_restore' => true));
		$this->config->set_option('restore_db_process', false);
		$this->config->set_option('is_bridge_process', false);
		$this->config->set_option('restore_db_index', 0);
		$this->restore_complete('Restoring DB error.');
	}

	private	function database_restore($file_name) {

		$file_name = $this->uncompress($file_name);

		$prev_index = $this->config->get_option('restore_db_index');

		$response = $this->restore_app_functions->import_sql_file($file_name, $prev_index);

		wptc_log($response, '--------database_restore response--------');

		if (empty( $response ) || empty($response['status']) || $response['status'] === 'error') {
			$this->restore_app_functions->disable_maintenance_mode();
			WPTC_Base_Factory::get('Wptc_Backup_Analytics')->send_report_data($this->restore_id, 'RESTORE', 'FAILED');
			$err = $response['status'] === 'error' ? $response['status']['msg'] : 'Unknown error during database import';
			$this->restore_app_functions->die_with_msg(array('error' => $err));
		}

		if ($response['status'] === 'continue') {
			$this->config->set_option('restore_db_index', $response['offset']); //updating the status in db for each 10 lines
			$this->restore_app_functions->die_with_msg('wptcs_callagain_wptce');
		}

		if ($response['status'] === 'completed') {
			return true;
		}

	}

	private function get_all_db_sql_files($path){
		$sql_files = array();
		$file_iterator = new WPTC_File_Iterator();
		$files_obj = $file_iterator->get_files_obj_by_path($path, $recursive = 1);
		foreach ($files_obj as $key => $file) {
				$file_path = $file->getPathname();
				$file_name= basename($file_path);
				if ($file_name == '.' || $file_name == '..' || !$file->isReadable() || stripos($file_path, 'wptc_saved_queries_restore.sql') ) {
					continue;
				}
				$sql_files[] = $file_name;
		}
		return $sql_files;
	}

	private function replace_wptc_sercet_custom($raw_data){
		$response = $raw_data;
		$headerPos = stripos($response, 'wptc_saved_queries.sql.');
		if($headerPos !== false){
			$response = substr($response, $headerPos);
			$response = substr($response, strlen('wptc_saved_queries.sql.'), stripos($response, '-wptc-')-strlen('wptc_saved_queries.sql.'));
			$result = str_replace($response, '', $raw_data);
		}
		return $result;
	}

	private function cmp_wptc_sql_array($ta, $tb){
		$a = $this->replace_wptc_sercet_custom($ta);
		$b = $this->replace_wptc_sercet_custom($tb);
		return strcmp($a, $b);
	}

	private function is_unwanted_query_staging($req_query){
		$queries = array('CREATE DATABASE IF NOT EXISTS ', 'USE ');
		foreach ($queries as $query) {
			if (strpos($req_query, $query) !== FALSE) {
				return true;
			}
		}
		return false;
	}

	public function uncompress($file){


		//Return original sql file for normal sql file or compression completed file.
		if(strpos($file, '.gz') === false || $this->config->get_option('sql_gz_uncompression')){
			wptc_log(array(), '--------Either compression done or file is not compressed--------');
			return $this->restore_app_functions->remove_gz_ext_from_file($file);
		}

		wptc_log(array(), '---------------Uncompressing file-----------------');

		if (!$this->restore_app_functions->is_gzip_available()) {
			$this->config->set_option('sql_gz_uncompression', true);
			$this->restore_app_functions->die_with_msg(array('error' => 'gzip not installed on this server so could not uncompress the sql file'));
		}

		wptc_manual_debug('', 'start_uncompress_db');

		$this->restore_app_functions->gz_uncompress_file($file, $offset = 0);
		$this->config->set_option('sql_gz_uncompression', true);
		return $this->restore_app_functions->remove_gz_ext_from_file($file);
	}

	private function download_fresh_wptc_plugin($destination){
		$this->utils_base->createRecursiveFileSystemFolder($destination);
		$file_path = $destination . 'wp-time-capsule.zip';
		$result = $this->download_URL( self::WPTC_PLUGIN_URL, $file_path );
		$this->extract_zip($file_path, $destination);
		if (file_exists($file_path)) {
			unlink($file_path);
		}
	}

	private function download_URL($URL, $filePath){
		return ($this->curl_download_URL($URL, $filePath) || $this->fopen_download_URL($URL, $filePath));
	}

	private	function extract_zip($backup_file, $temp_unzip_dir){
		$archive   = new WPTCPclZip($backup_file);
		$extracted = $archive->extract(WPTC_PCLZIP_OPT_PATH, $temp_unzip_dir, WPTC_PCLZIP_OPT_TEMP_FILE_THRESHOLD, 1);

		wptc_log($extracted,'-----------$extracted----------------');

		if (!$extracted || $archive->error_code) {
			wptc_log('Error: Failed to extract fresh wptc plugin (' . $archive->error_string . ')' ,'---------- Failed to extract fresh wptc plugin-----------------');
			return false;
		}

		return true;
	}

	private function curl_download_URL($URL, $filePath){

		wptc_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		$fp = fopen ($filePath, 'w');

		if ($fp === false) {
			wptc_log(error_get_last(),'-----------error_get_last()----------------');
			return false;
		}

		wptc_log($fp,'-----------$fp----------------');
		$ch = curl_init($URL);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 180);
		curl_setopt($ch, CURLOPT_FILE, $fp);

		if (!ini_get('safe_mode') && !ini_get('open_basedir')){
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		}

		$callResponse = curl_exec($ch);

		wptc_log($callResponse,'-----------$callResponse----------------');
		curl_close($ch);
		fclose($fp);

		if($callResponse == 1){
			return true;
		}

		return false;
	}

	private function fopen_download_URL($URL, $filePath){

		if (!function_exists('ini_get') || ini_get('allow_url_fopen') != 1) {
			return false;
		}

		$src = @fopen($URL, "r");
		$dest = @fopen($filePath, 'wb');
		if(!$src || !$dest){
			return false;
		}

		while ($content = @fread($src, 1024 * 1024)) {
			@fwrite($dest, $content);
		}

		@fclose($src);
		@fclose($dest);
		return true;
	}

}

new WPTC_Copy();
