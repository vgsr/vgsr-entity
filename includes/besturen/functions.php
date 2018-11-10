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
 * @return string Post type name
 */
function vgsr_entity_get_bestuur_post_type() {
	return vgsr_entity_get_post_type( 'bestuur' );
}

/**
 * Return the Bestuur post type labels
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_bestuur_post_type_labels'
 * @return array Post type labels
 */
function vgsr_entity_get_bestuur_post_type_labels() {
	return (array) apply_filters( 'vgsr_entity_get_bestuur_post_type_labels', array(
		'name'                  => esc_html__( 'Besturen',                   'vgsr-entity' ),
		'menu_name'             => esc_html__( 'Besturen',                   'vgsr-entity' ),
		'singular_name'         => esc_html__( 'Bestuur',                    'vgsr-entity' ),
		'all_items'             => esc_html__( 'All Besturen',               'vgsr-entity' ),
		'add_new'               => esc_html__( 'New Bestuur',                'vgsr-entity' ),
		'add_new_item'          => esc_html__( 'Add new Bestuur',            'vgsr-entity' ),
		'edit'                  => esc_html__( 'Edit',                       'vgsr-entity' ),
		'edit_item'             => esc_html__( 'Edit Bestuur',               'vgsr-entity' ),
		'new_item'              => esc_html__( 'New Bestuur',                'vgsr-entity' ),
		'view'                  => esc_html__( 'View Bestuur',               'vgsr-entity' ),
		'view_item'             => esc_html__( 'View Bestuur',               'vgsr-entity' ),
		'view_items'            => esc_html__( 'View Besturen',              'vgsr-entity' ), // Since WP 4.7
		'search_items'          => esc_html__( 'Search Besturen',            'vgsr-entity' ),
		'not_found'             => esc_html__( 'No Besturen found',          'vgsr-entity' ),
		'not_found_in_trash'    => esc_html__( 'No Besturen found in trash', 'vgsr-entity' ),
		'insert_into_item'      => esc_html__( 'Insert into bestuur',        'vgsr-entity' ),
		'uploaded_to_this_item' => esc_html__( 'Uploaded to this bestuur',   'vgsr-entity' ),
		'filter_items_list'     => esc_html__( 'Filter besturen list',       'vgsr-entity' ),
		'items_list_navigation' => esc_html__( 'Besturen list navigation',   'vgsr-entity' ),
		'items_list'            => esc_html__( 'Besturen list',              'vgsr-entity' ),

		// Custom
		'settings_title'        => esc_html__( 'Besturen Settings',          'vgsr-entity' ),
	) );
}

/**
 * Add post-type specific messages for post updates
 *
 * @since 2.0.0
 *
 * @param array $messages Messages
 * @return array Messages
 */
function vgsr_entity_bestuur_post_updated_messages( $messages ) {

	// Define post view link
	$view_post_link = sprintf( ' <a href="%s">%s</a>',
		esc_url( get_permalink() ),
		esc_html__( 'View Bestuur', 'vgsr-entity' )
	);

	// Add post type messages
	$messages[ vgsr_entity_get_bestuur_post_type() ] = array(
		 1 => __( 'Bestuur updated.',   'vgsr-entity' ) . $view_post_link,
		 4 => __( 'Bestuur updated.',   'vgsr-entity' ),
		 6 => __( 'Bestuur created.',   'vgsr-entity' ) . $view_post_link,
		 7 => __( 'Bestuur saved.',     'vgsr-entity' ),
		 8 => __( 'Bestuur submitted.', 'vgsr-entity' ) . $view_post_link,
	);

	return $messages;
}

/** Current Bestuur ************************************************/

/**
 * Update which post is the current bestuur
 *
 * @since 2.0.0
 *
 * @param int $post_id Optional. Post ID of new current bestuur. Defaults to the newest bestuur.
 * @return bool Update success
 */
function vgsr_entity_update_current_bestuur( $post_id = 0 ) {

	// Query newest bestuur
	if ( empty( $post_id ) ) {
		if ( $query = new WP_Query( array(
			'posts_per_page' => 1,
			'post_type'      => vgsr_entity_get_post_type( 'bestuur' ),
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
		) ) ) {
			if ( $query->posts ) {
				$post_id = $query->posts[0]->ID;
			}
		}

	// Validate post ID
	} elseif ( $post = get_post( $post_id ) ) {
		$post_id = $post->ID;

	// Default to none
	} else {
		$post_id = 0;
	}

	return update_option( '_bestuur-latest-bestuur', (int) $post_id );
}

/** Positions ******************************************************/

/**
 * Return the available Bestuur positions or a single bestuur's signed positions
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_bestuur_get_positions'
 *
 * @param WP_Post|int|null $post Optional. Post ID or object. Defaults to `null`.
 * @return array Available positions or a single bestuur's signed positions
 */
