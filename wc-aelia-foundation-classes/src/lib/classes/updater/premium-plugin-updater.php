<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;

class Premium_Plugin_Updater extends Updater {
	//protected $api_url = 'http://aelia.co';
	// Test URL
	protected $api_url = 'http://wc-demo.aelia.co';

	// @var bool Indicates if the check for plugin updates has already been
	// performed. This will prevent multiple checks, which would just slow down
	// the site
	protected $updates_checked = false;

	const URL_CUSTOMER_ACCOUNT = 'http://aelia.co/my-account';
	const URL_SUPPORT = 'https://aelia.freshdesk.com/helpdesk/tickets/new';

	public function __construct(array $products_to_update) {
		parent::__construct($products_to_update);
		$this->updates_checked = false;
	}

	protected function get_api_call_url(array $args) {
		$url = add_query_arg('wc-api', 'upgrade-api', $this->api_url);
		return $url . '&' . http_build_query( $args );
	}

	protected function url($url_key) {
		$urls = array(
			'customer_account' => self::URL_CUSTOMER_ACCOUNT,
			'support' => self::URL_SUPPORT,
			'product_licences' => 'http://URL_NOT_SET',
		);
		return $urls[$url_key];
	}

	protected function get_licence_info($plugin_slug) {
		$option_name = Definitions::OPT_LICENCE_INFO_PREFIX . $plugin_slug;
		return get_option($option_name, array(
			'api_key' => '',
			'activation_email' => '',
		));
	}

	protected function plugin_has_required_properties($plugin) {
		$plugin_class = get_class($plugin);
		return isset($plugin_class::$plugin_slug) &&
					 isset($plugin_class::$version) &&
					 isset($plugin->main_plugin_file);
	}

	protected function get_plugin_api_args($plugin, $action) {
		$plugin_class = get_class($plugin);
		return array_merge(array(
			'request' => $action,
			'slug' => $plugin_class::$plugin_slug,
			// Plugin name is actually the main plugin filer (folder and file name).
			// The parameter name is just confusing (like many other things in the API
			// Manager)
			'plugin_name' => $plugin->main_plugin_file,
			'version' => $plugin_class::$version,
			// The plugin slug can be used as a product ID, as it's unique
			'product_id' => $plugin_class::$plugin_slug,
			'software_version' => $plugin_class::$version,
			//'extra' => $this->extra,

			// Instance and domain will be retrieved by the Updated class
			//'instance' => $this->instance,
			//'domain' => $this->domain,

			// Licence information is retrieved by the get_licence_info() method
			//'api_key' => $this->api_key,
			//'activation_email' =>	$this->activation_email,
		), $this->get_licence_info($plugin_class::$plugin_slug));
	}

	/**
	 * Sends and receives data to and from the server API
	 *
	 * @access public
	 * @since  1.0.0
	 * @return object $response
	 */
	protected function get_plugin_information(array &$args) {
		// Add the instance ID and the site domain to the API call. They will be used
		// to track activations
		$args['instance'] = $this->get_installation_instance_id();
		$args['domain'] = $this->get_site_url();

		// Debug
		//var_dump($args);

		$target_url = esc_url_raw($this->get_api_call_url($args));
		// Add the target URL to the arguments, for debugging purposes
		$args['request_url'] = $target_url;

		// Debug
		//var_dump("PLUGIN UPDATE URL", $target_url);
		$response = wp_safe_remote_get($target_url);

		if(is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200)) {
			$this->logger->error(__('An error occurred while retrieving plugin information ' .
															'from the remote API.', Definitions::TEXT_DOMAIN),
													 array(
														'Request URL' => $target_url,
														'Response' => $response,
													 ));
			return false;
		}

		$response = wp_remote_retrieve_body($response);
		// Debug
		//var_dump("Update API raw response body: ", $response);
		$response = unserialize($response);

		if(!is_object($response)) {
			$this->logger->error(__('Unexpected response received from remote API.', Definitions::TEXT_DOMAIN),
													 array(
														'Request URL' => $target_url,
														'Response' => $response,
													 ));
			return false;
		}

