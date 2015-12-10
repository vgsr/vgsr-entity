<?php

/**
 * VGSR Entity Functions
 * 
 * @package VGSR Entity
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pow2' ) ) :
/**
 * Return a single value from  the given values' power of 2
 *
 * @since 1.1.0
 *
 * @param array $values Values to convert from
 * @return int Value created out of power of 2
 */
function pow2( $values = array() ) {
	$retval = 0;
	foreach ( (array) $values as $val ) {
		$retval += pow( 2, $val );
	}

	return $retval;
}
endif;

if ( ! function_exists( 'unpow2' ) ) :
/**
 * Return all power of 2 values that are in the value
 *
 * @since 1.1.0
 *
 * @param int $value Value to convert back
 * @return array Values of power of 2 found
 */
function unpow2( $value = 0 ) {
	$retval = array();
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		foreach ( array_reverse( str_split( (string) decbin( (int) $value ) ) ) as $pow => $bi ) {
			if ( $bi ) $retval[] = $pow;
		}
	}

	return $retval;
}
endif;
