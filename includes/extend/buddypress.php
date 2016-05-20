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
 * @since 2.0.0
 */
class VGSR_Entity_BuddyPress {

	/**
	 * Holds internal reference of the XProfile field ID for which
	 * to query members.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	private $query_field_id = 0;

	/**
	 * Holds internal reference of the post ID for which to query members.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	private $query_post_id = 0;

	/**
	 * Holds internal reference whether to query a multi-value field.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private $query_multiple = false;

	/**
	 * Class constructor
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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

		// Kast: Address
		add_filter( 'bp_xprofile_get_groups',         array( $this, 'address_profile_groups_fields' ),  5, 2 );
		add_filter( 'bp_get_the_profile_field_value', array( $this, 'address_profile_field_value'   ), 10, 3 );
		add_filter( 'bp_get_member_profile_data',     array( $this, 'address_profile_field_data'    ), 10, 2 );
		add_filter( 'bp_get_profile_field_data',      array( $this, 'address_profile_field_data'    ), 10, 2 );
	}

	/** Settings **************************************************************/

	/**
	 * Add to the admin settings sections
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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

			// Dispuut members
			$bp_fields['bp-members-field'] = array(
				'title'             => esc_html__( 'Members Field', 'vgsr-entity' ),
				'callback'          => array( $this, 'xprofile_field_setting' ),
				'sanitize_callback' => 'intval',
				'entity'            => array( 'dispuut' ),
				'column_title'      => esc_html__( 'Members', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-members-field',
					'description' => esc_html__( "Select the field that holds the Dispuut's members.", 'vgsr-entity' ),
				),

				// Field display
				'is_entry_meta'     => true,
				'meta_label'        => esc_html__( '%d Members', 'vgsr-entity' ),
				'detail_callback'   => array( $this, 'entity_members_detail' ),
				'show_detail'       => is_user_vgsr(),
			);

			// Kast habitants
			$bp_fields['bp-habitants-field'] = array(
				'title'             => esc_html__( 'Habitants Field', 'vgsr-entity' ),
				'callback'          => array( $this, 'xprofile_field_setting' ),
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Habitants', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-habitants-field',
					'description' => esc_html__( "Select the field that holds the Kast's habitants.", 'vgsr-entity' ),
				),

				// Field display
				'is_entry_meta'     => true,
				'meta_label'        => esc_html__( '%d Habitants', 'vgsr-entity' ),
				'detail_callback'   => array( $this, 'entity_habitants_detail' ),
				'show_detail'       => is_user_vgsr(),
			);

			// Kast former habitants
			$bp_fields['bp-olim-habitants-field'] = array(
				'title'             => esc_html__( 'Former Habitants Field', 'vgsr-entity' ),
				'callback'          => array( $this, 'xprofile_field_setting' ),
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Former Habitants', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-olim-habitants-field',
					'description' => esc_html__( "Select the field that holds the Kast's former habitants.", 'vgsr-entity' ),
				),

				// Field display
				'detail_callback'   => array( $this, 'entity_olim_habitants_detail' ),
				'show_detail'       => is_user_vgsr(),
			);

			// Kast Address fields
			foreach ( vgsr_entity()->kast->address_meta() as $meta ) {
				$bp_fields["bp-address-map-{$meta['name']}"] = array(
					'title'             => sprintf( esc_html__( 'Address Map: %s', 'vgsr-entity' ), $meta['column_title'] ),
					'callback'          => array( $this, 'xprofile_field_setting' ),
					'sanitize_callback' => 'intval',
					'entity'            => array( 'kast' ),
					'args'              => array(
						'setting'     => "bp-address-map-{$meta['name']}",
						'description' => sprintf( esc_html__( "Select the field that holds the member's %s address detail.", 'vgsr-entity' ), $meta['column_title'] ),
					),
				);
			}
		}

		// Add fields to the BuddyPress section
		$fields['buddypress'] = $bp_fields;

		return $fields;
	}

	/**
	 * Display a XProfile field selector settings field
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_BuddyPress::get()
	 * @uses VGSR_Entity_BuddyPress::xprofile_fields_dropdown()
	 * @uses xprofile_get_field()
	 * @uses network_admin_url() Defaults to `admin_url()` when not in multisite
	 * @uses get_post_type_object()
	 */
	public function xprofile_field_setting( $args = array() ) {

		// Get current post type and the settings field's value
		$post_type = get_current_screen()->post_type;
		$field_id  = $this->get( $args['setting'], $post_type );

		// Fields dropdown
		$this->xprofile_fields_dropdown( array(
			'name'     => "_{$post_type}-{$args['setting']}",
			'selected' => $field_id,
			'echo'     => true,
		) );

		// Display View link
		if ( $field_id && current_user_can( 'bp_moderate' ) ) {
			printf( ' <a class="button button-secondary" href="%s" target="_blank">%s</a>', 
				esc_url( add_query_arg(
					array(
						'page'     => 'bp-profile-setup',
						'group_id' => xprofile_get_field( $field_id )->group_id,
						'field_id' => $field_id,
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
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity::get_entities()
	 * @uses vgsr_entity_settings_fields_by_type()
	 * @uses VGSR_Entity_BuddyPress::get()
	 *
	 * @param array $columns Columns
	 * @return array Columns
	 */
	public function table_columns( $columns ) {

		// Define local variables
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
					if ( ! isset( $args['column_title'] ) || ! $this->get( $field, $post_type ) )
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
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_BuddyPress::get()
	 *
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function column_content( $column, $post_id ) {

		// Check column name
		switch ( $column ) {
			case 'bp-members-field' :
			case 'bp-habitants-field' :
			case 'bp-olim-habitants-field' :

				// Display user count
				if ( $users = $this->get( $column, $post_id ) ) {
					echo count( $users );
				}

				break;
		}
	}

	/**
	 * Output scripts on entity admin pages
	 *
	 * @since 2.0.0
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
	 * Return the value for the given settings field of the post (type)
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_Base::get_setting()
	 * @uses is_user_vgsr()
	 * @uses VGSR_Entity_BuddyPress::get_post_users()
	 *
	 * @param string $field Settings field
	 * @param string|int|WP_Post $post Optional. Post type, post ID or object. Defaults to current post.
	 * @param string $context Optional. Defaults to 'display'.
	 * @return mixed Entity setting value
	 */
	public function get( $field, $post = 0, $context = 'display' ) {

		// When not providing a post type
		if ( ! post_type_exists( $post ) ) {

			// Find a valid post, or bail
			if ( $post = get_post( $post ) ) {
				$post_type = $post->post_type;
			} else {
				return null;
			}
		} else {
			$post_type = $post;
		}

		// Get settings field's value
		$value   = vgsr_entity()->{$post_type}->get_setting( $field );
		$display = ( 'display' === $context );

		// Return early when not going into a post's detail
		if ( ! is_a( $post, 'WP_Post' ) )
			return $value;

		// Consider settings field
		switch ( $field ) {

			// Public members
			case 'bp-members-field' :
			case 'bp-habitants-field' :
				if ( $display ) {
					// For non-VGSR, discount oud-leden
					$query_args = is_user_vgsr() ? array() : array( 'member_type__not_in' => array( 'oud-lid' ) );
					$value = $this->get_post_users( $value, $post, $query_args );
				}
				break;

			// Private members
			case 'bp-olim-habitants-field' :
				$value = $this->get_post_users( $value, $post, array(), true );
				break;
		}

		return $value;
	}

	/**
	 * Return the users that have the post as a field value
	 *
	 * @since 2.0.0
	 *
	 * @uses BP_User_Query
	 *
	 * @param int|string $field Field ID or name
	 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to current post.
	 * @param array $query_args Additional query arguments for BP_User_Query
	 * @param bool $multiple Optional. Whether the profile field holds multiple values.
	 * @return array User ids
	 */
	public function get_post_users( $field, $post = 0, $query_args = array(), $multiple = false ) {

		// Define local variable
		$users = array();

		// Bail when the field or post is invalid
		if ( ! $field || ! $post = get_post( $post ) )
			return $users;

		// Parse query args
		$query_args = wp_parse_args( $query_args, array(
			'type'            => '',    // Query $wpdb->users, sort by ID
			'per_page'        => 0,     // No limit
			'populate_extras' => false,
			'count_total'     => false
		) );

		/**
		 * Account for multi-value profile fields which are stored as
		 * serialized arrays.
		 *
		 * @see https://buddypress.trac.wordpress.org/ticket/6789
		 */
		if ( $multiple ) {
			global $wpdb, $bp;

			// Query user ids that compare against post ID, title or slug
			$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->profile->table_name_data} WHERE field_id = %d AND ( value LIKE %s OR value LIKE %s OR value LIKE %s )",
				$field, '%"' . $post->ID . '"%', '%"' . $post->post_title . '"%', '%"' . $post->post_name . '"%'
			) );

			// Limit member query to the found users
			if ( ! empty( $user_ids ) ) {
				$query_args['include'] = $user_ids;

			// Bail when no users were found
			} else {
				return $users;
			}

		// Use BP_XProfile_Query
		} else {

			// Define XProfile query args
			$xprofile_query   = isset( $query_args['xprofile_query'] ) ? $query_args['xprofile_query'] : array();
			$xprofile_query[] = array(
				'field' => $field,
				// Compare against post ID, title or slug
				'value' => array( $post->ID, $post->post_title, $post->post_name ),
			);
			$query_args['xprofile_query'] = $xprofile_query;
		}

		// Query users that are connected to this entity
		if ( $query = new BP_User_Query( $query_args ) ) {
			$users = $query->results;
		}

		return $users;
	}

	/**
	 * Run a modified version of {@see bp_has_members()} for the given post users
	 *
	 * When the post has users, the `$members_template` global is setup for use.
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_BuddyPress::get()
	 * @uses add_action()
	 * @uses bp_has_members()
	 * @uses remove_action()
	 *
	 * @param string $field Settings field name
	 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post.
	 * @param bool $multiple Optional. Whether the profile field holds multiple values.
	 * @return bool Whether the post has any users
	 */
	public function bp_has_members_for_post( $field, $post = 0, $multiple = false ) {

		// Bail when the post is invalid
		if ( ! $post = get_post( $post ) )
			return false;

		// Bail when the field is invalid
		if ( ! $field_id = $this->get( $field, $post->post_type ) )
			return false;

		// Define global query ids
		$this->query_field_id = (int) $field_id;
		$this->query_post_id  = (int) $post->ID;
		$this->query_multiple = $multiple;

		// Modify query vars
		add_action( 'bp_pre_user_query_construct', array( $this, 'filter_user_query_post_users' ) );

		// Query members and setup members template
		$has_members = bp_has_members( array(
			'type'            => '',    // Query $wpdb->users, order by ID
			'per_page'        => 0,     // No limit
			'populate_extras' => false,
		) );

		// Unhook query modifier
		remove_action( 'bp_pre_user_query_construct', array( $this, 'filter_user_query_post_users' ) );

		// Reset global query ids
		$this->query_field_id = $this->query_post_id = 0;
		$this->query_multiple = false;

		return $has_members;
	}

	/**
	 * Modify the BP_User_Query before query construction
	 *
	 * @since 2.0.0
	 *
	 * @param BP_User_Query $query
	 */
	public function filter_user_query_post_users( $query ) {

		// Bail when the field or post is invalid
		if ( ! $this->query_field_id || ! $post = get_post( $this->query_post_id ) )
			return;

		/**
		 * Account for multi-value profile fields which are stored as
		 * serialized arrays.
		 *
		 * @see https://buddypress.trac.wordpress.org/ticket/6789
		 */
		if ( $this->query_multiple ) {
			global $wpdb, $bp;

			// Query user ids that compare against post ID, title or slug
			$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->profile->table_name_data} WHERE field_id = %d AND ( value LIKE %s OR value LIKE %s OR value LIKE %s )",
				$this->query_field_id, '%"' . $post->ID . '"%', '%"' . $post->post_title . '"%', '%"' . $post->post_name . '"%'
			) );

			// Bail query when nothing found
			if ( empty( $user_ids ) ) {
				$user_ids = array( 0 );
			}

			// Limit member query to the found users
			$query->query_vars['include'] = $user_ids;

		// Use BP_XProfile_Query
		} else {

			// Define XProfile query args
			$xprofile_query   = is_array( $query->query_vars['xprofile_query'] ) ? $query->query_vars['xprofile_query'] : array();
			$xprofile_query[] = array(
				'field' => $this->query_field_id,
				// Compare against post ID, title or slug
				'value' => array( $post->ID, $post->post_title, $post->post_name ),
			);

			$query->query_vars['xprofile_query'] = $xprofile_query;
		}
	}

	/**
	 * Modify the entity's display meta
	 *
	 * @since 2.0.0
	 *
	 * @uses vgsr_entity_settings_fields_by_type()
	 * @uses VGSR_Entity_BuddyPress::get()
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

			// Add field with value to meta collection
			if ( $value = $this->get( $field, $post ) ) {

				// Default to an array's count
				if ( is_array( $value ) ) {
					$value = count( $value );
				}

				$meta[ $field ] = array( 'value' => $value, 'label' => $args['meta_label'] );
			}
		}

		return $meta;
	}

	/**
	 * Setup hooks for the BP fields entity details
	 *
	 * @since 2.0.0
	 *
	 * @uses vgsr_entity_settings_fields()
	 * @uses VGSR_Entity::get_entities()
	 * @uses add_action()
	 */
	public function entity_details() {

		// Get registered settings fields for display
		$fields = vgsr_entity_settings_fields();
		$fields = wp_list_filter( $fields['buddypress'], array( 'show_detail' => true ) );

		// Walk BP fields
		foreach ( $fields as $field ) {

			// Bail when without valid detail callback
			if ( ! isset( $field['detail_callback'] ) || ! is_callable( $field['detail_callback'] ) )
				continue;

			// Default to all entities
			if ( ! isset( $field['entity'] ) ) {
				$field['entity'] = vgsr_entity()->get_entities();
			}

			// Hook detail callback
			foreach ( (array) $field['entity'] as $post_type ) {
				add_action( "vgsr_entity_{$post_type}_details", $field['detail_callback'] );
			}
		}

		// For VGSR members
		if ( function_exists( 'vgsr' ) && is_user_vgsr() ) {

			// Bestuur: Replace Positions detail
			remove_action( 'vgsr_entity_bestuur_details', array( vgsr_entity()->bestuur, 'positions_detail' ) );
			add_action( 'vgsr_entity_bestuur_details', array( $this, 'bestuur_positions_detail' ) );
		}
	}

	/** Details ***************************************************************/

	/**
	 * Output a list of members of the post's field
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_BuddyPress::bp_has_members_for_post()
	 * @uses bp_member_class()
	 * @uses bp_member_permalink()
	 * @uses bp_member_avatar()
	 * @uses bp_member_name()
	 *
	 * @param WP_Post $post Post object
	 * @param array $args List arguments
	 */
	public function display_members_list( $post, $args = array() ) {

		// Parse list args
		$args = wp_parse_args( $args, array(
			'field'    => '',
			'label'    => esc_html__( 'Members', 'vgsr-entity' ),
			'multiple' => false,
		) );

		// Bail when this post has no members
		if ( ! $this->bp_has_members_for_post( $args['field'], $post->ID, $args['multiple'] ) )
			return;

		?>

		<div class="entity-members">
			<h4><?php echo $args['label']; ?></h4>

			<ul class="bp-item-list">
				<?php while ( bp_members() ) : bp_the_member(); ?>
				<li <?php bp_member_class( array( 'member' ) ); ?>>
					<div class="item-avatar">
						<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar(); ?></a>
					</div>

					<div class="item">
						<div class="item-title">
							<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
						</div>
					</div>
				</li>
				<?php endwhile; ?>
			</ul>
		</div>

		<?php
	}

	/**
	 * Display the Members entity detail
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_BuddyPress::display_members_list()
	 * @param WP_Post $post Post object
	 */
	public function entity_members_detail( $post ) {
		$this->display_members_list( $post, array(
			'field' => 'bp-members-field',
			'label' => esc_html__( 'Members', 'vgsr-entity' ),
		) );
	}

	/**
	 * Display the Habitants entity detail
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_BuddyPress::display_members_list()
	 * @param WP_Post $post Post object
	 */
	public function entity_habitants_detail( $post ) {
		$this->display_members_list( $post, array(
			'field' => 'bp-habitants-field',
			'label' => esc_html__( 'Habitants', 'vgsr-entity' ),
		) );
	}

	/**
	 * Display the Former Habitants entity detail
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_BuddyPress::display_members_list()
	 * @param WP_Post $post Post object
	 */
	public function entity_olim_habitants_detail( $post ) {
		$this->display_members_list( $post, array(
			'field'    => 'bp-olim-habitants-field',
			'label'    => esc_html__( 'Former Habitants', 'vgsr-entity' ),
			'multiple' => true,
		) );
	}

	/**
	 * Display the Bestuur Positions entity detail with BP data
	 *
	 * Replaces the Positions detail with member profile links.
	 *
	 * @since 2.0.0
	 *
	 * @see VGSR_Bestuur::positions_detail()
	 *
	 * @uses VGSR_Bestuur::get_positions()
	 * @uses get_user_by()
	 * @param WP_Post $post Post object
	 */
	public function bestuur_positions_detail( $post ) {

		// Bail when no positions are signed for this entity
		if ( ! $positions = vgsr_entity()->bestuur->get_positions( $post ) )
			return;

		?>

		<div class="bestuur-positions">
			<h4><?php _ex( 'Members', 'Bestuur positions', 'vgsr-entity' ) ?></h4>

			<dl>
				<?php foreach ( $positions as $args ) : ?>
				<dt class="position position-<?php echo $args['slug']; ?>"><?php echo $args['label']; ?></dt>
				<dd class="member"><?php

					// Use existing user's display name
					if ( $user = get_user_by( is_numeric( $args['user'] ) ? 'id' : 'slug', $args['user'] ) ) {

						// Collect template global
						global $members_template;
						$_members_template = $members_template;

						/**
						 * Use BP member loop for using template tags
						 *
						 * Setting up the template loop per member is really
						 * not efficient, but for now it does the job.
						 */
						if ( bp_has_members( array( 'type' => '', 'include' => $user->ID ) ) ) :
							while ( bp_members() ) : bp_the_member();

								// Member profile link
								printf( '<a href="%s">%s</a>', bp_get_member_permalink(), bp_get_member_name() );
							endwhile;
						endif;

						// Reset global
						$members_template = $_members_template;

					// Default to the provided 'user' name or content
					} else {
						echo $args['user'];
					}
				?></dd>
				<?php endforeach; ?>
			</dl>
		</div>

		<?php
	}

	/** Kast: Address *********************************************************/

	/**
	 * Return the address meta and their profile field ids
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Kast::address_meta()
	 * @uses VGSR_Entity_Base::get_setting()
	 *
	 * @return array Profile field ids
	 */
	public function address_get_field_ids() {

		// Define local variables
		$vgsr_entity = vgsr_entity();
		$fields      = array();

		foreach ( $vgsr_entity->kast->address_meta() as $meta ) {
			$field_id = $vgsr_entity->kast->get_setting( "bp-address-map-{$meta['name']}" );

			// Skip when field is not found
			if ( ! $field_id || ! xprofile_get_field( $field_id ) )
				continue;

			$fields[ $meta['name'] ] = $field_id;
		}

		return $fields;
	}

	/**
	 * Return a member's Kast address field data
	 *
	 * @since 2.0.0
	 *
	 * @param integer $user_id Optional. User ID. Defaults to displayed user.
	 * @return array Field ids and their data
	 */
	public function address_get_field_data( $user_id = 0 ) {

		// Default to displayed user
		if ( ! $user_id ) {
			$user_id = bp_displayed_user_id();
		}

		// Define local variable
		$data = array();

		// When member has a registered Kast
		if ( $post = $this->address_get_member_kast( $user_id ) ) {

			// Get profile fields to replace and replacement values
			$fields = $this->address_get_field_ids();
			$values = vgsr_entity()->kast->address_meta( $post );

			// Map meta values to field ids
			foreach ( $fields as $k => $field_id ) {
				foreach ( $values as $meta ) {
					if ( $meta['name'] === $k ) {
						$data[ $field_id ] = $meta['value'];
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Return the member's registered Kast
	 *
	 * @since 2.0.0
	 *
	 * @uses VGSR_Entity_Base::get_setting()
	 * @uses xprofile_get_field()
	 * @uses bp_displayed_user_id()
	 * @uses xprofile_get_field_data()
	 *
	 * @param int $user_id Optional. User ID. Defaults to displayed user ID.
	 * @return WP_Post|bool Post object when found, False when not found.
	 */
	public function address_get_member_kast( $user_id = 0 ) {

		// Get Kast setting
		$field_id = vgsr_entity()->kast->get_setting( 'bp-habitants-field' );

		// Bail when the Kast field is not found
		if ( ! $field_id || ! xprofile_get_field( $field_id ) )
			return false;

		// Default to the displayed user
		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		// Get the member's kast post ID
		$post_id = xprofile_get_field_data( $field_id, $user_id );
		$post    = $post_id ? get_post( $post_id ) : false;

		return $post;
	}

	/**
	 * Filter profile groups to add missing address fields
	 *
	 * @since 2.0.0
	 *
	 * @see BP_XProfile_Field::get_fields_for_member_type()
	 *
	 * @uses VGSR_Entity_BuddyPress:address_get_field_data()
	 * @uses xprofile_get_field()
	 * @uses xprofile_get_field_group()
	 *
	 * @param array $groups Profile groups
	 * @param array $args
	 * @return array Profile groups
	 */
	public function address_profile_groups_fields( $groups, $args ) {

		// Default query args
		$r = wp_parse_args( $args, array(
			'profile_group_id'  => false,
			'user_id'           => bp_displayed_user_id(),
			'member_type'       => false,
			'hide_empty_groups' => false,
			'hide_empty_fields' => false,
			'fetch_fields'      => false,
			'fetch_field_data'  => false,
			'exclude_groups'    => false,
			'exclude_fields'    => false,
		) );

		// Bail when no fields or field data are fetched
		if ( ! $r['fetch_fields'] || ! $r['fetch_field_data'] )
			return $groups;

		// Member has replacement data
		if ( $data = $this->address_get_field_data( $r['user_id'] ) ) {

			// Empty fields are removed
			if ( $args['hide_empty_fields'] ) {

				// Define local variables
				$member_type_fields = BP_XProfile_Field::get_fields_for_member_type( $r['member_type'] );
				$groups_added = false;

				foreach ( array_keys( $data ) as $field_id ) {

					// Skip when without field data
					if ( empty( $data[ $field_id ] ) )
						continue;

					// Skip excluded field
					if ( $r['exclude_fields'] && in_array( $field_id, (array) $r['exclude_fields'] ) )
						continue;

					// Skip restricted field
					if ( ! in_array( $field_id, array_keys( $member_type_fields ) ) )
						continue;

					// Setup field
					$field = xprofile_get_field( $field_id );

					// Skip specific other field group
					if ( $r['profile_group_id'] && $field->group_id != $r['profile_group_id'] )
						continue;

					// Skip excluded field group
					if ( $r['exclude_groups'] && in_array( $field->group_id, (array) $r['exclude_groups'] ) )
						continue;

					// Add group when missing
					if ( ! in_array( $field->group_id, wp_list_pluck( $groups, 'id' ) ) ) {
						$groups[] = xprofile_get_field_group( $field->group_id );
						$groups_added = true;
					}

					// Add field to group when missing
					foreach ( $groups as $key => $group ) {
						if ( $group->id != $field->group_id )
							continue;

						if ( ! in_array( $field->id, wp_list_pluck( $group->fields, 'id' ) ) ) {
							$groups[ $key ]->fields[] = $field;

							// Reset field order
							usort( $groups[ $key ]->fields, function( $a, $b ) {
								$x = (int) $a->field_order;
								$y = (int) $b->field_order;

								if ( $x === $y ) {
									return 0;
								} else {
									return ( $x > $y ) ? 1 : -1;
								}
							});

							break;
						}
					}
				}

				// Reset group order
				if ( $groups_added ) {
					usort( $groups, function( $a, $b ) {
						$x = (int) $a->group_order;
						$y = (int) $b->group_order;

						if ( $x === $y ) {
							return 0;
						} else {
							return ( $x > $y ) ? 1 : -1;
						}
					});
				}
			}

			// Apply all new values to their respective fields
			foreach ( $data as $field_id => $value ) {
				foreach ( $groups as $gk => $group ) {
					if ( ! isset( $group->fields ) )
						continue;

					foreach ( $group->fields as $fk => $field ) {
						if ( $field->id == $field_id ) {
							if ( ! $field->data ) {
								$data        = new stdClass;
								$data->id    = null;
								$data->value = 'null';
							} else {
								$data = $field->data;
							}

							// Set extra replacement value
							$data->_value = $value;

							// Overwrite data object
							$groups[ $gk ]->fields[ $fk ]->data = $data;

							break 2;
						}
					}
				}
			}
		}

		return $groups;
	}

	/**
	 * Replace a member's address details when they're a Kast habitant
	 *
	 * Filters {@see bp_get_the_profile_field_value()}
	 *
	 * @since 2.0.0
	 *
	 * @global BP_XProfile_Field $field
	 *
	 * @param mixed $value Field value
	 * @param string $field_type Field type
	 * @param string $field_id Field ID
	 * @return mixed Field value
	 */
	public function address_profile_field_value( $value, $field_type, $field_id ) {
		global $field;

		/**
		 * Replace field data value with the dummy value that was set
		 * in {@see VGSR_Entity_BuddyPress::address_profile_groups_fields()}.
		 */
		if ( isset( $field->data->_value ) ) {
			$value = $field->data->_value;
		}

		return $value;
	}

	/**
	 * Replace a member's address details when they're a Kast habitant
	 *
	 * Filters {@see bp_get_member_profile_data()} and {@see bp_get_profile_field_data()}.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $data Field data
	 * @param array $args Query args. Since BP 2.6+
	 * @return mixed Field data
	 */
	public function address_profile_field_data( $data, $args = array() ) {
		global $members_template;

		// Field is queried by name. It is not available in this filter (!)
		$r = wp_parse_args( $args, array(
			'field'   => false,
			'user_id' => isset( $members_template ) ? $members_template->member->id : bp_displayed_user_id(),
		) );

		// Get the field ID
		if ( $r['field'] ) {
			$field_id = is_numeric( $r['field'] ) ? (int) $r['field'] : xprofile_get_field_id_from_name( $r['field'] );
		} else {
			$field_id = false;
		}

		/**
		 * Dummy values assigned in {@see xprofile_get_groups()} are lost when used
		 * in {@see BP_XProfile_ProfileData::get_all_for_user()}, so here we
		 * re-fetch and overwrite the data from the address data collection.
		 */
		if ( $field_id && $address = $this->address_get_field_data( $r['user_id'] ) ) {
			if ( isset( $address[ $field_id ] ) ) {
				$data = $address[ $field_id ];
			}
		}

		return $data;
	}
}

/**
 * Setup the VGSR Entity BuddyPress class
 *
 * @since 2.0.0
 *
 * @uses VGSR_Entity_BuddyPress
 */
function vgsr_entity_buddypress() {
	vgsr_entity()->extend->bp = new VGSR_Entity_BuddyPress;
}

endif; // class_exists
