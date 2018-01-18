function process_wtc_reload(data){
	jQuery('#progress').html('<div class="calendar_wrapper"></div>');
	jQuery("#progress").append('<div id="wptc-content-id" style="display:none;"> <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p></div><a class="thickbox wptc-thickbox" style="display:none" href="#TB_inline?width=500&height=500&inlineId=wptc-content-id&modal=true"></a>');

	if (typeof data == 'undefined' || !data) {
		return;
	}

	jQuery('.calendar_wrapper').fullCalendar({
		theme: false,
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay'
		},
		defaultDate: defaultDateWPTC, //setting from global var
		editable: false,
		events: data.stored_backups,
		eventAfterAllRender: function(){
			var first_one = jQuery('.fc-header-right')[0];
			jQuery(first_one).html('<div class="last-bp-taken-wptc">Last backup on - <span class="last-bp-taken-time">'+data.last_backup_time+'</span> </div>');
		}
	});

	var backup_progress = data.backup_progress;
	if (backup_progress != '') {
		showLoadingDivInCalendarBoxWptc();
	} else {
		resetLoadingDivInCalendarBoxWptc();
	}
}

function getThisDayBackups(backupIds) {
	remove_other_thickbox_wptc();
	jQuery('.notice, #update-nag').remove();
	var loading = '<div class="dialog_cont" style="padding:2%"><div class="loaders"><div class="loader_strip"><div class="wptc-loader_strip_cl" style="background:url(' + wptcOptionsPageURl + '/images/loader_line.gif)"></div></div></div></div>';
	jQuery("#wptc-content-id").html(loading);
	jQuery(".wptc-thickbox").click();
	if (typeof styling_thickbox_tc !== 'undefined' && jQuery.isFunction(styling_thickbox_tc)) {
		styling_thickbox_tc();
	}
	registerDialogBoxEventsTC();
	//to show all the backup list when a particular date is clicked
	get_this_day_backups_ajax(backupIds);
}

function registerDialogBoxEventsTC() {
	if (typeof cuurent_bridge_file_name == 'undefined') {
		cuurent_bridge_file_name = '';
	}
	jQuery.curCSS = jQuery.css;
	jQuery('.checkbox_click').on('click', function() {

		if (!(jQuery(this).hasClass("active"))) {
			jQuery(this).addClass("active");
		} else {
			jQuery(this).removeClass("active");
		}
	});

	jQuery('.single_backup_head').on('click', function() {
		var this_obj = jQuery(this).closest(".single_group_backup_content");

		if (!(jQuery(this).hasClass("active"))) {
			jQuery(".single_backup_content_body", this_obj).show();
		} else {
			jQuery(".single_backup_content_body", this_obj).hide();
		}
	});

	//UI actions for the file selection
	jQuery(".toggle_files").on("click", function(e) {
		var par_obj = jQuery(this).closest(".single_group_backup_content");
		if (!jQuery(par_obj).hasClass("open")) {
			//close all other restore tabs ; remove the active items
			jQuery(".this_leaf_node li").removeClass("selected");
			jQuery(".toggle_files.selection_mode_on").click();

			jQuery(par_obj).addClass("open");
			jQuery(".changed_files_count, .this_restore", par_obj).show();
			jQuery(".this_restore_point_wptc", par_obj).hide();
			jQuery(".restore_to_staging_wptc", par_obj).hide();
			jQuery(this).addClass("selection_mode_on");
		} else {
			jQuery(par_obj).removeClass("open");
			jQuery(".changed_files_count, .this_restore", par_obj).hide();
			jQuery(".this_restore_point_wptc", par_obj).show();
			jQuery(".restore_to_staging_wptc", par_obj).show();
			jQuery(this).removeClass("selection_mode_on");
		}
		e.stopImmediatePropagation();
		if (typeof styling_thickbox_tc !== 'undefined' && jQuery.isFunction(styling_thickbox_tc)) {
			styling_thickbox_tc("");
		}
		return false;
	});

	jQuery(".folder").on("click", function(e) {
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		get_sibling_files_wptc(this);
		e.stopImmediatePropagation();
		return false;
	});

	jQuery(".restore_the_db").on("click", function() {
		var par_obj = jQuery(this).closest(".single_group_backup_content");
		if (!jQuery(this).hasClass("selected")) {
			jQuery(".sql_file", par_obj).parent(".this_parent_node").prev(".sub_tree_class").removeClass("selected");
			jQuery(".sql_file li", par_obj).removeClass("selected");
		} else {
			jQuery(".sql_file", par_obj).parent(".this_parent_node").prev(".sub_tree_class").addClass("selected");
			jQuery(".sql_file li", par_obj).addClass("selected");
		}

		if ((!jQuery(".this_leaf_node li", par_obj).hasClass("selected")) && (!jQuery(".sub_tree_class", par_obj).hasClass("selected"))) {
			jQuery(".this_restore", par_obj).addClass("disabled");
		} else {
			jQuery(".this_restore", par_obj).removeClass("disabled");
		}
	});

	jQuery('.this_restore').on('click', function(e) {
		if (jQuery(this).hasClass("disabled")) {
			return false;
		}
		restore_obj = this;
		restore_type = 'selected_files';
		wptc_restore_confirmation_pop_up();
		return false;

	});

	jQuery('.this_restore_point_wptc').on('click', function(e) {
		restore_obj = this;
		restore_type = 'to_point';
		wptc_restore_confirmation_pop_up();
		return false;
	});



	jQuery("#TB_overlay").on("click", function() {
		if ((typeof is_backup_started == 'undefined' || is_backup_started == false) && !on_going_restore_process) { //for enabling dialog close on complete
			tb_remove();
			backupclickProgress = false;
		}
	});

	jQuery(".dialog_close").on("click", function() {
		tb_remove();
	});
}

