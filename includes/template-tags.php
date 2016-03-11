<?php

/**
 * VGSR Entity Template Tags
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the entity post's display meta
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
	if ( ! is_entity( $post ) )
		return array();

	// Get post display meta fields
	$meta = vgsr_entity()->get_meta( $post );

	return apply_filters( 'vgsr_entity_get_meta', $meta, $post );
}

/** Details ************************************************************/

/**
 * Return the markup for a post's entity details
 *
 * @since 1.1.0
 *
 * @uses is_entity()
 * @uses do_action() Calls 'vgsr_entity_{$post_type}_details'
 *
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to current post.
 * @return string Entity details markup
 */
function vgsr_entity_details( $post = 0 ) {

	// Bail when this is not a post
	if ( ! $post = get_post( $post ) )
		return;

	// Bail when this is not an entity
	if ( ! is_entity( $post->post_type ) )
		return;

	// Start output buffer
	ob_start();

	/**
	 * Output entity details of the given post
	 *
	 * The `post_type` variable in the action name points to the post type.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post $post Post object
	 */
	do_action( "vgsr_entity_{$post->post_type}_details", $post );

	// Close output buffer
	$details = ob_get_clean();

	// Wrap details in div
	if ( ! empty( $details ) ) {
		$details = sprintf( '<div class="entity-details">%s</div>', $details );
	}

	return $details;
}

/** Is *****************************************************************/

if ( ! function_exists( 'is_entity' ) ) :
/**
 * Return whether the post('s post) type is an entity
 *
 * @since 1.1.0
 *
 * @param string|int|WP_Post $post_type Optional. Post type, post ID or object. Defaults
 *                                      to current post's post type.
 * @return bool Post (type) is an entity
 */
function is_entity( $post_type = 0 ) {

	// Default to the current post's post type
	if ( ! is_string( $post_type ) || ! post_type_exists( $post_type ) ) {
		$post_type = get_post_type( $post_type );
	}

	return in_array( $post_type, vgsr_entity()->get_entities() );
}
endif;

if ( ! function_exists( 'is_bestuur' ) ) :
/**
 * Return whether the post is a Bestuur
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Bestuur
 */
function is_bestuur( $post = 0 ) {
	return isset( vgsr_entity()->bestuur ) && ( get_post_type( $post ) === vgsr_entity()->bestuur->type );
}
endif;

if ( ! function_exists( 'is_dispuut' ) ) :
/**
 * Return whether the post is a Dispuut
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Dispuut
 */
function is_dispuut( $post = 0 ) {
	return isset( vgsr_entity()->dispuut ) && ( get_post_type( $post ) === vgsr_entity()->dispuut->type );
}
endif;

if ( ! function_exists( 'is_kast' ) ) :
/**
 * Return whether the post is a Kast
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Kast
 */
function is_kast( $post = 0 ) {
	return isset( vgsr_entity()->kast ) && ( get_post_type( $post ) === vgsr_entity()->kast->type );
}
endif;

if ( ! function_exists( 'is_entity_parent' ) ) :
/**
 * Return whether the post is an entity parent page
 *
 * @since 1.1.0
 *
 * @uses VGSR_Entity::get_entity_parents()
 * @uses post_type_exists()
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return string|bool Post type of parent's entity or False if it is not.
 */
function is_entity_parent( $post = 0 ) {
	if ( ! $post = get_post( $post ) )
		return false;

	// Find this post as a parent
	$post_type = array_search( $post->ID, vgsr_entity()->get_entity_parents() );

	return ( post_type_exists( $post_type ) ? $post_type : false );
}
endif;
