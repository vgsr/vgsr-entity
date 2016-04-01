<?php

/**
 * VGSR Entity Actions and Filters
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Actions ************************************************************/

// Core
add_action( 'init',        'vgsr_entity_init' );
add_filter( 'the_content', 'vgsr_entity_list' );

// Nav Menu
add_filter( 'nav_menu_css_class', 'vgsr_entity_nav_menu_css_class', 10, 4 );

// AJAX
add_action( 'wp_ajax_vgsr_entity_suggest_user', 'vgsr_entity_suggest_user' );

// Extend
add_action( 'bp_loaded', 'vgsr_entity_buddypress' );

/** Sub-Actions ********************************************************/

/**
 * Setup our own hook on 'init'
 *
 * @since 1.0.0
 * @since 2.0.0 Made the logic procedural.
 *
 * @uses do_action() Calls 'vgsr_entity_init'
 */
function vgsr_entity_init() {
	do_action( 'vgsr_entity_init' );
}