function vgsr_entity_bestuur_get_positions( $post = null ) {
	$positions = (array) get_option( '_bestuur-positions', array() );

	// Get signed positions for a single bestuur
	if ( null !== $post ) {

		// Walk positions for this post
		if ( $post = get_post( $post ) ) {
			foreach ( $positions as $position => $args ) {

				// Position slot is signed
				if ( $user = get_post_meta( $post->ID, "position_{$args['slug']}", true ) ) {
					$positions[ $position ]['user'] = $user ? $user : false;

				// Unsigned position
				} else {
					unset( $positions[ $position ] );
				}
			}
		} else {
			$positions = array();
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
 * @uses apply_filters() Calls 'vgsr_entity_bestuur_get_user_position'
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
		$position_map = implode( ',', array_map( function( $value ) {
			return "'position_{$value['slug']}'"; // Return quoted, to use for IN statement
		}, $positions ) );

		// Define query for the user's position(s)
		$sql = $wpdb->prepare( "SELECT p.ID, pm.meta_key FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE 1=1 AND post_type = %s AND pm.meta_key IN ($position_map) AND pm.meta_value = %d",
			vgsr_entity_get_bestuur_post_type(),
			$user_id
		);

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
	return (array) apply_filters( 'vgsr_entity_bestuur_get_user_position', $retval, $user_id, $query );
}

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
function vgsr_entity_bestuur_posts_search( $search, $posts_query ) {
	global $wpdb;

	// When searching posts and positions are defined
	if ( $posts_query->is_search() && $positions = vgsr_entity_bestuur_get_positions() ) {

		// Get searched term(s)
		$term = $posts_query->get( 's' );
		$n_p  = $posts_query->get( 'exact' ) ? '' : '%';
		$n_u  = $posts_query->get( 'exact' ) ? '' : '*';

		// Setup post meta search for name match
		$search_meta = $wpdb->prepare( "meta_value LIKE %s", $n_p . $wpdb->esc_like( $term ) . $n_p );

		// Query users by searched name
		$users_query = new WP_User_Query( array(
			'fields'         => 'ID',
			'search'         => $n_u . $term . $n_u,
			'search_columns' => array( 'user_login', 'display_name' )
		) );

		// Extend post meta search for found user ids
		if ( $users_query->results ) {
			$user_ids     = implode( ',', $users_query->results );
			$search_meta .= " OR meta_value IN ($user_ids)";
		}

		// Collect positions
		$position_map = implode( ',', array_map( function( $value ) {
			return "'position_{$value['slug']}'"; // Return quoted, to use for IN statement
		}, $positions ) );

		// Replace the search WHERE clause
		$search = sprintf( " AND (%s OR %s)",
			// Use existing definition, but strip leading ' AND '
			substr( $search, 5 ),
			// Search for Besturen that have defined for any position either 1. one of the found user ids or 2. a matched name
			$wpdb->prepare( "({$wpdb->posts}.post_type = %s AND {$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ($position_map) AND ($search_meta) ))",
				vgsr_entity_get_bestuur_post_type()
			)
		);
	}

	return $search;
}

/** Nav Menus **********************************************************/

/**
 * Return the available custom Bestuur nav menu items
 *
 * @since 2.0.0
 *
 * @return array Custom nav menu items
 */
function vgsr_entity_bestuur_nav_menu_items( $items ) {

	// Get type object
	$type   = vgsr_entity_get_type_object( 'bestuur' );
	$_items = array();

	// Entity parent
	if ( $parent = vgsr_entity_get_entity_parent( $type->type ) ) {
		$_items['bestuur-parent'] = array(
			'title'      => get_the_title( $parent ),
			'type_label' => esc_html__( 'Bestuur Parent', 'vgsr-entity' ),
			'url'        => get_permalink( $parent ),
			'is_current' => vgsr_is_entity_parent() === $type->type,
			'is_parent'  => vgsr_is_bestuur(),
		);
	}

	// Current bestuur
	if ( $current = vgsr_entity_get_current_bestuur() ) {
		$_items['bestuur-current'] = array(
			'title'      => esc_html__( 'Current Bestuur', 'vgsr-entity' ),
			'type_label' => esc_html__( 'Current Bestuur', 'vgsr-entity' ),
			'url'        => get_permalink( $current ),
			'is_current' => vgsr_entity_is_current_bestuur(),
			'is_parent'  => false,
		);
	}

	// Setup nav menu items
	$_items = (array) apply_filters( 'vgsr_entity_bestuur_nav_menu_get_items', $_items );

	// Set default arguments
	foreach ( $_items as $item_id => &$item ) {
		$item = wp_parse_args( $item, array(
			'id'           => $item_id,
			'title'        => '',
			'type'         => vgsr_entity_get_bestuur_post_type(),
			'type_label'   => esc_html_x( 'Bestuur Page', 'Customizer menu type label', 'vgsr-entity' ),
			'url'          => '',
			'is_current'   => false,
			'is_parent'    => false,
			'is_ancestor'  => false,
			'search_terms' => isset( $item['title'] ) ? strtolower( $item['title'] ) : 'bestuur'
		) );
	}

	return array_merge( $items, $_items );
}
