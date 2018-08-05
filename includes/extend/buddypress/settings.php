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
 * Modify the admin settings sections for BuddyPress settings
 *
 * @since 2.0.0
 *
 * @param array $sections Settings sections
 * @return array Settings sections
 */
function vgsr_entity_bp_settings_sections( $sections = array() ) {

	// Add Profile section
	if ( bp_is_active( 'xprofile' ) ) {
		$sections['bp-profile'] = array(
			'title'    => esc_html__( 'Profile Settings', 'vgsr-entity' ),
			'callback' => 'vgsr_entity_bp_settings_profile_section',
		);
	}

	return $sections;
}

/**
 * Modify the admin settings fields for BuddyPress settings
 *
 * @since 2.0.0
 *
 * @param array $fields Settings fields
 * @return array Settings fields
 */
function vgsr_entity_bp_settings_fields( $fields ) {

	// Define local vars
	$access = vgsr_entity_check_access();

	// Add Profile settings
	if ( bp_is_active( 'xprofile' ) ) {
		$fields['bp-profile'] = array(

			// Dispuut members
			'bp-members-field' => array(
				'title'             => esc_html__( 'Members Field', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
				'sanitize_callback' => 'intval',
				'entity'            => array( 'dispuut' ),
				'column_title'      => esc_html__( 'Members', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-members-field',
					'description' => esc_html__( "Select the field that holds the Dispuut's members.", 'vgsr-entity' ),
				),

				// Field display
				'is_entry_meta'     => true,
				'meta_label'        => _n_noop( '%d Member', '%d Members', 'vgsr-entity' ),
				'detail_callback'   => 'vgsr_entity_bp_list_post_members',
				'show_detail'       => $access,
			),

			// Kast residents
			'bp-residents-field' => array(
				'title'             => esc_html__( 'Residents Field', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Residents', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-residents-field',
					'description' => esc_html__( "Select the field that holds the Kast's residents.", 'vgsr-entity' ),
				),

				// Field display
				'is_entry_meta'     => true,
				'meta_label'        => _n_noop( '%d Resident', '%d Residents', 'vgsr-entity' ),
				'detail_callback'   => 'vgsr_entity_bp_list_post_residents',
				'show_detail'       => $access,
			),

			// Kast former residents
			'bp-olim-residents-field' => array(
				'title'             => esc_html__( 'Former Residents Field', 'vgsr-entity' ),
				'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'column_title'      => esc_html__( 'Former Residents', 'vgsr-entity' ),
				'args'              => array(
					'setting'     => 'bp-olim-residents-field',
					'description' => esc_html__( "Select the field that holds the Kast's former residents.", 'vgsr-entity' ),
				),

				// Field display
				'detail_callback'   => 'vgsr_entity_bp_list_post_olim_residents',
				'show_detail'       => $access,
			)
		);

		// Kast Address fields
		foreach ( vgsr_entity()->kast->address_meta() as $meta ) {
			$fields['bp-profile']["bp-address-map-{$meta['name']}"] = array(
				'title'             => sprintf( esc_html__( 'Address: %s', 'vgsr-entity' ), $meta['column_title'] ),
				'callback'          => 'vgsr_entity_bp_xprofile_field_setting',
				'sanitize_callback' => 'intval',
				'entity'            => array( 'kast' ),
				'args'              => array(
					'setting'     => "bp-address-map-{$meta['name']}",
					'description' => sprintf( esc_html__( "Select the profile field that holds this address detail: %s.", 'vgsr-entity' ), $meta['column_title'] ),
				),
			);
		}
	}

	return $fields;
}

/** Profile *******************************************************************/

/**
 * Output the content of the BuddyPress Profile settings section
 *
 * @since 2.0.0
 */
function vgsr_entity_bp_settings_profile_section() {
	$post_type = get_current_screen()->post_type; ?>

	<p><?php printf( esc_html__( 'Customize the settings that relate %s to member profiles.', 'vgsr-entity' ), get_post_type_object( $post_type )->labels->name ); ?></p>

	<?php
}

/**
 * Display a BuddyPress XProfile field selector settings field
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
 * Output or return a dropdown with BuddyPress XProfile fields
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
