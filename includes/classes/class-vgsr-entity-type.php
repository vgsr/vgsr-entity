<?php

/**
 * VGSR Entity Type Class
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_Type' ) ) :
/**
 * Single Entity Type base class
 *
 * @since 1.0.0
 */
abstract class VGSR_Entity_Type {

	/**
	 * Holds tye entity type name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $type = '';

	/**
	 * Holds tye entity post type
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $post_type = '';

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
	 * Construct the VGSR Entity
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Rearranged parameters and added `$meta` parameter.
	 *
	 * @uses apply_filters() Calls 'vgsr_entity_{$type}_post_type'
	 *
	 * @param string $type Post type name. Required
	 * @param array $args {
	 *     Optional. Array of entity arguments
	 *
	 *     @type string $path          Relative or absolute path for referencing includable files. Defaults to none.
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

		// Bail when type name contains invalid chars
		if ( sanitize_key( $type ) !== $type ) {
			_doing_it_wrong( 'VGSR_Entity_Type', 'The provided entity type name contains invalid characters', '2.0.0' );
			return;
		}

		// Set entity type
		$this->type = $type;

		// Set entity args
		$this->args = wp_parse_args( $args, array(
			'path'           => '',
			'post_type'      => $type,
			'post_type_args' => array(),
			'has_archive'    => false,
			'features'       => array( 'logo' ),
			'parent'         => null,
			'thumbsize'      => 'post-thumbnail',

			// Admin
			'admin_class'    => 'VGSR_Entity_Type_Admin',
			'posts_page'     => "edit.php?post_type={$type}",
			'settings_page'  => '',

			// Back-compat
			'labels'         => array(),
			'menu_icon'      => '',
		) );

		// Set post type
		$this->post_type = apply_filters( "vgsr_entity_{$this->type}_post_type", $this->args['post_type'] );

		// Set meta fields
		$this->meta = $meta;

		// Set error messages
		$this->errors = wp_parse_args( $errors, array(
			1 => esc_html__( 'Some of the provided values were not given in the valid format.', 'vgsr-entity' ),
		) );

		// Set entity query
		$this->query = new WP_Query;

		// Setup entity globals
		$this->setup_globals();

		// Include entity files
		$this->includes();

		// Setup entity actions
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
				case 'labels' :
					return get_post_type_object( $this->post_type )->labels;
				case 'menu_icon' :
					return get_post_type_object( $this->post_type )->menu_icon;
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

	/** Setup Type *****************************************************/

	/**
	 * Define child class globals
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_entity_{$type}_setup_globals'
	 */
	public function setup_globals() {

		/** Paths ******************************************************/

		if ( ! empty( $this->args['path'] ) ) {

			// Includes
			$this->includes_dir  = trailingslashit( vgsr_entity()->includes_dir . $this->args['path']  );
			$this->includes_url  = trailingslashit( vgsr_entity()->includes_url . $this->args['path']  );
		}

		do_action( "vgsr_entity_{$this->type}_setup_globals" );
	}

	/**
	 * Include required child class files
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_entity_{$type}_includes'
	 *
	 * @param array $includes Filenames to include
	 */
	public function includes( $includes = array() ) {

		if ( ! empty( $includes ) ) {
			foreach ( $includes as $file ) {
				require( $this->includes_dir . $file . '.php' );
			}
		}

		do_action( "vgsr_entity_{$this->type}_includes" );
	}

	/**
	 * Setup child class actions and filters
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_entity_{$type}_setup_actions'
	 */
	public function setup_actions() {

		// Post type and features
		add_action( 'vgsr_entity_init', array( $this, 'register_post_type' ), 5 );
		add_action( 'vgsr_entity_init', array( $this, 'setup_features'     )    );

		// Parent
		add_action( 'update_option',         array( $this, 'update_entity_parent' ), 10, 3 );
		add_filter( 'wp_insert_post_parent', array( $this, 'filter_entity_parent' ), 10, 4 );

		// Template
		add_filter( 'vgsr_entity_get_the_posts_navigation', array( $this, 'get_the_posts_navigation' ) );

		// Admin
		if ( is_admin() ) {
			add_action( 'vgsr_entity_init', array( $this, 'admin_init' ) );
		}

		do_action( "vgsr_entity_{$this->type}_setup_actions" );
	}

	/** Post Type ******************************************************/

