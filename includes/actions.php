<?php

/**
 * VGSR Entity Actions
 * 
 * @package VGSR Entity
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Sub-actions ********************************************************/

add_action( 'init',       'vgsr_entity_init'       );
add_action( 'admin_init', 'vgsr_entity_admin_init' );

/** Post ***************************************************************/

add_filter( 'the_content', 'vgsr_entity_list'           );
add_filter( 'the_content', 'vgsr_entity_filter_content' );

/** Nav menus **********************************************************/

add_filter( 'nav_menu_css_class', 'vgsr_entity_nav_menu_css_class', 10, 4 );

/** AJAX ***************************************************************/

add_action( 'wp_ajax_vgsr_entity_suggest_user', 'vgsr_entity_suggest_user' );

/** Extensions *********************************************************/

add_action( 'bp_loaded', 'vgsr_entity_buddypress' );
