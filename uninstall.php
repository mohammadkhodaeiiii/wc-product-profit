<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WC_Product_Profit
 */

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin data from the database.
 *
 * Deletes every _wc_cost_price post meta entry and the plugin settings option.
 * No custom tables were created, so no table drops are needed.
 */
function wcpp_uninstall_cleanup() {
	global $wpdb;

	// Delete all cost price meta entries (products and variations).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => '_wc_cost_price' ),
		array( '%s' )
	);

	delete_option( 'wcpp_settings' );
}

wcpp_uninstall_cleanup();
