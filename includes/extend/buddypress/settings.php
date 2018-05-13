<?php

/**
 * VGSR Entity BuddyPress Settings Functions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Display an XProfile field selector settings field
 *
 * @since 2.0.0
 */
function vgsr_entity_bp_xprofile_field_setting( $args = array() ) {

	// Get current post type and the settings field's value
	$post_type = get_current_screen()->post_type;
	$field_id  = vgsr_entity_bp_get_field( $args['setting'], $post_type );

	// Fields dropdown
	vgsr_entity_bp_xprofile_fields_dropdown( array(
		'name'     => "_{$post_type}-{$args['setting']}",
		'selected' => $field_id,
		'echo'     => true,
	) );

	// Display View link
	if ( current_user_can( 'bp_moderate' ) && $field = xprofile_get_field( $field_id ) ) {
		printf( ' <a class="button button-secondary" href="%s" target="_blank">%s</a>', 
			esc_url( add_query_arg(
				array(
					'page'     => 'bp-profile-setup',
					'group_id' => $field->group_id,
					'field_id' => $field->id,
					'mode'     => 'edit_field'
				),
				bp_get_admin_url( 'users.php' )
			) ),
			esc_html__( 'View', 'vgsr-entity' )
		);
	} ?>

	<p class="description"><?php printf( $args['description'], get_post_type_object( $post_type )->labels->name ); ?></p>

	<?php
}

/**
 * Output or return a dropdown with XProfile fields
 *
 * @since 2.0.0
 *
 * @param array $args Dropdown arguments
 * @return void|string Dropdown markup
 */
function vgsr_entity_bp_xprofile_fields_dropdown( $args = array() ) {

	// Parse default args
	$args = wp_parse_args( $args, array(
		'id' => '', 'name' => '', 'multiselect' => false, 'selected' => 0, 'echo' => false,
	) );

	// Bail when missing attributes
	if ( empty( $args['name'] ) )
		return '';

	// Default id attribute to name
	if ( empty( $args['id'] ) ) {
		$args['id'] = $args['name'];
	}

	// Get all field groups with their fields
	$xprofile = bp_xprofile_get_groups( array( 'fetch_fields' => true, 'hide_empty_groups' => true ) );

	// Start dropdown markup
	$dd  = sprintf( '<select id="%s" name="%s" %s>', esc_attr( $args['id'] ), esc_attr( $args['name'] ), $args['multiselect'] ? 'multiple="multiple"' : '' );
	$dd .= '<option value="">' . __( '&mdash; No Field &mdash;', 'vgsr-entity' )  . '</option>';

	// Walk profile groups
	foreach ( $xprofile as $field_group ) {

		// Start optgroup
		$dd .= sprintf( '<optgroup label="%s">', esc_attr( $field_group->name ) );

		// Walk profile group fields
		foreach ( $field_group->fields as $field ) {
			$dd .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $field->id ), selected( $args['selected'], $field->id, false ), esc_html( $field->name ) );
		}

		// Close optgroup
		$dd .= '</optgroup>';
	}

	// Close dropdown
	$dd .= '</select>';

	if ( $args['echo'] ) {
		echo $dd;
	} else {
		return $dd;
	}
}
