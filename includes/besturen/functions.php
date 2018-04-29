<?php

/**
 * VGSR Entity Bestuur Functions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post Type ******************************************************/

/**
 * Return the Bestuur post type name
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_bestuur_get_post_type'
 * @return string Post type name
 */
function vgsr_entity_bestuur_get_post_type() {
	return apply_filters( 'vgsr_entity_bestuur_get_post_type', 'bestuur' );
}

/** Positions ******************************************************/

/**
 * Return the available Bestuur positions or a single bestuur's filled positions
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_bestuur_get_positions'
 *
 * @param WP_Post|int|null $post Optional. Post ID or object. Defaults to `null`.
 * @return array Available positions or a single bestuur's filled positions
 */
function vgsr_entity_bestuur_get_positions( $post = null ) {
	$positions = (array) get_option( '_bestuur-positions', array() );

	// Get filled positions for a single bestuur
	if ( null !== $post && $post = get_post( $post ) ) {

		// Walk positions
		foreach ( $positions as $position => $args ) {

			// Position slot is filled
			if ( $user = get_post_meta( $post->ID, "position_{$args['slug']}", true ) ) {
				$positions[ $position ]['user'] = $user ? $user : false;

			// Unfilled position
			} else {
				unset( $positions[ $position ] );
			}
		}
	}

	return (array) apply_filters( 'vgsr_entity_bestuur_get_positions', $positions, $post );
}

/**
 * Return the bestuur position for a given user
 *
 * @since 2.0.0
 *
 * @uses $wpdb WPDB
 * @uses apply_filters() Calls 'vgsr_entity_bestuur_user_position'
 *
 * @param int $user_id Optional. User ID. Defaults to the current user.
 * @return array Position details or empty array when nothing found.
 */
function vgsr_entity_bestuur_get_user_position( $user_id = 0 ) {
	global $wpdb;

	// Default to the current user
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	// Define return variable
	$retval = array();

	// Get registered positions
	if ( $positions = vgsr_entity_bestuur_get_positions() ) {
		$position_map = implode( ', ', array_map( function( $value ) {
			return "'position_{$value['slug']}'";
		}, $positions ) );

		// Define query for the user's position(s)
		$sql = $wpdb->prepare( "SELECT p.ID, pm.meta_key FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE 1=1 AND post_type = %s AND pm.meta_key IN ($position_map) AND pm.meta_value = %d", 'bestuur', $user_id );

		// Run query
		if ( $query = $wpdb->get_results( $sql ) ) {
			$retval['position'] = str_replace( 'position_', '', $query[0]->meta_key );
			$retval['bestuur']  = (int) $query[0]->ID;
		}
	}

	/**
	 * Filters the user's bestuur position(s)
	 *
	 * @since 2.0.0
	 *
	 * @param array $value   User bestuur position details
	 * @param int   $user_id User ID
	 * @param array $query   Query results
	 */
	return (array) apply_filters( 'vgsr_entity_bestuur_user_position', $retval, $user_id, $query );
}
