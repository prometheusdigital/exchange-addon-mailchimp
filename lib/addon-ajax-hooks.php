<?php
/**
 * iThemes Exchange MailChimp Add-on
 * @package IT_Exchange_Addon_MailChimp
 * @since 1.0.0
*/

/**
 * AJAX to update MailChimp list dropdown automatically when changing
 * the MailChimp API key
 *
 * @since 1.0.0
 *
 * @return die() string HTML drop down
*/
function it_exchange_update_mailchimp_lists_ajax() {
	
	$lists = array();
	
	if ( ! empty( $_POST['api_key'] ) )
		$lists = it_exchange_get_mailchimp_lists( $_POST['api_key'] );

	$form = new ITForm( array(), array( 'prefix' => 'it-exchange-add-on-mailchimp' ) );
	die( $form->get_drop_down( 'mailchimp-list', $lists ) );
	
}
add_action('wp_ajax_it_exchange_update_mailchimp_lists', 'it_exchange_update_mailchimp_lists_ajax');