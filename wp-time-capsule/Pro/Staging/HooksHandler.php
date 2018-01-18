<?php

class Wptc_Staging_Hooks_Hanlder extends Wptc_Base_Hooks_Handler{
	const JS_URL = '/Pro/Staging/init.js';
	const CSS_URL = '/Pro/Staging/style.css';
	protected $staging;
	protected $config;
	protected $update_in_staging;

	public function __construct() {
		$this->staging = WPTC_Pro_Factory::get('Wptc_Staging');
		$this->config = WPTC_Pro_Factory::get('Wptc_staging_Config');
		$this->update_in_staging = new WPTC_Update_In_Staging();
	}

	public function staging_view(){
		include_once ( WPTC_PLUGIN_DIR . 'Pro/Staging/Views/wptc-staging-options.php' );
	}

	public function init_staging_wptc_h(){
		wptc_log(array(), '-----------init_staging_wptc_h-------------');
		$this->staging->init_staging_wptc_h(true);
	}

	public function get_staging_details(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();
		$details = $this->staging->get_staging_details();
		$details['is_running'] = $this->is_any_staging_process_going_on();
		die_with_json_encode($details, 1);
	}

	public function delete_staging_wptc(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		$this->staging->delete_staging_wptc();
	}

	public function stop_staging_wptc(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		wptc_log(array(), '-----------stop_staging_wptc_h-------------');
		$this->staging->stop_staging_wptc();
	}

	public function send_response_node_staging_wptc_h(){
		$progress_status = $this->config->get_option('staging_progress_status', true);
		$return_array = array('progress_status' => $progress_status);
		send_response_wptc('progress', 'STAGING', $return_array);
	}

	public function get_staging_url_wptc(){

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		wptc_log(array(), '---------get_staging_url_wptc---------------');
		$this->staging->get_staging_url_wptc();
	}

	public function page_settings_tab($tabs){
		$tabs['staging'] = __( 'Staging', 'wp-time-capsule' );
		return $tabs;
	}

	public function add_additional_sub_menus_wptc_h($value=''){
		$text = __('Staging', 'wptc');
		add_submenu_page('wp-time-capsule-monitor', $text, $text, 'activate_plugins', 'wp-time-capsule-staging-options', 'wordpress_time_capsule_staging_options');
	}

	public function is_any_staging_process_going_on($value=''){
		// wptc_log(array(), '---------is_any_staging_process_going_on---------------');
		return $this->staging->is_any_staging_process_going_on();
	}

	public function get_internal_staging_db_prefix($value=''){
		// wptc_log(array(), '---------get_internal_staging_db_prefix---------------');
		return $this->staging->get_staging_details('db_prefix');
	}

	public function is_staging_taken($value=''){
		// wptc_log(array(), '---------get_internal_staging_db_prefix---------------');
		if($this->config->get_option('same_server_staging_status') === 'staging_completed'){
			return true;
		}

		return false;
	}

	public function enque_js_files() {
		wp_enqueue_style('wptc-staging-style', plugins_url() . '/' . WPTC_TC_PLUGIN_NAME . self::CSS_URL, array(), WPTC_VERSION);
		wp_enqueue_script('wptc-staging', plugins_url() . '/' . WPTC_TC_PLUGIN_NAME . self::JS_URL, array(), WPTC_VERSION);
	}

