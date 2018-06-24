<?php

/**
 * VGSR Entity Menu Widget Class
 *
 * @package VGSR Entity
 * @subpackage Widgets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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
		parent::__construct( 'vgsr_entity_family', esc_html__( 'VGSR Entity Menu', 'vgsr-entity' ), array(
			'description' => esc_html__( 'Display a list of all the entities of the type of the current page', 'vgsr-entity' )
		) );
	}

	/**
	 * Front-end display of widget
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Widget::widget()
	 *
	 * @uses apply_filters() Calls 'vgsr_entity_{$type}_menu_widget_query_args'
	 *
	 * @param array $args Widget arguments
	 * @param array $instance Saved widget values from DB
	 */
	public function widget( $args, $instance ) {

		// Bail when there's no related entity type, hiding the widget
		if ( ! $type = vgsr_entity_get_type() ) {
			return;
		}

		// Define local variables
		$post_id = get_the_ID();
		$qargs   = apply_filters( "vgsr_entity_{$type}_menu_widget_query_args", array(), $type );

		// Query entities
		if ( vgsr_entity_query_entities( $qargs ) ) {

			// Setup widget title
			$post_type_object = vgsr_entity_get_post_type( $type, true );
			$title            = $post_type_object->labels->name;

			// Provide link to parent page when we're not already there
			if ( ! vgsr_is_entity_parent() && ! is_post_type_archive( $post_type_object->name ) ) {
				$title = sprintf( '<a href="%s">%s</a>',
					esc_url( get_permalink( vgsr_entity_get_entity_parent( $type ) ) ),
					$title
				);
			}

			// Open widget structure
			echo $args['before_widget'];
			echo $args['before_title'] . $title . $args['after_title'];
			echo '<ul id="menu-{$type}" class="menu">';

			// Walk queried posts
			while ( vgsr_entity_has_entities( $type ) ) : vgsr_entity_the_entity( $type );

				// Mimic nav-menu list classes
				$class = sprintf( "menu-item menu-item-type-post_type menu-item-object-%s menu-item-%d",
					get_post_type(),
					get_the_ID()
				);

				// Mark the current post
				if ( is_single() && get_the_ID() === $post_id ) {
					$class .= sprintf( ' current-menu-item current_%s_item', get_post_type() );
				}

				// Print post list item
				printf( '<li class="%s"><a href="%s">%s</a></li>',
					esc_attr( $class ),
					esc_url( get_permalink() ),
					get_the_title()
				);

			endwhile;

			// Close widget structure
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

		<p><?php esc_html_e( 'This widget will only display a list of entities on a entity related page.', 'vgsr-entity' ); ?></p>
		<p class="description"><?php esc_html_e( 'There are no settings for this widget.', 'vgsr-entity' ); ?></p>

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
