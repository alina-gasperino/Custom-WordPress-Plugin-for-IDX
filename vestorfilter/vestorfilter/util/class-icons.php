<?php

namespace VestorFilter\Util;

class Icons {

	private static $instance;

	private static $sources = array();

	function __construct() {}

	static function init() {

		$self = apply_filters( 'vestorfilter_icons_controller', new Icons() );
		$self->install();

		do_action( 'vestorfilter_icons_init', $self );

		return $self;

	}

	function install() {

		if ( ! empty( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = $this;

		add_shortcode( 'icon', array( $this, 'shortcode' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_sources' ) );

	}

	function setup_sources() {
		self::add_source( 'theme', plugin_dir_path( __FILE__ ) . 'dist/icons', plugin_dir_url( __FILE__ ) . 'dist/icons' );
		self::$sources = apply_filters( 'vestorfilter_icon_sources', self::$sources );
	}

	function add_source( $key, $path, $url ) {
		self::$sources[ $key ] = [ 'path' => $path, 'url' => $url ];
	}

	function shortcode( $atts ) {

		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$id = $atts['id'];

		return self::inline( $id );

	}

	static function url( $filename, $id = null ) {

		$source = self::find_source( $filename );
		if ( empty( $source ) ) {
			return '';
		}

		$icon_url = $source['url'];

		if ( ! empty( $id ) ) {
			$icon_url .= '#' . $id;
		}
		return $icon_url;

	}
	
	static function use( $slug, $classes = '', $width = '', $height = '' ) {
		$classes .= ' vf-use-icon vf-use-icon--' . $slug;
		$classes = trim( $classes );
		if ( ! empty( $width ) ) {
			$width = 'width="' . $width . '"';
		}
		if ( ! empty( $height ) ) {
			$height = 'height="' . $height . '"';
		}
		return sprintf( '<svg %s %s class="%s"><use xlink:href="#%s" /></svg>', $width, $height, $classes, $slug );
	}

	static function inline( $filename ) {

		$source = self::find_source( $filename );

		if ( empty( $source ) ) {
			return '';
		}

		return file_get_contents( $source['path'] );

	}


	static function find_source( $filename ) {

		foreach ( self::$sources as $source ) {

			if ( file_exists( $source['path'] . '/' . $filename . '.svg' ) ) {
				
				$source['path'] .= '/' . $filename . '.svg';
				$source['url']  .= '/' . $filename . '.svg';

				return $source;

			}

		}

		return false;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Util\Icons', 'init' ) );
