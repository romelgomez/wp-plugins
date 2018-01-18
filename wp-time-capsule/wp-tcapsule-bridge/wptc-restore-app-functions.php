<?php

class WPTC_Restore_App_Functions {

	private $config;
	private $fs;
	private $wpdb;
	private $is_restore_to_staging;
	private	$live_db_prefix;
	private	$replace_links_obj;
	private $old_url;
	private $new_url;
	private $old_dir;
	private $new_dir;

	const SECRET_HEAD = '<wptc_head>';
	const SECRET_TAIL = '</wptc_head>';

	public function __construct(){
	}

	public function verify_request(){
		if (!empty( $_POST['wptc_request'] ) || !empty( $_POST['data']['wptc_request'] ) ) {
			return true;
		}

		$this->die_with_msg(array('error' => 'Not wptc request'));
	}

	public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public function enable_php_errors(){
		error_reporting(E_ERROR | E_PARSE);
		ini_set('display_errors', 'On');
	}

	public function init_other_functions(){
		wptc_set_time_limit(0); //to stay in safe side (30 + 5) secs
	}

	public function start_request_time($type = false){

		if ($type === 'iterator') {
			return ;
		}

		global $start_time_tc;
		$start_time_tc = time();
	}

	public function set_fs(){
		global $wp_filesystem;
		$this->fs = $wp_filesystem;

		return $this->fs;
	}

	public function define_constants($enable_bridge_alone = true){
		//used in wptc-constants.php
		$this->define('WPTC_BRIDGE', true);

		if ($enable_bridge_alone) {
			return;
		}

		$this->define('FS_CHMOD_FILE', 0644);

		$this->define('FS_CHMOD_DIR', 0755);
	}

	public function init_db_connection(){
		//initialize wpdb since we are using it independently
		global $wpdb;
		$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

		//setting the prefix from post value;
		$wpdb->prefix = $wpdb->base_prefix = DB_PREFIX_WPTC;

		$this->wpdb = $wpdb;

		return $wpdb;
	}

	public function init_other_objects(){
		if (!empty($this->config)) {
			return ;
		}

		$this->config = WPTC_Factory::get('config');
	}

	public function init_file_system(){
		$credentials = request_filesystem_credentials("", "", false, false, null);
		if (false === $credentials) {
			$this->die_with_msg(array('error' => 'Filesystem error: Could not get filesystem credentials.'));
		}

		if (!WP_Filesystem($credentials)) {
			$this->die_with_msg(array('error' => 'Filesystem error: Could not initiate filesystem.'));
		}

		return $this->set_fs();
	}

	public function decode_request(){
		return $_POST;
	}

	public function die_with_msg($msg){
		$json_encoded_msg = json_encode($msg);
		$msg_with_secret = self::SECRET_HEAD . $json_encoded_msg . self::SECRET_TAIL;
		die($msg_with_secret);
	}

	public function init_log_files(){
		$tcapsule_path = wptc_get_tmp_dir();
		$tcapsule_restore_path = $tcapsule_path . '/wptc_restore_logs/';
		$this->create_folder_by_path($tcapsule_restore_path);
		$this->create_golden_file($tcapsule_restore_path);
		$this->create_log_file($tcapsule_restore_path, $type = 'queries');
		$this->create_log_file($tcapsule_restore_path, $type = 'files');
	}

	public function create_folder_by_path($path){
		$base = new Utils_Base();
		$base->createRecursiveFileSystemFolder($path);
	}

	public function create_golden_file($dir_path){
		$this->fs->put_contents($dir_path. 'index.php', '<?php //silence is golden'); // to create a file
	}

