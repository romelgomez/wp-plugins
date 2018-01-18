jQuery(document).ready(function($) {

	jQuery("#select_wptc_backup_slots").on('change', function(){
		var value = jQuery(this).val();
		if (value === 'daily') {
			jQuery('#select_wptc_default_schedule').show();
		} else {
			jQuery('#select_wptc_default_schedule').hide();
		}
	});

	if(jQuery("#select_wptc_backup_slots").val() === 'daily'){
		jQuery('#select_wptc_default_schedule').show();
	}

	jQuery("#wptc_save_changes").on("click", function() {
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		jQuery('#calculating_file_db_size_temp, #show_final_size').toggle();
		jQuery(this).addClass('disabled').attr('disabled', 'disabled').val('Saving new changes...').html('Saving...');
		save_settings_wptc();
		return false;
	});

	jQuery('body').on('click', '.change_dbox_user_tc', function(e) {
		if (jQuery(this).hasClass('wptc-link-disabled')) {
			e.stopImmediatePropagation();
			e.preventDefault();
			return false;
		}
	});

	jQuery("#toggle_exlclude_files_n_folders").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();
		jQuery("#wptc_exc_files").toggle();
		if (jQuery("#wptc_exc_files").css('display') === 'block') {
			fancy_tree_init_exc_files_wptc();
		}
		return false;
	});

	jQuery("#wptc_init_toggle_files").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();
		if (jQuery("#wptc_exc_files").css('display') === 'block') {
			return false;
		}
		jQuery("#wptc_exc_files").toggle();
		if (jQuery("#wptc_exc_files").css('display') === 'block') {
			if (typeof wptc_file_size_in_bytes != 'undefined') {
				jQuery("#included_file_size").html(convert_bytes_to_hr_format(wptc_file_size_in_bytes));
				jQuery("#file_size_in_bytes").html(wptc_file_size_in_bytes);
			}
			fancy_tree_init_exc_files_wptc(1);

		}
		return false;
	});

	jQuery("#toggle_wptc_db_tables").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();
		jQuery("#wptc_exc_db_files").toggle();
		if (jQuery("#wptc_exc_db_files").css('display') === 'block') {
			fancy_tree_init_exc_tables_wptc();
		}
		return false;
	});

	jQuery("#wptc_init_toggle_tables").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();
		if (jQuery("#wptc_exc_db_files").css('display') === 'block') {
			return false;
		}
		jQuery("#wptc_exc_db_files").toggle();
		if (jQuery("#wptc_exc_db_files").css('display') === 'block') {
			fancy_tree_init_exc_tables_wptc(1);
		}
		return false;
	});

	jQuery('body').on('click', '#connect_to_cloud, #save_g_drive_refresh_token', function(e) {

		jQuery('.cloud_error_mesg, .cloud_error_mesg_g_drive_token').html('');
		var cloud_type_wptc = $(this).attr("cloud_type");
		var auth_url_func = '';
		var wptc_gdrive_token_btn = false;
		var data = {};

		if (cloud_type_wptc == 'dropbox') {
			auth_url_func 	= 'get_dropbox_authorize_url_wptc';
			cloud_type 		= 'Dropbox';
		} else if (cloud_type_wptc == 'g_drive') {
			if(jQuery('#gdrive_refresh_token_input_wptc').is(':visible') && this.id === 'save_g_drive_refresh_token' ){
				if(jQuery('#gdrive_refresh_token_input_wptc').val().length < 1){
					jQuery('.cloud_error_mesg_g_drive_token').html('Please enter the token !').show();
					return false;
				}
				wptc_gdrive_token_btn = true;
				data['g_drive_refresh_token'] = jQuery('#gdrive_refresh_token_input_wptc').val();
			}
			auth_url_func = 'get_g_drive_authorize_url_wptc';
			cloud_type = 'Google Drive';
		} else if (cloud_type_wptc == 's3') {
			data['as3_access_key']      = jQuery('#as3_access_key').val();
			data['as3_secure_key']      = jQuery('#as3_secure_key').val();
			data['as3_bucket_region']   = jQuery('#as3_bucket_region').val();
			data['as3_bucket_name']     = jQuery('#as3_bucket_name').val();
			auth_url_func 				= 'get_s3_authorize_url_wptc';
			cloud_type 					= 'Amazon S3';
		}

		jQuery('.cloud_error_mesg').removeClass('cloud_acc_connection_error').html('').hide();

		if (auth_url_func == '') {
			return false;
		}

		if (cloud_type_wptc === 'g_drive' && !wptc_gdrive_token_btn) {
			wptc_tmp_auth_url_func = auth_url_func;
			wptc_tmp_data = data;
			wptc_tmp_gdrive_token_btn = wptc_gdrive_token_btn;
			wptc_tmp_cloud_type = cloud_type;
			swal({
				title: '<div class="wptc-swal-title">Are you sure?</div>',
				html: '<div class="wptc-swal-msg">Google has a limit on the number of sites you can authenticate per account, once that is crossed then all your other sites will be revoked! <br> Are you sure about connecting it to a different account ? </div>',
				type: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, I am sure',
				cancelButtonText: 'No, its the same',
			}).then(function () {
				swal(
					'<div class="wptc-swal-title">Redirecting...</div>',
					'<div class="wptc-swal-msg">You will be redirected to the authorization page once the process is compeleted.</div>',
					'success'
					);
					wptc_make_cloud_auth_req(wptc_tmp_auth_url_func, wptc_tmp_data, wptc_tmp_gdrive_token_btn, wptc_tmp_cloud_type);
				}, function (dismiss) {
				// dismiss can be 'cancel', 'overlay',
				// 'close', and 'timer'
				if (dismiss === 'cancel') {
					swal(
						'<div class="wptc-swal-title">For more info</div>',
						'<div class="wptc-swal-msg">Please read <a href="http://docs.wptimecapsule.com/article/23-add-new-site-using-existing-google-drive-token">this</a> on how to use the existing authorization token.</div>',
						'warning'
					)
				}
			})

			return false;
		}

		wptc_make_cloud_auth_req(auth_url_func, data, wptc_gdrive_token_btn, cloud_type);
	});

	if (typeof Clipboard != 'undefined') {
		var clipboard = new Clipboard("#copy_gdrive_token_wptc");
		if (clipboard != undefined) {
			clipboard.on("success", function(e) {
				jQuery("#gdrive_token_copy_message_wptc").show();
				setTimeout( function (){
					jQuery("#gdrive_token_copy_message_wptc").hide();
				},1000);
				e.clearSelection();
			});
			clipboard.on("error", function(e) {
				jQuery("#copy_gdrive_token_wptc").remove();
				jQuery("#gdrive_refresh_token_wptc").click(function(){jQuery(this).select();});
			});
		}else{
			jQuery("#gdrive_refresh_token_wptc").click(function(){jQuery(this).select();});
		}
	}

	jQuery('#start_backup_from_settings').click(function(e){
		e.stopImmediatePropagation();
		e.preventDefault();
		if (jQuery("#start_backup_from_settings").hasClass('disabled')) {
			return false;
		}
		start_manual_backup_wptc(this);
	});

	jQuery("#backup_type").on('change', function(){
		var cur_backup_type = jQuery(this).val();
		if(cur_backup_type == 'WEEKLYBACKUP' || cur_backup_type == 'AUTOBACKUP'){
			jQuery('#select_wptc_default_schedule').hide();
			jQuery('.init_backup_time_n_zone').html('Timezone');
		} else {
			jQuery('#select_wptc_default_schedule').show();
			jQuery('.init_backup_time_n_zone').html('Backup Schedule and Timezone');
		}
	});

	jQuery("#select_wptc_cloud_storage").on('change', function(){
		jQuery(".creds_box_inputs", this_par).hide();
		jQuery('#connect_to_cloud').show();
		jQuery('#s3_seperate_bucket_note, #see_how_to_add_refresh_token_wptc, #gdrive_refresh_token_input_wptc, #google_token_add_btn, #google_limit_reached_text_wptc').hide();
		jQuery('.dummy_select, .wptc_error_div').remove();

		jQuery(".cloud_error_mesg, .cloud_error_mesg_g_drive_token").hide();
		var cur_cloud = jQuery(this).val();
		if(cur_cloud == ""){
			return false;
		}
		var cur_cloud_label = get_cloud_label_from_val_wptc(cur_cloud);
		var this_par = jQuery(this).closest(".wcard");
		jQuery("#connect_to_cloud, #save_g_drive_refresh_token").attr("cloud_type", cur_cloud);
		jQuery("#connect_to_cloud").val("Connect to " + cur_cloud_label).show();
		jQuery("#mess").show();
		jQuery("#donot_touch_note").show();
		jQuery("#donot_touch_note_cloud").html(cur_cloud_label);

		if(cur_cloud == 's3'){
			jQuery("#mess, #s3_seperate_bucket_note").toggle();
			if (check_cloud_min_php_min_req.indexOf('s3') == -1) {
				jQuery(".cloud_error_mesg").show();
				jQuery(".cloud_error_mesg").html('Amazon S3 requires PHP v5.3.3+. Please upgrade your PHP to use Amazon S3.');
				jQuery('#connect_to_cloud').hide();
				return false;
			}
			jQuery(".s3_inputs", this_par).show();
		}
		else if(cur_cloud == 'g_drive'){
			if (check_cloud_min_php_min_req.indexOf('gdrive') == -1) {
				jQuery(".cloud_error_mesg").show();
				jQuery(".cloud_error_mesg").html('Google Drive requires PHP v5.4.0+. Please upgrade your PHP to use Google Drive.');
				jQuery('#connect_to_cloud').hide();
				return false;
			}
			jQuery('#see_how_to_add_refresh_token_wptc, #gdrive_refresh_token_input_wptc, #google_token_add_btn, #google_limit_reached_text_wptc').show();
			if (jQuery('#google_token_add_btn').length) {
				jQuery("#connect_to_cloud, #save_g_drive_refresh_token").attr("cloud_type", cur_cloud);
				jQuery("#connect_to_cloud").val("Connect to " + cur_cloud_label).show();
			}
			jQuery(".g_drive_inputs", this_par).show();
		}
	});

	jQuery(".wcard").on('keypress', '#wptc_main_acc_email', function(e){
		wptc_trigger_login(e);
	});

	jQuery(".wcard").on('keypress', '#wptc_main_acc_pwd', function(e){
		wptc_trigger_login(e);
	});

	jQuery("#wptc_analyze_inc_exc_lists").click(function(e){
		e.stopImmediatePropagation();
		e.preventDefault();

		wptc_analyze_inc_exc_lists();

	});

	jQuery("#wptc_show_all_exc_files").click(function(e){
		e.stopImmediatePropagation();
		e.preventDefault();

		wptc_show_all_excluded_files();

	});

	// jQuery('body').on('click', '#wptc_exclude_all_suggested', function(e) {
	// 	wptc_exclude_all_suggested();
	// });

	// jQuery('body').on('click', '#wptc_save_all_edited_suggested', function(e) {
	// 	swal(
	// 	'<div class="wptc-swal-title">Success</div>',
	// 	'<div class="wptc-swal-msg">You custom settings saved!</div>',
	// 	'success'
	// 	);
	// });

});

function wptc_make_cloud_auth_req(auth_url_func, data, wptc_gdrive_token_btn, cloud_type){
	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: auth_url_func,
		credsData: data
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch (e){
			if (typeof wptc_gdrive_token_btn != 'undefined' && wptc_gdrive_token_btn) {
				jQuery('.cloud_error_mesg_g_drive_token').addClass('cloud_acc_connection_error').html(data).show();
				delete wptc_gdrive_token_btn;
			} else {
				jQuery('.cloud_error_mesg').addClass('cloud_acc_connection_error').html(data).show();
			}
			jQuery('#connect_to_cloud').removeClass('disabled').removeAttr("disabled").val('Connect to '+cloud_type);
			return false;
		}

		if (typeof data.error != 'undefined') {
			jQuery('#connect_to_cloud').removeClass('disabled').removeAttr("disabled").val('Connect to '+cloud_type);
			jQuery('.cloud_error_mesg').addClass('cloud_acc_connection_error').html(data.error).show();
			return false;
		}

		parent.location.assign(data.authorize_url);
	});
}

function enable_settings_button_wptc(){
	jQuery("#wptc_save_changes").removeAttr('disabled').removeClass('disabled').val("Save Changes").html("Save");
	jQuery('#exc_files_db_canc').css('color','#0073aa').unbind('click', false);
}

