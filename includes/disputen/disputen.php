<?php

/**
 * VGSR Dispuut Class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Load base class
if ( ! class_exists( 'VGSR_Entity_Type' ) ) {
	require( vgsr_entity()->includes_dir . 'classes/class-vgsr-entity-type.php' );
}

if ( ! class_exists( 'VGSR_Dispuut' ) ) :
/**
 * VGSR Dispuut Entity Class
 *
 * @since 1.0.0
 */
class VGSR_Dispuut extends VGSR_Entity_Type {

	/**
	 * Construct Dispuut Entity
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Entity type name
	 */
	public function __construct( $type ) {

		// Default error strings
		$error_wrong_format = esc_html__( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' );

		// Construct entity
		parent::__construct( $type, array(
			'_builtin'       => __FILE__,
			'post_type_args' => array(
				'description'      => esc_html__( "A Dispuut is part of the VGSR's social structure for discussing topics in small groups.", 'vgsr-entity' ),
				'has_archive'      => true,
				'menu_icon'        => 'dashicons-format-status',
				'posts_navigation' => array(
					'prev_text'          => esc_html__( 'Older disputen',      'vgsr-entity' ),
					'next_text'          => esc_html__( 'Newer disputen',      'vgsr-entity' ),
					'screen_reader_text' => esc_html__( 'Disputen navigation', 'vgsr-entity' )
				)
			),
			'has_archive'    => true,

		// Meta
		), array(

			// Since
			'since' => array(
				'column_title' => esc_html__( 'Since',    'vgsr-entity' ),
				'label'        => esc_html__( 'Since %s', 'vgsr-entity' ),
				'type'         => 'year',
				'name'         => 'menu_order',
				'display'      => true,
			),

			// Ceased
			'ceased' => array(
				'column_title' => esc_html__( 'Ceased',    'vgsr-entity' ),
				'label'        => esc_html__( 'Ceased %s', 'vgsr-entity' ),
				'type'         => 'year',
				'name'         => 'vgsr_entity_dispuut_ceased',
				'display'      => true,
			),

		// Errors
		), array(
			2 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Since',  'vgsr-entity' ) . '</strong>' ),
			3 => sprintf( $error_wrong_format, '<strong>' . esc_html__( 'Ceased', 'vgsr-entity' ) . '</strong>' ),
		) );
	}

	/**
	 * Include required files
	 *
	 * @since 2.0.0
	 *
	 * @param array $includes See VGSR_Entity_Type::includes() for description.
	 */
	public function includes( $includes = array() ) {

		// Default
		$includes = array(
			'actions',
			'functions',
		);

		parent::includes( $includes );
	}

	/**
	 * Setup default Kast actions and filters
	 *
	 * @since 2.0.0
	 */
	public function setup_actions() {
		parent::setup_actions();

		// Post
		add_filter( "vgsr_{$this->type}_display_meta", array( $this, 'entity_display_meta' ), 10, 2 );
	}

	/** Meta ***********************************************************/

	/**
	 * Modify the display meta for Dispuut posts
	 *
	 * @since 2.0.0
	 *
	 * @param array $meta Entity meta
	 * @param WP_Post $post Post object
	 * @return array Entity meta
	 */
	public function entity_display_meta( $meta, $post ) {

		// When both since and ceased data is present
		if ( isset( $meta['since'] ) && isset( $meta['ceased'] ) ) {

			// Combine both data into a single display value
			$meta['since']['value'] = array( $meta['since']['value'], $meta['ceased']['value'] );
			$meta['since']['label'] = esc_html__( 'Active between %1$s and %2$s', 'vgsr-entity' );

			// Remove individual value for ceased
			unset( $meta['ceased'] );
		}

		return $meta;
	}

	/**
	 * Return the requested entity meta value
	 *
	 * @since 2.0.0
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
					$value = $post->menu_order;
					break;
				case 'ceased' :
				default :
					$value = parent::get( $key, $post, $context );
					break;
			}
		}

		return $value;
	}

	/**
	 * Sanitize the given entity meta value
	 *
	 * @since 2.0.0
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
