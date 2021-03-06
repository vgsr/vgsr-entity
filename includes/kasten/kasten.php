<?php

/**
 * VGSR Kast Class
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

if ( ! class_exists( 'VGSR_Kast' ) ) :
/**
 * VGSR Kast Entity Class
 *
 * @since 1.0.0
 */
class VGSR_Kast extends VGSR_Entity_Type {

	/**
	 * Construct Kast Entity
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
				'description'      => esc_html__( "A Kast is an initiated house where active VGSR members dwell.", 'vgsr-entity' ),
				'has_archive'      => true,
				'menu_icon'        => 'dashicons-admin-home',
				'posts_navigation' => array(
					'prev_text'          => esc_html__( 'Older kasten',      'vgsr-entity' ),
					'next_text'          => esc_html__( 'Newer kasten',      'vgsr-entity' ),
					'screen_reader_text' => esc_html__( 'Kasten navigation', 'vgsr-entity' )
				)
			),
			'has_archive'    => true,
			'thumbsize'      => 'mini-thumb',
			'mini_size'      => 100,
			'admin_class'    => 'VGSR_Kast_Admin'

		// Meta
		), array(

			// Address: Street
			'address-street' => array(
				'column_title' => esc_html__( 'Street', 'vgsr-entity' ),
				'label'        => esc_html__( 'Street', 'vgsr-entity' ),
				'type'         => 'text',
				'name'         => 'address_street',
			),

			// Address: Number
			'address-number' => array(
				'column_title' => esc_html__( 'Number', 'vgsr-entity' ),
				'label'        => esc_html__( 'Number', 'vgsr-entity' ),
				'type'         => 'address-number',
				'name'         => 'address_number',
				'pair_with'    => 'address-addition',
			),

			// Address: Addition
			'address-addition' => array(
				'column_title' => esc_html__( 'Addition', 'vgsr-entity' ),
				'label'        => esc_html__( 'Addition', 'vgsr-entity' ),
				'type'         => 'address-addition',
				'name'         => 'address_addition',
			),

			// Address: Postcode
			'address-postcode' => array(
				'column_title' => esc_html__( 'Postcode', 'vgsr-entity' ),
				'label'        => esc_html__( 'Postcode', 'vgsr-entity' ),
				'type'         => 'postcode',
				'name'         => 'address_postcode',
			),

			// Address: City
			'address-city' => array(
				'column_title' => esc_html__( 'City', 'vgsr-entity' ),
				'label'        => esc_html__( 'City', 'vgsr-entity' ),
				'type'         => 'text',
				'name'         => 'address_city',
			),

			// Address: Phone
			'address-phone' => array(
				'column_title' => esc_html__( 'Phone', 'vgsr-entity' ),
				'label'        => esc_html__( 'Phone', 'vgsr-entity' ),
				'type'         => 'phone',
				'name'         => 'address_phone',
			),

			// Since
			'since' => array(
				'column_title' => esc_html__( 'Since',    'vgsr-entity' ),
				'label'        => esc_html__( 'Since %s', 'vgsr-entity' ),
				'type'         => 'date',
				'name'         => 'vgsr_entity_kast_since',
				'display'      => true,
				'column-after' => 'title',
				'column-width' => '80px',
				'column-hide'  => false,
			),

			// Ceased
			'ceased' => array(
				'column_title' => esc_html__( 'Ceased',    'vgsr-entity' ),
				'label'        => esc_html__( 'Ceased %s', 'vgsr-entity' ),
				'type'         => 'year',
				'name'         => 'vgsr_entity_kast_ceased',
				'display'      => true,
				'column-width' => '80px',
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
	 */
	public function setup_globals() {
		parent::setup_globals();

		add_image_size( $this->thumbsize, $this->mini_size, $this->mini_size, true );
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
			'template',
		);

		// Admin
		if ( is_admin() ) {
			$includes[] = 'admin';
			$includes[] = 'settings';
		}