function yes_continue_restore_wptc(){
	revert_confirmation_backup_popups();
	if (restore_type == 'selected_files') {
		trigger_selected_files_restore();
	} else if(restore_type == 'to_point'){
		trigger_to_point_restore();
	}
}

function revert_confirmation_backup_popups(){
	jQuery('.wptc_restore_confirmation').remove();
	jQuery('#TB_ajaxContent').show();
	jQuery('#TB_overlay').show();
}

function wtc_initializeRestore(obj, type) {
	//this function returns the files to be restored ; shows the dialog box ; clear the reload timeout for backup ajax function
	var files_to_restore = {};

	var par_obj = jQuery(obj).closest('.single_group_backup_content');

	if (type == 'all') {
		var is_selected = ''; //a trick to include all files during restoring-at-a-point;

		var sql_obj_wptc = jQuery(".this_leaf_node li.sql_file_li", par_obj);

		var this_revision_id = jQuery(sql_obj_wptc).find(".file_path").attr("revision_id");

		files_to_restore[this_revision_id] = {};
		files_to_restore[this_revision_id]['file'] = jQuery(sql_obj_wptc).find(".file_path").attr("file_name");
		files_to_restore[this_revision_id]['uploaded_file_size'] = jQuery(sql_obj_wptc).find(".file_path").attr("file_size");
		files_to_restore[this_revision_id]['g_file_id'] = jQuery(sql_obj_wptc).find(".file_path").attr("g_file_id");
		files_to_restore[this_revision_id]['mtime_during_upload'] = jQuery(sql_obj_wptc).find(".file_path").attr("mod_time");

	} else {
		var is_selected = '.selected';
		var files_to_restore = {};
		files_to_restore['folders'] = {};
		files_to_restore['files'] = {};
		var folders_count = 0;
		var files_count = 0;
		var selected_items = jQuery(par_obj).find(is_selected);
		jQuery.each(selected_items, function(key, value) {
			if (jQuery(value).hasClass('sub_tree_class') && jQuery(value).hasClass('restore_the_db') == false) {
				files_to_restore['folders'][folders_count] = {};
				files_to_restore['folders'][folders_count]['file'] = jQuery(value).children().attr('file_name');
				files_to_restore['folders'][folders_count]['backup_id'] = jQuery(value).children().attr('backup_id');
				folders_count++;
			} else {
				files_to_restore['files'][files_count] = {};
				files_to_restore['files'][files_count]['file'] = jQuery(value).children().attr('file_name');
				files_to_restore['files'][files_count]['backup_id'] = jQuery(value).children().attr('backup_id');
				files_to_restore['files'][files_count]['revision_id'] = jQuery(value).children().attr('revision_id');
				files_to_restore['files'][files_count]['mtime_during_upload'] = jQuery(value).children().attr('mod_time');
				files_to_restore['files'][files_count]['g_file_id'] = jQuery(value).children().attr('g_file_id');
				files_to_restore['files'][files_count]['uploaded_file_size'] = jQuery(value).children().attr('file_size');
				files_count++;
			}
		});
	}

	prepareRestoreProgressDialogWPTC();

	if (typeof reloadFuncTimeout != 'undefined') {
		clearTimeout(reloadFuncTimeout);
	}
	return files_to_restore;
}

