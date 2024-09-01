<?php

namespace VestorFilter;

use VestorFilter\Util\Math;

if ( ! defined( 'ABSPATH' ) ) {
    die();
}

class Search {

    /**
     * A pointless bit of self-reference
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Store query
     *
     * @var array
     */
    private static $query = null;

    private static $filters = [],
        $error = null,
        $properties = null,
        $geo = null,
        $geo_expanded = null,
        $hash = null,
        $original_hash = null,
        $original_nonce = null,
        $vf = '',
        $vf_rules = null,
        $expires = null,
        $points_outside = null,
        $modified_after = null,
        $bounds = [],
        $center = null,
        $zoom = null,
        $custom_map,
        $forced = null,
        $forced_geo = null;

    private static $valid_search_page = null;
    private static $favorites_page = null;

    public function __construct() {}

    public static function init() {

        $self = apply_filters( 'vestorfilter_search_instance', new Search() );

        $self->install();

        do_action( 'vestorfilter_search_init', $self );


        return $self;

    }

    private function install() {

        if ( ! is_null( self::$instance ) ) {
            return self::$instance;
        }

        self::$instance = $this;

        add_action( 'rest_api_init', array( $this, 'init_rest' ) );
        add_action( 'wp', array( $this, 'test_is_search_valid' ) );
        add_action( 'wp', array( $this, 'setup_page_title' ), 99 );

        do_action( 'vestorfilter_search_installed', $this );

    }

    public static function instance() {
        return self::$instance;
    }

    public static function is_location_free_search_allowed() {

        return apply_filters( 'vestorfilter_allow_locationless_search', false );

    }

    public static function search_active()  {

        return ! is_null( self::$query );

    }

    public static function has_results() {

        return is_null( self::$query ) ? false : self::$query->total_results() > 0;

    }

    public static function total_results() {

        return count( self::$properties );

    }

    public static function current_page_number() {

        return absint( $_GET['pagenum'] ?? 1 ) ?: 1;

    }

    public static function number_of_results_pages( $per_page ) {

        return empty( self::$query ) ? 1 : ceil( self::$query->total_results() / $per_page );

    }

    public static function get_filter_value( $key ) {

        return self::$filters[$key] ?? null;

    }

    public static function map_hash() {

        return self::$hash ?? null;

    }

    public static function get_vf() {
        return self::$vf;
    }

    public static function get_geo() {
        $geo = self::$geo;
        if ( self::$forced_geo && self::$geo_expanded ) {
            return self::$geo_expanded;
        }
        return $geo;
    }

    public static function get_center() {
        return self::$center;
    }

    public static function get_zoom() {
        return self::$zoom;
    }

    public static function is_forced_geo() {
        return self::$forced_geo ?: false;
    }

    public static function test_is_search_valid() {

        $page = get_queried_object_id();

        if ( empty( $page ) ) {
            return;
        }
        $search_page = absint( Settings::get_page_template( 'search' ) );

        if ( $page !== $search_page ) {
            return;
        }

        $filters = Data::get_allowed_filters();
        foreach ( $_GET as $key => $var ) {
            if ( isset( $filters[$key] ) ) {
                self::$valid_search_page = true;
                return;
            }
        }

        if ( isset( $_GET['favorites'] ) ) {
            self::$favorites_page = true;
            self::$valid_search_page = true;
            return;
        }

        wp_safe_redirect( get_bloginfo( 'url' ) );
        exit;

    }

    public function setup_page_title() {

        if ( self::$valid_search_page !== true ) {
            return 'Search Results';
        }

        $location_ids = filter_input( INPUT_GET, 'location', FILTER_SANITIZE_STRING );
        if ( empty( $location_ids ) ) {
            $title = 'Search Results';
        }

        if ( self::$favorites_page ) {
            $title = 'My Favorite Properties';
        } else {
            $type = filter_input( INPUT_GET, 'property-type', FILTER_SANITIZE_STRING );
            $title = self::get_results_title_string( $location_ids, $type );
        }

        if ( ! empty( $title ) ) {
            add_filter( 'template__search_page_title', function () use ( $title ) {
                return $title;
            } );
            /*add_filter( 'vestorfilter_search_results_count', function () use ( $title ) {
                return '%s ' . $title;
            } );*/
            add_filter( 'pre_get_document_title', function ( $title_parts ) use ( $title ) {
                //$title_parts['title'] = $title;
                return $title . ' - Real Estate Smart Search';
            } );
            if ( strpos( $location_ids, '[' ) !== false ) {
                add_filter( 'body_class', function ( $classes ) {
                    $classes[] = 'custom-map-loaded';
                    return $classes;
                } );
            }
        }

        return $title;

    }

    public static function get_results_title_string( $location_ids, $type ) {

        $title = null;

        switch( $type ) {

            case 'condos':
                $type = 'Condos';
                break;

            case 'land':
                $type = 'Lots';
                break;

            case 'sf':
            case 'mf':
            case '55':
                $type = 'Homes';
                break;

            case 'all':
            case 'commercial':
            default:
                $type = 'Properties';
                break;

        }


        if ( empty( $location_ids ) ) {
            $title = " $type for Sale";
        } else if ( strpos( $location_ids, '[' ) !== false ) {
            $title = "Customized Map Search For $type";
        } else if ( strpos( $location_ids, ',' ) === false ) {

            $location = Location::get( $location_ids );
            if ( $location ) {
                $title = $location->value . " $type for Sale";
            }
        } else {
            $title = " $type for Sale in ";
            $ids = explode( ',', $location_ids );
            $count = 0;
            foreach( $ids as $location_id ) {
                $count++;
                $location = Location::get( $location_id );
                if ( $count > 1 && $count === count( $ids ) ) {
                    $title .= " and ";
                } elseif ( $count > 1 ) {
                    $title .= ", ";
                }
                if ( $location ) {
                    $title .= $location->value;
                }
            }
        }


        return $title;

    }

