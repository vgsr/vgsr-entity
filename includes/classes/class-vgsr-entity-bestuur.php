<?php

/**
 * VGSR Bestuur Class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Entity_Bestuur' ) ) :
/**
 * VGSR Bestuur Entity Class
 *
 * @since 1.0.0
 */
class VGSR_Entity_Bestuur extends VGSR_Entity_Base {

	/**
	 * The latest Bestuur post ID
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $latest_bestuur;

	/**
	 * Construct Bestuur Entity
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( array(
			'single'    => 'Bestuur',
			'plural'    => 'Besturen',
			'menu_icon' => 'dashicons-awards',
		) );
	}

	/**
	 * Define default Bestuur globals
	 *
	 * @since 1.0.0
	 */
	public function setup_globals() {
		$this->latest_bestuur = get_option( '_bestuur-latest-bestuur' );
	}

	/**
	 * Setup default Bestuur actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		add_action( 'vgsr_entity_init', array( $this, 'add_bestuur_rewrite_rule'  )        );
		add_action( 'admin_init',       array( $this, 'bestuur_register_settings' )        );
		add_action( 'save_post',        array( $this, 'latest_bestuur_save_id'    ), 10, 2 );
		add_action( 'save_post',        array( $this, 'bestuur_metabox_save'      ), 10, 2 );

		// Mark the current bestuur
		add_filter( 'display_post_states', array( $this, 'display_post_states' ), 9, 2 );

		// Entity Widget
		add_filter( 'vgsr_entity_menu_widget_get_posts', array( $this, 'widget_menu_order' ) );
	}

	/**
	 * Add additional Bestuur settings fields
	 *
	 * @since 1.0.0
	 */
	public function bestuur_register_settings() {

		// Bestuur widget menu order setting
		add_settings_field( '_bestuur-menu-order', __( 'Widget menu order', 'vgsr-entity' ), array( $this, 'setting_menu_order_field' ), $this->settings_page, $this->settings_section );
		register_setting( $this->settings_page, '_bestuur-menu-order', 'intval' );
	}

	/**
	 * Output the Bestuur menu order settings field
	 *
	 * @since 1.0.0
	 */
	public function setting_menu_order_field() {
		$value = (int) get_option( '_bestuur-menu-order' ); ?>

		<select name="_bestuur-menu-order" id="_bestuur-menu-order">
			<option value="0" <?php selected( $value, 0 ); ?>><?php _e( 'Seniority',         'vgsr-entity' ); ?></option>
			<option value="1" <?php selected( $value, 1 ); ?>><?php _e( 'Reverse seniority', 'vgsr-entity' ); ?></option>
		</select>
		<label for="_bestuur-menu-order"><span class="description"><?php sprintf( __( 'The order in which the %s will be displayed in the Menu Widget.', 'vgsr-entity' ), $this->args->plural ); ?></span></label>

		<?php
	}

	/**
	 * Add metaboxes to the Bestuur edit screen
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param object $post The current post
	 */
	public function metabox_display( $post ) {

		// Output nonce verification field
		wp_nonce_field( vgsr_entity()->file, 'vgsr_entity_bestuur_meta_nonce' );

		/** Season *****************************************************/

		// Get stored meta value
		$season = get_post_meta( $post->ID, 'vgsr_entity_bestuur_season', true );

		// If no value served set it empty
		if ( ! $season )
			$season = '';

		?>

		<p id="vgsr_entity_bestuur_season">

			<label>
				<strong><?php _e( 'Season', 'vgsr-entity' ); ?>:</strong>
				<input type="text" name="vgsr_entity_bestuur_season" value="<?php echo esc_attr( $season ); ?>" placeholder="yyyy/yyyy" />
			</label>

		</p>

		<?php

		/** Members ****************************************************/

		// Get stored meta value
		$members = get_post_meta( $post->ID, 'vgsr_entity_bestuur_members', true );

		// If no value served set it empty
		if ( ! $members )
			$members = '';

		?>

		<p id="vgsr_entity_bestuur_members">

			<label>
				<strong><?php _e( 'Members', 'vgsr-entity' ); ?>:</strong><br/>
				<textarea name="vgsr_entity_bestuur_members" rows="4" placeholder="Voornaam Achternaam, Praeses"><?php echo esc_textarea( $members ); ?></textarea>
			</label>

		</p>

		<?php

		do_action( "vgsr_{$this->type}_metabox", $post );
	}

