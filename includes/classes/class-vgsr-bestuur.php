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
class VGSR_Bestuur extends VGSR_Entity_Base {

	/**
	 * Holds the current Bestuur post ID
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $current_bestuur;

	/**
	 * Construct Bestuur Entity
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( 'bestuur', array(
			'menu_icon' => 'dashicons-awards',
			'labels'    => array(
				'name'               => __( 'Besturen',                   'vgsr-entity' ),
				'singular_name'      => __( 'Bestuur',                    'vgsr-entity' ),
				'add_new'            => __( 'New Bestuur',                'vgsr-entity' ),
				'add_new_item'       => __( 'Add new Bestuur',            'vgsr-entity' ),
				'edit_item'          => __( 'Edit Bestuur',               'vgsr-entity' ),
				'new_item'           => __( 'New Bestuur',                'vgsr-entity' ),
				'all_items'          => __( 'All Besturen',               'vgsr-entity' ),
				'view_item'          => __( 'View Bestuur',               'vgsr-entity' ),
				'search_items'       => __( 'Search Besturen',            'vgsr-entity' ),
				'not_found'          => __( 'No Besturen found',          'vgsr-entity' ),
				'not_found_in_trash' => __( 'No Besturen found in trash', 'vgsr-entity' ),
				'menu_name'          => __( 'Besturen',                   'vgsr-entity' ),
				'settings_title'     => __( 'Besturen Settings',          'vgsr-entity' ),
			),

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
			2 => sprintf( esc_html__( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' ), '<strong>' . __( 'Season', 'vgsr-entity' ) . '</strong>' ),
		) );
	}

	/**
	 * Define default Bestuur globals
	 *
	 * @since 1.0.0
	 */
	public function setup_globals() {
		$this->current_bestuur = get_option( '_bestuur-latest-bestuur' );
	}

	/**
	 * Setup default Bestuur actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		add_action( 'vgsr_entity_init',            array( $this, 'add_rewrite_rules'   ) );
		add_action( 'vgsr_entity_settings_fields', array( $this, 'add_settings_fields' ) );

		// Post
		add_action( "save_post_{$this->type}", array( $this, 'save_current_bestuur' ), 10, 2 );
		add_filter( 'display_post_states',     array( $this, 'display_post_states'  ),  9, 2 );

		// Theme
		add_filter( 'document_title_parts',                      array( $this, 'document_title_parts' ) );
		add_filter( "vgsr_{$this->type}_menu_widget_query_args", array( $this, 'widget_menu_order'    ) );
	}

	/** Settings ***************************************************/

	/**
	 * Add additional Bestuur settings fields
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Settings fields
	 * @return array Settings fields
	 */
	public function add_settings_fields( $fields ) {

		// Menu Order
		$fields['main_settings']['menu-order'] = array(
			'title'             => __( 'Menu Widget Order', 'vgsr-entity' ),
			'callback'          => array( $this, 'setting_menu_order_field' ),
			'sanitize_callback' => 'intval',
			'entity'            => $this->type,
			'args'              => array(),
		);

		return $fields;
	}

	/**
	 * Output the Bestuur menu order settings field
	 *
	 * @since 1.0.0
	 */
	public function setting_menu_order_field() {

		// Define local variables
		$option_name = "_{$this->type}-menu-order";
		$value       = (int) get_option( $option_name ); ?>

		<select name="<?php echo esc_attr( $option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>">
			<option value="0" <?php selected( $value, 0 ); ?>><?php _e( 'Seniority',         'vgsr-entity' ); ?></option>
			<option value="1" <?php selected( $value, 1 ); ?>><?php _e( 'Reverse seniority', 'vgsr-entity' ); ?></option>
		</select>

		<p for="<?php echo esc_attr( $option_name ); ?>" class="description"><?php printf( __( 'The order in which the %s will be displayed in the Menu Widget.', 'vgsr-entity' ), $this->args['labels']['name'] ); ?></p>

		<?php
	}

	/** Current Bestuur ********************************************/

