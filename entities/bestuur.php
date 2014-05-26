<?php

/**
 * VGSR Bestuur Class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Include Entity Base Class
if ( ! class_exists( 'VGSR_Entity' ) )
	require( plugin_dir_path( __FILE__ ) . 'vgsr-entity.php' );

if ( ! class_exists( 'VGSR_Bestuur' ) ) :

/**
 * VGSR Bestuur Entity Class
 *
 * @since 0.1
 */
class VGSR_Bestuur extends VGSR_Entity {

	/**
	 * The latest Bestuur post ID
	 *
	 * @since 0.1
	 * @var int
	 */
	public $latest_bestuur;

	/**
	 * Construct Bestuur Entity
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( array( 
			'single' => 'Bestuur', 
			'plural' => 'Besturen' 
		) );
	}

	/** 
	 * Define default Bestuur globals
	 *
	 * @since 0.1
	 */
	public function setup_globals() {
		$this->latest_bestuur = get_option( '_bestuur-latest-bestuur' );
	}

	/**
	 * Setup default Bestuur actions and filters
	 *
	 * @since 0.1
	 */
	public function setup_actions() {

		add_action( 'init',           array( $this, 'latest_bestuur_rewrite_rule' ) );
		add_action( 'admin_init',     array( $this, 'bestuur_register_settings'   ) );
		add_action( 'save_post',      array( $this, 'latest_bestuur_save_id'      ) );
		add_action( 'save_post',      array( $this, 'metabox_season_save'         ) );

		add_filter( 'vgsr_entity_menu_widget_get_posts',  array( $this, 'widget_menu_order' ) );

		// Undo entity listing on parent page
		remove_filter( 'the_content', array( $this, 'parent_page_add_children'    ) );
	}

	/**
	 * Add additional Bestuur settings fields
	 * 
	 * @since 0.1
	 */
	public function bestuur_register_settings() {

		// Bestuur widget menu order setting
		add_settings_field( '_bestuur-menu-order', __( 'Widget menu order', 'vgsr-entity' ), array( $this, 'setting_menu_order_field' ), $this->settings_page, $this->settings_section );
		register_setting( $this->settings_page, '_bestuur-menu-order', 'intval' );
	}

	/**
	 * Output the Bestuur menu order settings field
	 * 
	 * @since 0.1
	 */
	public function setting_menu_order_field() {
		$value = (int) get_option( '_bestuur-menu-order' );
		?>
			<select name="_bestuur-menu-order" id="_bestuur-menu-order">
				<option value="0" <?php selected( $value, 0 ); ?>><?php _e('Seniority',         'vgsr-entity' ); ?></option>
				<option value="1" <?php selected( $value, 1 ); ?>><?php _e('Reverse seniority', 'vgsr-entity' ); ?></option>
			</select>
			<label for="_bestuur-menu-order"><span class="description"><?php sprintf( __( 'The order in which the %s will be displayed in the Menu Widget.', 'vgsr-entity' ), $this->labels->plural ); ?></span></label>
		<?php
	}

	/**
	 * Add metaboxes to the Bestuur edit screen
	 * 
	 * @since 0.1
	 *
	 * @uses add_meta_box()
	 */
	public function add_metabox() {

		// Add Bestuur Data meta box
		add_meta_box(
			"vgsr-entity-{$this->type}",
			__( 'Bestuur Data', 'vgsr-entity' ),
			array( $this, 'metabox_display' ),
			$this->type,
			'side'
		);
	}

	/**
	 * Output bestuur meta box
	 * 
	 * @since 0.1
	 * 
	 * @param object $post The current post
	 */
	public function metabox_display( $post ) {
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
		echo '<label><strong>' . __( 'Season', 'vgsr-entity' ) . ': </strong><input type="text" name="vgsr_entity_bestuur_season" value="' . $value . '" /></label>';

		// Output field information
		echo '<span class="howto">' . __( 'The required format is yyyy/yyyy.', 'vgsr-entity' ) . '</span>';

		// End field
		echo '</p>';
		
		/** Other Meta **/

		do_action( "vgsr_{$this->type}_metabox", $post );
	}

