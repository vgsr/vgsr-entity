<?php

/**
 * Plugin Name: VGSR Entity
 * Plugin URI: http://www.offereinspictures.nl/wp-plugins/vgsr-entity/
 * Description: Plugin voor VGSR entiteiten zoals besturen, disputen, kasten, commissies
 * Version: 0.0.1
 * Author: Laurens Offereins
 * Author URI: http://www.offereinspictures.nl
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'VGSR_Entity' ) ) :

class VGSR_Entity {

	static $type;
	static $hook;
	static $labels;
	static $page;
	static $settings_title;
	static $parent;
	static $thumbsize;

	static $settings_page;
	static $settings_section;

	/**
	 * Construct the VGSR Entity
	 */
	function __construct( $labels ){
		$this->labels = $labels;

		// Setup defaults
		$this->entity_globals();
		$this->entity_requires();
		$this->entity_actions();

		// Setup child class
		$this->setup_globals();
		$this->setup_requires();
		$this->setup_actions();
	}

	/**
	 * Create class globals
	 */
	function entity_globals(){
		$this->type           = strtolower( $this->labels['single'] );
		$this->hook           = 'edit.php?post_type='. $this->type;
		$this->settings_title = $this->labels['single'] .' '. __('Settings');
		$this->parent         = '_'. $this->type .'-parent-page';
		$this->thumbsize      = 'post-thumbnail';
	}

	/**
	 * Make sure we include all necessary files
	 */
	function entity_requires(){
		// require();
	}

	/**
	 * Hook class functions into WP actions
	 */
	function entity_actions(){
		// Actions
		add_action( 'init',          array( $this, 'setup_globals'         ) );
		add_action( 'init',          array( $this, 'register_cpt'          ) );
		add_action( 'admin_init',    array( $this, 'register_settings'     ) );
		add_action( 'admin_menu',    array( $this, 'admin_menu'            ) );
		add_action( 'admin_notices', array( $this, 'admin_notices'         ) );

		// Plugin actions
		add_filter( 'vgsr_entity_'. $this->type .'_meta', array( $this, 'entity_meta' ) );
		add_filter( $this->type .'_admin_messages', array( $this, 'admin_messages' ) );

		// Filters
		add_filter( 'vgsr_entity-'. $this->type .'-register_cpt', array( $this, 'edit_register_cpt' ) );
		add_filter( 'the_content',            array( $this, 'parent_page_add_children'   ) );
		add_filter( 'wp_insert_post_parent',  array( $this, 'type_parent_page_save_post' ), 10, 4 );
	}

	function setup_globals(){
		// Set child globals
	}

	function setup_requires(){
		// Set child globals
	}

	function setup_actions(){
		// Set child globals
	}

	/** Setup custom post type *****************************************/

	/**
	 * Register the post type
	 *
	 * @uses register_post_type()
	 * @uses apply_filters() To call vgsr_entity-{$post_type}-register_cpt
	 *                        filter to enable post type arguments filtering
	 * @return void
	 */
	public function register_cpt(){
		global $vgsr_entity;

		extract( $this->labels );

		$ev = strtolower( $single );
		$mv = strtolower( $multi );

		// Create post type labels
		$labels = array(
			'name'                 => $multi,
			'singular_name'        => $single,
			'add_new'              => sprintf( __('New %s'), $ev ),
			'add_new_item'         => sprintf( __('Add new %s'), $ev ),
			'edit_item'            => sprintf( __('Edit %s'), $ev ),
			'new_item'             => sprintf( __('New %s'), $ev ),
			'all_items'            => sprintf( __('All %s'), $mv ),
			'view_item'            => sprintf( __('View %s'), $ev ),
			'search_items'         => sprintf( __('Search %s'), $mv ),
			'not_found'            => sprintf( __('No %s found'), $mv ),
			'not_found_in_trash'   => sprintf( __('No %s found in trash'), $mv ), 
			'parent_item_colon'    => '',
			'menu_name'            => $multi
			);

		// Setup post type arguments
		$args = array(
			'labels'               => $labels,
			'public'               => true,
			'menu_icon'            => null, // url to the admin menu icon
			'menu_position'        => $vgsr_entity->menu_position,
			'hierarchical'         => false,
			'rewrite'              => array( 'slug' => $this->type_parent_page_slug() ),
			'capability_type'      => 'page',
			'supports'             => array(
									'title'
									, 'editor'
									, 'author'
									, 'thumbnail'
									, 'revisions'
									, 'page-attributes' // To set menu order
									),
			'register_meta_box_cb' => array( $this, 'metabox_cb' )
			);

		// Register post type
		register_post_type( $this->type, apply_filters( 'vgsr_entity-'. $this->type .'-register_cpt', $args ) );
	}

	/**
	 * Mirror method for filtering register_post_type args
	 * 
	 * @param array $args
	 * @return array
	 */
	function edit_register_cpt( $args ){
		return $args;
	}

	function metabox_cb(){
		// add_meta_box();
	}

	/**
	 * Output the admin messages if requested after save post
	 *
	 * @uses apply_filters() To call the {$this->type}_admin_messages filter
	 * @return void
	 */
	function admin_notices(){

		// Only continue if error is sent
		if ( !isset( $_REQUEST[$this->type .'-error'] ) || empty( $_REQUEST[$this->type .'-error'] ) )
			return;

		// Get the message num
		$num = trim( $_REQUEST[$this->type .'-error'] );

		// The messages to pick from
		$messages = apply_filters( $this->type .'_admin_messages', array(
			0 => '' // Default empty
			) );

		// Message must exist
		if ( !isset( $messages[$num] ) )
			return;

		// Output message
		echo '<div class="error message"><p>'. $messages[$num] .'</p></div>';
	}

	/**
	 * Dummy function
	 * 
	 * @param array $messages
	 * @return array $messages
	 */
	function admin_messages( $messages ){
		return $messages;
	}

	/**
	 * Setup the entity admin menu settings page
	 *
	 * @uses add_submenu_page()
	 * @uses add_action() To call some actions on page load
	 *                     head and footer
	 * @return void
	 */
	function admin_menu(){
		$this->page = add_submenu_page( $this->hook, $this->settings_title, __('Settings'), 'manage_options', $this->type .'-settings', array( $this, 'settings_page' ) );

		// Setup page specific hooks
		add_action( 'load-'. $this->page,                array( $this, 'page_actions'             ), 9 );
		add_action( 'admin_print_scripts-'. $this->page, array( $this, 'page_styles'              ), 10);
		add_action( 'admin_print_styles-'. $this->page,  array( $this, 'page_scripts'             ), 10);
		add_action( 'admin_footer-'. $this->page,        array( $this, 'footer_scripts'           ) );

		// Additional actions
		add_action( 'load-'. $this->page,                array( $this, 'type_parent_page_process' ), 8 );
	}

	function register_settings(){
		$this->settings_page    = $this->type .'-settings';
		$this->settings_section = 'vgsr-entity-'. $this->settings_page;

		add_settings_section( $this->settings_section, __('Another section title', 'vgsr-entity'), array( $this, 'settings_information' ), $this->settings_page );

		// Type parent page
		add_settings_field( $this->parent, $this->labels['multi'] .' '. __('Parent Page', 'vgsr-entity'), array( $this, 'type_parent_page_field' ), $this->settings_page, $this->settings_section );
		register_setting( $this->settings_page, $this->parent, 'intval' );
	}

	function settings_information(){
		echo '<p>'. __('Some blabla about the settings here below. Fill \'em and enjoy!', 'vgsr-entity') .'</p>';
	}

	function settings_page(){
		$this->settings_page = $this->type .'-settings';

		// Display settings page
		?><div class="wrap">

			<?php screen_icon(); ?>
			<h2><?php echo $this->settings_title; ?></h2>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( $this->settings_page ); ?>
				<?php do_settings_sections( $this->settings_page ); ?>
				<?php submit_button(); ?>
			</form>

		</div><?php		
	}

	// Output parent page field
	function type_parent_page_field(){
		$args = array(
			'name'             => $this->parent,
			'selected'         => get_option( $this->parent ),
			'echo'             => false,
			'show_option_none' => __('None')
			);

		echo '<label>'. wp_dropdown_pages( $args ) .' <span class="description">'. sprintf( __('Pick the parent page you want to have your %s appear on.', 'vgsr-entity'), $this->labels['multi'] ) .'</span></label>';
	}

	/**
	 * Rewrite permalink setup if post parent changes
	 *
	 * @uses VGSR_Entitites::rewrite_flush()
	 * @return void
	 */
	function type_parent_page_process(){

		// Get values
		$new_id  = intval( get_option( $this->parent ) );
		$checkie = get_posts( array( 'post_type' => $this->type, 'numberposts' => 1 ) );
		$old_id  = $checkie[0]->post_parent;

		// Only continue if the values are different
		if ( $new_id != $old_id ){
			$posts = get_posts( array( 'post_type' => $this->type, 'numberposts' => -1 ) );

			// Loop over all posts
			foreach ( $posts as $post ){

				// Set the new post parent
				$post->post_parent = $new_id;

				// Update the posts
				wp_update_post( $post );
			}

			// Flush rewrite rules for new page parent
			global $vgsr_entity;
			$vgsr_entity->rewrite_flush();
		}
	}

	/**
	 * Save entity parent page ID as post parent
	 * 
	 * @param int $parent_id The parent page ID
	 * @param int $post_id The post ID
	 * @param unknown $args1
	 * @param unknown $args2
	 * @return int The parent ID
	 */
	function type_parent_page_save_post( $parent_id, $post_id, $args1, $args2 ){

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $parent_id;

		if ( get_post_type( $post_id ) !== $this->type )
			return $parent_id;

		$cpt_obj = get_post_type_object( $this->type );

		if ( !current_user_can( $cpt_obj->cap->edit_posts ) || !current_user_can( $cpt_obj->cap->edit_post, $post_id ) )
			return $parent_id;

		$parent_page_id = (int) get_option( $this->parent );

		if ( $parent_id !== $parent_page_id )
			return $parent_id;

		return $parent_page_id;
	}

	/**
	 * Returns the slug for the custom post type
	 * 
	 * @return string Slug
	 */
	function type_parent_page_slug(){

		// Get parent page option
		$parent_id = get_option( $this->parent );

		// Setup return var
		$slug = '';

		// Do we have a parent page?
		if ( $_post = get_post( $parent_id ) ){

			// Get the parent slug
			$slug = $_post->post_name;

			// Loop over all next parents
			while ( !empty( $_post->post_parent ) ){

				// Get next parent
				$_post = get_post( $_post->post_parent );

				// Prepend parent slug
				$slug = $_post->post_name .'/'. $slug;
			}
		}

		return $slug;
	}

	function page_actions(){
		// Page actions before page load
	}

	function page_styles(){
		// Page styles
	}

	function page_scripts(){
		// Page scripts
	}

	function footer_scripts(){
		// Page footer scripts
	}

	/**
	 * Append entity parent page content with entity children
	 * 
	 * @param string $content The post content
	 * @return string $content
	 */
	function parent_page_add_children( $content ){
		global $post;

		if ( $post->ID === (int) get_option( $this->parent ) )
			return $content . $this->parent_page_list_children();
		else
			return $content;
	}

	/**
	 * Returns a list of all parent page entity children with
	 * their respective post thumbnails.
	 * 
	 * @return string $retval The list of children
	 */
	function parent_page_list_children(){

		// Get all posts
		$children = get_posts( array(
			'post_type'   => $this->type,
			'numberposts' => -1, // We want all!
			'orderby'     => 'menu_order',
			'order'       => 'ASC'
			) );

		$retval = '<ul class="parent-page-children '. $this->type .'-children">';

		foreach ( $children as $post ) : setup_postdata( $post );
			$retval .=	'<li class="parent-child '. $this->type.' '. $this->type.'-type">';
			$retval .=		'<a href="'. get_permalink( $post->ID ) .'" title="'. $post->post_title .'">';
			$retval .=			'<span class="parent-child-thumbnail '. $this->type .'-thumbnail">';

			// Get the post thumbnail
			if ( has_post_thumbnail( $post->ID ) ) :
				$img     = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $this->thumbsize );
				$retval .= '<img src="'. $img[0] .'" />';

			// Get first image attachment
			elseif ( $att = get_children( array('post_type' => 'attachment', 'post_mime_type' => 'image', 'post_parent' => $post->ID ) ) ) :
				$att     = reset( $att );
				$img     = wp_get_attachment_image_src( $att->ID, $this->thumbsize );
				$retval .= '<img src="'. $img[0] .'" />';

			// Get dummy image
			else :
				if ( is_string( $this->thumbsize ) ){
					global $_wp_additional_image_sizes;
					$format = $_wp_additional_image_sizes[$this->thumbsize];
				} else {
					$format = $this->thumbsize;
				}
				
				if ( is_array( $format ) ){
					if ( isset( $format[0] ) ) // Numerical array
						$size = $format[0] .'x'. $format[1];
					else // Textual string
						$size = $format['width'] .'x'. $format['height'];
				}

				else
					$size = '200x200'; // Random default value

				$retval .= '<img src="http://dummyimage.com/'. $size .'/eee/000&text=plaatshouder" />';
			endif;

			$retval .=			'</span>';
			$retval .=			'<span class="parent-child-title '. $this->type .'-title">' .
									'<h3>'. $post->post_title .'</h3>' .
								'</span>';
			$retval .=		'</a>';
			$retval .=	'</li>';
		endforeach;

		$retval .= '</ul>';

		return $retval;
	}

	function entity_meta( $meta ){
		return $meta;
	}

}

