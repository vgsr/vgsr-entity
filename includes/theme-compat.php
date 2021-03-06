<?php

/**
 * VGSR Entity Theme Compatability Functions
 *
 * @package VGSR Entity
 * @subpackage Theme
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the path to the plugin's theme compat directory
 *
 * @since 2.0.0
 *
 * @return string Path to theme compat directory
 */
function vgsr_entity_get_theme_compat_dir() {
	return trailingslashit( vgsr_entity()->themes_dir . 'default' );
}

/**
 * Return the stack of template path locations
 *
 * @since 2.0.0
 *
 * @return array Template locations
 */
function vgsr_entity_get_template_stack() {
	return apply_filters( 'vgsr_entity_get_template_stack', array(
		get_stylesheet_directory(),        // Child theme
		get_template_directory(),          // Parent theme
		vgsr_entity_get_theme_compat_dir() // Plugin theme-compat
	) );
}

/**
 * Return the template folder locations to look for files
 *
 * @since 2.0.0
 *
 * @return array Template folders
 */
function vgsr_entity_get_template_locations() {
	return apply_filters( 'vgsr_entity_get_template_locations', array(
		'vgsr-entity', // Plugin folder
		''             // Root folder
	) );
}

/**
 * Get a template part in an output buffer and return it
 *
 * @since 2.0.0
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Template name.
 * @param bool $echo Optional. Whether to echo the template part. Defaults to false.
 * @return string Template part content
 */
