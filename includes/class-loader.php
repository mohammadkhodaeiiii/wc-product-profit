<?php
/**
 * Class autoloader and hook loader.
 *
 * @package WC_Product_Profit
 */

namespace WC_Product_Profit;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a lightweight autoloader that maps namespaced classes to
 * WordPress-style "class-*.php" files, and collects WordPress hooks so they can
 * be registered in a single place.
 *
 * Mapping example:
 *   \WC_Product_Profit\Admin\Product_Fields => includes/admin/class-product-fields.php
 *
 * @since 1.0.0
 */
class Loader {

	/**
	 * The collection of actions registered with WordPress.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $actions = array();

	/**
	 * The collection of filters registered with WordPress.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $filters = array();

	/**
	 * Register the PSR-4 style autoloader.
	 *
	 * @return void
	 */
	public static function register_autoloader() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload a class file based on its fully-qualified name.
	 *
	 * @param string $class_name The fully-qualified class name.
	 *
	 * @return void
	 */
	public static function autoload( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$segments = explode( '\\', $relative );

		$class_part = array_pop( $segments );
		$file_name  = 'class-' . str_replace( '_', '-', strtolower( $class_part ) ) . '.php';

		$sub_path = '';
		if ( ! empty( $segments ) ) {
			$sub_path = strtolower( implode( '/', $segments ) ) . '/';
		}

		$path = WCPP_PLUGIN_DIR . 'includes/' . $sub_path . $file_name;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string $hook          The name of the WordPress action.
	 * @param object $component      A reference to the instance of the object.
	 * @param string $callback       The name of the method on the component.
	 * @param int    $priority       Optional. The priority. Default 10.
	 * @param int    $accepted_args  Optional. The number of accepted arguments. Default 1.
	 *
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string $hook          The name of the WordPress filter.
	 * @param object $component      A reference to the instance of the object.
	 * @param string $callback       The name of the method on the component.
	 * @param int    $priority       Optional. The priority. Default 10.
	 * @param int    $accepted_args  Optional. The number of accepted arguments. Default 1.
	 *
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Utility to register the actions and hooks into a single collection.
	 *
	 * @param array<int, array<string, mixed>> $hooks         The collection of hooks.
	 * @param string                           $hook          The name of the WordPress hook.
	 * @param object                           $component     A reference to the instance of the object.
	 * @param string                           $callback      The name of the method on the component.
	 * @param int                              $priority      The priority.
	 * @param int                              $accepted_args The number of accepted arguments.
	 *
	 * @return array<int, array<string, mixed>> The updated collection of hooks.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}

// Register the autoloader as soon as this file is loaded.
Loader::register_autoloader();
