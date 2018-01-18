<?php

class Wptc_Backup_Before_Update_Hooks_Hanlder extends Wptc_Base_Hooks_Handler {
	const JS_URL = '/Pro/BackupBeforeUpdate/init.js';

	protected $config;
	protected $backup_before_update_obj;
	protected $backup_before_auto_update_obj;
	protected $backup_before_auto_update_settings;
	protected $upgrade_wait_time;

	public function __construct() {
		$this->config = WPTC_Pro_Factory::get('Wptc_Backup_Before_Update_Config');
		$this->upgrade_wait_time = 2 * 60; // 2 min
		$this->backup_before_update_obj = WPTC_Pro_Factory::get('Wptc_Backup_Before_Update');
		$this->backup_before_auto_update_obj = WPTC_Pro_Factory::get('Wptc_Backup_Before_Auto_Update');
		$this->backup_before_auto_update_settings = WPTC_Pro_Factory::get('Wptc_Backup_Before_Auto_Update_Settings');
		$this->install_actions_wptc();
	}

	//WPTC's specific hooks start

	public function just_initialized_fresh_backup_wptc_h($args) {
		wptc_log($args, '-------just_initialized_fresh_backup_wptc_h--------');

		$this->backup_before_update_obj->check_and_initiate_if_update_required_after_backup_wptc($args);
	}

	public function do_auto_updates($arg1 = null, $arg2 = null) {

		if (!$this->config->get_option('started_backup_before_auto_update')) {
			//Need atleast 13 secs to start a upgrade
			if(is_wptc_timeout_cut(false, 10)){
				$this->config->set_option('upgrade_process_running', false);
				send_response_wptc('NEED_FRESH_REQUEST_TO_CHECK_UPDATES', 'BACKUP');
			}

			wptc_log(array(), '--------started_backup_before_auto_update 1-------------');
			if ($this->config->get_option('is_bulk_update_request')) {
				$this->backup_before_update_obj->do_bulk_upgrade_request();
			} else {
				$this->backup_before_update_obj->do_update_after_backup_wptc();
			}
		} else {
			//Need atleast 19 secs to start a upgrade a auto backup plugin
			if(is_wptc_timeout_cut(false, 10)){
				$this->config->set_option('upgrade_process_running', false);
				send_response_wptc('NEED_FRESH_REQUEST_TO_CHECK_UPDATES', 'BACKUP');
			}

			wptc_log(array(), '--------started_backup_before_auto_update 2-------------');
			$this->backup_before_auto_update_obj->do_auto_update_after_backup_wptc();
		}

		$this->config->flush();
	}
	public function site_transient_update_plugins_h($value, $url){
		// wptc_log($value, '---------$value------------');
		// if (stripos($url, 'https://downloads.wordpress.org/plugin/') === 0) {
		// 	wptc_log(array(), '---------PLUGIN UPDATE------------');
		// 	$data = explode('.' ,str_replace('https://downloads.wordpress.org/plugin/', '', $url));
		// 	// wptc_log($data[0], '---------Plugin name------------');
		// 	// wptc_log($value, '---------$value------------');
		// 	// return false;
		// } else if (stripos($url, 'https://downloads.wordpress.org/theme/') === 0) {
		// 	wptc_log(array(), '---------THEME UPDATE------------');
		// 	$data = explode('.' ,str_replace('https://downloads.wordpress.org/theme/', '', $url));
		// 	// wptc_log($data[0], '---------Theme name------------');
		// 	// wptc_log($value, '---------$value------------');
		// 	// return false;
		// } else if (stripos($url, 'https://downloads.wordpress.org/release/') === 0) {
		// 	wptc_log(array(), '---------CORE UPDATE------------');
		// 	// return false;
		// 	//process once normal core update and check what data needs to duplicated
		// } else if (stripos($url, 'https://downloads.wordpress.org/translation/') === 0) {
		// 	wptc_log(array(), '---------TRANSLATION UPDATE------------');
		// 	//simply invoke following function upgrade_translation_wptc();
		// }
		return $value;
	}


