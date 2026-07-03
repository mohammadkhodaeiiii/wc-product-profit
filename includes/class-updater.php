<?php
/**
 * Self-hosted plugin updater.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit;

defined( 'ABSPATH' ) || exit;

/**
 * Connects the plugin to a remote JSON manifest so updates appear in the
 * WordPress dashboard exactly like updates from wordpress.org.
 *
 * The manifest is a single JSON file hosted on the author's site. When a newer
 * "version" is published there, every site running this plugin will see the
 * update notice and can update with one click.
 *
 * @since 1.1.0
 */
class Updater {

	/**
	 * Remote JSON manifest URL.
	 *
	 * @var string
	 */
	private $manifest_url;

	/**
	 * Plugin basename, e.g. "wc-product-profit/wc-product-profit.php".
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Plugin slug, e.g. "wc-product-profit".
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Current installed version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Transient key used to cache the remote manifest.
	 *
	 * @var string
	 */
	private $cache_key = 'wcpp_update_manifest';

	/**
	 * How long (in seconds) to cache the remote manifest.
	 *
	 * @var int
	 */
	private $cache_ttl = 21600; // 6 hours.

	/**
	 * Constructor.
	 *
	 * @param string $manifest_url Remote JSON manifest URL.
	 * @param string $plugin_file  Absolute path to the main plugin file.
	 * @param string $version      Current plugin version.
	 */
	public function __construct( $manifest_url, $plugin_file, $version ) {
		$this->manifest_url = $manifest_url;
		$this->basename     = plugin_basename( $plugin_file );
		$this->slug         = dirname( $this->basename );
		$this->version      = $version;
	}

	/**
	 * Register all update related hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'add_check_update_link' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'maybe_force_check' ) );
	}

	/**
	 * Fetch and cache the remote manifest.
	 *
	 * @param bool $force Whether to bypass the cache.
	 *
	 * @return array<string, mixed>|false
	 */
	private function get_manifest( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached ) {
				return is_array( $cached ) ? $cached : false;
			}
		}

		if ( empty( $this->manifest_url ) ) {
			return false;
		}

		$response = wp_remote_get(
			$this->manifest_url,
			array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			set_transient( $this->cache_key, array(), MINUTE_IN_SECONDS * 30 );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			set_transient( $this->cache_key, array(), MINUTE_IN_SECONDS * 30 );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['version'] ) ) {
			set_transient( $this->cache_key, array(), MINUTE_IN_SECONDS * 30 );
			return false;
		}

		set_transient( $this->cache_key, $data, $this->cache_ttl );

		return $data;
	}

	/**
	 * Inject update data into the WordPress update transient.
	 *
	 * @param object $transient The update_plugins transient.
	 *
	 * @return object
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$manifest = $this->get_manifest();

		if ( ! $manifest ) {
			return $transient;
		}

		$remote_version = isset( $manifest['version'] ) ? $manifest['version'] : '';

		if ( ! $remote_version ) {
			return $transient;
		}

		$has_update = version_compare( $this->version, $remote_version, '<' );

		$item = array(
			'id'            => $this->basename,
			'slug'          => $this->slug,
			'plugin'        => $this->basename,
			'new_version'   => $remote_version,
			'url'           => isset( $manifest['homepage'] ) ? $manifest['homepage'] : '',
			'package'       => isset( $manifest['download_url'] ) ? $manifest['download_url'] : '',
			'icons'         => isset( $manifest['icons'] ) ? (array) $manifest['icons'] : array(),
			'banners'       => isset( $manifest['banners'] ) ? (array) $manifest['banners'] : array(),
			'tested'        => isset( $manifest['tested'] ) ? $manifest['tested'] : '',
			'requires_php'  => isset( $manifest['requires_php'] ) ? $manifest['requires_php'] : '',
			'requires'      => isset( $manifest['requires'] ) ? $manifest['requires'] : '',
		);

		$item_object = (object) $item;

		if ( $has_update ) {
			if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
				$transient->response = array();
			}
			$transient->response[ $this->basename ] = $item_object;
		} else {
			if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
				$transient->no_update = array();
			}
			$transient->no_update[ $this->basename ] = $item_object;
		}

		return $transient;
	}

	/**
	 * Provide the plugin information popup ("View details") content.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Arguments passed to the API.
	 *
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$manifest = $this->get_manifest();

		if ( ! $manifest ) {
			return $result;
		}

		$info = array(
			'name'          => isset( $manifest['name'] ) ? $manifest['name'] : 'WooCommerce Product Profit Calculator',
			'slug'          => $this->slug,
			'version'       => isset( $manifest['version'] ) ? $manifest['version'] : $this->version,
			'author'        => isset( $manifest['author'] ) ? $manifest['author'] : '',
			'homepage'      => isset( $manifest['homepage'] ) ? $manifest['homepage'] : '',
			'download_link' => isset( $manifest['download_url'] ) ? $manifest['download_url'] : '',
			'requires'      => isset( $manifest['requires'] ) ? $manifest['requires'] : '',
			'tested'        => isset( $manifest['tested'] ) ? $manifest['tested'] : '',
			'requires_php'  => isset( $manifest['requires_php'] ) ? $manifest['requires_php'] : '',
			'last_updated'  => isset( $manifest['last_updated'] ) ? $manifest['last_updated'] : '',
			'sections'      => isset( $manifest['sections'] ) ? (array) $manifest['sections'] : array(),
			'banners'       => isset( $manifest['banners'] ) ? (array) $manifest['banners'] : array(),
			'icons'         => isset( $manifest['icons'] ) ? (array) $manifest['icons'] : array(),
		);

		return (object) $info;
	}

	/**
	 * Clear the cached manifest after an update completes.
	 *
	 * @param \WP_Upgrader $upgrader The upgrader instance.
	 * @param array        $options  Upgrade options.
	 *
	 * @return void
	 */
	public function clear_cache( $upgrader, $options ) {
		if ( ! is_array( $options ) ) {
			return;
		}

		$is_plugin_update = isset( $options['type'], $options['action'] )
			&& 'plugin' === $options['type']
			&& 'update' === $options['action'];

		if ( $is_plugin_update ) {
			delete_transient( $this->cache_key );
		}
	}

	/**
	 * Add a "Check for updates" link to the plugin row.
	 *
	 * @param array  $links Existing row meta links.
	 * @param string $file  Plugin basename for the current row.
	 *
	 * @return array
	 */
	public function add_check_update_link( $links, $file ) {
		if ( $file !== $this->basename ) {
			return $links;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array( 'wcpp_force_update_check' => '1' ),
				admin_url( 'plugins.php' )
			),
			'wcpp_force_update_check'
		);

		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for updates', 'wc-product-profit' ) . '</a>';

		return $links;
	}

	/**
	 * Force a fresh manifest check when the user clicks the link.
	 *
	 * @return void
	 */
	public function maybe_force_check() {
		if ( ! isset( $_GET['wcpp_force_update_check'] ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wcpp_force_update_check' ) ) {
			return;
		}

		delete_transient( $this->cache_key );
		$this->get_manifest( true );
		delete_site_transient( 'update_plugins' );

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}
}
