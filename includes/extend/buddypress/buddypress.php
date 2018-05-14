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

		// Core
		require( $this->includes_dir . 'actions.php'   );
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'kasten.php'    );
		require( $this->includes_dir . 'members.php'   );

		// Admin
		if ( is_admin() ) {
			require( $this->includes_dir . 'settings.php' );
		}
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
				'detail_callback'   => 'vgsr_entity_bp_list_post_members',
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
				'detail_callback'   => 'vgsr_entity_bp_list_post_residents',
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
				'detail_callback'   => 'vgsr_entity_bp_list_post_olim_residents',
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
						'description' => sprintf( esc_html__( "Select the profile field that holds this address detail: %s.", 'vgsr-entity' ), $meta['column_title'] ),
					),
				);
			}
		}

		// Add fields to the BuddyPress section
		$fields['buddypress'] = $bp_fields;

		return $fields;
	}

	/** Query *****************************************************************/

	/**
	 * Setup global query vars for querying post members
	 *
	 * @since 2.0.0
	 *
	 * @param int $field_id Profile field ID
	 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
	 * @param bool $multiple Optional. Whether the profile field allows for multiple values.
	 */
	public function set_post_query_vars( $args = array() ) {

		// Parse args
		$args = wp_parse_args( $args, array(
			'field_id' => 0,
			'post'     => 0,
			'multiple' => false
		) );

		// Get the post
		$post = get_post( $args['post'] );

		// Set class globals
		$this->query_field_id = (int) $args['field_id'];
		$this->query_post_id  = $post ? (int) $post->ID : 0;
		$this->query_multiple = (bool) $args['multiple'];
	}

	/**
	 * Return the global query vars for querying post members
	 *
	 * @since 2.0.0
	 *
	 * @return object Post query vars
	 */
	public function get_post_query_vars() {
		return (object) array(
			'field_id' => $this->query_field_id,
			'post_id'  => $this->query_post_id,
			'multiple' => $this->query_multiple,
		);
	}

	/**
	 * Reset global query vars for querying post members
	 *
	 * @since 2.0.0
	 */
	public function reset_post_query_vars() {
		$this->query_field_id = 0;
		$this->query_post_id  = 0;
		$this->query_multiple = false;
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
