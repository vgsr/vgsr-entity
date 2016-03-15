/**
 * VGSR Entity Media Editor scripts
 *
 * @package VGSR Entity
 * @subpackage Media
 */

/* global wp, entityEditPost */
( function( $, _ ) {
	var Attachment = wp.media.model.Attachment,
	    FeaturedImage = wp.media.controller.FeaturedImage;

	/**
	 * Construct implementation of the FeaturedImage modal controller
	 * for the Entity Logo feature
	 *
	 * @since 2.0.0
	 */
	wp.media.controller.vgsrEntityLogo = FeaturedImage.extend({
		defaults: _.defaults({
			id:      'vgsr-entity-logo',
			title:    entityEditPost.l10n.entityLogoTitle,
			toolbar: 'vgsr-entity-logo'
		}, FeaturedImage.prototype.defaults ),

		/**
		 * Overload the controller's native selection updater method
		 *
		 * @since 2.0.0
		 */
		updateSelection: function() {
			var selection = this.get('selection'),
				id = wp.media.view.settings.post.entityLogoId,
				attachment;

			if ( '' !== id && -1 !== id ) {
				attachment = Attachment.get( id );
				attachment.fetch();
			}

			selection.reset( attachment ? [ attachment ] : [] );
		}
	});

	/**
	 * wp.media.vgsrEntityLogo
	 * @namespace
	 *
	 * @see wp.media.featuredImage wp-includes/js/media-editor.js
	 */
	wp.media.vgsrEntityLogo = {
		/**
		 * Get the entity logo post ID
		 *
		 * @global wp.media.view.settings
		 *
		 * @returns {wp.media.view.settings.post.entityLogoId|number}
		 */
		get: function() {
			return wp.media.view.settings.post.entityLogoId;
		},
		/**
		 * Set the entity logo id, save the entity logo data and
		 * set the HTML in the post meta box to the new entity logo.
		 *
		 * @global wp.media.view.settings
		 * @global wp.media.post
		 *
		 * @param {number} id The post ID of the entity logo, or -1 to unset it.
		 */
		set: function( id ) {
			var settings = wp.media.view.settings;

			settings.post.entityLogoId = id;

			wp.media.post( 'vgsr_entity_set_logo', {
				json:     true,
				post_id:  settings.post.id,
				logo_id:  settings.post.entityLogoId,
				_wpnonce: settings.post.nonce
			}).done( function( html ) {
				$( '#entity-logo', '#vgsr-entity-details' ).html( html );
			});
		},
		/**
		 * The Entity Logo workflow
		 *
		 * @global wp.media.controller.FeaturedImage
		 * @global wp.media.view.l10n
		 *
		 * @this wp.media.vgsrEntityLogo
		 *
		 * @returns {wp.media.view.MediaFrame.Select} A media workflow.
		 */
		frame: function() {
			if ( this._frame ) {
				wp.media.frame = this._frame;
				return this._frame;
			}

			this._frame = wp.media({
				state: 'vgsr-entity-logo',
				states: [ new wp.media.controller.vgsrEntityLogo() , new wp.media.controller.EditImage() ]
			});

			this._frame.on( 'toolbar:create:vgsr-entity-logo', function( toolbar ) {
				/**
				 * @this wp.media.view.MediaFrame.Select
				 */
				this.createSelectToolbar( toolbar, {
					text: entityEditPost.l10n.setEntityLogo
				});
			}, this._frame );

			this._frame.on( 'content:render:edit-image', function() {
				var selection = this.state('vgsr-entity-logo').get('selection'),
					view = new wp.media.view.EditImage( { model: selection.single(), controller: this } ).render();

				this.content.set( view );

				// after bringing in the frame, load the actual editor via an ajax call
				view.loadEditor();

			}, this._frame );

			this._frame.state('vgsr-entity-logo').on( 'select', this.select );
			return this._frame;
		},
		/**
		 * 'select' callback for Entity Logo workflow, triggered when
		 *  the 'Set Entity Logo' button is clicked in the media modal.
		 *
		 * @global wp.media.view.settings
		 *
		 * @this wp.media.controller.FeaturedImage
		 */
		select: function() {
			var selection = this.get('selection').single();

			if ( ! wp.media.view.settings.post.entityLogoId ) {
				return;
			}

			wp.media.vgsrEntityLogo.set( selection ? selection.id : -1 );
		},
		/**
		 * Open the content media manager to the 'entity logo' tab when
		 * the entity logo is clicked.
		 *
		 * Update the entity logo id when the 'remove' link is clicked.
		 *
		 * @global wp.media.view.settings
		 */
		init: function() {
			$( '#vgsr-entity-details' ).on( 'click', '#set-entity-logo', function( event ) {
				event.preventDefault();

				wp.media.vgsrEntityLogo.frame().open();
			}).on( 'click', '#remove-entity-logo', function() {
				wp.media.vgsrEntityLogo.set( -1 );
			});
		}
	};

	$( wp.media.vgsrEntityLogo.init );

}( jQuery, _ ) );