    public static function default_results_count() {

        if ( empty( self::$filters['location'] ) || empty( self::$filters['property-type'] ) ) {
            return '%s results found';
        }

        return '%s ' . self::get_results_title_string( self::$filters['location'], self::$filters['property-type'] );

    }

    public static function get_query_filters( $values, $context = 'internal' ) {

        $filters = Data::get_allowed_filters();

        if ( isset( $values['favorites'] ) ) {

            $user = $values['favorites'];
            if ( $user !== 'user' ) {
                $user = filter_var( $user, FILTER_SANITIZE_STRING );
                if ( $user ) {
                    $user = Favorites::find_user_from_slug( $user );
                }

            } elseif ( is_user_logged_in() ) {
                $user = get_current_user_id();
            }
            if ( empty( $user ) ) {
                wp_safe_redirect( '/' );
                exit;
            }


            $query_filters = [
                'user' => absint( $user ),
                'friend_favorites' => true,
            ];

        } elseif ( isset( $values['user'] ) ) {

            $query_filters = [ 'user' => absint( $values['user'] ) ];
            if ( isset( $values['friend_favorites'] ) ) {
                $query_filters['friend_favorites'] = $values['friend_favorites'];
            }

        } else {

            $values = apply_filters( 'vestorfilter_search_query_filter_values', $values, $context );

            if ( ! empty( $values['map_user'] ) ) {
                $user = absint( $values['map_user'] );
                if ( $user ) {
                    $values['location'] = "{$user}[" . $values['location'] . ']';
                }
            }

            $query_filters = [];
            foreach( $filters as $filter_key => $props ) {

                if ( ! isset( $values[ $filter_key ] ) ) {
                    continue;
                }
                $value = filter_var( $values[ $filter_key ], FILTER_SANITIZE_STRING );


                if ( $context === 'live' && $filter_key === 'vf' && ! Filters::is_filter_live( $value ) ) {
                    self::$vf = sanitize_title( $value );
                }

                if ( $filter_key === 'location' && strpos( $value, '[' ) !== false ) {

                    list( $user, $map_id ) = explode( '[', trim( $value, ']' ) ) + ['',''];
                    $map_id = sanitize_title( $map_id );
                    if ( ! empty( $user ) && ! empty( $map_id ) ) {
                        $custom_map = Location::get_custom_map( $user, $map_id );
                    }
                    if ( ! empty( $custom_map ) ) {
                        self::$custom_map = $custom_map;

                        $query_filters['location'] = $map_id;

                        $query_filters['geo'] = [
                            'min' => [ Location::float_to_geo( $custom_map['min'][0] ), Location::float_to_geo( $custom_map['min'][1] ) ],
                            'max' => [ Location::float_to_geo( $custom_map['max'][0] ), Location::float_to_geo( $custom_map['max'][1] ) ],
                        ];
                        $query_filters['map_user'] = $user;

                    } else {
                        return new \WP_Error( 'bad_request', 'Custom map requested does not exist.', [ 'status' => 404 ] );
                    }
                    continue;
                }


                if ( ! empty( $value ) && $value !== ':' ) {
                    $query_filters[ $filter_key ] = $value;
                }

            }
            if ( isset( $values['geo'] ) && empty( $custom_map ) ) {
                $coords = explode( ',', urldecode( $values['geo'] ) );
                if ( count( $coords ) !== 4 ) {
                    return new \WP_Error( 'bad_request', 'Invalid geo range specified.', [ 'status' => 403 ] );
                }
                $area = abs( (float) $coords[0] - (float) $coords[2] ) * abs( (float) $coords[1] - (float) $coords[3] );
                /*if ( $area > 0.35 ) {
                    return new \WP_Error( 'bad_request', 'Area specified is too large.', [ 'status' => 403 ] );
                }*/
                $min_lat = $coords[0] < $coords[2] ? $coords[0] : $coords[2];
                $max_lat = $coords[0] > $coords[2] ? $coords[0] : $coords[2];
                $min_lon = $coords[1] < $coords[3] ? $coords[1] : $coords[3];
                $max_lon = $coords[1] > $coords[3] ? $coords[1] : $coords[3];

                self::$geo_expanded = [
                    'min' => [ Location::float_to_geo( $min_lat ), Location::float_to_geo( $min_lon ) ],
                    'max' => [ Location::float_to_geo( $max_lat ), Location::float_to_geo( $max_lon ) ],
                ];

                $lat_width = abs( $max_lat - $min_lat ) / 2;
                $lon_width = abs( $max_lon - $min_lon ) / 2;

                $min_lat -= $lat_width;
                $max_lat += $lat_width;
                $min_lon -= $lon_width;
                $max_lon += $lon_width;

                unset( $query_filters['geo'] );
                ksort( $query_filters );

                self::$original_hash = self::get_hash( $query_filters );
                self::$original_nonce = wp_create_nonce( md5( serialize( $query_filters ) . self::$original_hash ) );

                $query_filters['geo'] = [
                    'min' => [ Location::float_to_geo( $min_lat ), Location::float_to_geo( $min_lon ) ],
                    'max' => [ Location::float_to_geo( $max_lat ), Location::float_to_geo( $max_lon ) ],
                ];

                if ( ! empty( $query_filters['location'] ) ) {
                    $new_locations = [];
                    foreach( explode( ',', $query_filters['location'] ) as $location_id ) {
                        $location = Location::get( $location_id );
                        if ( $location && in_array( $location->type, apply_filters( 'vestorfilter_geo_filter_allowed_types', [ 'school' ] ) ) ) {
                            $new_locations[] = $location_id;
                        }
                    }
                    if ( ! empty( $new_locations ) ) {
                        $query_filters['location'] = implode( ',', $new_locations );
                    } else {
                        unset( $query_filters['location'] );
                    }
                }
            } else if ( empty( $custom_map ) ) {
                self::$geo = false;

                $locations = ( $query_filters['location'] ?? null ) ? explode( ',', $query_filters['location'] ): [];

                if ( count( $locations ) === 1
                    && $locations === Settings::get( 'default_location_id' )
                    && Settings::get( 'default_lat' ) && Settings::get( 'default_lat' ) ) {
                    self::$center = [ Location::float_to_geo( Settings::get( 'default_lat' ) ), Location::float_to_geo( Settings::get( 'default_lon' ) ) ];
                    self::$zoom = Settings::get( 'default_zoom' ) ?: self::$zoom;
                    self::$forced_geo = true;
                } else if ( count( $locations ) === 1 && Settings::get( 'geocoding_api' ) && Settings::get( 'use_geocoding' ) && empty( self::$geo ) ) {
                    $geoset = Location::get_geocoded_coords( $locations[0] );
                    if ( $geoset ) {
                        self::$center = [ Location::float_to_geo( $geoset['center'][0] ), Location::float_to_geo( $geoset['center'][1] ) ];
                        self::$geo_expanded = [
                            'min' => [ Location::float_to_geo( $geoset['sw'][0] ), Location::float_to_geo( $geoset['sw'][1] ) ],
                            'max' => [ Location::float_to_geo( $geoset['ne'][0] ), Location::float_to_geo( $geoset['ne'][1] ) ]
                        ];
                        self::$geo = self::$geo_expanded;
                        //self::$zoom   = $geoset['zoom'];
                        self::$forced_geo = true;
                    }
                }
            }
            if ( ! empty( $custom_map ) ) {

                $nonce_filters = $query_filters;
                unset($nonce_filters['geo']);
                ksort($nonce_filters);

                self::$original_hash = self::get_hash( $nonce_filters );
                self::$original_nonce = wp_create_nonce( md5( serialize( $nonce_filters ) . self::$original_hash ) );

            }

        }

        return $query_filters;

    }

