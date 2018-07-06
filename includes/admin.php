<?php

/**
 * VGSR Entity Admin Functions
 *
 * @package VGSR Entity
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_Admin' ) ) :
/**
 * The VGSR Entity Admin class
 *
 * @since 2.0.0
 */
class VGSR_Entity_Admin {

	/**
	 * Setup this class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Define class globals
	 *
	 * @since 2.0.0
	 */
	private function setup_globals() {
		$this->menu_position = 35;
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 2.0.0
	 */
	private function setup_actions() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/** Public methods **************************************************/

	/**
	 * Filters the admin menu to add a separator
	 *
	 * @since 2.0.0
	 *
	 * @global array $menu
	 */
	public function admin_menu() {

		// When entities were registered
		if ( vgsr_entity_get_types() ) {
			/**
			 * Run through the admin menu to add a separator at given position
			 *
			 * The separator name can affect the order of the separators,
			 * therefor the separator{$index} naming is changed.
			 *
			 * @link http://wordpress.stackexchange.com/questions/2666/add-a-separator-to-the-admin-menu
			 */
			global $menu;

			$position = $this->menu_position - 1;
			$index    = 1;

			// Walk all registered menu items
			foreach ( $menu as $offset => $item ) {
				if ( 'separator' === substr( $item[2], 0, 9 ) ) {
					$index++;
				}

				// Insert separator
				if ( $offset >= $position ) {
					$menu[ $position ] = array( '', 'read', "separator-pos{$index}", '', 'wp-menu-separator' );
					break;
				}
			}

			ksort( $menu );
		}
	}
}

/**
 * Setup the extension logic for BuddyPress
 *
 * @since 2.0.0
 *
 * @uses VGSR_Entity_Admin
 */
function vgsr_entity_admin() {
	vgsr_entity()->admin = new VGSR_Entity_Admin;
}

endif; // class_exists
