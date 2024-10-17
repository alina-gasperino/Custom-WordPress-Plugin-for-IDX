<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
    die();
}

class Data {

    /**
     * A pointless bit of self-reference
     *
     * @var object
     */
    private static $instance = null;

    public function __construct() {}

    public static function init() {

        $self = apply_filters( 'vestorfilter_data_instance', new Data() );

        $self->install();

        do_action( 'vestorfilter_data_init', $self );

        return $self;

    }

    private function install() {

        if ( ! is_null( self::$instance ) ) {
            return self::$instance;
        }

        self::$instance = $this;

        add_filter( 'vestortemplate_filter_value__price', [ $this, 'filter_price_label' ], 10, 2 );
        add_filter( 'vestortemplate_filter_value__location', [ $this, 'filter_location_label' ], 10, 2 );

        add_filter( 'vestorfilter_status_available_options', [ $this, 'filter_status_options' ] );

        do_action( 'vestorfilter_data_installed', $this );



    }

    public static function instance() {
        return self::$instance;
    }

    public static function get_property_types() {

        return [
            'sf'         => 'Single Family',
            'mf'         => 'Multi-family',
            'condos'     => 'Condos / Townhomes',
            '55'         => '55+',
            'land'       => 'Land',
            'commercial' => 'Commercial',
            'all'        => 'All',
        ];

    }

    public function filter_price_label( $output, $value ) {

        if ( empty( $value ) ) {
            return $output;
        }

        $values = explode( ':', $value );

        $min = absint( $values[0] ?: 0 );
        if ( $min < 1000000 ) {
            $minl = round( $min / 1000, 0 ) . 'K';
        } else {
            $minl = round( $min / 1000000, 1 ) . 'MM';
        }

        $max = absint( $values[1] ?: 0 );
        if ( $max < 1000000 ) {
            $maxl = round( $max / 1000, 0 ) . 'K';
        } else {
            $maxl = round( $max / 1000000, 1 ) . 'MM';
        }

        if ( ! empty( $min ) && empty( $max ) ) {
            return '$' . $minl . '+';
        } elseif ( empty( $min ) && ! empty( $max ) ) {
            return '$' . $maxl . '-';
        } elseif ( ! empty( $min ) && ! empty( $max ) ) {
            return "\${$minl} - $maxl";
        } elseif ( empty( $min ) && empty( $max ) ) {
            return '';
        }

        return $output;

    }

    public function filter_location_label( $output, $value ) {

        if ( empty( $value ) ) {
            return $output;
        }

        if ( strpos( $value, '[' ) !== false ) {
            return 'Custom map';
        }

        if ( strpos( $value, ',' ) !== false ) {
            return 'Multiple locations';
        }

        $location = Location::get( $value );
        if ( ! empty( $location ) ) {
            return $location->value;
        }

        return $output;

    }

    public function filter_status_options( $options ) {

        foreach( $options as &$option ) {
            if ( $option === 'Sold' ) {
                $option = 'Sold All Time';
            }
        }

        $options[] = 'Sold Last 12 Months';

        return $options;

    }

    public static function get_query_fields() {

        return apply_filters(
            'vestorfilter_query_filters',
            [ 'price', 'bedrooms', 'bathrooms', 'dom', 'sqft', 'hoa', 'stories', 'garage_spaces', 'taxes', 'year_built', 'units', 'oh' ]
        );

    }

    public static function get_query_meta() {

        return [ 'open_house' ];

    }

