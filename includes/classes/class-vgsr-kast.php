<?php

/**
 * VGSR Kast Class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Kast' ) ) :
/**
 * VGSR Kast Entity Class
 *
 * @since 1.0.0
 */
class VGSR_Kast extends VGSR_Entity_Base {

	/**
	 * Construct Kast Entity
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Default error strings
		$error_wrong_format = esc_html__( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' );

		// Construct entity
		parent::__construct( 'kast', array(
			'menu_icon' => 'dashicons-admin-home',
			'labels'    => array(
				'name'               => __( 'Kasten',                   'vgsr-entity' ),
				'singular_name'      => __( 'Kast',                     'vgsr-entity' ),
				'add_new'            => __( 'New Kast',                 'vgsr-entity' ),
				'add_new_item'       => __( 'Add new Kast',             'vgsr-entity' ),
				'edit_item'          => __( 'Edit Kast',                'vgsr-entity' ),
				'new_item'           => __( 'New Kast',                 'vgsr-entity' ),
				'all_items'          => __( 'All Kasten',               'vgsr-entity' ),
				'view_item'          => __( 'View Kast',                'vgsr-entity' ),
				'search_items'       => __( 'Search Kasten',            'vgsr-entity' ),
				'not_found'          => __( 'No Kasten found',          'vgsr-entity' ),
				'not_found_in_trash' => __( 'No Kasten found in trash', 'vgsr-entity' ),
				'menu_name'          => __( 'Kasten',                   'vgsr-entity' ),
				'settings_title'     => __( 'Kasten Settings',          'vgsr-entity' ),
			),

			// Thumbnail
			'thumbsize' => 'mini-thumb',
			'mini_size' => 100,

		// Meta
		), array(

			// Since
			'since' => array(
				'label'   => esc_html__( 'Since', 'vgsr-entity' ),
				'type'    => 'date',
				'name'    => 'vgsr_entity_kast_since',
				'display' => true,
			),

			// Ceased
			'ceased' => array(
				'label'   => esc_html__( 'Ceased', 'vgsr-entity' ),
				'type'    => 'year',
				'name'    => 'vgsr_entity_kast_ceased',
				'display' => true,
			),

		// Errors
		), array(
			1 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Since',  'vgsr-entity' ) . '</strong>' ),
			2 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Ceased', 'vgsr-entity' ) . '</strong>' ),
		) );
	}

	/**
	 * Define default Kast globals
	 *
	 * @since 1.0.0
	 *
	 * @uses add_image_size()
	 */
	public function setup_globals() {
		add_image_size( $this->args['thumbsize'], $this->args['mini_size'], $this->args['mini_size'], true );
	}

	/**
	 * Setup default Kast actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		// Actions
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Filters
		add_filter( 'vgsr_kast_settings_load', array( $this, 'downsize_thumbs' ) );
	}

	/**
	 * Add additional Kast settings fields
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {

		// Kast recreate thumbnail option
		add_settings_field( '_kast-downsize-thumbs', __( 'Recreate Thumbnails', 'vgsr-entity' ), array( $this, 'settings_downsize_thumbs_field' ), $this->args['settings']['page'], $this->args['settings']['section'] );
		register_setting( $this->args['settings']['page'], '_kast-downsize-thumbs', 'intval' );
	}

	/**
	 * Output the Kast downsize thumbs settings field
	 *
	 * @since 1.0.0
	 */
	public function settings_downsize_thumbs_field() {
	?>

		<input type="checkbox" name="_kast-downsize-thumbs" id="_kast-downsize-thumbs" <?php checked( get_option( '_kast-downsize-thumbs' ) ); ?> value="1"/>
		<label for="_kast-downsize_thumbs"><span class="description"><?php echo sprintf( __( 'This is a one time resizing of thumbs for %s. NOTE: This option only <strong>adds</strong> new image sizes, it doesn\'t remove old ones.', 'vgsr-entity' ), $this->args['labels']['name'] ); ?></span></label>

	<?php
	}

	/**
	 * Resize Kast thumbs of all kasten first attachments
	 *
	 * Will only be run if the _kast-downsize-thumbs option is set.
	 *
	 * @since 1.0.0
	 *
	 * @uses get_posts()
	 * @uses get_children()
	 * @uses wp_get_attachment_image_src()
	 * @uses image_resize()
	 * @uses wp_get_attachment_metadata()
	 * @uses wp_udpate_attachment_metadata()
	 */
	public function downsize_thumbs() {

		// Only do this if we're asked to
		if ( ! get_option( '_kast-downsize-thumbs' ) )
			return;

		// Get all kasten
		$kasten = get_posts( array(
			'post_type'   => $this->type,
			'numberposts' => -1
		) );

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
			$logo    = wp_get_attachment_image_src( $logo_id, $this->args['thumbsize'] );

			if ( $logo[1] == $this->args['mini_size'] && $logo[2] == $this->args['mini_size'] )
				continue;

			//
			// No perfect match found so continue to edit images
			//

			// Create absolute file path
			$file_path = ABSPATH . substr( dirname( $logo[0] ), ( strpos( $logo[0], parse_url( site_url(), PHP_URL_PATH ) ) + strlen( parse_url( site_url(), PHP_URL_PATH ) ) + 1 ) ) . '/'. basename( $logo[0] );

			// Do the resizing
			$logo = image_resize( $file_path, $this->args['mini_size'], $this->args['mini_size'], true );

			// Setup image size meta
			$args = array(
				'file'   => basename( $logo ),
				'width'  => $this->args['mini_size'],
				'height' => $this->args['mini_size']
			);

			// Store attachment metadata > we're havin a mini-thumb!
			$meta = wp_get_attachment_metadata( $logo_id );
			$meta['sizes'][ $this->args['thumbsize'] ] = $args;
			wp_update_attachment_metadata( $logo_id, $meta );

		endforeach;

		// Downsizing done, set option off
		update_option( '_kast-downsize-thumbs', 0 );
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
		$value   = null;
		$display = ( 'display' === $context );

		switch ( $key ) {
			case 'since' :
			case 'ceased' :
				$value = get_post_meta( $post->ID, $key, true );
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

		// Basic input sanitization
		$value = sanitize_text_field( $value );

		switch ( $key ) {
			case 'since' :
			case 'ceased' :
			default :
				$value = parent::save( $key, $value, $post );
		}

		return $value;
	}
}

endif; // class_exists
