<?php

/**
 * The VGSR Entity Plugin
 *
 * @package VGSR Entity
 * @subpackage Main
 */

/**
 * Plugin Name:       VGSR Entity
 * Description:       Custom post type management for community entities
 * Plugin URI:        https://github.com/vgsr/vgsr-entity
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins
 * Version:           2.0.0-beta-2
 * Text Domain:       vgsr-entity
 * Domain Path:       /languages/
 * GitHub Plugin URI: vgsr/vgsr-entity
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity' ) ) :
/**
 * Main Plugin Entities Class
 *
 * @since 1.0.0
 */
final class VGSR_Entity {

	/**
	 * Holds all built-in entity names
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $entities = array();

	/** Singleton *************************************************************/

	/**
	 * Main VGSR Entities Instance
	 *
	 * Insures that only one instance of VGSR_Entity exists in memory
	 * at any one time. Also prevents needing to define globals all over
	 * the place.
	 *
	 * @since 1.0.0
	 *
	 * @see vgsr_entity()
	 * @return The one true VGSR_Entity
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been ran previously
		if ( null === $instance ) {
			$instance = new VGSR_Entity;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		// Always return the instance
		return $instance;
	}

	/**
	 * Construct the main plugin class
	 *
	 * @since 1.0.0
	 */
	public function __construct() { /* do nothing here */ }

	/**
	 * Define default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version       = '2.0.0-beta-2';
		$this->db_version    = '20000';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->plugin_url    = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir  = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url  = trailingslashit( $this->plugin_url . 'includes' );

		// Assets
		$this->assets_dir    = trailingslashit( $this->plugin_dir . 'assets' );
		$this->assets_url    = trailingslashit( $this->plugin_url . 'assets' );

		// Extensions
		$this->extend_dir    = trailingslashit( $this->includes_dir . 'extend' );
		$this->extend_url    = trailingslashit( $this->includes_url . 'extend' );

		// Templates
		$this->themes_dir    = trailingslashit( $this->includes_dir . 'templates' );
		$this->themes_url    = trailingslashit( $this->includes_url . 'templates' );

		// Languages
		$this->lang_dir      = trailingslashit( $this->plugin_dir . 'languages' );

		/** Identifiers *******************************************************/

		$this->archive_status_id = apply_filters( 'vgsr_entity_archive_status', 'archive' );

		/** Misc **************************************************************/

