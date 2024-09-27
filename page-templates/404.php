<?php
/**
 * Template Name: 404
 * @package VestorTheme
 * @subpackage Archive
 * @since 1.0.0
 */

namespace VestorTheme;
use VestorFilter\Util\Template as Template;

wp_safe_redirect( get_bloginfo( 'url' ) );
exit;


lock_navigation();
get_header(); the_post();

?>

<?php Template::action( 'before_main', 'page' ); ?>

<main class="website-content page is-slim" role="main">

	<?php Template::action( 'open_main', 'page' ); ?>

	<article <?php post_class( 'with-blocks page__wrap' ) ?>>

		<header>
			<h1>Error - Page Not Found</h1>
		</header>

		<p>The content you are looking for may have moved. Looking for real estate? Try a <a href="/">property search.</a>

	</article>

	<?php Template::action( 'close_main', 'page' ); ?>

</main>

<?php Template::action( 'after_main', 'page' ); ?>

<?php get_footer(); ?>
