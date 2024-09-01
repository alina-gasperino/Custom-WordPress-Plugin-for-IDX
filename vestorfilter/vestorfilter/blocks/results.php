<?php

namespace VestorFilter\Blocks;

use VestorFilter\Search as Search;
use VestorFilter\Location;

use \VestorFilter\Util\Icons as Icons;
use \VestorFilter\Util\Template as Template;

class Results extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null, $per_page = null, $error;

	public function install() {

		add_action( 'init', array( $this, 'register' ) );

	}

	function register() {

		add_shortcode( 'vestorsearch-results', array( $this, 'render' ) );

	}

	static function render( $attrs, $content = '', $tag = null ) {

		self::$per_page = absint( $attrs['per_page'] ?? 0 ) ?: 60;

		try {
			Search::do_search();
		} catch ( \Exception $e ) {
			$error = $e->getMessage();
			self::$error = $error;
		}

		if ( ! empty( $error ) || $error = Search::get_error() ) {

			$default_location = \VestorFilter\Settings::get( 'default_location_id' );

			if ( empty( $default_location ) ) {
				return self::get_html( [], [], $error );
			}
	
			$default_location = is_array( $default_location ) ? implode( ',', $default_location ) : $default_location;
	
			$filters = [
				'dynamic'       => true,
				'location'      => $default_location,
				'property-type' => 'all',
				'status'        => 'active',
				'vf'            => 'ppsf',
			];

			Search::do_search( $filters );

			self::$error = $error;
			
		}

		$data = Search::get_map_data();

		Search::sort_by_vf( $data );

		$locations = explode( ',', filter_input( INPUT_GET, 'location', FILTER_SANITIZE_STRING ) );
		$maps = [];

		if ( $locations ) {
			foreach( $locations as $location_id ) {
				$location_id = absint( $location_id );
				$location = \VestorFilter\Location::get( $location_id );
				if ( $location && $location->type === 'neighborhood' ) {
					$map = \VestorFilter\Location::get_location_map( $location->ID );
					if ( $map ) {
						$maps[] = $map->ID;
					}
				}
			}
		}


		return self::get_html( $data, $maps ?? [] );

		/*if ( empty( $_GET['nomap'] ) ) {

			$locations = explode( ',', filter_input( INPUT_GET, 'location', FILTER_SANITIZE_STRING ) );
			$maps = [];
			foreach( $locations as $location_id ) {
				$location_id = absint( $location_id );
				$location = \VestorFilter\Location::get( $location_id );
				if ( $location && $location->type === 'neighborhood' ) {
					$map = \VestorFilter\Location::get_location_map( $location->ID );
					if ( $map ) {
						$maps[] = $map->ID;
					}
				}
			}

		}*/

		//if ( isset( $maps ) ) {

		
		

		/*} else {

			$property_loop = Search::get_results_loop( [
				'per_page' => $per_page,
				'page'     => Search::current_page_number()
			] );

		}*/

		

	}

	static function get_html( $data, $maps = [], $error = false ) {


		
		ob_start(); 

		do_action( 'vestorfilter__block_results_before', $data );

		wp_enqueue_script( 'vestorhouse-property' );
		wp_enqueue_script( 'vestorhouse-map' );

		$loop = Search::get_results_loop( [
			'per_page' => self::$per_page,
			'page'     => Search::current_page_number()
		] );


		?>

		<div class="website-content__results is-map is-full-width">

			<?php Template::get_part( 'vestorfilter', 'search-map', [ 
				'data'  => $data, 
				'maps'  => $maps, 
				'loop'  => $loop,
				'error' => self::$error ?? false,
			] ); ?>

			<?php Template::get_part( 'vestorfilter', 'search-filters', [ 'hide_cards' => true ] ); ?>

		</div>

		<?php
//        if(current_user_can('administrator')) {
//            echo '<pre>';
//            print_r(self::$error);
//            echo '</pre>';
//        }
		do_action( 'vestorfilter__block_results_after', $data );

		return ob_get_clean();

	}


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Blocks\Results', 'init' ) );
