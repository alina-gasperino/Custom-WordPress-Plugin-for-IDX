<?php

namespace VestorFilter\Util;

class Template {

	public static $theme_action_prefix = 'theme';

	private static $instance;

	public function __construct() {}

	public static function init() {

		$self = apply_filters( 'vestorfilter_template_controller', new Template() );

		$self->install();

		do_action( 'vestorfilter_template_init', $self );

		return $self;

	}

	public function install() {

		if ( ! empty( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = $this;

		self::$theme_action_prefix = apply_filters( 'template_action_prefix', self::$theme_action_prefix );

		//add_action( 'wp_body_open', array( $this, 'attach_onpage_js' ) );

	}

	public static function action( $what, $where = '', ...$args ) {

		do_action( self::$theme_action_prefix . '__' . $what, ...$args );

		if ( empty( $where ) ) {
			return;
		}

		do_action( self::$theme_action_prefix . "__{$where}__{$what}", ...$args );

	}

	public function attach_onpage_js() {


		if ( ! apply_filters( 'vestorfilter_attach_js_helpers', true ) ) {
			return;
		}
		
		$contents = "
		( function() {
			if (window.navigator.userAgent.indexOf('MSIE ') === -1 && window.navigator.userAgent.indexOf('Trident/') === -1) {
				document.body.classList.remove('no-js');
				document.body.classList.add('js-ok');
			}
			function checkScrollPosition() {
				let y = window.scrollY || window.pageYOffset || document.body.scrollTop;
				if (y > 0) {
					document.body.classList.remove('at-top');
				} else {
					document.body.classList.add('at-top');
				}
			}
			window.addEventListener( 'scroll', checkScrollPosition );
			checkScrollPosition();
		} )();
		";

		$contents = apply_filters( 'vestorfilter_onpage_js', $contents );

		if ( empty( $contents ) ) {
			return;
		}

		echo '<script type="text/javascript">' . $contents . '</script>';

	}

	public static function get_part( $key, $file, $vars = [] ) {

		$paths = [
			Theme::$current_theme_path . '/' . $key,
			Theme::$parent_theme_path . '/' . $key,
		];

		$paths = apply_filters( 'vestorfilter_template_paths', $paths, $key, $file );
		$paths = apply_filters( 'vestorfilter_template_paths__' . $key, $paths, $file );

		foreach ( $paths as $path ) {

			if ( file_exists( $path . '/' . $file . '.php' ) ) {
				extract( $vars );
				include $path . '/' . $file . '.php';
				return;
			}

		}

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Util\Template', 'init' ) );
