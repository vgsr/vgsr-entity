<?php

/**
 * VGSR Entity Extension Functions
 *
 * @package VGSR Entity
 * @subpackage Extensions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Loads the WordPress SEO component
 * 
 * @since 2.0.0
 *
 * @return When WordPress SEO is not active
 */
function vgsr_entity_setup_wpseo() {

	// Bail if no WordPress SEO
	if ( ! defined( 'WPSEO_VERSION' ) )
		return;

	// Include the WordPress SEO component
	require( vgsr_entity()->extend_dir . 'wordpress-seo/wordpress-seo.php' );

	// Instantiate WordPress SEO for VGSR Entity
	vgsr_entity()->extend->wpseo = new VGSR_Entity_WPSEO;
}
