<?php
/**
 * ExchangeWP MailChimp Add-on
 * @package IT_Exchange_Addon_MailChimp
 * @since 1.0.0
*/

/**
 * Call back for settings page
 *
 * This is set in options array when registering the add-on and called from it_exchange_enable_addon()
 *
 * @since 0.3.6
 * @return void
*/
function it_exchange_mailchimp_settings_callback() {
	$IT_Exchange_MailChimp_Add_On = new IT_Exchange_MailChimp_Add_On();
	$IT_Exchange_MailChimp_Add_On->print_settings_page();
}

class IT_Exchange_MailChimp_Add_On {

	/**
	 * @var boolean $_is_admin true or false
	 * @since 0.3.6
	*/
	var $_is_admin;

	/**
	 * @var string $_current_page Current $_GET['page'] value
	 * @since 0.3.6
	*/
	var $_current_page;

	/**
	 * @var string $_current_add_on Current $_GET['add-on-settings'] value
	 * @since 0.3.6
	*/
	var $_current_add_on;

	/**
	 * @var string $status_message will be displayed if not empty
	 * @since 0.3.6
	*/
	var $status_message;

	/**
	 * @var string $error_message will be displayed if not empty
	 * @since 0.3.6
	*/
	var $error_message;

	/**
 	 * Class constructor
	 *
	 * Sets up the class.
	 * @since 0.3.6
	 * @return void
	*/
	function __construct() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'mailchimp' == $this->_current_add_on ) {
			add_action( 'it_exchange_save_add_on_settings_mailchimp', array( $this, 'save_settings' ) );
			do_action( 'it_exchange_save_add_on_settings_mailchimp' );
		}

		add_filter( 'it_storage_get_defaults_exchange_addon_mailchimp', array( $this, 'set_default_settings' ) );

		// Creates our option in the database
		add_action( 'admin_init', array( $this, 'exchange_mailchimp_plugin_updater', 0 ) );
		add_action( 'admin_init', array( $this, 'exchange_mailchimp_register_option' ) );
		add_action( 'admin_notices', array( $this, 'exchange_mailchimp_admin_notices' ) );
		add_action( 'admin_init', array( $this, 'exchange_mailchimp_deactivate_license' ) );
		add_action( 'admin_init', array( $this, 'exchange_mailchimp_deactivate_license' ) );
		add_action( 'admin_init', array( $this, 'exchange_mailchimp_activate_license' ) );

		$this->includes();
	}

	/**
	* Include the updater class
	*
	* @access  private
	* @return  void
	*/
	private function includes() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) )  {
			require_once 'EDD_SL_Plugin_Updater.php';
		}
	}

	/**
 	 * Deprecated Class constructor
	 *
	 * Sets up the class.
	 * @since 0.3.6
	 * @return void
	*/
	function IT_Exchange_MailChimp_Add_On() {
		self::__construct();
	}

	function print_settings_page() {
		$settings = it_exchange_get_option( 'addon_mailchimp', true );

		$form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_mailchimp', 'it-exchange-add-on-mailchimp-settings' ),
			'enctype' => apply_filters( 'it_exchange_add_on_mailchimp_settings_form_enctype', false ),
			'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=mailchimp',
		);
		$form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-mailchimp' ) );

		if ( ! empty ( $this->status_message ) )
			ITUtility::show_status_message( $this->status_message );
		if ( ! empty( $this->error_message ) )
			ITUtility::show_error_message( $this->error_message );

		?>
		<div class="wrap">
			<?php screen_icon( 'it-exchange' ); ?>
			<h2><?php _e( 'MailChimp Settings', 'LION' ); ?></h2>

			<?php do_action( 'it_exchange_mailchimp_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

			<?php $form->start_form( $form_options, 'it-exchange-mailchimp-settings' ); ?>
				<?php do_action( 'it_exchange_mailchimp_settings_form_top' ); ?>
				<?php $this->get_mailchimp_payment_form_table( $form, $form_values ); ?>
				<?php do_action( 'it_exchange_mailchimp_settings_form_bottom' ); ?>
				<p class="submit">
					<?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
				</p>
			<?php $form->end_form(); ?>
			<?php do_action( 'it_exchange_mailchimp_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
		<?php
	}

	function get_mailchimp_payment_form_table( $form, $settings = array() ) {
		//$default_status_options = it_exchange_mailchimp_get_default_status_options();
		$mailchimp_lists = it_exchange_get_mailchimp_lists( $settings['mailchimp-api-key'] );

		if ( !empty( $settings ) )
			foreach ( $settings as $key => $var )
				$form->set_option( $key, $var );
		?>

        <div class="it-exchange-addon-settings it-exchange-mailchimp-addon-settings">
            <p><?php _e( 'MailChimp allows store owners to manage and email lists of their currently subscribed customers.', 'LION' ); ?></p>
            <p><?php _e( 'Video:', 'LION' ); ?>&nbsp;<a href="http://ithemes.com/tutorials/exchange-add-ons-mailchimp/" target="_blank"><?php _e( 'Setting Up MailChimp in Exchange', 'LION' ); ?></a></p>
            <p><?php _e( 'To setup MailChimp in Exchange, complete the settings below.', 'LION' ); ?></p>

						<h4>License Key</h4>
						<?php
						   $exchangewp_mailchimp_options = get_option( 'it-storage-exchange_addon_mailchimp' );
						   $license = $exchangewp_mailchimp_options['mailchimp_license'];
						   // var_dump($license);
						   $exstatus = trim( get_option( 'exchange_mailchimp_license_status' ) );
						   // var_dump($exstatus);
						?>
						<p>
						 <label class="description" for="exchange_mailchimp_license_key"><?php _e('Enter your license key'); ?></label>
						 <!-- <input id="mailchimp_license" name="it-exchange-add-on-mailchimp-mailchimp_license" type="text" value="<?php #esc_attr_e( $license ); ?>" /> -->
						 <?php $form->add_text_box( 'mailchimp_license' ); ?>
						 <span>
						   <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
									<span style="color:green;"><?php _e('active'); ?></span>
									<?php wp_nonce_field( 'exchange_mailchimp_nonce', 'exchange_mailchimp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="exchange_mailchimp_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
								<?php } else {
									wp_nonce_field( 'exchange_mailchimp_nonce', 'exchange_mailchimp_nonce' ); ?>
									<input type="submit" class="button-secondary" name="exchange_mailchimp_license_activate" value="<?php _e('Activate License'); ?>"/>
								<?php } ?>
						 </span>
						</p>

						<h4><label for="mailchimp-api-key"><?php _e( 'MailChimp API Key', 'LION' ) ?> <span class="tip" title="<?php _e( 'Enter your MailChimp API Key from your MailChimp dashboard, under Account Settings -> Extras -> API Keys', 'LION' ); ?>">i</span></label></h4>
						<p> <?php $form->add_text_box( 'mailchimp-api-key' ); ?> </p>
						<h4><label for="mailchimp-list"><?php _e( 'MailChimp List', 'LION' ) ?> <span class="tip" title="<?php _e( 'This is the list you want to use from MailChimp (only appears after saving your MailChimp API key).', 'LION' ); ?>">i</span></label></h4>
						<p> <?php $form->add_drop_down( 'mailchimp-list', $mailchimp_lists ); ?> </p>
						<h4><label for="mailchimp-label"><?php _e( 'Sign-up Label', 'LION' ) ?> <span class="tip" title="<?php _e( 'This will be the label displayed next to the sign-up option on the registration page.', 'LION' ); ?>">i</span></label></h4>
			            <p> <?php $form->add_text_box( 'mailchimp-label' ); ?> </p>
						<h4><label for="mailchimp-optin"><?php _e( 'Enable Opt-in.', 'LION' ) ?> <span class="tip" title="<?php _e( 'Enabling opt-in is a good way to prevent your list from being black listed as SPAM. Users will only be added to this list if they select the opt-in checkmark when registering for an account.', 'LION' ); ?>">i</span></label></h4>
						<p> <?php $form->add_check_box( 'mailchimp-optin' ); ?> </p>
						<h4><label for="mailchimp-double-optin"><?php _e( 'Enable Double Opt-in.', 'LION' ) ?> <span class="tip" title="<?php _e( 'Enabling double opt-in is a good way to prevent your list from being black listed as SPAM. Users will be sent a confirmation email from MailChimp after signing up, and will only be added to your list after they have confirmed their subscription.', 'LION' ); ?>">i</span></label></h4>
						<p> <?php $form->add_check_box( 'mailchimp-double-optin' ); ?> </p>
				</div>
			<?php
	}

	/**
	 * Save settings
	 *
	 * @since 0.3.6
	 * @return void
	*/
	function save_settings() {
		$defaults = it_exchange_get_option( 'addon_mailchimp' );
		$new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-mailchimp-settings' ) ) {
			$this->error_message = __( 'Error. Please try again', 'LION' );
			return;
		}

		$errors = apply_filters( 'it_exchange_add_on_manual_transaction_validate_settings', $this->get_form_errors( $new_values ), $new_values );
		if ( ! $errors && it_exchange_save_option( 'addon_mailchimp', $new_values ) ) {
			ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
		} else if ( $errors ) {
			$errors = implode( '<br />', $errors );
			$this->error_message = $errors;
		} else {
			$this->status_message = __( 'Settings not saved.', 'LION' );
		}

		if( isset( $_POST['exchange_mailchimp_license_activate'] ) ) {

			// run a quick security check
		 	if( ! check_admin_referer( 'exchange_mailchimp_nonce', 'exchange_mailchimp_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			// $license = trim( get_option( 'exchange_mailchimp_license_key' ) );
	   $exchangewp_mailchimp_options = get_option( 'it-storage-exchange_addon_mailchimp' );
	   $license = trim( $exchangewp_mailchimp_options['mailchimp_license'] );

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( 'mailchimp' ), // the name of our product in EDD
				'url'        => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.' );
				}

			} else {

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( false === $license_data->success ) {

					switch( $license_data->error ) {

						case 'expired' :

							$message = sprintf(
								__( 'Your license key expired on %s.' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
							);
							break;

						case 'revoked' :

							$message = __( 'Your license key has been disabled.' );
							break;

						case 'missing' :

							$message = __( 'Invalid license.' );
							break;

						case 'invalid' :
						case 'site_inactive' :

							$message = __( 'Your license is not active for this URL.' );
							break;

						case 'item_name_mismatch' :

							$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'mailchimp' );
							break;

						case 'no_activations_left':

							$message = __( 'Your license key has reached its activation limit.' );
							break;

						default :

							$message = __( 'An error occurred, please try again.' );
							break;
					}

				}

			}

			// Check if anything passed on a message constituting a failure
			if ( ! empty( $message ) ) {
				$base_url = admin_url( 'admin.php?page=' . 'mailchimp-license' );
				$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

				wp_redirect( $redirect );
				exit();
			}

			//$license_data->license will be either "valid" or "invalid"
			update_option( 'exchange_mailchimp_license_status', $license_data->license );
			// wp_redirect( admin_url( 'admin.php?page=' . 'mailchimp-license' ) );
			exit();
		}

	 // deactivate here
	 // listen for our activate button to be clicked
		if( isset( $_POST['exchange_mailchimp_license_deactivate'] ) ) {

			// run a quick security check
		 	if( ! check_admin_referer( 'exchange_mailchimp_nonce', 'exchange_mailchimp_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			// $license = trim( get_option( 'exchange_mailchimp_license_key' ) );

	   $exchangewp_mailchimp_options = get_option( 'it-storage-exchange_addon_mailchimp' );
	   $license = $exchangewp_mailchimp_options['mailchimp_license'];


			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_name'  => urlencode( 'mailchimp' ), // the name of our product in EDD
				'url'        => home_url()
			);
			// Call the custom API.
			$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.' );
				}

				// $base_url = admin_url( 'admin.php?page=' . 'mailchimp-license' );
				// $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

				wp_redirect( 'admin.php?page=mailchimp-license' );
				exit();
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			// $license_data->license will be either "deactivated" or "failed"
			if( $license_data->license == 'deactivated' ) {
				delete_option( 'exchange_mailchimp_license_status' );
			}

			// wp_redirect( admin_url( 'admin.php?page=' . 'mailchimp-license' ) );
			exit();

		}

	}

	/**
	* This is a means of catching errors from the activation method above and displaying it to the customer
	*
	* @since 1.2.2
	*/
	function exchange_mailchimp_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

		switch( $_GET['sl_activation'] ) {

			case 'false':
				$message = urldecode( $_GET['message'] );
				?>
				<div class="error">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				break;

			case 'true':
			default:
				// Developers can put a custom success message here for when activation is successful if they way.
				break;

		}
	}
	}

	/**
	 * Validates for values
	 *
	 * Returns string of errors if anything is invalid
	 *
	 * @since 0.3.6
	 *
	 * @param array $values
	 *
	 * @return array
	*/
	function get_form_errors( $values ) {

		$errors = array();

		if ( empty( $values['mailchimp-api-key'] ) ) {
			$errors[] = __( 'The MailChimp API Key field cannot be left blank.', 'LION' );
		} else {

			$response = it_exchange_mailchimp_api_request( '', 'GET', array(), null, array(
				'api_key' => trim( $values['mailchimp-api-key'] )
			) );

			if ( is_wp_error( $response ) ) {
				$errors[] = $response->get_error_message();
			}
		}

		if ( empty( $values['mailchimp-label'] ) )
			$errors[] = __( 'The MailChimp sign-up label cannot be left blank.', 'LION' );


		return $errors;
	}

	/**
	 * Sets the default options for manual payment settings
	 *
	 * @since 0.3.6
	 * @return array settings
	*/
	function set_default_settings( $defaults ) {
		$defaults['mailchimp-api-key'] = '';
		$defaults['mailchimp-label']   = __( 'Sign up to receive updates via email!', 'LION' );
		$defaults['mailchimp-optin'] = true;
		$defaults['mailchimp-double-optin'] = true;
		return $defaults;
	}
}