function save_settings_wptc(){
	var hash = window.location.hash;
	switch(hash){
		case '':
		case '#wp-time-capsule-tab-general':
		save_general_settings_wptc();
		break;
		case '#wp-time-capsule-tab-backup':
		save_backup_settings_wptc();
		break;
		case '#wp-time-capsule-tab-bbu':
		save_bbu_settings_wptc();
		break;
		case '#wp-time-capsule-tab-vulns':
		save_vulns_settings_wptc();
		break;
		case '#wp-time-capsule-tab-staging':
		save_staging_settings_wptc();
		break;
		default:
		jQuery("#wptc_save_changes").removeAttr('disabled').removeClass('disabled').val("Hash does not match !").html("Hash does not match !");
		enable_settings_button_wptc();
	}
}

function save_general_settings_wptc(){
	var anonymouse = jQuery('input[name=anonymous_datasent]:checked').val();
	save_settings_ajax_request_wptc('save_general_settings_wptc', {'anonymouse' : anonymouse});
}

function save_settings_ajax_request_wptc(action, data){
	jQuery.post(ajaxurl, {
			security: wptc_ajax_object.ajax_nonce,
			action: action,
			data : data,
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return ;
		}

		if (data == undefined) {
			wptc_sweet_alert('Oops...', 'Update setting failed, Please try again!', 'error');
		} else 	if (data.notice == undefined) {
			wptc_sweet_alert('Success', 'Settings updated successfully!', 'success');
		} else {
			swal({
				title: data.notice.title,
				html: data.notice.message,
				type: data.notice.type,
				confirmButtonText: "I understood",
				}).then(function () {
					wptc_sweet_alert('Success', 'Settings updated successfully!', 'success');
				}
			);
		}

		enable_settings_button_wptc();
	});
}

