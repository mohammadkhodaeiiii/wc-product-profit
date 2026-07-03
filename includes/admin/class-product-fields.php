<?php
/**
 * Product cost-price fields and the live profit summary card.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Admin;

use WC_Product_Profit\Helpers\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the "cost price" field to simple and variable products and renders the
 * live profit summary card.
 *
 * @since 1.0.0
 */
class Product_Fields {

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
	 * Render the cost price field and summary card for a simple product.
	 *
	 * Hooked to "woocommerce_product_options_pricing" so it appears directly
	 * after the regular/sale price fields in the General tab.
	 *
	 * @return void
	 */
	public function render_simple_field() {
		global $post;

		$cost_price = '';
		if ( $post instanceof \WP_Post ) {
			$stored = get_post_meta( $post->ID, WCPP_COST_META_KEY, true );
			$cost_price = ( '' !== $stored && null !== $stored ) ? wcpp_format_localized_price( $stored ) : '';
		}

		woocommerce_wp_text_input(
			array(
				'id'          => '_wc_cost_price',
				'value'       => $cost_price,
				'label'       => sprintf(
					/* translators: %s: currency symbol. */
					__( 'Cost price (%s)', 'wc-product-profit' ),
					get_woocommerce_currency_symbol()
				),
				'placeholder' => __( 'e.g. 1500000', 'wc-product-profit' ),
				'desc_tip'    => true,
				'description' => __( 'The purchase price you paid for this product. Used to calculate profit, markup and margin. Visible to admins only.', 'wc-product-profit' ),
				'data_type'   => 'price',
				'wrapper_class' => 'wcpp-cost-field',
			)
		);

		$this->render_summary_card( 'wcpp-simple-summary' );

		wp_nonce_field( 'wcpp_save_cost_price', 'wcpp_cost_nonce' );
	}

	/**
	 * Persist the cost price for a simple product.
	 *
	 * Hooked to "woocommerce_admin_process_product_object" which provides the
	 * CRUD product object (HPOS-safe).
	 *
	 * @param \WC_Product $product The product being saved.
	 *
	 * @return void
	 */
	public function save_simple_field( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
			return;
		}

		$nonce = isset( $_POST['wcpp_cost_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wcpp_cost_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wcpp_save_cost_price' ) ) {
			return;
		}