		parent::includes( $includes );
	}

	/**
	 * Setup default Kast actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {
		parent::setup_actions();

		// Meta
		add_action( 'vgsr_entity_meta_input_address-number_field',   array( $this, 'address_number_input_field'   ), 10, 3 );
		add_action( 'vgsr_entity_meta_input_address-addition_field', array( $this, 'address_addition_input_field' ), 10, 3 );

		// Post
		add_filter( "vgsr_{$this->type}_display_meta",   array( $this, 'entity_display_meta' ), 10, 2 );
	}

	/** Meta ***********************************************************/

	/**
	 * Return a collection of address meta fields
	 *
	 * @since 2.0.0
	 *
	 * @param int|WP_Post $post Optional. Post object or ID.
	 * @param string $context Optional. The context to get the meta values for.
	 * @return array Address meta fields
	 */
	public function address_meta( $post = null, $context = 'display' ) {

		// Get address meta fields
		$fields = array_filter( $this->meta, function( $v ){
			return 0 === strpos( $v['name'], 'address' );
		});

		// Add post values
		if ( null !== $post && $post = get_post( $post ) ) {
			foreach ( array_keys( $fields ) as $k ) {
				$fields[ $k ]['value'] = $this->get( $k, $post, $context );
			}
		}

		return $fields;
	}

	/**
	 * Return the input markup for the Address: Number meta field
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Meta key
	 * @param int|WP_Post $post Post object
	 * @param array $meta Meta arguments with value
	 */
	public function address_number_input_field( $key, $post, $meta ) {
		$pair_with = $meta['pair_with'];
		$addition  = $this->meta[ $pair_with ]; ?>

		<label class="alignleft">
			<span class="title"><?php echo esc_html( $meta['column_title'] ); ?></span>
			<span class="input-text-wrap">
				<input id="<?php echo $meta['id']; ?>" type="number" name="<?php echo esc_attr( $meta['name'] ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" />
				<input id="<?php echo "{$this->type}_{$post->ID}_{$addition['name']}"; ?>" type="text" name="<?php echo "{$addition['name']}"; ?>" value="<?php echo esc_attr( $this->get( $pair_with, $post, 'edit' ) ); ?>" />
			</span>
		</label>

		<?php
	}

	/**
	 * Return the input markup for the Address: Addition meta field
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Meta key
	 * @param int|WP_Post $post Post object
	 * @param array $meta Meta arguments with value
	 */
	public function address_addition_input_field( $key, $post, $meta ) {
		// Just output an HTML space, so the field is not empty while it is.
		echo '&nbsp;';
	}

	/**
	 * Modify the display meta for Kast posts
	 *
	 * @since 2.0.0
	 *
	 * @param array $meta Entity meta
	 * @param WP_Post $post Post object
	 * @return array Entity meta
	 */
	public function entity_display_meta( $meta, $post ) {

		// When since data is present
		if ( isset( $meta['since'] ) ) {

			// Get date from format, display year only
			if ( $since = DateTime::createFromFormat( 'Y/m/d', $meta['since']['raw'] ) ) {
				$meta['since']['value'] = sprintf( '<time datetime="%s">%s</time>', $since->format( 'Y-m-d' ), $since->format( 'Y' ) );
			}

			// When ceased data is also present
			if ( isset( $meta['ceased'] ) ) {

				// Combine both data into a single display value
				$meta['since']['value'] = array( $meta['since']['value'], $meta['ceased']['value'] );
				$meta['since']['label'] = esc_html__( 'Active between %1$s and %2$s', 'vgsr-entity' );

				// Remove individual value for ceased
				unset( $meta['ceased'] );
			}
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
				case 'ceased' :
				default :
					$value = parent::get( $key, $post, $context );
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

		// Basic input sanitization
		$value = sanitize_text_field( $value );

		switch ( $key ) {
			case 'address-addition' :
				$value = parent::save( $key, strtoupper( $value ), $post );
				break;
			case 'since' :
			case 'ceased' :
			default :
				$value = parent::save( $key, $value, $post );
		}

		return $value;
	}
}

endif; // class_exists
