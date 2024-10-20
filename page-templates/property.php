<?php
/**
 * Template Name: Single Property
 *
 * @package VestorTheme
 * @subpackage Archive
 * @since 1.0.0
 */

namespace VestorTheme;

use VestorFilter\Util\Template as Template;
use VestorFilter\Cache as Cache;
use VestorFilter\Property as Property;
use VestorFilter\Filters as Filters;

define( 'IS_PROPERTY_TEMPLATE', true );

global $property;

// $mls_id = get_query_var( 'mlsid' );
$mls_id = "22161940";
if ( empty( $mls_id ) ) {
	include '404.php';
	exit;
}
$property_row = Cache::get_property_by( 'MLSID', $mls_id );
if ( empty( $property_row ) || $property_row[0]->hidden > 0 ) {
	include '404.php';
	exit;
}

add_filter( 'body_class', function( $classes ) use ( $property_row ) {
	$classes[] = 'vf-propery-' . $property_row[0]->ID;
	return $classes;
});

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'lightgallery' );
}, 20 );

add_filter( 'vestortheme__allow_jquery', '__return_true' );

//$property = new Property( $property_row[0], false );
$cache = Property::get_cache( [ 'id' => $property_row[0]->ID ], \VestorFilter\Plugin::$debug_mode );
if ( is_wp_error( $cache ) ) {
	wp_safe_redirect( get_bloginfo( 'url' ) );
	exit;
}

add_filter( 'get_canonical_url', function ( $url ) use ( $cache ) {
	return $cache['url'];
} );

add_filter( 'wp_title', function ( $title ) use ( $cache ) {
	return $cache['title'] . ' - ' . get_bloginfo('title');
}, 99 );

add_filter( 'the_seo_framework_pre_get_document_title', function ( $title ) use ( $cache ) {
	return $cache['title'] . ' - ' . get_bloginfo('title');
}, 99 );

add_filter( 'vestorfilter_og_tags', function() use ( $cache ) {
	return [
		'title'       => $cache['title'] . ' - ' . get_bloginfo('title'),
		'image'       => $cache['photos'][0]->thumbnail,
		'url'         => $cache['url'],
		'description' => '',
		'type'        => 'website',
	];
} );

$filtered_html = \VestorFilter\Templator::filter_html( $cache['html'], $cache );

// lock_navigation();
get_header();
the_post();

//wp_enqueue_script( 'vestorhouse-property' );
wp_enqueue_script( 'vestorhouse-favorites' );
wp_enqueue_script( 'vestorhouse-map' );

?>

<?php Template::action( 'before_main', 'property' ); ?>



<main class="website-content property-template" role="main" data-vestor-property data-mlsid="<?php echo esc_attr( $mls_id ); ?>">

	<?php Template::action( 'open_main', 'property' ); ?>

	<article id="property" <?php post_class( 'page__wrap' ); ?> data-property="<?php echo $property_row[0]->ID ?>">

		<?php echo $filtered_html; ?>

	</article>

	<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'search-filters' ); ?>

</main>
<script type="application/javascript">
	var thisProperty = <?= json_encode( $cache ) ?>;
</script>

<div style="display:none" id="share-links-template"><?php include plugin_dir_path(__DIR__) . 'parts/share-template.php'; ?></div>
<div style="display:none" id="agent-card-template"><?php include plugin_dir_path(__DIR__) . 'parts/agent-template.php'; ?></div>
<div style="display:none" id="tour-card-template"><?php include plugin_dir_path(__DIR__) . 'parts/tour-template.php'; ?></div>
<div style="display:none" id="calculator-template"><?php include plugin_dir_path(__DIR__) . 'parts/calculator-template.php'; ?></div>

<?php Template::action( 'after_main', 'property' ); ?>
<?php get_footer(); ?>