function save_backup_settings_wptc(){
	var backup_slot = '';
	if (jQuery("#select_wptc_backup_slots").hasClass('disabled') === false) {
		var backup_slot  = jQuery("#select_wptc_backup_slots").val();
	}

	var scheduled_time = '';
	if (jQuery("#select_wptc_default_schedule").hasClass('disabled') === false) {
		var scheduled_time  = jQuery("#select_wptc_default_schedule").val();
	}

	var timezone = '';
	if (jQuery("#wptc_timezone").hasClass('disabled') === false) {
		var timezone  = jQuery("#wptc_timezone").val();
	}

	var revision_limit = '';
	if (jQuery("#wptc_settings_revision_limit").hasClass('disabled') === false) {
		var revision_limit  = jQuery("#wptc_settings_revision_limit").val();
	}

	var user_excluded_extenstions  = jQuery("#user_excluded_extenstions").val();
	var user_excluded_files_more_than_size  = jQuery("#user_excluded_files_more_than_size").val();

	if (scheduled_time && timezone) {
			var request_params = {
				"backup_slot" : backup_slot,
				"scheduled_time": scheduled_time,
				"timezone" : timezone,
				"revision_limit" : revision_limit,
				"user_excluded_extenstions" : user_excluded_extenstions,
				"user_excluded_files_more_than_size" : user_excluded_files_more_than_size
			};
	} else {
		var request_params = {
			"revision_limit" : revision_limit,
			"user_excluded_extenstions" : user_excluded_extenstions,
			"user_excluded_files_more_than_size" : user_excluded_files_more_than_size
		};
	}
	save_settings_ajax_request_wptc('save_backup_settings_wptc', request_params);
}

function save_bbu_settings_wptc(){

	var backup_before_update_setting = jQuery('#backup_before_update_always').is(":checked");
	var backup_type = jQuery('#backup_type').val();
	var auto_update_wptc_setting = jQuery('input[name=auto_update_wptc_setting]:checked').val();
	var auto_updater_core_major = jQuery('input[name=wptc_auto_core_major]:checked').val();
	var auto_updater_core_minor = jQuery('input[name=wptc_auto_core_minor]:checked').val();
	var auto_updater_plugins = jQuery('input[name=wptc_auto_plugins]:checked').val();
	var auto_updater_plugins_included = jQuery('#auto_include_plugins_wptc').val();
	var auto_updater_themes = jQuery('input[name=wptc_auto_themes]:checked').val();
	var auto_updater_themes_included = jQuery('#auto_include_themes_wptc').val();

	var request_params = {
							"backup_before_update_setting" : backup_before_update_setting,
							"auto_update_wptc_setting" : auto_update_wptc_setting,
							"auto_updater_core_major" : (auto_updater_core_major) ? auto_updater_core_major : 0,
							"auto_updater_core_minor" : (auto_updater_core_minor) ? auto_updater_core_minor : 0,
							"auto_updater_plugins" : (auto_updater_plugins) ? auto_updater_plugins : 0,
							"auto_updater_plugins_included" : (auto_updater_plugins_included) ? auto_updater_plugins_included : '',
							"auto_updater_themes" : (auto_updater_themes) ? auto_updater_themes : 0,
							"auto_updater_themes_included" : (auto_updater_themes_included) ? auto_updater_themes_included : '',
						}
	save_settings_ajax_request_wptc('save_bbu_settings_wptc', request_params);
}

