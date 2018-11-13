<?php

/**
 * VGSR Entity Theme-Compat Functions
 *
 * Override this logic with your own vgsr-entity-functions.php inside your theme.
 *
 * @package VGSR Entity
 * @subpackage Theme
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_Default' ) ) :
/**
 * Loads default VGSR Entity theme compatibility functionality
 *
 * @since 2.0.0
 */
class VGSR_Entity_Default {

	/**
	 * Setup the class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 2.0.0
	 */
	public function setup_actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles'      )    );
		add_filter( 'the_content',        array( $this, 'the_archive_content' ), 2 );
	}

	/**
	 * Load the theme styles
	 *
	 * @since 2.0.0
	 */
	public function enqueue_styles() {

		// Bail when not an a plugin page
		if ( ! is_vgsr_entity() )
			return;

		vgsr_entity_enqueue_style( 'vgsr-entity', 'css/vgsr-entity.css', array(), vgsr_entity_get_version(), 'screen' );
	}

	/**
	 * Modify early the post content for post archives
	 *
	 * @since 2.0.0
	 *
	 * @param  string $content Post content
	 * @return string Post content
	 */
	public function the_archive_content( $content ) {

		// Limit words in post type archives
		if ( ! vgsr_entity_is_the_excerpt() && vgsr_is_entity() && is_post_type_archive() ) {
			$content = wp_trim_words( $content );
		}

		return $content;
	}
}

// Load it up
new VGSR_Entity_Default();

endif; // class_exists
