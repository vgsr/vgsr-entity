<?php

/**
 * VGSR Entity BuddyPress Administration Functions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_BuddyPress_Admin' ) ) :
/**
 * The VGSR Entity BuddyPress Administration class
 *
 * @since 2.0.0
 */
class VGSR_Entity_BuddyPress_Admin {

	/**
	 * Setup this class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 2.0.0
	 */
	private function setup_actions() {
		add_action( 'vgsr_entity_admin_init', array( $this, 'add_list_table_columns' ) );
	}

	/** Public methods ********************************************************/

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
}

/**
 * Setup the extension logic for BuddyPress
 *
 * @since 2.0.0
 *
 * @uses VGSR_Entity_BuddyPress_Admin
 */
function vgsr_entity_buddypress_admin() {
	vgsr_entity()->extend->bp->admin = new VGSR_Entity_BuddyPress_Admin;
}

endif; // class_exists
