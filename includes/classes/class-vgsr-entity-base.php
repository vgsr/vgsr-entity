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
	 * @since 2.0.0
	 * @var array
	 */
	protected $args = array();

	/**
	 * Holds the entity meta arguments
	 * 
	 * @since 2.0.0
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Registered error messages
	 *
	 * @since 2.0.0
	 * @var array
	 */
	public $errors = array();

	/**
	 * Reported error ids
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * Construct the VGSR Entity
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Rearranged parameters and added `$meta` parameter.
	 *
	 * @param string $type Post type name. Required
	 * @param array $args {
	 *     Optional. Array of entity arguments
	 *
	 *     @type array  $labels        A list of post type labels used in register_post_type(). Default
	 *                                 to the function's default.
	 *     @type string $menu_icon     The dashicon class name for a menu icon. Default to none.
	 *     @type bool   $has_archive   Whether to enable archiving of entity posts. Default to false.
	 *     @type array  $features      A collection of active entity features. Default to 'logo'.
	 *     @type int    $parent        The post ID of the entity's post parent. Default to entity setting.
	 *     @type string $posts_page    The admin url string that will serve as the parent menu slug.
	 *                                 Default to the post type edit.php page.
	 *     @type string $settings_page The settings page hook used for admin page targeting. Defaults to
	 *                                 the result of add_submenu_page().
	 * }
	 * @param array $meta Meta field arguments
	 * @param array $errors Error messages with their numeric ids
	 */
	public function __construct( $type, $args = array(), $meta = array(), $errors = array() ) {

		// Set type
		$this->type = $type;

		// Setup entity args
		$this->args = wp_parse_args( $args, array(
			'labels'        => array(),
			'menu_icon'     => '',
			'has_archive'   => false,
			'features'      => array( 'logo' ),
			'parent'        => null,
			'thumbsize'     => 'post-thumbnail',
			'posts_page'    => "edit.php?post_type={$type}",
			'settings_page' => '',
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
		add_action( 'admin_menu',            array( $this, 'entity_admin_menu'     ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_notices',         array( $this, 'admin_display_errors'  ) );

		// Settings
		add_action( 'admin_init',    array( $this, 'entity_register_settings' )        );
		add_action( 'update_option', array( $this, 'update_entity_parent'     ), 10, 3 );

		// Post
		add_filter( 'wp_insert_post_parent',      array( $this, 'filter_entity_parent' ), 10, 4 );
		add_action( "vgsr_{$this->type}_metabox", array( $this, 'details_metabox'      )        );
		add_action( "save_post_{$this->type}",    array( $this, 'save_metabox'         ), 10, 2 );
		add_filter( 'the_content',                array( $this, 'content_with_details' )        );

		// List Table
		add_filter( "manage_edit-{$this->type}_columns",        array( $this, 'table_columns'         )        );
		add_filter( 'hidden_columns',                           array( $this, 'hide_columns'          ), 10, 2 );
		add_action( "manage_{$this->type}_posts_custom_column", array( $this, 'column_content'        ), 10, 2 );
		add_action( 'display_post_states',                      array( $this, 'post_states'           ), 10, 2 );
		add_action( 'quick_edit_custom_box',                    array( $this, 'quick_edit_custom_box' ), 10, 2 );

		// Features
		add_action( 'vgsr_entity_init', array( $this, 'feature_logo_setup' ) );
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

		// The archive post status
		$this->archive_status_id = 'archive';

		// Register archive post status
		if ( $this->args['has_archive'] ) {

			// User access
			$access = vgsr_entity_check_access();

			register_post_status( $this->archive_status_id, array(
				'label'               => esc_html__( 'Archived', 'vgsr-entity' ),
				'label_count'         => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>', 'vgsr-entity' ),

				// Limit access to archived posts
				'exclude_from_search' => ! $access,
				'public'              => $access,
			) );
		}
	}

	/**
	 * Add default metabox for this entity's edit post page
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_{$post_type}_metabox'
	 */
	public function add_metabox() {

		// Details metabox
		add_meta_box(
			"vgsr-entity-details",
			sprintf( __( '%s Details', 'vgsr-entity' ), $this->args['labels']['singular_name'] ),
			/**
			 * Run only a dedicated action in the metabox
			 *
			 * The `type` variable in the action name points to the post type.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Post $post Post object
			 */
			function( $post ){ do_action( "vgsr_{$this->type}_metabox", $post ); },
			$this->type,
			'side',
			'high'
		);
	}

	/**
	 * Output the contents of the details metabox
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post
	 */
	public function details_metabox( $post ) {

		// Walk all meta fields
		foreach ( array_keys( $this->meta ) as $key ) {

			// Get the meta input field
			$field = $this->meta_input_field( $key, $post );

			// Output field and its nonce
			if ( $field ) : ?>

			<p class="<?php echo "{$post->post_type}-{$this->meta[ $key ]['type']}"; ?>"><?php
				echo $field;
				wp_nonce_field( vgsr_entity()->file, "vgsr_{$this->type}_meta_nonce_{$key}" );
			?></p>

			<?php endif;
		}
	}

	/**
	 * Save an entity's details metabox input
	 *
	 * @since 2.0.0
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

			// Save entity meta value
			$this->save( $key, $value, $post );
		}

		// Report errors
		if ( $this->has_errors() ) {
			add_filter( 'redirect_post_location', array( $this, 'add_error_query_arg' ) );
		}
	}

	/**
	 * Modify the content by adding the entity details
	 * 
	 * @since 2.0.0
	 *
	 * @param string $content Post content
	 * @return string Post content
	 */
	public function content_with_details( $content ) {

		// When in the main query's single entity
		if ( is_main_query() && is_singular( $this->type ) ) {

			// Prepend details to content
			$content = vgsr_entity_details() . $content;
		}

		return $content;
	}

	/** Feature: Logo **************************************************/

	/**
	 * Setup main logic for the logo feature
	 *
	 * @since 2.0.0
	 */
	public function feature_logo_setup() {

		// Bail when there's no logo feature support
		if ( ! entity_supports( 'logo', $this->type ) )
			return;

		// Define logo image size
		add_image_size( 'entity-logo', 500, 500, 1 );

		// Post actions
		add_action( "vgsr_{$this->type}_metabox",   'vgsr_entity_feature_logo_metabox',         8    );
		add_action( 'wp_ajax_vgsr_entity_set_logo', 'vgsr_entity_feature_logo_save'                  );
		add_filter( 'media_view_settings',          'vgsr_entity_feature_logo_media_settings', 10, 2 );

		// List table actions
		add_filter( "manage_edit-{$this->type}_columns",        'vgsr_entity_feature_logo_list_column'                );
		add_action( "manage_{$this->type}_posts_custom_column", 'vgsr_entity_feature_logo_list_column_content', 10, 2 );

		// Post details
		add_action( "vgsr_entity_{$this->type}_details", 'vgsr_entity_feature_logo_detail', 5 );
	}

	/** List Table *****************************************************/

	/**
	 * Modify the current screen's columns
	 *
	 * @since 2.0.0
	 *
	 * @param array $columns Columns
	 * @return array Columns
	 */
	public function table_columns( $columns ) {

		// Append meta columns
		foreach ( $this->meta as $key => $args ) {
			$columns[ $key ] = $args['column_title'];
		}

		return $columns;
	}

	/**
	 * Modify the current screen's hidden columns
	 *
	 * @since 2.0.0
	 *
	 * @param array $columns Hidden columns
	 * @param WP_Screen $screen
	 * @return array Hidden columns
	 */
	public function hide_columns( $columns, $screen ) {

		// Append meta columns for our entity's edit.php page
		if ( "edit-{$this->type}" === $screen->id ) {
			$columns = array_merge( $columns, array_keys( $this->meta ) );
		}

		return $columns;
	}

	/**
	 * Output the list table column content
	 *
	 * @since 2.0.0
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
	 * Modify the entity post's display post states
	 *
	 * @since 1.2.0
	 *
	 * @param array $states Post states
	 * @param WP_Post $post Post object
	 * @return array Post states
	 */
	public function post_states( $states, $post ) {

		// Append a state for archived posts
		if ( $this->type === $post->post_type && $this->args['has_archive'] && $this->archive_status_id === $post->post_status ) {
			$states[] = __( 'Archived', 'vgsr-entity' );
		}

		return $states;
	}

	/**
	 * Output the entity's meta input fields in the quick edit box
	 *
	 * @since 2.0.0
	 *
	 * @param string $column Meta key
	 * @param string $post_type Post type
	 */
	public function quick_edit_custom_box( $column, $post_type ) {

		// When this is an entity and our meta field
		if ( "edit-{$this->type}" === get_current_screen()->id && in_array( $column, array_keys( $this->meta ) ) ) {

			// Get dummy post data
			$post = get_default_post_to_edit( $post_type );

			// Output the meta input field
			$field = $this->meta_input_field( $column, $post );

			if ( $field ) : ?>

				<fieldset class="inline-edit-col-right entity-quick-edit">
					<div class="inline-edit-col <?php echo "{$post_type}-{$this->meta[ $column ]['type']}"; ?>">
						<div class="inline-edit-group">
							<?php // Output nonce verification field ?>
							<?php wp_nonce_field( vgsr_entity()->file, "vgsr_{$this->type}_meta_nonce_{$column}" ); ?>

							<?php echo $field; ?>
						</div>
					</div>
				</fieldset>

			<?php endif;
		}
	}

	/** Settings Page **************************************************/

	/**
	 * Register the entity admin menu with associated hooks
	 *
	 * @since 1.0.0
	 */
	public function entity_admin_menu() {

		// Register menu page
		$hook = add_submenu_page(
			$this->args['posts_page'],
			$this->args['labels']['settings_title'],
			esc_html__( 'Settings', 'vgsr-entity' ),
			'manage_options',
			"{$this->type}-settings",
			array( $this, 'settings_page'
		) );

		// Setup settings page hooks
		add_action( "load-{$hook}",         array( $this, 'settings_load'   ), 9 );
		add_action( "admin_footer-{$hook}", array( $this, 'settings_footer' )    );

		// Store $hook in class global
		$this->args['settings_page'] = $hook;
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
	 * Enqueue child class settings scripts
	 *
	 * @since 2.0.0
	 */
	public function enqueue_settings_scripts() { /* Overwrite this method in a child class */ }

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
	 * Output scripts on entity admin pages
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Admin page hook
	 */
	public function admin_enqueue_scripts( $hook ) {

		// Run admin settings scripts
		if ( $hook === $this->settings_page ) {
			$this->enqueue_settings_scripts();
			return;
		}

		// Define local variables
		$screen  = get_current_screen();
		$is_edit = "edit-{$this->type}" === $screen->id;
		$is_post = 'post' === $screen->base && $this->type === $screen->id;

		// Bail when not on an entity admin page
		if ( ! $is_edit && ! $is_post )
			return;

		// Get VGSR Entity
		$entity = vgsr_entity();

		// Date fields: Enqueue date picker
		if ( wp_list_filter( $this->meta, array( 'type' => 'date' ) ) ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'stuttter-datepicker', $entity->includes_url . 'assets/css/datepicker.css' );
		}

		// Enqueue admin scripts
		wp_enqueue_style( 'vgsr-entity-admin', $entity->includes_url . 'assets/css/admin.css' );
		wp_enqueue_script( 'vgsr-entity-admin', $entity->includes_url . 'assets/js/admin.js', array( 'jquery' ), '2.0.0', true );

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
		}

		// When on the edit or post view
		if ( $is_edit || $is_post ) {

			// Prepare meta for js
			$meta = $this->meta;
			foreach ( array_keys( $meta ) as $key ) {
				$meta[$key]['key'] = $key;
			}

			// Send data to admin js
			wp_localize_script( 'vgsr-entity-admin', 'entityEditPost', array(
				'l10n'   => array(

					// Archive post status
					'archiveStatusId' => $this->archive_status_id,
					'archiveLabel'    => esc_html__( 'Archived', 'vgsr-entity' ),
					'publishStatusId' => 'publish',
					'publishLabel'    => esc_html__( 'Published' ),
					'hasArchive'      => (bool) $this->args['has_archive'],
					'isArchived'      => $is_post ? ( $this->archive_status_id === get_post()->post_status ) : false,

					// Logo feature
					'entityLogoTitle' => sprintf( esc_html__( '%s Logo',     'vgsr-entity' ), $this->args['labels']['singular_name'] ),
					'setEntityLogo'   => sprintf( esc_html__( 'Set %s Logo', 'vgsr-entity' ), $this->args['labels']['singular_name'] ),
				),
				'fields' => array_values( $meta ),
			) );
		}
	}

	/**
	 * Output entity settings page
	 *
	 * @since 1.0.0
	 */
	public function settings_page() { ?>

		<div class="wrap">
			<h1><?php echo $this->args['labels']['settings_title']; ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( "vgsr_{$this->type}_settings" ); ?>
				<?php do_settings_sections( "vgsr_{$this->type}_settings" ); ?>
				<?php submit_button(); ?>
			</form>

		</div>

		<?php
	}

	/**
	 * Register entity settings
	 *
	 * @since 1.0.0
	 */
	public function entity_register_settings() {

		// Bail when not in the admin
		if ( ! get_current_screen() ) {
			set_current_screen();
		}

		// Define local variables
		$sections  = vgsr_entity_settings_sections();
		$fields    = vgsr_entity_settings_fields();
		$page_name = "vgsr_{$this->type}_settings";

		// Walk registered sections
		foreach ( $sections as $section => $s_args ) {

			// Skip empty settings sections
			if ( ! isset( $fields[ $section ] ) || empty( $fields[ $section ] ) )
				continue;

			// Prefix section name
			$section_name = "vgsr_{$this->type}_{$section}";
			$fields_count = 0;

			// Use provided page name
			if ( ! empty( $s_args['page'] ) ) {
				$page_name = $s_args['page'];
			}

			// Walk registered section's fields
			foreach ( $fields[ $section ] as $field => $f_args ) {

				// Skip when it does not apply to this entity
				if ( isset( $f_args['entity'] ) && ! in_array( $this->type, (array) $f_args['entity'] ) )
					continue;

				// Prefix field name
				$field_name = "_{$this->type}-{$field}";

				// Add field when callable
				if ( isset( $f_args['callback'] ) && is_callable( $f_args['callback'] ) ) {
					add_settings_field( $field_name, $f_args['title'], $f_args['callback'], $page_name, $section_name, $f_args['args'] );

					// Count registered section fields
					$fields_count++;
				}

				// Register field either way
				register_setting( $page_name, $field_name, $f_args['sanitize_callback'] );
			}

			// Register non-empty section
			if ( $fields_count > 0 ) {
				add_settings_section( $section_name, $s_args['title'], $s_args['callback'], $page_name );
			}
		}
	}

	/**
	 * Return the setting's field value
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Setting key
	 * @return mixed|bool Setting value or False when not found
	 */
	public function get_setting( $key ) {
		return get_option( "_{$this->type}-{$key}", false );
	}

	/** Errors *********************************************************/

	/**
	 * Report an error
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 *
	 * @return bool Errors are reported
	 */
	public function has_errors() {
		return ! empty( $this->_errors );
	}

	/**
	 * Return the reported errors
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 */
	public function admin_display_errors() {

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
	 * Return the entity's parent post ID
	 *
	 * @since 2.0.0
	 *
	 * @return int Post ID
	 */
	public function get_entity_parent() {

		// Get the parent post ID
		if ( null === $this->args['parent'] ) {
			$this->args['parent'] = (int) get_option( "_{$this->type}-parent-page", 0 );
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
	 * @since 2.0.0
	 *
	 * @global $wpdb WPDB
	 *
	 * @param string $option Option name
	 * @param mixed $old_value Previous option value
	 * @param mixed $value New option value
	 */
	public function update_entity_parent( $option, $old_value, $value ) {
		global $wpdb;

		// Bail when this is not our option
		if ( $option !== "_{$this->type}-parent-page" )
			return;

		// Run single update query for entities' post_parent
		$wpdb->update(
			$wpdb->posts,
			array( 'post_parent' => $value ),
			array( 'post_type' => $this->type ),
			array( '%d' ),
			array( '%s' )
		);

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

	/** Meta ***********************************************************/

	/**
	 * Return the input markup for the entity's meta field
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Meta key
	 * @param int|WP_Post $post Post object
	 * @return string Meta input field
	 */
	public function meta_input_field( $key, $post ) {

		// Define field variables
		$meta          = $this->meta[ $key ];
		$meta['id']    = esc_attr( "{$this->type}_{$post->ID}_{$meta['name']}" );
		$meta['value'] = esc_attr( $this->get( $key, $post, 'edit' ) );

		// Start output buffer
		ob_start();

		// Output field per type
		switch ( $meta['type'] ) {

			// Year
			case 'year' : ?>

		<label class="alignleft">
			<span class="title"><?php echo esc_html( $meta['column_title'] ); ?></span>
			<span class="input-text-wrap"><input type="number" size="4" placeholder="<?php esc_html_e( 'YYYY', 'vgsr-entity' ); ?>" name="<?php echo esc_attr( $meta['name'] ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" min="<?php echo esc_attr( vgsr_entity()->base_year ); ?>" max="<?php echo date( 'Y' ); ?>" /></span>
		</label>

				<?php
				break;

			// Date
			case 'date' : ?>

		<label class="alignleft">
			<span class="title"><?php echo esc_html( $meta['column_title'] ); ?></span>
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

		// Default to a text field
		if ( empty( $field ) ) {
			ob_start(); ?>

		<label class="alignleft">
			<span class="title"><?php echo esc_html( $meta['column_title'] ); ?></span>
			<span class="input-text-wrap"><input type="text" name="<?php echo esc_attr( $meta['name'] ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" /></span>
		</label>

			<?php

			$field = ob_get_clean();
		}

		return $field;
	}

	/**
	 * Return the requested entity meta value
	 *
	 * Override this method in a child class.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Meta key
	 * @param int|WP_Post $post Optional. Defaults to current post.
	 * @param string $context Optional. Context, defaults to 'display'.
	 * @return null
	 */
	public function get( $key, $post = 0, $context = 'display' ) {

		// Define default value
		$value   = null;
		$display = ( 'display' === $context ) && ! is_admin();

		// Bail when no post was found
		if ( ! $post = get_post( $post ) )
			return $value;

		// Get value
		$value = get_post_meta( $post->ID, $key, true );

		// Consider meta type
		switch ( $this->meta[ $key ]['type'] ) {

			// Date
			case 'date' :
				$date = DateTime::createFromFormat( 'Y-m-d', $value );
				if ( ! $date )
					break;

				if ( $display ) {
					$value = $date->format( get_option( 'date_format' ) );
				} else {
					$value = $date->format( 'Y/m/d' );
				}
				break;

			// Postcode
			case 'postcode' :
				if ( $display && $value ) {
					$value = substr( $value, 0, 4 ) . ' ' . substr( $value, 4 );
				}
				break;

			// Phone Number
			case 'phone' :

				// Display clickable call link
				if ( $display && $value ) {
					$tel = preg_replace( '/^0/', '+31', str_replace( '-', '', $value ) );
					// HTML5 uses `tel`, but Skype uses `callto`
					$value = sprintf( '<a href="' . ( wp_is_mobile() ? 'callto' : 'tel' ) . ':%s">%s</a>', $tel, $value );
				}
				break;
		}

		return $value;
	}

	/**
	 * Sanitize the given entity meta value
	 *
	 * Overwrite this method in a child class.
	 *
	 * @since 2.0.0
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

					// Number
					case 'number' :
						$value = absint( $value );
						break;

					// Year
					case 'year' :
						$value = (int) $value;
						// Expect an integer between the base and current year
						$error = ( vgsr_entity()->base_year > $value || $value > date( 'Y' ) );
						break;

					// Date
					case 'date' :
						// Expect Y/m/d, transform to Y-m-d, which can be sorted.
						$date  = DateTime::createFromFormat( 'Y/m/d', $value );

						if ( $date ) {
							$value = $date->format( 'Y-m-d' );
						} else {
							$error = true;
						}
						break;

					// Postcode
					case 'postcode' :
						// Strip spaces, uppercase
						$value = strtoupper( str_replace( ' ', '', trim( $value ) ) );
						// Expect a string in the form of 9999YZ
						$error = ! preg_match( "/^[0-9]{4}[A-Z]{2}/", $value );
						break;

					// Phone Number
					case 'phone' :
						// Strip all non-numeric chars
						$value = preg_replace( '/\D/', '', trim( $value ) );

						// Starts with 31
						if ( '31' === substr( $value, 0, 2 ) ) {
							$value = '0' . substr( $value, 2 );
						}

						// Expect a 10-digit number
						if ( $error = ( 10 != strlen( $value ) ) )
							break;

						// Define number prefixes
						$prefixes = dutch_net_numbers();
						$prefixes[] = '06';

						// Find the prefix applied
						foreach ( $prefixes as $prefix ) {
							if ( $prefix === substr( $value, 0, strlen( $prefix ) ) ) {
								$value = str_replace( $prefix, "{$prefix}-", $value );
								break;
							}
						}
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
	 * @since 2.0.0
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

					/**
					 * Filter an entity's meta fields for display
					 *
					 * @since 2.0.0
					 *
					 * @param array $meta Meta fields with details
					 * @param WP_Post $post Post object
					 */
					$meta = apply_filters( "vgsr_entity_display_meta", $meta, $post );

					/**
					 * Filter an entity's meta fields for display
					 *
					 * The variable part `$type` is the entity's post type.
					 *
					 * @since 2.0.0
					 *
					 * @param array $meta Meta fields with details
					 * @param WP_Post $post Post object
					 */
					return apply_filters( "vgsr_{$this->type}_display_meta", $meta, $post );
				}

				break;
		}

		return array();
	}
}

endif; // class_exsits
