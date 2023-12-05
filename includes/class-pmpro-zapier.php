<?php

class PMPro_Zapier {

	public $webhook_url;

	function __construct() {
	}

	/**
	 * Run some setup on init
	 */
	static function init() {
		// Set up PMPro hooks.
		add_action( 'pmpro_added_order', array( __CLASS__, 'pmpro_added_order' ) );
		add_action( 'pmpro_updated_order', array( __CLASS__, 'pmpro_updated_order' ) );
		add_action(
			'pmpro_after_change_membership_level', array(
				__CLASS__,
				'pmpro_after_change_membership_level',
			), 10, 3
		);
		add_action( 'pmpro_after_checkout', array( __CLASS__, 'pmpro_after_checkout' ), 10, 2 );


		// Load text domain.
		load_plugin_textdomain( 'pmpro-zapier' );
		
		// Load the webhook if the param is passed.
		if ( ! empty( $_REQUEST['pmpro_zapier_webhook'] ) ) {
			require_once( PMPRO_ZAPIER_DIR . '/includes/webhook-handler.php' );
			exit;
		}
	}

	/**
	 * Helper function to get options from WP DB
	 */
	static function get_options() {
		$options = get_option( 'pmproz_options' );

		if( !empty( $_REQUEST['pmproz_generate_api_key'] ) && current_user_can( 'manage_options' ) ){
			$can_generate = true;
		} else {
			$can_generate = false;
		}

		// generate an API key if we don't have one yet
		if ( empty( $options['api_key'] ) || $can_generate ) {
			$options['api_key'] = wp_generate_password( 32, false );
			PMPro_Zapier::update_options( $options );
		}

		return $options;
	}

	/**
	 * Helper function to save options in WP DB
	 */
	static function update_options( $options ) {
		return update_option( 'pmproz_options', $options, 'no' );
	}

	/**
	 * Helper function to get the webhook URL
	 */
	static function get_webhook_url() {
		$pmproz_options = PMPro_Zapier::get_options();
		return add_query_arg( array( 'pmpro_zapier_webhook' => 1, 'api_key' => $pmproz_options['api_key'] ), home_url( '/', 'https' ) );
	}
	
	/**
	 * Send data to Zapier when an new order is added
	 */
	static function pmpro_added_order( $order ) {
		// bail if setting is not checked
		$options = PMPro_Zapier::get_options();
		if ( empty( $options['pmpro_added_order'] ) ) {
			return;
		}

		// Get the saved order.
		$order = new MemberOrder( $order->id );

		// Add some extra data to the result.
		$data = array();

		$user = get_userdata( $order->user_id );

		$data['username'] = $user->user_login;
		$data['user_email'] = $user->user_email;

		$data['order'] = self::prepare_order_for_request( $order );
    
    	$data['date'] = date_i18n( get_option( 'date_format' ), $order->timestamp );
    	
		// filter the data before we send it to Zapier
		$data = apply_filters('pmproz_added_order_data', $data, $order, $order->user_id );

		$zap = new PMPro_Zapier();
		$zap->prepare_request( 'pmpro_added_order', $data );
		$zap->post( $data );
	}

	/**
	 * Send data to Zapier when an order is updated
	 */
	static function pmpro_updated_order( $order ) {
		// bail if setting is not checked
		$options = PMPro_Zapier::get_options();
		if ( empty( $options['pmpro_updated_order'] ) ) {
			return;
		}

		// Get the updated order.
		$order = new MemberOrder( $order->id );

		// Add some extra data to the result.
		$data = array();

		$user = get_userdata( $order->user_id );

		$data['username'] = $user->user_login;
		$data['user_email'] = $user->user_email;


		$data['order'] = self::prepare_order_for_request( $order );
    
	    $data['date'] = date_i18n( get_option( 'date_format' ), $order->timestamp );	   
    	
	    // filter the data before we send it to Zapier
		$data = apply_filters('pmproz_updated_order_data', $data, $order, $order->user_id );

		$zap = new PMPro_Zapier();
		$zap->prepare_request( 'pmpro_updated_order', $data );
		$zap->post( $data );
	}

	/**
	 * Send data to Zapier after a user's membership level changes
	 */
	static function pmpro_after_change_membership_level( $level_id, $user_id, $cancel_level ) {
		// bail if setting is not checked
		$options = PMPro_Zapier::get_options();
		if ( empty( $options['pmpro_after_change_membership_level'] ) ) {
			return;
		}

		global $wpdb;

		// Get user and level object.
		$user = get_userdata( $user_id );

		// Cancelling
		if ( $level_id == 0 ) {
			$level     = new StdClass();
			$level->ID = '0';
		} else {
			$level = clone pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id ); // Clone since we're going to modify the level object.
			// Unset some unnecessary things.
			unset( $level->allow_signups );
			unset( $level->categories );
			unset( $level->code_id );
			unset( $level->description );
			unset( $level->id );
			unset( $level->subscription_id );
		}

		// Make dates human-readable.
		if ( ! empty( $level->enddate ) ) {
			$level->enddate = date_i18n( get_option( 'date_format' ), $level->enddate );
		}
		if ( ! empty( $level->startdate ) ) {
			$level->startdate = date_i18n( get_option( 'date_format' ), $level->startdate );
		}

		// Add some extra data to the result.
		$data               = array();
		$data['user_id']    = $user_id;
		$data['username']   = $user->user_login;
		$data['user_email'] = $user->user_email;
		
