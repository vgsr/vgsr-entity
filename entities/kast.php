<?php

/**
 * VGSR Kast class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'VGSR_Entity' ) )
	require( plugin_dir_path( __FILE__ ) .'vgsr-entity.php' );

if ( !class_exists( 'VGSR_Entity_Kast' ) ) :

/**
 * Plugin class
 */
class VGSR_Entity_Kast extends VGSR_Entity {

	public $mini_size;

	/**
	 * Construct Kast
	 */
	function __construct(){
		parent::__construct( array( 
			'single' => 'Kast', 
			'multi'  => 'Kasten' 
			) );
	}

	/** 
	 * Setup class globals 
	 */
	function setup_globals(){
		$this->thumbsize = 'mini-thumb'; // Parent var
		$this->mini_size = 100;

		add_image_size( $this->thumbsize, $this->mini_size, $this->mini_size, true );
	}

	/**
	 * Setup class actions
	 *
	 * @uses add_filter()
	 * @return void
	 */
	function setup_actions(){
		add_action( 'admin_init',            array( $this, 'settings_downsize_thumbs' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts'          ) );
		add_action( 'admin_head',            array( $this, 'admin_scripts'            ) );
		add_action( 'save_post',             array( $this, 'metabox_since_save'       ) );
	}

	/**
	 * Filter register post type arguments
	 * 
	 * @param array $labels
	 * @return array $labels
	 */
	function edit_register_cpt( $args ){
		// Edit arguments
		$args['labels']['add_new'] = $args['labels']['new_item'] = sprintf( _x('New %s', 'In Dutch «New Kast» doesn\'t translate like «New Bestuur».', 'vgsr-entity'), strtolower( $this->labels['single'] ) );

		return $args;
	}

	/**
	 * Add additional entity settings fields
	 * 
	 * @return void
	 */
	function settings_downsize_thumbs(){
		add_settings_field( '_kast-downsize-thumbs', __('Recreate Thumbnails', 'vgsr-entity'), array( $this, 'settings_downsize_thumbs_field' ), $this->settings_page, $this->settings_section );
		register_setting( $this->settings_page, '_kast-downsize-thumbs', 'intval' );
	}

	/**
	 * Output the kast downsize thumbs settings field
	 * 
	 * @return void
	 */
	function settings_downsize_thumbs_field(){
		echo '<label><input type="checkbox" name="_kast-downsize-thumbs" '. checked( get_option( '_kast-downsize-thumbs' ), 1, false ) .' value="1"/> <span class="description">'. sprintf( __('This is a one time resizing of thumbs for %s. NOTE: This option only <strong>adds</strong> new image sizes, it doesn\'t remove old ones.', 'vgsr-entity'), $this->labels['multi'] ) .'</span></label>';
	}

	/**
	 * Resize kast thumbs of all kasten first attachments
	 *
	 * Will only be run if the _kast-downsize-thumbs option is set.
	 * 
	 * @return void
	 */
	function downsize_thumbs(){

		// Only do this if we're asked to
		if ( !get_option( '_kast-downsize-thumbs' ) )
			return;

		// Get all kasten
		$kasten = get_posts( array(
			'post_type'   => $this->type,
			'numberposts' => -1 
			) );

		// Loop over all kasten
		foreach ( $kasten as $kast ) :

			// Get first attachment - assuming that's the one we want to convert
			$logo = get_children( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_parent' => $kast->ID ) );
			$logo = is_array( $logo ) ? reset( $logo ) : false;

			// Do not continue without any attachment
			if ( !$logo ) continue;

			// Juggling with {$logo} so storing ID separate
			$logo_id = $logo->ID;
			$logo    = wp_get_attachment_image_src( $logo_id, $this->thumbsize );

			if ( $logo[1] == $this->mini_size && $logo[2] == $this->mini_size )
				continue;

			/** 
			 * No perfect match found so edit images
			 */
			
			// Create absolute file path
			$file_path = ABSPATH . substr( dirname( $logo[0] ), ( strpos( $logo[0], parse_url( site_url(), PHP_URL_PATH ) ) + strlen( parse_url( site_url(), PHP_URL_PATH ) ) + 1 ) ) .'/'. basename( $logo[0] );
			
			// Do the resizing
			$logo = image_resize( $file_path, $this->mini_size, $this->mini_size, true );

			// Setup image size meta
			$args = array( 
				'file'   => basename( $logo ),
				'width'  => $this->mini_size,
				'height' => $this->mini_size
				);

			// Store attachment metadata > we're havin a mini-thumb!
			$meta = wp_get_attachment_metadata( $logo_id );
			$meta['sizes'][$this->thumbsize] = $args;
			wp_update_attachment_metadata( $logo_id, $meta );

		endforeach;

		// Downsizing done, set option off
		update_option( '_kast-downsize-thumbs', 0 );
	}

	/**
	 * Do code on plugin admin settings page start
	 * 
	 * @return void
	 */
	function page_actions(){

		// Create kast thumbs if there are kasten found without one
		$this->downsize_thumbs();
	}

	/**
	 * Enqueue scripts to the edit kast page
	 * 
	 * @return void
	 */
	function enqueue_scripts(){
		global $pagenow, $post, $vgsr_entity;

		if ( !isset( $post ) || ( $post->post_type != $this->type && 'post.php' != $pagenow ) )
			return;

		// Enable jQuery UI Datepicker
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		// Include jQuery UI Theme style
		wp_register_style( 'jquery-ui-theme-fresh', plugins_url( 'css/jquery.ui.theme.css', $vgsr_entity->file ) );
		wp_enqueue_style( 'jquery-ui-theme-fresh' );
	}

	/**
	 * Output custom JS script to the edit kast page
	 * 
	 * @return void
	 */
	function admin_scripts(){
		global $pagenow, $post;

		if ( isset( $post ) && $post->post_type == $this->type && 'post.php' == $pagenow ){
			?>
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('.datepicker').datepicker({
					dateFormat: 'dd/mm/yy',
					changeMonth: true,
					changeYear: true
				});
			});
		</script><?php
		}
	}

	/**
	 * Add metaboxes to the edit kast screen
	 *
	 * @uses add_meta_box()
	 * @return void
	 */
	function metabox_cb(){

		// Add Kast Data meta box
		add_meta_box(
			'vgsr-entity-'. $this->type,
			__('Kast Data', 'vgsr-entity'),
			array( $this, 'metabox_display' ),
			$this->type,
			'side'
			);
	}

	/**
	 * Output kast meta box
	 * 
	 * @param object $post The current post
	 * @return void
	 */
	function metabox_display( $post ){
		global $vgsr_entity;

		/** Since Meta **/

			// Get stored meta value
			$value = get_post_meta( $post->ID, 'vgsr_entity_kast_since', true );

			// If no value served set it empty
			if ( !$value )
				$value = '';

			// Output nonce verification field
			wp_nonce_field( $vgsr_entity->file, 'vgsr_entity_kast_since_nonce' );

			// Start field
			echo '<p id="vgsr_entity_kast_since">';

			// Output input field
			echo '<label><strong>'. __('Since', 'vgsr-entity') .': </strong><input class="ui-widget-content ui-corner-all datepicker" type="text" name="vgsr_entity_kast_since" value="'. $value .'" /></label>';

			// Output field information
			echo '<span class="howto">'. __('The required format is dd/mm/yyyy.', 'vgsr-entity') .'</span>';

			// End field
			echo '</p>';
		
		/** Other Meta **/

		do_action( 'vgsr_entity_'. $this->type .'_metabox', $post );
	}

	/**
	 * Save kast since meta field
	 * 
	 * @param int $post_id The post ID
	 * @return void
	 */
	function metabox_since_save( $post_id ){
		global $vgsr_entity;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( get_post_type( $post_id ) !== $this->type )
			return;

		$cpt_obj = get_post_type_object( $this->type );

		if ( !current_user_can( $cpt_obj->cap->edit_posts ) || !current_user_can( $cpt_obj->cap->edit_post, $post_id ) )
			return;

		if ( !wp_verify_nonce( $_POST['vgsr_entity_kast_since_nonce'], $vgsr_entity->file ) )
			return;

		// We're authenticated now
		
		$input = sanitize_text_field( $_POST['vgsr_entity_kast_since'] );

		// Does the inserted input match our requirements? - Checks for 01-31 / 01-12 / 1900-2099
		if ( !preg_match( '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/(19|20)[0-9]{2}$/', $input, $matches ) ){

			// Alert the user
			add_filter( 'redirect_post_location', array( $this, 'metabox_since_save_redirect' ) );

			$input = false;
		}

		// Update post meta
		else {
			update_post_meta( $post_id, 'vgsr_entity_kast_since', $input );
		}
	}

	/**
	 * Add query arg to the redirect location after save_post()
	 * 
	 * @param string $location The redrirect location
	 * @return string $location
	 */
	function metabox_since_save_redirect( $location ){
		return add_query_arg( 'kast-error', '1', $location );
	}

	/**
	 * Output the admin message for the given kast error
	 * 
	 * @return void
	 */
	function admin_messages( $messages ){

		// Set up post messages
		$messages[1] = sprintf( __('The submitted value for %s is not given in the valid format.', 'vgsr-entity'), '<strong>'. __('Since', 'vgsr-entity') .'</strong>' );

		return $messages;
	}

	/**
	 * Returns the meta fields for post type kast
	 * 
	 * @param array $meta Meta fields
	 * @return array $meta
	 */
	function entity_meta( $meta ){
		global $post;

		// Setup value for since meta
		if ( $since = get_post_meta( $post->ID, 'vgsr_entity_kast_since', true ) ){

			// Meta icon
			$meta['since'] = array(
				'icon'   => 'icon-calendar',
				'before' => __('Since', 'vgsr-entity') .': ',
				'value'  => date_i18n( get_option( 'date_format' ), strtotime( str_replace( '/', '-', $since ) ) )
				);
		}

		return $meta;
	}

}

endif; // class_exists

/**
 * Setup VGSR Kast
 */
function vgsr_entity_kast(){
	global $vgsr_entity;

	$vgsr_entity->kast = new VGSR_Entity_Kast();
}

