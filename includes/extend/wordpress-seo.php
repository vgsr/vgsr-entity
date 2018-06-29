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

		// Breadcrumbs
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'breadcrumb_links' ) );
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
		if ( vgsr_is_bestuur() ) {

			// Apply title parts filter
			$parts  = array( 'title' => get_the_title() );
			$_parts = vgsr_entity_bestuur_document_title_parts( $parts );

			$title = str_replace( $parts['title'], $_parts['title'], $title );
		}

		return $title;
	}

	/**
	 * Modify the collection of page crumb links
	 *
	 * @since 2.0.0
	 *
	 * @param array $crumbs Breadcrumb links
	 * @return array Breadcrumb links
	 */
	public function breadcrumb_links( $crumbs ) {

		// Entity page. Fully overwrite crumb paths
		if ( vgsr_is_entity() ) {
			$post_type = get_post_type();
			$type      = vgsr_entity_get_type( $post_type );
			$parent    = vgsr_entity_get_entity_parent( $type, true );

			// Collect first and last
			$_crumbs = array( $crumbs[0], $crumbs[ count( $crumbs ) - 1] );

			// With entity parent
			if ( $parent ) {
				do {
					// Prepend parent
					array_splice( $_crumbs, 1, 0, array(
						array(
							'text'       => get_the_title( $parent ),
							'url'        => get_permalink( $parent ),
							'allow_html' => false
						)
					) );

					$continue = $parent->post_parent;
					$parent = get_post( $parent->post_parent );
				} while ( $continue );

				// Correct last/current item for post type archives
				if ( is_post_type_archive( $post_type ) ) {
					array_pop( $_crumbs );
				}

				$crumbs = array_values( $_crumbs );
			}
		}

		return $crumbs;
	}
}

/**
 * Setup the extension logic for WP SEO
 *
 * @since 2.0.0
 *
 * @uses VGSR_Entity_WPSEO
 */
function vgsr_entity_wpseo() {
	vgsr_entity()->extend->wpseo = new VGSR_Entity_WPSEO;
}

endif; // class_exists
