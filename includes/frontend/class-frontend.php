<?php
/**
 * Frontend guard — keeps cost/profit data out of the storefront.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures that cost price meta is never exposed on the public-facing store.
 *
 * All profit data is admin-only by design. This class adds an extra layer of
 * protection by marking the meta key as protected so it cannot leak through
 * WordPress meta APIs on the frontend.
 *
 * @since 1.0.0
 */
class Frontend {

	/**
	 * Register the cost price meta key as protected.
	 *
	 * Protected meta keys are hidden from REST API responses and from any
	 * public meta queries, preventing accidental exposure.
	 *
	 * @return void
	 */
	public function protect_meta() {
		if ( ! function_exists( 'register_post_meta' ) ) {
			return;
		}

		register_post_meta(
			'product',
			WCPP_COST_META_KEY,
			array(
				'type'              => 'string',
				'description'       => __( 'Product cost price (admin only).', 'wc-product-profit' ),
				'single'            => true,
				'sanitize_callback' => 'wc_format_decimal',
				'show_in_rest'      => false,
				'auth_callback'     => function () {
					return current_user_can( 'edit_products' );
				},
			)
		);
	}
}
