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
		), array(

			// Since
			'since' => array(
				'label' => __( 'Since', 'vgsr-entity' ),
				'type'  => 'year',
				'name'  => 'menu_order'
			),

			// Ceased
			'ceased' => array(
				'label' => __( 'Ceased', 'vgsr-entity' ),
				'type'  => 'year',
				'name'  => 'vgsr_entity_dispuut_ceased'
			),
		) );
	}

	/**
	 * Setup default Dispuut actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		// Save post meta
		add_action( 'save_post', array( $this, 'dispuut_metabox_save' ), 10, 2 );
	}

	/**
	 * Save dispuut since and ceased meta field
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID
	 * @param object $post Post data
	 */
	public function dispuut_metabox_save( $post_id, $post ) {

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Check post type
		if ( $post->post_type !== $this->type )
			return;

		// Check caps
		$pto = get_post_type_object( $this->type );
		if ( ! current_user_can( $pto->cap->edit_posts ) || ! current_user_can( $pto->cap->edit_post, $post_id ) )
			return;

		// Check nonce
		if ( ! isset( $_POST['vgsr_entity_dispuut_meta_nonce'] ) || ! wp_verify_nonce( $_POST['vgsr_entity_dispuut_meta_nonce'], vgsr_entity()->file ) )
			return;

		//
		// Authenticated
		//

		// Since & Ceased
		if ( isset( $_POST['vgsr_entity_dispuut_since'] ) || isset( $_POST['vgsr_entity_dispuut_ceased'] ) ) {

			// Walk since and ceased meta
			foreach ( array_filter( array(
				'since'  => sanitize_text_field( $_POST['vgsr_entity_dispuut_since']  ),
				'ceased' => sanitize_text_field( $_POST['vgsr_entity_dispuut_ceased'] )
			) ) as $meta_key => $value ) :

				// Ceased field may be empty, so delete
				if ( 'ceased' == $meta_key && empty( $value ) ) {
					delete_post_meta( $post_id, "vgsr_entity_dispuut_{$meta_key}" );
					continue;
				}

				// Does the inserted input match our requirements? - Checks for 1900 - 2099
				if ( ! preg_match( '/^(19\d{2}|20\d{2})$/', $value, $matches ) ) {

					// Alert the user
					add_filter( 'redirect_post_location', array( $this, "metabox_{$meta_key}" .'_save_redirect' ) );
					$value = false;

				// Update post meta
				} else {
					update_post_meta( $post_id, "vgsr_entity_dispuut_{$meta_key}", $value );
				}

			endforeach;
		}
	}

	/**
	 * Add query arg to the redirect location after save_post()
	 *
	 * @since 1.0.0
	 *
	 * @param string $location The redrirect location
	 * @return string $location
	 */
	public function metabox_since_save_redirect( $location ) {
		return add_query_arg( 'dispuut-error', '1', $location );
	}

	/**
	 * Add query arg to the redirect location after save_post()
	 *
	 * @since 1.0.0
	 *
	 * @param string $location The redrirect location
	 * @return string $location
	 */
	public function metabox_ceased_save_redirect( $location ) {
		return add_query_arg( 'dispuut-error', '2', $location );
	}

	/**
	 * Setup Dispuut admin error messages
	 *
	 * @since 1.0.0
	 *
	 * @param array $messages
	 * @return array $messages
	 */
	public function admin_messages( $messages ) {

		// Default strings
		$wrong_format = __( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' );

		$messages[1] = sprintf( $wrong_format, '<strong>' . __( 'Since',  'vgsr-entity' ) . '</strong>' );
		$messages[2] = sprintf( $wrong_format, '<strong>' . __( 'Ceased', 'vgsr-entity' ) . '</strong>' );

		return $messages;
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
	public function get( $key, $post = 0, $context = 'display' ) {

		// Define local variables
		$post    = get_post( $post );
		$value   = null;
		$display = ( 'display' === $context );

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
	 * @param string $value Meta value
	 * @param string $key Meta key
	 * @return mixed Meta value
	 */
	public function save( $value, $key ) {

		switch ( $key ) {
			case 'since' :
				// Will be saved through WP's default handling of 'menu_order'
				break;
			case 'ceased' :
				$value = (int) $value;
				break;
		}

		return $value;
	}
}

endif; // class_exists