    public static function get_allowed_filters() {

        $lot_options = self::sort_lot_options( Cache::get_index_values( 'lot-size' ) ?: [] );

        $status_options = Settings::get_filter_options( 'status' );
        $status_values  = [];
        foreach( $status_options as $status ) {
            if ( $status['value'] === 'sold' ) {
                $status_values[ 'sold_1yr' ] = 'Sold Last 12 Months';
                unset( $status_values['sold'] );
            } else {
                $status_values[ $status['value'] ] = $status['label'];
            }
        }

        $lot_options = Settings::get_filter_options( 'lot' );
        $lot_values  = [];
        foreach( $lot_options as $option ) {
            $lot_values[ $option['value'] ] = $option['label'];
        }

        $type_values = self::get_property_types();

        $filters = [
            'location' => [
                'label'   => __( 'Search', 'vestorfilter' ),
                'type'    => 'search',
                'display' => 'results',
                'classes' => [ 'no-label' ],
            ],
            'location_query' => [
                'label'   => __( 'Location Search', 'vestorfilter' ),
                'display' => false,
            ],
            'search' => [
                'label'   => __( 'Keyword Search', 'vestorfilter' ),
                'display' => false,
            ],
            'vf' => [
                'label'   => __( 'Vestor Filters', 'vestorfilter' ),
                'type'    => 'vestorfilters-panel',
                'options' => Filters::get_all(),
                'display' => false,
            ],
            'price'         => [
                'label'  => __( 'Price', 'vestorfilters' ),
                'type'   => 'min-max-options',
                'format' => 'price',
                'min-options' => [
                    '0'       => '$0',
                    '100000' => '$100K',
                    '200000' => '$200K',
                    '250000' => '$250K',
                    '300000' => '$300K',
                    '350000' => '$350K',
                    '400000' => '$400K',
                    '450000' => '$450K',
                    '500000' => '$500K',
                    '550000' => '$550K',
                    '600000' => '$600K',
                    '650000' => '$650K',
                    '700000' => '$700K',
                    '800000' => '$800K',
                    '900000' => '$900K',
                    '1000000' => '$1MM',
                    '1200000' => '$1.2MM',
                    '1500000' => '$1.5MM',
                ],
                'max-options' => [
                    '100000' => '$100K',
                    '200000' => '$200K',
                    '250000' => '$250K',
                    '300000' => '$300K',
                    '350000' => '$350K',
                    '400000' => '$400K',
                    '450000' => '$450K',
                    '500000' => '$500K',
                    '550000' => '$550K',
                    '600000' => '$600K',
                    '650000' => '$650K',
                    '700000' => '$700K',
                    '800000' => '$800K',
                    '900000' => '$900K',
                    '1000000' => '$1MM',
                    '1200000' => '$1.2MM',
                    '1500000' => '$1.5MM',
                ],
            ],
            'property-type' => [
                'label'   => __( 'Type', 'vestorfilter' ),
                'type'    => 'options',
                'classes' => [ 'no-label' ],
                'options' => $type_values,
                'default' => key( $type_values ),
            ],
            'bedrooms'      => [
                'label' => __( 'Beds', 'vestorfilters' ),
                'type'  => 'min-max-options',
                'format' => 'simple',
                'min-options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '6' => '6+',
                ],
                'max-options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                ],
            ],
            'bathrooms'     => [
                'label' => __( 'Baths', 'vestorfilters' ),
                'type'  => 'min-max-options',
                'format' => 'simple',
                'min-options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '9',
                    '9' => '9',
                    '10' => '10'
                ],
                'max-options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                ],
            ],
            'sqft'          => [
                'label'  => __( 'Sq. Ft', 'vestorfilters' ),
                'type'  => 'min-max-options',
                'format' => 'simple',
                'min-options' => [
                    '1' => '1 sqft',
                    '400' => '400 sqft',
                    '1000' => '1000 sqft',
                    '1500' => '1500 sqft',
                    '2000' => '2000 sqft',
                    '3000' => '3000 sqft',
                    '4000' => '4000 sqft',
                    '5000' => '5000 sqft',
                    '7000' => '7000+ sqft',
                ],
                'max-options' => [
                    '400' => '400 sqft',
                    '1000' => '1000 sqft',
                    '1500' => '1500 sqft',
                    '2000' => '2000 sqft',
                    '3000' => '3000 sqft',
                    '4000' => '4000 sqft',
                    '5000' => '5000 sqft',
                    '6000' => '6000 sqft'
                ],
            ],
            'lot-size'      => [
                'label'  => __( 'Lot', 'vestorfilters' ),
                'type'    => 'options',
                'options' => $lot_values,
                'index'   => true,
            ],

            'dom'           => [
                'label'   => __( 'Days', 'vestorfilters' ),
                'type'  => 'min-max-options',
                'format' => 'simple',
                'min-options' => [
                    '1' => '1 Day',
                    '8' => '1 Week',
                    '31' => '1 Month',
                    '180' => '6 Months',
                    '365' => '1+ Years',
                ],
                'max-options' => [
                    '8' => '1 Week',
                    '31' => '1 Month',
                    '181' => '6 Months',
                    '365' => '1 Years',
                ],
            ],
            'status'        => [
                'label'   => __( 'Status', 'vestorfilters' ),
                'type'    => 'options',
                'classes' => [ 'no-label' ],
                'options' => $status_values, //Cache::get_index_values( 'status', '`value` ASC' ),
                'default' => key( $status_values ),
                'index'   => true,
            ],

