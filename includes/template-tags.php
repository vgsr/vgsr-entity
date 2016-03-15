<?php

/**
 * VGSR Entity Template Tags
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Details ************************************************************/

/**
 * Return the markup for a post's entity details
 *
 * @since 1.1.0
 *
 * @uses is_entity()
 * @uses do_action() Calls 'vgsr_entity_{$post_type}_details'
 *
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to current post.
 * @return string Entity details markup
 */
function vgsr_entity_details( $post = 0 ) {

	// Bail when this is not a post
	if ( ! $post = get_post( $post ) )
		return;

	// Bail when this is not an entity
	if ( ! is_entity( $post->post_type ) )
		return;

	// Start output buffer
	ob_start();

	/**
	 * Output entity details of the given post
	 *
	 * The `post_type` variable in the action name points to the post type.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post $post Post object
	 */
	do_action( "vgsr_entity_{$post->post_type}_details", $post );

	// Close output buffer
	$details = ob_get_clean();

	// Wrap details in div
	if ( ! empty( $details ) ) {
		$details = sprintf( '<div class="entity-details">%s</div>', $details );
	}

	return $details;
}

/** List ***************************************************************/

/**
 * Return an entity parent's entity posts HTML markup
 *
 * Append the entity list to the parent's post content
 *
 * @since 2.0.0
 *
 * @uses is_entity_parent()
 * @uses WP_Query
 * @uses get_entity_logo()
 * @uses the_permalink()
 * @uses the_entity_logo()
 * @uses the_title()
 * @uses get_the_permalink()
 * @uses entity_has_more_tag()
 * @uses the_content()
 *
 * @param string $content The post content
 * @return string $retval HTML
 */
function vgsr_entity_list( $content ) {

	// Bail when this is not an entity parent
	if ( ! $post_type = is_entity_parent() )
		return $content;

	// Get all entity posts
	if ( $entities = new WP_Query( array(
		'post_type'   => $post_type,
		'numberposts' => -1,
	) ) ) {

		// Make use of the read-more tag
		global $more; $more = 0;

		// Start output buffer
		ob_start(); ?>

		<div class="entity-list <?php echo $post_type; ?>-entities">

		<?php while ( $entities->have_posts() ) : $entities->the_post(); ?>
			<article <?php post_class(); ?>>

				<?php // Display entity logo ?>
				<?php if ( get_entity_logo() ) : ?>
				<div class="entity-logo">
					<a href="<?php the_permalink(); ?>"><?php the_entity_logo(); ?></a>
				</div>
				<?php endif; ?>

				<h3 class="entity-title"><?php the_title( sprintf( '<a href="%s">', get_the_permalink() ), '</a>' ); ?></h3>

				<?php // Display teaser content ?>
				<?php if ( entity_has_more_tag() ) : ?>
				<div class="entity-content">
					<?php the_content( '' ); ?>
				</div>
				<?php endif; ?>
				
			</article>
		<?php endwhile; ?>

		</div>

		<?php

		// Append output buffer to content
		$content .= ob_get_clean();

		// Reste global `$post`
		wp_reset_postdata();
	}

	return $content;
}

/** Is *****************************************************************/

if ( ! function_exists( 'is_entity' ) ) :
/**
 * Return whether the post('s post) type is an entity
 *
 * @since 1.1.0
 *
 * @param string|int|WP_Post $post_type Optional. Post type, post ID or object. Defaults
 *                                      to current post's post type.
 * @return bool Post (type) is an entity
 */
function is_entity( $post_type = 0 ) {

	// Default to the current post's post type
	if ( ! is_string( $post_type ) || ! post_type_exists( $post_type ) ) {
		$post_type = get_post_type( $post_type );
	}

	return in_array( $post_type, vgsr_entity()->get_entities() );
}
endif;

if ( ! function_exists( 'is_bestuur' ) ) :
/**
 * Return whether the post is a Bestuur
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Bestuur
 */
function is_bestuur( $post = 0 ) {
	return isset( vgsr_entity()->bestuur ) && ( get_post_type( $post ) === vgsr_entity()->bestuur->type );
}
endif;

if ( ! function_exists( 'is_dispuut' ) ) :
/**
 * Return whether the post is a Dispuut
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Dispuut
 */
function is_dispuut( $post = 0 ) {
	return isset( vgsr_entity()->dispuut ) && ( get_post_type( $post ) === vgsr_entity()->dispuut->type );
}
endif;

if ( ! function_exists( 'is_kast' ) ) :
/**
 * Return whether the post is a Kast
 *
 * @since 1.1.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Kast
 */
function is_kast( $post = 0 ) {
	return isset( vgsr_entity()->kast ) && ( get_post_type( $post ) === vgsr_entity()->kast->type );
}
endif;

if ( ! function_exists( 'is_entity_parent' ) ) :
/**
 * Return whether the post is an entity parent page
 *
 * @since 1.1.0
 *
 * @uses VGSR_Entity::get_entity_parents()
 * @uses post_type_exists()
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return string|bool Post type of parent's entity or False if it is not.
 */
function is_entity_parent( $post = 0 ) {
	if ( ! $post = get_post( $post ) )
		return false;

	// Find this post as a parent
	$post_type = array_search( $post->ID, vgsr_entity()->get_entity_parents() );

	return ( post_type_exists( $post_type ) ? $post_type : false );
}
endif;
