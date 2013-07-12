jQuery(document).ready(function($) {
	$(document).on('change', 'input#mailchimp-api-key', function(event) {
		var data = {
			action: 'it_exchange_update_mailchimp_lists',
			api_key: $( this ).val()
		};

		$.post(ajaxurl, data, function(response) {
			$( '#mailchimp-list' ).replaceWith( response );
		});
	});
});