            'stories'       => [
                'label'   => __( 'Single Level', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    '1'  => 'Yes',
                    '2:' => 'No',
                ],
                'misc'    => true,
            ],
            'garage_spaces'  => [
                'label'   => __( 'Garage', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    '1:' => '1+ spaces',
                    '2:' => '2+',
                    '3:' => '3+',
                    '4:' => '4+',
                ],
                'misc'    => true,
            ],
            'hoa'           => [
                'label'   => __( 'HOA Monthly', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    '0:200' => '$200 -',
                    '0:300' => '$300 -',
                    '0:400' => '$400 -',
                    '0:500' => '$500 -',
                    '500:' => '$500 +',
                ],
                'misc'    => true,
            ],
            'taxes'           => [
                'label'   => __( 'Property Taxes', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    '0:2000' => '$2000 -',
                    '0:3000' => '$3000 -',
                    '0:4000' => '$4000 -',
                    '0:5000' => '$5000 -',
                    '5000:' => '$5000 +',
                ],
                'misc'    => true,
            ],
            'year_built'           => [
                'label'   => __( 'Year Built', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    '1920:' => '1920 +',
                    '1940:' => '1940 +',
                    '1960:' => '1960 +',
                    '1980:' => '1980 +',
                    '2000:' => '2000 +',
                    '2020:' => '2020 +',
                ],
                'misc'    => true,
            ],
            'units'           => [
                'label'   => __( 'Multifamily Unit Count', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    '2'   => '2',
                    '3'   => '3',
                    '4'   => '4',
                    '5:'  => '5+',
                    '10:' => '10+',
                ],
                'misc'    => true,
                'rules'   => [
                    'property-type' => [ 'mf' ]
                ],
            ],
            'oh'           => [
                'label'   => __( 'Open House', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    ':1'  => 'Next 24 Hrs',
                    ':8'  => 'Next 7 days',
                ],
                'misc'    => true,
                'rules'   => [
                    'property-type' => [ 'sf', 'condos', '55' ]
                ],
            ],
            'shortsale' => [
                'label'   => __( 'Short Sale', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    'no'  => 'No',
                    'yes'  => 'Yes',
                ],
                'misc'    => true,
            ],
            'auction' => [
                'label'   => __( 'Auction', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    'no'  => 'No',
                    'yes'  => 'Yes',
                ],
                'misc'    => true,
            ],
            'foreclosure' => [
                'label'   => __( 'Foreclosure', 'vestorfilters' ),
                'type'    => 'options',
                'options' => [
                    'no'  => 'No',
                    'yes'  => 'Yes',
                ],
                'misc'    => true,
            ],
            'agent' => [
                'label'   => __( 'Listing Agent', 'vestorfilter' ),
                'display' => false,
            ],
            'office' => [
                'label'   => __( 'Listing Office', 'vestorfilter' ),
                'display' => false,
            ],
            'search' => [
                'label' => 'Search Keywords',
                'display' => false,
            ],
            'favorites' => [
                'label' => 'User favorites',
                'display' => false,
            ],
        ];

        $filters = apply_filters( 'vestorfilters_data_filters', $filters );

        return $filters;

    }

    public static function make_rules_string( $rules ) {

        $rules_attrs = [];
        foreach ( $rules as $rule => $values ) {
            $rules_attrs[] = "$rule:" . implode( ',', $values );
        }

        return implode( ';', $rules_attrs );

    }

    public static function get_filter_value( $filter, $value, $key ) {

        $type = $filter['type'] ?? 'plain';

        if ( $type === 'range' || $type === 'min-max' ) {
            $output = str_replace( ':', ' - ', $value ?: '' );
        } elseif ( $type === 'options' ) {
            $values = explode( ',', $value );
            $output = [];
            foreach( $values as $option ) {
                $output[] = $filter['options'][ $option ] ?? str_replace( ':', ' - ', $option ?: '' );
            }
            $output = implode( ', ', $output );
        } else {
            $output = $value ?? '';
        }

        $output = apply_filters( 'vestortemplate_filter_value__' . $key, $output, $value ?? '' );

        if ( empty( $output ) && isset( $filter['label2'] ) ) {
            $output = $filter['label2'];
        }

        return $output;

    }

    public static function sort_lot_options( $options ) {

        usort( $options, function ( $a, $b ) {

            if ( strpos( $a->value, 'Acres' ) && strpos( $b->value, 'SqFt' ) ) {
                return 2;
            }
            if ( strpos( $b->value, 'Acres' ) && strpos( $a->value, 'SqFt' ) ) {
                return -2;
            }

            return strnatcmp( $a->value, $b->value );

        } );

        return $options;

    }

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Data', 'init' ) );