	/**
	 * Checks for the latest bestuur to be still correct
	 *
	 * We only do this when a new bestuur gets saved
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post data
	 */
	public function save_current_bestuur( $post_id, $post ) {

		// Bail when doing autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Bail when the user is not capable
		$cpt = get_post_type_object( $this->type );
		if ( ! current_user_can( $cpt->cap->edit_posts ) || ! current_user_can( $cpt->cap->edit_post, $post_id ) )
			return;

		// Check if this bestuur is already known as the current one
		if ( $post_id == $this->current_bestuur ) {

			// Bail when status isn't changed
			if ( 'publish' == $post->post_status )
				return;

			// Find the current bestuur
			if ( $_post = $this->get_current_bestuur() ) {
				$post_id = $_post->ID;

			// Default to 0
			} else {
				$post_id = 0;
			}

		// Bail when when the post is not published or is an older bestuur
		} elseif ( 'publish' != $post->post_status || ( $post->menu_order <= get_post( $this->current_bestuur )->menu_order ) ) {
			return;
		}

		// Update current bestuur
		update_option( '_bestuur-latest-bestuur', $post_id );
		$this->current_bestuur = $post_id;

		// Refresh rewrite rules to properly point to the current bestuur
		add_action( "save_post_{$this->type}", array( $this, 'add_rewrite_rules' ), 99 );
		add_action( "save_post_{$this->type}", 'flush_rewrite_rules',               99 );
	}

	/**
	 * Define custom rewrite rules
	 *
	 * @since 1.0.0
	 *
	 * @uses get_post_type_object() To find the post type slug for the parent
	 */
	public function add_rewrite_rules() {

		// Redirect requests for the entity parent page to the current bestuur
		if ( $this->current_bestuur ) {
			add_rewrite_rule(
				// The parent page ...
				get_post_type_object( $this->type )->rewrite['slug'] . '/?$',
				// ... should be interpreted as the current Bestuur
				'index.php?p=' . $this->current_bestuur,
				'top'
			);
		}
	}

	/**
	 * Returns the current (or current) bestuur
	 *
	 * @since 1.0.0
	 *
	 * @uses WP_Query
	 * @return WP_Post|bool Post object on success, false if not found
	 */
	public function get_current_bestuur() {

		// Get the current bestuur
		if ( $query = new WP_Query( array(
			'posts_per_page' => 1,
			'post_type'      => $this->type,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
		) ) ) {
			return $query->posts[0];
		}

		return false;
	}

	/**
	 * Mark which bestuur is the current one
	 *
	 * @since 1.0.0
	 *
	 * @param array $states Post states
	 * @param object $post Post object
	 * @return array Post states
	 */
	public function display_post_states( $states, $post ) {

		// Bestuur is the current one
		if ( $post->post_type === $this->type && $post->ID == $this->current_bestuur ) {
			$states['current'] = __( 'Current', 'vgsr-entity' );
		}

		return $states;
	}

	/** Theme **********************************************************/

	/**
	 * Modify the document title for our entity
	 *
	 * @since 1.1.0
	 *
	 * @uses is_bestuur()
	 * @uses VGSR_Bestuur::get()
	 *
	 * @param array $title Title parts
	 * @return array Title parts
	 */
	public function document_title_parts( $title ) {

		// When this is our entity
		if ( is_bestuur() ) {
			/* translators: 1. Bestuur title, 2. Bestuur season */
			$title['title'] = sprintf( __( '%1$s (%2$s)', 'vgsr-entity' ), $title['title'], $this->get( 'season' ) );
		}

		return $title;
	}

	/**
	 * Manipulate Entity Menu Widget posts arguments
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The arguments for get_posts()
	 * @return array $args
	 */
	public function widget_menu_order( $args ) {

		// Define query order
		$args['order'] = get_option( '_bestuur-menu-order' ) ? 'DESC' : 'ASC';

		return $args;
	}

	/** Meta ***********************************************************/

	/**
	 * Return the requested entity meta value
	 *
	 * @since 1.1.0
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
			}
		}

		return $value;
	}

	/**
	 * Sanitize the given entity meta value
	 *
	 * @since 1.1.0
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
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $value ), array( 'ID' => $post->ID ), array( '%d' ), array( '%d' ) );
				}

				break;
			default :
				$value = parent::save( $key, $value, $post );
		}

		return $value;
	}
}

endif; // class_exists
