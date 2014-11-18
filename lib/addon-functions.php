<?php
/**
 * iThemes Exchange MailChimp Add-on
 * @package IT_Exchange_Addon_MailChimp
 * @since 1.0.0
*/

/**
 * Get's existing MailChimp email lists
 *
 * @since 1.0.0
 *
 * @param string $api_key MailChimp API key
 * @return array $lists Sorted lists from MailChimp (if found)
*/
function it_exchange_get_mailchimp_lists( $api_key ) {
	$lists = array();

	if( !empty($api_key ) ) {
		
		$mc = new Mailchimp( trim( $api_key ) );
		$mc_lists = $mc->lists->getList();
		
		if( $mc_lists ) {
			foreach( $mc_lists['data'] as $key => $list ) {
				$lists[$list['id']] = $list['name'];
			}
		}
	}
	
	return $lists;	
}