<?php

/**
 * VGSR Entity Functions
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return whether the user has access to hidden parts
 *
 * @since 2.0.0
 *
 * @param int $user_id Optional. Defaults to the current user.
 * @return bool Has the user access?
 */
function vgsr_entity_check_access( $user_id = 0 ) {

	// Default to the current user
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	return function_exists( 'vgsr' ) && is_user_vgsr( $user_id );
}

/** Entities ***********************************************************/

/**
 * Return the registered entity types
 *
 * @since 2.0.0
 *
 * @param bool $object Optional. Whether to return type objects. Defaults to false.
 * @return array Entity type names or objects
 */
function vgsr_entity_get_types( $object = false ) {

	// Get entity types
	$types = vgsr_entity()->get_types();

	// Get type objects
	if ( $object ) {
		foreach ( $types as $key => $type ) {
			unset( $types[ $key ] );
			$types[ $type ] = vgsr_entity()->{$type};
		}
	}

	return $types;
}

/**
 * Return whether the type is a valid entity type
 *
 * @since 2.0.0
 *
 * @param  string $type Entity type name
 * @return bool Is this a valid entity type?
 */
function vgsr_entity_exists( $type ) {
	return in_array( $type, vgsr_entity_get_types(), true );
}

/**
 * Return the plugin's post types
 *
 * @since 2.0.0
 *
 * @param string $output Optional. See {@see get_post_types()}. Defaults to 'names'.
 * @return array Plugin post types
 */
function vgsr_entity_get_post_types( $output = 'names' ) {
	return get_post_types( array( 'vgsr-entity' => true ), $output );
}

/**
 * Return the plugin entity type's post types
 *
 * @since 2.0.0
 *
 * @param string $type Entity type name
 * @param bool $object Optional. Whether to return the post type object. Defaults to false.
 * @return string|WP_Post_Type|bool Plugin post type name or object, False when not found.
 */
function vgsr_entity_get_post_type( $type, $object = false ) {

	// Get post type from entity
	$post_type = vgsr_entity_exists( $type ) ? vgsr_entity_get_type( $type, true )->post_type : false;

	// Get post type object
	if ( $post_type && $object ) {
		$post_type = get_post_type_object( $post_type );
	}

	return $post_type;
}

/**
 * Return the entity type or post's entity type
 *
 * @since 2.0.0
 *
 * @param WP_Post|int|string $post Optional. Post object or ID or post type or entity type. Defaults to the current post.
 * @param bool $object Optional. Whether to return the registered entity type object. Defaults to false.
 * @return string|VGSR_Entity_Type|bool Entity type name or object or False when not found.
 */
function vgsr_entity_get_type( $post = 0, $object = false ) {

	// Bail early when a type was already provided
	if ( vgsr_entity_exists( $post ) ) {
		return $object ? vgsr_entity()->{$post} : $post;
	}

	// Setup return variable
	$type = false;

	// Post type provided
	if ( post_type_exists( $post ) ) {
		$post_type = $post;

	// Get post type from post
	} else {
		$post = get_post( $post );
		$post_type = $post ? $post->post_type : false;
	}

	$post_type_object = get_post_type_object( $post_type );

	// Get entity from post type object
	if ( $post_type_object ) {
		$post_type_object = (array) $post_type_object;

		if ( isset( $post_type_object['vgsr-entity'] ) ) {
			$type = $post_type_object['vgsr-entity'];
		}
	}

	// Try to get type by parent post
	if ( ! $type && $post ) {
		$type = vgsr_is_entity_parent( $post );
	}

	// Get the entity type object
	if ( $type && $object ) {
		$type = vgsr_entity()->{$type};
	}

	return $type;
}

/**
 * Return the entity post's display meta
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_meta'
 *
 * @param WP_Post|int $post Optional. Post ID or object. Defaults to current post.
 * @param string $field Optional. Specific meta field to return for the post. Defaults to all fields.
 * @param bool $return_value Optional. When fetching a single field, whether to return just the value. Defaults to true.
 * @return array|mixed Array with entity meta or mixed
 */
