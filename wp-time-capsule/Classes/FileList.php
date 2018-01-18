<?php

class WPTC_FileList {

	private $cached_user_extensions;

	public function __construct() {
		$this->db = WPTC_Factory::db();
	}

	public function get_user_excluded_extensions_arr() {

		if (!empty($this->cached_user_extensions)) {
			return $this->cached_user_extensions;
		}

		$config = WPTC_Factory::get('config');
		$raw_extenstions = $config->get_option('user_excluded_extenstions');

		if ( empty ( $raw_extenstions ) ){
			return array();
		}

		$excluded_extenstions = array();
		$extensions = explode(',', $raw_extenstions);

		foreach ($extensions as $extension) {
			if (empty($extension)) {
				continue;
			}

			$excluded_extenstions[] = trim( trim ( $extension ), '.');
		}

		return $excluded_extenstions;
	}

	public function in_ignore_list($file) {

		if (empty($file)) {
			return false;
		}

		$user_excluded_extenstions = $this->get_user_excluded_extensions_arr();

		$file_extension = $this->get_extension($file);

		if (empty($file_extension)) {
			return false;
		}

		return in_array($file_extension, $user_excluded_extenstions);
	}

	public function get_extension($file) {

		$extension = explode ( ".", $file );

		if (empty($extension)) {
			return false;
		}

		$extension = end($extension);
		return $extension ? $extension : false;
	}
}
