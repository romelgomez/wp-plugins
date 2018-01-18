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

class WPTC_DatabaseBackup {
	const SELECT_QUERY_LIMIT = 300;
	const WAIT_TIMEOUT = 600; //10 minutes
	const NOT_STARTED = 0;
	const COMPLETE = 1;
	const IN_PROGRESS = 2;

	private $temp,
			$database,
			$config,
			$exclude_class_obj,
			$app_functions,
			$processed_files;

	public function __construct($processed = null) {
		$this->database = WPTC_Factory::db();
		$this->config = WPTC_Factory::get('config');
		$this->processed = $processed ? $processed : new WPTC_Processed_iterator();
		$this->exclude_class_obj = WPTC_Base_Factory::get('Wptc_ExcludeOption');
		$this->app_functions = WPTC_Base_Factory::get('Wptc_App_Functions');
		$this->processed_files = WPTC_Factory::get('processed-files');
		$this->set_wait_timeout();
	}

	public function get_status() {

		if (wptc_is_meta_data_backup()) {
			return self::IN_PROGRESS;
		}

		if ($this->processed->count_complete() == 0) {
			return self::NOT_STARTED;
		}

		$count = $this->processed_files->get_overall_tables();

		if ($this->processed->count_complete() <= $count) {
			return self::IN_PROGRESS;
		}

		return self::COMPLETE;
	}

	public function get_file() {
		if (wptc_is_meta_data_backup()) {
			$file = rtrim($this->config->get_backup_dir(), '/') . '/' . DB_NAME . "-wptc_meta.sql";
		} else {
			$file = rtrim($this->config->get_backup_dir(), '/') . '/' . DB_NAME . "-backup.sql";
		}

		$files = glob($file . '*');

		if (isset($files[0])) {
			return $files[0];
		}

		$prepared_file_name = $file . '.' . WPTC_Factory::secret(DB_NAME);

		return $prepared_file_name;
	}

	private function set_wait_timeout() {
		$this->database->query("SET SESSION wait_timeout=" . self::WAIT_TIMEOUT);
	}

