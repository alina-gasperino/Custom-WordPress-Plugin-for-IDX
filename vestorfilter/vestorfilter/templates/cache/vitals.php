<?php

\VestorFilter\Util\Template::get_part(
	'vestorfilter',
	'cache/datatable',
	[
		'fields'   => [ 
			'price_range',
			'units',
			'bedrooms',
			'bedrooms_mf',
			'bathrooms',
			'bathrooms_mf',
			'rent',
			'cap',
			'sqft',
			'sqft_mf',
			'sqft_gross',
			'lot',
			'lot_est',
			'school_elementary',
			'school_middle',
			'school_high',
			'zoning',
			'dom'
		],
		'header'   => $header,
		'property' => $property,
		'icons'    => isset( $icons ) ? $icons : true,
	],
);

?>