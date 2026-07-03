<?php
/**
 * Admin assets and shared admin behaviour.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Handles enqueueing of admin styles and scripts.
 *
 * Assets are only loaded on the product edit screen to keep the rest of the
 * dashboard lean (performance requirement).
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Settings handler.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings The settings handler.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Enqueue admin styles on the product edit screen only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( ! $this->is_product_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'wcpp-admin',
			WCPP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WCPP_VERSION
		);
	}

	/**
	 * Enqueue admin scripts on the product edit screen only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( ! $this->is_product_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_script(
			'wcpp-admin',
			WCPP_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WCPP_VERSION,
			true
		);

		$settings = wcpp_get_settings();

		wp_localize_script(
			'wcpp-admin',
			'wcppData',
			array(
				'decimals'      => (int) $settings['decimals'],
				'showMarkup'    => 'yes' === $settings['show_markup'],
				'showMargin'    => 'yes' === $settings['show_margin'],
				'positiveColor' => $settings['positive_color'],
				'zeroColor'     => $settings['zero_color'],
				'negativeColor' => $settings['negative_color'],
				'currencySymbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '',
				'priceFormat'   => function_exists( 'get_woocommerce_price_format' ) ? get_woocommerce_price_format() : '%1$s%2$s',
				'decimalSep'    => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.',
				'thousandSep'   => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',',
				'i18n'          => array(
					'profit' => __( 'Profit', 'wc-product-profit' ),
					'markup' => __( 'Markup', 'wc-product-profit' ),
					'margin' => __( 'Margin', 'wc-product-profit' ),
					'noData' => __( 'No data', 'wc-product-profit' ),
				),
			)
		);
	}

	/**
	 * Determine if the current screen is the product add/edit screen.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return bool
	 */
	private function is_product_screen( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return false;
		}

		return wcpp_is_product_edit_screen();
	}
}