		// Get old level's status so we know why they changed levels.
		if ( ! empty( $cancel_level ) ) {
			$data['old_level_status'] = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$wpdb->pmpro_memberships_users} WHERE user_id = %d AND membership_id = %d AND status NOT LIKE 'active' ORDER BY id LIMIT 1", $user_id, $cancel_level ) );
		}
		if ( empty( $data['old_level_status'] ) ) {
			$data['old_level_status'] = '';
		}

		$data['level'] = $level;

		// filter the data before we send it to Zapier
		$data = apply_filters('pmproz_after_change_membership_level_data', $data, $level_id, $user_id, $cancel_level);

		$zap = new PMPro_Zapier();
		$zap->prepare_request( 'pmpro_after_change_membership_level', $data );
		$zap->post( $data );
	}


	/**
	 * Send data to Zapier when an order is updated
	 */
	static function pmpro_after_checkout( $user_id, $order ) {
		// bail if setting is not checked
		$options = PMPro_Zapier::get_options();
		if ( empty( $options['pmpro_after_checkout'] ) ) {
			return;
		}

		// Add some extra data to the result.
		$data = array();

		$user = get_userdata( $user_id );

		$data['user_id'] = $user_id;
		$data['username']   = $user->user_login;
		$data['user_email'] = $user->user_email;

		$level = clone pmpro_getSpecificMembershipLevelForUser( $user_id, $order->membership_id ); // Clone since we're going to modify the level object.
		if ( ! empty( $level ) ) {
			$data['level_id'] = $level->id;
			$data['level_name'] = $level->name;
		}

		if ( ! empty( $order ) ) {
			$data['date'] = date_i18n( get_option( 'date_format' ), $order->timestamp );
		}

		$data['order'] = self::prepare_order_for_request( $order );

		$data['first_name'] = "";
		$data['last_name'] = "";

		if( !empty( $_REQUEST['bfirstname'] ) ) {
			$data['first_name'] = sanitize_text_field( $_REQUEST['bfirstname'] );
		} else if ( !empty( $_REQUEST['first_name'] ) ) {
			$data['first_name'] = sanitize_text_field( $_REQUEST['first_name'] );
		}
		
		if( !empty( $_REQUEST['blastname'] ) ) {
			$data['last_name'] = sanitize_text_field( $_REQUEST['blastname'] );
		} else if ( !empty( $_REQUEST['last_name'] ) ) {
			$data['last_name'] = sanitize_text_field( $_REQUEST['last_name'] );
		}

		$data = apply_filters( 'pmproz_after_checkout_data', $data, $user_id, $level, $order );

		$zap = new PMPro_Zapier();
		$zap->prepare_request( 'pmpro_after_checkout', $data );
		$zap->post( $data );
	}

	/**
	 * Figure out which webhook url to use.
	 */
	function prepare_request( $hook, $data ) {
		$options = PMPro_Zapier::get_options();
		if ( empty( $options[ $hook ] ) && $hook != 'test' ) {
			return false;
		}
		$this->webhook_url = apply_filters( 'pmproz_prepare_request_webhook', $options[ $hook . '_url' ], $data );
	}

	/**
	 * Post data to Zapier
	 */
	function post( $data = array() ) {
		$args['headers'] = array(
			'Content-Type:' => 'application/json',
		);
		$args['body']    = json_encode( $data );

		$r = wp_remote_post( $this->webhook_url, $args );

		if ( is_wp_error( $r ) ) {
			pmpro_setMessage( __( 'An error occurred: ', 'pmpro-zapier' ) . $r->get_error_message(), 'pmpro_error' );
		}

		return $r;
	}

	/**
	 * Build a copy of a MemberOrder object to send to Zapier.
	 *
	 * @param MemberOrder $order The order object to copy.
	 * @return Object The copied order object.
	 */
	private static function prepare_order_for_request( $order ) {
		$prepared_order = new stdClass();
		$prepared_order->id = $order->id;
		$prepared_order->code = $order->code;
		$prepared_order->user_id = $order->user_id;
		$prepared_order->membership_id = $order->membership_id;
		$prepared_order->billing = $order->billing;
		$prepared_order->subtotal = $order->subtotal;
		$prepared_order->tax = $order->tax;
		$prepared_order->total = $order->total;
		$prepared_order->payment_type = $order->payment_type;
		$prepared_order->cardtype = $order->cardtype;
		$prepared_order->accountnumber = $order->accountnumber;
		$prepared_order->expirationmonth = $order->expirationmonth;
		$prepared_order->expirationyear = $order->expirationyear;
		$prepared_order->status = $order->status;
		$prepared_order->gateway = $order->gateway;
		$prepared_order->gateway_environment = $order->gateway_environment;
		$prepared_order->payment_transaction_id = $order->payment_transaction_id;
		$prepared_order->subscription_transaction_id = $order->subscription_transaction_id;
		$prepared_order->timestamp = $order->timestamp;
		$prepared_order->affiliate_id = $order->affiliate_id;
		$prepared_order->affiliate_subid = $order->affiliate_subid;
		$prepared_order->notes = $order->notes;
		$prepared_order->checkout_id = $order->checkout_id;
		
		return apply_filters( 'pmproz_prepare_order_for_request', $prepared_order, $order );
	}
}
