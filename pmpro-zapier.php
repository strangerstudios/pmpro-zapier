<?php
/*
Plugin Name: Paid Memberships Pro - Zapier Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-zapier/
Description: Integrate activity on your membership site with thousands of other apps via Zapier.
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Version: .1
Text Domain: pmpro-zapier
*/

// Includes.

define( "PMPRO_ZAPIER_DIR", plugin_dir_path( __FILE__ ) );

require_once( dirname( __FILE__ ) . '/includes/class-pmpro-zapier.php' );
require_once( dirname( __FILE__ ) . '/admin/init.php' );

if ( ! defined( 'PMPROZ_PLUGIN_URL' ) ) {
	define( 'PMPROZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
$pmproz = new PMPro_Zapier();

/* Register activation hook. */
register_activation_hook( __FILE__, 'pmproz_admin_notice_activation_hook' );

/**
 * Runs only when the plugin is activated.
 *
 * @since 0.1.0
 */
function pmproz_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmproz-admin-notice', true, 5 );
}

/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */
function pmproz_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmproz-admin-notice' ) ) { ?>
		<div class="updated notice is-dismissible">
			<p><?php printf( __( 'Thank you for activating. <a href="%s">Visit the settings page</a> to get started with the Zapier Add On.', 'pmpro-zapier' ), get_admin_url( null, 'admin.php?page=pmpro-zapier' ) ); ?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmproz-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmproz_admin_notice' );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function pmproz_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$new_links = array(
			'<a href="' . get_admin_url( null, 'admin.php?page=pmpro-zapier' ) . '">' . __( 'Settings', 'pmpro-zapier' ) . '</a>',
		);
	}
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmproz_plugin_action_links' );
/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmproz_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-zapier.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-zapier/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro-zapier' ) . '</a>',
			'<a href="' . esc_url( 'http://paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro-zapier' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmproz_plugin_row_meta', 10, 2 );
