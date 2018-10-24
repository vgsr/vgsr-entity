<?php

/**
 * VGSR Entity BuddyPress Members Functions
 *
 * @package VGSR Entity
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *********************************************************************/

/**
 * Run a modified version of {@see bp_has_members()} for the given post users
 *
 * When the post has users, the `$members_template` global is setup for use.
 *
 * @since 2.0.0
 * @since 2.1.0 Modified parameters to accept a list of arguments as the second parameter
 *
 * @param string $field Settings field name
 * @param array $args Query arguments, supports these args:
 *  - int|WP_Post $post     Post ID or object. Defaults to the current post.
 *  - bool        $multiple Whether the profile field holds multiple values. Defaults to False.
 * @return bool Whether the post has any users
 */
function vgsr_entity_bp_has_members_for_post( $field, $args = array() ) {

	// Back-compat
	if ( ! is_array( $args ) ) {
		$args = array( 'post' => $args );

		if ( func_num_args() > 2 ) {
			$args['multiple'] = func_get_arg( 2 );
		}
	}

	// Parse args
	$args = wp_parse_args( $args, array(
		'post'     => 0,
		'multiple' => false
	) );

	// Bail when the post is invalid
	if ( ! $post = get_post( $args['post'] ) )
		return false;

	$type = vgsr_entity_get_type( $post, true );

	// Bail when the field is invalid
	if ( ! $type || ! $field_id = $type->get_setting( $field ) )
		return false;

	/**
	 * In order to pass vars to the query construct filters, we're forced to use global
	 * properties, since there is no way to passing them properly through `bp_has_members()`.
	 */
	vgsr_entity()->extend->bp->set_post_query_vars( array(
		'field_id' => $field_id,
		'post'     => $post,
		'multiple' => $args['multiple']
	) );

	// Setup query modifier
	add_action( 'bp_pre_user_query_construct', 'vgsr_entity_bp_filter_user_query_post_users' );

	// Remove non-query arguments
	unset( $args['post'], $args['multiple'] );

	// Query members and setup members template
	$has_members = bp_has_members( wp_parse_args( $args, array(
		'type'            => '',    // Query $wpdb->users, order by ID
		'per_page'        => 0,     // No limit
		'populate_extras' => false,
	) ) );

	// Unhook query modifier
	remove_action( 'bp_pre_user_query_construct', 'vgsr_entity_bp_filter_user_query_post_users' );

	// Reset query vars
	vgsr_entity()->extend->bp->reset_post_query_vars();

	return $has_members;
}

/**
 * Modify the BP_User_Query before query construction
 *
 * @since 2.0.0
 *
 * @param BP_User_Query $query
 */
function vgsr_entity_bp_filter_user_query_post_users( $query ) {

	// Get post query vars
	$vars = vgsr_entity()->extend->bp->get_post_query_vars();

	// Bail when the field or post is invalid
	if ( ! $vars->field_id || ! $post = get_post( $vars->post_id ) )
		return;

	/**
	 * Account for multi-value profile fields which are stored as
	 * serialized arrays.
	 *
	 * @see https://buddypress.trac.wordpress.org/ticket/6789
	 */
	if ( $vars->multiple ) {
		global $wpdb, $bp;

		// Query user ids that compare against post ID, title or slug
		$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->profile->table_name_data} WHERE field_id = %d AND ( value LIKE %s OR value LIKE %s OR value LIKE %s )",
			$vars->field_id, '%"' . $post->ID . '"%', '%"' . $post->post_title . '"%', '%"' . $post->post_name . '"%'
		) );

		// Bail query when nothing found
		if ( empty( $user_ids ) ) {
			$user_ids = array( 0 );
		}

		// Limit member query to the found users
		$query->query_vars['include'] = $user_ids;

	// Use BP_XProfile_Query
	} else {

		// Define XProfile query args
		$xprofile_query   = is_array( $query->query_vars['xprofile_query'] ) ? $query->query_vars['xprofile_query'] : array();
		$xprofile_query[] = array(
			'field' => $vars->field_id,
			// Compare against post ID, title or slug
			'value' => array( $post->ID, $post->post_title, $post->post_name ),
		);

		$query->query_vars['xprofile_query'] = $xprofile_query;
	}
}

/** Template ******************************************************************/

/**
 * Output a list of members of the post's field
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_bp_members_list_limit'
 *
 * @param WP_Post $post Post object
 * @param array $args List arguments, supports these args:
 *  - string $field    Option name of the field
 *  - string $label    Label of the list. Defaults to 'Members'.
 *  - bool   $multiple Whether the field supports multiple values. Defaults to False.
 */
function vgsr_entity_bp_the_members_list( $post, $args = array() ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) )
		return;

	// Parse list args
	$args = wp_parse_args( $args, array(
		'field'    => '',
		'label'    => esc_html__( 'Members', 'vgsr-entity' ),
		'multiple' => false,
	) );

	// Bail when this post has no members
	if ( ! vgsr_entity_bp_has_members_for_post( $args['field'], $post->ID, $args['multiple'] ) )
		return;

	// Define list limits
	$total_count = $GLOBALS['members_template']->total_member_count;
	$list_limit  = apply_filters( 'vgsr_entity_bp_members_list_limit', 12, $post );
	$apply_limit = ! is_singular() && $total_count > $list_limit;
	$limit_count = $apply_limit ? ( $list_limit - 1 ) : $total_count;

	?>

	<div class="entity-members">
		<h4><?php echo $args['label']; ?></h4>

		<ul class="bp-item-list">
			<?php while ( bp_members() && ( ! $apply_limit || $GLOBALS['members_template']->current_member < ( $limit_count - 1 ) ) ) : bp_the_member(); ?>

				<li <?php bp_member_class( array( 'member' ) ); ?>>
					<div class="item-avatar">
						<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar(); ?></a>
					</div>

					<div class="item">
						<div class="item-title">
							<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
						</div>
					</div>
				</li>

			<?php endwhile; ?>

			<?php if ( $apply_limit ) : ?>

				<li class="bp-list-limit <?php echo $limit_count % 2 ? 'even' : 'odd'; ?>">
					<div class="item">
						<a href="<?php the_permalink( $post ); ?>"><?php echo '&plus;' . ( $total_count - $limit_count ); ?></a>
					</div>
				</li>

			<?php endif; ?>
		</ul>
	</div>

	<?php
}

/**
 * Display the Members entity detail
 *
 * @since 2.0.0
 *
 * @param WP_Post $post Post object
 */
function vgsr_entity_bp_list_post_members( $post ) {
	vgsr_entity_bp_the_members_list( $post, array(
		'field' => 'bp-members-field',
		'label' => esc_html__( 'Members', 'vgsr-entity' ),
	) );
}

/**
 * Display the Residents entity detail
 *
 * @since 2.0.0
 *
 * @param WP_Post $post Post object
 */
function vgsr_entity_bp_list_post_residents( $post ) {
	vgsr_entity_bp_the_members_list( $post, array(
		'field' => 'bp-residents-field',
		'label' => esc_html__( 'Residents', 'vgsr-entity' ),
	) );
}

/**
 * Display the Former Residents entity detail
 *
 * @since 2.0.0
 *
 * @param WP_Post $post Post object
 */
function vgsr_entity_bp_list_post_olim_residents( $post ) {

	// For singular posts
	if ( is_singular() ) {
		vgsr_entity_bp_the_members_list( $post, array(
			'field'    => 'bp-olim-residents-field',
			'label'    => esc_html__( 'Former Residents', 'vgsr-entity' ),
			'multiple' => true,
		) );
	}
}
