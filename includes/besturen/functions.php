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
	return vgsr_entity_get_type( 'bestuur', true )->post_type;
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
	if ( null !== $post && $post = get_post( $post ) ) {

		// Walk positions
		foreach ( $positions as $position => $args ) {

			// Position slot is signed
			if ( $user = get_post_meta( $post->ID, "position_{$args['slug']}", true ) ) {
				$positions[ $position ]['user'] = $user ? $user : false;

			// Unsigned position
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
	return (array) apply_filters( 'vgsr_entity_bestuur_get_user_position', $retval, $user_id, $query );
}

/** Nav Menus **********************************************************/

/**
 * Return the available custom Bestuur nav menu items
 *
 * @since 2.0.0
 *
 * @return array Custom nav menu items
 */
function vgsr_entity_bestuur_nav_menu_get_items() {

	// Get type object
	$type = vgsr_entity_get_type( 'bestuur', true );

	// Try to return items from cache
	if ( ! empty( $type->wp_nav_menu_items ) ) {
		return $type->wp_nav_menu_items;
	} else {
		$type->wp_nav_menu_items = new stdClass;
	}

	// Setup nav menu items
	$items = array();

	// Entity parent
	if ( $parent = vgsr_entity_get_entity_parent( $type->type ) ) {
		$items['parent'] = array(
			'title'      => get_the_title( $parent ),
			'type_label' => esc_html__( 'Bestuur Parent', 'vgsr-entity' ),
			'url'        => get_permalink( $parent ),
			'is_current' => vgsr_is_entity_parent() === $type->type,
			'is_parent'  => vgsr_is_bestuur(),
		);
	}

	// Current bestuur
	if ( $current = vgsr_entity_get_current_bestuur() ) {
		$items['current'] = array(
			'title'      => esc_html__( 'Current Bestuur', 'vgsr-entity' ),
			'type_label' => esc_html__( 'Current Bestuur', 'vgsr-entity' ),
			'url'        => get_permalink( $current ),
			'is_current' => vgsr_entity_is_current_bestuur(),
			'is_parent'  => vgsr_is_entity_parent() === $type->type,
		);
	}

	// Setup nav menu items
	$items = (array) apply_filters( 'vgsr_entity_bestuur_nav_menu_get_items', $items );

	// Set default arguments
	foreach ( $items as $item_id => &$item ) {
		$item = wp_parse_args( $item, array(
			'id'          => $item_id,
			'title'       => '',
			'type'        => vgsr_entity_get_bestuur_post_type(),
			'type_label'  => esc_html_x( 'Bestuur Page', 'Customizer menu type label', 'vgsr-entity' ),
			'url'         => '',
			'is_current'  => false,
			'is_parent'   => false,
			'is_ancestor' => false,
		) );
	}

	// Assign items to global
	$type->wp_nav_menu_items = $items;

	return $items;
}

/**
 * Add custom Bestuur pages to the available nav menu items metabox
 *
 * @since 2.0.0
 *
 * @param array $items The nav menu items for the current post type.
 * @param array $args An array of WP_Query arguments.
 * @param WP_Post_Type $post_type The current post type object for this menu item meta box.
 * @return array $items Nav menu items
 */
function vgsr_entity_bestuur_nav_menu_items_metabox( $items, $args, $post_type ) {
	global $_wp_nav_menu_placeholder;

	// Bestuur items
	if ( vgsr_entity_get_bestuur_post_type() === $post_type->name ) {
		$_items = vgsr_entity_bestuur_nav_menu_get_items();

		// Prepend all custom items
		foreach ( array_reverse( $_items ) as $item_id => $item ) {
			$_wp_nav_menu_placeholder = ( 0 > $_wp_nav_menu_placeholder ) ? intval( $_wp_nav_menu_placeholder ) -1 : -1;

			// Prepend item
			array_unshift( $items, (object) array(
				'ID'           => $post_type->name . '-' . $item_id,
				'object_id'    => $_wp_nav_menu_placeholder,
				'object'       => $item_id,
				'post_content' => '',
				'post_excerpt' => '',
				'post_title'   => $item['title'],
				'post_type'    => 'nav_menu_item',
				'type'         => $item['type'],
				'type_label'   => $item['type_label'],
				'url'          => $item['url'],
			) );
		}
	}

	return $items;
}

/**
 * Add custom Bestuur pages to the available menu items in the Customizer
 *
 * @since 2.0.0
 *
 * @param array $items The array of menu items.
 * @param string $type The object type.
 * @param string $object The object name.
 * @param int $page The current page number.
 * @return array Menu items
 */
function vgsr_entity_bestuur_customize_nav_menu_available_items( $items, $type, $object, $page ) {

	// First page of Besturen list
	if ( vgsr_entity_get_bestuur_post_type() === $object && 0 === $page ) {
		$_items = vgsr_entity_bestuur_nav_menu_get_items();

		// Prepend all custom items
		foreach ( array_reverse( $_items ) as $item_id => $item ) {

			// Redefine item details
			$item['id']     = $object . '-' . $item_id;
			$item['object'] = $item_id;

			// Prepend item
			array_unshift( $items, $item );
		}
	}

	return $items;
}

/**
 * Add custom Bestuur pages to the searched menu items in the Customizer
 *
 * @since 2.0.0
 *
 * @param array $items The array of menu items.
 * @param array $args Includes 'pagenum' and 's' (search) arguments.
 * @return array Menu items
 */
function vgsr_entity_bestuur_customize_nav_menu_searched_items( $items, $args ) {

	// Search query matches a part of the term 'bestuur'
	if ( false !== strpos( 'bestuur', strtolower( $args['s'] ) ) ) {
		$post_type = vgsr_entity_get_bestuur_post_type();

		// Append all custom items
		foreach ( vgsr_entity_bestuur_nav_menu_get_items() as $item_id => $item ) {

			// Redefine item details
			$item['id']     = $post_type . '-' . $item_id;
			$item['object'] = $item_id;

			// Append item
			$items[] = $item;
		}
	}

	return $items;
}

/**
 * Setup details of nav menu item for Bestuur pages
 *
 * @since 2.0.0
 *
 * @param WP_Post $menu_item Nav menu item object
 * @return WP_Post Nav menu item object
 */
function vgsr_entity_bestuur_setup_nav_menu_item( $menu_item ) {

	// Bestuur
	if ( vgsr_entity_get_bestuur_post_type() === $menu_item->type ) {

		// This is a registered custom menu item
		if ( $item = wp_list_filter( vgsr_entity_bestuur_nav_menu_get_items(), array( 'id' => $menu_item->object ) ) ) {
			$item = (object) reset( $item );

			// Set item details
			$menu_item->type_label = $item->type_label;
			$menu_item->url        = $item->url;

			// Set item classes
			if ( ! is_array( $menu_item->classes ) ) {
				$menu_item->classes = array();
			}

			// This is the current page
			if ( $item->is_current ) {
				$menu_item->classes[] = 'current_page_item';
				$menu_item->classes[] = 'current-menu-item';

			// This is the parent page
			} elseif ( $item->is_parent ) {
				$menu_item->classes[] = 'current_page_parent';
				$menu_item->classes[] = 'current-menu-parent';

			// This is an ancestor page
			} elseif ( $item->is_ancestor ) {
				$menu_item->classes[] = 'current_page_ancestor';
				$menu_item->classes[] = 'current-menu-ancestor';
			}
		}

		// Prevent rendering when the link is empty
		if ( empty( $menu_item->url ) ) {
			$menu_item->_invalid = true;
		}
	}

	// Enable plugin filtering
	return apply_filters( 'vgsr_entity_bestuur_setup_nav_menu_item', $menu_item );
}
