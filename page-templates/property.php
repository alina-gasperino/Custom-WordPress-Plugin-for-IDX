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
use VestorFilter\Source as Source;
use \Aws\S3\S3Client;
use \Aws\Exception\AwsException;
use \Aws\Credentials\CredentialProvider;

define( 'IS_PROPERTY_TEMPLATE', true );

global $property;

$test = Source::get_object(647);
$connection = new \VestorFilter\Source( 647 );
$connection->connect();
print_r($test);

if ( is_user_logged_in() ) {
	if ( current_user_can( 'see_leads' ) ) {
		$is_agent = true;
		$friends = get_users( [
			'role__in' => [ 'subscriber' ],
			'orderby'  => 'first_name',
			'number'   => -1,
		] );
	}
}

include plugin_dir_path(__DIR__) . 'parts/save-map-dialog.php';

// $mls_id = get_query_var( 'mlsid' );
$mls_id = "23137327";
if ( empty( $mls_id ) ) {
	include '404.php';
	exit;
}

get_header();
the_post();

wp_enqueue_script( 'vestorhouse-favorites' );
wp_enqueue_script( 'vestorhouse-map' );

?>

<?php Template::action( 'before_main', 'property' ); ?>

<?php Template::action( 'after_main', 'property' ); ?>
<?php get_footer(); ?>
<?php $compliance_text = get_option('my_idx_options_general')['footer_text']; ?>
<?php echo $compliance_text; ?>