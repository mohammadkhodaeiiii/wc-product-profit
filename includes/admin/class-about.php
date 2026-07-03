<?php
/**
 * About page.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the plugin About page with version, documentation and author info.
 *
 * @since 1.0.0
 */
class About {

	/**
	 * The About page slug.
	 */
	public const PAGE_SLUG = 'wc-profit-calculator-about';

	/**
	 * Register the About submenu page under WooCommerce.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'About Profit Calculator', 'wc-product-profit' ),
			__( 'About Profit Calculator', 'wc-product-profit' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the About page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<style>
			.wcpp-about-wrap{max-width:720px;margin-top:20px}.wcpp-about-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 4px 16px rgba(15,23,42,.06);padding:32px;text-align:center}.wcpp-about-card__logo{margin-bottom:16px}.wcpp-about-card__title{font-size:24px;font-weight:700;color:#0f172a;margin:0 0 6px}.wcpp-about-card__version{font-size:14px;color:#64748b;margin:0 0 16px}.wcpp-about-card__desc{font-size:15px;color:#475569;line-height:1.6;margin:0 0 24px}.wcpp-about-card__meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;text-align:left;padding:20px;background:#f8fafc;border-radius:12px;margin-bottom:24px}.wcpp-about-meta-item{display:flex;flex-direction:column;gap:4px}.wcpp-about-meta-item strong{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b}.wcpp-about-meta-item span,.wcpp-about-meta-item a{font-size:14px;color:#1e293b}.wcpp-about-card__features{text-align:left}.wcpp-about-card__features h2{font-size:16px;font-weight:600;margin:0 0 12px;color:#1e293b}.wcpp-about-card__features ul{margin:0;padding-left:20px;color:#475569;line-height:1.8}
		</style>
		<div class="wrap wcpp-about-wrap">
			<div class="wcpp-about-card">
				<div class="wcpp-about-card__logo" aria-hidden="true">
					<svg viewBox="0 0 64 64" width="72" height="72" fill="none" xmlns="http://www.w3.org/2000/svg">
						<rect width="64" height="64" rx="16" fill="#2271B1"/>
						<path d="M18 42V22" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
						<path d="M32 42V18" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
						<path d="M46 42V28" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
						<path d="M14 42H50" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
					</svg>
				</div>
				<h1 class="wcpp-about-card__title"><?php esc_html_e( 'WooCommerce Product Profit Calculator', 'wc-product-profit' ); ?></h1>
				<p class="wcpp-about-card__version">
					<?php
					printf(
						/* translators: %s: plugin version number. */
						esc_html__( 'Version %s', 'wc-product-profit' ),
						esc_html( WCPP_VERSION )
					);
					?>
				</p>
				<p class="wcpp-about-card__desc">
					<?php esc_html_e( 'Track cost prices, profit, markup and margin for every WooCommerce product. Admin-only, HPOS compatible, and built for future Pro extensions.', 'wc-product-profit' ); ?>
				</p>

				<div class="wcpp-about-card__meta">
					<div class="wcpp-about-meta-item">
						<strong><?php esc_html_e( 'Author', 'wc-product-profit' ); ?></strong>
						<span>Mohammadkhodaei</span>
					</div>
					<div class="wcpp-about-meta-item">
						<strong><?php esc_html_e( 'Website', 'wc-product-profit' ); ?></strong>
						<a href="https://yazweb.ir" target="_blank" rel="noopener noreferrer">yazweb.ir</a>
					</div>
					<div class="wcpp-about-meta-item">
						<strong><?php esc_html_e( 'Documentation', 'wc-product-profit' ); ?></strong>
						<a href="https://yazweb.ir" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View documentation', 'wc-product-profit' ); ?>
						</a>
					</div>
					<div class="wcpp-about-meta-item">
						<strong><?php esc_html_e( 'Requirements', 'wc-product-profit' ); ?></strong>
						<span><?php esc_html_e( 'WordPress 4.0+, WooCommerce 2.6+, PHP 5.6+', 'wc-product-profit' ); ?></span>
					</div>
				</div>

				<div class="wcpp-about-card__features">
					<h2><?php esc_html_e( 'Features', 'wc-product-profit' ); ?></h2>
					<ul>
						<li><?php esc_html_e( 'Cost price field on simple and variable products', 'wc-product-profit' ); ?></li>
						<li><?php esc_html_e( 'Live profit, markup and margin calculations', 'wc-product-profit' ); ?></li>
						<li><?php esc_html_e( 'Sortable product list columns', 'wc-product-profit' ); ?></li>
						<li><?php esc_html_e( 'Customizable display and color settings', 'wc-product-profit' ); ?></li>
						<li><?php esc_html_e( 'HPOS compatible — no custom database tables', 'wc-product-profit' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}
