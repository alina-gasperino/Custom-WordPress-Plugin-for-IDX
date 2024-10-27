<?php

namespace VestorTheme;

function mortgage_calculator_range_wrapper( $output ) {

	return sprintf( '<span class="frm_range_value_wrapper">%s</span>', $output );

}

add_filter( 'frm_range_output', 'VestorTheme\mortgage_calculator_range_wrapper' );

function share_form_values( $attrs = [] ) {

	$page = get_queried_object();

	$share_btns = \VestorFilter\Social::get_share_links( [
		'url'   => 'REPLACE_THIS',
		'image' => get_the_post_thumbnail_url( $page ),
	] );

	$share_list = '<li>' . implode( '</li><li>', $share_btns ) . '</li>';
	$share_list .= '<li><button type="button" class="btn btn-link btn-link--copy" data-copy-url="REPLACE_THIS"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M8.465,11.293c1.133-1.133,3.109-1.133,4.242,0L13.414,12l1.414-1.414l-0.707-0.707c-0.943-0.944-2.199-1.465-3.535-1.465 S7.994,8.935,7.051,9.879L4.929,12c-1.948,1.949-1.948,5.122,0,7.071c0.975,0.975,2.255,1.462,3.535,1.462 c1.281,0,2.562-0.487,3.536-1.462l0.707-0.707l-1.414-1.414l-0.707,0.707c-1.17,1.167-3.073,1.169-4.243,0 c-1.169-1.17-1.169-3.073,0-4.243L8.465,11.293z"></path><path d="M12,4.929l-0.707,0.707l1.414,1.414l0.707-0.707c1.169-1.167,3.072-1.169,4.243,0c1.169,1.17,1.169,3.073,0,4.243 l-2.122,2.121c-1.133,1.133-3.109,1.133-4.242,0L10.586,12l-1.414,1.414l0.707,0.707c0.943,0.944,2.199,1.465,3.535,1.465 s2.592-0.521,3.535-1.465L19.071,12c1.948-1.949,1.948-5.122,0-7.071C17.121,2.979,13.948,2.98,12,4.929z"></path></svg></button></li>';

	return sprintf( '<div data-form-share class="form-share">Share On: <ul class="menu share-icons">%s</ul></div>', $share_list );	

}

add_shortcode( 'share-form-values', 'VestorTheme\share_form_values' );


function get_calculator_value( $old_value, $field ) {

	if ( isset( $_GET[ 'field_' . $field->field_key ] ) ) {

		if ( ! is_numeric( $_GET[ 'field_' . $field->field_key ] ) ) {
			return $old_value;
		}

		$value = filter_input( INPUT_GET, 'field_' . $field->field_key, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION  );
		
		if ( empty( $value ) && $value !== 0 ) {
			return $old_value;
		}

		return $value;

	}

	return $old_value;

}

add_filter( 'frm_get_default_value', 'VestorTheme\get_calculator_value', 99, 2 );
/*
function get_calculator_property_value( $old_value, $field, $is_default ) {

	if ( defined( 'IS_PROPERTY_TEMPLATE' ) ) {

		global $property;
		$nf = new \NumberFormatter( 'en_EN', \NumberFormatter::DECIMAL );

		if ( $field->field_key === 'mortgage_amt' ) {
			return round( $nf->parse( $property->get_prop( 'price' ), \NumberFormatter::TYPE_INT32 ) );
		}
		if ( $field->field_key === 'downpayment_amt' ) {
			return round( $nf->parse( $property->get_prop( 'price' ), \NumberFormatter::TYPE_INT32 ) * 0.2 );
		}
		if ( $field->field_key === 'property_tax_value' ) {
			return round( $nf->parse( $property->get_prop( 'taxes', true ), \NumberFormatter::TYPE_INT32 ) );
		}
		if ( $field->field_key === 'hoa_fee' ) {
			return round( $nf->parse( $property->get_prop( 'hoa', true ), \NumberFormatter::TYPE_INT32 ) );
		}
		if ( $field->field_key === 'hoa_freq' && $is_default ) {
			$freq = $property->get_prop( 'hoa_fee_freq', true );
			
			switch ( $freq ) {
				case 'Semi-Annually':
					return "6";
				break;
				
				case 'Quarterly':
					return "3";
				break;
				
				case 'Annually':
					return "12";
				break;

				default:
					return "1";
				break;
			}
			
		}

	}

	return $old_value;

}

add_filter( 'frm_get_default_value', 'VestorTheme\get_calculator_property_value', 90, 3 );
*/
