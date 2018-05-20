<?php

/**
 * VGSR Bestuur Class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Bestuur' ) ) :
/**
 * VGSR Bestuur Entity Class
 *
 * @since 1.0.0
 */
class VGSR_Bestuur extends VGSR_Entity_Type {

	/**
	 * Construct Bestuur Entity
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Entity type name
	 */
	public function __construct( $type ) {
		parent::__construct( $type, array(
			'path'           => 'besturen',
			'post_type_args' => array(
				'menu_icon' => 'dashicons-awards',
			),
			'admin_class'    => 'VGSR_Bestuur_Admin'

		// Meta
		), array(

			// Season
			'season' => array(
				'column_title' => esc_html__( 'Season',    'vgsr-entity' ),
				'label'        => esc_html__( 'Season %s', 'vgsr-entity' ),
				'type'         => 'year',
				'name'         => 'menu_order',
				'display'      => true,
			)

		// Errors
		), array(
			2 => sprintf( esc_html__( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' ), '<strong>' . esc_html__( 'Season', 'vgsr-entity' ) . '</strong>' ),
		) );
	}

	/**
	 * Include required files
	 *
	 * @since 2.0.0
	 *
	 * @param array $includes See VGSR_Entity_Type::includes() for description.
	 */
	public function includes( $includes = array() ) {

		// Default
		$includes = array(
			'actions',
			'functions',
			'template',
		);

		// Admin
		if ( is_admin() ) {
			$includes[] = 'admin';
			$includes[] = 'settings';
		}

		parent::includes( $includes );
	}

	/**
	 * Setup default Bestuur actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		// Current Bestuur
		add_action( "save_post_{$this->post_type}", array( $this, 'save_current_bestuur' ), 10, 2 );
		add_action( 'vgsr_entity_init',             array( $this, 'add_rewrite_rules'    )        );

		// Menus
		add_filter( "nav_menu_items_{$this->post_type}", 'vgsr_entity_bestuur_nav_menu_items_metabox', 10, 3 );

		parent::setup_actions();
	}

	/** Current Bestuur ********************************************/

	/**
	 * Updates the current bestuur setting
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param WP_Post $post Post data
	 */
	public function save_current_bestuur( $post_id, $post ) {

		// Bail when doing autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		$post_type_object = get_post_type_object( $this->post_type );

		// Bail when the user is not capable
		if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->edit_post, $post_id ) )
			return;

		// Update current bestuur
		vgsr_entity_update_current_bestuur();

		// Refresh rewrite rules to properly point to the current bestuur
		add_action( "save_post_{$this->post_type}", array( $this, 'add_rewrite_rules' ), 99 );
		add_action( "save_post_{$this->post_type}", 'flush_rewrite_rules',               99 );
	}

	/**
	 * Define custom rewrite rules
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_rules() {

		// Redirect requests for the entity parent page to the current bestuur
		if ( $current = vgsr_entity_get_current_bestuur() ) {
			add_rewrite_rule(
				// The parent page ...
				get_post_type_object( $this->post_type )->rewrite['slug'] . '/?$',
				// ... should be interpreted as the current Bestuur
				'index.php?p=' . $current,
				'top'
			);
		}
	}

	/** Meta ***********************************************************/

	/**
	 * Return the requested entity meta value
	 *
	 * @since 2.0.0
	 *
	 * @param string $key
	 * @param int|WP_Post $post Optional. Defaults to current post.
	 * @param string $context Optional. Context, defaults to 'display'.
	 * @return mixed Entity meta value
	 */
	public function get( $key, $post = 0, $context = 'display' ) {

		// Define local variables
		$value   = null;
		$display = ( 'display' === $context );

		if ( $post = get_post( $post ) ) {
			switch ( $key ) {
				case 'season' :
					$value = $post->menu_order;
					if ( $display ) {
						$value = sprintf( "%s/%s", $value, $value + 1 );
					}
					break;
				default :
					$value = parent::get( $key, $post, $context );
			}
		}

		return $value;
	}

	/**
	 * Sanitize the given entity meta value
	 *
	 * @since 2.0.0
	 *
	 * @uses $wpdb WPDB
	 *
	 * @param string $key Meta key
	 * @param string $value Meta value
	 * @param WP_Post $post Post object
	 * @return mixed Meta value
	 */
	public function save( $key, $value, $post ) {
		global $wpdb;

		// Basic input sanitization
		$value = sanitize_text_field( $value );

		switch ( $key ) {
			case 'season' :
				$value = intval( $value );

				// When saving a post, WP handles 'menu_order' by default
				if ( 'save_post' != current_filter() ) {
					$wpdb->update(
						$wpdb->posts,
						array( 'menu_order' => $value ),
						array( 'ID' => $post->ID ),
						array( '%d' ),
						array( '%d' )
					);
				}

				break;
			default :
				$value = parent::save( $key, $value, $post );
		}

		return $value;
	}
}

endif; // class_exists
