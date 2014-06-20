<?php

/**
 * VGSR Kast class
 *
 * @package VGSR Entity
 * @subpackage Entities
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Include Entity Base Class
if ( ! class_exists( 'VGSR_Entity' ) )
	require( plugin_dir_path( __FILE__ ) . 'vgsr-entity.php' );

if ( ! class_exists( 'VGSR_Kast' ) ) :

/**
 * VGSR Kast Entity Class
 *
 * @since 0.1
 */
class VGSR_Kast extends VGSR_Entity {

	/**
	 * Kast post mini thumbnail size
	 *
	 * @since 0.1
	 * @var int
	 */
	public $mini_size;

	/**
	 * Construct Kast Entity
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( array( 
			'single' => 'Kast', 
			'plural' => 'Kasten' 
		) );
	}

	/** 
	 * Define default Kast globals
	 *
	 * @since 0.1
	 * 
	 * @uses add_image_size()
	 */
	public function setup_globals() {
		$this->thumbsize = 'mini-thumb'; // Parent var
		$this->mini_size = 100;

		add_image_size( $this->thumbsize, $this->mini_size, $this->mini_size, true );
	}

	/**
	 * Setup default Kast actions and filters
	 *
	 * @since 0.1
	 */
	public function setup_actions() {

		// Actions
		add_action( 'admin_init', array( $this, 'register_settings'  ) );
		add_action( 'admin_head', array( $this, 'admin_scripts'      ) );
		add_action( 'save_post',  array( $this, 'metabox_since_save' ) );

		// Filters
		add_filter( "vgsr_{$this->type}_register_cpt",     array( $this, 'post_type_args'  ) );
		add_filter( "vgsr_{$this->type}_settings_load",    array( $this, 'downsize_thumbs' ) );
		add_filter( "vgsr_{$this->type}_settings_scripts", array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Manipulate entity custom post type arguments
	 * 
	 * @since 0.1
	 * 
	 * @param array $args Post type arguments
	 * @return array Args
	 */
	public function post_type_args( $args ) {

		// Rename labels
		$args['labels']['add_new'] = $args['labels']['new_item'] = sprintf( _x( 'New %s', 'In Dutch «New Kast» doesn\'t translate like «New Bestuur».', 'vgsr-entity' ), strtolower( $this->labels->single ) );

		return $args;
	}

	/**
	 * Add additional Kast settings fields
	 * 
	 * @since 0.1
	 */
	public function register_settings() {

		// Kast recreate thumbnail option
		add_settings_field( '_kast-downsize-thumbs', __( 'Recreate Thumbnails', 'vgsr-entity' ), array( $this, 'settings_downsize_thumbs_field' ), $this->settings_page, $this->settings_section );
		register_setting( $this->settings_page, '_kast-downsize-thumbs', 'intval' );
	}

	/**
	 * Output the Kast downsize thumbs settings field
	 * 
	 * @since 0.1
	 */
	public function settings_downsize_thumbs_field() {
	?>

		<input type="checkbox" name="_kast-downsize-thumbs" id="_kast-downsize-thumbs" <?php checked( get_option( '_kast-downsize-thumbs' ) ); ?> value="1"/>
		<label for="_kast-downsize_thumbs"><span class="description"><?php echo sprintf( __( 'This is a one time resizing of thumbs for %s. NOTE: This option only <strong>adds</strong> new image sizes, it doesn\'t remove old ones.', 'vgsr-entity' ), $this->labels->plural ); ?></span></label>

	<?php
	}

	/**
	 * Resize Kast thumbs of all kasten first attachments
	 *
	 * Will only be run if the _kast-downsize-thumbs option is set.
	 * 
	 * @since 0.1
	 *
	 * @uses get_posts()
	 * @uses get_children()
	 * @uses wp_get_attachment_image_src()
	 * @uses image_resize()
	 * @uses wp_get_attachment_metadata()
	 * @uses wp_udpate_attachment_metadata()
	 */
	public function downsize_thumbs() {

		// Only do this if we're asked to
		if ( ! get_option( '_kast-downsize-thumbs' ) )
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
			if ( ! $logo ) 
				continue;

			// Juggling with {$logo} so storing ID separate
			$logo_id = $logo->ID;
			$logo    = wp_get_attachment_image_src( $logo_id, $this->thumbsize );

			if ( $logo[1] == $this->mini_size && $logo[2] == $this->mini_size )
				continue;

			// 
			// No perfect match found so continue to edit images
			//
			
			// Create absolute file path
			$file_path = ABSPATH . substr( dirname( $logo[0] ), ( strpos( $logo[0], parse_url( site_url(), PHP_URL_PATH ) ) + strlen( parse_url( site_url(), PHP_URL_PATH ) ) + 1 ) ) . '/'. basename( $logo[0] );
			
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
	 * Enqueue scripts to the edit kast page
	 * 
	 * @since 0.1
	 *
	 * @uses wp_enqueue_script()
	 * @uses wp_register_style()
	 * @uses wp_enqueue_style()
	 */
	public function enqueue_scripts() {
		global $pagenow, $post;

		// Bail if not on a Kast page
		if ( ! isset( $post ) || ( $this->type != $post->post_type && 'post.php' != $pagenow ) )
			return;

		// Enable jQuery UI Datepicker
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		// Include jQuery UI Theme style
		wp_register_style( 'jquery-ui-theme-fresh', plugins_url( 'css/jquery.ui.theme.css', vgsr_entity()->file ) );
		wp_enqueue_style( 'jquery-ui-theme-fresh' );
	}

	/**
	 * Output custom JS script to the edit kast page
	 * 
	 * @since 0.1
	 */
	public function admin_scripts() {
		global $pagenow, $post;

		if ( isset( $post ) && $post->post_type == $this->type && 'post.php' == $pagenow ) {
			?>
<script type="text/javascript">
	jQuery(document).ready( function($) {
		$('.datepicker').datepicker({
			dateFormat: 'dd/mm/yyyy',
			changeMonth: true,
			changeYear: true
		});
	});
</script>
			<?php
		}
	}

	/**
	 * Add metaboxes to the Kast edit screen
	 *
	 * @since 0.1
	 * 
	 * @uses add_meta_box()
	 */
	public function add_metabox() {

		// Add Kast Data metabox
		add_meta_box(
			"vgsr-entity-{$this->type}",
			__( 'Kast Data', 'vgsr-entity' ),
			array( $this, 'metabox_display' ),
			$this->type,
			'side'
		);
	}

	/**
	 * Output kast meta box
	 * 
	 * @since 0.1
	 *
	 * @uses get_post_meta()
	 * @uses wp_nonce_field()
	 * @uses do_action() Calls 'vgsr_{$this->type}_metabox' hook with the post object
	 * 
	 * @param object $post The current post
	 */
	public function metabox_display( $post ) {

		// Output nonce verification field
		wp_nonce_field( vgsr_entity()->file, 'vgsr_entity_kast_meta_nonce' ); 

		/** Since ******************************************************/

		// Get stored meta value
		$since = get_post_meta( $post->ID, 'vgsr_entity_kast_since', true );

		// If no value served set it empty
		if ( ! $since )
			$since = '';

		?>

		<p id="vgsr_entity_kast_since">

			<label>
				<strong><?php _e( 'Since', 'vgsr-entity' ); ?>: </strong>
				<input class="ui-widget-content ui-corner-all datepicker" type="text" name="vgsr_entity_kast_since" value="<?php echo esc_attr( $since ); ?>" />
			</label>
			<span class="howto"><?php _e( 'The required format is dd/mm/yyyy.', 'vgsr-entity' ); ?></span>

		</p>

		<?php

		/** Ceased *****************************************************/

		// Get stored meta value
		$ceased = get_post_meta( $post->ID, 'vgsr_entity_kast_ceased', true );

		// If no value served set it empty
		if ( ! $ceased )
			$ceased = '';

		?>

		<p id="vgsr_entity_kast_ceased">

			<label>
				<strong><?php _e( 'Ceased', 'vgsr-entity' ); ?>: </strong>
				<input class="ui-widget-content ui-corner-all datepicker" type="text" name="vgsr_entity_kast_ceased" value="<?php echo esc_attr( $ceased ); ?>" />
			</label>
			<span class="howto"><?php _e( 'The required format is dd/mm/yyyy.', 'vgsr-entity' ); ?></span>

		</p>

		<?php

		/** Occupants **************************************************/

		// Get stored meta value
		$occupants = get_post_meta( $post->ID, 'vgsr_entity_kast_occupants', true );

		// If no value served set it empty
		if ( ! $occupants )
			$occupants = '';

		?>

		<p id="vgsr_entity_kast_occupants">

			<label>
				<strong><?php __( 'Occupants', 'vgsr-entity' ); ?>: </strong>
				<input type="text" name="vgsr_entity_kast_occupants" value="<?php echo $occupants; ?>" />
			</label>
			<span class="howto"><?php __( 'The current occupants.', 'vgsr-entity' ); ?></span>

		</p>

		<?php

		/** Previous Occupants *****************************************/

		// Get stored meta value
		$prev_occupants = get_post_meta( $post->ID, 'vgsr_entity_kast_prev_occupants', true );

		// If no value served set it empty
		if ( ! $prev_occupants )
			$prev_occupants = '';

		?>

		<p id="vgsr_entity_kast_prev_occupants">

			<label>
				<strong><?php __( 'Previous Occupants', 'vgsr-entity' ); ?>: </strong>
				<input type="text" name="vgsr_entity_kast_prev_occupants" value="<?php echo $prev_occupants; ?>" />
			</label>
			<span class="howto"><?php __( 'The previous occupants.', 'vgsr-entity' ); ?></span>

		</p>

		<?php

		/** Other ******************************************************/

		do_action( "vgsr_{$this->type}_metabox", $post );
	}

	/**
	 * Save kast since meta field
	 * 
	 * @since 0.1
	 * 
	 * @param int $post_id The post ID
	 */
	public function metabox_since_save( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( $this->type != get_post_type( $post_id ) )
			return;

		$cpt_obj = get_post_type_object( $this->type );

		if (   ! current_user_can( $cpt_obj->cap->edit_posts          ) 
			|| ! current_user_can( $cpt_obj->cap->edit_post, $post_id ) 
			)
			return;

		if ( ! wp_verify_nonce( $_POST['vgsr_entity_kast_meta_nonce'], vgsr_entity()->file ) )
			return;

		// We're authenticated now
		
		$input = sanitize_text_field( $_POST['vgsr_entity_kast_since'] );

		// Does the inserted input match our requirements? - Checks for 01-31 / 01-12 / 1900-2099
		if ( ! preg_match( '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/(19|20)[0-9]{2}$/', $input, $matches ) ) {

			// Alert the user
			add_filter( 'redirect_post_location', array( $this, 'metabox_since_save_redirect' ) );

			$input = false;
		
		// Update post meta
		} else {
			update_post_meta( $post_id, 'vgsr_entity_kast_since', $input );
		}
	}

	/**
	 * Add query arg to the redirect location after save_post()
	 *
	 * @since 0.1
	 *
	 * @uses add_query_arg()
	 * 
	 * @param string $location The redrirect location
	 * @return string $location
	 */
	public function metabox_since_save_redirect( $location ) {
		return add_query_arg( 'kast-error', '1', $location );
	}

	/**
	 * Setup Kast admin error messages
	 * 
	 * @since 0.1
	 */
	public function admin_messages( $messages ) {
		$messages[1] = sprintf( __( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' ), '<strong>'. __( 'Since', 'vgsr-entity' ) . '</strong>' );

		return $messages;
	}

	/**
	 * Returns the meta fields for post type kast
	 *
	 * @since 0.1
	 * 
	 * @param array $meta Meta fields
	 * @return array $meta
	 */
	public function entity_meta( $meta ) {
		global $post;

		// Setup value for since meta
		if ( $since = get_post_meta( $post->ID, 'vgsr_entity_kast_since', true ) ) {

			// Setup kast Since meta
			$meta['since'] = array(
				'icon'   => 'icon-calendar',
				'before' => __( 'Since', 'vgsr-entity' ) . ': ',
				'value'  => date_i18n( get_option( 'date_format' ), strtotime( str_replace( '/', '-', $since ) ) )
			);
		}

		return $meta;
	}

}

endif; // class_exists

/**
 * Setup VGSR Kast Entity
 *
 * @since 0.1
 *
 * @uses VGSR_Kast
 */
function vgsr_entity_kast() {
	vgsr_entity()->kast = new VGSR_Kast;
}