function save_staging_settings_wptc(){
	var db_rows_clone_limit_wptc =jQuery("#db_rows_clone_limit_wptc").val();
	var files_clone_limit_wptc =jQuery("#files_clone_limit_wptc").val();
	var deep_link_replace_limit_wptc =jQuery("#deep_link_replace_limit_wptc").val();
	var enable_admin_login_wptc =jQuery('input[name=enable_admin_login_wptc]:checked').val();

	var request_params = {  "db_rows_clone_limit_wptc": db_rows_clone_limit_wptc,
							"files_clone_limit_wptc" : files_clone_limit_wptc,
							"deep_link_replace_limit_wptc" : deep_link_replace_limit_wptc,
							"enable_admin_login_wptc" : enable_admin_login_wptc
						 };
	save_settings_ajax_request_wptc('save_staging_settings_wptc', request_params);
}

function save_vulns_settings_wptc(){
	var enable_vulns_email_wptc = jQuery('input[name=enable_vulns_email_wptc]:checked').val();
	var vulns_wptc_setting = jQuery('input[name=vulns_wptc_setting]:checked').val();
	var wptc_vulns_core = jQuery('input[name=wptc_vulns_core]:checked').val();
	var wptc_vulns_plugins = jQuery('input[name=wptc_vulns_plugins]:checked').val();
	var wptc_vulns_themes = jQuery('input[name=wptc_vulns_themes]:checked').val();
	var vulns_include_themes_wptc = jQuery('#vulns_include_themes_wptc').val();
	var vulns_include_plugins_wptc = jQuery('#vulns_include_plugins_wptc').val();

	var request_params = {
		"enable_vulns_email_wptc": enable_vulns_email_wptc,
		"vulns_wptc_setting": vulns_wptc_setting,
		"wptc_vulns_core": wptc_vulns_core,
		"wptc_vulns_plugins": wptc_vulns_plugins,
		"wptc_vulns_themes": wptc_vulns_themes,
		"vulns_themes_included": vulns_include_themes_wptc,
		"vulns_plugins_included": vulns_include_plugins_wptc,
	};

	save_settings_ajax_request_wptc('save_vulns_settings_wptc', request_params);
}

function wptc_sweet_alert(title, text, type){
	swal({
		title: title,
		html: text,
		type: type,
	});
}

function wptc_trigger_login(e) {
	if (!is_email_and_pwd_not_empty_wptc()) {
		return false;
	}
	var key = e.which;
	if (key == 13) {
		jQuery("#wptc_login").click();
		return false;
	}
}

function fancy_tree_init_exc_files_wptc(call_from_init){

	jQuery("#wptc_exc_files").fancytree({
		checkbox: false,
		selectMode: 3,
		clickFolderMode: 3,
		debugLevel:0,
		source: {
			url: ajaxurl,
			security: wptc_ajax_object.ajax_nonce,
			data: (call_from_init == undefined) ? {
				"action": "wptc_get_root_files",
				security: wptc_ajax_object.ajax_nonce,
			} : {
				"action": "wptc_get_init_root_files",
				security: wptc_ajax_object.ajax_nonce,
			},
		},
		postProcess: function(event, data) {
			data.result = data.response;
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
				if (node.data.partial) node.addClass('fancytree-partsel');
			});
		},
		lazyLoad: function(event, ctx) {
			var key = ctx.node.key;
			ctx.result = {
				url: ajaxurl,
				security: wptc_ajax_object.ajax_nonce,
				data: (call_from_init == undefined) ? {
					"action": "wptc_get_files_by_key",
					"key" : key,
					security: wptc_ajax_object.ajax_nonce,
				} : {
					"action": "wptc_get_init_files_by_key",
					"key" : key,
					security: wptc_ajax_object.ajax_nonce,
				},
			};
		},
		renderNode: function(event, data){ // called for every toggle
			if (!data.node.getChildren())
				return false;
			if(data.node.expanded === false){
				data.node.resetLazy();
			}
			jQuery.each( data.node.getChildren(), function( key, value ) {
				if (value.data.preselected){
					value.setSelected(true);
				} else {
					value.setSelected(false);
				}
			});
		},
		loadChildren: function(event, data) {
			data.node.fixSelection3AfterClick();
			data.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
			// data.node.toggleSelected();
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		mouse_enter_files_wptc(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		mouse_leave_files_wptc(event);
	}).on("click", '.fancytree-file-exclude-key' ,function(event){
		mouse_click_files_exclude_key_wptc(event);
	}).on("click", '.fancytree-file-include-key' ,function(event){
		mouse_click_files_include_key_wptc(event);
	});

	return false;
}