	public function save_stage_n_update() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		wptc_log($_POST, '---------$_POST------------');
		if (empty($_POST['update_items'])) {
			die_with_json_encode(array('status' => 'failed'));
		}
		return $this->update_in_staging->save_stage_n_update($_POST);
	}

	public function force_update_in_staging() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		return $this->staging->force_update_in_staging();
	}

	public function continue_staging() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		return $this->staging->choose_action();
	}

	public function start_fresh_staging() {
		wptc_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		if (empty($_POST['path'])) {
			die_with_json_encode(array('status' => 'error', 'msg' => 'path is missing'));
		}

		return $this->staging->choose_action($_POST['path'], $reqeust_type = 'fresh');
	}

	public function copy_staging() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		return $this->staging->choose_action(false, $reqeust_type = 'copy');
	}

	public function save_staging_settings() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		return $this->staging->save_staging_settings($_POST['data']);
	}

	public function is_staging_need_request() {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		return $this->staging->is_staging_need_request();
	}

	public function process_staging_details_hook($request) {

		WPTC_Base_Factory::get('Wptc_App_Functions')->verify_ajax_requests();

		return $this->staging->process_staging_details_hook($request);
	}

	public function set_options_to_staging_site($name, $value) {
		return $this->staging->set_options_to_staging_site($name, $value);
	}

	public function page_settings_content($more_tables_div, $dets1 = null, $dets2 = null, $dets3 = null) {

		$internal_staging_db_rows_copy_limit = $this->config->get_option('internal_staging_db_rows_copy_limit');
		$internal_staging_db_rows_copy_limit = ($internal_staging_db_rows_copy_limit) ? $internal_staging_db_rows_copy_limit : WPTC_STAGING_DEFAULT_COPY_DB_ROWS_LIMIT ;

		$internal_staging_file_copy_limit = $this->config->get_option('internal_staging_file_copy_limit');
		$internal_staging_file_copy_limit = ($internal_staging_file_copy_limit) ? $internal_staging_file_copy_limit : WPTC_STAGING_DEFAULT_FILE_COPY_LIMIT ;

		$internal_staging_deep_link_limit = $this->config->get_option('internal_staging_deep_link_limit');
		$internal_staging_deep_link_limit = ($internal_staging_deep_link_limit) ? $internal_staging_deep_link_limit : WPTC_STAGING_DEFAULT_DEEP_LINK_REPLACE_LIMIT ;

		$internal_staging_enable_admin_login = $this->config->get_option('internal_staging_enable_admin_login');
		$internal_staging_enable_admin_login = ($internal_staging_enable_admin_login) ? 'checked="checked"' : '';

		$more_tables_div .= '
		<div class="table ui-tabs-hide" id="wp-time-capsule-tab-staging">
			<p style="font-size: 17px;"> These settings are also common to the staging to live process.</p>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="db_rows_clone_limit_wptc">DB rows cloning limit</label>
					</th>
					<td>
						<input name="db_rows_clone_limit_wptc" type="number" min="0" step="1" id="db_rows_clone_limit_wptc" value="'.$internal_staging_db_rows_copy_limit.'" class="medium-text">
					<p class="description">'. __( 'Reduce this number by a few hundred when staging process hangs at <CODE>DB cloning status</CODE>', 'wp-time-capsule' ).' </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="files_clone_limit_wptc">Files cloning limit</label>
					</th>
					<td>
						<input name="files_clone_limit_wptc" type="number" min="0" step="1" id="files_clone_limit_wptc" value="'.$internal_staging_file_copy_limit.'" class="medium-text">
					<p class="description">'. __( 'Reduce this number by a few hundred when staging process hangs at <CODE>Copying files</CODE>', 'wp-time-capsule' ).' </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="deep_link_replace_limit_wptc">Deep Link replacing limit</label>
					</th>
					<td>
						<input name="deep_link_replace_limit_wptc" type="number" min="0" step="1" id="deep_link_replace_limit_wptc" value="'.$internal_staging_deep_link_limit.'" class="medium-text">
					<p class="description">'. __( 'Reduce this number by a few hundred when staging process hangs at <CODE>Replace links</CODE>', 'wp-time-capsule' ).' </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="enable_admin_login_wptc">Enable admin login</label>
					</th>
					<td>
					<input type="checkbox" id="enable_admin_login_wptc" name="enable_admin_login_wptc" value="1" '.$internal_staging_enable_admin_login.'>
					<p class="description">'. __( 'If you want to remove the requirement to login to the staging site you can deactivate it here. If you disable authentication everyone can see your staging site.', 'wp-time-capsule' ).' </p>
					</td>
				</tr>
				';

		return $more_tables_div. '</table> </div>';
	}

}