jQuery(document).ready(function($) {
	$(document).on('change', 'input#mail-chimp-api-key', function(event) {
		var data = {
			action: 'it_exchange_update_mail_chimp_lists',
			api_key: $( this ).val()
		};

		$.post(ajaxurl, data, function(response) {
			$( '#mail-chimp-list' ).replaceWith( response );
		});
	});
});
