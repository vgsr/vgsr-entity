/**
 * VGSR Entity Admin Scripts
 *
 * @package VGSR Entity
 * @subpackage Administration
 */

( function( $ ) {

	// Can I use datepicker?
	if ( $.fn.datepicker ) {

		// Initiate datepickers
		$( '.input-text-wrap .datepicker' ).datepicker({
			dateFormat: 'dd/mm/yyyy',
			changeMonth: true,
			changeYear: true
		});
	}

	/* wp-admin/edit.php */

	var $inline = $( '.inline-edit-row' );

	// Move entity quick edit into the right col
	$inline.find( '.entity-quick-edit .inline-edit-col' ).appendTo( $inline.find( '.inline-edit-col-right' ).first() );

})( jQuery );
