<?php
/**
 * Plugin Name:       WooCommerce Product Profit Calculator
 * Plugin URI:        https://yazweb.ir
 * Description:       Store the cost price for every WooCommerce product and automatically calculate profit, markup and margin. Admin-only, HPOS compatible, fully extensible for a future Pro version.
 * Version:           1.0.0
 * Requires at least: 4.0
 * Requires PHP:      5.6
 * Author:            Mohammadkhodaei
 * Author URI:        https://yazweb.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-product-profit
 * Domain Path:       /languages
 *
 * WC requires at least: 2.6
 * WC tested up to:      9.5
 *
 * @package WC_Product_Profit
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Core plugin constants.
 */
define( 'WCPP_VERSION', '1.0.0' );
define( 'WCPP_PLUGIN_FILE', __FILE__ );
define( 'WCPP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCPP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WCPP_TEXT_DOMAIN', 'wc-product-profit' );

/**
 * The meta key used to store the cost price for products and variations.
 */
define( 'WCPP_COST_META_KEY', '_wc_cost_price' );

/**
 * Remote JSON manifest URL used by the self-hosted updater.
 *
 * Upload "update.json" (template included in the plugin root) to this location
 * on your site. When you publish a higher "version" there, every installed copy
 * of the plugin will see the update in the WordPress dashboard.
 *
 * You can override this in wp-config.php by defining WCPP_UPDATE_MANIFEST_URL.
 */
if ( ! defined( 'WCPP_UPDATE_MANIFEST_URL' ) ) {
	define( 'WCPP_UPDATE_MANIFEST_URL', 'https://yazweb.ir/wc-product-profit/update.json' );
}

require_once WCPP_PLUGIN_DIR . 'includes/class-loader.php';
require_once WCPP_PLUGIN_DIR . 'includes/class-plugin.php';
require_once WCPP_PLUGIN_DIR . 'includes/class-updater.php';

/**
 * Initialise the self-hosted updater (runs in admin for all sites,
 * independent of WooCommerce being active).
 *
 * @return void
 */
function wcpp_init_updater() {
	if ( ! is_admin() ) {
		return;
	}

	$updater = new \WC_Product_Profit\Updater(
		WCPP_UPDATE_MANIFEST_URL,
		WCPP_PLUGIN_FILE,
		WCPP_VERSION
	);
	$updater->register();
}
add_action( 'init', 'wcpp_init_updater' );

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( __FILE__, array( '\WC_Product_Profit\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\WC_Product_Profit\Deactivator', 'deactivate' ) );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 */
function wcpp_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			WCPP_PLUGIN_FILE,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'wcpp_declare_hpos_compatibility' );

/**
 * Bootstrap the plugin once all plugins are loaded so we can safely check for WooCommerce.
 *
 * @return void
 */
function wcpp_run_plugin() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wcpp_woocommerce_missing_notice' );
		return;
	}

	\WC_Product_Profit\Plugin::instance()->run();
}
add_action( 'plugins_loaded', 'wcpp_run_plugin' );

/**
 * Display an admin notice when WooCommerce is not active.
 *
 * @return void
 */
function wcpp_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$message = sprintf(
		/* translators: %s: WooCommerce plugin name. */
		esc_html__( 'WooCommerce Product Profit Calculator requires %s to be installed and active.', 'wc-product-profit' ),
		'<strong>WooCommerce</strong>'
	);

	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}
