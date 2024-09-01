<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Location extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public static $table_name = 'locationcache';
	public static $index_name = 'locationcache_property';
	public static $map_name   = 'locationcache_map';
	public static $geo_name   = 'locationcache_geo';

	public static $allowed_types = [
		'city', 'state', 'zip', 'county', 'school', 'neighborhood'
	];

	public function install() {

		global $vfdb;

		self::$table_name = $vfdb->prefix . self::$table_name;
		self::$index_name = $vfdb->prefix . self::$index_name;
		self::$map_name = $vfdb->prefix . self::$map_name;
		self::$geo_name = $vfdb->prefix . self::$geo_name;

		self::$allowed_types = apply_filters( 'vestorfilter_allowed_location_types', self::$allowed_types );

		//$this->maybe_install_db_table();

		add_shortcode( 'vestorfilter-locations', array( $this, 'location_block' ) );

		add_action( 'rest_api_init', array( $this, 'init_rest' ) );


	}

	public function init_rest() {

		register_rest_route( 'vestorfilter/v1', '/location/maps', array(
			'methods'             => 'GET',
			'callback'            => array( self::class, 'get_maps' ),
			'permission_callback' => '__return_true',
		) );

	}

	public static function get_location_query_endpoint() {

		return get_rest_url( null, 'vestorfilter/v1/location' );

	}

	private function maybe_install_db_table() {

		$maps_table_version = get_option( '_maps_table_version' );

		if ( empty( $maps_table_version ) ) {

			global $wpdb, $vfdb;

			$vfdb->query('ALTER TABLE `' . self::$table_name . "`
				CHANGE COLUMN `type`
					`type` ENUM('city','state','zip','county','school','neighborhood','custom')
					NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `ID`,
				CHANGE COLUMN `value`
					`value` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `type`,
				CHANGE COLUMN `slug`
					`slug` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `value`
				"
			);

			$vfdb->query('ALTER TABLE `' . self::$geo_name . "`
				ALTER TABLE `wp_locationcache_geo`
					CHANGE COLUMN `ID` `ID` BIGINT UNSIGNED NOT NULL DEFAULT AUTO_INCREMENT FIRST,
					CHANGE COLUMN `lat` `lat` INT NULL DEFAULT NULL AFTER `ID`,
					CHANGE COLUMN `lng` `lon` INT NULL DEFAULT NULL AFTER `lat`,
					CHANGE COLUMN `property_id` `property_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `lon`
					CHANGE COLUMN `property_type` `property_type` VARCHAR(10) NULL DEFAULT NULL AFTER `property_id`
					DROP COLUMN `location_id`
			");

			$charset = $wpdb->charset ?? 'utf8mb4';
			$collate = $wpdb->collate ?? 'utf8mb4_bin';

			$query = "

			CREATE TABLE IF NOT EXISTS `wp_locationcache_map` (
				`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(100) NOT NULL,
				`slug` varchar(100) DEFAULT NULL,
				`owner` bigint(20) unsigned DEFAULT NULL,
				`visibility` enum('public','private') NOT NULL DEFAULT 'private',
				`max_lat` int(11) NOT NULL DEFAULT 0,
				`max_lon` int(11) NOT NULL DEFAULT 0,
				`min_lat` int(11) NOT NULL DEFAULT 0,
				`min_lon` int(11) NOT NULL DEFAULT 0,
				`vectors` longtext CHARACTER SET {$charset} COLLATE {$collate} DEFAULT NULL CHECK (json_valid(`vectors`)),
				`location_id` bigint(20) unsigned DEFAULT NULL,
				`data` longtext CHARACTER SET {$charset} COLLATE {$collate} DEFAULT NULL CHECK (json_valid(`data`)),
				`state` bigint(20) unsigned DEFAULT NULL,
				`county` bigint(20) unsigned DEFAULT NULL,
				`city` bigint(20) unsigned DEFAULT NULL,
				PRIMARY KEY (`ID`)
			) ENGINE=InnoDB DEFAULT CHARSET={$charset};

			";

			$wpdb->query( $query );
			$vfdb->query( $query );

			update_option( '_maps_table_version', '1' );

		}

	}

	public static function get_all_data( $type = '', $sort = '', $duplicates = false ) {

		$data = wp_cache_get( "location_data-$type-$sort", 'vestorfilter' );

		if ( ! empty( $data ) ) {
			return $data;
		}

		global $vfdb;

		$query = 'SELECT * FROM ' . self::$table_name . ' WHERE `count` > 0 ';
		if ( $type ) {
			$query .= $vfdb->prepare( ' AND `type` = %s', $type );
		}

		if ( empty( $duplicates ) ) {
			$query .= ' AND `duplicate_of` IS NULL';
		}
		if ( $sort ) {
			$query .= ' ORDER BY ' . $sort;
		}

		$data = $vfdb->get_results( $query );


		foreach( $data as &$location ) {
			if ( $slug = self::get_slug( $location ) ) {
				$location->url = $slug;
			}
			$location = apply_filters( 'vestorfilter_location_data_item', $location, $type, $sort );
		}

		wp_cache_set( "location_data-$type-$sort", $data, 'vestorfilter' );

		return $data;

	}

	public static function add_location( $type, $value, $check_duplicates = true ) {

		global $vfdb;

		$value = trim( $value );

		$new_slug = substr( sanitize_title( $value ), 0, 50 );

		if ( $check_duplicates ) {

			$location_id = $vfdb->get_var( $vfdb->prepare(
				'SELECT `ID` FROM ' . self::$table_name . ' WHERE `type` = %s AND `slug` = %s AND `duplicate_of` IS NULL',
				$type,
				$new_slug
			) );

			if ( $location_id ) {
				return $location_id;
			}

		}

		if ( ! in_array( $type, self::$allowed_types ) ) {
			throw new \Exception( "New location type {$type} is not allowed to be created." );
		}

		$label = apply_filters( 'vestorfilter_new_location_label', $value, $type );
		$label = substr( $label, 0, 50 );

		if ( $vfdb->insert(
			self::$table_name,
			[
				'type'  => $type,
				'value' => $label,
				'slug'  => $new_slug,
			],[
				'%s',
				'%s',
				'%s',
			]
		) ) {
			return $vfdb->insert_id;
		} else {
			throw new \Exception( "There was an error adding location data for {$type} / {$value}" );
		}

		return false;

	}

	public static function get( $location_id ) {

		$data = wp_cache_get( 'location_data__' . $location_id, 'vestorfilter' );

		if ( ! empty( $data ) ) {
			return $data;
		}

		global $vfdb;
		$data = $vfdb->get_row( $vfdb->prepare(
			'SELECT * FROM ' . self::$table_name . ' WHERE `ID` = %s',
			$location_id
		) );

		wp_cache_set( 'location_data__' . $location_id, $data, 'vestorfilter' );

		return $data;

	}

	public static function get_for_property( $property_id, $type = 'all' ) {

		global $wpdb;

		$data = wp_cache_get( 'location_property__' . $property_id . '__' . $type, 'vestorfilter' );

		if ( ! empty( $data ) ) {
			return $data;
		}

		if ( $type !== 'all' ) {
			$join = 'INNER JOIN ' . self::$table_name . ' as loc ON (loc.ID = locpr.location_id)';
			$swlect = 'locpr.*,loc.value';
		} else {
			$join = '';
			$swlect = '*';
		}

		global $vfdb;
		$query = $vfdb->prepare(
			"SELECT $swlect FROM " . self::$index_name . " as locpr $join WHERE locpr.`property_id` = %s",
			$property_id
		);
		if ( $type !== 'all' ) {
			$query .= $wpdb->prepare(' AND loc.type = %s', $type);
		}

		$data = $vfdb->get_results( $query );

		wp_cache_set( 'location_property__' . $property_id . '__' . $type, $data, 'vestorfilter' );

		return $data;

	}

	public static function get_slug( $location ) {

		if ( is_numeric( $location ) ) {
			$location = self::get( $location );
		}
		if ( ! is_object( $location ) || empty( $location ) ) {
			return '';
		}

		if ( ! empty( $location->slug ) ) {
			$slug = $location->slug;
		} else {
			$slug = sanitize_title( $location->value );
		}
		if ( ! empty( $location->type ) ) {
			$slug = sanitize_title( $location->type ) . '/' . $slug;
		}

		return $slug;

	}

	public static function clear_index( $property_id ) {

		global $vfdb;

		$vfdb->query( $vfdb->prepare(
			'DELETE FROM ' . self::$index_name . ' WHERE `property_id` = %d',
			$property_id
		) );

	}

	public static function add_index( $location_id, $property_id, $check_duplicates = true, $update_count = true ) {

		global $vfdb;

		if ( $check_duplicates ) {

			$index_id = $vfdb->get_var( $vfdb->prepare(
				'SELECT `ID` FROM ' . self::$index_name . ' WHERE `location_id` = %d AND `property_id` = %d',
				$location_id,
				$property_id
			) );

			if ( $index_id ) {
				return $index_id;
			}

		}

		$success = $vfdb->replace(
			self::$index_name,
			[
				'location_id' => $location_id,
				'property_id' => $property_id,
			],
			[
				'%d',
				'%d',
			]
		);

		if ( $success !== false ) {
			if ( $update_count ) {
				self::update_index_count( $location_id );
			}
			return $vfdb->insert_id;
		} else {
			throw new \Exception( "There was an error indexing location for {$location_id} / {$property_id}" );
		}

		return false;

	}

	public static function update_index_count( $location_id ) {

		global $vfdb;


		$count = $vfdb->get_var( 
			$vfdb->prepare( "
				SELECT COUNT(*) FROM " . self::$index_name . " `index`
				INNER JOIN " . Cache::$prop_table_name . " `property` ON ( `property`.`ID` = `index`.`property_id` )
				WHERE `index`.`location_id` = %d
				AND `property`.`hidden` = 0",
				$location_id
			)
		);
		$vfdb->update( self::$table_name, [ 'count' => $count ], [ 'ID' => $location_id ] );

	}

	public static function find( $swarch, $type = null) {

		global $vfdb;

		$query = 'SELECT * FROM ' . self::$table_name . ' WHERE duplicate_of IS NULL AND `value` LIKE %s AND `count` > 0';
		if ( $type !== 'all' ) {
			if ( ! empty( $type ) && in_array( $type, self::$allowed_types ) ) {
				$query .= $vfdb->prepare( ' AND `type` = %s', $type );
			} else {
				$query .= ' AND `type` != "zip"';
			}
		}

		$query = $vfdb->prepare(
			$query,
			"%$swarch%",
		);

		return $vfdb->get_results( $query );

	}

	public function location_block( $atts = [], $content = '' ) {

		ob_start();

		$types = [ 'city', 'county', 'zip' ];

		$base_url = Property::base_url();
		$base = untrailingslashit( Settings::get_page_url( 'search' ) );

		?>

		<div class="locations">

			<?php foreach ( $types as $type ) : ?>

				<h3>Properties by <?php echo ucwords( $type ); ?></h3>
				<ul class="locations-list">

				<?php

				$locations = Location::get_all_data( $type, 'value ASC', false );

				foreach( $locations as $location ) {

					$url = $base . '/' . Location::get_slug( $location ) . '/?location=' . $location->ID . '&property-type=all&mode=list';

					printf( '<li><a href="%s">%s</a></li>', $url, $location->value );

				}

				?>

				</ul>

			<?php endforeach; ?>

		</div>

		<?php

		return ob_get_clean();

	}

	public static function find_address_coords( $address ) {

		$api_key = Settings::get( 'geocoding_api' );

		$url = 'https://maps.googleapis.com/maps/api/geocode/json';
		$url = add_query_arg( [
			'key' => $api_key,
			'address' => $address,
		], $url );
		
		$response = wp_remote_get( $url );

		if ( ! is_array( $response ) || is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( $response['body'], true );

		if ( ! $body || empty( $body['results'] ) ) {
			return null;
		}

		if ( ! empty( $body['results'][0]['partial_match'] ) ) {
			return null;
		}

		if ( ! empty( $body['results'][0]['geometry']['location'] ) ) {
			return [ $body['results'][0]['geometry']['location']['lat'], $body['results'][0]['geometry']['location']['lng'] ];
		}

		return null;
	
	}

	public static function get_geocoded_coords( $location_id ) {

		global $vfdb;

		$map = self::get_location_map( absint( $location_id ), false );

		if ( $map ) {
			$lat = self::geo_to_float( ( $map->max_lat + $map->min_lat ) / 2 );
			$lon = self::geo_to_float( ( $map->max_lon + $map->min_lon ) / 2 );
			return [ 
				'ne'     => [ self::geo_to_float( $map->max_lat ), self::geo_to_float( $map->max_lon ) ],
				'sw'     => [ self::geo_to_float( $map->min_lat ), self::geo_to_float( $map->min_lon ) ],
				'center' => [ $lat, $lon ],
			];
		}

		$location = Location::get( $location_id );
		if ( ! $location ) {
			return null;
		}
		if ( $location->type !== 'city' ) {
			return null;
		}

		list( $city, $state ) = explode( ',', $location->value );

		$api_key = Settings::get( 'geocoding_api' );

		$url = 'https://maps.googleapis.com/maps/api/geocode/json';
		$url = add_query_arg( [
			'key' => $api_key,
			'address' => $location->value,
		], $url );
		
		$response = wp_remote_get( $url );

		if ( ! is_array( $response ) || is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( $response['body'], true );

		if ( ! $body || empty( $body['results'] ) ) {
			return null;
		}

		$viewport = $body['results'][0]['geometry']['viewport'];
		$center = $body['results'][0]['geometry']['location'];

		$map_table = self::$map_name;

		$data = [
			'name'        => $location->value,
			'slug'        => sanitize_title( $location->value ),
			'visibility'  => 'public',
			'max_lat'     => (int) self::float_to_geo( $viewport[ 'northeast' ]['lat'] ),
			'min_lat'     => (int) self::float_to_geo( $viewport[ 'southwest' ]['lat'] ),
			'max_lon'     => (int) self::float_to_geo( $viewport[ 'northeast' ]['lng'] ),
			'min_lon'     => (int) self::float_to_geo( $viewport[ 'southwest' ]['lng'] ),
			'location_id' => $location->ID,
			'state'       => trim( $state ),
			'city'        => $location->value,
			'data'        => json_encode( $body['results'][0] ),
			'indexable'   => 0,
		];

		$vfdb->insert( $map_table, $data );

		return [
			'center' => [ $center['lat'], $center['lng'] ],
			'ne'     => [ $viewport['northeast']['lat'], $viewport['northeast']['lng'] ],
			'sw'     => [ $viewport['southwest']['lat'], $viewport['southwest']['lng'] ],
		];

	}

	public static function get_ne_bounds() {

		$swtting = Settings::get( 'map_bounds_ne' );
		if ( $swtting ) {
			return explode( ',', $swtting );
		}

		return defined( 'VF_MAP_NEBOUND' ) ? VF_MAP_NEBOUND : [ -90, 0 ];

	}

	public static function get_sw_bounds() {

		$swtting = Settings::get( 'map_bounds_sw' );
		if ( $swtting ) {
			return explode( ',', $swtting );
		}
		
		return defined( 'VF_MAP_SWBOUND' ) ? VF_MAP_SWBOUND : [ 0, -180 ];

	}

	public static function get_location_map( $location_id, $bounded_only = true ) {

		global $vfdb;

		$map_table = self::$map_name;


		// todo: only pulling public maps, check to see if user is allowed to view queried map
		$query = $vfdb->prepare( 
			"SELECT * FROM `$map_table` WHERE `location_id` = %d AND `owner` IS NULL",
			$location_id
		);
		if ( $bounded_only ) {
			$query .= ' AND `vectors` IS NOT NULL';
		}
		$map = $vfdb->get_row( $query );

		return $map;

	}

	public static function geo_to_float( $geo_coord ) {
		if ( abs( $geo_coord ) <= 180 ) {
			return $geo_coord;
		}
		return $geo_coord / 1000000;
	}

	public static function float_to_geo( $geo_coord ) {
		if ( abs( $geo_coord ) > 180 ) {
			return $geo_coord;
		}
		return $geo_coord * 1000000;
	}

	public static function add_map( $name, $properties, $map, $owner = null, $visibility = 'private' ) {

		global $vfdb, $wpdb;

		$data = [
			'name'    => substr( $name, 0, 100 ),
			'slug'    => substr( sanitize_title( $name ), 0, 100 ),
			'max_lat' => Location::float_to_geo( -90 ),
			'min_lat' => Location::float_to_geo( 90 ),
			'max_lon' => Location::float_to_geo( -180 ),
			'min_lon' => Location::float_to_geo( 180 ),
		];

		$location_name = [ $name ];

		if ( ! empty( $properties['County'] ) ) {
			$data['county'] = substr( $properties['County'], 0, 50 );
			//$county = self::find( $properties['County'], 'county' );
			//if ( $county ) {
			//	$data['county'] = $county[0]->duplicate_of ?: $county[0]->ID;
			//}
			$county_string = [ $properties['County'] ];
			if ( ! empty( $properties['State'] ) ) {
				$county_string[] = $properties['State'];
			}
			$county_string = implode( ', ', $county_string );
		}
		if ( ! empty( $properties['City'] ) ) {
			$city_string = [ $properties['City'] ];
			if ( ! empty( $properties['State'] ) ) {
				$city_string[] = $properties['State'];
			}
			$data['city'] = substr( implode( ', ', $city_string ), 0, 50 );
			//$city = self::find( implode( ', ', $city_string ), 'city' );
			//if ( $city || $city = self::find( $properties['City'], 'city' ) ) {
			//	$data['city'] = $city[0]->duplicate_of ?: $city[0]->ID;
			//}
			$location_name[] = $properties['City'];
		}
		if ( ! empty( $properties['State'] ) ) {
			$data['state'] = substr( $properties['State'], 0, 2 );
			//$state = self::find( $properties['State'], 'state' );
			//if ( $state ) {
			//	$data['state'] = $state[0]->duplicate_of ?: $state[0]->ID;
			//}
			$location_name[] = $properties['State'];
		}
		if ( isset( $properties['indexable'] ) ) {
			if ( $properties['indexable'] ) {
				$data['indexable'] = 1;
			}
			
			unset( $properties['indexable'] );
		} else {
			$data['indexable'] = '1';
		}

		$data['data'] = json_encode( $properties );

		$vectors = [];

		foreach( $map as $group_id => $group ) {
			$last_vect = [ 0, 0 ];
			$vectors[$group_id] = [];
			foreach( $group as $coord ) {
				if ( $coord[0] === $last_vect[0] && $coord[1] === $last_vect[1] ) {
					continue;
				}
				$vectors[$group_id][] = $coord;
				$last_vect = $coord;
				if ( $coord[0] > $data['max_lon'] ) {
					$data['max_lon'] = $coord[0];
				}
				if ( $coord[0] < $data['min_lon'] ) {
					$data['min_lon'] = $coord[0];
				}
				if ( $coord[1] > $data['max_lat'] ) {
					$data['max_lat'] = $coord[1];
				}
				if ( $coord[1] < $data['min_lat'] ) {
					$data['min_lat'] = $coord[1];
				}
			}
		}
		foreach( [ 'min_lat', 'max_lat', 'min_lon', 'max_lon' ] as $key ) {
			$data[$key] = (int) ( $data[$key] * 1000000 );
		}
		$data['vectors'] = json_encode( $vectors );

		$map_table = self::$map_name;

		if ( ! empty( $owner ) ) {
			$data['owner']      = absint( $owner );
			$data['visibility'] = ( $visibility === 'public' ) ? 'public' : 'private';

			$wpdb->insert( $map_table, $data );
		} else {
			if ( isset( $properties['Name'] ) ) {
				$data['location_id'] = self::add_location( 'neighborhood', implode( ', ', $location_name ), true );
			} elseif ( isset( $properties['City'] ) ) {
				$city = self::find( $data['city'], 'city' );
				if ( $city ) {
					$location = current( $city );
				} else {
					$data['location_id'] = self::add_location( 'city', $data['city'], true );
				}
			} elseif ( isset( $properties['County'] ) ) {
				$county = self::find( $county_string, 'county' );
				if ( $county ) {
					$location = current( $county );
				} else {
					$data['location_id'] = self::add_location( 'county', $county_string, true );
				}
			}
			if ( ! empty( $location ) ) {
				$data['location_id'] = $location->duplicate_of ?: $location->ID;
			}

			$data['visibility'] = 'public';

			if ( ! empty( $data['location_id'] ) ) {
				$existing = $vfdb->get_var( $vfdb->prepare( "SELECT ID FROM $map_table WHERE location_id = %d", $data['location_id'] ) );
			}
			if ( ! empty( $existing ) ) {
				$vfdb->update( $map_table, $data, [ 'ID' => $existing ] );
			} else {
				$vfdb->insert( $map_table, $data );
			}
		}


	}

	public static function update_property_geo( $property_id, $latitude, $longitude, $type = null ) {

		global $vfdb;

		$found = $vfdb->get_var( $vfdb->prepare(
			'SELECT `ID` FROM ' . self::$geo_name . ' WHERE `property_id` = %d',
			$property_id
		) );

		$intlat = (int) ( $latitude * 1000000 );
		$intlon = (int) ( $longitude * 1000000 );

		if ( $found ) {
			$vfdb->update( self::$geo_name, [ 'lat' => $intlat, 'lon' => $intlon, 'property_type' => $type ], [ 'ID' => $found ] );
			return $found;
		} else {
			$vfdb->insert( self::$geo_name, [ 'lat' => $intlat, 'lon' => $intlon, 'property_id' => $property_id, 'property_type' => $type ] );
			return $vfdb->insert_id;
		}

	}

	public static function find_maps_nearby( $params, $indexed_only = false ) {

		global $wpdb, $vfdb;

		$map_table = self::$map_name;
		$query = "SELECT * FROM `$map_table` WHERE `owner` IS NULL";
		if ( ! empty( $params['state'] ) ) {
			$query .= $wpdb->prepare( ' AND `state` = %s', $params['state'] );
		}
		if ( ! empty( $params['city'] ) ) {
			$query .= $wpdb->prepare( ' AND `city` = %s', $params['city'] );
		}
		if ( ! empty( $params['county'] ) ) {
			$query .= $wpdb->prepare( ' AND `county` = %s', $params['county'] );
		}
		if ( ! empty( $params['lat'] ) ) {
			$query .= $wpdb->prepare( ' AND `max_lat` >= %d AND `min_lat` <= %d', $params['lat'], $params['lat'] );
		}
		if ( ! empty( $params['lon'] ) ) {
			$query .= $wpdb->prepare( ' AND `max_lon` >= %d AND `min_lon` <= %d', $params['lon'], $params['lon'] );
		}
		if ( $indexed_only ) {
			$query .= " AND `indexable` = 1";
		}
		return $vfdb->get_results( $query );

	}

	public static function find_maps_in_rect( $ne, $sw, $indexed_only = false ) {

		global $vfdb;

		$cache_string = 'mapsearch_' . implode( '-', $ne ?: [] ) . '_' . implode( '-', $sw ?: [] );
		$cache_string .= is_string( $indexed_only ) ? "_{$indexed_only}" : ( $indexed_only ? '_indexed' : '' );
		$results = wp_cache_get( $cache_string, 'vestorfilter' );
		if ( $results !== false ) {
			return $results;
		}
		$map_table = self::$map_name;
		$loc_table = self::$table_name;

		$query = "SELECT `$map_table`.*, lt.type as `type`, lt.value as `location_name` FROM `$map_table` ";
		$query .= "INNER JOIN `$loc_table` lt ON (`$map_table`.location_id = lt.ID) ";

		$query .= 'WHERE `owner` IS NULL AND `location_id` IS NOT NULL ';
		if ( $indexed_only === true ) {
			$query .= " AND `indexable` = 1";
		} elseif ( is_string( $indexed_only ) ) {
			$query .= $vfdb->prepare( ' AND lt.type = %s ', $indexed_only );
		}
		if ( Settings::get( 'map_states' ) || defined( 'VF_MAP_STATES' ) ) {
			$states = Settings::get( 'map_states' ) ?: VF_MAP_STATES;
			if ( ! is_array( $states ) ) {
				$states = explode( ',', $states );
			}
			$query .= " AND `state` IN ('" . implode( "','", $states ) . "')";
		}

		if ( $ne ) {
			$minlat = $ne['lat'] < $sw['lat'] ? $ne['lat'] : $sw['lat'];
			$maxlat = $ne['lat'] > $sw['lat'] ? $ne['lat'] : $sw['lat'];
			$query .= $vfdb->prepare( ' AND ( ( `max_lat` BETWEEN %1$d AND %2$d ) OR ( `min_lat` BETWEEN %1$d AND %2$d ) )', $minlat, $maxlat );
		}
		if ( $sw ) {
			$minlon = $ne['lon'] < $sw['lon'] ? $ne['lon'] : $sw['lon'];
			$maxlon = $ne['lon'] > $sw['lon'] ? $ne['lon'] : $sw['lon'];
			$query .= $vfdb->prepare( ' AND ( ( `max_lon` BETWEEN %1$d AND %2$d ) OR ( `min_lon` BETWEEN %1$d AND %2$d ) )', $minlon, $maxlon );
		}

		$query .= ' GROUP BY `location_id`';

		$results = $vfdb->get_results( $query );
		wp_cache_set( $cache_string, $results, 'vestorfilter' );

		return $results;

	}

	public static function get_maps( $request ) {

		$type = $request->get_param( 'type' );
		if ( ! $type || ! in_array( $type, [ 'neighborhood', 'county' ] ) ) {
			return WP_Error( 'bad_params', 'Invalid map type sent.', [ 'code' => '403' ] );
		}

		$coords = $request->get_param( 'coords' );
		$coords = json_decode( $coords, true );
		if ( ! $coords ) {
			return WP_Error( 'bad_params', 'Invalid coordinate values sent.', [ 'code' => '403' ] );
		}
		if ( empty( $coords ) || ! is_array( $coords ) || count( $coords ) !== 2 ) {
			return WP_Error( 'incomplete_params', 'Incomplete coordinate values sent.', [ 'code' => '403' ] );
		}
		if ( ! is_array( $coords[0] ) || ! is_array( $coords[1] ) ) {
			return WP_Error( 'bad_params', 'Bad coordinate values sent.', [ 'code' => '403' ] );
		}
		foreach( $coords as $index => $coord ) {
			foreach( ['lat','lon'] as $key ) {
				if ( ! isset( $coord[$key] ) ) {
					return WP_Error( 'bad_params', 'Bad coordinate values sent.', [ 'code' => '403' ] );
				}
				$coords[$index][$key] = self::float_to_geo( (float) $coord[$key] );
			}
		}
		$area_maps = self::find_maps_in_rect( $coords[0], $coords[1], $type );

		
		$maps = [];
		foreach( $area_maps as $map ) {
			$maps[] = [
				'id' => $map->ID,
				'name' => $map->name,
				'location' => $map->location_id,
				'location_name' => $map->location_name,
				'type' => $map->type,
				'vectors' => json_decode( $map->vectors ),
			];
		}

		return $maps;

	}

	public static function is_geocoord_in_map( $geo, $map_boundaries ) {

		if ( ! is_array( $map_boundaries ) ) {
			return false;
		}

		$lat = Location::geo_to_float( $geo->lat );
		$lon = Location::geo_to_float( $geo->lon );

		foreach( $map_boundaries as $bounds ) {
			$count = 0;
			$bounds_count = count($bounds);
			for ( $b = 0; $b < $bounds_count; $b++ ) {
				$vertex1 = $bounds[$b];
				$vertex2 = $bounds[($b + 1) % $bounds_count];
				if ( self::ray_test( $vertex1, $vertex2, $lon, $lat) ) {
					$count ++;
				}
			}

			if ( $count % 2 ) {
				return true;
			}
		}

		return false;

	}

	private static function ray_test($A, $B, $x, $y) {
		if ($A[1] <= $B[1]) {
			if ($y <= $A[1] || $y > $B[1] ||
				$x >= $A[0] && $x >= $B[0]) {
				return false;
			}
			if ($x < $A[0] && $x < $B[0]) {
				return true;
			}
			if ($x == $A[0]) {
				if ($y == $A[1]) {
					$result1 = NAN;
				} else {
					$result1 = INF;
				}
			} else {
				$result1 = ($y - $A[1]) / ($x - $A[0]);
			}
			if ($B[0] == $A[0]) {
				if ($B[1] == $A[1]) {
					$result2 = NAN;
				} else {
					$result2 = INF;
				}
			} else {
				$result2 = ($B[1] - $A[1]) / ($B[0] - $A[0]);
			}
			return $result1 > $result2;
		}
		return self::ray_test($B, $A, $x, $y);
	}

	public static function get_user_maps( $user_id = null ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return [];
		}

		$maps = get_user_meta( $user_id, 'custom_map' ) ?: [];
		
		return $maps;

	}

	public static function get_custom_map( $user_id, $map_id ) {

		$maps = self::get_user_maps( $user_id ) ?: [];
		if ( empty( $maps ) ) {
			return false;
		}
		foreach( $maps as $map ) {
			if ( $map_id === $map['id'] ) {
				return $map;
			}
		}
		
		return false;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Location', 'init' ) );
