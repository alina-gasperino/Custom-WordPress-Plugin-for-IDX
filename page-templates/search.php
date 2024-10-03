<?php
/**
 * Template Name: Search Results
 * @package VestorTheme
 * @subpackage Archive
 * @since 1.0.0
 */

namespace VestorTheme;

use VestorFilter\Util\Template as Template;
use VestorFilter\Util\Icons as Icons;

get_header();

wp_enqueue_script( 'vestorhouse-blog' );

$page = get_query_var( 'paged' ) ?: 1 ;

?>

<?php Template::action( 'before_main', 'search' ); ?>

<main class="website-content archive-loop is-search" role="main">

	<?php Template::action( 'open_main', 'home' ); ?>

	<header class="archive-loop__header container">

		<h1>Search Results</h1>

	</header>

	<div class="container archive-loop__search" >

			<?php 
				$s = filter_input( INPUT_GET, 's', FILTER_SANITIZE_STRING );

			?>

			<form action="/" method="GET" class="archive-loop__search-input">

				<div class="pill-input pill-input__search ">
					<?php echo Icons::use( 'data-search-alt' ); ?>
					<label for="search-query"><?php esc_html_e( 'Search Our Articles', 'vestorfilter' ); ?></label>
					<input type="text" class="value" value="<?php echo esc_attr( $s ); ?>" id="search-query" name="s" placeholder="<?php esc_attr_e( 'Enter a keyword', 'vestorfilter' ); ?>">
				</div>

			</form>
		
		</div>

	<div class="archive-loop__posts container">

		

		<?php if ( have_posts() ) : $count = 1; ?>

			<?php while ( have_posts() ) : the_post(); ?>

			<?php get_template_part( 'parts/excerpt' ); ?>

			<?php $count += 1; endwhile; ?>

		<?php else: ?>

			<div class="archive-loop__empty">
				<p>No articles could be found matching your search.</p>
			</div>

		<?php endif; ?>

	</div>

	
	<div class="archive-loop__pagination container pagination">

		<span class="page-count"></span>

		<?php

		echo paginate_links( array(
			'mid_size' => 2,
			'end_size' => 2,
		) );

		?>

	</div>

	<?php Template::action( 'close_main', 'search' ); ?>

</main>

<?php Template::action( 'after_main', 'search' ); ?>

<?php get_footer(); ?>
