<?php

/**
 * VGSR Entity Kast Settings Functions
 *
 * @package VGSR Entity
 * @subpackage Kast
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add additional Kast settings fields
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_kast_settings_fields'
 * @return array Settings fields
 */
function vgsr_entity_kast_settings_fields() {
	return (array) apply_filters( 'vgsr_entity_kast_settings_fields', array(

		// Thumbnails
		'downsize-thumbs' => array(
			'title'             => esc_html__( 'Recreate Thumbnails', 'vgsr-entity' ),
			'callback'          => 'vgsr_entity_kast_settings_downsize_thumbs_field',
			'sanitize_callback' => 'intval',
			'entity'            => 'kast',
			'section'           => 'main',
			'args'              => array(),		
		)
	) );
}

/**
 * Output the Kast downsize thumbs settings field
 *
 * @since 1.0.0
 */
function vgsr_entity_kast_settings_downsize_thumbs_field() { ?>

	<input type="checkbox" name="_kast-downsize-thumbs" id="_kast-downsize-thumbs" <?php checked( get_option( '_kast-downsize-thumbs' ) ); ?> value="1" />

	<p class="description"><?php esc_html_e( 'This is a one time resizing of thumbs for Kasten. NOTE: This option only adds new image sizes, it does not remove old ones.', 'vgsr-entity' ); ?></p>

	<?php
}