    public static function get_nonce() {
        return self::$original_nonce ?: '';
    }

    public static function do_search( $values = null, $context = 'internal', $default_zoom = null ) {

        if ( is_null( $values ) ) {
            $values = $_GET;
            $context = 'live';
        }

        if(isset($values['sqft']) and !empty($values['sqft'])) {
            list($min_sqft,$max_sqft) = explode(':',$values['sqft']);
            if(!$min_sqft) $min_sqft = 1;
            $values['sqft'] = "$min_sqft:$max_sqft";
        }

        if(isset($values['dom']) and !empty($values['dom'])) {
            list($min_days,$max_days) = explode(':',$values['dom']);
            if(!$min_days) $min_days = 1;
            if(!$max_days) $max_days = 36500;
            // reversing the values for days
            $values['dom'] = "$max_days:$min_days";
        }

        if(isset($values['bathrooms']) and !empty($values['bathrooms'])) {
            list($min,$max) = explode(':',$values['bathrooms']);
            if(!$min) $min = 1;
            $values['bathrooms'] = "$min:$max";
        }

        if(isset($values['price']) and !empty($values['price'])) {
            list($min_price,$max_price) = explode(':',$values['price']);
            if(!$min_price) $min_price = 1;
            if($min_price == 1500000 and !$max_price) $max_price = 100000000;
            $values['price'] = "$min_price:$max_price";
        }

        self::$geo = false;

        if ( isset( $values['location_query'] ) && empty( $values['location'] ) ) {
            $query = filter_var( $values['location_query'], FILTER_SANITIZE_STRING );
            $location = Location::find( $query, 'all' );
            if ( ! empty( $location ) ) {
                $values['location'] = $location[0]->ID;
            }
        }

        $query_filters = self::get_query_filters( $values, $context );
        if ( is_wp_error( $query_filters ) ) {
            self::$filters = null;
            self::$error = $query_filters->get_error_message();
            return;
        }

        if ( isset( $values['since'] ) ) {
            $since = absint( $values['since'] );
            if ( ! empty( $since ) ) {
                self::$modified_after = $since;
            }
            unset( $query_filters['since'] );
        }

        if ( isset( $query_filters['geo'] ) ) {
            self::$geo = $query_filters['geo'];
            unset( $query_filters['geo'] );
        }

        if ( isset( $values['hash'] ) ) {
            //$query_filters = [];
            $hash = sanitize_title( $values['hash'] );
        }

        self::$filters = $query_filters;
        ksort( self::$filters );

        $query = [];
        $query['cache'] = true;
        $query['geo'] = self::$geo;

        //var_export( $values );
        if ( ! empty( $values['user'] ) ) {
            self::$favorites_page = absint( $values['user'] );

            $hash = null;
            $query['show_hidden'] = false;
            $query['user'] = absint( self::$favorites_page );
            $query['friend_favorites'] = $query_filters['friend_favorites'] ?? false;


        } else if ( empty( $hash ) ) {
            $hash = self::get_hash( $query_filters );
        }

        $query['filters'] = $query_filters;

        if ( ! empty( self::$custom_map ) ) {
            $hash = null;
        }

        //echo $hash . ' ';

        // pull cache here
        if ( ! empty( $hash ) && apply_filters( 'vestorfilter_results_cache_allowed', true ) ) {
            self::$hash = $hash;
            if ( ! empty( self::$geo ) ) {
                $new_hash = $hash . 'vp';
            }

//            $cache_hit = Cache::get_results( $new_hash ?? $hash, $query, self::$geo_expanded ?? self::$geo, self::$geo );
//            $file = fopen('request.json', 'a');
//            fwrite($file, json_encode($cache_hit));
//            fclose($file);
            // BREAKPOINT: uncomment following line to disable cache
            //$cache_hit = null;
//            if ( $cache_hit ) {
//                $query_filters = [];
//                self::$properties = $cache_hit->results;
//                self::$filters = json_decode( $cache_hit->filters, true );
//                self::$hash    = $hash;
//                self::$expires = $cache_hit->expires;
//                self::$zoom    = $cache_hit->def_zoom;
//            }
        }


        if ( empty( $cache_hit )  ) {
            self::$properties = [];

            if ( empty( $query_filters ) && ! self::$favorites_page ) {
                throw new \Exception( 'Cannot search without filters.' );
            }

            try {

                $newquery = new Query( $query );
                self::$query = $newquery;

                if ( $newquery ) {
                    self::$properties = $newquery->get_all();

                    if ( ! self::$favorites_page ) {
                        $return_properties = self::get_filtered( self::$geo_expanded ?: $query['geo'] );
                        self::$expires = time() + 1800;
                        $args = [
                            'expires'    => time() + 1800,
                            'hash'       => $new_hash ?? self::get_hash( $query_filters ),
                            'results'    => self::$properties,
                            'filters'    => $query_filters,
                            'min_lat'    => $return_properties ? self::$geo['min'][0] : null,
                            'min_lon'    => $return_properties ? self::$geo['min'][1] : null,
                            'max_lat'    => $return_properties ? self::$geo['max'][0] : null,
                            'max_lon'    => $return_properties ? self::$geo['max'][1] : null,
                            'center_lat' => $return_properties ? absint( self::$center[0] ) : null,
                            'center_lon' => $return_properties ? absint( self::$center[1] ) : null,
                        ];
                        if ( $default_zoom ) {
                            $args['def_zoom'] = $default_zoom;
                        }

//                        if ( apply_filters( 'vestorfilter_results_cache_allowed', true ) ) {
//                            Cache::set_results( $args );
//                        }
                    } else {
                        $return_properties = self::$properties;
                    }
                }
            } catch( \Exception $e ) {
                self::$error = $e->getMessage();

                self::$properties = [];
            }
        }

        if ( empty( $return_properties ) ) {

            $return_properties = self::get_filtered( self::$geo_expanded ?: self::$geo );

        }
        return $return_properties;


    }