function vgsr_entity_buffer_template_part( $slug, $name = '', $echo = false ) {

	// Start buffer
	ob_start();

	// Output template part
	vgsr_entity_get_template_part( $slug, $name );

	// Close buffer and get its contents
	$output = ob_get_clean();

	// Echo or return the output buffer contents
	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Output a template part
 *
 * @since 2.0.0
 *
 * @uses do_action() Calls 'vgsr_entity_get_template_part_{$slug}'
 * @uses apply_filters() Calls 'vgsr_entity_get_template_part'
 *
 * @param string $slug Template slug.
 * @param string $name Optional. Template name. Defaults to the current entity type.
 */
function vgsr_entity_get_template_part( $slug, $name = '' ) {

	// Default to current entity type
	if ( empty( $name ) ) {
		$name = vgsr_entity_get_type();
	}

	// Execute code for this part
	do_action( "vgsr_entity_get_template_part_{$slug}", $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( $name )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '-entity.php';
	$templates[] = $slug . '.php';

	// Allow template part to be filtered
	$templates = apply_filters( 'vgsr_entity_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return vgsr_entity_locate_template( $templates, true, false );
}

/**
 * Retrieve the path of the highest priority template file that exists.
 *
 * @since 2.0.0
 *
 * @param array $template_names Template hierarchy
 * @param bool $load Optional. Whether to load the file when it is found. Default to false.
 * @param bool $require_once Optional. Whether to require_once or require. Default to true.
 * @return string Path of the template file when located.
 */
function vgsr_entity_locate_template( $template_names, $load = false, $require_once = true ) {

	// No file found yet
	$located = '';

	// Get template stack and locations
	$stack     = vgsr_entity_get_template_stack();
	$locations = vgsr_entity_get_template_locations();

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Skip empty template
		if ( empty( $template_name ) )
			continue;

		// Loop through the template stack
		foreach ( $stack as $template_dir ) {

			// Loop through the template locations
			foreach ( $locations as $location ) {

				// Construct template location
				$template_location = trailingslashit( $template_dir ) . $location;

				// Skip empty locations
				if ( empty( $template_location ) )
					continue;

				// Locate template file
				if ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
					$located = trailingslashit( $template_location ) . $template_name;
					break 3;
				}
			}
		}
	}

	// Maybe load the template when it was located
	if ( $load && ! empty( $located ) ) {
		load_template( $located, $require_once );
	}

	return $located;
}

/**
 * Enqueue a script from the highest priority location in the template stack.
 *
 * Registers the style if file provided (does NOT overwrite) and enqueues.
 *
 * @since 2.0.0
 *
 * @param string      $handle Name of the stylesheet.
 * @param string|bool $file   Relative path to stylesheet. Example: '/css/mystyle.css'.
 * @param array       $deps   An array of registered style handles this stylesheet depends on. Default empty array.
 * @param string|bool $ver    String specifying the stylesheet version number, if it has one. This parameter is used
 *                            to ensure that the correct version is sent to the client regardless of caching, and so
 *                            should be included if a version number is available and makes sense for the stylesheet.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 *                            Default 'all'. Accepts 'all', 'aural', 'braille', 'handheld', 'projection', 'print',
 *                            'screen', 'tty', or 'tv'.
 *
 * @return string The style filename if one is located.
 */
function vgsr_entity_enqueue_style( $handle = '', $file = '', $dependencies = array(), $version, $media = 'all' ) {

	// No file found yet
	$located = false;

	// Trim off any slashes from the template name
	$file = ltrim( $file, '/' );

	// Make sure there is always a version
	if ( empty( $version ) ) {
		$version = vgsr_entity_get_version();
	}

	// Loop through template stack
	foreach ( (array) vgsr_entity_get_template_stack() as $template_location ) {

		// Continue if $template_location is empty
		if ( empty( $template_location ) ) {
			continue;
		}

		// Check child theme first
		if ( file_exists( trailingslashit( $template_location ) . $file ) ) {
			$located = trailingslashit( $template_location ) . $file;
			break;
		}
	}

	// Enqueue if located
	if ( !empty( $located ) ) {

		$content_dir = constant( 'WP_CONTENT_DIR' );

		// IIS (Windows) here
		// Replace back slashes with forward slash
		if ( strpos( $located, '\\' ) !== false ) {
			$located     = str_replace( '\\', '/', $located     );
			$content_dir = str_replace( '\\', '/', $content_dir );
		}

		// Make path to file relative to site URL
		$located = str_replace( $content_dir, content_url(), $located );

		// Enqueue the style
		wp_enqueue_style( $handle, $located, $dependencies, $version, $media );
	}

	return $located;
}

/**
 * Enqueue a script from the highest priority location in the template stack.
 *
 * Registers the style if file provided (does NOT overwrite) and enqueues.
 *
 * @since 2.0.0
 *
 * @param string      $handle    Name of the script.
 * @param string|bool $file      Relative path to the script. Example: '/js/myscript.js'.
 * @param array       $deps      An array of registered handles this script depends on. Default empty array.
 * @param string|bool $ver       Optional. String specifying the script version number, if it has one. This parameter
 *                               is used to ensure that the correct version is sent to the client regardless of caching,
 *                               and so should be included if a version number is available and makes sense for the script.
 * @param bool        $in_footer Optional. Whether to enqueue the script before </head> or before </body>.
 *                               Default 'false'. Accepts 'false' or 'true'.
 *
 * @return string The script filename if one is located.
 */
function vgsr_entity_enqueue_script( $handle = '', $file = '', $dependencies = array(), $version = false, $in_footer = 'all' ) {

	// No file found yet
	$located = false;

	// Trim off any slashes from the template name
	$file = ltrim( $file, '/' );

	// Make sure there is always a version
	if ( empty( $version ) ) {
		$version = vgsr_entity_get_version();
	}

	// Loop through template stack
	foreach ( (array) vgsr_entity_get_template_stack() as $template_location ) {

		// Continue if $template_location is empty
		if ( empty( $template_location ) ) {
			continue;
		}

		// Check child theme first
		if ( file_exists( trailingslashit( $template_location ) . $file ) ) {
			$located = trailingslashit( $template_location ) . $file;
			break;
		}
	}

	// Enqueue if located
	if ( !empty( $located ) ) {

		$content_dir = constant( 'WP_CONTENT_DIR' );

		// IIS (Windows) here
		// Replace back slashes with forward slash
		if ( strpos( $located, '\\' ) !== false ) {
			$located     = str_replace( '\\', '/', $located     );
			$content_dir = str_replace( '\\', '/', $content_dir );
		}

		// Make path to file relative to site URL
		$located = str_replace( $content_dir, content_url(), $located );

		// Enqueue the style
		wp_enqueue_script( $handle, $located, $dependencies, $version, $in_footer );
	}

	return $located;
}

/**
 * Intercept the template loader to load the entity template
 *
 * @since 1.0.0
 *
 * @param string $template The current template match
 * @return string $template
 */
function vgsr_entity_template_include( $template ) {

	// Single entity requested
	if ( vgsr_is_entity() && is_singular() ) {

		// Get the current entity type
		$type = vgsr_entity_get_type();

		/**
		 * Define our own tempate candidates
		 *
		 * The template(s) should be defined in the current child 
		 * or parent theme.
		 */
		$templates = array(

			// Type specific template
			"single-{$type}.php",
			"{$type}.php",

			// Default to page, then single template
			'page.php',
			'single.php',
		);

		// Generic entity template
		if ( ! post_type_exists( 'entity' ) ) {
			array_splice( $templates, 2, 0, 'single-entity.php' );
		}

		// Query for a usable template
		$template = vgsr_entity_locate_template( $templates );
	}

	return $template;
}

/**
 * Load a custom plugin functions file, similar to each theme's functions.php file.
 *
 * @since 2.0.0
 *
 * @global string $pagenow
 */
function vgsr_entity_load_theme_functions() {
	global $pagenow;

	// When plugin is being deactivated, do not load any more files
	if ( vgsr_entity_is_deactivation() )
		return;

	// Load file when not installing
	if ( ! defined( 'WP_INSTALLING' ) || ( ! empty( $pagenow ) && ( 'wp-activate.php' !== $pagenow ) ) ) {
		vgsr_entity_locate_template( 'vgsr-entity-functions.php', true );
	}
}
