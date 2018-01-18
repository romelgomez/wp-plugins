jQuery(document).ready(function($) {

	jQuery('body').on('click', '.restore_to_staging_wptc', function (){
		if (jQuery(this).hasClass('disabled')) {
			return ;
		}
		restore_obj = this;

		jQuery('#TB_overlay, #TB_ajaxContent').hide();
		swal({
			title: "<div class='wptc_alert_title'> Are you sure? </div>",
			html: "<div class='wptc_alert_notes'> This will erase your entire staging site and do fresh staging then initiate the restore !</div>",
			type: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Yes, do it!',
			cancelButtonText: 'No, cancel!',
		}).then(function () {
			dialog_close_wptc();
			// jQuery('#TB_overlay, #TB_ajaxContent').show();
			backupclickProgress = false;
			swal(
				"<div class='wptc_alert_title'> Process started !</div>",
				"<div class='wptc_alert_notes'> During the restore process on your staging site, there will be multiple page redirects. Don't close the window during this process and kindly wait till it completes.</div>",
				'success'
			);

			setTimeout(function(){
				wptc_init_restore_to_staging();
			}, 2000);

		}, function (dismiss) {
			// dismiss can be 'cancel', 'overlay',
			// 'close', and 'timer'
			jQuery('#TB_overlay, #TB_ajaxContent').show();
			revert_confirmation_backup_popups();
			backupclickProgress = false;
			// if (dismiss === 'cancel') {
			// }
		})
	});

});

function wptc_init_restore_to_staging(){

	//as of now support only restore to point to staging
	var cur_res_b_id = jQuery(restore_obj).closest(".single_group_backup_content").attr("this_backup_id");
	var type = 'restore_to_point';

	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: 'init_restore_to_staging_wptc',
		type: type,
		selected_folder: false,
		is_first_call: true,
		cur_res_b_id: cur_res_b_id,
	}, function(data) {
		var data = jQuery.parseJSON(data);

		console.log('start_restore_in_staging_wptc_stored', data);
		if (data.status === 'success') {
			wptc_R2S_redirect_to_staging = true;
			copy_staging_wptc();
		} else if(data.status === 'error'){
			swal(
				"<div class='wptc_alert_title'> Oops !</div>",
				"<div class='wptc_alert_notes'> " + data.msg + ".</div>",
				'error'
			);
		} else {
			swal(
				"<div class='wptc_alert_title'> Something went wrong !</div>",
				"<div class='wptc_alert_notes'> Please try again !.</div>",
				'error'
			);
		}
	});
}

function wptc_redirect_to_staging_page(){
	if (typeof wptc_R2S_redirect_to_staging != 'undefined' && wptc_R2S_redirect_to_staging === true) {
		if (window.location.href.indexOf('wp-time-capsule-staging-options') !== -1) {
			return ;
		}

		delete wptc_R2S_redirect_to_staging;

		parent.location.assign(wptc_ajax_object.admin_url + 'admin.php?page=wp-time-capsule-staging-options');
	}
}