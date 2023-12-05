<?php
/*
Plugin Name: Paid Memberships Pro - Zapier Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-zapier/
Description: Integrate activity on your membership site with thousands of other apps via Zapier.
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Version: 1.2.2
Text Domain: pmpro-zapier
Domain Path: /languages
*/

// Includes.
define( 'PMPRO_ZAPIER_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMPRO_ZAPIER_BASENAME', plugin_basename( __FILE__ ) );
define( 'PMPRO_ZAPIER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PMPRO_ZAPIER_DIR . '/includes/admin.php';
require_once PMPRO_ZAPIER_DIR . '/includes/class-pmpro-zapier.php';
require_once PMPRO_ZAPIER_DIR . '/includes/settings.php';

/**
 * Load the localization function to make strings translatable.
 * 
 * @since 1.2.0
 */
function pmproz_load_textdomain(){
	load_plugin_textdomain( 'pmpro-zapier', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'pmproz_load_textdomain' );