	/**
	 * Register the post type
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'vgsr_entity_{$type}_register_post_type'
	 */
	public function register_post_type() {

		// Setup labels
		$labels = function_exists( "vgsr_entity_get_{$this->type}_post_type_labels" )
			? call_user_func( "vgsr_entity_get_{$this->type}_post_type_labels" )
			: $this->args['labels'];

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
			$this->post_type,
			(array) apply_filters( "vgsr_entity_{$this->type}_register_post_type", wp_parse_args( (array) $this->post_type_args, array(
				'labels'               => $labels,
				'public'               => true,
				'menu_position'        => vgsr_entity()->menu_position,
				'hierarchical'         => false,
				'capability_type'      => 'page',
				'rewrite'              => $rewrite,
				'supports'             => $supports,
				'menu_icon'            => $this->args['menu_icon'],
				'register_meta_box_cb' => array( $this, 'add_metabox' ),
				'vgsr-entity'          => $this->type
			) ) )
		);
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
			'vgsr-entity-details',
			sprintf( esc_html__( '%s Details', 'vgsr-entity' ), $this->labels->singular_name ),
			/**
			 * Run only a dedicated action in the metabox
			 *
			 * The `type` variable in the action name points to the entity type name.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Post $post Post object
			 */
			function( $post ){ do_action( "vgsr_{$this->type}_metabox", $post ); },
			$this->post_type,
			'side',
			'high'
		);
	}

	/**
	 * Return the entity's option field value
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Setting key
	 * @return mixed|bool Setting value or False when not found
	 */
	public function get_setting( $key ) {
		return get_option( "_{$this->type}-{$key}", false );
	}

	/** Features *******************************************************/

	/**
	 * Setup main logic for the logo feature
	 *
	 * @since 2.0.0
	 */
	public function setup_features() {

		// Logo feature
		if ( vgsr_entity_supports( 'logo', $this->type ) ) {

			// Define logo image size
			add_image_size( 'entity-logo', 500, 500, 1 );

			// Post actions
			add_action( "vgsr_{$this->type}_metabox",   'vgsr_entity_feature_logo_metabox',         8    );
			add_action( 'wp_ajax_vgsr_entity_set_logo', 'vgsr_entity_feature_logo_save'                  );
			add_filter( 'media_view_settings',          'vgsr_entity_feature_logo_media_settings', 10, 2 );

			// List table actions
			add_filter( "manage_edit-{$this->post_type}_columns",        'vgsr_entity_feature_logo_list_column'                );
			add_action( "manage_{$this->post_type}_posts_custom_column", 'vgsr_entity_feature_logo_list_column_content', 10, 2 );

			// Post details
			add_action( "vgsr_entity_{$this->type}_details", 'vgsr_entity_feature_logo_detail', 5 );
		}
	}

	/** Admin **********************************************************/

	/**
	 * Initiate entity type administration
	 *
	 * @since 2.0.0
	 */
	public function admin_init() {

		// Load type admin base class
		require_once( vgsr_entity()->includes_dir . 'classes/class-vgsr-entity-type-admin.php' );

		// Load entity admin class
		if ( ! empty( $this->args['admin_class'] ) && class_exists( $this->args['admin_class'] ) ) {
			$this->admin = new $this->args['admin_class']( $this->type );
		}

		do_action( "vgsr_entity_{$this->type}_admin_init" );
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

			// Get and check the parent post
			$post = (int) get_option( "_{$this->type}-parent-page" );
			$post = $post ? get_post( $post ) : false;

			// Default non-parents to false
			$this->args['parent'] = $post ? (int) $post->ID : false;
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
		if ( $new_postarr['post_type'] === $this->post_type ) {
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
			array( 'post_type' => $this->post_type ),
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

	/** Template *******************************************************/

	/**
	 * Modify the paramaters for the posts navigation
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Posts navigation arguments
	 * @return array Posts navigation arguments
	 */
	public function get_the_posts_navigation( $args ) {

		// Post type archive navigation
		if ( is_post_type_archive( $this->post_type ) && isset( $this->post_type_args['posts_navigation'] ) ) {
			$args = wp_parse_args( $this->post_type_args['posts_navigation'], $args );
		}

		return $args;
	}

	/** Meta ***********************************************************/

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

						// Get display value
						$value = $this->get( $key, $post, $context );

						// Remove field from display when there's nothing to show
						if ( empty( $value ) ) {
							unset( $meta[ $key ] ) ;

						// Add when there is a value to display
						} else {
							$meta[ $key ]['raw']   = $this->get( $key, $post, 'raw' );
							$meta[ $key ]['value'] = $value;
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
					$meta = apply_filters( 'vgsr_entity_display_meta', $meta, $post );

					/**
					 * Filter an entity's meta fields for display
					 *
					 * The variable part `$type` is the entity's type name.
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
}

endif; // class_exsits