		$this->extend        = new stdClass();
		$this->menu_position = 35;
		$this->base_year     = 1950; // 'Al sinds 1950!'
	}

	/**
	 * Include the required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		// Core
		require( $this->includes_dir . 'actions.php'      );
		require( $this->includes_dir . 'extend.php'       );
		require( $this->includes_dir . 'functions.php'    );
		require( $this->includes_dir . 'sub-actions.php'  );
		require( $this->includes_dir . 'template.php'     );
		require( $this->includes_dir . 'theme-compat.php' );

		// Admin
		if ( is_admin() ) {
			require( $this->includes_dir . 'settings.php' );
		}
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Plugin
		add_action( 'plugins_loaded',         array( $this, 'load_textdomain'  ) );
		add_action( 'vgsr_entity_admin_init', array( $this, 'check_for_update' ) );

		// Entities
		add_action( 'plugins_loaded',   array( $this, 'setup_entities'         ) );
		add_action( 'vgsr_entity_init', array( $this, 'register_post_statuses' ) );

		// Admin & Widgets
		add_action( 'admin_menu',   array( $this, 'admin_menu'   ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		// Query
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

		// Adjacent entity
		add_filter( 'get_previous_post_where', array( $this, 'adjacent_post_where' ), 10, 5 );
		add_filter( 'get_next_post_where',     array( $this, 'adjacent_post_where' ), 10, 5 );
		add_filter( 'get_previous_post_sort',  array( $this, 'adjacent_post_sort'  ), 10, 2 );
		add_filter( 'get_next_post_sort',      array( $this, 'adjacent_post_sort'  ), 10, 2 );

		// Activation
		add_action( "activate_{$this->basename}", array( $this, 'flush_rewrite_rules' ) );
	}

	/** Entities *******************************************************/

	/**
	 * Setup the registered entities
	 *
	 * @since 2.0.0
	 *
	 * @uses apply_filters() Calls 'vgsr_entity_entities'
	 */
	public function setup_entities() {

		// Load base classes
		require_once( $this->includes_dir . 'classes/class-vgsr-entity-type.php'       );
		require_once( $this->includes_dir . 'classes/class-vgsr-entity-type-admin.php' );

		// Define the entities as type => class_name
		$entities = apply_filters( 'vgsr_entity_entities', array(
			'bestuur' => 'VGSR_Bestuur',
			'dispuut' => 'VGSR_Dispuut',
			'kast'    => 'VGSR_Kast',
		) );

		// Walk registered entities
		foreach ( $entities as $type => $class ) {

			// Load class file
			$class_file = $this->includes_dir . 'classes/class-' . str_replace('_', '-', strtolower( $class ) ) . '.php';
			if ( file_exists( $class_file ) ) {
				require_once( $class_file );
			}

			// Load entity class
			if ( ! array_key_exists( $type, $this->entities ) && class_exists( $class ) ) {
				$this->entities[ $type ] = new $class( $type );
			}
		}
	}

	/**
	 * Return the registered entity types
	 *
	 * @since 2.0.0
	 *
	 * @return array Registered entity types
	 */
	public function get_entities() {
		return array_keys( $this->entities );
	}

	/**
	 * Magic check for isset(). Handles protected entity objects
	 *
	 * @since 2.0.0
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {

		// Check for protected entity object when present
		if ( array_key_exists( $key, $this->entities ) ) {
			return true;
		} else {
			return isset( $this->{$key} );
		}
	}

	/**
	 * Magic getter. Handles protected entity objects
	 *
	 * @since 2.0.0
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {

		// Return protected entity object when present
		if ( array_key_exists( $key, $this->entities ) ) {
			return $this->entities[ $key ];

		// Return registered types for 'entities'
		} elseif ( 'entities' === $key ) {
			return $this->get_entities();

		// Key is present
		} elseif ( isset( $this->{$key} ) ) {
			return $this->{$key};

		// Default
		} else {
			return null;
		}
	}

	/**
	 * Magic setter. Handles protected entity objects
	 *
	 * @since 2.0.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {

		// Prevent overwriting entity objects when present
		if ( ! array_key_exists( $key, $this->entities ) && 'entities' !== $key ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Magic unsetter. Handles protected entity objects
	 *
	 * @since 2.0.0
	 *
	 * @param string $key
	 */
	public function __unset( $key ) {

		// Prevent overwriting entity objects when present
		if ( ! array_key_exists( $key, $this->entities ) && 'entities' !== $key ) {
			unset( $this->{$key} );
		}
	}

	/** Plugin *********************************************************/

	/**
	 * Refresh permalink structure on activation
	 *
	 * @since 1.0.0
	 */
	public function flush_rewrite_rules() {

		/**
		 * On activation, the 'init' hook was already passed, so
		 * our post types have not been registered at this point.
		 */

		// Setup entities
		$this->setup_entities();

		// Call post type registration
		foreach ( $this->entities as $type_obj ) {
			$type_obj->register_post_type();
		}

		// Flush rules only on activation
		flush_rewrite_rules();
	}

	/**
	 * Loads the textdomain file for this plugin
	 *
	 * @since 1.0.0
	 *
	 * @return mixed Text domain if found, else boolean false
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$mofile = sprintf( 'vgsr-entity-%s.mo', get_locale() );

		// Setup paths to current locale file
		$mofile_local  = $this->plugin_dir . 'languages/' . $mofile;
		$mofile_global = WP_LANG_DIR . '/vgsr-entity/' . $mofile;

		// Look in global /wp-content/languages/vgsr-entity folder
		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( 'vgsr-entity', $mofile_global );

		// Look in local /wp-content/plugins/vgsr-entity/languages/ folder
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( 'vgsr-entity', $mofile_local );
		}

		// Nothing found
		return false;
	}

	/**
	 * Check if the plugin needs to run the update logic
	 *
	 * @since 2.0.0
	 */
	public function check_for_update() {

		// Get current version in DB
		$version = get_site_option( '_vgsr_entity_version', false );

		// Run updater when we're updating
		if ( ! $version || version_compare( $version, $this->db_version, '<' ) ) {
			$this->version_updater( $version );
		}
	}

	/**
	 * Run logic when updating the plugin
	 *
	 * @since 2.0.0
	 *
	 * @param string $version Version number
	 */
	public function version_updater( $version = '' ) {

		// Pre-2.0.0
		if ( false === $version ) {
			vgsr_entity_update_20000();
		}

		// Update current version in DB
		update_site_option( '_vgsr_entity_version', $this->db_version );
	}

	/** Posts **********************************************************/

	/**
	 * Register plugin post statuses
	 *
	 * @since 2.0.0
	 *
	 * @uses apply_filters() Calls 'vgsr_entity_register_archive_post_status'
	 */
	public function register_post_statuses() {

		// Get whether the current user has access
		$access = vgsr_entity_check_access();

		/** Archive ****************************************************/

		register_post_status( vgsr_entity_get_archive_status_id(),
			(array) apply_filters( 'vgsr_entity_register_archive_post_status', array(
				'label'               => esc_html__( 'Archived', 'vgsr-entity' ),
				'label_count'         => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>', 'vgsr-entity' ),

				// Limit access to archived posts
				'exclude_from_search' => ! $access,
				'public'              => $access,
		) ) );
	}

	/** Admin **********************************************************/

	/**
	 * Filters the admin menu to add a separator
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {

		// Bail when there are no entities registered
		if ( empty( $this->entities ) )
			return;

		$this->add_separator( $this->menu_position - 1 );
	}

	/**
	 * Runs through the admin menu to add a separator at given position
	 *
	 * The separator name can affect the order of the separators,
	 * therefor the separator{$index} naming is changed.
	 *
	 * @link http://wordpress.stackexchange.com/questions/2666/add-a-separator-to-the-admin-menu
	 *
	 * @since 1.0.0
	 *
	 * @global array $menu
	 *
	 * @param int $pos The position after which to add the sep
	 */
	public function add_separator( $pos ) {
		global $menu;
		$index = 1;

		foreach( $menu as $offset => $item ) {
			if ( substr( $item[2], 0, 9 ) == 'separator' )
				$index++;

			if ( $offset >= $pos ) {
				$menu[ $pos ] = array( '', 'read', "separator-pos{$index}", '', 'wp-menu-separator' );
				break;
			}
		}

		ksort( $menu );
	}

	/**
	 * Initiate entity widgets
	 *
	 * @since 1.0.0
	 */
	public function widgets_init() {

		// Include files
		require_once( $this->includes_dir . 'classes/class-vgsr-entity-menu-widget.php' );

		// Register widgets
		register_widget( 'VGSR_Entity_Menu_Widget' );
	}

	/** Theme **********************************************************/

	/**
	 * Return all entity parent page ids
	 *
	 * @since 1.0.0
	 *
	 * @return array Entity parents. Keys are post type, values are post IDs
	 */
	public function get_entity_parents() {

		// Define local variable
		$parents = array();

		foreach ( $this->get_entities() as $type ) {
			$parents[ $type ] = $this->{$type}->parent;
		}

		return $parents;
	}

	/** Queries ********************************************************/

	/**
	 * Modify the post query vars
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {

		// Force entity ordering by menu_order
		if ( isset( $query->query_vars['post_type'] ) && in_array( $query->query_vars['post_type'], $this->get_entities() ) ) {
			$query->query_vars['orderby'] = 'menu_order';

			// Define sort order
			if ( ! isset( $query->query_vars['order'] ) ) {
				$query->query_vars['order'] = 'DESC';
			}
		}
	}

	/**
	 * Modify the adjacent's post WHERE query clause
	 *
	 * Custom entity order is assumed to be set through the menu_order 
	 * parameter.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Added support for the `$taxonomy` and `$post` params as per WP 4.4
	 *
	 * @global $wpdb
	 * 
	 * @param string $where WHERE clause
	 * @param bool $in_same_term 
	 * @param array $excluded_terms
	 * @param string $taxonomy
	 * @param WP_Post $post Post object. Added in WP 4.4
	 * @return string WHERE clause
	 */
	public function adjacent_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post = null ) {

		// Get the post
		$post = get_post( $post );

		// When this is an entity
		if ( is_entity( $post ) ) {
			global $wpdb;

			$previous = ( 'get_previous_post_where' === current_filter() );
			$op = $previous ? '<' : '>';

			/**
			 * Replace the `p.post_date` WHERE clause with a comparison based
			 * on the menu order.
			 */
			$original = $wpdb->prepare( "WHERE p.post_date $op %s",  $post->post_date  );
			$improved = $wpdb->prepare( "WHERE p.menu_order $op %s", $post->menu_order );
			$where    = str_replace( $original, $improved, $where );
		}

		return $where;
	}

	/**
	 * Modify the adjacent post's ORDER BY query clause
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Added support for the `$post` param as per WP 4.4
	 * 
	 * @param string $order_by ORDER BY clause
	 * @param WP_Post $post Post object. Added in WP 4.4
	 * @return string ORDER BY clause
	 */
	public function adjacent_post_sort( $order_by, $post = null ) {

		// Get the post
		$post = get_post( $post );

		// When this is an entity
		if ( is_entity( $post ) ) {

			// Order by the post menu order
			$order_by = str_replace( 'p.post_date', 'p.menu_order', $order_by );
		}

		return $order_by;
	}

	/** Wrappers *******************************************************/

	/**
	 * Wrapper for a single entity's meta getter
	 *
	 * @see VGSR_Entity_Type::meta()
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post|int $post Optional. Post ID or object. Defaults to the current post.
	 * @return array Entity meta
	 */
	public function get_meta( $post = 0 ) {
		$post = get_post( $post );
		$meta = array();

		// Get entity type from post
		if ( $post && $type = vgsr_entity_get_type( $post, true ) ) {
			$meta = $type->meta( $post, 'display' );
		}

		return $meta;
	}
}

/**
 * Return the single instance of VGSR_Entity
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $entity = vgsr_entity(); ?>
 *
 * @since 1.0.0
 *
 * @uses VGSR_Entity
 * @return The one single VGSR Entity
 */
function vgsr_entity() {
	return VGSR_Entity::instance();
}

// Fire it up!
vgsr_entity();

endif; // class_exists
