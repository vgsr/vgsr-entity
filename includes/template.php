<?php

/**
 * VGSR Entity Template Functions
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query **************************************************************/

/**
 * Setup and run the entity query
 *
 * @since 2.0.0
 *
 * @param array $args Query arguments. See {@see WP_Query}.
 * @return bool Query has entities
 */
function vgsr_entity_query_entities( $args = array() ) {

	// Parse args
	$args = wp_parse_args( $args, array(
		'type'           => vgsr_entity_get_type(),
		'post_type'      => false,
		'post_status'    => 'publish',
		'posts_per_page' => -1
	) );

	// Bail when entity type is invalid
	if ( ! vgsr_entity_exists( $args['type'] ) ) {
		return false;
	}

	// Get entity type object
	$type = vgsr_entity_get_type( $args['type'], true );

	// Default to entity post type
	if ( ! $args['post_type'] ) {
		$args['post_type'] = $type->post_type;
	}

	// Get query and store in type object
	$type->query = new WP_Query( $args );

	return $type->query->have_posts();
}

/**
 * Return whether the query has entities to loop over
 *
 * @since 2.0.0
 *
 * @param string $type Optional. Entity type name. Defaults to current entity type.
 * @return bool Query has entities
 */
function vgsr_entity_has_entities( $type = '' ) {
	$type = vgsr_entity_get_type( $type, true );

	// Has query a next post?
	$has_next = $type ? $type->query->have_posts() : false;

	// Clean up after ourselves
	if ( ! $has_next ) {
		wp_reset_postdata();
	}

	return $has_next;
}

/**
 * Setup next entity in the current loop
 *
 * @since 2.0.0
 *
 * @param string $type Optional. Entity type name. Defaults to current entity type.
 */
function vgsr_entity_the_entity( $type = '' ) {
	$type = vgsr_entity_get_type( $type, true );

	if ( $type ) {
		$type->query->the_post();
	}
}

/**
 * Rewind the entities and reset post index
 *
 * @since 2.0.0
 *
 * @param string $type Optional. Entity type name. Defaults to current entity type.
 */
function vgsr_entity_rewind_entities( $type = '' ) {
	$type = vgsr_entity_get_type( $type, true );

	if ( $type ) {
		$type->query->rewind_posts();
	}
}

/**
 * Return whether we're in the entity loop
 *
 * @since 2.0.0
 *
 * @param string $type Optional. Entity type name. Defaults to current entity type.
 * @return bool Are we in the entity loop?
 */
function vgsr_entity_in_the_entity_loop( $type = '' ) {
	$type = vgsr_entity_get_type( $type, true );

	return $type ? $type->query->in_the_loop : false;
}

/**
 * Return the current entity post from the loop
 *
 * @since 2.0.0
 *
 * @param string $type Optional. Entity type name. Defaults to current entity type.
 * @return WP_Post|bool Post object or False when not found
 */
function vgsr_entity_get_entity( $type = '' ) {
	$type = vgsr_entity_get_type( $type, true );
	$post = false;

	// When in the loop, get the post
	if ( $type && $type->query->in_the_loop ) {
		$post = $type->query->post;
	}

	return $post;
}

/** Details ************************************************************/

/**
 * Modify the post content by adding entity details
 *
 * @since 2.0.0
 *
 * @param string $content Post content
 * @return string Post content
 */
function vgsr_entity_filter_content( $content ) {

	// When in the main query's single entity
	if ( is_main_query() && ! vgsr_entity_in_the_entity_loop() && is_entity() ) {

		// Prepend details to content
		$content = vgsr_entity_details() . $content;
	}

	return $content;
}

/**
 * Return the markup for a post's entity details
 *
 * @since 2.0.0
 *
 * @uses do_action() Calls 'vgsr_entity_{$type}_details'
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return string Entity details markup
 */
function vgsr_entity_details( $post = 0 ) {

	// Bail when this is not a post
	if ( ! $post = get_post( $post ) )
		return;

	// Bail when this is not an entity
	if ( ! is_entity( $post ) )
		return;

	$type = vgsr_entity_get_type( $post );

	// Start output buffer
	ob_start();

	/**
	 * Output entity details of the given post
	 *
	 * The `type` part in the action name points to the entity's type name.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	do_action( "vgsr_entity_{$type}_details", $post );

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
 * Append the entity list to the parent's post content.
 *
 * @since 2.0.0
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
				<?php if ( vgsr_entity_get_logo() ) : ?>
				<div class="entity-logo">
					<a href="<?php the_permalink(); ?>"><?php vgsr_entity_the_logo(); ?></a>
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
 * Return whether the post or post type is an entity
 *
 * @since 2.0.0
 *
 * @param WP_Post|int|string $post_type Optional. Post object or ID or post type. Defaults to the current post.
 * @return bool Is post (type) a VGSR entity?
 */
function is_entity( $post_type = 0 ) {

	// Default to the current post's post type
	if ( ! is_string( $post_type ) || ! post_type_exists( $post_type ) ) {
		$post_type = get_post_type( $post_type );
	}

	return in_array( $post_type, vgsr_entity_get_post_types(), true );
}
endif;

if ( ! function_exists( 'is_bestuur' ) ) :
/**
 * Return whether the post is a Bestuur
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Bestuur
 */
function is_bestuur( $post = 0 ) {
	return vgsr_entity_exists( 'bestuur' ) && get_post_type( $post ) === vgsr_entity_get_post_type( 'bestuur' );
}
endif;

if ( ! function_exists( 'is_dispuut' ) ) :
/**
 * Return whether the post is a Dispuut
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Dispuut
 */
function is_dispuut( $post = 0 ) {
	return vgsr_entity_exists( 'dispuut' ) && get_post_type( $post ) === vgsr_entity_get_post_type( 'dispuut' );
}
endif;

if ( ! function_exists( 'is_kast' ) ) :
/**
 * Return whether the post is a Kast
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Optional. Post ID or object
 * @return bool Post is a Kast
 */
function is_kast( $post = 0 ) {
	return vgsr_entity_exists( 'kast' ) && get_post_type( $post ) === vgsr_entity_get_post_type( 'kast' );
}
endif;

/**
 * Return whether the post is an entity parent page
 *
 * @since 2.0.0
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return string|bool Entity type name related to the parent page or False when not a parent.
 */
function vgsr_entity_is_parent( $post = 0 ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) )
		return false;

	// Find this post as a parent
	$type = array_search( $post->ID, vgsr_entity_get_entity_parents(), true );

	return $type;
}
