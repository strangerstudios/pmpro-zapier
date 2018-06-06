<?php
/*
Plugin Name: Paid Memberships Pro - Zapier Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-zapier/
Description: Integrate activity on your membership site with thousands of other apps via Zapier.
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Version: .3
Text Domain: pmpro-zapier
*/

// Includes.
define( 'PMPRO_ZAPIER_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMPRO_ZAPIER_BASENAME', plugin_basename( __FILE__ ) );
define( 'PMPRO_ZAPIER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PMPRO_ZAPIER_DIR . '/includes/admin.php';
require_once PMPRO_ZAPIER_DIR . '/includes/class-pmpro-zapier.php';
require_once PMPRO_ZAPIER_DIR . '/includes/settings.php';