<?php
/**
 * Fired during plugin deactivation.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit;

defined( 'ABSPATH' ) || exit;

/**
 * Runs deactivation tasks.
 *
 * Deactivation is intentionally non-destructive: product data and settings are
 * preserved. Permanent removal happens in uninstall.php.
 *
 * @since 1.0.0
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
