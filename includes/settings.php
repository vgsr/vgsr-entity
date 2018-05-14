<?php

/**
 * VGSR Entity Settings Functions
 *
 * @package VGSR Entity
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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
	if ( $parent && get_post( $parent ) ) : ?>

	<a class="button button-secondary" href="<?php echo esc_url( get_permalink( $parent ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'vgsr-entity' ); ?></a>
	<?php endif; ?>

	<p class="description"><?php printf( esc_html__( 'Select the page that should act as the parent page for %s.', 'vgsr-entity' ), get_post_type_object( $post_type )->labels->name ); ?></p>

	<?php
}
