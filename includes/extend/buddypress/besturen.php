<?php

/**
 * VGSR Entity BuddyPress Bestuur Functions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Details *******************************************************************/

/**
 * Modify the Bestuur's position name to link to the member's profile
 *
 * @since 2.0.0
 *
 * @param string $name Displayed name
 * @param WP_User|bool $user User object or False when not found
 * @param array $args Bestuur position arguments
 */
function vgsr_entity_bp_bestuur_position_name( $name, $user, $args ) {

	// For existing users
	if ( is_a( $user, 'WP_User' ) ) {

		// Collect template global. Might not exist yet.
		global $members_template;
		$_members_template = $members_template;

		/**
		 * Use BP member loop for using template tags
		 *
		 * Setting up the template loop for each member is really not efficient,
		 * but for now it does the job.
		 */
		if ( bp_has_members( array(
			'type'         => '',
			'include'      => $user->ID,
			'search_terms' => false      // Ignore global search terms
		) ) ) :
			while ( bp_members() ) : bp_the_member();

				// Member profile link
				$name = sprintf( '<a href="%s">%s</a>', bp_get_member_permalink(), bp_get_member_name() );
			endwhile;
		endif;

		// Reset global
		$members_template = $_members_template;
	}

	return $name;
}