function vgsr_entity_get_meta( $post = 0, $field = '', $return_value = true ) {

	// Get the post
	$post = get_post( $post );

	// Bail when this is not an entity
	if ( ! vgsr_is_entity( $post ) ) {
		return empty( $field ) ? array() : null;
	}

	// Get post display meta fields
	$meta = vgsr_entity()->get_meta( $post );

	// Get specified field
	if ( ! empty( $field ) ) {
		$meta = isset( $meta[ $field ] ) ? $meta[ $field ] : null;

		// Return meta value
		if ( $meta && $return_value ) {
			$meta = $meta['value'];
		}
	}

	return apply_filters( 'vgsr_entity_get_meta', $meta, $post, $field, $return_value );
}

/** Post ***************************************************************/

/**
 * Return all entity parent page ids
 *
 * @since 1.0.0
 *
 * @return array Entity parents as entity type name => parent post ID.
 */
function vgsr_entity_get_entity_parents() {

	// Define local variable
	$parents = array();

	// Fetch registered parents
	foreach ( vgsr_entity_get_types( true ) as $name => $type ) {
		$parents[ $name ] = $type->parent;
	}

	return $parents;
}

/**
 * Return the entity type's parent page
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_entity_parent'
 *
 * @param string $type Optional. Entity type name. Defaults to the current entity type.
 * @return int|bool Post ID or False when not found
 */
function vgsr_entity_get_entity_parent( $type = '' ) {
	$type   = vgsr_entity_get_type( $type );
	$parent = false;

	if ( $type ) {
		$parents = vgsr_entity_get_entity_parents();
		$parent  = $parents[ $type ];
	}

	return apply_filters( 'vgsr_entity_get_entity_parent', $parent, $type );
}

/**
 * Return the archive post status id
 *
 * @since 2.0.0
 *
 * @return string Archive status id
 */
function vgsr_entity_get_archive_status_id() {
	return vgsr_entity()->archive_status_id;
}

/** Nav Menus **********************************************************/

/**
 * Return the available custom plugin nav menu items
 *
 * @since 2.0.0
 *
 * @return array Custom nav menu items
 */
function vgsr_entity_get_nav_menu_items() {

	// Setup items in cache
	if ( empty( vgsr_entity()->wp_nav_menu_items ) ) {

		// Setup plugin nav menu items
		$items = (array) apply_filters( 'vgsr_entity_get_nav_menu_items', array() );

		// Set default arguments
		foreach ( $items as $item_id => &$item ) {
			$item = wp_parse_args( $item, array(
				'id'           => $item_id,
				'title'        => '',
				'type'         => 'vgsr-entity',
				'type_label'   => esc_html_x( 'Entity Page', 'Customizer menu type label', 'vgsr-entity' ),
				'url'          => '',
				'is_current'   => false,
				'is_parent'    => false,
				'is_ancestor'  => false,
				'prepend_item' => true,
				'search_terms' => ''
			) );
		}

		// Assign items to global
		vgsr_entity()->wp_nav_menu_items = $items;
	}

	return vgsr_entity()->wp_nav_menu_items;
}

/**
 * Add custom plugin pages to the available nav menu items metabox
 *
 * @since 2.0.0
 *
 * @global int $_wp_nav_menu_placeholder
 *
 * @param array $items The nav menu items for the current post type.
 * @param array $args An array of WP_Query arguments.
 * @param WP_Post_Type $post_type The current post type object for this menu item meta box.
 * @return array $items Nav menu items
 */
function vgsr_entity_nav_menu_items_metabox( $items, $args, $post_type ) {
	global $_wp_nav_menu_placeholder;

	// Walk plugin nav items
	foreach ( vgsr_entity_get_nav_menu_items() as $item_id => $item ) {

		// Skip for unmatched post types
		if ( $item['type'] !== $post_type->name )
			continue;

		$_wp_nav_menu_placeholder = ( 0 > $_wp_nav_menu_placeholder ) ? intval( $_wp_nav_menu_placeholder ) -1 : -1;

		// Prepend item
		$metabox_item = (object) array(
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
		);

		// Add to metabox items
		if ( $item['prepend_item'] ) {
			array_unshift( $items, $metabox_item );
		} else {
			$items[] = $metabox_item;
		}
	}

	return $items;
}

/**
 * Add custom plugin pages to the available menu items in the Customizer
 *
 * @since 2.0.0
 *
 * @param array $items The array of menu items.
 * @param string $type The object type.
 * @param string $object The object name.
 * @param int $page The current page number.
 * @return array Menu items
 */
