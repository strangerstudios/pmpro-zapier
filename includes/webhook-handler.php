<?php
// Don't access directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $pmpro_error, $logstr;

// Log string for debugging.
$logstr = '';

$pmproz_options = PMPro_Zapier::get_options();
$api_key        = ! empty( $_REQUEST['api_key'] ) ? sanitize_key( $_REQUEST['api_key'] ) : '';
$action         = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

header( 'Content-Type: application/json' );

if ( $api_key != $pmproz_options['api_key'] ) {
	status_header( 403 );
	echo json_encode( __( 'A valid API key is required.', 'pmpro-zapier' ) );
	exit;
}

pmproz_webhook_log( __( 'Data Received', 'pmpro-zapier' ) . ': ' . var_export( $_REQUEST, true ) );

// Bail if PMPro is not loaded
if ( ! function_exists( 'pmpro_getParam' ) ) {
	pmproz_webhook_log( __( 'Paid Memberships Pro must be activated.', 'pmpro-zapier' ) );
	pmproz_webhook_exit();
}

switch ( $action ) {

	case 'add_member':
		pmproz_webhook_log( __( 'add member called successfully', 'pmpro-zapier' ) );

		// check for existing user
		$user = pmproz_get_user_data();

		$level_id    = pmpro_getParam( 'level_id' );
		$pmpro_error = '';

		// only update user data if this is a new user
		if ( empty( $user ) ) {
			$user_email = pmpro_getParam( 'user_email' );
			$user_login = pmpro_getParam( 'user_login' );
			$full_name  = pmpro_getParam( 'full_name' );
			$first_name = pmpro_getParam( 'first_name' );
			$last_name  = pmpro_getParam( 'last_name' );

			// we need an email address at least
			if ( empty( $user_email ) ) {
				$pmpro_error = __( 'You must pass the user_email parameter to the add_member method.', 'pmpro-zapier' );
			} else {
				// if a full name is passed, maybe use it to get first and last name
				if ( ! empty( $full_name ) && empty( $first_name ) && empty( $last_name ) ) {
					$name_parts = pnp_split_full_name( $full_name );
					$first_name = $name_parts['fname'];
					$last_name  = $name_parts['lname'];
				}

				// if no user name is passed, make one
				if ( empty( $user_login ) ) {
					$user_login = pmpro_generateUsername( $first_name, $last_name, $user_email );
				}

				// generate a password
				if ( empty( $user_pass ) ) {
					$user_pass = pmpro_getDiscountCode() . pmpro_getDiscountCode();
				}

				// insert user
				$new_user_array = array(
					'user_login' => $user_login,
					'user_pass'  => $user_pass,
					'user_email' => $user_email,
					'first_name' => $first_name,
					'last_name'  => $last_name,
				);
				$user_id        = apply_filters( 'pmpro_new_user', '', $new_user_array );
				if ( empty( $user_id ) ) {
					$user_id = wp_insert_user( $new_user_array );
				}
			}
		} else {
			$user_id = $user->ID;
		}

		// make sure we have a user and he or she doesn't have a membership level already
		if ( empty( $user_id ) ) {
			$pmpro_error .= __( 'User creation failed.', 'pmpro-zapier' );
		} elseif ( pmpro_hasMembershipLevel( null, $user_id ) ) {
			$pmpro_error .= __( 'This user already has a membership level.', 'pmpro-zapier' );
		}

		// check the level
		if ( empty( $level_id ) && $level_id !== '0' ) {
			$pmpro_error .= __( 'You must pass in valid level_id.', 'pmpro-zapier' );
		}

		// add membership level
		if ( empty( $pmpro_error ) && pmpro_changeMembershipLevel( $level_id, $user_id, 'zapier_changed' ) ) {
			echo json_encode( array( 'status' => 'success' ) );
			pmproz_webhook_log( __( 'changed level' , 'pmpro-zapier' ) );
		} else {

			echo json_encode(
				array(
					'status'  => 'failed',
					'message' => $pmpro_error,
				)
			);
			pmproz_webhook_log( $pmpro_error );
		}

		break;

	case 'change_membership_level':
		pmproz_webhook_log( 'change membership level called successfully.' );

		// need a user id, login, or email address and a membership level id
		$user     = pmproz_get_user_data();
		$level_id = intval( pmpro_getParam( 'level_id' ) );
		$user_id  = $user->ID;

		// old level status
		$old_level_status = pmpro_getParam( 'old_level_status', 'REQUEST', 'zapier_changed' );

		$pmpro_error = '';

		// failed to get the user object.
		if ( empty( $user ) ) {
			$pmpro_error .= __( 'You must pass in a user_id, user_login, or user_email.', 'pmpro-zapier' );
		}

		// check the level
		if ( empty( $level_id ) && $level_id !== '0' ) {
			$pmpro_error .= __( 'You must pass in a new level_id or 0.', 'pmpro-zapier' );
		}

		if ( empty( $pmpro_error ) && pmpro_changeMembershipLevel( $level_id, $user_id, 'zapier_changed' ) ) {
			echo json_encode( array( 'status' => 'success' ) );
			pmproz_webhook_log( __( 'changed level', 'pmpro-zapier' ) );
		} else {

			echo json_encode(
				array(
					'status'  => 'failed',
					'message' => $pmpro_error,
				)
			);
			pmproz_webhook_log( $pmpro_error );
		}

		break;

	case 'add_order':
		$user = pmproz_get_user_data();

		$order                = new MemberOrder();
		$order->user_id       = $user->ID;
		$order->membership_id = intval( pmpro_getParam( 'level_id' ) );

		$order->code                        = $order->getRandomCode();
		$order->subtotal                    = pmpro_getParam( 'subtotal' );
		$order->tax                         = pmpro_getParam( 'tax' );
		$order->couponamount                = pmpro_getParam( 'couponamount' );
		$order->total                       = pmpro_getParam( 'total' );
		$order->payment_type                = pmpro_getParam( 'payment_type' );
		$order->cardtype                    = pmpro_getParam( 'cardtype' );
		$order->accountnumber               = pmpro_getParam( 'accountnumber' );
		$order->expirationmonth             = pmpro_getParam( 'expirationmonth' );
		$order->expirationyear              = pmpro_getParam( 'expirationyear' );
		$order->status                      = pmpro_getParam( 'status' );
		$order->gateway                     = pmpro_getParam( 'gateway' );
		$order->gateway_environment         = pmpro_getParam( 'gateway_environment' );
		$order->payment_transaction_id      = pmpro_getParam( 'payment_transaction_id' );
		$order->subscription_transaction_id = pmpro_getParam( 'subscription_transaction_id' );
		$order->affiliate_id                = pmpro_getParam( 'affiliate_id' );
		$order->affiliate_subid             = pmpro_getParam( 'affiliate_subid' );
		$order->notes                       = pmpro_getParam( 'notes' );
		$order->checkout_id                 = pmpro_getParam( 'checkout_id' );
		$order->billing                     = new stdClass();
		$order->billing->name               = pmpro_getParam( 'billing_name' );
		$order->billing->street             = pmpro_getParam( 'billing_street' );
		$order->billing->city               = pmpro_getParam( 'billing_city' );
		$order->billing->state              = pmpro_getParam( 'billing_state' );
		$order->billing->zip                = pmpro_getParam( 'billing_zip' );
		$order->billing->country            = pmpro_getParam( 'billing_country' );
		$order->billing->phone              = pmpro_getParam( 'billing_phone' );

		if ( $order->saveOrder() ) {
			echo json_encode( array( 'status' => 'success' ) );
		} else {
			echo json_encode(
				array(
					'status'  => 'failed',
					'message' => $pmpro_error,
				)
			);
		}

		break;

	case 'update_order':
		// figure out which kind of id was passed
		if ( ! empty( $_REQUEST['order'] ) ) {
			$order = new MemberOrder( pmpro_getParam( 'order' ) );
		} elseif ( ! empty( $_REQUEST['order_id'] ) ) {
			$order = new MemberOrder( pmpro_getParam( 'order_id' ) );
		} elseif ( ! empty( $_REQUEST['code'] ) ) {
			$order = new MemberOrder( pmpro_getParam( 'code' ) );
		} elseif ( ! empty( $_REQUEST['id'] ) ) {
			$order = new MemberOrder( pmpro_getParam( 'id' ) );
		}

		if ( empty( $order ) || empty( $order->id ) ) {
			// assume this is a new order
			$order       = new MemberOrder();
			$order->code = pmpro_getParam( 'code' );    // in case they pass in a specific code
		}

		// defaults to the existing order values if getParam is empty
		$order->subtotal                    = pmpro_getParam( 'subtotal', 'REQUEST', $order->subtotal );
		$order->tax                         = pmpro_getParam( 'tax', 'REQUEST', $order->tax );
		$order->couponamount                = pmpro_getParam( 'couponamount', 'REQUEST', $order->couponamount );
		$order->total                       = pmpro_getParam( 'total', 'REQUEST', $order->total );
		$order->payment_type                = pmpro_getParam( 'payment_type', 'REQUEST', $order->payment_type );
		$order->cardtype                    = pmpro_getParam( 'cardtype', 'REQUEST', $order->cardtype );
		$order->accountnumber               = pmpro_getParam( 'accountnumber', 'REQUEST', $order->accountnumber );
		$order->expirationmonth             = pmpro_getParam( 'expirationmonth', 'REQUEST', $order->expirationmonth );
		$order->expirationyear              = pmpro_getParam( 'expirationyear', 'REQUEST', $order->expirationyear );
		$order->status                      = pmpro_getParam( 'status', 'REQUEST', $order->status );
		$order->gateway                     = pmpro_getParam( 'gateway', 'REQUEST', $order->gateway );
		$order->gateway_environment         = pmpro_getParam( 'gateway_environment', 'REQUEST', $order->gateway_environment );
		$order->payment_transaction_id      = pmpro_getParam( 'payment_transaction_id', 'REQUEST', $order->payment_transaction_id );
		$order->subscription_transaction_id = pmpro_getParam( 'subscription_transaction_id', 'REQUEST', $order->subscription_transaction_id );
		$order->affiliate_id                = pmpro_getParam( 'affiliate_id', 'REQUEST', $order->affiliate_id );
		$order->affiliate_subid             = pmpro_getParam( 'affiliate_subid', 'REQUEST', $order->affiliate_subid );
		$order->notes                       = pmpro_getParam( 'notes', 'REQUEST', $order->notes );
		$order->checkout_id                 = pmpro_getParam( 'checkout_id', 'REQUEST', $order->checkout_id );
		if ( empty( $order->billing ) ) {
			$order->billing = new stdClass();
		}
		$order->billing->name    = pmpro_getParam( 'billing_name', 'REQUEST', $order->billing->name );
		$order->billing->street  = pmpro_getParam( 'billing_street', 'REQUEST', $order->billing->street );
		$order->billing->city    = pmpro_getParam( 'billing_city', 'REQUEST', $order->billing->city );
		$order->billing->state   = pmpro_getParam( 'billing_state', 'REQUEST', $order->billing->state );
		$order->billing->zip     = pmpro_getParam( 'billing_zip', 'REQUEST', $order->billing->zip );
		$order->billing->country = pmpro_getParam( 'billing_country', 'REQUEST', $order->billing->country );
		$order->billing->phone   = pmpro_getParam( 'billing_phone', 'REQUEST', $order->billing->phone );

		if ( $order->saveOrder() ) {
			echo json_encode( array( 'status' => 'success' ) );
		} else {
			echo json_encode(
				array(
					'status'  => 'failed',
					'message' => $pmpro_error,
				)
			);
		}

	case 'has_membership_level':
		$user = pmproz_get_user_data();

		$user_id  = $user->ID;
		$level_id = intval( pmpro_getParam( 'level_id' ) );

		if ( pmpro_hasMembershipLevel( $level_id, $user_id ) ) {
			echo json_encode( 'true' );
		} else {
			echo json_encode( 'false' );
		}

		break;

	default:
		// testing connection
		break;
}
// write debug info to the text file.
pmproz_webhook_exit();

