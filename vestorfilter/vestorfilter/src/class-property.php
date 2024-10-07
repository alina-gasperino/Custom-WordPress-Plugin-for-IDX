<?php

namespace VestorFilter;

use \Aws\S3\S3Client;
use \Aws\Exception\AwsException;
use \Aws\Credentials\CredentialProvider;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Property {

	private $property_id = null;

	private $mls_id = null;

	private $data = [];

	private $meta = null, $original_meta = null;

	private $photos = null;

	private $slug = '';

	private $source = null;

	private $index = null;

	private $modified = null;

	private $vf = [];

	private $geo = null;

	private $hidden = null;

	private $types = [];

	private $status = [];

	private $upgraded = false;

	public function __construct( $property_data, $get_photos = true ) {

		if ( is_numeric( $property_data ) ) {
			$property_lookup = Cache::get_property_by( 'ID', $property_data );
			if ( ! empty( $property_lookup ) ) {
				$property_data = $property_lookup[0];
			} else {
				throw new \Exception( "Property $property_data requested does not exist" );
			}
		}

		$this->property_id = $property_data->ID;
		$this->slug        = $property_data->slug ?: '';
		$this->modified    = $property_data->modified ?: null;

		$this->source      = new Source(
			apply_filters(
				'vf_property_source_object_id',
				$property_data->post_id,
				$property_data
			)
		);
		$this->hidden      = $property_data->hidden;
		
		// todo: perhaps this should be limited to the vital meta records
		$meta       = $this->source->get_property_meta( $property_data->ID );
		$this->meta = $meta;

		if ( isset( $this->meta['__data' ] ) ) {
			$this->upgraded = true;
			$this->original_meta = json_decode( $this->meta['__data'], true );
		} else {
			$this->original_meta = $this->meta;
		}
		

		$indexes     = Cache::get_indexes( $property_data->ID );
		$this->index = $indexes;
		if ( ! empty( $indexes['property-type'] ) ) {
			$this->types = $indexes['property-type'] ?? [];
		}
		if ( ! empty( $indexes['status'] ) ) {
			$this->status = $indexes['status'] ?? [];
		}

        if(isset($property_data->MLSID) && !empty($property_data->MLSID)) {
            $this->mls_id = $property_data->MLSID; // phpcs:ignore
        } else {
            $this->mls_id = $property_data->mlsid;
        }

		if ( $get_photos ) {
			$this->get_photos();
		}

	}

	public function get_modified( $format = 'U' ) {
		return gmdate( $format, $this->modified );
	}

	public function get_source() {
		return $this->source;
	}

	public function get_office_name() {
		$key = $this->source->get_field_map( 'listing_office', 'ListOfficeName' );
		if ( empty( $key ) ) {
			return '';
		}
		$value = $this->original_meta[ $key ] ?? '';
		if ( is_array( $value ) ) {
			$value = $value[0];
		}
		return $value ?: '';
	}

	public function destroy() {

		unset( $this->photos );
		unset( $this->meta );
		unset( $this->original_meta );

	}

	public function ID() {
		return $this->property_id;
	}

	public function MLSID() {
		return $this->mls_id;
	}

	public function slug() {
		return $this->slug;
	}

	public function is_hidden() {
		return $this->hidden > 0;
	}

	public function is_type( $type ) {
		return in_array( $type, $this->types );
	}

	public function is_status( $status ) {
		return is_array( $status ) 
			? array_intersect( $status, $this->status )
			: in_array( $status, $this->status );
	}

	public function get_mls_data( $key ) { 

		$mlskey = $this->source->get_meta( $key ) 
				?: $this->source->get_meta( 'property_' . $key )
				?: $this->source->get_meta( 'field_' . $key )
				?: ( isset( self::$fields_available[ $key ] ) && isset( self::$fields_available[ $key ]['key'] ) ? self::$fields_available[ $key ]['key'] : null );

		if ( empty( $mlskey ) ) {
			return null;
		}

		$data = $this->original_meta[ $mlskey ] ?? null;
		if ( is_array( $data ) ) {
			return current( $data );
		}
		return $data;

	}

	public function load_vestorfilter( $key ) {

		$value            = Filters::get_stored_value( $this, $key );
		$this->vf[ $key ] = $value;

		// echo "<!-- value: $key / $value -->";
	}

	public function load_all_vestorfilters() {

		foreach ( Filters::get_all() as $vf ) {
			$this->load_vestorfilter( $vf );
		}

		// echo "<!-- value: $key / $value -->";
	}

	public function show_vestorfilter() {

		return ! empty( $this->vf );

	}

	public function get_vestorfilter( $key, $formatted = true ) {

		if ( empty( $this->vf[ $key ] ) ) {
			return $formatted ? Filters::get_formatted_value( $key, null ) : null;
		}

		$value = $this->vf[ $key ];
		if ( $formatted ) {
			$value = Filters::get_formatted_value( $key, $value );
		}

		return $value;

	}

	public function can_show_vestorfilter( $key ) {

		$rules = Filters::get_filter_rules( $key );
		if ( empty( $rules ) ) {
			return true;
		}

		foreach ( $rules as $key => $values_allowed ) {
			$prop = $this->get_index( 'property-type' );
			$prop = array_pop( $prop );

			$negative = substr( $values_allowed[0], 0, 1 ) === '!';
			if ( empty( $prop ) && ! $negative ) {
				return false;
			}

			if ( ! $negative && ! in_array( $prop, $values_allowed ) ) {
				return false;
			}
			if ( $negative ) {
				foreach ( $values_allowed as $value ) {
					$value = trim( $value, '!' );
					if ( $prop === $value ) {
						return false;
					}
				}
			}
		}

		return true;

	}

	public function get_page_url() {

		return self::make_page_url( $this->mls_id, $this->slug );

	}

	public static function get_cache( $request, $force_live = null ) {

		$id = $request['id'] ?? '';
		if ( is_object( $id ) ) {
			$property = $id;
			$id = $property->ID();
		}
		$template = $request['template'] ?? 'page';
		if ( empty( $id ) ) {
			return new \WP_Error( 'bad_id', 'Please provide a property ID', [ 'status' => 404 ] );
		}

		global $vfdb;

		$property_table = Cache::$prop_table_name;
		$cache_table    = Cache::$cache_table_name;
		$geo_table      = Location::$geo_name;

		$query = $vfdb->prepare(
			"SELECT * FROM `$property_table` as `prop`
				LEFT JOIN `$cache_table` as `cache` ON ( `cache`.property_id = `prop`.ID )
				INNER JOIN `$geo_table` as `geo` ON ( `geo`.property_id = `prop`.ID )
				WHERE `prop`.ID = %d",
			$id
		);
		$row   = $vfdb->get_row( $query );

		if ( empty( $row ) ) {
			return new \WP_Error( 'bad_id', 'Could not find the property requested', [ 'status' => 404 ] );
		}
		/*
		if ( empty( $row->page_cache ) || empty( $row->data_cache ) || empty( $row->block_cache ) ) {
			$rebuild_property = $property ?? new Property( $row );
			$cache            = $rebuild_property->rebuild_cache();
			$row->page_cache  = $cache['page_cache'];
			$row->data_cache  = $cache['data_cache'];
			$row->block_cache = $cache['block_cache'];
			$row->address     = $cache['address'];
		}

		$page_cache  = $row->page_cache;
		$block_cache = $row->block_cache;
		$data_cache  = $row->data_cache;
		$address     = $row->address;
		*/

		//if ( $force_live === true || ( is_null( $force_live ) && Plugin::$debug_mode ) ) {
		$live_property = self::make_cache( $id );
		
		$page_cache  = $live_property['page_cache'];
		$block_cache = $live_property['block_cache'];
		$data_cache  = $live_property['data_cache'];
		$address     = $live_property['address'];
		
		//} else {
		//	$data_cache = unserialize( $row->data_cache );
		//}

		if ( is_string( $block_cache ) ) {
			$block_cache = json_decode( $block_cache );
		}
		if ( is_string( $data_cache ) ) {
			$data_cache = json_decode( $data_cache );
		}

		$source = new Source( $row->post_id );
		$photos = $source->get_photos( $id );

		return [
			'html'          => $template === 'page' ? $page_cache : '', //Property::get_cache_template(),
			'block'         => $block_cache,
			'data'          => $data_cache,
			'url'           => self::make_page_url( $row->MLSID, $row->slug ),
			'photos'        => $photos,
			'imageLocation' => Templator::$image_url,
			'id'            => $id,
			'title'         => $address,
			'logo'          => $source->get_compliance_logo(),
		];

	}

	public static function get_cache_html( $template, $property, $presets = [], $flags = [] ) {

		if ( is_numeric( $property ) ) {
			$property_id = $property;
			try {
				$property = new Property( $property_id, false );
				if ( ! $property ) {
					return '';
				} 
			} catch( Exception $e ) {
				return '';
			}
		} else {
			$property_id = $property->ID();
		}

		$cache = self::get_cache( [ 'id' => $property ?? $property_id, 'template' => 'block' ], Plugin::$debug_mode );
		if ( is_wp_error( $cache ) ) {
			return '';
		}

		if ( $template === 'block-classic' ) {

			ob_start();

			\VestorFilter\Util\Template::get_part(
				'vestorfilter',
				'cache/block-classic',
				[ 'property' => $property, 'recommended' => ( $flags['recommended'] ?? false ) ]
			);

			$html = Hooks\minify_html( ob_get_clean() );
			$html = Templator::filter_html( $html, $cache, $property, $flags );

		} else {

			$html = Templator::filter_html( $cache['html'], $cache, $property, $flags );

		}

		return $html;

	}
	/*
	public static function process_tags( $property, $html, $presets = [], $flags = [] ) {

		if ( is_numeric( $property ) ) {
			$property_id = $property;
			$property = new Property( $property, true );
			if ( ! $property ) {
				return '';
			} else {
				$property = current( $property );
			}
		} else {
			$property_id = $property->ID();
		}

		$photos = $property->get_photos();

		preg_match_all( '/<!--\{\{(.*?)\}\}-->/', $html, $htmltags, PREG_SET_ORDER );
		preg_match_all( '/\{\{(.*?)\}\}/', $html, $attrtags, PREG_SET_ORDER );

		if ( defined( 'VF_IMG_URL' ) ) {
			$image_url = VF_IMG_URL;
		} else {
			$upload_dir = wp_upload_dir( 'vf' );
			$image_url = $upload_dir['url'];
		}

		$tagcache = [];
		foreach( [ $htmltags, $attrtags ] as $tags ) {
			foreach( $tags as $tag ) {
				$replace = $tag[0];
				$tagattr = trim( $tag[1] );
				if ( isset( $presets[ $tagattr ] ) ) {
					$replacement = $presets[ $tagattr ];
				} elseif ( isset( $tagcache[ $tagattr ] ) ) {
					$replacement = $tagcache[ $tagattr ];
				} else {
					$replacement = null;
					$attrs = explode( ':', trim( $tagattr ), 2 );
					$value = null;
					$subset = null;
					if ( count( $attrs ) > 1 ) {
						$value = $attrs[1];
						if ( preg_match( '/(.*?)\[(.*?)\]/', $value, $match ) ) {
							$value = $match[1];
							$subset = $match[2];
						}
					}
					switch( $attrs[0] ) {
						case 'property':
							if ( $value === 'url' ) {
								$replacement = $property->get_page_url();
							} elseif ( $value === 'photo' && $subset ) {
								$replacement = $property->get_thumbnail_url( $subset );
							} elseif ( $value === 'url' && $subset ) {
								$replacement = $photos[ $subset ] ?? '';
							}
							break;
						case 'flags':
							$replacement = '<button type="button" data-vestor-favorite="' . $property->ID() . '" class="vf-favorite-toggle-btn"><span class="toggle-favorite"></button>';
							if ( ! empty( $flags['vf'] ) ) {
								$value = $property->get_vestorfilter( $flags['vf'] );
								if ( $value && $value != 'Yes' ) {
									$replacement .= '<span class="vf-property-block__vf vf-property-block__vf--' . $flags['vf'] . '">' . $value . '</span>';
								}
							}
							break;
						case 'image':
							if ( strpos( $value, '//' ) === false ) {
								$replacement = $image_url . $value;
							} else {
								$replacement = $value;
							}
							break;
						default:
							//do nothing;
					}
				}
				if ( ! is_null( $replacement ) ) {
					$html = str_replace( $replace, $replacement, $html );
					$tagcache[ $tag[1] ] = $replacement;
				}
			}
		}

		return $html;

	}
	*/
	public static function make_page_url( $mls_id, $slug = null ) {

		$url = self::base_url() . $mls_id;
		if ( $slug ) {
			$url .= '/' . $slug;
		}

		return trailingslashit( $url );

	}

	public function get_thumbnail_url( $absolute = true ) {

		if ( empty( $this->photos ) ) {
			return null;
		}

		if ( strpos( $this->photos[0]->thumbnail, '//' ) !== false ) {
			return $this->photos[0]->thumbnail;
		}

		if ( $absolute ) {
			$path = VF_IMG_URL;
			$path = untrailingslashit( $path );
		} else {
			$path = '';
		}

		if ( $this->photos[0]->thumbnail && ! empty( $this->photos[0]->tiny ) ) {
			$url = $path . '/' . $this->photos[0]->thumbnail;
		} else {
			$url = str_replace( 'http://', 'https://', $this->photos[0]->url );
		}

		return $url;

	}

	public function get_thumbnail_datauri() {

		if ( empty( $this->photos ) ) {
			return $this->get_thumbnail_url();
		}

		$filename = $this->photos[0]->tiny;
		if ( ! empty( $filename ) ) {

			if ( defined( 'VF_IMG_PATH' ) ) {
				$path = VF_IMG_PATH;
			} else {
				$path = wp_upload_dir( 'vf' );
				$path = $path['path'];
			}
			$path = untrailingslashit( $path );

			if ( file_exists( $path . '/' . $filename ) || is_link( $path . '/' . $filename ) ) {
				$contents = file_get_contents( $path . '/' . $filename );
				if ( ! empty( $contents ) ) {
					$contents = 'data:image/jpeg;base64,' . base64_encode( $contents );
					return $contents;
				}
			}
		}

		return $this->get_thumbnail_url();

	}
	public function get_price( $format = 'value' ) {

		$value = $this->get_prop( 'price' );
		if ( $format === 'html' ) {
			// $value = number_format( (float) $value, 0 );
			$value = '<span class="currency__unit">' . __( '$', 'vestorfilter' ) . '</span><span class="currency__value">' . $value . '</span>';
		}

		return $value;

	}

	public function get_data( $key = null ) {

		if ( empty( $this->data ) ) {
			$this->data = Cache::get_data( $this->property_id );
		}

		return empty( $key ) ? $this->data : ( $this->data[ $key ] ?? null );

	}

	public function get_photos() {

		if ( empty( $this->photos ) ) {

			$path = untrailingslashit( VF_IMG_URL );

			$this->photos = $this->source->get_photos( $this->ID() );

			if ( ! empty( $this->photos ) ) {
				foreach ( $this->photos as &$photo ) {
					if ( empty( $photo->alt ) ) {
						$photo->alt = $this->get_meta( 'address_line1' ) . ' (1 of ' . count( $this->photos ) . ')';
					}
					if ( strpos( $photo->url, '//' ) === false ) {
						$photo->url = $path . '/' . $photo->url;
					}
				}
			}
		}

		return $this->photos;
	}

	public function get_photo_count() {

		if ( is_null( $this->photos ) ) {
			$this->get_photos();
		}

		return count( $this->photos );
	}

	public function has_photos() {
		return $this->get_photo_count() > 0;
	}

	public function get_geo() {
		if ( ! empty( $this->geo ) ) {
			return $this->geo;
		}
		global $vfdb;
		$geo_table = Location::$geo_name;
		$this->geo = $vfdb->get_row( $vfdb->prepare( "SELECT * FROM `$geo_table` WHERE `property_id` = %d", $this->ID() ) );
		return $this->geo;
	}

	public function find_maps() {

		$geo = $this->get_geo();
		if ( empty( $geo ) ) {
			return [];
		}
		$state      = $this->get_prop( 'address_state' );
		$city       = $this->get_prop( 'address_city' );
		$potentials = Location::find_maps_nearby(
			[
				'state' => $state,
				'city'  => $city,
				'lat'   => $geo->lat,
				'lon'   => $geo->lon,
			],
			true
		);

		$maps = [];
		if ( ! empty( $potentials ) ) {
			foreach ( $potentials as $map ) {
				$coords = json_decode( $map->vectors, true );
				if ( Location::is_geocoord_in_map( $geo, $coords ) ) {
					$maps[ $map->ID ] = $map;
				}
			}
		}

		return $maps;

	}

	public function filter_city( $value ) {

		$state = $this->get_prop( 'address_state' );
		if ( empty( $state ) || empty( $value ) ) {
			return $value;
		}
		if ( strrpos( $value, $state ) !== false ) {
			return $value;
		}
		return $value . ', ' . $state;

	}

	public function filter_county( $value ) {

		if ( $comma = strpos( $value, ',' ) ) {
			return substr( $value, 0, $comma );
		}
		return $value;

	}

	public function filter_zip( $value ) {

		if ( strlen( $value ) > 5 ) {
			return substr( $value, 0, 5 );
		}
		return $value;

	}

	public function get_street_address() {

		if ( $this->get_prop( 'address_yn' ) !== true ) {
			return '';
		}

		$line1 = trim( $this->get_prop( 'address_line1' ) );

		return $line1;

	}

	public function get_address_string( $for_map = false ) {

		$address_yn = $this->get_prop( 'address_yn' );

		if ( $address_yn === true ) {
			$line1 = trim( $this->get_prop( 'address_line1' ) );
			$line2 = trim( $this->get_prop( 'address_line2' ) );
			if ( $for_map && substr( $line1, 0, 1 ) === '0' ) {
				$line1 = trim( substr( $line1, 1 ) );
			}
		}
		$city  = trim( $this->get_prop( 'address_city' ) );
		$state = trim( $this->get_prop( 'address_state' ) );
		$zip   = trim( $this->get_prop( 'address_zip' ) );

		$address = [];
		if ( ! empty( $line1 ) ) {
			$address[] = $line1;
		}
		if ( ! empty( $line2 ) ) {
			$address[] = $line2;
		}
		if ( ! empty( $city ) ) {
			$address[] = $city;
		}
		if ( ! empty( $zip ) ) {
			$address[] = $zip;
		}

		$string = implode( ', ', $address );

		return $string;

	}

	public function get_address_html() {

		$address_yn = $this->get_prop( 'address_yn' );

		if ( $address_yn === true ) {
			$line1 = $this->get_prop( 'address_line1' );
			$line2 = $this->get_prop( 'address_line2' );
		}
		$city  = $this->get_prop( 'address_city' );
		$state = $this->get_prop( 'address_state' );
		$zip   = $this->get_prop( 'address_zip' );

		$html = '';
		if ( ! empty( $line1 ) ) {
			$html .= sprintf( '<span class="address__line1" itemprop="streetAddress">%s</span>', esc_html( $line1 ) );
		}
		if ( ! empty( $line2 ) ) {
			$html .= sprintf( '<span class="address__line2" itemprop="streetAddress">%s</span>', esc_html( $line2 ) );
		}
		if ( ! empty( $city ) ) {
			$html .= sprintf( '<span class="address__city" itemprop="addressLocality">%s</span>', esc_html( $city ) );
		}
		// todo: In RMLS, City seems to include the state (so dumb!) so this may need to be smartly processed
		/*
		if ( ! empty( $state ) ) {
			$html .= sprintf( '<span class="address__state" itemprop="addressRegion">%s</span>', esc_html( $state ) );
		}*/
		if ( ! empty( $zip ) ) {
			$html .= sprintf( '<span class="address__zip" itemprop="postalCode">%s</span>', esc_html( $zip ) );
		}

		$html = sprintf( '<span class="address" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">%s</span>', $html );

		$html = apply_filters( 'vestorfilter_property_address_html', $html, $this );

		return $html;

	}

	public static function make_slug( $data, $source ) {

		$address_yn = $data['address_yn']    ?? $data['InternetAddressDisplayYN'] ?? 'Yes';
		$address    = $data['address_line1'] ?? $data['FullStreetAddress'] ?? '';
		$city       = $data['address_city']  ?? $data['City'] ?? '';
		$state      = $data['address_state'] ?? $data['State'] ?? '';
		$zip        = $data['address_zip']   ?? $data['ZipCode'] ?? '';

		if ( $address_yn === 'No' || $address_yn === 'N' ) {
			$address = '';
		}
		if ( ! empty( $state ) && strpos( $city, $state ) === false ) {
			$city .= " $state";
		}

		$string_together = sanitize_title( trim( $address . ' ' . $city . ' ' . $zip ) );
		return $string_together;

	}

	public function is_favorite( $user = null ) {

		if ( empty( $user ) ) {
			$user_id = get_current_user_id();
		} elseif ( is_numeric( $user ) ) {
			$user_id = $user;
		} elseif ( is_object( $user ) ) {
			$user_id = $user->ID ?? null;
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		return Favorites::is_property_user_favorite( $this->ID(), $user_id );

	}

	public function get_raw_meta( $key, $single = true ) {

		return $this->meta[ $key ] ?? null;

	}

	public function get_meta( $key, $single = true ) {

		if ( $this->upgraded ) {
			$value = $this->meta[ $key ] ?? null;
            if($key == 'cap') {
                $value = $this->original_meta['CAPRate'];
            }
		} else {
			$value = self::data_map( $key, $this->meta );
		}

		return $value;

	}

	public function get_formatted_meta( $key, $unit = false, $land = false ) {

		$template = self::get_field_template( $key );

        if($key == 'bathrooms') {
            $value = $this->get_mls_data('unit_bathrooms');
        } else if($key == 'lot') {
            $value = $this->get_mls_data('lot');
        } else {
            $value = $this->get_meta($key);
        }

		if ( ! empty( $template['filter'] ) ) {
            if(!$land) {
                $value = call_user_func([$this, $template['filter']], $value, $template, $unit);
            }
		} elseif ( ! empty( $template['round'] ) && is_numeric( $value ) ) {
			$value = number_format( $value, $template['round'] );
		}

		if ( empty( $value ) ) {
			return null;
		}

		$value = self::format_meta( $value, $key );

		$format = $template['format'] ?? '%s';
		if ( is_callable( $format ) ) {
			$format = call_user_func( $format, $value, $template, $unit );
		} elseif ( is_callable( [ $this, $format ] ) ) {
			$format = call_user_func( [ $this, $format ], $value, $template );
		}

		$output = sprintf( $format, $value );

		$output = apply_filters( 'vestorfilter_data_formatted', $output, $key, $value, $this, $template );

		$output = apply_filters( 'vestorfilter_data_formatted__' . $key, $output, $value, $this, $template );

		return $output;

	}

	public function get_prop( $key, $raw = false ) {

		$value = $this->get_meta( $key );

		if ( is_array( $value ) ) {
			$value = $value[0];
		}

		if ( ! $raw ) {

			$template = self::get_field_template( $key );
			if ( ! empty( $template['filter'] ) ) {
				$value = call_user_func( [ $this, $template['filter'] ], $value, $template );
			}
		}

		return $value;

	}

	public function get_index( $tax_slug ) {

		if ( empty( $this->index[ $tax_slug ] ) ) {
			return [];
		}

		return $this->index[ $tax_slug ];

	}


	public static function data_map( $field, $values ) {

		if ( empty( self::$fields_available[ $field ] ) ) {
			return false;
		}

		$key = self::$fields_available[ $field ]['key'];

		$value = $values[ $key ] ?? null;

		if ( is_array( $value ) ) {
			return $value[0];
		}

		return $value;
	}

	public static function base_url() {

		$base_url = wp_cache_get( 'property_url', 'vestorfilter' );
		if ( ! empty( $base_url ) ) {
			return $base_url;
		}

		$property_template = Settings::get_page_template( 'single_property' );
		if ( empty( $property_template ) ) {
			return false;
		}
		$property_template = get_post( $property_template );

		$base_url = trailingslashit( get_bloginfo( 'url' ) ) . $property_template->post_name . '/';
		wp_cache_set( 'property_url', $base_url, 'vestorfilter' );

		return $base_url;

	}

	public static function get_label( $field ) {

		if ( empty( self::$fields_available[ $field ] ) ) {
			return '';
		}

		$label = self::$fields_available[ $field ]['label'] ?? ucwords( $field );
		return $label;

	}

	public static function get_field_template( $field ) {

		if ( empty( self::$fields_available[ $field ] ) ) {
			return '';
		}

		$template = self::$fields_available[ $field ];
		if ( empty( $template['type'] ) ) {
			$template['type'] = 'string';
		}
		if ( empty( $template['format'] ) ) {
			$template['format'] = '%s';
		}
		if ( empty( $template['label'] ) ) {
			$template['label'] = self::get_label( $field );
		}
		if ( empty( $template['icon'] ) ) {
			$template['icon'] = $field;
		}
		$template['icon'] = 'data-' . $template['icon'];

		return $template;

	}

	public function get_next_openhouse() {

		if ( empty( $this->meta['open_houses'] ) ) {
			return null;
		}
		$meta = $this->meta['open_houses'];
		if ( is_array( $meta ) ) {
			$meta = $meta[0];
		}

		$oh = maybe_unserialize( $meta );

		if ( is_array( $oh ) && count( $oh ) === 2 ) {
			return $oh;
		}

		$oh_data = json_decode( $meta, true );
		if ( empty( $oh_data ) ) {
			return null;
		}

		foreach ( $oh_data as $oh ) {
			if ( time() < strtotime( $oh['End'] ) ) {
				return [ $oh['Start'], $oh['End'] ];
				break;
			}
		}

		return null;

	}

	public function process_location( $index_location = true, $find_maps = true, $update_count = true ) {

		if ( $index_location ) {

			Location::clear_index( $this->ID() );

			$fields = [
				[
					'key'   => 'city',
					'field' => 'address_city'
				],
				[
					'key'   => 'state',
					'field' => 'address_state'
				],
				[
					'key'   => 'county',
					'field' => 'county'
				],
				[
					'key'   => 'zip',
					'field' => 'address_zip'
				],
				[
					'key'   => 'school',
					'field' => 'school_elementary'
				],
				[
					'key'   => 'school',
					'field' => 'school_middle'
				],
				[
					'key'   => 'school',
					'field' => 'school_high'
				],
				[
					'key'   => 'school',
					'field' => 'school_district'
				],
			];

			$fields = apply_filters( 'vestorfilter_location_fields', $fields );

			foreach ( $fields as $index ) {

				$type  = $index['key'];
				$field = $index['field'];

				$value = $this->get_prop( $field );
				$value = ucwords( strtolower( $value ) );

				if ( $type === 'county' && strpos( $value, ',' ) === false ) {
					$state = $this->get_prop( 'address_state' );
					if ( $state ) {
						$value .= ', ' . strtoupper( $state );
					}
				}

				if ( $value ) {
					$value = trim( $value );
					$id    = Location::add_location( $type, $value );
					if ( $id ) {
						Location::add_index( $id, $this->ID(), false, $update_count );
					}
				}
			}
				
			$lat = $this->get_meta( 'latitude' );
			$lon = $this->get_meta( 'longitude' );
			if ( is_array( $lat ) ) {
				$lat = $lat[0];
			}
			if ( is_array( $lon ) ) {
				$lon = $lon[0];
			}
			if ( $lat && $lon ) {
				$type = $this->get_index( 'property-type' ) ?: null;
				if ( is_array( $type ) ) {
					$type = current( $type );
				}
				Location::update_property_geo( $this->ID(), $lat, $lon, $type );
			} elseif ( Settings::get( 'geocoding_api' ) && Settings::get( 'upgrade_empty_coords' ) ) {

				$coords = Location::find_address_coords( $this->get_address_string() );
				if ( $coords ) {
					$type = $this->get_index( 'property-type' ) ?: null;
					if ( is_array( $type ) ) {
						$type = current( $type );
					}
					Location::update_property_geo( $this->ID(), $coords[0], $coords[1], $type );
				}

			}

			if ( $find_maps ) {
				$maps = $this->find_maps();
				if ( ! empty( $maps ) ) {
					foreach ( $maps as $map ) {
						if ( $map->location_id ) {
							Location::add_index( $map->location_id, $this->ID(), false, $update_count );
						}
					}
				}
			}
		}

		$address = $this->get_address_string();
		Cache::update_address( $this->property_id, $address );

	}

	public static function sanitize_address_string( $value ) {

		$value = ' ' . strtolower( $value ) . ' ';

		$directions = [
			'n'  => 'north',
			'nw' => 'northwest',
			'w'  => 'west',
			'sw' => 'southwest',
			's'  => 'south',
			'se' => 'southeast',
			'e'  => 'east',
			'ne' => 'northeast',
		];
		foreach ( $directions as $was => $now ) {
			$value = str_replace( [ " $was ", " $was,", " $was." ], " $now ", $value );
		}

		$suffixes = [
			'st'   => 'street',
			'cir'  => 'circle',
			'ave'  => 'avenue',
			'blvd' => 'boulevard',
			'hwy'  => 'highway',
			'ct'   => 'court',
			'dr'   => 'drive',
			'rd'   => 'road',
			'ln'   => 'lane',
			'pl'   => 'place',
		];
		foreach ( $suffixes as $was => $now ) {
			$value = preg_replace( '/\b' . $was . '\b/', $now, $value );
		}

		$value = preg_replace( '/[^a-z0-9 ]/', '', $value );

		$value = str_replace( '  ', ' ', $value );

		return trim( $value );

	}

	public function process_text() {

		$fields = [ 'description' ];

		foreach ( $fields as $key ) {

			$value = $this->get_meta( $key );
			Cache::update_text( $this->property_id, $key, $value );

		}

	}

	public function process_type( $types = null, $tax = null ) {

		if ( empty( $types ) ) {
			$types = Data::get_property_types();
		}

		if ( empty( $tax ) ) {
			$tax = Cache::find_taxonomy_by( 'name', 'Property Type' );
			if ( empty( $tax ) || ! is_object( $tax ) ) {
				return;
			}
		}

		$cat_field  = $this->source->get_meta( 'category_field', 'PropertyCategory' );
		$type_field = $this->source->get_meta( 'type_field', 'PropertyType' );

		$types_found = [];

		foreach ( $types as $type => $label ) {

			$rules = $this->source->get_meta( 'status_conditions_' . $type );
			if ( empty( $rules ) ) {
				continue;
			}
			$rules = maybe_unserialize( $rules );

			$categories = empty( $rules['categories'] ) ? null : explode( ',', trim( $rules['categories'] ) );
			$types      = empty( $rules['types'] ) ? null : explode( ',', trim( $rules['types'] ) );
			$query      = empty( $rules['query'] ) ? null : explode( ',', trim( $rules['query'] ) );

			$in_cat      = empty( $categories );
			$in_type     = empty( $types );
			$query_match = empty( $query );

			if ( $in_cat && $in_type && $query_match ) {
				// don't parse an empty ruleset.
				continue;
			}

			$prop_cat_value = $this->original_meta[ $cat_field ] ?? null;
			if ( is_array( $prop_cat_value ) ) {
				$prop_cat_value = $prop_cat_value[0];
			}
			if ( ! empty( $prop_cat_value ) && ! empty( $categories ) ) {
				$exclusive = false;
				if ( substr( $categories[0], 0, 1 ) === '!' ) {
					$in_cat    = true;
					$exclusive = true;
				}
				$prop_cat_value = strtolower( $prop_cat_value );
				foreach ( $categories as $value ) {
					$value = trim( $value, '! ' );
					$value = strtolower( $value );
					if ( $prop_cat_value === $value ) {
						$in_cat = ! $exclusive;
					}
				}
			}

			$prop_type_value = $this->original_meta[ $type_field ] ?? null;
			if ( is_array( $prop_type_value ) ) {
				$prop_type_value = $prop_type_value[0];
			}
			if ( ! empty( $prop_type_value ) && ! empty( $types ) ) {
				$exclusive = false;
				if ( substr( $types[0], 0, 1 ) === '!' ) {
					$in_type   = true;
					$exclusive = true;
				}
				$prop_type_value = strtolower( $prop_type_value );
				foreach ( $types as $value ) {
					$value = trim( $value, '! ' );
					$value = strtolower( $value );
					if ( $prop_type_value === $value ) {
						$in_type = ! $exclusive;
					}
				}
			}

			if ( ! empty( $query ) ) {
				$match_fail = false;
				foreach ( $query as $expression ) {
					$split = explode( '=', $expression );
					if ( count( $split ) != 2 ) {
						continue;
					}
					$not      = substr( $split[0], -1, 1 ) === '!';
					$key      = trim( $split[0], ' !' );
					$match_to = strtolower( trim( $split[1] ) );
					if ( empty( $this->original_meta[ $key ] ) ) {
						if ( $not ) {
							continue;
						} else {
							$match_fail = true;
							break;
						}
					}
					$value = $this->original_meta[ $key ];
					if ( is_array( $value ) ) {
						$value = $value[0];
					}
					$value = strtolower( trim( $value ) );
					if ( ( $match_to === $value && $not ) || ( $match_to !== $value && ! $not ) ) {
						$match_fail = true;
						break;
					}
				}
				if ( ! $match_fail ) {
					$query_match = true;
				}
			}

			if ( $in_cat && $in_type && $query_match ) {
				$types_found[] = $type;
			}
		}

		Cache::clean_property_index( $this->property_id, $tax );

		if ( ! empty( $types_found ) ) {

			Cache::delete_data( $this->property_id, 'hidden' );
			foreach ( $types_found as $type ) {
				Cache::add_index( $this->property_id, $tax, $type, true );
			}
			Cache::update_property( $this->property_id, [ 'hidden' => 0 ] );
			$this->hidden = 0;

		} else {

			Cache::delete_data( $this->property_id, 'hidden' );
			Cache::add_data( $this->property_id, 'hidden', 1 );
			Cache::update_property( $this->property_id, [ 'hidden' => 1 ] );
			$this->hidden = 1;
		}

		$this->types = $types_found;

		$indexes     = Cache::get_indexes( $this->property_id, true );
		$this->index = $indexes;

	}

	public function rebuild_meta() {

		$data = $this->source->convert_incoming_data( $this->original_meta );

		foreach( $data as $key => $value ) {
			if ( empty( $value ) ) {
				$this->source->delete_meta( $this->property_id, $key );
			} else {
				$this->source->update_meta( $this->property_id, $key, is_array( $value ) ? maybe_serialize( $value ) : $value );
			}
		}

	}

	public function process_filters( $metapoints, $indexpoints, $get_columns = false ) {

		// $metapoints = apply_filters( 'vestorfilter_process_data__meta', $metapoints, $this );
		// $indexpoints = apply_filters( 'vestorfilter_process_data__index', $indexpoints, $this );

		if ( ! $get_columns ) {
			$photo_count = $this->source->get_photo_count( $this->ID() );
			Cache::update_data( $this->property_id, 'photos', $photo_count );

			$values = [ 'photos' => $photo_count ];
		} else {
			$values = [];
		}

		$columns = self::standard_columns();

		foreach ( $metapoints as $key ) {

			$value    = $this->get_meta( $key );
			$template = self::get_field_template( $key );

			if ( ! empty( $template['process_filter'] ) ) {
				$value = call_user_func( [ $this, $template['process_filter'] ], $value, $template, $key );
			}
			if ( $template['type'] === 'date' ) {
				$value = strtotime( $value );
			}

			$value = apply_filters( 'vestorfilter_after_process_meta_' . $key, $value, $template, $this );

			if ( is_numeric( $value ) ) {
				Cache::update_data( $this->property_id, $key, $value * 100 );
			} else {
				Cache::delete_data( $this->property_id, $key );
			}

			if ( in_array( $key, $columns ) ) {
				$values[ $key ] = $value;
			}
		}

		Cache::clean_index( $this->property_id );

		$this->status = [];
		foreach ( $indexpoints as $key => $taxonomy ) {
			$template = self::get_field_template( $key );
			$value    = $this->get_meta( $key );
			if ( $template['type'] === 'date' ) {
				$value = strtotime( $value );
			}
			if ( ! empty( $value ) ) {
				$index_id = Cache::add_index( $this->property_id, $taxonomy->name, $value );
				$value_id = Cache::find_value( $taxonomy->ID, $value );
				if ( $key === 'status' ) {
					$this->status[] = $value;
				}
				if ( ! empty( $value_id ) ) {
					Cache::update_data( $this->property_id, $key, $value_id );
				}
			}
		}

		Cache::update_property( $this->property_id, $values );

		foreach ( Filters::get_all() as $key ) {

			$value = Filters::get_value( $this, $key );

			if ( is_numeric( $value ) ) {
				Cache::update_data( $this->property_id, $key, $value * 100 );
			} else {
				Cache::delete_data( $this->property_id, $key );
			}
		}

		$indexes     = Cache::get_indexes( $this->property_id, true );
		$this->index = $indexes;

		return $values;

	}

	public function process_rules( $rules_string = null ) {

		if ( empty( $rules_string ) ) {
			return;
		}

		$rules = explode( "\n", $rules_string );

		foreach ( $rules as $rule ) {
			$rule    = trim( $rule );
			$ruleset = explode( '//', $rule );

			if ( count( $ruleset ) != 2 ) {
				continue;
			}
			$conditions = explode( ';;', $ruleset[0] );
			$dowhat     = explode( ';;', $ruleset[1] );
			$matched    = 0;

			foreach ( $conditions as $condition ) {
				$query = explode( '=', $condition, 2 );
				if ( count( $query ) < 2 ) {
					break;
				}
				$value = $this->original_meta[ $query[0] ] ?? null;
				if ( is_array( $value ) ) {
					$value = $value[0];
				}

				if ( empty( $value ) ) {
					break;
				}

				$values = explode( '||', $query[1] );
				foreach ( $values as $val ) {
					if ( stripos( $value, $val ) !== false ) {
						$matched++;
						break;
					}
				}
			}

			if ( count( $conditions ) === $matched ) {

				foreach ( $dowhat as $action ) {

					$assignment = explode( '=', $action, 2 );
					if ( count( $assignment ) < 2 ) {
						break;
					}
					$keyset = explode( ':', $assignment[0] );
					if ( count( $keyset ) < 2 ) {
						break;
					}
					switch ( $keyset[0] ) {

						case 'tax':
							$taxonomy = Cache::find_taxonomy_by( 'slug', $keyset[1] );
							if ( $taxonomy ) {
								Cache::clean_property_index( $this->property_id, $taxonomy );
								$index_id = Cache::add_index( $this->property_id, $taxonomy->name, $assignment[1] );
								$value_id = Cache::find_value( $taxonomy->ID, $assignment[1] );

								if ( ! empty( $value_id ) ) {
									Cache::update_data( $this->property_id, $taxonomy->slug, $value_id );
								}
							}
							break;

						case 'data':
							Cache::update_data( $this->property_id, $keyset[1], absint( $assignment[1] ) );
							break;

					}
				}
			}
		}

	}

	public static function get_cache_template() {

		ob_start();

		\VestorFilter\Util\Template::get_part(
			'vestorfilter',
			'cache/block',
			[]
		);

		$block_cache = Hooks\minify_html( ob_get_clean() );

		return $block_cache;

	}

	public static function make_cache( $property_id, $property = null ) {

		if ( empty( $property ) ) {
			$property = new Property( $property_id, true );
		}
		$property->get_photos();

		$block_cache = [];

		$thumbnail = $property->get_thumbnail_url();
		if ( $thumbnail ) {
			$block_cache['photo'] = $thumbnail;
		}
		$compliance_text       = $property->get_source()->get_compliance_line( $property->get_office_name() );
		//$compliance_source     = $property->get_source()->slug();
		//$compliance_photo_text = $property->get_source()->get_compliance_line_under_photo( $property->get_office_name() );

		$property_type = $property->get_index( 'property-type' );

		if ( in_array( 'land', $property_type ) || in_array( 'commercial', $property_type ) ) {

			$size = $property->get_prop( 'lot' ) ?: $property->get_prop( 'lot_est' );
			$zoning = $property->get_prop( 'zoning' );
			if ( $size ) {
				$block_cache['lot'] = $size;
			}
			if ( $zoning ) {
				$block_cache['zoning'] = $zoning;
			}

			if ( in_array( 'commercial', $property_type ) ) {
				$sqft = $property->get_prop( 'sqft_gross' );
				if ( $sqft ) {
					$block_cache['sqft'] = $sqft;
				}
			}

		} elseif ( in_array( 'mf', $property_type ) ) {

			if ( $units = $property->get_prop( 'units' ) ) {
				$block_cache['units'] = $units;
			}
			if ( $beds = $property->get_prop( 'bedrooms_mf' ) ) {
				$block_cache['beds'] = $beds;
			}
			if ( $sqft = $property->get_prop( 'sqft_mf' ) ) {
				$block_cache['sqft'] = $sqft;
			}

		} elseif ( array_intersect( [ 'condos', 'sf', '55' ], $property_type ) ) {

			if ( $beds = $property->get_prop( 'bedrooms' ) ) {
				$block_cache['beds'] = $beds;
			}
			if ( $bath = $property->get_prop( 'bathrooms' ) ) {
				$block_cache['bath'] = $beds;
			}
			if ( $sqft = $property->get_prop( 'sqft' ) ) {
				$block_cache['sqft'] = $sqft;
			}
		}

		if ( $compliance_text ) {
			$block_cache['comp'] = $compliance_text;
		}

		
		ob_start();

		\VestorFilter\Util\Template::get_part(
			'vestorfilter',
			'cache/page',
			[ 'property' => $property ]
		);

		$page_cache = Hooks\minify_html( ob_get_clean() );

		$data_cache = $property->get_data();

		return [
			'block_cache' => $block_cache,
			'page_cache'  => $page_cache,
			'data_cache'  => $data_cache,
			'property_id' => $property_id,
			'address'     => $property->get_address_string(),
		];

	}

	public static function update_cache( $property_id, $property = null ) {

		global $vfdb;
		
		//print_r($property);exit;

		$update               = self::make_cache( $property_id, $property );
		$update['data_cache'] = json_encode( $update['data_cache'] );
		$update['block_cache'] = json_encode( $update['block_cache'] );
		$update['page_cache'] = null;

		$vfdb->replace( Cache::$cache_table_name, $update );

		return $update;

	}

	public function rebuild_cache( $version = null ) {

		return self::update_cache( $this->ID(), $this );

	}

	public function hide_duplicate( $other_sources ) {

		global $vfdb;

		$address = self::sanitize_address_string( $this->get_address_string() );
		if ( empty( $address ) ) {
			return;
		}

		$words       = explode( ' ', $address );
		$other_words = [];
		foreach ( $words as $i => $word ) {
			if ( strlen( $word ) <= 2 ) {
				$other_words[] = $word;
				unset( $words[ $i ] );
			}
		}
		if ( empty( $words ) ) {
			return;
		}
		$property_table = Cache::$prop_table_name;
		$geo_table = Location::$geo_name;
		$address_table  = Cache::$addr_table_name;

		$type = $this->get_index( 'property-type' ) ?: 'any';
		if ( is_array( $type ) ) {
			$type = current( $type );
		}

		foreach ( $other_sources as $source ) {
			$source_id = $source->ID();
			$query     = $vfdb->prepare(
				"select adr.* from {$address_table} adr
				LEFT JOIN {$property_table} pr ON ( adr.property_id = pr.ID )
				LEFT JOIN {$geo_table} geo ON ( adr.property_id = geo.property_id )
				WHERE pr.post_id = {$source_id} 
				  AND geo.property_type != %s
				  AND MATCH(`full_address`) AGAINST ( '%s' IN BOOLEAN MODE )",
				$type,
				'+' . implode( ' +', $words )
			);
			$results   = $vfdb->get_results( $query );
			foreach ( $results as $result ) {
				$value     = $result->full_address;
				$all_found = true;
				foreach ( $other_words as $word ) {
					if ( strpos( $value, $word ) === false ) {
						$all_found = false;
						break;
					}
				}

				if ( $all_found ) {
					Cache::update_data( $this->property_id, 'hidden', 2 );
					Cache::update_property( $this->property_id, [ 'hidden' => 2 ] );
					$this->hidden = 2;
				}
			}
		}

	}

	public static function rest_process( $request ) {

		$post_id = absint( $request['source'] );
		$offset  = absint( $request['offset'] ?? 0 );
		$limit   = absint( $request['limit'] ?? 100 );

		global $vfdb;

		$query = $vfdb->prepare( 'SELECT * FROM ' . Cache::$prop_table_name . ' LIMIT %d,%d', $offset, $limit );

		$properties = $vfdb->get_results( $query );

		$metapoints  = self::standard_filters();
		$indexpoints = self::standard_indexes();

		foreach ( $properties as $property_row ) {
			$property = new Property( $property_row );
			$property->process_filters( $metapoints, $indexpoints );
			$property->process_type();
		}

		return true;

	}

	public function calculate_price_drop( $value ) {

		$price = $this->get_meta( 'price' );

		return $this->format_price( absint( $price - $value ) );

	}

	public function is_auction( $value ) {

		return stripos( $value, 'auction' ) !== false ? 'Yes' : 'No';

	}

	public static function format_meta( $value, $key ) {

		// $template = self::get_field_template( $key );

		/*
		if ( ! empty( $template['display'] ) ) {

			$display = $template['display'];

			if ( is_callable( $display ) ) {
				$value = call_user_func( $display, $value, $template );
			} elseif ( is_callable( [ get_class( self ), $display ] ) ) {
				$value = call_user_func( [ get_class( self ), $display ], $value, $template );
			}

		}*/

		$value = apply_filters( 'vestorfilters_display_meta__' . $key, $value );

		return $value;

	}

	public function format_price_drop( $value ) {

		// Last Price Drop / Amount say $10,000 drop / Date

		$original = $this->get_meta( 'price_original' );
		$current  = $this->get_meta( 'price' );
		$modified = date( 'm/d/Y', strtotime( $this->get_meta( 'modified' ) ) );

		$change = absint( $current - $value );

		return sprintf(
			'%s $%s on %s<br>Total $%s %s',
			$current < $value ? 'Dropped' : 'Increased',
			$this->format_price( $change ),
			$modified,
			$this->format_price( absint( $current - $original ) ),
			$current < $original ? 'drop' : 'increase'
		);

	}

	public function filter_hoa( $value ) {

		if ( empty( $value ) ) {
			return null;
		}

		$hoa_freq  = $this->get_prop( 'hoa_fee_freq', true );
		$hoa_total = $value;
		switch ( $hoa_freq ) {
			case 'Semi-Annually':
				$hoa_total /= 6;
				break;

			case 'Quarterly':
				$hoa_total /= 3;
				break;

			case 'Annually':
				$hoa_total /= 12;
				break;
		}

		$hoa_total = round( $hoa_total, 2 );

		return $hoa_total;

	}

	public function filter_hoa_freq( $value ) {

		if ( ! empty( $value ) ) {
			return $value;
		}

		$backup_value = self::$fields_available['hoa_fee_freq']['key'];

		if ( ! empty( $backup_value ) ) {
			return $backup_value;
		}

		return null;

	}

	public function format_hoa( $value ) {

		if ( empty( $value ) ) {
			return null;
		}

		$hoa_freq = $this->get_prop( 'hoa_fee_freq', true );
		$hoa_cost = $this->get_prop( 'hoa', true );

		$hoa_cost = $this->format_price( $hoa_cost );

		return sprintf( '$%s / %s', $hoa_cost, $hoa_freq );

	}

	public function format_price( $value ) {

		if ( empty( $value ) ) {
			$value = $this->get_prop( 'price_max', true );
		}

		return is_numeric( $value ) ? number_format( $value ) : $value;
	}

	public function format_dom( $value ) {

		$value = strtotime( $value );
		if ( empty( $value ) ) {
			return false;
		}

		return floor( ( time() - $value ) / 24 / 3600 );
	}

	public function filter_last_updated( $value ) {

		if ( empty( $value ) ) {
			return $this->get_prop( 'modified' );
		}

		return $value;

	}

	public function filter_acres( $value ) {
		$value = ( (int) $value === 0 ) ? false : $value;
		if ( ! $value ) {
			$acres = $this->get_prop( 'lot_est', true );
			if ( preg_match( '/[0-9.,+]*/i', $acres, $value ) ) {
				$unit_acres = ( strpos( $acres, 'SqFt' ) === false );
				$value = (float) str_replace( [ ',', '+' ], '', $value[0] );
				if ( ! $unit_acres ) {
					$value = $value / 42560;
				}
			} else {
				return false;
			}
		}
		return $value;
	}

	public function filter_yn_boolean( $value ) {
		return ( $value !== 'No' && $value !== 'N' );
	}

	/*
	public function filter_status( $value ) {

		if ( strtolower( $value ) !== 'sold' ) {
			return $value;
		}

		$sold_when = $this->get_meta( 'sold_last' );
		if ( empty( $sold_when ) ) {
			return $value;
		}

		$sold_when = strtotime( $sold_when );
		if ( $sold_when < time() - 31536000 ) {
			$value .= ' Last Year';
		}

		return $value;

	}*/

	public function format_school( $value, $template ) {
		if ( $value === 'Other' || empty( $value ) ) {
			return '';
		}
		return $template['label'] . ': ' . $value;
	}

	public function get_multi_prop( $value, $template ) {

		if ( ! in_array( 'mf', $this->index['property-type'] ?? [] ) ) {
			return null;
		}

		$unittypes = $this->original_meta['UnitTypeType'] ?? null;
		if ( empty( $unittypes ) ) {
			return null;
		}
		if ( is_array( $unittypes ) ) {
			$unittypes = $unittypes[0];
		}
		$units = explode( ',', $unittypes );

		$total = 0;
		foreach ( $units as $unit ) {
			$unit = trim( $unit );
			if ( ! is_numeric( $unit ) ) {
				continue;
			}
			$key = 'UnitType' . $unit . $template['key'];
			if ( empty( $this->original_meta[ $key ] ) ) {
				continue;
			}
			$add = $this->original_meta[ $key ];
			if ( is_array( $add ) ) {
				$add = $add[0];
			}
			if ( is_numeric( $add ) ) {
				$total += $add;
			}
		}

		return $total;

	}

	public function get_multi_sqft( $value, $template ) {

		if ( ! in_array( 'mf', $this->index['property-type'] ?? [] ) ) {
			return null;
		}

		if ( empty( $value ) ) {
			$template['key'] = 'SqFt';
			$value           = $this->get_multi_prop( $value, $template );
		}

		return $value;

	}

	public function filter_multi_total( $value, $template, $key ) {

		if ( empty( $value ) ) {
			$value = $this->get_prop( $key . '_mf' );
		}

		if ( empty( $value ) ) {
			$value = $this->get_prop( $key . '_gross' );
		}

		return $value;

	}

	public function get_unit_prop( $value, $template, $unit = false ) {

		if ( empty( $unit ) ) {
			return null;
		}

		$key = 'UnitType' . $unit . $template['key'];
		if ( empty( $this->original_meta[ $key ] ) ) {
			return null;
		}

		return is_array( $this->original_meta[ $key ] ) ? $this->original_meta[ $key ][0] : $this->original_meta[ $key ];

	}

	public function get_price_range( $value ) {

		$price_type = $this->get_prop( 'price_type' );
		if ( $price_type === 'Range Price' ) {
			return sprintf(
				'$%s - %s',
				$this->format_price( $this->get_prop( 'price_min' ) ),
				$this->format_price( $this->get_prop( 'price_max' ) )
			);
		} elseif ( $price_type === 'Auction' ) {
			return 'Auction';
		} else {
			return '$' . $this->format_price( $value );
		}

	}

	public static function standard_columns() {
		return [
			// 'photos',
			// 'hidden',
			'price',
			'modified',
		];
	}

	public static function search_columns() {
		return [
			'photos',
			'hidden',
			'price',
			'modified',
		];
	}

	public static function standard_filters() {

		return apply_filters( 'vestorfilter_standard_filters', [
			'bedrooms',
			'bathrooms',
			'onmarket',
			'offmarket',
			'sold',
			'sqft',
			'lot',
			'price',
			'modified',
			'stories',
			'garage_spaces',
			'hoa',
			'taxes',
			'units',
			'year_built',
			'last_updated',
		] );

	}

	public static function standard_indexes() {

		$indexes = [
			'lot_est' => 'Lot Size',
			'status'  => 'Status',
			'agent'   => 'ListAgentID',
			'office'  => 'ListOfficeID',
		];
		$indexes = apply_filters( 'vestorfilter_standard_indexes', $indexes );

		$return  = [];
		foreach ( $indexes as $key => $name ) {
			$tax = Cache::find_taxonomy_by( 'name', $name );
			if ( empty( $tax ) ) {
				\VestorFilter\Cache::create_taxonomy( [ 'name' => $name ] );
				$tax = Cache::find_taxonomy_by( 'name', $name );
			}
			$return[ $key ] = $tax;
		}

		return $return;

	}

	public static function get_available_fields() {

		return apply_filters( 'vestorfilter_available_fields', self::$fields_available );

	}

	private static $fields_available = [
		'modified'          => [
			'type'   => 'date',
			'key'    => 'DateTimeModified',
			'format' => '%s',
			'label'  => 'Last Updated'
		],
		'photomodified'          => [
			'type'   => 'date',
			'key'    => 'PhotoDateTimeModified',
			'format' => '%s',
			'label'  => 'Photos Last Updated'
		],
		'last_updated'      => [
			'type'           => 'date',
			'key'            => 'DateLastTransaction',
			'format'         => '%s',
			'filter'         => 'filter_last_updated',
			'label'          => 'Last Transaction',
			'process_filter' => 'filter_last_updated',
		],
		'price'             => [
			'type'   => 'currency',
			'key'    => 'PriceCurrentForStatus',
			'format' => '$%s',
			'filter' => 'format_price',
			'label'  => 'Current Price'
		],
		'price_type'        => [
			'type'  => 'string',
			'key'   => 'PriceType',
			'label' => 'Price Type'
		],
		'price_min'         => [
			'type'   => 'currency',
			'key'    => 'PriceMinimum',
			'format' => '$%s',
			'filter' => 'format_price',
			'label'  => 'Minimum Price'
		],
		'price_max'         => [
			'type'   => 'currency',
			'key'    => 'PriceMaximum',
			'format' => '$%s',
			'filter' => 'format_price',
			'label'  => 'Maximum Price'
		],
		'price_range'       => [
			'type'   => 'string',
			'key'    => 'PriceCurrentForStatus',
			'format' => '%s',
			'filter' => 'get_price_range',
			'label'  => 'Current Price',
			'icon'   => 'price',
		],
		'price_original'    => [
			'type'   => 'currency',
			'key'    => 'PriceListOriginal',
			'format' => '$%s',
			'filter' => 'format_price',
			'label'  => 'Original Price',
		],
		'price_drop'        => [
			'type'   => 'currency',
			'key'    => 'PriceListPrevious',
			'format' => 'format_price_drop',
			'label'  => 'Price Change',
		],
		'price_drop_recent' => [
			'type'   => 'currency',
			'key'    => 'PriceListPrevious',
			'format' => 'format_price',
			'label'  => 'Most Recent Price Drop',
		],
		'school_district'   => [
			'type'  => 'string',
			'key'   => 'SchoolDistrict',
			'label' => 'School District',
			'icon'  => 'school',
		],
		'school_elementary' => [
			'type'   => 'string',
			'key'    => 'SchoolElementary',
			'label'  => 'Elementary School',
			'format' => 'format_school',
			'icon'   => 'school',
		],
		'school_middle'     => [
			'type'   => 'string',
			'key'    => 'SchoolMiddle',
			'label'  => 'Middle School',
			'format' => 'format_school',
			'icon'   => 'school',
		],
		'school_high'       => [
			'type'   => 'string',
			'key'    => 'SchoolHigh',
			'label'  => 'High School',
			'format' => 'format_school',
			'icon'   => 'school',
		],
		'county'            => [
			'type'   => 'string',
			'key'    => 'County',
			'label'  => 'County',
			'filter' => 'filter_county',
		],
		'community'        => [
			'type'  => 'string',
			'key'   => 'Community',
			'label' => 'Community or Subdivision',
		],
		'waterfront'        => [
			'type'  => 'string',
			'key'   => 'Waterfront',
			'label' => 'Lake or Waterway',
		],
		'waterfront_yn'     => [
			'type'   => 'string',
			'key'    => 'Waterfront',
			'label'  => 'Is Waterfront?',
			'filter' => 'filter_yn_boolean',
		],
		'address_yn'        => [
			'type'   => 'string',
			'key'    => 'InternetAddressDisplayYN',
			'label'  => 'Display Address',
			'filter' => 'filter_yn_boolean',
		],
		'latitude'          => [
			'type'  => 'string',
			'key'   => 'Latitude',
			'label' => 'Latitude',
		],
		'longitude'         => [
			'type'  => 'string',
			'key'   => 'Longitude',
			'label' => 'Longitude',
		],
		'address_line1'     => [
			'type'  => 'string',
			'key'   => 'FullStreetAddress',
			'label' => 'Street Address',
		],
		'address_city'      => [
			'type'   => 'string',
			'key'    => 'City',
			'label'  => 'City',
			'filter' => 'filter_city',
		],

		'address_state'     => [
			'type'  => 'string',
			'key'   => 'State',
			'label' => 'State',
		],
		'address_zip'       => [
			'type'   => 'string',
			'key'    => 'ZipCode',
			'label'  => 'Zip Code',
			'index'  => 'single',
			'filter' => 'filter_zip',
		],
		'bathrooms'         => [
			'type'           => 'float',
			'key'            => 'BathsTotal',
			'format'         => '%s bathrooms',
			'process_filter' => 'filter_multi_total',
			// 'index'  => true,
		],
		'bedrooms'          => [
			'type'           => 'int',
			'key'            => 'Beds',
			'format'         => '%d bedrooms',
			'process_filter' => 'filter_multi_total',
			// 'index'  => true,
		],
		'sqft'              => [
			'type'           => 'int',
			'key'            => 'SqFtApproximateTotal',
			'label'          => 'Sq. Ft.',
			'format'         => '%d sq. ft',
			'process_filter' => 'filter_multi_total',
		],
		'bathrooms_mf'      => [
			'type'    => 'float',
			'key'     => 'BathsTotal',
			'filter'  => 'get_multi_prop',
			'format'  => '%s total bathrooms',
			'display' => 'replace_halves',
			'icon'    => 'bathrooms',
		],
		'bedrooms_mf'       => [
			'type'   => 'int',
			'key'    => 'BedsTotal',
			'filter' => 'get_multi_prop',
			'format' => '%d total bedrooms',
			'icon'   => 'bedrooms',
			// 'index'  => true,
		],
		'sqft_mf'           => [
			'type'   => 'int',
			'key'    => 'SqFtApproximateBuildingTotal',
			'label'  => 'Sq. Ft.',
			'format' => '%d total sq. ft',
			'filter' => 'get_multi_sqft',
			'icon'   => 'sqft',
		],
		'rent'              => [
			'type'   => 'currency',
			'key'    => 'GrossIncomeActual',
			'format' => '$%s annual income',
			'filter' => 'format_price',
			'icon'   => 'rent',
		],
		'unit_name'         => [
			'type'  => 'string',
			'key'   => 'UnitName',
			'label' => 'Unit Name or No.'
		],
		'unit_bathrooms'    => [
			'type'   => 'float',
			'key'    => 'BathsTotal',
			'filter' => 'get_unit_prop',
			'format' => '%s bathrooms',
			// 'index'  => true,
			'icon'   => 'bathrooms',
		],
		'unit_bedrooms'     => [
			'type'   => 'int',
			'key'    => 'BedsTotal',
			'filter' => 'get_unit_prop',
			'format' => '%d bedrooms',
			'icon'   => 'bedrooms',
			// 'index'  => true,
		],
		'unit_sqft'         => [
			'type'   => 'int',
			'key'    => 'SqFt',
			'label'  => 'Sq. Ft.',
			'format' => '%d sq. ft',
			'filter' => 'get_unit_prop',
			'icon'   => 'sqft',
		],
		'unit_features'     => [
			'type'   => 'multi',
			'split'  => ',',
			'key'    => 'Features',
			'label'  => 'Features',
			'filter' => 'get_unit_prop',
			'icon'   => 'int_features',
		],
		'unit_rent'         => [
			'type'   => 'currency',
			'key'    => 'Rent',
			'label'  => 'Est. Rent',
			'filter' => 'get_unit_prop',
			'icon'   => 'price',
			'format' => 'Rent: $%s',
		],
		'lot'               => [
			'type'   => 'float',
			'key'    => 'Acres',
			'label'  => 'Lot Size',
			'format' => '%s acres',
			'filter' => 'filter_acres',
			'round'  => 2,
		],
		'lot_est'           => [
			'type' => 'string',
			'key'  => 'LotSize',
		],
		'onmarket'          => [
			'type'  => 'date',
			'key'   => 'DateList',
			'label' => 'Date Listed',
		],
		'offmarket'         => [
			'type'  => 'date',
			'key'   => 'DateOffMarket',
			'label' => 'Date Off Market',
		],
		'sold'              => [
			'type'  => 'date',
			'key'   => 'DateClose',
			'label' => 'Date Sold',
			'icon'  => 'shortsale',
		],
		'sold_price'        => [
			'type'   => 'date',
			'key'    => 'PriceClose',
			'label'  => 'Sold Price',
			'format' => '$%s',
			'filter' => 'format_price',
			'icon'   => 'price',
		],
		'dom'               => [
			'type'   => 'date',
			'key'    => 'DateList',
			'label'  => 'Days On Market',
			'filter' => 'format_dom',
			'format' => '%d days on market',
			// 'index'  => true,
		],
		'doffm'             => [
			'type'   => 'date',
			'key'    => 'DateOffMarket',
			'label'  => 'Days Off Market',
			'filter' => 'format_dom',
			'format' => '%d days off market',
			// 'index'  => true,
		],
		'tour_1'            => [
			'type'  => 'url',
			'key'   => 'VideoVirtualTour1URL',
			'label' => 'Online Tour 1',
		],
		'tour_2'            => [
			'type'  => 'url',
			'key'   => 'VideoVirtualTour2URL',
			'label' => 'Online Tour 2',
		],
		'description'       => [
			'type'  => 'string',
			'key'   => 'RemarksPublic',
			'label' => 'Description',
		],
		'condition'         => [
			'type'  => 'string',
			'key'   => 'PropertyCondition',
			'label' => 'Property Condition',
		],
		'taxes'             => [
			'type'   => 'currency',
			'key'    => 'TaxAmount',
			'format' => '$%s',
			'filter' => 'format_price',
			'label'  => 'Property Taxes',
		],
		'hoa'               => [
			'type'           => 'currency',
			'key'            => 'HOAFee',
			'format'         => 'format_hoa',
			'filter'         => 'filter_hoa',
			'label'          => 'HOA Fee',
			'process_filter' => 'filter_hoa',
		],
		'hoa_fee_freq'      => [
			'key'            => 'HOAPaymentFreq',
			'process_filter' => 'filter_hoa_freq',
		],
		'hoa_yn'            => [
			'type'   => 'string',
			'key'    => 'HOAYN',
			'format' => '%s',
			'label'  => "Home Owner's Association Fees",
			'index'  => 'single',
		],
		'foreclosure_yn'    => [
			'type'   => 'string',
			'key'    => 'BankOwnedYN',
			'format' => '%s',
			'label'  => 'Foreclosure?',
			'index'  => 'single',
		],
		'auction_yn'        => [
			'type'   => 'string',
			'key'    => 'PriceType',
			'format' => '%s',
			'filter' => 'is_auction',
			'label'  => 'Auction Sale',
		],
		'shortsale_yn'      => [
			'type'   => 'string',
			'key'    => 'ShortSaleYN',
			'format' => '%s',
			'label'  => 'Short Sale?',
			'index'  => 'single',
		],
		// 'status', 'year_built', 'stories', 'int_features', 'ext_features', 'ext_desc', 'kitchen', 'garage', 'fireplace'
		'status'            => [
			'type'  => 'string',
			'key'   => 'ListingStatus',
			'label' => 'Status',
			'index' => 'single',
		],
		'year_built'        => [
			'type'  => 'string',
			'key'   => 'YearBuilt',
			'label' => 'Year Built',
		],
		'stories'           => [
			'type' => 'int',
			'key'  => 'Stories',
		],
		'int_features'      => [
			'type'  => 'multi',
			'split' => ',',
			'key'   => 'InteriorFeatures',
			'label' => 'Interior Features',
		],
		'ext_features'      => [
			'type'  => 'multi',
			'split' => ',',
			'key'   => 'ExteriorFeatures',
			'label' => 'Exterior Features',
		],
		'ext_desc'          => [
			'type'  => 'multi',
			'split' => ',',
			'key'   => 'ExteriorDescription',
			'label' => 'Exterior Description',
		],
		'kitchen'           => [
			'type'  => 'multi',
			'split' => ',',
			'key'   => 'KitchenAppliances',
			'label' => 'Appliances',
		],
		'garage'            => [
			'type'  => 'string',
			'key'   => 'GarageType',
			'label' => 'Garage',
		],
		'garage_spaces'     => [
			'type'  => 'string',
			'key'   => 'GarageOrParkingSpaces',
			'label' => 'Parking Spaces',
		],
		'fireplace'         => [
			'type' => 'string',
			'key'  => 'FireplaceDescription',
		],
		'units'             => [
			'type'   => 'int',
			'key'    => 'NumberOfUnitsTotal',
			'label'  => 'Units',
			'format' => '%d total units'
		],
		'unit_types'        => [
			'type'  => 'array',
			'label' => 'Unit Object',
			'key'   => 'PropertyUnitTypes',
		],
		'zoning'            => [
			'type'   => 'string',
			'key'    => 'Zoning',
			'label'  => 'Zoning',
			'format' => 'Zoning: %s',
		],
		'sqft_gross'        => [
			'type'   => 'int',
			'key'    => 'SqFtApproximateGross',
			'label'  => 'Gross Sq. Ft',
			'format' => '%d sq. ft',
			'icon'   => 'sqft',
		],
		'cap'               => [
			'type'   => 'float',
			'key'    => 'CapitalizationRate',
			'label'  => 'Capitilization Rate',
			'format' => 'Capitilization Rate: %s',
			'icon'   => 'cap',
		],
		'green'             => [
			'type'  => 'string',
			'key'   => 'GreenEnergySupplementYN',
			'label' => 'Green/Energy Supplement',
			'icon'  => 'energy',
		],
		'hes'               => [
			'type'  => 'string',
			'key'   => 'GreenVerificationHESMetric',
			'label' => 'Home Energy Score',
			'icon'  => 'energy',
		],
		'agent'             => [
			'type'  => 'string',
			'key'   => 'ListAgentID',
			'label' => 'Listing Agent',
		],
		'office'            => [
			'type'  => 'string',
			'key'   => 'ListOfficeID',
			'label' => 'Listing Office',
		],
	];



}
