<?php

namespace VestorFilter\Hooks;

function dom_query_setup( $field_query ) {

	$now = time() * 100;

	$field_query['key'] = 'onmarket';

	if ( is_array( $field_query['value'] ) ) {
		foreach( $field_query['value'] as &$value ) {
			if ( ! empty( $value ) ) {
				$value = $now - ( $value * 24 * 3600 );
			}
		}
	} elseif ( is_numeric( $field_query['value'] ) ) {
		$field_query['value'] = $now - ( $field_query['value'] * 24 * 3600 );
	}

	if ( $field_query['comparison'] === '<=' ) {
		$field_query['comparison'] = '>=';
	} elseif ( $field_query['comparison'] === '>=' ) {
		$field_query['comparison'] = '<=';
	}


	return $field_query;

}

add_filter( 'vestorfilter_setup_query_field__dom', 'VestorFilter\Hooks\dom_query_setup' );
/*
function search_query_setup( $field_query ) {
	

	var_dump( $field_query );

	return $field_query;
}
add_filter( 'vestorfilter_setup_query_field__search', 'VestorFilter\Hooks\search_query_setup' );
*/

function sold_status( $value ) {

	if ( $value === 'sold_1yr' ) {
		return 'sold';
	}

	return $value;

}

add_filter( 'vestorfilter_get_query_index__status', 'VestorFilter\Hooks\sold_status' );

function sold_in_last_year( $query, $filters ) {

	if ( isset( $filters['status'] ) && $filters['status'] === 'sold_1yr' ) {
		$query['data'][] = [
			'key'        => 'sold',
			'value'      => ( time() * 100 ) - ( 36500 * 24 * 3600 ),
			'comparison' => '>=',
		];
	}

	return $query;

}

add_filter( 'vestorfilter_query__after_setup', 'VestorFilter\Hooks\sold_in_last_year', 10, 2 );


function oh_query_setup( $field_query ) {

	$now = time() * 100;

	//$field_query['key'] = 'next_oh';

	$field_query['value'] = [ 
		strtotime( '12:01 am today' ) * 100, 
		$now + ( $field_query['value'] * 24 * 3600 ) 
	];

	$field_query['comparison'] = 'BETWEEN';
	
	/*if ( $field_query['comparison'] === '<=' ) {
		$field_query['comparison'] = '>=';
	} elseif ( $field_query['comparison'] === '>=' ) {
		$field_query['comparison'] = '<=';
	}*/

	$field_query['show_empty'] = false;
	

	return $field_query;

}

add_filter( 'vestorfilter_setup_query_field__oh', 'VestorFilter\Hooks\oh_query_setup' );
