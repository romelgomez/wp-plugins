<?php
/**
* A class with functions the perform a backup of WordPress
*
* @copyright Copyright (C) 2011-2014 Awesoft Pty. Ltd. All rights reserved.
* @author Michael De Wildt (http://www.mikeyd.com.au/)
* @license This program is free software; you can redistribute it and/or modify
*          it under the terms of the GNU General Public License as published by
*          the Free Software Foundation; either version 2 of the License, or
*          (at your option) any later version.
*
*          This program is distributed in the hope that it will be useful,
*          but WITHOUT ANY WARRANTY; without even the implied warranty of
*          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*          GNU General Public License for more details.
*
*          You should have received a copy of the GNU General Public License
*          along with this program; if not, write to the Free Software
*          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
*/

abstract class WPTC_Processed_Base {
	protected
	$db,
	$default_life_span,
	$config,
	$restore_app_functions,
	$processed = array()
	;

	public function __construct() {
		$this->default_life_span = 60 * 60 * 24 * WPTC_KEEP_MAX_BACKUP_DAYS_LIMIT; //150 days default life span
		$this->existing_users_rev_limit_hold = 60 * 60 * 24 * WPTC_FALLBACK_REVISION_LIMIT_DAYS; //30 days default life span
		$this->db = WPTC_Factory::db();
		$this->config = WPTC_Factory::get('config');
		if (defined('WPTC_BRIDGE')) {
			$this->restore_app_functions = new WPTC_Restore_App_Functions();
		}

	}

	abstract protected function getTableName();

	abstract protected function getProcessType();

	abstract protected function getRestoreTableName();

	abstract protected function getId();

	abstract protected function getRevisionId();

	abstract protected function getFileId(); //file column is not unique now .. so we should update using file_id

	abstract protected function getUploadMtime();

	protected function getBackupID() {
		return 'backupID';
	}

	protected function getLifeSpan() {
		return 'life_span';
	}

