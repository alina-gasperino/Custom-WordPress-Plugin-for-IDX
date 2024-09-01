<?php

namespace VestorFilter\Util;

final class Theme {

	private static $instance;

	public static $parent_theme_path;
	public static $parent_theme_url;

	public static $site_path;
	public static $site_url;

	public static $current_theme_path;
	public static $current_theme_url;

	public static $dist_path;
	public static $dist_url;

	public static $version;

	public static $is_child = false;

	private $package;

	public function __construct() {}

	public static function init() {

		$self = apply_filters( 'vestorfilter_theme_controller', new Theme() );

		$self->install();

		do_action( 'vestorfilter_theme_init', $self );

		return $self;

	}

	public function install() {

		if ( ! empty( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = $this;

		self::$site_path = untrailingslashit( ABSPATH );
		self::$site_url  = untrailingslashit( get_bloginfo( 'url' ) );

		add_action( 'setup_theme', array( $this, 'setup' ) );

	}

	function setup() {

		self::$parent_theme_path = untrailingslashit( get_template_directory() );
		self::$parent_theme_url  = untrailingslashit( get_template_directory_uri() );

		self::$current_theme_path = untrailingslashit( get_stylesheet_directory() );
		self::$current_theme_url = untrailingslashit( get_stylesheet_directory_uri() );

		if ( self::$parent_theme_path !== self::$current_theme_path ) {
			self::$is_child = true;
		}

		self::$dist_path = self::$current_theme_path . '/dist';
		self::$dist_url  = self::$current_theme_url . '/dist';

		$this->load_package_file();

		self::$version = ! empty( $this->package ) && ! empty( $this->package->version )
			? $this->package->version
			: null;

		add_action( 'vestorfilter_theme_installed', array( $this, 'setup' ) );

	}

	private function load_package_file() {

		if ( file_exists( self::$current_theme_path . '/package.json' ) ) {
			$path = self::$current_theme_path . '/package.json';
		} elseif ( file_exists( self::$parent_theme_path . '/package.json' ) ) {
			$path = self::$parent_theme_path . '/package.json';
		} else {
			$this->package = null;
			return;
		}

		$package = json_decode( file_get_contents( $path ) );

		if ( empty( $package ) ) {
			$package = null;
		}

		$this->package = $package;

	}

	public static function instance() {
		return self::$instance;
	}

	public static function embed_asset( $path, $echo = true ) {

		$contents = file_get_contents( self::$dist_path . '/' . $path );

		if ( $echo ) {
			echo $contents;
		}

		return $contents;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Util\Theme', 'init' ) );
