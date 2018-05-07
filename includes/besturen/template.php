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
 *
 * @param bool $object Optional. Whether to return as a post object. Defaults to false.
 * @return int|WP_Post|false Post ID or object or False when not found
 */
function vgsr_entity_get_current_bestuur( $object = false ) {

	// Get post setting and validate
	$post  = (int) get_option( '_bestuur-latest-bestuur' );
	$_post = get_post( $post );

	// Get post object
	if ( $_post && $object ) {
		$post = $_post;

	// Default false for invalid post
	} elseif ( ! $_post ) {
		$post = false;
	}

	return apply_filters( 'vgsr_entity_get_current_bestuur', $post, $object );
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
