<?php
/**
 * Profit calculation engine.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Performs all profit related calculations.
 *
 * Stateless helper with a single responsibility: turning a sale price and a
 * cost price into profit, markup and margin values. Division by zero is always
 * guarded against.
 *
 * @since 1.0.0
 */
class Profit_Calculator {

	/**
	 * Calculate profit, markup and margin from a sale and cost price.
	 *
	 * @param float $sale_price The selling price.
	 * @param float $cost_price The cost (purchase) price.
	 *
	 * @return array{profit: float, markup: float, margin: float, status: string, has_cost: bool}
	 */
	public static function calculate( $sale_price, $cost_price ) {
		$profit = $sale_price - $cost_price;

		$markup = 0.0;
		if ( $cost_price > 0 ) {
			$markup = ( $profit / $cost_price ) * 100;
		}

		$margin = 0.0;
		if ( $sale_price > 0 ) {
			$margin = ( $profit / $sale_price ) * 100;
		}

		return array(
			'profit'   => $profit,
			'markup'   => $markup,
			'margin'   => $margin,
			'status'   => self::get_status( $profit ),
			'has_cost' => $cost_price > 0,
		);
	}

	/**
	 * Determine the profit status keyword.
	 *
	 * @param float $profit The profit amount.
	 *
	 * @return string One of "positive", "zero" or "negative".
	 */
	public static function get_status( $profit ) {
		if ( $profit > 0 ) {
			return 'positive';
		}

		if ( $profit < 0 ) {
			return 'negative';
		}

		return 'zero';
	}

	/**
	 * Resolve the effective sale price for a product or variation.
	 *
	 * Uses the active price (sale price if set, otherwise the regular price).
	 *
	 * @param \WC_Product $product The product or variation object.
	 *
	 * @return float
	 */
	public static function get_effective_price( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_price' ) ) {
			return 0.0;
		}

		$price = $product->get_price();

		if ( '' === $price || null === $price ) {
			$price = $product->get_regular_price();
		}

		return (float) wcpp_format_decimal( (string) $price );
	}
}