function prepareRestoreProgressDialogWPTC(){
	var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1; color: #444;padding: 0px 34px 26px 34px; left:20%; z-index:1000 "><div class="pu_title">Restoring ' + sitenameWPTC + '</div><div class="wcard progress_reverse" style="height:60px; padding:0;"><div class="progress_bar" style="width:0%;"></div>  <div class="progress_cont">Preparing files to restore...</div></div><div style="padding: 10px; text-align: center;">Note: Please do not close this tab until restore completes.</div></div>';

	jQuery("#TB_ajaxContent").html(this_html);
	if (typeof styling_thickbox_tc !== 'undefined' && jQuery.isFunction(styling_thickbox_tc)) {
		styling_thickbox_tc('restore');
	}
}

function getTcRestoreProgress() {

	if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
		var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/restore-progress-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	} else {
		var this_url = 'restore-progress-ajax.php';
	}

	jQuery.ajax({
		traditional: true,
		type: 'post',
		url: this_url,
		data: {
			wptc_request: true
		},
		success: function(request) {
			request = parse_wptc_response_from_raw_data(request);
			request = jQuery.parseJSON(request);
			if ((typeof request != 'undefined') && request != null && request['status'] != null) {
				if (request['status'] === 'process' || request['status'] === 'analyze') {
						jQuery(".progress_reverse .progress_cont").html(request['msg']);
					} else if (request['status'] === 'download' || request['status'] === 'copy') {
						jQuery('.progress_reverse .progress_cont').html(request['msg']);
						if (request['percentage'] != 0) {
							jQuery('.progress_reverse .progress_bar').css('width', request['percentage'] + '%' );
						}
					} else {
						jQuery('.progress_reverse .progress_cont').html(request['msg']);
						jQuery('.progress_reverse .progress_bar').css('width', '0%' );
					}
			} else if (request == null) {
				if (typeof getRestoreProgressTimeout != 'undefined') {
					clearTimeout(getRestoreProgressTimeout);
				}
			}
		},
		error: function() {

		}
	});
	getRestoreProgressTimeout = setTimeout(function() {
		getTcRestoreProgress();
	}, 10000);
}

function startRestore(files_to_restore, cur_res_b_id, selectedID, is_first_call) {
	start_time_tc = Date.now(); //global variable which will be used to see the activity so as to trigger new call when there is no activity for 60secs
	on_going_restore_process = true;

	if (typeof reloadFuncTimeout != 'undefined') {
		clearTimeout(reloadFuncTimeout);
	}

	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: 'start_restore_tc_wptc',
		data: {
			cur_res_b_id : cur_res_b_id,
			files_to_restore : files_to_restore,
			selectedID : selectedID,
			is_first_call : is_first_call,
			wptc_request : true,
		},
		dataType: 'json',
	}, function(request) {
		console.log('startRestore', request);
		if ((typeof request != 'undefined') && request != null) {
			if (request.indexOf("wptcs_callagain_wptce") != -1) {
				startRestore();
			} else if (request.indexOf("restoreInitiatedResult") != -1) {
				request = jQuery.parseJSON(request);
				if (typeof request['restoreInitiatedResult'] != 'undefined' && typeof request['restoreInitiatedResult']['bridgeFileName'] != 'undefined' && request['restoreInitiatedResult']['bridgeFileName']) {
					cuurent_bridge_file_name = request['restoreInitiatedResult']['bridgeFileName'];
					getTcRestoreProgress();
					if (request['restoreInitiatedResult']['is_restore_to_staging']) {
						request['initialize'] = true;
						request['redirect_url'] = request['restoreInitiatedResult']['staging_url'];
						startBridgeDownload(request);
					} else {
						request['initialize'] = true;
						startBridgeDownload(request);
					}
					checkIfNoResponse('startBridgeDownload');
				} else {
					show_error_dialog_and_clear_timeout({ error: 'Didnt get required values to initiated restore.' });
				}
			} else if (request.indexOf("error") != -1) {
				request = jQuery.parseJSON(request);
				if (typeof request['error'] != 'undefined') {
					show_error_dialog_and_clear_timeout(request);
				}
			} else {
				show_error_dialog_and_clear_timeout({ error: 'Initiating Restore failed.' });
			}
		}
	});
}

