=== WooCommerce Product Profit Calculator ===
Contributors: mohammadkhodaei
Author URI: https://yazweb.ir
Plugin URI: https://yazweb.ir
Tags: woocommerce, profit, margin, markup, cost price
Requires at least: 4.0
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track cost prices and automatically calculate profit, markup and margin for every WooCommerce product. Admin-only, HPOS compatible.

== Description ==

**WooCommerce Product Profit Calculator** (ماشین حساب سود ووکامرس) lets store managers record the purchase (cost) price for each product and instantly see profit, markup percentage and profit margin — all inside the WordPress admin.

No data is shown on the storefront. Everything stays private to administrators.

Developed by Mohammadkhodaei — [yazweb.ir](https://yazweb.ir).

= Key Features =

* **Cost price field** on simple and variable products (General → Pricing tab)
* **Live profit summary card** that updates as you type — no page reload needed
* **Product list columns** for cost price, sale price, profit, margin and markup
* **Sortable columns** for quick analysis
* **Full variable product support** — each variation has its own cost and calculations
* **Settings page** under WooCommerce → Profit Calculator
* **Customizable colors** for positive, break-even and negative profit
* **HPOS compatible** — works with WooCommerce High-Performance Order Storage
* **No custom database tables** — all data stored in post meta
* **Translation ready** — includes a .pot file; Persian (fa_IR) translation provided

= Calculations =

* **Profit** = Sale Price − Cost Price
* **Markup** = ((Sale − Cost) / Cost) × 100
* **Margin** = ((Sale − Cost) / Sale) × 100

Division by zero is handled gracefully.

= Requirements =

* WordPress 4.0 or higher
* WooCommerce 2.6 or higher
* PHP 5.6 or higher

== Installation ==

1. Upload the `wc-product-profit` folder to the `/wp-content/plugins/` directory, or install through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen.
3. Make sure WooCommerce is installed and active.
4. Go to **WooCommerce → Profit Calculator** to configure display options.
5. Edit any product and enter a **Cost price** in the General → Pricing section.

== Frequently Asked Questions ==

= Does this plugin show profit data on the storefront? =

No. All profit information is visible only in the WordPress admin area.

= Does it work with variable products? =

Yes. Each variation has its own cost price field and live profit summary.

= Is it compatible with HPOS? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage and does not create custom order tables.

= Where is the cost price stored? =

In post meta with the key `_wc_cost_price`. No custom database tables are created.

= What happens when I uninstall the plugin? =

All `_wc_cost_price` meta entries and plugin settings are permanently deleted.

== Screenshots ==

1. Cost price field with live profit summary card on the product edit screen.
2. Profit columns on the WooCommerce products list.
3. Settings page under WooCommerce menu.
4. About page with plugin information.

== Changelog ==

= 1.0.0 =
* Self-hosted automatic update system (updates show in the WordPress dashboard).
* Broad compatibility: WordPress 4.0+, WooCommerce 2.6+, PHP 5.6+.
* Initial release.
* Cost price field for simple and variable products.
* Live profit, markup and margin calculations.
* Sortable product list columns.
* Settings page with color pickers and display options.
* About page.
* HPOS compatibility declaration.
* Persian (fa_IR) translation.
* Uninstall cleanup for all plugin data.

== Upgrade Notice ==

= 1.0.0 =
Initial release of WooCommerce Product Profit Calculator.
