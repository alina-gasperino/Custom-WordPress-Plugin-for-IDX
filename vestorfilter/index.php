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

global $vfdb, $wpdb, $table_prefix;

if ( defined( 'VF_DB_NAME' ) ) {

	$vfdb = new wpdb( 
		defined( 'VF_DB_USER' ) ? VF_DB_USER : DB_USER, 
		defined( 'VF_DB_PASSWORD' ) ? VF_DB_PASSWORD : DB_PASSWORD, 
		VF_DB_NAME, 
		defined( 'VF_DB_HOST' ) ? VF_DB_HOST : DB_HOST,  
	);
	$vfdb->set_prefix( defined( 'VF_DB_PREFIX' ) ? VF_DB_PREFIX : $table_prefix );


} else {

	$vfdb = $wpdb;

}

include 'vestorfilter/loader.php';