	public function create_log_file($dir_path, $type){
		if (empty($dir_path) || empty($type)) {
			return false; //we cannot handle if anyone is false
		}

		if ($type === 'queries') {
			$is_restore_failed_queries_file_created = $this->config->get_option('is_restore_failed_queries_file_created');
			$file_path = $restore_failed_queries_file_path = $this->config->get_option('restore_failed_queries_file_path');

			if(!$is_restore_failed_queries_file_created || !$restore_failed_queries_file_path || !$this->fs->exists($restore_failed_queries_file_path)) {
				$file_name = WPTC_Factory::secret('failed_queries');
				$file_path = $dir_path . $file_name . '.sql';
				$this->fs->put_contents($file_path, ''); // to create a file
				$this->config->set_option('is_restore_failed_queries_file_created', true);
				$this->config->set_option('restore_failed_queries_file_path', $file_path);
			}

		} else if($type === 'files'){
			$is_restore_failed_downloads_file_created = $this->config->get_option('is_restore_failed_downloads_file_created');
			$file_path = $restore_failed_downloads_file_path = $this->config->get_option('restore_failed_downloads_file_path');

			if(!$is_restore_failed_downloads_file_created || !$restore_failed_downloads_file_path || ($restore_failed_downloads_file_path && !$this->fs->exists($restore_failed_downloads_file_path)) ) {
				$file_name = WPTC_Factory::secret('failed_downloads');
				$file_path = $dir_path . $file_name . '.txt';
				$this->fs->put_contents($file_path, ''); // to create a file
				$this->config->set_option('is_restore_failed_downloads_file_created', true);
				$this->config->set_option('restore_failed_downloads_file_path', $file_path);
			}
		}

		return $file_path;
	}

	public function log_data($type, $data){
		if (empty($type) || empty($data)) {
			return false;
		}

		if ($type === 'files') {
			$file_path = $this->config->get_option('restore_failed_downloads_file_path');
		} else if($type === 'queries') {
			$file_path = $this->config->get_option('restore_failed_queries_file_path');
		}

		if (empty($file_path) || !file_exists($file_path)) {
			wptc_log($file_path, '--------$file_path not exist so cannot log--------');
			return false;
		}

		if ($type === 'files') {
			foreach ($data as $key => $value) {
				file_put_contents($file_path, $key . " : " . $value . "\n", FILE_APPEND);
			}
			file_put_contents($file_path, "\n", FILE_APPEND);
		} else if($type === 'queries') {
			file_put_contents($file_path, $data . "\n", FILE_APPEND);
		}

	}

	public function get_failure_data(){
		$restore_failed_queries_file_path =  $this->config->get_option('restore_failed_queries_file_path');
		$restore_failed_downloads_file_path = $this->config->get_option('restore_failed_downloads_file_path');

		$result = array();

		if (!empty($restore_failed_downloads_file_path) && file_exists($restore_failed_downloads_file_path) && filesize($restore_failed_downloads_file_path) > 0) {
			$result['failed_files'] = str_replace(ABSPATH, $this->config->get_option('site_url_wptc'). '/', $restore_failed_downloads_file_path);
		}

		if (!empty($restore_failed_queries_file_path) && file_exists($restore_failed_queries_file_path) && filesize($restore_failed_queries_file_path) > 0) {
			$result['failed_queries'] = str_replace(ABSPATH, $this->config->get_option('site_url_wptc'). '/', $restore_failed_queries_file_path);
		}

		return $result;
	}

	public function check_and_record_not_safe_for_write($this_file) {

		$this_file = $this->config->wp_filesystem_safe_abspath_replace($this_file);

		if($this->fs->is_dir($this_file)){
			return true;
		}

		$this_file = rtrim($this_file, '/');

		if (!$this->fs->exists($this_file) ){
			return true;
		}

		if($this->fs->is_writable($this_file)){
			return true;
		}

		$chmod_result = $this->fs->chmod($this_file, 0644);
		if (!$chmod_result || !$this->fs->is_writable($this_file)) {
			$this->config->save_encoded_not_safe_for_write_files($this_file);
			return false;
		}

		return true;
	}

	public function maybe_call_again_tc($return = false) {
		global $start_time_tc;

		$this->define('WPTC_TIMEOUT', 21);

		if ((time() - $start_time_tc) >= WPTC_TIMEOUT) {

			if ($return) return true;

			$this->die_with_msg("wptcs_callagain_wptce");

		}

		return false;
	}