function startRestore_bridge(files_to_restore, cur_res_b_id, selectedID, ignore_file_write_check) {
	start_time_tc = Date.now(); //global variable which will be used to see the activity so as to trigger new call when there is no activity for 60secs
	on_going_restore_process = true;

	if (typeof reloadFuncTimeout != 'undefined') {
		clearTimeout(reloadFuncTimeout);
	}

	jQuery.post('index.php', {
		traditional: true,
		type: 'post',
		url: 'index.php',
		data: {
			cur_res_b_id : cur_res_b_id,
			files_to_restore : files_to_restore,
			selectedID : selectedID,
			wptc_request : true,
		},
	}, function(request) {
		request = parse_wptc_response_from_raw_data(request);
		try{
			request = jQuery.parseJSON(request);
		} catch(err){
			show_error_dialog_and_clear_timeout({ error: 'Didnt get required values to initiated restore.' });
			return ;
		}
		if ((typeof request != 'undefined') && request != null) {
			if (typeof request.restoreInitiatedResult != 'undefined') {
				if (typeof request['restoreInitiatedResult'] != 'undefined' && typeof request['restoreInitiatedResult']['bridgeFileName'] != 'undefined' && request['restoreInitiatedResult']['bridgeFileName']) {
					cuurent_bridge_file_name = request['restoreInitiatedResult']['bridgeFileName'];
					getTcRestoreProgress();
					request['initialize'] = true;
					startBridgeDownload(request);
					checkIfNoResponse('startBridgeDownload');
				} else {
					show_error_dialog_and_clear_timeout({ error: 'Didnt get required values to initiated restore.' });
				}
			} else if (typeof request.error != 'undefined') {
				if (typeof request['error'] != 'undefined') {
					show_error_dialog_and_clear_timeout(request);
				}
			} else {
				show_error_dialog_and_clear_timeout({ error: 'Initiating Restore failed.' });
			}
		}
	});
}

function show_error_dialog_and_clear_timeout(request) {
	hard_reset_restore_settings_wptc();
	if (typeof checkIfNoResponseTimeout != 'undefined') {
		clearTimeout(checkIfNoResponseTimeout);
	}
	if (typeof getRestoreProgressTimeout != 'undefined') {
		clearTimeout(getRestoreProgressTimeout);
	}

	var this_head = '<div class="this_modal_div" style="background-color: #f1f1f1; color: #444;padding: 0px 34px 26px 34px; left:20%; z-index:1000"><div class="pu_title">ERROR DURING RESTORE</div><div class="wcard progress_reverse error" style="overflow: scroll;max-height: 210px; padding:0;">  <div class="" style="text-overflow: ellipsis;word-wrap: break-word;text-align: center;padding-top: 19px;padding-bottom: 19px;">' + request['error'];

	var content = get_failure_data_wptc(request);

	var this_html = this_head + content + '</div></div><div style="padding: 10px; text-align: center;">Note: Please do not close this tab until restore completes.</div></div>';
	jQuery("#TB_ajaxContent").html(this_html);
}

function hard_reset_restore_settings_wptc(){
	 if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
		var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	} else {
		var this_url = 'wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	}

	jQuery.ajax({
		traditional: true,
		type: 'post',
		url: this_url,
		dataType: 'json',
		data: {
			action: 'reset_restore_settings',
			wptc_request: true
		},
		success: function(request) {
		},
	});
}


function wptc_get_search_params(key){
	var p={};
	location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(s,k,v){p[key]=v})
	return key?p[key]:p;
}

