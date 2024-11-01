<?php

namespace VestorFilter;

use \Aws\S3\S3Client;
use \Aws\Exception\AwsException;
use \Aws\Credentials\CredentialProvider;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Source {

	public $rets;

	private $meta;

	private $id;

	private $classes;

	private $reso_map = [], $meta_map = [], $multipart_fields = [];

	public $obj;

	private $reso, $reso_token, $reso_url;

	private static $reso_media, $reso_id;

	public function __construct( $source_id ) {

		if ( WP_CACHE ) {
			$meta = wp_cache_get( 'source_meta__' . $source_id, 'vestorfilter' );
		}
		if (  empty( $meta ) ) {
			$meta = self::get_metadata( $source_id );
			wp_cache_set( 'source_meta__' . $source_id, $meta, 'vestorfilter' );
		}

		$this->meta = $meta;
		$this->id = $source_id;

		$this->obj = self::get_object( $source_id );

		$this->build_reso_map();


	}

	public static function get_object( $source_id ) {

		global $vfdb;

		if ( WP_CACHE ) {
			$obj = wp_cache_get( 'source_obj__' . $source_id, 'vestorfilter' );
		}
		if (  empty( $obj ) ) {
			$obj = $vfdb->get_row( $vfdb->prepare(
				'SELECT * FROM ' . Cache::$source_table_name . ' WHERE post_id = %d',
				$source_id,
			) );
			wp_cache_set( 'source_obj__' . $source_id, $obj, 'vestorfilter' );
		}

		return $obj;

	}

	public function slug() {
		return $this->obj->slug;
	}

	public function ID() {
		return $this->id;
	}

	public function get_classes() {
		return $this->classes;
	}

	public function update_modified( $time = null ) {

		global $vfdb;

		if ( empty( $time ) ) {
			$time = time();
		}

		$vfdb->update(
			Cache::$source_table_name,
			[ 'last_updated' => $time ],
			[ 'ID' => $this->obj->ID ],
			[ '%d' ],
			[ '%d' ]
		);

	}

	public function photo_table_name() {

		return Cache::$photo_table_name . '_' . $this->obj->slug;

	}

	public function meta_table_name() {

		return Cache::$meta_table_name . '_' . $this->obj->slug;

	}

	public static function get_metadata( $source_id ) {

		global $vfdb;

		$query = $vfdb->prepare( "SELECT * FROM {$vfdb->prefix}postmeta WHERE post_id = %d", $source_id );
		$rows  = $vfdb->get_results( $query );

		$data = [];
		foreach ( $rows as $row ) {
			if ( empty( $data[ $row->meta_key ] ) ) {
				$data[ $row->meta_key ] = array();
			}
			$data[ $row->meta_key ][] = maybe_unserialize( $row->meta_value );
		}

		return $data;

	}

	public function get_meta( $key, $default = null ) {

		if ( isset( $this->meta[ '_datasource_' . $key ] ) ) {
			return $this->meta[ '_datasource_' . $key ][0];
		}

		return $default;

	}

	private function build_reso_map() {

	    $data = Property::get_available_fields();
        foreach( $data as $key => $info ) {
            if ( empty( $info['key'] ) ) {
                continue;
            }
            $value = $this->get_field_map( $key, $info['key'] );
            if ( empty( $value ) ) {
                continue;
            }
    
            $fields = explode( ',', $value );
            foreach( $fields as $field ) {
                $field = trim( $field );
                if ( empty( $this->reso_map[ $field ] ) ) {
                    $this->reso_map[ $field ] = array();
                }
                $this->reso_map[ $field ][] = $info['key'];
    
                $field_string = explode( ' ', $field );
    
                if ( count( $field_string ) === 1 ) {
                    if ( empty( $this->meta_map[ $field ] ) ) {
                        $this->meta_map[ $field ] = array();
                    }
                    $this->meta_map[ $field ][] = $key;
                } else {
                    $this->multipart_fields[ $key ] = $field_string;
                }
            }
        }
    }

	public function get_meta_map() {
		return $this->meta_map;
	}

	public function convert_field( $key ) {

		return $this->reso_map[ $key ] ?? [ $key ];

	}

	public function get_field_map( $key, $default = null ) {
		return $this->get_meta( 'field_' . $key, $default );
	}

	public function filter_incoming_data( $data ) {

		$new_data = apply_filters( 'vestorfilter_data_before_conversion', $data, $this );

		foreach( $new_data as $key => $value ) {
			if ( ! empty( $value ) ) {
				$keys = $this->convert_field( $key );
				foreach( $keys as $field ) {
					$new_data[ $field ] = $value;
				}
			}
		}

		$new_data = apply_filters( 'vestorfilter_data_after_conversion', $new_data, $this );

		return $new_data;

	}

	public function convert_field_to_meta( $key ) {

		return $this->meta_map[ $key ] ?? null;

	}

	public function convert_incoming_data( $data ) {

		$data = apply_filters( 'vestorfilter_data_before_conversion', $data, $this );

		$new_data = [];
		foreach( $data as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$keys = $this->convert_field_to_meta( $key );
			if ( empty( $keys ) ) {
				continue;
			}
			foreach( $keys as $field ) {
				$new_data[ $field ] = $value;
			}
		}
		foreach( $this->multipart_fields as $field => $parts ) {
			$strung = [];
			foreach( $parts as $partkey ) {
				if ( ! empty( $data[ $partkey ] ) ) {
					$strung[] = $data[ $partkey ];
				}
			}
			$new_data[ $field ] = implode( ' ', $strung );
		}

		return $new_data;

	}

	public function get_compliance_logo( $size = 'tiny' ) {

		$logo_id = $this->get_meta( 'compliance_logo' );
		if ( empty( $logo_id ) ) {
			return '';
		}

		if ( WP_CACHE ) {
			$img = wp_cache_get( 'source_logo_img--' . $logo_id, 'vestorfilter' );
		}
		if ( ! empty( $img ) ) {
			return $img;
		}

		global $wpdb, $vfdb;

		$core_url = $vfdb->get_var( "SELECT `option_value` FROM $vfdb->options WHERE `option_name` = 'siteurl'" );

		$olddb = $wpdb;
		$wpdb = $vfdb;
		$image = wp_get_attachment_image( $logo_id, $size );
		$wpdb = $olddb;

		$this_url = get_bloginfo( 'url' );

		$image = str_replace( $this_url, $core_url, $image );

		wp_cache_set( 'source_logo_img--' . $logo_id, $image, 'vestorfilter' );

		return $image;

	}

	public function get_compliance_line( $office = '' ) {

		$text = $this->get_meta( 'compliance_line', '' );

		if ( empty( $office ) && strpos( $text, '{{NAME}}' ) !== false ) {
			return '';
		}

		return str_replace( '{{NAME}}', $office, $text );

	}

	public function get_compliance_line_under_photo( $office = '' ) {

		if ( empty( $office ) ) {
			return '';
		}

		$text = $this->get_meta( 'compliance_line_under_photo', '' );

		return str_replace( '{{NAME}}', $office, $text );

	}

	public function get_compliance_text( $date = '' ) {

		if ( empty( $date ) ) {
			return '';
		}

		$text = $this->get_meta( 'compliance_fulltext', '' );

		$text = str_replace( '{{DATE}}', $date, $text );

		return $text;

	}

	public function connect_reso( $url, $token ) {

		require_once Plugin::$plugin_path . '/vendor/autoload.php';

		$this->reso_url = trailingslashit( $url );
		$this->reso_token = $token;
		$this->reso_query = empty( $this->meta[ '_datasource_api_query' ] ) ? '' : $this->meta[ '_datasource_api_query' ][0];
		$this->classes    = [ 'WebAPI' ];

		/*\RESO\RESO::setAPIRequestUrl( $url );
		\RESO\RESO::setAccessToken( $token );
		\RESO\Request::setAcceptType( "json" );*/

		return true;

	}

	public function is_reso_api() {

		return ! empty( $this->reso_url );
		
	}

	public function get_reso_deleted( $timestamp = null, $limit = null, $offset = null, $select = null ) {

		//$url = $this->reso_url . 'Property';
		$url = empty( $this->meta[ '_datasource_api_query_deleted' ] ) ? null : $this->meta[ '_datasource_api_query_deleted' ][0];

		if ( empty( $url ) ) {
			return false;
		}

		$url = str_replace( '#time#', $timestamp ?: '2000-01-01T00:00:00.000Z', $url );

		if ( ! is_null( $limit ) ) {
			$url = add_query_arg( '$top', $limit, $url );
		}
		if ( ! is_null( $offset ) ) {
			$url = add_query_arg( '$skip', $offset, $url );
		}
		
		$url = add_query_arg( '$count', 'true', $url );
		if ( ! is_null( $select ) ) {
			$url = add_query_arg( '$select', $select, $url );
		}

		$fetch = wp_safe_remote_get( $url, [
			'timeout' => 30,
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->reso_token,
			]
		] );

		if ( is_wp_error( $fetch ) ) {
			throw new \Exception( $fetch->get_error_message() );
		}

		if ( $fetch && $fetch['response'] && $fetch['response']['code'] == 200 ) {

			$fetch = json_decode( $fetch['body'], true );
			//if ( $fetch ) {
			//	$fetch = $fetch['value'];
			//}
		} else {
			return false;
		}

		return $fetch;

	}

	public function reso_api_query( $object, $filter = null, $limit = null, $offset = null, $do_expand = true ) {

		$media_type = $this->get_data_meta( 'property_photo_resource', 'Photo' );
		$mf_type    = $this->get_data_meta( 'mf_class', '' );

		$url = $this->reso_url . $object;
        $filters = ! empty( $this->reso_query ) ? [ $this->reso_query ] : [];
        if ( $filter ) {
            $filters = array_merge( $filters, $filter );
        }
        $url = add_query_arg( '$filter', implode( ' and ', $filters ), $url );
        
        if ( is_string( $do_expand ) ) {
            $select = $do_expand;
            $do_expand = false;
        }
        
        if ( ! is_null( $limit ) ) {
            $url = add_query_arg( '$top', $limit, $url );
        }
        if ( ! is_null( $offset ) ) {
            $url = add_query_arg( '$skip', $offset, $url );
        }
        if ( $do_expand ) {
            $expand = [ $media_type ];
            if ( $mf_type ) {
                $expand[] = $mf_type;
            }
            $url = add_query_arg( '$expand', implode( ',', $expand ), $url );
        }
        if ( ! empty( $select ) ) {
            $url = add_query_arg( '$select', $select, $url );
        }
        
        $url = add_query_arg( '$count', 'true', $url );
        $fetch = wp_safe_remote_get( $url, [
			'timeout' => 30,
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->reso_token,
			]
		] );

		if ( is_wp_error( $fetch ) ) {
			echo "\r\nError with $url\r\n";
			throw new \Exception( $fetch->get_error_message() );
		}


		if ( $fetch && $fetch['response'] && $fetch['response']['code'] == 200 ) {

			$fetch = json_decode( $fetch['body'], true );
			//if ( $fetch ) {
			//	$fetch = $fetch['value'];
			//}
		} else {
			return false;
		}

		return $fetch;

	}

	public function connect() {

		if ( ! empty( $this->meta[ '_datasource_api_url' ] ) && ! empty( $this->meta[ '_datasource_api_token' ] ) ) {
			return $this->connect_reso( $this->meta['_datasource_api_url'][0], $this->meta['_datasource_api_token'][0] );
		}

		if ( empty( $this->meta[ '_datasource_url' ] ) ) {
			return false;
		}

		require_once Plugin::$plugin_path . '/vendor/autoload.php';

		$source_url  = $this->meta[ '_datasource_url' ][0];
		$source_user = $this->meta[ '_datasource_username' ][0];
		$source_pass = $this->meta[ '_datasource_password' ][0];
		$source_ua   = $this->meta[ '_datasource_useragent_postfix' ][0];
		echo $source_url;
		echo "user". $source_user;
		echo "pass". $source_pass;
		echo "ua". $source_ua;

		$this->classes = $this->meta[ '_datasource_classes' ] ?? null;

		$config = new \PHRETS\Configuration;
		$config->setLoginUrl( $source_url )
				->setUsername( $source_user )
				->setPassword( $source_pass )
				->setUserAgent( "VestorFilter/1.0 ($source_ua)" );

		if ( ! empty( $this->meta[ '_datasource_version'] ) ) {
			$config->setRetsVersion( $this->meta[ '_datasource_version'][0] );
		}

		$this->rets = new \PHRETS\Session( $config );

		$connect = $this->rets->Login();

		return $this->rets;

	}

	public function api_search( $last_modified ) {

		$primary_key  = $this->get_data_meta( 'alt_listing_id', $this->get_data_meta( 'primary_id', 'ListingID' ) );
		$modified_key = $this->get_data_meta( 'property_datemodified', 'DateTimeModified' );

		if ( empty( $this->reso_url ) ) {

			return $this->rets->Search( 'Property', $class, "($modified_key=$last_modified+)", [ 'Select' => $primary_key ] );

		} else {
			$query = [ $this->reso_query ];
			$query[] = "$modified_key gt $last_modified";

			$results = $this->reso_api_query( 'Property', $query );
			return $results['value'];

		}

	}

	public function get_all_properties() {

		if ( empty( $this->classes ) ) {
			throw new \Exception( 'No property classes were defined on the feed source' );
		}

		$primary_key  = $this->get_data_meta( 'alt_listing_id', $this->get_data_meta( 'primary_id', 'ListingID' ) );

		$last_modified = date( 'c', 0 );
		$last_modified = substr( $last_modified, 0, strpos( $last_modified, '+' ) );

		$ids = array();
		$index = array();
		foreach ( $this->classes as $class) {
			$class = trim( $class );
			$results = $this->api_search( $last_modified );
			foreach( $results as $result ) {
				$id = is_array( $result ) ? $result[ $primary_key ] : $result->get( $primary_key );
				if ( empty( $ids[ $id ] ) ) {
					$ids[ $id ] = array();
				}
				array_push( $ids[ $id ], $class );
			}
		}

		return $ids;

	}

	/**
	 * DEPRECATED.
	 *
	 */
	public function get_all_property_data( $class, $get_active = true, $page = 0 ) {


		if ( empty( $this->classes ) ) {
			throw new \Exception( 'No property classes were defined on the feed source' );
		}

		$primary_key  = $this->get_data_meta( 'alt_listing_id', $this->get_data_meta( 'primary_id', 'ListingID' ) );


		$modified_key = $this->get_data_meta( 'property_datemodified', 'DateTimeModified' );
		$status_key   = $this->get_data_meta( 'status', 'ListingStatus' );
		$status_val   = $this->get_data_meta( 'status_active', 'ACT' );

		$last_modified = date( 'c', strtotime( '-1 year' ) );
		$last_modified = substr( $last_modified, 0, strpos( $last_modified, '+' ) );

		if ( empty( $this->reso_url ) ) {

			if ( $get_active ) {
				$query = "($status_key=|$status_val)";
			} else {
				$query = "($modified_key=$last_modified+),~($status_key=|$status_val)";
			}

			$results = $this->rets->Search( 'Property', $class, $query, [ 'Limit' => 1000, 'Offset' => $page * 1000 ] );

		} else {

			$status_inactive = $this->get_data_meta( 'query_inactive' );

			$query = [ $this->reso_query ];
			if ( $get_active ) {
				$query[] = "$status_key eq $status_val";
			} else {
				$query[] = "$modified_key gt {$last_modified}Z";
				//$query[] = "$status_key ne $status_val";
				if ( ! empty( $status_inactive ) ) {
					$query[] = '(' . $status_inactive . ')';
				}
			}

			$results = $this->reso_api_query( 'Property', $query, 1000, $page * 1000 );

		}


		return $results;

	}

	public function get_missing_openhouses() {

		global $vfdb;

		$query = 'SELECT pr.ID AS id, pr.listing_id AS listing_id '
			   . 'FROM ' . Cache::$prop_table_name . ' AS pr '
			   . 'LEFT JOIN ' . Cache::$meta_table_name . ' AS pm ON ( pr.ID = pm.property_id AND pm.key = "open_houses" ) '
			   . "WHERE pm.value IS NULL AND pr.post_id = {$this->id}"
			   . "GROUP BY pr.ID, pm.value "
			   . "ORDER BY pr.ID DESC";

		$records = $vfdb->get_results( $query );
		$return = [];
		foreach( $records as $row ) {
			$return[ $row->id ] = $row->listing_id;
		}

		return $return;

	}

	public function get_broken_properties() {

		global $vfdb;

		$query = 'SELECT pr.ID id, pr.listing_id listing_id, COUNT(ph.ID) AS photos '
			   . 'FROM ' . Cache::$prop_table_name . ' pr '
			   . 'LEFT JOIN ' . $this->photo_table_name() . ' ph ON ( pr.ID = ph.property_id ) '
			   . "WHERE pr.post_id = {$this->id} AND pr.post_id = {$this->id} "
			   . "GROUP BY pr.ID "
			   . "HAVING photos = 0";

		$properties_aggr = $vfdb->get_results( $query );
		if ( $properties_aggr === false ) {
			throw new Exception( $vfdb->last_error );
		}

		return $properties_aggr;

	}

	public function get_broken_images() {

		global $vfdb;

		$query = 'SELECT * '
		. 'FROM ' . $this->photo_table_name() . ' ph '
		. 'WHERE ph.tiny IS NOT NULL';

		$images = $vfdb->get_results( $query );
		$path = wp_upload_dir( 'vf' );

		$broken = array();
		foreach( $images as $image ) {
			if ( ! file_exists( $path['path'] . '/' . $image->tiny ) ) {
				$broken[] = $image->property_id;
			}
		}

		return $broken;

	}

	public function get_property( $listing_id, $class, $test = false ) {

		$primary_key  = $this->get_data_meta( 'alt_listing_id', $this->get_data_meta( 'primary_id', 'ListingID' ) );

		$params = [ 'Limit' => 1 ];
		if ( $test ) {
			$params['Select'] = $primary_key;
		}

		$search = $this->rets->Search( 'Property', $class, "({$primary_key}={$listing_id})", $params );

		$result = $search->first();
		if ( empty( $result ) ) {
			return false;
			//throw new \Exception( "Property MLS ID  $listing_id not found" );
		}

		return $result;

	}

	public function pull_property( $listing_id, $classes = null, $property = null ) {

		//echo "pull property:\nline:".__LINE__."\n";print_r($property);exit;
	    $mls_key         = $this->get_data_meta( 'primary_id', 'ListingID' );
		$primary_key     = $this->get_data_meta( 'alt_listing_id', '' );
		if ( empty( $primary_key ) ) {
			$primary_key = $mls_key;
		}
		//$photos_modified = $this->get_data_meta( 'photomodified', 'PhotoDateTimeModified' );
		//$modified_key    = $this->get_data_meta( 'property_datemodified', 'DateTimeModified' );

		$media_type      = $this->get_data_meta( 'property_photo_resource', 'Photo' );

		if ( empty( $classes ) ) {
			$classes = $this->classes;
		}

		if ( empty( $classes ) ) {
			throw new \Exception( "No classes defined for property $listing_id" );
		}


		if ( ! empty( $property ) && is_object( $property ) ) {
			$property_id = $property->ID();
		} elseif ( ! empty( $property ) && is_array( $property ) ) {
			$property_id = $property[0]->ID;
		} elseif ( ! empty( $property ) && is_numeric( $property ) ) {
			$property_id = $property;
		}

		if ( ! empty( $property_id ) ) {
			$this->clean_meta( $property_id );
		}

		$all_data = [];

		foreach( $classes as $class ) {

			self::$reso_media = null;
			self::$reso_id = null;

			if ( $this->is_reso_api() ) {

				$last_call = absint( get_option( 'last_reso_query' ) ?: 0 );
				if ( time() < $last_call + 1 ) {
					time_sleep_until( $last_call + 1 );
				}

				$data = $this->reso_api_query( 'Property', ["ListingId eq '{$listing_id}'"], 1, null, true );
				update_option( 'last_reso_query', time(), true );

				if ( empty( $data['value'][0] ) ) {
					continue;
				}
				$data = $data['value'][0];

				if ( isset( $data[ $media_type ] ) ) {
					self::$reso_media = $data[ $media_type ];
					self::$reso_id = $listing_id;
					unset( $data[ $media_type ] );
				}

			} else {
				$search = $this->rets->Search( 'Property', $class, "({$primary_key}={$listing_id})", [ 'Limit' => 1 ] );
				$result = $search->first();
				if ( empty( $result ) ) {
					continue;
				}
				$data  = $result->toArray();
			}

			$all_data = array_merge( $all_data, $data );
			$mlsid = $data[ $mls_key ] ?? $listing_id;
			$listing_id = $data[ $primary_key ];

			$data = $this->convert_incoming_data( $data );

			$property_updated = $data[ 'property_datemodified' ] ?? '';

			if ( empty( $property ) ) {

				$slug = Property::make_slug( $data, $this );
				$property_id = Cache::create_property( $this->id, $mlsid, $listing_id, $slug, $property_updated );

			} else {

				$property_id = $property->ID();
				$args = [ 'modified' => strtotime( $property_updated ) ?: time() ];
				if ( empty( $property->slug() ) ) {
					$args['slug'] = Property::make_slug( $data, $this );
				}
				Cache::update_property( $property_id, $args );
			}

			try {

				Cache::add_index( $property_id, 'Class', $class, true );

				foreach( $data as $key => $value ) {
					if ( ! empty( $value ) ) {
						if ( is_array( $value ) ) {
							$this->add_meta( $property_id, $key, maybe_serialize( $value ) );
						} else {
							$this->add_meta( $property_id, $key, $value );
						}
					}
				}

			} catch ( Exception $e ) {

				$this->update_meta( $property_id, '__last_error', $e->getMessage() );
				throw $e;

			}

		}

		if ( ! empty( $all_data ) ) {
			foreach( $all_data as $key => $value ) {
				if ( empty( $value ) ) {
					unset( $all_data[ $key ] );
				}
			}
			Cache::update_text( $property_id, 'meta', json_encode( $all_data ) );
		}
		

		return $property_id ?? false;

	}

	/**
	 * DEPRECATED: use pull_property
	 */
	public function download_property( $listing_id, $categories = null, $download_photos = true, $skip_existing = false ) {

		$mls_key      = $this->get_data_meta( 'primary_id', 'ListingID' );
		$primary_key  = $this->get_data_meta( 'alt_listing_id', '' );
		if ( empty( $primary_key ) ) {
			$primary_key = $mls_key;
		}
		$photos_modified = $this->get_data_meta( 'photomodified', 'PhotoDateTimeModified' );
		//$photos_count    = $this->get_data_meta( 'photocount', 'PhotosCount' );
		$modified_key    = $this->get_data_meta( 'property_datemodified', 'DateTimeModified' );

		$unit_class   = $this->get_data_meta( 'mf_class', '' );

		$property = Cache::get_property_by( 'listing_id', $listing_id, $this->id );
		if ( ! empty( $property ) && $skip_existing ) {
			return false;
		}
		if ( empty( $categories ) ) {
			$categories = $this->classes;
		}

		if ( empty( $categories ) ) {
			throw new \Exception( 'No category defined for property' );
		}

		if ( ! empty( $property ) ) {
			$this->clean_meta( $property[0]->ID );
		}

		foreach( $categories as $category ) {

			$search = $this->rets->Search( 'Property', $category, "({$primary_key}={$listing_id})", [ 'Limit' => 1 ] );

			$result = $search->first();
			if ( empty( $result ) ) {
				continue;
			}

			$data  = $result->toArray();
			$mlsid = $data[ $mls_key ] ?? $listing_id;

			$fields = $this->filter_incoming_data( $data );

			if ( empty( $property ) ) {

				$slug = Property::make_slug( $fields, $this );
				$property_id = Cache::create_property( $this->id, $mlsid, $listing_id, $slug );
				if ( ! empty( $download_photos ) ) {
					$photosupdated = 0;
					//$photoscount = -1;
					//$photos = [];
				}

			} else {

				$property_id = $property[0]->ID;

				if ( ! empty( $download_photos ) ) {

					$photosupdated = $this->get_meta_value( $property_id, 'PhotoDateTimeModified' );

					if ( empty( $photosupdated ) ) {
						$photosupdated = 0;
					} else {
						$photosupdated = strtotime( $photosupdated );
					}
				}
				if ( empty( $property[0]->slug ) ) {
					Cache::update_property( $property_id, [ 'slug' => Property::make_slug( $fields, $this ) ] );
				}
				$property_updated = $result->get( $modified_key );
				Cache::update_property( $property_id, [ 'modified' => strtotime( $property_updated ) ] );
			}

			//if ( ! empty( $unit_class) && $unit_class == $category ) {
			//	$this->download_unitdata( $property_id, $listing_id );
			//}

			try {

				Cache::add_index( $property_id, 'Class', $category, true );

				foreach( $fields as $key => $value ) {
					if ( ! empty( $value ) ) {
						$this->add_meta( $property_id, $key, $value );
					}
				}

				if ( empty( $fields[ $photos_modified ] ) ) {
					$lastupdated = time();
				} else {
					$lastupdated = strtotime( $fields[ $photos_modified ] );
				}

				if ( ! empty( $download_photos ) ) {
					if ( $download_photos === 'force' || $lastupdated > $photosupdated ) {
						$this->clean_photos( $property_id );
						$photos_added = $this->download_photos( $property_id, $listing_id );
					}
				}

			} catch ( Exception $e ) {

				$this->update_meta( $property_id, 'last_error', $e->getMessage() );
				throw $e;

			}

			if ( empty( $property ) ) {
				$property = Cache::get_property_by( 'listing_id', $listing_id, $this->id );
				$property_id = $property[0]->ID;
			}

		}

		//if ( ! empty( $property ) ) {
		//	$this->download_openhouses( $property_id, $listing_id );
		//}

		return [ 'object_id' => $property_id ?? null, 'photos' => $photos_added ?? false ];

	}

	public function download_unitdata( $property_id, $listing_id ) {

		return;

		$unit_obj     = $this->get_data_meta( 'mf_object', 'Unit' );
		$unit_class   = $this->get_data_meta( 'mf_class', 'Unit' );
		$unit_listid  = $this->get_data_meta( 'mf_listid', $this->get_data_meta( 'mf_mlsid', 'ListingID' ) );
		$unit_no      = $this->get_data_meta( 'mf_unitno', '' );
		$unit_beds    = $this->get_data_meta( 'mf_beds', '' );
		$unit_baths   = $this->get_data_meta( 'mf_baths', '' );
		$unit_rent    = $this->get_data_meta( 'mf_rent', '' );
		$unit_sqft    = $this->get_data_meta( 'mf_sqft', '' );
		$unit_total   = $this->get_data_meta( 'mf_total', '' );

		$search = $this->rets->Search( $unit_obj, $unit_class, "({$unit_listid}={$listing_id})" );
		if ( $search->getTotalResultsCount() === 0 ) {
			return;
		}

		$unit = 0;
		$total_units = 0;
		$income = 0;
		foreach ( $search as $unit ) {

			$units ++;

			$data = $unit->toArray();

			if ( ! empty( $unit_no ) ) {
				$unit_id = preg_replace( '/[^0-9]/', '', $unit[ $unit_no ] ?? $units );
			} else {
				$unit_id = $units;
			}

			$beds  = ! empty( $unit_beds ) ? ( $unit[ $unit_beds ] ?? '' ) : '';
			$baths = ! empty( $unit_baths ) ? ( $unit[ $unit_baths ] ?? '' ) : '';
			$rent  = ! empty( $unit_rent ) ? ( $unit[ $unit_rent ] ?? '' ) : '';
			$sqft  = ! empty( $unit_rent ) ? ( $unit[ $unit_sqft ] ?? '' ) : '';
			$total = ! empty( $unit_total ) ? ( $unit[ $unit_total ] ?? 1 ) : 1;

			$total_units += $total;

			if ( ! empty( $sqft ) ) {
				$sqft = preg_replace( '/[^0-9]/', '', $sqft );
			}
			if ( ! empty( $rent ) ) {
				$rent = preg_replace( '/[^0-9.]/', '', $rent );
				$income += $rent;
			}

			$this->update_meta( $property_id, "UnitType{$unit_id}BedsTotal", $beds );
			$this->update_meta( $property_id, "UnitType{$unit_id}BathsTotal", $baths );
			$this->update_meta( $property_id, "UnitType{$unit_id}Rent", $rent );
			$this->update_meta( $property_id, "UnitType{$unit_id}SqFt", $sqft );
			$this->update_meta( $property_id, "UnitType{$unit_id}UnitsTotal", $total );

		}

		$this->update_meta( $property_id, "GrossIncomeActual", $income );
		$this->update_meta( $property_id, "NumberOfUnitsTotal", $total );


	}

	public function get_upcoming_openhouses() {

		$primary_key  = $this->get_data_meta( 'alt_listing_id', $this->get_data_meta( 'primary_id', 'ListingID' ) );
		$oh_listid   = $this->get_data_meta( 'oh_listid', $primary_key );

		$oh_object   = $this->get_data_meta( 'oh_object', 'OpenHouseBrokerTour' );
		$oh_class    = $this->get_data_meta( 'oh_class', 'OpenHouseBrokerTourDD' );
		$oh_date     = $this->get_data_meta( 'oh_date', 'OpenHouseDate' );

		$now = date( 'Y-m-d' );
		$next_seven_days = date( 'Y-m-d', strtotime( '+8 days' ) );

		$openhouses = $this->rets->Search( $oh_object, $oh_class, "({$oh_date}={$now}+),({$oh_date}={$next_seven_days}-)", [ 'Select' => $oh_listid ] );

		$mlsids = [];
		foreach( $openhouses as $openhouse ) {
			$mlsids[] = $openhouse->get( $oh_listid );
		}

		return $mlsids;


	}

	// multi-source safe
	public function download_openhouses( $property_id, $listing_id ) {

		if ( ! empty( $this->reso_url ) ) {
			return;
		}

		$primary_key  = $this->get_data_meta( 'alt_listing_id', $this->get_data_meta( 'primary_id', 'ListingID' ) );
		$oh_listid   = $this->get_data_meta( 'oh_listid', $primary_key );

		$oh_object   = $this->get_data_meta( 'oh_object', 'OpenHouseBrokerTour' );
		$oh_class    = $this->get_data_meta( 'oh_class', 'OpenHouseBrokerTourDD' );
		$oh_prefix   = $this->get_data_meta( 'oh_prefix', 'OpenHouse' );
		$oh_date     = $this->get_data_meta( 'oh_date', 'OpenHouseDate' );
		$oh_start    = $this->get_data_meta( 'oh_time_start', 'OpenHouseStart' );
		$oh_end      = $this->get_data_meta( 'oh_time_end', 'OpenHouseEnd' );

		try {
			if ( empty( $this->reso_url ) ) {

				$openhouses = $this->rets->Search( $oh_object, $oh_class, "({$oh_listid}={$listing_id})", [] );

			} else {

				//$openhouses = $this->reso_api_query( $oh_object, [ "$oh_listid eq $listing_id" ] );
				return false;

			}
		} catch( \Exception $e ) {
			return false;
		}

		if ( ! empty( $openhouses ) && count( $openhouses ) > 0 ) {

			$next_upcoming = null;
			$now = time();
			$found = [];

			Cache::delete_data( $property_id, 'oh' );

			$ohdata = null;
			foreach ( $openhouses as $oh ) {

				$fields = $oh->toArray();

				$time = strtotime( $fields[ $oh_date ]  );
				Cache::add_data( $property_id, 'oh', $time * 100 );

				if ( time() < $time && empty( $ohdata ) ) {

					$ohdata = [ $fields[ $oh_start ], $fields[ $oh_end ] ];

				}

			}

			if ( empty( $ohdata ) ) {
				$this->delete_meta( $property_id, 'open_houses' );
			} else {
				$this->update_meta( $property_id, 'open_houses', serialize( $ohdata ) );
			}

			return true;

		} else {

			$this->delete_meta( $property_id, 'open_houses' );
			Cache::delete_data( $property_id, 'oh' );

		}

		return false;


	}


	/**
	 * DEPRECATED. Use upload_photos
	 */
	public function import_photos( $property_id, $photos, $download = true, $maximum_count = null ) {

		if ( empty( $photos ) || ! is_array( $photos ) ) {
			return 0;
		}

		//$property_obj            = $this->get_data_meta( 'property_obj', 'Property' );
		//$download_all            = $this->get_data_meta( 'property_photo_download_all' ) === '1';
		//$photo_count_key         = $this->get_data_meta( 'photokey', 'ImageNumber' );
		//$photo_caption_key       = $this->get_data_meta( 'photocaption', 'ImageCaption' );
		//$photo_short_caption_key = $this->get_data_meta( 'photoshort', 'ImageCaptionShort' );
		//$photos_count_meta       = $this->get_data_meta( 'photocount', 'PhotosCount' );
		//$total_photos = $resource->get( $photos_count_meta );
		//if ( empty( $total_photos ) ) {
		//	return false;
		//}

		//if ( $download_all ) {
		//	$tmppath = $vf_path;
		//}

		if ( is_array( $photos ) ) {
			usort( $photos, function( $a, $b ) {
				if ( $a['Order'] === $b['Order'] ) {
					return 0;
				}
				return $a['Order'] > $b['Order'] ? 1 : -1;
			} );
		}

		$added = 0;
		foreach( $photos as $photo ) {

			$this->import_photo( $property_id, $photo );

			$added += 1;

			if ( is_numeric( $maximum_count ) && $added >= $maximum_count ) {
				break;
			}

		}

		return $added;

	}

	public function upload_photos( $property_id, $listing_id, $s3, $maximum = null ) {

		$property_obj            = $this->get_mls_field_key( 'property_obj', 'Property' );
		$photo_class             = $this->get_mls_field_key( 'property_photo_resource', 'Photo' );

		if ( defined( 'IMAGE_SWAP_DIR' ) ) {
			$tmppath = IMAGE_SWAP_DIR;
		} else {
			$tmppath = trailingslashit( $upload_dir['path'] ) . 'tmp/';
		}

		try {
			if ( empty( $this->reso_url ) ) {
				$photos = $this->rets->GetObject( $property_obj, $photo_class, $listing_id, '*', 1 );
			} elseif ( $listing_id === self::$reso_id && ! empty( self::$reso_media ) ) {
				$photos = self::$reso_media;
				usort( $photos, function ( $item1, $item2 ) {
					return $item1['Order'] <=> $item2['Order'];
				} );
			} else {
				return 0;
			}
		} catch( \Exception $e ) {
			throw new \Exception( "Error in photos download ({$listing_id}) - " . $e->getMessage() );
		}

		$added = 0;
		foreach( $photos as $photo ) {

			if ( empty( $this->reso_url ) && $photo->isError() ) {
				continue;
				//throw new \Exception( "({$listing_id}-{$number}): " . $object->getError()->getMessage() );
			}

			if ( empty( $this->reso_url ) ) {
				$number        = $photo->getObjectId();
				$original_url  = $photo->getLocation();
				$caption       = $photo->getContentDescription();
				$caption_short = $photo->getContentSubDescription();
			} else {
				$number        = absint( $photo['Order'] ) + 1;
				$original_url  = $photo['MediaURL'];
				$caption = null;
				$caption_short = null;
			}

			if ( ! is_null( $maximum ) && $added >= $maximum ) {
				$this->add_photo( $property_id, [
					'url'           => $original_url,
					'thumbnail'     => $original_url,
					//'tiny'          => $tiny_url,
					'order'         => $number,
					'caption'       => $caption,
					'caption_short' => $caption_short,
				] );
				$added ++;
				continue;
			}

			$thumbnail_url = $original_url;
			$tiny_url      = null;

			$caption = null;
			$caption_short = null;
			$original = null;

			$caption       = empty( $caption ) ? null : substr( $caption, 0, 103 );
			$caption_short = empty( $caption_short ) ? null : substr( $caption_short, 0, 50 );

			if ( ! is_numeric( $listing_id) || ( $listing_id > 1000000000 ) ) {
				$folder = $this->slug() . '/' . substr( $listing_id, 0, 6 );
			} else {
				$folder = $this->slug() . '/' . floor( $listing_id / 1000 );
			}

			$filebase  = "original-{$listing_id}-{$number}";
			$original  = "$filebase.jpg";
			$thumbnail = "{$filebase}__thumbnail.jpg";
			$tiny      = "{$filebase}__tiny.jpg";

			$original_exists = $s3->doesObjectExist( Settings::get_aws('bucket'), $folder . '/' . $listing_id . '/' . $original );
			if ( $original_exists ) {
				$thumbnail_exists = $s3->doesObjectExist( Settings::get_aws('bucket'), $folder . '/' . $listing_id . '/' . $thumbnail );
			}

			if ( $original_exists && $thumbnail_exists ) {
				
				$large_url  = Settings::get_aws('url') . '/' . $folder . '/' . $listing_id . '/' . $original;
				$thumbnail_url = Settings::get_aws('url') . '/' . $folder . '/' . $listing_id . '/' . $thumbnail;

			} else {

				$local = false;
				if ( substr( $original_url, 0, 2 ) === '//' ) {
					$original_url = 'http:' . $original_url;
				}

				$image_source = wp_safe_remote_get( $original_url );
				if ( empty( $image_source ) || is_wp_error( $image_source ) ) {
					continue;
				}

				$contents = wp_remote_retrieve_body( $image_source );

				$localpath = $tmppath . $original;
				file_put_contents( $localpath, $contents );

				$image = wp_get_image_editor( $localpath );
				if ( is_wp_error( $image ) ) {
					$this->add_photo( $property_id, [
						'url'           => $original_url,
						'thumbnail'     => $original_url,
						//'tiny'          => $tiny_url,
						'order'         => $number,
						'caption'       => $caption,
						'caption_short' => $caption_short,
					] );
					$added ++;
					continue;
				}

				$image->set_quality( 70 );
				$image->resize( 600, 450, true );
				$image->save( $tmppath . $thumbnail );

				

				$s3->putObject([
					'Bucket'      => Settings::get_aws('bucket'),
					'Key'         => $folder . '/' . $listing_id . '/' . $original,
					'ACL'         => 'public-read',
					'ContentType' => 'image',
					'Body'        => $contents,
				]);
				$large_url  = Settings::get_aws('url') . '/' . $folder . '/' . $listing_id . '/' . $original;

				$s3->putObject([
					'Bucket'      => Settings::get_aws('bucket'),
					'Key'         => $folder . '/' . $listing_id . '/' . $thumbnail,
					'ACL'         => 'public-read',
					'ContentType' => 'image',
					'Body'        => file_get_contents( $tmppath . $thumbnail ),
				]);
				$thumbnail_url = Settings::get_aws('url') . '/' . $folder . '/' . $listing_id . '/' . $thumbnail;

				if ( file_exists( $localpath ) ) {
					unlink( $localpath );
				}
				if ( file_exists( $tmppath . $thumbnail ) ) {
					unlink( $tmppath . $thumbnail );
				}

			}

			$this->add_photo( $property_id, [
				'url'           => $large_url,
				'thumbnail'     => $thumbnail_url,
				//'tiny'          => $tiny_url,
				'order'         => $number,
				'caption'       => $caption,
				'caption_short' => $caption_short,
			] );

			$added ++;

		}

		return $added;

	}

	/**
	 * DEPRECATED. Use upload_photos
	 */
	public function import_photo( $property_id, $photo ) {

		$upload_dir = wp_upload_dir( 'vf', true );
		$vf_path  = $upload_dir['path'];
		if ( defined( 'VF_IMG_PATH' ) ) {
			$vf_path = VF_IMG_PATH;
		}
		$vf_path = trailingslashit( $vf_path );
		//$vf_url   = trailingslashit( $upload_dir['url'] );

		if ( defined( 'IMAGE_SWAP_DIR' ) ) {
			$tmppath = IMAGE_SWAP_DIR;
		} else {
			$tmppath = trailingslashit( $upload_dir['path'] ) . 'tmp/';
		}


		if ( defined( 'AWS_CONFIG' ) && defined( 'AWS_REGION' ) && defined( 'AWS_BUCKET' ) && defined( 'AWS_URL' ) ) {

			$provider = CredentialProvider::ini( 'default', AWS_CONFIG );
			$provider = CredentialProvider::memoize($provider);

			$s3 = new S3Client([
				'version'     => 'latest',
				'region'      => Settings::get_aws('region'),
				'credentials' => $provider,
			]);

		}

		$number        = absint( $photo['Order'] ) + 1;
		$original_url  = $photo['MediaURL'];
		$thumbnail_url = $original_url;
		$tiny_url      = null;

		$caption = null;
		$caption_short = null;

		//$photoid = $photo->getContentId();

		$filebase  = "original-{$property_id}-{$number}";
		$original  = "$filebase.jpg";
		$thumbnail = "{$filebase}__thumbnail.jpg";
		$tiny      = "{$filebase}__tiny.jpg";

		if ( substr( $original_url, 0, 2 ) === '//' ) {
			$download_url = 'http:' . $original_url;
		} else {
			$download_url = $original_url;
		}

		try {
			//$contents = file_get_contents( $download_url );

			$image = wp_get_image_editor( $download_url );

		} catch( \Exception $e ) {
			throw new \Exception( "Error in photo download ({$listing_id}:{$number}) - " . $e->getMessage() );
		}

		//file_put_contents( $tmppath . $original, $contents );
		//$image = wp_get_image_editor( $tmppath . $original );

		if ( ! empty( $image ) && ! is_wp_error( $image ) ) {

			$image->set_quality( 70 );

			if ( ! empty( $s3 ) ) {

				$folder = $this->slug() . '/' . floor( $property_id / 1000 );

				$image->resize( 1120, 840, false );
				$image->save( $tmppath . $original );

				$s3->putObject([
					'Bucket'      => Settings::get_aws('bucket'),
					'Key'         => $folder . '/' . $property_id . '/' . $original,
					'ACL'         => 'public-read',
					'ContentType' => 'image',
					'Body'        => file_get_contents( $tmppath . $original ),
				]);

				
				$image->resize( 600, 450, true );
				$image->save( $tmppath . $thumbnail );

				$s3->putObject([
					'Bucket'      => Settings::get_aws('bucket'),
					'Key'         => $folder . '/' . $property_id . '/' . $thumbnail,
					'ACL'         => 'public-read',
					'ContentType' => 'image',
					'Body'        => file_get_contents( $tmppath . $thumbnail ),
				]);

				$image->set_quality( 40 );
				$image->resize( 40, 30, true );
				$image->save( $vf_path . $tiny );

				$s3->putObject([
					'Bucket'      => Settings::get_aws('bucket'),
					'Key'         => $folder . '/' . $property_id . '/' . $tiny,
					'ACL'         => 'public-read',
					'ContentType' => 'image',
					'Body'        => file_get_contents( $vf_path . $tiny ),
				]);

				unlink( $tmppath . $original );
				unlink( $tmppath . $thumbnail );

				$original_url  = Settings::get_aws('url') . '/' . $folder . '/' . $property_id . '/' . $original;
				$thumbnail_url = Settings::get_aws('url') . '/' . $folder . '/' . $property_id . '/' . $thumbnail;
				$tiny_url      = $tiny;

				$original = false;

			} else {

				
				$image->set_quality( 70 );

				$image->resize( 1120, 840, false );
				$image->save( $vf_path . $original );

				$image->resize( 600, 450, true );
				$image->save( $vf_path . $thumbnail );

				$image->set_quality( 40 );
				$image->resize( 40, 30, true );
				$image->save( $vf_path . $tiny );

				$original_url  = $vf_url . $original;
				$thumbnail_url = $thumbnail;
				$tiny_url      = $tiny;

				//if ( ! $download_all ) {
				//	unlink( $tmppath . $original );
				//}

			}

		}

		unset( $image );
		unset( $contents );

		$this->add_photo( $property_id, [
			'url'           =>  ! empty( $original ) ? $original : $original_url,
			'thumbnail'     => $thumbnail_url,
			'tiny'          => $tiny_url,
			'order'         => $number,
			'caption'       => $caption,
			'caption_short' => $caption_short,
		] );

		return true;

	}

	/**
	 * DEPRECATED. Use upload_photos
	 */
	public function download_photos( $property_id, $resource, $download = true ) {

		$property_obj            = $this->get_data_meta( 'property_obj', 'Property' );
		$photo_class             = $this->get_data_meta( 'property_photo_resource', 'Photo' );

		$download_all            = $this->get_data_meta( 'property_photo_download_all' ) === '1';
		//$photo_count_key         = $this->get_data_meta( 'photokey', 'ImageNumber' );
		//$photo_caption_key       = $this->get_data_meta( 'photocaption', 'ImageCaption' );
		//$photo_short_caption_key = $this->get_data_meta( 'photoshort', 'ImageCaptionShort' );
		//$photos_count_meta       = $this->get_data_meta( 'photocount', 'PhotosCount' );
		//$total_photos = $resource->get( $photos_count_meta );
		//if ( empty( $total_photos ) ) {
		//	return false;
		//}

		$upload_dir = wp_upload_dir( 'vf', true );
		$vf_path  = $upload_dir['path'];
		if ( defined( 'VF_IMG_PATH' ) ) {
			$vf_path = VF_IMG_PATH;
		}
		$vf_path = trailingslashit( $vf_path );
		//$vf_url   = trailingslashit( $upload_dir['url'] );

		if ( defined( 'IMAGE_SWAP_DIR' ) ) {
			$tmppath = IMAGE_SWAP_DIR;
		} else {
			$tmppath = trailingslashit( $upload_dir['path'] ) . 'tmp/';
		}


		if ( $download_all ) {
			$tmppath = $vf_path;
		}

		$primary_key  = $this->get_data_meta( 'alt_listing_id', $this->get_data_meta( 'primary_id', 'ListingID' ) );

		if ( is_numeric( $resource ) || is_string( $resource ) ) {
			$listing_id = $resource;
		} else {
			$listing_id = $resource->get( $primary_key );
		}

		try {
			$photos = $this->rets->GetObject( 'Property', $photo_class, $listing_id, '*', 1 );
		} catch( \Exception $e ) {
			throw new \Exception( "Error in photos download ({$listing_id}) - " . $e->getMessage() );
		}

		$added = 0;
		foreach( $photos as $photo ) {

			if ( $photo->isError() ) {
				continue;
				//throw new \Exception( "({$listing_id}-{$number}): " . $object->getError()->getMessage() );
			}

			$number        = $photo->getObjectId();
			$original_url  = $photo->getLocation();
			$thumbnail_url = $original_url;
			$tiny_url      = null;

			$caption = null;
			$caption_short = null;
			$original = null;

			$caption       = $photo->getContentDescription();
			$caption       = empty( $caption ) ? null : substr( $caption, 0, 103 );
			$caption_short = $photo->getContentSubDescription();
			$caption_short = empty( $caption_short ) ? null : substr( $caption_short, 0, 50 );

			if ( ( $download_all || absint( $number ) === 1 ) && $download ) {

				//$photoid = $photo->getContentId();

				$filebase  = "original-{$listing_id}-{$number}";
				$original  = "$filebase.jpg";
				$thumbnail = "{$filebase}__thumbnail.jpg";
				$tiny      = "{$filebase}__tiny.jpg";

				if ( substr( $original_url, 0, 2 ) === '//' ) {
					$download_url = 'http:' . $original_url;
				} else {
					$download_url = $original_url;
				}

				try {
					$contents  = @file_get_contents( $download_url );
				} catch( \Exception $e ) {
					throw new \Exception( "Error in photo download ({$listing_id}:{$number}) - " . $e->getMessage() );
				}

				if ( ! empty( $contents ) ) {

					file_put_contents( $tmppath . $original, $contents );

					$image = wp_get_image_editor( $tmppath . $original );
					$image->set_quality( 70 );
					$image->resize( 600, 450, true );
					$image->save( $vf_path . $thumbnail );

					/*$image->set_quality( 40 );
					$image->resize( 40, 30, true );
					$image->save( $vf_path . $tiny );*/

					//$original_url  = $vf_url . $original;
					$thumbnail_url = $thumbnail;
					//$tiny_url      = $tiny;

					unlink( $tmppath . $original );

				} else {
					$original = $download_url;
					$original_url = $download_url;
					$thumbnail_url = $thumbnail;
					$tiny_url = '';
				}

			}

			$this->add_photo( $property_id, [
				'url'           => $download_all ? $original : $original_url,
				'thumbnail'     => $thumbnail_url,
				//'tiny'          => $tiny_url,
				'order'         => $number,
				'caption'       => $caption,
				'caption_short' => $caption_short,
			] );

			$added += 1;

		}

		return $added;

	}

	public function get_properties_for_update( $assoc_keys = true, $last_modified = null, $inactive_query = false, $rate_limit = 1, $maximum_count = null ) {

		if ( empty( $this->classes ) ) {
			throw new \Exception( 'No property classes were defined on the feed source' );
		}

		$mls_key = $this->get_data_meta( 'primary_id', 'ListingID' );
		$primary_key  = $this->get_data_meta( 'alt_listing_id', $mls_key );

		$use_tz  = $this->get_data_meta( 'use_tz', false );

		$max_count  = absint( $this->get_data_meta( 'maximum_count', 2000 ) ) ?: 2000;

		$modified_key = $this->get_data_meta( 'property_datemodified', 'DateTimeModified' );

		if ( $last_modified ) {
			$last_modified = date( 'c', $last_modified );
		} else {
			$last_modified = $this->obj->last_updated ?? 0;
			$last_modified = date( 'c', $last_modified );
		}
		if ( ! $use_tz ) {
			$last_modified = substr( $last_modified, 0, strpos( $last_modified, '+' ) );
		}
		
		$ids = array();
		$index = array();
		$i = 0;
		if ( $this->is_reso_api() ) {

			$page = 0;
			$query[] = "$modified_key gt {$last_modified}Z";
			if ( $inactive_query ) {
				$inactive_query = $this->get_data_meta( 'query_inactive', '' );
				if ( $inactive_query ) {
					$query[] = $inactive_query;
				}
			}
			do {
				$returned = $this->reso_api_query( 'Property', $query, $max_count, $page * $max_count, "$mls_key,$primary_key,$modified_key" );
                //echo "pull returned class-source:\nline:".__LINE__."\n";print_r($returned);exit;
				if ( ! empty( $returned ) && ! empty( $returned['@odata.count'] ) ) {
					echo "\rCollecting properties, working on page $page of ";
					echo ceil( $returned['@odata.count'] / $max_count );
					foreach( $returned['value'] as $result ) {
						$id = $result[ $primary_key ];
						$mlsid = $result[ $mls_key ];
						$modified = $result[ $modified_key ];
						if ( $assoc_keys ) {
							if ( empty( $ids[ $id ] ) ) {
								$ids[ $id ] = [ 'mlsid' => $mlsid, 'modified' => strtotime( $modified ), 'classes' => [ 'WebAPI' ] ];
							}
						} else {
							if ( ! isset( $ids[ $id ] ) ) {
								$index[] = [ 'mlsid' => $mlsid, 'modified' => strtotime( $modified ), 'classes' => [ 'WebAPI' ] ];
								$ids[ $id ] = $i;
								$i++;
							}
						}
					}
				}
				$page++;
				if ( $rate_limit ) {
					sleep( $rate_limit );
				}
			} while ( ! empty( $returned ) && ! empty( $returned['value'] ) && ( $maximum_count === null || $maximum_count > count( $ids ) ) );

			echo "\n";
			echo "Collected " . count( $index ) . " properties.\r\n";

		} else {
			
			foreach ( $this->classes as $class ) {
				$class = trim( $class );

				$results = $this->rets->Search( 'Property', $class, "($modified_key=$last_modified+)", [ 'Select' => "$primary_key,$mls_key,$modified_key" ] );

				foreach( $results as $result ) {
					$id = $result->get( $primary_key );
					$mlsid = $result->get( $mls_key );
					$modified = $result->get( $modified_key );

					if ( $assoc_keys ) {
						if ( empty( $ids[ $id ] ) ) {
							$ids[ $id ] = [ 'listing_id' => $id, 'mlsid' => $mlsid, 'modified' => strtotime( $modified ), 'classes' => [] ];
						}
						array_push( $ids[ $id ]['classes'], $class );
					} else {
						if ( ! isset( $ids[ $id ] ) ) {
							$index[] = [ 'listing_id' => $id, 'mlsid' => $mlsid, 'modified' => strtotime( $modified ), 'classes' => [] ];
							$ids[ $id ] = $i;
							$i++;
						}
						array_push( $index[ $ids[ $id ] ]['classes'], $class );
					}
					
				}
			}
		}

		return $assoc_keys ? $ids : $index;

	}

	/**
	 * DEPRECATED
	 */
	public function get_properties_updated_after( $time ) {

		global $vfdb;

		$mls_key = $this->get_data_meta( 'primary_id', 'ListingID' );
		$primary_key  = $this->get_data_meta( 'alt_listing_id', $mls_key );
		$modified_key = $this->get_data_meta( 'property_datemodified', 'DateTimeModified' );

		$use_tz  = $this->get_data_meta( 'use_tz', false );

		$last_modified = date( 'c', $time );
		if ( ! $use_tz ) {
			$last_modified = substr( $last_modified, 0, strpos( $last_modified, '+' ) );
		}

		$all_properties = $vfdb->get_results( $vfdb->prepare(
			"SELECT * FROM " . Cache::$prop_table_name . " WHERE modified > %d AND post_id = %d",
			$time,
			$this->id
		) );

		$all_index = [];
		foreach( $all_properties as $property ) {
			$all_index[ $property->listing_id ] = $property;
		}

		$changed = [];
		foreach ( $this->classes as $class ) {
			$class = trim( $class );
			$results = $this->rets->Search( 'Property', $class, "($modified_key=$last_modified+)", [ 'Select' => "$primary_key,$mls_key,$modified_key" ] );
			foreach( $results as $result ) {
				$id = $result->get( $primary_key );

				if ( ! isset( $all_index[ $id ] ) ) {
					continue;
				}

				$modified = (int) $result->get( $modified_key );
				if ( absint( $all_index[ $id ]->modified ) === strtotime( $modified ) ) {
					continue;
				}

				if ( ! isset( $changed[ $id ] ) ) {
					$changed[ $id ] = $all_index[ $id ];
					$changed[ $id ]->classes = [ $class ];
				} else {
					$changed[ $id ]->classes[] = $class;
				}

			}
		}

		return $changed;

	}

	public function get_mls_field_key( $key, $default = null ) {

		if ( empty( $this->meta[ '_datasource_' . $key ] ) ) {
			return $default;
		}

		return $this->meta[ '_datasource_' . $key ][0];

	}

	/** 
	 * deprecated
	 */
	public function get_data_meta( $key, $default = null ) {

		return $this->get_mls_field_key( $key, $default );

	}

	public function get_photos( $property_id ) {

		global $vfdb;

		if ( WP_CACHE ) {
			$photos = wp_cache_get( 'photos_' . $property_id, 'vestorfilter' );
		}
		if ( isset( $photos ) && $photos !== false ) {
			return $photos;
		}

		$table = $this->photo_table_name();

		$query = "SELECT * FROM $table WHERE property_id = %d ORDER BY `order` ASC";
		$query = $vfdb->prepare( $query, $property_id );

		$photos = $vfdb->get_results( $query );

		wp_cache_set( 'photos_' . $property_id, $photos, 'vestorfilter' );

		return $photos;

	}

	public function get_photo_count( $property_id ) {

		global $vfdb;


		$table = $this->photo_table_name();

		$query = "SELECT COUNT(*) FROM $table WHERE property_id = %d";
		$query = $vfdb->prepare( $query, $property_id );

		return $vfdb->get_var( $query );

	}

	public function add_photo( $property_id, $values = array() ) {

		global $vfdb;

		$values['property_id'] = $property_id;

		$sanitization = [];
		foreach ( $values as $value ) {
			$sanitization[] = is_null( $value ) ? null : '%s';
		}

		if ( $error = $vfdb->insert(
			$this->photo_table_name(),
			$values,
			$sanitization
		) ) {
			return $vfdb->insert_id;
		} else {
			throw new \Exception( 'Could not add photo to records table // ' . json_encode( $values ) );
		}

	}

	public function update_photo( $photo_id, $values = array() ) {

		global $vfdb;

		$sanitization = [];
		foreach ( $values as $value ) {
			$sanitization[] = is_null( $value ) ? null : '%s';
		}

		if ( $error = $vfdb->update(
			$this->photo_table_name(),
			$values,
			[ 'ID' => $photo_id ],
			$sanitization,
			[ '%d' ]
		) ) {
			return true;
		} else {
			throw new \Exception( 'Could not update photo ' . $photo_id );
		}

	}

	public function clean_photos( $property_id ) {

		global $vfdb;


		$table = $this->photo_table_name();

		$query = "DELETE FROM $table WHERE property_id = %d";
		$query = $vfdb->prepare( $query, $property_id );

		return $vfdb->query( $query );

	}

	public function delete_photo( $photo_id ) {

		global $vfdb;

		$table = $this->photo_table_name();

		$query = "DELETE FROM $table WHERE ID = %d";
		$query = $vfdb->prepare( $query, $photo_id );

		return $vfdb->query( $query );

	}

	public function clean_meta( $property_id ) {

		global $vfdb;

		$table = $this->meta_table_name();
		$vfdb->query( $vfdb->prepare(
			"DELETE FROM `$table` WHERE `property_id` = %d",
			$property_id
		) );

	}

	public function get_property_meta( $property_id ) {

		global $vfdb;

		if ( WP_CACHE ) {
			$meta = wp_cache_get( "meta_{$property_id}", 'vestorfilter' );
			if ( $meta !== false ) {
				return $meta;
			}
		}

		$datatable = $this->meta_table_name();
		$query = $vfdb->prepare( "SELECT `key`,`value` FROM $datatable WHERE property_id = %d ORDER BY ID DESC", $property_id );
		$results = $vfdb->get_results( $query );

		$texttable = Cache::$text_table_name;
		$meta = $vfdb->get_var( $vfdb->prepare( "SELECT `value` FROM $texttable WHERE property_id = %d AND `key` = 'meta'", $property_id ) );
		if ( $meta ) {
			$meta = [ '__data' => $meta ];
		} else {
			$meta = [];
		}

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				if ( isset( $meta[ $row->key ] ) ) {
					if ( is_array( $meta[ $row->key ] ) ) {
						$meta[ $row->key ][] = $row->value;
					} else {
						$meta[ $row->key ] = array( $meta[ $row->key ], $row->value );
					}
				} else {
					$meta[ $row->key ] = $row->value;
				}
			}
			wp_cache_set( "meta_{$property_id}", $meta, 'vestorfilter' );
		} else {
			return [];
		}

		return $meta;

	}

	public function get_meta_value( $property_id, $key ) {

		global $vfdb;

		if ( WP_CACHE ) {
			$meta = wp_cache_get( "meta_{$property_id}_{$key}", 'vestorfilter' );
			if ( $meta !== false ) {
				return $meta;
			}
		}

		$datatable = $this->meta_table_name();
		$query = $vfdb->prepare( "SELECT `value` FROM $datatable WHERE property_id = %d and `key` = %s", $property_id, $key );

		$result = $vfdb->get_var( $query );

		return $result;

	}

	public function add_meta( $property_id, $key, $value ) {

		global $vfdb;

		if ( $vfdb->insert(
				$this->meta_table_name(),
				[ 'property_id' => $property_id, 'key' => $key, 'value' => $value ],
				[ '%d', '%s', '%s' ]
		) ) {
			return $vfdb->insert_id;
		} else {
			throw new \Exception( "There was an error creating the meta record ($key:$value)" );
		}

	}

	public function update_meta( $property_id, $key, $value, $delete_empty = false ) {

		global $vfdb;

		$old = $vfdb->get_var( $vfdb->prepare(
			'SELECT `ID` FROM ' . $this->meta_table_name() . ' WHERE `property_id` = %d and `key` = %s',
			$property_id,
			$key
		) );

		if ( empty( $old ) ) {

			if ( ! empty( $value ) ) {

				return $this->add_meta( $property_id, $key, $value );

			} else {

				return false;

			}

		} else {

			$update = $vfdb->update(
				$this->meta_table_name(),
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

	public function delete_meta( $property_id, $key ) {

		global $vfdb;

		$vfdb->query( $vfdb->prepare(
			'DELETE FROM ' . $this->meta_table_name() . ' WHERE `property_id` = %d and `key` = %s',
			$property_id,
			$key
		) );

	}

}