/**
 * Helper function to retrieve the user object.
 *
 * @return user (object)
 */
function pmproz_get_user_data() {

	$user       = false;
	$user_id    = intval( pmpro_getParam( 'user_id' ) );
	$user_login = sanitize_user( pmpro_getParam( 'user_login' ) );
	$user_email = sanitize_email( pmpro_getParam( 'user_email' ) );

	if ( ! empty( $user_id ) ) {
		$user = get_userdata( $user_id );
	} elseif ( ! empty( $user_login ) ) {
		$user = get_user_by( 'login', $user_login );
	} elseif ( ! empty( $user_email ) ) {
		$user = get_user_by( 'email', $user_email );
	}

	return $user;
}

/**
 * Serves as a buffer for logging details to text file.
 *
 * @param string $s string to log to log file.
 */
function pmproz_webhook_log( $s ) {
	global $logstr;
	$logstr .= "\t" . $s . "\n";
}

/**
 * Output the log string to the text file and log what details are received.
 * Ensure PMPRO_ZAPIER_DEBUG_LOG is set to true
 */
function pmproz_webhook_exit() {
	global $logstr;

	if ( $logstr ) {
		$logstr = __('Logged On', 'pmpro-zapier' ) . ': ' . date( 'm/d/Y H:i:s' ) . "\n" . $logstr . "\n-------------\n";

		// save to log
		if ( defined( 'PMPRO_ZAPIER_DEBUG_LOG' ) && true === PMPRO_ZAPIER_DEBUG_LOG ) {			
			$loghandle = fopen( PMPRO_ZAPIER_DIR . '/logs/zapier-logs.txt', 'a+' );
			fwrite( $loghandle, $logstr );
			fclose( $loghandle );
		}
		
		if( defined( 'PMPRO_ZAPIER_DEBUG' ) && PMPRO_ZAPIER_DEBUG !== false ) {
			// output to screen
			if ( current_user_can( 'manage_options' ) ) {
				echo $logstr;
			}
			
			// send email
			if ( strpos( PMPRO_ZAPIER_DEBUG, '@' ) ) {
				$log_email = PMPRO_ZAPIER_DEBUG;
			} else {
				$log_email = get_option( 'admin_email' );
			}			
			wp_mail( $log_email, get_option( 'blogname' ) . ' ' . __( 'Zapier Log', 'pmpro-zapier' ), nl2br( $logstr ) );
		}					
	}
	exit;
}
