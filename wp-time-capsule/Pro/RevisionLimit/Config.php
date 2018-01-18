<?php

class Wptc_Revision_Limit_Config extends Wptc_Base_Config {
	protected $config;
	protected $used_options;
	protected $used_wp_options;

	public function __construct() {
		$this->init();
	}

	private function init() {
		$this->set_used_options();
	}

	protected function set_used_options() {
		$this->used_options = array(
			'privileges_wptc' => 'retainable',
			'privileges_args' => 'retainable',
			'revision_limit' => 'retainable',
			'eligible_revision_limit' => 'retainable',
			'settings_revision_limit' => 'retainable',
			'default_repo' => 'retainable',
		);
		$this->used_wp_options = array(
		);
	}
}