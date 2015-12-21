<?php

/**
 * VGSR Entity BuddyPress Functions
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

		// Require the VGSR plugin, since we'll be working with member
		// queries here.
		if ( ! function_exists( 'vgsr' ) )
			return;

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
		add_action( 'admin_init', array( $this, 'add_list_table_columns' ) );

		// Post
		add_filter( 'vgsr_entity_display_meta', array( $this, 'display_meta'   ), 10, 2 );
		add_action( 'vgsr_entity_init',         array( $this, 'entity_details' )        );
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
				'args'              => array(
					'setting'     => 'bp-members-field',
					'description' => esc_html__( 'Select the field that holds the %s members.', 'vgsr-entity' ),
				),

				// Field display
				'is_entry_meta'     => true,
				'meta_label'        => esc_html__( '%d Members', 'vgsr-entity' ),
				'detail_callback'   => array( $this, 'entity_members_detail' ),
			);

			// Entity olim members
			$bp_fields['bp-olim-members-field'] = array(
				'title'             => esc_html__( 'Former Members Field', 'vgsr-entity' ),
				'callback'          => array( $this, 'xprofile_field_setting' ),
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Former Members', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-olim-members-field',
					'description' => esc_html__( 'Select the field that holds the %s former members.', 'vgsr-entity' ),
				),

				// Field display
				'detail_callback'   => array( $this, 'entity_olim_members_detail' ),
			);
		}

		// Add fields to the BuddyPress section
		$fields['buddypress'] = $bp_fields;

		return $fields;
	}

	/**
	 * Display a XProfile field selector settings field
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
				$dd .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $field->id ), selected( $args['selected'], $field->id, false ), esc_html( $field->name ) );
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
	 * @uses vgsr_entity()
	 * @uses VGSR_Entity::get_entities()
	 * @uses vgsr_entity_settings_fields_by_type()
	 * @uses VGSR_Entity_Base::get_setting()
	 *
	 * @param array $columns Columns
	 * @return array Columns
	 */
	public function table_columns( $columns ) {

		// Define local variables
		$entity    = vgsr_entity();
		$entities  = $entity->get_entities();
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
					if ( ! $entity->{$post_type}->get_setting( $field ) )
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
	 * @uses VGSR_Entity_Base::get_setting()
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
				$field_id  = vgsr_entity()->{$post_type}->get_setting( $column );

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
	 * @param array $query_args Additional query arguments for BP_User_Query
	 * @return array User ids
	 */
	public function get_post_users( $field, $post = 0, $query_args = array() ) {

		// Define local variable
		$users = array();

		// Bail when the post is not valid
		if ( ! $post = get_post( $post ) )
			return $users;

		// Define query args
		$query_args = wp_parse_args( $query_args, array(
			'type'            => 'alphabetical',
			'populate_extras' => false,
			'count_total'     => false
		) );

		// Define XProfile query args
		$xprofile_query   = isset( $query_args['xprofile_query'] ) ? $query_args['xprofile_query'] : array();
		$xprofile_query[] = array(
			'field' => $field,
			// Compare against post ID, title or slug
			'value' => array( $post->ID, $post->post_title, $post->post_name ),
		);
		$query_args['xprofile_query'] = $xprofile_query;

		// Query users that are connected to this entity
		if ( $field && $query = new BP_User_Query( $query_args ) ) {
			$users = $query->results;
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
		$fields = wp_list_filter( $fields['buddypress'], array( 'is_entry_meta' => true ) );

		// Walk BP fields
		foreach ( $fields as $field => $args ) {
			$value = $this->get_post_users( vgsr_entity()->{$post->post_type}->get_setting( $field ), $post );

			// Add field with value to meta collection
			if ( $value ) {
				$meta[ $field ] = array( 'value' => count( $value ), 'label' => $args['meta_label'] );
			}
		}

		return $meta;
	}

	/**
	 * Setup hooks for the BP fields entity details
	 *
	 * @since 1.1.0
	 *
	 * @uses vgsr_entity_settings_fields()
	 * @uses VGSR_Entity::get_entities()
	 * @uses add_action()
	 */
	public function entity_details() {

		// Get registered settings fields
		$fields = vgsr_entity_settings_fields();

		// Walk BP fields
		foreach ( $fields['buddypress'] as $field ) {

			// Bail when without valid detail callback
			if ( ! isset( $field['detail_callback'] ) || ! is_callable( $field['detail_callback'] ) )
				continue;

			// Default to all entities
			if ( ! isset( $field['entity'] ) ) {
				$field['entity'] = vgsr_entity()->get_entities();
			}

			// Hook detail callback
			foreach ( (array) $field['entity'] as $post_type ) {
				add_action( "vgsr_{$post_type}_details", $field['detail_callback'] );
			}
		}
	}

	/** Details ***************************************************************/

	/**
	 * Output the members entity detail
	 *
	 * @since 1.1.0
	 *
	 * @uses VGSR_Entity_BuddyPress::get_post_users()
	 * @uses bp_core_get_userlink()
	 * @param WP_Post $post Post object
	 */
	public function entity_members_detail( $post ) {

		// Bail when no users were found
		if ( ! $users = $this->get_post_users( vgsr_entity()->{$post->post_type}->get_setting( 'bp-members-field' ), $post->ID ) )
			return;

		?>

		<div class="entity-members">
			<h4><?php _e( 'Members', 'vgsr-entity' ); ?></h4>

			<ul class="users">
				<?php foreach ( $users as $user ) : ?>
				<li class="user"><?php echo bp_core_get_userlink( $user->ID ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<?php
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
