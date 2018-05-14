<?php

/**
 * VGSR Entity BuddyPress Actions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Kast **********************************************************************/

add_filter( 'bp_xprofile_get_groups',         'vgsr_entity_bp_filter_kast_address_profile_groups_fields',  5, 2 );
add_filter( 'bp_get_the_profile_field_value', 'vgsr_entity_bp_kast_address_profile_field_value',          10, 3 );
add_filter( 'bp_get_member_profile_data',     'vgsr_entity_bp_kast_address_profile_field_data',           10, 2 );
add_filter( 'bp_get_profile_field_data',      'vgsr_entity_bp_kast_address_profile_field_data',           10, 2 );

/** Admin *********************************************************************/

if ( is_admin() ) {
	add_action( 'vgsr_entity_init', 'vgsr_entity_buddypress_admin' );
}
