<?php

/**
 * The VGSR Entity Plugin
 *
 * @package VGSR Entity
 * @subpackage Main
 */

/**
 * Plugin Name:       VGSR Entity
 * Description:       Custom post type management for besturen, disputen and kasten
 * Plugin URI:        https://github.com/vgsr/vgsr-entity
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins
 * Version:           0.1
 * Text Domain:       vgsr-entity
 * Domain Path:       /languages/
 * GitHub Plugin URI: vgsr/vgsr-entity
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//
// Main entities
// 

if ( ! class_exists( 'VGSR_Entities' ) ) :

/**
 * Main Plugin Entities Class
 *
 * @since 0.1
 */
final class VGSR_Entities {

	/**
	 * Contains all built-in entity names
	 *
	 * @since 0.1
	 * @var array
	 */
	public $entities = array();

	/** Singleton *************************************************************/

	/**
	 * Main VGSR Entities Instance
	 *
	 * Insures that only one instance of VGSR_Entities exists in memory 
	 * at any one time. Also prevents needing to define globals all over 
	 * the place.
	 *
	 * @since 0.2
	 * 
	 * @staticvar object $instance
	 * @uses VGSR_Entities::setup_globals() Setup the globals needed
	 * @uses VGSR_Entities::includes() Include the required files
	 * @uses VGSR_Entities::setup_actions() Setup the hooks and actions
	 * @see vgsr_entity()
	 * @return The one true VGSR_Entities
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been ran previously
		if ( null === $instance ) {
			$instance = new VGSR_Entities;
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
	 * @since 0.1
	 */
	public function __construct() { /* do nothing here */ }

	/**
	 * Define default class globals
	 *
	 * @since 0.1
	 */
	private function setup_globals() {

		// Paths
		$this->file          = __FILE__;
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->menu_position = 35;

		// Predefine all entities as class name => post type
		$this->entities = array( 
			'VGSR_Entity_Bestuur' => 'bestuur', 
			'VGSR_Entity_Dispuut' => 'dispuut', 
			'VGSR_Entity_Kast'    => 'kast', 
		);
	}

