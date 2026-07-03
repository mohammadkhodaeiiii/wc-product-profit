<?php
/**
 * Custom columns on the products list table.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Adds cost price, sale price, profit, margin and markup columns to the
 * WooCommerce products list table, with sorting support.
 *
 * @since 1.0.0
 */
class Product_Columns {

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
	 * Add the custom columns after the WooCommerce price column.
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string>
	 */
	public function add_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'price' === $key ) {
				$new_columns['wcpp_cost']   = __( 'Cost price', 'wc-product-profit' );
				$new_columns['wcpp_sale']   = __( 'Sale price', 'wc-product-profit' );
				$new_columns['wcpp_profit'] = __( 'Profit', 'wc-product-profit' );

				if ( 'yes' === $this->settings->get( 'show_margin', 'yes' ) ) {
					$new_columns['wcpp_margin'] = __( 'Margin', 'wc-product-profit' );
				}

				if ( 'yes' === $this->settings->get( 'show_markup', 'yes' ) ) {
					$new_columns['wcpp_markup'] = __( 'Markup', 'wc-product-profit' );
				}
			}
		}

		return $new_columns;
	}

	/**
	 * Render the content of each custom column.
	 *
	 * @param string $column     The column key.
	 * @param int    $product_id The product ID.
	 *
	 * @return void
	 */
	public function render_column( $column, $product_id ) {
		if ( ! in_array( $column, array( 'wcpp_cost', 'wcpp_sale', 'wcpp_profit', 'wcpp_margin', 'wcpp_markup' ), true ) ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			echo '<span class="wcpp-col-empty">&mdash;</span>';
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			$this->render_variable_column( $column, $product );
			return;
		}

		$cost = wcpp_get_cost_price( $product_id );
		$sale = Profit_Calculator::get_effective_price( $product );

		if ( 'wcpp_sale' === $column ) {
			if ( $sale <= 0 ) {
				echo '<span class="wcpp-col-empty">&mdash;</span>';
				return;
			}
			echo '<span class="wcpp-col-value">' . esc_html( wcpp_format_money( $sale ) ) . '</span>';
			return;
		}

		if ( $cost <= 0 ) {
			echo '<span class="wcpp-col-empty">&mdash;</span>';
			return;
		}

		$result = Profit_Calculator::calculate( $sale, $cost );

		$this->output_value( $column, $cost, $result );
	}

	/**
	 * Render a column for a variable product, summarising its variations.
	 *
	 * @param string      $column  The column key.
	 * @param \WC_Product $product The variable product.
	 *
	 * @return void
	 */
	private function render_variable_column( $column, $product ) {
		$children = $product->get_children();

		$min = null;
		$max = null;

		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );

			if ( ! $variation ) {
				continue;
			}

			$sale = Profit_Calculator::get_effective_price( $variation );
			$cost = wcpp_get_cost_price( (int) $child_id );

			if ( 'wcpp_sale' === $column ) {
				if ( $sale <= 0 ) {
					continue;
				}
				$metric = $sale;
			} else {
				if ( $cost <= 0 ) {
					continue;
				}
				$result = Profit_Calculator::calculate( $sale, $cost );
				$metric = $this->metric_for_column( $column, $cost, $sale, $result );
			}

			if ( null === $metric ) {
				continue;
			}

			$min = ( null === $min ) ? $metric : min( $min, $metric );
			$max = ( null === $max ) ? $metric : max( $max, $metric );
		}

		if ( null === $min ) {
			echo '<span class="wcpp-col-empty">&mdash;</span>';
			return;
		}

		$is_percent = in_array( $column, array( 'wcpp_margin', 'wcpp_markup' ), true );
		$is_money   = in_array( $column, array( 'wcpp_cost', 'wcpp_sale', 'wcpp_profit' ), true );

		if ( $min === $max ) {
			$formatted = $is_percent ? wcpp_format_percent( $min ) : wcpp_format_money( $min );
		} else {
			$formatted = $is_percent
				? wcpp_format_percent( $min ) . ' &ndash; ' . wcpp_format_percent( $max )
				: wcpp_format_money( $min ) . ' &ndash; ' . wcpp_format_money( $max );
		}

		if ( $is_money && 'wcpp_sale' === $column ) {
			printf( '<span class="wcpp-col-value">%s</span>', wp_kses_post( $formatted ) );
			return;
		}

		$color = $this->color_for_value( $max );
		printf( '<span class="wcpp-col-value" style="color:%s;font-weight:600;">%s</span>', esc_attr( $color ), wp_kses_post( $formatted ) );
	}

	/**
	 * Extract the numeric metric for a given column.
	 *
	 * @param string                                                            $column The column key.
	 * @param float                                                             $cost   The cost price.
	 * @param float                                                             $sale   The sale price.
	 * @param array{profit: float, markup: float, margin: float, status: string, has_cost: bool} $result The calculation result.
	 *
	 * @return float|null
	 */
	private function metric_for_column( $column, $cost, $sale, $result ) {
		switch ( $column ) {
			case 'wcpp_cost':
				return $cost;
			case 'wcpp_sale':
				return $sale;
			case 'wcpp_profit':
				return $result['profit'];
			case 'wcpp_margin':
				return $result['margin'];
			case 'wcpp_markup':
				return $result['markup'];
			default:
				return null;
		}
	}

	/**
	 * Output a formatted, colored value for a column.
	 *
	 * @param string                                                            $column The column key.
	 * @param float                                                             $cost   The cost price.
	 * @param array{profit: float, markup: float, margin: float, status: string, has_cost: bool} $result The calculation result.
	 *
	 * @return void
	 */
	private function output_value( $column, $cost, $result ) {
		switch ( $column ) {
			case 'wcpp_cost':
				echo '<span class="wcpp-col-value">' . esc_html( wcpp_format_money( $cost ) ) . '</span>';
				break;

			case 'wcpp_profit':
				$color = $this->color_for_status( $result['status'] );
				printf(
					'<span class="wcpp-col-value" style="color:%s;font-weight:600;">%s</span>',
					esc_attr( $color ),
					esc_html( wcpp_format_money( $result['profit'] ) )
				);
				break;

			case 'wcpp_margin':
				$color = $this->color_for_status( $result['status'] );
				printf(
					'<span class="wcpp-col-value" style="color:%s;font-weight:600;">%s</span>',
					esc_attr( $color ),
					esc_html( wcpp_format_percent( $result['margin'] ) )
				);
				break;

			case 'wcpp_markup':
				$color = $this->color_for_status( $result['status'] );
				printf(
					'<span class="wcpp-col-value" style="color:%s;font-weight:600;">%s</span>',
					esc_attr( $color ),
					esc_html( wcpp_format_percent( $result['markup'] ) )
				);
				break;
		}
	}

	/**
	 * Resolve a color for a profit status keyword.
	 *
	 * @param string $status One of positive/zero/negative.
	 *
	 * @return string
	 */
	private function color_for_status( $status ) {
		switch ( $status ) {
			case 'positive':
				return (string) $this->settings->get( 'positive_color', '#22C55E' );
			case 'negative':
				return (string) $this->settings->get( 'negative_color', '#EF4444' );
			default:
				return (string) $this->settings->get( 'zero_color', '#F59E0B' );
		}
	}

	/**
	 * Resolve a color for a numeric value.
	 *
	 * @param float $value The value.
	 *
	 * @return string
	 */
	private function color_for_value( $value ) {
		if ( $value > 0 ) {
			return (string) $this->settings->get( 'positive_color', '#22C55E' );
		}

		if ( $value < 0 ) {
			return (string) $this->settings->get( 'negative_color', '#EF4444' );
		}

		return (string) $this->settings->get( 'zero_color', '#F59E0B' );
	}

	/**
	 * Register the cost and profit columns as sortable.
	 *
	 * @param array<string, string> $columns Existing sortable columns.
	 *
	 * @return array<string, string>
	 */
	public function sortable_columns( $columns ) {
		$columns['wcpp_cost']   = 'wcpp_cost';
		$columns['wcpp_sale']   = 'wcpp_sale';
		$columns['wcpp_profit'] = 'wcpp_profit';
		$columns['wcpp_margin'] = 'wcpp_margin';
		$columns['wcpp_markup'] = 'wcpp_markup';

		return $columns;
	}

	/**
	 * Apply sorting for the custom columns.
	 *
	 * Cost price sorts directly on the meta value. Profit/margin/markup cannot be
	 * sorted purely in SQL without the sale price, so we approximate by sorting on
	 * the cost meta and let the admin scan values; this keeps queries lightweight.
	 *
	 * @param \WP_Query $query The current query.
	 *
	 * @return void
	 */
	public function sort_columns( \WP_Query $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( ! in_array( $orderby, array( 'wcpp_cost', 'wcpp_sale', 'wcpp_profit', 'wcpp_margin', 'wcpp_markup' ), true ) ) {
			return;
		}

		if ( 'wcpp_sale' === $orderby ) {
			$query->set( 'meta_key', '_price' );
			$query->set( 'orderby', 'meta_value_num' );
			return;
		}

		$query->set( 'meta_key', WCPP_COST_META_KEY );
		$query->set( 'orderby', 'meta_value_num' );

		// Only include products that actually have a cost set when sorting.
		$meta_query = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'     => WCPP_COST_META_KEY,
			'compare' => 'EXISTS',
		);
		$query->set( 'meta_query', $meta_query );
	}
}
