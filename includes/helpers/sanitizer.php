<?php
/**
 * Input sanitization helpers.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Centralised, reusable sanitization routines.
 *
 * Keeps all input cleaning in one place so the rest of the codebase only ever
 * deals with trusted, normalised values.
 *
 * @since 1.0.0
 */
class Sanitizer {

	/**
	 * Sanitize a price value into a normalized decimal string.
	 *
	 * Accepts localized input, strips everything that is not a number and
	 * formats it through WooCommerce so it is safe to store.
	 *
	 * @param mixed $value The raw price input.
	 *
	 * @return string A WooCommerce-formatted decimal string, or empty string.
	 */
	public static function price( $value ) {
		if ( null === $value || '' === $value ) {
			return '';
		}

		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		return wcpp_format_decimal( $value );
	}

	/**
	 * Sanitize a checkbox style "yes"/"no" option.
	 *
	 * @param mixed $value The raw value.
	 *
	 * @return string Either "yes" or "no".
	 */
	public static function yes_no( $value ) {
		return ( 'yes' === $value || '1' === $value || 1 === $value || true === $value ) ? 'yes' : 'no';
	}

	/**
	 * Sanitize a hex color, falling back to a default.
	 *
	 * @param mixed  $value   The raw color value.
	 * @param string $default_color The fallback color.
	 *
	 * @return string A valid hex color.
	 */
	public static function color( $value, $default_color ) {
		$color = sanitize_hex_color( (string) $value );

		return $color ? $color : $default_color;
	}

	/**
	 * Sanitize the decimals option, clamping it between 0 and 4.
	 *
	 * @param mixed $value The raw value.
	 *
	 * @return int An integer between 0 and 4.
	 */
	public static function decimals( $value ) {
		$decimals = absint( $value );

		return max( 0, min( 4, $decimals ) );
	}
}
