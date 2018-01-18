<?php
namespace Aelia\WC\CurrencySwitcher;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Writes to the log used by the plugin.
 */
class Logger extends \Aelia\WC\Logger {
	protected static $_instance;

	/**
	 * Class constructor.
	 *
	 * @param string log_id The identifier for the log.
	 * @param bool debug_mode Indicates if debug mode is active. If it's not,
	 * debug messages won't be logged.
	 */
	public function __construct($log_id, $debug_mode = false) {
		parent::__construct($log_id, WC_Aelia_CurrencySwitcher::settings()->debug_mode());
	}

	public static function instance() {
		if(empty(self::$_instance)) {
			self::$_instance = new self(Definitions::PLUGIN_SLUG);
		}
		return self::$_instance;
	}
}
