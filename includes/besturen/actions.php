<?php

/**
 * VGSR Entity Bestuur Actions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Template ******************************************************************/

add_action( 'vgsr_entity_bestuur_details', 'vgsr_entity_bestuur_positions_detail' );

/** Theme *********************************************************************/

add_filter( 'document_title_parts',                'vgsr_entity_bestuur_document_title_parts' );
add_filter( 'vgsr_bestuur_menu_widget_query_args', 'vgsr_entity_bestuur_widget_menu_order'    );

/** Menus *********************************************************************/

add_filter( 'customize_nav_menu_available_items', 'vgsr_entity_bestuur_customize_nav_menu_available_items', 10, 4 );
add_filter( 'customize_nav_menu_searched_items',  'vgsr_entity_bestuur_customize_nav_menu_searched_items',  10, 2 );
add_filter( 'wp_setup_nav_menu_item',             'vgsr_entity_bestuur_setup_nav_menu_item'                       );