function startBridgeDownload(data) {
	console.log('startBridgeDownload', data);

	start_time_tc = Date.now();

	if (typeof getRestoreProgressTimeout == 'undefined') {
		getTcRestoreProgress();
	}

	if(jQuery('.restore_process').length == 0 && jQuery('#TB_ajaxContent').length == 0){
		jQuery('body').append("<div class='restore_process'><div id='TB_ajaxContent'><div class='pu_title'>Restoring your website</div><div class='wcard progress_reverse' style='height:60px; padding:0;'><div class='progress_bar' style='width: 0%;'></div>  <div class='progress_cont'>Preparing files to restore...</div></div><div style='padding: 10px; text-align: center;'>Note: Please do not close this tab until restore completes.</div></div>");
	}

	var this_data = {};

	var is_restore_in_staging = false;

	if (typeof data != 'undefined' && typeof data.redirect_url != 'undefined' && data.redirect_url) {
		this_home_url_wptc = data.redirect_url;
		var is_restore_in_staging = true;
	}

	if (window.location.href.indexOf('wp-tcapsule-bridge') === -1) {
		if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
			var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/index.php?continue=true&position=beginning&is_restore_in_staging=' + is_restore_in_staging; //cuurent_bridge_file_name is a global variable and is set already
		} else {
			var this_url = '/index.php?continue=true&position=beginning&is_restore_in_staging=' + is_restore_in_staging; //cuurent_bridge_file_name is a global variable and is set already
		}
		window.location.assign(this_url);
		return false;
	}

	if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
		var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	} else {
		var this_url = 'wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	}

	if (typeof data != 'undefined') {
		this_data = data;
	}

	this_data['wptc_request'] = true;

	jQuery.ajax({
		traditional: true,
		type: 'post',
		url: this_url,
		data: this_data,
		// dataType: 'json',
		success: function(request) {
			request = parse_wptc_response_from_raw_data(request);
			request = jQuery.parseJSON(request);
			if (typeof request != 'undefined' && request != null) {
				// jsonParsedRequest = jQuery.parseJSON(request);

				if (request == 'wptcs_callagain_wptce') {
					startBridgeDownload();
				} else if (request == 'continue_from_email') {
					if (typeof checkIfNoResponseTimeout != 'undefined') {
						clearTimeout(checkIfNoResponseTimeout);
					}

					startBridgeCopy(start_bridge_copy);
					checkIfNoResponse('startBridgeCopy');
				} else if (request == 'wptcs_over_wptce') {
				   startBridgeDownloadOver();
				} else if (typeof request.error != 'undefined') {
					//request = jQuery.parseJSON(request);
					show_error_dialog_and_clear_timeout(request);
				} else if (typeof request.not_safe_for_write_limit_reached != 'undefined' && request.not_safe_for_write_limit_reached) {
					//request = jQuery.parseJSON(request);
					show_safe_files_limit_dialog_and_clear_timeout(request.not_safe_for_write_limit_reached);
				}
			}
		},
		error: function(errData) {
			if (errData.responseText.indexOf('wptcs_callagain_wptce') !== -1) {
				startBridgeDownload();
				return false;
			}else if (errData.responseText.indexOf('wptcs_over_wptce') !== -1) {
				startBridgeDownloadOver();
				return false;
			}
			if(!wptc_restore_retry_limit_checker()){
				if (errData.status != 200) {
					var deep_err_check = errData.responseText.replace(/\s+/, "");
					 if(deep_err_check == ''){
						var fomatted_err_msg = 'Ajax call returned error: '+errData.statusText;
					 } else {
						var fomatted_err_msg = 'Ajax call returned error: '+errData.responseText;
					}
					show_error_dialog_and_clear_timeout({ error: fomatted_err_msg });
				} else {
					show_error_dialog_and_clear_timeout({ error: 'unknown error occured  :-(' });
				}
				return false;
			}
			if(deep_err_check == ''){
				var fomatted_err_msg = 'Ajax call returned error: '+errData.statusText;
			} else {
				var fomatted_err_msg = 'Ajax call returned error: '+errData.responseText;
			}
			show_error_dialog_and_clear_timeout({ error: fomatted_err_msg });
		}
	});

}

function startBridgeDownloadOver(){
	 if (typeof checkIfNoResponseTimeout != 'undefined') {
		clearTimeout(checkIfNoResponseTimeout);
	}
	var start_bridge_copy = {};
	start_bridge_copy['initialize'] = true;
	start_bridge_copy['wp_prefix'] = wp_base_prefix_wptc; //getting from global var

	startBridgeCopy(start_bridge_copy);
	checkIfNoResponse('startBridgeCopy');
}

function wptc_restore_retry_limit_checker(){

	var max_retry = 3;
	if (typeof wptc_restore_retry_count == 'undefined') {
		wptc_restore_retry_count = 1;
	} else {
		wptc_restore_retry_count++;
	}
	if (wptc_restore_retry_count >= max_retry) {
		get_last_php_error();
		return false;
	} else {
		return true;
	}
}

