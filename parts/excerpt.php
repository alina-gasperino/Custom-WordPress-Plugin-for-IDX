<a href="<?php the_permalink(); ?>" <?php post_class( 'archive-loop__excerpt' ); ?>>

	<span class="archive-loop__excerpt--header">
		<span class="title"><?php the_title(); ?></span>
		<span class="date"><span><?php the_time('j') ?></span><span><?php the_time('M') ?></span></span>
	</span>

	<span class="excerpt"><?php echo strip_tags( apply_filters( 'the_excerpt', get_the_excerpt() ) ) ?> ... <strong>READ ARTICLE</strong></span>

	<?php the_post_thumbnail( 'large', [ 'class' => 'background-image' ] ); ?>

</a>