<?php

/**
 * VGSR Dispuut Class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Dispuut' ) ) :
/**
 * VGSR Dispuut Entity Class
 *
 * @since 1.0.0
 */
class VGSR_Dispuut extends VGSR_Entity_Base {

	/**
	 * Construct Dispuut Entity
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Default error strings
		$error_wrong_format = esc_html__( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' );

		// Construct entity
		parent::__construct( 'dispuut', array(
			'menu_icon' => 'dashicons-format-status',
			'labels'    => array(
				'name'               => __( 'Disputen',                   'vgsr-entity' ),
				'singular_name'      => __( 'Dispuut',                    'vgsr-entity' ),
				'add_new'            => __( 'New Dispuut',                'vgsr-entity' ),
				'add_new_item'       => __( 'Add new Dispuut',            'vgsr-entity' ),
				'edit_item'          => __( 'Edit Dispuut',               'vgsr-entity' ),
				'new_item'           => __( 'New Dispuut',                'vgsr-entity' ),
				'all_items'          => __( 'All Disputen',               'vgsr-entity' ),
				'view_item'          => __( 'View Dispuut',               'vgsr-entity' ),
				'search_items'       => __( 'Search Disputen',            'vgsr-entity' ),
				'not_found'          => __( 'No Disputen found',          'vgsr-entity' ),
				'not_found_in_trash' => __( 'No Disputen found in trash', 'vgsr-entity' ),
				'menu_name'          => __( 'Disputen',                   'vgsr-entity' ),
				'settings_title'     => __( 'Disputen Settings',          'vgsr-entity' ),
			)

		// Meta
		), array(

			// Since
			'since' => array(
				'label' => esc_html__( 'Since', 'vgsr-entity' ),
				'type'  => 'year',
				'name'  => 'menu_order'
			),

			// Ceased
			'ceased' => array(
				'label' => esc_html__( 'Ceased', 'vgsr-entity' ),
				'type'  => 'year',
				'name'  => 'vgsr_entity_dispuut_ceased'
			),

		// Errors
		), array(
			1 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Since',  'vgsr-entity' ) . '</strong>' ),
			2 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Ceased', 'vgsr-entity' ) . '</strong>' ),
		) );
	}

	/** Meta ***********************************************************/

	/**
	 * Return the requested entity meta value
	 *
	 * @since 1.1.0
	 *
	 * @param string $key
	 * @param int|WP_Post $post
	 * @param string $context Optional. Context, defaults to 'display'.
	 * @return mixed Entity meta value
	 */
	protected function _get( $key, $post, $context ) {

		// Define local variables
		$value = null;

		switch ( $key ) {
			case 'since' :
				$value = $post->menu_order;
				break;
			case 'ceased' :
				$value = get_post_meta( $post->ID, $this->meta[ $key ]['name'], true );
				break;
		}

		return $value;
	}

	/**
	 * Sanitize the given entity meta value
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Meta key
	 * @param string $value Meta value
	 * @param WP_Post $post Post object
	 * @return mixed Meta value
	 */
	public function save( $key, $value, $post ) {
		global $wpdb;

		// Basic input sanitization
		$value = sanitize_text_field( $value );

		switch ( $key ) {
			case 'since' :
				$value = intval( $value );

				// When saving a post, WP handles 'menu_order' by default
				if ( 'save_post' != current_filter() ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $value ), array( 'ID' => $post->ID ), array( '%d' ), array( '%d' ) );
				}

				break;
			case 'ceased' :
			default :
				$value = parent::save( $key, $value, $post );
		}

		return $value;
	}
}

endif; // class_exists
