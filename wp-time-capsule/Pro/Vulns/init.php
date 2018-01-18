<?php

class Wptc_Vulns extends WPTC_Privileges{
	protected	$config,
				$cron_server_curl,
				$update_common,
				$app_functions;

	public function __construct() {
		$this->config = WPTC_Pro_Factory::get('Wptc_Vulns_Config');
		$this->cron_server_curl = WPTC_Base_Factory::get('Wptc_Cron_Server_Curl_Wrapper');
		$this->app_functions = WPTC_Base_Factory::get('Wptc_App_Functions');
		$this->update_common = WPTC_Base_Factory::get('Wptc_Update_Common');
	}

	public function init(){
		if ($this->is_privileged_feature(get_class($this)) && $this->is_switch_on()) {
			$supposed_hooks_class = get_class($this) . '_Hooks';
			WPTC_Pro_Factory::get($supposed_hooks_class)->register_hooks();
		}
	}

	private function is_switch_on(){
		return true;
	}

	public function run_vulns_check(){
		//if vulns not enabled in the settings then do not run vulns updates
		if(!$this->is_vulns_enabled()){
			return false;
		}

		$upgradable_plugins = $this->get_upgradable_plugins();
		$upgradable_themes = $this->get_upgradable_themes();
		$upgradable_core_arr = $this->get_upgradable_core();

		$upgradable_core = $upgradable_core_arr['update_data'];
		$upgradable_core_deep_data = $upgradable_core_arr['deep_data'];

		$post_arr = array(
			'plugins_data' => $upgradable_plugins,
			'themes_data' => $upgradable_themes,
			'core_data' => $upgradable_core,
			);

		$raw_response = $this->cron_server_curl->do_call('run-vulns-check', $post_arr);

		if (empty($raw_response)) {
			return false;
		}

		$response = json_decode($raw_response);
		$response = $response->vulns_result;

		$plugins = $themes = $core = array();

		if (!empty($response->affectedPlugins)) {
			$plugins = (array) $response->affectedPlugins;
		}

		if(!empty($response->affectedThemes)){
			$themes = (array) $response->affectedThemes;
		}

		if(!empty($response->affectedCores)){
			$core = (array) $response->affectedCores;
		}

		$update_plugins = $this->purify_plugins_for_update($plugins, $upgradable_plugins);
		$update_themes = $this->purify_themes_for_update($themes);
		$update_core = $this->purify_core_for_update($core, $upgradable_core_deep_data);

		$this->prepare_bulk_upgrade_structure($update_plugins, $update_themes, $update_core);

	}

	private function prepare_bulk_upgrade_structure($upgrade_plugins, $upgrade_themes, $wp_upgrade){
		$final_upgrade_details = array();

		if (!empty($upgrade_plugins)) {
			$final_upgrade_details['upgrade_plugins']['update_items'] = $upgrade_plugins;
			$final_upgrade_details['upgrade_plugins']['updates_type'] = 'plugin';
			$final_upgrade_details['upgrade_plugins']['is_auto_update'] = '0';
		}

		if (!empty($upgrade_themes)) {
			$final_upgrade_details['upgrade_themes']['update_items'] = $upgrade_themes;
			$final_upgrade_details['upgrade_themes']['updates_type'] = 'theme';
			$final_upgrade_details['upgrade_themes']['is_auto_update'] = '0';

		}

		if (!empty($wp_upgrade)) {
			$final_upgrade_details['wp_upgrade']['update_items'] = $wp_upgrade;
			$final_upgrade_details['wp_upgrade']['updates_type'] = 'core';
			$final_upgrade_details['wp_upgrade']['is_auto_update'] = '0';
		}

		//Translations does not have vulns updates
		/*if (!empty($upgrade_translations)) {
			$final_upgrade_details['upgrade_translations']['update_items'] = $upgrade_translations;
			$final_upgrade_details['upgrade_translations']['updates_type'] = 'translation';
			$final_upgrade_details['upgrade_translations']['is_auto_update'] = '0';
		}*/

		wptc_log($final_upgrade_details, '--------$final_upgrade_details--------');
		if (empty($final_upgrade_details)) {
			return false;
		}
		// return false;
		$this->bulk_update_request($final_upgrade_details);
		$this->config->set_option('is_bulk_update_request', true);
		$this->config->set_option('backup_before_update_details', false);
		$this->config->set_option('is_vulns_updates', true);
		start_fresh_backup_tc_callback_wptc('manual');
	}

