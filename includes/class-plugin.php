<?php
/**
 * The core plugin class.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit;

use WC_Product_Profit\Admin\Admin;
use WC_Product_Profit\Admin\About;
use WC_Product_Profit\Admin\Product_Fields;
use WC_Product_Profit\Admin\Product_Columns;
use WC_Product_Profit\Admin\Settings;
use WC_Product_Profit\Frontend\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin orchestrator.
 *
 * Wires together every component of the plugin and boots them through the loader.
 * Implemented as a singleton so a single instance manages the whole lifecycle.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Single instance of the plugin.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * The hook loader responsible for maintaining and registering all hooks.
	 *
	 * @var Loader
	 */
	private $loader;

	/**
	 * Constructor. Loads dependencies and defines hooks.
	 */
	private function __construct() {
		$this->loader = new Loader();
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_frontend_hooks();
	}

	/**
	 * Retrieve the single instance of the plugin.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load required helper files that are not autoloaded.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once WCPP_PLUGIN_DIR . 'includes/helpers/functions.php';
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	private function set_locale() {
		$this->loader->add_action( 'init', $this, 'load_textdomain' );
	}

	/**
	 * Load the plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wc-product-profit',
			false,
			dirname( WCPP_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register all of the hooks related to the admin area.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$settings = new Settings();
		$admin    = new Admin( $settings );
		$fields   = new Product_Fields( $settings );
		$columns  = new Product_Columns( $settings );
		$about    = new About();

		// Assets.
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

		// Simple product fields.
		$this->loader->add_action( 'woocommerce_product_options_pricing', $fields, 'render_simple_field', 20 );
		$this->loader->add_action( 'woocommerce_admin_process_product_object', $fields, 'save_simple_field' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $fields, 'save_simple_field_legacy', 10, 1 );

		// Variation fields.
		$this->loader->add_action( 'woocommerce_variation_options_pricing', $fields, 'render_variation_field', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $fields, 'save_variation_field', 10, 2 );

		// Product list columns.
		$this->loader->add_filter( 'manage_edit-product_columns', $columns, 'add_columns' );
		$this->loader->add_action( 'manage_product_posts_custom_column', $columns, 'render_column', 10, 2 );
		$this->loader->add_filter( 'manage_edit-product_sortable_columns', $columns, 'sortable_columns' );
		$this->loader->add_action( 'pre_get_posts', $columns, 'sort_columns' );

		// Settings page (WooCommerce submenu) and About page.
		$this->loader->add_action( 'admin_menu', $settings, 'register_menu', 60 );
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );
		$this->loader->add_action( 'admin_menu', $about, 'register_menu', 61 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * The plugin intentionally exposes nothing on the storefront; the Frontend
	 * class acts as a guard that keeps cost/profit data private.
	 *
	 * @return void
	 */
	private function define_frontend_hooks() {
		$frontend = new Frontend();
		$this->loader->add_action( 'init', $frontend, 'protect_meta' );
	}

	/**
	 * Run the loader to execute all registered hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Retrieve the loader instance.
	 *
	 * @return Loader
	 */
	public function get_loader() {
		return $this->loader;
	}
}
