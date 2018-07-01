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
	$is_singular = 0 === $post ? is_singular() : true;
	$post        = get_post( $post );
	$retval      = false;

	if ( $post && $is_singular && vgsr_entity_get_current_bestuur() === $post->ID ) {
		$retval = true;
	}

	return $retval;
}

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

/** Positions **********************************************************/

/**
 * Add the bestuur positions entity detail to the post content
 *
 * @since 2.0.0
 *
 * @param string $content Post content
 * @return string Post content
 */
function vgsr_entity_bestuur_positions_detail( $content ) {

	// Bail when filtering the excerpt
	if ( vgsr_entity_is_the_excerpt() )
		return $content;

	// Bail when no positions are signed for this entity
	if ( $positions = vgsr_entity_bestuur_get_positions( 0 ) ) {

		// Start output buffer
		ob_start(); ?>

		<div class="bestuur-positions">
			<h4 class="detail-title"><?php echo esc_html_x( 'Members', 'Bestuur positions', 'vgsr-entity' ); ?></h4>

			<dl>
				<?php foreach ( $positions as $args ) : ?>
				<dt class="position position-<?php echo $args['slug']; ?>"><?php echo $args['label']; ?></dt>
				<dd class="member"><?php

					// Display existing user's name, default to provided name
					$user = isset( $args['user'] ) ? get_user_by( is_numeric( $args['user'] ) ? 'id' : 'slug', $args['user'] ) : false;
					$name = $user ? $user->display_name : $args['user'];

					/**
					 * Filter the displayed name for the Bestuur's position
					 *
					 * @since 2.0.0
					 *
					 * @param string $name Displayed name
					 * @param WP_User|bool $user User object or False when not found
					 * @param array $args Bestuur position arguments
					 */
					echo apply_filters( 'vgsr_entity_bestuur_position_name', $name, $user, $args );
				?></dd>
				<?php endforeach; ?>
			</dl>
		</div>

		<?php

		// Get output buffer
		$positions = ob_get_clean();

		// Prefix content with positions
		$content = $positions . $content;
	}

	return $content;
}

/** Theme **************************************************************/

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