	public function is_file_hash_same($file_path, $prev_file_hash ,$prev_file_size, $prev_file_mtime = 0) {
		wptc_add_abspath($file_path);

		$this->init_other_objects();

		$file_path = $this->config->wp_filesystem_safe_abspath_replace($file_path);
		$file_path = rtrim($file_path, '/');

		if (!file_exists($file_path)) {
			return false;
		}

		if(!wptc_is_hash_required($file_path) || empty($prev_file_hash)){
			return $this->is_same_size_and_same_mtime($file_path, $prev_file_size, $prev_file_mtime);
		}

		$new_file_hash = wptc_get_hash($file_path);
		if ($prev_file_hash != $new_file_hash) {
			return false;
		}

		return true;

	}

	private function is_same_size_and_same_mtime($file_path, $prev_file_size, $prev_file_mtime){
		$new_file_size = @filesize($file_path);

		$this_file_m_time = @filemtime($file_path);

		if (($new_file_size == $prev_file_size) && ($this_file_m_time == $prev_file_mtime)) {
			return true;
		}

		return false;
	}

	public function enable_maintenance_mode() {
		$file_content = '<?php global $upgrading; $upgrading = time();';

		$file_name = $this->config->wp_filesystem_safe_abspath_replace(ABSPATH);
		$file_name .= '.maintenance';

		$this->fs->put_contents($file_name, $file_content);
	}

	public function disable_maintenance_mode() {
		$maintenance_file = $this->config->wp_filesystem_safe_abspath_replace(ABSPATH);
		$maintenance_file .= '.maintenance';

		if ($this->fs->is_file($maintenance_file)) {
			$this->fs->delete($maintenance_file);
		}
	}

	public function remove_gz_ext_from_file($file){
		return str_replace('.gz', '', $file);
	}

	public function is_gzip_available(){
		if(!wptc_function_exist('gzwrite') || !wptc_function_exist('gzopen') || !wptc_function_exist('gzclose') ){
			wptc_log(array(), '--------ZGIP not available--------');
			return false;
		}

		return true;
	}

	public function gz_uncompress_file($source, $offset = 0){

		$dest =  str_replace('.gz', '', $source);

		$fp_in = gzopen($source, 'rb');

		if (empty($fp_in)) {
			gzclose($fp_in);
			$this->die_with_msg(array('error' => 'Cannot open gzfile to uncompress sql'));
		}

		$fp_out = ($offset === 0) ? fopen($dest, 'wb') : fopen($dest, 'ab');

		if (empty($fp_out)) {
			fclose($fp_out);
			$this->die_with_msg(array('error' => 'Cannot open temp file to uncompress sql'));
		}

		gzseek($fp_in, $offset);

		$emptimes = 0;

		while (!gzeof($fp_in)){

			$chunk_data = gzread($fp_in, 1024 * 1024 * 5); //read 5MB per chunk

			wptc_log(strlen($chunk_data), '---------------strlen($chunk_data)-----------------');

			if (empty($chunk_data)) {

				$emptimes++;

				wptc_log(array(), "---------------Got empty gzread ($emptimes times)---------------");

				if ($emptimes > 3){
					$this->die_with_msg(array('error' => "Got empty gzread ($emptimes times)"));
				}

			} else {
				@fwrite($fp_out, $chunk_data);
			}

			wptc_manual_debug('', 'during_uncompress_db', 2);

			$current_offset = gztell($fp_in);

			wptc_log($current_offset, '---------------$current_offset-----------------');

			//Clearning to save memory
			unset($chunk_data);
		}

		fclose($fp_out);
		gzclose($fp_in);

		wptc_log(array(), '--------Un compression done--------');

		// @unlink($source);

		wptc_manual_debug('', 'end_uncompress_db');

		return $dest;
	}

