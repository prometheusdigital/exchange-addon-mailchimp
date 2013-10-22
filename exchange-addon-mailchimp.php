<?php
/*
 * Plugin Name: iThemes Exchange - MailChimp Add-on
 * Version: 1.0.6
 * Description: Adds the MailChimp addon to iThemes Exchange
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
function it_exchange_register_mailchimp_addon() {
	$versions         = get_option( 'it-exchange-versions', false );
	$current_version  = empty( $versions['current'] ) ? false: $versions['current'];
	
	if ( true || version_compare( $current_version, '1.0.3', '>' ) ) {
		
		$options = array(
			'name'              => __( 'MailChimp', 'LION' ),
			'description'       => __( 'Add MailChimp Opt-In Checkbox to user registration form.', 'LION' ),
			'author'            => 'iThemes',
			'author_url'        => 'http://ithemes.com/exchange/mailchimp/',
			'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/mailchimp50px.png' ),
			'file'              => dirname( __FILE__ ) . '/init.php',
			'category'          => 'email',
			'settings-callback' => 'it_exchange_mailchimp_settings_callback',
		);
		it_exchange_register_addon( 'mailchimp', $options );
	
	} else {
			
		add_action( 'admin_notices', 'it_exchange_add_mailchimp_nag' );
		
	}
	
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_mailchimp_addon' );

/**
 * Adds the MailChimp nag if not on the correct version of iThemes Exchange
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_add_mailchimp_nag() {
	?>
	<div id="it-exchange-mailchimp-nag" class="it-exchange-nag">
		<?php
		printf( __( 'To use the MailChimp add-on for iThemes Exchange, you must be using iThemes Exchange version 1.0.3 or higher. <a href="%s">Please update now</a>.', 'LION' ), admin_url( 'update-core.php' ) );
		?>
	</div>
    <?php
}

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_mailchimp_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'it_exchange_mailchimp_set_textdomain' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function ithemes_exchange_addon_mailchimp_updater_register( $updater ) { 
	    $updater->register( 'exchange-addon-mailchimp', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_mailchimp_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );