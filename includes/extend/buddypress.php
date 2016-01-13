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
	 * Holds internal reference of the XProfile field ID for which
	 * to query members.
	 *
	 * @since 1.1.0
	 * @var int
	 */
	private $query_field_id = 0;

	/**
	 * Holds internal reference of the post ID for which to query members.
	 *
	 * @since 1.1.0
	 * @var int
	 */
	private $query_post_id = 0;

	/**
	 * Holds internal reference whether to query a multi-value field.
	 *
	 * @since 1.1.0
	 * @var bool
	 */
	private $query_multiple = false;

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

			// Dispuut members
			$bp_fields['bp-members-field'] = array(
				'title'             => esc_html__( 'Members Field', 'vgsr-entity' ),
				'callback'          => array( $this, 'xprofile_field_setting' ),
				'sanitize_callback' => 'intval',
				'entity'            => array( 'dispuut' ),
				'column_title'      => esc_html__( 'Members', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-members-field',
					'description' => esc_html__( 'Select the field that holds the Dispuut\'s members.', 'vgsr-entity' ),
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
					'description' => esc_html__( 'Select the field that holds the Kast\'s habitants.', 'vgsr-entity' ),
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
					'description' => esc_html__( 'Select the field that holds the Kast\'s former habitants.', 'vgsr-entity' ),
				),

				// Field display
				'detail_callback'   => array( $this, 'entity_olim_habitants_detail' ),
				'show_detail'       => is_user_vgsr(),
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
					if ( ! $this->get( $field, $post_type ) )
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
	 * Return the value for the given settings field of the post (type)
	 *
	 * @since 1.1.0
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
	 * @since 1.1.0
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
	 * @since 1.1.0
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
	 * @since 1.1.0
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
	 * @since 1.1.0
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
	 * @since 1.1.0
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
				add_action( "vgsr_{$post_type}_details", $field['detail_callback'] );
			}
		}
	}

	/** Details ***************************************************************/

	/**
	 * Output a list of members of the post's field
	 *
	 * @since 1.1.0
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
	 * @since 1.1.0
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
	 * @since 1.1.0
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
	 * @since 1.1.0
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
