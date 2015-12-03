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

add_action( 'plugins_loaded', 'vgsr_entity_loaded' );
add_action( 'init',           'vgsr_entity_init'   );

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

/**
 * Setup our own hook on 'plugins_loaded'
 *
 * @since 1.1.0
 *
 * @uses do_action() Calls 'vgsr_entity_loaded'
 */
function vgsr_entity_loaded() {
	do_action( 'vgsr_entity_loaded' );
}
