<?php

/**
 * VGSR Entity BuddyPress functions
 * 
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_BuddyPress' ) ) :
/**
 * The VGSR Entity BuddyPress Class
 *
 * @since 1.1.0
 */
class VGSR_Entity_BuddyPress {

	/**
	 * Class constructor
	 *
	 * @since 1.1.0
	 *
	 * @uses VGSR_Entity_BuddyPress::setup_actions()
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup actions and filters
	 *
	 * @since 1.1.0
	 */
	private function setup_actions() {

		// Settings
		add_filter( 'vgsr_entity_settings_sections', array( $this, 'add_settings_sections' ) );
		add_filter( 'vgsr_entity_settings_fields',   array( $this, 'add_settings_fields'   ) );

		// List Table
		add_filter( 'admin_init', array( $this, 'add_list_table_columns' ) );

		// Post
		add_filter( 'vgsr_entity_display_meta', array( $this, 'display_meta' ), 10, 2 );
	}

	/** Settings **************************************************************/

	/**
	 * Add to the admin settings sections
	 *
	 * @since 1.1.0
	 *
	 * @param array $sections Settings sections
	 * @return array Settings sections
	 */
	public function add_settings_sections( $sections ) {

		// Add BuddyPress section
		$sections['buddypress'] = array(
			'title'    => esc_html__( 'BuddyPress Settings', 'vgsr-entity' ),
			'callback' => '',
		);

		return $sections;
	}

	/**
	 * Add to the admin settings fields
	 *
	 * @since 1.1.0
	 *
	 * @uses bp_is_active()
	 *
	 * @param array $fields Settings fields
	 * @return array Settings fields
	 */
	public function add_settings_fields( $fields ) {

		// Define local variable
		$bp_fields = array();

		// When using XProfile
		if ( bp_is_active( 'xprofile' ) ) {

			// Entity members
			$bp_fields['bp-members-field'] = array(
				'title'             => esc_html__( 'Members Field', 'vgsr-entity' ),
				'callback'          => array( $this, 'xprofile_field_setting' ),
				'sanitize_callback' => 'intval',
				'entity'            => array( 'dispuut', 'kast' ),
				'column_title'      => esc_html__( 'Members', 'vgsr-entity' ),
				'label'             => esc_html__( '%d Members', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-members-field',
					'description' => esc_html__( 'Select the field that holds the %s members.', 'vgsr-entity' ),
				),
				'display'           => true,
			);

			// Entity olim members
			$bp_fields['bp-olim-members-field'] = array(
				'title'             => esc_html__( 'Former Members Field', 'vgsr-entity' ),
				'callback'          => array( $this, 'xprofile_field_setting' ),
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Former Members', 'vgsr-entity' ),
				'label'             => esc_html__( '%d Former Members', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-olim-members-field',
					'description' => esc_html__( 'Select the field that holds the %s former members.', 'vgsr-entity' ),
				),
			);
		}

		// Add fields to the BuddyPress section
		$fields['buddypress'] = $bp_fields;

		return $fields;
	}

	/**
	 * Display the members settings field
	 *
	 * @since 1.1.0
	 *
	 * @uses get_post_type_object()
	 * @uses VGSR_Entity_BuddyPress::xprofile_fields_dropdown()
	 * @uses xprofile_get_field()
	 * @uses network_admin_url() Defaults to `admin_url()` when not in multisite
	 */
	public function xprofile_field_setting( $args = array() ) {

		// Get current post type
		$post_type = get_current_screen()->post_type;
		$setting   = "_{$post_type}-{$args['setting']}";
		$field     = get_option( $setting, false );

		// Fields dropdown
		$this->xprofile_fields_dropdown( array(
			'name'     => $setting,
			'selected' => $field,
			'echo'     => true,
		) );

		// Display View link
		if ( $field && current_user_can( 'bp_moderate' ) ) {
			printf( ' <a class="button button-secondary" href="%s" target="_blank">%s</a>', 
				esc_url( add_query_arg(
					array(
						'page'     => 'bp-profile-setup',
						'group_id' => xprofile_get_field( $field )->group_id,
						'field_id' => $field,
						'mode'     => 'edit_field'
					),
					network_admin_url( 'users.php' )
				) ),
				esc_html__( 'View', 'vgsr-entity' )
			);
		} ?>

		<p class="description"><?php printf( $args['description'], get_post_type_object( $post_type )->labels->name ); ?></p>

		<?php
	}

