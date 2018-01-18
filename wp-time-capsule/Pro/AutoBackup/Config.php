<?php

Class Wptc_Auto_Backup_Config extends Wptc_Base_Config {
	public function __construct() {
		$this->init();
	}

	private function init() {
		$this->set_used_options();
	}

	protected function set_used_options() {
		$this->used_options = array(
			'wptcrquery_secret' => '',
			'auto_update_history' => '',
			'last_backup_time' => '',
			'auto_backup_running' => '',
			'in_progress_restore' => '',
			'is_running' => '',
			'backup_db_path' => '',
			'wptc_current_backup_type' => '',
			'last_backup_ping' => '',
			'backup_type_setting' => '',
			'last_auto_backup_started' => '',
			'backup_slot' => 'retainable',
			'last_auto_backup_started' => 'retainable',
		);
	}

}