	/**
	 * Save bestuur season meta field
	 * 
	 * @since 0.1
	 * 
	 * @param int $post_id The post ID
	 */
	public function metabox_season_save( $post_id ) {
		global $vgsr_entity;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( get_post_type( $post_id ) !== $this->type )
			return;

		$cpt_obj = get_post_type_object( $this->type );

		if (   ! current_user_can( $cpt_obj->cap->edit_posts          ) 
			|| ! current_user_can( $cpt_obj->cap->edit_post, $post_id ) 
			)
			return;

		if ( ! wp_verify_nonce( $_POST['vgsr_entity_bestuur_season_nonce'], $vgsr_entity->file ) )
			return;

		// We're authenticated now
		
		$input = sanitize_text_field( $_POST['vgsr_entity_bestuur_season'] );

		// Does the inserted input match our requirements? - Checks for 1900 - 2099
		if ( ! preg_match( '/^(19\d{2}|20\d{2})\/(19\d{2}|20\d{2})$/', $input, $matches ) ) {

			// Alert the user
			add_filter( 'redirect_post_location', array( $this, 'metabox_season_save_redirect' ) );

			$input = false;

		// Update post meta
		} else {
			update_post_meta( $post_id, 'vgsr_entity_bestuur_season', $input );
		}
	}

	/**
	 * Add query arg to the redirect location after save_post()
	 * 
	 * @since 0.1
	 * 
	 * @param string $location The redrirect location
	 * @return string $location
	 */
	public function metabox_season_save_redirect( $location ) {
		return add_query_arg( 'bestuur-error', '1', $location );
	}

	/**
	 * Setup Bestuur admin error messages
	 * 
	 * @since 0.1
	 *
	 * @param array $messages
	 * @return array $messages
	 */
	public function admin_messages( $messages ) {
		$messages[1] = sprintf( __( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' ), '<strong>' . __( 'Season', 'vgsr-entity' ) . '</strong>' );

		return $messages;
	}

	/**
	 * Checks for the latest bestuur to be still correct
	 *
	 * We only do this when a new bestuur gets saved
	 * 
	 * @since 0.1
	 */
	public function latest_bestuur_save_id( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( get_post_type( $post_id ) !== $this->type )
			return;

		$cpt_obj = get_post_type_object( $this->type );

		if (   ! current_user_can( $cpt_obj->cap->edit_posts          ) 
			|| ! current_user_can( $cpt_obj->cap->edit_post, $post_id ) 
			)
			return;

		if ( $post_id == $this->latest_bestuur )
			return;

		$current_bestuur = get_post( $post_id );
		$latest_bestuur  = get_post( $this->latest_bestuur );

		// Can be silent for a year
		if ( $current_bestuur->menu_order <= $latest_bestuur->menu_order )
			return;

		// Let's do this, the menu order is higher, so save it!
		update_option( '_bestuur-latest-bestuur', $post_id );
		$this->latest_bestuur = $post_id;

		// Reset rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Reroutes requests for the parent page to the latest bestuur
	 * 
	 * @since 0.1
	 * 
	 * @uses get_post_type_object() To find the post type slug for the parent
	 */
	public function latest_bestuur_rewrite_rule() {
		add_rewrite_rule( 
			get_post_type_object( $this->type )->rewrite['slug'] . '/?$', // The parent page ...
			'index.php?p=' . $this->latest_bestuur, // ... appears to be the latest Bestuur
			'top'
		);
	}

	/**
	 * Returns the latest (or current) bestuur
	 *
	 * @since 0.1
	 * 
	 * @return object|boolean Post object on success, false if not found
	 */
	public function get_latest_bestuur() {

		// Get the latest bestuur
		$bestuur = get_posts( array(
			'numberposts' => 1,
			'post_type'   => $this->type,
			'orderby'     => 'menu_order'
		) );

		if ( $bestuur )
			return $bestuur[0];

		return false;
	}

	/**
	 * Returns the meta fields for post type bestuur
	 *
	 * @since 0.1
	 * 
	 * @param array $meta Meta fields
	 * @return array $meta
	 */
	public function entity_meta( $meta ) {
		global $post;

		// Setup value for season meta
		if ( $season = get_post_meta( $post->ID, 'vgsr_entity_bestuur_season', true ) ) {

			// Meta icon
			$meta['season'] = array(
				'icon'   => 'icon-calendar',
				'before' => __( 'Season', 'vgsr-entity' ) . ': ',
				'value'  => $season
			);
		}

		return $meta;
	}

	/**
	 * Manipulate Entity Menu Widget posts arguments
	 *
	 * @since 0.1
	 * 
	 * @param array $args The arguments for get_posts()
	 * @return array $args
	 */
	public function widget_menu_order( $args ) {
		$args['order'] = get_option( '_bestuur-menu-order' ) ? 'DESC' : 'ASC';

		return $args;
	}

}

endif; // class_exists

/**
 * Setup VGSR Bestuur Entity
 *
 * @since 0.1
 *
 * @uses VGSR_Bestuur
 */
function vgsr_entity_bestuur() {
	global $vgsr_entity;

	$vgsr_entity->bestuur = new VGSR_Bestuur();
}

