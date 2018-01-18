/* JavaScript for Admin section (loaded on all admin pages) */
jQuery(document).ready(function($) {
	var $messages = $('.wc_aelia.message');

	$messages.find('.message_action').on('click', function(e) {
		e.stopPropagation();

		var $action = $(this);
		var action_url = $action.attr('href');
		var message_id = $action.attr('message_id');

		// Hide the message in the UI, while the Ajax call is processed. This will
		// give an immediate feedback to the user
		$action.parents('#' + message_id).fadeOut('slow');

		$.ajax({
			type: 'POST',
			url: action_url,
			dataType: 'json',
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				console.log(XMLHttpRequest.responseText);
				console.log(textStatus);
				console.log(errorThrown);
			},
			success: function(json) {
				console.log(json);
			},
			complete: function(XMLHttpRequest, textStatus) {
				console.log('complete');
			}
		});
		return false;
	});
});
