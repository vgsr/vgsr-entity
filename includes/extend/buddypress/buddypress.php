<?php

/**
 * VGSR Entity Extension for BuddyPress
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
		require( $this->includes_dir . 'besturen.php'  );
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'kasten.php'    );
		require( $this->includes_dir . 'members.php'   );

		// Admin
		if ( is_admin() ) {
			require( $this->includes_dir . 'admin.php'    );
			require( $this->includes_dir . 'settings.php' );
		}
	}

	/**
	 * Setup actions and filters
	 *
	 * @since 2.0.0
	 */
	private function setup_actions() {

		// Bail when plugin is being deactivated
		if ( vgsr_entity_is_deactivation() )
			return;

		// Post
		add_filter( 'vgsr_entity_display_meta', array( $this, 'display_meta'   ), 10, 2 );
		add_action( 'vgsr_entity_init',         array( $this, 'entity_details' )        );
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

	/** Post ******************************************************************/

	/**
	 * Return the BuddyPress settings fields for the entity type
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Optional. Entity type name. Defaults to null, returning all fields.
	 * @return array Settings fields in sections
	 */
	public function get_settings_fields( $type = null ) {

		// Make settings functions available
		require_once( vgsr_entity()->includes_dir . 'settings.php' );
		require_once( $this->includes_dir . 'settings.php' );

		// Get registered BuddyPress settings fields
		$fields = null === $type ? vgsr_entity_settings_fields() : vgsr_entity_settings_fields_by_type( $type );
		$fields = array_intersect_key( $fields, vgsr_entity_bp_settings_sections() );

		return $fields;
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

		// Get registered settings fields for entry meta
		foreach ( $this->get_settings_fields( $post ) as $fields ) {
			foreach ( wp_list_filter( $fields, array( 'is_entry_meta' => true ) ) as $field => $args ) {

				// Add field with value to meta collection
				if ( $value = vgsr_entity_bp_get_field( $field, $post ) ) {

					// Default to an array's count
					if ( is_array( $value ) ) {
						$value = count( $value );
					}

					// Parse nooped plural
					if ( is_array( $args['meta_label'] ) ) {
						$args['meta_label'] = translate_nooped_plural( $args['meta_label'], $value );
						$value = number_format_i18n( $value );
					}

					// Setup field
					$meta[ $field ] = array(
						'value' => $value,
						'label' => $args['meta_label'],
					);
				}
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

		// Get registered settings fields for entry details
		foreach ( $this->get_settings_fields() as $fields ) {
			foreach ( wp_list_filter( $fields, array( 'show_detail' => true ) ) as $field ) {

				// Bail when without valid detail callback
				if ( ! isset( $field['detail_callback'] ) || ! is_callable( $field['detail_callback'] ) )
					continue;

				// Default to all entity types
				if ( ! isset( $field['entity'] ) ) {
					$field['entity'] = vgsr_entity_get_types();
				}

				// Hook detail callback
				foreach ( (array) $field['entity'] as $type ) {
					add_action( "vgsr_entity_{$type}_details", $field['detail_callback'] );
				}
			}
		}

		// For VGSR members
		if ( vgsr_entity_check_access() ) {

			// Bestuur: replace position name
			add_filter( 'vgsr_entity_bestuur_position_name', 'vgsr_entity_bp_bestuur_position_name', 10, 3 );
		}
	}
}

endif; // class_exists
