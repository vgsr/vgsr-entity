<?php

/**
 * VGSR Entity Bestuur Actions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *********************************************************************/

add_filter( 'posts_search', 'vgsr_entity_bestuur_posts_search', 10, 2 );

/** Post **********************************************************************/

add_filter( 'post_updated_messages', 'vgsr_entity_bestuur_post_updated_messages' );

/** Template ******************************************************************/

add_filter( 'document_title_parts', 'vgsr_entity_bestuur_document_title_parts', 10 );
add_action( 'the_content',          'vgsr_entity_bestuur_positions_detail',      5 );

/** Theme *********************************************************************/

add_filter( 'vgsr_entity_bestuur_menu_widget_query_args', 'vgsr_entity_bestuur_widget_menu_order'    );

/** Menus *********************************************************************/

add_filter( 'vgsr_entity_get_nav_menu_items', 'vgsr_entity_bestuur_nav_menu_items' );
