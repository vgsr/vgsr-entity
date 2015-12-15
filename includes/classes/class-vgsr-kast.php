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
				'column_title' => esc_html__( 'Since',    'vgsr-entity' ),
				'label'        => esc_html__( 'Since %s', 'vgsr-entity' ),
				'type'         => 'date',
				'name'         => 'vgsr_entity_kast_since',
				'display'      => true,
			),

			// Ceased
			'ceased' => array(
				'column_title' => esc_html__( 'Ceased',    'vgsr-entity' ),
				'label'        => esc_html__( 'Ceased %s', 'vgsr-entity' ),
				'type'         => 'year',
				'name'         => 'vgsr_entity_kast_ceased',
				'display'      => true,
			),

		// Errors
		), array(
			2 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Since',  'vgsr-entity' ) . '</strong>' ),
			3 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Ceased', 'vgsr-entity' ) . '</strong>' ),
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

		// Admin
		add_action( 'vgsr_entity_settings_fields', array( $this, 'add_settings_fields' ) );

		// Thumbnails
		add_filter( 'vgsr_kast_settings_load', array( $this, 'downsize_thumbs' ) );

		// Post
		add_filter( 'the_title',         array( $this, 'filter_the_title' ), 10, 2 );
		add_filter( 'single_post_title', array( $this, 'filter_the_title' ), 10, 2 );
	}

	/** Settings *******************************************************/

	/**
	 * Add additional Kast settings fields
	 *
	 * @since 1.0.0
	 */
	public function add_settings_fields( $fields ) {

		// Kast recreate thumbnail option
		$fields['main_settings']['downsize-thumbs'] = array(
			'title'             => esc_html__( 'Recreate Thumbnails', 'vgsr-entity' ),
			'callback'          => array( $this, 'settings_downsize_thumbs_field' ),
			'sanitize_callback' => 'intval',
			'entity'            => $this->type,
			'args'              => array(),
		);

		return $fields;
	}

	/**
	 * Output the Kast downsize thumbs settings field
	 *
	 * @since 1.0.0
	 */
	public function settings_downsize_thumbs_field() { ?>

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

	/** Post ***********************************************************/

	/**
	 * Modify the post title for this entity
	 *
	 * @since 1.1.0
	 *
	 * @uses is_kast()
	 * @uses VGSR_Kast::get()
	 *
	 * @param string $title Post title
	 * @param int $post_id Post ID
	 * @return string Post title
	 */
	public function filter_the_title( $title, $post_id ) {

		// When this is our entity
		if ( ! is_admin() && is_kast( $post_id ) ) {
			$ceased = $this->get( 'ceased', $post_id );

			// Append the 'ceased' date with a Latin Cross
			if ( ! empty( $ceased ) ) {
				$title .= sprintf( ' (&#10013; %s)', $ceased );
			}
		}

		return $title;
	}

	/** Meta ***********************************************************/

	/**
	 * Return the requested entity meta value
	 *
	 * @since 1.1.0
	 *
	 * @param string $key
	 * @param int|WP_Post $post Optional. Defaults to current post.
	 * @param string $context Optional. Context, defaults to 'display'.
	 * @return mixed Entity meta value
	 */
	public function get( $key, $post = 0, $context = 'display' ) {

		// Define local variables
		$value   = null;
		$display = ( 'display' === $context );

		if ( $post = get_post( $post ) ) {
			switch ( $key ) {
				case 'since' :
				case 'ceased' :
					$value = parent::get( $key, $post, $context );
					break;
			}
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
