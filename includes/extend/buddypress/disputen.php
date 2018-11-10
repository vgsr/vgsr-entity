<?php

/**
 * VGSR Entity BuddyPress Dispuut Functions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *********************************************************************/

/**
 * Modify the search part of the posts query WHERE clause
 *
 * @see WP_Query::parse_search()
 *
 * @since 2.1.0
 *
 * @global WPDB $wpdb
 *
 * @param string $search Search SQL
 * @param WP_Query $posts_query Query object
 * @return Search SQL
 */
function vgsr_entity_bp_dispuut_posts_search( $search, $posts_query ) {
	global $wpdb;

	// Get residents field
	$field_id = vgsr_entity_get_type_object( 'dispuut' )->get_setting( 'bp-members-field' );

	// When searching posts and the residents field exists
	if ( $posts_query->is_search() && bp_is_active( 'xprofile' ) && $field_id && xprofile_get_field( $field_id ) ) {

		// Get searched term(s)
		$term = $posts_query->get( 's' );
		$n    = $posts_query->get( 'exact' ) ? '' : '*';

		// Query users by searched name
		$users_query = new WP_User_Query( array(
			'fields'         => 'ID',
			'search'         => $n . $term . $n,
			'search_columns' => array( 'user_login', 'display_name' )
		) );

		// Extend post meta search for found user ids
		if ( $users_query->results ) {
			$user_ids = implode( ',', $users_query->results );

			// Get BuddyPress
			$bp = buddypress();

			// Replace the search WHERE clause
			$search = sprintf( " AND (%s OR %s)",
				// Use existing definition, but strip leading ' AND '
				substr( $search, 5 ),
				// Search for Disputen that have a matched user as member, either by post id or post title (lowercase match)
				$wpdb->prepare( "({$wpdb->posts}.post_type = %s AND ({$wpdb->posts}.ID IN ( SELECT value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN ($user_ids) ) OR LOWER({$wpdb->posts}.post_title) IN ( SELECT LOWER(value) as value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN ($user_ids) ) ))",
					vgsr_entity_get_dispuut_post_type(),
					$field_id,
					$field_id
				)
			);
		}
	}

	return $search;
}
