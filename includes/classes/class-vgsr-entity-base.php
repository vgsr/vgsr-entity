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
	 * Construct the VGSR Entity
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Rearranged parameters and added `$meta` parameter.
	 * 
	 * @param string $type Post type name. Required
	 * @param array $args Entity and post type arguments
	 * @param array $meta Meta field arguments
	 *
	 * @uses VGSR_Entity_Base::entity_globals()
	 * @uses VGSR_Entity_Base::entity_actions()
	 * @uses VGSR_Entity_Base::setup_globals()
	 * @uses VGSR_Entity_Base::setup_requires()
	 * @uses VGSR_Entity_Base::setup_actions()
	 */
	public function __construct( $type, $args = array(), $meta = array() ) {

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
			'hook'       => '',
			'settings'   => array(
				'page'    => "vgsr_{$type}_settings",
				'section' => "vgsr_{$type}_options_main",
			),
		) );

		// Set meta fields
		$this->meta = $meta;

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
		add_action( 'admin_init',                        array( $this, 'entity_register_settings' ) );
		add_action( 'admin_menu',                        array( $this, 'entity_admin_menu'        ) );
		add_action( 'admin_enqueue_scripts',             array( $this, 'enqueue_scripts'          ) );
		add_action( 'admin_notices',                     array( $this, 'entity_admin_notices'     ) );
		add_filter( "vgsr_{$this->type}_admin_messages", array( $this, 'admin_messages'           ) );

		// Post
		add_filter( "manage_edit-{$this->type}_columns", array( $this, 'meta_columns'          )        );
		add_filter( 'hidden_columns',                    array( $this, 'hide_columns'          ), 10, 2 );
		add_action( 'quick_edit_custom_box',             array( $this, 'quick_edit_custom_box' ), 10, 2 );
		add_filter( 'wp_insert_post_parent',             array( $this, 'filter_entity_parent'  ), 10, 4 );
		foreach ( array_keys( $this->meta ) as $key ) {
			add_filter( "sanitize_post_meta_{$key}", array( $this, 'save' ), 10, 2 );
		}

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

		// Output nonce verification field
		wp_nonce_field( vgsr_entity()->file, "vgsr_entity_{$this->type}_meta_nonce" );

		// Walk all meta fields
		foreach ( $this->meta as $key => $args ) {
			
			// Define field variables
			$id    = esc_attr( "{$this->type}_{$args['name']}" );
			$value = $this->get( $key, $post, 'edit' );

			// Output field per type
			switch ( $args['type'] ) {

				// Year
				case 'year' : ?>

		<label for="<?php echo $id; ?>"><?php echo esc_html( $args['label'] ); ?></label>
		<input id="<?php echo $id; ?>" type="number" size="4" placeholder="<?php esc_html_e( 'YYYY', 'vgsr-entity' ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( vgsr_entity()->base_year ); ?>" max="<?php echo date( 'Y' ); ?>" />

					<?php
					break;

				// Date
				case 'date' : ?>

		<label for="<?php echo $id; ?>"><?php echo esc_html( $args['label'] ); ?></label>
		<input id="<?php echo $id; ?>" class="ui-widget-content ui-corner-all datepicker" type="text" placeholder="<?php esc_html_e( 'DD/MM/YYYY', 'vgsr-entity' ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $value ); ?>" />

					<?php
					break;
			}
		}

		do_action( "vgsr_{$this->type}_metabox", $post );
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
		$this->args['hook'] = add_submenu_page( $this->args['page'], $this->args['labels']['settings_title'], __( 'Settings' ), 'manage_options', "{$this->type}-settings", array( $this, 'settings_page' ) );

		// Setup settings specific hooks
		add_action( "load-{$this->args['hook']}",         array( $this, 'settings_load'   ), 9  );
		add_action( "admin_footer-{$this->args['hook']}", array( $this, 'settings_footer' )     );
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
	 * @uses do_action() Calls 'vgsr_{$post_type}_settings_enqueue_scripts'
	 */
	public function enqueue_scripts( $page_hook ) {

		// Define local variables
		$screen      = get_current_screen();
		$is_edit     = "edit-{$this->type}" === $screen->id;
		$is_post     = $this->type === $screen->id;
		$is_settings = $page_hook === $this->args['hook'];

		// When on an entity admin page
		if ( $is_edit || $is_post || $is_settings ) {
			$entity = vgsr_entity();

			// Enqueue date picker
			if ( wp_list_filter( $this->meta, array( 'type' => 'date' ) ) ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_style( 'jquery-ui-theme-fresh', $entity->includes_url . 'assets/css/jquery.ui.theme.css' );
			}

			// Enqueue admin scripts
			wp_enqueue_style( 'vgsr-entity-admin', $entity->includes_url . 'assets/css/admin.css' );
			wp_enqueue_script( 'vgsr-entity-admin', $entity->includes_url . 'assets/js/admin.js', array( 'jquery' ), '1.1.0', true );
		}

		// Run hook when we're on the settings page
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

	/**
	 * Output the admin messages if requested
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls '{$post_type}_admin_messages'
	 */
	public function entity_admin_notices() {

		// Define error key
		$error_key = "{$this->type}-error";

		// Bail when no valid errors are reported
		if ( ! isset( $_REQUEST[ $error_key ] ) || empty( $_REQUEST[ $error_key ] ) )
			return;

		// Get the message number
		$num = trim( $_REQUEST[ $error_key ] );

		// The messages to pick from
		$messages = apply_filters( "vgsr_{$this->type}_admin_messages", array(
			0 => '' // Default empty
		) );

		// Print available message
		if ( isset( $messages[ $num ] ) && ! empty( $messages[ $num ] ) ) {
			printf( '<div class="error message"><p>%s</p></div>', $messages[ $num ] );
		}
	}

	/**
	 * Return the custom admin messages
	 *
	 * Should be overriden in child class.
	 *
	 * @since 1.0.0
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

	/** Theme **********************************************************/

	/**
	 * Return the requested entity meta value
	 *
	 * Override the `_get()` method in a child class to use this.
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
		$value = null;

		// Bail when no post was found
		if ( $post = get_post( $post ) ) {
			$value = $this->_get( $key, $post, $context );
		}

		return $value;
	}

	/**
	 * Abstract method to return entity meta
	 *
	 * @since 1.1.0
	 */
	abstract protected function _get( $key, $post, $context );

	/**
	 * Sanitize the given entity meta value
	 *
	 * Overwrite this method in a child class.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $value Meta value
	 * @param string $key Meta key
	 * @return mixed Meta value
	 */
	public function save( $value, $key ) {
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
	public function meta( $context = 'raw' ) {
		switch ( $context ) {
			case 'raw' :
				return $this->meta;
				break;
			case 'display' :
				if ( is_callable( array( $this, 'get_meta' ) ) ) {
					return $this->get_meta();
				}
		}

		return array();
	}
}

endif; // class_exsits
