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
			'callback' => 'vgsr_entity_settings_main_section',
			'page'     => '',
		),

		// Attribute Settings
		'attributes' => array(
			'title'    => esc_html__( 'Attribute Settings', 'vgsr-entity' ),
			'callback' => 'vgsr_entity_settings_attribute_section',
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

			// Entity slug
			'entity-slug' => array(
				'title'             => esc_html__( 'Entity Slug', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_settings_display_entity_slug_field',
				'sanitize_callback' => 'sanitize_key',
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
	$type   = vgsr_entity_get_type( $type );
	$fields = array();

	if ( $type ) {

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
	}

	return $fields;
}

/** Main ***************************************************************/

/**
 * Output the content of the main settings section
 *
 * @since 2.0.0
 */
function vgsr_entity_settings_main_section() {

	// Flush rerwite rules if this setting is saved
	if ( isset( $_GET['settings-updated'] ) && isset( $_GET['page'] ) ) {
		flush_rewrite_rules();
	} ?>

	<p><?php esc_html_e( 'Customize the entity page and permalink structure here.', 'vgsr-entity' ); ?></p>

	<?php
}

/**
 * Output the content of the entity parent page settings field
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

	<p class="description"><?php printf( esc_html__( 'Select the page that should act as the parent page for %s.', 'vgsr-entity' ), '"' . get_post_type_object( $post_type )->labels->name . '"' ); ?></p>

	<?php
}

/**
 * Output the content of the entity slug settings field
 *
 * @since 2.0.0
 */
function vgsr_entity_settings_display_entity_slug_field() {

	// Get VGSR Entity
	$post_type = get_current_screen()->post_type;
	$type      = vgsr_entity_get_type( $post_type, true );

	// Bail when this is not an entity
	if ( ! $type )
		return;

	// Define setting name
	$input = "_{$type->type}-entity-slug";

	// Only when no parent page is selected
	if ( ! vgsr_entity_get_entity_parent( $type->type ) ) : ?>

		<input name="<?php echo $input; ?>" id="<?php echo $input; ?>" type="text" class="regular-text code" value="<?php echo $type->get_setting( 'entity-slug' ); ?>" />

	<?php else : ?>

		<p><?php esc_html_e( 'When a parent page is selected, there is no slug to define.', 'vgsr-entity' ); ?></p>

	<?php endif;
}

/** Attributes *********************************************************/

/**
 * Output the content of the attributes settings section
 *
 * @since 2.0.0
 */
function vgsr_entity_settings_attribute_section() { ?>

	<p><?php esc_html_e( 'Customize the settings for entity attributes here.', 'vgsr-entity' ); ?></p>

	<?php
}
