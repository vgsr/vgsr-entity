<?php

/**
 * VGSR Entity Bestuur Template Functions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Address ************************************************************/

/**
 * Return the details of a Kast's address
 *
 * @since 2.1.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_kast_get_address'
 *
 * @param WP_Post|int $post Optional. Post object or ID. Defaults to the current post.
 * @return array Address details
 */
function vgsr_entity_kast_get_address( $post = 0 ) {

	// Define return variable
	$address = array();

	// Get post object
	if ( $post = vgsr_entity_get_post_of_type( 'kast', $post ) ) {

		// Walk address details
		foreach ( array(
			'address-street', 'address-number', 'address-addition',
			'address-postcode', 'address-city', 'address-phone'
		) as $detail ) {
			$address[ $detail ] = vgsr_entity_get_type_object( 'kast' )->get( $detail, $post );
		}
	}

	/**
	 * Filter the details of a Kast's address
	 *
	 * @since 2.1.0
	 *
	 * @param array $address Address details
	 * @param WP_Post $post Kast post object
	 */
	return apply_filters( 'vgsr_entity_kast_get_address', $address, $post );
}

/**
 * Output the address details with schema.org markup
 *
 * @link http://www.iandevlin.com/blog/2012/01/html/marking-up-a-postal-address-with-html
 *
 * @since 2.1.0
 *
 * @param array|WP_Post|int $args Optional. Address details or post object or ID. Defaults to the current post.
 */
function vgsr_entity_kast_the_address( $args = 0 ) {

	// Get address details
	if ( ! array( $args ) ) {
		$args = vgsr_entity_kast_get_address( $args );
	}

	// Bail when details are not defined
	if ( ! array_filter( $args ) )
		return;

	// Parse args
	$r = wp_parse_args( $args, array(
		'address-street'   => '',
		'address-number'   => '',
		'address-addition' => '',
		'address-postcode' => '',
		'address-city'     => '',
		'address-phone'    => ''
	) );

	?>

	<div itemscope itemtype="http://schema.org/PostalAddress">
		<span itemprop="streetAddress" class="address-street"><?php
			echo "{$r['address-street']} {$r['address-number']}{$r['address-addition']}";
		?></span><br/>
		<span itemprop="postalCode" class="address-postcode"><?php echo $r['address-postcode']; ?></span>
		<span itemprop="addressLocality" class="address-city"><?php echo $r['address-city']; ?></span><br/>
	</div>

	<?php if ( ! empty( $r['address-phone'] ) ) : ?>
	<span itemprop="telephone" class="address-phone"><?php echo $r['address-phone']; ?></span>
	<?php endif; ?>

	<?php
}

/**
 * Output the entity's details
 *
 * @since 2.0.0
 *
 * @param WP_Post $post Post object
 */
function vgsr_entity_kast_address_detail( $post ) {

	// Bail when the user has no access or this is not a singular post
	if ( ! vgsr_entity_check_access() || ! is_singular() )
		return;

	// Get address details
	$address = vgsr_entity_kast_get_address( $post );

	// When details are defined
	if ( array_filter( $address ) ) : ?>

	<div class="entity-address" itemscope itemtype="http://schema.org/ContactPoint">
		<h4><?php esc_html_e( 'Address', 'vgsr-entity' ); ?></h4>

		<div class="address-details">
			<?php vgsr_entity_kast_the_address( $address ); ?>
		</div>
	</div>
		
	<?php endif;
}
