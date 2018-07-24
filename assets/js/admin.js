/**
 * VGSR Entity Admin Scripts
 *
 * @package VGSR Entity
 * @subpackage Administration
 */

/* global inlineEditPost, entityEditPost */
( function( $ ) {

	var settings = entityEditPost.l10n, _inline, dpArgs;

	// Can I use datepicker? Initiate datepickers
	if ( $.fn.datepicker ) {
		dpArgs = {
			dateFormat: 'yy/mm/dd',
			changeMonth: true,
			changeYear: true
		};

		$( '.input-text-wrap:not(#inline-edit .input-text-wrap) .datepicker' ).datepicker( dpArgs );
	}

	/* wp-admin/post.php */

	// Archive post status
	if ( settings.hasArchive ) {

		// Prepend post status dropdown option
		$( '<option />', {
			value:    settings.archiveStatusId,
			selected: settings.isArchived,
			text:     settings.archiveLabel
		}).prependTo( '#post_status' );

		// Correct displayed status
		if ( settings.isArchived ) {
			$( '#post-status-display' ).text( settings.archiveLabel );

			// Restore 'Published' dropdown option
			$( '<option />', {
				value:    settings.publishStatusId,
				text:     settings.publishLabel
			}).insertAfter( '#post_status option:first' );
		}
	}

	// Bestuur Positions
	var $bestuurPositions = $( '.post-type-bestuur .positions' ),
		suggestAjaxUrl = $bestuurPositions.find( 'input[name="positions-ajax-url"]' ).val(),
		suggestArgs = {
			resultsClass: 'ac_results bestuur-positions',
			onSelect: function() {
				this.value = this.value.substr( 0, this.value.indexOf( ' (' ) );
			}
		};

	$bestuurPositions
		// Add row
		.on( 'click', '.position-add', function() {
			$bestuurPositions
				.find( '.positions-add-row' )
					.clone()
					.removeClass( 'positions-add-row' )
					.insertBefore( '.positions-add-row' )
					.show()
					// Add suggest UI to cloned user input
					.find( '.positions-user-name' )
						.suggest( suggestAjaxUrl, suggestArgs );
		})
		// Remove row
		.on( 'click', '.position-remove', function() {
			$(this).parent().remove();
		})
		// Add suggest UI to user input
		.find( '.positions-user-name' )
			.suggest( suggestAjaxUrl, suggestArgs );

	/* wp-admin/edit.php */

	// Move entity quick edit lines into the right col
	_inline = $( '.inline-edit-row' );
	_inline.find( '.entity-quick-edit .inline-edit-col' ).appendTo( _inline.find( '.inline-edit-col-right' ).first() );

	// When editing inline
	if ( typeof inlineEditPost !== 'undefined' ) {

		// Create a copy of the WP inline edit post function
		var wp_inline_edit = inlineEditPost.edit;

		// Overwrite the function with our own code
		inlineEditPost.edit = function( id ) {

			// Run the original inline edit function
			wp_inline_edit.apply( this, arguments );

			/*
			 * From here on we add to the original logic
			 */
			var t = this, _editRow, _rowData, fields, val;

			if ( typeof( id ) === 'object' ) {
				id = t.getId( id );
			}

			_editRow = $( '.inline-editor' );
			_rowData = $( '#post-' + id );

			// Can I use datepicker? Initiate datepickers inside the edit row
			if ( $.fn.datepicker ) {
				_editRow.find( '.input-text-wrap .datepicker' ).datepicker( dpArgs );
			}

			// Post type meta fields
			fields = entityEditPost.fields;

			// Refresh input values for this post's meta fields
			for ( f = 0; f < fields.length; f++ ) {
				val = $( '.' + fields[ f ].key + ' .edit-value', _rowData );
				// Deal with Twemoji
				val.find( 'img' ).replaceWith( function() { return this.alt; } );
				val = val.text();
				$( ':input[name="' + fields[ f ].name + '"]', _editRow ).val( val );
			}

			// Archive post status
			if ( settings.hasArchive ) {

				// Prepend post status dropdown option
				$( '<option />', {
					value:    settings.archiveStatusId,
					selected: ( settings.archiveStatusId == _rowData.find( '#inline_' + id ).find( '._status' ).text() ),
					text:     settings.archiveLabel
				}).prependTo( _editRow.find( ':input[name="_status"]' ) );
			}
		};

		// Create a copy of the WP inline bulk edit function
		var wp_bulk_edit = inlineEditPost.setBulk;

		// Overwrite the function with our own code
		inlineEditPost.setBulk = function() {

			// Run the original bulk edit function
			wp_bulk_edit.apply( this, arguments );

			/*
			 * From here on we add to the original logic
			 */
			var _bulkRow = $( '.inline-editor' );

			// Archive post status
			if ( settings.hasArchive && ! _bulkRow.find( ':input[name="_status"] option[value="archive"]' ).length ) {

				// Prepend post status dropdown option - after the select-none
				$( '<option />', {
					value: settings.archiveStatusId,
					text:  settings.archiveLabel
				}).insertAfter( _bulkRow.find( ':input[name="_status"] option:first' ) );
			}
		};
	}

})( jQuery );
