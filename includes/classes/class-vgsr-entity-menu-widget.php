<?php

/**
 * VGSR Entity Menu Widget Class
 *
 * @package VGSR Entity
 * @subpackage Widgets
 */

if ( ! class_exists( 'VGSR_Entity_Menu_Widget' ) ) :

/**
 * VGSR Entity Menu Widget
 *
 * @since 1.0.0
 */
class VGSR_Entity_Menu_Widget extends WP_Widget {

	/**
	 * Construct Entity Menu Widget
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Widget::__construct()
	 */
	public function __construct() {
		parent::__construct(
			'vgsr_entity_family', // VGSR base ID
			__( 'VGSR Entity Menu', 'vgsr-entity' ), // Widget name
			array( 
				'description' => __( 'Display entity menu list', 'vgsr-entity' )
			)
		);
	}

	/**
	 * Front-end display of widget
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Widget::widget()
	 *
	 * @uses VGSR_Entities::get_entity_parent_ids()
	 * @uses get_posts()
	 *
	 * @param array $args Widget arguments
	 * @param array $instance Saved widget values from DB
	 */
	public function widget( $args, $instance ) {

		// Define local variable(s)
		$post_type  = false;
		$entity     = vgsr_entity();
		$entities   = $entity->entities;
		$parent_ids = $entity->get_entity_parent_ids();
		$parent     = false;

		// Are we on a post type page? Explicitly check for a valid ID to
		// check for cases where the global post object is a dummy.
		if ( ( $post = get_post() ) && $post->ID ) {
			if ( in_array( $post->ID, $parent_ids ) ) {
				$_parent   = array_keys( $parent_ids, $post->ID );
				$post_type = reset( $_parent );
				$parent    = $post->ID;
			} else if ( in_array( $post->post_type, $entities ) ) {
				$post_type = $post->post_type;
			}

		// Are we otherwise entity related?
		} else if ( in_array( get_query_var( 'post_type' ), $entities ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		// Bail when there's no entity context
		if ( ! $post_type || ! in_array( $post_type, $entities ) || ! post_type_exists( $post_type ) )
			return;

		// Get all post type items
		$items = get_posts( apply_filters( 'vgsr_entity_menu_widget_get_posts', array( 
			'post_type'   => $post_type,
			'orderby'     => 'menu_order',
			'numberposts' => -1,
			'post_status' => 'publish',
			'post_parent' => $parent ? $parent : $parent_ids[ $post_type ]
		) ) );

		?>

		<?php echo $args['before_widget']; ?>
			<?php echo $args['before_title'] . get_post_type_object( $post_type )->labels->name . $args['after_title']; ?>

			<ul id="menu-<?php echo $post_type; ?>" class="menu">
				<?php foreach( $items as $item ) {

					// Mimic nav-menu list classes
					$class = 'menu-item menu-item-type-post_type menu-item-object-'. $post_type .' menu-item-'. $item->ID;
					if ( is_single() && $post->ID === $item->ID ) {
						$class .= ' current-menu-item current_'. $post_type .'_item';
					} 

					?>
					<li class="<?php echo esc_attr( $class ); ?>">
						<a href="<?php echo esc_attr( get_permalink( $item->ID ) ); ?>"><?php echo get_the_title( $item->ID ); ?></a>
					</li>
					<?php
				} ?>

			</ul>

		<?php echo $args['after_widget'];
	}

	/**
	 * Back-end widget form
	 *
	 * Use $this->get_field_id() to fetch the widget input ID.
	 * Use $this->get_field_name() to fetch the widget input name.
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from DB
	 */
	public function form( $instance ) { ?>

		<p><?php _e( "This widget will only be shown on a entity related page.", 'vgsr-entity' ); ?></p>
		<p><?php _e( 'There are no settings for this widget', 'vgsr-entity' ); ?></p>

		<?php
	}

	/**
	 * Sanitize widget form values before saving
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance The new values
	 * @param array $old_instance The old values
	 * @return array The sanitized values save for saving to DB
	 */
	public function update( $new_instance, $old_instance ) {
		return $instance;
	}
}

endif; // class_exists
