<blockquote cite="<?php the_permalink(); ?>" <?php post_class( 'archive-loop__excerpt feature' ); ?>>

	<div class="inside">

	<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

	<?php the_excerpt(); ?>

	</div>

	<?php the_post_thumbnail( 'hero-cover', [ 'alt' => '' ] ); ?>

</blockquote>