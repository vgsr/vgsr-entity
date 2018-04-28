<?php

/**
 * VGSR Entity Bestuur Template Functions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the post ID for the current bestuur
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_current_bestuur'
 * @return int Post ID
 */
function vgsr_entity_get_current_bestuur() {
	return (int) apply_filters( 'vgsr_entity_get_current_bestuur', get_option( '_bestuur-latest-bestuur' ) );
}

/**
 * Return whether the post is the current bestuur
 *
 * @since 2.0.0
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return bool Is this the current bestuur?
 */
function vgsr_entity_is_current_bestuur( $post = 0 ) {
	$post   = get_post( $post );
	$retval = false;

	if ( $post && vgsr_entity_get_current_bestuur() === $post->ID ) {
		$retval = true;
	}

	return $retval;
}
