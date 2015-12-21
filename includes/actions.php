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
add_action( 'init',             'vgsr_entity_init'          );
add_action( 'vgsr_entity_init', 'vgsr_entity_vgsr_fallback' );

// Extend
add_action( 'bp_loaded', 'vgsr_entity_buddypress' );

/** Sub-Actions ********************************************************/

/**
 * Setup our own hook on 'init'
 *
 * @since 1.0.0
 * @since 1.1.0 Made the logic procedural.
 *
 * @uses do_action() Calls 'vgsr_entity_init'
 */
function vgsr_entity_init() {
	do_action( 'vgsr_entity_init' );
}
