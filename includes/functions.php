<?php

/**
 * VGSR Entity Functions
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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

/** Features ***********************************************************/

/**
 * Return the entity's logo ID
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post_id Post ID or object
 * @return int|bool Logo post ID or False when not found
 */
function get_entity_logo( $post_id ) {
	if ( ! $post = get_post( $post_id ) )
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
 * @since 1.1.0
 *
 * @uses get_entity_logo()
 * @uses _vgsr_entity_feature_logo_html()
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
	wp_enqueue_script( 'vgsr-entity-media-editor', vgsr_entity()->includes_url . 'assets/js/media-editor.js', array( 'vgsr-entity-admin', 'media-editor' ), '1.1.0', true );
}

	/**
	 * Return the logo feature editor HTML
	 *
	 * @since 1.1.0
	 *
	 * @see _wp_post_thumbnail_html()
	 *
	 * @param int $post_id Post ID
	 * @param int $logo_id Post ID
	 * @return string Editor HTML
	 */
	function _vgsr_entity_feature_logo_html( $logo_id, $post_id ) {
		global $_wp_additional_image_sizes;

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
			$size = isset( $_wp_additional_image_sizes['entity-logo'] ) ? 'entity-logo' : array( 266, 266 );
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
 * Modify the post's media settings
 *
 * @since 1.1.0
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
	if ( is_entity( $post->post_type ) ) {
		$logo_id = get_entity_logo( $post );
		$settings['post']['entityLogoId'] = $logo_id ? $logo_id : -1;
	}

	return $settings;
}

/**
 * Save an entity's logo feature input
 *
 * @since 1.1.0
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
 * Modify the current screen's columns
 *
 * @since 1.1.0
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
 * Output the list table column content
 *
 * @since 1.1.0
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
