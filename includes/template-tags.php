<?php

/**
 * VGSR Entity Template Tags
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'is_entity' ) ) :
/**
 * Return whether the post is an entity
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is an entity
 */
function is_entity( $post = 0 ) {
	return in_array( get_post_type( $post ), vgsr_entity()->get_entities() );
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
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is an entity parent page
 */
function is_entity_parent( $post = 0 ) {
	if ( ! $post = get_post( $post ) )
		return false;
	return in_array( $post->ID, vgsr_entity()->get_entity_parents() );
}
endif;
