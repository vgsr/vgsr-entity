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
 * Return the entity post's display meta
 *
 * @since 1.1.0
 *
 * @uses is_entity()
 * @uses VGSR_Entity_Base::get_meta()
 * @uses apply_filters() Calls 'vgsr_entity_get_meta'
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return array Array with entity meta. Empty array when post is not an entity.
 */
function vgsr_entity_get_meta( $post = 0 ) {

	// Get the post
	$post = get_post( $post );

	// Bail when this is not an entity
	if ( ! is_entity( $post ) )
		return array();

	// Get post display meta fields
	$meta = vgsr_entity()->get_meta( $post );

	return apply_filters( 'vgsr_entity_get_meta', $meta, $post );
}

/** Settings ***********************************************************/

/**
 * Return entity admin settings sections
 *
 * @since 1.1.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_settings_sections'
 * @return array Settings sections
 */
function vgsr_entity_settings_sections() {
	return (array) apply_filters( 'vgsr_entity_settings_sections', array(

		// Main Settings
		"main" => array(
			'title'    => esc_html__( 'Main Settings', 'vgsr-entity' ),
			'callback' => '',
			'page'     => '',
		),
	) );
}

/**
 * Return entity admin settings fields
 *
 * @since 1.1.0
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
 * @since 1.1.0
 *
 * @uses vgsr_entity_settings_fields()
 *
 * @param string $entity Post type
 * @return array Settings fields
 */
function vgsr_entity_settings_fields_by_type( $entity = '' ) {

	// Bail when this is not an entity
	if ( ! is_entity( $entity ) )
		return array();

	// Get settings fields
	$fields = vgsr_entity_settings_fields();

	// Walk all section's fields
	foreach ( array_keys( $fields ) as $section ) {
		foreach ( $fields[ $section ] as $field => $args ) {

			// Remove fields from the set when they do not apply
			if ( isset( $args['entity'] ) && ! in_array( $entity, (array) $args['entity'] ) ) {
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
 *
 * @uses VGSR_Entity_Base::get_entity_parent()
 * @uses wp_dropdown_pages()
 * @uses get_post_type_object()
 */
function vgsr_entity_settings_display_entity_parent_field() {

	// Get VGSR Entity
	$post_type = get_current_screen()->post_type;
	$entity    = vgsr_entity()->{$post_type};
	$parent    = $entity->get_entity_parent();

	// Display select box
	wp_dropdown_pages( array(
		'name'             => "_{$post_type}-parent-page",
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

/** Utilities **********************************************************/

if ( ! function_exists( 'pow2' ) ) :
/**
 * Return a single value by applying the power of 2
 *
 * @since 1.1.0
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
 * @since 1.1.0
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
 * @since 1.1.0
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
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post
 * @return bool Post has more tag
 */
function entity_has_more_tag( $post = 0 ) {
	if ( ! $post = get_post( $post ) )
		return false;

	return (bool) preg_match( '/<!--more(.*?)?-->/', $post->post_content );
}

/** Features ***********************************************************/

/**
 * Output an entity's logo
 *
 * @since 2.0.0
 *
 * @uses VGSR_Entity_Base::has_feature()
 * @uses wp_get_attachment_image()
 * @uses get_entity_logo()
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post
 */
function the_entity_logo( $post = 0 ) {
	if ( ! $post = get_post( $post ) )
		return;

	// Output entity logo image
	if ( is_entity( $post ) && vgsr_entity()->{$post->post_type}->has_feature( 'logo' ) ) {
		echo wp_get_attachment_image( get_entity_logo( $post->ID ) );
	}
}

/**
 * Return the entity's logo ID
 *
 * @since 2.0.0
 *
 * @uses is_entity()
 * @uses VGSR_Entity_Base::has_feature()
 * @uses get_post_meta()
 *
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post
 * @return int|bool Logo post ID or False when not found
 */
function get_entity_logo( $post = 0 ) {
	if ( ! $post = get_post( $post ) )
		return false;

	// Bail when not an entity or has no logo
	if ( ! is_entity( $post ) || ! vgsr_entity()->{$post->post_type}->has_feature( 'logo' ) )
		return false;

	$logo_id = get_post_meta( $post->ID, "_{$post->post_type}-logo-id", true );

	if ( get_post( $logo_id ) ) {
		return $logo_id;
	}

	return false;
}

/**
 * Output the logo feature metabox input field
 *
 * @since 2.0.0
 *
 * @uses get_entity_logo()
 * @uses _vgsr_entity_feature_logo_html()
 * @uses wp_enqueue_script()
 * @param WP_Post $post
 */
function vgsr_entity_feature_logo_metabox( $post ) {
	$logo_id = get_entity_logo( $post ); ?>

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
	 * @uses has_image_size()
	 * @uses wp_get_attachment_image()
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
 * @uses is_entity()
 * @uses get_entity_logo()
 *
 * @param array $settings Media settings
 * @param WP_Post $post Post object
 * @return array Media settings
 */
function vgsr_entity_feature_logo_media_settings( $settings, $post ) {

	// Add logo ID to the post's media settings
	if ( is_a( $post, 'WP_Post' ) && is_entity( $post->post_type ) ) {
		$logo_id = get_entity_logo( $post );
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
 * @uses delete_post_meta()
 * @uses _vgsr_entity_feature_logo_html()
 * @uses update_post_meta()
 * @uses wp_send_json_success()
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
			$new_columns['entity-logo'] = __( 'Logo', 'vgsr-entity' );
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
 * @uses get_entity_logo()
 * @uses wp_get_attachment_image()
 *
 * @param string $column Column name
 * @param int $post_id Post ID
 */
function vgsr_entity_feature_logo_list_column_content( $column, $post_id ) {

	// When this is our column
	if ( 'entity-logo' === $column ) {
		if ( $logo_id = get_entity_logo( $post_id ) ) {
			echo wp_get_attachment_image( $logo_id, array( 38, 38 ) );
		}
	}
}

/**
 * Output markup for the logo feature in the entity details
 *
 * @since 2.0.0
 *
 * @uses get_entity_logo()
 * @uses has_image_size()
 * @uses wp_get_attachment_image()
 * @param WP_Post $post Post object
 */
function vgsr_entity_feature_logo_detail( $post ) {

	// Bail when this post has no logo
	if ( ! $logo_id = get_entity_logo( $post ) )
		return;

	$size = has_image_size( 'entity-logo' ) ? 'entity-logo' : array( 250, 250 );
	printf( '<div class="entity-logo">%s</div>', wp_get_attachment_image( $logo_id, $size ) );
}

/** Update *************************************************************/

/**
 * Update routine for version 1.1.0
 *
 * @since 1.1.0
 *
 * @global $wpdb
 *
 * @uses get_post_meta()
 * @uses update_post_meta()
 */
function vgsr_entity_update_110() {
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
		foreach ( $query->posts as $kast_id ) {
			$value = get_post_meta( $kast_id, 'since', true );

			if ( $value ) {
				$date  = DateTime::createFromFormat( 'd/m/Y', $value );
				if ( $date ) {
					$value = $date->format( 'Y-m-d' );
					update_post_meta( $kast_id, 'since', $value );
				}
			}
		}
	}
}