	protected function getVar($val) {
		return $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s", $val)
		);
	}

	protected function get_processed_restores($this_backup_id = null) {
		$all_restores = $this->db->get_results(
			$this->db->prepare("
				SELECT *
				FROM {$this->db->base_prefix}wptc_processed_{$this->getRestoreTableName()} ")
		);

		return $all_restores;
	}

	protected function getBackups($this_backup_id = null, $backups_view = false, $specific_dir = null, $get_files_count = null) {

		if($this->is_existing_users_rev_limit_hold_expired()){
			$revision_limit = $this->config->get_option('revision_limit');
		} else {
			$revision_limit = WPTC_DEFAULT_MAX_REVISION_LIMIT;
		}

		if (empty($revision_limit)) {
			$revision_limit = WPTC_FALLBACK_REVISION_LIMIT_DAYS;
			$this->config->set_option('revision_limit', WPTC_FALLBACK_REVISION_LIMIT_DAYS);
		}

		$last_month_time = strtotime(date('Y-m-d', strtotime('today - '.$revision_limit.' days')));

		if (!empty($get_files_count)) {
			$sql = $this->db->prepare("
					SELECT count(*)
					FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()}
					WHERE {$this->getBackupID()} = %s AND is_dir = 0 ", $this_backup_id);
			$count =  $this->db->get_var($sql);
			return ($count) ? $count : 0 ;

		} else if ($backups_view == true && !empty($this_backup_id)) {
			$sql = $this->db->prepare("
					SELECT *
					FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()}
					WHERE {$this->getBackupID()} = %s AND parent_dir = %s ", $this_backup_id, wp_normalize_path($specific_dir)
				);

			$all_backups = $this->db->get_results($sql);
		} else if (empty($this_backup_id)) {
			$all_backups = $this->db->get_results(
				$this->db->prepare("
				SELECT DISTINCT backupID
				FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()}
				WHERE {$this->getBackupID()} > %s ", $last_month_time)
			);
		} else {
			$all_backups = $this->db->get_results(
				$this->db->prepare("
				SELECT *
				FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()}
				WHERE {$this->getBackupID()} = %s ", $this_backup_id)
			);
		}

		if (empty($this_backup_id)) {
			$this->filter_backups($all_backups);
		}

		return $all_backups;
	}

	private function filter_backups(&$all_backups){
		if (empty($all_backups) || !is_array($all_backups)) {
			return ;
		}

		$available_backups = $this->db->get_results("
				SELECT backup_id
				FROM {$this->db->base_prefix}wptc_backups", ARRAY_N);

		if (empty($available_backups)) {
			return ;
		}

		$available_backups = call_user_func_array('array_merge', $available_backups);

		if (empty($available_backups)) {
			return ;
		}

		foreach ($all_backups as $key => $backup) {
			if(in_array($backup->backupID, $available_backups) === false ){
				unset($all_backups[$key]);
			}
		}

	}

	protected function get_db_backups($this_backup_id, $path) {
		wptc_remove_abspath($path);

		$sql_file = DB_NAME . '-backup.sql';

		$sql = $this->db->prepare("
					SELECT *
					FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()}
					WHERE {$this->getBackupID()} = %s AND parent_dir = %s AND file LIKE '%%%s%%'", $this_backup_id, $path, $sql_file
				);

		$all_backups = $this->db->get_results($sql);

		if (!empty($all_backups)) {
			return json_decode(json_encode($all_backups));
		}

		if(!WPTC_BACKWARD_DB_SEARCH){
			return $all_backups;
		}

		// wptc_add_abspath($path);

		$sql = $this->db->prepare("
			SELECT *
			FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()}
			WHERE {$this->getBackupID()} = %s AND parent_dir = %s AND file LIKE '%%%s%%'", $this_backup_id, $path, $sql_file
		);

		$all_backups = $this->db->get_results($sql);

		if (!empty($all_backups)) {
			return json_decode(json_encode($all_backups));
		}

		return $all_backups;
	}

	protected function get_all_restores() {
		$all_restores = $this->db->get_results("
			SELECT *
			FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()}  "//manual
		);
		return $all_restores;
	}

	protected function get_last_backup_id() {
		$all_restores = $this->db->get_var("
			SELECT backup_id
			FROM {$this->db->base_prefix}wptc_backup_names ORDER BY this_id DESC LIMIT 0,1"//manual
		);
		return $all_restores;
	}

	protected function get_limited_restores($file_id) {
		$all_restores = $this->db->get_results("SELECT * FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()} WHERE ( (file_id = $file_id AND offset != 0 ) OR file_id > $file_id ) AND is_future_file = 0 LIMIT 1");
		return $all_restores;
	}

	protected function get_sql_files_iterator_of_folder_by_restore_id($cur_res_b_id, $parent_folder) {
		// $prepared_query = $this->db->prepare(" SELECT revision_id, uploaded_file_size, mtime_during_upload, g_file_id, file_hash, file	FROM {$this->db->base_prefix}wptc_processed_files A WHERE backupID = %f AND file NOT LIKE '%%-wptc-secret%%' AND file NOT LIKE '%%db_meta_data%%' AND file NOT LIKE '%%wptc_saved_queries.sql' AND file LIKE '%%%s%%' AND is_dir = '0' AND NOT EXISTS (SELECT file, revision_id FROM {$this->db->base_prefix}wptc_processed_restored_files B WHERE A.file = B.file)", $cur_res_b_id, $parent_folder); //tricky; used like to get  childs from path.
		$prepared_query = $this->db->prepare("SELECT revision_number, revision_id, uploaded_file_size, mtime_during_upload, g_file_id, file_hash, file, MAX(file_id) file_id FROM ( SELECT revision_number, revision_id, uploaded_file_size, mtime_during_upload, g_file_id, file_hash, file, file_id FROM {$this->db->base_prefix}wptc_processed_files A WHERE backupID = %f AND file NOT LIKE '%%-wptc-secret%%' AND file NOT LIKE '%%db_meta_data%%' AND file NOT LIKE '%%wptc_saved_queries.sql' AND file LIKE '%%%s%%' AND is_dir = '0' AND NOT EXISTS (SELECT file, revision_id FROM {$this->db->base_prefix}wptc_processed_restored_files B WHERE A.file = B.file ORDER BY file_id DESC ) ) A GROUP BY file ORDER BY file_id DESC", $cur_res_b_id, $parent_folder); //tricky; used like to get  childs from path.

		wptc_log($prepared_query, '---------------$prepared_query-----------------');

		return $this->db->wptc_do_query($prepared_query);
	}

	protected function get_sql_files_iterator_for_whole_site_by_restore_id($cur_res_b_id, $offset) {
		// $prepared_query = $this->db->prepare("SELECT revision_id, uploaded_file_size, mtime_during_upload, g_file_id, file_hash, file	FROM {$this->db->base_prefix}wptc_processed_files A WHERE backupID <= %f AND file NOT LIKE '%%-wptc-secret%%' AND file NOT LIKE '%%wptc_saved_queries.sql' AND file NOT LIKE '%%db_meta_data%%' AND  is_dir = %d AND offset = %d ORDER BY file_id DESC", $cur_res_b_id, 0, 0);
		$prepared_query = $this->db->prepare("SELECT revision_number, revision_id, uploaded_file_size, mtime_during_upload, g_file_id, file_hash, file, MAX(file_id) file_id FROM (SELECT revision_number, revision_id, uploaded_file_size, mtime_during_upload, g_file_id, file_hash, file, file_id FROM {$this->db->base_prefix}wptc_processed_files A WHERE backupID <= %f AND file NOT LIKE '%%-wptc-secret%%' AND file NOT LIKE '%%wptc_saved_queries.sql' AND file NOT LIKE '%%db_meta_data%%' AND  is_dir = %d AND offset = %d ORDER BY file_id DESC ) A GROUP BY file ORDER BY file_id DESC", $cur_res_b_id, 0, 0);

		wptc_log($prepared_query, '---------------$prepared_query-----------------');

		return $this->db->wptc_do_query($prepared_query);
	}

	protected function upsert($data) {

		if (!empty($data[$this->getUploadMtime()])) {
			//am introducing this condition to avoid conflicts with multipart upload     manual
			//am adding an extra condition to check the modified time (if the modified time is different then add the values to DB or else leave it)
			$exists = $this->db->get_var(
				$this->db->prepare("SELECT * FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s AND {$this->getBackupID()} = %s", $data[$this->getId()], $data['backupID']));
		} else {

			if (isset($data['is_dir']) && $data['is_dir'] == 1) {
				$exists = $this->db->get_var(
					$this->db->prepare("SELECT * FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s  AND {$this->getBackupID()} = %s", $data[$this->getId()], $data['backupID']));
			} else {
				if( ($this->getTableName() === 'restored_files' || $this->getTableName() === 'files' ) &&  empty($data['revision_id'])){
					$data['revision_id'] = '';
				}

				$exists = $this->db->get_var(
					$this->db->prepare("SELECT * FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s  AND {$this->getRevisionId()} = %s", $data[$this->getId()], $data[$this->getRevisionId()])); //must be used only for restoring , i guess
			}
		}

		$last_restore = $this->config->get_option('last_process_restore');
		$restore_progress = $this->config->get_option('in_progress_restore');

		$upsert_result = false;
		if (is_null($exists) || ($last_restore && !$restore_progress)) {
			if (!$this->config->get_option('starting_first_backup') && !$restore_progress && $this->getTableName() != 'iterator') {
				$this->update_life_span($data);
			}
			// wptc_log($data, '---------$data------------');
			// wptc_log($this->getTableName(), '---------$this->getTableName()------------');
			if ($this->getTableName() === 'iterator') {
				$result = $this->db->delete("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", array( 'name' => $data['name'] ));
				// wptc_log($result, '---------delete $result------------');
				$upsert_result = $this->db->insert("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", $data);
			} else {
				$upsert_result = $this->db->insert("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", $data);
			}

		} else {
			if (isset($data['is_dir']) && $data['is_dir'] == 1) {

				$upsert_result = $this->db->update("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", $data, array($this->getId() => $data[$this->getId()], 'backupID' => $data['backupID'])); //am changing the whole update process to file_id
				global $wpdb;
			} else {
				if (!empty($data['file_id'])) {
					$upsert_result = $this->db->update("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", $data, array($this->getFileId() => $data[$this->getFileId()])); //am changing the whole update process to file_id
				} else {
					if (isset($data[$this->getRevisionId()]) && isset($data['uploaded_file_size']) && ($data['uploaded_file_size'] > 4024000)) {
						$upsert_result = $this->db->update("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", $data, array($this->getId() => $data[$this->getId()] , 'backupID' => $data['backupID'])); //am changing the whole update process to file_id
					} else {
						$temp_data = $data;
						unset($temp_data['file']);
						$upsert_result = $this->db->update("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", $temp_data, array($this->getId() => $data[$this->getId()], $this->getRevisionId() => $data[$this->getRevisionId()])); //am changing the whole update process to file_id
					}
				}
			}
		}
	}

	public function update_life_span($data){

		if (empty($data[$this->getId()]) || (isset($data['is_dir']) && $data['is_dir'] !== 0)) {
			return false;
		}

		$query = $this->db->prepare(
						"SELECT file_id FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getBackupID()} < %s AND {$this->getId()} = %s ORDER BY `file_id` DESC LIMIT 1", $data[$this->getBackupID()], $data[$this->getId()]
						);

		$file_id = 	$this->db->get_var(
						$query
					); //to get previous version of file

		if (empty($file_id)) {
			return false;
		}

		$new_life_span = $data[$this->getBackupID()] + $this->default_life_span; // 150 days life span
		$update_life_span = array($this->getLifeSpan() => $new_life_span);
		$this->db->update("{$this->db->base_prefix}wptc_processed_{$this->getTableName()}", $update_life_span , array($this->getFileId() => $file_id)); //am updating new life span for prev revision
	}

	public function truncate() {
		$this->db->query("TRUNCATE {$this->db->base_prefix}wptc_processed_{$this->getTableName()}");
	}

	public function get_stored_backup_name($backup_id = null) {
		$this_name = $this->db->get_results("SELECT backup_name FROM {$this->db->base_prefix}wptc_backup_names WHERE backup_id = '$backup_id'");
		if (isset($this_name[0])) {
			return $this_name[0]->backup_name;
		} else {
			return '';
		}
	}

	public function get_backup_id_details($backup_id) {
		$this_name = $this->db->get_results("SELECT * FROM {$this->db->base_prefix}wptc_processed_{$this->getTableName()} WHERE backup_id = '$backup_id'");
	}

	public function delete_expired_life_span_backups($days = null, $backup_id = null) {
		$current_time = time();
		$delete_expired_life_span_backups = $this->db->query("DELETE FROM {$this->db->base_prefix}wptc_processed_files WHERE life_span IS NOT NULL AND life_span != '' AND life_span < $current_time");
		wptc_log($delete_expired_life_span_backups, '---------------$delete_expired_life_span_backups-----------------');
	}

	public function delete_incompleted_chunks($days = null, $backup_id = null) {
		$delete_incompleted_chunks = $this->db->query("DELETE FROM {$this->db->base_prefix}wptc_processed_files WHERE `offset` != '0'");
		wptc_log($delete_incompleted_chunks, '--------$delete_incompleted_chunks--------');
	}

	public function delete_old_backups($days = null, $backup_id = null) {
		$revision_limit = WPTC_KEEP_MAX_BACKUP_DAYS_LIMIT;

		$rev_limit_time = strtotime("-$revision_limit days");

		$wptc_backups = $this->db->query("DELETE FROM {$this->db->base_prefix}wptc_backups WHERE backup_id < '$rev_limit_time'");
		wptc_log($wptc_backups, '-----wptc_backups deleted rows count-----------');

		$wptc_backup_names = $this->db->query("DELETE FROM {$this->db->base_prefix}wptc_backup_names WHERE backup_id < '$rev_limit_time'");
		wptc_log($wptc_backup_names, '-----wptc_backup_names deleted rows count-----------');

		$wptc_processed_files = $this->db->query("DELETE FROM {$this->db->base_prefix}wptc_processed_files WHERE is_dir = 1 AND backupID < '$rev_limit_time'");
		wptc_log($wptc_processed_files, '-----wptc_processed_files deleted rows count-----------');

		$limit_time = strtotime("-15 days");
		$wptc_activity_log = $this->db->query("DELETE FROM {$this->db->base_prefix}wptc_activity_log WHERE action_id < '$limit_time'");
		wptc_log($wptc_activity_log, '-----wptc_activity_log deleted rows count-----------');
	}

	private function is_existing_users_rev_limit_hold_expired(){
		$existing_users_updated_time = WPTC_Factory::get('config')->get_option('existing_users_rev_limit_hold');
		if (empty($existing_users_updated_time)) {
			return true;
		}
		if (time() > ($existing_users_updated_time + $this->existing_users_rev_limit_hold)) {
			// wptc_log(array(), '---------expired------------');
			return true;
		}
		// wptc_log(array(), '---------Not expired------------');
		return false;
	}

	public function hide_db_backup_folder($parent_dir, $backup_id) {

		$parent_dir = wp_normalize_path($parent_dir);

		wptc_remove_abspath($parent_dir);

		$sql = "SELECT count(file) FROM {$this->db->base_prefix}wptc_processed_files WHERE parent_dir = '$parent_dir' AND backupID = '$backup_id'";
		$count = $this->db->get_var($sql);

		if (empty($count) && WPTC_BACKWARD_DB_SEARCH) {
			// wptc_add_abspath($parent_dir);
			$sql = "SELECT count(file) FROM {$this->db->base_prefix}wptc_processed_files WHERE parent_dir = '$parent_dir' AND backupID = '$backup_id'";
			$count = $this->db->get_var($sql);
		}

		if ($count <= 1) {
			return true;
		}

		return false;
	}

	public function get_future_delete_files($backup_id) {
		$delete_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->base_prefix}wptc_processed_files WHERE backupID > '$backup_id'");
		return $delete_files;
	}

	public function get_most_recent_revision($file, $backup_id = '') {
		wptc_remove_abspath($file);
		$this_revision = $this->db->get_results(
					$this->db->prepare(
						"SELECT file, revision_id, uploaded_file_size, mtime_during_upload, g_file_id,file_hash FROM {$this->db->base_prefix}wptc_processed_files WHERE file = %s AND offset = %d AND backupID <= %s ORDER BY file_id DESC LIMIT 0,1 ", $file, 0, $backup_id
					)
				);

		if (!empty($this_revision)) {
			return !empty($this_revision[0]) ? $this_revision[0] : $this_revision;
		}

		if (!WPTC_BACKWARD_DB_SEARCH) {
			return false;
		}

		// wptc_add_abspath($file);
		$this_revision = $this->db->get_results(

		$this->db->prepare(
			"SELECT revision_id,uploaded_file_size,mtime_during_upload,g_file_id,file_hash FROM {$this->db->base_prefix}wptc_processed_files WHERE file = %s AND offset = %d AND backupID <= %s ORDER BY file_id DESC LIMIT 0,1 ", $file, 0, $backup_id
			)
		);

		if (empty($this_revision)) {
			return false;
		}

		return !empty($this_revision[0]) ? $this_revision[0] : $this_revision;
	}

	public function get_past_replace_files($backup_id) {
		//$replace_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->base_prefix}wptc_processed_files WHERE NOT EXISTS (SELECT DISTINCT file FROM {$this->db->base_prefix}wptc_processed_files WHERE backupID > '$backup_id')  AND backupID < '$backup_id'" );
		$replace_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->base_prefix}wptc_processed_files WHERE backupID < '$backup_id'");

		return $replace_files;
	}

	public function get_all_processed_files() {
		$unknown_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->base_prefix}wptc_processed_files", ARRAY_N);
		return $unknown_files;
	}

	public function get_all_processed_files_from_and_before_now($backup_id) {
		$unknown_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->base_prefix}wptc_processed_files WHERE backupID <= '$backup_id'", ARRAY_N);
		return $unknown_files;
	}

	public function get_file_uploaded_file_size($file, $g_file_id) {

		wptc_remove_abspath($file);

		$uploaded_file_size = $this->db->get_var(
									$this->db->prepare(
										"SELECT uploaded_file_size FROM {$this->db->base_prefix}wptc_processed_files WHERE file = %s AND g_file_id <= %s ORDER BY file_id DESC LIMIT 0,1 ", $file, $g_file_id
									)
								);

		if (!empty($uploaded_file_size)) {
			return $uploaded_file_size;
		}

		if(!WPTC_BACKWARD_DB_SEARCH){
			return false;
		}

		// wptc_add_abspath($file);

		return $this->db->get_var(
					$this->db->prepare(
						"SELECT uploaded_file_size FROM {$this->db->base_prefix}wptc_processed_files WHERE file = %s AND g_file_id <= %s ORDER BY file_id DESC LIMIT 0,1 ", $file, $g_file_id
					)
				);

	}

	public function get_parent_details($file){
		$parent_dir = $this->get_parent_dir($file);
		$is_present = $this->is_file_present_in_backup($parent_dir);
		if (!empty($is_present)) {
			$g_file_id = $this->get_g_file_id($parent_dir);
		} else {
			$g_file_id = false;
		}
		return array('parent_dir' => $parent_dir, 'is_present' => $is_present, 'g_file_id' => $g_file_id);
	}

	public function get_g_file_id($file){

		if (empty($file)) {
			return false;
		}

		$file = wp_normalize_path($file);

		wptc_remove_abspath($file);

		$g_file_id = $this->db->get_var(
					$this->db->prepare(
						"SELECT g_file_id
						FROM {$this->db->base_prefix}wptc_processed_files
						WHERE file = %s AND g_file_id != 'NULL'", $file
					)
				);

		if (!empty($g_file_id)) {
			return $g_file_id;
		}

		if(!WPTC_BACKWARD_DB_SEARCH){
			return false;
		}

		// wptc_add_abspath($file);

		return $this->db->get_var(
				$this->db->prepare(
					"SELECT g_file_id
					FROM {$this->db->base_prefix}wptc_processed_files
					WHERE file = %s AND g_file_id != 'NULL'", $file
				)
			);

	}

	public function get_parent_dir($file) {
		return dirname($file);
	}

	public function is_file_present_in_backup($file) {

		$file = wp_normalize_path($file);

		wptc_remove_abspath($file);

		$count = $this->db->get_var(
					$this->db->prepare("
						SELECT count(file)
						FROM {$this->db->base_prefix}wptc_processed_files
						WHERE file = %s ", $file
					)
				);

		if (!empty($count)) {
			return $count;
		}

		if(!WPTC_BACKWARD_DB_SEARCH){
			return false;
		}

		// wptc_add_abspath($file);

		return $this->db->get_var(
				$this->db->prepare("
					SELECT count(file)
					FROM {$this->db->base_prefix}wptc_processed_files
					WHERE file = %s ", $file
				)
			);
	}

	public function update_g_file_id($file, $g_file_id){
		$data['file'] = $file;
		$data['g_file_id'] = $g_file_id;
		$upsert_result = $this->db->query("UPDATE `{$this->db->base_prefix}wptc_processed_files` SET g_file_id = '$g_file_id' WHERE file = '$file'");
		wptc_log($upsert_result,'--------$upsert_result-------------------');
	}

	public function insert_g_file_id($file, $g_file_id){
		$data['file'] = $file;
		$data['g_file_id'] = $g_file_id;
		$data['backupID'] = getTcCookie('backupID');
		$insert_result = $this->db->replace("{$this->db->base_prefix}wptc_processed_files", $data);
		// wptc_log($insert_result,'--------------$insert_result-------------');
	}

	public function record_as_skimmed($file_dets) {
		foreach ($file_dets as $file) {
			$upsert_array = array(
				'file' => $file->file,
				'backupID' => getTcCookie('backupID'),
			);
			$this->db->insert("{$this->db->base_prefix}wptc_skimmed_files", $upsert_array);
		}
	}

	public function record_as_deleted($file_dets) {
		foreach ($file_dets as $file) {
			$upsert_array = array(
				'file' => $file->file,
				'backupID' => getTcCookie('backupID'),
			);
			$this->db->insert("{$this->db->base_prefix}wptc_skimmed_files", $upsert_array);
		}
	}

	public function get_last_meta_file(){
		// $sql = "SELECT * FROM {$this->db->base_prefix}wptc_processed_files WHERE file LIKE '%selva%' ORDER BY file DESC";
		$sql = "SELECT * FROM {$this->db->base_prefix}wptc_processed_files WHERE file LIKE '%db_meta_data%' ORDER BY file_id DESC";
		$result = $this->db->get_results($sql);
		if (!empty($result) && is_array($result)) {
			return $result[0];
		} else {
			return false;
		}
	}
}