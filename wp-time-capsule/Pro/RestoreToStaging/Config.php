<?php

class Wptc_Restore_To_Staging_Config extends Wptc_Base_Config {
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
			'is_restore_to_staging' => 'flushable',
			'restore_to_staging_details' => 'flushable',
			'R2S_replace_links' => '',
			'R2S_deep_links_completed' => '',
		);
		$this->used_wp_options = array(
		);
	}
}