		if ( isset( $_POST['_wc_cost_price'] ) ) {
			$cost = Sanitizer::price( wp_unslash( $_POST['_wc_cost_price'] ) );
			wcpp_update_cost_meta( $product->get_id(), $cost );
		}
	}

	/**
	 * Legacy save handler for WooCommerce versions before 3.0.
	 *
	 * @param int $post_id Product post ID.
	 */
	public function save_simple_field_legacy( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST['wcpp_cost_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wcpp_cost_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wcpp_save_cost_price' ) ) {
			return;
		}

		if ( isset( $_POST['_wc_cost_price'] ) ) {
			$cost = Sanitizer::price( wp_unslash( $_POST['_wc_cost_price'] ) );
			wcpp_update_cost_meta( $post_id, $cost );
		}
	}

	/**
	 * Render the cost price field for a single variation.
	 *
	 * Hooked to "woocommerce_variation_options_pricing".
	 *
	 * @param int      $loop           The variation loop index.
	 * @param array    $variation_data The variation data.
	 * @param \WP_Post $variation      The variation post object.
	 *
	 * @return void
	 */
	public function render_variation_field( $loop, $variation_data, $variation ) {
		$stored     = get_post_meta( $variation->ID, WCPP_COST_META_KEY, true );
		$cost_price = ( '' !== $stored && null !== $stored ) ? wcpp_format_localized_price( $stored ) : '';

		woocommerce_wp_text_input(
			array(
				'id'            => '_wc_cost_price_variation[' . $loop . ']',
				'name'          => '_wc_cost_price_variation[' . $loop . ']',
				'value'         => $cost_price,
				'label'         => sprintf(
					/* translators: %s: currency symbol. */
					__( 'Cost price (%s)', 'wc-product-profit' ),
					get_woocommerce_currency_symbol()
				),
				'placeholder'   => __( 'e.g. 1500000', 'wc-product-profit' ),
				'desc_tip'      => true,
				'description'   => __( 'The purchase price for this variation.', 'wc-product-profit' ),
				'data_type'     => 'price',
				'wrapper_class' => 'form-row form-row-full wcpp-cost-field',
			)
		);

		$this->render_summary_card( 'wcpp-variation-summary-' . $loop, true );
	}

	/**
	 * Persist the cost price for a single variation.
	 *
	 * Hooked to "woocommerce_save_product_variation".
	 *
	 * @param int $variation_id The variation ID.
	 * @param int $loop         The variation loop index.
	 *
	 * @return void
	 */
	public function save_variation_field( $variation_id, $loop ) {
		if ( ! current_user_can( 'edit_product', $variation_id ) ) {
			return;
		}

		// The variation save process is itself nonce protected by WooCommerce
		// (security nonce on the variations panel), so we read the posted value
		// for this specific loop index.
		if ( ! isset( $_POST['_wc_cost_price_variation'][ $loop ] ) ) {
			return;
		}

		$cost = Sanitizer::price( wp_unslash( $_POST['_wc_cost_price_variation'][ $loop ] ) );
		wcpp_update_cost_meta( $variation_id, $cost );
	}

	/**
	 * Render the profit summary card markup.
	 *
	 * Values are populated live by JavaScript; the static markup acts as the
	 * template/placeholder.
	 *
	 * @param string $card_id     A unique DOM id for the card.
	 * @param bool   $is_variation Whether this card belongs to a variation.
	 *
	 * @return void
	 */
	private function render_summary_card( $card_id, $is_variation = false ) {
		$show_markup = 'yes' === $this->settings->get( 'show_markup', 'yes' );
		$show_margin = 'yes' === $this->settings->get( 'show_margin', 'yes' );

		$wrapper_class = $is_variation ? 'wcpp-summary wcpp-summary--variation' : 'wcpp-summary';
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>" id="<?php echo esc_attr( $card_id ); ?>" data-wcpp-summary="<?php echo $is_variation ? 'variation' : 'simple'; ?>">
			<div class="wcpp-summary__header">
				<span class="wcpp-summary__title"><?php esc_html_e( 'Profit overview', 'wc-product-profit' ); ?></span>
				<span class="wcpp-summary__badge" data-wcpp="status-badge"><?php esc_html_e( 'No data', 'wc-product-profit' ); ?></span>
			</div>
			<div class="wcpp-summary__grid">
				<div class="wcpp-card wcpp-card--profit" data-wcpp="profit-card">
					<div class="wcpp-card__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
					</div>
					<div class="wcpp-card__body">
						<span class="wcpp-card__label"><?php esc_html_e( 'Profit', 'wc-product-profit' ); ?></span>
						<span class="wcpp-card__value" data-wcpp="profit">&mdash;</span>
					</div>
				</div>

				<?php if ( $show_markup ) : ?>
				<div class="wcpp-card wcpp-card--markup" data-wcpp="markup-card">
					<div class="wcpp-card__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
					</div>
					<div class="wcpp-card__body">
						<span class="wcpp-card__label"><?php esc_html_e( 'Markup', 'wc-product-profit' ); ?></span>
						<span class="wcpp-card__value" data-wcpp="markup">&mdash;</span>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $show_margin ) : ?>
				<div class="wcpp-card wcpp-card--margin" data-wcpp="margin-card">
					<div class="wcpp-card__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"></path><path d="M12 20V4"></path><path d="M6 20v-6"></path></svg>
					</div>
					<div class="wcpp-card__body">
						<span class="wcpp-card__label"><?php esc_html_e( 'Margin', 'wc-product-profit' ); ?></span>
						<span class="wcpp-card__value" data-wcpp="margin">&mdash;</span>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
