<?php
/**
 * iThemes Exchange MailChimp Add-on
 *
 * @package IT_Exchange_Addon_MailChimp
 * @since   1.0.0
 */

/**
 * Get's existing MailChimp email lists
 *
 * @since 1.0.0
 *
 * @param string $api_key MailChimp API key
 *
 * @return array $lists Sorted lists from MailChimp (if found)
 */
function it_exchange_get_mailchimp_lists( $api_key = '' ) {
	$lists = array();

	$mc_lists = it_exchange_mailchimp_api_request( 'lists', 'GET', array( 'count' => 100 ), null, array( 'api_key' => $api_key ) );

	if ( is_wp_error( $mc_lists ) || is_null( $mc_lists ) || empty( $mc_lists['lists'] ) ) {
		return $lists;
	}

	foreach ( $mc_lists['lists'] as $list ) {
		$lists[ $list['id'] ] = $list['name'];
	}

	return $lists;
}

/**
 * Subscribe a user to a MailChimp list.
 *
 * @since 2.1.0
 *
 * @param string $email Email address to sign up.
 * @param string $list  List to sign up to. If empty, global list is used.
 * @param array  $merge Merge fields to be sent to MailChimp. For example: 'FNAME' => 'John', 'LNAME' => 'Doe'
 * @param bool   $double_optin Whether to perform double opt-in.
 *
 * @return array|null|WP_Error
 */
function it_exchange_mailchimp_subscribe_email_to_list( $email, $list = '', $merge = array(), $double_optin = null ) {

	$settings     = it_exchange_get_option( 'addon_mailchimp' );
	$list_id      = $list ? $list : $settings['mailchimp-list'];

	if ( ! is_bool( $double_optin ) ) {
		$double_optin = empty( $settings['mailchimp-double-optin'] ) ? false : true;
	}

	$status = $double_optin ? 'pending' : 'subscribed';

	return it_exchange_mailchimp_api_request(
		"lists/{$list_id}/members",
		'POST',
		array(),
		array(
			'email_address' => $email,
			'email_type'    => 'html',
			'status'        => $status,
			'merge_fields'  => $merge,
		)
	);
}

/**
 * Perform an API request to MailChimp.
 *
 * @since 2.2.0
 *
 * @param string $route      The route to visit. For example /lists/319fejd01/
 * @param string $method     The HTTP method to use. Defaults to GET.
 * @param array  $query_args Any query args that should be included in the request.
 * @param null   $data       The request body. Used for POST or PATCH or PUT requests.
 * @param array  $options
 *
 * @return array|WP_Error|null Returns WP_Error if error occurs, an array form of the JSON response if success, or null
 *                             if no response body ( DELETE requests ).
 */
function it_exchange_mailchimp_api_request( $route, $method = 'GET', $query_args = array(), $data = null, $options = array() ) {

	if ( empty( $options['api_key'] ) ) {
		$settings = it_exchange_get_option( 'addon_mailchimp' );
		$api_key  = $settings['mailchimp-api-key'];
	} else {
		$api_key = $options['api_key'];
	}

	$api_key = trim( $api_key );

	if ( ! $api_key || ! ( $parts = explode( '-', $api_key ) ) || count( $parts ) !== 2 ) {
		return new WP_Error( 'it_exchange_mc_invalid_api_key', __( 'Invalid API key.', 'LION' ) );
	}

	$api_key = $parts[0];
	$dc      = $parts[1];

	$url = "https://{$dc}.api.mailchimp.com/3.0/{$route}";

	if ( $query_args ) {
		$url = add_query_arg( $query_args, $url );
	}

	unset( $options['api_key'] );

	$response = wp_safe_remote_request( $url, ITUtility::merge_defaults( $options, array(
		'method'  => $method,
		'body'    => $data ? json_encode( $data ) : null,
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( 'notany:' . $api_key )
		)
	) ) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$response_body = $response_body ? json_decode( $response_body, true ) : null;

	if ( substr( $response_code, 0, 1 ) != 2 ) {
		if ( ! $response_body ) {
			return new WP_Error(
				'it_exchange_rest_mc_unknown_error',
				__( 'An unknown error occurred.', 'LION' ),
				array( 'status' => $response_code )
			);
		}

		return new WP_Error(
			$response_body['type'],
			$response_body['detail'],
			array( 'status' => $response_code )
		);
	}

	return $response_body;
}