<?php

/**
 * VGSR Dispuut Class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'VGSR_Entity_Dispuut' ) ) :

/**
 * VGSR Dispuut Entity Class
 *
 * @since 0.1
 */
class VGSR_Entity_Dispuut extends VGSR_Entity {

	/**
	 * Construct Dispuut Entity
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( array( 
			'single'    => 'Dispuut', 
			'plural'    => 'Disputen',
			'menu_icon' => 'dashicons-format-status',
		) );
	}

	/**
	 * Setup default Dispuut actions and filters
	 *
	 * @since 0.1
	 */
	public function setup_actions() {

		// Save post meta
		add_action( 'save_post', array( $this, 'dispuut_metabox_save' ), 10, 2 );

		// Append entity children
		add_filter( 'the_content', array( $this, 'entity_parent_page_children' ) );
	}

	/**
	 * Add metaboxes to the Dispuut edit screen
	 * 
	 * @since 0.1
	 *
	 * @uses add_meta_box()
	 */
	public function add_metabox() {

		// Add Dispuut Data metabox
		add_meta_box(
			"vgsr-entity-{$this->type}",
			__( 'Dispuut Data', 'vgsr-entity' ),
			array( $this, 'metabox_display' ),
			$this->type,
			'side'
		);
	}

	/**
	 * Output dispuut meta box
	 * 
	 * @since 0.1
	 * 
	 * @uses get_post_meta()
	 * @uses wp_nonce_field()
	 * @uses do_action() Calls 'vgsr_{$this->type}_metabox' hook with the post object
	 * 
	 * @param object $post The current post
	 */
	public function metabox_display( $post ) {

		// Output nonce verification field
		wp_nonce_field( vgsr_entity()->file, 'vgsr_entity_dispuut_meta_nonce' ); 

		/** Since ******************************************************/

		// Get stored meta value
		$since = get_post_meta( $post->ID, 'vgsr_entity_dispuut_since', true );

		// If no value served set it empty
		if ( ! $since )
			$since = '';

		?>

		<p id="vgsr_entity_dispuut_since">

			<label>
				<strong><?php _e( 'Since', 'vgsr-entity' ); ?>: </strong>
				<input type="text" name="vgsr_entity_dispuut_since" value="<?php echo esc_attr( $since ); ?>" placeholder="yyyy" />
			</label>

		</p>

		<?php

		/** Ceased *****************************************************/

		// Get stored meta value
		$ceased = get_post_meta( $post->ID, 'vgsr_entity_dispuut_ceased', true );

		// If no value served set it empty
		if ( ! $ceased )
			$ceased = '';

		?>

		<p id="vgsr_entity_dispuut_ceased">

			<label>
				<strong><?php _e( 'Ceased', 'vgsr-entity' ); ?>: </strong>
				<input type="text" name="vgsr_entity_dispuut_ceased" value="<?php echo esc_attr( $ceased ); ?>" placeholder="yyyy" />
			</label>

		</p>
	
		<?php

		do_action( "vgsr_{$this->type}_metabox", $post );
	}

	/**
	 * Save dispuut since and ceased meta field
	 * 
	 * @since 0.1
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
	 * @since 0.1
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
	 * @since 0.1
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
	 * @since 0.1
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

	/**
	 * Returns the meta fields for post type dispuut
	 *
	 * @since 0.1
	 * 
	 * @param array $meta Meta fields
	 * @return array $meta
	 */
	public function entity_display_meta( $meta ) {
		global $post;

		// Setup value for since meta
		if ( $since = get_post_meta( $post->ID, 'vgsr_entity_dispuut_since', true ) ) {

			// Meta icon
			$meta['since'] = array(
				'icon'   => 'icon-calendar',
				'before' => __( 'Since', 'vgsr-entity' ) .': ',
				'value'  => $since
			);
		}

		// Setup value for ceased meta
		if ( $ceased = get_post_meta( $post->ID, 'vgsr_entity_dispuut_ceased', true ) ) {

			// Meta icon
			$meta['ceased'] = array(
				'icon'   => 'icon-cancel',
				'before' => __( 'Ceased', 'vgsr-entity' ) .': ',
				'value'  => $ceased
			);
		}

		return $meta;
	}
}

endif; // class_exists

/**
 * Setup VGSR Dispuut Entity
 *
 * @since 0.1
 *
 * @uses VGSR_Entity_Dispuut
 */
function vgsr_entity_dispuut() {
	vgsr_entity()->dispuut = new VGSR_Entity_Dispuut();
}

