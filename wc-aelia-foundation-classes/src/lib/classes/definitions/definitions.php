<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements a base class to store definitions for the plugin.
 */
class Definitions {
	// @var string The menu slug for plugin's settings page.
	const MENU_SLUG = 'aelia_foundation_classes';
	// @var string The plugin slug
	const PLUGIN_SLUG = 'wc-aelia-foundation-classes';
	// @var string The plugin text domain
	const TEXT_DOMAIN = 'wc-aelia-foundation-classes';

	// Get/Post Arguments
	const ARG_INSTALL_GEOIP_DB = 'aelia_install_geoip_db';
	const ARG_MESSAGE_ID = 'aelia_msg_id';
	const ARG_AJAX_COMMAND = 'exec';

	// Error codes
	const OK = 0;
	const RES_OK = 0;
	const ERR_COULD_NOT_UPDATE_GEOIP_DATABASE = 1100;
	const ERR_INVALID_AJAX_REQUEST = 17001;
	const ERR_AJAX_COMMAND_MISSING = 17002;
	const ERR_AJAX_INVALID_COMMAND = 17003;
	const ERR_AJAX_INVALID_REFERER = 17004;


	// Session/User Keys

	// Options
	const OPT_LICENCE_INFO_PREFIX = 'aelia_plugin_licence_key_';

	// Transients
}
