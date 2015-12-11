<?php

/**
 * VGSR Entity Base Class
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_Base' ) ) :
/**
 * Single entity base class
 *
 * @since 1.0.0
 */
abstract class VGSR_Entity_Base {

	/**
	 * Holds tye entity post type
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $type = '';

	/**
	 * Holds the entity arguments
	 * 
	 * @since 1.1.0
	 * @var array
	 */
	protected $args = array();

	/**
	 * Holds the entity meta arguments
	 * 
	 * @since 1.1.0
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Registered error messages
	 *
	 * @since 1.1.0
	 * @var array
	 */
	public $errors = array();

	/**
	 * Reported error ids
	 *
	 * @since 1.1.0
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * Construct the VGSR Entity
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Rearranged parameters and added `$meta` parameter.
	 * 
	 * @param string $type Post type name. Required
	 * @param array $args Entity and post type arguments
	 * @param array $meta Meta field arguments
	 * @param array $errors Error messages with their numeric ids
	 *
	 * @uses VGSR_Entity_Base::entity_globals()
	 * @uses VGSR_Entity_Base::entity_actions()
	 * @uses VGSR_Entity_Base::setup_globals()
	 * @uses VGSR_Entity_Base::setup_requires()
	 * @uses VGSR_Entity_Base::setup_actions()
	 */
	public function __construct( $type, $args = array(), $meta = array(), $errors = array() ) {

		// Set type
		$this->type = $type;

		// Setup entity args
		$this->args = wp_parse_args( $args, array(

			// Post type
			'menu_icon'  => '',
			'labels'     => array(),

			// Parent
			'parent'     => null,
			'parent_key' => "_{$type}-parent-page",

			// Default thumbsize. @todo When theme does not support post-thumbnail image size
			'thumbsize'  => 'post-thumbnail',

			// Admin: Posts
			'page'       => "edit.php?post_type={$type}",

			// Admin: Settings
			'settings'   => array(
				'hook'    => '',
				'page'    => "vgsr_{$type}_settings",
				'section' => "vgsr_{$type}_options_main",
			),
		) );

		// Set meta fields
		$this->meta = $meta;

		// Set error messages
		$this->errors = wp_parse_args( $errors, array(
			1 => esc_html__( 'Some of the provided values were not given in the valid format.', 'vgsr-entity' ),
		) );

		// Setup global entity
		$this->entity_globals();
		$this->entity_actions();

		// Setup specific entity
		$this->setup_globals();
		$this->setup_requires();
		$this->setup_actions();
	}

	/**
	 * Magic isset-er
	 *
	 * @since 1.1.0
	 *
	 * @param string $key
	 * @return bool Value isset
	 */
	public function __isset( $key ) {
		if ( array_key_exists( $key, $this->args ) ) {
			return true;
		} else {
			return isset( $this->{$key} );
		}
	}

	/**
	 * Magic getter
	 *
	 * @since 1.1.0
	 *
	 * @param string $key
	 * @return bool Value isset
	 */
	public function __get( $key ) {
		if ( array_key_exists( $key, $this->args ) ) {
			switch ( $key ) {
				case 'parent' :
					return $this->get_entity_parent();
					break;
				default :
					return $this->args[ $key ];
			}
		} else {
			return $this->{$key};
		}
	}

	/**
	 * Magic setter
	 *
	 * @since 1.1.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		if ( ! array_key_exists( $key, $this->args ) ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Magic unsetter
	 *
	 * @since 1.1.0
	 *
	 * @param string $key
	 */
	public function __unset( $key ) {
		if ( ! array_key_exists( $key, $this->args ) ) {
			unset( $this->{$key} );
		}
	}

	/** Setup Base *****************************************************/

