<?php

/**
 * VGSR Entity Bestuur Settings Functions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add additional Bestuur settings fields
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'vgsr_entity_bestuur_settings_fields'
 * @return array Settings fields
 */
function vgsr_entity_bestuur_settings_fields() {
	return (array) apply_filters( 'vgsr_entity_bestuur_settings_fields', array(

		// Menu Order
		'menu-order' => array(
			'title'             => esc_html__( 'Menu Widget Order', 'vgsr-entity' ),
			'callback'          => 'vgsr_entity_bestuur_setting_menu_order_field',
			'sanitize_callback' => 'intval',
			'entity'            => 'bestuur',
			'section'           => 'main',
			'args'              => array(),
		),

		// Bestuur Positions
		'positions' => array(
			'title'             => esc_html__( 'Positions', 'vgsr-entity' ),
			'callback'          => 'vgsr_entity_bestuur_setting_positions_field',
			'sanitize_callback' => 'vgsr_entity_bestuur_sanitize_positions_field',
			'entity'            => 'bestuur',
			'section'           => 'attributes',
			'args'              => array(),
		),
	) );
}

/**
 * Output the Bestuur Menu Order settings field
 *
 * @since 1.0.0
 */
function vgsr_entity_bestuur_setting_menu_order_field() {

	// Define local variables
	$option_name = "_bestuur-menu-order";
	$value       = (int) get_option( $option_name ); ?>

	<select name="<?php echo esc_attr( $option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>">
		<option value="0" <?php selected( $value, 0 ); ?>><?php esc_html_e( 'Seniority',         'vgsr-entity' ); ?></option>
		<option value="1" <?php selected( $value, 1 ); ?>><?php esc_html_e( 'Reverse seniority', 'vgsr-entity' ); ?></option>
	</select>

	<p for="<?php echo esc_attr( $option_name ); ?>" class="description"><?php esc_html_e( 'The order in which the Besturen will be displayed in the Menu Widget.', 'vgsr-entity' ); ?></p>

	<?php
}

/**
 * Output the Bestuur Positions settings field
 *
 * @since 2.0.0
 */
function vgsr_entity_bestuur_setting_positions_field() {

	// Define table controls
	$controls = '<button type="button" class="button-link position-remove dashicons-before dashicons-no-alt"><span class="screen-reader-text">' . esc_html__( 'Remove position', 'vgsr-entity' ) . '</span></button>';

	// Get all positions
	$positions = vgsr_entity_bestuur_get_positions();

	?>

	<table class="widefat fixed striped positions">
		<thead>
			<tr>
				<th class="label"><?php esc_html_e( 'Label', 'vgsr-entity' ); ?></th>
				<th class="slug"><?php esc_html_e( 'Slug', 'vgsr-entity' ); ?></th>
				<th class="controls">
					<button type="button" class="button-link position-add dashicons-before dashicons-plus"><span class="screen-reader-text"><?php esc_html_e( 'Add position', 'vgsr-entity' ); ?></span></button>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th class="label"><?php esc_html_e( 'Label', 'vgsr-entity' ); ?></th>
				<th class="slug"><?php esc_html_e( 'Slug', 'vgsr-entity' ); ?></th>
				<th class="controls"></th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ( $positions as $position => $args ) : ?>
			<tr>
				<td class="label"><input type="text" name="positions[label][]" value="<?php echo esc_attr( $args['label'] ); ?>" /></td>
				<td class="slug"><input type="text" name="positions[slug][]" value="<?php echo esc_attr( $args['slug'] ); ?>" /></td>
				<td class="controls"><?php echo $controls; ?></td>
			</tr>
			<?php endforeach; ?>

			<?php if ( empty( $positions ) ) : ?>
			<tr>
				<td class="label"><input type="text" name="positions[label][]" value="" /></td>
				<td class="slug"><input type="text" name="positions[slug][]" value="" /></td>
				<td class="controls"><?php echo $controls; ?></td>
			</tr>
			<?php endif; ?>

			<tr class="positions-add-row" style="display:none;">
				<td class="label"><input type="text" name="positions[label][]" value="" /></td>
				<td class="slug"><input type="text" name="positions[slug][]" value="" /></td>
				<td class="controls"><?php echo $controls; ?></td>
			</tr>
		</tbody>
	</table>

	<?php
}

/**
 * Sanitize the Bestuur Positions settings field input
 *
 * @since 2.0.0
 *
 * @param mixed $input Option input
 * @param string $option Option name
 * @param mixed $original_value Original option value
 * @return array Option input
 */
function vgsr_entity_bestuur_sanitize_positions_field( $input, $option = '', $original_value = null ) {

	// No input available to sanitize
	if ( ! $input && ! isset( $_REQUEST['positions'] ) ) {
		$input = $original_value;
	} else {
		$_input = array();

		// Values were passed
		if ( ! empty( $input ) ) {
			$_input = $input;

		// Collect and sanitize input from `$_REQUEST`
		} else {
			foreach ( $_REQUEST['positions'] as $key => $values ) {
				foreach ( $values as $k => $v ) {
					$_input[ $k ][ $key ] = esc_html( $v );
				}
			}
		}

		// Process input
		foreach ( $_input as $position => $args ) {

			// Remove empty row
			if ( empty( $args['slug'] ) && empty( $args['label'] ) ) {
				unset( $_input[ $position ] );

			// Add missing slug
			} elseif ( empty( $args['slug'] ) ) {
				$_input[ $position ]['slug'] = sanitize_title( $args['label'] );

			// Add missing label
			} elseif ( empty( $args['label'] ) ) {
				$_input[ $position ]['label'] = ucfirst( $args['slug'] );
			}
		}

		$input = ! empty( $_input ) ? $_input : $original_value;
	}

	return $input;
}
