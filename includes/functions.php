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
 * Return whether the type is a valid entity type
 *
 * @since 2.0.0
 *
 * @param  string $type Entity type name
 * @return bool Is this a valid entity type?
 */
function vgsr_entity_exists( $type ) {
	return in_array( $type, vgsr_entity()->get_entities(), true );
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

	// Get post type from post
	if ( ! post_type_exists( $post ) ) {
		$post = get_post( $post );
		$post_type = $post ? $post->post_type : false;

	// Post type
	} else {
		$post_type = $post;
	}

	$post_type_object = get_post_type_object( $post_type );

	// Get entity from post type object
	if ( $post_type_object ) {
		$post_type_object = (array) $post_type_object;

		if ( isset( $post_type_object['vgsr-entity'] ) ) {
			$type = $post_type_object['vgsr-entity'];
		}
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
 * @return array|mixed Array with entity meta or mixed
 */
function vgsr_entity_get_meta( $post = 0, $field = '' ) {

	// Get the post
	$post = get_post( $post );

	// Bail when this is not an entity
	if ( ! is_entity( $post ) ) {
		return empty( $field ) ? array() : null;
	}

	// Get post display meta fields
	$meta = vgsr_entity()->get_meta( $post );

	// Get specified field
	if ( ! empty( $field ) ) {
		$meta = isset( $meta[ $field ] ) ? $meta[ $field ] : null;
	}

	return apply_filters( 'vgsr_entity_get_meta', $meta, $post, $field );
}

/** Settings ***********************************************************/

/**
 * Return entity admin settings sections
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_settings_sections'
 * @return array Settings sections
 */
function vgsr_entity_settings_sections() {
	return (array) apply_filters( 'vgsr_entity_settings_sections', array(

		// Main Settings
		'main' => array(
			'title'    => esc_html__( 'Main Settings', 'vgsr-entity' ),
			'callback' => '',
			'page'     => '',
		),
	) );
}

/**
 * Return entity admin settings fields
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_settings_fields'
 * @return array Settings fields
 */
function vgsr_entity_settings_fields() {
	return (array) apply_filters( 'vgsr_entity_settings_fields', array(

		// Main Settings
		'main' => array(

			// Parent Page
			'parent-page' => array(
				'title'             => esc_html__( 'Parent Page', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_settings_display_entity_parent_field',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),
		)
	) );
}

/**
 * Return the settings fields that apply for a given entity type
 *
 * @since 2.0.0
 *
 * @param string $type Entity type name
 * @return array Settings fields
 */
function vgsr_entity_settings_fields_by_type( $type = '' ) {

	// Get entity type
	$type = vgsr_entity_get_type( $type );

	// Bail when this is not an entity
	if ( ! $type ) {
		return array();
	}

	// Get settings fields
	$fields = vgsr_entity_settings_fields();

	// Walk all section's fields
	foreach ( array_keys( $fields ) as $section ) {
		foreach ( $fields[ $section ] as $field => $args ) {

			// Remove fields from the set when they do not apply
			if ( isset( $args['entity'] ) && ! in_array( $type, (array) $args['entity'], true ) ) {
				unset( $fields[ $section ][ $field ] );
			}
		}
	}

	return $fields;
}

/**
 * Output entity parent page settings field
 *
 * @since 1.0.0
 */
function vgsr_entity_settings_display_entity_parent_field() {

	// Get VGSR Entity
	$post_type = get_current_screen()->post_type;
	$type      = vgsr_entity_get_type( $post_type, true );

	// Bail when this is not an entity
	if ( ! $type )
		return;

	// Get the entity parent page ID
	$parent = $type->get_entity_parent();

	// Display select box
	wp_dropdown_pages( array(
		'name'             => "_{$type->type}-parent-page",
		'selected'         => $parent,
		'show_option_none' => esc_html__( '&mdash; No Parent &mdash;', 'vgsr-entity' ),
		'echo'             => true,
	) );

	// Display link to view the page
	if ( $parent ) : ?>

	<a class="button button-secondary" href="<?php echo esc_url( get_permalink( $parent ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'vgsr-entity' ); ?></a>
	<?php endif; ?>

	<p class="description"><?php printf( __( 'Select the page that should act as the %s parent page.', 'vgsr-entity' ), get_post_type_object( $post_type )->labels->name ); ?></p>

	<?php
}

/** Post ***************************************************************/

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
 * Modify the nav item classes
 *
 * @since 2.0.0
 *
 * @param array $classes
 * @param object $item
 * @param array $args
 * @param int $depth
 * @return array Nav item classes
 */
function vgsr_entity_nav_menu_css_class( $classes, $item, $args, $depth ) {

	// Current page is entity with parent
	if ( 'post_type' == $item->type &&
		! in_array( 'current-menu-ancestor', $classes ) &&
		is_entity() && ( $post_type = get_post_type() ) &&
		( $parent = vgsr_entity()->{$post_type}->parent ) &&
		( $parent = get_post( $parent ) )
	) {

		// Nav item is parent
		if ( $item->object_id == $parent->ID ) {
			$classes[] = 'current-menu-parent';
			$classes[] = 'current-menu-ancestor';

		// Nav item is ancestor
		} elseif ( in_array( $item->object_id, $parent->ancestors ) ) {
			$classes[] = 'current-menu-ancestor';
		}
	}

	return array_unique( $classes );
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
function entity_has_more_tag( $post = 0 ) {

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
	wp_enqueue_script( 'vgsr-entity-media-editor', vgsr_entity()->includes_url . 'assets/js/media-editor.js', array( 'vgsr-entity-admin', 'media-editor' ), '2.0.0', true );
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
	if ( is_a( $post, 'WP_Post' ) && is_entity( $post ) ) {
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
