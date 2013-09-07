<?php

/**
 * VGSR Bestuur class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'VGSR_Entity' ) )
	require( plugin_dir_path( __FILE__ ) .'vgsr-entity.php' );

if ( !class_exists( 'VGSR_Entity_Bestuur' ) ) :

/**
 * Plugin class
 */
class VGSR_Entity_Bestuur extends VGSR_Entity {

	public $latest;

	/**
	 * Construct Bestuur
	 */
	function __construct(){
		parent::__construct( array( 
			'single' => 'Bestuur', 
			'multi'  => 'Besturen' 
			) );
	}

	/** 
	 * Setup class globals 
	 */
	function setup_globals(){
		$this->latest = get_option( '_bestuur-latest-bestuur' );
	}

	/**
	 * Setup class actions
	 *
	 * @uses add_filter()
	 * @return void
	 */
	function setup_actions(){

		add_action( 'init',           array( $this, 'latest_bestuur_rewrite_rule' ) );
		add_action( 'admin_init',     array( $this, 'bestuur_register_settings'   ) );
		add_action( 'save_post',      array( $this, 'latest_bestuur_save_id'      ) );
		add_action( 'save_post',      array( $this, 'metabox_season_save'         ) );

		add_filter( 'vgsr_entity_menu_widget_get_posts',  array( $this, 'widget_menu_order' ) );

		// Undo entity listing on parent page
		remove_filter( 'the_content', array( $this, 'parent_page_add_children'    ) );
	}

	/**
	 * Add additional settings to the entity settings admin page
	 * 
	 * @return void
	 */
	function bestuur_register_settings(){

		// Enable Widger menu order setting
		add_settings_field( '_bestuur-menu-order', __('Widget menu order', 'vgsr-entity' ), array( $this, 'setting_menu_order_field' ), $this->settings_page, $this->settings_section );
		register_setting( $this->settings_page, '_bestuur-menu-order', 'intval' );
	}

	/**
	 * Output the _bestuur-menu-order settings field
	 * 
	 * @return void
	 */
	function setting_menu_order_field(){
		$value = get_option( '_bestuur-menu-order' );

		if ( !$value )
			$value = 0;

		echo '<label><select name="_bestuur-menu-order">';
		echo 	'<option value="0" '. selected( $value, 0, false ) .'>'. __('Seniority', 'vgsr-entity') .'</option>';
		echo 	'<option value="1" '. selected( $value, 1, false ) .'>'. __('Reverse seniority', 'vgsr-entity') .'</option>';
		echo '</select>';

		echo ' <span class="description">'. sprintf( __('The order in which the %s will be displayed in the Menu Widget.', 'vgsr-entity' ), $this->labels['multi'] ) .'</span></label>';
	}

	/**
	 * Add metaboxes to the edit bestuur screen
	 * 
	 * @return void
	 */
	function metabox_cb(){

		// Add Bestuur Data meta box
		add_meta_box(
			'vgsr-entity-'. $this->type,
			__('Bestuur Data', 'vgsr-entity'),
			array( $this, 'metabox_display' ),
			$this->type,
			'side'
			);
	}

	/**
	 * Output bestuur meta box
	 * 
	 * @param object $post The current post
	 * @return void
	 */
	function metabox_display( $post ){
		global $vgsr_entity;

		/** Season Meta **/

			// Get stored meta value
			$value = get_post_meta( $post->ID, 'vgsr_entity_bestuur_season', true );

			// If no value served set it empty
			if ( !$value )
				$value = '';

			// Output nonce verification field
			wp_nonce_field( $vgsr_entity->file, 'vgsr_entity_bestuur_season_nonce' );

			// Start field
			echo '<p id="vgsr_entity_bestuur_season">';

			// Output input field
			echo '<label><strong>'. __('Season', 'vgsr-entity') .': </strong><input type="text" name="vgsr_entity_bestuur_season" value="'. $value .'" /></label>';

			// Output field information
			echo '<span class="howto">'. __('The required format is yyyy/yyyy.', 'vgsr-entity') .'</span>';

			// End field
			echo '</p>';
		
		/** Other Meta **/

		do_action( 'vgsr_entity_'. $this->type .'_metabox', $post );
	}

