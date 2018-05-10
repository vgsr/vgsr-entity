<?php

/**
 * VGSR Entity Bestuur Administration Functions
 *
 * @package VGSR Entity
 * @subpackage Bestuur
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'VGSR_Bestuur_Admin' ) ) :
/**
 * The VGSR Bestuur Administration class
 *
 * @since 2.0.0
 */
class VGSR_Bestuur_Admin extends VGSR_Entity_Type_Admin {

	/**
	 * Define default actions and filters
	 *
	 * @since 2.0.0
	 */
	protected function setup_actions() {

		// Settings
		add_action( "vgsr_{$this->type}_settings_footer", array( $this, 'settings_footer_scripts' ) );

		// Post
		add_action( "vgsr_{$this->type}_metabox",   array( $this, 'positions_metabox'   ), 20    );
		add_action( "save_post_{$this->post_type}", array( $this, 'positions_save'      ), 10, 2 );
		add_filter( 'display_post_states',          array( $this, 'display_post_states' ),  9, 2 );

		parent::setup_actions();
	}

	/** Public methods **************************************************/

	/**
	 * Enqueue settings page scripts
	 *
	 * @since 2.0.0
	 */
	public function enqueue_settings_scripts() {
		parent::enqueue_settings_scripts();
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	/**
	 * Print settings page footer scripts
	 *
	 * @since 2.0.0
	 */
	public function settings_footer_scripts() { ?>

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

	/** Post ************************************************************/

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
		$cpt = get_post_type_object( $this->post_type );
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
	 * Mark which Bestuur is the current one
	 *
	 * @since 2.0.0
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
}

endif; // class_exists