	private function write_db_dump_header() {

		if($this->config->choose_db_backup_path() === false){
			$get_default_backup_dir = $this->config->get_default_backup_dir();
			$msg = sprintf(__("A database backup cannot be created because WordPress does not have write access to '%s', please ensure this directory has write access.", 'wptc'), $get_default_backup_dir);
				WPTC_Factory::get('logger')->log($msg);
				return false;
		}

		//clearing the db file for the first time by simple logic to clear all the contents of the file if it already exists;
		$fh = fopen($this->get_file(), 'a');
		if (ftell($fh) < 2) {
			fclose($fh);
			$fh = fopen($this->get_file(), 'w');
		}
		fwrite($fh, '');
		fclose($fh);

		$blog_time = strtotime(current_time('mysql'));

		$this->write_to_temp("-- WP Time Capsule SQL Dump\n");
		$this->write_to_temp("-- Version " . WPTC_VERSION . "\n");
		$this->write_to_temp("-- https://wptimecapsule.com\n");
		$this->write_to_temp("-- Generation Time: " . date("F j, Y", $blog_time) . " at " . date("H:i", $blog_time) . "\n\n");
		$this->write_to_temp("
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n");
		$this->write_to_temp("CREATE DATABASE IF NOT EXISTS " . DB_NAME . ";\n");
		$this->write_to_temp("USE " . DB_NAME . ";\n\n");

		$this->persist();

		$this->processed->update_iterator('header', -1);
	}

	public function execute() {

		if (!$this->processed->is_complete('header')) {
			$this->write_db_dump_header();
		}

		$backup_id = getTcCookie('backupID');
		// $tables = $this->database->get_results('SHOW TABLES', ARRAY_N);
		$wp_tables = $this->processed_files->get_all_tables();

		foreach ($wp_tables as $tableName) {
			$table_skip_status = $this->exclude_class_obj->is_excluded_table($tableName);

			wptc_log($tableName, '---------------$tableName-----------------');
			wptc_log($table_skip_status, '---------------$table_skip_status-----------------');

			if ($table_skip_status === 'table_excluded') {
				continue;
			}

			if ($this->processed->is_complete($tableName)) {
				continue;
			}

			wptc_log($tableName , '---------------Table not completed-----------------');
			if (is_wptc_table($tableName)) {
				$this->processed->update_iterator($tableName, -1); //Done
				continue;
			}

			$table = $this->processed->get_table($tableName);

			$count = empty($table->offset) ? 0 : $table->offset ;

			if ($count > 0) {
				WPTC_Factory::get('logger')->log(sprintf(__("Resuming table '%s' at row %s.", 'wptc'), $tableName, $count), 'backup_progress', $backup_id);
			}

			$this->backup_database_table($tableName, $count, $table_skip_status);
			WPTC_Factory::get('logger')->log(sprintf(__("Processed table %s.", 'wptc'), $tableName), 'backup_progress', $backup_id);
		}
		$this->write_to_temp("
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n\n");
		$blog_time = strtotime(current_time('mysql'));
		$this->write_to_temp("-- Dump completed on ". date("F j, Y", $blog_time) . " at " . date("H:i", $blog_time) );
		$this->persist();
	}

	public function backup_database_table($table, $offset, $table_skip_status) {

		wptc_manual_debug('', 'start_backup_' . $table);

		$db_error = __('Error while accessing database.', 'wptc');

		if ($offset == 0) {
			$this->write_to_temp("\n--\n-- Table structure for table `$table`\n--\n\n");

			$table_creation_query = '';
			$table_creation_query .= "DROP TABLE IF EXISTS `$table`;";
			$table_creation_query .= "
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;\n";

			$table_create = $this->database->get_row("SHOW CREATE TABLE $table", ARRAY_N);
			if ($table_create === false) {
				throw new Exception($db_error . ' (ERROR_3)');
			}

			$table_creation_query .= $table_create[1].";";
			$table_creation_query .= "\n/*!40101 SET character_set_client = @saved_cs_client */;\n\n";

			if ($table_skip_status !== 'content_excluded') {
				$table_creation_query .= "--\n-- Dumping data for table `$table`\n--\n";
				$table_creation_query .= "\nLOCK TABLES `$table` WRITE;\n";
				$table_creation_query .= "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;";

			}

			$this->write_to_temp($table_creation_query . "\n");
		}

		if ($table_skip_status === 'content_excluded') {
			$this->processed->update_iterator($table, -1); //Done
			return true;
		}

		$row_count = $offset;
		$table_count = $this->database->get_var("SELECT COUNT(*) FROM $table");
		$columns = $this->database->get_results("SHOW COLUMNS IN `$table`", OBJECT_K);

		if ($table_count != 0) {
			for ($i = $offset; $i < $table_count; $i = $i + self::SELECT_QUERY_LIMIT) {

				wptc_manual_debug('', 'during_db_backup', 1000);

				$table_data = $this->database->get_results("SELECT * FROM $table LIMIT " . self::SELECT_QUERY_LIMIT . " OFFSET $i", ARRAY_A);
				if ($table_data === false || !is_array($table_data[0])) {
					throw new Exception($db_error . ' (ERROR_4)');
				}

				$out = '';
				foreach ($table_data as $key => $row) {
					$data_out = $this->create_row_insert_statement($table, $row, $columns);
					$out .= $data_out;
					$row_count++;
				}

				$this->write_to_temp($out);

				if ($row_count >= $table_count) {
					$this->processed->update_iterator($table, -1); //Done
				} else {
					$this->processed->update_iterator($table, $row_count);
				}

				$this->persist();

				wptc_log($table . ' - ' . $row_count, '---------Table backing up-----------------------');

				if($this->app_functions->is_backup_request_timeout($return = true, true)){
					send_response_wptc('Backing up table' . $table . ' (Offset : ' .$row_count . ')' );
				}

			}
		}
		$this->processed->update_iterator($table, -1); //Done
		$this->write_to_temp("/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n");
		$this->write_to_temp("UNLOCK TABLES;\n");
		$this->persist();
		return true;
	}

	protected function create_row_insert_statement( $tableName, array $row, array $columns = array()) {
		$values = $this->create_row_insert_values($row, $columns);
		$joined = join(', ', $values);
		$sql    = "INSERT INTO `$tableName` VALUES($joined);\n";
		return $sql;
	}

	protected function create_row_insert_values($row, $columns) {
		$values = array();

		foreach ($row as $columnName => $value) {
			$type = $columns[$columnName]->Type;
			// If it should not be enclosed
			if ($value === null) {
				$values[] = 'null';
			} elseif (strpos($type, 'int') !== false
				|| strpos($type, 'float') !== false
				|| strpos($type, 'double') !== false
				|| strpos($type, 'decimal') !== false
				|| strpos($type, 'bool') !== false
			) {
				$values[] = $value;
			} elseif (strpos($type, 'blob') !== false) {
				$values[] = strlen( $value ) ? ( '0x' . $value ) : "''";
			} elseif (strpos($type, 'binary') !== false) {
				$values[] = strlen($value) ? "UNHEX('" . $value . "')" : "''";
			} else {
				/*
					there is a behaviour change of esc_sql()
					https://make.wordpress.org/core/2017/10/31/changed-behaviour-of-esc_sql-in-wordpress-4-8-3/
				*/
				if ( $this->is_wp_version_greater_than_4_8_3() ) {
					$values[] = "'" . $this->database->remove_placeholder_escape( esc_sql( $value ) ) . "'";
				} else{
					$values[] = "'" . esc_sql( $value ) . "'";
				}
			}
		}

		return $values;
	}

	public function is_wp_version_greater_than_4_8_3(){
		return version_compare($this->app_functions->get_wp_core_version(), '4.8.3', '>=');
	}

	public function shell_db_dump(){
		if(!$this->is_shell_exec_available()){
			return 'failed';
		}

		$status = $this->config->get_option('shell_db_dump_status');

		if ($status === 'failed' || $status === 'error') {
			return 'failed';
		}

		if ($status === 'completed') {
			return 'completed';
		}

		if ($status === 'running') {
			return $this->check_is_shell_db_dump_running();
		}

		wptc_set_time_limit(0);
		$this->config->set_option('shell_db_dump_status', 'running');
		return $this->backup_db_dump();
	}

	private function check_is_shell_db_dump_running(){
		$file = $this->get_file();

		if ( !file_exists($file) ) {
			$this->config->set_option('shell_db_dump_status', 'failed');
			return 'failed';
		}

		$filesize = filesize($file);

		if ($filesize === false) {
			$this->config->set_option('shell_db_dump_status', 'failed');
			return 'failed';
		}

		wptc_log($filesize, '---------------$filesize-----------------');
		wptc_log($this->config->get_option('shell_db_dump_prev_size'), '---------------$prev-----------------');

		if ($this->config->get_option('shell_db_dump_prev_size') === false || $this->config->get_option('shell_db_dump_prev_size') === null) {
			$this->config->set_option('shell_db_dump_prev_size', $filesize );
			return 'running';
		} else if($this->config->get_option('shell_db_dump_prev_size') < $filesize){
			$this->config->set_option('shell_db_dump_prev_size', $filesize );
			return 'running';
		} else {
			return 'failed';
		}
		$this->config->set_option('shell_db_dump_status');
	}

	private function backup_db_dump() {

		$this->mysqldump_structure_only_tables();

		$this->mysqldump_full_tables();

		$file = $this->get_file();

		if (wptc_get_file_size($file) == 0 || !is_file($file)) {
			$this->config->set_option('shell_db_dump_status', 'failed');
			if (file_exists($file)) {
				@unlink($file);
			}
			return 'failed';
		} else {
			$this->config->set_option('shell_db_dump_status', 'completed');
			return 'do_not_continue';
		}
	}

	private function mysqldump_structure_only_tables(){
		$tables = $this->processed_files->get_all_included_tables($structure_only = true);

		if (empty($tables)) {
			return true;
		}

		$tables =  implode("\" \"",$tables);

		$this->exec_mysqldump($tables, $structure_only = '--no-data');
	}

	private function mysqldump_full_tables(){
		$tables = $this->processed_files->get_all_included_tables();

		if (empty($tables)) {
			return true;
		}

		$tables =  implode("\" \"",$tables);

		$this->exec_mysqldump($tables);
	}

	private function exec_mysqldump($tables, $structure_only = ''){

		$file = $this->get_file();
		$paths   = $this->check_mysql_paths();
		$brace   = (substr(PHP_OS, 0, 3) == 'WIN') ? '"' : '';

		$comments = '';
		if (file_exists($file) && filesize($file) > 0) {
			$comments = '--skip-comments'; //assume already comments are dumped
		}

		$command = $brace . $paths['mysqldump'] . $brace . ' --force ' . $comments . ' ' . $structure_only . ' --host="' . DB_HOST . '" --user="' . DB_USER . '" --password="' . DB_PASSWORD . '" --add-drop-table --skip-lock-tables --extended-insert=FALSE "' . DB_NAME . '" "' . $tables . '" >> ' . $brace . $file . $brace;

		wptc_log($command, '---------------$command-----------------');
		return $this->wptc_exec($command);
	}

	### Function: Auto Detect MYSQL and MYSQL Dump Paths
	private function check_mysql_paths() {
		global $wpdb;
		$paths = array(
			'mysql' => '',
			'mysqldump' => ''
		);
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			$mysql_install = $wpdb->get_row("SHOW VARIABLES LIKE 'basedir'");
			if ($mysql_install) {
				$install_path       = str_replace('\\', '/', $mysql_install->Value);
				$paths['mysql']     = $install_path . 'bin/mysql.exe';
				$paths['mysqldump'] = $install_path . 'bin/mysqldump.exe';
			} else {
				$paths['mysql']     = 'mysql.exe';
				$paths['mysqldump'] = 'mysqldump.exe';
			}
		} else {
			$paths['mysql'] = $this->wptc_exec('which mysql', true);
			if (empty($paths['mysql']))
				$paths['mysql'] = 'mysql'; // try anyway

			$paths['mysqldump'] = $this->wptc_exec('which mysqldump', true);
			if (empty($paths['mysqldump']))
				$paths['mysqldump'] = 'mysqldump'; // try anyway

		}
		return $paths;
	}

	private function wptc_exec($command, $string = false, $rawreturn = false) {
		if ($command == '')
			return false;

		if (function_exists('exec')) {
			$log = @exec($command, $output, $return);
			wptc_log($log, '---------------$log-----------------');
			wptc_log($output, '---------------$output-----------------');
			if ($string)
				return $log;
			if ($rawreturn)
				return $return;

			return $return ? false : true;
		} elseif (function_exists('system')) {
			$log = @system($command, $return);
			wptc_log($log, '---------------$log-----------------');

			if ($string)
				return $log;

			if ($rawreturn)
				return $return;

			return $return ? false : true;
		} else if (function_exists('passthru')) {
			$log = passthru($command, $return);
			wptc_log($log, '---------------$log-----------------');

			if ($rawreturn)
				return $return;

			return $return ? false : true;
		}

		if ($rawreturn)
			return -1;

		return false;
	}

	public function is_shell_exec_available() {
		if (in_array(strtolower(ini_get('safe_mode')), array('on', '1'), true) || (!function_exists('exec'))) {
			return false;
		}
		$disabled_functions = explode(',', ini_get('disable_functions'));
		$exec_enabled = !in_array('exec', $disabled_functions);
		return ($exec_enabled) ? true : false;
	}

	private function modify_table_description($table_data){
		$temp_table = array();
		foreach ($table_data as $key => $value) {
			$temp = $table_data[$key];
			$temp_table[$value['Field']] = $table_data[$key];
		}
		return $temp_table;
	}

	private function write_to_temp($out) {
		if (!$this->temp) {
			$this->temp = fopen('php://memory', 'rw');
		}

		if (fwrite($this->temp, $out) === false) {
			throw new Exception(__('Sql Backup : Error writing to php://memory.', 'wptc'));
		}
	}

	private function persist() {

		$file = $this->get_file();
		if (file_exists($file)) {
			$fh = fopen($file, 'a');
		} else {
			$fh = fopen($file, 'w');
		}

		if (!$fh) {
			throw new Exception(__('Sql Backup : Error creating sql dump file.', 'wptc'));
		}

		fseek($this->temp, 0);

		fwrite($fh, stream_get_contents($this->temp));

		if (!fclose($fh)) {
			throw new Exception(__(' Sql Backup : Error closing sql dump file.', 'wptc'));
		}

		if (!fclose($this->temp)) {
			throw new Exception(__(' Sql Backup : Error closing php://memory.', 'wptc'));
		}

		$this->temp = null;
	}


	public function compress(){
		wptc_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		if(!wptc_function_exist('gzwrite') || !wptc_function_exist('gzopen') || !wptc_function_exist('gzclose') ){
			wptc_log(array(), '--------ZGIP not available--------');
			$this->config->set_option('sql_gz_compression', true);
			return ;
		}

		$offset = $this->config->get_option('sql_gz_compression_offset');
		$offset = empty($offset) ? 0 : $offset;

		wptc_log($offset, '--------$offset--------');

		$file = $this->get_file();

		wptc_log($file, '--------$file--------');

		$this->gz_compress_file($file, $offset);
	}

	private function gz_compress_file($source, $offset, $level = 9){
		wptc_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		$dest = $source . '.gz';
		$mode = 'ab' . $level;

		$break = false;

		$fp_out = gzopen($dest, $mode);

		if (empty($fp_out)) {
			return false;
		}

		$fp_in = fopen($source,'rb');

		if (empty($fp_in)) {
			return false;
		}

		fseek($fp_in, $offset);

		while (!feof($fp_in)){

			gzwrite($fp_out, fread($fp_in, 1024 * 1024 * 5)); //read 5MB chunk

			wptc_manual_debug('', 'during_compress_db', 10);

			if($this->app_functions->is_backup_request_timeout($return = true)){
				$break = true;
				$offset = ftell($fp_in);
				break;
			}
		}

		fclose($fp_in);
		gzclose($fp_out);

		if ($break) {
			$this->config->set_option('sql_gz_compression_offset', $offset);
			send_response_wptc('Compressing database file, Offset : ' .$offset );
		}

		wptc_log(array(), '--------Done--------');
		$this->config->set_option('sql_gz_compression', true);
		@unlink($source);
		return ;
	}

	public function is_wp_table($tableName) {
		//ignoring tables other than wordpress table
		$wp_prefix = $this->database->prefix;
		$wptc_strpos = strpos($tableName, $wp_prefix);

		if (false !== $wptc_strpos && $wptc_strpos === 0) {
			return true;
		}
		return false;
	}

	public function clean_up() {
		$file_iterator = new WPTC_File_Iterator();

		$tmp_dir = $this->config->get_backup_dir();

		$file_obj = $file_iterator->get_files_obj_by_path($tmp_dir, true);

		foreach ($file_obj as $file_meta) {

			$file = $file_meta->getPathname();

			if (wptc_is_dir($file)) {
				continue;
			}

			if (basename($file) === 'index.php') {
				continue;
			}

			@unlink($file);
		}

	}

	public function complete_all_tables(){
		$structure_tables = $this->processed_files->get_all_included_tables($structure_only = true);
		wptc_log($structure_tables, '---------------$structure_tables-----------------');
		$full_tables = $this->processed_files->get_all_included_tables();

		wptc_log($full_tables, '---------------$full_tables-----------------');

		$tables = array_merge( $structure_tables, $full_tables );

		wptc_log($tables, '---------------$tables-----------------');

		if (empty($tables)) {
			return ;
		}

		$this->processed->update_iterator('header', -1);

		$query = '';

		foreach ($tables as $table) {
			$query .= empty($query) ? "(" : ", (" ;
			$query .= "NULL, '" . $table . "', -1 )";
		}

		if (empty($query)) {
			return ;
		}

		$update_all_tables = "insert into " . $this->database->base_prefix . "wptc_processed_iterator (id, name, offset) values $query";
		wptc_log($update_all_tables, '---------------$update_all_tables-----------------');
		$result = $this->database->query($update_all_tables);
		wptc_log($result, '---------------$result-----------------');
	}
}