	private function bulk_update_request($bulk_update_request){
		wptc_log($bulk_update_request, '--------$bulk_update_request--------');
		if (empty($bulk_update_request)) {
			return $this->config->set_option('bulk_update_request', false);
		}

		$this->config->set_option('bulk_update_request', serialize($bulk_update_request));
	}

	private function purify_plugins_for_update($plugins_data, $upgradable_plugins){

		$plugins = array();

		if (empty($plugins_data)) {
			return $plugins;
		}

		foreach ($plugins_data as $key => $plugin_data) {
			$plugins[$upgradable_plugins[$key]['path']] = $upgradable_plugins[$key]['version'];
		}

		return $plugins;

	}

	private function purify_themes_for_update($themes_data){

		$themes = array();

		if (empty($themes_data)) {
			return $themes;
		}

		foreach ($themes_data as $key => $theme_data) {
			$themes[] = $key;
		}

		return $themes;

	}

	private function purify_core_for_update($core_data, $upgradable_core_deep_data){
		if (empty($core_data)) {
			return array();
		}

		return $upgradable_core_deep_data;
	}

	public function get_upgradable_plugins() {
		$current = wptc_mmb_get_transient('update_plugins');

		$upgradable_plugins = array();

		if (empty($current->response)) {
			return array();
		}

		if (!function_exists('get_plugin_data')) {
			include_once ABSPATH.'wp-admin/includes/plugin.php';
		}

		foreach ($current->response as $plugin_path => $plugin_data) {
			$data = get_plugin_data(WP_PLUGIN_DIR.'/'.$plugin_path, false, false);

			if (strlen($data['Name']) > 0 && strlen($data['Version']) > 0) {
				$slug = $this->app_functions->shortern_plugin_slug($plugin_path);
				$upgradable_plugins[$slug] = array(
						'path' => $plugin_path,
						'version' => $data['Version'],
						'slug' => $slug,
					);
			}
		}

		return $upgradable_plugins;
	}

	public function get_upgradable_themes() {
		if (function_exists('wp_get_themes')) {
			$all_themes     = wp_get_themes();
			$upgrade_themes = array();

			$current = wptc_mmb_get_transient('update_themes');

			if (empty($current->response)) {
				return $upgrade_themes;
			}

			foreach ((array)$all_themes as $theme_template => $theme_data) {
				foreach ($current->response as $current_themes => $theme) {

					if ($theme_data->Stylesheet !== $current_themes) {
						continue;
					}

					if (strlen($theme_data->Name) === 0 || strlen($theme_data->Version) === 0) {
						continue;
					}

					$upgrade_themes[$current_themes] = array(
							'slug' => $theme_data->Stylesheet,
							'version' => $theme_data->Version,
						);
				}
			}
		} else {

			$all_themes = get_themes();

			$upgrade_themes = array();

			$current = wptc_mmb_get_transient('update_themes');

			if (empty($current->response)) {
				return $upgrade_themes;
			}

			foreach ((array)$all_themes as $theme_template => $theme_data) {

				if (isset($theme_data['Parent Theme']) && !empty($theme_data['Parent Theme'])) {
					continue;
				}

				if (isset($theme_data['Name']) && in_array($theme_data['Name'], $filter)) {
					continue;
				}

				foreach ($current->response as $current_themes => $theme) {
					if ($theme_data['Template'] != $current_themes) {
						continue;
					}

					if (strlen($theme_data['Name']) == 0 || strlen($theme_data['Version']) == 0) {
						continue;
					}

					$upgrade_themes[$current_themes] = array(
							'slug' => $theme_data->Stylesheet,
							'version' => $theme_data->Version,
						);
				}
			}
		}

		return $upgrade_themes;
	}

	private function get_upgradable_core() {
		global $wp_version;

		$upgrade_core = array(
				'update_data' => '',
				'deep_data' => '',
			);

		$core = wptc_mmb_get_transient('update_core');

		if (!isset($core->updates) || empty($core->updates)) {
			return false;
		}

		$current_transient = $core->updates[0];

		if ($current_transient->response == "development" || version_compare($wp_version, $current_transient->current, '<')) {
			$current_transient->current_version = $wp_version;
			$upgrade_core['update_data'][$wp_version] = array('Version' => $wp_version);
			$upgrade_core['deep_data'] = $current_transient;
		}

		return $upgrade_core;
	}

	public function is_vulns_enabled(){
		$settings = $this->get_vulns_settings();
		return ( !empty($settings['status']) && $settings['status'] === 'yes') ? true : false;

	}

