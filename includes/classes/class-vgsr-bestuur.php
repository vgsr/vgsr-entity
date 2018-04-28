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
	 * Construct Bestuur Entity
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( 'bestuur', array(
			'menu_icon' => 'dashicons-awards',
			'labels'    => array(
				'name'               => esc_html__( 'Besturen',                   'vgsr-entity' ),
				'singular_name'      => esc_html__( 'Bestuur',                    'vgsr-entity' ),
				'add_new'            => esc_html__( 'New Bestuur',                'vgsr-entity' ),
				'add_new_item'       => esc_html__( 'Add new Bestuur',            'vgsr-entity' ),
				'edit_item'          => esc_html__( 'Edit Bestuur',               'vgsr-entity' ),
				'new_item'           => esc_html__( 'New Bestuur',                'vgsr-entity' ),
				'all_items'          => esc_html__( 'All Besturen',               'vgsr-entity' ),
				'view_item'          => esc_html__( 'View Bestuur',               'vgsr-entity' ),
				'search_items'       => esc_html__( 'Search Besturen',            'vgsr-entity' ),
				'not_found'          => esc_html__( 'No Besturen found',          'vgsr-entity' ),
				'not_found_in_trash' => esc_html__( 'No Besturen found in trash', 'vgsr-entity' ),
				'menu_name'          => esc_html__( 'Besturen',                   'vgsr-entity' ),
				'settings_title'     => esc_html__( 'Besturen Settings',          'vgsr-entity' ),
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
			2 => sprintf( esc_html__( 'The submitted value for %s is not given in the valid format.', 'vgsr-entity' ), '<strong>' . esc_html__( 'Season', 'vgsr-entity' ) . '</strong>' ),
		) );
	}

	/**
	 * Include required files
	 *
	 * @since 2.0.0
	 */
	public function includes() {
		require( vgsr_entity()->includes_dir . 'besturen/functions.php' );
		require( vgsr_entity()->includes_dir . 'besturen/template.php'  );
	}

	/**
	 * Setup default Bestuur actions and filters
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {

		add_action( 'vgsr_entity_init',                   array( $this, 'add_rewrite_rules'    ) );
		add_action( 'vgsr_entity_settings_fields',        array( $this, 'add_settings_fields'  ) );
		add_action( "vgsr_{$this->type}_settings_footer", array( $this, 'print_footer_scripts' ) );

		// Post
		add_action( "save_post_{$this->type}", array( $this, 'save_current_bestuur' ), 10, 2 );
		add_filter( 'display_post_states',     array( $this, 'display_post_states'  ),  9, 2 );

		// Positions
		add_action( "vgsr_{$this->type}_metabox",        array( $this, 'positions_metabox' ), 20    );
		add_action( "save_post_{$this->type}",           array( $this, 'positions_save'    ), 10, 2 );
		add_action( "vgsr_entity_{$this->type}_details", array( $this, 'positions_detail'  )        );

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

		// Define entity fields
		$_fields = array(

			// Bestuur Positions
			'positions' => array(
				'title'             => esc_html__( 'Positions', 'vgsr-entity' ),
				'callback'          => array( $this, 'setting_positions_field' ),
				'sanitize_callback' => array( $this, 'sanitize_positions_field' ),
				'entity'            => $this->type,
				'args'              => array(),
			),

			// Menu Order
			'menu-order' => array(
				'title'             => esc_html__( 'Menu Widget Order', 'vgsr-entity' ),
				'callback'          => array( $this, 'setting_menu_order_field' ),
				'sanitize_callback' => 'intval',
				'entity'            => $this->type,
				'args'              => array(),
			)
		);

		// Append fields to Main Section
		$fields['main'] += $_fields;

		return $fields;
	}

	/**
	 * Output the Bestuur Positions settings field
	 *
	 * @since 2.0.0
	 */
	public function setting_positions_field() {

		// Define table controls
		$controls = '<button type="button" class="button-link position-remove dashicons-before dashicons-no-alt"><span class="screen-reader-text">' . esc_html__( 'Remove position', 'vgsr-entity' ) . '</span></button>';

		// Get all positions
		$positions = $this->get_positions();

		?>

		<table class="widefat fixed striped positions">
			<thead>
				<tr>
					<th class="label"><?php esc_html_e( 'Label', 'vgsr-entity' ); ?></th>
					<th class="slug"><?php esc_html_e( 'Slug', 'vgsr-entity' ); ?></th>
					<th class="controls">
						<button type="button" class="button-link position-add dashicons-before dashicons-plus"><span class="screen-reader-text"><?php esc_html_e( 'Add position', 'vgsr-entity' ); ?></span></button>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="label"><?php esc_html_e( 'Label', 'vgsr-entity' ); ?></th>
					<th class="slug"><?php esc_html_e( 'Slug', 'vgsr-entity' ); ?></th>
					<th class="controls"></th>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ( $positions as $position => $args ) : ?>
				<tr>
					<td class="label"><input type="text" name="positions[label][]" value="<?php echo esc_attr( $args['label'] ); ?>" /></td>
					<td class="slug"><input type="text" name="positions[slug][]" value="<?php echo esc_attr( $args['slug'] ); ?>" /></td>
					<td class="controls"><?php echo $controls; ?></td>
				</tr>
				<?php endforeach; ?>

				<?php if ( empty( $positions ) ) : ?>
				<tr>
					<td class="label"><input type="text" name="positions[label][]" value="" /></td>
					<td class="slug"><input type="text" name="positions[slug][]" value="" /></td>
					<td class="controls"><?php echo $controls; ?></td>
				</tr>
				<?php endif; ?>

				<tr class="positions-add-row" style="display:none;">
					<td class="label"><input type="text" name="positions[label][]" value="" /></td>
					<td class="slug"><input type="text" name="positions[slug][]" value="" /></td>
					<td class="controls"><?php echo $controls; ?></td>
				</tr>
			</tbody>
		</table>

		<?php
	}

	/**
	 * Output the Bestuur Menu Order settings field
	 *
	 * @since 1.0.0
	 */
	public function setting_menu_order_field() {

		// Define local variables
		$option_name = "_{$this->type}-menu-order";
		$value       = (int) get_option( $option_name ); ?>

		<select name="<?php echo esc_attr( $option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>">
			<option value="0" <?php selected( $value, 0 ); ?>><?php esc_html_e( 'Seniority',         'vgsr-entity' ); ?></option>
			<option value="1" <?php selected( $value, 1 ); ?>><?php esc_html_e( 'Reverse seniority', 'vgsr-entity' ); ?></option>
		</select>

		<p for="<?php echo esc_attr( $option_name ); ?>" class="description"><?php printf( esc_html__( 'The order in which the %s will be displayed in the Menu Widget.', 'vgsr-entity' ), $this->args['labels']['name'] ); ?></p>

		<?php
	}

	/**
	 * Sanitize the Bestuur Positions settings field input
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Option input
	 * @param string $option Option name
	 * @param mixed $original_value Original option value
	 * @return array Option input
	 */
	public function sanitize_positions_field( $input, $option = '', $original_value = null ) {

		// No input available to sanitize
		if ( ! $input && ! isset( $_REQUEST['positions'] ) ) {
			$input = $original_value;
		} else {
			$_input = array();

			// Values were passed
			if ( ! empty( $input ) ) {
				$_input = $input;

			// Collect and sanitize input from `$_REQUEST`
			} else {
				foreach ( $_REQUEST['positions'] as $key => $values ) {
					foreach ( $values as $k => $v ) {
						$_input[ $k ][ $key ] = esc_html( $v );
					}
				}
			}

			// Process input
			foreach ( $_input as $position => $args ) {

				// Remove empty row
				if ( empty( $args['slug'] ) && empty( $args['label'] ) ) {
					unset( $_input[ $position ] );

				// Add missing slug
				} elseif ( empty( $args['slug'] ) ) {
					$_input[ $position ]['slug'] = sanitize_title( $args['label'] );

				// Add missing label
				} elseif ( empty( $args['label'] ) ) {
					$_input[ $position ]['label'] = ucfirst( $args['slug'] );
				}
			}

			$input = ! empty( $_input ) ? $_input : $original_value;
		}

		return $input;
	}

	/**
	 * Enqueue settings page scripts
	 *
	 * @since 2.0.0
	 */
	public function enqueue_settings_scripts() {

		// Enqueue sortable
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Enqueue admin scripts
		wp_enqueue_style( 'vgsr-entity-admin', vgsr_entity()->includes_url . 'assets/css/admin.css' );
	}

	/**
	 * Print settings page footer scripts
	 *
	 * @since 2.0.0
	 */
	public function print_footer_scripts() { ?>

		<script type="text/javascript">
			jQuery(document).ready( function( $ ) {
				var $el = $( '.positions' ),
				    $tr = $el.find( 'tr.positions-add-row' );

				// Make list rows sortable
				$el.sortable({
					items: 'tbody tr',
					axis: 'y',
					containment: 'parent',
					handle: 'td.controls',
					tolerance: 'pointer'
				});

				// Add row
				$el.on( 'click', '.position-add', function() {
					$tr.clone().removeClass( 'positions-add-row' ).insertBefore( $tr ).show();

				// Remove row
				}).on( 'click', '.position-remove', function() {
					$(this).parents( '.positions tr' ).remove();
				});
			});
		</script>

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

		// Get registered current bestuur
		$current = vgsr_entity_get_current_bestuur();

		// Bail when the bestuur is not published, when editing an older bestuur or when keeping the current bestuur published
		if (
			'publish' != $post->post_status
			|| ( $post->menu_order <= get_post( $current )->menu_order )
			|| ( $current === $post_id && 'publish' == $post->post_status )
		)
			return;

		// Current bestuur was edited
		if ( $current === $post_id ) {

			// Get the current bestuur
			if ( $query = new WP_Query( array(
				'posts_per_page' => 1,
				'post_type'      => $this->type,
				'post_status'    => 'publish',
				'orderby'        => 'menu_order',
			) ) && $query->posts ) {
				$post_id = $query->posts[0]->ID;

			// Default to 0
			} else {
				$post_id = 0;
			}
		}

		// Update current bestuur
		update_option( '_bestuur-latest-bestuur', $post_id );

		// Refresh rewrite rules to properly point to the current bestuur
		add_action( "save_post_{$this->type}", array( $this, 'add_rewrite_rules' ), 99 );
		add_action( "save_post_{$this->type}", 'flush_rewrite_rules',               99 );
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
				get_post_type_object( $this->type )->rewrite['slug'] . '/?$',
				// ... should be interpreted as the current Bestuur
				'index.php?p=' . $current,
				'top'
			);
		}
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

		// The current bestuur
		if ( vgsr_entity_is_current_bestuur( $post ) ) {
			$states['current'] = esc_html__( 'Current', 'vgsr-entity' );
		}

		return $states;
	}

	/** Positions ******************************************************/

	/**
	 * Output the Bestuur Positions metabox section
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	public function positions_metabox( $post ) {

		// Get entity's positions and all positions
		$positions  = vgsr_entity_bestuur_get_positions( $post );
		$_positions = vgsr_entity_bestuur_get_positions();

		// Define remove control
		$remove_control = '<button type="button" class="button-link position-remove dashicons-before dashicons-no-alt"><span class="screen-reader-text">' . esc_html__( 'Remove position', 'vgsr-entity' ) . '</span></button>';

		?>

		<h4><?php esc_html_e( 'Positions', 'vgsr-entity' ); ?></h4>

		<p class="positions">
			<?php foreach ( $positions as $args ) : ?>
			<label class="alignleft">
				<span class="input-text-wrap">
					<select name="positions[slug][]">
						<option value=""><?php esc_html_e( '&mdash; Select position &mdash;', 'vgsr-entity' ); ?></option>
						<?php foreach ( $_positions as $position ) : ?>
						<option value="<?php echo esc_attr( $position['slug'] ); ?>" <?php selected( $position['slug'], $args['slug'] ); ?>><?php echo esc_html( $position['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
				<span class="input-text-wrap">
					<?php
						// Get user details
						$user       = get_user_by( is_numeric( $args['user'] ) ? 'id' : 'slug', $args['user'] );
						$user_id    = $user ? $user->ID : '';
						$user_name  = $user ? $user->user_login : $args['user'];
						$user_class = $user ? 'is-user' : '';
					?>
					<input type="text" class="positions-user-name <?php echo $user_class; ?>" name="positions[user_name][]" value="<?php echo esc_attr( $user_name ); ?>" />
					<input type="hidden" class="positions-user-id" name="positions[user_id][]" value="<?php echo $user_id; ?>" />
				</span>
				<?php echo $remove_control; ?>
			</label>
			<?php endforeach; ?>

			<?php if ( empty( $positions ) ) : ?>
			<label class="alignleft">
				<span class="input-text-wrap">
					<select name="positions[slug][]">
						<option value=""><?php esc_html_e( '&mdash; Select position &mdash;', 'vgsr-entity' ); ?></option>
						<?php foreach ( $_positions as $position ) : ?>
						<option value="<?php echo esc_attr( $position['slug'] ); ?>"><?php echo esc_html( $position['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
				<span class="input-text-wrap">
					<input type="text" class="positions-user-name" name="positions[user_name][]" value="" />
				</span>
				<?php echo $remove_control; ?>
			</label>
			<?php endif; ?>

			<label class="alignleft positions-add-row" style="display:none;">
				<span class="input-text-wrap">
					<select name="positions[slug][]">
						<option value=""><?php esc_html_e( '&mdash; Select position &mdash;', 'vgsr-entity' ); ?></option>
						<?php foreach ( $_positions as $position ) : ?>
						<option value="<?php echo esc_attr( $position['slug'] ); ?>"><?php echo esc_html( $position['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
				<span class="input-text-wrap">
					<input type="text" class="positions-user-name" name="positions[user_name][]" value="" />
				</span>
				<?php echo $remove_control; ?>
			</label>

			<input type="hidden" name="positions-ajax-url" value="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'vgsr_entity_suggest_user' ), admin_url( 'admin-ajax.php', 'relative' ) ), 'vgsr_entity_suggest_user_nonce' ) ); ?>" />

			<span class="positions-actions">
				<button type="button" class="button-link positions-help">
					<i class="dashicons-before dashicons-editor-help"></i>
					<span><?php esc_html_e( 'Assign a site user (by ID or login) to a position or provide a full name. A green border indicates a verified site user.', 'vgsr-entity' ); ?></span>
				</button>
				<button type="button" class="button position-add"><?php esc_html_e( 'Add position', 'vgsr-entity' ); ?></button>
			</span>
		</p>

		<?php
	}

	/**
	 * Save the Bestuur Positions metabox input
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID
	 * @param WP_Post $post Post object
	 */
	public function positions_save( $post_id, $post ) {

		// Bail when doing outosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Bail when the user is not capable
		$cpt = get_post_type_object( $this->type );
		if ( ! current_user_can( $cpt->cap->edit_posts ) || ! current_user_can( $cpt->cap->edit_post, $post_id ) )
			return;

		// Bail when no positions were submitted
		if ( ! isset( $_POST['positions'] ) || empty( $_POST['positions'] ) )
			return;

		// Collect and sanitize input
		$positions = array();
		foreach ( $_POST['positions'] as $key => $input ) {
			foreach ( $input as $k => $v ) {
				$positions[ $k ][ $key ] = esc_html( $v );
			}
		}

		// Process removed positions
		foreach ( array_diff( wp_list_pluck( vgsr_entity_bestuur_get_positions( $post ), 'slug' ), wp_list_pluck( $positions, 'slug' ) ) as $slug ) {
			delete_post_meta( $post_id, "position_{$slug}" );
		}

		// Process input
		foreach ( $positions as $args ) {

			// Skip when without position or user
			if ( empty( $args['slug'] ) || empty( $args['user_name'] ) )
				continue;

			// Accept user id input
			$user_id = ! empty( $args['user_id'] ) ? $args['user_id'] : ( is_numeric( $args['user_name'] ) ? $args['user_name'] : false );

			// Get user
			if ( $user = $user_id ? get_user_by( 'id', $user_id ) : get_user_by( 'login', $args['user_name'] ) ) {
				$user = $user->ID;
			} else {
				$user = $args['user_name'];
			}

			// Update position in post meta
			update_post_meta( $post_id, "position_{$args['slug']}", $user );
		}
	}

	/**
	 * Display the Bestuur Positions entity detail
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post $post Post object
	 */
	public function positions_detail( $post ) {

		// Bail when no positions are signed for this entity
		if ( ! $positions = vgsr_entity_bestuur_get_positions( $post ) )
			return;

		?>

		<div class="bestuur-positions">
			<h4><?php _ex( 'Members', 'Bestuur positions', 'vgsr-entity' ) ?></h4>

			<dl>
				<?php foreach ( $positions as $args ) : ?>
				<dt class="position position-<?php echo $args['slug']; ?>"><?php echo $args['label']; ?></dt>
				<dd class="member"><?php

					// Use existing user's display name
					if ( $user = get_user_by( is_numeric( $args['user'] ) ? 'id' : 'slug', $args['user'] ) ) {
						echo $user->display_name;

					// Default to the provided 'user' name or content
					} else {
						echo $args['user'];
					}
				?></dd>
				<?php endforeach; ?>
			</dl>
		</div>

		<?php
	}

	/** Theme **********************************************************/

	/**
	 * Modify the document title for our entity
	 *
	 * @since 2.0.0
	 *
	 * @param array $title Title parts
	 * @return array Title parts
	 */
	public function document_title_parts( $title ) {

		// When this is our entity
		if ( is_bestuur() ) {
			/* translators: 1. Bestuur title, 2. Bestuur season */
			$title['title'] = sprintf( esc_html__( '%1$s (%2$s)', 'vgsr-entity' ), $title['title'], $this->get( 'season' ) );
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
