<?php

/**
 * VGSR Entity Type Administration Class
 * 
 * @package VGSR Entity
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_Type_Admin' ) ) :
/**
 * Single entity type administration class
 *
 * @since 2.0.0
 */
class VGSR_Entity_Type_Admin {

	/**
	 * Holds tye entity type name.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $type = '';

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
	 * Construct the VGSR Entity Admin
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Entity type name
	 */
	public function __construct( $type ) {
		$this->type = $type;

		$this->setup_globals();
		$this->includes();
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
		return null !== $this->type()->{$key} || isset( $this->{$key} );
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
		$value = $this->type()->{$key};

		if ( null === $value && isset( $this->{$key} ) ) {
			$value = $this->{$key};
		}

		return $value;
	}

	/**
	 * Define class globals
	 *
	 * @since 2.0.0
	 */
	protected function setup_globals() { /* Overwrite this method in a child class */ }

	/**
	 * Include required class files
	 *
	 * @since 2.0.0
	 */
	protected function includes() { /* Overwrite this method in a child class */ }

	/**
	 * Setup class actions and filters
	 *
	 * @since 2.0.0
	 */
	protected function setup_actions() {

		// Core
		add_action( 'admin_menu',            array( $this, 'entity_admin_menu'     )     );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' )     );
		add_action( 'admin_notices',         array( $this, 'admin_display_errors'  )     );
		add_action( 'admin_bar_menu',        array( $this, 'admin_bar_menu'        ), 80 );

		// Settings
		add_action( 'vgsr_entity_admin_init',      array( $this, 'entity_register_settings' ) );
		add_action( 'vgsr_entity_settings_fields', array( $this, 'add_settings_fields'      ) );

		// Post
		add_action( "vgsr_{$this->type}_metabox", array( $this, 'details_metabox' )        );
		add_action( "save_post_{$this->type}",    array( $this, 'save_metabox'    ), 10, 2 );

		// List Table
		add_filter( "manage_edit-{$this->type}_columns",        array( $this, 'table_columns'         )        );
		add_filter( 'hidden_columns',                           array( $this, 'hide_columns'          ), 10, 2 );
		add_action( "manage_{$this->type}_posts_custom_column", array( $this, 'column_content'        ), 10, 2 );
		add_action( 'display_post_states',                      array( $this, 'post_states'           ), 10, 2 );
		add_action( 'quick_edit_custom_box',                    array( $this, 'quick_edit_custom_box' ), 10, 2 );

		// Menus
		add_filter( "nav_menu_items_{$this->post_type}", 'vgsr_entity_nav_menu_items_metabox', 10, 3 );
	}

	/** Entity Type ****************************************************/

	/**
	 * Return the entity type object
	 *
	 * @since 2.0.0
	 *
	 * @return VGSR_Entity_Type
	 */
	private function type() {
		return vgsr_entity()->{$this->type};
	}

	/**
	 * Return the raw entity type meta details
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_type_meta() {
		return $this->type()->meta();
	}

	/** Settings *******************************************************/

