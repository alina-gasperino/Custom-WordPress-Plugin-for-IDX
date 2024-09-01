<?php

namespace VestorFilter\Hooks;

use VestorFilter\Location as Location;
use VestorFilter\Settings as Settings;
use VestorFilter\Property as Property;

add_filter( 'the_seo_framework_sitemap_additional_urls', 'VestorFilter\Hooks\add_property_urls_to_sitemap' );

function add_property_urls_to_sitemap( $custom_urls = [] ) {


	$base_url = Property::base_url();

	$locations = Location::get_all_data();
	$base = untrailingslashit( Settings::get_page_url( 'search' ) );

	foreach( $locations as $location ) {

		$custom_urls[ htmlentities( $base . '/' . Location::get_slug( $location ) . '/?location=' . $location->ID . '&property-type=all' ) ] = [
			'lastmod'  => null,
			'priority' => null,
		];

	}
	
	return $custom_urls;

}

/*
function add_property_urls_to_sitemap( $extend = '' ) {

	global $vfdb;

	$query = "SELECT * FROM {$vfdb->prefix}propertycache";
	if ( defined( 'VF_ALLOWED_FEEDS' ) ) {
		$query .= ' WHERE `post_id` IN (' . implode( ',', VF_ALLOWED_FEEDS ) . ')';
	}

	$base_url = \VestorFilter\Property::base_url();

	$page = 0;
	do {

		$properties = $vfdb->get_results( $query . ' LIMIT ' . ($page*1000) . ',1000' );
		$count = count( $properties );

		foreach( $properties as $prop ) {
			$extend .= '<url>'
					.  '<loc>' . $base_url . $prop->MLSID . '/' . $prop->slug . '</loc>'
					.  '<lastmod>' . date( 'c', $prop->modified ) . '</lastmod>'
					.  '</url>';
		}

		unset( $properties );
		$page ++;

	} while( $count > 0 );

	return $extend;

}
*/

