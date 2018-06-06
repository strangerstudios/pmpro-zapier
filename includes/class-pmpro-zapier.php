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

		// generate an API key if we don't have one yet
		if ( empty( $options['api_key'] ) ) {
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

		// Remove redundant and unnecessary things.
		unset( $order->ExpirationDate );
		unset( $order->ExpirationDate_YdashM );
		unset( $order->Gateway );
		unset( $order->paypal_token );
		unset( $order->session_id );

		// Add some extra data to the result.
		$data = array();

		$user = get_userdata( $order->user_id );

		$data['username'] = $user->user_login;

		$data['order'] = $order;

		$zap = new PMPro_Zapier();
		$zap->prepare_request( 'pmpro_added_order' );
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

		// Remove redundant and unnecessary things.
		unset( $order->ExpirationDate );
		unset( $order->ExpirationDate_YdashM );
		unset( $order->Gateway );
		unset( $order->paypal_token );
		unset( $order->session_id );

		// Add some extra data to the result.
		$data = array();

		$user = get_userdata( $order->user_id );

		$data['username'] = $user->user_login;

		$data['order'] = $order;

		$zap = new PMPro_Zapier();
		$zap->prepare_request( 'pmpro_updated_order' );
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
			$level->id = '0';
		} else {
			$level = pmpro_getMembershipLevelForUser( $user_id );

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
			$level->enddate = date( get_option( 'date_format' ), $level->enddate );
		}
		if ( ! empty( $level->startdate ) ) {
			$level->startdate = date( get_option( 'date_format' ), $level->startdate );
		}

		// Add some extra data to the result.
		$data               = array();
		$data['user_id']    = $user_id;
		$data['username']   = $user->user_login;
		$data['user_email'] = $user->user_email;

		// Get old level's status so we know why they changed levels.
		$sqlQuery                 = "SELECT status FROM {$wpdb->pmpro_memberships_users} WHERE user_id = {$user_id} AND status NOT LIKE 'active' ORDER BY id DESC LIMIT 1";
		$data['old_level_status'] = $wpdb->get_var( $sqlQuery );

		$data['level'] = $level;

		$zap = new PMPro_Zapier();
		$zap->prepare_request( 'pmpro_after_change_membership_level' );
		$zap->post( $data );
	}

	/**
	 * Figure out which webhook url to use.
	 */
	function prepare_request( $hook ) {
		$options = PMPro_Zapier::get_options();
		if ( empty( $options[ $hook ] ) && $hook != 'test' ) {
			return false;
		}
		$this->webhook_url = $options[ $hook . '_url' ];
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
}
