<?php

/**
 * VGSR Entity Bestuur Actions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Positions
add_action( 'vgsr_entity_bestuur_details', 'vgsr_entity_bestuur_positions_detail' );

// Theme
add_filter( 'document_title_parts',                'vgsr_entity_bestuur_document_title_parts' );
add_filter( 'vgsr_bestuur_menu_widget_query_args', 'vgsr_entity_bestuur_widget_menu_order'    );