	public function page_settings_content($more_tables_div, $dets1 = null, $dets2 = null, $dets3 = null) {

		$current_setting = $this->config->get_option('backup_before_update_setting');

		$more_tables_div .= '
		<div class="table ui-tabs-hide" id="wp-time-capsule-tab-bbu"> <p></p>
			<table class="form-table">
				<tr>
					<th scope="row"> '.__( 'Backup before manual updates', 'wp-time-capsule' ).'
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">Backup before manual updates</legend>
							<label title="Always">' .
				get_checkbox_input_wptc('backup_before_update_always', 'always', $current_setting, 'backup_before_update_setting') .
				'<span class="">
									'.__( 'Always', 'wp-time-capsule' ).'
								</span>
							</label>
							<p class="description">'.__( 'A backup of the changed files will be taken before updating the core, plugins or themes', 'wp-time-capsule' ).'</p>
						</fieldset>
					</td>
				</tr>';
		$more_tables_div .= $this->get_auto_update_settings_html($current_setting);
		$more_tables_div .= '</table>
		</div>';
		return $more_tables_div;
	}


	public function page_settings_tab($tabs){
		$tabs['bbu'] = __( 'Backup/Auto Updates', 'wp-time-capsule' );
		return $tabs;
	}

	public function may_be_prevent_auto_update($is_update_required, $update_details = null, $dets2 = null, $dets3 = null) {
		wptc_log($update_details, '---------$update_details auto-update-backup------------');

		if (!$this->backup_before_auto_update_settings->is_backup_required_before_auto_update()) {
			wptc_log(array(), "------is_backup_required_before_auto_update failed--------");
			return false;
		}

		if (is_any_ongoing_wptc_restore_process() || is_any_other_wptc_process_going_on()) {
			wptc_log(array(), "------Already Some process going on cannot auto update now--------");
			return false;
		}

		if ($this->backup_before_auto_update_obj->is_backup_running_already_for_auto_update()) {
			wptc_log(array(), '---------is_backup_running_already_for_auto_update------------');
			return false;
		}

		if ($this->config->get_option('auto_update_queue')) {
			wptc_log(array(), '---------auto_update_queue is full------------');
			return false;
		}

		if (is_any_ongoing_wptc_backup_process()) {
			wptc_log(array(), '---------is_any_ongoing_wptc_backup_process------------');
			return false;
		}

		if (!$this->backup_before_auto_update_settings->is_allowed_to_auto_update($update_details)) {
			wptc_log($update_details, '---------$update_details is_allowed_to_auto_update------------');
			wptc_log(array(), '---------This update rejected------------');
			return false; // this update not enabled
		}

		$this->config->set_option('started_backup_before_auto_update', true);
		$this->backup_before_auto_update_settings->add_auto_update_queue($update_details);
		$testing = $this->config->get_option('auto_update_queue');
		wptc_log($testing, '---------$add_auto_update_queue------------');
		$this->backup_before_auto_update_obj->simulate_fresh_backup_during_auto_update($update_details);

		return false;

		//Do not update anything without wptc knowledge
		// wptc_log(array(), "------auto-update-backup not required--------");

		// return $is_update_required;
	}

	public function automatic_updates_complete($arg1 = '', $arg2 = null, $arg3 = null, $arg4 = null) {
		$this->config->set_option('started_backup_before_auto_update', false);
	}

	// public function wptc_backup_before_update_setting() {

	// 	WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

	// 	$this->backup_before_update_obj->wptc_backup_before_update_setting();
	// }

	public function get_check_to_show_dialog_callback_wptc() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		$current_setting = $this->config->get_option('backup_before_update_setting');

		if ($current_setting == 'always') {
			$backup_status['backup_before_update_setting'] = 'always';
		} else {
			$backup_status['backup_before_update_setting'] = 'everytime';
		}

