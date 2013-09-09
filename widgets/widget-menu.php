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
 * @since 0.1
 */
class VGSR_Entity_Menu_Widget extends WP_Widget {

	/**
	 * Construct Entity Menu Widget
	 *
	 * @since 0.1
	 *
	 * @see WP_Widget::__construct()
	 */
	public function __construct() {
		parent::__construct(
			'vgsr_entity_family', // VGSR base ID
			__('VGSR Entity Menu', 'vgsr-entity'), // Widget name
			array( 'description' => __( 'Display VGSR Entity menu list', 'vgsr-entity' ) )
		);
	}

	/**
	 * Front-end display of widget
	 *
	 * @since 0.1
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
		global $post, $vgsr_entity;

		// Are we on a parent page?
		$parents = $vgsr_entity->get_entity_parent_ids();
		$parent  = in_array( $post->ID, $parents ) ? reset( array_keys( $parents, $post->ID ) ) : false;

		// Don't display widget if not on entity page or entity parent page
		if ( ! in_array( $post->post_type, $vgsr_entity->entities ) && ! $parent )
			return;

		// Get generic widget variables
		extract( $args );

		// Get post type label
		$cpt    = get_post_type_object( $parent ? $parent : $post->post_type );
		$title  = $cpt->labels->name;

		// Get all post type items
		$items  = get_posts( apply_filters( 'vgsr_entity_menu_widget_get_posts', array( 'post_type' => 'any', 'orderby' => 'menu_order', 'numberposts' => -1, 'post_status' => 'publish', 'post_parent' => $parent ? $post->ID : $parents[$post->post_type] ) ) );

		// Defined by themes
		echo $before_widget;

		// Widget title - before & after defined by themes
		echo $before_title . $title . $after_title;

		// Display your widget here...
		// Start list
		echo '<ul id="menu-'. $post->post_type .'" class="menu">';

		// Loop over all children
		foreach( $items as $item ) {

			$class = 'menu-item menu-item-type-post_type menu-item-object-'. $item->post_type .' menu-item-'. $item->ID;

			// Don't do attachments
			if ( 'attachment' == $item->post_type )
				continue;

			// Is current item
			if ( $post->ID == $item->ID ) 
				$class .= ' current-menu-item current_'. $item->post_type .'_item';

			// Output list item
			echo '<li class="'. $class .'"><a href="'. get_permalink( $item->ID ) .'">'. $item->post_title .'</a></li>';
		}

		// End list
		echo '</ul>';

		// Defined by themes
		echo $after_widget;
	}

	/**
	 * Back-end widget form
	 * 
	 * Use $this->get_field_id($var) to fetch the widget input ID 
	 * Use $this->get_field_name($var) to fetch the widget input name
	 *
	 * @since 0.1
	 * 
	 * @see WP_Widget::form()
	 * 
	 * @param array $instance Previously saved values from DB
	 */
	public function form( $instance ) {
		?>
			<p>
				<?php _e( 'This widget will only be shown on a VGSR Entity page and on it\'s parent page.', 'vgsr-entity' );  ?>
			</p>
			<p>
				<?php _e( 'There are no settings for this widget', 'vgsr-entity' ); ?>
			</p>
		<?php
	}

	/**
	 * Sanitize widget form values before saving
	 *
	 * @since 0.1
	 * 
	 * @see WP_Widget::update()
	 * 
	 * @param array $new_instance The new values
	 * @param array $old_instance The old values
	 * @return array The sanitized values save for saving to DB
	 */
	public function update( $new_instance, $old_instance ) {

		// Accept previous values
		$instance = $old_instance;

		// Set new values if submitted
		if ( isset( $new_instance['title'] ) )
			$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
}

endif; // class_exists