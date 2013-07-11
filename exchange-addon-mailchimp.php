<?php
/*
 * Plugin Name: iThemes Exchange - Mail Chimp Add-on
 * Version: 1.0.0
 * Description: Adds the Mail Chimp addon to iThemes Exchange
 * Plugin URI: http://ithemes.com/exchange/mailchimp/
 * Author: iThemes
 * Author URI: http://ithemes.com
 * iThemes Package: exchange-addon-mailchimp
 
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
*/

/**
 * This registers our plugin as a membership addon
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_mail_chimp_addon() {
	$options = array(
		'name'              => __( 'Mail Chimp', 'LION' ),
		'description'       => __( 'Add Mail Chimp Opt-In Checkbox to user registration form.', 'LION' ),
		'author'            => 'iThemes',
		'author_url'        => 'http://ithemes.com/exchange/mailchimp/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/images/mailchimp50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'basename'          => plugin_basename( __FILE__ ),
		'category'          => 'email',
		'settings-callback' => 'it_exchange_mail_chimp_settings_callback',
	);
	it_exchange_register_addon( 'mail-chimp', $options );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_mail_chimp_addon' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
/*
function ithemes_exchange_addon_stripe_updater_register( $updater ) { 
	    $updater->register( 'exchange-addon-membership', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_membership_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );
*/