<?php

class Wptc_Vulns_Hooks_Hanlder extends Wptc_Base_Hooks_Handler {
	protected $vulns;
	protected $config;
	protected $app_functions;

	const JS_URL = '/Pro/Vulns/init.js';

	public function __construct() {
		$this->vulns = WPTC_Base_Factory::get('Wptc_Vulns');
		$this->config = WPTC_Pro_Factory::get('Wptc_Vulns_Config');
		$this->app_functions = WPTC_Base_Factory::get('Wptc_App_Functions');
	}

	public function enque_js_files() {
		wp_enqueue_script('wptc-vulns-update', plugins_url() . '/' . WPTC_TC_PLUGIN_NAME . self::JS_URL, array(), WPTC_VERSION);
	}

	public function run_vulns_check(){
		$this->vulns->run_vulns_check();
	}

	public function page_settings_tab($tabs){
		$tabs['vulns'] = __( 'Vulnerability Updates', 'wp-time-capsule' );
		return $tabs;
	}

	public function page_settings_content($more_tables_div, $dets1 = null, $dets2 = null, $dets3 = null) {
		$vulns_settings = $this->get_vulns_settings();
		$current_setting = (empty($vulns_settings['send_email'])) ? false : true;
		$vulns_status = ($current_setting) ? 'checked="checked"' : '';

		$more_tables_div .= '
		<div class="table ui-tabs-hide" id="wp-time-capsule-tab-vulns"> <p></p>
			<table class="form-table">';
		$more_tables_div .= $this->get_ptc_selector_html($vulns_settings);
		$more_tables_div .= '</table>
		</div>';
		return $more_tables_div;
	}

	public function get_ptc_selector_html($vulns_settings){

		$vulns_status = $vulns_settings['status'];

		$enable_vulns = $disable_vulns = $show_options = '';

		if ($vulns_status == 'yes') {
			$enable_vulns = 'checked';
			$show_options = 'display:block';
		} else {
			$disable_vulns = 'checked';
			$show_options = 'display:none';
		}

		$core = empty($vulns_settings['core']['status']) ? false : true;
		$core_checked = ($core) ? 'checked="checked"' : '';

		$plugins = empty($vulns_settings['plugins']['status']) ? false : true;
		$plugins_checked = ($plugins) ? 'checked="checked"' : '';

		$themes = empty($vulns_settings['themes']['status']) ? false : true ;
		$themes_checked = ($themes) ? 'checked="checked"' : '';

		$header = '
			<tr id="vulns_settings_wptc" valign="top">
				<th scope="row"> '.__( 'Email vulnerability updates', 'wp-time-capsule' ).'</th>
				<td>
					<fieldset>
						<label title="Yes">
							<input name="vulns_wptc_setting"  type="radio" id="enable_vulns_wptc" '.$enable_vulns.' value="yes">
							<span class="">
								'.__( 'Yes', 'wp-time-capsule' ).'
							</span>
						</label>
						<label title="No">
							<input name="vulns_wptc_setting" type="radio" id="disable_vulns_wptc" '.$disable_vulns.' value="no">
							<span class="">
								'.__( 'No', 'wp-time-capsule' ).'
							</span>
						</label>
					</fieldset>
					<fieldset style="'.$show_options.'" id="enable_vulns_options_wptc">';

		$core = '<input type="checkbox" id="wptc_vulns_core" name="wptc_vulns_core" value="1" '.$core_checked.'>
							<label for="wptc_vulns_core">'.__( 'For WordPress', 'wp-time-capsule' ).'</label>
							</div>';

		$plugins = '<p>
						<input type="checkbox" id="wptc_vulns_plugins" name="wptc_vulns_plugins" value="1" '.$plugins_checked.'>
						<label for="wptc_vulns_plugins">'.__( 'For plugins', 'wp-time-capsule' ).'
							<div style="display: none;" id="wptc_vulns_plugins_dw"></div>
							<input style="display: none;" type="hidden" id="vulns_include_plugins_wptc" name="vulns_include_plugins_wptc"/>
						</label>
					</p>';

		$themes = '<p>
						<input type="checkbox" id="wptc_vulns_themes" name="wptc_vulns_themes" value="1" '.$themes_checked.'>
							<label for="wptc_vulns_themes">	'.__( 'For themes', 'wp-time-capsule' ).'
								<div style="display: none;" id="wptc_vulns_themes_dw"></div>
								<input style="display: none;" type="hidden" id="vulns_include_themes_wptc" name="vulns_include_themes_wptc"/>
							</label>
						<p class="description">'.__( 'New Plugins and Themes will be added automatically.', 'wp-time-capsule' ).'</p>
					</p>
					</fieldset>
					</td></tr>';

		//themes removed from the updates
		return $header . $core . $plugins . $themes;
	}

	public function get_enabled_themes(){

		$this->app_functions->verify_ajax_requests();

		$themes = $this->vulns->get_enabled_themes();

		$themes = $this->app_functions->fancytree_format($themes, 'themes');


		if ($themes) {
			die(json_encode($themes));
		}
	}

	public function get_enabled_plugins(){

		$this->app_functions->verify_ajax_requests();

		$plugins = $this->vulns->get_enabled_plugins();

		$plugins = $this->app_functions->fancytree_format($plugins, 'plugins');

		if ($plugins) {
			die(json_encode($plugins));
		}

	}

	public function update_vulns_settings(){

		$this->app_functions->verify_ajax_requests();

		$data = isset($_POST['data']) ? $_POST['data'] : array() ;

		return $this->vulns->update_vulns_settings($data);
	}

	public function get_vulns_settings(){
		return $this->vulns->get_vulns_settings();
	}

	public function get_format_vulns_settings_to_send_server(){
		return $this->vulns->get_format_vulns_settings_to_send_server();
	}
}