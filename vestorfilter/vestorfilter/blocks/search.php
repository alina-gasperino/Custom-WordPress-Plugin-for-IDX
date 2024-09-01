<?php

namespace VestorFilter\Blocks;

use VestorFilter\Filters as Filters;
use \VestorFilter\Data as Data;
use \VestorFilter\Util\Icons as Icons;

class Search {

	private static $instance = null;

	public function __construct() {}

	public static function init() {

		$self = apply_filters( 'vestorfilter_search_block_init', new Search() );
		$self->install();

		return $self;

	}

	public function install() {

		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = $this;

		add_action( 'init', array( $this, 'register' ) );

		do_action( 'vestorfilter_search_block_installed', $this );

	}

	public static function instance() {
		return self::$instance;
	}

	function register() {

		add_shortcode( 'vestorfilter-search', array( $this, 'render' ) );

	}

	static function render( $attrs, $content = '', $tag = null ) {

		ob_start();

		$filters = [];

		if ( ! empty( $attrs['filters'] ) ) {

			$exploded = explode( ',', $attrs['filters'] );
			$types = [];
			foreach ( $exploded as $type ) {
				$split = explode( '=', $type, 2 );
				$key   = $split[0];
				$value = count( $split ) > 1 ? $split[1] : $key;

				$filters[ $key ] = $value;
			}

		}

		//$selected_type     = filter_input( INPUT_GET, 'property_type', FILTER_SANITIZE_STRING ) ?: array_key_first( $types );
		//$selected_location = filter_input( INPUT_GET, 'location', FILTER_SANITIZE_STRING );
		//$selected_filter   = filter_input( INPUT_GET, 'vestor_filter', FILTER_SANITIZE_STRING ) ?: 'ppsf';

		$use_tabs = $attrs['with-tabs'] ?? true;

		\VestorFilter\Util\Template::get_part(
			'vestorfilter',
			'search-cards',
			[
				'show_learn_more' => true,
				'search_only' => isset( $attrs['hide-cards'] ) && $attrs['hide-cards'] === 'yes',
				'default_filters' => $filters,
			],
		);


		return ob_get_clean();

	}


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Blocks\Search', 'init' ) );
