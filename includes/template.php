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
	$r = wp_parse_args( $args, array(
		'type'           => vgsr_entity_get_type(),
		'post_type'      => false,
		'post_status'    => 'publish',
		'posts_per_page' => -1
	) );

	// Bail when entity type is invalid
	if ( ! vgsr_entity_exists( $r['type'] ) ) {
		return false;
	}

	// Get entity type object
	$type = vgsr_entity_get_type( $r['type'], true );

	// Default to entity post type
	if ( ! $r['post_type'] ) {
		$r['post_type'] = $type->post_type;
	}

	// Get query and store in type object
	$type->query = new WP_Query( $r );

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
 * Return whether we're in the main query loop
 *
 * @since 2.0.0
 *
 * @return bool Are we in the main query loop?
 */
function vgsr_entity_is_main_query() {

	// Is this the main query?
	$main = is_main_query();

	// Check whether we're in any sort of entity loop
	foreach ( vgsr_entity_get_types( true ) as $type ) {
		if ( $type->query->in_the_loop ) {
			$main = false;
			break;
		}
	}

	return $main;
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

/** Post ***************************************************************/

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
	if ( ! vgsr_entity_is_the_excerpt() && vgsr_entity_is_main_query() && vgsr_is_entity() ) {

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
	if ( ! vgsr_is_entity( $post ) )
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

	// Stop output buffer
	$details = ob_get_clean();

	// Wrap details in div
	if ( ! empty( $details ) ) {
		$details = '<div class="entity-details">' . $details . '</div>';
	}

	return $details;
}

/**
 * Modify the post's CSS classes
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls vgsr_entity_{$type}_post_class
 *
 * @param array $classes Post CSS classes
 * @param array $class Additional class names
 * @param int $post_id Post ID
 * @return array Post CSS classes
 */
function vgsr_entity_filter_post_class( $classes, $class, $post_id ) {

	// This is an entity
	if ( $type = vgsr_entity_get_type( $post_id ) ) {

		// Entity type
		$classes[] = "entity-{$type}";

		// Entity logo
		$classes[] = vgsr_entity_has_logo( $post_id ) ? 'entity-has-logo' : 'entity-no-logo';

		// Enable filtering
		$classes = (array) apply_filters( "vgsr_entity_{$type}_post_class", $classes, $post_id );
	}

	return $classes;
}

/** Is_* ***************************************************************/

/**
 * Return whether the post or post type is an entity
 *
 * @since 2.0.0
 *
 * @param WP_Post|int|string $post_type Optional. Post object or ID or post type. Defaults to the current post.
 * @return bool Is post (type) an entity?
 */
function vgsr_is_entity( $post_type = 0 ) {

	// Default to the current post's post type
	if ( ! is_string( $post_type ) || ! post_type_exists( $post_type ) ) {
		$post_type = get_post_type( $post_type );
	}

	return in_array( $post_type, vgsr_entity_get_post_types(), true );
}

/**
 * Return whether the post is a Bestuur
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post.
 * @return bool Is post a Bestuur?
 */
function vgsr_is_bestuur( $post = 0 ) {
	return get_post_type( $post ) === vgsr_entity_get_post_type( 'bestuur' );
}

/**
 * Return whether the post is a Dispuut
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post.
 * @return bool Is post a Dispuut?
 */
function vgsr_is_dispuut( $post = 0 ) {
	return get_post_type( $post ) === vgsr_entity_get_post_type( 'dispuut' );
}

/**
 * Return whether the post is a Kast
 *
 * @since 2.0.0
 *
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post.
 * @return bool Is post a Kast?
 */
function vgsr_is_kast( $post = 0 ) {
	return get_post_type( $post ) === vgsr_entity_get_post_type( 'kast' );
}

/**
 * Return whether the post is of the given entity type
 *
 * @since 2.0.0
 *
 * @param string $type Entity type name
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post.
 * @return bool Is post of the given type?
 */
function vgsr_entity_is_post_of_type( $type, $post = 0 ) {
	$type = vgsr_entity_get_type( $type );
	return $type && get_post_type( $post ) === vgsr_entity_get_post_type( $type );
}

/**
 * Return whether the post is an entity parent page
 *
 * @since 2.0.0
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return string|bool Entity type name related to the parent page or False when not a parent.
 */
function vgsr_is_entity_parent( $post = 0 ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) )
		return false;

	// Find this post as a parent
	$type = array_search( $post->ID, vgsr_entity_get_entity_parents(), true );

	return $type;
}

