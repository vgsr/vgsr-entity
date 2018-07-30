<?php

/**
 * The template for displaying the entity shortlist
 *
 * @package VGSR Entity
 * @subpackage Theme
 */

?>

<?php if ( vgsr_entity_query_entities() ) : ?>

<ul <?php vgsr_entity_shortlist_class(); ?>>

	<?php while ( vgsr_entity_has_entities() ) : vgsr_entity_the_entity(); ?>

		<li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php if ( vgsr_entity_has_logo() ) : ?>

			<div class="entity-logo">
				<a href="<?php the_permalink(); ?>"><?php vgsr_entity_the_logo(); ?></a>
			</div>

			<?php endif; ?>

			<?php the_title( sprintf( '<div class="entity-title"><a href="%s">', esc_url( get_permalink() ) ), '</a></div>' ); ?>

		</li>

	<?php endwhile; ?>

</ul>

<?php endif; ?>
