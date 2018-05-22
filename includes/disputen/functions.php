<?php

/**
 * VGSR Entity Dispuut Functions
 *
 * @package VGSR Entity
 * @subpackage Dispuut
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post Type ******************************************************/

/**
 * Return the Dispuut post type name
 *
 * @since 2.0.0
 *
 * @return string Post type name
 */
function vgsr_entity_get_dispuut_post_type() {
	return vgsr_entity_get_type( 'dispuut', true )->post_type;
}

/**
 * Return the Dispuut post type labels
 *
 * @since 2.0.0
 *
 * @return array Post type labels
 */
function vgsr_entity_get_dispuut_post_type_labels() {
	return (array) apply_filters( 'vgsr_entity_get_dispuut_post_type_labels', array(
		'name'                  => esc_html__( 'Disputen',                   'vgsr-entity' ),
		'menu_name'             => esc_html__( 'Disputen',                   'vgsr-entity' ),
		'singular_name'         => esc_html__( 'Dispuut',                    'vgsr-entity' ),
		'all_items'             => esc_html__( 'All Disputen',               'vgsr-entity' ),
		'add_new'               => esc_html__( 'New Dispuut',                'vgsr-entity' ),
		'add_new_item'          => esc_html__( 'Add new Dispuut',            'vgsr-entity' ),
		'edit'                  => esc_html__( 'Edit',                       'vgsr-entity' ),
		'edit_item'             => esc_html__( 'Edit Dispuut',               'vgsr-entity' ),
		'new_item'              => esc_html__( 'New Dispuut',                'vgsr-entity' ),
		'view'                  => esc_html__( 'View Dispuut',               'vgsr-entity' ),
		'view_item'             => esc_html__( 'View Dispuut',               'vgsr-entity' ),
		'view_items'            => esc_html__( 'View Disputen',              'vgsr-entity' ), // Since WP 4.7
		'search_items'          => esc_html__( 'Search Disputen',            'vgsr-entity' ),
		'not_found'             => esc_html__( 'No Disputen found',          'vgsr-entity' ),
		'not_found_in_trash'    => esc_html__( 'No Disputen found in trash', 'vgsr-entity' ),
		'insert_into_item'      => esc_html__( 'Insert into dispuut',        'vgsr-entity' ),
		'uploaded_to_this_item' => esc_html__( 'Uploaded to this dispuut',   'vgsr-entity' ),
		'filter_items_list'     => esc_html__( 'Filter disputen list',       'vgsr-entity' ),
		'items_list_navigation' => esc_html__( 'Disputen list navigation',   'vgsr-entity' ),
		'items_list'            => esc_html__( 'Disputen list',              'vgsr-entity' ),

		// Custom
		'settings_title'        => esc_html__( 'Disputen Settings',          'vgsr-entity' ),
	) );
}

/** Nav Menus **********************************************************/

/**
 * Return the available custom Dispuut nav menu items
 *
 * @since 2.0.0
 *
 * @return array Custom nav menu items
 */
function vgsr_entity_dispuut_nav_menu_items( $items ) {

	// Get type object
	$type   = vgsr_entity_get_type( 'dispuut', true );
	$_items = array();

	// Entity parent
	if ( $parent = vgsr_entity_get_entity_parent( $type->type ) ) {
		$_items['dispuut-parent'] = array(
			'title'      => get_the_title( $parent ),
			'type_label' => esc_html__( 'Dispuut Parent', 'vgsr-entity' ),
			'url'        => get_permalink( $parent ),
			'is_current' => vgsr_is_entity_parent() === $type->type,
			'is_parent'  => vgsr_is_dispuut(),
		);
	}

	// Setup nav menu items
	$_items = (array) apply_filters( 'vgsr_entity_dispuut_nav_menu_get_items', $_items );

	// Set default arguments
	foreach ( $_items as $item_id => &$item ) {
		$item = wp_parse_args( $item, array(
			'id'           => $item_id,
			'title'        => '',
			'type'         => vgsr_entity_get_dispuut_post_type(),
			'type_label'   => esc_html_x( 'Dispuut Page', 'Customizer menu type label', 'vgsr-entity' ),
			'url'          => '',
			'is_current'   => false,
			'is_parent'    => false,
			'is_ancestor'  => false,
			'search_terms' => isset( $item['title'] ) ? strtolower( $item['title'] ) : 'dispuut'
		) );
	}

	return array_merge( $items, $_items );
}