/**
 * Return whether we're on an/the entity archive page
 *
 * @since 2.0.0
 *
 * @param string $type Optional. Entity type name to check the archive for. Defaults to none.
 * @return bool Is page an/the entity archive?
 */
function vgsr_is_entity_archive( $type = '' ) {

	// Default to false
	$retval = false;

	if ( vgsr_is_entity_parent() || is_post_type_archive( vgsr_entity_get_post_types() ) ) {

		// Check requested entity type
		$retval = $type ? vgsr_entity_get_type( $type ) === vgsr_entity_get_type() : true;
	}

	return $retval;
}

/**
 * Return whether we're on a plugin page
 *
 * @since 2.0.0
 *
 * @return bool On a plugin page
 */
function is_vgsr_entity() {

	// Default to false
	$retval = false;

	/** Entity ****************************************************************/

	if ( is_singular() && vgsr_is_entity() ) {
		$retval = true;

	/** Archives **************************************************************/

	} elseif ( vgsr_is_entity_archive() ) {
		$retval = true;
	}

	return $retval;
}

/** Archive ************************************************************/

/**
 * Modify globals after the main WP instance is setup
 *
 * Runs before the main `$wp_the_query` is set, which is used for admin bar links.
 *
 * @since 2.0.0
 *
 * @param WP $wp The main WordPres object
 */
function vgsr_entity_set_globals( $wp ) {

	// Entity archive
	if ( is_post_type_archive( vgsr_entity_get_post_types() ) ) {
		$parent = vgsr_entity_get_entity_parent( get_post_type(), true );

		// Set the page's global post
		if ( $parent ) {
			$GLOBALS['post']                        = $parent;
			$GLOBALS['wp_query']->queried_object    = $parent;
			$GLOBALS['wp_query']->queried_object_id = $parent->ID;
		}
	}
}

/**
 * Modify the template hierarchy stack for archive pages
 *
 * @since 2.0.0
 *
 * @param array $templates Template hierarchy
 * @return array Template hierarchy
 */
function vgsr_entity_archive_template_hierarchy( $templates ) {

	// Entity archive
	if ( $type = vgsr_entity_get_type() ) {

		// Prepend the entity type's archive template file
		array_splice( $templates, 0, 0, array(
			"archive-{$type}.php"
		) );
	}

	return $templates;
}

/**
 * Modify the post archive page title
 *
 * @since 2.0.0
 *
 * @param  string $title Archive page title
 * @return string Archive page title
 */
function vgsr_entity_get_the_archive_title( $title ) {

	// Get current post type
	$post_type = get_post_type();

	// Entity post archive
	if ( vgsr_is_entity( $post_type ) && is_post_type_archive( $post_type ) ) {
		$title = vgsr_entity_get_archive_title( $post_type );
	}

	return $title;
}

/**
 * Return the entity type's post archive title
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_archive_title'
 *
 * @param  string $type Optional. Entity type name. Defaults to the current type.
 * @return string Entity type's archive title.
 */
function vgsr_entity_get_archive_title( $type = '' ) {

	// Define return value
	$type  = vgsr_entity_get_type( $type );
	$title = '';

	if ( $type ) {
		$parent = vgsr_entity_get_entity_parent( $type, true );

		// Use parent post title
		if ( $parent ) {
			$title = vgsr_entity_call_with_post( $parent, 'get_the_title' );

		// Default to post type name
		} else {
			$title = vgsr_entity_get_post_type( $type, true )->labels->name;
		}
	}

	return apply_filters( 'vgsr_entity_get_archive_title', $title, $type );	
}

/**
 * Modify the post archive page description
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_get_the_archive_description'
 *
 * @param  string $description Archive page description
 * @return string Archive page description
 */
function vgsr_entity_get_the_archive_description( $description ) {

	// Get current post type
	$post_type = get_post_type();
	$type      = false;

	// Entity post archive
	if ( vgsr_is_entity( $post_type ) && is_post_type_archive( $post_type ) ) {
		$type   = vgsr_entity_get_type( $post_type );
		$parent = vgsr_entity_get_entity_parent( $type, true );

		// Use parent post content
		if ( $parent ) {
			// Get filtered parent post content
			$description = vgsr_entity_call_with_post( $parent, 'apply_filters', array( 'the_content', $parent->post_content ) );
		}
	}

	return apply_filters( 'vgsr_entity_get_the_archive_description', $description, $type );
}