    public static function get_error() {

        return self::$error ?? false;

    }

    public static function get_hash( $filters = null ) {

        if ( is_null( $filters ) ) {
            if ( ! empty( self::$hash ) ) {
                return self::$hash;
            }
            if ( self::$favorites_page ) {
                return 'favorites';
            }
            $filters = self::$filters ?? null;
        }
        if ( empty( $filters ) ) {
            return null;
        }

        foreach( $filters as $key => $filter ) {
            if ( is_string( $filter ) ) {
                $filter = trim( $filter, ' :' );
                if ( empty( $filter ) ) {
                    unset( $filters[ $key ] );
                }
                $filters[$key] = $filter;
            }
            if ( $key === 'location' && $bracket = strpos( $filter, '[' ) ) {
                $filters[$key] = substr( $filter, $bracket + 1, -1 );

            }
        }
        ksort( $filters );

        return md5( json_encode( $filters ) . implode( '', VF_ALLOWED_FEEDS ) );

    }

    public static function convert_to_query_hash( $query ) {

        $query = Query::make_query( $query );

        return md5( serialize( $query ) . implode( '', VF_ALLOWED_FEEDS ) );

    }

    public function set_template( $classes ) {

        $classes[] = 'vestorfilter-results';

        return $classes;
    }

    public static function get_results_loop( $args ) {

        if ( ! empty( self::$properties ) ) {

            $args = wp_parse_args( $args, [
                'per_page'  => 60,
                'offset'    => 0,
                'page'      => 1,
            ] );

            $args['page'] -= 1;
            if ( $args['page'] < 0 ) {
                $args['page'] = 0;
            }

            if ( empty( $args['per_page'] ) ) {
                $args['per_page'] = 60;
            }

            if ( $args['page'] * $args['per_page'] + $args['offset'] > self::total_results() ) {
                return false;
            }

            $loop_data = array_slice( self::$properties, $args['page'] * $args['per_page'] + $args['offset'], $args['per_page'] );

            $properties = [];
            foreach ( $loop_data as $property ) {
                $new_property = new Property( $property );
                if ( self::$vf ) {
                    $new_property->load_vestorfilter( self::$vf );
                }
                $properties[] = $new_property;
            }
            return new Loop( $properties );

        } else {

            return is_null( self::$query )
                ? new Loop( [] )
                : self::$query->get_page_loop( $args );

        }



    }

    public static function get_map_data() {

        return self::$properties;

    }

    public static function sort_compare( $a, $b ) {


        if ( is_string( $a->data_cache ) ) {
            $a->data_cache = json_decode( $a->data_cache );
        }
        if ( is_string( $b->data_cache ) ) {
            $b->data_cache = json_decode( $b->data_cache );
        }

        foreach( self::$vf_rules['data'] as $rule ) {

            if ( $a->hidden && $b->hidden ) {
                return 0;
            }
            if ( $a->hidden ) {
                return 2;
            }
            if ( $b->hidden ) {
                return -2;
            }

            $key = $rule['key'];
            $a_data = $a->data_cache->$key ?? false;
            $b_data = $b->data_cache->$key ?? false;
            $a->hidden = false;
            $b->hidden = false;

            if ( $a_data === false ) {
                $a->hidden = true;
                return 2;
            }
            if ( $b_data === false ) {
                $b->hidden = true;
                return -2;
            }
            if ( $rule['comparison'] ?? null ) {
                if ( $rule['comparison'] === '>=' ) {
                    if ( $a_data < $rule['value'] ) {
                        $a->hidden = true;
                        return 2;
                    }
                    if ( $b_data < $rule['value'] ) {
                        $b->hidden = true;
                        return -2;
                    }
                }
            }
            if ( $a_data === $b_data ) {
                continue;
            }
            if ( $rule['order'] ?? null ) {
                if ( $rule['order'] === 'ASC' ) {
                    return $a_data < $b_data ? -1 : 1;
                }
                if ( $rule['order'] === 'DESC' ) {
                    return $a_data > $b_data ? -1 : 1;
                }
            }
        }
        return 0;

    }

    public static function sort_by_vf( $properties, $vf = null ) {

        return $properties;

        if ( empty( $vf ) ) {
            $vf = self::$vf;
        }

        if ( empty( $properties ) || empty( $vf ) ) {
            return;
        }

        $rules = Filters::get_filter_query( $vf );
        if ( empty( $rules ) ) {
            return;
        }

        self::$vf_rules = $rules;

        usort( $properties, [ self::class, 'sort_compare' ] );

        return $properties;

    }

