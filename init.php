<?php
/**
 * iThemes Exchange MailChimp Add-on
 * @package IT_Exchange_Addon_MailChimp
 * @since 1.0.0
*/

// Initialized MailChimp...
if ( !class_exists( 'MCAPI' ) )
	require_once( 'mailchimp-api/MCAPI.class.php' );

/**
 * Adds actions to the plugins page for the iThemes Exchange MailChimp plugin
 *
 * @since 1.0.0
 *
 * @param array $meta Existing meta
 * @param string $plugin_file the wp plugin slug (path)
 * @param array $plugin_data the data WP harvested from the plugin header
 * @param string $context 
 * @return array
*/
function it_exchange_mailchimp_plugin_row_actions( $actions, $plugin_file, $plugin_data, $context ) {
	
	$actions['setup_addon'] = '<a href="' . get_admin_url( NULL, 'admin.php?page=it-exchange-addons&add-on-settings=mailchimp' ) . '">' . __( 'Setup Add-on', 'LION' ) . '</a>';
	
	return $actions;
	
}
add_filter( 'plugin_action_links_exchange-addon-mailchimp/exchange-addon-mailchimp.php', 'it_exchange_mailchimp_plugin_row_actions', 10, 4 );

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

function it_exchange_update_mailchimp_lists_ajax() {
	
	$lists = array();
	
	if ( ! empty( $_POST['api_key'] ) )
		$lists = it_exchange_get_mailchimp_lists( $_POST['api_key'] );

	$form = new ITForm( array(), array( 'prefix' => 'it-exchange-add-on-mailchimp' ) );
	die( $form->get_drop_down( 'mailchimp-list', $lists ) );
	
}
add_action('wp_ajax_it_exchange_update_mailchimp_lists', 'it_exchange_update_mailchimp_lists_ajax');

function it_exchange_get_mailchimp_lists( $api_key ) {

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
function it_exchange_sign_up_email_to_mailchimp_list() {
	
	$settings = it_exchange_get_option( 'addon_mailchimp' );
	
	if( ! empty( $settings['mailchimp-api-key'] ) ) {
		
		if ( ! empty( $_POST['it-exchange-mailchimp-signup'] ) ) {
							
			if ( ! empty( $_POST['email'] ) )
				$email = trim( $_POST['email'] );
			else
				$email = false;
				
			if ( ! empty( $_POST['first_name'] ) )
				$fname = trim( $_POST['first_name'] );
			else
				$fname = false;
				
			if ( ! empty( $_POST['last_name'] ) )
				$lname = trim( $_POST['last_name'] );
			else
				$lname = false;
									
			if ( is_email( $email ) ) {
				
				$mc = new MCAPI( trim( $settings['mailchimp-api-key'] ) );
				$double_optin = empty( $settings['mailchimp-double-optin'] ) ? false : true;
				
				$args = array();
				if ( ! empty( $fname ) )
					$args['FNAME'] = $fname;
				if ( ! empty( $lname ) )
					$args['LNAME'] = $lname;
				
				return $mc->listSubscribe( $settings['mailchimp-list'], $email, $args, 'html', $double_optin );
			
			}
	
		}
			
	}

	return false;
}
add_action( 'it_exchange_register_user', 'it_exchange_sign_up_email_to_mailchimp_list' );

/**
 * This function adds our registration field to the list of fields included in the content-registration template part
 *
 * @since 1.0.0
 *
 * @param array $fields existing fields
 * @return array
*/
function it_exchange_mailchimp_sign_up_add_field_to_content_registration_template_part( $fields ) { 

    /** 
     * We want to add our field right before the save button
     * 1) Find the save button
     * 2) Spice our value in right before the save button
     * 3) In the event that the save button wasn't found, just tack onto the end
    */

    $save_key = array_search( 'save', $fields );
    if ( false === $save_key )
        $fields[] = 'mailchimp-signup';
    else
        array_splice( $fields, $save_key, 0, array( 'mailchimp-signup' ) );

    return $fields;
}
add_filter( 'it_exchange_get_content_registration_field_details', 'it_exchange_mailchimp_sign_up_add_field_to_content_registration_template_part' );

/**
 * This function tells Exchange to look in a directory in the MailChimp add-on for template parts
 *
 * @since 1.0.0
 *
 * @param array $template_paths existing template paths. Exchange core paths will be added after this filter.
 * @param array $template_names the template part names we're looking for right now.
 * @return array
*/
function it_exchange_mailchimp_add_template_directory( $template_paths, $template_names ) { 

    /** 
     * Use the template_names array to target a specific template part you want to add
     * In this example, we're adding the following template part: content-registration/details/my-addon-field.php
     * So we're going to only add our templates directory if Exchange is looking for that part.
    */
    if ( ! in_array( 'content-registration/details/mailchimp-signup.php', $template_names ) ) 
        return $template_paths;

    /** 
     * If we are looking for the mailchimp-signup template part, go ahead and add our add_ons directory to the list
     * No trailing slash
    */
    $template_paths[] = dirname( __FILE__ ) . '/templates';

    return $template_paths;
}
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_mailchimp_add_template_directory', 10, 2 );

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
	function IT_Exchange_MailChimp_Add_On() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'mailchimp' == $this->_current_add_on ) {
			add_action( 'it_exchange_save_add_on_settings_mailchimp', array( $this, 'save_settings' ) );
			do_action( 'it_exchange_save_add_on_settings_mailchimp' );
		}

		add_filter( 'it_storage_get_defaults_exchange_addon_mailchimp', array( $this, 'set_default_settings' ) );
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
            <p><?php _e( 'Video:', 'LION' ); ?>&nbsp;<a href="http://ithemes.com/tutorials/using-mailchimp-in-exchange/" target="_blank"><?php _e( 'Setting Up MailChimp in Exchange', 'LION' ); ?></a></p>
            <p><?php _e( 'To setup MailChimp in Exchange, complete the settings below.', 'LION' ); ?></p>
			<h4><label for="mailchimp-api-key"><?php _e( 'MailChimp API Key', 'LION' ) ?> <span class="tip" title="<?php _e( 'Enter your MailChimp API Key from your MailChimp dashboard, under Account Settings -> Extras -> API Keys', 'LION' ); ?>">i</span></label></h4>
			<p> <?php $form->add_text_box( 'mailchimp-api-key' ); ?> </p>
			<h4><label for="mailchimp-list"><?php _e( 'MailChimp List', 'LION' ) ?> <span class="tip" title="<?php _e( 'This is the list you want to use from MailChimp (only appears after saving your MailChimp API key).', 'LION' ); ?>">i</span></label></h4>
			<p> <?php $form->add_drop_down( 'mailchimp-list', $mailchimp_lists ); ?> </p>
			<h4><label for="mailchimp-label"><?php _e( 'Sign-up Label', 'LION' ) ?> <span class="tip" title="<?php _e( 'This will be the label displayed next to the sign-up option on the registration page.', 'LION' ); ?>">i</span></label></h4>
            <p> <?php $form->add_text_box( 'mailchimp-label' ); ?> </p>
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
		
		$default_wizard_mailchimp_settings = apply_filters( 'default_wizard_mailchimp_settings', array( 'mailchimp-api-key', 'mailchimp-list', 'mailchimp-label', 'mailchimp-double-optin' ) );
		$errors = array();
		if ( empty( $values['mailchimp-api-key'] ) )
			$errors[] = __( 'The MailChimp API Key field cannot be left blank.', 'LION' );
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
		return $defaults;
	}
}
