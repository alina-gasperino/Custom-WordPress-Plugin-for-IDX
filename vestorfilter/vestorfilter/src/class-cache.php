<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Cache extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public static $source_table_name = 'propertysource';

	public static $prop_table_name  = 'propertycache';
	public static $meta_table_name  = 'propertycache_meta';
	public static $text_table_name  = 'propertycache_text';
	public static $addr_table_name  = 'propertycache_address';
	public static $addrpart_table_name  = 'propertycache_address_part';

	public static $cache_table_name  = 'propertycache_cache';
	public static $results_table_name  = 'propertycache_results';


	public static $tax_table_name   = 'propertycache_taxonomy';
	public static $index_table_name = 'propertycache_index';
	public static $value_table_name = 'propertycache_value';

	public static $data_table_name  = 'propertycache_data';

	public static $photo_table_name = 'propertycache_photo';

	public function install() {

		global $vfdb;

		self::$source_table_name = $vfdb->prefix . self::$source_table_name;

		self::$prop_table_name = $vfdb->prefix . self::$prop_table_name;
		self::$meta_table_name = $vfdb->prefix . self::$meta_table_name;
		self::$text_table_name = $vfdb->prefix . self::$text_table_name;
		self::$data_table_name = $vfdb->prefix . self::$data_table_name;
		self::$addr_table_name = $vfdb->prefix . self::$addr_table_name;
		self::$addrpart_table_name = $vfdb->prefix . self::$addrpart_table_name;

		self::$cache_table_name = $vfdb->prefix . self::$cache_table_name;
		self::$results_table_name = $vfdb->prefix . self::$results_table_name;

		self::$tax_table_name   = $vfdb->prefix . self::$tax_table_name;
		self::$index_table_name = $vfdb->prefix . self::$index_table_name;
		self::$value_table_name = $vfdb->prefix . self::$value_table_name;

		self::$photo_table_name = $vfdb->prefix . self::$photo_table_name;

		add_action( 'rest_api_init', array( $this, 'init_rest' ) );

	}

	public function init_rest() {

		register_rest_route( 'vestorfilter/v1', "/cache/property/(?P<id>\d+)", array(
			'methods'             => 'GET',
			'callback'            => array( Property::class, 'get_cache' ),
			'permission_callback' => '__return_true',
			'args' => array(
				'id' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					},
				),
			),
		) );

	}

	public static function get_endpoint() {

		return get_rest_url( null, 'vestorfilter/v1/cache/' );

	}

	public static function get_property_by( $key, $value, $source_id = '' ) {

		global $vfdb;

		$key = strtolower( $key );

		$sources = is_array( $source_id ) ? implode( ',', $source_id ) : $source_id;

		$results = wp_cache_get( "property_lookup_$key--$value--$sources", 'vestorfilter' );
		if ( $results !== false ) {
			return $results;
		}

		$proptable = self::$prop_table_name;
		if ( $key === 'mlsid' ) {

			$query = $vfdb->prepare( "SELECT * FROM $proptable WHERE MLSID = %s", $value );

		} elseif ( $key === 'listing_id' ) {

			$query = $vfdb->prepare( "SELECT * FROM $proptable WHERE listing_id = %s", $value );

		} elseif ( $key === 'id' ) {

			$query = $vfdb->prepare( "SELECT * FROM $proptable WHERE ID = %d", $value );

		}

		if ( ! empty ( $source_id ) ) {
			if ( is_string( $source_id ) ) {
				$query .= $vfdb->prepare( " AND post_id = %d", $source_id );
			} elseif ( is_array( $source_id ) ) {
				$query .= sprintf( " AND post_id IN (%s)", implode( ',', $source_id ) );
			}
		}

		$results = $vfdb->get_results( $query );
		if ( ! empty( $results ) ) {
			wp_cache_set( "property_lookup_$key--$value--$sources", $results, 'vestorfilter' );
		}

		return $results;

	}

	public static function find_property_mlsid( $search, $source_id = '' ) {

		global $vfdb;

		$sources = implode( ',', $source_id );

		$results = wp_cache_get( "mls_search--$search--$sources", 'vestorfilter' );
		if ( $results !== false ) {
			return $results;
		}

		$proptable = self::$prop_table_name;
		$query = $vfdb->prepare( "SELECT * FROM $proptable WHERE MLSID LIKE %s", '%' . absint( $search ) . '%' );


		if ( ! empty ( $source_id ) ) {
			if ( is_string( $source_id ) ) {
				$query .= $vfdb->prepare( " AND post_id = %d", $source_id );
			} elseif ( is_array( $source_id ) ) {
				$query .= sprintf( " AND post_id IN (%s)", implode( ',', $source_id ) );
			}
		}

		$results = $vfdb->get_results( $query );
		if ( ! empty( $results ) ) {
			wp_cache_set( "mls_search--$search--$sources", $results, 'vestorfilter' );
		}

		return $results;

	}

	public static function search_property_address( $search, $sources ) {

		global $vfdb;

		$sourcetable = self::$source_table_name;
		$proptable = self::$prop_table_name;
		$addrtable = self::$addr_table_name;

		$basequery = "SELECT pt.*, mt.full_address FROM $proptable pt";

		$search = Property::sanitize_address_string( $search );
		$parts = explode( ' ', $search );

		$terms = [];
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( empty( $part ) || strlen( $part ) === 1 ) {
				continue;
			}

			$terms[] = $vfdb->prepare( "mt.full_address LIKE %s", '%' . $part . '%' );

		}

		if ( empty( $terms ) ) {
			return [];
		}

		$wherequery = "WHERE " . implode( ' AND ', $terms );

		if ( is_string( $sources ) ) {
			$wherequery .= $vfdb->prepare( " AND pt.post_id = %d", $source_id );
		} elseif ( is_array( $sources ) ) {
			$wherequery .= sprintf( " AND pt.post_id IN (%s)", implode( ',', $sources ) );
		}

		$wherequery .= " AND pt.hidden = 0";

		$join = "LEFT JOIN {$addrtable} mt ON ( pt.ID = mt.property_id )";

		$results = $vfdb->get_results( "$basequery $join $wherequery GROUP BY pt.ID ORDER BY pt.ID DESC LIMIT 0,20" );

		return $results;

	}

	public static function create_property( $source_id, $mlsid, $listing_id, $slug = '', $modified = null ) {

		global $vfdb;

		if ( $vfdb->insert(
			self::$prop_table_name,
			[
				'post_id'    => $source_id,
				'MLSID'      => $mlsid,
				'listing_id' => $listing_id,
				'slug'       => substr( $slug, 0, 50 ),
				'modified'   => $modified ?: time(),
			],
			[
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
			]
		) ) {
			return $vfdb->insert_id;
		} else {
			throw new \Exception( "Couldn't create record for $mlsid" );
		}

	}

	public static function update_property( $property_id, $data ) {

		global $vfdb;
		
		if ( $vfdb->update( self::$prop_table_name, $data, [ 'ID' => $property_id ] ) !== false ) {
			//echo "$property_id\n";
		    return $property_id;
		} else {
			throw new \Exception( "Couldn't update record for $property_id" );
		}

	}

	public static function create_taxonomy( $data ) {

		global $vfdb;

		if ( empty( $data ) ) {
			throw new \Exception( "Can't create an empty taxonomy" );
		}

		if ( empty( $data['name'] ) ) {
			$data['name'] = ucwords( str_replace( [ '-', '_' ], ' ', $data['slug'] ) );
		}
		if ( empty( $data['slug'] ) ) {
			$data['slug'] = sanitize_title( $data['name'] );
		}

		if ( $vfdb->insert(
			self::$tax_table_name,
			$data
		) ) {
			return $vfdb->insert_id;
		} else {
			throw new \Exception( "Couldn't create record for $mlsid" );
		}

	}

	public static function find_taxonomy_by( $field, $value ) {

		global $vfdb;


		$results = wp_cache_get( "tax_lookup_$field--$value", 'vestorfilter' );
		if ( $results !== false ) {
			return $results;
		}

		if ( $field !== 'name' && $field !== 'slug' ) {
			return false;
		}

		$taxtable = self::$tax_table_name;
		$query = $vfdb->prepare( "SELECT * FROM $taxtable WHERE $field = %s", $value );

		$results = $vfdb->get_row( $query );
		if ( ! empty( $results ) ) {
			wp_cache_set( "tax_lookup_$field--$value", $results, 'vestorfilter' );
		}

		return $results;

	}

	public static function find_value( $taxonomy, $value ) {

		global $vfdb;


		$results = wp_cache_get( "data_lookup_$taxonomy--$value", 'vestorfilter' );
		if ( $results !== false ) {
			return $results;
		}

		$datatable = self::$value_table_name;
		$query = $vfdb->prepare( "SELECT ID FROM $datatable WHERE `taxonomy` = %d AND `value` = %s", $taxonomy, $value );

		$results = $vfdb->get_var( $query );
		if ( ! empty( $results ) ) {
			wp_cache_set( "data_lookup_$taxonomy--$value", $results, 'vestorfilter' );
		}

		return $results;

	}

	public static function get_favorite_properties( $user, $friends = false, $hidden = false ) {

		global $vfdb;

		$proptable = self::$prop_table_name;
		$locindextable = Location::$index_name;
		$locationtable = Location::$table_name;
		$geotable = Location::$geo_name;
		$cachetable = self::$cache_table_name;


		$query = 'SELECT ';
		$query .= 'pr.*';
		$query .= ',geo.lat,geo.lon,geo.property_type';
		$query .= ',cache.block_cache,cache.data_cache,cache.address';
		$query .= " FROM $proptable as pr ";
		$query .= " INNER JOIN $geotable AS `geo` ON ( `geo`.property_id = pr.ID )";
		$query .= " INNER JOIN $cachetable AS `cache` ON ( `cache`.property_id = pr.ID )";
		$query .= " WHERE pr.photos > 0";
		if ( ! $hidden ) {
			$query .= ' AND pr.hidden = 0';
		}

		if ( ! empty( $friends ) ) {
			$friend_favs = get_user_meta(
				$user,
				'_friend_favorite'
			);
		} else {
			$friend_favs = [];
		}
		if ( $friends !== 'only' ) {
			$user_favs = get_user_meta(
				$user,
				'_favorite_property'
			);
		} else {
			$user_favs = [];
		}
		$favorites = array_merge( $friend_favs, $user_favs );
		if ( empty( $favorites ) ) {
			return [];
		}

		foreach( $favorites as $id => $fav ) {
			if ( is_numeric( $fav ) ) {
				$favorites[$id] = absint( $fav );
			} else {
				unset( $favorites[$id] );
			}
		}

		$query .= ' AND pr.ID IN ( ' . implode( ',', $favorites ) . ')';

		return $vfdb->get_results( $query );

	}

	public static function get_properties( $search = null ) {

		global $vfdb;

		if ( is_null( $search ) ) {
			$search = [
				'order' => [ 'modified' => 'DESC' ],
			];
		}

		$proptable = self::$prop_table_name;
		$phototable = self::$photo_table_name;
		$texttable = self::$text_table_name;
		$datatable = self::$data_table_name;
		$indextable = self::$index_table_name;
		$cachetable = self::$cache_table_name;

		$locindextable = Location::$index_name;
		$locationtable = Location::$table_name;
		$geotable = Location::$geo_name;


		$query = 'SELECT ';
		if ( ! empty( $search['count'] ) ) {
			$query .= 'COUNT(pr.*)';
		} else {
			$query .= 'pr.*';
			$query .= ',geo.lat,geo.lon,geo.property_type';
			$query .= ',cache.block_cache,cache.data_cache,cache.address';
		}

		$query .= " FROM $proptable as pr ";
		if ( empty( $search['count'] ) ) {
			$query .= " INNER JOIN $geotable AS `geo` ON ( `geo`.property_id = pr.ID )";
			$query .= " INNER JOIN $cachetable AS `cache` ON ( `cache`.property_id = pr.ID )";
		}

		$values = [];
		$where = [];

		if ( defined( 'VF_ALLOWED_FEEDS' ) || ! empty( $search['sources'] ) ) {
			$allowed = $search['sources'] ?? VF_ALLOWED_FEEDS;
			$where[] = 'pr.post_id IN (' . implode( ',', $allowed ) . ')';
		}

		$search_columns = Property::search_columns();

		$index = 0;
		$group_by = [];
		$columns = [];
		foreach( [ 'data' => $datatable, 'text' => $texttable ] as $method => $table_name ) {
			if ( isset( $search[ $method ] ) ) {
				$this_where = [];
				foreach( $search[ $method ] as $what ) {
					$index += 1;
					$join_type = empty( $what['comparison'] ) || ! in_array( $what['comparison'], [ 'EXISTS', 'NOT EXISTS' ] ) ? 'INNER' : 'LEFT';
					if ( ! empty( $what['show_empty'] ) ) {
						$join_type = 'LEFT';
					}
					if ( isset( $columns[ $what['key'] ] ) ) {
						$table_column = $columns[ $what['key'] ];
					} else if ( ! in_array( $what['key'], $search_columns ) ) {
						$table_alias = "d{$index}";
						$table_column = "{$table_alias}.value";
						$query .= $vfdb->prepare(
							"$join_type JOIN $table_name as {$table_alias} ON ({$table_alias}.property_id = pr.ID and {$table_alias}.key = %s) ",
							$what['key']
						);
					} else {
						$table_alias = 'pr';
						$table_column = 'pr.' . $what['key'];
					}
					if ( empty( $what['comparison'] ) ) {

						if ( ! empty( $what['value'] ) ) {
							$this_where[] = $vfdb->prepare( "{$table_column} = %s", $what['value'] );
						} elseif ( ! empty( $what['order'] ) ) {
							$order_by = "{$table_column} " . ( $what['order'] === 'DESC' ? 'DESC' : 'ASC' );
							if ( ! empty( $what['null_last'] ) ) {
								$order_by = "{$table_column} IS NULL, " . $order_by;
							}
						}

					} else if ( $what['comparison'] === 'NOT EXISTS' ) {
						$this_where[] = "{$table_column} IS NULL";
					} else if ( $what['comparison'] === 'EXISTS' ) {
						$this_where[] = "{$table_column} IS NOT NULL";
					} else if ( $what['comparison'] === 'LIKE' ) {
						$this_where[] = $vfdb->prepare( "{$table_column} LIKE %s", $what['value'] );
					} else if ( in_array( $what['comparison'], [ '<', '<=', '>', '>=', '!=', '=' ] ) ) {
						$this_where[] = $vfdb->prepare( "{$table_column} {$what['comparison']} %d", $what['value'] );
					} else if ( $what['comparison'] === 'BETWEEN' ) {
						$this_where[] = $vfdb->prepare(
							"({$table_column} >= %d AND {$table_column} <= %d)",
							$what['value'][0],
							$what['value'][1]
						);
					}

					$group_by[] = $table_column;
					$columns[ $what['key'] ] = $table_column;
				}
				if ( ! empty( $this_where ) ) {
					$where[] = '(' . implode( ' AND ', $this_where ) . ')';
				}
			}
		}
		foreach ( $search['order'] as $key => $dir ) {
			if ( ! in_array( $key, Property::standard_filters() ) ) {
				continue;
			}
			if ( ! in_array( $key, $search_columns ) ) {
				$table_alias = "d{$index}";
				$table_column = "{$table_alias}.value";
			} else {
				$table_alias = 'pr';
				$table_column = 'pr.' . $key;
			}
			if ( ! in_array( $key, $columns ) ) {
				$index += 1;
				if ( $table_alias !== 'pr' ) {
					$query .= $vfdb->prepare(
						"LEFT JOIN $datatable as {$table_alias} ON ({$table_alias}.property_id = pr.ID and {$table_alias}.key = %s) ",
						$key
					);
				}
				$columns[ $key ] = $table_column;
			}

			$search['order'][$key] = [ 'col' => $table_column, 'dir' => $dir ];
		}


		if ( isset( $search['index'] ) ) {
			foreach( $search['index'] as $what ) {
				if ( empty( $what ) ) {
					continue;
				}
				$index += 1;
				$query .= "INNER JOIN $indextable as i{$index} ON (i{$index}.property_id = pr.ID) ";
				if ( is_array( $what ) ) {
					$index_where = "i{$index}.value_id IN (" . implode( ',', $what ) . ')';
				} else {
					$index_where = $vfdb->prepare( "i{$index}.value_id = %d", $what );
				}
				$index_where = apply_filters( 'vestorfilter_sql_index_where', $index_where, $what );
				$where[] = $index_where;
			}
		}
		if ( isset( $search['location'] ) ) {
			$query .= "INNER JOIN $locindextable as li ON (li.property_id = pr.ID)";
			if ( is_array( $search['location'] ) ) {
				$where[] = sprintf( 'li.location_id IN (%1$s) ', implode( ',', $search['location'] ) );
			} else if ( is_numeric( $search['location'] ) ) {
				$where[] = $vfdb->prepare( 'li.location_id = %1$d ', $search['location'] );
			}
		}
		if ( isset( $search['school'] ) ) {
			
			if ( is_array( $search['school'] ) ) {
				$i = 0;
				foreach( $search['school'] as $school_id ) {
					$i++;
					$query .= "INNER JOIN `$locindextable` as `skl{$i}` ON (`skl{$i}`.`property_id` = pr.ID) ";
					$where[] = sprintf( '`skl%d`.`location_id` = %d', $i, $school_id );
				}
			} else if ( is_numeric( $search['school'] ) ) {
				$query .= "INNER JOIN $locindextable as skl ON (skl.property_id = pr.ID) ";
				$where[] = $vfdb->prepare( 'skl.location_id = %d', $search['school'] );
			}

		}
		if ( isset( $search['user'] ) ) {

			$favorites = get_user_meta(
				$search['user'],
				empty( $search['friend_favorites'] ) ? '_favorite_property' : '_friend_favorite'
			);
			if ( empty( $favorites ) ) {
				$favorites = [ 0 ];
			} else {
				foreach( $favorites as &$fav ) {
					$fav = absint( $fav );
				}
			}

			$where[] = 'pr.ID IN ( ' . implode( ',', $favorites ) . ')';
		}
		$query = apply_filters( 'vestorfilter_sql_after_join', $query );

		if ( ! empty( $search['geo'] ) && is_array( $search['geo'] ) ) {

			if ( $search['geo']['min'][0] < $search['geo']['max'][0] ) {
				$min_lat = $search['geo']['min'][0];
				$max_lat = $search['geo']['max'][0];
			} else {
				$min_lat = $search['geo']['max'][0];
				$max_lat = $search['geo']['min'][0];
			}

			if ( $search['geo']['min'][1] < $search['geo']['max'][1] ) {
				$min_lon = $search['geo']['min'][1];
				$max_lon = $search['geo']['max'][1];
			} else {
				$min_lon = $search['geo']['max'][1];
				$max_lon = $search['geo']['min'][1];
			}

			$where[] = sprintf(
				'geo.lat BETWEEN %d AND %d AND geo.lon BETWEEN %d AND %d',
				(int) $min_lat,
				(int) $max_lat,
				(int) $min_lon,
				(int) $max_lon
			);
		}

		if ( ! empty( $where ) ) {
			$query .= 'WHERE ' . implode( ' AND ', $where ) . ' ';
		}

		$query .= 'GROUP BY pr.id';
		$query .= ' ';

		if ( ! empty( $search['order'] ) && empty( $order_by ) ) {
			$query .= ' ORDER BY ';
			foreach ( $search['order'] as $sort => $dir ) {
				if ( is_array( $dir ) ) {
					$query .= "{$dir['col']} {$dir['dir']},";
				} else {
					$query .= "pr.`$sort` $dir,";
				}
			}
			$query = trim( $query, ',' );
		}
		if ( ! empty( $order_by ) ) {
			$query .= ' ORDER BY ' . $order_by;
		}

		if ( ! empty( $search['limit'] ) ) {
			$query .= " LIMIT %d,%d";
			$values[] = $search['offset'] ?? 0;
			$values[] = $search['limit'];
		} /*elseif ( empty( $search['count'] ) ) {
			$query .= " LIMIT 0,700";
		}*/

		if ( ! empty( $values ) ) {
			$query = $vfdb->prepare( $query, $values );
		}

		remove_all_filters( 'vestorfilter_sql_after_join' );
		remove_all_filters( 'vestorfilter_sql_index_where' );

		return $vfdb->get_results( $query );

	}

    public static function get_properties_new( $search = null ) {

        global $vfdb;
        global $wpdb;


        if ( is_null( $search ) ) {
            $search = [
                'order' => [ 'modified' => 'DESC' ],
            ];
        }

        $table = $wpdb->prefix . 'test_table';
        $table = $wpdb->prefix . 'property_v2';

        $locindextable = Location::$index_name;
        $texttable = self::$text_table_name;

        $query = 'SELECT ';
        if ( ! empty( $search['count'] ) ) {
            $query .= 'COUNT(pr.*)';
        } else {
            $query .= 'pr.id, pr.block_cache, pr.status, pr.data_cache, pr.lot, pr.property_id, pr.mlsid, pr.location, pr.property_type, pr.photos, pr.price, pr.slug, pr.hidden, pr.modified, pr.post_id, pr.lat, pr.lon, pr.address';
        }
        if($_GET['vf'] == 'ppsf-dev') {
            $query .= " FROM $table as pr ";
        } else {
            $query .= " FROM $table as pr ";
        }

		$values = [];
        $where = [];

        if ( defined( 'VF_ALLOWED_FEEDS' ) || ! empty( $search['sources'] ) ) {
            $allowed = $search['sources'] ?? VF_ALLOWED_FEEDS;
            $where[] = 'pr.`post_id` IN (' . implode( ',', $allowed ) . ')';
        }

        $land = true;
        if(isset($search['index'])) {
            foreach ($search['index'] as $index) {
                if(in_array(4154, $index)) {
                    $land = false;
                }
            }
        }
        if(isset($_GET['frequency'])) {
			print_r($search['data']);
            foreach ($search['data'] as $key => $data) {
                if($data['key'] == 'onmarket') {
                    $search['data'][$key]['value'][0] = strtotime(date('Y-m-d', $data['value'][0] / 100)) * 100;
                    $search['data'][$key]['value'][1] = strtotime(date('Y-m-d', $data['value'][1] / 100)) * 100;
                }
            }
        }
        if( isset( $search['data'] ) ) {
            foreach ( $search['data'] as $data ) {
                if ( empty( $data ) ) {
                    continue;
                }
                $key = $data['key'];
                if(in_array($key, ['ppsf', 'bpd', 'ppls', 'bpc', 'ppu', 'ppbc', 'elc', 'onmarket', 'fixer', 'oh', 'ss', 'units', 'auc', '3p', 'stories'])) {
                    $order = $data['order'];
                    if($order != '') {
                        $order_by = " ORDER BY pr.`$key` IS NULL, pr.`$key` = 0, pr.`$key` $order";
//                        if($land) {
                        if($key == 'ppbc') {
                            $where[] = " pr.`$key` != 0 ";
                        }
                    } else {
                        $comparison = $data['comparison'];
                        if($comparison == 'EXISTS') {
                            $where[] = " pr.`$key` IS NOT NULL ";
                            $where[] = " pr.`$key` != 0 ";
                        } else if($comparison == 'NOT EXISTS') {
                            $where[] = " pr.`$key` IS NULL ";
//                            $where[] = " pr.`$key` = 0 ";
                        } else if ($comparison == 'BETWEEN') {
                            $value = $data['value'];
                            $where[] = " pr.`$key` $comparison $value[0] AND $value[1]";
                        } else if(empty($comparison)) {
                            $value = $data['value'];
                            $where[] = " pr.`$key` = $value";
                        } else {
                            $value = $data['value'];
                            $where[] = " pr.`$key` $comparison $value ";
                        }
                    }
                } else {
                    $comparison = $data['comparison'];
                    $value = $data['value'];
                    if($key == 'hoa' && $comparison == '<=') {
                        $where[] = "pr.`$key` > 0 ";
                    }
                    if ($key != 'sold' && $key != 'last_updated') {
                        if ($comparison != 'BETWEEN') {
                            if(empty($comparison)) {
                                $where[] = "pr.`$key` = $value";
                            } else {
                                $where[] = "pr.`$key` $comparison $value";
                            }
                        } else {
                            $where[] = "pr.`$key` $comparison $value[0] AND $value[1]";
                        }
                    }
                }
            }
        }

        if ( isset( $search['index'] ) ) {
            foreach( $search['index'] as $what ) {
                if ( empty( $what ) ) {
                    continue;
                }
                if(in_array($what[0], [4145,4146,4147,4148,4149,4150,4151])) {
                    $index_where = "pr.`status` IN (" . implode( ',', $what ) . ")";
                    if(in_array($what[0], [4146])) {
                        $date = strtotime("now - 1 year");
                        $date = $date * 100;
                        $where[] = "pr.`sold` >= $date";
                    }
                } else if(in_array($what[1], [54291,54292,54293,54294,54295,54296,54297,54298,54299,54300,54301,54302,
                    54303,54304,54305,54314,4159,4160,4161,4162,4163,4164,4165,4166,4167,4168,4169,4170,4171,4172,4173,4174,4175])) {
                    $index_where = "pr.`lot` IN (" . implode(',', $what) . ")";
                } else if(in_array($what[0], [4152,4153,4154,4155,4156,4158])) {
                    $index_where = "pr.`pt_index` IN (" . implode(',', $what) . ")";
                } else if(in_array($what[0], [4157])) {
                    $index_where = "pr.`55plus` IS NOT NULL AND pr.`55plus` != 0 ";
                }
                if($index_where != '') {
                    $where[] = $index_where;
                }
            }
            $index_where = " pr.`pt_index` != 0 ";
            $where[] = $index_where;
        }

        if( isset ( $search['text'] ) && !empty ( $search['text'] ) ) {
            foreach ( $search['text']  as $textsearch ) {
                $comparison = $textsearch['comparison'];
                $value = $textsearch['value'];
                $where[] = " des.`value` $comparison '$value' ";
            }
            $query .= ' INNER JOIN wp_propertycache_text as des ON pr.`property_id` = des.`property_id` ';
        }
        if ( isset( $search['user'] ) ) {

            $favorites = get_user_meta(
                $search['user'],
                empty( $search['friend_favorites'] ) ? '_favorite_property' : '_friend_favorite'
            );
            if ( empty( $favorites ) ) {
                $favorites = [ 0 ];
            } else {
                foreach( $favorites as &$fav ) {
                    $fav = absint( $fav );
                }
            }

            $where[] = 'pr.ID IN ( ' . implode( ',', $favorites ) . ')';
        }
        $query = apply_filters( 'vestorfilter_sql_after_join', $query );


        if ( ! empty( $search['modified_after'] ) ) {
            $where[] = sprintf( 'pr.modified >= %d', $search['modified_after'] );
        }

        if ( ! empty( $search['geo'] ) && is_array( $search['geo'] ) ) {

            if ( $search['geo']['min'][0] < $search['geo']['max'][0] ) {
                $min_lat = $search['geo']['min'][0];
                $max_lat = $search['geo']['max'][0];
            } else {
                $min_lat = $search['geo']['max'][0];
                $max_lat = $search['geo']['min'][0];
            }

            if ( $search['geo']['min'][1] < $search['geo']['max'][1] ) {
                $min_lon = $search['geo']['min'][1];
                $max_lon = $search['geo']['max'][1];
            } else {
                $min_lon = $search['geo']['max'][1];
                $max_lon = $search['geo']['min'][1];
            }

            $where[] = sprintf(
                'pr.lat BETWEEN %d AND %d AND pr.lon BETWEEN %d AND %d',
                (int) $min_lat,
                (int) $max_lat,
                (int) $min_lon,
                (int) $max_lon
            );
        }

        if ( ! empty( $where ) ) {
            $query .= 'WHERE ' . implode( ' AND ', $where ) . ' ';
        }
        if ( ! empty( $order_by ) ) {
            $query .= $order_by;
        }

        $query = str_replace('WHERE AND', 'WHERE', $query);
        return $vfdb->get_results( $query );

    }


    public static function clean_property_index( $property_id, $tax ) {

		global $vfdb;

		if ( ! is_object( $tax ) ) {
			$tax = self::find_taxonomy_by( 'name', $tax );
		}
		if ( empty( $tax ) || ! is_object( $tax ) ) {
			return;
		}

		$terms = self::get_index_values( $tax );
		$ids = array();
		foreach( $terms as $term ) {
			$ids[] = $term->ID;
		}
		if ( empty( $ids ) ) {
			return;
		}

		$table = self::$index_table_name;

		$query = "DELETE FROM $table WHERE property_id = %s AND value_id IN (" . implode( ',', $ids ) . ")";
		$query = $vfdb->prepare( $query, $property_id );

		return $vfdb->query( $query );

	}

	public static function get_indexes( $property_id, $skip_cache = false ) {

		global $vfdb;

		if ( ! $skip_cache ) {
			$meta = wp_cache_get( "index_{$property_id}", 'vestorfilter' );
			if ( $meta !== false ) {
				return $meta;
			}
		}

		$indextable = self::$index_table_name;
		$valuetable = self::$value_table_name;
		$taxtable   = self::$tax_table_name;
		$query = $vfdb->prepare(
			"SELECT pci.ID as index_id, pct.`slug` as taxonomy, pcv.`value` as `value` FROM $indextable pci "
			. "JOIN $valuetable pcv ON (pcv.ID = pci.value_id) "
			. "JOIN $taxtable pct ON (pct.ID = pcv.taxonomy) "
			. "WHERE pci.property_id = %d",
			[ $property_id ]
		);

		$results = $vfdb->get_results( $query );


		if ( ! empty( $results ) ) {
			$organized = [];

			foreach( $results as $row ) {
				if ( empty( $organized[ $row->taxonomy ] ) ) {
					$organized[ $row->taxonomy ] = array();
				}
				$organized[ $row->taxonomy ][ $row->index_id ] = $row->value;
			}
			$results = $organized;

			wp_cache_set( "index_{$property_id}", $results, 'vestorfilter' );
		} else {
			return [];
		}

		return $results;

	}

	public static function get_data( $property_id ) {

		global $vfdb;


		$meta = wp_cache_get( "data_{$property_id}", 'vestorfilter' );
		if ( $meta !== false ) {
			return $meta;
		}

		$datatable = self::$data_table_name;
		$query = $vfdb->prepare( "SELECT `key`,`value` FROM $datatable WHERE property_id = %d", $property_id );

		$results = $vfdb->get_results( $query );

		if ( ! empty( $results ) ) {
			$meta = [];
			foreach ( $results as $row ) {
				$meta[ $row->key ] = $row->value;
			}
			//var_dump( $meta );
			//exit;
			wp_cache_set( "data_{$property_id}", $meta, 'vestorfilter' );
		} else {
			return [];
		}

		return $meta;

	}

	public static function get_data_value( $property_id, $key ) {

		global $vfdb;


		$meta = wp_cache_get( "data_{$property_id}_{$key}", 'vestorfilter' );
		if ( $meta !== false ) {
			return $meta;
		}

		$datatable = self::$data_table_name;
		$query = $vfdb->prepare( "SELECT `value` FROM $datatable WHERE `property_id` = %d AND `key` = %s", $property_id, $key );

		$value = $vfdb->get_var( $query );

		if ( ! empty( $value ) ) {
			wp_cache_set( "data_{$property_id}_{$key}", $value, 'vestorfilter' );
		}

		return $value;

	}



	public static function clean_index( $property_id ) {

		global $vfdb;

		$vfdb->get_var( $vfdb->prepare(
			'DELETE FROM ' . self::$index_table_name . ' WHERE `property_id` = %d',
			$property_id,
		) );

	}

	public static function delete_data( $property_id, $key ) {

		global $vfdb;

		$old = $vfdb->get_var( $vfdb->prepare(
			'DELETE FROM ' . self::$data_table_name . ' WHERE `property_id` = %d and `key` = %s',
			$property_id,
			$key
		) );

	}

	public static function add_index( $property_id, $key, $value, $check_duplicates = false ) {

		global $vfdb;

		if ( is_object( $key ) ) {

			$tax = $key;
			$taxonomy_id = $tax->ID;

		} else {

			$tax = self::find_taxonomy_by( 'name', $key );
			if ( empty( $tax ) ) {
				$taxonomy_id = self::create_taxonomy( [ 'name' => $key ] );
			} else {
				$taxonomy_id = $tax->ID;
			}

		}

		$value_id = self::find_value( $taxonomy_id, $value );
		if ( empty( $value_id ) ) {
			$vfdb->insert(
				self::$value_table_name,
				[ 'taxonomy' => $taxonomy_id, 'value' => $value ],
				[ '%d', '%s' ]
			);
			$value_id = $vfdb->insert_id;
		}

		if ( $check_duplicates ) {
			$dupe = $vfdb->get_var( $vfdb->prepare(
				'SELECT `ID` FROM ' . self::$index_table_name . ' WHERE `property_id` = %d and `value_id` = %d',
				$property_id,
				$value_id
			) );
			if ( ! empty( $dupe ) ) {
				return $dupe;
			}
		}

		if ( $vfdb->insert(
			self::$index_table_name,
			[ 'property_id' => $property_id, 'value_id' => $value_id ],
			[ '%d', '%d' ]
		) ) {
			return $vfdb->insert_id;
		} else {
			return false;
		}

	}

	public static function add_address( $property_id, $value ) {

		global $vfdb;

		$value = Property::sanitize_address_string( $value );

		if ( $vfdb->insert(
				self::$addr_table_name,
				[ 'property_id' => $property_id, 'full_address' => $value ],
				[ '%d', '%s' ]
		) ) {
			return $vfdb->insert_id;
		} else {
			throw new \Exception( "There was an error creating the address record ($key:$value)" );
		}

	}

	public static function update_address( $property_id, $value ) {

		global $vfdb;

		$value = Property::sanitize_address_string( $value );

		$old = $vfdb->get_var( $vfdb->prepare(
			'SELECT `ID` FROM ' . self::$addr_table_name . ' WHERE `property_id` = %d',
			$property_id
		) );

		if ( empty( $old ) ) {

			return self::add_address( $property_id, $value );

		} else {

			if ( $vfdb->update(
					self::$addr_table_name,
					[ 'full_address' => $value ],
					[ 'ID' => $old ],
					[ '%s' ],
					[ '%d' ]
			) !== false ) {
				return $old;
			} else {
				throw new \Exception( "There was an error updating the address record ($property_id:$value)" );
			}

		}

	}

	public static function add_data( $property_id, $key, $value ) {

		global $vfdb;
		
		echo "\nadding data: $property_id\n";

		if ( $vfdb->insert(
				self::$data_table_name,
				[ 'property_id' => $property_id, 'key' => $key, 'value' => $value ],
				[ '%d', '%s', '%d' ]
		) ) {
			return $vfdb->insert_id;
		} else {
			throw new \Exception( "There was an error creating the meta record ($key:$value)" );
		}

	}

	public static function update_data( $property_id, $key, $value ) {

		global $vfdb;

		$old = $vfdb->get_var( $vfdb->prepare(
			'SELECT `ID` FROM ' . self::$data_table_name . ' WHERE `property_id` = %d and `key` = %s',
			$property_id,
			$key
		) );

		if ( empty( $old ) ) {

			return self::add_data( $property_id, $key, $value );

		} else {

			if ( $vfdb->update(
					self::$data_table_name,
					[ 'value' => $value ],
					[ 'ID' => $old ],
					[ '%d' ],
					[ '%d' ]
			) !== false ) {
				return $old;
			} else {
				throw new \Exception( "There was an error updating the data record ($key:$value)" );
			}

		}

	}



	public static function get_data_query( $key, $function ) {

		global $vfdb;

		$value = wp_cache_get( "data_query_{$function}_{$key}", 'vestorfilter' );
		if ( ! empty( $value ) ) {
			return $value;
		}

		$var = $vfdb->get_var( $vfdb->prepare(
			"SELECT {$function}(`value`) FROM " . self::$data_table_name . ' WHERE `key` = %s',
			$key
		) );

		if ( ! empty( $var ) ) {
			$var = $var / 100;
			wp_cache_set( "data_query_{$function}_{$key}", $var, 'vestorfilter' );
		}

		return $var;

	}

	public static function get_index_query( $taxonomy_slug, $function ) {

		global $vfdb;

		$value = wp_cache_get( "index_query_{$function}_{$taxonomy_slug}", 'vestorfilter' );
		if ( ! empty( $value ) ) {
			return $value;
		}

		$taxonomy = self::find_taxonomy_by( 'slug', $taxonomy_slug );
		if ( empty( $taxonomy ) ) {
			return false;
		}

		$var = $vfdb->get_var( $vfdb->prepare(
			"SELECT {$function}(`value`) FROM " . self::$value_table_name . ' WHERE `taxonomy` = %d',
			$taxonomy->ID
		) );

		if ( ! empty( $var ) ) {
			wp_cache_set( "index_query_{$function}_{$taxonomy_slug}", $var, 'vestorfilter' );
		}

		return $var;

	}

	public static function get_index_values( $taxonomy_slug, $order = '' ) {

		global $vfdb;

		if ( is_object( $taxonomy_slug ) ) {
			$taxonomy = $taxonomy_slug;
			$taxonomy_slug = $taxonomy->slug;
		}

		$value = wp_cache_get( "index_query_{$taxonomy_slug}_{$order}", 'vestorfilter' );
		if ( ! empty( $value ) ) {
			return $value;
		}

		if ( empty( $taxonomy ) ) {
			$taxonomy = self::find_taxonomy_by( 'slug', $taxonomy_slug );
			if ( empty( $taxonomy ) ) {
				return false;
			}
		}

		$query = 'SELECT * FROM ' . self::$value_table_name . ' WHERE `taxonomy` = %d';
		if ( ! empty( $order ) ) {
			$query .= ' ORDER BY ' . $order;
		}

		$vars = $vfdb->get_results( $vfdb->prepare(
			$query,
			$taxonomy->ID
		) );

		if ( ! empty( $vars ) ) {
			wp_cache_set( "index_query_{$taxonomy_slug}_{$order}", $vars, 'vestorfilter' );
		}

		return $vars;

	}

	public static function get_index( $property_id, $taxonomy_slug, $single = false ) {

		global $vfdb;

		if ( is_object( $taxonomy_slug ) ) {
			$taxonomy      = $taxonomy_slug;
			$taxonomy_slug = $taxonomy->slug;
			$taxonomy_id   = $taxonomy->ID;
		}

		$value = wp_cache_get( "data_{$property_id}_{$taxonomy_slug}", 'vestorfilter' );
		if ( ! empty( $value ) ) {
			return $single ? $value[0] : $value;
		}

		if ( empty( $taxonomy_id ) ) {
			$taxonomy = self::find_taxonomy_by( 'slug', $taxonomy_slug );
			if ( empty( $taxonomy ) ) {
				return false;
			}
			$taxonomy_id = $taxonomy->ID;
		}

		$query = 'SELECT tv.value '
			   . 'FROM ' . self::$index_table_name . ' ti '
			   . 'JOIN ' . self::$value_table_name . ' tv ON ( tv.ID = ti.value_id AND tv.taxonomy = %d ) '
			   . 'WHERE ti.property_id = %d';

		$query = $vfdb->prepare(
			$query,
			$taxonomy_id,
			$property_id
		);

		$values = $vfdb->get_col( $query );

		if ( ! empty( $values ) ) {
			wp_cache_set( "data_{$property_id}_{$taxonomy_slug}", $values, 'vestorfilter' );
			return $single ? $values[0] : $values;
		}

		return null;

	}

	public static function update_text( $property_id, $key, $value, $delete_empty = false ) {

		global $vfdb;

		$old = $vfdb->get_var( $vfdb->prepare(
			'SELECT `ID` FROM ' . self::$text_table_name . ' WHERE `property_id` = %d and `key` = %s',
			$property_id,
			$key
		) );

		if ( empty( $old ) ) {

			if ( ! empty( $value ) ) {

				return $vfdb->insert(
					self::$text_table_name,
					[ 'property_id' => $property_id, 'key' => $key, 'value' => $value ],
					[ '%d', '%s', '%s' ],
				);

			} else {

				return false;

			}

		} else {

			$update = $vfdb->update(
				self::$text_table_name,
				[ 'value' => $value ],
				[ 'ID' => $old ],
				[ '%s' ],
				[ '%d' ]
			);

			if ( $update !== false ) {
				return $old;
			} else {
				throw new \Exception( "There was an error updating the meta record ($property_id / $key:$value:$short)" );
			}

		}

	}

	public static function get_results( $hash, $fallback_query = null, $geo = null, $reset_geo = null ) {

		global $vfdb;

		$name = self::$results_table_name;

		$results = $vfdb->get_row( $vfdb->prepare( "SELECT * FROM $name WHERE `hash` = %s", $hash ) );
		if ( empty( $results ) ) {
			return null;
		}

		$inside = true;
		if ( $geo && $results->min_lat && $results->max_lat && $results->min_lon && $results->max_lon ) {
			if ( $results->min_lat > (int) $geo['min'][0] + 1000
			  || $results->min_lon > (int) $geo['min'][1] + 1000
			  || $results->max_lat < (int) $geo['max'][0] - 1000
			  || $results->max_lon < (int) $geo['max'][1] - 1000 ) {
				$inside = false;
			}
		}

		if ( $results->expires < time() || ! $inside ) {
			if ( ! empty( $fallback_query ) ) {
				$filters = json_decode( $results->filters, true );
				
				if ( ! empty( $filters ) ) {
					$fallback_query['filters'] = $filters;
					
				
					if ( ! empty( $reset_geo ) ) {

						$min_lat = min( $results->min_lat, $reset_geo['min'][0] );
						$max_lat = max( $results->max_lat, $reset_geo['max'][0] );
						$min_lon = min( $results->min_lon, $reset_geo['min'][1] );
						$max_lon = max( $results->max_lon, $reset_geo['max'][1] );

						unset( $fallback_query['filters']['location'] );
						$fallback_query['geo'] = [
							'min' => [ $min_lat, $min_lon ],
							'max' => [ $max_lat, $max_lon ],
						];
					}
					
					try {
						$query = new Query( $fallback_query );
						$properties = $query->get_all();
					} catch( \Exception $e ) {}

				}
			}
			if ( empty( $properties ) ) {
				$vfdb->query( $vfdb->prepare( "DELETE FROM $name WHERE `hash` = %s", $hash ) );
				return null;
			} else {
				$expires = time() + 1800;
				if ( $geo ) {
					$coords = $vfdb->prepare('
								`min_lat` = %d,
								`min_lon` = %d,
								`max_lat` = %d,
								`max_lon` = %d,',
								$fallback_query['geo']['min'][0],
								$fallback_query['geo']['min'][1],
								$fallback_query['geo']['max'][0],
								$fallback_query['geo']['max'][1]
							);
					/*if ( $default_zoom ) {
						$coords .= $vfdb->prepare( '`def_zoom` = %d,', $default_zoom );
					}*/
				} else { 
					$coords = '';
				}

				try {
					if ( count( $properties ) < 4000 && ! empty( $properties ) ) {
						$vfdb->query( $vfdb->prepare(
							"UPDATE $name SET
								$coords
								`count` = %d,
								`expires` = %d,
								`results` = %s 
								WHERE `hash` = %s ",
							count( $properties ),
							$expires,
							serialize( $properties ),
							$hash
						) );
					}
				} catch( \Exception $e ) {};

				$results->results = $properties;
				$results->expires = $expires;
			}
		} else {
			$results->results = unserialize( $results->results );
		}

		return $results;

	}

	public static function set_results( $columns ) {

		global $vfdb;

		$name = self::$results_table_name;

		if ( empty( $columns['filters'] ) && empty( $columns['hash'] ) ) {
			throw new \Exception( "Must specify the filters or hash value of cache results." );
		}

		if ( empty( $columns['results'] ) ) {
			$columns['results'] = [];
		}
		if ( count( $columns['results'] ) > 4000 ) {
			return;
		}
		
		if ( empty( $columns['filters'] ) ) {
			$columns['filters'] = [];
		}
		if ( empty( $columns['expires'] ) ) {
			$columns['expires'] = time() + 3600;
		}
		if ( empty( $columns['hash'] ) ) {
			$columns['hash'] = Search::get_hash( $columns['filters'] );
		}

		if ( count( $columns['results'] ) > 1000 && empty( $columns['min_lat'] ) ) {
			$columns['results'] = array_slice( $columns['results'], 0, 1000 );
		}

		if ( empty( $columns['results'] ) ) {
			return;
		}

		$replace = [
			'initialized' => time(),
			'expires'     => absint( $columns['expires'] ) ?: time() + 3600,
			'hash'        => substr( $columns['hash'], 0, 34 ),
			'filters'     => json_encode( $columns['filters'] ),
			'results'     => serialize( $columns['results'] ),
			'count'       => absint( $columns['total'] ?? 0 ) ?: count( $columns['results'] ),
			'min_lat'     => $columns['min_lat'] ?? 0,
			'min_lon'     => $columns['min_lon'] ?? 0,
			'max_lat'     => $columns['max_lat'] ?? 0,
			'max_lon'     => $columns['max_lon'] ?? 0,
			'center_lat'  => $columns['center_lat'] ?? 0,
			'center_lon'  => $columns['center_lon'] ?? 0,
		];
		/*if ( ! empty( $columns['def_zoom'] ) ) {
			$replace['def_zoom'] = $columns['def_zoom'];
		}*/

		$vfdb->replace( $name, $replace, [ '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' ] );

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Cache', 'init' ) );

