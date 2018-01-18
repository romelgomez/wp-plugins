<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;
use \InvalidArgumentException;

abstract class Updater extends Base_Class {
	protected static $updaters = array();
	protected $products_to_update = array();

	// @var string The message key to use for a generic "unexpected error" message
	const MSG_KEY_UNEXPECTED_ERROR = '_unexpected_error';

	public function __construct(array $products_to_update) {
		parent::__construct();

		$this->logger = WC_AeliaFoundationClasses::instance()->get_logger();
		$this->products_to_update = $products_to_update;
		$this->set_hooks();
	}

	public function set_hooks() {
		// Automatic updates
		add_filter('pre_set_site_transient_update_plugins', array($this, 'pre_set_site_transient_update_plugins'), 10, 1);

		// Response for information checking
		add_filter('plugins_api', array($this, 'plugins_api'), 10, 3);
	}

	public function pre_set_site_transient_update_plugins($checked_data) {
		return $checked_data;
	}

	protected static function get_site_url() {
		/**
		 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
		 * so only the host portion of the URL can be sent. For example the host portion might be
		 * www.example.com or example.com. http://www.example.com includes the scheme http,
		 * and the host www.example.com.
		 * Sending only the host also eliminates issues when a client site changes from http to https,
		 * but their activation still uses the original scheme.
		 * To send only the host, use a line like the one below:
		 *
		 * $domain = str_ireplace(array('http://', 'https://'), '', strtolower(home_url()));
		 */
		return str_ireplace(array('http://', 'https://'), '', strtolower(home_url()));
	}

	protected function get_installation_instance_id($prefix = '', $suffix = '') {
		return $prefix . md5(self::get_site_url()) . '-' . (string)get_current_blog_id() . $suffix;
	}

	protected abstract function get_api_call_url(array $args);

	protected abstract function check_for_product_updates($product);

	public function check_for_updates() {
		foreach($this->products_to_update as $product_to_update) {
			$this->check_for_product_updates($product_to_update);
		}
	}

	/**
	 * Filter for "plugins_api" hook. Adds information about self-hosted plugins
	 * to the list.
	 *
	 * @param boolean default The default response.
	 * @param string action The type of information being requested from the
	 * Plugin Install API.
	 * @param object args Plugin API arguments.
	 * @return bool|object
	 * @since 1.7.1.150824
	 */
	public function plugins_api($default, $action, $args) {
		if(!empty($args->slug) && isset($this->products_to_update[$args->slug])) {
			return $this->get_product_info($this->products_to_update[$args->slug], $args, $default);
		}
		return $default;
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
	protected abstract function get_product_info($product, $args, $default);

	public static function init_updater($product_type, array $product_list) {
		if(empty(self::$updaters[$product_type])) {
			$plugin_updaters_map = array(
				'premium' => 'Aelia\WC\Premium_Plugin_Updater',
				'free' => 'Aelia\WC\Free_Plugin_Updater',
			);
			$updater_class = get_value($product_type, $plugin_updaters_map);
			if(empty($updater_class)) {
				$error_msg = sprintf(__('Aelia Updater - Updater could not be loaded. ' .
																'Invalid product type specified: "%s". Please ' .
																'contact Aelia Support team and provide them ' .
																'with the information you can find below.',
																Definitions::TEXT_DOMAIN),
														 $product_type) .
										 '<pre>' .
										 sprintf(__('Backtrace (JSON): %s', Definitions::TEXT_DOMAIN),
														 json_encode(debug_backtrace())) .
										 '</pre>';
				// Show the to the Shop Admininistrator
				Messages::admin_message(
					WC_AeliaFoundationClasses::$plugin_slug,
					E_USER_ERROR,
					$error_msg
				);
				return false;
			}
			self::$updaters[$product_type] = new $updater_class($product_list);
		}
		return self::$updaters[$product_type];
	}
}
