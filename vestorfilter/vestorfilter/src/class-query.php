<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Query {

	private $args, $queried;

	private $results = [];

	private $page_num = 1;

	private $current_vf = false;

	public $hash;

	public function __construct( $query = [], $updated_since = null, $filter_map = null, $sample = false ) {

		$this->args[ 'filters' ]  = $query['filters'] ?? [];
		$this->args[ 'per_page' ] = get_option( 'posts_per_page' ) ?: 20;

		if ( isset( $query['page'] ) ) {
			$this->page_num = $query['page'];
		}

		$this->results = array();

		if ( ! empty( $query['user'] ) ) {
			$favorites = true;
		} else {
			$query = self::make_query( $query, $this->current_vf, $this->page_num, $this->args['per_page'], $sample, $filter_map );
            if($_GET['customer']) {
                echo '<pre>';
                print_r($query);
                echo '</pre>';
            }
			if ( is_wp_error( $query ) ) {
				throw new \Exception( $query->get_error_message() );
			}
			if ( ! is_null( $updated_since   ) ) {
				$query['modified_after'] = $updated_since;
			}
		}

		$query = apply_filters( 'vestorfilter_query_prebuild', $query );
		if ( ! empty( $query ) ) {
			
			if ( ! empty( $favorites ) ) {
				
				$found = Cache::get_favorite_properties( 
					$query['user'], 
					$query['friend_favorites'] ?? false, 
					$query['show_hidden'] ?? false, 
				);
			} else {
				//var_dump( $query );
                if(in_array($query['data'][1]['key'], ['bpd', 'fixer', 'elc']) && is_front_page()) {
                    $key = $query['data'][1]['key'];
                    if(isset($_GET['update']) && $_GET['update'] == '1' && is_user_logged_in()) {
                        $file = fopen('portland-' . $key . '.json', 'w');
                        $found = Cache::get_properties($query);
                        fwrite($file, json_encode($found));
                        fclose($file);
                    } else {
                        $file = fopen('portland-' . $key . '.json', 'r');
                        $found = fread($file, filesize('portland-' . $key . '.json'));
                        fclose($file);
                        $found = json_decode($found);
                    }
                } else {
//                    $found = Cache::get_properties($query);
                    $found = Cache::get_properties_new($query);

                    foreach ($found as $key => $property) {
//                        if(!in_array($property->property_id, $withFixerInDesc) && !empty($withFixerInDesc)) {
//                            unset($found[$key]);
//                        }
                        if($property->block_cache == '') {
                            $property->block_cache = '{"photo":"https://vestorfilter-photos.s3.us-west-2.amazonaws.com/rmlsreso/21696/21696615/original-21696615-1__thumbnail.jpg","bedrooms":"300","lot":"4.12","zoning":"IP","comp":"Listing Provided By eXp Realty, LLC"}';
                        }
                        if($property->data_cache == '') {
                            $property->data_cache = '{"lot":"39","price":"9995000","status":"4145","onmarket":"116683200000","lot_est":"54304","ppls":"25628205","modified":"166197949900","last_updated":"166197949900","photos":"7","agent":"5050","office":"5051"}';
                        }
                        if(empty($property->location)) {
                            unset($found[$key]);
                        } else {
                            if(isset($query['location']) && empty($query['geo'])) {
                                $hide = true;
                                $explode = explode(',', $property->location);
                                foreach ($query['location'] as $loc ) {
                                    if(in_array($loc, $explode)) {
                                        $hide = false;
                                    }
                                }
                                if($hide) {
                                    unset($found[$key]);
                                }
                            }
                            if(isset($query['school'])) {
                                $hide = true;
                                $explode = explode(',', $property->location);
                                foreach ($query['school'] as $loc ) {
                                    if(in_array($loc, $explode)) {
                                        $hide = false;
                                    }
                                }
                                if($hide) {
                                    unset($found[$key]);
                                }
                            }
                        }
                        $property->ID = $property->property_id;
                    }

                    $found = array_slice($found, 0, 300);
                    if($_GET['frequency']) {
                        echo '<pre>';
                        print_r($found);
                        echo '</pre>';
                    }
                    $IDs = [];
                    foreach ($found as $key => $property) {
                        $IDs[] = $property->property_id;
                    }
                    $IDs = implode(',', $IDs);
                    global $vfdb;
                    $cacheQuery = $vfdb->get_results("SELECT `property_id`, `data_cache`, `block_cache`, `address` from wp_propertycache_cache where `property_id` IN ($IDs)");
                    foreach ($found as $ks => $property) {
                        foreach ($cacheQuery as $key => $cache) {
                            if($property->property_id == $cache->property_id) {
                                $found[$ks]->data_cache = $cache->data_cache;
                                $found[$ks]->address = $cache->address;
                                $found[$ks]->block_cache = $cache->block_cache;
                            }
                        }
                    }

                }
				$this->results = $found;
				if ( $filter_map && ! empty( $query['custom'] ) ) {
					$this->filter_by_custom_map( $query['custom']['coords'] );
					
				}
				return;

			}

		} else {
			$found = [];
		}


        $this->results = $found;

	}

	public static function make_query( $query = [], &$vf = null, $page_num = 1, $per_page = 20, $sample = false, $user = null ) {

	    $filters = $query['filters'] ?? [];
		$show_hidden = ! empty( $query['show_hidden' ] );

		//var_export( $query );
		$query = [
			'order' => $query['order'] ?? [ 'modified' => 'DESC' ],
			'limit' => $query['limit'] ?? null,
			'text'  => [],
			'index' => [],
			'data'  => [
				[ 'key' => 'photos', 'value' => 0, 'comparison' => '>' ],
			],
			'geo'   => $query['geo'] ?? false,
			'cache' => true,
			'user'  => $query['user'] ?? null,
			'friend_favorites'  => $query['friend_favorites'] ?? null,
			//'map_user' => $query['map_user'] ?? null,
		];

		if ( ! empty( $filters[ 'status' ] ) ) {

			$value = apply_filters( "vestorfilter_get_query_index__status", $filters[ 'status' ] );
			if ( is_string( $value ) ) {
				$value = [ $value ];
			}

			$options = Settings::get_filter_options( 'status' );
			$values = [];
			foreach ( $options as $option ) {
				if ( in_array( $option['value'], $value ) ) {
					foreach ( $option['terms'] as $term_id ) {
						$values[] = $term_id;
					}
				}
			}

			$query['index'][] = $values;

		}

		if ( ! empty( $filters['search'] ) ) {

			$values = explode( ' ', $filters['search'] );
			foreach( $values as $value ) {
				$value = filter_var( $value, FILTER_SANITIZE_STRING );
				$query['text'][] = [
					'key'        => 'description',
					'comparison' => 'LIKE',
					'value'     => "%$value%",
				];
			}

		}

		if ( ! empty( $filters['lot-size'] ) ) {

			$lot_options = get_option('my_idx_options_filters')['available_lot_sizes'];;
			foreach( $lot_options as $option ) {
				if ( $option['value'] === $filters['lot-size'] ) {
					$terms = $option['terms'];
					$query['index'][] = $terms;

					if ( ! empty( $option['range'] ) ) {
						add_filter(
							'vestorfilter_sql_after_join',
							[ self::class, 'join_lotsize' ]
						);
						$range = explode( '-', $option['range'] );
						$range[0] = absint( $range[0]*100 );
						if ( ! empty( $range[1] ) ) {
							$range[1] = absint( $range[1]*100 );
						}
						add_filter(
							'vestorfilter_sql_index_where',
							function ( $query, $what ) use ( $terms, $range ) {
								if ( $terms === $what ) {
									$query = sprintf( "( %s OR ( %s ) )",
										$query,
										empty( $range[1] )
											? sprintf( 'lotsize.value >= %d', $range[0] )
											: sprintf( 'lotsize.value >= %d AND lotsize.value <= %d', $range[0], $range[1] )
									);
								}
								return $query;
							},
							10, 2
						);
					}
				}
			}

		}

		foreach( [ 'agent' => 'listagentid', 'office' => 'listofficeid' ] as $key => $slug ) {

			if ( empty( $filters[ $key ] ) ) {
				continue;
			}

			$requested = apply_filters( "vestorfilter_get_query_index__$slug", $filters[ $key ] );
			if ( empty( $requested ) ) {
				continue;
			}
			if ( is_string( $requested ) ) {
				$requested = [ $requested ];
			}

			$taxonomy = Cache::find_taxonomy_by( 'slug', $slug );
			if ( ! $taxonomy ) {
				continue;
			}

			$values = [];
			foreach ( $requested as $value ) {
				$index_id = Cache::find_value( $taxonomy->ID, $value );
				if ( $index_id ) {
					$values[] = $index_id;
				}
			}


			$query['index'][] = $values;

		}

		foreach( [ 'property-type' ] as $tax ) {

			if ( empty( $filters[ $tax ] ) ) {
				continue;
			}
			$values = $filters[ $tax ];
			if ( is_string( $values ) ) {
				$values = explode( ',', $values );
			}
			$values = apply_filters( "vestorfilter_get_query_tax__$tax", $values );
			if ( is_string( $values ) ) {
				$values = [ $values ];
			}

			if ( in_array( 'all', $values ) ) {
				continue;
			}

			$taxonomy = Cache::find_taxonomy_by( 'slug', $tax );
			$value_ids = [];
			foreach( $values as $value ) {
				$value_ids[] = Cache::find_value( $taxonomy->ID, filter_var( $value, FILTER_SANITIZE_STRING ) );
			}

			if ( empty( $value_ids ) ) {
				continue;
			}

			$query['index'][] = $value_ids;

		}

		$fields = Data::get_query_fields();
		foreach( $fields as $field ) {
			$multiplier = in_array( $field, Property::search_columns() ) ? 1 : 100;
            if($_GET['frequency']) {
                if($field == 'dom') {
                    echo '<pre>';
                    print_r($field . ' ' . $multiplier);
                    echo '</pre>';
                }
            }
			if ( empty( $filters[$field] ) ) {
				continue;
			}
			if ( strpos( $filters[ $field ], ':' ) === false ) {

				if ( $filters[ $field ] === 'yes' || $filters[ $field ] === 'no' ) {
					$field_query = [
						'key'        => $field,
						'comparison' => $filters[ $field ] === 'yes' ? 'EXISTS' : 'NOT EXISTS',
					];
					$query['data'][] = $field_query;
					continue;
				}

				if ( ! is_numeric( $filters[ $field ] ) ) {
					continue;
				}

				$field_query = [
					'key'        => $field,
					'value'      => absint( $filters[ $field ] * $multiplier ),
				];
				$field_query = apply_filters( 'vestorfilter_setup_query_field__' . $field, $field_query );
				$query['data'][] = $field_query;
				continue;
			}
			$values = explode( ':', $filters[ $field ] );
			$field_query = null;

			$low = absint( $values[0] );
			$high = absint( $values[1] );

			if ( ! empty( $low ) && ! empty( $high ) ) {
				$field_query = [
					'key'        => $field,
					'comparison' => 'BETWEEN',
					'value'      => [ absint( $low * $multiplier ), absint( $high * $multiplier ) ],
				];
			} elseif ( empty( $low ) && ! empty( $high ) ) {
				$field_query = [
					'key'        => $field,
					'comparison' => '<=',
					'value'      => absint( $high * $multiplier ),
				];
			} elseif ( empty( $high ) && ! empty( $low ) ) {
				$field_query = [
					'key'        => $field,
					'comparison' => '>=',
					'value'      => absint( $low * $multiplier ),
				];
			}
            // BREAKPOINT
            //print_r($field_query);
			$field_query = apply_filters( 'vestorfilter_setup_query_field__' . $field, $field_query );


			if ( ! empty( $field_query ) ) {
				$query['data'][] = $field_query;
			}
		}



		if ( ! empty( $filters['vf'] ) && in_array( $filters['vf'], Filters::get_all() ) ) {

			$query_args = Filters::get_query( $filters['vf'], $filters[ $filters['vf'] ] ?? null );
			if ( ! empty( $query_args ) ) {

				foreach ( $query_args as $column => $sets ) {

					foreach ( $sets as $args ) {
						$query[ $column ][] = $args;
					}

				}

				$vf = $filters['vf'];

			}

		}

		foreach( [ 'shortsale' => 'ss', 'auction' => 'auc', 'foreclosure' => '3p' ] as $filter => $key ) {

			if ( ! empty( $filters[$filter] ) ) {

				$query['data'][] = [
					'key'        => $key,
					'comparison' => $filters[$filter] === 'yes' ? 'EXISTS' : 'NOT EXISTS',
					'null_last'  => false,
					'show_empty' => false,
				];

			}

		}

		if ( ! empty( $filters['location'] ) ) {

			$query['location'] = [];
			$map_user = strpos( $filters['location'], '[' );

			if ( $map_user !== false || ( ! is_numeric( $filters['location'] ) && strpos( $filters['location'], ',' ) === false ) ) {

				if ( $map_user ) {
					$map_user = substr( $map_user, 0, $map_user );
				}
				if ( $filters['map_user'] ?? false ) {
					$map_user = $filters['map_user'];
				}

				if ( ! empty( $user ) || ! empty( $map_user ) ) {
					$custom_map = Location::get_custom_map( $map_user ?: $user, $filters['location'] );
					if ( ! empty( $custom_map ) ) {
						$query['geo'] = [
							'min' => [ Location::float_to_geo( $custom_map['min'][0] ), Location::float_to_geo( $custom_map['min'][1] ) ],
							'max' => [ Location::float_to_geo( $custom_map['max'][0] ), Location::float_to_geo( $custom_map['max'][1] ) ],
						];
						$query['custom'] = $custom_map;
					}
				}

			} else {

				$locations = explode( ',', urldecode( $filters['location'] ) );
				foreach( $locations as $location ) {
					if ( is_numeric( $location ) ) {
						$location = Location::get( absint( $location ) );
						if ( ! $location ) {
							continue;
						}
						$location_id = empty( $location->duplicate_of ) ? $location->ID : $location->duplicate_of;
						if ( $location->type === 'school' ) {
							$query['school'][] = $location_id;
						} else {
							$query['location'][] = $location_id;
						}
					}
				}

			}

			if ( empty( $query['text'] ) && empty( $query['location'] ) && empty( $query['school'] ) && empty( $query['geo'] ) && empty( $query['user'] ) ) {
				return new \WP_Error( 'bad_request', 'Could not return data with no location specified', [ 'status' => 403 ] );
			}

			if ( empty( $query['location'] ) ) {
				unset( $query['location'] );
			}

		} elseif ( empty( $query['text'] ) && ! is_array( $query['geo'] ) && empty( $sample ) && empty( $query['user'] ) ) {

			throw new \Exception( 'Cannot query properties without a location or geographic area.' );
		}

		if ( ! empty( $query['user'] ) && is_numeric( $query['user'] ) ) {

			$query[ 'user' ] = absint( $query['user'] );
			if ( isset( $filters['friend_favorites'] ) ) {
				$filters[ 'friend_favorites' ] = $filters['friend_favorites'];
			}

		}

		if ( $page_num > 1 ) {
			$query['offset'] = $per_page * ( $page_num - 1 );
		}

		if ( $show_hidden !== true ) {
			$query['data'][] = [
				'key'        => 'hidden',
				'comparison' => '=',
				'value'      => 0,
			];
		} else {
			$query['show_hidden'] = true;
		}

        if($_GET['customer']) {
            foreach ($query['data'] as $key => $value) {
                if($value['key'] == 'onmarket') {
                    $date = $_GET['date'] ?? date('Y-m-d');
                    $onMarket = strtotime($date . ' - 1 day') * 100;
                    $onMarketPlus = strtotime($date) * 100;
                    $query['data'][$key]['value'][0] = $onMarket;
                    $query['data'][$key]['value'][1] = $onMarketPlus;
                }
            }
        }

        // BREAKPOINT
		//print_r($query);
		$query = apply_filters( 'vestorfilter_query__after_setup', $query, $filters );


		return $query;

	}

	public static function join_lotsize( $query ) {
		$query .= 'LEFT JOIN ' . Cache::$data_table_name . ' as lotsize ON (lotsize.property_id = pr.ID and lotsize.key = "lot") ';
		//remove_filter( 'vestorfilter_sql_after_join', [ self::class, 'join_lotsize' ] );
		return $query;
	}

	public function filter_by_custom_map( $bounds ) {

		if ( ! is_array( $bounds ) ) {
			return;
		}

		$flipped_bounds = [];
		foreach( $bounds as $bound ) {
			$flipped_bounds[] = [ (float) $bound[1], (float) $bound[0] ];
		}

		$new_results = [];
		foreach( $this->results as $property ) {
			if ( ! Location::is_geocoord_in_map( $property, [$flipped_bounds] ) ) {
				continue;
			}
			$new_results[] = $property;

		}

		$this->results = $new_results;

	}

	public function goto_page( $number ) {

		$this->page_num = absint( $number );

	}

	public function set( $key, $value ) {

		$this->args[ $key ] = $value;

	}

	public function get( $key ) {

		return $this->args[ $key ] ?? null;

	}

	public function filter( $key ) {

		return $this->args['filters'][ $key ] ?? null;

	}

	public function total_results() {

		return count( $this->results );

	}

	public function current_page() {

		return $this->page_num;

	}

	public function page_count() {

		return ceil( $this->total_results() / $this->get( 'per_page' ) );

	}

	public function results_string() {

		if ( empty( $this->args['filters']['location'] ) || empty( $this->args['filters']['property-type'] ) ) {
			$return = sprintf( '%s results found', $this->total_results() );
		} else {
			$return = '%s ' . Search::get_results_title_string( $this->args['filters']['location'], $this->args['filters']['property-type'] );
		}

		return apply_filters( 'vestorfilter_search_results_count', $return, $this->total_results() );

	}

	public function get_all() {

		return $this->results;

	}

	public function get_page_loop( $args ) {

		$args = wp_parse_args( $args, [
			'per_page'  => $this->get( 'per_page' ),
			'offset'    => 0,
			'page'      => 1,
			'overshoot' => 0,
		] );

		$args['page'] -= 1;
		if ( $args['page'] < 0 ) {
			$args['page'] = 0;
		}

		$overshoot = absint( $args['overshoot'] );

		if ( empty( $args['per_page'] ) || $args['per_page'] < 0 ) {

			$loop_data = $this->results;

		} else {

			if ( $args['page'] * $args['per_page'] + $args['offset'] > $this->total_results() ) {
				return false;
			}

			$loop_data = array_slice( $this->results, $args['page'] * $args['per_page'] + $args['offset'], $args['per_page'] + $overshoot );

		}

		$properties = [];
		foreach ( $loop_data as $property ) {
			$new_property = new Property( $property );
			if ( $this->current_vf ) {
				$new_property->load_vestorfilter( $this->current_vf );
			}
			$properties[] = $new_property;
		}

		return new Loop( $properties );

	}

}
