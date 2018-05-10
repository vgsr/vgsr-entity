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
