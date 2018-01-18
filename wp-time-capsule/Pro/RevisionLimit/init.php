<?php

class Wptc_Revision_Limit extends WPTC_Privileges {
	protected $config;

	public function __construct() {
		$this->config = WPTC_Pro_Factory::get('Wptc_Revision_Limit_Config');
	}

	public function init() {
		if ($this->is_privileged_feature(get_class($this)) && $this->is_switch_on()) {
			$supposed_hooks_class = get_class($this) . '_Hooks';
			WPTC_Pro_Factory::get($supposed_hooks_class)->register_hooks();
			$this->update_revision_limit();
		}
	}

	public function allow_revision_limit(){
		if (
			(
				!empty($_SERVER['REQUEST_URI']) &&
				(
					( strpos($_SERVER['REQUEST_URI'], 'wp-time-capsule-settings') !== false ||
					  strpos($_SERVER['REQUEST_URI'], 'wp-time-capsule-monitor') !== false
					)
				)
				||
				!empty($_SERVER['HTTP_REFERER']) &&
				(
					( strpos($_SERVER['HTTP_REFERER'], 'wp-time-capsule-settings') !== false ||
					  strpos($_SERVER['HTTP_REFERER'], 'wp-time-capsule-monitor') !== false
					)
				)
			)
			 &&
			 isset($_POST['action']) && $_POST['action'] === 'progress_wptc'
			) {

			return true;
		}

		return false;

	}

	private function is_switch_on(){
		return true;
	}

	private function update_revision_limit(){

		if(!$this->allow_revision_limit()){
			return ;
		}

		$args = $this->config->get_option('privileges_args');

		if(!empty($args)){
			$args = json_decode($args, true);
			$this_class_name = get_class($this);
			$revision_obj = $args[$this_class_name];
			$revision_days = $revision_obj['days'];
		}

		if(empty($revision_days)){
			$revision_days = WPTC_FALLBACK_REVISION_LIMIT_DAYS;
		}

		//See users eligible
		$this->config->set_option('eligible_revision_limit', $revision_days);

		//Process settings revision limit
		$settings_revision_limit = $this->config->get_option('settings_revision_limit');
		$settings_revision_limit = empty($settings_revision_limit ) ? 0 : $settings_revision_limit ;

		$this->validate_revision_limit($revision_days, $settings_revision_limit);

		$this->config->set_option('revision_limit', $revision_days);

		// wptc_log($revision_days, '--------$revision_days--------');

		//Update if user have not chosen the revision limit
		// if (empty($settings_revision_limit)) {
		// 	$result = $this->config->set_option('settings_revision_limit', $revision_days);
		// }
	}

	private function validate_revision_limit(&$revision_days, $settings_revision_limit){

		if (!empty($settings_revision_limit) && $settings_revision_limit !== 0 && $settings_revision_limit <= WPTC_DEFAULT_MAX_REVISION_LIMIT) {
			$revision_days = $settings_revision_limit;
			return ;
		}

		if ($revision_days <= WPTC_DEFAULT_MAX_REVISION_LIMIT) {
			return ;
		}

		$default_repo = $this->config->get_option('default_repo');
		$cloud_repo = WPTC_Factory::get($default_repo);

		if (empty($cloud_repo)) {
			$revision_days = WPTC_DEFAULT_MAX_REVISION_LIMIT;
			return ;
		}

		$is_authorized = $cloud_repo->is_authorized(true);

		if (empty($is_authorized)) {
			$revision_days = WPTC_DEFAULT_MAX_REVISION_LIMIT;
			return ;
		}


		switch ($default_repo) {
			case 'g_drive':
				$revision_days = WPTC_DEFAULT_MAX_REVISION_LIMIT;
				break;
			case 'dropbox':
				$revision_days = $cloud_repo->validate_max_revision_limit();
				break;
			case 's3':
				$revision_days = $cloud_repo->validate_max_revision_limit();
				break;
		}
		// wptc_log($revision_days, '--------$revision_days--------');
	}

}