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
			'path'           => 'kasten',
			'post_type_args' => array(
				'description' => esc_html__( "A Kast is an initiated house where active VGSR members dwell.", 'vgsr-entity' ),
				'menu_icon'   => 'dashicons-admin-home',
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
	 */
	public function setup_globals() {
		parent::setup_globals();

		add_image_size( $this->args['thumbsize'], $this->args['mini_size'], $this->args['mini_size'], true );
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
		add_action( "vgsr_entity_{$this->type}_details", array( $this, 'entity_details' ) );
	}

	/** Post ***********************************************************/

	/**
	 * Output the entity's details
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	public function entity_details( $post ) {

		// Bail when the user has no access
		if ( ! vgsr_entity_check_access() )
			return;

		// Define local variables
		$address = array();

		// Walk address details
		foreach ( array(
			'address-street', 'address-number', 'address-addition',
			'address-postcode', 'address-city', 'address-phone'
		) as $detail ) {
			$address[ $detail ] = $this->get( $detail, $post );
		}

		// Bail when whithout details
		if ( ! array_filter( $address ) )
			return;

		// Concat street detail
		$street = "{$address['address-street']} {$address['address-number']}{$address['address-addition']}";

		/**
		 * Define address markup using schema.org's PostalAddress definition
		 * @see http://www.iandevlin.com/blog/2012/01/html/marking-up-a-postal-address-with-html
		 */

		?>

		<div class="entity-address" itemscope itemtype="http://schema.org/ContactPoint">
			<h4><?php esc_html_e( 'Address', 'vgsr-entity' ); ?></h4>

			<div itemscope itemtype="http://schema.org/PostalAddress">
				<span itemprop="streetAddress" class="address-street"><?php echo $street; ?></span><br/>
				<span itemprop="postalCode" class="address-postcode"><?php echo $address['address-postcode']; ?></span>
				<span itemprop="addressLocality" class="address-city"><?php echo $address['address-city']; ?></span><br/>
			</div>

			<?php if ( ! empty( $addressp['address-phone'] ) ) : ?>
			<span itemprop="telephone" class="address-phone"><?php echo $address['address-phone']; ?></span>
			<?php endif; ?>
		</div>

		<?php
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
