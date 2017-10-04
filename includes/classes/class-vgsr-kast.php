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
			'labels'      => array(
				'name'               => esc_html__( 'Kasten',                   'vgsr-entity' ),
				'singular_name'      => esc_html__( 'Kast',                     'vgsr-entity' ),
				'add_new'            => esc_html__( 'New Kast',                 'vgsr-entity' ),
				'add_new_item'       => esc_html__( 'Add new Kast',             'vgsr-entity' ),
				'edit_item'          => esc_html__( 'Edit Kast',                'vgsr-entity' ),
				'new_item'           => esc_html__( 'New Kast',                 'vgsr-entity' ),
				'all_items'          => esc_html__( 'All Kasten',               'vgsr-entity' ),
				'view_item'          => esc_html__( 'View Kast',                'vgsr-entity' ),
				'search_items'       => esc_html__( 'Search Kasten',            'vgsr-entity' ),
				'not_found'          => esc_html__( 'No Kasten found',          'vgsr-entity' ),
				'not_found_in_trash' => esc_html__( 'No Kasten found in trash', 'vgsr-entity' ),
				'menu_name'          => esc_html__( 'Kasten',                   'vgsr-entity' ),
				'settings_title'     => esc_html__( 'Kasten Settings',          'vgsr-entity' ),
			),
			'menu_icon'   => 'dashicons-admin-home',
			'has_archive' => true,

			// Thumbnail
			'thumbsize'   => 'mini-thumb',
			'mini_size'   => 100,

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

		// Settings
		add_action( 'vgsr_entity_settings_fields', array( $this, 'add_settings_fields' ) );
		add_filter( 'vgsr_kast_settings_load',     array( $this, 'downsize_thumbs'     ) );

		// Meta
		add_action( 'vgsr_entity_meta_input_address-number_field',   array( $this, 'address_number_input_field'   ), 10, 3 );
		add_action( 'vgsr_entity_meta_input_address-addition_field', array( $this, 'address_addition_input_field' ), 10, 3 );

		// Post
		add_action( "vgsr_entity_{$this->type}_details", array( $this, 'entity_details' ) );
	}

	/** Settings *******************************************************/

	/**
	 * Add additional Kast settings fields
	 *
	 * @since 1.0.0
	 */
	public function add_settings_fields( $fields ) {

		// Kast recreate thumbnail option
		$fields['main']['downsize-thumbs'] = array(
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
		<label for="_kast-downsize_thumbs"><span class="description"><?php echo sprintf( __( 'This is a one time resizing of thumbs for %s. NOTE: This option only <em>adds</em> new image sizes, it does not remove old ones.', 'vgsr-entity' ), $this->args['labels']['name'] ); ?></span></label>

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
			<h4><?php _e( 'Address', 'vgsr-entity' ); ?></h4>

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