	/**
	 * Define the entity globals
	 *
	 * @since 1.0.0
	 */
	public function entity_globals() {

		// Define local variables
		$single = ucfirst( $this->type );
		$plural = $single . 's';

		// Complete post type labels
		$this->args['labels'] = array_map( 'esc_html', wp_parse_args( $this->args['labels'], array(

			// Post Type
			'name'               => $plural,
			'singular_name'      => $single,
			'add_new'            => sprintf( _x( 'New %s',               'Post type add_new',            'vgsr-entity' ), $single ),
			'add_new_item'       => sprintf( _x( 'Add new %s',           'Post type add_new_item',       'vgsr-entity' ), $single ),
			'edit_item'          => sprintf( _x( 'Edit %s',              'Post type edit_item',          'vgsr-entity' ), $single ),
			'new_item'           => sprintf( _x( 'New %s',               'Post type new_item',           'vgsr-entity' ), $single ),
			'all_items'          => sprintf( _x( 'All %s',               'Post type all_items',          'vgsr-entity' ), $plural ),
			'view_item'          => sprintf( _x( 'View %s',              'Post type view_item',          'vgsr-entity' ), $single ),
			'search_items'       => sprintf( _x( 'Search %s',            'Post type search_items',       'vgsr-entity' ), $plural ),
			'not_found'          => sprintf( _x( 'No %s found',          'Post type not_found',          'vgsr-entity' ), $plural ),
			'not_found_in_trash' => sprintf( _x( 'No %s found in trash', 'Post type not_found_in_trash', 'vgsr-entity' ), $plural ),
			'menu_name'          => $plural,

			// Custom
			'settings_title'     => sprintf( _x( '%s Settings',          'Post type settings_title',     'vgsr-entity' ), $plural ),
		) ) );
	}

