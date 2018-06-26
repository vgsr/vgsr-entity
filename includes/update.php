<?php

/**
 * Econozel Updater
 *
 * @package Econozel
 * @subpackage Updater
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * If there is no raw DB version, this is the first installation
 *
 * @since 2.0.0
 *
 * @return bool True if update, False if not
 */
function vgsr_entity_is_install() {
	return ! vgsr_entity_get_db_version_raw();
}

/**
 * Compare the plugin version to the DB version to determine if updating
 *
 * @since 2.0.0
 *
 * @return bool True if update, False if not
 */
function vgsr_entity_is_update() {
	$raw    = (int) vgsr_entity_get_db_version_raw();
	$cur    = (int) vgsr_entity_get_db_version();
	$retval = (bool) ( $raw < $cur );
	return $retval;
}

/**
 * Determine if the plugin is being activated
 *
 * Note that this function currently is not used in the plugin's core and is here
 * for third party plugins to use to check for plugin activation.
 *
 * @since 2.0.0
 *
 * @return bool True if activating the plugin, false if not
 */
function vgsr_entity_is_activation( $basename = '' ) {
	global $pagenow;

	$plugin = vgsr_entity();
	$action = false;

	// Bail if not in admin/plugins
	if ( ! ( is_admin() && ( 'plugins.php' === $pagenow ) ) ) {
		return false;
	}

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' !== $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' !== $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not activating
	if ( empty( $action ) || ! in_array( $action, array( 'activate', 'activate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being activated
	if ( $action === 'activate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && ! empty( $plugin->basename ) ) {
		$basename = $plugin->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is the plugin being activated?
	return in_array( $basename, $plugins );
}

/**
 * Determine if the plugin is being deactivated
 *
 * @since 2.0.0
 * 
 * @return bool True if deactivating the plugin, false if not
 */
function vgsr_entity_is_deactivation( $basename = '' ) {
	global $pagenow;

	$plugin = vgsr_entity();
	$action = false;

	// Bail if not in admin/plugins
	if ( ! ( is_admin() && ( 'plugins.php' === $pagenow ) ) ) {
		return false;
	}

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' !== $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' !== $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not deactivating
	if ( empty( $action ) || ! in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being deactivated
	if ( $action === 'deactivate' ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty
	if ( empty( $basename ) && ! empty( $plugin->basename ) ) {
		$basename = $plugin->basename;
	}

	// Bail if no basename
	if ( empty( $basename ) ) {
		return false;
	}

	// Is the plugin being deactivated?
	return in_array( $basename, $plugins );
}

/**
 * Update the DB to the latest version
 *
 * @since 2.0.0
 */
function vgsr_entity_version_bump() {
	update_site_option( 'vgsr_entity_db_version', vgsr_entity_get_db_version() );
}

/**
 * Setup the plugin updater
 *
 * @since 2.0.0
 */
function vgsr_entity_setup_updater() {

	// Bail if no update needed
	if ( ! vgsr_entity_is_update() )
		return;

	// Call the automated updater
	vgsr_entity_version_updater();
}

/**
 * Plugin's version updater looks at what the current database version is, and
 * runs whatever other code is needed.
 *
 * This is most-often used when the data schema changes, but should also be used
 * to correct issues with plugin meta-data silently on software update.
 *
 * @since 2.0.0
 *
 * @todo Log update event
 */
function vgsr_entity_version_updater() {

	// Get the raw database version
	$raw_db_version = (int) vgsr_entity_get_db_version_raw();

	/** 2.0 Branch ********************************************************/

	// 2.0.0
	if ( $raw_db_version < 20000 ) {

		// Run updates
		vgsr_entity_update_20000();
	}

	/** All done! *********************************************************/

	// Flush rewrite rules
	vgsr_entity_delete_rewrite_rules();

	// Bump the version
	vgsr_entity_version_bump();
}

/**
 * Update routine for version 2.0.0
 *
 * @since 2.0.0
 *
 * @global $wpdb WPDB
 */
function vgsr_entity_update_20000() {
	global $wpdb;

	// Get VGSR Entity
	$entity = vgsr_entity();

	// Bestuur: Update old-style menu-order (+ 1950)
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} p SET p.menu_order = ( p.menu_order + %d ) WHERE p.post_type = %s AND p.menu_order < %d", $entity->base_year, 'bestuur', $entity->base_year ) );

	// Kast: Rename 'since' meta key
	$wpdb->update(
		$wpdb->postmeta,
		array( 'meta_key' => 'since' ),
		array( 'meta_key' => 'vgsr_entity_kast_since' ),
		array( '%s' ),
		array( '%s' )
	);

	// Kast: Change 'since' date format from d/m/Y to Y-m-d
	if ( $query = new WP_Query( array(
		'post_type'      => 'kast',
		'fields'         => 'ids',
		'posts_per_page' => -1,
	) ) ) {
		foreach ( $query->posts as $post_id ) {
			$value = get_post_meta( $post_id, 'since', true );

			if ( $value ) {
				$date  = DateTime::createFromFormat( 'd/m/Y', $value );
				if ( $date ) {
					$value = $date->format( 'Y-m-d' );
					update_post_meta( $post_id, 'since', $value );
				}
			}
		}
	}
}