function fancy_tree_init_exc_tables_wptc(call_from_init){

	if (call_from_init) {
		jQuery('#wptc_init_table_div').css('position', 'absolute');
	}
	jQuery("#wptc_exc_db_files").fancytree({
		checkbox: false,
		selectMode: 2,
		icon:false,
		debugLevel:0,
		// clickFolderMode: 3,
		source: {
			url: ajaxurl,
			data: (call_from_init == undefined) ? {
				"action": "wptc_get_tables",
				security: wptc_ajax_object.ajax_nonce,
			} : {
				"action": "wptc_get_init_tables",
				security: wptc_ajax_object.ajax_nonce,
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected){
					node.setSelected(true);
					if (node.data.content_excluded && node.data.content_excluded == 1) {
						node.addClass('fancytree-partial-selected');
					}
				}
			});
		},
		loadChildren: function(event, ctx) {
			// ctx.node.fixSelection3AfterClick();
			// ctx.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		mouse_enter_tables_wptc(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		mouse_leave_tables_wptc(event);
	}).on("click", '.fancytree-table-exclude-key' ,function(event){
		mouse_click_table_exclude_key_wptc(event);
	}).on("click", '.fancytree-table-include-key' ,function(event){
		mouse_click_table_include_key_wptc(event);
	}).on("click", '.fancytree-table-exclude-content' ,function(event){
		mouse_click_table_exclude_content_wptc(event);
	});

}

function save_inc_exc_data_wptc(request, file, isdir){
	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: request,
		data: {file : file, isdir : isdir},
	}, function(data) {
	});
}

function wptc_analyze_inc_exc_lists(is_continue){

	if (!is_continue) {
		wptc_cache_lists_of_files = [];
		swal({
			title: 'Analyzing ...',
			text: 'Do not close the window, it will take few mins',
			onOpen: function () {
				swal.showLoading()
			}
		});
	}

	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: 'analyze_inc_exc_lists_wptc',
	}, function(response) {
		response = jQuery.parseJSON(response)

		if (response.status == 'continue') {
			wptc_combine_cache_lists_of_files(response.files);
			wptc_analyze_inc_exc_lists('continue');
			return ;
		}

		swal({
			title: '<div class="wptc-swal-title"> Optimize MySQL backups</div>',
			// html: ' <div>Please exclude table or Exclude contents of these large tables </div> <div> <div style="width: 408px;text-align: left;float: left;padding-top: 40px;" id="wptc-suggested-exclude-files"></div> <div style="width: 408px;text-align: left;float: right;padding-top: 40px;" id="wptc-suggested-exclude-tables"></div></div><div style="position: relative;top: 0px;padding: 30px 0px 0px 0px;float: left;margin-left: 285px;"><button class="swal2-styled wptc-swal-confirm" id="wptc_save_all_edited_suggested">Save changes</button><button class="swal2-styled wptc-swal-confirm" id="wptc_exclude_all_suggested">Exlude all these</button></div>',
			html: ' <div style="font-size: 16px;" > Please make changes to the exclusion by moving your mouse near the table name. </div> <br> <div id="wptc-suggested-exclude-files"></div> <div style="text-align: left; " id="wptc-suggested-exclude-tables"></div>',
			// width: '50%',
			showCancelButton: false,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#3085d6',
			confirmButtonText: 'Save',
			// cancelButtonText: 'Save the changes only',
		}).then(function () {
				wptc_exclude_all_suggested();
			}, function (dismiss) {
			// dismiss can be 'cancel', 'overlay',
			// 'close', and 'timer'
			if (dismiss === 'cancel') {
				swal(
				'<div class="wptc-swal-title">Success</div>',
				'<div class="wptc-swal-msg">You custom changes are saved!</div>',
				'success'
				);
			}
		});

		if (Object.keys(response).length == 0) {
			swal({
				title: '<div style="font-size: 27px;">Your database has been analyzed</div>',
				html: '<div class="wptc-swal-msg">Everything looks good!</div>',
			})
			return ;
		}

		if ( ( !response.tables || !response.tables.length ) && ( !response.files || !response.files.length ) ) {
			swal({
				title: '<div style="font-size: 27px;">Your database has been analyzed</div>',
				html: '<div class="wptc-swal-msg">Everything looks good!</div>',
			})

			return ;
		}

		if (!response.tables || !response.tables.length ) {
			jQuery("#wptc-suggested-exclude-tables").html('All tables are good !');
		} else {
			add_suggested_tables_lists_wptc(response.tables);
		}

		// if (!response.files || !response.files.length) {
		// 	jQuery("#wptc-suggested-exclude-files").html('All files are good !');
		// } else {
		// 	add_suggested_files_lists_wptc(response.files);
		// }

	});
}