	public function import_sql_file($file_name, $prev_index, $replace_collation = false){
		wptc_log(func_get_args(), "--------------" . __FUNCTION__ . "------------------");

		if (!$replace_collation) {
			$replace_collation = $this->get_collation_replacement_status();
		}

		wptc_log($replace_collation,'-----------$replace_collation----------------');

		$handle = fopen($file_name, "rb");

		if (empty($handle)) {
			return array('status' => 'error', 'msg' => 'Cannot open database file');
		}

		$prev_index = empty($prev_index) ? 0 : $prev_index;

		$current_query = '';

		$this_lines_count = $loop_iteration = 0;

		while ( ( $line = fgets( $handle ) ) !== false ) {

			$loop_iteration++;

			if ($loop_iteration <= $prev_index ) {
				continue; //check index; if it is previously written ; then continue;
			}

			$this_lines_count++;

			if (substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 3) == '/*!') {
				continue; // Skip it if it's a comment
			}

			$current_query .= $line;

			// If it does not have a semicolon at the end, then it's not the end of the query
			if (substr(trim($line), -1, 1) != ';') {
				continue;
			}

			if ($this->is_restore_to_staging) {
				$current_query = preg_replace_callback("/(TABLE[S]?|INSERT\ INTO|DROP\ TABLE\ IF\ EXISTS) [`]?([^`\;\ ]+)[`]?/", array($this, 'search_and_replace_prefix'), $current_query);
			}

			wptc_manual_debug('', 'during_db_restore', 1000);

			if ( $replace_collation ) {
				wptc_log(array(),'-----------Collation replaced----------------');
				$current_query = $this->replace_collation($current_query);
			}

			$result = $this->wpdb->query($current_query);

			if ($result === false) {

				if( !$replace_collation && $this->is_collation_issue($this->wpdb->last_error) ){

					wptc_log(array(),'-----------Collation issue----------------');

					$this->wpdb->query('UNLOCK TABLES;');
					fclose($handle);

					//restart the processes
					return array('status' => 'continue', 'offset' => 0, 'replace_collation' => true);
				}
				//log failed queries
				$this->log_data('queries', $current_query);
			}

			$current_query = $line = '';

			//check timeout after every 10 queries executed
			if ($this_lines_count <= 10) {
				continue;
			}

			$this_lines_count = 0;

			if(!$this->maybe_call_again_tc($return = true)){
				continue;
			}

			$this->wpdb->query('UNLOCK TABLES;');
			fclose($handle);
			return array('status' => 'continue', 'offset' => $loop_iteration, 'replace_collation' => false);
		}

		$this->wpdb->query('UNLOCK TABLES;');

