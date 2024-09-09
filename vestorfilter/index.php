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