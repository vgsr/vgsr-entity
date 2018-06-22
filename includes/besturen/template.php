<?php

/**
 * VGSR Entity Bestuur Template Functions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the post ID for the current bestuur
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_current_bestuur'
 *
 * @param bool $object Optional. Whether to return as a post object. Defaults to false.
 * @return int|WP_Post|false Post ID or object or False when not found
 */
function vgsr_entity_get_current_bestuur( $object = false ) {

	// Get post setting and validate
	$post = (int) get_option( '_bestuur-latest-bestuur' );

	// Get post object
	if ( $post && $object ) {
		$post = get_post( $post );

	// Default false for invalid post
	} elseif ( ! $post ) {
		$post = false;
	}

	return apply_filters( 'vgsr_entity_get_current_bestuur', $post, $object );
}

/**
 * Return whether the post is the current bestuur
 *
 * @since 2.0.0
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return bool Is this the current bestuur?
 */
function vgsr_entity_is_current_bestuur( $post = 0 ) {
	$post   = get_post( $post );
	$retval = false;

	if ( $post && vgsr_entity_get_current_bestuur() === $post->ID ) {
		$retval = true;
	}

	return $retval;
}

/** Positions **********************************************************/

/**
 * Display the bestuur positions entity detail
 *
 * @since 2.0.0
 *
 * @param WP_Post $post Post object
 */
function vgsr_entity_bestuur_positions_detail( $post ) {

	// Bail when no positions are signed for this entity
	if ( ! $positions = vgsr_entity_bestuur_get_positions( $post ) )
		return;

	?>

	<div class="bestuur-positions">
		<h4><?php esc_html( _ex( 'Members', 'Bestuur positions', 'vgsr-entity' ) ); ?></h4>

		<dl>
			<?php foreach ( $positions as $args ) : ?>
			<dt class="position position-<?php echo $args['slug']; ?>"><?php echo $args['label']; ?></dt>
			<dd class="member"><?php

				// Use existing user's display name
				if ( $user = get_user_by( is_numeric( $args['user'] ) ? 'id' : 'slug', $args['user'] ) ) {
					echo $user->display_name;

				// Default to the provided 'user' name or content
				} else {
					echo $args['user'];
				}
			?></dd>
			<?php endforeach; ?>
		</dl>
	</div>

	<?php
}

/** Theme **************************************************************/

/**
 * Modify the document title for our entity
 *
 * @since 2.0.0
 *
 * @param array $title Title parts
 * @return array Title parts
 */
function vgsr_entity_bestuur_document_title_parts( $title ) {

	// When this is our entity
	if ( vgsr_is_bestuur() ) {
		$title['title'] = sprintf(
			/* translators: 1. Bestuur title, 2. Bestuur season */
			esc_html__( '%1$s (%2$s)', 'vgsr-entity' ),
			$title['title'],
			vgsr_entity_get_meta( 0, 'season' )
		);
	}

	return $title;
}

/**
 * Modify Entity Menu Widget posts arguments
 *
 * @since 1.0.0
 *
 * @param array $args The arguments for get_posts()
 * @return array $args
 */
function vgsr_entity_bestuur_widget_menu_order( $args ) {

	// Define query order
	$args['order'] = get_option( '_bestuur-menu-order' ) ? 'DESC' : 'ASC';

	return $args;
}