	/**
	 * Define default base actions and filters
	 *
	 * @since 1.0.0
	 */
	private function entity_actions() {

		// Post type
		add_action( 'vgsr_entity_init', array( $this, 'register_post_type' ) );

		// Admin
		add_action( 'admin_init',            array( $this, 'entity_register_settings' ) );
		add_action( 'admin_menu',            array( $this, 'entity_admin_menu'        ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts'          ) );
		add_action( 'admin_notices',         array( $this, 'display_errors'           ) );

		// Post
		add_filter( 'wp_insert_post_parent',   array( $this, 'filter_entity_parent' ), 10, 4 );
		add_action( "save_post_{$this->type}", array( $this, 'save_metabox'         ), 10, 2 );

		// List Table
		add_filter( "manage_edit-{$this->type}_columns",        array( $this, 'meta_columns'          )        );
		add_filter( 'hidden_columns',                           array( $this, 'hide_columns'          ), 10, 2 );
		add_action( "manage_{$this->type}_posts_custom_column", array( $this, 'column_content'        ), 10, 2 );
		add_action( 'quick_edit_custom_box',                    array( $this, 'quick_edit_custom_box' ), 10, 2 );

		// Entity children
		add_filter( 'the_content', array( $this, 'entity_parent_page_children' ) );

	}

	/**
	 * Define child class globals
	 *
	 * @since 1.0.0
	 */
	public function setup_globals() { /* Overwrite this method in a child class */ }

	/**
	 * Include required child class files
	 *
	 * @since 1.0.0
	 */
	public function setup_requires() { /* Overwrite this method in a child class */ }

	/**
	 * Setup child class actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() { /* Overwrite this method in a child class */ }

	/** Post Type ******************************************************/

	/**
	 * Register the post type
	 *
	 * @since 1.0.0
	 *
	 * @uses register_post_type()
	 * @uses VGSR_Entity_Base::get_entity_parent_slug()
	 * @uses apply_filters() Calls 'vgsr_{$post_type}_register_post_type'
	 */
	public function register_post_type() {

		// Setup rewrite
		$rewrite = array(
			'slug' => $this->get_entity_parent_slug()
		);

		// Setup post type support
		$supports = array(
			'title',
			'editor',
			'author',
			'thumbnail',
			'revisions',
		);

		// Register this entity post type
		register_post_type(
			$this->type,
			apply_filters( "vgsr_{$this->type}_register_post_type", array(
				'labels'               => $this->args['labels'],
				'public'               => true,
				'menu_position'        => vgsr_entity()->menu_position,
				'hierarchical'         => false,
				'capability_type'      => 'page',
				'rewrite'              => $rewrite,
				'supports'             => $supports,
				'menu_icon'            => $this->args['menu_icon'],
				'register_meta_box_cb' => array( $this, 'add_metabox' ),
			) )
		);
	}

	/**
	 * Add default metabox for this entity's edit post page
	 *
	 * @since 1.0.0
	 *
	 * @uses add_meta_box()
	 */
	public function add_metabox() {
		add_meta_box(
			"vgsr-entity-details",
			sprintf( __( '%s Details', 'vgsr-entity' ), $this->args['labels']['singular_name'] ),
			array( $this, 'details_metabox' ),
			$this->type,
			'side',
			'high'
		);
	}

	/**
	 * Output the contents of the details metabox
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post $post
	 */
	public function details_metabox( $post ) {

		// Walk all meta fields
		foreach ( array_keys( $this->meta ) as $key ) {

			// Output the meta input field
			$field = $this->meta_input_field( $key, $post );

			if ( $field ) {

				// Output nonce verification field
				wp_nonce_field( vgsr_entity()->file, "vgsr_{$this->type}_meta_nonce_{$key}" );

				printf( '<p>%s</p>', $field );
			}
		}

		do_action( "vgsr_{$this->type}_metabox", $post );
	}

	/**
	 * Save details metabox input
	 *
	 * @since 1.1.0
	 *
	 * @uses wp_verify_nonce()
	 * @uses VGSR_Entity_Base::has_errors()
	 *
	 * @param int $post_id Post ID
	 * @param WP_Post $post Post object
	 */
	public function save_metabox( $post_id, $post ) {

		// Bail when doing outosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Bail when the user is not capable
		$cpt = get_post_type_object( $this->type );
		if ( ! current_user_can( $cpt->cap->edit_posts ) || ! current_user_can( $cpt->cap->edit_post, $post_id ) )
			return;

		// Now, update meta fields
		foreach ( $this->meta as $key => $args ) {

			// Bail when the nonce does not verify
			if ( ! isset( $_POST["vgsr_{$this->type}_meta_nonce_{$key}"] )
				|| ! wp_verify_nonce( $_POST["vgsr_{$this->type}_meta_nonce_{$key}"], vgsr_entity()->file )
			)
				continue;

			$value = isset( $_POST[ $args['name'] ] ) ? $_POST[ $args['name'] ] : null;

			$this->save( $key, $value, $post );
		}

		// Report errors
		if ( $this->has_errors() ) {
			add_filter( 'redirect_post_location', array( $this, 'add_error_query_arg' ) );
		}
	}

	/** List Table *****************************************************/

	/**
	 * Modify the current screen's columns
	 *
	 * @since 1.1.0
	 *
	 * @param array $columns Columns
	 * @return array Columns
	 */
	public function meta_columns( $columns ) {

		// Append meta columns
		foreach ( $this->meta as $key => $args ) {
			$columns[ $key ] = $args['label'];
		}

		return $columns;
	}

	/**
	 * Modify the current screen's hidden columns
	 *
	 * @since 1.1.0
	 *
	 * @param array $columns Hidden columns
	 * @param WP_Screen $screen
	 * @return array Hidden columns
	 */
	public function hide_columns( $columns, $screen ) {

		// Append meta columns for our entity's edit.php page
		if ( "edit-{$this->type}" == $screen->id ) {
			$columns = array_merge( $columns, array_keys( $this->meta ) );
		}

		return $columns;
	}

	/**
	 * Output the list table column content
	 *
	 * @since 1.1.0
	 *
	 * @uses VGSR_Entity_Base::get()
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function column_content( $column, $post_id ) {

		// When this is our meta field
		if ( in_array( $column, array_keys( $this->meta ) ) ) {

			// Output display value
			echo $this->get( $column, $post_id );

			// Output value for edit
			if ( current_user_can( get_post_type_object( $this->type )->cap->edit_post, $post_id ) ) {
				printf( '<p class="edit-value hidden">%s</p>', $this->get( $column, $post_id, 'edit' ) );
			}
		}
	}

	/**
	 * Output the entity's meta input fields in the quick edit box
	 *
	 * @since 1.1.0
	 *
	 * @uses get_default_post_to_edit()
	 * @uses VGSR_Entity_Base::met_input_field()
	 *
	 * @param string $column Meta key
	 * @param string $post_type Post type
	 */
	public function quick_edit_custom_box( $column, $post_type ) {

		// When this is an entity and our meta field
		if ( "edit-{$this->type}" == get_current_screen()->id && in_array( $column, array_keys( $this->meta ) ) ) {

			// Get dummy post data
			$post = get_default_post_to_edit( $post_type );

			// Output the meta input field
			$field = $this->meta_input_field( $column, $post );

			if ( $field ) : ?>

				<fieldset class="inline-edit-col-right entity-quick-edit"><div class="inline-edit-col">
					<div class="inline-edit-group">
						<?php // Output nonce verification field ?>
						<?php wp_nonce_field( vgsr_entity()->file, "vgsr_{$this->type}_meta_nonce_{$column}" ); ?>

						<?php echo $field; ?>
					</div>
				</div></fieldset>

			<?php endif;
		}
	}

	/** Settings Page **************************************************/

	/**
	 * Register the entity admin menu with associated hooks
	 *
	 * @since 1.0.0
	 *
	 * @uses add_submenu_page()
	 * @uses add_action() To call some actions on page load, head and footer
	 */
	public function entity_admin_menu() {

		// Register menu page
		$this->args['settings']['hook'] = add_submenu_page( $this->args['page'], $this->args['labels']['settings_title'], __( 'Settings' ), 'manage_options', "{$this->type}-settings", array( $this, 'settings_page' ) );

		// Setup settings specific hooks
		add_action( "load-{$this->args['settings']['hook']}",         array( $this, 'settings_load'   ), 9 );
		add_action( "admin_footer-{$this->args['settings']['hook']}", array( $this, 'settings_footer' )    );
	}

	/**
	 * Create admin page load hook
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_{$post_type}_settings_load'
	 */
	public function settings_load() {
		do_action( "vgsr_{$this->type}_settings_load" );
	}

	/**
	 * Create admin settings enqueue scripts hook
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_enqueue_style()
	 * @uses wp_enqueue_script()
	 * @uses wp_add_inline_style()
	 * @uses wp_localize_script()
	 * @uses do_action() Calls 'vgsr_{$post_type}_settings_enqueue_scripts'
	 */
	public function enqueue_scripts( $page_hook ) {

		// Define local variables
		$screen      = get_current_screen();
		$is_edit     = "edit-{$this->type}" === $screen->id;
		$is_post     = 'post' === $screen->base && $this->type === $screen->id;
		$is_settings = $page_hook === $this->args['settings']['hook'];

		// When on an entity admin page
		if ( $is_edit || $is_post || $is_settings ) {
			$entity = vgsr_entity();

			// Date fields: Enqueue date picker
			if ( wp_list_filter( $this->meta, array( 'type' => 'date' ) ) ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_style( 'stuttter-datepicker', $entity->includes_url . 'assets/css/datepicker.css' );
			}

			// Enqueue admin scripts
			wp_enqueue_style( 'vgsr-entity-admin', $entity->includes_url . 'assets/css/admin.css' );
			wp_enqueue_script( 'vgsr-entity-admin', $entity->includes_url . 'assets/js/admin.js', array( 'jquery' ), '1.1.0', true );
		}

		// When on the edit view
		if ( $is_edit ) {

			// Define additional column styles
			$css = '';
			foreach ( $this->meta as $key => $args ) {
				$width = isset( $args['column-width'] ) ? $args['column-width'] : '10%';
				$css .= ".fixed .column-{$key} { width: {$width} }\n";
			}

			// Append additional styles
			wp_add_inline_style( 'vgsr-entity-admin', $css );

			// Prepare meta for js
			$meta = $this->meta;
			foreach ( array_keys( $meta ) as $key ) {
				$meta[$key]['key'] = $key;
			}

			// Send data to admin js
			wp_localize_script( 'vgsr-entity-admin', 'entityEditPost', array(
				'fields' => array_values( $meta ),
			) );
		}

		// When on the settings page, run hook
		if ( $is_settings ) {
			do_action( "vgsr_{$this->type}_settings_enqueue_scripts" );
		}
	}

	/**
	 * Create admin footer hook
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_{$post_type}_settings_footer'
	 */
	public function settings_footer() {
		do_action( "vgsr_{$this->type}_settings_footer" );
	}

	/**
	 * Output entity settings page
	 *
	 * @since 1.0.0
	 *
	 * @uses settings_errors()
	 * @uses settings_fields()
	 * @uses do_settings_sections()
	 */
	public function settings_page() { ?>

		<div class="wrap">
			<h1><?php echo $this->args['labels']['settings_title']; ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( $this->args['settings']['page'] ); ?>
				<?php do_settings_sections( $this->args['settings']['page'] ); ?>
				<?php submit_button(); ?>
			</form>

		</div>

		<?php
	}

	/**
	 * Register entity settings
	 *
	 * @since 1.0.0
	 *
	 * @uses add_settings_section()
	 * @uses add_settings_field()
	 * @uses register_setting()
	 */
	public function entity_register_settings() {

		// Register main settings section
		add_settings_section( $this->args['settings']['section'], __( 'Main Settings', 'vgsr-entity' ), '', $this->args['settings']['page'] );

		// Entity Parent
		add_settings_field( $this->args['parent_key'], __( 'Parent Page', 'vgsr-entity' ), array( $this, 'entity_parent_settings_field' ), $this->args['settings']['page'], $this->args['settings']['section'] );
		register_setting( $this->args['settings']['page'], $this->args['parent_key'], 'intval' );
		add_action( 'update_option', array( $this, 'update_entity_parent' ), 10, 3 );
	}

	/** Errors *********************************************************/

	/**
	 * Report an error
	 *
	 * @since 1.1.0
	 *
	 * @param int $error_id Error ID
	 */
	public function add_error( $error_id = 0 ) {
		if ( ! empty( $error_id ) && array_key_exists( $error_id, $this->errors ) ) {
			$this->_errors[] = $error_id;
		}
	}

	/**
	 * Return whether errors are reported
	 *
	 * @since 1.1.0
	 *
	 * @return bool Errors are reported
	 */
	public function has_errors() {
		return ! empty( $this->_errors );
	}

	/**
	 * Return the reported errors
	 *
	 * @since 1.1.0
	 *
	 * @uses pow2()
	 *
	 * @param bool $combine Optional. Whether to comine the reported error ids
	 * @return array|int Reported errors or combined reported errors
	 */
	public function get_errors( $combine = false ) {
		if ( $combine ) {
			return pow2( array_unique( $this->_errors ) );
		} else {
			return $this->_errors;
		}
	}

	/**
	 * Add the error query argument to a given URI
	 *
	 * @since 1.1.0
	 *
	 * @uses add_query_arg()
	 * @uses VGSR_Entity_Base::get_errors()
	 *
	 * @param string $uri URI
	 * @return string URI with error query argument
	 */
	public function add_error_query_arg( $uri ) {
		return add_query_arg( array( "{$this->type}-error" => $this->get_errors( true ) ), $uri );
	}

	/**
	 * Display reported errors
	 *
	 * @since 1.0.0
	 *
	 * @uses unpow2()
	 */
	public function display_errors() {

		// Bail when no valid errors are reported
		if ( ! isset( $_REQUEST[ "{$this->type}-error" ] ) )
			return;

		// Get the errors
		$errors = (array) unpow2( (int) $_REQUEST[ "{$this->type}-error" ] );
		foreach ( $errors as $k => $error_id ) {
			if ( ! array_key_exists( $error_id, $this->errors ) ) {
				unset( $errors[ $k ] );
			} else {
				$errors[ $k ] = sprintf( '<p>%s</p>', $this->errors[ $error_id ] );
			}
		}

		// Print available message
		if ( $errors ) {
			printf( '<div class="notice notice-error is-dismissible">%s</div>', implode( '', $errors ) );
		}
	}

	/** Parent Page ****************************************************/

	/**
	 * Output entity parent page settings field
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_dropdown_pages()
	 * @uses VGSR_Entity_Base::get_entity_parent()
	 */
	public function entity_parent_settings_field() { ?>

		<?php wp_dropdown_pages( array(
			'name'             => $this->args['parent_key'],
			'selected'         => $this->get_entity_parent(),
			'show_option_none' => __( 'None', 'vgsr-entity' ),
			'echo'             => true,
		) ); ?>
		<a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->get_entity_parent() ) ); ?>" target="_blank"><?php _e( 'View', 'vgsr-entity' ); ?></a>

