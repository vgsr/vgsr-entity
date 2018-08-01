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

add_action( 'init',              'vgsr_entity_init'              );
add_action( 'admin_init',        'vgsr_entity_admin_init'        );
add_action( 'after_setup_theme', 'vgsr_entity_after_setup_theme' );

/** Utility *******************************************************************/

add_action( 'vgsr_entity_activation',   'vgsr_entity_delete_rewrite_rules' );
add_action( 'vgsr_entity_deactivation', 'vgsr_entity_delete_rewrite_rules' );

/** Main **********************************************************************/

add_action( 'wp', 'vgsr_entity_set_globals' );

/** Post **********************************************************************/

add_filter( 'post_class',  'vgsr_entity_filter_post_class',   10, 3 );
add_filter( 'the_content', 'vgsr_entity_filter_content',      10    );

/** Nav menus *****************************************************************/

add_filter( 'customize_nav_menu_available_items', 'vgsr_entity_customize_nav_menu_available_items', 10, 4 );
add_filter( 'customize_nav_menu_searched_items',  'vgsr_entity_customize_nav_menu_searched_items',  10, 2 );
add_filter( 'wp_setup_nav_menu_item',             'vgsr_entity_setup_nav_menu_item'                       );

/** Template ******************************************************************/

add_action( 'vgsr_entity_after_setup_theme', 'vgsr_entity_load_theme_functions'        );
add_filter( 'document_title_parts',          'vgsr_entity_document_title_parts'        ); // Since WP 4.4
add_filter( 'archive_template_hierarchy',    'vgsr_entity_archive_template_hierarchy'  ); // Since WP 4.7
add_action( 'template_include',              'vgsr_entity_template_include'            );
add_filter( 'get_the_archive_title',         'vgsr_entity_get_the_archive_title'       );
add_filter( 'get_the_archive_description',   'vgsr_entity_get_the_archive_description' );

/** Archive *******************************************************************/

add_filter( 'vgsr_entity_get_the_archive_description', 'vgsr_entity_archive_add_shortlist', 10, 2 );

/** AJAX **********************************************************************/

add_action( 'wp_ajax_vgsr_entity_suggest_user', 'vgsr_entity_suggest_user' );

/** Admin *********************************************************************/

if ( is_admin() ) {
	add_action( 'vgsr_entity_init',       'vgsr_entity_admin',          10 );
	add_action( 'vgsr_entity_admin_init', 'vgsr_entity_setup_updater', 999 );
}

/** Extensions ****************************************************************/

add_action( 'bp_loaded',        'vgsr_entity_setup_buddypress', 10 );
add_action( 'vgsr_entity_init', 'vgsr_entity_setup_wpseo',      99 );