		return array('status' => 'completed');
	}

	private function is_collation_issue($error){
		wptc_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		if (!$error) {
			return false;
		}

		if (strstr($error, 'Unknown collation') === false) {
			return false;
		}

		$this->config->set_option('replace_collation_for_this_restore', true);

		return true;

	}

	private function get_collation_replacement_status(){
		return $this->config->get_option('replace_collation_for_this_restore');
	}

	private function replace_collation($current_query){
		if (strstr($current_query,'utf8mb4_unicode_520_ci') === false) {
			return $current_query;
		}

		return str_replace('utf8mb4_unicode_520_ci','utf8mb4_unicode_ci', $current_query);
	}

	public function is_restore_to_staging(){
		$this->is_restore_to_staging = $this->config->get_option('is_restore_to_staging');
	}

	public function set_old_prefix_restore_to_staging(){
		$this->live_db_prefix = $this->config->get_option('s2l_live_db_prefix');
	}

	public function search_and_replace_prefix($matches){
		$subject = $matches[0];
		$old_table_name = $matches[2];
		$new_table_name = preg_replace("/$this->live_db_prefix/", DB_PREFIX_WPTC, $old_table_name, 1);
		return str_replace($old_table_name, $new_table_name, $subject);
	}

	public function replace_links(){

		if($this->config->get_option('R2S_replace_links')){
			wptc_log(array(),'----------replace links done already----------------');
			return ;
		}

		if(!$this->config->get_option('is_restore_to_staging')){
			wptc_log(array(),'----------not is_restore_to_staging----------------');
			return ;
		}

		$this->init_necessary_things();

		$replace_db_links = $this->config->get_option('R2S_deep_links_completed');
		wptc_log($replace_db_links , '-------------$replace_db_links -------------------');

		if(empty($replace_db_links)){

			wptc_manual_debug('', 'start_replace_old_url_R2S');
			$this->replace_db_links();
			wptc_manual_debug('', 'end_replace_old_url_R2S');

		}

		$this->create_default_htaccess();

		wptc_log(array(),'-----------1----------------');

		$this->replace_links_obj->discourage_search_engine(DB_PREFIX_WPTC, $reset_permalink = true);
		wptc_log(array(),'-----------2----------------');

		$this->replace_links_obj->update_site_and_home_url(DB_PREFIX_WPTC, $this->new_url);
		wptc_log(array(),'-----------3----------------');

		$this->replace_links_obj->rewrite_rules(DB_PREFIX_WPTC);
		wptc_log(array(),'-----------4----------------');

		$this->replace_links_obj->update_user_roles(DB_PREFIX_WPTC, $this->live_db_prefix);
		wptc_log(array(),'-----------5----------------');

			//Replace new prefix
		$this->replace_links_obj->replace_prefix(DB_PREFIX_WPTC, $this->live_db_prefix);
		wptc_log(array(),'-----------6----------------');

		//multisite changes
		if (is_multisite()) {
			$this->replace_links_obj->multi_site_db_changes(DB_PREFIX_WPTC, $this->new_url, $this->old_url);
		}
		wptc_log(array(),'-----------7-----------------');

		//replace $table_prefix in wp-config.php
		$this->replace_links_obj->modify_wp_config(
			array(
				'old_url' =>  $this->old_url,
				'new_url' =>  $this->new_url,
				'new_path' => $this->new_dir,
				'old_path' => $this->old_dir,
				'new_prefix' =>  DB_PREFIX_WPTC,
			)
		);

		wptc_log(array(),'-----------8-----------------');


		//Deactivate WP Time Capsule on staging site
		WPTC_Base_Factory::get('Wptc_App_Functions')->run_deactivate_plugin('wp-time-capsule/wp-time-capsule.php', DB_PREFIX_WPTC);

		//Activate WP Time Capsule Staging plugin on staging site
		WPTC_Base_Factory::get('Wptc_App_Functions')->run_activate_plugin('wp-time-capsule-staging/wp-time-capsule-staging.php', DB_PREFIX_WPTC);

		$this->config->set_option('R2S_replace_links', true);
	}

	private function create_default_htaccess(){
		if (is_multisite()) {
			return $this->multi_site_default_htaccess();
		}

		return $this->normal_site_default_htaccess();
	}

	private function multi_site_default_htaccess(){
		$this->replace_links_obj->create_htaccess($this->new_url, $this->new_dir, 'multisite');
	}

	private function normal_site_default_htaccess(){
		$this->replace_links_obj->create_htaccess($this->new_url, $this->new_dir, 'normal');
	}

	public function replace_db_links(){
		if (!$this->is_restore_to_staging) {
			return ;
		}

		$replace_deep_links = $this->config->get_option('R2S_deep_links_completed');

		if ($replace_deep_links) {
			return ;
		}

		$raw_result = $this->config->get_option('same_server_replace_old_url_data');
		$tables = false;
		if (!empty($raw_result)) {
			$tables = @unserialize($raw_result);
		}

		$this->replace_links_obj->replace_uri($this->old_url, $this->new_url, $this->old_dir, $this->new_dir, DB_PREFIX_WPTC, $tables);

		$this->config->set_option('R2S_deep_links_completed', true);
	}

	private function init_necessary_things(){
		$this->get_replace_db_link_obj();
		$this->old_url = $this->config->get_option('s2l_live_url');
		$this->new_url = $this->config->get_option('site_url_wptc');
		$this->old_dir = $this->config->get_option('s2l_live_path');
		$this->new_dir = $this->config->get_option('site_abspath');

		wptc_log($this->old_url, '---------------$this->old_url-----------------');
		wptc_log($this->new_url, '---------------$this->new_url-----------------');
		wptc_log($this->old_dir, '---------------$this->old_dir-----------------');
		wptc_log($this->new_dir, '---------------$this->new_dir-----------------');
	}

	private function get_replace_db_link_obj(){
		$this->replace_links_obj = new WPTC_Replace_DB_Links();
	}

}