<?php
/**
 * Template Name: Search Results
 * @package VestorTheme
 * @subpackage SearchResults
 * @since 1.0.0
 */

namespace VestorTheme;

use VestorFilter\Util\Template as Template;

if ( isset( $_GET['location' ] ) ) {
echo "tru";
	$location = filter_input( INPUT_GET, 'location', FILTER_SANITIZE_NUMBER_INT );

	if ( strpos( $location, '[' ) !== false ) {
		
	} else {
		
		$location = \VestorFilter\Location::get( $location );
		if ( $location ) {

			$locale_url = trailingslashit( \VestorFilter\Settings::get_page_url( 'search' ) );
			$locale_url .= \VestorFilter\Location::get_slug( $location ) . '/';
			$locale_url = add_query_arg( [ 'location' => $location->ID, 'property-type' => 'all' ], $locale_url );	

			add_filter( 'get_canonical_url', function ( $url ) use ( $locale_url ) {
				
				return $locale_url;

			}, 99 );

			add_filter( 'vestorfilter_og_tags', function() use ( $location, $locale_url ) {
				return [
					'title'       => $location->value . ' Homes for sale - Real Estate Smart Search',
					'url'         => $locale_url,
					'description' => '',
					'type'        => 'website',
				];
			} );

		}
	}
}

if ( is_user_logged_in() ) {
	
	if ( current_user_can( 'see_leads' ) ) {
		$is_agent = true;
		$friends = get_users( [
			'role__in' => [ 'subscriber' ],
			'orderby'  => 'first_name',
			'number'   => -1,
		] );//\VestorFilter\Agents::get_agent_leads( get_current_user_id() );
	} else {
		$friends = \VestorFilter\Account::get_user_friends();
	}
}

get_header(); the_post();

?>

<?php Template::action( 'before_main', 'page' ); ?>

<main class="website-content page" role="main">

	<?php Template::action( 'open_main', 'page' ); ?>

	<article <?php post_class( 'with-blocks page__wrap' ) ?>>

		<h1 class="screen-reader-text"><?php echo esc_html( apply_filters( 'template__search_page_title', 'Search Results' ) ); ?></h1>

		<?php the_content(); ?>

	</article>

	<?php /*if ( ! empty( $location ) && $location->type === 'neighborhood' ) : ?> 

	<a class="website-content__map-btn btn btn-primary" href="<?= isset( $_GET['map'] ) ? remove_query_arg( 'map' ) : add_query_arg( 'map', 'location' ) ?>"><?= isset( $_GET['map'] ) ? 'View List' : 'View Map' ?></a>

	<?php endif;*/ ?>

	<?php Template::action( 'close_main', 'page' ); ?>

</main>

<div style="display:none" id="share-links-template"><?php get_template_part( 'parts/share-template' ); ?></div>
<div style="display:none" id="agent-card-template"><?php get_template_part( 'parts/agent-template' ); ?></div>
<div style="display:none" id="tour-card-template"><?php get_template_part( 'parts/tour-template' ); ?></div>
<div style="display:none" id="calculator-template"><?php get_template_part( 'parts/calculator-template' ); ?></div>


<?php 

if ( ! empty( $friends ) ) {
	get_template_part( 'parts/friends-dialog', null, [ 'friends' => $friends, 'is_agent' => $is_agent ?? false ] );
} 

get_template_part( 'parts/save-map-dialog', null, [ 'friends' => $friends ?? [], 'is_agent' => $is_agent ?? false ] );


?>

<?php Template::action( 'after_main', 'page' ); ?>

<?php get_footer(); ?>