		if (is_any_ongoing_wptc_restore_process() || is_any_ongoing_wptc_backup_process() || is_any_other_wptc_process_going_on()) {
			$backup_status['is_backup_running'] = 'yes';
		} else {
			$backup_status['is_backup_running'] = 'no';
		}

		die_with_json_encode($backup_status);
	}

	public function enque_js_files() {
		wp_enqueue_script('wptc-backup-before-update', plugins_url() . '/' . WPTC_TC_PLUGIN_NAME . self::JS_URL, array(), WPTC_VERSION);
	}

	public function get_backup_before_update_setting_wptc() {
		return $this->config->get_option('backup_before_update_setting');
	}

	public function get_bbu_note_view() {
		$data = $this->config->get_option('bbu_note_view');
		return empty($data) ? false : unserialize($data);
	}

	public function clear_bbu_notes() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		$this->config->set_option('bbu_note_view', false);
		die(json_encode(array('status' => 'success')));
	}

	public function get_auto_update_settings(){
		return $this->backup_before_auto_update_settings->get_auto_update_settings();
	}

	public function save_bbu_settings(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		$data = $_POST['data'];

		if (! empty( $data['backup_before_update_setting'] ) && $data['backup_before_update_setting'] == 'true') {
			$this->config->set_option('backup_before_update_setting', 'always');
		} else {
			$this->config->set_option('backup_before_update_setting', 'everytime');
		}
		return $this->backup_before_auto_update_settings->update_auto_update_settings($data);
	}

	public function get_auto_update_settings_html($bbu_setting){
		// wptc_log(array(), '---------get_auto_update_settings_html-----------');
		return $this->backup_before_auto_update_settings->get_auto_update_settings_html($bbu_setting);
	}

	public function get_installed_plugins(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		wptc_log(array(), '---------get_installed_plugins-----------');
		$plugins = $this->backup_before_auto_update_settings->get_installed_plugins();
		if ($plugins) {
			die(json_encode($plugins));
		}

	}

	public function get_installed_themes(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		wptc_log(array(), '---------get_installed_themes-----------');
		$themes = $this->backup_before_auto_update_settings->get_installed_themes();
		if ($themes) {
			die(json_encode($themes));
		}
	}

	public function install_actions_wptc(){
		if ($this->config->get_option('run_init_setup_bbu')) {
			$this->config->set_option('run_init_setup_bbu', false);
			return $this->backup_before_auto_update_settings->save_default_settings();
		}
	}

	public function turn_off_auto_update(){
		return $this->backup_before_auto_update_settings->turn_off_auto_update();
	}

	public function auto_update_failed_email_user($data){
		return $this->backup_before_auto_update_obj->auto_update_failed_email_user($data);
	}

	public function force_trigger_auto_updates(){
		if (!$this->backup_before_auto_update_settings->is_backup_required_before_auto_update()) {
			return false;
		}
		return $this->backup_before_auto_update_obj->force_trigger_auto_updates();
	}

	public function is_upgrade_in_progress(){
		$progress = $this->config->get_option('upgrade_process_running');
		if (empty($progress)) {
			return false;
		}

		$progress = $progress + $this->upgrade_wait_time;
		if ($progress < time()) {
			return false;
		}

		return true;
	}

	public function backup_and_update($data){
		return $this->backup_before_update_obj->handle_iwp_update_request($data);
	}

	public function turn_off_themes_auto_updates(){
		return $this->backup_before_auto_update_settings->disable_theme_updates($data);
	}

	public function exclude_paid_plugin_from_au(){
		return $this->backup_before_auto_update_settings->exclude_paid_plugin_from_au($data);
	}

	public function validate_free_paid_items(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		wptc_log($_POST, '--------$_POST--------');
		$update_ptc_type = $_POST['update_ptc_type'];
		$update_items = $_POST['update_items'];
		return $this->backup_before_update_obj->validate_free_paid_items($update_ptc_type, $update_items);
	}

}