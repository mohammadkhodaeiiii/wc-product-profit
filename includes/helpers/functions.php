<?php
/**
 * Global helper functions.
 *
 * @package WC_Product_Profit
 */

defined( 'ABSPATH' ) || exit;

// Make sure the sanitizer is available to these helpers.
require_once WCPP_PLUGIN_DIR . 'includes/helpers/sanitizer.php';

if ( ! function_exists( 'wcpp_get_settings' ) ) {
	/**
	 * Retrieve the plugin settings merged with the defaults.
	 *
	 * @return array<string, mixed>
	 */
	function wcpp_get_settings() {
		$defaults = array(
			'show_markup'    => 'yes',
			'show_margin'    => 'yes',
			'positive_color' => '#22C55E',
			'zero_color'     => '#F59E0B',
			'negative_color' => '#EF4444',
			'decimals'       => 0,
		);

		$settings = get_option( 'wcpp_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, $defaults );
	}
}

if ( ! function_exists( 'wcpp_get_setting' ) ) {
	/**
	 * Retrieve a single plugin setting.
	 *
	 * @param string $key           The setting key.
	 * @param mixed  $default_value The fallback value.
	 *
	 * @return mixed
	 */
	function wcpp_get_setting( $key, $default_value = null ) {
		$settings = wcpp_get_settings();

		return isset($settings[ $key ]) ? $settings[ $key ] : $default_value;
	}
}

if ( ! function_exists( 'wcpp_get_cost_price' ) ) {
	/**
	 * Retrieve the stored cost price for a product or variation ID.
	 *
	 * @param int $product_id The product or variation ID.
	 *
	 * @return float
	 */
	function wcpp_get_cost_price( $product_id ) {
		$raw = get_post_meta( $product_id, WCPP_COST_META_KEY, true );

		if ( '' === $raw || null === $raw ) {
			return 0.0;
		}

		return (float) wcpp_format_decimal( (string) $raw );
	}
}

if ( ! function_exists( 'wcpp_format_money' ) ) {
	/**
	 * Format a numeric amount using WooCommerce price formatting (no currency tag markup).
	 *
	 * @param float $amount The amount to format.
	 *
	 * @return string
	 */
	function wcpp_format_money( $amount ) {
		return wp_strip_all_tags( wc_price( $amount ) );
	}
}

if ( ! function_exists( 'wcpp_format_percent' ) ) {
	/**
	 * Format a percentage using the configured number of decimals.
	 *
	 * @param float $value The percentage value.
	 *
	 * @return string
	 */
	function wcpp_format_percent( $value ) {
		$decimals = (int) wcpp_get_setting( 'decimals', 0 );

		return number_format_i18n( $value, $decimals ) . '%';
	}
}

if ( ! function_exists( 'wcpp_update_cost_meta' ) ) {
	/**
	 * Save or delete the cost price meta for a product or variation.
	 *
	 * Works with WooCommerce 3.0+ CRUD API and falls back to post meta on older versions.
	 *
	 * @param int    $product_id Product or variation ID.
	 * @param string $cost       Sanitized cost price, or empty string to delete.
	 */
	function wcpp_update_cost_meta( $product_id, $cost ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;

		if ( $product && method_exists( $product, 'update_meta_data' ) ) {
			if ( '' === $cost ) {
				$product->delete_meta_data( WCPP_COST_META_KEY );
			} else {
				$product->update_meta_data( WCPP_COST_META_KEY, $cost );
			}
			$product->save();
			return;
		}

		if ( '' === $cost ) {
			delete_post_meta( $product_id, WCPP_COST_META_KEY );
		} else {
			update_post_meta( $product_id, WCPP_COST_META_KEY, $cost );
		}
	}
}

if ( ! function_exists( 'wcpp_format_decimal' ) ) {
	/**
	 * Normalize a decimal value across WooCommerce versions.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function wcpp_format_decimal( $value ) {
		if ( function_exists( 'wc_format_decimal' ) ) {
			return wc_format_decimal( $value );
		}

		return preg_replace( '/[^0-9\.\-]/', '', (string) $value );
	}
}

if ( ! function_exists( 'wcpp_format_localized_price' ) ) {
	/**
	 * Format a price for display in admin inputs across WooCommerce versions.
	 *
	 * @param string|float $price Raw price value.
	 * @return string
	 */
	function wcpp_format_localized_price( $price ) {
		if ( function_exists( 'wc_format_localized_price' ) ) {
			return wc_format_localized_price( $price );
		}

		return (string) $price;
	}
}

if ( ! function_exists( 'wcpp_is_product_edit_screen' ) ) {
	/**
	 * Determine whether the current admin screen is the product add/edit screen.
	 *
	 * @return bool
	 */
	function wcpp_is_product_edit_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		return 'product' === $screen->id || ( 'post' === $screen->base && 'product' === $screen->post_type );
	}
}

if ( ! function_exists( 'wcpp_update_cost_meta' ) ) {
	/**
	 * Save or delete the cost price meta for a product or variation.
	 *
	 * Works with WooCommerce CRUD (3.0+) and falls back to post meta on older setups.
	 *
	 * @param int    $product_id Product or variation ID.
	 * @param string $cost       Sanitized cost price or empty string to delete.
	 */
	function wcpp_update_cost_meta( $product_id, $cost ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;

		if ( $product && method_exists( $product, 'update_meta_data' ) ) {
			if ( '' === $cost ) {
				$product->delete_meta_data( WCPP_COST_META_KEY );
			} else {
				$product->update_meta_data( WCPP_COST_META_KEY, $cost );
			}
			$product->save();
			return;
		}

		if ( '' === $cost ) {
			delete_post_meta( $product_id, WCPP_COST_META_KEY );
		} else {
			update_post_meta( $product_id, WCPP_COST_META_KEY, $cost );
		}
	}
}
