<?php

/**
 * VGSR Entity BuddyPress Kast Functions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *****************************************************************/

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
function vgsr_entity_bp_kast_posts_search( $search, $posts_query ) {
	global $wpdb;

	// Get residents field
	$field_id = vgsr_entity_get_type_object( 'kast' )->get_setting( 'bp-residents-field' );

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

		// d( $users_query->results );

		// Extend post meta search for found user ids
		if ( $users_query->results ) {
			$user_ids = implode( ',', $users_query->results );

			// Get BuddyPress
			$bp = buddypress();

			// Replace the search WHERE clause
			$search = sprintf( " AND (%s OR %s)",
				// Use existing definition, but strip leading ' AND '
				substr( $search, 5 ),
				// Search for Kasten that have a matched user as resident, either by post id or post title (lowercase match)
				$wpdb->prepare( "({$wpdb->posts}.post_type = %s AND ({$wpdb->posts}.ID IN ( SELECT value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN ($user_ids) ) OR LOWER({$wpdb->posts}.post_title) IN ( SELECT LOWER(value) as value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN ($user_ids) ) ))",
					vgsr_entity_get_kast_post_type(),
					$field_id,
					$field_id
				)
			);
		}
	}

	return $search;
}

/** Template **************************************************************/

/**
 * Return the member's registered Kast
 *
 * @since 2.0.0
 *
 * @param int $user_id Optional. User ID. Defaults to displayed user ID.
 * @return WP_Post|bool Post object, False when not found.
 */
function vgsr_entity_bp_get_member_kast( $user_id = 0 ) {

	// Get Kast setting
	$field_id = vgsr_entity_get_type_object( 'kast' )->get_setting( 'bp-residents-field' );

	// Bail when the Kast field is not found
	if ( ! $field_id || ! xprofile_get_field( $field_id ) )
		return false;

	// Default to the displayed user
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Get the member's kast post ID
	$post_id = xprofile_get_field_data( $field_id, $user_id );
	$post    = $post_id ? get_post( $post_id ) : false;

	return $post;
}

/** Address ***************************************************************/

/**
 * Return the address meta and their profile field ids
 *
 * @since 2.0.0
 *
 * @return array Profile field ids
 */
function vgsr_entity_bp_kast_get_address_field_ids() {

	// Define local variables
	$type   = vgsr_entity_get_type_object( 'kast' );
	$fields = array();

	foreach ( $type->address_meta() as $meta ) {
		$field_id = $type->get_setting( "bp-address-map-{$meta['name']}" );

		// Skip when field is not found
		if ( ! $field_id || ! xprofile_get_field( $field_id ) )
			continue;

		$fields[ $meta['name'] ] = $field_id;
	}

	return $fields;
}

/**
 * Return a member's Kast address field data
 *
 * @since 2.0.0
 *
 * @param integer $user_id Optional. User ID. Defaults to displayed user.
 * @return array Field ids and their data
 */
function vgsr_entity_bp_get_kast_address_field_data( $user_id = 0 ) {

	// Default to displayed user
	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	// Define local variable
	$data = array();
	$type = vgsr_entity_get_type_object( 'kast' );

	// When member has a registered Kast
	if ( $post = vgsr_entity_bp_get_member_kast( $user_id ) ) {

		// Get profile fields to replace and replacement values
		$fields = vgsr_entity_bp_kast_get_address_field_ids();
		$values = $type->address_meta( $post );

		// Map meta values to field ids
		foreach ( $fields as $k => $field_id ) {
			foreach ( $values as $meta ) {
				if ( $meta['name'] === $k ) {
					$data[ $field_id ] = $meta['value'];
				}
			}
		}
	}

	return $data;
}

/**
 * Filter profile groups to add missing address fields
 *
 * @since 2.0.0
 *
 * @see BP_XProfile_Field::get_fields_for_member_type()
 *
 * @param array $groups Profile groups
 * @param array $args
 * @return array Profile groups
 */
