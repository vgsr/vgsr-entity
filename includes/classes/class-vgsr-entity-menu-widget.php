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
		parent::__construct( 'vgsr_entity_family', __( 'VGSR Entity Menu', 'vgsr-entity' ), array(
			'description' => __( 'Display a list of all the entities of the type of the current page', 'vgsr-entity' )
		) );
	}

	/**
	 * Front-end display of widget
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Widget::widget()
	 *
	 * @uses VGSR_Entity::get_entity_parents()
	 * @uses apply_filters() Calls 'vgsr_entity_menu_widget_get_posts'
	 * @uses get_post_type_object()
	 *
	 * @param array $args Widget arguments
	 * @param array $instance Saved widget values from DB
	 */
	public function widget( $args, $instance ) {

		// This is an entity parent page
		if ( is_entity_parent() ) {
			$parent    = get_post()->ID;
			$post_type = array_search( $parent, vgsr_entity()->get_entity_parents() );

		// This is an entity
		} elseif ( is_entity() ) {
			$parent    = get_post()->post_parent;
			$post_type = get_post_type();

		/**
		 * Without any explicit relation to an entity type, this
		 * widget is not displayed.
		 */
		} else {
			return;
		}

		// Get the current post
		$post_id = get_the_ID();

		// Get all post type items
		if ( $query = new WP_Query( apply_filters( 'vgsr_entity_menu_widget_get_posts', array(
			'post_type'   => $post_type,
			'post_parent' => $parent,
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'DESC',
		) ) ) ) {

			echo $args['before_widget'];
			echo $args['before_title'] . get_post_type_object( $post_type )->labels->name . $args['after_title'];

			printf( '<ul id="menu-%s" class="menu">', $post_type );

			// Walk queried posts
			while ( $query->have_posts() ) : $query->the_post();

				// Mimic nav-menu list classes
				$class = sprintf( "menu-item menu-item-type-post_type menu-item-object-%s menu-item-%d", get_post_type(), get_the_ID() );

				// This is the current post
				if ( is_single() && $post_id === get_the_ID() ) {
					$class .= sprintf( ' current-menu-item current_%s_item', get_post_type() );
				}

				// Print post list item
				printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( $class ), esc_url( get_permalink() ), get_the_title() );

			endwhile;

			// Reset globa post data
			wp_reset_postdata();

			echo '</ul>';

			echo $args['after_widget'];
		}
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

		<p><?php _e( "This widget will only display a list of entities on a entity related page.", 'vgsr-entity' ); ?></p>
		<p class="description"><?php _e( 'There are no settings for this widget.', 'vgsr-entity' ); ?></p>

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
		return $new_instance;
	}
}

endif; // class_exists
