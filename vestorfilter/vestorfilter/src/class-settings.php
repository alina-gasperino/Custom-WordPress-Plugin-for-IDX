<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Settings extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public static $filters = [ 'status' => 'Listing Status' ];

	public function install() {

		add_filter( 'mb_settings_pages', [ $this, 'settings_page' ] );

		add_shortcode( 'vestorsetting', [ $this, 'config_var' ] );

		add_action( 'wp_footer', [ $this, 'footer_scripts' ], 1 );
		add_action( 'wp_head', [ $this, 'analytics' ], 99 );
		add_action( 'wp_footer', [ $this, 'gtm_head' ], 1 );

		add_action( 'mb_settings_page_submit_buttons', [ $this, 'hide_some_meta_boxes' ] );

	}

	public function register_settings() {

		add_option( 'vestorfilter_property_page', '' );
		register_setting( 'vestorfilter_settings', 'vestorfilter_property_page' );
		register_setting( 'vestorfilter_settings', 'vestorfilter_search_page' );

		foreach ( self::$filters as $filter => $label ) {
			register_setting(
				'vestorfilter_settings',
				"vestorfilter_{$filter}_options",
				array( 'sanitize_callback' => [ $this, 'sanitize_filter_options' ] )
			);
		}

	}

	public function config_var( $attrs, $content = '', $tag = null ) {

		if ( empty( $attrs['key'] ) ) {
			return '';
		}

		$value = get_option( 'my_idx_settings' );

		if ( empty( $value ) ) {
			return false;
		}

		return $value[ 'config_' . sanitize_title( $attrs['key'] ) ] ?? '';

	}

	public function sanitize_filter_options( $values ) {

		$valpack = array();
		foreach ( $values as $key => $value ) {
			$valpack[] = $key;
		}

		return $valpack;

	}

	public static function get( $key ) {
		switch ($key) {
			case 'default_location_id':
				$value = get_option('my_idx_options_general')['location_search'];
				break;
			case 'default_results_view':
				$value = get_option('my_idx_options_general')['search_results_view'];
				break;
			default:
				# code...
				break;
		}

		$value = get_option( 'my_idx_settings' );

		if ( empty( $value ) ) {
			return false;
		}

		return $value[ $key ] ?? false;

	}

	public static function get_page_template( $key ) {
		if($key == "search") {
			$value = get_option('my_idx_options_templates')['search_page'];
		}
		else {
			$value = get_option('my_idx_options_templates')[$key];
		}

		if ( empty( $value ) ) {
			return false;
		}

		return $value ?? false;

	}

	public static function get_page_url( $key ) {

		$page_id = self::get_page_template( $key );

		if ( empty( $page_id ) ) {
			return false;
		}

		return get_permalink( $page_id );

	}

	public static function get_filter_options( $key ) {
		switch ($key) {
			case 'status':
				$value = get_option('my_idx_options_filters')['available_status_options'];
				break;
			case 'lot':
				$value = get_option('my_idx_options_filters')['available_lot_sizes'];
				break;
			default:
				$value = get_option('my_idx_options_filters')[$key];
				break;
		}
echo $key;
		return $value;

	}

	function footer_scripts() {

		$scripts = self::get( 'footer_scripts' );
		if ( ! empty( $scripts ) ) {
			echo $scripts;
		}

	}

	function analytics() {

		$id = self::get( 'analytics_id' );
		if ( ! empty( $id ) ) { ?>

			<script async src="https://www.googletagmanager.com/gtag/js?id=<?=esc_attr($id)?>"></script>
			<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag('js', new Date());

				gtag('config', '<?=esc_attr($id)?>');
			</script>

		<?php }

	}

	function gtm_head() {

		$id = self::get( 'gtm_id' );
		if ( empty( $id ) ) {
			return;
		}

		?>

		<script>

		document.addEventListener('DOMContentLoaded', () => {
			/** init gtm after 3500 seconds - this could be adjusted */
			setTimeout(initGTM, 3500);
		});
		document.addEventListener('scroll', initGTMOnEvent);
		document.addEventListener('mousemove', initGTMOnEvent);
		document.addEventListener('touchstart', initGTMOnEvent);
		
		function initGTMOnEvent (event) {
			initGTM();
			event.currentTarget.removeEventListener(event.type, initGTMOnEvent);
		}
		
		function initGTM () {
			if (window.gtmDidInit) {
				return false;
			}
			window.gtmDidInit = true;
			const script = document.createElement('script');
			script.type = 'text/javascript';
			script.async = true;
			script.onload = () => { dataLayer.push({ event: 'gtm.js', 'gtm.start': (new Date()).getTime(), 'gtm.uniqueEventId': 0 }); }
			script.src = 'https://www.googletagmanager.com/gtm.js?id=<?=esc_attr($id)?>';
		
			document.head.appendChild(script);
		}

		</script>

		<?php 
	}
	
	public static function get_aws( $what ) {

		$setting = self::get( 'aws_' . $what );
		if ( $setting ) {
			return $setting;
		}
		switch( $what ) {
			case 'url':
				return AWS_URL;
				break;
			case 'region':
				return AWS_REGION;
				break;
			case 'bucket':
				return AWS_BUCKET;
				break;
		}
		return null;

	}
	

	function general_settings_meta( $meta_boxes ) {

		$prefix = 'vestorfilter_';

		$status_filters = Cache::get_index_values( 'status', '`value` ASC' ) ?: [];
		$status_options = [];
		
		foreach ( $status_filters as $status ) {
			$status_options[ $status->ID ] = $status->value;
		}

		$lot_filters = Cache::get_index_values( 'lot-size' ) ?: [];
		$lot_filters = Data::sort_lot_options( $lot_filters );
		$lot_options = [];
		foreach ( $lot_filters as $lot ) {
			$lot_options[ $lot->ID ] = $lot->value;
		}

		$all_vf = Filters::get_all();
		$vf_fields = [];
		foreach ( $all_vf as $vf_key ) {

			$vf_fields[] = [
				'type' => 'text',
				'id'   => $vf_key,
				'name' => Filters::get_filter_name( $vf_key ),
			];

		}

		$location_options = [];

		$location_types_allowed = apply_filters( 'vestorfilter_allowed_default_locations', [ 'city', 'county', 'zip' ] );
		foreach( $location_types_allowed as $type ) {
			$locations = Location::get_all_data( $type );
			foreach( $locations as $locale ) {
				$location_options[ $locale->ID ] = $locale->value;
			}
		}
	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Settings', 'init' ) );