function vgsr_entity_customize_nav_menu_available_items( $items, $type, $object, $page ) {

	// Walk plugin nav items
	foreach ( vgsr_entity_get_nav_menu_items() as $item_id => $item ) {

		// Skip for unmatched post types and non-first page loads
		if ( $item['type'] !== $object || 0 !== $page )
			continue;

		// Redefine item details
		$item['id']     = $object . '-' . $item_id;
		$item['object'] = $item_id;

		// Prepend item
		if ( $item['prepend_item'] ) {
			array_unshift( $items, $item );
		} else {
			$items[] = $item;
		}
	}

	return $items;
}

/**
 * Add custom plugin pages to the searched menu items in the Customizer
 *
 * @since 2.0.0
 *
 * @param array $items The array of menu items.
 * @param array $args Includes 'pagenum' and 's' (search) arguments.
 * @return array Menu items
 */
function vgsr_entity_customize_nav_menu_searched_items( $items, $args ) {

	// Walk plugin nav items
	foreach ( vgsr_entity_get_nav_menu_items() as $item_id => $item ) {

		// Skip when without search terms or serach did not match
		if ( ! $item['search_terms'] || false === strpos( $item['search_terms'], strtolower( $args['s'] ) ) )
			continue;

		// Redefine item details
		$item['id']     = $item['type'] . '-' . $item_id;
		$item['object'] = $item_id;

		// Append item
		$items[] = $item;
	}

	return $items;
}

/**
 * Setup details of nav menu item for plugin pages
 *
 * @since 2.0.0
 *
 * @param WP_Post $menu_item Nav menu item object
 * @return WP_Post Nav menu item object
 */
function vgsr_entity_setup_nav_menu_item( $menu_item ) {

	// Walk plugin nav items
	foreach ( vgsr_entity_get_nav_menu_items() as $item ) {

		// Skip for unmatched menu item
		if ( $item['id'] !== $menu_item->object )
			continue;

		// Set item details
		$menu_item->type_label = $item['type_label'];
		$menu_item->url        = $item['url'];

		// Set item classes
		if ( ! is_array( $menu_item->classes ) ) {
			$menu_item->classes = array();
		}

		// This is the current page
		if ( $item['is_current'] ) {
			$menu_item->classes[] = 'current_page_item';
			$menu_item->classes[] = 'current-menu-item';

		// This is the parent page
		} elseif ( $item['is_parent'] ) {
			$menu_item->classes[] = 'current_page_parent';
			$menu_item->classes[] = 'current-menu-parent';

		// This is an ancestor page
		} elseif ( $item['is_ancestor'] ) {
			$menu_item->classes[] = 'current_page_ancestor';
			$menu_item->classes[] = 'current-menu-ancestor';
		}

		// Prevent rendering when the link is empty
		if ( empty( $menu_item->url ) ) {
			$menu_item->_invalid = true;
		}
	}

	// Mark entity parent page nav items
	if ( 'post_type' === $menu_item->type
		&& ( $type = vgsr_is_entity_parent( $menu_item->object_id ) )
		&& vgsr_entity_is_post_of_type( $type )
	) {

		// Set item classes
		if ( ! is_array( $menu_item->classes ) ) {
			$menu_item->classes[] = array();
		}

		// This is the parent page
		$menu_item->classes[] = 'current_page_parent';
		$menu_item->classes[] = 'current-menu-parent';
	}

	// Enable plugin filtering
	return apply_filters( 'vgsr_entity_setup_nav_menu_item', $menu_item );
}

/** AJAX ***************************************************************/

/**
 * Output a list of suggested users for a $.suggest AJAX call
 *
 * @since 2.0.0
 */
function vgsr_entity_suggest_user() {
	global $wpdb;

	// Bail early when no request
	if ( empty( $_REQUEST['q'] ) ) {
		wp_die( '0' );
	}

	// NOTE: Suggest.js does not allow for sending the post ID along with
	// the request to check for post type specific cap checking.
	if ( ! current_user_can( 'create_posts' ) ) {
		wp_die( '0' );
	}

	// Check the ajax nonce
	check_ajax_referer( 'vgsr_entity_suggest_user_nonce' );

	// Try to get some users
	$users_query = new WP_User_Query( array(
		'search'         => '*' . $wpdb->esc_like( $_REQUEST['q'] ) . '*',
		'fields'         => array( 'ID' ),
		'search_columns' => array( 'ID', 'user_nicename', 'user_login', 'user_email', 'display_name' ),
		'orderby'        => 'user_login'
	) );

	// If we found some users, loop through and display them
	if ( ! empty( $users_query->results ) ) {
		foreach ( (array) $users_query->results as $user ) {
			$user = new WP_User( $user->ID );

			// Build selectable line for output as 'user login (ID) - display name'
			$line = sprintf( '%s (%s)', $user->user_login, $user->ID );
			if ( $user->user_login !== $user->display_name ) {
				$line .= sprintf( ' - %s', $user->display_name );
			}

			echo $line . "\n";
		}
	}

	die();
}

