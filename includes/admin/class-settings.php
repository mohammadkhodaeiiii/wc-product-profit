<?php
/**
 * Plugin settings page.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Admin;

use WC_Product_Profit\Helpers\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the "Profit Calculator" settings page under the
 * WooCommerce menu using the WordPress Settings API.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * The option name used to store all settings.
	 */
	private const OPTION_NAME = 'wcpp_settings';

	/**
	 * The settings page slug.
	 */
	public const PAGE_SLUG = 'wc-profit-calculator';

	/**
	 * Retrieve a single setting value.
	 *
	 * @param string $key           The setting key.
	 * @param mixed  $default_value The fallback value.
	 *
	 * @return mixed
	 */
	public function get( $key, $default_value = null ) {
		return wcpp_get_setting( $key, $default_value );
	}

	/**
	 * Register the settings submenu page under WooCommerce.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Profit Calculator', 'wc-product-profit' ),
			__( 'Profit Calculator', 'wc-product-profit' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings, sections and fields with the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'wcpp_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'wcpp_display_section',
			__( 'Display options', 'wc-product-profit' ),
			array( $this, 'render_display_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'show_markup',
			__( 'Show markup', 'wc-product-profit' ),
			array( $this, 'render_checkbox_field' ),
			self::PAGE_SLUG,
			'wcpp_display_section',
			array(
				'key'   => 'show_markup',
				'label' => __( 'Display the markup percentage in cards and columns.', 'wc-product-profit' ),
			)
		);

		add_settings_field(
			'show_margin',
			__( 'Show margin', 'wc-product-profit' ),
			array( $this, 'render_checkbox_field' ),
			self::PAGE_SLUG,
			'wcpp_display_section',
			array(
				'key'   => 'show_margin',
				'label' => __( 'Display the profit margin percentage in cards and columns.', 'wc-product-profit' ),
			)
		);

		add_settings_field(
			'decimals',
			__( 'Decimal places', 'wc-product-profit' ),
			array( $this, 'render_decimals_field' ),
			self::PAGE_SLUG,
			'wcpp_display_section'
		);

		add_settings_section(
			'wcpp_color_section',
			__( 'Colors', 'wc-product-profit' ),
			array( $this, 'render_color_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'positive_color',
			__( 'Positive profit color', 'wc-product-profit' ),
			array( $this, 'render_color_field' ),
			self::PAGE_SLUG,
			'wcpp_color_section',
			array(
				'key'     => 'positive_color',
				'default' => '#22C55E',
			)
		);

		add_settings_field(
			'zero_color',
			__( 'Break-even color', 'wc-product-profit' ),
			array( $this, 'render_color_field' ),
			self::PAGE_SLUG,
			'wcpp_color_section',
			array(
				'key'     => 'zero_color',
				'default' => '#F59E0B',
			)
		);

		add_settings_field(
			'negative_color',
			__( 'Negative profit color', 'wc-product-profit' ),
			array( $this, 'render_color_field' ),
			self::PAGE_SLUG,
			'wcpp_color_section',
			array(
				'key'     => 'negative_color',
				'default' => '#EF4444',
			)
		);
	}

	/**
	 * Sanitize all settings before they are saved.
	 *
	 * @param mixed $input The raw submitted settings.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		return array(
			'show_markup'    => Sanitizer::yes_no( isset($input['show_markup']) ? $input['show_markup'] : 'no' ),
			'show_margin'    => Sanitizer::yes_no( isset($input['show_margin']) ? $input['show_margin'] : 'no' ),
			'positive_color' => Sanitizer::color( isset($input['positive_color']) ? $input['positive_color'] : '', '#22C55E' ),
			'zero_color'     => Sanitizer::color( isset($input['zero_color']) ? $input['zero_color'] : '', '#F59E0B' ),
			'negative_color' => Sanitizer::color( isset($input['negative_color']) ? $input['negative_color'] : '', '#EF4444' ),
			'decimals'       => Sanitizer::decimals( isset($input['decimals']) ? $input['decimals'] : 0 ),
		);
	}

	/**
	 * Render the display section description.
	 *
	 * @return void
	 */
	public function render_display_section() {
		echo '<p>' . esc_html__( 'Choose which profit metrics to display across the admin.', 'wc-product-profit' ) . '</p>';
	}

	/**
	 * Render the color section description.
	 *
	 * @return void
	 */
	public function render_color_section() {
		echo '<p>' . esc_html__( 'Customize the colors used to highlight profit values.', 'wc-product-profit' ) . '</p>';
	}

	/**
	 * Render a yes/no checkbox field.
	 *
	 * @param array<string, string> $args Field arguments (key, label).
	 *
	 * @return void
	 */
	public function render_checkbox_field( $args ) {
		$key     = $args['key'];
		$value   = $this->get( $key, 'yes' );
		$name    = self::OPTION_NAME . '[' . $key . ']';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="yes" <?php checked( 'yes', $value ); ?> />
			<?php echo esc_html( $args['label'] ); ?>
		</label>
		<?php
	}

	/**
	 * Render the decimals number field.
	 *
	 * @return void
	 */
	public function render_decimals_field() {
		$value = (int) $this->get( 'decimals', 0 );
		$name  = self::OPTION_NAME . '[decimals]';
		?>
		<input type="number" min="0" max="4" step="1" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" class="small-text" />
		<p class="description"><?php esc_html_e( 'Number of decimal places for percentage values (0 to 4).', 'wc-product-profit' ); ?></p>
		<?php
	}

	/**
	 * Render a color picker field.
	 *
	 * @param array<string, string> $args Field arguments (key, default).
	 *
	 * @return void
	 */
	public function render_color_field( $args ) {
		$key     = $args['key'];
		$default = $args['default'];
		$value   = $this->get( $key, $default );
		$name    = self::OPTION_NAME . '[' . $key . ']';
		?>
		<input type="text" class="wcpp-color-picker" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $default ); ?>" />
		<?php
	}

	/**
	 * Render the full settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){ $(".wcpp-color-picker").wpColorPicker(); });'
		);
		?>
		<style>
			.wcpp-settings-wrap{max-width:780px}.wcpp-settings-title{display:flex;align-items:center;gap:8px;font-size:23px;font-weight:600;margin-bottom:20px}.wcpp-settings-title .dashicons{font-size:28px;width:28px;height:28px;color:#2271b1}.wcpp-settings-form{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.06);padding:20px 24px 8px}.wcpp-settings-form .form-table th{padding-left:0;font-weight:600;color:#1e293b}.wcpp-settings-form .form-table td{padding-left:0}.wcpp-settings-form .submit{padding-left:0;margin-top:8px}
		</style>
		<div class="wrap wcpp-settings-wrap">
			<h1 class="wcpp-settings-title">
				<span class="dashicons dashicons-chart-line"></span>
				<?php esc_html_e( 'Profit Calculator Settings', 'wc-product-profit' ); ?>
			</h1>
			<form action="options.php" method="post" class="wcpp-settings-form">
				<?php
				settings_fields( 'wcpp_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save settings', 'wc-product-profit' ) );
				?>
			</form>
		</div>
		<?php
	}
}