function get_last_php_error(){
	if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
		var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	} else {
		var this_url = 'wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	}

	jQuery.ajax({
		traditional: true,
		type: 'post',
		url: this_url,
		data: {
			action: 'get_last_php_error',
			wptc_request: true,
		},
		success: function(request) {
			request = parse_wptc_response_from_raw_data(request);
			request = jQuery.parseJSON(request);
			var deep_err_check = request.replace(/\s+/, "");
			if (request && deep_err_check) {
				show_error_dialog_and_clear_timeout({ error: request });
			} else {
				show_error_dialog_and_clear_timeout({ error: 'unknown error occured  :-(' });
			}
		},
	});
}

function show_safe_files_limit_dialog_and_clear_timeout(filesObj) {
	if (typeof checkIfNoResponseTimeout != 'undefined') {
		clearTimeout(checkIfNoResponseTimeout);
	}
	if (typeof getRestoreProgressTimeout != 'undefined') {
		clearTimeout(getRestoreProgressTimeout);
	}

	jQuery("#TB_ajaxContent").html('');

	var files_div = '';
	jQuery.each(filesObj, function(k, v) {
		files_div += '<p> - ' + k + '</p>';
	});

	var btn_div = '';
	btn_div = '<input type="button" class="button-primary resume_restore_ignore_selected_files_write_wptc" value="Skip these all files &amp; restore" style="float: right;">';
	btn_div += '<input type="button" class="button-primary resume_restore_ignore_all_files_write_wptc" value="Skip all unwritable files &amp; restore" style="margin-right: 30px;float: right;">';
	btn_div += '<input type="button" class="button-primary resume_restore_restart_file_write_wptc" value="Try Again">';

	var this_html = '';
	this_html += '<div class="this_modal_div" style="background-color: #f1f1f1;color: #444;padding: 0px 34px 26px 34px; left:20%; z-index:1000">';
	this_html += '<div class="pu_title">FILES NOT WRITABLE FOR RESTORE</div>'+
	'<div style="line-height: 22px; margin-bottom: 20px;">The following files are not writable. Please change the file permissions or enable FTP for this restore - <a href="http://docs.wptimecapsule.com/article/10-enable-ftp-file-permissions" target="_blank"> Check how? </a></div>'+
	'<div class="wcard progress_reverse error" style="overflow: scroll;max-height: 210px; padding:0;width: auto;margin: 0 auto 20px;padding-left: 10px;">';
	this_html += '<div class="error_files_cont_wptc">' + files_div + '</div>';
	this_html += '</div>';
	this_html += '<div class="error_files_btn_wptc">' + btn_div + '</div>';
	this_html += '</div>';

	jQuery("#TB_ajaxContent").html(this_html);
}

function startBridgeCopy(data) {
	start_time_tc = Date.now();

	var this_data = {};

	if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
		var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/wptc-copy.php'; //cuurent_bridge_file_name is a global variable and is set already
	} else {
		var this_url = 'wptc-copy.php'; //cuurent_bridge_file_name is a global variable and is set already
	}

	if (typeof data != 'undefined') {
		this_data = data;
	}

	this_data['wptc_request'] = true;

	jQuery.ajax({
		traditional: true,
		type: 'post',
		url: this_url,
		data: this_data,
		// dataType: 'json',
		success: function(request) {
			request = parse_wptc_response_from_raw_data(request);
			request = jQuery.parseJSON(request);
			if (typeof request != 'undefined' && request != null) {
				if (request == 'wptcs_callagain_wptce') {
					startBridgeCopy({ wp_prefix: wp_base_prefix_wptc }); //getting from global variable
				} else if (typeof request['status'] != 'undefined' &&  request['status'] === 'wptcs_over_wptce') {
					startBridgeCopyOver(request);
				}  else if (request == 'wptcs_over_wptce') {
					startBridgeCopyOver();
				} else if (typeof request['error'] != 'undefined' || typeof request['status'] != 'undefined') {
					show_error_dialog_and_clear_timeout(request);
				} else {
					show_error_dialog_and_clear_timeout({ error: 'Fatal error during Bridge Process.' });
				}
			}
		},
		error: function(errData) {
			if (errData.responseText.indexOf('wptcs_callagain_wptce') !== -1) {
				startBridgeCopy({ wp_prefix: wp_base_prefix_wptc }); //getting from global variable
				return false;
			} else if (errData.responseText.indexOf('wptcs_over_wptce') !== -1) {
				startBridgeCopyOver();
				return false;
			}
			if(!wptc_restore_retry_limit_checker()){
				return false;
			}
			if (typeof errData.responseText == 'undefined' ||errData.responseText == undefined || !errData.responseText) {
				setTimeout(function(){
					startBridgeCopy({ wp_prefix: wp_base_prefix_wptc }); //getting from global variable
				}, 5000);
				return false;
			} else {
				var deep_err_check = errData.responseText.replace(/\s+/, "");
				if (!deep_err_check || deep_err_check == 'undefined' || deep_err_check == '') {
					setTimeout(function(){
						startBridgeCopy({ wp_prefix: wp_base_prefix_wptc }); //getting from global variable
					}, 5000);
					return false;
				}
			}
			var fomatted_err_msg = 'Ajax call returned error: '+errData.responseText;
			show_error_dialog_and_clear_timeout({ error: fomatted_err_msg });
		}
	});
}