/**
 * Output the entity type shortlist class
 *
 * @since 2.0.0
 *
 * @param string|array $class Optional. One or more classes to add to the class list.
 * @param string $type Optional. Entity type name. Defaults to the current type.
 */
function vgsr_entity_shortlist_class( $class = '', $type = '' ) {
	echo ' class="' . join( ' ', vgsr_entity_get_shortlist_class( $class, $type ) ) . '"';
}

/**
 * Return the entity type shortlist classes
 *
 * @since 2.0.0
 *
 * @param string|array $class Optional. One or more classes to add to the class list.
 * @param string $type Optional. Entity type name. Defaults to the current type.
 * @return array Entity shortlist classes
 */
function vgsr_entity_get_shortlist_class( $class = '', $type = '' ) {
	$type    = vgsr_entity_get_type( $type );
	$classes = array();

	if ( $class ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}
		$classes = array_map( 'esc_attr', $class );
	} else {
		$class = array();
	}

	$classes[] = 'entity-shortlist';

	if ( $type ) {
		$classes[] = "entity-shortlist-{$type}";

		// Entity logo
		$classes[] = vgsr_entity_supports( 'logo', $type ) ? 'feature-logo' : 'no-feature-logo';
	}

	$classes = array_map( 'esc_attr', $classes );

	// Enable filtering
	$classes = (array) apply_filters( 'vgsr_entity_get_shortlist_class', $classes, $class, $type );

	return array_unique( $classes );
}

/**
 * Output markup for the entity type shortlist
 *
 * @since 2.0.0
 *
 * @param string $type Optional. Entity type name. Defaults to the current type.
 */
function vgsr_entity_the_shortlist( $type = '' ) {
	echo vgsr_entity_get_shortlist( $type );
}

/**
 * Return markup for the entity type shortlist
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls vgsr_entity_get_shortlist
 *
 * @param string $type Optional. Entity type name. Defaults to the current type.
 * @return string Shortlist markup
 */
function vgsr_entity_get_shortlist( $type = '' ) {
	$type = vgsr_entity_get_type( $type );
	$html = '';

	// Get entity type shortlist
	if ( $type ) {
		$html = vgsr_entity_buffer_template_part( 'entity-shortlist', $type );
	}

	return apply_filters( 'vgsr_entity_get_shortlist', $html, $type );
}

/**
 * Modify the content to add the entity type shortlist
 *
 * @since 2.0.0
 *
 * @uses apply_filtesr() Calls vgsr_entity_{$type}_archive_add_shortlist
 *
 * @param string $content Post content
 * @param string $type Optional. Entity type name. Defaults to the current type.
 * @return string Post content
 */
function vgsr_entity_archive_add_shortlist( $content, $type = '' ) {
	$type = vgsr_entity_get_type( $type );

	// Enable shortlist addition through filter
	if ( $type && apply_filters( "vgsr_entity_{$type}_archive_add_shortlist", false ) ) {
		$content .= vgsr_entity_get_shortlist( $type );
	}

	return $content;
}

/**
 * Output navigation markup to next/previous plugin pages
 *
 * @see the_posts_navigation()
 *
 * @since 2.0.0
 *
 * @param array $args Arguments for {@see get_the_posts_navigation()}
 */
function vgsr_entity_the_posts_navigation( $args = array() ) {
	echo vgsr_entity_get_the_posts_navigation( $args );
}

	/**
	 * Return navigation markup to next/previous plugin pages
	 *
	 * @see get_the_posts_navigation()
	 *
	 * @since 2.0.0
	 *
	 * @uses apply_filters() Calls 'vgsr_entity_get_the_posts_navigation'
	 *
	 * @param array $args Arguments for {@see get_the_posts_navigation()}
	 * @return string Navigation markup
	 */
	function vgsr_entity_get_the_posts_navigation( $args = array() ) {
		return get_the_posts_navigation( apply_filters( 'vgsr_entity_get_the_posts_navigation', $args ) );
	}