	/**
	 * Save bestuur season meta field
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID
	 * @param object $post Post data
	 */
	public function bestuur_metabox_save( $post_id, $post ) {

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Check post type
		if ( $post->post_type !== $this->type )
			return;

		// Check caps
		$pto = get_post_type_object( $this->type );
		if ( ! current_user_can( $pto->cap->edit_posts ) || ! current_user_can( $pto->cap->edit_post, $post_id ) )
			return;

		// Check nonce
		if ( ! isset( $_POST['vgsr_entity_bestuur_meta_nonce'] ) || ! wp_verify_nonce( $_POST['vgsr_entity_bestuur_meta_nonce'], vgsr_entity()->file ) )
			return;

		//
		// Authenticated
		//

		// Season
		if ( isset( $_POST['vgsr_entity_bestuur_season'] ) ) {
			$value = sanitize_text_field( $_POST['vgsr_entity_bestuur_season'] );

			// Does the inserted input match our requirements? - Checks for 1900 - 2099
			if ( ! preg_match( '/^(19\d{2}|20\d{2})\/(19\d{2}|20\d{2})$/', $value, $matches ) ) {

				// Alert the user
				add_filter( 'redirect_post_location', array( $this, 'metabox_season_save_redirect' ) );
				$value = false;

			// Update post meta
			} else {
				update_post_meta( $post_id, 'vgsr_entity_bestuur_season', $value );
			}
		}
	}

	/**
	 * Add query arg to the redirect location after save_post()
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post data
	 */
	public function latest_bestuur_save_id( $post_id, $post ) {

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Check post type
		if ( $post->post_type !== $this->type )
			return;

		// Check caps
		$pto = get_post_type_object( $this->type );
		if ( ! current_user_can( $pto->cap->edit_posts ) || ! current_user_can( $pto->cap->edit_post, $post_id ) )
			return;

		// Check if this bestuur is already known as the latest one
		if ( $post_id == $this->latest_bestuur ) {

			// Bail if status isn't changed
			if ( 'publish' == $post->post_status )
				return;

			// Find now latest bestuur
			if ( $_post = $this->get_latest_bestuur() ) {
				$post_id = $_post->ID;

			// Default to 0
			} else {
				$post_id = 0;
			}

		// Is not latest bestuur
		} else {

			// Nothing changes when it's not published or it's an older bestuur
			if ( 'publish' != $post->post_status || ( $post->menu_order <= get_post( $this->latest_bestuur )->menu_order ) ) {
				return;
			}
		}

		// Update latest bestuur option
		update_option( '_bestuur-latest-bestuur', $post_id );
		$this->latest_bestuur = $post_id;

		// Overwrite latest bestuur rule
		$this->add_bestuur_rewrite_rule();

		// Reset rewrite rules to properly point to the latest bestuur
		add_action( 'save_post', 'flush_rewrite_rules', 99 );
	}

	/**
	 * Redirect requests for the entity parent page to the latest bestuur
	 *
	 * @since 1.0.0
	 *
	 * @uses get_post_type_object() To find the post type slug for the parent
	 */
	public function add_bestuur_rewrite_rule() {

		// Point parent page to latest bestuur
		if ( $this->latest_bestuur ) {
			add_rewrite_rule(
				get_post_type_object( $this->type )->rewrite['slug'] . '/?$', // The parent page ...
				'index.php?p=' . $this->latest_bestuur, // ... appears to be the latest Bestuur
				'top'
			);
		}
	}

	/**
	 * Returns the latest (or current) bestuur
	 *
	 * @since 1.0.0
	 *
	 * @uses get_posts()
	 * @return WP_Post|bool Post object on success, false if not found
	 */
	public function get_latest_bestuur() {

		// Get the latest bestuur
		if ( $bestuur = get_posts( array(
			'numberposts' => 1,
			'post_type'   => $this->type,
			'post_status' => 'publish',
			'orderby'     => 'menu_order',
		) ) ) {
			return $bestuur[0];
		}

		return false;
	}

	/**
	 * Show which bestuur is the current one by appending a 'Current'
	 * post state
	 *
	 * @since 1.0.0
	 *
	 * @param array $states Post states
	 * @param object $post Post object
	 * @return array Post states
	 */
	public function display_post_states( $states, $post ) {

		// Bestuur is the latest one
		if ( $post->post_type == $this->type && $post->ID == $this->latest_bestuur ) {
			$states['current'] = __( 'Current', 'vgsr-entity' );
		}

		return $states;
	}

	/**
	 * Returns the meta fields for post type bestuur
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta Meta fields
	 * @return array $meta
	 */
	public function entity_display_meta( $meta ) {
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
	 * @since 1.0.0
	 *
	 * @param array $args The arguments for get_posts()
	 * @return array $args
	 */
	public function widget_menu_order( $args ) {
		$args['order'] = get_option( '_bestuur-menu-order' ) ? 'DESC' : 'ASC';
		return $args;
	}

	/** Bestuur Meta ***************************************************/

	/**
	 * Return the season of a given bestuur
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post|int $post Optional. Post object or post ID
	 * @return string Bestuur season
	 */
	public function get_season( $post = 0 ) {
		if ( ( ! $post = get_post( $post ) ) || $this->type != $post->post_type )
			return;

		return get_post_meta( $post->ID, 'vgsr_entity_bestuur_season', true );
	}
}

endif; // class_exists
