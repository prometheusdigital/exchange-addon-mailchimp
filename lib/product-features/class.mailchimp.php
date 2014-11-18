<?php
/**
 * This will control email messages with any product types that register email message support.
 * By default, it registers a metabox on the product's add/edit screen and provides HTML / data for the frontend.
 *
 * @since CHANGE
 * @package exchange-addon-mailchimp
*/


class IT_Exchange_Product_Feature_MailChimp {

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Product_Feature_MailChimp() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
		}
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_product_types' ) );
		add_action( 'it_exchange_update_product_feature_mailchimp', array( $this, 'save_feature' ), 9, 3 );
		add_filter( 'it_exchange_get_product_feature_mailchimp', array( $this, 'get_feature' ), 9, 3 );
		add_filter( 'it_exchange_product_has_feature_mailchimp', array( $this, 'product_has_feature') , 9, 3 );
		add_filter( 'it_exchange_product_supports_feature_mailchimp', array( $this, 'product_supports_feature') , 9, 2 );
	}

	/**
	 * Register the product feature and add it to enabled product-type addons
	 *
	 * @since 1.0.0
	*/
	function add_feature_support_to_product_types() {
		// Register the product feature
		$slug        = 'mailchimp';
		$description = __( "Set the Product's MailChimp options", 'LION' );
		it_exchange_register_product_feature( $slug, $description );

		// Add it to all enabled product-type addons
		$products = it_exchange_get_enabled_addons( array( 'category' => 'product-type' ) );
		foreach( $products as $key => $params ) {
			it_exchange_add_feature_support_to_product_type( 'mailchimp', $params['slug'] );
		}
	}

	/**
	 * Register's the metabox for any product type that supports the feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function init_feature_metaboxes() {

		global $post;

		if ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = $_REQUEST['post_type'];
		} else {
			if ( isset( $_REQUEST['post'] ) )
				$post_id = (int) $_REQUEST['post'];
			elseif ( isset( $_REQUEST['post_ID'] ) )
				$post_id = (int) $_REQUEST['post_ID'];
			else
				$post_id = 0;

			if ( $post_id )
				$post = get_post( $post_id );

			if ( isset( $post ) && !empty( $post ) )
				$post_type = $post->post_type;
		}

		if ( !empty( $_REQUEST['it-exchange-product-type'] ) )
			$product_type = $_REQUEST['it-exchange-product-type'];
		else
			$product_type = it_exchange_get_product_type( $post );

		if ( !empty( $post_type ) && 'it_exchange_prod' === $post_type ) {
			if ( !empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'mailchimp' ) )
				add_action( 'it_exchange_product_metabox_callback_' . $product_type, array( $this, 'register_metabox' ) );
		}

	}

	/**
	 * Registers the feature metabox for a specific product type
	 *
	 * Hooked to it_exchange_product_metabox_callback_[product-type] where product type supports the feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function register_metabox() {
		add_meta_box( 'it-exchange-product-mailchimp', __( 'MailChimp', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'normal' );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function print_metabox( $product ) {
		$settings = it_exchange_get_option( 'addon_mailchimp' );
	}

	/**
	 * This saves the value
	 *
	 * @since 1.0.0 
	 * @param object $post wp post object
	 * @return void
	*/
	function save_feature_on_product_save() {
		// Abort if we can't determine a product type
		if ( ! $product_type = it_exchange_get_product_type() )
			return;

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id )
			return;

		// Abort if this product type doesn't support this feature
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'mailchimp' ) )
			return;

		// Get new value from post
		$tax_exempt = empty( $_POST['it-exchange-add-on-easy-eu-mailchimp-value-added-tax-exempt'] ) ? false : true;

		// Save new value
		it_exchange_update_product_feature( $product_id, 'mailchimp', $tax_exempt, array( 'setting' => 'exempt' ) );

		// Get new value from post
		$tax_type = !isset( $_POST['it-exchange-add-on-easy-eu-mailchimp-value-added-tax-type'] ) ? 'default' : $_POST['it-exchange-add-on-easy-eu-mailchimp-value-added-tax-type'];

		// Save new value
		it_exchange_update_product_feature( $product_id, 'mailchimp', $tax_type, array( 'setting' => 'type' ) );

	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.0.0
	 * @param integer $product_id the product id
	 * @param mixed $new_value the new value
	 * @return bolean
	*/
	function save_feature( $product_id, $new_value, $options=array() ) {
		$defaults['setting'] = 'exempt';
		$options = ITUtility::merge_defaults( $options, $defaults );
		
		switch ( $options['setting'] ) {
			
			case 'exempt':
				update_post_meta( $product_id, '_it-exchange-easy-eu-mailchimp-exempt', $new_value );
				break;
			case 'type':
				update_post_meta( $product_id, '_it-exchange-easy-eu-mailchimp-type', $new_value );
				break;
			
		}
		return true;
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.0.0
	 * @param mixed $existing the values passed in by the WP Filter API. Ignored here.
	 * @param integer product_id the WordPress post ID
	 * @return string product feature
	*/
	function get_feature( $existing, $product_id, $options=array() ) {
		$defaults['setting'] = 'exempt';
		$options = ITUtility::merge_defaults( $options, $defaults );
		
		switch ( $options['setting'] ) {
			
			case 'exempt':
				return get_post_meta( $product_id, '_it-exchange-easy-eu-mailchimp-exempt', true );
			case 'type':
				return get_post_meta( $product_id, '_it-exchange-easy-eu-mailchimp-type', true );

		}
		
		return false;
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_has_feature( $result, $product_id, $options=array() ) {
		$defaults['setting'] = 'exempt';
		$options = ITUtility::merge_defaults( $options, $defaults );

		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id, $options ) )
			return false;

		// If it does support, does it have it?
		return (boolean) $this->get_feature( false, $product_id, $options );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_supports_feature( $result, $product_id ) {
		// Does this product type support this feature?
		$product_type = it_exchange_get_product_type( $product_id );
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'mailchimp' ) )
			return false;

		return true;
	}
}
$IT_Exchange_Product_Feature_MailChimp = new IT_Exchange_Product_Feature_MailChimp();
