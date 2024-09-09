<?php
/**
 * Plugin Name: VestorFilter Core
 * Version: 1.0.0
 * Author: Vestor Filter
 * Author URI: https://vestorfilter.com
 * License: GPLv2
 *
 * @package VestorFilter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GOOGLE_MAP_KEY', 'AIzaSyBlJeUhIAmEBAWL9jOaAAMHtlJe5_X3Xe8' );
define( 'GOOGLE_CLIENT_ID', '262677866211-faeo4sjteuu1e6son4luvvjbigmchne4' );
define( 'GOOGLE_CLIENT_SECRET', 'mPZ8SQ85-WO_6Vk-uwKeLt50' );
define( 'FACEBOOK_APP_ID', '795764537891600' );
define( 'FACEBOOK_APP_SECRET', 'bf2b3399a3bed129efaedcfbab9c8da6' );
define( 'LINKEDIN_CLIENT_ID', '788y19ij3zhdzl' );
define( 'LINKEDIN_CLIENT_SECRET', 'RRSNELwMaMsBPnr7' );
 

define( 'TWILIO_TOKEN', '26002c558e6985e5ce7696ef13b87c1b' );
define( 'TWILIO_NUMBER', '+15034003757' );
define( 'IMAGE_SWAP_DIR', __DIR__ . '/.tmp/' );

define( 'VF_MAP_STATES', ['OR','WA'] );
define( 'VF_MAP_NEBOUND', [ 48.9548805, -117.8193769 ] );
define( 'VF_MAP_SWBOUND', [ 41.9701896, -124.9595301 ] );
define( 'VF_ALLOWED_FEEDS', [ 647 ] );
define( 'VF_DB_NAME', 'rfvjfqkjdr' );
define( 'VF_IMG_URL', '//images.vestorhouse.com/photos/' );
define( 'VF_IMG_PATH', __DIR__ . '/.tmp/' );

$vf_host = "45.76.244.68";
$vf_user = "rfvjfqkjdr";
$vf_db_password = "YGXgW2wKfe";
$vf_db_name = "rfvjfqkjdr";

global $vfdb, $wpdb, $table_prefix;

if ( defined( 'VF_DB_NAME' ) ) {
	$vfdb = new wpdb( $vf_user, $vf_db_password, $vf_db_name, $vf_host );
	$vfdb->set_prefix( defined( 'VF_DB_PREFIX' ) ? VF_DB_PREFIX : $table_prefix );

} else {

	$vfdb = $wpdb;

}

include 'vestorfilter/loader.php';