/** Utilities **********************************************************/

if ( ! function_exists( 'pow2' ) ) :
/**
 * Return a single value by applying the power of 2
 *
 * @since 2.0.0
 *
 * @param array $values Values to convert from
 * @return int Value created out of power of 2
 */
function pow2( $values = array() ) {
	$retval = 0;
	foreach ( (array) $values as $val ) {
		$retval += pow( 2, $val );
	}

	return $retval;
}
endif;

if ( ! function_exists( 'unpow2' ) ) :
/**
 * Return all power of 2 values that are in the value
 *
 * @since 2.0.0
 *
 * @param int $value Value to convert back
 * @return array Values of power of 2 found
 */
function unpow2( $value = 0 ) {
	$retval = array();
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		foreach ( array_reverse( str_split( (string) decbin( (int) $value ) ) ) as $pow => $bi ) {
			if ( $bi ) $retval[] = $pow;
		}
	}

	return $retval;
}
endif;

if ( ! function_exists( 'dutch_net_numbers' ) ) :
/**
 * Return a collection of all Dutch net numbers
 *
 * @since 2.0.0
 *
 * @see nl.wikipedia.org/wiki/Lijst_van_Nederlandse_netnummers
 *
 * @return array Dutch net numbers
 */
function dutch_net_numbers() {
	return array(
		'010', '0111', '0113', '0114', '0115', '0117', '0118', '013', '015', '0161', '0162', '0164',
		'0165', '0166', '0167', '0168', '0172', '0174', '0180', '0181', '0182', '0183', '0184',
		'0186', '0187', '020', '0222', '0223', '0224', '0226', '0227', '0228', '0229', '023', '024',
		'0251', '0252', '0255', '026', '0294', '0297', '0299', '030', '0313', '0314', '0315', '0316',
		'0317', '0318', '0320', '0321', '033', '0341', '0342', '0343', '0344', '0345', '0346', '0347',
		'0348', '035', '036', '038', '040', '0411', '0412', '0413', '0416', '0418', '043', '045',
		'046', '0475', '0478', '0481', '0485', '0486', '0487', '0488', '0492', '0493', '0495', '0497',
		'0499', '050', '0511', '0512', '0513', '0514', '0515', '0516', '0517', '0518', '0519', '0521',
		'0522', '0523', '0524', '0525', '0527', '0528', '0529', '053', '0541', '0543', '0544', '0545',
		'0546', '0547', '0548', '055', '0561', '0562', '0566', '0570', '0571', '0572', '0573', '0575',
		'0577', '0578', '058', '0591', '0592', '0593', '0594', '0595', '0596', '0597', '0598', '0599',
		'070', '071', '072', '073', '074', '075', '076', '077', '078', '079'
	);
}
endif;

/**
 * Return whether the post has a Read More tag
 *
 * @since 2.0.0
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return bool Post has more tag
 */
function vgsr_entity_has_more_tag( $post = 0 ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) )
		return false;

	return (bool) preg_match( '/<!--more(.*?)?-->/', $post->post_content );
}

/** Features ***********************************************************/

/**
 * Return whether the given entity supports the given feature
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_supports'
 *
 * @param string $feature Feature name
 * @param string|WP_Post $type Optional. Entity type name or post object. Defaults to the current post.
 * @return bool Is feature supported for type?
 */
function vgsr_entity_supports( $feature, $type = 0 ) {

	// Get entity type
	$type = vgsr_entity_get_type( $type, true );

	// Is feature supported?
	$supports = $type ? in_array( $feature, $type->features, true ) : false;

	return apply_filters( 'vgsr_entity_supports', $supports, $type );
}

