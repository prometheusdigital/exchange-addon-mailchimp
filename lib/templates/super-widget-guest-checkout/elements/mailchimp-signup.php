<?php
/**
 * This is the MailChimp signup template part for the save field in the super-widget-guest-checkout template part
 * @since CHANGEME
 * @version 1.0.0
 * @package IT_Exchange_Addon_MailChimp
*/
?>
<?php do_action( 'it_exchange_super_widget_guest_checkout_fields_before_mailchimp_signup' );

$settings = it_exchange_get_option( 'addon_mailchimp' );

if (  ! empty( $settings['mailchimp-api-key'] ) 
    && ( empty( $options['format'] ) || 'html' === $options['format'] )
    && !empty( $settings['mailchimp-optin'] ) ) {
    
    echo '<div class="mailchimp-signup"><label for="it-exchange-mailchimp-signup"><input type="checkbox" id="it-exchange-mailchimp-signup" name="it-exchange-mailchimp-signup" /> ' . $settings['mailchimp-label'] . '</label></div>';
    
}
do_action( 'it_exchange_super_widget_guest_checkout_fields_after_mailchimp_signup' ); ?>