function startBridgeCopyOver(request){
	clearTimeout(checkIfNoResponseTimeout);
	if (typeof getRestoreProgressTimeout != 'undefined') {
		clearTimeout(getRestoreProgressTimeout);
	}

	var success_content = 'Your site was restored successfully. Yay! ';

	if (wptc_get_search_params('is_restore_in_staging') == "true") {
		success_content = 'Your staging site was restored successfully. Yay! ';
	}

	var this_head = '<div class="<div class="this_modal_div" style="background-color: #f1f1f1;  color: #444;padding: 0px 34px 26px 34px; left:20%; z-index:1000"><span class="dialog_close"></span><div class="pu_title">DONE</div><div class="wcard clearfix" style="width:375px"><div class="l1">' + success_content;

	var content = get_failure_data_wptc(request);

	//redirect if no error found on restore.
	if (content != '') {
		var this_html = this_head + content + '</div>  </div></div>';
	} else {
		var this_html = this_head + '<br> Redirecting in 5 secs... </div>  </div></div>';
	}

	jQuery("#TB_ajaxContent").html(this_html);
	if(location.href.toLowerCase().indexOf('wp-tcapsule-bridge') !== -1){
		 jQuery('.this_modal_div').css('left','40%');
	 }

	if (typeof redirect_to_plugin_home == 'undefined' && content == '') {
		redirect_to_plugin_home = setTimeout(function() {
			if (wptc_get_search_params('is_restore_in_staging') == "true" ) {
				parent.location.assign(this_home_url_wptc);
			} else {
				parent.location.assign(wptcMonitorPageURl);
			}
		}, 3000);
	}
}

function get_failure_data_wptc(request){
	var content = '';
	//add failed files file list if some files failed to download
	if (typeof request != 'undefined' && typeof request['failure_data'] != 'undefined' && typeof request['failure_data']['failed_files'] != 'undefined') {
		content += ' <br> <a href="'+wptcMonitorPageURl+'">Take me to WP-admin</a> <br><br>And unable to download these <a href='+request['failure_data']['failed_files']+' target="_blank">files</a>';
	}

	//add failed queries file link if some queries failed to execute
	if (typeof request != 'undefined' && typeof request['failure_data'] != 'undefined' && typeof request['failure_data']['failed_queries'] != 'undefined') {
		content += '<br>And unable to execute these <a href='+request['failure_data']['failed_queries']+' target="_blank">queries</a>';
	}

	//If added any links then ask them to contact
	if (content != '') {
		content += '<br><a href="http://docs.wptimecapsule.com/article/27-what-you-can-do-with-your-wptc-restore-logs" target="_blank">Help?</a> or email us at  <a href="mailto:help@wptimecapsule.com?Subject=Contact" target="_top">help@wptimecapsule.com</a>';
	}

	return content;
}

function checkIfNoResponse(this_func) {
	if (typeof this_func != 'undefined' && this_func != null) {
		ajax_function_tc = this_func;
	}

	var this_time_tc = Date.now();
	if ((this_time_tc - start_time_tc) >= 60000) {
		if (ajax_function_tc == 'startBridgeCopy') {
			var continue_bridge = {};
			continue_bridge['wp_prefix'] = wp_base_prefix_wptc; //am sending the prefix ; since it is a bridge
			startBridgeCopy(continue_bridge);
		} else if (ajax_function_tc == 'startBridgeDownload') {
			startBridgeDownload();
		}
	}
	if (typeof checkIfNoResponseTimeout != 'undefined') {
		clearTimeout(checkIfNoResponseTimeout);
	}
	checkIfNoResponseTimeout = setTimeout(function() {
		checkIfNoResponse();
	}, 15000);
}

