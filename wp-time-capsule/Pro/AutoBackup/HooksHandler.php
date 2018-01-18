<?php

class Wptc_Auto_Backup_Hooks_Hanlder extends Wptc_Base_Hooks_Handler{
	protected $autobackup;
	protected $config;

	public function __construct() {
		$this->autobackup = WPTC_Pro_Factory::get('Wptc_Auto_Backup');
		$this->config = WPTC_Pro_Factory::get('Wptc_Auto_Backup_Config');
	}

	public function update_handler($data) {
		return $this->autobackup->update_handler($data);
	}

	public function update_handler_filters($data, $dets = false) {
		return $this->autobackup->update_handler_filters($data, $dets);
	}

	public function install_handler_filters($data, $dets) {

		return $this->autobackup->install_handler_filters($data, $dets);
	}

	public function upload_handler($data) {
		return $this->autobackup->upload_handler($data);
	}

	public function upload_handler_filters($data) {
		return $this->autobackup->upload_handler_filters($data);
	}

	public function plugin_theme_install_update_handler($updated_data, $options) {
		return $this->autobackup->plugin_theme_install_update_handler($updated_data, $options);
	}

	public function edit_handler_options_table($option_name, $old, $new) {
		return $this->autobackup->edit_handler_options_table($option_name, $old, $new);
	}

	public function translation_update_make_up($update, $language_update) {
		return $this->autobackup->translation_update_make_up($update, $language_update);
	}

	public function add_auto_backup_record_to_backup() {
		return $this->autobackup->add_auto_backup_record_to_backup();
	}

	public function record_auto_backup_complete($backup_id) {
		return $this->autobackup->record_auto_backup_complete($backup_id);
	}

	public function update_auto_backup_record_db() {
		return $this->autobackup->update_auto_backup_record_db();
	}

	public function start_auto_backup() {
		return $this->autobackup->start_auto_backup();
	}

	public function force_stop_reset_autobackup_wptc_h() {
		return $this->autobackup->force_stop_reset_autobackup();
	}

	public function is_auto_backup_running() {
		return $this->autobackup->is_auto_backup_running();
	}

	public function get_backup_slots($backup_timing) {
		return $this->autobackup->get_backup_slots($backup_timing);
	}

	public function check_requirements() {
		return $this->autobackup->check_requirements();
	}

	public function inside_backup_type_settings_wptc_h($more_tables_div, $dets1 = null, $dets2 = null, $dets3 = null) {
		$current_setting = $this->config->get_option('backup_type_setting');
		$is_checked = '';
		if ($current_setting == 'AUTOBACKUP') {
			$is_checked = 'selected';
		}
		return "<option value='AUTOBACKUP' ".$is_checked.">Auto Backup</option>";
	}

	public function validate_auto_backup($die = false) {
		return $this->autobackup->validate_auto_backup($die);
	}
}