function wptc_combine_cache_lists_of_files(new_list){
	if (typeof wptc_cache_lists_of_files == 'undefined' || wptc_cache_lists_of_files.length === 0) {
		wptc_cache_lists_of_files = new_list;
	} else {
		wptc_cache_lists_of_files = wptc_cache_lists_of_files.concat(new_list);
	}
}

function add_suggested_files_lists_wptc(source_data){
	wptc_combine_cache_lists_of_files(source_data);

	jQuery("#wptc-suggested-exclude-files").fancytree({
		checkbox: false,
		selectMode: 3,
		clickFolderMode: 3,
		debugLevel:0,
		source: wptc_cache_lists_of_files,
		postProcess: function(event, data) {
			data.result = data.response;
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
				if (node.data.partial) node.addClass('fancytree-partsel');
			});
		},
		renderNode: function(event, data){ // called for every toggle
			if (!data.node.getChildren())
				return false;
			if(data.node.expanded === false){
				data.node.resetLazy();
			}
			jQuery.each( data.node.getChildren(), function( key, value ) {
				if (value.data.preselected){
					value.setSelected(true);
				} else {
					value.setSelected(false);
				}
			});
		},
		loadChildren: function(event, data) {
			data.node.fixSelection3AfterClick();
			data.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
			// data.node.toggleSelected();
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		mouse_enter_files_wptc(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		mouse_leave_files_wptc(event);
	}).on("click", '.fancytree-file-exclude-key' ,function(event){
		mouse_click_files_exclude_key_wptc(event);
	}).on("click", '.fancytree-file-include-key' ,function(event){
		mouse_click_files_include_key_wptc(event);
	});
}

function mouse_enter_files_wptc(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (	node &&
			typeof node.span != 'undefined'
			&& (!node.getParentList().length
					|| node.getParent().selected !== false
					|| node.getParent().partsel !== false
					|| (node.getParent()
						&& node.getParent()[0]
						&& node.getParent()[0].extraClasses
						&& node.getParent()[0].extraClasses.indexOf("fancytree-selected") !== false )
					|| (node.getParent()
						&& node.getParent()[0]
						&&node.getParent()[0].extraClasses
						&& node.getParent()[0].extraClasses.indexOf("fancytree-partsel") !== false )
						 )
			) {
		jQuery(node.span).addClass('fancytree-background-color');
		jQuery(node.span).find('.fancytree-size-key').hide();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		if(node.selected){
			jQuery(node.span).append("<span role='button' class='fancytree-file-exclude-key'><a>Exclude</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-file-include-key'><a>Include</a></span>");
		}
	}
}

function mouse_leave_files_wptc(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function mouse_click_files_exclude_key_wptc(event){
	var node = jQuery.ui.fancytree.getNode(event);
	var children = node.getChildren();
	jQuery.each(children, function( index, value ) {
		value.selected = false;
		value.setSelected(false);
		value.removeClass('fancytree-partsel fancytree-selected')
	});
	folder = (node.folder) ? 1 : 0;
	node.removeClass('fancytree-partsel fancytree-selected');
	node.selected = false;
	node.partsel = false;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	save_inc_exc_data_wptc('exclude_file_list_wptc', node.key, folder);
}

function mouse_click_files_include_key_wptc(event){
	var node = jQuery.ui.fancytree.getNode(event);
	var children = node.getChildren();
	jQuery.each(children, function( index, value ) {
		value.selected = true;
		value.setSelected(true);
		value.addClass('fancytree-selected')
	});
	folder = (node.folder) ? 1 : 0;
	node.addClass('fancytree-selected');
	node.selected = true;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	save_inc_exc_data_wptc('include_file_list_wptc', node.key, folder);
}


function add_suggested_tables_lists_wptc(source_data){

	jQuery("#wptc-suggested-exclude-tables").fancytree({
		checkbox: false,
		selectMode: 1,
		icon:false,
		debugLevel:0,
		source: source_data,
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected){
					node.setSelected(true);
					node.selected = true;
					node.addClass('fancytree-selected ');
					if (node.data.content_excluded && node.data.content_excluded == 1) {
						node.addClass('fancytree-partial-selected ');
					}
				}
			});
		},
		loadChildren: function(event, ctx) {
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		mouse_enter_tables_wptc(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		mouse_leave_tables_wptc(event);
	}).on("click", '.fancytree-table-exclude-key' ,function(event){
		mouse_click_table_exclude_key_wptc(event);
	}).on("click", '.fancytree-table-include-key' ,function(event){
		mouse_click_table_include_key_wptc(event);
	}).on("click", '.fancytree-table-exclude-content' ,function(event){
		mouse_click_table_exclude_content_wptc(event);
	});

}