	/**
	 * Save bestuur season meta field
	 * 
	 * @param int $post_id The post ID
	 * @return void
	 */
	function metabox_season_save( $post_id ){
		global $vgsr_entity;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( get_post_type( $post_id ) !== $this->type )
			return;

		$cpt_obj = get_post_type_object( $this->type );

		if ( !current_user_can( $cpt_obj->cap->edit_posts ) || !current_user_can( $cpt_obj->cap->edit_post, $post_id ) )
			return;

		if ( !wp_verify_nonce( $_POST['vgsr_entity_bestuur_season_nonce'], $vgsr_entity->file ) )
			return;

		// We're authenticated now
		
		$input = sanitize_text_field( $_POST['vgsr_entity_bestuur_season'] );

		// Does the inserted input match our requirements? - Checks for 1900 - 2099
		if ( !preg_match( '/^(19\d{2}|20\d{2})\/(19\d{2}|20\d{2})$/', $input, $matches ) ){

			// Alert the user
			add_filter( 'redirect_post_location', array( $this, 'metabox_season_save_redirect' ) );

			$input = false;
		}

		// Update post meta
		else {
			update_post_meta( $post_id, 'vgsr_entity_bestuur_season', $input );
		}
	}

	/**
	 * Add query arg to the redirect location after save_post()
	 * 
	 * @param string $location The redrirect location
	 * @return string $location
	 */
	function metabox_season_save_redirect( $location ){
		return add_query_arg( 'bestuur-error', '1', $location );
	}

	/**
	 * Output the admin message for the given bestuur error
	 * 
	 * @return void
	 */
	function admin_messages( $messages ){

		// Set up post messages
		$messages[1] = sprintf( __('The submitted value for %s is not given in the valid format.', 'vgsr-entity'), '<strong>'. __('Season', 'vgsr-entity') .'</strong>' );

		return $messages;
	}

	/**
	 * Checks for the latest bestuur to be still correct
	 *
	 * We only do this when a new bestuur gets saved
	 * 
	 * @return void
	 */
	function latest_bestuur_save_id( $post_id ){

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( get_post_type( $post_id ) !== $this->type )
			return;

		$cpt_obj = get_post_type_object( $this->type );

		if ( !current_user_can( $cpt_obj->cap->edit_posts ) || !current_user_can( $cpt_obj->cap->edit_post, $post_id ) )
			return;

		if ( $this->latest === $post_id )
			return;

		$post_new = get_post( $post_id );
		$post_old = get_post( $this->latest );

		// Can be silent for a year
		if ( $post_new->menu_order <= $post_old->menu_order )
			return;

		// Let's do this, the menu order is higher, so save it!
		update_option( '_bestuur-latest-bestuur', $post_id );

		// Reset rewrite rules
		flush_rewrite_rules();

		$this->latest = $post_id;
	}

	/**
	 * Reroutes requests for the parent page to the latest bestuur
	 * 
	 * @uses get_post_type_object() To find the post type slug to the parent
	 * @return void
	 */
	function latest_bestuur_rewrite_rule(){
		// Here we can find the custom post type parent slug
		$cpt_obj = get_post_type_object( $this->type );

		// Rewriting
		add_rewrite_rule( 
			$cpt_obj->rewrite['slug'] .'/?$', // The parent page
			'index.php?p='. $this->latest, // Appears to be the latest bestuur
			'top' // Priority
			);
	}

	/**
	 * Returns the latest (or current) bestuur
	 * 
	 * @return object|boolean Post object on success, false if not found
	 */
	function get_latest_bestuur(){

		// Get the latest bestuur
		$bestuur = get_posts( array(
			'numberposts' => 1,
			'post_type'   => $this->type,
			'orderby'     => 'menu_order'
			) );

		if ( $bestuur )
			return $bestuur[0];
		else
			return false;
	}

	/**
	 * Returns the meta fields for post type bestuur
	 * 
	 * @param array $meta Meta fields
	 * @return array $meta
	 */
	function entity_meta( $meta ){
		global $post;

		// Setup value for season meta
		if ( $season = get_post_meta( $post->ID, 'vgsr_entity_bestuur_season', true ) ){

			// Meta icon
			$meta['season'] = array(
				'icon'   => 'icon-calendar',
				'before' => __('Season', 'vgsr-entity') .': ',
				'value'  => $season
				);
		}

		return $meta;
	}

	/**
	 * Alter the Menu Widget order as set
	 * 
	 * @param array $args The arguments for get_posts()
	 * @return array $args
	 */
	function widget_menu_order( $args ){

		$order = get_option( '_bestuur-menu-order' );

		// Set order arg
		$args['order'] = $order && 0 != $order ? 'DESC' : 'ASC';

		return $args;
	}

}

endif; // class_exists

/**
 * Setup VGSR Bestuur
 */
function vgsr_entity_bestuur(){
	global $vgsr_entity;

	$vgsr_entity->bestuur = new VGSR_Entity_Bestuur();
}

