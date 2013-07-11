<?php
/**
 * iThemes Exchange Mail Chimp Add-on
 * @package IT_Exchange_Addon_Mail_Chimp
 * @since 1.0.0
*/

// Initialized Mail Chimp...
if ( !class_exists( 'MCAPI' ) )
	require_once( 'mailchimp-api/MCAPI.class.php' );

/**
 * Call back for settings page
 *
 * This is set in options array when registering the add-on and called from it_exchange_enable_addon()
 *
 * @since 0.3.6
 * @return void
*/
function it_exchange_mail_chimp_settings_callback() {
	$IT_Exchange_Mail_Chimp_Add_On = new IT_Exchange_Mail_Chimp_Add_On();
	$IT_Exchange_Mail_Chimp_Add_On->print_settings_page();
}

/**
 * Enqueues any scripts we need on the backend during a mail chimp setup
 *
 * @since 0.1.0
 *
 * @return void
*/
function it_exchange_stripe_addon_admin_enqueue_scripts() {
	wp_enqueue_script( 'mailchimp-addon-js', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/mailchimp-addon.js', array( 'jquery' ) );
}
add_action( 'admin_enqueue_scripts', 'it_exchange_stripe_addon_admin_enqueue_scripts' );

/**
 * Adds actions to the plugins page for the iThemes Exchange Mail Chimp plugin
 *
 * @since 1.0.0
 *
 * @param array $meta Existing meta
 * @param string $plugin_file the wp plugin slug (path)
 * @param array $plugin_data the data WP harvested from the plugin header
 * @param string $context 
 * @return array
*/
function it_exchange_mail_chimp_plugin_row_actions( $actions, $plugin_file, $plugin_data, $context ) {
	
	$actions['setup_addon'] = '<a href="' . get_admin_url( NULL, 'admin.php?page=it-exchange-addons&add-on-settings=mail-chimp' ) . '">' . __( 'Setup Add-on', 'LION' ) . '</a>';
	
	return $actions;
	
}
add_filter( 'plugin_action_links_exchange-addon-mailchimp/exchange-addon-mailchimp.php', 'it_exchange_mail_chimp_plugin_row_actions', 10, 4 );

function it_exchange_get_mail_chimp_lists( $api_key ) {

	$lists = array();

	if( !empty($api_key ) ) {
		
		$mc = new MCAPI( trim( $api_key ) );
		$mc_lists = $mc->lists();
		
		if( $mc_lists ) {
			foreach( $mc_lists['data'] as $key => $list ) {
				$lists[$list['id']] = $list['name'];
			}
		}
	}
	
	return $lists;	
}


// adds an email to the mailchimp subscription list
function it_exchange_sign_up_email_to_mail_chimp_list() {
	
	$settings = it_exchange_get_option( 'addon_mail_chimp' );
	
	wp_mail( 'lew@ithemes.com', 'request', print_r( $_REQUEST, true ) );
	wp_mail( 'lew@ithemes.com', 'request', print_r( $_POST, true ) );

	if( ! empty( $settings['mail-chimp-api-key'] ) ) {
		
		if ( ! empty( $_REQUEST['it-exchange-mail-chimp-signup'] ) ) {
				
			if ( ! empty( $_REQUEST['email'] ) )
				$email = trim( $_REQUEST['email'] );
			else if ( ! empty( $_REQUEST['sw-em'] ) )
				$email = trim( $_REQUEST['sw-em'] );
			else
				$email = false;
				
			if ( ! empty( $_REQUEST['first_name'] ) )
				$fname = trim( $_REQUEST['first_name'] );
			else if ( ! empty( $_REQUEST['sw-fn'] ) )
				$fname = trim( $_REQUEST['sw-fn'] );
			else
				$fname = false;
				
			if ( ! empty( $_REQUEST['last_name'] ) )
				$lname = trim( $_REQUEST['last_name'] );
			else if ( ! empty( $_REQUEST['sw-ln'] ) )
				$lname = trim( $_REQUEST['sw-ln'] );
			else
				$lname = false;
									
			if ( is_email( $email ) ) {
				
				$mc = new MCAPI( trim( $settings['mail-chimp-api-key'] ) );
				$double_optin = empty( $settings['mail-chimp-double-optin'] ) ? false : true;
				
				$args = array();
				if ( ! empty( $fname ) )
					$args['FNAME'] = $fname;
				if ( ! empty( $lname ) )
					$args['LNAME'] = $lname;
				
				return $mc->listSubscribe( $settings['mail-chimp-list'], $email, $args, 'html', $double_optin );
			
			}
	
		}
			
	}

	return false;
}
add_action( 'it_exchange_register_user', 'it_exchange_sign_up_email_to_mail_chimp_list' );

function it_exchange_mail_chimp_sign_up( $result, $options ) {
	
	$settings = it_exchange_get_option( 'addon_mail_chimp' );
		
	if (  ! empty( $settings['mail-chimp-api-key'] ) 
		&& ( empty( $options['format'] ) || 'html' === $options['format'] ) ) {
		
		$result = '<label for="it-exchange-mail-chimp-signup"><input type="checkbox" id="it-exchange-mail-chimp-signup" name="it-exchange-mail-chimp-signup" /> ' . $settings['mail-chimp-label'] . '</label>' . $result;
		
	}
	
	return $result;
	
}
add_filter( 'it_exchange_theme_api_registration_save', 'it_exchange_mail_chimp_sign_up', 10, 2 );

class IT_Exchange_Mail_Chimp_Add_On {

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
	function IT_Exchange_Mail_Chimp_Add_On() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'mail-chimp' == $this->_current_add_on ) {
			add_action( 'it_exchange_save_add_on_settings_mail-chimp', array( $this, 'save_settings' ) );
			do_action( 'it_exchange_save_add_on_settings_mail-chimp' );
		}

		add_filter( 'it_storage_get_defaults_exchange_addon_mail_chimp', array( $this, 'set_default_settings' ) );
	}

	function print_settings_page() {
		$settings = it_exchange_get_option( 'addon_mail_chimp', true );
	
		$form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_mail-chimp', 'it-exchange-add-on-mail-chimp-settings' ),
			'enctype' => apply_filters( 'it_exchange_add_on_mail-chimp_settings_form_enctype', false ),
			'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=mail-chimp',
		);
		$form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-mail_chimp' ) );

		if ( ! empty ( $this->status_message ) )
			ITUtility::show_status_message( $this->status_message );
		if ( ! empty( $this->error_message ) )
			ITUtility::show_error_message( $this->error_message );

		?>
		<div class="wrap">
			<?php screen_icon( 'it-exchange' ); ?>
			<h2><?php _e( 'Mail Chimp Settings', 'LION' ); ?></h2>

			<?php do_action( 'it_exchange_mail-chimp_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

			<?php $form->start_form( $form_options, 'it-exchange-mail-chimp-settings' ); ?>
				<?php do_action( 'it_exchange_mail-chimp_settings_form_top' ); ?>
				<?php $this->get_mail_chimp_payment_form_table( $form, $form_values ); ?>
				<?php do_action( 'it_exchange_mail-chimp_settings_form_bottom' ); ?>
				<p class="submit">
					<?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
				</p>
			<?php $form->end_form(); ?>
			<?php do_action( 'it_exchange_mail-chimp_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
		<?php
	}

	function get_mail_chimp_payment_form_table( $form, $settings = array() ) {
		//$default_status_options = it_exchange_mail_chimp_get_default_status_options();
		$mail_chimp_lists = it_exchange_get_mail_chimp_lists( $settings['mail-chimp-api-key'] );

		if ( !empty( $settings ) )
			foreach ( $settings as $key => $var )
				$form->set_option( $key, $var );
		?>
        
        <p><?php _e( 'Mail Chimp allows store owners to manage and email lists of their currently subscribed customers.', 'LION' ); ?></p>
        <p><?php _e( 'Video:', 'LION' ); ?>&nbsp;<a href="http://ithemes.com/tutorials/using-mail-chimp-in-exchange/" target="_blank"><?php _e( 'Setting Up Mail Chimp in Exchange', 'LION' ); ?></a></p>
        <p><?php _e( 'To setup Mail Chimp in Exchange, complete the settings below.', 'LION' ); ?></p>
		<table class="form-table">
			<?php do_action( 'it_exchange_mail_chimp_settings_table_top' ); ?>
			<tr valign="top">
				<th scope="row"><label for="mail-chimp-api-key"><?php _e( 'Mail Chimp API Key', 'LION' ) ?> <span class="tip" title="<?php _e( 'Enter your Mail Chimp API Key from your Mail Chimp dashboard, under Account Settings -> Extras -> API Keys', 'LION' ); ?>">i</span></label></th>
				<td>
					<?php $form->add_text_box( 'mail-chimp-api-key', array( 'class' => 'normal-text' ) ); ?>				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="mail-chimp-list"><?php _e( 'Mail Chimp List', 'LION' ) ?> <span class="tip" title="<?php _e( 'This is the list you want to use from Mail Chimp (only appears after saving your Mail Chimp API key).', 'LION' ); ?>">i</span></label></th>
				<td>
					<?php $form->add_drop_down( 'mail-chimp-list', $mail_chimp_lists ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="mail-chimp-label"><?php _e( 'Sign-up Label', 'LION' ) ?> <span class="tip" title="<?php _e( 'This will be the label displayed next to the sign-up option on the registration page.', 'LION' ); ?>">i</span></label></th>
				<td>
					<?php $form->add_text_box( 'mail-chimp-label', array( 'class' => 'normal-text' ) ); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="mail-chimp-double-optin"><?php _e( 'Enable Double Opt-in.', 'LION' ) ?> <span class="tip" title="<?php _e( 'Enabling double opt-in is a good way to prevent your list from being black listed as SPAM. Users will be sent a confirmation email from Mail Chimp after signing up, and will only be added to your list after they have confirmed their subscription.', 'LION' ); ?>">i</span></label></th>
				<td>
					<?php $form->add_check_box( 'mail-chimp-double-optin' ); ?>
				</td>
			</tr>
			<?php do_action( 'it_exchange_mail_chimp_settings_table_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</table>
		<?php
	}

	/**
	 * Save settings
	 *
	 * @since 0.3.6
	 * @return void
	*/
	function save_settings() {
		$defaults = it_exchange_get_option( 'addon_mail_chimp' );
		$new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-mail-chimp-settings' ) ) {
			$this->error_message = __( 'Error. Please try again', 'LION' );
			return;
		}

		$errors = apply_filters( 'it_exchange_add_on_manual_transaction_validate_settings', $this->get_form_errors( $new_values ), $new_values );
		if ( ! $errors && it_exchange_save_option( 'addon_mail_chimp', $new_values ) ) {
			ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
		} else if ( $errors ) {
			$errors = implode( '<br />', $errors );
			$this->error_message = $errors;
		} else {
			$this->status_message = __( 'Settings not saved.', 'LION' );
		}
	}

	/**
	 * Validates for values
	 *
	 * Returns string of errors if anything is invalid
	 *
	 * @since 0.3.6
	 * @return void
	*/
	function get_form_errors( $values ) {
		
		$default_wizard_mail_chimp_settings = apply_filters( 'default_wizard_mail_chimp_settings', array( 'mail-chimp-api-key', 'mail-chimp-list', 'mail-chimp-label', 'mail-chimp-double-optin' ) );
		$errors = array();
		if ( empty( $values['mail-chimp-api-key'] ) )
			$errors[] = __( 'The Mail Chimp API Key field cannot be left blank.', 'LION' );
		if ( empty( $values['mail-chimp-label'] ) )
			$errors[] = __( 'The Mail Chimp sign-up label cannot be left blank.', 'LION' );

		return $errors;
	}

	/**
	 * Sets the default options for manual payment settings
	 *
	 * @since 0.3.6
	 * @return array settings
	*/
	function set_default_settings( $defaults ) {
		$defaults['mail-chimp-api-key'] = '';
		$defaults['mail-chimp-label']   = __( 'Sign-up and receive updates via email!', 'LION' );
		return $defaults;
	}
}
