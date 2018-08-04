<?php

/**
 * VGSR Entity Kast Settings Functions
 *
 * @package VGSR Entity
 * @subpackage Kast
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add additional Kast settings fields
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_kast_settings_fields'
 * @return array Settings fields
 */
function vgsr_entity_kast_settings_fields() {
	return (array) apply_filters( 'vgsr_entity_kast_settings_fields', array() );
}
