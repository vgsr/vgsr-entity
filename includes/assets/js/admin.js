/**
 * VGSR Entity Admin Scripts
 *
 * @package VGSR Entity
 * @subpackage Administration
 */

( function( $ ) {

	// Can I use datepicker? Initiate datepickers
	if ( $.fn.datepicker ) {
		var dp_args = {
			dateFormat: 'yy/mm/dd',
			changeMonth: true,
			changeYear: true
		};

		$( '.input-text-wrap:not(#inline-edit .input-text-wrap) .datepicker' ).datepicker( dp_args );
	}

	/* wp-admin/edit.php */

	// Move entity quick edit lines into the right col
	var $inline = $( '.inline-edit-row' );
	$inline.find( '.entity-quick-edit .inline-edit-col' ).appendTo( $inline.find( '.inline-edit-col-right' ).first() );

	/* global inlineEditPost, entityEditPost */

	// Create a copy of the WP inline edit post function
	var wp_inline_edit = inlineEditPost.edit;

	// Overwrite the function with our own code
	inlineEditPost.edit = function( id ) {

		// Run the original inline edit function
		wp_inline_edit.apply( this, arguments );

		var t = this, $editRow, $rowData, fields, val;

		if ( typeof( id ) === 'object' ) {
			id = t.getId( id );
		}

		$editRow = $( '.inline-editor' );
		$rowData = $( '#post-' + id );

		// Can I use datepicker? Initiate datepickers inside the edit row
		if ( $.fn.datepicker ) {
			$editRow.find( '.input-text-wrap .datepicker' ).datepicker( dp_args );
		}

		fields = entityEditPost.fields;

		// Refresh input values for this post
		for ( f = 0; f < fields.length; f++ ) {
			val = $( '.' + fields[ f ].key + ' .edit-value', $rowData );
			// Deal with Twemoji
			val.find( 'img' ).replaceWith( function() { return this.alt; } );
			val = val.text();
			$( ':input[name="' + fields[ f ].name + '"]', $editRow ).val( val );
		}
	};

})( jQuery );