    public static function get_filtered( $geo = false ) {

        $return = [];

        self::$points_outside = false;

        $i = 0;

        if ( is_string( $geo ) ) {
            $coords = explode( ',', urldecode( sanitize_text_field( $geo ) ) );
            if ( count( $coords ) !== 4 ) {
                return new \WP_Error( 'bad_request', 'Invalid geo range specified.', [ 'status' => 403 ] );
            }
            /*$area = abs( (float) $coords[0] - (float) $coords[2] ) * abs( (float) $coords[1] - (float) $coords[3] );
            if ( $area > 0.35 ) {
                return new \WP_Error( 'bad_request', 'Area specified is too large.', [ 'status' => 403 ] );
            }*/
            $min_lat = $coords[0] < $coords[2] ? $coords[0] : $coords[2];
            $max_lat = $coords[0] > $coords[2] ? $coords[0] : $coords[2];
            $min_lon = $coords[1] < $coords[3] ? $coords[1] : $coords[3];
            $max_lon = $coords[1] > $coords[3] ? $coords[1] : $coords[3];
            if ( absint( $min_lat ) < 100 ) {
                $min_lat = (int) Location::float_to_geo( $min_lat );
                $max_lat = (int) Location::float_to_geo( $max_lat );
                $min_lon = (int) Location::float_to_geo( $min_lon );
                $max_lon = (int) Location::float_to_geo( $max_lon );
            }
            $geo = [
                'min' => [ (int) $min_lat, (int) $min_lon ],
                'max' => [ (int) $max_lat, (int) $max_lon ],
            ];
            $sort_geo = true;
        }

        $sort_geo = false;
        $sort_mod = false;

        $world_bounds = [
            'ne' => Location::get_ne_bounds(),
            'sw' => Location::get_sw_bounds(),
        ];

        $world_bounds['min'][0] = (int) Location::float_to_geo( $world_bounds['sw'][0] );
        $world_bounds['min'][1] = (int) Location::float_to_geo( $world_bounds['sw'][1] );
        $world_bounds['max'][0] = (int) Location::float_to_geo( $world_bounds['ne'][0] );
        $world_bounds['max'][1] = (int) Location::float_to_geo( $world_bounds['ne'][1] );

        if ( is_array( $geo ) ) {
            if ( absint( $geo['min'][0] ) < 100 ) {
                $geo['min'][0] = (int) Location::float_to_geo( $geo['min'][0] );
                $geo['min'][1] = (int) Location::float_to_geo( $geo['min'][1] );
                $geo['max'][0] = (int) Location::float_to_geo( $geo['max'][0] );
                $geo['max'][1] = (int) Location::float_to_geo( $geo['max'][1] );
            }
            $sort_geo = true;
        }
        if ( self::$modified_after ) {
            $sort_mod = true;
        }

        if ( ! $sort_geo ) {
            $geo = [
                'min' => [ Location::float_to_geo( 90 ), Location::float_to_geo( 180 ) ],
                'max' => [ Location::float_to_geo(-90 ), Location::float_to_geo(-180 ) ],
            ];
        }

        $total_parsed = 0;
        $center = [ 0, 0 ];

        $forced = null;

        foreach( self::$properties as $property ) {
            $i++;

            if ( self::$forced === (int) $property->ID ) {
                $forced = $property;
            }

            if ( $sort_mod && $property->modified < self::$modified_after ) {
                continue;
            }

            if ( empty( $property->lat ) || empty( $property->lon ) ) {
                continue;
            }

            if (   $property->lat > $world_bounds['max'][0]
                || $property->lon > $world_bounds['max'][1]
                || $property->lat < $world_bounds['min'][0]
                || $property->lon < $world_bounds['min'][1]
            ) {
                continue;
            }

            if ( $sort_geo &&
                ( $property->lat > $geo['max'][0]
                    || $property->lon > $geo['max'][1]
                    || $property->lat < $geo['min'][0]
                    || $property->lon < $geo['min'][1] )
            ) {
                self::$points_outside = true;
                continue;
            }

            if ( $property->lat > $geo['max'][0] ) {
                $geo['max'][0] = $property->lat;
            }
            if ( $property->lon > $geo['max'][1] ) {
                $geo['max'][1] = $property->lon;
            }
            if ( $property->lat < $geo['min'][0] ) {
                $geo['min'][0] = $property->lat;
            }
            if ( $property->lon < $geo['min'][1] ) {
                $geo['min'][1] = $property->lon;
            }

            $center[0] += $property->lat;
            $center[1] += $property->lon;

            $property->zIndex = $i - 1;
            $return[] = $property;

            $total_parsed++;

            if ( $forced && $forced->ID === $property->ID && $total_parsed <= 600 ) {
                $forced = null;
            }

        }

        self::$forced = $forced;

        if ( empty( self::$geo ) && ! empty( $return ) ) {
            self::$geo = $geo;
        }

        if ( empty( self::$center ) && ! empty( $return ) ) {
            self::$center[0] = $center[0] / $total_parsed;
            self::$center[1] = $center[1] / $total_parsed;
        }



        return $return;

    }