/**
 * Output an entity's logo
 *
 * @since 2.0.0
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 */
function vgsr_entity_the_logo( $post = 0 ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) )
		return;

	// Output entity logo image
	if ( vgsr_entity_supports( 'logo', $post ) ) {
		echo wp_get_attachment_image( vgsr_entity_get_logo( $post->ID ) );
	}
}

/**
 * Return the entity's logo ID
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_logo'
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return int|bool Logo post ID or False when not found
 */
function vgsr_entity_get_logo( $post = 0 ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) ) {
		return false;
	}

	// Bail when not an entity or has no logo
	if ( ! vgsr_entity_supports( 'logo', $post ) ) {
		return false;
	}

	// Get the post's entity type
	$type = vgsr_entity_get_type( $post );

	// Get the logo attachment post ID
	$logo_id = get_post_meta( $post->ID, "_{$type}-logo-id", true );

	// Check if the logo still exists
	if ( ! get_post( $logo_id ) ) {
		$logo_id = false;
	}

	return apply_filters( 'vgsr_entity_get_logo', $logo_id, $post );
}

/**
 * Output the logo feature metabox input field
 *
 * @since 2.0.0
 *
 * @param WP_Post $post
 */
function vgsr_entity_feature_logo_metabox( $post ) {
	$logo_id = vgsr_entity_get_logo( $post ); ?>

	<p>
		<span class="title"><?php esc_html_e( 'Logo', 'vgsr-entity' ); ?></span>
		<span id="entity-logo"><?php echo _vgsr_entity_feature_logo_html( $logo_id, $post->ID ); ?></span>
	</p>

	<?php

	// Enqueue media modal script
	wp_enqueue_script( 'vgsr-entity-media-editor', vgsr_entity()->assets_url . 'js/media-editor.js', array( 'vgsr-entity-admin', 'media-editor' ), '2.0.0', true );
}

	/**
	 * Return the logo feature editor HTML
	 *
	 * @since 2.0.0
	 *
	 * @see _wp_post_thumbnail_html()
	 *
	 * @param int $post_id Post ID
	 * @param int $logo_id Post ID
	 * @return string Editor HTML
	 */
	function _vgsr_entity_feature_logo_html( $logo_id, $post_id ) {

		// Define local variables
		$post             = get_post( $post_id );
		$post_type_object = get_post_type_object( $post->post_type );
		$set_action_text  = sprintf( __( 'Set %s Logo', 'vgsr-entity' ), $post_type_object->labels->singular_name );
		$set_image_link   = '<span class="hide-if-no-js"><a title="%s" href="#" id="set-entity-logo">%s</a></span>';

		$content = sprintf( $set_image_link,
			esc_attr( $set_action_text ),
			esc_html( $set_action_text )
		);

		// This post has a logo
		if ( $logo_id && get_post( $logo_id ) ) {
			$size = has_image_size( 'entity-logo' ) ? 'entity-logo' : array( 250, 250 );
			$image_html = wp_get_attachment_image( $logo_id, $size );

			if ( ! empty( $image_html ) ) {
				$remove_action_text = sprintf( __( 'Remove %s Logo', 'vgsr-entity' ), $post_type_object->labels->singular_name );
				$remove_image_link  = '<span class="hide-if-no-js"><a href="#" id="remove-entity-logo" title="%s"><span class="screen-reader-text">%s</span></a></span>';

				$content = sprintf( $set_image_link,
					esc_attr( $set_action_text ),
					$image_html
				) . sprintf( $remove_image_link,
					esc_attr( $remove_action_text ),
					esc_html( $remove_action_text )
				);
			}
		}

		return $content;
	}

/**
 * Modify the post's media settings for the logo feature
 *
 * @since 2.0.0
 *
 * @param array $settings Media settings
 * @param WP_Post $post Post object
 * @return array Media settings
 */
function vgsr_entity_feature_logo_media_settings( $settings, $post ) {

	// Add logo ID to the post's media settings
	if ( is_a( $post, 'WP_Post' ) && vgsr_is_entity( $post ) ) {
		$logo_id = vgsr_entity_get_logo( $post );
		$settings['post']['entityLogoId'] = $logo_id ? $logo_id : -1;
	}

	return $settings;
}