	public function get_vulns_settings(){
		$settings_serialized = $this->config->get_option('vulns_settings');
		if (empty($settings_serialized)) {
			return false;
		}

		$settings = unserialize($settings_serialized);

		return empty($settings) ? array() : $settings;
	}

	public function get_enabled_themes(){

		$all_themes = $this->app_functions->get_all_themes_data();

		$themes = array();

		$vulns_settings = $this->get_vulns_settings();

		$excluded_themes = empty($vulns_settings['themes']['excluded']) ? array() : unserialize($vulns_settings['themes']['excluded']);

		$i=0;
		foreach ($all_themes as $slug => $theme) {
			$themes[$i]['slug'] = $slug;
			$themes[$i]['name'] = $theme->get('Name');
			$themes[$i]['selected'] = (!in_array($slug, $excluded_themes)) ?  true : false;
			$i++;
		}

		return $themes;
	}

	public function get_enabled_plugins(){

		$all_plugins = $this->app_functions->get_all_plugins_data();

		$plugins = array();
		$vulns_settings = $this->get_vulns_settings();
		$excluded_plugins = empty($vulns_settings['plugins']['excluded']) ? array() : unserialize($vulns_settings['plugins']['excluded']);

		$i=0;
		foreach ($all_plugins as $slug => $plugin) {
			$plugins[$i]['slug'] = $slug;
			$plugins[$i]['name'] = $plugin['Name'];
			$plugins[$i]['selected'] = (!in_array($slug, $excluded_plugins)) ?  true : false;
			$i++;
		}

		return $plugins;
	}

	public function update_vulns_settings($options){
		wptc_log($options, '---------$options------------');
		$settings['status'] = empty($options['vulns_wptc_setting']) ? "no" : $options['vulns_wptc_setting'];
		$settings['core']['status'] = empty($options['wptc_vulns_core']) ? 0 : 1;
		$settings['themes']['status'] = empty($options['wptc_vulns_themes']) ? 0 : 1;
		$settings['plugins']['status'] = empty($options['wptc_vulns_plugins']) ? 0 : 1;

		if (!empty($options['vulns_plugins_included'])) {
			$plugin_include_array = explode(',', $options['vulns_plugins_included']);
			wptc_log($plugin_include_array, '--------$plugin_include_array--------');
			$included_plugins = $this->filter_plugins($plugin_include_array);
			wptc_log($included_plugins, '--------$included_plugins--------');
			$settings['plugins']['excluded'] = serialize($included_plugins);
		}

		if (!empty($options['vulns_themes_included'])) {
			$themes_include_array = explode(',', $options['vulns_themes_included']);
			$included_themes = $this->filter_themes($themes_include_array);
			$settings['themes']['excluded'] = serialize($included_themes);
		}

		// $settings['send_email'] = empty($options['enable_vulns_email_wptc']) ? 0 : 1;

		wptc_log($settings, '---------$settings------------');

		$result = $this->config->set_option('vulns_settings', serialize($settings));

		do_action('send_ptc_list_to_server_wptc', time());
	}

	private function filter_plugins($included_plugins){
		$plugins_data = $this->app_functions->get_all_plugins_data($specific = true, $attr = 'slug');
		$not_included_plugin = array_diff($plugins_data, $included_plugins);
		wptc_log($plugins_data, '--------$plugins_data--------');
		wptc_log($not_included_plugin, '--------$not_included_plugin--------');
		return $not_included_plugin;
	}

	private function filter_themes($included_themes){
		$themes_data = $this->app_functions->get_all_themes_data($specific = true, $attr = 'slug');
		$not_included_theme = array_diff($themes_data, $included_themes);
		wptc_log($themes_data, '--------$themes_data--------');
		wptc_log($not_included_theme, '--------$not_included_theme--------');
		return $not_included_theme;
	}

	public function get_format_vulns_settings_to_send_server(){
		$vulns_settings = $this->get_vulns_settings();

		$excluded_themes = empty($vulns_settings['themes']['excluded']) ? array() : unserialize($vulns_settings['themes']['excluded']);
		$excluded_plugins = empty($vulns_settings['plugins']['excluded']) ? array() : unserialize($vulns_settings['plugins']['excluded']);

		return array(
			'status' => ($vulns_settings['status'] === 'yes' ) ? true : false,
			'is_core_exclude' => empty($vulns_settings['core']['status'] ) ? false : true,
			'themes_to_exclude' => $excluded_themes,
			'plugins_to_exclude' => $excluded_plugins,
			);
	}
}