	/**
	 * Output or return a dropdown with XProfile fields
	 *
	 * @since 1.1.0
	 *
	 * @uses bp_xprofile_get_groups()
	 *
	 * @param array $args Dropdown arguments
	 * @return void|string Dropdown markup
	 */
	public function xprofile_fields_dropdown( $args = array() ) {

		// Parse default args
		$args = wp_parse_args( $args, array(
			'id' => '', 'name' => '', 'multiselect' => false, 'selected' => 0, 'echo' => false,
		) );

		// Bail when missing attributes
		if ( empty( $args['name'] ) )
			return '';

		// Default id attribute to name
		if ( empty( $args['id'] ) ) {
			$args['id'] = $args['name'];
		}

		// Get all field groups with their fields
		$xprofile = bp_xprofile_get_groups( array( 'fetch_fields' => true, 'hide_empty_groups' => true ) );

		// Start dropdown markup
		$dd  = sprintf( '<select id="%s" name="%s" %s>', esc_attr( $args['id'] ), esc_attr( $args['name'] ), $args['multiselect'] ? 'multiple="multiple"' : '' );
		$dd .= '<option value="">' . __( '&mdash; No Field &mdash;', 'vgsr-entity' )  . '</option>';

		// Walk profile groups
		foreach ( $xprofile as $field_group ) {

			// Start optgroup
			$dd .= sprintf( '<optgroup label="%s">', esc_attr( $field_group->name ) );

			// Walk profile group fields
			foreach ( $field_group->fields as $field ) {
				$dd .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $field->id ), selected( $args['selected'], $field->id, false ), esc_attr( $field->name ) );
			}