		// Add a flag that indicates if a new version is available
		$response->new_version_available = !empty($response->new_version) && version_compare($response->new_version, $args['version'], '>');
		return $response;
	}

	protected function process_api_error_messages($response, array $request_args) {
		// The following tokens may be found in the error messages, and have to be
		// replaced with the appropriate values
		$tokens = array(
			'product_name' => sprintf('<strong>%s</strong>', $request_args['plugin_name']),
			'request_url' => $request_args['request_url'],
			'response' => json_encode($response),
		);

		$messages = $this->get_api_error_messages($request_args);
		foreach($response->errors as $message_key) {
			// If the returned error code is not in the list, show a generic "enexpected
			// error" message
			if(!isset($messages[$message_key])) {
				$message_key = self::MSG_KEY_UNEXPECTED_ERROR;
			}
			$message = $messages[$message_key];

			// Replace all the tokens with the respective values
			foreach($tokens as $token_id => $value) {
				$token_id = '{' . $token_id . '}';
				$message = str_ireplace($token_id, $value, $message);
			}

			// Show all the errors in the admin section
			Messages::admin_message(
				$request_args['slug'],
				E_USER_WARNING,
				$message,
				$message_key,
				true
			);
		}
	}

	protected function get_api_error_messages() {
		if(empty($this->api_error_messages)) {
			// Common messages
			$buy_licence_msg = sprintf(__('To reactivate a licence, or buy a licence key, ' .
																		'please go to your <a href="%s" target="_blank">account ' .
																		'dashboard</a>.', Definitions::TEXT_DOMAIN),
																 $this->url('customer_account'));
			$reactivate_subscription_msg = sprintf(__('You can reactivate the subscription from ' .
																								'your <a href="%s" target="_blank">account ' .
																								'dashboard</a>.', Definitions::TEXT_DOMAIN),
																						 $this->url('customer_account'));
			$buy_subscription = sprintf(__('You can buy a new subscription from your ' .
																		 '<a href="%s" target="_blank">account dashboard</a>. ' .
																		 'You will receive a new licence key by email after ' .
																		 'completing the order.',
																		 Definitions::TEXT_DOMAIN),
																	$this->url('customer_account'));

			$this->api_error_messages = array(
				'no_key' => sprintf(__('Could not find a licence key for "{product_name}". This could have ' .
															 'happened because you did not enter a licence key for the ' .
															 'product, or because the key was deactivated in your account. ' .
															 'To enter a licence key, please go to the <a href="%s">Product ' .
															 'Licences page</a>.', Definitions::TEXT_DOMAIN),
														$this->url('product_licences')) .
										// Append the "To reactivate or buy licence" message
										' ' . $buy_licence_msg,
				'no_subscription' => sprintf(__('Could not find a subscription for "{product_name}". You can ' .
																				'buy or renew a subscription from your ' .
																				'<a href="%s" target="_blank">account ' .
																				'dashboard</a>.', Definitions::TEXT_DOMAIN),
																		 $this->url('customer_account')),
				'exp_license' => sprintf(__('The licence for "{product_name}" has expired. You can still use ' .
																		'the product, as there are no restrictions from that ' .
																		'perspective. If you wish to receive further updates, ' .
																		'you will have to place a renewal order.', Definitions::TEXT_DOMAIN)) .
												 // Append the "To reactivate or buy licence" message
												 ' ' . $buy_licence_msg,
				'hold_subscription' => sprintf(__('The subscription for "{product_name}" is on hold.',
																					Definitions::TEXT_DOMAIN)) .
															 // Append the "reactivate subscription" message
															 ' ' . $reactivate_subscription_msg,
				'cancelled_subscription' => sprintf(__('The subscription for "{product_name}" has been cancelled. ' .
																							 'You can renew the subscription from your ' .
																							 '<a href="%s" target="_blank">account ' .
																							 'dashboard</a>. You will receive a new licence ' .
																							 'key by email after completing the order.',
																							 Definitions::TEXT_DOMAIN),
																						$this->url('customer_account')),
				'exp_subscription' => sprintf(__('The subscription for "{product_name}" has expired.',
																				 Definitions::TEXT_DOMAIN)) .
															// Append the "reactivate subscription" message
															' ' . $reactivate_subscription_msg,
				'suspended_subscription' => sprintf(__('The subscription for "{product_name}" has been suspended.',
																							 Definitions::TEXT_DOMAIN)) .
																		// Append the "reactivate subscription" message
																		' ' . $reactivate_subscription_msg,
				'pending_subscription' => sprintf(__('The subscription for "{product_name}" is still pending. You can ' .
																						 'check the status of the subscription from your ' .
																						 '<a href="%s" target="_blank">account ' .
																						 'dashboard</a>. You will receive a new licence ' .
																						 'key by email after completing the order.',
																						 Definitions::TEXT_DOMAIN),
																					$this->url('customer_account')),
				'trash_subscription' => sprintf(__('The subscription for "{product_name}" has been queued ' .
																					 'for deletion and permanent deactivation.',
																					 Definitions::TEXT_DOMAIN)) .
																// Append the "buy new subscription" message
																' ' . $buy_subscription,
				'no_subscription' => sprintf(__('Could not find a subscription for "{product_name}".',
																				 Definitions::TEXT_DOMAIN)) .
														 // Append the "buy new subscription" message
														 ' ' . $buy_subscription,
				'no_activation' => sprintf(__('The licence key for "{product_name}" was not activated. To enter ' .
																			'a licence key, please go to the <a href="%s">Product ' .
																			'Licences page</a>.', Definitions::TEXT_DOMAIN),
																	 $this->url('product_licences')) .
													 // Append the "To reactivate or buy licence" message
													 ' ' . $buy_licence_msg,
				'download_revoked' => sprintf(__('The permission to download updates for "{product_name}" was revoked. ' .
																				 'The most common cause of this issue is that the licence ' .
																				 'key is expired.', Definitions::TEXT_DOMAIN),
																	 $this->url('product_licences')) .
															// Append the "To reactivate or buy licence" message
															' ' . $buy_licence_msg,
				'switched_subscription' => sprintf(__('The subscription for "{product_name}" was changed. ' .
																							'You should have received a new licence key ' .
																							'by email, which <a href="%s">you will need ' .
																							'to activate</a> to receive updates for this ' .
																							'product. If you did not receive a new licence ' .
																							'key by email, you can retrieve it from your ' .
																							'<a href="%s" target="_blank">account ' .
																							'dashboard</a>.',
																							Definitions::TEXT_DOMAIN),
																					 $this->url('product_licences'),
																					 $this->url('customer_account')),
				self::MSG_KEY_UNEXPECTED_ERROR => sprintf(__('Unexpected error message returned ' .
																										 'by updates server ' .
																										 'for product "{product_name}. ' .
																										 'Please <a href="%s" target="_blank">contact our ' .
																										 'support team</a> and send them with the ' .
																										 'information you can find below.', Definitions::TEXT_DOMAIN),
																									$this->url('support')) .																			 '<pre>' .
																					sprintf(__('Request URL: "{request_url}."', Definitions::TEXT_DOMAIN)) .
																					' ' .
																					sprintf(__('Response (JSON): "{response}."', Definitions::TEXT_DOMAIN)) .
																					'</pre>',
			);
		}
		return $this->api_error_messages;
	}

	//public function check_for_plugin_updates($plugin_data) {
	public function pre_set_site_transient_update_plugins($transient) {
		// The pre_set_site_transient_update_plugins is called twice, but we just
		// need to run it once to check for all plugin updates
		if($this->updates_checked || empty($transient->checked)) {
			return $transient;
		}

		$this->updates_checked = true;
		foreach($this->products_to_update as $plugin) {
			$plugin_class = get_class($plugin);
			// Skip plugins that do not have the properties required to query the
			// updates API
			if(!$this->plugin_has_required_properties($plugin)) {
				// Keep track of the fact that an invalid plugin was passed
				$err_msg = sprintf(__('[%s] A plugin without the properties required to check for ' .
															'updates was found in the list. Plugin class: "%s". Updates ' .
															'for such plugin will not be retrieved.', Definitions::TEXT_DOMAIN),
													 __CLASS__,
													 $plugin_class);
				$this->log($err_msg, false);
				// Debug
				//trigger_error($err_msg, E_USER_WARNING);
				continue;
			}
			// Check for plugin updates
			$response = $this->check_for_updates($plugin);

			// Debug
			//var_dump($response,
			//				 $transient->response,
			//				 $plugin->main_plugin_file);die();

			// Add the response to the list of updates, so that it can be displayed
			// in WP Admin > Plugins page
			if(is_object($response) && $response->new_version_available) {
				$transient->response[$plugin->main_plugin_file] = $response;
			}
		}
		return $transient;
	}

	protected function check_for_product_updates($plugin) {
		// Check for a plugin update
		$request_args = $this->get_plugin_api_args($plugin, 'pluginupdatecheck');

		$response = $this->get_plugin_information($request_args);
		// Process any error message and display it to the site administrator
		if(is_object($response) && !empty($response->errors)) {
			$this->process_api_error_messages($response, $request_args);
		}

		return $response;
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
		// Check for a plugin update
		$request_args = $this->get_plugin_api_args($plugin, 'plugininformation');

		$response = $this->get_plugin_information($request_args);
		return is_object($response) ? $response : $default;
	}
}