    public function init_rest() {

        register_rest_route( 'vestorfilter/v1', '/search/template', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_template' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/count', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_count' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/location', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_locations' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/query', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'search_exact_properties' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/map-data', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_map' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/save-map', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'save_map' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/delete-map', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'delete_map' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/my-maps', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_current_user_maps' ),
            'permission_callback' => 'is_user_logged_in',
        ) );

        register_rest_route( 'vestorfilter/v1', '/search/get_shortlink_params', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_shortlink_params' ),
            'permission_callback' => '__return_true',
        ) );

    }

    public static function get_search_query_endpoint() {

        return get_rest_url( null, 'vestorfilter/v1/search/map-data' );

    }

    public static function get_exact_query_endpoint() {

        return get_rest_url( null, 'vestorfilter/v1/search/query' );

    }

    public static function get_user_maps_endpoint() {

        return get_rest_url( null, 'vestorfilter/v1/search/my-maps' );

    }

    public function get_count( $request ) {

        if ( empty( self::$properties ) ) {
            self::do_search( $request, 'live' );
        }

        return self::total_results();

    }

    public function get_locations( $request ) {

        if ( empty( $request['query'] ) ) {
            return [];
        }


        $query = filter_var( $request['query'], FILTER_SANITIZE_STRING );

        $results = Location::find( $query );

        return array_slice( $results, 0, 10 );

    }

    public function get_template( $request ) {

        self::do_search( $request );

        $property_loop = self::get_results_loop( [
            'per_page' => absint( $request['per_page'] ?? 18 ),
            'page'     => absint( $request['page'] ?? self::current_page_number() ),
        ] );

        $html = \VestorFilter\Blocks\Results::get_html( $property_loop, true );

        $url = self::get_url();

        return [
            'html'  => $html,
            'total' => self::total_results(),
            'url'   => $url,
        ];

    }

    public static function get_share_url() {

        global $vfdb;

        $find_hash = $vfdb->get_row( $vfdb->prepare(
            "SELECT * FROM {$vfdb->prefix}propertycache_links WHERE `hash` = %s",
            self::$original_hash ?: self::$hash
        ) );
        if ( $find_hash ) {
            $shortlink = $find_hash->shortlink;
        } else {
            $shortlink = base_convert( time() , 10, 36 ) . substr( self::$original_hash ?: self::$hash, 0, 2 );
            $vfdb->insert(
                $vfdb->prefix . 'propertycache_links',
                [
                    'filters' => json_encode( self::$filters ),
                    'hash' => self::$original_hash ?: self::$hash,
                    'shortlink' => $shortlink
                ]
            );
        }

        if ( self::$geo ) {
            $geo = Math::conv_base(
                self::$zoom . ',' .
                round( ( self::$geo['max'][0] + self::$geo['min'][0] ) / 20 ) . ',' .
                round( ( self::$geo['max'][1] + self::$geo['min'][1] ) / 20 ),
                '0123456789,-'
            );
            $shortlink .= '&g=' . $geo;
        }

        return trailingslashit( get_bloginfo( 'url' ) ) . '?l=' . $shortlink;

    }

    public function get_map( $request ) {

        //$request['map'] = true;

        $location  = $request['location'] ?? null;
        $geo       = $request['geo'] ?? null;
        $favorites = $request['favorites'] ?? null;
        $hash      = $request['hash'] ?? null;
        $zoom      = $request['zoom'] ?? null;
        $limit     = absint( $request['limit'] ?? 300 ) ?: 300;
        if ( $limit > 300 ) {
            $limit = 300;
        }

        $forced    = absint( $request['forced'] ?? 0 );

        if ( $zoom && $zoom < 8 ) {
            $zoom = 8;
        }
        if ( $zoom && $zoom > 20 ) {
            $zoom = 20;
        }
        if ( empty( $filtered_properties ) ) {

            try {
                self::$forced = $forced;
                $filtered_properties = self::do_search( $request, 'live', $zoom );

            } catch( \Exception $e ) {
                return new \WP_Error( 'bad_request', $e->getMessage(), [ 'status' => 403 ] );
            }
            if ( ! empty( self::$error ) ) {
                return new \WP_Error( 'bad_request', self::$error, [ 'status' => 403 ] );
            }

        }

        if ( $zoom && empty( self::$zoom ) ) {
            self::$zoom = $zoom;
        }

        $url = self::get_url();
        if ( ! self::$favorites_page && ! empty( $location ) ) {
            $maps = [];
            if ( ! empty( self::$custom_map ) ) {
                $maps[] = self::$custom_map;
                $url = add_query_arg( 'location', $location, $url );

            } else if ( ! empty( self::$filters['location'] ) && is_string( self::$filters['location'] ) ) {
                foreach( explode( ',', self::$filters['location'] ) as $location_id ) {
                    $map = Location::get_location_map( $location_id );
                    if ( $map ) {
                        $maps[] = $map->ID;
                    }
                }
            }
        }

        $returned = array_slice( $filtered_properties, 0, $limit );




        if ( is_object( self::$forced ) ) {
            $returned[599] = self::$forced;
        }

        if ( empty( self::$original_nonce ) ) {

            $nonce = wp_create_nonce( md5( serialize( self::$filters ) . $hash ) );
        }

        $bounds = self::$geo_expanded ?: ( self::$geo ?? null );
//        echo '<pre>';
//        print_r($bounds);
//        echo '</pre>';
        if ( $bounds ) {
            $bounds['min'][0] = (int) Location::float_to_geo( $bounds['min'][0] );
            $bounds['min'][1] = (int) Location::float_to_geo( $bounds['min'][1] );
            $bounds['max'][0] = (int) Location::float_to_geo( $bounds['max'][0] );
            $bounds['max'][1] = (int) Location::float_to_geo( $bounds['max'][1] );
        }

        $center = self::$center ?? null;
        if ( $center ) {
            $center[0] = (int) Location::float_to_geo( $center[0] );
            $center[1] = (int) Location::float_to_geo( $center[1] );
        }

        if( empty ($bounds) && !empty($returned) ) {
            $possibleCoordinates = [
                'min' => [
                    $returned[0]->lat,
                    $returned[0]->lon,
                ],
                'max' => [
                    $returned[0]->lat,
                    $returned[0]->lon
                ]
            ];

            foreach ($returned as $key => $value) {
                if ($value->lat < $possibleCoordinates['min'][0]) {
                    $possibleCoordinates['min'][0] = $value->lat;
                }

                if ($value->lon < $possibleCoordinates['min'][1]) {
                    $possibleCoordinates['min'][1] = $value->lon;
                }

                if ($value->lat > $possibleCoordinates['max'][0]) {
                    $possibleCoordinates['max'][0] = $value->lat;
                }

                if ($value->lon > $possibleCoordinates['max'][1]) {
                    $possibleCoordinates['max'][1] = $value->lon;
                }
            }
        } else {
            $possibleCoordinates = $bounds;
        }

        $return = [
            'properties'  => $returned,
            'total'       => self::total_results(),
            'map'         => true,
            'url'         => $url,
            'search_maps' => $maps ?? [],
            'title'       => self::setup_page_title(),
            'hash'        => self::$hash ?: null,
            'search_hash' => self::$original_hash ?? self::$hash,
            'search_nonce'=> self::$original_nonce ?? $nonce ?? null,
            'base_url'    => Property::base_url(),
            'filters'     => self::$filters,
            'initial'     => empty( $hash ),
            'expires'     => self::$expires,
            'subset'      => self::$points_outside ? 'yes' : 'no',
            'vf'          => self::$vf,
            'bounds'      => (!empty($bounds) ? $bounds : $possibleCoordinates),
            'center'      => $center,
            'zoom'        => self::$zoom,
            'forced'      => self::$forced_geo,
        ];
        if ( current_user_can( 'manage_vf_options' ) ) {
            $return['share'] = self::get_share_url();
        }
        /*if ( ! empty( $request['vf'] ) ) {

            $return['vf']      = $request['vf'];
            $return['vfLabel'] = Filters::get_formatted_value( $request['vf'], 100 );
            $return['vfLabel'] = str_replace( [ '1.000', '1.00', '1.0', '1', '0K' ], '{{value}}', $return['vfLabel'] );
            $return['vfRange'] = Filters::get_filter_data_range( $request['vf'] ) ?: null;
            $return['vfScale'] = Filters::get_filter_data_scale( $request['vf'] ) ?: null;
        }*/

        //print_r($return);exit;
        return $return;




    }

    public static function get_url() {

        $url = trailingslashit( Settings::get_page_url( 'search' ) );

        if ( self::$favorites_page ) {
            $url = add_query_arg( 'favorites', self::$favorites_page, $url );
        } else {
            if ( ! empty( self::$filters['location'] ) && ! is_array( self::$filters['location'] ) && strpos( self::$filters['location'], ',' ) === false ) {
                $slug = Location::get_slug( self::$filters['location'] );

                $url .= $slug . '/';
            }
            //print_r(self::$filters);
            if ( self::$filters ) {
                foreach( self::$filters as $key => $value ) {
                    if ( is_string( $value ) ) {
                        $url = add_query_arg( $key, $value, $url );
                    }
                }
            }
        }

        return $url;

    }

    public function search_exact_properties( $request ) {

        $for   = $request->get_param( 'for' );
        $query = $request->get_param( 'query' );

        if ( empty( $for ) || empty( $query ) ) {
            return new WP_Error( 'incomplete', 'Incomplete request sent', [ 'code' => 400 ] );
        }

        switch( $for ) {
            case 'mlsid':
                $mlsid = absint( $query );
                if ( ! $mlsid || strlen( $mlsid ) <= 5 ) {
                    return []; //new \WP_Error( 'invalid', 'Invalid request sent', [ 'code' => 400 ] );
                }
                $results = Cache::find_property_mlsid( $mlsid, VF_ALLOWED_FEEDS ?? '' );

                $properties = [];
                foreach( $results as $key => $property ) {

                    $obj = new Property( $property, false );
                    $sold = $obj->get_data('sold') / 100;
                    if(!empty($sold) && $sold < strtotime('now - 1 year')) {
                        continue;
                    }
                    if ( $obj->get_index( 'hidden' ) ) {
                        continue;
                    }

                    $result = [
                        'label'    => $property->MLSID,
                        'id'       => $obj->ID(),
                        'sublabel' => $obj->get_address_string(),
                        'url'      => $obj->get_page_url(),
                    ];

                    if ( strpos( $property->MLSID, $mlsid . '' ) === 0 ) {
                        array_unshift( $properties, $result );
                    } else {
                        array_push( $properties, $result );
                    }
                }

                break;

            case 'address':

                if ( ! defined( 'VF_ALLOWED_FEEDS' ) ) {
                    return new WP_Error( 'incomplete', 'Address search is unavailable for this site', [ 'code' => 400 ] );
                }

                $address = filter_var( $query, FILTER_SANITIZE_STRING );

                $results = Cache::search_property_address( $address, VF_ALLOWED_FEEDS );

                $properties = [];
                foreach( $results as $property ) {

                    $obj = new Property( $property, false );
                    $sold = $obj->get_data('sold') / 100;
                    if(!empty($sold) && $sold < strtotime('now - 1 year')) {
                        continue;
                    }
                    /*if ( $obj->get_index( 'hidden' ) ) {
                        continue;
                    }*/

                    $fulladdress = $property->full_address;

                    $result = [
                        'label'    => $property->MLSID,
                        'id'       => $obj->ID(),
                        'sublabel' => $obj->get_address_string(),
                        'url'      => $obj->get_page_url(),
                    ];

                    // exact matches first
                    if ( strpos( $fulladdress, $address ) !== false ) {
                        array_unshift( $properties, $result );
                    } else {
                        array_push( $properties, $result );
                    }
                }

                break;
        }

        if ( empty( $properties ) ) {
            return [];
        }

        return $properties;

    }

    public static function parse_url_to_filters( $url ) {

        $query = parse_url( urldecode( $url ), PHP_URL_QUERY );

        if ( empty( $query ) ) {
            return false;
        }

        parse_str( $query, $params );

        $filters = self::get_query_filters( $params );
        if ( empty( $filters ) ) {
            return false;
        }

        foreach( $filters as $index => $filter ) {
            if ( empty( trim( $filter, ' :' ) ) ) {
                unset( $filters[$index] );
            }
        }
        if ( empty( $filters ) ) {
            return false;
        }

        ksort( $filters );

        return $filters;

    }

    public function delete_map( $request ) {

        $map_id  = sanitize_title( $request['map_id'] ?? '' );

        if ( $map_id ) {
            // delete plz
            $user_id = get_current_user_id();

            $maps = get_user_meta( $user_id, 'custom_map' );
            foreach( $maps as $id => $map ) {
                if ( $map_id === $map['id'] ) {
                    unset( $maps[$id] );
                    $found = true;
                }
            }
            if ( empty( $found ) ) {
                return 'not found';
            }



            delete_user_meta( $user_id, 'custom_map' );
            foreach( $maps as $map ) {
                add_user_meta( $user_id, 'custom_map', $map );
            }

            return 'deleted ' . $map_id;

        }

        return 'no map id';

    }

    public function save_map( $request ) {

        $user_id  = $request['user_id'] ?? 'user';
        $coords   = $request['coords'] ?? null;
        $map_name = $request['map_name'] ?? '';
        $filters  = $request['filters'] ?? [];
        $map_id   = sanitize_title( $request['map_id'] ?? '' );

        if ( empty( $map_id ) && ( empty( $coords ) || empty( $filters ) ) ) {
            return new \WP_Error( 'bad_params', 'Bad parameters passed to map save request.', [ 'status' => '403' ] );
        }

        if ( $user_id !== 'user' && ! current_user_can( 'see_leads' ) ) {
            return new \WP_Error( 'bad_auth', 'Unauthorized.', [ 'status' => '401' ] );
        } elseif ( $user_id !== 'user' && ! is_numeric( $user_id ) ) {
            return new \WP_Error( 'bad_auth', 'Unauthorized.', [ 'status' => '401' ] );
        }

        if ( is_string( $coords ) ) {
            $coords = json_decode( $coords );
        }

        if ( is_string( $filters ) ) {
            $filters = json_decode( $filters );
        }

        if ( isset( $filters->location ) ) {
            unset( $filters->location );
        }


        if ( empty( $map_name ) ) {
            $name = \VestorFilter\Blocks\SavedSearches::get_search_label_html( $filters );
            $map_name = $name['plain'];
            $no_name = true;
        }

        $min = [ 90, 180 ];
        $max = [ -90, -180 ];
        foreach( $coords as $id => $coord ) {
            if ( ! is_numeric( $coord[0] ) || ! is_numeric( $coord[1] ) || $coord[0] < -90 || $coord[0] > 90 || $coord[1] < -180 || $coord[1] > 180 ) {
                return new \WP_Error( 'bad_params', 'Bad coordinates passed to map save request.', [ 'status' => '403' ] );
            }

            if ( $coord[0] > $max[0] ) {
                $max[0] = $coord[0];
            }
            if ( $coord[1] > $max[1] ) {
                $max[1] = $coord[1];
            }
            if ( $coord[0] < $min[0] ) {
                $min[0] = $coord[0];
            }
            if ( $coord[1] < $min[1] ) {
                $min[1] = $coord[1];
            }
        }

        if ( $user_id === 'user' ) {
            $user_id = get_current_user_id();
        }
        $id = base_convert( $user_id, 10, 36 ) . base_convert( time(), 10, 36 );
        $new_map = [
            'id'  => $id,
            'min' => $min,
            'max' => $max,
            'coords' => $coords,
            'filters' => $filters,
            'name' => $map_name,
            'search_param' => "{$user_id}[$id]",
            'user' => $user_id,
        ];

        if ( $map_id ) {
            $maps = get_user_meta( $user_id, 'custom_map' );
            delete_user_meta( $user_id, 'custom_map' );
            foreach( $maps as $map ) {
                if ( $map['id'] === $map_id ) {
                    $new_map['id'] = $map_id;
                    $new_map['search_param'] = "{$user_id}[$map_id]";
                    if ( isset( $no_name ) ) {
                        $new_map['name'] = $map['name'];
                    }

                    $map = $new_map;
                }
                add_user_meta( $user_id, 'custom_map', $new_map );
            }
            $new_map['found'] = true;
            $new_map['mine'] = true;

            return $new_map;

        } else {
            add_user_meta( $user_id, 'custom_map', $new_map );
        }

        $new_map['new'] = true;
        if ( $user_id === get_current_user_id() || $user_id === 'user' ) {
            $new_map['mine'] = true;
        }
        return $new_map;

    }

    public static function get_user_map( $map_id, $user_id = null ) {

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return null;
        }
        $map_id = absint( $map_id );

        $maps = get_user_meta( $user_id, 'custom_map' );
        foreach( $maps as $map ) {
            if ( $map_id === $map['id'] ) {
                return $map;
            }
        }

        return false;

    }

    public static function get_current_user_maps() {

        $user_id = get_current_user_id();

        $maps = get_user_meta( $user_id, 'custom_map' );

        foreach( $maps as $i => $map ) {
            unset( $map[$i]['coords'] );
        }

        return [ 'maps' => $maps ];

    }

    public static function get_shortlink_params( $request ) {

        global $vfdb;

        $l = $request->get_param( 'l' );
        if ( empty( $l ) ) {
            return new WP_Error( 'bad_request', 'No shortlink code provided', [ 'status' => '403' ] );
        }
        $g = $request->get_param( 'g' );

        $found = $vfdb->get_row( $vfdb->prepare(
            "SELECT * FROM {$vfdb->prefix}propertycache_links WHERE `shortlink` = %s",
            sanitize_title( $l )
        ) );
        if ( empty( $found ) ) {
            return new WP_Error( 'bad_request', 'Could not find shortlink requested', [ 'status' => '404' ] );
        }

        $return = [
            'filters' => json_decode( $found->filters ),
        ];
        if ( ! empty( $g ) ) {
            $center = Math::conv_base(
                $g,
                '0123456789,-',
                false
            );
            if ( ! empty( $center ) ) {
                $coords = explode( ',', $center );
                if ( count( $coords ) === 3 ) {
                    $return['zoom'] = $coords[0];
                    $return['lat'] = Location::geo_to_float( $coords[1] * 10 );
                    $return['lon'] = Location::geo_to_float( $coords[2] * 10 );
                }
            }
        }
        return $return;

    }

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Search', 'init' ) );