			// Close optgroup
			$dd .= '</optgroup>';
		}

		// Close dropdown
		$dd .= '</select>';

		if ( $args['echo'] ) {
			echo $dd;
		} else {
			return $dd;
		}
	}

	/** List Table ************************************************************/

	/**
	 * Setup hooks for the entities' list tables
	 *
	 * @since 1.1.0
	 *
	 * @uses vgsr_entity_settings_fields()
	 * @uses VGSR_Entity::get_entities()
	 * @uses add_filter()
	 * @uses add_action()
	 */
	public function add_list_table_columns() {

		// Get registered fields
		$fields = vgsr_entity_settings_fields();

		// When BP settings fields are present
		if ( ! empty( $fields['buddypress'] ) ) {

			// Setup column hooks for all entities
			foreach ( vgsr_entity()->get_entities() as $post_type ) {
				add_filter( "manage_edit-{$post_type}_columns",        array( $this, 'table_columns'  )        );
				add_filter( "manage_{$post_type}_posts_custom_column", array( $this, 'column_content' ), 10, 2 );
			}

			// Enqueue scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}
	}

	/**
	 * Modify the current screen's columns
	 *
	 * @since 1.1.0
	 *
	 * @uses VGSR_Entity::get_entities()
	 * @uses vgsr_entity_settings_fields_by_type()
	 *
	 * @param array $columns Columns
	 * @return array Columns
	 */
	public function table_columns( $columns ) {

		// Define local variables
		$entities  = vgsr_entity()->get_entities();
		$post_type = get_current_screen()->post_type;

		// Define new columns
		$new_columns = array();

		// Walk columns. Insert BP columns right after 'title'
		foreach ( $columns as $k => $label ) {
			$new_columns[ $k ] = $label;

			// This is the Title column
			if ( 'title' === $k ) {

				// Get registered settings fields
				$fields = vgsr_entity_settings_fields_by_type( $post_type );

				// Walk BP fields
				foreach ( $fields['buddypress'] as $field => $args ) {

					// Skip fields without values
					if ( ! get_option( "_{$post_type}-{$field}", false ) )
						continue;

					// Add column
					$new_columns[ $field ] = $args['column_title'];
				}
			}
		}

		return $new_columns;
	}

	/**
	 * Output the list table column content
	 *
	 * @since 1.1.0
	 *
	 * @uses VGSR_Entity_BuddyPress:get_post_users()
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function column_content( $column, $post_id ) {

		// Check column name
		switch ( $column ) {
			case 'bp-members-field' :
			case 'bp-olim-members-field' :

				// Get column user count
				$post_type = get_post_type( $post_id );
				$field_id  = get_option( "_{$post_type}-{$column}", false );

				// Display user count
				if ( $users = $this->get_post_users( $field_id, $post_id ) ) {
					echo count( $users );
				}

				break;
		}
	}

	/**
	 * Output scripts on entity admin pages
	 *
	 * @since 1.1.0
	 *
	 * @uses vgsr_entity_settings_fields()
	 * @uses VGSR_Entity::get_entities()
	 * @uses wp_add_inline_script()
	 */
	public function admin_enqueue_scripts() {

		// Get settings fields
		$fields = vgsr_entity_settings_fields();

		// Walk entities
		foreach ( vgsr_entity()->get_entities() as $post_type ) {

			// Skip when this page does not apply
			if ( "edit-{$post_type}" !== get_current_screen()->id )
				continue;

			// Define additional column styles
			$css = '';
			foreach ( $fields['buddypress'] as $key => $args ) {
				$width = isset( $args['column-width'] ) ? $args['column-width'] : '10%';
				$css .= ".fixed .column-{$key} { width: {$width} }\n";
			}

			// Append additional styles
			wp_add_inline_style( 'vgsr-entity-admin', $css );
		}
	}

	/** Post ******************************************************************/

	/**
	 * Return the users that are have the post as a field value
	 *
	 * @since 1.1.0
	 *
	 * @uses BP_User_Query
	 *
	 * @param int|string $field Field ID or name
	 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to current post.
	 * @return array User ids
	 */
	public function get_post_users( $field, $post = 0 ) {

		// Define local variable
		$users = array();

		// Bail when the post is not valid
		if ( ! $post = get_post( $post ) )
			return $users;

		// Query users that have registered to be a member of this entity
		if ( $field && $query = new BP_User_Query( array(
			'type'            => 'alphabetical',
			'xprofile_query'  => array(
				array(
					'field' => $field,
					// Compare against post ID, title or slug
					'value' => array( $post->ID, $post->post_title, $post->post_name ),
				)
			),
			'populate_extras' => false,
			'count_total'     => false
		) ) ) {
			$users = $query->user_ids;
		}

		return $users;
	}

	/**
	 * Modify the entity's display meta
	 *
	 * @since 1.1.0
	 *
	 * @uses vgsr_entity_settings_fields_by_type()
	 * @uses VGSR_Entity_BuddyPress::get_post_users()
	 *
	 * @param array $meta Display meta
	 * @param WP_Post $post Post object
	 * @return array Display meta
	 */
	public function display_meta( $meta, $post ) {

		// Get registered settings fields
		$fields = vgsr_entity_settings_fields_by_type( $post->post_type );
		$fields = wp_list_filter( $fields['buddypress'], array( 'display' => true ) );

		// Walk BP fields
		foreach ( $fields as $field => $args ) {
			$value = $this->get_post_users( get_option( "_{$post->post_type}-{$field}", false ), $post );

			// Add field with value to meta collection
			if ( $value ) {
				$meta[ $field ] = array( 'value' => count( $value ), 'label' => $args['label'] );
			}
		}

		return $meta;
	}
}

/**
 * Setup the VGSR Entity BuddyPress class
 *
 * @since 1.1.0
 *
 * @uses VGSR_Entity_BuddyPress
 */
function vgsr_entity_buddypress() {
	vgsr_entity()->extend->bp = new VGSR_Entity_BuddyPress;
}

endif; // class_exists