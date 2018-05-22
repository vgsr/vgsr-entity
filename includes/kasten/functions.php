<?php

/**
 * VGSR Entity Kast Functions
 *
 * @package VGSR Entity
 * @subpackage Kast
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post Type ******************************************************/

/**
 * Return the Kast post type name
 *
 * @since 2.0.0
 *
 * @return string Post type name
 */
function vgsr_entity_get_kast_post_type() {
	return vgsr_entity_get_type( 'kast', true )->post_type;
}

/**
 * Return the Kast post type labels
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_kast_post_type_labels'
 * @return array Post type labels
 */
function vgsr_entity_get_kast_post_type_labels() {
	return (array) apply_filters( 'vgsr_entity_get_kast_post_type_labels', array(
		'name'                  => esc_html__( 'Kasten',                   'vgsr-entity' ),
		'menu_name'             => esc_html__( 'Kasten',                   'vgsr-entity' ),
		'singular_name'         => esc_html__( 'Kast',                     'vgsr-entity' ),
		'all_items'             => esc_html__( 'All Kasten',               'vgsr-entity' ),
		'add_new'               => esc_html__( 'New Kast',                 'vgsr-entity' ),
		'add_new_item'          => esc_html__( 'Add new Kast',             'vgsr-entity' ),
		'edit'                  => esc_html__( 'Edit',                     'vgsr-entity' ),
		'edit_item'             => esc_html__( 'Edit Kast',                'vgsr-entity' ),
		'new_item'              => esc_html__( 'New Kast',                 'vgsr-entity' ),
		'view'                  => esc_html__( 'View Kast',                'vgsr-entity' ),
		'view_item'             => esc_html__( 'View Kast',                'vgsr-entity' ),
		'view_items'            => esc_html__( 'View Kasten',              'vgsr-entity' ), // Since WP 4.7
		'search_items'          => esc_html__( 'Search Kasten',            'vgsr-entity' ),
		'not_found'             => esc_html__( 'No Kasten found',          'vgsr-entity' ),
		'not_found_in_trash'    => esc_html__( 'No Kasten found in trash', 'vgsr-entity' ),
		'insert_into_item'      => esc_html__( 'Insert into kast',         'vgsr-entity' ),
		'uploaded_to_this_item' => esc_html__( 'Uploaded to this kast',    'vgsr-entity' ),
		'filter_items_list'     => esc_html__( 'Filter kasten list',       'vgsr-entity' ),
		'items_list_navigation' => esc_html__( 'Kasten list navigation',   'vgsr-entity' ),
		'items_list'            => esc_html__( 'Kasten list',              'vgsr-entity' ),

		// Custom
		'settings_title'        => esc_html__( 'Kasten Settings',          'vgsr-entity' ),
	) );
}

/** Nav Menus **********************************************************/

/**
 * Return the available custom Kast nav menu items
 *
 * @since 2.0.0
 *
 * @return array Custom nav menu items
 */
function vgsr_entity_kast_nav_menu_items( $items ) {

	// Get type object
	$type   = vgsr_entity_get_type( 'kast', true );
	$_items = array();

	// Entity parent
	if ( $parent = vgsr_entity_get_entity_parent( $type->type ) ) {
		$_items['kast-parent'] = array(
			'title'      => get_the_title( $parent ),
			'type_label' => esc_html__( 'Kast Parent', 'vgsr-entity' ),
			'url'        => get_permalink( $parent ),
			'is_current' => vgsr_is_entity_parent() === $type->type,
			'is_parent'  => vgsr_is_kast(),
		);
	}

	// Setup nav menu items
	$_items = (array) apply_filters( 'vgsr_entity_kast_nav_menu_get_items', $_items );

	// Set default arguments
	foreach ( $_items as $item_id => &$item ) {
		$item = wp_parse_args( $item, array(
			'id'           => $item_id,
			'title'        => '',
			'type'         => vgsr_entity_get_kast_post_type(),
			'type_label'   => esc_html_x( 'Kast Page', 'Customizer menu type label', 'vgsr-entity' ),
			'url'          => '',
			'is_current'   => false,
			'is_parent'    => false,
			'is_ancestor'  => false,
			'search_terms' => isset( $item['title'] ) ? strtolower( $item['title'] ) : 'kast'
		) );
	}

	return array_merge( $items, $_items );
}