function wptc_restore_confirmation_pop_up(){
	swal({
		title: '<div class="wptc-swal-title">Restore your website?</div>',
		html: '<div class="wptc-swal-msg" >Clicking on Yes will continue to restore your website. Are you sure want to continue ?</div>',
		type: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes',
		cancelButtonText: 'No, Cancel',
	}).then(function () {
			yes_continue_restore_wptc();
		}, function (dismiss) {
			revert_confirmation_backup_popups();
		}
	);

	jQuery('#TB_overlay').hide();
	jQuery('#TB_ajaxContent').hide();
}

function trigger_selected_files_restore(){
	var selectedID = jQuery('.open').attr('this_backup_id');
	var files_to_restore = {};
	files_to_restore = wtc_initializeRestore(jQuery(restore_obj), 'single');
	startRestore(files_to_restore, false, selectedID, true);
	checkIfNoResponse('startRestore');
	// e.stopImmediatePropagation();
	return false;
}

function trigger_to_point_restore(){
	var cur_res_b_id = jQuery(restore_obj).closest(".single_group_backup_content").attr("this_backup_id");
	var files_to_restore = {};
	files_to_restore = wtc_initializeRestore(jQuery(restore_obj), 'all');
	startRestore(false, cur_res_b_id, '', true);
	// e.stopImmediatePropagation();
	return false;
}

function parse_wptc_response_from_raw_data(raw_response){
	//return substring closed by <wptc_head> and </wptc_head>
	return raw_response.split('<wptc_head>').pop().split('</wptc_head>').shift();
}

jQuery(document).ready(function() {

	jQuery('#start_backup').on('click', function() {
		if (jQuery(this).text() != 'Stop Backup') {
			is_backup_started = true; //setting global variable for backup status
			jQuery(this).text("Stop Backup");
			wtc_start_backup_func('');
		} else {
			wtc_stop_backup_func();
		}
	});

	jQuery('#stop_backup').on('click', function() {
		if (jQuery(this).text() != 'Stop Backup') {
			jQuery(this).text("Stop Backup");
			wtc_start_backup_func('');
		} else {
			wtc_stop_backup_func();
		}
	});

	jQuery('body').on('click', '.bridge_restore_now',function(e) {
		var cur_res_b_id = jQuery(this).attr('backup_id');
		var files_to_restore = {};

		jQuery('.show_restores').hide();
		jQuery('.restore_process').show();

		startRestore_bridge(files_to_restore, cur_res_b_id);

		e.stopImmediatePropagation();
		return false;
	});


	jQuery('body').on('click', '.resume_restore_ignore_selected_files_write_wptc', function(e) {
		prepareRestoreProgressDialogWPTC();

		getTcRestoreProgress();

		startBridgeDownload({ignore_file_write_check: 0});

		checkIfNoResponse('startBridgeDownload');

		e.stopImmediatePropagation();
		return false;
	});

	jQuery('body').on('click', '.resume_restore_ignore_all_files_write_wptc', function(e) {
		prepareRestoreProgressDialogWPTC();

		getTcRestoreProgress();

		startBridgeDownload({ignore_file_write_check: 1});

		checkIfNoResponse('startBridgeDownload');

		e.stopImmediatePropagation();
		return false;
	});

	jQuery('body').on('click', '.resume_restore_restart_file_write_wptc', function(e) {
		prepareRestoreProgressDialogWPTC();

		getTcRestoreProgress();

		startBridgeDownload({ignore_file_write_check: 2});

		checkIfNoResponse('startBridgeDownload');

		e.stopImmediatePropagation();
		return false;
	});

	jQuery('#stop_restore_tc').on('click', function() {
		wtc_stop_restore_func();
	});

	jQuery('#stop_restore_tc').on('click', '.this_modal_div', function(e) {
		wtc_stop_restore_func();
	});

	jQuery('.restore_err_demo_wptc').on('click', function(e) {
		jQuery('.notice, #update-nag').remove();
		var yo_files = {
			'wp-content/dark/wp-file-1': 1,
			'wp-content/dark/wp-file-2': 1,
			'wp-content/dark/wp-file-3': 1,
			'wp-content/dark/wp-file-4': 1,
		};
		jQuery(".wptc-thickbox").click();
		if (typeof styling_thickbox_tc !== 'undefined' && jQuery.isFunction(styling_thickbox_tc)) {
			styling_thickbox_tc();
		}
		show_safe_files_limit_dialog_and_clear_timeout(yo_files);
	});
});
