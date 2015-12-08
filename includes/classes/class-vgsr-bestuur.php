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
			)
		), array(

			// Season
			'season' => array(
				'label' => __( 'Season', 'vgsr-entity' ),
				'type'  => 'year',
				'name'  => 'menu_order'
			)
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

		add_action( 'vgsr_entity_init', array( $this, 'rewrite_rules'     ) );
		add_action( 'admin_init',       array( $this, 'register_settings' ) );

		// Post
		add_action( "save_post_{$this->type}", array( $this, 'save_metabox'         ), 10, 2 );
		add_action( "save_post_{$this->type}", array( $this, 'save_current_bestuur' ), 10, 2 );
		add_filter( 'document_title_parts',    array( $this, 'document_title_parts' )        );

		// Current bestuur
		add_filter( 'display_post_states', array( $this, 'display_post_states' ), 9, 2 );

		// Widgets
		add_filter( "vgsr_{$this->type}_menu_widget_query_args", array( $this, 'widget_menu_order' ) );
	}

	/**
	 * Add additional Bestuur settings fields
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {

		// Bestuur widget menu order setting
		add_settings_field( '_bestuur-menu-order', __( 'Menu Widget Order', 'vgsr-entity' ), array( $this, 'setting_menu_order_field' ), $this->args['settings']['page'], $this->args['settings']['section'] );
		register_setting( $this->args['settings']['page'], '_bestuur-menu-order', 'intval' );
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
		<label for="_bestuur-menu-order"><span class="description"><?php sprintf( __( 'The order in which the %s will be displayed in the Menu Widget.', 'vgsr-entity' ), $this->args['labels']['name'] ); ?></span></label>

		<?php
	}

	/**
	 * Output bestuur details metabox
	 *
	 * @since 1.0.0
	 *
	 * @param object $post The current post
	 */
	public function details_metabox( $post ) {

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
	public function save_metabox( $post_id, $post ) {

		// Bail when doing outosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Bail when this is not our entity
		if ( $post->post_type !== $this->type )
			return;

		// Bail when the user is not capable
		$cpt = get_post_type_object( $this->type );
		if ( ! current_user_can( $cpt->cap->edit_posts ) || ! current_user_can( $cpt->cap->edit_post, $post_id ) )
			return;

		// Bail when the nonce does not verify
		if ( ! isset( $_POST['vgsr_entity_bestuur_meta_nonce'] ) || ! wp_verify_nonce( $_POST['vgsr_entity_bestuur_meta_nonce'], vgsr_entity()->file ) )
			return;

		//
		// Authenticated
		//

		// Update: Season
		if ( isset( $_POST['vgsr_entity_bestuur_season'] ) ) {
			$value = sanitize_text_field( $_POST['vgsr_entity_bestuur_season'] );

			// Does the inserted input match our requirements? - Checks for 1900 - 2099
			if ( ! preg_match( '/^(19\d{2}|20\d{2})\/(19\d{2}|20\d{2})$/', $value, $matches ) ) {

				// Alert the user
				add_filter( 'redirect_post_location', array( $this, 'metabox_season_save_redirect' ) );

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
	public function save_current_bestuur( $post_id, $post ) {

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Check post type
		if ( $post->post_type !== $this->type )
			return;

		// Check caps
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

		// Is not current bestuur
		} else {

			// Nothing changes when it's not published or it's an older bestuur
			if ( 'publish' != $post->post_status || ( $post->menu_order <= get_post( $this->current_bestuur )->menu_order ) ) {
				return;
			}
		}

		// Update current bestuur option
		update_option( '_bestuur-latest-bestuur', $post_id );
		$this->current_bestuur = $post_id;

		// Refresh rewrite rules to properly point to the current bestuur
		add_action( "save_post_{$this->type}", array( $this, 'rewrite_rules' ), 99 );
		add_action( "save_post_{$this->type}", 'flush_rewrite_rules',           99 );
	}

	/**
	 * Define custom rewrite rules
	 *
	 * @since 1.0.0
	 *
	 * @uses get_post_type_object() To find the post type slug for the parent
	 */
	public function rewrite_rules() {

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
		if ( $bestuur = new WP_Query( array(
			'numberposts' => 1,
			'post_type'   => $this->type,
			'post_status' => 'publish',
			'orderby'     => 'menu_order',
		) ) ) {
			return $bestuur->posts[0];
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

	/**
	 * Modify the document title for our entity
	 *
	 * @since 1.1.0
	 *
	 * @uses is_bestuur()
	 * @uses VGSR_Bestuur::get_season()
	 *
	 * @param array $title Title parts
	 * @return array Title parts
	 */
	public function document_title_parts( $title ) {

		// When this is our entity
		if ( is_bestuur() ) {
			$title['title'] .= sprintf( ' (%s)', $this->get_season() );
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
	 * @param int|WP_Post $post
	 * @return mixed Entity meta value
	 */
	public function get( $key, $post = 0 ) {

		// Define local variables
		$post  = get_post( $post );
		$value = null;

		switch ( $key ) {
			case 'season' :
				$value = $post->menu_order;
				$value = sprintf( "%s/%s", $value, $value + 1 );
				break;
		}

		return $value;
	}

	/**
	 * Sanitize the given entity meta value
	 *
	 * @since 1.1.0
	 *
	 * @param string $value Meta value
	 * @param string $key Meta key
	 * @return mixed Meta value
	 */
	public function save( $value, $key ) {

		switch ( $key ) {
			case 'season' :
				// Will be saved through WP's default handling of 'menu_order'
				break;
		}

		return $value;
	}

	/**
	 * Return the season of a given bestuur
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post|int $post Optional. Post object or post ID
	 * @return string Bestuur season
	 */
	public function get_season( $post = 0 ) {
		return $this->get( 'season', $post );
	}
}

endif; // class_exists
