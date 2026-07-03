<?php
/**
 * Fired during plugin activation.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit;

defined( 'ABSPATH' ) || exit;

/**
 * Runs activation tasks.
 *
 * Sets up the default plugin options. No custom database tables are created;
 * all product data lives in post meta.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		$defaults = array(
			'show_markup'    => 'yes',
			'show_margin'    => 'yes',
			'positive_color' => '#22C55E',
			'zero_color'     => '#F59E0B',
			'negative_color' => '#EF4444',
			'decimals'       => 0,
		);

		$existing = get_option( 'wcpp_settings', array() );

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		update_option( 'wcpp_settings', wp_parse_args( $existing, $defaults ) );

		flush_rewrite_rules();
	}
}
