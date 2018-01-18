<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use Monolog\Handler\StreamHandler;
use Monolog\Processor\ProcessIdProcessor;

/**
 * Writes to the log used by the plugin.
 */
class Logger {
	// @var string The log id.
	public $log_id = '';
	// @var bool Indicates if debug mode is active.
	protected $_debug_mode = false;

	/**
	 * The logger used to store log messages.
	 *
	 * @var \Monolog\Logger
	 * @since 1.8.0.160728
	 */
	protected $logger;
	/**
	 * A list of handlers that will be used by Monolog to log messages.
	 *
	 * @var array
	 * @since 1.8.0.160728
	 */
	protected $log_handlers = array();

	/**
	 * Sets the log level for the logger.
	 *
	 * @param int log_level The new log level.
	 * @since 1.8.0.160728
	 */
	protected function set_log_level($log_level) {
		foreach($this->log_handlers as $log_handler) {
			$log_handler->setLevel($log_level);
		}
	}

	/**
	 * Sets the "debug mode" setting.
	 *
	 * @return bool
	 */
	protected function set_debug_mode($debug_mode) {
		$this->_debug_mode = $debug_mode;

		if($debug_mode) {
			$log_level = \Monolog\Logger::DEBUG;
		}
		else {
			$log_level = \Monolog\Logger::NOTICE;
		}
		$this->set_log_level($log_level);
	}

	/**
	 * Retrieves the "debug mode" setting.
	 *
	 * @return bool
	 */
	protected function get_debug_mode() {
		return $this->_debug_mode;
	}

	/**
	 * Indicates if debug mode is active.
	 *
	 * @return bool
	 */
	protected function debug_mode() {
		if($this->_debug_mode === null) {
			$this->_debug_mode = $this->get_debug_mode();
		}
		return $this->_debug_mode;
	}

	/**
	 * Determines if WordPress maintenance mode is active.
	 *
	 * @return bool
	 */
	protected function maintenance_mode() {
		return file_exists(ABSPATH . '.maintenance') || defined('WP_INSTALLING');
	}

	/**
	 * Adds a message to the log.
	 *
	 * @param string message The message to log.
	 * @param bool is_debug_msg Indicates if the message should only be logged
	 * while debug mode is true.
	 */
	public function log($message, $is_debug_msg = true) {
		if($is_debug_msg) {
			return $this->logger->debug($message);
		}
		else {
			return $this->logger->notice($message);
		}
	}

	/**
	 * Adds a log record at the DEBUG level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\debug()
	 * @since 1.8.0.160728
	 */
	public function debug($message, array $context = array()) {
		return $this->logger->debug($message, $context);
	}

	/**
	 * Adds a log record at the INFO level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\info()
	 * @since 1.8.0.160728
	 */
	public function info($message, array $context = array())	{
		return $this->logger->info($message, $context);
	}

	/**
	 * Adds a log record at the NOTICE level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\notice()
	 * @since 1.8.0.160728
	 */
	public function notice($message, array $context = array()) {
		return $this->logger->notice($message, $context);
	}

	/**
	 * Adds a log record at the WARNING level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\warning()
	 * @since 1.8.0.160728
	 */
	public function warning($message, array $context = array()) {
		return $this->logger->warning($message, $context);
	}

	/**
	 * Adds a log record at the ERROR level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\error()
	 * @since 1.8.0.160728
	 */
	public function error($message, array $context = array()) {
		return $this->logger->error($message, $context);
	}

	/**
	 * Adds a log record at the CRITICAL level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\critical()
	 * @since 1.8.0.160728
	 */
	public function critical($message, array $context = array()) {
		return $this->logger->critical($message, $context);
	}

	/**
	 * Adds a log record at the ALERT level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\alert()
	 * @since 1.8.0.160728
	 */
	public function alert($message, array $context = array()) {
		return $this->logger->alert($message, $context);
	}

	/**
	 * Adds a log record at the EMERGENCY level.
	 *
	 * This method allows for compatibility with common interfaces.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\emergency()
	 * @since 1.8.0.160728
	 */
	public function emergency($message, array $context = array()) {
		return $this->logger->emergency($message, $context);
	}

	/**
	 * Adds an AUDIT log record. This is a special record, at INFO level, which is
	 * used to keep track of special events, to audit application usage.
	 *
	 * @param  string  $message The log message
	 * @param  array   $context The log context
	 * @return Boolean Whether the record has been processed
	 * @see Monolog\Logger\emergency()
	 * @since 1.8.0.160728
	 */
	public function audit($message, array $context = array()) {
		// Add a special "audit" argument, to separate this message from the normal
		// INFO messages
		$context['_audit'] = true;
		return $this->info($message, $context);
	}

	/**
	 * Returns the log file to be used by this logger.
	 *
	 * @param string log_id The ID of this log.
	 * @return string
	 * @since 1.8.0.160728
	 */
	public static function get_log_file_name($log_id) {
		if(defined('WC_LOG_DIR')) {
			$log_dir = WC_LOG_DIR;
		}
		else {
			$upload_dir = wp_upload_dir();
			$log_dir = $upload_dir['basedir'] . '/wc-logs/';
		}
		return trailingslashit($log_dir) . sanitize_file_name($log_id) . '.log';
	}

	/**
	 * Initialises the logger.
	 *
	 * @param bool debug_mode Indicates if debug mode is active. If it's not,
	 * debug messages won't be logged.
	 * @since 1.8.0.160728
	 */
	protected function init_logger($debug_mode = false) {
		// TODO Check that the target log file is writable. If not, raise a PHP warning

		$this->log_handlers = apply_filters('wc_aelia_log_handlers', array(
			new StreamHandler(self::get_log_file_name($this->log_id), \Monolog\Logger::NOTICE),
		), $this->log_id, $this);
		$this->set_debug_mode($debug_mode);

		$this->logger = new \MonoLog\Logger($this->log_id, $this->log_handlers);
		$this->logger->pushProcessor(new ProcessIdProcessor());
	}

	/**
	 * Class constructor.
	 *
	 * @param string log_id The identifier for the log.
	 * @param bool debug_mode Indicates if debug mode is active. If it's not,
	 * debug messages won't be logged.
	 */
	public function __construct($log_id, $debug_mode = false) {
		$this->log_id = $log_id;

		$this->init_logger($debug_mode);
	}

	/**
	 * Factory method.
	 *
	 * @param string log_id The identifier for the log.
	 * @return Aelia\WC\Logger.
	 */
	public static function factory($log_id) {
		$class = get_called_class();
		return new $class($log_id);
	}
}
