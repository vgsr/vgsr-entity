<?php

/**
 * VGSR Entity BuddyPress Functions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the value for the given settings field of the post (type)
 *
 * @since 2.0.0
 *
 * @param string $field Settings field
 * @param WP_Post|int|string $post Optional. Post object or ID or post type or entity type. Defaults to the current post.
 * @param string $context Optional. Defaults to 'display'.
 * @return mixed Entity setting value
 */
function vgsr_entity_bp_get_field( $field, $post = 0, $context = 'display' ) {

	// Get the entity type object
	$type = vgsr_entity_get_type( $post, true );
	$post = get_post( $post );

	// Get settings field's value
	$value   = $type->get_setting( $field );
	$display = ( 'display' === $context );

	// When requesting a single post's detail
	if ( $post ) {

		// Consider settings field
		switch ( $field ) {

			// Post members
			case 'bp-members-field' :
				// Only count leden when displaying
				$query_args = $display ? array( 'vgsr' => 'lid' ) : array();
				$value = vgsr_entity_bp_get_post_users( $value, $post, $query_args );
				break;

			// Residents
			case 'bp-residents-field' :
				$value = vgsr_entity_bp_get_post_users( $value, $post );
				break;

			// Olim-residents
			case 'bp-olim-residents-field' :
				$value = vgsr_entity_bp_get_post_users( $value, $post, array(), true );
				break;
		}
	}

	return $value;
}

/**
 * Return the users that have the post as a field value
 *
 * @since 2.0.0
 *
 * @param int|string $field Field ID or name
 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to current post.
 * @param array $query_args Additional query arguments for BP_User_Query
 * @param bool $multiple Optional. Whether the profile field holds multiple values.
 * @return array User ids
 */
function vgsr_entity_bp_get_post_users( $field, $post = 0, $query_args = array(), $multiple = false ) {

	// Define local variable
	$users = array();

	// Bail when the field or post is invalid
	if ( ! $field || ! $post = get_post( $post ) )
		return $users;

	// Parse query args
	$query_args = wp_parse_args( $query_args, array(
		'type'            => '',    // Query $wpdb->users, sort by ID
		'per_page'        => 0,     // No limit
		'populate_extras' => false,
		'count_total'     => false
	) );

	/**
	 * Account for multi-value profile fields which are stored as
	 * serialized arrays.
	 *
	 * @see https://buddypress.trac.wordpress.org/ticket/6789
	 */
	if ( $multiple ) {
		global $wpdb, $bp;

		// Query user ids that compare against post ID, title or slug
		$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->profile->table_name_data} WHERE field_id = %d AND ( value LIKE %s OR value LIKE %s OR value LIKE %s )",
			$field, '%"' . $post->ID . '"%', '%"' . $post->post_title . '"%', '%"' . $post->post_name . '"%'
		) );

		// Limit member query to the found users
		if ( ! empty( $user_ids ) ) {
			$query_args['include'] = $user_ids;

		// Bail when no users were found
		} else {
			return $users;
		}

	// Use BP_XProfile_Query
	} else {

		// Define XProfile query args
		$xprofile_query   = isset( $query_args['xprofile_query'] ) ? $query_args['xprofile_query'] : array();
		$xprofile_query[] = array(
			'field' => $field,
			// Compare against post ID, title or slug
			'value' => array( $post->ID, $post->post_title, $post->post_name ),
		);
		$query_args['xprofile_query'] = $xprofile_query;
	}

	// Query users that are connected to this entity
	if ( $query = new BP_User_Query( $query_args ) ) {
		$users = $query->results;
	}

	return $users;
}