/**
 * Save an entity's logo feature input
 *
 * @since 2.0.0
 *
 * @see wp_ajax_set_post_thumbnail()
 *
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 */
function vgsr_entity_feature_logo_save() {
	$json = ! empty( $_REQUEST['json'] ); // New-style request

	$post_ID = intval( $_POST['post_id'] );

	if ( ! $post = get_post( $post_ID ) )
		wp_die( -1 );
	if ( ! current_user_can( 'edit_post', $post_ID ) )
		wp_die( -1 );

	$logo_id = intval( $_POST['logo_id'] );

	if ( $json ) {
		check_ajax_referer( "update-post_$post_ID" );
	} else {
		check_ajax_referer( "set_entity_logo-$post_ID" );
	}

	// Delete entity logo
	if ( $logo_id == '-1' ) {
		if ( delete_post_meta( $post_ID, "_{$post->post_type}-logo-id" ) ) {
			$return = _vgsr_entity_feature_logo_html( null, $post_ID );
			$json ? wp_send_json_success( $return ) : wp_die( $return );
		} else {
			wp_die( 0 );
		}
	}

	// Update entity logo
	if ( update_post_meta( $post_ID, "_{$post->post_type}-logo-id", $logo_id ) ) {
		$return = _vgsr_entity_feature_logo_html( $logo_id, $post_ID );
		$json ? wp_send_json_success( $return ) : wp_die( $return );
	}

	wp_die( 0 );
}

/**
 * Modify the current screen's columns for the logo feature
 *
 * @since 2.0.0
 *
 * @param array $columns Columns
 * @return array Columns
 */
function vgsr_entity_feature_logo_list_column( $columns ) {

	// Define new columns
	$new_columns = array();

	// Walk columns. Insert logo column right before 'title'
	foreach ( $columns as $k => $label ) {

		// This is the Title column
		if ( 'title' === $k ) {
			$new_columns['entity-logo'] = esc_html__( 'Logo', 'vgsr-entity' );
		}

		$new_columns[ $k ] = $label;
	}

	return $new_columns;
}

/**
 * Output the list table column content for the logo feature
 *
 * @since 2.0.0
 *
 * @param string $column Column name
 * @param int $post_id Post ID
 */
function vgsr_entity_feature_logo_list_column_content( $column, $post_id ) {

	// When this is our column
	if ( 'entity-logo' === $column ) {
		if ( $logo_id = vgsr_entity_get_logo( $post_id ) ) {
			echo wp_get_attachment_image( $logo_id, array( 38, 38 ) );
		}
	}
}

/**
 * Output markup for the logo feature in the entity details
 *
 * @since 2.0.0
 *
 * @param WP_Post $post Post object
 */
function vgsr_entity_feature_logo_detail( $post ) {

	// Bail when this post has no logo
	if ( ! $logo_id = vgsr_entity_get_logo( $post ) )
		return;

	// Get image size
	$size = has_image_size( 'entity-logo' ) ? 'entity-logo' : array( 250, 250 );

	printf( '<div class="entity-logo">%s</div>', wp_get_attachment_image( $logo_id, $size ) );
}

/** Update *************************************************************/

/**
 * Update routine for version 2.0.0
 *
 * @since 2.0.0
 *
 * @global $wpdb WPDB
 */
function vgsr_entity_update_20000() {
	global $wpdb;

	// Get VGSR Entity
	$entity = vgsr_entity();

	// Bestuur: Update old-style menu-order (+ 1950)
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} p SET p.menu_order = ( p.menu_order + %d ) WHERE p.post_type = %s AND p.menu_order < %d", $entity->base_year, 'bestuur', $entity->base_year ) );

	// Kast: Rename 'since' meta key
	$wpdb->update(
		$wpdb->postmeta,
		array( 'meta_key' => 'since' ),
		array( 'meta_key' => 'vgsr_entity_kast_since' ),
		array( '%s' ),
		array( '%s' )
	);

	// Kast: Change 'since' date format from d/m/Y to Y-m-d
	if ( $query = new WP_Query( array(
		'post_type'      => 'kast',
		'fields'         => 'ids',
		'posts_per_page' => -1,
	) ) ) {
		foreach ( $query->posts as $post_id ) {
			$value = get_post_meta( $post_id, 'since', true );

			if ( $value ) {
				$date  = DateTime::createFromFormat( 'd/m/Y', $value );
				if ( $date ) {
					$value = $date->format( 'Y-m-d' );
					update_post_meta( $post_id, 'since', $value );
				}
			}
		}
	}
}