	/**
	 * Include the required files
	 *
	 * @since 0.1
	 */
	private function includes() {
		require( $this->plugin_dir . 'widgets/widget-menu.php' );

		// Load each entity
		foreach ( $this->entities as $entity ) {
			require( $this->plugin_dir . "entities/class-vgsr-entity-{$entity}.php" );
		}
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 0.1
	 */
	private function setup_actions() {

		add_action( 'plugins_loaded',     array( $this, 'load_textdomain'  ) );
		add_action( 'init',               array( $this, 'entities_init'    ) );
		add_action( 'admin_menu',         array( $this, 'admin_menu'       ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts'  ) );
		add_action( 'widgets_init',       array( $this, 'widgets_init'     ) );
		add_action( 'template_include',   array( $this, 'template_include' ) );

		// Setup all entities
		foreach ( $this->entities as $entity ) {
			add_action( 'vgsr_entity_init', "vgsr_entity_{$entity}", 9 );
		}

		register_activation_hook( $this->file, array( $this, 'rewrite_flush' ) );
	}

	/** Functions ******************************************************/

	/**
	 * Create vgsr_entity_init action
	 *
	 * @since 0.2
	 */
	public function entities_init() {
		do_action( 'vgsr_entity_init' );
	}

	/**
	 * Set new permalink structure by refreshing the rewrite rules
	 * on activation
	 *
	 * @since 0.1
	 * 
	 * @uses VGSR_Entity::register_post_type()
	 * @uses flush_rewrite_rules()
	 */
	public function rewrite_flush() {

		// Call post type registration
		foreach ( array_keys( $this->entities ) as $class_name ) {
			$class = new $class_name;
			$class->register_post_type();
		}

		// Flush rules only on activation
		flush_rewrite_rules();
	}

	/**
	 * Loads the textdomain file for this plugin
	 *
	 * @since 0.1
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
	 * @since 0.1
	 *
	 * @uses VGSR_Entities::add_separator()
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
	 * @since 0.1
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
	 * @since 0.1
	 *
	 * @uses VGSR_Entities::get_entitiy_parent_id()
	 * @uses wp_register_style()
	 * @uses wp_enqueue_style()
	 */
	public function enqueue_scripts() {
		global $post;

		// Bail when $post is not set
		if ( ! isset( $post ) || ! $post )
			return;

		// Bail when not on entity parent page
		if (   ! in_array( $post->post_type, $this->entities ) 
			&& ! in_array( $post->ID, $this->get_entity_parent_ids()       ) 
		)
			return;

		wp_register_style( 'vgsr-entity', plugins_url( 'css/style.css', __FILE__ ) );
		wp_enqueue_style(  'vgsr-entity' );
	}

	/**
	 * Return all entity parent page IDs
	 *
	 * @since 0.1
	 */
	public function get_entity_parent_ids() {
		$parents = array();
		foreach ( $this->entities as $entity ) {
			$parents[$entity] = get_option( $this->{$entity}->parent_option_key );
		}

		return $parents;
	}

	/**
	 * Initiate entity widgets
	 *
	 * @since 0.1
	 *
	 * @uses register_widget()
	 */
	public function widgets_init() {
		register_widget( 'VGSR_Entity_Menu_Widget' );
	}

	/**
	 * Intercept the template loader to load the entity template
	 *
	 * @since 0.1
	 * 
	 * @param string $template The current template match
	 * @return string $template
	 */
	public function template_include( $template ) {
		$post_type = get_post_type();

		// Serve single-{$entity} template if asked for
		if ( in_array( $post_type, $this->entities ) && is_singular( $post_type ) ) {

			// Get our template path
			$single = $this->plugin_dir . 'templates/single-' . $post_type . ' .php';

			// Only serve our template when it exists
			$template = file_exists( $single ) ? $single : $template;
		}

		return $template;
	}

	/**
	 * Outputs the entity meta list
	 * 
	 * @since 0.1
	 *
	 * @uses apply_filters() Calls 'vgsr_{$post_type}_meta' with the meta array
	 */
	public function entity_meta() {
		global $post;

		// Setup meta list
		$list = '';

		// Loop over all meta fields
		foreach ( apply_filters( "vgsr_{$post->post_type}_meta", array() ) as $key => $meta ) {

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
}

endif; // class_exists VGSR_Entities

//
// Single entity
// 

if ( ! class_exists( 'VGSR_Entity' ) ) :

/**
 * Single entity base class
 *
 * @since 0.1
 */
abstract class VGSR_Entity {

	/**
	 * The entity post type
	 * 
	 * @since 0.1
	 * @var string
	 */
	public $type = '';

	/**
	 * The entity admin page 
	 *
	 * @since 0.1
	 * @var string
	 */
	public $page = '';

	/**
	 * The entity post type labels
	 *
	 * @since 0.1
	 * @var array {
	 *  @type string $single Post type single label
	 *  @type string $plural Post type plural label
	 * }
	 */
	public $labels = array();

	/**
	 * The entity settings page hook
	 *
	 * @since 0.1
	 * @var string
	 */
	public $hook = '';

	/**
	 * The entity parent page option name
	 *
	 * @since 0.1
	 * @var string
	 */
	public $parent_option_key = '';

	/**
	 * The entity post thumbnail size
	 *
	 * @since 0.1
	 * @var string
	 */
	public $thumbsize = '';

	/**
	 * The entity settings page
	 *
	 * @since 0.1
	 * @var string
	 */
	public $settings_page = '';

	/**
	 * The entity main settings section
	 *
	 * @since 0.1
	 * @var string
	 */
	public $settings_section = '';

	/**
	 * Construct the VGSR Entity
	 *
	 * @since 0.1
	 */
	public function __construct( $args ) {

		// Setup entity args
		$this->args = (object) wp_parse_args( $args, array( 
			'single'    => '',
			'plural'    => '',
			'menu_icon' => '',
		) );

		// Bail when labels are msising
		if ( empty( $this->args->single ) || empty( $this->args->plural ) ) {
			wp_die( __( 'The VGSR entity is missing some of the post type labels', 'vgsr-entity' ), 'vgsr-entity-missing-labels' );
		}

		// Setup defaults
		$this->entity_globals();
		$this->entity_actions();

		// Setup child class
		$this->setup_globals();
		$this->setup_requires();
		$this->setup_actions();
	}

	/**
	 * Define default entity base globals
	 *
	 * @since 0.1
	 */
	private function entity_globals() {

		// Build post type from single type label
		$this->type = strtolower( $this->args->single );
		$this->page = 'edit.php?post_type=' . $this->type;

		// Post type parent page option value
		$this->parent_option_key  = "_{$this->type}-parent-page";

		// Default thumbsize. @todo When theme does not support post-thumbnail image size
		$this->thumbsize = 'post-thumbnail';

		// Setup settings page title
		$this->settings_title = sprintf( __( '%s Settings' ), $this->args->single );

		// Settings globals
		$this->settings_page    = "vgsr_{$this->type}_settings";
		$this->settings_section = "vgsr_{$this->type}_options_main";
	}

	/**
	 * Setup default entity base actions and filters
	 *
	 * @since 0.1
	 */
	private function entity_actions() {

		// Actions
		add_action( 'vgsr_entity_init', array( $this, 'register_post_type'       ) );
		add_action( 'admin_init',       array( $this, 'entity_register_settings' ) );
		add_action( 'admin_menu',       array( $this, 'entity_admin_menu'        ) );
		add_action( 'admin_notices',    array( $this, 'entity_admin_notices'     ) );

		// Plugin hooks
		add_filter( "vgsr_{$this->type}_meta",      array( $this, 'entity_meta'               )    );
		add_filter( "{$this->type}_admin_messages", array( $this, 'admin_messages'            )    );
		add_action( "{$this->type}_settings_load",  array( $this, 'entity_parent_page_update' ), 1 );

		// Post hooks
		add_filter( 'wp_insert_post_parent', array( $this, 'entity_parent_page_save_post' ), 10, 2 );
	}

	/**
	 * Define child class globals
	 *
	 * @since 0.1
	 */
	public function setup_globals() { /* Overwrite this method in a child class */ }

	/**
	 * Include required child class files
	 *
	 * @since 0.1
	 */
	public function setup_requires() { /* Overwrite this method in a child class */ }

	/**
	 * Setup child class actions and filters
	 *
	 * @since 0.1
	 */
	public function setup_actions() { /* Overwrite this method in a child class */ }

	/** Setup Entity Post Type *****************************************/

	/**
	 * Register the post type
	 *
	 * @since 0.1
	 *
	 * @uses register_post_type()
	 * @uses apply_filters() To call vgsr_{$post_type}_register_cpt
	 *                        filter to enable post type arguments filtering
	 */
	public function register_post_type() {

		// Create post type labels
		$labels = array(
			'name'                 => $this->args->plural,
			'singular_name'        => $this->args->single,
			'add_new'              => sprintf( __( 'New %s' ),               $this->args->single ),
			'add_new_item'         => sprintf( __( 'Add new %s' ),           $this->args->single ),
			'edit_item'            => sprintf( __( 'Edit %s' ),              $this->args->single ),
			'new_item'             => sprintf( __( 'New %s' ),               $this->args->single ),
			'all_items'            => sprintf( __( 'All %s' ),               $this->args->plural ),
			'view_item'            => sprintf( __( 'View %s' ),              $this->args->single ),
			'search_items'         => sprintf( __( 'Search %s' ),            $this->args->plural ),
			'not_found'            => sprintf( __( 'No %s found' ),          $this->args->plural ),
			'not_found_in_trash'   => sprintf( __( 'No %s found in trash' ), $this->args->plural ), 
			'menu_name'            => $this->args->plural
		);

		// Setup post type support
		$supports = array(
			'title', 
			'editor', 
			'author', 
			'thumbnail', 
			'revisions', 
			'page-attributes' // To set menu order
		);

		// Register this entity post type
		register_post_type( $this->type, apply_filters( "vgsr_{$this->type}_register_post_type", array(
			'labels'               => $labels,
			'public'               => true,
			'menu_position'        => vgsr_entity()->menu_position,
			'hierarchical'         => false,
			'rewrite'              => array( 
				'slug' => $this->entity_parent_page_slug() 
			),
			'capability_type'      => 'page',
			'supports'             => $supports,
			'register_meta_box_cb' => array( $this, 'add_metabox' ),
			'menu_icon'            => $this->args->menu_icon,
		) ) );
	}

	/**
	 * Add metabox callback for entity CPT
	 *
	 * @since 0.1
	 */
	public function add_metabox() { /* Overwrite this method in a child class */ }

	/** Entity Settings Page *******************************************/

	/**
	 * Register the entity admin menu with associated hooks
	 *
	 * @since 0.1
	 *
	 * @uses add_submenu_page()
	 * @uses add_action() To call some actions on page load
	 *                     head and footer
	 */
	public function entity_admin_menu() {
		$this->hook = add_submenu_page( $this->page, $this->settings_title, __( 'Settings' ), 'manage_options', $this->type . '-settings', array( $this, 'settings_page' ) );

		// Setup settings specific hooks
		add_action( 'load-'                . $this->hook, array( $this, 'entity_settings_load'    ), 9  );
		add_action( 'admin_print_scripts-' . $this->hook, array( $this, 'entity_settings_styles'  ), 10 );
		add_action( 'admin_print_styles-'  . $this->hook, array( $this, 'entity_settings_scripts' ), 10 );
		add_action( 'admin_footer-'        . $this->hook, array( $this, 'entity_settings_footer'  )     );
	}

	/**
	 * Create admin page load hook
	 *
	 * @since 0.x
	 */
	public function entity_settings_load() {
		do_action( "vgsr_{$this->type}_settings_load" );
	}

	/**
	 * Create admin page styles hook
	 * 
	 * @since 0.x
	 */
	public function entity_settings_styles() {
		do_action( "vgsr_{$this->type}_settings_styles" );
	}

	/**
	 * Create admin settings scripts hook
	 * 
	 * @since 0.x
	 */
	public function entity_settings_scripts() {
		do_action( "vgsr_{$this->type}_settings_scripts" );
	}

	/**
	 * Create admin footer scripts hook
	 * 
	 * @since 0.x
	 */
	public function entity_settings_footer() {
		do_action( "vgsr_{$this->type}_settings_footer" );
	}

	/**
	 * Output entity settings page 
	 *
	 * @since 0.1
	 *
	 * @uses settings_errors()
	 * @uses settings_fields()
	 * @uses do_settings_sections()
	 */
	public function settings_page() { ?>

		<div class="wrap">

			<?php screen_icon(); ?>
			<h2><?php echo $this->settings_title; ?></h2>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( $this->settings_page ); ?>
				<?php do_settings_sections( $this->settings_page ); ?>
				<?php submit_button(); ?>
			</form>

		</div>

	<?php
	}

	/**
	 * Register entity settings
	 *
	 * @since 0.1
	 * 
	 * @uses add_settings_section()
	 * @uses add_settings_field()
	 * @uses register_setting()
	 */
	public function entity_register_settings() {

		// Register main settings section
		add_settings_section( 
			$this->settings_section, 
			sprintf( __( 'Main %s Settings', 'vgsr-entity' ), $this->args->plural ),
			array( $this, 'main_settings_info' ), 
			$this->settings_page 
		);

		// Entity post type parent page
		add_settings_field( $this->parent_option_key, sprintf( __( '%s Parent Page', 'vgsr-entity' ), $this->args->plural ), array( $this, 'entity_parent_page_settings_field' ), $this->settings_page, $this->settings_section );
		register_setting( $this->settings_page, $this->parent_option_key, 'intval' );
	}

	/**
	 * Output main settings section info
	 *
	 * @since 0.1
	 */
	public function main_settings_info() { /* Nothing to display */ }

	/**
	 * Output entity parent page settings field
	 *
	 * @since 0.1
	 *
	 * @uses wp_dropdown_pages()
	 */
	public function entity_parent_page_settings_field() { ?>

		<label>
			<?php

			// Output page dropdown
			wp_dropdown_pages( array(
				'name'             => $this->parent_option_key,
				'selected'         => get_option( $this->parent_option_key ),
				'echo'             => true,
				'show_option_none' => __( 'None' )
			) ); ?>

			<span class="description"><?php printf( __( 'Select the parent page you want to have your %s to appear on.', 'vgsr-entity' ), $this->args->plural ); ?></span>
		</label>

	<?php
	}

	/**
	 * Rewrite permalink setup if post parent changes
	 *
	 * @since 0.1
	 *
	 * @uses get_posts()
	 * @uses wp_update_post()
	 */
	public function entity_parent_page_update() {

		// Get random entity post
		$post = get_posts( array( 'post_type' => $this->type, 'numberposts' => 1 ) );

		// Compare entity parent page ID with updated ID
		if ( $post[0]->post_parent != get_option( $this->parent_option_key ) ) {

			// Loop all entity posts
			foreach ( get_posts( array( 'post_type' => $this->type, 'numberposts' => -1 ) ) as $post ) {

				// Update the post parent
				$post->post_parent = $new_pid;
				wp_update_post( $post );
			}
		}
	}

	/**
	 * Return entity parent page ID as post parent on post save
	 *
	 * @since 0.1
	 * 
	 * @param int $parent_id The parent page ID
	 * @param int $post_id The post ID
	 * @return int The parent ID
	 */
	public function entity_parent_page_save_post( $parent_id, $post_id ) {

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $parent_id;

		// Check post type
		if ( get_post_type( $post_id ) !== $this->type )
			return $parent_id;

		// Check caps
		$pto = get_post_type_object( $this->type );
		if ( ! current_user_can( $pto->cap->edit_posts ) || ! current_user_can( $pto->cap->edit_post, $post_id ) )
			return $parent_id;

		$entity_ppid = (int) get_option( $this->parent_option_key );

		if ( $parent_id != $entity_ppid )
			return $parent_id;

		return $entity_ppid;
	}

	/**
	 * Return the slug for the entity parent page
	 *
	 * @since 0.1
	 * 
	 * @return string Parent page slug
	 */
	public function entity_parent_page_slug() {
		$slug = '';

		// Find entity parent page
		if ( $_post = get_post( get_option( $this->parent_option_key ) ) ) {
			$slug = $_post->post_name;

			// Loop over all next parents
			while ( ! empty( $_post->post_parent ) ) {

				// Get next parent
				$_post = get_post( $_post->post_parent );

				// Prepend parent slug
				$slug = $_post->post_name . '/' . $slug;
			}
		}

		return $slug;
	}

	/**
	 * Append entity parent page content with entity children
	 *
	 * @since 0.1
	 * 
	 * @param string $content The post content
	 * @return string $content
	 */
	public function entity_parent_page_children( $content ) {

		// Append child entities if available
		if ( (int) get_option( $this->parent_option_key ) == get_the_ID() ) {
			$content .= $this->parent_page_list_children();
		}

		return $content;
	}

	/**
	 * Return entity posts HTML markup
	 * 
	 * Creates a list of all posts with their respective post thumbnails.
	 *
	 * @since 0.1
	 *
	 * @uses get_posts()
	 * @uses setup_postdata()
	 * @uses get_permalink()
	 * @uses has_post_thumbnail()
	 * @uses wp_get_attachment_image_src()
	 * @uses get_post_thumbnail_id()
	 * @uses get_children()
	 * @global array $_wp_additional_image_sizes
	 * 
	 * @return string $retval HTML
	 */
	public function parent_page_list_children() {

		// Get all entity posts
		$children = get_posts( array(
			'post_type'   => $this->type,
			'numberposts' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'ASC'
		) );

		$retval = '<ul class="parent-page-children ' . $this->type . '-children">';

		foreach ( $children as $post ) : setup_postdata( $post );
			$retval .=	'<li class="parent-child ' . $this->type. ' ' . $this->type. '-type">';
			$retval .=		'<a href="' . get_permalink( $post->ID ) . '" title="' . $post->post_title . '">';
			$retval .=			'<span class="parent-child-thumbnail ' . $this->type . '-thumbnail">';

			// Get the post thumbnail
			if ( has_post_thumbnail( $post->ID ) ) :
				$img     = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $this->thumbsize );
				$retval .= '<img src="' . $img[0] . '" />';

			// Get first image attachment
			elseif ( $att = get_children( array('post_type' => 'attachment', 'post_mime_type' => 'image', 'post_parent' => $post->ID ) ) ) :
				$att     = reset( $att );
				$img     = wp_get_attachment_image_src( $att->ID, $this->thumbsize );
				$retval .= '<img src="' . $img[0] . '" />';

			// Get dummy image
			else :
				if ( is_string( $this->thumbsize ) ) {
					global $_wp_additional_image_sizes;
					$format = $_wp_additional_image_sizes[$this->thumbsize];
				} else {
					$format = $this->thumbsize;
				}
				
				// Setup dummy image size
				if ( is_array( $format ) ) {
					if ( isset( $format[0] ) ) // Numerical array
						$size = $format[0] . 'x' . $format[1];
					else // Textual string
						$size = $format['width'] . 'x' . $format['height'];
				} else {
					$size = '200x200'; // Random default value
				}

				$retval .= '<img src="http://dummyimage.com/' . $size . '/fefefe/000&text=' .  __( 'Placeholder', 'vgsr-entity' ) . '" />';

			endif;

			$retval .=			'</span>';
			$retval .=			'<span class="parent-child-title ' . $this->type . '-title">' .
									'<h3>' . $post->post_title . '</h3>' .
								'</span>';
			$retval .=		'</a>';
			$retval .=	'</li>';

		endforeach;

		$retval .= '</ul>';

		return $retval;
	}

	/**
	 * Output the admin messages if requested
	 *  
	 * @since 0.1
	 *
	 * @uses apply_filters() To call the {$this->type}_admin_messages filter
	 */
	public function entity_admin_notices() {

		// Only continue if error is sent
		if (   ! isset( $_REQUEST[$this->type . '-error'] ) 
			||   empty( $_REQUEST[$this->type . '-error'] ) 
		)
			return;

		// Get the message number
		$num = trim( $_REQUEST[$this->type . '-error'] );

		// The messages to pick from
		$messages = apply_filters( $this->type . '_admin_messages', array(
			0 => '' // Default empty
		) );

		// Message must exist
		if ( ! isset( $messages[$num] ) )
			return;

		// Output message
		echo '<div class="error message"><p>' . $messages[$num] . '</p></div>';
	}

	/**
	 * Return the custom admin messages
	 *
	 * Should be overriden in child class.
	 *
	 * @since 0.1
	 * 
	 * @param array $messages {
	 *  @type int    Message number
	 *  @type string Message content
	 * }
	 * @return array $messages
	 */
	public function admin_messages( $messages ) {
		return $messages;
	}

	/**
	 * Return the entity meta data
	 *
	 * @since 0.1
	 * 
	 * @param array $meta The entity meta data
	 * @return array $meta
	 */
	public function entity_meta( $meta ) {
		return $meta;
	}
}

endif; // class_exsits

/**
 * Return the single instance of VGSR_Entities
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $entities = vgsr_entity(); ?>
 *
 * @since 0.2
 * 
 * @uses VGSR_Entities
 * @return The one single VGSR Entities
 */
function vgsr_entity() {
	return VGSR_Entities::instance();
}

// Fire it up!
vgsr_entity();
