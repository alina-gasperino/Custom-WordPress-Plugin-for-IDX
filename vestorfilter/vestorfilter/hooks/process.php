<?php

namespace VestorFilter\Hooks;

add_filter( 'vestorfilter_data_before_conversion', 'VestorFilter\Hooks\build_street_address', 10, 2 );

function build_street_address( $data, $source ) {

	$address_filter = $source->get_meta( 'alt_address_line1' );
	if ( empty( $address_filter ) ) {
		return $data;
	}
	
	$fields = explode( ' ', $address_filter );
	
	$address = [];
	foreach( $fields as $field_key ) {
		if ( ! empty( $data[ $field_key ] ) ) {
			$address[] = $data[ $field_key ];
		}
	}
	if ( ! empty( $address ) ) {
		$data['FullStreetAddress'] = implode( ' ', $address );
	}

	$address_unit = $source->get_meta( 'alt_address_unit' );
	if ( ! empty( $address_unit ) && ! empty( $data[ $address_unit ] ) ) {
		$data['FullStreetAddress'] .= ' #' . $data[ $address_unit ];
	}

	return $data;

}

add_filter( 'vestorfilter_data_before_conversion', 'VestorFilter\Hooks\process_unitdata', 10, 2 );

function process_unitdata( $data, $source ) {
	
	$unit_fields = $source->get_data_meta( 'mf_fields', null );

	if ( empty( $unit_fields ) ) {
		return $data;
	}

	//$property_class = $this->get_data_meta( 'type_field', '' );
	//$unit_class     = $this->get_data_meta( 'mf_class', '' );

	$unit_id = 0;
	$types = [];
	foreach( $unit_fields as $fields ) {

		$unitno = ! empty( $fields[ '_datasource_unitno' ] ) ? ( $data[ $fields[ '_datasource_unitno' ] ] ?? null ) : null;
		$beds   = ! empty( $fields[ '_datasource_beds' ] )   ? ( $data[ $fields[ '_datasource_beds' ] ]   ?? '' )   : '';
		$baths  = ! empty( $fields[ '_datasource_baths' ] )  ? ( $data[ $fields[ '_datasource_baths' ] ]  ?? '' )   : '';
		$rent   = ! empty( $fields[ '_datasource_rent' ] )   ? ( $data[ $fields[ '_datasource_rent' ] ]   ?? '' )   : '';
		$sqft   = ! empty( $fields[ '_datasource_sqft' ] )   ? ( $data[ $fields[ '_datasource_sqft' ] ]   ?? '' )   : '';
		$total  = ! empty( $fields[ '_datasource_total' ] )  ? ( $data[ $fields[ '_datasource_total' ] ]  ?? '' )   : '';

		if ( empty( $unitno ) && empty( $beds ) && empty( $baths ) && empty( $rent ) && empty( $sqft ) && empty( $total ) ) {
			continue;
		}

		$unit_id ++;

		if ( ! empty( $sqft ) ) {
			$sqft = preg_replace( '/[^0-9.]/', '', $sqft );
		}
		if ( ! empty( $rent ) ) {
			$rent = preg_replace( '/[^0-9.]/', '', $rent );
		}

		$data[ "UnitType{$unit_id}BedsTotal" ] = $beds;
		$data[ "UnitType{$unit_id}BathsTotal" ] = $baths;
		$data[ "UnitType{$unit_id}Rent" ] = $rent;
		$data[ "UnitType{$unit_id}SqFt" ] = $sqft;
		$data[ "UnitType{$unit_id}UnitsTotal" ] = $total;

		$types[] = $unit_id;

	}

	$data[ 'UnitTypeType' ] = implode( ',', $types );

	return $data;

}

add_filter( 'vestorfilter_after_process_meta_stories', 'VestorFilter\Hooks\process_stories', 10, 1 );

function process_stories( $value ) {

	if ( is_numeric( $value ) ) {
		return $value;
	}

	$lowval = strtolower( $value );

	if ( strpos( $lowval, 'three' ) !== false ) {
		return 3;
	}
	if ( strpos( $lowval, 'two' )  !== false ) {
		return 2;
	}
	if ( strpos( $lowval, 'one' ) !== false ) {
		return 1;
	}
	if ( strpos( $lowval, 'split' ) !== false ) {
		return 1.5;
	}

	return null;

}