function vgsr_entity_bp_filter_kast_address_profile_groups_fields( $groups, $args ) {

	// Default query args
	$r = wp_parse_args( $args, array(
		'profile_group_id'  => false,
		'user_id'           => bp_displayed_user_id(),
		'member_type'       => false,
		'hide_empty_groups' => false,
		'hide_empty_fields' => false,
		'fetch_fields'      => false,
		'fetch_field_data'  => false,
		'exclude_groups'    => false,
		'exclude_fields'    => false,
	) );

	// Bail when no fields or field data are fetched
	if ( ! $r['fetch_fields'] || ! $r['fetch_field_data'] )
		return $groups;

	// Member has replacement data
	if ( $data = vgsr_entity_bp_get_kast_address_field_data( $r['user_id'] ) ) {

		// Empty fields are removed
		if ( $args['hide_empty_fields'] ) {

			// Define local variables
			$member_type_fields = BP_XProfile_Field::get_fields_for_member_type( $r['member_type'] );
			$groups_added = false;

			foreach ( array_keys( $data ) as $field_id ) {

				// Skip when without field data
				if ( empty( $data[ $field_id ] ) )
					continue;

				// Skip excluded field
				if ( $r['exclude_fields'] && in_array( $field_id, (array) $r['exclude_fields'] ) )
					continue;

				// Skip restricted field
				if ( ! in_array( $field_id, array_keys( $member_type_fields ) ) )
					continue;

				// Setup field
				$field = xprofile_get_field( $field_id );

				// Skip specific other field group
				if ( $r['profile_group_id'] && $field->group_id != $r['profile_group_id'] )
					continue;

				// Skip excluded field group
				if ( $r['exclude_groups'] && in_array( $field->group_id, (array) $r['exclude_groups'] ) )
					continue;

				// Add group when missing
				if ( ! in_array( $field->group_id, wp_list_pluck( $groups, 'id' ) ) ) {
					$groups[] = xprofile_get_field_group( $field->group_id );
					$groups_added = true;
				}

				// Add field to group when missing
				foreach ( $groups as $key => $group ) {
					if ( $group->id != $field->group_id )
						continue;

					if ( ! in_array( $field->id, wp_list_pluck( $group->fields, 'id' ) ) ) {
						$groups[ $key ]->fields[] = $field;

						// Reset field order
						usort( $groups[ $key ]->fields, function( $a, $b ) {
							$x = (int) $a->field_order;
							$y = (int) $b->field_order;

							if ( $x === $y ) {
								return 0;
							} else {
								return ( $x > $y ) ? 1 : -1;
							}
						});

						break;
					}
				}
			}

			// Reset group order
			if ( $groups_added ) {
				usort( $groups, function( $a, $b ) {
					$x = (int) $a->group_order;
					$y = (int) $b->group_order;

					if ( $x === $y ) {
						return 0;
					} else {
						return ( $x > $y ) ? 1 : -1;
					}
				});
			}
		}

		// Apply all new values to their respective fields
		foreach ( $data as $field_id => $value ) {
			foreach ( $groups as $gk => $group ) {
				if ( ! isset( $group->fields ) )
					continue;

				foreach ( $group->fields as $fk => $field ) {
					if ( $field->id == $field_id ) {
						if ( ! $field->data ) {
							$field_data        = new stdClass;
							$field_data->id    = null;
							$field_data->value = 'null';
						} else {
							$field_data = $field->data;
						}

						// Set extra replacement value
						$field_data->_value = $value;

						// Overwrite data object
						$groups[ $gk ]->fields[ $fk ]->data = $field_data;

						break 2;
					}
				}
			}
		}
	}

	return $groups;
}

/**
 * Replace a member's address details when they're a Kast resident
 *
 * Filters {@see bp_get_the_profile_field_value()}
 *
 * @since 2.0.0
 *
 * @global BP_XProfile_Field $field
 *
 * @param mixed $value Field value
 * @param string $field_type Field type
 * @param string $field_id Field ID
 * @return mixed Field value
 */
function vgsr_entity_bp_kast_address_profile_field_value( $value, $field_type, $field_id ) {
	global $field;

	/**
	 * Replace field data value with the dummy value that was set
	 * in {@see VGSR_Entity_BuddyPress::address_profile_groups_fields()}.
	 */
	if ( isset( $field->data->_value ) ) {
		$value = $field->data->_value;
	}

	return $value;
}

/**
 * Replace a member's address details when they're a Kast resident
 *
 * Filters {@see bp_get_member_profile_data()} and {@see bp_get_profile_field_data()}.
 *
 * @since 2.0.0
 *
 * @param mixed $data Field data
 * @param array $args Query args. Since BP 2.6+
 * @return mixed Field data
 */
function vgsr_entity_bp_kast_address_profile_field_data( $data, $args = array() ) {
	global $members_template;

	// Field is queried by name. It is not available in this filter (!)
	$r = wp_parse_args( $args, array(
		'field'   => false,
		'user_id' => isset( $members_template ) ? $members_template->member->id : bp_displayed_user_id(),
	) );

	// Get the field ID
	if ( $r['field'] ) {
		$field_id = is_numeric( $r['field'] ) ? (int) $r['field'] : xprofile_get_field_id_from_name( $r['field'] );
	} else {
		$field_id = false;
	}

	/**
	 * Dummy values assigned in {@see xprofile_get_groups()} are lost when used
	 * in {@see BP_XProfile_ProfileData::get_all_for_user()}, so here we
	 * re-fetch and overwrite the data from the address data collection.
	 */
	if ( $field_id && $address = vgsr_entity_bp_get_kast_address_field_data( $r['user_id'] ) ) {
		if ( isset( $address[ $field_id ] ) ) {
			$data = $address[ $field_id ];
		}
	}

	return $data;
}
