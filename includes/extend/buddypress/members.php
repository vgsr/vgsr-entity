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
 *
 * @param string $field Settings field name
 * @param int|WP_Post $post Optional. Post ID or object. Defaults to the current post.
 * @param bool $multiple Optional. Whether the profile field holds multiple values.
 * @return bool Whether the post has any users
 */
function vgsr_entity_bp_has_members_for_post( $field, $post = 0, $multiple = false ) {

	// Bail when the post is invalid
	if ( ! $post = get_post( $post ) )
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
		'multiple' => $multiple
	) );

	// Setup query modifier
	add_action( 'bp_pre_user_query_construct', 'vgsr_entity_bp_filter_user_query_post_users' );

	// Query members and setup members template
	$has_members = bp_has_members( array(
		'type'            => '',    // Query $wpdb->users, order by ID
		'per_page'        => 0,     // No limit
		'populate_extras' => false,
	) );

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
 * @param WP_Post $post Post object
 * @param array $args List arguments
 */
function vgsr_entity_bp_the_members_list( $post, $args = array() ) {

	// Parse list args
	$args = wp_parse_args( $args, array(
		'field'    => '',
		'label'    => esc_html__( 'Members', 'vgsr-entity' ),
		'multiple' => false,
	) );

	// Bail when this post has no members
	if ( ! vgsr_entity_bp_has_members_for_post( $args['field'], $post->ID, $args['multiple'] ) )
		return;

	?>

	<div class="entity-members">
		<h4><?php echo $args['label']; ?></h4>

		<ul class="bp-item-list">
			<?php while ( bp_members() ) : bp_the_member(); ?>
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
	vgsr_entity_bp_the_members_list( $post, array(
		'field'    => 'bp-olim-residents-field',
		'label'    => esc_html__( 'Former Residents', 'vgsr-entity' ),
		'multiple' => true,
	) );
}
