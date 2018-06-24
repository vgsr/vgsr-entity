<?php

/**
 * VGSR Entity Actions
 * 
 * @package VGSR Entity
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Sub-actions ***************************************************************/

add_action( 'init',       'vgsr_entity_init'       );
add_action( 'admin_init', 'vgsr_entity_admin_init' );

/** Main **********************************************************************/

add_action( 'wp', 'vgsr_entity_set_globals' );

/** Post **********************************************************************/

add_action( 'the_content', 'vgsr_entity_the_archive_content', -1 );
add_filter( 'the_content', 'vgsr_entity_filter_content',      10 );

/** Nav menus *****************************************************************/

add_filter( 'customize_nav_menu_available_items', 'vgsr_entity_customize_nav_menu_available_items', 10, 4 );
add_filter( 'customize_nav_menu_searched_items',  'vgsr_entity_customize_nav_menu_searched_items',  10, 2 );
add_filter( 'wp_setup_nav_menu_item',             'vgsr_entity_setup_nav_menu_item'                       );

/** Template ******************************************************************/

add_filter( 'archive_template_hierarchy',  'vgsr_entity_archive_template_hierarchy'  ); // Since WP 4.7
add_action( 'template_include',            'vgsr_entity_template_include'            );
add_filter( 'get_the_archive_title',       'vgsr_entity_get_the_archive_title'       );
add_filter( 'get_the_archive_description', 'vgsr_entity_get_the_archive_description' );

/** AJAX **********************************************************************/

add_action( 'wp_ajax_vgsr_entity_suggest_user', 'vgsr_entity_suggest_user' );

/** Extensions ****************************************************************/

add_action( 'bp_loaded',        'vgsr_entity_setup_buddypress', 10 );
add_action( 'vgsr_entity_init', 'vgsr_entity_setup_wpseo',      99 );
