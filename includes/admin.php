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
		$this->setup_actions();
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 2.0.0
	 */
	private function setup_actions() {
		add_action( 'admin_menu',          array( $this, 'admin_menu'          )        );
		add_action( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
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

			$position = vgsr_entity()->menu_position - 1;
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

	/**
	 * Modify the data of a post to be saved
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Post data
	 * @param array $postarr Initial post data
	 * @return array Post data
	 */
	public function wp_insert_post_data( $data, $postarr ) {

		// Get archived status
		$archived = vgsr_entity_get_archived_status_id();

		/**
		 * Article was archived and should remain so. This is however interpreted by
		 * WP in a different way. See {@see _wp_translate_postdata()}, where the post
		 * status is reset to 'publish' when the 'Publish' button was used, even when
		 * a different/custom post status was provided.
		 */
		if ( isset( $_REQUEST['publish'] ) && $archived === $postarr['original_post_status'] && $archived === $_REQUEST['post_status'] ) {
			$data['post_status'] = $archived;
		}

		return $data;
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
