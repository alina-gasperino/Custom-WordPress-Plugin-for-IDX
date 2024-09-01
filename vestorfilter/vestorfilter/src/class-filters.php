<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Filters {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Store results
	 *
	 * @var array
	 */
	private static $filters = [];

	private static $descriptions = [];

	public function __construct() {}

	public static function init() {

		$self = apply_filters( 'vestorfilter_filter_instance', new Filters() );

		$self->install();

		do_action( 'vestorfilter_filter_init', $self );

		return $self;

	}

	private function install() {

		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = $this;

		self::$filters = [
			/*'search' => [
				'label'   => 'Custom Keyword Search',
				'desc'    => 'Keywords',
				'display' => false,
				'query'   => function ( $values ) {
					$meta_query = [];
					$values = explode( ' ', $values );
					foreach ( $values as $value ) {
						$value = filter_var( $value, FILTER_SANITIZE_STRING );
						$meta_query[] = [
							'key'        => 'description',
							'comparison' => 'LIKE',
							'value'     => "%$value%",
						];
					}
					return [
						'text' => $meta_query,
					];
				},
				'function' => '__return_null',
				'format'   => '__return_null',
				'value'    => '__return_null',
				'icon'     => 'search',
				
			],*/
			'ppsf' => [
				'label'    => 'Best price per square foot',
				'desc'     => 'Price per ft&sup2;',
				'data'     => 'sqft',
				'data_scale'=> [ 
					'sf'         => [ 1000, 5000 ],
					'55'         => [ 1000, 5000 ],
					'mf'         => [ 2000, 4000, 10000 ],
					'commercial' => [ 1000, 3500, 10000 ],
					'condos'     => [ 800, 1500, 3000 ],
				],
				'function' => function( $property ) {
					$price = $property->get_prop( 'price', true );
					$sqft  = $property->get_prop( 'sqft', true );
					if ( empty( $sqft ) ) {
						$sqft = $property->get_prop( 'sqft_mf' );
					}
					if ( empty( $sqft ) ) {
						$sqft = $property->get_prop( 'sqft_gross', true );
					}

					if ( empty( $price ) || empty( $sqft ) ) {
						return null;
					}

					if ( ! is_numeric ( $price ) || ! is_numeric( $sqft ) || $sqft == 0 ) {
						return null;
					}

					return round( $price / $sqft, 2 );
				},
				'format' => function( $value ) {
					return sprintf( '$%s/ft&sup2;', number_format( $value/100, 2 ) );
				},
				'query' => function( $values ) {
					return [
						'data' => [[
							'key'        => 'ppsf',
							'order'      => 'ASC',
							'null_last'  => true,
							'show_empty' => true,
						]],
					];
				},
				'rules' => [
					'property-type' => [
						'!land',
					],
				],
				'alt'  => 'ppls',
				'icon' => 'data-sqft',
				'default' => 'all',
			],
			'bpd'  => [
				'label' => 'Biggest price drop last 7 days',
				'desc'  => 'Recent price drop',
				'data'     => 'sqft',
				'data_scale'=> [ 
					'sf'         => [ 1000, 5000 ],
					'55'         => [ 1000, 5000 ],
					'mf'         => [ 2000, 4000, 10000 ],
					'commercial' => [ 1000, 3500, 10000 ],
					'condos'     => [ 800, 1500, 3000 ],
				],
				'function' => function( $property ) {

					$price    = $property->get_prop( 'price', true );
					$drop     =  $property->get_prop( 'price_drop_recent', true );
					//$modified = strtotime( $this->get_meta( 'modified' ) );

					if ( $price >= $drop ) {
						return null;
					}

					/*if ( $modified < time() - 3600*24*7 ) {
						return null;
					}*/
					
					return $drop - $price;
				},
				'format' => function( $value ) {
					//$value = round( $value/100000, 1 );
					return sprintf( '$%s', number_format( $value/100 , 0 ) );
				},
				'query' => function( $values ) {
					return [
						'data' => [
							[
								'key'        => 'bpd',
								'order'      => 'DESC',
								'null_last'  => true,
								'show_empty' => false,
							],[
								'key'        => 'last_updated',
								'comparison' => '>=',
								'value'      => ( time() - 3600*24*7 ) * 100,
								'null_last'  => true,
								'show_empty' => false,
							],
						],
					];
				},
				'icon' => 'data-price_drop',
				'default' => 'all',
			],
			'ppls' => [
				'label' => 'Best price per acre',
				'desc'  => 'Price per acre',
				'data'  => 'lot',
				'data_scale'=> [
					'land'       => [ 0.2, 1, 10 ],
					'sf'         => [ 0.2, 1 ],
					'55'         => [ 0.2, 1 ],
					'mf'         => [ 0.2, 1 ],
				],
				'function' => function( $property ) {
					$price = $property->get_prop( 'price', true );
					$acres = (float) $property->get_prop( 'lot', true );

					if ( empty( $acres ) ) {
						$acres = $property->get_prop( 'lot_est', true );
						if ( preg_match( '/[0-9.,+]*/i', $acres, $value ) ) {
							$unit_acres = ( strpos( $acres, 'SqFt' ) === false );
							$value = (float) str_replace( [ ',', '+' ], '', $value[0] );
							if ( ! $unit_acres ) {
								$value = $value / 42560;
							}
							$acres = $value;
						} else {
							return null;
						}
					}
					
					if ( empty( $price ) || empty( $acres ) || ! is_numeric( $price ) || ! is_numeric( $acres ) ) {
						return null;
					}

					return round( $price / $acres, 2 );
				},
				'format' => function( $value ) {
					if ( $value > 99999900 ) {
						return sprintf( '$%sM/ac', round( $value/100000000, 2 ) );
					} else {
						return sprintf( '$%sK/ac', round( $value/100000, 1 ) );
					}
				},
				'query' => function( $values ) {
					return [
						'data' => [[
							'key'        => 'ppls',
							'order'      => 'ASC',
							'null_last'  => true,
							'show_empty' => true,
						]],
					];
				},
				'rules' => [
					'property-type' => [
						'!condos',
					],
				],
				'default' => 'land',
				'icon' => 'data-lot',
			],
			'bpc'  => [
				'label' => 'Best priced condo',
				'desc'  => 'Cost over 5 years',
				'data_scale'=> [ 
					'condos'     => [ 800, 1500, 3000 ],
				],
				'function' => function( $property ) {
					$price    = $property->get_prop( 'price', true );
					$hoa      = $property->get_prop( 'hoa' );
					$tax      = $property->get_prop( 'taxes', true ) ?: 0;
					
					if ( empty( $price ) ) {
						return null;
					}

					if ( empty( $hoa ) ) {
						return null;
					}
					
					$hoa *= ( 12 * 5 );

					if ( ! empty( $tax ) ) {
						$tax *= 5;
					}

					return $price + $hoa + $tax;
				},
				'format' => function( $value ) {
					$value = round( $value/100000, 1 );
					return sprintf( '$%sK', $value );
				},
				'query' => function( $values ) {
					return [
						'data' => [[
							'key'        => 'bpc',
							'order'      => 'ASC',
							'null_last'  => true,
							'show_empty' => false,
						]],
					];
				},
				'rules' => [
					'property-type' => [
						'condos',
					],
				],
				'default' => 'condos',
				'alt'     => 'ppsf',
				'icon'    => 'data-condo',
				'display_order' => 1,
				
			],
			'ppu'  => [
				'label' => 'Best price per unit',
				'desc'  => 'Price per unit',
				'data_scale'=> [ 
					'mf'         => [ 2000, 4000, 10000 ],
				],
				'function' => function( $property ) {
					$price = $property->get_prop( 'price', true );
					$units = $property->get_prop( 'units', true );
					
					if ( empty( $price ) || empty( $units ) ) {
						return null;
					}

					return round( $price / $units, 2 );
				},
				'format' => function( $value ) {
					$value = round( $value/100000, 1 );
					return sprintf( '$%sK/unit', $value );
				},
				'query' => function( $values ) {
					return [
						'data' => [[
							'key'        => 'ppu',
							'order'      => 'ASC',
							'null_last'  => true,
							'show_empty' => true,
						]],
					];
				},
				'rules' => [
					'property-type' => [
						'mf',
					],
				],
				'default' => 'mf',
				'icon' => 'data-units',
				'display_order' => 1,
			],
			'ppbc' => [
				'label' => 'Best price per bedroom',
				'desc'  => 'Price per bedroom',
				'data_scale'=> [ 
					'sf'         => [ 1000, 5000 ],
					'55'         => [ 1000, 5000 ],
					'mf'         => [ 2000, 4000, 10000 ],
					'condos'     => [ 800, 1500, 3000 ],
				],
				'function' => function( $property ) {
					$price = $property->get_prop( 'price', true );
					$beds = $property->get_prop( 'bedrooms', true );

					if ( empty( $beds ) ) {
						$beds = $property->get_prop( 'bedrooms_mf' );
					}
					
					if ( empty( $price ) || empty( $beds ) ) {
						return null;
					}
					$beds = preg_replace("/[^0-9.]/", "", $beds );
					
					return round( $price / $beds, 2 );
				},
				'format' => function( $value ) {
					$value = round( $value/100000, 1 );
					return sprintf( '$%sK/bed', $value );
				},
				'query' => function( $values ) {
					return [
						'data' => [[
							'key'        => 'ppbc',
							'order'      => 'ASC',
							'null_last'  => true,
							'show_empty' => true,
						]],
					];
				},
				'rules' => [
					'property-type' => [ 'all', 'sf', 'mf', '55', 'condos' ],
				],
				'default' => 'all',
				'icon' => 'data-bedrooms',
			],
			'elc'  => [
				'label' => 'Extra Living Quarters',
				'desc'  => 'Extra Living Quarters',
				'function' => function( $property ) {
					$features = $property->get_prop( 'int_features' ) 
							  . $property->get_prop( 'ext_features' ) 
							  . $property->get_prop( 'description' );

					$keywords = [
						'auxiliary',
						'second residence',
						'separate living quarters',
						'apartment',
						'aux living unit',
						'ADU',
						'mother-in-law',
						'mother in law',
						'additional dwelling',
						'additional living quarter',
					];

					if ( preg_match( '/(' . implode( '|', $keywords ).')/i', $features ) ) {
						return 1;
					}

					return null;
				},
				'format'   => function( $value ) {
					return empty( $value ) ? 'No' : 'Yes';
				},
				'query'    => function( $values ) {
					return [
						'data' => [[
							'key'        => 'elc',
							'comparison' => 'EXISTS',
							'null_last'  => false,
							'show_empty' => false,
						]],
					];
				},
				'rules' => [
					'property-type' => [ 'sf' ],
				],
				'default' => 'sf',
				'alt'  => 'lotm',
				'icon' => 'data-extra_quarters',
				'data' => 'sqft',
				'data_scale'=> [ 
					'sf'         => [ 1000, 5000 ],
				],
			],
			'notm' => [
				'label' => 'Newest on the market',
				'desc'  => 'Time on market',
				'query' => function( $values ) {
					return [
						'data' => [[
							'key'        => 'onmarket',
							'order'      => 'DESC',
						]],
					];
				},
				'format' => function ( $value ) {
					return sprintf( '%d days', $value );
				},
				'value' => function ( $property ) {
					$date = Cache::get_data_value( $property->ID(), 'onmarket' ) / 100;
					$days = floor( ( time() - $date ) / ( 24 * 3600 ) );
					return $days;
				},
				'icon' => 'data-dom',
				'data' => 'sqft',
				'data_scale'=> [ 
					'sf'         => [ 1000, 5000 ],
					'55'         => [ 1000, 5000 ],
					'mf'         => [ 2000, 4000, 10000 ],
					'commercial' => [ 1000, 3500, 10000 ],
					'condos'     => [ 800, 1500, 3000 ],
				],
			],
			'lotm' => [
				'label' => 'Longest on the market',
				'desc'  => 'Time on market',
				'query' => function( $values ) {
					return [
						'data' => [[
							'key'        => 'onmarket',
							'order'      => 'ASC',
						]],
					];
				},
				'format' => function ( $value ) {
					return sprintf( '%d days', $value );
				},
				'value' => function ( $property ) {
					$date = Cache::get_data_value( $property->ID(), 'onmarket' ) / 100;
					$days = floor( ( time() - $date ) / ( 24 * 3600 ) );
					return $days;
				},
				'icon' => 'data-dom',
				'data' => 'sqft',
				'data_scale'=> [ 
					'sf'         => [ 1000, 5000 ],
					'55'         => [ 1000, 5000 ],
					'mf'         => [ 2000, 4000, 10000 ],
					'commercial' => [ 1000, 3500, 10000 ],
					'condos'     => [ 800, 1500, 3000 ],
				],
			],
			'fixer' => [
				'label'    => 'Smart Fixer List',
				'desc'     => 'Smart Fixer List',
				'function' => function( $property ) {
					
					$condition = $property->get_prop( 'condition' );
					if ( stripos( $condition, 'fixer' ) !== false ) {
						return 1;
					}

					$desc = $property->get_prop( 'description' );
					if ( preg_match( '/(as is|as-is|fixer|cash only)/i', $desc ) ) {
						return 1;
					}

					return null;
				},
				'display'  => true,
				'reload'   => true,
				'format'   => function( $value ) {
					return empty( $value ) ? 'No' : 'Yes';
				},
				'query'    => function( $values ) {
					return [
						'data' => [[
							'key'        => 'fixer',
							'comparison' => 'EXISTS',
							'null_last'  => false,
							'show_empty' => false,
						]],
					];
				},
				'icon' => 'data-fixer',
				'data'     => 'sqft',
				'data_scale'=> [ 
					'sf'         => [ 1000, 5000 ],
					'55'         => [ 1000, 5000 ],
					'mf'         => [ 2000, 4000, 10000 ],
					'commercial' => [ 1000, 3500, 10000 ],
					'condos'     => [ 800, 1500, 3000 ],
				],
			],
			
			'3p'   => [
				'label'    => 'Foreclosure List',
				'desc'     => 'Foreclosure List',
				'function' => function( $property ) {
					$fc = $property->get_prop( 'foreclosure_yn', true );
					if ( $fc !== 'Yes' ) {
						return null;
					}

					return 1;
				},
				'display'  => false,
				'reload'   => true,
				'format'   => function( $value ) {
					return empty( $value ) ? 'No' : 'Yes';
				},
				'query'    => function( $values ) {
					return [
						'data' => [[
							'key'        => '3p',
							'comparison' => 'EXISTS',
							'null_last'  => false,
							'show_empty' => false,
						]],
					];
				},
				'icon' => 'data-foreclosure',
			],
			'ss'   => [
				'label'    => 'Short Sale List',
				'desc'     => 'Short Sale List',
				'function' => function( $property ) {
					$fc = $property->get_prop( 'shortsale_yn', true );
					if ( $fc !== 'Yes' ) {
						return null;
					}

					return 1;
				},
				'display'  => false,
				'reload'   => true,
				'format'   => function( $value ) {
					return empty( $value ) ? 'No' : 'Yes';
				},
				'query'    => function( $values ) {
					return [
						'data' => [[
							'key'        => 'ss',
							'comparison' => 'EXISTS',
							'null_last'  => false,
							'show_empty' => false,
						]],
					];
				},
				'icon' => 'data-shortsale',
			],
			'auc'   => [
				'label'    => 'Auction List',
				'desc'     => 'Auction List',
				'function' => function( $property ) {
					$auc = $property->get_prop( 'auction_yn' );
					if ( $auc !== 'Yes' ) {
						return null;
					}

					return 1;
				},
				'display'  => false,
				'format'   => function( $value ) {
					return empty( $value ) ? 'No' : 'Yes';
				},
				'reload'   => true,
				'query'    => function( $values ) {
					return [
						'data' => [[
							'key'        => 'auc',
							'comparison' => 'EXISTS',
							'null_last'  => false,
							'show_empty' => false,
						]],
					];
				},
				'icon' => 'data-auction',
			],
			
		];

		self::$filters = apply_filters( 'vestorfilter_standard_filters', self::$filters );

		do_action( 'vestorfilter_filter_installed', $this );

	}

	public static function instance() {
		return self::$instance;
	}

	public static function get_all( $reorder = false, $get_hidden = true ) {

		$filters = self::$filters;

		if ( ! $get_hidden ) {
			foreach( $filters as $key => $data ) {
				if ( ( $data['display'] ?? true ) === false ) {
					unset( $filters[$key] );
				}
			}
		}

		if ( $reorder ) {
			uasort( $filters, function ( $a, $b ) {
				
				if ( ! isset( $a['display_order'] ) && ! isset( $b['display_order'] ) ) {
					return 0;
				}
				if ( ! isset( $a['display_order'] ) ) {
					return 1;
				}
				if ( ! isset( $b['display_order'] ) ) {
					return -1;
				}
				return $a['display_order'] < $b['display_order'] ? -1 : 1;
			} );
		}

		$keys = array_keys( $filters );

		return $keys;

	}

	public static function get_filter_name( $key ) {

		return ! empty( self::$filters ) && isset( self::$filters[ $key ] ) ? self::$filters[ $key ]['label'] : null;

	}

	public static function get_filter_name_singular( $key ) {

		return self::$filters[ $key ]['desc'] ?? self::$filters[ $key ]['label'];

	}

	public static function get_filter_icon( $key ) {

		return self::$filters[ $key ]['icon'] ?? 'bxs-grid';

	}

	public static function get_filter_rules( $key ) {

		return self::$filters[ $key ]['rules'] ?? [];

	}

	public static function get_filter_query( $key ) {

		$query = self::$filters[ $key ]['query'];
		if ( is_callable( $query ) ) {
			return ($query)( [] );
		}
		return $query;

	}

	public static function get_filter_data_range( $key ) {

		return self::$filters[ $key ]['data'] ?? '';

	}

	public static function is_filter_live( $key ) {

		return empty( self::$filters[ $key ]['reload'] ?? true );

	}

	public static function get_filter_data_scale( $key ) {

		return self::$filters[ $key ]['data_scale'] ?? null;

	}

	public static function get_alt_filter( $key ) {

		return self::$filters[ $key ]['alt'] ?? false;

	}

	public static function get_default_type( $key ) {

		return self::$filters[ $key ]['default'] ?? 'sf';

	}

	public static function get_filter_description( $key ) {

		if ( empty( self::$descriptions ) ) {
			self::$descriptions = Settings::get_filter_options( 'desc' );
		}

		return self::$descriptions[ $key ] ?? '';

	}

	public static function get_formatted_value( $key, $value ) {

		if ( empty( self::$filters[$key] ) || empty( self::$filters[$key]['format'] ) ) {
			return $value;
		}

		if ( empty( $value ) && empty( self::$filters[ $key ]['display'] ) ) {
			return '';
		}

		return call_user_func( self::$filters[ $key ]['format'], $value );

	}

	public static function get_stored_value( $property, $key ) {

		if ( isset( self::$filters[ $key ]['value'] ) ) {
			return call_user_func( self::$filters[ $key ]['value'], $property );
		}

		return Cache::get_data_value( $property->ID(), $key );

	}

	public static function get_value( $property, $key ) {

		if ( empty( self::$filters[$key] ) || empty( self::$filters[$key]['function'] ) ) {
			return null;
		}

		return call_user_func( self::$filters[ $key ]['function'], $property );

	}

	public static function get_query( $key, $values = [] ) {

		if ( empty( self::$filters[$key] ) || empty( self::$filters[ $key ]['query'] ) ) {
			return null;
		}

		return call_user_func( self::$filters[ $key ]['query'], $values );

	}

	public static function get_formats() {

		$formats = [];
		foreach( self::get_all( false, false ) as $key ) {
			$formats[ $key ] = [
				'label' => str_replace( 
					[ '100', '10', '1.000', '1.00', '1.0', '1', '0K' ],
					'{{value}}',
					Filters::get_formatted_value( $key, 100 )
				),
				'range' => Filters::get_filter_data_range( $key ) ?: null,
				'scale' => Filters::get_filter_data_scale( $key ) ?: null,
				'rules' => Filters::get_filter_query( $key ) ?: null,
			];
		}

		return $formats;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Filters', 'init' ) );
