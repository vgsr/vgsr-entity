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
 * Return whether the post is a VGSR Entity
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
 * Return whether the post is a VGSR Bestuur
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is an entity
 */
function is_bestuur( $post = 0 ) {
	return isset( vgsr_entity()->bestuur ) && ( get_post_type( $post ) === vgsr_entity()->bestuur->type );
}
endif;

if ( ! function_exists( 'is_dispuut' ) ) :
/**
 * Return whether the post is a VGSR Dispuut
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is an entity
 */
function is_dispuut( $post = 0 ) {
	return isset( vgsr_entity()->dispuut ) && ( get_post_type( $post ) === vgsr_entity()->dispuut->type );
}
endif;

if ( ! function_exists( 'is_kast' ) ) :
/**
 * Return whether the post is a VGSR Kast
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is an entity
 */
function is_kast( $post = 0 ) {
	return isset( vgsr_entity()->kast ) && ( get_post_type( $post ) === vgsr_entity()->kast->type );
}
endif;
