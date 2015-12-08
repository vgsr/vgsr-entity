<?php

/**
 * VGSR Entity Functions
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'dissect_pow2' ) ) :
/**
 * Return all power of 2 values that are in the value
 *
 * @since 1.1.0
 *
 * @param int $value Value to dissect
 * @return array Values of power of 2 found
 */
function dissect_pow2( $value = 0 ) {
	$retval = array();
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		foreach ( array_reverse( str_split( (string) decbin( (int) $value ) ) ) as $pow => $bi ) {
			if ( $bi ) $retval[] = pow( 2, $pow );
		}
	}

	return $retval;
}
endif;