	/**
	 * Add entity settings fields for registration
	 *
	 * @since 2.0.0
	 *
	 * @param array $fields Settings fields
	 * @return array Settings fields
	 */
	public function add_settings_fields( $fields ) {

		// Get settings fields
		$entity_fields = function_exists( "vgsr_entity_{$this->type}_settings_fields" )
			? call_user_func( "vgsr_entity_{$this->type}_settings_fields" )
			: array();

		// Walk entity settings fields
		foreach ( $entity_fields as $field_key => $args ) {

			// Default to main section
			$section = isset( $args['section'] ) ? $args['section'] : 'main';

			// Add settings field to section
			$fields[ $section ][ $field_key ] = $args;
		}

		return $fields;
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
		foreach ( $this->get_type_meta() as $key => $args ) {
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

		// Hide entity meta columns for our entity's edit.php page
		if ( "edit-{$this->post_type}" === $screen->id ) {
			$columns = array_merge( $columns, array_keys( wp_list_filter( $this->get_type_meta(), array( 'column-hide' => true ) ) ) );
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
		if ( in_array( $column, array_keys( $this->get_type_meta() ) ) ) {
			$type = $this->type();

			// Output display value
			echo $type->get( $column, $post_id );

			// Output value for edit
			if ( current_user_can( get_post_type_object( $this->post_type )->cap->edit_post, $post_id ) ) {
				printf( '<p class="edit-value hidden">%s</p>', $type->get( $column, $post_id, 'edit' ) );
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

		// Append state for the entity parent page
		if ( 'page' === $post->post_type && vgsr_is_entity_parent( $post ) === $this->type ) {
			$states[] = get_post_type_object( vgsr_entity_get_post_type( $this->type ) )->labels->name;
		}

		// Append a state for archived posts
		if ( $this->post_type === $post->post_type && $this->has_archive && vgsr_entity_get_archived_status_id() === $post->post_status ) {
			$states[] = esc_html__( 'Archived', 'vgsr-entity' );
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
		$meta = $this->get_type_meta();

		// When editing this entity and our meta field
		if ( "edit-{$this->post_type}" === get_current_screen()->id && in_array( $column, array_keys( $meta ) ) ) {

			// Get dummy post data
			$post = get_default_post_to_edit( $post_type );

			// Output the meta input field
			$field = $this->meta_input_field( $column, $post );

			if ( $field ) : ?>

				<fieldset class="inline-edit-col-right entity-quick-edit">
					<div class="inline-edit-col <?php echo "{$post_type}-{$meta[ $column ]['type']}"; ?>">
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
			$this->posts_page,
			$this->labels->settings_title,
			esc_html__( 'Settings', 'vgsr-entity' ),
			'manage_options',
			"{$this->type}-settings",
			array( $this, 'settings_page' )
		);

		// Setup settings page hooks
		add_action( "load-{$hook}",         array( $this, 'settings_load'   ), 9 );
		add_action( "admin_footer-{$hook}", array( $this, 'settings_footer' )    );

		// Store $hook in class global
		$this->settings_page = $hook;
	}

	/**
	 * Create admin page load hook
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_{$type}_settings_load'
	 */
	public function settings_load() {
		do_action( "vgsr_{$this->type}_settings_load" );
	}

	/**
	 * Create admin footer hook
	 *
	 * @since 1.0.0
	 *
	 * @uses do_action() Calls 'vgsr_{$type}_settings_footer'
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
		$is_edit = "edit-{$this->post_type}" === $screen->id;
		$is_post = 'post' === $screen->base && $this->post_type === $screen->id;

		// Bail when not on an entity admin page
		if ( ! $is_edit && ! $is_post )
			return;

		// Get VGSR Entity
		$entity    = vgsr_entity();
		$type_meta = $this->get_type_meta();

		// Date fields: Enqueue date picker
		if ( wp_list_filter( $type_meta, array( 'type' => 'date' ) ) ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'stuttter-datepicker', $entity->assets_url . 'css/datepicker.css' );
		}

		// Enqueue admin scripts
		wp_enqueue_style( 'vgsr-entity-admin', $entity->assets_url . 'css/admin.css' );
		wp_enqueue_script( 'vgsr-entity-admin', $entity->assets_url . 'js/admin.js', array( 'jquery' ), '2.0.0', true );

		// When on the edit view
		if ( $is_edit ) {

			// Define additional column styles
			$css = '';
			foreach ( $type_meta as $key => $args ) {
				$width = ! empty( $args['column-width'] ) ? $args['column-width'] : '10%';
				$css .= ".fixed .column-{$key} { width: {$width} }\n";
			}

			// Append additional styles
			wp_add_inline_style( 'vgsr-entity-admin', $css );
		}

		// When on the edit or post view
		if ( $is_edit || $is_post ) {

			// Prepare meta for js
			$meta = $type_meta;
			foreach ( array_keys( $meta ) as $key ) {
				$meta[$key]['key'] = $key;
			}

			// Send data to admin js
			wp_localize_script( 'vgsr-entity-admin', 'entityEditPost', array(
				'l10n'   => array(

					// Archive post status
					'archiveStatusId' => vgsr_entity_get_archived_status_id(),
					'archiveLabel'    => esc_html__( 'Archived', 'vgsr-entity' ),
					'publishStatusId' => 'publish',
					'publishLabel'    => esc_html__( 'Published' ),
					'hasArchive'      => (bool) $this->has_archive,
					'isArchived'      => $is_post ? ( vgsr_entity_get_archived_status_id() === get_post()->post_status ) : false,

					// Logo feature
					'entityLogoTitle' => sprintf( esc_html__( '%s Logo',     'vgsr-entity' ), $this->labels->singular_name ),
					'setEntityLogo'   => sprintf( esc_html__( 'Set %s Logo', 'vgsr-entity' ), $this->labels->singular_name ),
				),
				'fields' => array_values( $meta ),
			) );
		}
	}

	/**
	 * Enqueue settings scripts and styles
	 *
	 * @since 2.0.0
	 */
	public function enqueue_settings_scripts() {
		wp_enqueue_style( 'vgsr-entity-admin', vgsr_entity()->assets_url . 'css/admin.css' );
	}

	/**
	 * Output entity settings page
	 *
	 * @since 1.0.0
	 */
	public function settings_page() { ?>

		<div class="wrap">
			<h1><?php echo $this->labels->settings_title; ?></h1>

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
	 * Modify the admin bar menu
	 *
	 * @since 2.0.1
	 *
	 * @global string $hook_suffix
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
		global $hook_suffix;

		if ( is_admin() ) {

			// Add View Items menu for the settings page
			if ( $hook_suffix === $this->settings_page ) {
				$post_type_object = get_post_type_object( $this->post_type );
				$wp_admin_bar->add_node(
					array(
						'id'    => 'archive',
						'title' => $post_type_object->labels->view_items,
						'href'  => get_post_type_archive_link( $this->post_type ),
					)
				);
			}
		}
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

	/** Post ***********************************************************/

	/**
	 * Output the contents of the details metabox
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post
	 */
	public function details_metabox( $post ) {

		// Walk all meta fields
		foreach ( $this->get_type_meta() as $key => $meta_args ) {

			// Get the meta input field
			$field = $this->meta_input_field( $key, $post );

			// Output field and its nonce
			if ( $field ) : ?>

			<p class="<?php echo "{$this->post_type}-{$meta_args['type']}"; ?>"><?php
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
		foreach ( $this->get_type_meta() as $key => $args ) {

			// Bail when the nonce does not verify
			if ( ! isset( $_POST["vgsr_{$this->type}_meta_nonce_{$key}"] )
				|| ! wp_verify_nonce( $_POST["vgsr_{$this->type}_meta_nonce_{$key}"], vgsr_entity()->file )
			)
				continue;

			$value = isset( $_POST[ $args['name'] ] ) ? $_POST[ $args['name'] ] : null;

			// Save entity meta value
			$this->type()->save( $key, $value, $post );
		}

		// Report errors
		if ( $this->has_errors() ) {
			add_filter( 'redirect_post_location', array( $this, 'add_error_query_arg' ) );
		}
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
		$type_meta     = $this->get_type_meta();
		$meta          = $type_meta[ $key ];
		$meta['id']    = esc_attr( "{$this->type}_{$post->ID}_{$meta['name']}" );
		$meta['value'] = esc_attr( $this->type()->get( $key, $post, 'edit' ) );

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
}

endif; // class_exsits
