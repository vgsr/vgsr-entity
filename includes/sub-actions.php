<?php

/**
 * VGSR Entity Sub Actions
 *
 * @package VGSR Entity
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Run dedicated init hook for this plugin
 *
 * @since 1.0.0
 * @since 2.0.0 Made the logic procedural.
 *
 * @uses do_action() Calls 'vgsr_entity_init'
 */
function vgsr_entity_init() {
	do_action( 'vgsr_entity_init' );
}

/**
 * Run dedicated admin init hook for this plugin
 *
 * @since 2.0.0
 *
 * @uses do_action() Calls 'vgsr_entity_admin_init'
 */
function vgsr_entity_admin_init() {
	do_action( 'vgsr_entity_admin_init' );
}
