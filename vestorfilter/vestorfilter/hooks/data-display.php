<?php 

namespace VestorFilter\Hooks;

add_filter( 'vestorfilter_data_formatted__sqft_mf', 'VestorFilter\Hooks\hide_dupe_sqft', 10, 3 );
add_filter( 'vestorfilter_data_formatted__sqft_gross', 'VestorFilter\Hooks\hide_dupe_sqft', 10, 3 );

function hide_dupe_sqft( $output, $value, $property ) {

	$original = $property->get_prop( 'sqft' );

	if ( $value === $original ) {
		return null;
	}

	return $output;
}
