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
 * Modify the query clauses for the user ids query
 *
 * @since 2.1.0
 *
 * @param array $clauses UID query clauses
 * @param BP_User_Query $query Query object
 * @return array UID query clauses
 */
function vgsr_entity_bp_user_query_uid_clauses( $clauses, $query ) {

	/**
	 * Support members search based on entity posts matches
	 */
	if ( $search_terms = $query->query_vars['search_terms'] ) {

		// Try to match an entity post by title
		$post_query = new WP_Query( array(
			'post_type' => vgsr_entity_get_post_types(),
			'title'     => $search_terms,
			'per_page'  => 1
		) );

		// Post was found
		if ( $post_query->posts ) {
			$post             = $post_query->posts[0];
			$matched_user_ids = array();

			switch ( vgsr_entity_get_type( $post ) ) {
				case 'bestuur' :
					$matched_user_ids = array_filter(
						wp_list_pluck(
							vgsr_entity_bestuur_get_positions( $post ),
							'user'
						), 'is_numeric' // Remove entries that are not user ids
					);
					break;
				case 'dispuut' :
					$matched_user_ids = vgsr_entity_bp_get_post_users(
						'bp-members-field',
						$post,
						array(
							'fields' => 'ids'
						)
					);
					break;
				case 'kast' :
					$matched_user_ids = vgsr_entity_bp_get_post_users(
						'bp-residents-field',
						$post,
						array(
							'fields' => 'ids'
						)
					);
					break;
			}

			// Append search clause
			if ( $matched_user_ids ) {
				$clauses['where']['search'] = '(' . $clauses['where']['search'] . " OR u.{$query->uid_name} IN (" . implode( ',', $matched_user_ids ) . ') )';
			}
		}
	}

	return $clauses;
}

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
	$r = wp_parse_args( $args, array(
		'post'     => 0,
		'multiple' => false
	) );

	// Bail when the post is invalid
	if ( ! $post = get_post( $r['post'] ) )
		return false;

	$type = vgsr_entity_get_type_object( $post );

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
		'multiple' => $r['multiple']
	) );

	// Setup query modifier
	add_action( 'bp_pre_user_query_construct', 'vgsr_entity_bp_filter_user_query_post_users' );

	// Query members and setup members template
	$has_members = bp_has_members( wp_parse_args( $r, array(
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
 * @uses apply_filters() Calls 'vgsr_entity_bp_the_members_list_limit'
 *
 * @param WP_Post $post Post object
 * @param array $args List arguments, supports these args:
 *  - string      $field      Option name of the field
 *  - string      $label      Label of the list. Defaults to 'Members'.
 *  - bool        $multiple   Whether the field supports multiple values. Defaults to False.
 *  - string|bool $vgsr       Whether to query for vgsr users. Defaults to True.
 *  - int         $per_page   Query limit. Defaults to 12 for non-singular pages, else 0.
 *  - string      $limit_link The link used for limited lists. Defaults to the post permalink.
 */
function vgsr_entity_bp_the_members_list( $post, $args = array() ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) )
		return;

	// Parse args
	$r = wp_parse_args( $args, array(
		'field'      => '',
		'label'      => esc_html__( 'Members', 'vgsr-entity' ),
		'multiple'   => false,
		'vgsr'       => true,

		// Limit
		'per_page'   => ( ! is_singular() ) ? 12 : 0,
		'limit_link' => get_permalink( $post )
	) );

	// Add post argument
	$r['post']     = $post->ID;

	// Define list limit
	$r['per_page'] = (int) apply_filters( 'vgsr_entity_bp_the_members_list_limit', $r['per_page'], $r );

	// Bail when this post has no members
	if ( ! vgsr_entity_bp_has_members_for_post( $r['field'], $r ) )
		return;

	?>

	<div class="entity-members">
		<h4><?php echo esc_html( $r['label'] ); ?></h4>

		<ul class="bp-item-list">
			<?php while ( bp_members() && vgsr_entity_bp_members_list_limiting() ) : bp_the_member(); ?>

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

			<?php if ( vgsr_entity_bp_members_list_is_limited() ) : ?>

				<li class="bp-list-limit <?php echo $GLOBALS['members_template']->total_member_count % 2 ? 'odd' : 'even'; ?>">
					<div class="item">
						<?php printf( $r['limit_link'] ? '<a href="%1$s">%2$s</a>' : '<span>%2$s</span>',
							esc_url( $r['limit_link'] ),
							'&plus;' . vgsr_entity_bp_members_list_limited_count()
						); ?>
					</div>
				</li>

			<?php endif; ?>
		</ul>
	</div>

	<?php
}

/**
 * Return whether the profiles list limit is applied
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template
 *
 * @param int|bool $limit Optional. Custom limit value to check against. Defaults to the member count.
 * @return bool Is list limit applied?
 */
function vgsr_entity_bp_members_list_is_limited( $limit = null ) {
	global $members_template;

	// Define return variable
	$retval = false;
	$limit  = null === $limit ? $members_template->member_count : (int) $limit;

	// Determine whether to limit hte
	if ( $limit > 0 ) {
		$retval = $members_template->total_member_count > $limit;
	}

	return $retval;
}

/**
 * Return whether the profiles list limit is reached
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template
 *
 * @param int|bool $limit Optional. Custom limit value to check against. Defaults to the member count.
 * @return bool Is list limit reached?
 */
function vgsr_entity_bp_members_list_limiting( $limit = null ) {
	global $members_template;

	// Define return variable
	$retval = true;
	$limit  = null === $limit ? $members_template->member_count : (int) $limit;

	// Determine limit reached by current loop iteration
	if ( $limit > 0 && vgsr_entity_bp_members_list_is_limited( $limit ) ) {
		$retval = $members_template->current_member < ( $limit - 2 );
	}

	return $retval;
}

/**
 * Return the limited count for the profiles list
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template
 *
 * @param int|bool $limit Optional. Custom limit value to check against. Defaults to the member count.
 * @return int Limited list count
 */
function vgsr_entity_bp_members_list_limited_count( $limit = null ) {
	global $members_template;

	// Define return variable
	$retval = 0;
	$limit  = null === $limit ? $members_template->member_count : (int) $limit;

	// Determine whether to limit hte
	if ( $limit > 0 && vgsr_entity_bp_members_list_is_limited( $limit ) ) {
		$retval = $members_template->total_member_count - $limit + 1;
	}

	return $retval;
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
		'vgsr'  => 'lid'
	) );

	// For singular posts, display oud-leden
	if ( is_singular() ) {

		// Construct limit link
		$query_arg  = bp_core_get_component_search_query_arg( 'members' );
		$limit_link = add_query_arg( $query_arg, urlencode( get_post( $post )->post_title ), bp_get_members_directory_permalink() );

		vgsr_entity_bp_the_members_list( $post, array(
			'field'      => 'bp-members-field',
			'label'      => esc_html__( 'Oud-leden', 'vgsr-entity' ),
			'type'       => 'random',
			'vgsr'       => 'oud-lid',
			'per_page'   => 12,
			'limit_link' => $limit_link
		) );
	}
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
		'vgsr'  => true
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
			'vgsr'     => true
		) );
	}
}
