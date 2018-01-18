<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;

class Free_Plugin_Updater extends Updater {
	protected $api_url;

	public function __construct(array $products_to_update) {
		parent::__construct($products_to_update);
	}

	protected function get_api_call_url(array $args) {
		return 'http://wpupdate.aelia.co?action=get_metadata&slug=' . $args['slug'];
	}

	protected function check_for_product_updates($plugin) {
		// If plugin_file property is not set, we can't check for updates, as such
		// information is required by the updater client
		if(empty($plugin->main_plugin_file)) {
			return;
		}

		$plugin_class = get_class($plugin);
		// In case the plugin uses a different slug from the one registered in the
		// update server, it can set the "slug_for_update_check" property to indicate
		// it
		$slug_for_update_check = isset($plugin_class::$slug_for_update_check) ? $plugin_class::$slug_for_update_check : $plugin_class::$plugin_slug;

		// Debug
		//var_dump(
		//	$this->get_api_call_url(array(
		//				'slug' => $slug_for_update_check,
		//	)),
		//	$plugin->main_plugin_file,
		//	$slug_for_update_check
		//);

		$update_checker = \PucFactory::buildUpdateChecker(
				$this->get_api_call_url(array(
					'slug' => $slug_for_update_check,
				)),
				$plugin->main_plugin_file,
				$slug_for_update_check
		);
	}

	public function check_for_updates() {
		require_once(WC_AeliaFoundationClasses::instance()->path('vendor') . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php');
		return parent::check_for_updates();
	}

	/**
	 * Retrieves and returns the plugin information from the remote server.
	 *
	 * @param Aelia_Plugin plugin The plugin whose information should be retrieved.
	 * @param object args Plugin API arguments.
	 * @param mixed default The default value to return if the plugin information
	 * cannot be retrieved.
	 * @return bool|object
	 * @since 1.7.1.150824
	 */
	protected function get_product_info($plugin, $args, $default) {
		// The Free Plugin Updates class doesn't have to do anything in this method
		return $default;
	}
}