function mouse_enter_tables_wptc(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	jQuery(node.span).addClass('fancytree-background-color');
	jQuery(node.span).find('.fancytree-size-key').hide();
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	if(node.selected || (node.extraClasses  && node.extraClasses.indexOf('fancytree-selected')!== -1 ) ){
		if (!node.extraClasses || node.extraClasses.indexOf('fancytree-partial-selected') === -1) {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key' style='margin-left: 10px;position: absolute;right: 120px;'><a>Exclude Table</a></span>");
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-content' style='position: absolute;right: 4px;'><a>Exclude Content</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key'><a>Exclude Table</a></span>");
		}
	} else {
		jQuery(node.span).append("<span role='button' class='fancytree-table-include-key'><a>Include Table</a></span>");
	}
}

function mouse_leave_tables_wptc(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function mouse_click_table_exclude_key_wptc(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.removeClass('fancytree-partsel fancytree-selected fancytree-partial-selected');
	node.partsel = node.selected = false;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wptc('exclude_table_list_wptc', node.key, false);
}

function mouse_click_table_include_key_wptc(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.removeClass('fancytree-partial-selected');
	node.addClass('fancytree-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wptc('include_table_list_wptc', node.key, false);
}

function mouse_click_table_exclude_content_wptc(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.addClass('fancytree-partial-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wptc('include_table_structure_only_wptc', node.key, false);
}

function wptc_exclude_all_suggested(){
	var files 	= wptc_get_exclude_all_suggested_items('files');
	var tables 	= wptc_get_exclude_all_suggested_items('tables');

	swal({
		title: 'processing ...',
		text: 'Excluding contents for all those tables...',
		onOpen: function () {
			swal.showLoading()
		}
	});

	jQuery.post(ajaxurl, {
			security: wptc_ajax_object.ajax_nonce,
			action: 'exclude_all_suggested_items_wptc',
			data : {
				files: files,
				tables: tables,
			},
	}, function(response) {
		console.log(response);
		response = jQuery.parseJSON(response)
		if (response.status !== 'success') {
			swal(
				'<div class="wptc-swal-title">Something went wrong!</div>',
				'<div class="wptc-swal-msg">Cannot save the list! please try again!</div>',
				'error'
				);

			return ;
		}

		swal(
			'<div class="wptc-swal-title">Success</div>',
			'<div class="wptc-swal-msg">Changes saved</div>',
			'success'
			);
	});
}

function wptc_get_exclude_all_suggested_items(type){
	var id = '';
	if (type === 'files') {
		id = '#wptc-suggested-exclude-files';
	} else {
		id = '#wptc-suggested-exclude-tables';
	}
	if(!jQuery(id + " ul").hasClass('ui-fancytree') || jQuery(id).fancytree('getTree').rootNode === undefined){
		return false;
	}

	var childrens = jQuery(id).fancytree('getTree').getRootNode().children;

	var items = [];
	jQuery(childrens).each(function(index, value){
		items.push(value.key);
	})

	return items;
}

function wptc_show_all_excluded_files(){
	wptc_cache_lists_of_files = [];
	swal({
		title: 'Analyzing ...',
		text: 'Do not close the window, it will take few mins',
		onOpen: function () {
			swal.showLoading()
		}
	});

	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: 'get_all_excluded_files_wptc',
	}, function(response) {
		response = jQuery.parseJSON(response);
		if (!response.files || !response.files.length) {
			swal({
				text: 'No files excluded on this site',
			});
			return ;
		}

		swal({
			title: '<div class="wptc-swal-title">Excluded files</div>',
			html: '<div style="text-align: left;float: left; width:100%" id="wptc-suggested-exclude-files"></div>',
			showConfirmButton: false,
		});

		add_suggested_files_lists_wptc(response.files);

	});
}