<?php

/**
 * VGSR Entity Functions
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the entity post meta
 *
 * @since 1.1.0
 *
 * @uses is_entity()
 * @uses VGSR_Entity_Base::get_meta()
 * @uses apply_filters() Calls 'vgsr_entity_get_meta'
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return array Array with entity meta. Empty array when post is not an entity.
 */
function vgsr_entity_get_meta( $post = 0 ) {

	// Get the post
	$post = get_post( $post );

	// Bail when this is not an entity
	if ( is_entity( $post ) )
		return array();

	$type = get_post_type( $post );
	$meta = vgsr_entity()->{$type}->get_meta();

	return apply_filters( 'vgsr_entity_get_meta', $meta, $post );
}