endif; // class_exisist VGSR_Entity


/**
 * This is the main class that loads all entities
 */
if ( !class_exists( 'VGSR_Entities' ) ) :

/**
 * Plugin class
 */
class VGSR_Entities {

	public $file          = '';
	public $plugin_path   = '';
	public $entities      = array();
	public $menu_position = 0;

	/**
	 * Construct the VGSR Entity plugin class
	 */
	public function __construct(){
		$this->setup_globals();
		$this->setup_requires();
		$this->setup_actions();
	}

	/**
	 * Create class globals
	 */
	private function setup_globals(){
		$this->file          = __FILE__;
		$this->plugin_path   = plugin_dir_path( $this->file );
		$this->menu_position = 35;

		// Set all entities
		$this->entities = array( 'bestuur', 'dispuut', 'kast' );
	}

	/**
	 * Make sure we include all necessary files
	 */
	private function setup_requires(){
		require( $this->plugin_path . 'widgets/widget-menu.php' );

		foreach ( $this->entities as $e )
			require( $this->plugin_path . "entities/$e.php" );
	}

	/**
	 * Hook class functions into WP actions
	 */
	private function setup_actions(){
		register_activation_hook( $this->file, array( $this, 'rewrite_flush' ) );

		add_action( 'plugins_loaded',     array( $this, 'load_textdomain'    ) );
		add_action( 'admin_menu',         array( $this, 'admin_menu'         ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts'    ) );
		add_action( 'widgets_init',       array( $this, 'widgets_init'       ) );

		foreach ( $this->entities as $e )
			add_action( 'init', "vgsr_entity_$e", 9 );

		add_action( 'template_include',   array( $this, 'template_include'   ) );
	}

	/** Setup custom post types ****************************************/

	/**
	 * Set new permalink structure by refreshing the rewrite rules
	 * on activation.
	 *
	 * @uses flush_rewrite_rules()
	 * @return void
	 */
	public function rewrite_flush(){

		// Call post type registration
		foreach ( $this->entities as $e ){
			$name = 'VGSR_Entity_'. ucfirst( $e );
			$class = new $name;
			$class->register_cpt();
		}

		// Flush rules only on activation
		flush_rewrite_rules();
	}

	/**
	 * Loads the textdomain file for this plugin
	 *
	 * @uses load_textdomain() To insert the matched language file
	 * @return mixed Text domain if found, else boolean false
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$mofile        = sprintf( 'vgsr-entity-%s.mo', get_locale() );

		// Setup paths to current locale file
		$mofile_local  = $this->plugin_path .'languages/'. $mofile;
		$mofile_global = WP_LANG_DIR . '/vgsr-entity/' . $mofile;

		// Look in global /wp-content/languages/vgsr-entity folder
		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( 'vgsr-entity', $mofile_global );

		// Look in local /wp-content/plugins/vgsr-entity/languages/ folder
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( 'vgsr-entity', $mofile_local );
		}

		// Nothing found
		return false;
	}

	/**
	 * Filters the admin menu to add a separator
	 * 
	 * @return void
	 */
	public function admin_menu(){
		$this->add_separator( $this->menu_position - 1 );
	}

	/**
	 * Runs through the admin menu to add a separator at position {pos}
	 *
	 * @link http://wordpress.stackexchange.com/questions/2666/add-a-separator-to-the-admin-menu
	 *
	 * The separator name can affect the order of the separators,
	 * therefor the separator{$index} naming is changed.
	 * 
	 * @param int $pos The position after which to add the sep
	 * @return void
	 */
	public function add_separator( $pos ){
		global $menu;
		$index = 1;

		foreach( $menu as $offset => $item ){

			if ( substr( $item[2], 0, 9 ) == 'separator' )
				$index++;

			if ( $offset >= $pos ){
				$menu[$pos] = array( '', 'read', "separator-pos{$index}", '', 'wp-menu-separator' );
				break;
			}
		}

		ksort( $menu );
	}

	/**
	 * Enqueue additional page styles if on post type page and
	 * on post type parent page.
	 * 
	 * @return void
	 */
	public function enqueue_scripts(){
		global $post;

		// Return if $post is not set
		if ( !$post )
			return;

		// Return if page is not of given post types or is not post type parent page
		if ( !in_array( $post->post_type, $this->entities ) && !in_array( $post->ID, $this->get_entity_parent_ids() ) )
			return;

		wp_register_style( 'vgsr-entity', plugins_url( 'css/style.css', __FILE__ ) );
		wp_enqueue_style( 'vgsr-entity' );
	}

	public function get_entity_parent_ids(){
		$parents = array();
		foreach ( $this->entities as $entity )
			$parents[$entity] = get_option( $this->{$entity}->parent );

		return $parents;
	}

	/**
	 * Initiate entity widgets
	 *
	 * @return void
	 */
	public function widgets_init(){
		register_widget( 'VGSR_Entity_Widget_Menu' );
	}

	/**
	 * Intercept the template loader to load the entity template
	 * 
	 * @param string $template The current template match
	 * @return string $template
	 */
	public function template_include( $template ){

		// Serve single-{entity} template if asked for
		if ( in_array( get_post_type(), $this->entities ) && is_singular( get_post_type() ) ){

			// Get our template path
			$single = $this->plugin_path . 'templates/single-'. get_post_type() .'.php';

			// Only serve our template if it exists
			$template = file_exists( $single ) ? $single : $template;
		}

		return $template;
	}

	/**
	 * Outputs the entity meta list
	 * 
	 * @return void
	 */
	public function entity_meta(){
		global $post;

		// Get the meta
		$meta_fields = apply_filters( "vgsr_entity_{$post->post_type}_meta", array() );

		// Don't output anything if there's no meta
		if ( empty( $meta_fields ) )
			return;

		// Start list
		echo '<ul class="post-meta entity-meta">';

		// Loop over all meta fields
		foreach ( $meta_fields as $key => $meta ){

			// Merge defaults
			$defaults = array(
				'icon'   => '',
				'before' => '',
				'value'  => '',
				'after'  => ''
				);
			$meta = wp_parse_args( $meta, $defaults );

			echo '<li><i class="'. $meta['icon'] .'"></i> '. $meta['before'] . $meta['value'] . $meta['after'] .'</li>';
		}
		
		// End list
		echo '</ul>';
	}
}

$GLOBALS['vgsr_entity'] = new VGSR_Entities();

endif; // class_exists VGSR_Entities

