<?php

namespace VestorFilter\Hooks;

function format_bathroom_halves( $value ) {

	if ( $value - floor( $value ) > 0 ) {
		return absint( $value ) . '&frac12;';
	}

	return absint( $value );

}

add_filter( 'vestorfilters_display_meta__bathrooms', 'VestorFilter\Hooks\format_bathroom_halves' );

function replace_bathroom_halves( $output ) {

	return str_replace( ".1", "&frac12;", $output );

}
add_filter( 'vestortemplate_filter_value__bathrooms', 'VestorFilter\Hooks\replace_bathroom_halves' );

add_filter( 'vestorfilter_data_formatted__status', function( $value ) {
	$value = ucwords( strtolower( $value ) );
	return $value;
} );

