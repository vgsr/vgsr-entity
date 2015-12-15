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
