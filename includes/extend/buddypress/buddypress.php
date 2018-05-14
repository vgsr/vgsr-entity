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
	 */
	public function __construct() {

		// Require the VGSR plugin, since we'll be working with member
		// queries here.
		if ( ! function_exists( 'vgsr' ) )
			return;

		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Setup actions and filters
	 *
	 * @since 2.0.0
	 */
	private function setup_globals() {

		/** Paths *************************************************************/
		
		$this->includes_dir = trailingslashit( vgsr_entity()->extend_dir . 'buddypress' );
		$this->includes_url = trailingslashit( vgsr_entity()->extend_url . 'buddypress' );
	}

	/**
	 * Setup actions and filters
	 *
	 * @since 2.0.0
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'   );
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'kasten.php'    );
		require( $this->includes_dir . 'settings.php'  );
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
	 * @param array $fields Settings fields
	 * @return array Settings fields
	 */
	public function add_settings_fields( $fields ) {

		// Define local variable
		$bp_fields = array();
		$access    = vgsr_entity_check_access();

		// When using XProfile
		if ( bp_is_active( 'xprofile' ) ) {

			// Dispuut members
			$bp_fields['bp-members-field'] = array(
				'title'             => esc_html__( 'Members Field', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
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
				'show_detail'       => $access,
			);

			// Kast residents
			$bp_fields['bp-residents-field'] = array(
				'title'             => esc_html__( 'Residents Field', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Residents', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-residents-field',
					'description' => esc_html__( "Select the field that holds the Kast's residents.", 'vgsr-entity' ),
				),

				// Field display
				'is_entry_meta'     => true,
				'meta_label'        => esc_html__( '%d Residents', 'vgsr-entity' ),
				'detail_callback'   => array( $this, 'entity_residents_detail' ),
				'show_detail'       => $access,
			);

			// Kast former residents
			$bp_fields['bp-olim-residents-field'] = array(
				'title'             => esc_html__( 'Former Residents Field', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Former Residents', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-olim-residents-field',
					'description' => esc_html__( "Select the field that holds the Kast's former residents.", 'vgsr-entity' ),
				),

				// Field display
				'detail_callback'   => array( $this, 'entity_olim_residents_detail' ),
				'show_detail'       => $access,
			);

			// Kast Address fields
			foreach ( vgsr_entity()->kast->address_meta() as $meta ) {
				$bp_fields["bp-address-map-{$meta['name']}"] = array(
					'title'             => sprintf( esc_html__( 'Address Map: %s', 'vgsr-entity' ), $meta['column_title'] ),
					'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
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

	/** List Table ************************************************************/

	/**
	 * Setup hooks for the entities' list tables
	 *
	 * @since 2.0.0
	 */
	public function add_list_table_columns() {

		// Get registered fields
		$fields = vgsr_entity_settings_fields();

		// When BP settings fields are present
		if ( ! empty( $fields['buddypress'] ) ) {

			// Setup column hooks for all entities
			foreach ( vgsr_entity_get_post_types() as $post_type ) {
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
	 * @param array $columns Columns
	 * @return array Columns
	 */
	public function table_columns( $columns ) {

		// Get admin page's entity type
		$type = vgsr_entity_get_type( get_current_screen()->post_type );

		// Define new columns
		$new_columns = array();

		// Walk columns. Insert BP columns right after 'title'
		foreach ( $columns as $k => $label ) {
			$new_columns[ $k ] = $label;

			// This is the Title column
			if ( 'title' === $k ) {

				// Get registered settings fields
				$fields = vgsr_entity_settings_fields_by_type( $type );

				// Walk BP fields
				foreach ( $fields['buddypress'] as $field => $args ) {

					// Skip fields without values
					if ( ! isset( $args['column_title'] ) || ! vgsr_entity_bp_get_field( $field, $type ) )
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
	 * @param string $column Column name
	 * @param int $post_id Post ID
	 */
	public function column_content( $column, $post_id ) {

		// Check column name
		switch ( $column ) {
			case 'bp-members-field' :
			case 'bp-residents-field' :
			case 'bp-olim-residents-field' :

				// Display user count
				if ( $users = vgsr_entity_bp_get_field( $column, $post_id ) ) {
					echo count( $users );
				}

				break;
		}
	}

	/**
	 * Output scripts on entity admin pages
	 *
	 * @since 2.0.0
	 */
	public function admin_enqueue_scripts() {

		// Get settings fields
		$fields = vgsr_entity_settings_fields();

		// Walk entities
		foreach ( vgsr_entity_get_post_types() as $post_type ) {

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
	 * Run a modified version of {@see bp_has_members()} for the given post users
	 *
	 * When the post has users, the `$members_template` global is setup for use.
	 *
	 * @since 2.0.0
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

		$type = vgsr_entity_get_type( $post, true );

		// Bail when the field is invalid
		if ( ! $type || ! $field_id = $type->get_setting( $field ) )
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
	 * @param array $meta Display meta
	 * @param WP_Post $post Post object
	 * @return array Display meta
	 */
	public function display_meta( $meta, $post ) {

		// Make settings functions available
		require_once( vgsr_entity()->includes_dir . 'settings.php' );

		// Get registered settings fields
		$fields = vgsr_entity_settings_fields_by_type( $post->post_type );
		$fields = wp_list_filter( $fields['buddypress'], array( 'is_entry_meta' => true ) );

		// Walk BP fields
		foreach ( $fields as $field => $args ) {

			// Add field with value to meta collection
			if ( $value = vgsr_entity_bp_get_field( $field, $post ) ) {

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
	 */
	public function entity_details() {

		// Make settings functions available
		require_once( vgsr_entity()->includes_dir . 'settings.php' );

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
			foreach ( (array) $field['entity'] as $type ) {
				add_action( "vgsr_entity_{$type}_details", $field['detail_callback'] );
			}
		}

		// For VGSR members
		if ( vgsr_entity_check_access() ) {

			// Bestuur: Replace Positions detail
			remove_action( 'vgsr_entity_bestuur_details', 'vgsr_entity_bestuur_positions_detail' );
			add_action( 'vgsr_entity_bestuur_details', array( $this, 'bestuur_positions_detail' ) );
		}
	}

	/** Details ***************************************************************/

	/**
	 * Output a list of members of the post's field
	 *
	 * @since 2.0.0
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
	 * @param WP_Post $post Post object
	 */
	public function entity_members_detail( $post ) {
		$this->display_members_list( $post, array(
			'field' => 'bp-members-field',
			'label' => esc_html__( 'Members', 'vgsr-entity' ),
		) );
	}

	/**
	 * Display the Residents entity detail
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	public function entity_residents_detail( $post ) {
		$this->display_members_list( $post, array(
			'field' => 'bp-residents-field',
			'label' => esc_html__( 'Residents', 'vgsr-entity' ),
		) );
	}

	/**
	 * Display the Former Residents entity detail
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	public function entity_olim_residents_detail( $post ) {
		$this->display_members_list( $post, array(
			'field'    => 'bp-olim-residents-field',
			'label'    => esc_html__( 'Former Residents', 'vgsr-entity' ),
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
	 * @param WP_Post $post Post object
	 */
	public function bestuur_positions_detail( $post ) {

		// Bail when no positions are signed for this entity
		if ( ! $positions = vgsr_entity_bestuur_get_positions( $post ) )
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
}

endif; // class_exists
