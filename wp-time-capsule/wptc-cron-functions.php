<?php

function init_wptc_cron(){
	check_wptc_update(); // check updates on every server pings
	$post_data = decode_server_request_wptc();
	if (empty($post_data)) {
		return false;
	}

	$offset = !empty($post_data['extra']['offset']) ? $post_data['extra']['offset'] : 0;
	$limit = !empty($post_data['extra']['limit']) ? $post_data['extra']['limit'] : 100;

	perform_request_wptc($post_data['type'], $offset, $limit);
}

function decode_server_request_wptc(){
	global $HTTP_RAW_POST_DATA;
	$HTTP_RAW_POST_DATA_LOCAL = NULL;
	$HTTP_RAW_POST_DATA_LOCAL = file_get_contents('php://input');

	if(empty($HTTP_RAW_POST_DATA_LOCAL)){
		if (isset($HTTP_RAW_POST_DATA)) {
			$HTTP_RAW_POST_DATA_LOCAL = $HTTP_RAW_POST_DATA;
		}
	}
	ob_start();
	$data = base64_decode($HTTP_RAW_POST_DATA_LOCAL);
	if ($data && $HTTP_RAW_POST_DATA_LOCAL != 'action=progress'){
		$post_data_encoded = $data;
			$post_data = json_decode($post_data_encoded, true);

			// wptc_log($post_data, "--------post_data from node --------");

			$is_validated = false;
			$is_validated = is_valid_wptc_request($post_data);
			if(empty($is_validated)){
				return false;
			}

			if (!isset($post_data['type'])) {
				wptc_log(array(), '-----------type not set-------------');
				return false;
			}
		return $post_data;
	} else {
		$HTTP_RAW_POST_DATA =  $HTTP_RAW_POST_DATA_LOCAL;
	}
	ob_end_clean();
}


function perform_request_wptc($request_type, $offset = 0, $limit = 100){
	set_server_req_wptc();

	if ($request_type === 'SCHEDULE') {
		check_cloud_in_auth_state();
	}

	set_server_req_wptc(true);

	//premium updates
	// create_admin_environment_wptc();

	wptc_init();

	global $wptc_ajax_start_time, $wptc_profiling_start;
	$wptc_ajax_start_time = $wptc_profiling_start = time();

	wptc_manual_debug('', 'start_cron_request');

	// $config = WPTC_Factory::get('config');
	// $config->is_main_account_authorized();

	wptc_log($request_type, '---------$request_type from node------------');

	switch ($request_type) {
		case 'BACKUP':
		case 'B':
		case 'RETRY':
		case 'R':
			$status = monitor_tcdropbox_backup_wptc();
			if ($status == 'declined') {
				send_response_wptc('backup_not_initialized', $request_type);
			}
			break;
		case 'S':
		case 'SCHEDULE':
		case 'A':
		case 'AUTOBACKUP':
		// case 'WEEKLYBACKUP':
			sub_cycle_event_func_wptc($request_type);
			break;
		break;
		case 'STAGING':
			// do_action('process_staging_req_wptc_h', time());
			// do_action('send_response_node_staging_wptc_h', time());
			break;
		case 'SYNC_SERVER':
			wptc_own_cron_status();
			send_response_wptc('connected', $request_type);
			break;
		case 'SYNC_SERVICE':
			WPTC_Factory::get('config')->is_main_account_authorized(null, null, false, false);
			break;
		case 'TEST':
			send_response_wptc('connected', $request_type);
			break;
		case 'BACKUP_RESET':
			stop_fresh_backup_tc_callback_wptc();
			reset_backup_related_settings_wptc();
			send_response_wptc('success', $request_type);
			break;
		case 'STAGING_RESET':
			do_action('wp_ajax_delete_staging_wptc');
			send_response_wptc('success', $request_type);
			break;
		case 'BACKUP_STATUS':
			do_action('send_backups_data_to_server_wptc', time());
			send_current_backup_response_to_server();
			break;
		case 'BACKUP_START':
			reset_backup_related_settings_wptc();
			start_fresh_backup_tc_callback_wptc('manual');
			send_response_wptc('backup_started', $request_type);
			break;
		case 'DEBUG_LOG':
			WPTC_Factory::get('Debug_Log')->get_logs($offset, $limit);
			break;
		default:
			send_response_wptc('request_not_in_list', $request_type);
			wptc_log(array(), '-----------Request is not in list-------------');
			break;
	}
	wptc_log(array(), '-----------Task end-------------');
	send_response_wptc('notified', $request_type);
}

function is_valid_wptc_request($post_data){
	if (empty($post_data['authorization'])) {
		return false;
	}

	// wptc_log($post_data['authorization'], '---------$post_data["authorization"]------------');
	$app_id = decode_auth_token($post_data['authorization']);
	// wptc_log($app_id, '---------$app_id------------');

	if (empty($app_id)) {
		return false;
	}
	if(WPTC_Factory::get('config')->get_option('appID') != $app_id ){
		return false;
	}

	if (!isset($post_data['source']) && $post_data['source'] != 'WPTC') {
		return false;
	}

	return true;
}

function create_admin_environment_wptc(){

	$admins = get_users(array('role' => 'administrator'));

	foreach ($admins as $admin) {
		$user = $admin;
		break;
	}

	if (isset($user) && isset($user->ID)) {
		wp_set_current_user($user->ID);
		// Compatibility with All In One Security
		update_user_meta($user->ID, 'last_login_time', current_time('mysql'));
	}
	define('WP_ADMIN', true);
}