		<p class="description"><?php printf( __( 'Select the page that should act as the %s parent page.', 'vgsr-entity' ), $this->args['labels']['name'] ); ?></p>

		<?php
	}

	/**
	 * Return the entity's parent post ID
	 *
	 * @since 1.1.0
	 *
	 * @return int Post ID
	 */
	public function get_entity_parent() {

		// Get the parent post ID
		if ( null === $this->args['parent'] ) {
			$this->args['parent'] = (int) get_option( $this->args['parent_key'], 0 );
		}

		return $this->args['parent'];
	}

	/**
	 * Filter the parent page ID on save for entity posts
	 *
	 * @since 1.0.0
	 *
	 * @param int $parent_id The parent page ID
	 * @param int $post_id The post ID
	 * @param array $new_postarr Array of parsed post data
	 * @param array $postarr Array of unmodified post data
	 * @return int The parent ID
	 */
	public function filter_entity_parent( $parent_id, $post_id, $new_postarr, $postarr ) {

		// When this is our post type, set the post parent
		if ( $new_postarr['post_type'] === $this->type ) {
			$parent_id = $this->get_entity_parent();
		}

		return $parent_id;
	}

	/**
	 * Run logic when updating the parent page option
	 *
	 * @since 1.1.0
	 *
	 * @uses wpdb::update()
	 * @uses VGSR_Entity_Base::register_post_type()
	 * @uses flush_rewrite_rules()
	 *
	 * @param string $option Option name
	 * @param mixed $old_value Previous option value
	 * @param mixed $value New option value
	 */
	public function update_entity_parent( $option, $old_value, $value ) {

		// Bail when this is not our option
		if ( $option !== $this->args['parent_key'] )
			return;

		global $wpdb;

		// Run single update query for entities' post_parent
		$wpdb->update( $wpdb->posts, array( 'post_parent' => $value ), array( 'post_type' => $this->type ), array( '%d' ), array( '%s' ) );

		// Renew rewrite rules
		$this->args['parent'] = $value;
		$this->register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Return the slug for the entity parent page
	 *
	 * @since 1.0.0
	 *
	 * @uses VGSR_Entity_Base::get_entity_parent()
	 * @return string Parent page slug
	 */
	public function get_entity_parent_slug() {

		// Define retval
		$slug = '';

		// Find entity parent page
		if ( $post = get_post( $this->get_entity_parent() ) ) {
			$slug = $post->post_name;

			// Loop over all next parents
			while ( ! empty( $post->post_parent ) ) {

				// Get next parent
				$post = get_post( $post->post_parent );

				// Prepend parent slug
				$slug = $post->post_name . '/' . $slug;
			}
		}

		return $slug;
	}

	/** Theme **********************************************************/

	/**
	 * Append entity parent page content with entity children
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The post content
	 * @return string $content
	 */
	public function entity_parent_page_children( $content ) {

		// Append child entities if available
		if ( $this->get_entity_parent() === get_the_ID() ) {
			$content .= $this->parent_page_list_children();
		}

		return $content;
	}

	/**
	 * Return entity posts HTML markup
	 *
	 * Creates a list of all posts with their respective post thumbnails.
	 *
	 * @since 1.0.0
	 * 
	 * @global array $_wp_additional_image_sizes
	 *
	 * @uses WP_Query
	 * @uses setup_postdata()
	 * @uses get_permalink()
	 * @uses has_post_thumbnail()
	 * @uses wp_get_attachment_image_src()
	 * @uses get_post_thumbnail_id()
	 * @uses get_children()
	 *
	 * @return string $retval HTML
	 */
	public function parent_page_list_children() {

		// Define retval variable
		$retval = '';

		// Get all entity posts
		if ( $children = new WP_Query( array(
			'post_type'   => $this->type,
			'numberposts' => -1,
			'order'       => 'ASC'
		) ) ) {

			// Start output buffer
			ob_start(); ?>

			<ul class="parent-page-children <?php echo $this->type; ?>-children">

			<?php while ( $children->have_posts() ) : $children->the_post(); ?>
				<li class="parent-child <?php echo "{$this->type} {$this->type}-type"; ?>">
					<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( get_the_title() ); ?>">
						<span class="parent-child-thumbnail <?php echo $this->type; ?>-thumbnail">

						<?php // Get the post thumbnail ?>
						<?php if ( has_post_thumbnail() ) :
							$image = wp_get_attachment_image_src( get_post_thumbnail_id(), $this->args['thumbsize'] );
						?>
							<img src="<?php echo $image[0]; ?>" />

						<?php // Get first image attachment ?>
						<?php elseif ( $att = get_children( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_parent' => get_the_ID() ) ) ) :
							$att   = reset( $att );
							$image = wp_get_attachment_image_src( $att->ID, $this->args['thumbsize'] );
						?>
							<img src="<?php echo $image[0]; ?>" />

						<?php // Get dummy image ?>
						<?php else :
							if ( is_string( $this->args['thumbsize'] ) ) {
								global $_wp_additional_image_sizes;
								$format = $_wp_additional_image_sizes[ $this->args['thumbsize'] ];
							} else {
								$format = $this->args['thumbsize'];
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
						?>
							<img src="http://dummyimage.com/<?php echo $size; ?>/fefefe/000&text=<?php _e( 'Placeholder', 'vgsr-entity' ); ?>" />

						<?php endif; ?>

						</span>
						<span class="parent-child-title <?php echo $this->type; ?>-title">
							<h3><?php the_title(); ?></h3>
						</span>
					</a>
				</li>
			<?php endwhile; ?>

			</ul>

			<?php

			// Get output buffer content
			$retval = ob_get_clean();

			// Reste global `$post`
			wp_reset_postdata();
		}

		return $retval;
	}

	/** Meta ***********************************************************/

	/**
	 * Return the input markup for the entity's meta field
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Meta key
	 * @param int|WP_Post $post Current post
	 * @return string Meta input field
	 */
	public function meta_input_field( $key, $post ) {

		// Define field variables
		$meta          = $this->meta[ $key ];
		$meta['id']    = esc_attr( "{$this->type}_{$meta['name']}" );
		$meta['value'] = esc_attr( $this->get( $key, $post, 'edit' ) );

		// Start output buffer
		ob_start();

		// Output field per type
		switch ( $meta['type'] ) {

			// Year
			case 'year' : ?>

		<label class="alignleft">
			<span class="title"><?php echo esc_html( $meta['label'] ); ?></span>
			<span class="input-text-wrap"><input type="number" size="4" placeholder="<?php esc_html_e( 'YYYY', 'vgsr-entity' ); ?>" name="<?php echo esc_attr( $meta['name'] ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" min="<?php echo esc_attr( vgsr_entity()->base_year ); ?>" max="<?php echo date( 'Y' ); ?>" /></span>
		</label>

				<?php
				break;

			// Date
			case 'date' : ?>

		<label class="alignleft">
			<span class="title"><?php echo esc_html( $meta['label'] ); ?></span>
			<span class="input-text-wrap"><input id="<?php echo $meta['id']; ?>" class="ui-widget-content ui-corner-all datepicker" type="text" size="10" placeholder="<?php esc_html_e( 'DD/MM/YYYY', 'vgsr-entity' ); ?>" name="<?php echo esc_attr( $meta['name'] ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" /></span>
		</label>

				<?php
				break;

			// Default fallback
			default :
				do_action( "vgsr_entity_meta_input_{$meta['type']}_field", $key, $post, $meta );
				break;
		}

		$field = ob_get_clean();

		return $field;
	}

	/**
	 * Return the requested entity meta value
	 *
	 * Override this method in a child class.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Meta key
	 * @param int|WP_Post $post Optional. Defaults to current post.
	 * @param string $context Optional. Context, defaults to 'display'.
	 * @return null
	 */
	public function get( $key, $post = 0, $context = 'display' ) {

		// Define default value
		$value   = null;
		$display = ( 'display' === $context );

		// Bail when no post was found
		if ( $post = get_post( $post ) ) {

			// Get value
			$value = get_post_meta( $post->ID, $key, true );

			// Consider meta type
			switch ( $this->meta[ $key ]['type'] ) {
				case 'date' :
					$date = DateTime::createFromFormat( 'Y-m-d', $value );

					if ( $display ) {
						$value = $date->format( get_option( 'date_format' ) );
					} else {
						$value = $date->format( 'd/m/Y' );
					}
			}
		}

		return $value;
	}

	/**
	 * Sanitize the given entity meta value
	 *
	 * Overwrite this method in a child class.
	 *
	 * @since 1.1.0
	 *
	 * @uses update_post_meta()
	 * @uses sanitize_text_field()
	 *
	 * @param string $key Meta key
	 * @param mixed $value Meta value
	 * @param WP_Post $post Post object
	 * @return mixed Meta value
	 */
	public function save( $key, $value, $post ) {

		// Basic input sanitization
		$value = sanitize_text_field( $value );

		// When this is a valid meta
		if ( array_key_exists( $key, $this->meta ) ) {

			// Update as post meta. Allow '0' values
			if ( ! empty( $value ) || '0' === $value ) {
				$error = false;

				// Consider meta type
				switch ( $this->meta[ $key ]['type'] ) {
					case 'date' :
						// Expect d/m/Y, transform to Y-m-d, which can be sorted.
						$date  = DateTime::createFromFormat( 'd/m/Y', $value );
						if ( $date ) {
							$value = $date->format( 'Y-m-d' );
						} else {
							$error = true;
						}

						break;
					case 'year' :
						$value = (int) $value;
						$error = ( 1950 > $value || $value > date( 'Y' ) );
						break;
				}

				// Report error and unset value
				if ( $error ) {
					$this->add_error( 1 );
					$value = null;
				}

				update_post_meta( $post->ID, $key, $value );

			// Delete empty values as post meta
			} else {
				delete_post_meta( $post->ID, $key );
			}
		}

		return $value;
	}

	/**
	 * Return the entity's meta data
	 *
	 * @since 1.1.0
	 *
	 * @uses VGSR_Entity_Base::get_meta()
	 *
	 * @param string $context Optional. Defaults to 'raw'.
	 * @return array Entity meta
	 */
	public function meta( $post = 0, $context = 'raw' ) {

		// Consider context
		switch ( $context ) {

			// Return raw meta fields
			case 'raw' :
				return $this->meta;
				break;

			// Return meta fields for edit
			case 'edit' :
				if ( $post = get_post( $post ) ) {
					$meta = $this->meta;

					// Provide with meta edit value
					foreach ( $meta as $key => $args ) {
						$meta[ $key ]['value'] = $this->get( $key, $post, $context );
					}

					return $meta;
				}

				break;

			// Return meta fields for display
			case 'display' :
				if ( $post = get_post( $post ) ) {

					// Get display meta fields
					$meta = wp_list_filter( $this->meta, array( 'display' => true ) );

					// Provide with meta value
					foreach ( $meta as $key => $args ) {
						$value = $this->get( $key, $post, $context );

						// Add when there is a value to display
						if ( ! empty( $value ) ) {
							$meta[ $key ]['value'] = $value;

						// Remove field from display when emtpy
						} else {
							unset( $meta[ $key ] );
						}
					}

					return $meta;
				}

				break;
		}

		return array();
	}
}

endif; // class_exsits
