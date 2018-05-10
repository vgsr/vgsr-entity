<?php

/**
 * VGSR Entity Kast Administration Functions
 *
 * @package VGSR Entity
 * @subpackage Kast
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_Kast_Admin' ) ) :
/**
 * The VGSR Kast Administration class
 *
 * @since 2.0.0
 */
class VGSR_Kast_Admin extends VGSR_Entity_Type_Admin {

	/**
	 * Define default actions and filters
	 *
	 * @since 2.0.0
	 */
	protected function setup_actions() {

		// Settings
		add_filter( 'vgsr_kast_settings_load', array( $this, 'downsize_thumbs' ) );

		parent::setup_actions();
	}

	/** Public methods **************************************************/

	/**
	 * Resize Kast thumbs of all kasten first attachments
	 *
	 * Will only be run if the _kast-downsize-thumbs option is set.
	 *
	 * @since 1.0.0
	 */
	public function downsize_thumbs() {

		// Only do this if we're asked to
		if ( ! get_option( '_kast-downsize-thumbs' ) )
			return;

		// Get all kasten
		$kasten = get_posts( array(
			'post_type'   => $this->post_type,
			'numberposts' => -1
		) );

		// Get defined sizes
		$thumbsize = $this->thumbsize;
		$mini_size = $this->mini_size;

		// Loop over all kasten
		foreach ( $kasten as $kast ) :

			// Get first attachment - assuming that's the one we want to convert
			$logo = get_children( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_parent' => $kast->ID ) );
			$logo = is_array( $logo ) ? reset( $logo ) : false;

			// Do not continue without any attachment
			if ( ! $logo )
				continue;

			// Juggling with {$logo} so storing ID separately
			$logo_id = $logo->ID;
			$logo    = wp_get_attachment_image_src( $logo_id, $thumbsize );

			if ( $logo[1] == $mini_size && $logo[2] == $mini_size )
				continue;

			//
			// No perfect match found so continue to edit images
			//

			// Create absolute file path
			$file_path = ABSPATH . substr( dirname( $logo[0] ), ( strpos( $logo[0], parse_url( site_url(), PHP_URL_PATH ) ) + strlen( parse_url( site_url(), PHP_URL_PATH ) ) + 1 ) ) . '/'. basename( $logo[0] );

			// Do the resizing
			$logo = image_resize( $file_path, $mini_size, $mini_size, true );

			// Setup image size meta
			$args = array(
				'file'   => basename( $logo ),
				'width'  => $mini_size,
				'height' => $mini_size
			);

			// Store attachment metadata > we're havin a mini-thumb!
			$meta = wp_get_attachment_metadata( $logo_id );
			$meta['sizes'][ $thumbsize ] = $args;
			wp_update_attachment_metadata( $logo_id, $meta );

		endforeach;

		// Downsizing done, set option off
		update_option( '_kast-downsize-thumbs', 0 );
	}
}

endif; // class_exists
