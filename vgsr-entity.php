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
 * Version:           1.0.2
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
	 * @staticvar object $instance
	 * @uses VGSR_Entity::setup_globals() Setup the globals needed
	 * @uses VGSR_Entity::includes() Include the required files
	 * @uses VGSR_Entity::setup_actions() Setup the hooks and actions
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

		$this->version       = '1.0.2';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->plugin_url    = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir  = trailingslashit( $this->plugin_dir . 'includes'  );
		$this->includes_url  = trailingslashit( $this->plugin_url . 'includes'  );

		// Languages
		$this->lang_dir      = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->menu_position = 35;

		// Define the entities as class_name => post_type
		$this->entities      = array(
			'VGSR_Entity_Bestuur' => 'bestuur',
			'VGSR_Entity_Dispuut' => 'dispuut',
			'VGSR_Entity_Kast'    => 'kast',
		);
	}

	/**
	 * Include the required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'       );
		require( $this->includes_dir . 'functions.php'     );
		require( $this->includes_dir . 'template-tags.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Plugin
		add_action( 'plugins_loaded',     array( $this, 'load_textdomain'  ) );

		// Entities
		add_action( 'vgsr_entity_loaded', array( $this, 'setup_entities'   ) );

		add_action( 'admin_menu',         array( $this, 'admin_menu'       ) );
		add_action( 'widgets_init',       array( $this, 'widgets_init'     ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts'  ) );
		add_action( 'template_include',   array( $this, 'template_include' ) );

		// Adjacent entity
		add_filter( 'get_previous_post_where',  array( $this, 'adjacent_post_where'  ), 10, 3 );
		add_filter( 'get_next_post_where',      array( $this, 'adjacent_post_where'  ), 10, 3 );

		register_activation_hook( $this->file, array( $this, 'flush_rewrite_rules' ) );
	}

	/** Functions ******************************************************/

	/**
	 * Return the registered entities
	 *
	 * @since 1.1.0
	 *
	 * @return array Registered entities as `array( class => type )`
	 */
	public function get_entities() {
		return $this->entities;
	}

	/**
	 * Setup the registered entities
	 *
	 * @since 1.1.0
	 */
	public function setup_entities() {

		// Base class
		require_once( $this->includes_dir . "classes/class-vgsr-entity-base.php" );

		// Walk registered entities
		foreach ( $this->entities as $class => $type ) {

			// Setup entity
			require_once( $this->includes_dir . "classes/class-vgsr-entity-{$type}.php" );

			if ( ! isset( $type->{$type} ) ) {
				$this->{$type} = new $class;
			}
		}
	}

	/**
	 * Refresh permalink structure on activation
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Entity::setup_entities()
	 * @uses VGSR_Entity_Base::register_post_type()
	 * @uses flush_rewrite_rules()
	 */
	public function flush_rewrite_rules() {

		/**
		 * On activation, the 'init' hook was already passed, so
		 * our post types have not been registered at this point.
		 */

		// Setup entities
		$this->setup_entities();

		// Call post type registration
		foreach ( $this->entities as $type ) {
			$this->{$type}->register_post_type();
		}

		// Flush rules only on activation
		flush_rewrite_rules();
	}

	/**
	 * Loads the textdomain file for this plugin
	 *
	 * @since 1.0.0
	 *
	 * @uses load_textdomain() To insert the matched language file
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
	 * Filters the admin menu to add a separator
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Entity::add_separator()
	 */
	public function admin_menu() {
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
	 * @param int $pos The position after which to add the sep
	 */
	public function add_separator( $pos ) {
		global $menu;
		$index = 1;

		foreach( $menu as $offset => $item ) {
			if ( substr( $item[2], 0, 9 ) == 'separator' )
				$index++;

			if ( $offset >= $pos ) {
				$menu[$pos] = array( '', 'read', "separator-pos{$index}", '', 'wp-menu-separator' );
				break;
			}
		}

		ksort( $menu );
	}

	/**
	 * Enqueue page scripts
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Entity::get_entitiy_parent_id()
	 * @uses wp_register_style()
	 * @uses wp_enqueue_style()
	 */
	public function enqueue_scripts() {
		global $post;

		// Bail when $post is not set
		if ( ! isset( $post ) || ! $post )
			return;

		// Bail when not on entity parent page
		if ( ! in_array( $post->post_type, $this->entities ) && ! in_array( $post->ID, $this->get_entity_parent_ids() ) )
			return;

		wp_register_style( 'vgsr-entity', $this->includes_url . 'assets/css/style.css' );
		wp_enqueue_style(  'vgsr-entity' );
	}

	/**
	 * Return all entity parent page IDs
	 *
	 * @since 1.0.0
	 */
	public function get_entity_parent_ids() {
		$parents = array();
		foreach ( $this->entities as $post_type ) {
			$parents[ $post_type ] = get_option( $this->{$post_type}->parent_option_key );
		}

		return $parents;
	}

	/**
	 * Initiate entity widgets
	 *
	 * @since 1.0.0
	 *
	 * @uses register_widget()
	 */
	public function widgets_init() {

		// Include files
		require_once( $this->includes_dir . 'classes/class-vgsr-entity-menu-widget.php' );

		// Register widgets
		register_widget( 'VGSR_Entity_Menu_Widget' );
	}

	/**
	 * Intercept the template loader to load the entity template
	 *
	 * @since 1.0.0
	 *
	 * @uses get_post_type()
	 * @uses is_singular()
	 * @uses get_query_template()
	 *
	 * @param string $template The current template match
	 * @return string $template
	 */
	public function template_include( $template ) {

		// Get the current post type
		$post_type = get_post_type();

		// Entity requested
		if ( in_array( $post_type, $this->entities ) && is_singular( $post_type ) ) {

			/**
			 * Define our own tempate stack
			 *
			 * The template(s) should be defined in the current child 
			 * or parent theme.
			 */
			$templates = array(

				// Post-type specific template
				"single-{$post_type}.php",
				"{$post_type}.php",

				// Default to page and single template
				'page.php',
				'single.php'
			);

			// Generic entity template
			if ( ! post_type_exists( 'entity' ) ) {
				array_splice( $templates, 2, 0, 'single-entity.php' );
			}

			// Query for a suitable template
			$template = get_query_template( $post_type, $templates );
		}

		return $template;
	}

	/**
	 * Outputs the entity meta list
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'vgsr_{$post_type}_meta' with the meta array
	 */
	public function entity_meta() {
		global $post;

		// Setup meta list
		$list = '';

		// Loop over all meta fields
		foreach ( apply_filters( "vgsr_{$post->post_type}_display_meta", array() ) as $key => $meta ) {

			// Merge meta args
			$meta = wp_parse_args( $meta, array(
				'icon'   => '',
				'before' => '',
				'value'  => '',
				'after'  => ''
			) );

			$list .= '<li><i class="' . $meta['icon'] . '"></i> ' . $meta['before'] . $meta['value'] . $meta['after'] . '</li>';
		}

		// End list
		if ( ! empty( $list ) ) {
			echo '<ul class="post-meta entity-meta">' . $list . '</ul>';
		}
	}

	/** Queries ********************************************************/

	/**
	 * Modify the adjacent's post WHERE query clause
	 *
	 * Custom entity order is assumed to be set through the menu_order 
	 * parameter.
	 *
	 * @since 1.1.0
	 * 
	 * @param string $where WHERE clause
	 * @param bool $in_same_term 
	 * @param array $excluded_terms
	 * @return string WHERE clause
	 */
	public function adjacent_post_where( $where, $in_same_term, $excluded_terms ) {
		global $wpdb;

		// Get the current post
		$post = get_post();

		// Bail when this is not an entity
		if ( ! $post || ! in_array( $post->post_type, $this->entities ) )
			return $where;

		$prev     = false !== strpos( current_filter(), 'previous' );
		$adjacent = $prev ? 'previous' : 'next';
		$op       = $prev ? '<' : '>';

		// Compare for the post menu order
		$where = str_replace( $wpdb->prepare( "p.post_date $op %s", $post->post_date ), $wpdb->prepare( "p.menu_order $op %s", $post->menu_order ), $where );

		// Hook sorting filter after this
		add_filter( "get_{$adjacent}_post_sort", array( $this, 'adjacent_post_sort' ) );

		return $where;
	}

	/**
	 * Modify the adjacent post's ORDER BY query clause
	 *
	 * @since 1.1.0
	 * 
	 * @param string $sort ORDER BY clause
	 * @return string ORDER BY clause
	 */
	public function adjacent_post_sort( $sort ) {

		// Sort by the post menu order
		$sort = str_replace( 'p.post_date', 'p.menu_order', $sort );

		// Unhook single-use sorting filter
		remove_filter( current_filter(), array( $this, __FUNCTION__ ) );

		return $sort;
	}
}

endif; // class_exists VGSR_Entity

/**
 * Return the single instance of VGSR_Entity
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $entities = vgsr_entity(); ?>
 *
 * @since 1.0.0
 *
 * @uses VGSR_Entity
 * @return The one single VGSR Entities
 */
function vgsr_entity() {
	return VGSR_Entity::instance();
}

// Fire it up!
vgsr_entity();
