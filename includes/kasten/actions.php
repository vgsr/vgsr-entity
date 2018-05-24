<?php

/**
 * VGSR Entity Kast Actions
 *
 * @package VGSR Entity
 * @subpackage Kast
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post **********************************************************************/

add_filter( 'post_updated_messages', 'vgsr_entity_kast_post_updated_messages'    );
add_action( 'updated_post_meta',     'vgsr_entity_kast_updated_post_meta', 10, 4 );

/** Menus *********************************************************************/

add_filter( 'vgsr_entity_get_nav_menu_items', 'vgsr_entity_kast_nav_menu_items' );
