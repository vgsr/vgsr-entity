<?php

/**
 * VGSR Entity Extension for WP SEO
 *
 * @package VGSR Entity
 * @subpackage WP SEO
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_WPSEO' ) ) :
/**
 * The VGSR Entity WP SEO class
 *
 * @since 2.0.0
 */
class VGSR_Entity_WPSEO {

	/**
	 * Setup this class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 2.0.0
	 */
	private function setup_actions() {
		add_filter( 'wpseo_title', array( $this, 'wpseo_title' ) );
	}

	/** Public methods **************************************************/

	/**
	 * Modify the page title for WP SEO
	 *
	 * @since 2.0.0
	 *
	 * @param  string $title Page title
	 * @return string Page title
	 */
	public function wpseo_title( $title ) {

		// Bestuur
		if ( is_bestuur() ) {

			// Apply title parts filter
			$parts  = array( 'title' => get_the_title() );
			$_parts = vgsr_entity_bestuur_document_title_parts( $parts );

			$title = str_replace( $parts['title'], $_parts['title'], $title );
		}

		return $title;
	}
}

/**
 * Setup the extension logic for BuddyPress
 *
 * @since 2.0.0
 *
 * @uses VGSR_Entity_WPSEO
 */
function vgsr_entity_wpseo() {
	vgsr_entity()->extend->wpseo = new VGSR_Entity_WPSEO;
}

endif; // class_exists
